<?php
declare(strict_types=1);
final class CalculationEngine
{
    private const LEIS = [
        'SELIC'   => 'Lei 14.905/2024 — Art. 406 CC c/c Art. 39 Lei 4.320/64',
        'IPCA_E'  => 'Tema 810 STF / RE 870.947 — Débitos fazendários e precatórios',
        'INPC'    => 'Lei 8.177/91 — Débitos trabalhistas (TST Precedente 441)',
        'IGP_M'   => 'Contratual — FGV (locação e contratos imobiliários)',
        'CUB_SINDUSCON' => 'ABNT NBR 12721 — Construção Civil / Sinduscon',
        'TR'      => 'Resolução BCB 2.932/2002 — FGTS',
    ];

    public function __construct(private DB $db) {}

    public function calcular(array $params): array
    {
        $valorBase   = (float) $params['valor_base'];
        $dataBase    = new \DateTimeImmutable($params['data_base']);
        $dataCalculo = new \DateTimeImmutable($params['data_calculo']);
        $indice      = $params['indice'] ?? 'SELIC';
        $metodo      = $params['metodo_juros'] ?? 'simples';
        $taxaJuros   = (float) ($params['taxa_juros'] ?? 1.0);
        $temMulta    = (bool)  ($params['tem_multa'] ?? false);
        $pctMulta    = (float) ($params['percentual_multa'] ?? 0);

        if ($dataCalculo < $dataBase) throw new \InvalidArgumentException('Data de cálculo anterior à data base.', 400);
        if ($valorBase <= 0) throw new \InvalidArgumentException('Valor base deve ser positivo.', 400);

        // 1. Correção monetária
        [$valorCorrigido, $fatorTotal, $memoriaCorrecao] = $this->correcao($valorBase, $dataBase, $dataCalculo, $indice);

        // 2. Juros de mora
        $dias   = (int) $dataBase->diff($dataCalculo)->days;
        $meses  = $this->mesesEntre($dataBase, $dataCalculo);
        $juros  = match($metodo) {
            'simples'      => $valorBase * ($taxaJuros / 100) * $meses,
            'composto'     => $valorBase * ((1 + $taxaJuros / 100) ** $meses) - $valorBase,
            'pro_rata_die' => $valorBase * ($taxaJuros / 30 / 100) * $dias,
            default        => 0.0,
        };

        // 3. Multa
        $multa = $temMulta && $pctMulta > 0 ? $valorBase * ($pctMulta / 100) : 0.0;

        $total = $valorCorrigido + $juros + $multa;

        return [
            'valor_base'         => round($valorBase, 2),
            'valor_original'      => round($valorBase, 2),         // alias frontend
            'valor_corrigido'     => round($total, 2),             // total final
            'valor_correcao'      => round($valorCorrigido - $valorBase, 2),
            'correcao_monetaria'  => round($valorCorrigido - $valorBase, 2), // alias
            'valor_juros'         => round($juros, 2),
            'juros_mora'          => round($juros, 2),             // alias
            'fator_acumulado' => round($fatorTotal, 8),
            'valor_juros'     => round($juros, 2),
            'valor_multa'     => round($multa, 2),
            'valor_total'     => round($total, 2),
            'periodo_meses'   => $meses,
            'periodo_dias'    => $dias,
            'indice'          => $indice,
            'metodo_juros'    => $metodo,
            'taxa_juros'      => $taxaJuros,
            'lei_base'        => self::LEIS[$indice] ?? '',
            'memoria_calculo' => $memoriaCorrecao,
            'calculado_em'    => date('Y-m-d H:i:s'),
        ];
    }

    private function correcao(float $valor, \DateTimeImmutable $inicio, \DateTimeImmutable $fim, string $indice): array
    {
        // Normalizar nome do índice para busca no banco
        $indiceDB = match(strtoupper(str_replace(['-','_',' '],'',$indice))) {
            'SELICOVERMETA','SELIC' => 'SELIC',
            'IPCAE','IPCA'         => 'IPCA_E',
            'INPC'                 => 'INPC',
            'IGPM'                 => 'IGP_M',
            default                => $indice,
        };

        $memoria    = [];
        $fatorAcum  = 1.0;
        $cursor     = new \DateTimeImmutable($inicio->format('Y-m-01'));
        $fimMes     = new \DateTimeImmutable($fim->format('Y-m-01'));

        while ($cursor <= $fimMes) {
            $comp = $cursor->format('Y-m-01');
            $taxa = (float) $this->db->scalar(
                "SELECT valor FROM indices_monetarios WHERE indice = ? AND competencia = ?",
                [$indiceDB, $comp]
            );
            if ($taxa > 0) {
                $fator      = 1 + ($taxa / 100);
                $fatorAcum *= $fator;
                $memoria[]  = [
                    'competencia'      => $cursor->format('m/Y'),
                    'mes'              => $cursor->format('m/Y'),
                    'taxa_pct'         => $taxa,
                    'indice_pct'       => $taxa,
                    'fator'            => round($fator, 8),
                    'fator_mes'        => $taxa / 100,
                    'acumulado'        => round($fatorAcum, 8),
                    'saldo'            => round($valor * $fatorAcum, 2),
                    'saldo_inicial'    => round($valor * ($fatorAcum / $fator), 2),
                    'correcao'         => round($valor * ($fatorAcum - $fatorAcum / $fator), 2),
                    'correcao_parcial' => round($valor * ($fatorAcum - $fatorAcum / $fator), 2),
                    'juros'            => 0,
                    'juros_mes'        => 0,
                    'total_periodo'    => round($valor * ($fatorAcum - $fatorAcum / $fator), 2),
                    'total_mes'        => round($valor * ($fatorAcum - $fatorAcum / $fator), 2),
                ];
            }
            $cursor = $cursor->modify('+1 month');
        }

        return [round($valor * $fatorAcum, 2), round($fatorAcum, 8), $memoria];
    }

    private function mesesEntre(\DateTimeImmutable $d1, \DateTimeImmutable $d2): int
    {
        return ((int)$d2->format('Y') - (int)$d1->format('Y')) * 12
             + ((int)$d2->format('m') - (int)$d1->format('m'));
    }

    public function salvar(int $processoId = null, int $userId, string $titulo, array $resultado, array $extra = []): int
    {
        // Normalizar índice para ENUM do banco
        $indiceNorm = match(strtoupper(str_replace(['-',' '],['_','_'], $resultado['indice'] ?? 'SELIC'))) {
            'SELIC'  => 'SELIC',
            'IPCA_E','IPCA' => 'IPCA_E',
            'INPC'   => 'INPC',
            'IGP_M'  => 'IGP_M',
            'TR'     => 'TR',
            default  => 'SELIC',
        };
        return (int) $this->db->insert('calculos', [
            'tenant_id'       => $this->db->getTenantId(),
            'processo_id'     => $processoId ?: null,
            'pericia_id'      => $extra['pericia_id'] ?? null,
            'user_id'         => $userId,
            'titulo'          => $titulo,
            'tipo'            => $extra['tipo'] ?? 'atualizacao_monetaria',
            'metodo_juros'    => $resultado['metodo_juros'],
            'indice_correcao' => $indiceNorm,
            'taxa_juros'      => $resultado['taxa_juros'],
            'valor_base'      => $resultado['valor_base'],
            'data_base'       => $extra['data_base'],
            'data_calculo'    => $extra['data_calculo'],
            'valor_correcao'  => $resultado['valor_correcao'],
            'valor_juros'     => $resultado['valor_juros'],
            'valor_multa'     => $resultado['valor_multa'],
            'valor_total'     => $resultado['valor_total'],
            'memoria_calculo' => json_encode($resultado['memoria_calculo']),
            'lei_aplicada'    => $resultado['lei_base'],
            'observacoes'     => $extra['observacoes'] ?? null,
        ]);
    }

    /** Importa SELIC do BCB */
    public function importarSelic(int $ano, int $mes): bool
    {
        $comp = sprintf('%04d-%02d-01', $ano, $mes);
        $m2   = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
        $url  = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.4189/dados?formato=json&dataInicial=01/{$m2}/{$ano}&dataFinal=28/{$m2}/{$ano}";
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!$resp) return false;
        $data = json_decode($resp, true);
        if (empty($data[0]['valor'])) return false;
        $taxa = (float) str_replace(',', '.', $data[0]['valor']);
        $this->db->run(
            "INSERT INTO indices_monetarios (indice, competencia, valor, lei_base) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
            ['SELIC', $comp, $taxa, 'Lei 14.905/2024']
        );
        return true;
    }

    /** Importa INPC do IBGE */
    public function importarInpc(int $ano, int $mes): bool
    {
        $comp  = sprintf('%04d-%02d-01', $ano, $mes);
        $mesStr = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
        // Série 188 = INPC mensal
        $url  = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.188/dados?formato=json&dataInicial=01/{$mesStr}/{$ano}&dataFinal=28/{$mesStr}/{$ano}";
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => false]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!$resp) return false;
        $data = json_decode($resp, true);
        if (empty($data[0]['valor'])) return false;
        $taxa = (float) str_replace(',', '.', $data[0]['valor']);
        $this->db->run(
            "INSERT INTO indices_monetarios (indice, competencia, valor, lei_base) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
            ['INPC', $comp, $taxa, 'Lei 8.177/91']
        );
        return true;
    }

    /** Importa IGP-M da FGV via BCB */
    public function importarIgpm(int $ano, int $mes): bool
    {
        $comp  = sprintf('%04d-%02d-01', $ano, $mes);
        $mesStr = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
        // Série 189 = IGP-M mensal
        $url  = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.189/dados?formato=json&dataInicial=01/{$mesStr}/{$ano}&dataFinal=28/{$mesStr}/{$ano}";
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => false]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!$resp) return false;
        $data = json_decode($resp, true);
        if (empty($data[0]['valor'])) return false;
        $taxa = (float) str_replace(',', '.', $data[0]['valor']);
        $this->db->run(
            "INSERT INTO indices_monetarios (indice, competencia, valor, lei_base) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
            ['IGP_M', $comp, $taxa, 'FGV']
        );
        return true;
    }

    /** Importa IPCA-E do IBGE */
    public function importarIpcaE(int $ano, int $mes): bool
    {
        $comp = sprintf('%04d-%02d-01', $ano, $mes);
        $url  = "https://servicodados.ibge.gov.br/api/v3/agregados/2938/periodos/{$ano}{$mes}/variaveis/44?localidades=N1[all]";
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!$resp) return false;
        $data = json_decode($resp, true);
        $taxa = (float) ($data[0]['resultados'][0]['series'][0]['serie']["{$ano}{$mes}"] ?? 0);
        if ($taxa === 0.0) return false;
        $this->db->run(
            "INSERT INTO indices_monetarios (indice, competencia, valor, lei_base) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)",
            ['IPCA_E', $comp, $taxa, 'Tema 810 STF']
        );
        return true;
    }
}
