<?php
/**
 * includes/PDF.php
 * Gerador de PDF usando TCPDF
 * Instale: composer require tecnickcom/tcpdf
 */

class PDF {

    private static function tcpdfPath(): string {
        // Procura o TCPDF em locais comuns
        $paths = [
            __DIR__ . '/../tcpdf/tcpdf.php',                       // pasta tcpdf na raiz (PREFERIDO)
            __DIR__ . '/../tcpdf/TCPDF.php',                       // variação maiúscula
            __DIR__ . '/../vendor/tcpdf/tcpdf.php',                // vendor/tcpdf direto
            __DIR__ . '/../vendor/tcpdf/TCPDF.php',                // vendor/tcpdf maiúsculo
            __DIR__ . '/../vendor/tcpdf/src/tcpdf.php',            // vendor/tcpdf/src
            __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',     // via Composer
            __DIR__ . '/../vendor/autoload.php',                    // autoload Composer
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        return '';
    }

    public static function disponivel(): bool {
        return self::tcpdfPath() !== '' || class_exists('TCPDF');
    }

    /**
     * Carrega o TCPDF se disponível
     */
    private static function carregar(): bool {
        if (class_exists('TCPDF')) return true;
        $path = self::tcpdfPath();
        if (!$path) return false;
        require_once $path;
        return class_exists('TCPDF');
    }

    /**
     * Gera PDF de relatório financeiro mensal
     */
    public static function relatorioFinanceiro(array $dados): void {
        if (!self::carregar()) {
            self::erroPdfIndisponivel();
        }

        $pdf = self::criarDoc('Relatório Financeiro — ' . $dados['titulo']);
        $pdf->AddPage();

        // Cabeçalho
        $pdf->writeHTML(self::htmlCabecalho($dados['estabelecimento'], 'RELATÓRIO FINANCEIRO', $dados['titulo']));

        // Cards de resumo
        $pdf->writeHTML(self::htmlCards([
            ['label' => 'Faturamento do Período', 'valor' => 'R$ ' . number_format($dados['fat_total'], 2, ',', '.'), 'cor' => '#f59e0b'],
            ['label' => 'Total de Vendas',         'valor' => $dados['n_vendas'] . ' venda(s)',                        'cor' => '#3b82f6'],
            ['label' => 'Ticket Médio',             'valor' => 'R$ ' . number_format($dados['ticket_medio'], 2, ',', '.'), 'cor' => '#22c55e'],
        ]));

        // Formas de pagamento
        if (!empty($dados['fat_forma'])) {
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-top:16px">Por Forma de Pagamento</h3>');
            $rows = [];
            $labels = ['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro'];
            foreach ($dados['fat_forma'] as $f) {
                $rows[] = [
                    $labels[$f['forma_pagamento']] ?? $f['forma_pagamento'],
                    $f['n'],
                    'R$ ' . number_format($f['total'], 2, ',', '.'),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Forma', 'Qtd', 'Total'], $rows));
        }

        // Top produtos
        if (!empty($dados['top_prods'])) {
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-top:16px">Top Produtos</h3>');
            $rows = [];
            foreach ($dados['top_prods'] as $i => $p) {
                $rows[] = [
                    ($i + 1) . 'º',
                    $p['descricao'],
                    number_format($p['qtd'], 0, ',', '.'),
                    'R$ ' . number_format($p['total'], 2, ',', '.'),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['#', 'Produto', 'Qtd', 'Total'], $rows));
        }

        // Vendas por dia
        if (!empty($dados['fat_dia'])) {
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-top:16px">Vendas por Dia</h3>');
            $rows = [];
            foreach ($dados['fat_dia'] as $d) {
                $rows[] = [
                    date('d/m/Y', strtotime($d['dia'])),
                    $d['n'],
                    'R$ ' . number_format($d['total'], 2, ',', '.'),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Data', 'Vendas', 'Total'], $rows));
        }

        self::rodape($pdf, $dados['estabelecimento']);
        $pdf->Output('relatorio_financeiro_' . date('Y_m') . '.pdf', 'D');
    }

    /**
     * Gera PDF de relatório de estoque
     */
    public static function relatorioEstoque(array $dados): void {
        if (!self::carregar()) {
            self::erroPdfIndisponivel();
        }

        $pdf = self::criarDoc('Relatório de Estoque — ' . $dados['titulo']);
        $pdf->AddPage();

        $pdf->writeHTML(self::htmlCabecalho($dados['estabelecimento'], 'RELATÓRIO DE ESTOQUE', $dados['titulo']));

        // Cards resumo
        $pdf->writeHTML(self::htmlCards([
            ['label' => 'Valor em Estoque (Custo)', 'valor' => 'R$ ' . number_format($dados['valor_estoque'], 2, ',', '.'), 'cor' => '#f59e0b'],
            ['label' => 'Produtos Sem Estoque',      'valor' => $dados['sem_estoque'],                                       'cor' => '#ef4444'],
            ['label' => 'Alertas Estoque Baixo',     'valor' => count($dados['alertas']),                                    'cor' => '#f97316'],
        ]));

        // Alertas
        if (!empty($dados['alertas'])) {
            $pdf->writeHTML('<h3 style="color:#dc2626;border-bottom:2px solid #ef4444;padding-bottom:4px;margin-top:16px">⚠ Estoque Baixo / Zerado</h3>');
            $rows = [];
            foreach ($dados['alertas'] as $a) {
                $rows[] = [
                    $a['nome'],
                    number_format($a['estoque_atual'], 2, ',', '.') . ' ' . $a['unidade_estoque'],
                    number_format($a['estoque_minimo'], 2, ',', '.') . ' ' . $a['unidade_estoque'],
                    $a['estoque_atual'] <= 0 ? 'ZERADO' : 'BAIXO',
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Produto', 'Estoque Atual', 'Mínimo', 'Status'], $rows, '#fef2f2'));
        }

        // Todos os produtos
        if (!empty($dados['produtos'])) {
            $pdf->AddPage();
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-bottom:8px">Posição de Estoque</h3>');
            $rows = [];
            foreach ($dados['produtos'] as $p) {
                $rows[] = [
                    $p['nome'],
                    $p['cat_nome'] ?? '—',
                    $p['tipo'],
                    number_format($p['estoque_atual'], 2, ',', '.'),
                    $p['unidade_estoque'],
                    'R$ ' . number_format($p['preco_custo'], 2, ',', '.'),
                    'R$ ' . number_format($p['estoque_atual'] * $p['preco_custo'], 2, ',', '.'),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Produto', 'Categoria', 'Tipo', 'Estoque', 'Un.', 'Custo Unit.', 'Total'], $rows, null, true));
        }

        self::rodape($pdf, $dados['estabelecimento']);
        $pdf->Output('relatorio_estoque_' . date('Y_m_d') . '.pdf', 'D');
    }

    /**
     * Gera PDF de fechamento de caixa
     */
    public static function fechamentoCaixa(array $dados): void {
        if (!self::carregar()) {
            self::erroPdfIndisponivel();
        }

        $pdf = self::criarDoc('Fechamento de Caixa #' . $dados['caixa']['id']);
        $pdf->AddPage();

        $pdf->writeHTML(self::htmlCabecalho(
            $dados['estabelecimento'],
            'FECHAMENTO DE CAIXA',
            'Caixa #' . $dados['caixa']['id'] . ' — Operador: ' . $dados['caixa']['operador']
        ));

        // Info do caixa
        $cx = $dados['caixa'];
        $esperado = (float)$cx['saldo_inicial'] + (float)$cx['total_vendas'] + (float)$cx['total_suprimentos'] - (float)$cx['total_sangrias'];

        $pdf->writeHTML(self::htmlCards([
            ['label' => 'Abertura',       'valor' => date('d/m/Y H:i', strtotime($cx['data_abertura'])),                  'cor' => '#6b7280'],
            ['label' => 'Fechamento',     'valor' => $cx['data_fechamento'] ? date('d/m/Y H:i', strtotime($cx['data_fechamento'])) : '—', 'cor' => '#6b7280'],
            ['label' => 'Saldo Inicial',  'valor' => 'R$ ' . number_format($cx['saldo_inicial'], 2, ',', '.'),            'cor' => '#3b82f6'],
            ['label' => 'Total Vendas',   'valor' => 'R$ ' . number_format($cx['total_vendas'], 2, ',', '.'),             'cor' => '#f59e0b'],
            ['label' => 'Suprimentos',    'valor' => 'R$ ' . number_format($cx['total_suprimentos'], 2, ',', '.'),        'cor' => '#22c55e'],
            ['label' => 'Sangrias',       'valor' => 'R$ ' . number_format($cx['total_sangrias'], 2, ',', '.'),           'cor' => '#ef4444'],
        ]));

        // Saldo esperado e diferença
        $dif = isset($cx['saldo_final_informado']) ? ((float)$cx['saldo_final_informado'] - $esperado) : null;
        $html = '<table width="100%" style="margin-top:12px;border-collapse:collapse">';
        $html .= '<tr><td style="padding:8px;background:#f8f9fa;font-size:11px">Saldo Esperado</td><td style="padding:8px;text-align:right;font-weight:bold;font-size:14px">R$ ' . number_format($esperado, 2, ',', '.') . '</td></tr>';
        if ($dif !== null) {
            $corDif = abs($dif) < 0.01 ? '#22c55e' : ($dif > 0 ? '#3b82f6' : '#ef4444');
            $txtDif = abs($dif) < 0.01 ? 'Sem diferença ✓' : (($dif > 0 ? '+ R$ ' : '- R$ ') . number_format(abs($dif), 2, ',', '.'));
            $html .= '<tr><td style="padding:8px;background:#f8f9fa;font-size:11px">Saldo Informado</td><td style="padding:8px;text-align:right;font-weight:bold">R$ ' . number_format($cx['saldo_final_informado'], 2, ',', '.') . '</td></tr>';
            $html .= '<tr><td style="padding:8px;background:#f8f9fa;font-size:11px;font-weight:bold">Diferença</td><td style="padding:8px;text-align:right;font-weight:bold;font-size:14px;color:' . $corDif . '">' . $txtDif . '</td></tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html);

        // Formas de pagamento
        if (!empty($dados['fat_forma'])) {
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-top:16px">Por Forma de Pagamento</h3>');
            $labels = ['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro'];
            $rows = [];
            foreach ($dados['fat_forma'] as $f) {
                $rows[] = [$labels[$f['forma_pagamento']] ?? $f['forma_pagamento'], $f['n'], 'R$ ' . number_format($f['total'], 2, ',', '.')];
            }
            $pdf->writeHTML(self::htmlTabela(['Forma', 'Qtd', 'Total'], $rows));
        }

        // Sangrias e Suprimentos
        if (!empty($dados['movimentos'])) {
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-top:16px">Sangrias / Suprimentos</h3>');
            $rows = [];
            foreach ($dados['movimentos'] as $m) {
                $rows[] = [
                    ucfirst($m['tipo']),
                    'R$ ' . number_format($m['valor'], 2, ',', '.'),
                    $m['motivo'] ?? '—',
                    date('H:i', strtotime($m['created_at'])),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Tipo', 'Valor', 'Motivo', 'Hora'], $rows));
        }

        // Listagem de vendas
        if (!empty($dados['vendas'])) {
            $pdf->AddPage();
            $pdf->writeHTML('<h3 style="color:#1e2330;border-bottom:2px solid #f59e0b;padding-bottom:4px;margin-bottom:8px">Vendas do Caixa (' . count($dados['vendas']) . ')</h3>');
            $rows = [];
            $labels = ['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro'];
            foreach ($dados['vendas'] as $v) {
                $rows[] = [
                    $v['numero'],
                    date('H:i', strtotime($v['data_venda'])),
                    $v['mesa'] ?? '—',
                    $labels[$v['forma_pagamento']] ?? $v['forma_pagamento'],
                    'R$ ' . number_format($v['total'], 2, ',', '.'),
                ];
            }
            $pdf->writeHTML(self::htmlTabela(['Nº', 'Hora', 'Mesa', 'Forma', 'Total'], $rows));

            $total_geral = array_sum(array_column($dados['vendas'], 'total'));
            $pdf->writeHTML('<p style="text-align:right;font-weight:bold;font-size:13px;margin-top:8px">Total Geral: R$ ' . number_format($total_geral, 2, ',', '.') . '</p>');
        }

        // Assinaturas
        $pdf->writeHTML('
            <table width="100%" style="margin-top:40px">
                <tr>
                    <td width="45%" style="text-align:center;border-top:1px solid #333;padding-top:6px;font-size:10px">Operador: ' . htmlspecialchars($cx['operador']) . '</td>
                    <td width="10%"></td>
                    <td width="45%" style="text-align:center;border-top:1px solid #333;padding-top:6px;font-size:10px">Responsável / Gerência</td>
                </tr>
            </table>
        ');

        self::rodape($pdf, $dados['estabelecimento']);
        $pdf->Output('fechamento_caixa_' . $cx['id'] . '_' . date('Y_m_d') . '.pdf', 'D');
    }

    // ── Helpers HTML internos ─────────────────────────────────────────────

    private static function criarDoc(string $titulo): object {
        $class = class_exists('\TCPDF') ? '\TCPDF' : 'TCPDF';
        $pdf = new $class('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Bar System Pro');
        $pdf->SetAuthor('Bar System Pro');
        $pdf->SetTitle($titulo);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('dejavusans', '', 9);
        return $pdf;
    }

    private static function htmlCabecalho(string $empresa, string $tipo, string $subtitulo): string {
        return '
        <table width="100%" style="border-bottom:3px solid #f59e0b;padding-bottom:10px;margin-bottom:14px">
            <tr>
                <td width="70%">
                    <span style="font-size:18px;font-weight:bold;color:#1e2330">' . htmlspecialchars($empresa) . '</span><br>
                    <span style="font-size:11px;color:#6b7280">Gerado em: ' . date('d/m/Y H:i') . '</span>
                </td>
                <td width="30%" style="text-align:right">
                    <span style="background:#f59e0b;color:#000;font-weight:bold;font-size:10px;padding:4px 10px;border-radius:4px">' . $tipo . '</span><br>
                    <span style="font-size:10px;color:#374151;margin-top:4px;display:block">' . htmlspecialchars($subtitulo) . '</span>
                </td>
            </tr>
        </table>';
    }

    private static function htmlCards(array $cards): string {
        $w = floor(100 / count($cards));
        $html = '<table width="100%" style="margin-bottom:14px"><tr>';
        foreach ($cards as $c) {
            $html .= '<td width="' . $w . '%" style="text-align:center;background:#f8f9fa;border:1px solid #e5e7eb;border-radius:6px;padding:10px;margin:2px">';
            $html .= '<div style="font-size:16px;font-weight:bold;color:' . $c['cor'] . '">' . htmlspecialchars((string)$c['valor']) . '</div>';
            $html .= '<div style="font-size:9px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px">' . htmlspecialchars($c['label']) . '</div>';
            $html .= '</td>';
        }
        $html .= '</tr></table>';
        return $html;
    }

    private static function htmlTabela(array $headers, array $rows, ?string $rowBg = null, bool $small = false): string {
        $fs = $small ? '8px' : '9px';
        $html  = '<table width="100%" style="border-collapse:collapse;margin-bottom:10px;font-size:' . $fs . '">';
        // Header
        $html .= '<tr>';
        foreach ($headers as $h) {
            $html .= '<th style="background:#1e2330;color:#f59e0b;padding:5px 7px;text-align:left;font-weight:bold;border:1px solid #374151">' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr>';
        // Rows
        foreach ($rows as $i => $row) {
            $bg = $rowBg ?? ($i % 2 === 0 ? '#ffffff' : '#f9fafb');
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td style="background:' . $bg . ';padding:5px 7px;border:1px solid #e5e7eb">' . htmlspecialchars((string)$cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    private static function rodape(object $pdf, string $empresa): void {
        $pageCount = $pdf->getNumPages();
        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->setPage($i);
            $pdf->SetY(-12);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 5, $empresa . ' — Bar System Pro — Pág. ' . $i . '/' . $pageCount . ' — ' . date('d/m/Y H:i'), 0, 0, 'C');
        }
    }

    private static function erroPdfIndisponivel(): never {
        while (ob_get_level()) ob_end_clean();
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#0d0f14;color:#f0f2f7;margin:0}
        .box{background:#1e2330;border:1px solid #2d3447;border-radius:12px;padding:2rem;max-width:500px;text-align:center}
        h2{color:#f59e0b}code{background:#252b38;padding:3px 8px;border-radius:4px;font-size:.85rem}
        </style></head><body>
        <div class="box">
          <h2>⚠ TCPDF não instalado</h2>
          <p>Para gerar PDFs, instale o TCPDF via Composer:</p>
          <p><code>composer install</code></p>
          <p style="color:#8892a4;font-size:.85rem">Execute este comando na raiz do projeto no servidor.</p>
          <p style="margin-top:1.5rem"><a href="javascript:history.back()" style="color:#f59e0b">← Voltar</a></p>
        </div></body></html>';
        exit;
    }
}
