<?php
/**
 * api/auto_renovar.php
 * Processo automático de renovação de anúncios com 120+ dias.
 * Usa Gemini para validar e corrigir o payload antes de enviar ao ML.
 *
 * Fluxo seguro (nunca perde anúncio):
 *   1. Busca produtos com 120+ dias no banco
 *   2. Gemini analisa e valida o payload
 *   3. CRIA o novo anúncio no ML
 *   4. SÓ SE criou com sucesso → fecha o antigo
 *   5. Atualiza banco + registra log
 *
 * Executado via cron:
 *   0 6 * * * /usr/local/lsws/lsphp83/bin/php8.3 /home/www/lupa/api/auto_renovar.php >> /home/www/lupa/storage/logs/auto_renovar.log 2>&1
 */

define('IS_CLI', php_sapi_name() === 'cli');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../ai.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../crypto.php';

// ── Logger ────────────────────────────────────────────────
function log_renovar(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [AUTO_RENOVAR] ' . $msg;
    echo $line . PHP_EOL;
}

// ── Gerar UUID ────────────────────────────────────────────
function gen_uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

// ── Salvar log no banco ───────────────────────────────────
function salvar_log(string $tenantId, string $acctId, string $productId, string $title,
                    ?string $oldItemId, ?string $newItemId, int $dias,
                    string $status, ?string $error = null, ?array $changes = null): void {
    try {
        db_insert('renovacoes_log', [
            'id'             => gen_uuid(),
            'tenant_id'      => $tenantId,
            'meli_account_id'=> $acctId,
            'product_id'     => $productId,
            'product_title'  => mb_substr($title, 0, 255),
            'old_item_id'    => $oldItemId,
            'new_item_id'    => $newItemId,
            'dias_ativo'     => $dias,
            'status'         => $status,
            'error_message'  => $error,
            'gemini_changes' => $changes ? json_encode($changes) : null,
        ]);
    } catch (Throwable $e) {
        log_renovar("WARN: falha ao salvar log — " . $e->getMessage());
    }
}

// ── IA: validar e corrigir payload ───────────────────────
function gemini_validar_payload(array $product, array $payload, string $geminiKey = ''): array {
    $tenantId = $product['tenant_id'] ?? '';
    if (!$tenantId) {
        return ['ok' => true, 'payload' => $payload, 'changes' => []];
    }

    $prompt = "Você é um especialista em anúncios do Mercado Livre Brasil.\n"
        . "Analise o payload JSON e corrija problemas que impeçam a publicação via API.\n\n"
        . "REGRAS:\n"
        . "- title: máximo 60 chars, sem !@#%&*, sem markdown\n"
        . "- description: texto puro, sem HTML\n"
        . "- price: número positivo\n"
        . "- available_quantity: inteiro >= 1\n"
        . "- category_id, listing_type_id, pictures: manter existentes\n"
        . "- NÃO invente informações\n\n"
        . "PAYLOAD:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n"
        . 'Responda APENAS com JSON: {"ok":true,"payload":{...},"changes":["..."]}';

    $result = ai_generate_for($tenantId, 'renovar', $prompt, 2000);

    if (!$result['text']) {
        log_renovar("WARN: IA sem resposta — payload original");
        return ['ok' => true, 'payload' => $payload, 'changes' => ['IA indisponível']];
    }

    $text   = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $result['text']));
    $parsed = json_decode($text, true);

    if (!$parsed || empty($parsed['payload'])) {
        log_renovar("WARN: IA retornou JSON inválido — payload original");
        return ['ok' => true, 'payload' => $payload, 'changes' => ['IA JSON inválido']];
    }

    log_renovar("IA validou via {$result['provider']}");
    return ['ok' => true, 'payload' => $parsed['payload'], 'changes' => $parsed['changes'] ?? []];
}
// ── Criar anúncio no ML ───────────────────────────────────
function criar_anuncio_ml(array $payload, string $token): array {
    $result = curl_ml('https://api.mercadolibre.com/items', [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $data = json_decode($result['body'], true);

    if (!in_array($result['code'], [200, 201]) || empty($data['id'])) {
        $causes = $data['cause'] ?? [];
        $msgs   = array_map(fn($c) => ($c['message'] ?? $c['code'] ?? ''), $causes);
        $error  = !empty($msgs) ? implode('; ', array_filter($msgs)) : ($data['message'] ?? "HTTP {$result['code']}");
        return ['ok' => false, 'error' => $error, 'raw' => $data];
    }

    return [
        'ok'           => true,
        'meli_item_id' => $data['id'],
        'status'       => $data['status'] ?? 'active',
        'permalink'    => $data['permalink'] ?? '',
    ];
}

// ── Fechar anúncio no ML ──────────────────────────────────
function fechar_anuncio_ml(string $itemId, string $token): bool {
    $result = curl_ml("https://api.mercadolibre.com/items/{$itemId}", [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS    => json_encode(['status' => 'closed']),
        CURLOPT_HTTPHEADER    => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    return $result['code'] === 200;
}

// ── Montar payload do produto ─────────────────────────────
function montar_payload(array $product): array {
    $pictureIds = json_decode($product['picture_ids'] ?? '[]', true) ?: [];
    $pictures   = array_map(fn($id) => ['id' => $id], $pictureIds);
    $attributes = json_decode($product['ml_attributes'] ?? '[]', true) ?: [];

    // Garante ITEM_CONDITION
    if (!array_filter($attributes, fn($a) => ($a['id'] ?? '') === 'ITEM_CONDITION')) {
        $attributes[] = [
            'id'         => 'ITEM_CONDITION',
            'value_name' => $product['item_condition'] === 'used' ? 'Usado' : 'Novo',
        ];
    }

    $payload = [
        'title'              => mb_substr(trim($product['title']), 0, 60),
        'category_id'        => $product['category_id'],
        'price'              => (float)$product['price'],
        'currency_id'        => 'BRL',
        'available_quantity' => max(1, (int)$product['stock_quantity']),
        'buying_mode'        => 'buy_it_now',
        'listing_type_id'    => $product['listing_type_id'] ?? 'gold_special',
        'item_condition'     => $product['item_condition'] ?? 'new',
        'pictures'           => $pictures,
        'attributes'         => $attributes,
        'shipping'           => ['mode' => 'me2', 'free_shipping' => false],
    ];

    if (!empty($product['description'])) {
        $payload['description'] = ['plain_text' => strip_tags($product['description'])];
    }

    if (!empty($product['catalog_product_id'])) {
        $payload['catalog_product_id'] = $product['catalog_product_id'];
        $payload['catalog_listing']    = true;
    }

    return $payload;
}

// ════════════════════════════════════════════════════════════
// PROCESSO PRINCIPAL
// ════════════════════════════════════════════════════════════

log_renovar("Iniciando processo de renovação automática");

// Garante que a tabela existe
try {
    db_query("CREATE TABLE IF NOT EXISTS renovacoes_log (
        id               VARCHAR(36)   NOT NULL,
        tenant_id        VARCHAR(36)   NOT NULL,
        meli_account_id  VARCHAR(36)   NULL,
        product_id       VARCHAR(36)   NOT NULL,
        product_title    VARCHAR(255)  NULL,
        old_item_id      VARCHAR(30)   NULL,
        new_item_id      VARCHAR(30)   NULL,
        dias_ativo       INT           NULL,
        status           ENUM('SUCCESS','FAILED','SKIPPED') NOT NULL DEFAULT 'FAILED',
        error_message    TEXT          NULL,
        gemini_changes   JSON          NULL,
        created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant  (tenant_id),
        KEY idx_status  (tenant_id, status),
        KEY idx_date    (tenant_id, created_at),
        KEY idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {
    log_renovar("WARN: falha ao criar tabela — " . $e->getMessage());
}

// Busca todos os tenants ativos
$tenants = db_all("SELECT id FROM tenants WHERE is_active=1");
log_renovar(count($tenants) . " tenant(s) ativo(s)");

$totalRenovados = 0;
$totalErros     = 0;
$totalSkipped   = 0;

foreach ($tenants as $tenant) {
    $tenantId = $tenant['id'];

    // Chave de IA carregada automaticamente pelo ai_generate_for via ai_get_config()

    // Contas ML ativas do tenant
    $accounts = db_all(
        "SELECT * FROM meli_accounts WHERE tenant_id=? AND is_active=1",
        [$tenantId]
    );

    foreach ($accounts as $acct) {
        $acctId = $acct['id'];

        // Descriptografa token
        try {
            $token = crypto_decrypt_token($acct['access_token_enc']);
        } catch (Throwable $e) {
            log_renovar("SKIP conta {$acct['nickname']}: token inválido");
            continue;
        }

        // Busca produtos com 120+ dias que têm anúncio ativo
        $produtos = db_all(
            "SELECT * FROM products
             WHERE tenant_id=? AND meli_account_id=?
               AND meli_item_id IS NOT NULL
               AND ml_status IN ('ACTIVE','PAUSED')
               AND DATEDIFF(NOW(), created_at) >= 120
               AND category_id IS NOT NULL
               AND price > 0
             ORDER BY created_at ASC",
            [$tenantId, $acctId]
        );

        if (empty($produtos)) {
            log_renovar("Conta {$acct['nickname']}: nenhum produto para renovar");
            continue;
        }

        log_renovar("Conta {$acct['nickname']}: " . count($produtos) . " produto(s) para renovar");

        foreach ($produtos as $product) {
            $pid       = $product['id'];
            $title     = $product['title'];
            $oldItemId = $product['meli_item_id'];
            $dias      = (int)db_one(
                "SELECT DATEDIFF(NOW(), created_at) as d FROM products WHERE id=?",
                [$pid]
            )['d'];

            log_renovar("Processando: \"{$title}\" ({$oldItemId}) — {$dias} dias");

            // Verificar se já foi renovado hoje
            $jaRenovado = db_one(
                "SELECT id FROM renovacoes_log
                 WHERE product_id=? AND status='SUCCESS' AND DATE(created_at)=CURDATE()",
                [$pid]
            );
            if ($jaRenovado) {
                log_renovar("SKIP \"{$title}\": já renovado hoje");
                $totalSkipped++;
                continue;
            }

            // ── Passo 1: Montar payload base ─────────────────
            $payload = montar_payload($product);

            // ── Passo 2: Gemini valida e corrige ─────────────
            log_renovar("Gemini validando payload...");
            $geminiResult = gemini_validar_payload($product, $payload, $geminiKey);

            if (!$geminiResult['ok']) {
                $err = $geminiResult['error'] ?? 'Gemini rejeitou o payload';
                log_renovar("ERRO Gemini \"{$title}\": {$err}");
                salvar_log($tenantId, $acctId, $pid, $title, $oldItemId, null, $dias, 'FAILED', $err);
                $totalErros++;
                continue;
            }

            $payloadFinal = $geminiResult['payload'];
            $changes      = $geminiResult['changes'] ?? [];

            if (!empty($changes)) {
                log_renovar("Gemini ajustou: " . implode(', ', $changes));
            }

            // ── Passo 3: CRIAR novo anúncio PRIMEIRO ─────────
            log_renovar("Criando novo anúncio no ML...");
            $novo = criar_anuncio_ml($payloadFinal, $token);

            if (!$novo['ok']) {
                $err = $novo['error'];
                log_renovar("ERRO ao criar \"{$title}\": {$err}");
                log_renovar("SEGURANÇA: anúncio antigo {$oldItemId} mantido intacto");
                salvar_log($tenantId, $acctId, $pid, $title, $oldItemId, null, $dias, 'FAILED', $err, $changes);
                $totalErros++;
                continue;
            }

            $newItemId = $novo['meli_item_id'];
            log_renovar("Novo anúncio criado: {$newItemId}");

            // ── Passo 4: Fechar anúncio antigo ───────────────
            log_renovar("Fechando anúncio antigo {$oldItemId}...");
            $fechado = fechar_anuncio_ml($oldItemId, $token);

            if (!$fechado) {
                // Não é crítico — o novo já existe e está ativo
                // O antigo pode ficar duplicado temporariamente até expirar
                log_renovar("WARN: falha ao fechar {$oldItemId} — novo {$newItemId} já está ativo");
            }

            // ── Passo 5: Atualizar banco ──────────────────────
            db_update('products', [
                'meli_item_id' => $newItemId,
                'ml_status'    => 'ACTIVE',
                'ml_permalink' => $novo['permalink'],
                'created_at'   => date('Y-m-d H:i:s'), // reseta contador 120 dias
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'id=? AND tenant_id=?', [$pid, $tenantId]);

            salvar_log($tenantId, $acctId, $pid, $title, $oldItemId, $newItemId, $dias, 'SUCCESS', null, $changes);
            audit_log('AUTO_RENOVACAO', 'products', $pid, ['meli_item_id' => $oldItemId], ['meli_item_id' => $newItemId]);

            log_renovar("OK \"{$title}\": {$oldItemId} → {$newItemId}");
            $totalRenovados++;

            // Pausa entre requisições para não sobrecarregar a API ML
            sleep(2);
        }
    }
}

log_renovar("Concluído — renovados: {$totalRenovados}, erros: {$totalErros}, pulados: {$totalSkipped}");
