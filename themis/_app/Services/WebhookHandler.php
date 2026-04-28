<?php
declare(strict_types=1);
// ============================================================
// WebhookHandler
// ============================================================
final class WebhookHandler
{
    public function __construct(private DB $db) {}

    public function receive(string $fonte, array $payload, ?string $signature = null): int
    {
        if ($signature && !$this->verifySignature($fonte, json_encode($payload), $signature)) {
            throw new \RuntimeException("Assinatura inválida do webhook: {$fonte}", 401);
        }
        $id = (int) $this->db->insert('webhook_eventos', [
            'tenant_id' => $this->db->getTenantId() ?? 0,
            'fonte'     => $fonte,
            'evento'    => $payload['event'] ?? 'unknown',
            'payload'   => json_encode($payload),
            'status'    => 'recebido',
        ]);
        // Processa síncronos imediatamente
        if ($fonte === 'assinafy') $this->processEvent($id, $fonte, $payload);
        return $id;
    }

    public function processEvent(int $id, string $fonte, array $payload): bool
    {
        try {
            match($fonte) {
                'assinafy' => $this->handleAssinafy($payload),
                'whatsapp' => $this->handleWhatsApp($payload),
                'datajud'  => (new DataJudService($this->db))->processWebhookPayload($payload),
                default    => null,
            };
            $this->db->update('webhook_eventos', ['status' => 'processado', 'processado_em' => date('Y-m-d H:i:s')], ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->db->update('webhook_eventos', ['status' => 'erro', 'erro_msg' => $e->getMessage()], ['id' => $id]);
            return false;
        }
    }

    private function handleAssinafy(array $p): void
    {
        $docId  = $p['document_id'] ?? null;
        $status = $p['status'] ?? null;
        if (!$docId || !$status) return;
        $map = ['signed' => 'assinado', 'refused' => 'recusado', 'pending' => 'enviado', 'expired' => 'recusado'];
        $s   = $map[$status] ?? 'enviado';
        $this->db->run("UPDATE documentos SET assinatura_status = ? WHERE assinafy_doc_id = ?", [$s, $docId]);
        $this->db->run("UPDATE laudos SET assinatura_status = ? WHERE assinafy_doc_id = ?", [$s, $docId]);
    }

    private function handleWhatsApp(array $p): void
    {
        $tel = $p['from'] ?? null;
        $msg = $p['text']['body'] ?? null;
        if (!$tel || !$msg) return;
        $sh = $this->db->first(
            "SELECT id, responsavel_id FROM stakeholders WHERE (whatsapp = ? OR telefone = ?) AND deleted_at IS NULL LIMIT 1",
            [$tel, $tel]
        );
        if (!$sh) return;
        $this->db->insert('crm_interacoes', [
            'tenant_id'      => $this->db->getTenantId(),
            'stakeholder_id' => $sh['id'],
            'user_id'        => $sh['responsavel_id'] ?? 0,
            'tipo'           => 'whatsapp',
            'titulo'         => 'Mensagem recebida via WhatsApp',
            'descricao'      => mb_substr($msg, 0, 2000),
            'data_interacao' => date('Y-m-d H:i:s'),
        ]);
        $this->db->update('stakeholders', ['ultimo_contato' => date('Y-m-d H:i:s')], ['id' => $sh['id']]);
    }

    public function enqueueRetry(string $servico, string $endpoint, ?array $payload, array $contexto = []): int
    {
        return (int) $this->db->insert('api_retry_queue', [
            'tenant_id'      => $this->db->getTenantId(),
            'servico'        => $servico,
            'endpoint'       => $endpoint,
            'payload'        => $payload ? json_encode($payload) : null,
            'tentativas'     => 0,
            'max_tentativas' => 5,
            'proximo_retry'  => date('Y-m-d H:i:s', time() + 300),
            'status'         => 'pendente',
            'contexto_json'  => json_encode($contexto),
        ]);
    }

    /** Processar fila de retry — cron a cada 5min */
    public function processRetryQueue(): array
    {
        $pendentes = $this->db->all(
            "SELECT * FROM api_retry_queue
             WHERE status IN ('pendente') AND proximo_retry <= NOW()
             ORDER BY proximo_retry LIMIT 50"
        );
        $delays = [300, 900, 3600, 14400, 43200];
        $r = ['sucesso' => 0, 'falhou' => 0, 'esgotado' => 0];

        foreach ($pendentes as $item) {
            $this->db->update('api_retry_queue', ['status' => 'processando'], ['id' => $item['id']]);
            try {
                $res = $this->callApi($item['endpoint'], $item['payload'] ? json_decode($item['payload'], true) : null);
                $this->db->update('api_retry_queue', [
                    'status'        => 'sucesso',
                    'resposta_json' => json_encode($res),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], ['id' => $item['id']]);
                $r['sucesso']++;
            } catch (\Throwable $e) {
                $t = $item['tentativas'] + 1;
                if ($t >= $item['max_tentativas']) {
                    $this->db->update('api_retry_queue', ['status' => 'falhou', 'tentativas' => $t, 'erro_msg' => $e->getMessage()], ['id' => $item['id']]);
                    $r['esgotado']++;
                } else {
                    $delay = $delays[$t] ?? 43200;
                    $this->db->update('api_retry_queue', [
                        'status'        => 'pendente',
                        'tentativas'    => $t,
                        'proximo_retry' => date('Y-m-d H:i:s', time() + $delay),
                        'erro_msg'      => $e->getMessage(),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ], ['id' => $item['id']]);
                    $r['falhou']++;
                }
            }
        }
        return $r;
    }

    public function sendWhatsApp(string $tel, string $msg, int $tenantId): bool
    {
        $phoneId = $_ENV['WHATSAPP_PHONE_ID'] ?? '';
        $token   = $_ENV['WHATSAPP_TOKEN']    ?? '';
        if (!$phoneId || !$token) return false;
        $endpoint = "https://graph.facebook.com/v19.0/{$phoneId}/messages";
        try {
            $this->callApi($endpoint, [
                'messaging_product' => 'whatsapp',
                'to'   => preg_replace('/\D/', '', $tel),
                'type' => 'text',
                'text' => ['body' => $msg],
            ], ['Authorization: Bearer ' . $token]);
            return true;
        } catch (\Throwable) {
            $this->enqueueRetry('whatsapp', $endpoint, ['tel' => $tel, 'msg' => $msg]);
            return false;
        }
    }

    public function callApi(string $url, ?array $body = null, array $headers = []): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        ];
        if ($body !== null) { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = json_encode($body); }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($err)      throw new \RuntimeException("cURL: {$err}");
        if ($code >= 400) throw new \RuntimeException("HTTP {$code}: {$resp}");
        return json_decode((string)$resp, true) ?? [];
    }

    private function verifySignature(string $fonte, string $payload, string $sig): bool
    {
        $secrets = ['assinafy' => $_ENV['ASSINAFY_SECRET'] ?? ''];
        $secret  = $secrets[$fonte] ?? '';
        if (!$secret) return true;
        return hash_equals(hash_hmac('sha256', $payload, $secret), $sig);
    }
}

// ============================================================
// DataJudService
// ============================================================
final class DataJudService
{
    private const CODIGOS_ALVARA = ['196', '11541', '11588', '12213'];

    private WebhookHandler $wh;

    public function __construct(private DB $db)
    {
        $this->wh = new WebhookHandler($db);
    }

    public function consultarProcesso(string $cnj, int $processoId): array
    {
        $endpoints = $this->endpoints($cnj);
        foreach ($endpoints as $ep) {
            try {
                $r = $this->request($ep, ['query' => ['match' => ['numeroProcesso' => $cnj]]]);
                if (!empty($r['hits']['hits'][0]['_source'])) {
                    $src = $r['hits']['hits'][0]['_source'];
                    $this->sincronizarMovimentos($processoId, $cnj, $src['movimentos'] ?? []);
                    $this->verificarAlvara($processoId, $src);
                    $this->db->update('processos', ['datajud_ultimo_sync' => date('Y-m-d H:i:s'), 'dias_parado' => 0], ['id' => $processoId]);
                    return $src;
                }
            } catch (\Throwable $e) {
                $this->wh->enqueueRetry('datajud', $ep, ['cnj' => $cnj], ['processo_id' => $processoId, 'cnj' => $cnj]);
                error_log("[Themis:DataJud] {$e->getMessage()} — Processo {$cnj}");
            }
        }
        return [];
    }

    private function sincronizarMovimentos(int $processoId, string $cnj, array $movimentos): void
    {
        foreach ($movimentos as $m) {
            $cod = $m['codigo']['codigo'] ?? '';
            if ($cod && $this->db->scalar("SELECT id FROM datajud_movimentos WHERE processo_id = ? AND codigo_movimento = ? LIMIT 1", [$processoId, $cod])) continue;
            $this->db->insert('datajud_movimentos', [
                'tenant_id'       => $this->db->getTenantId(),
                'processo_id'     => $processoId,
                'numero_cnj'      => $cnj,
                'codigo_movimento'=> $cod ?: null,
                'nome_movimento'  => $m['nome'] ?? 'Sem descrição',
                'data_movimento'  => date('Y-m-d H:i:s', strtotime($m['dataHora'] ?? 'now')),
                'complemento'     => json_encode($m['complementosTabelados'] ?? []),
                'raw_json'        => json_encode($m),
            ]);
            $this->db->run("UPDATE processos SET ultimo_andamento = ?, dias_parado = 0 WHERE id = ?", [date('Y-m-d'), $processoId]);
        }
    }

    private function verificarAlvara(int $processoId, array $src): void
    {
        foreach ($src['movimentos'] ?? [] as $m) {
            if (!in_array($m['codigo']['codigo'] ?? '', self::CODIGOS_ALVARA, true)) continue;
            $alv = $this->db->first("SELECT * FROM alvaras_monitoramento WHERE processo_id = ? AND status = 'aguardando' AND gatilho_ativo = 1 LIMIT 1", [$processoId]);
            if (!$alv || $alv['alerta_enviado']) continue;
            $this->db->update('alvaras_monitoramento', ['status' => 'expedido', 'data_expedicao' => date('Y-m-d'), 'alerta_enviado' => 1], ['id' => $alv['id']]);
            $proc = $this->db->first("SELECT * FROM processos WHERE id = ?", [$processoId]);
            if ($proc) {
                $this->db->insert('notificacoes', [
                    'tenant_id' => $this->db->getTenantId(),
                    'user_id'   => $proc['responsavel_id'],
                    'tipo'      => 'alvara',
                    'titulo'    => '🎯 Alvará expedido!',
                    'mensagem'  => 'Alvará de R$ ' . number_format($alv['valor_alvara'], 2, ',', '.') . " — #{$proc['numero_interno']}",
                    'cor'       => 'green',
                    'link_url'  => "/processos/{$processoId}",
                ]);
            }
        }
    }

    public function monitorarTodos(int $tenantId): array
    {
        $procs = $this->db->all(
            "SELECT id, numero_cnj FROM processos
             WHERE tenant_id = ? AND datajud_monitorado = 1 AND numero_cnj IS NOT NULL
               AND status NOT IN ('arquivado','encerrado') AND deleted_at IS NULL",
            [$tenantId]
        );
        $r = ['sincronizados' => 0, 'erros' => 0, 'sem_retorno' => 0];
        foreach ($procs as $p) {
            try {
                $ret = $this->consultarProcesso($p['numero_cnj'], $p['id']);
                $ret ? $r['sincronizados']++ : $r['sem_retorno']++;
            } catch (\Throwable) { $r['erros']++; }
            usleep(250_000); // 250ms entre requests
        }
        return $r;
    }

    public function buscarPorOAB(string $oabNumero, string $oabUf, int $tenantId, array $user): array
    {
        // Buscar API key do tenant
        $tenant = $this->db->first("SELECT datajud_key FROM tenants WHERE id = ?", [$tenantId]);
        $apiKey = $tenant['datajud_key'] ?? $_ENV['DATAJUD_API_KEY'] ?? '';

        if (empty($apiKey)) throw new \RuntimeException('API Key do DataJud não configurada. Configure em Configurações.', 400);
        if (empty($oabNumero)) throw new \RuntimeException('Número OAB não informado.', 400);

        $base     = $_ENV['DATAJUD_BASE_URL'] ?? 'https://api-publica.datajud.cnj.jus.br';
        $imported = 0;
        $found    = 0;
        $erros    = [];

        // Buscar em todos os tribunais principais
        $tribunais = [
            'tjsp','tjrj','tjmg','tjrs','tjpr','tjsc','tjba','tjgo','tjpe','tjdf',
            'trt1','trt2','trt3','trt4','trt15',
            'trf1','trf2','trf3','trf4','trf5',
        ];

        $query = [
            'query' => [
                'bool' => [
                    'should' => [
                        ['nested' => ['path' => 'advogados', 'query' => ['bool' => ['must' => [
                            ['match' => ['advogados.OABNumero' => $oabNumero]],
                        ]]]]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
            'size' => 100,
            '_source' => ['numeroProcesso','tribunal','dataAjuizamento','classeProcessual','assuntos','partes','orgaoJulgador'],
        ];

        // Adicionar filtro de UF se informado
        if ($oabUf) {
            $query['query']['bool']['should'][0]['nested']['query']['bool']['must'][] =
                ['match' => ['advogados.OABUFName' => strtoupper($oabUf)]];
        }

        foreach ($tribunais as $tribunal) {
            $ep = "{$base}/api_publica_{$tribunal}/_search";
            try {
                $ch = curl_init($ep);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($query),
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Authorization: ApiKey ' . $apiKey,
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code !== 200 || empty($resp)) continue;
                $data = json_decode($resp, true);
                $hits = $data['hits']['hits'] ?? [];

                foreach ($hits as $hit) {
                    $src = $hit['_source'] ?? [];
                    $cnj = $src['numeroProcesso'] ?? null;
                    if (!$cnj) continue;
                    $found++;

                    // Verificar se já existe
                    $existe = $this->db->first(
                        "SELECT id FROM processos WHERE numero_cnj = ? AND tenant_id = ?",
                        [$cnj, $tenantId]
                    );
                    if ($existe) continue;

                    // Identificar cliente (polo passivo ou ativo)
                    $partes   = $src['partes'] ?? [];
                    $cliente  = null;
                    foreach ($partes as $p) {
                        if (in_array($p['tipoParte'] ?? '', ['Requerente','Autor','Reclamante','Impetrante'], true)) {
                            $cliente = $p['nome'] ?? null;
                            break;
                        }
                    }
                    if (!$cliente && !empty($partes[0]['nome'])) $cliente = $partes[0]['nome'];

                    // Buscar ou criar stakeholder cliente
                    $clienteId = null;
                    if ($cliente) {
                        $sh = $this->db->first(
                            "SELECT id FROM stakeholders WHERE tenant_id=? AND nome=? AND tipo='cliente' LIMIT 1",
                            [$tenantId, $cliente]
                        );
                        if (!$sh) {
                            $clienteId = $this->db->insert('stakeholders', [
                                'tenant_id' => $tenantId,
                                'tipo'      => 'cliente',
                                'nome'      => $cliente,
                                'ativo'     => 1,
                            ]);
                        } else {
                            $clienteId = $sh['id'];
                        }
                    }

                    // Classe e assunto
                    $classe  = $src['classeProcessual']['nome'] ?? 'Ação';
                    $assunto = !empty($src['assuntos'][0]['nome']) ? $src['assuntos'][0]['nome'] : $classe;
                    $orgao   = $src['orgaoJulgador']['nome'] ?? strtoupper($tribunal);
                    $dataAj  = !empty($src['dataAjuizamento']) ? substr($src['dataAjuizamento'], 0, 10) : null;

                    // Criar processo
                    $procId = $this->db->insert('processos', [
                        'tenant_id'           => $tenantId,
                        'responsavel_id'      => $user['id'],
                        'cliente_id'          => $clienteId,
                        'numero_cnj'          => $cnj,
                        'numero_interno'      => 'DJ-' . substr($cnj, 0, 7),
                        'titulo'              => $assunto,
                        'tipo'                => 'civel',
                        'polo'                => 'ativo',
                        'vara'                => $orgao,
                        'tribunal'            => strtoupper($tribunal),
                        'data_distribuicao'   => $dataAj,
                        'status'              => 'ativo',
                        'datajud_monitorado'  => 1,
                        'datajud_ultimo_sync' => date('Y-m-d H:i:s'),
                    ]);

                    // Sincronizar movimentos
                    $this->consultarProcesso($cnj, $procId);
                    $imported++;
                }
            } catch (\Throwable $e) {
                $erros[] = "{$tribunal}: " . $e->getMessage();
                error_log("[DataJud][OAB] {$tribunal}: " . $e->getMessage());
            }
        }

        error_log("[DataJud][OAB] OAB {$oabNumero}/{$oabUf} → encontrados={$found} importados={$imported}");
        return ['encontrados' => $found, 'importados' => $imported, 'erros' => $erros];
    }

    public function processWebhookPayload(array $p): void
    {
        if (!empty($p['numeroProcesso']) && !empty($p['processo_id'])) {
            $this->consultarProcesso($p['numeroProcesso'], (int)$p['processo_id']);
        }
    }

    private function request(string $ep, array $body): array
    {
        return $this->wh->callApi($ep, $body, ['Authorization: ApiKey ' . ($_ENV['DATAJUD_API_KEY'] ?? '')]);
    }

    private function endpoints(string $cnj): array
    {
        preg_match('/\d{7}-\d{2}\.\d{4}\.(\d)\.(\d{2})/', $cnj, $m);
        $j = $m[1] ?? '8';
        $t = $m[2] ?? '26';
        $mapa = [
            '8' => ['26'=>'tjsp','19'=>'tjrj','13'=>'tjmg','12'=>'tjrs','21'=>'tjdf','6'=>'tjce','5'=>'tjba'],
            '4' => ['01'=>'trt1','02'=>'trt2','03'=>'trt3','04'=>'trt4'],
            '5' => ['01'=>'trf1','02'=>'trf2','03'=>'trf3','04'=>'trf4','05'=>'trf5'],
        ];
        $sigla = $mapa[$j][$t] ?? null;
        $base  = $_ENV['DATAJUD_BASE_URL'] ?? 'https://api-publica.datajud.cnj.jus.br';
        return $sigla ? ["{$base}/api_publica_{$sigla}/_search"] : [];
    }
}
