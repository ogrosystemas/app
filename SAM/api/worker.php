<?php
/**
 * api/worker.php
 * Worker de processamento da fila de webhooks.
 * Executado via cron a cada minuto:
 *   * * * * * php /home/www/lupa/api/worker.php >> /home/www/lupa/storage/logs/worker.log 2>&1
 *
 * Processa por prioridade DESC, max 50 jobs por execução.
 * Cada job tem timeout de 30s e max 3 tentativas.
 */

// Evita execução via web
if (PHP_SAPI !== 'cli' && (empty($_SERVER['HTTP_HOST']) === false)) {
    http_response_code(403);
    exit('Only CLI');
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/crypto.php';
require_once dirname(__DIR__) . '/ai.php';
require_once __DIR__ . '/process_auto_messages.php';

define('WORKER_MAX_JOBS',    50);
define('WORKER_MAX_ATTEMPTS', 3);
define('WORKER_TIMEOUT',     30);

$startTime = microtime(true);
$processed = 0;
$failed    = 0;

log_worker("Worker started");

// ── Busca jobs pendentes por prioridade ──────────────────
$jobs = db_all(
    "SELECT * FROM queue_jobs
     WHERE status = 'PENDING'
       AND attempts < ?
     ORDER BY priority DESC, created_at ASC
     LIMIT ?",
    [WORKER_MAX_ATTEMPTS, WORKER_MAX_JOBS]
);

if (empty($jobs)) {
    log_worker("No pending jobs. Exiting.");
    exit(0);
}

log_worker(count($jobs) . " jobs to process");

foreach ($jobs as $job) {
    // Marca como PROCESSING (evita outro worker pegar o mesmo job)
    $updated = db_query(
        "UPDATE queue_jobs SET status='PROCESSING', attempts=attempts+1, updated_at=NOW()
         WHERE id=? AND status='PENDING'",
        [$job['id']]
    )->rowCount();

    if (!$updated) continue; // Outro worker já pegou

    try {
        set_time_limit(WORKER_TIMEOUT);

        $payload = json_decode($job['payload'], true) ?? [];
        $topic   = $job['topic'];
        $resource= $job['resource'];

        // Carrega conta ML
        $account = db_one(
            "SELECT * FROM meli_accounts WHERE id=? AND is_active=1",
            [$job['meli_account_id']]
        );
        if (!$account) throw new Exception("Account not found: {$job['meli_account_id']}");

        // Descriptografa tokens se TOKEN_KEY estiver configurado
        if (TOKEN_KEY) {
            try {
                $account['access_token_enc']  = crypto_decrypt_token($account['access_token_enc']);
                $account['refresh_token_enc'] = $account['refresh_token_enc']
                    ? crypto_decrypt_token($account['refresh_token_enc']) : '';
            } catch (Throwable $te) {
                // Token não está criptografado (migração) — usa como está
            }
        }

        // Processa por tópico com prioridade
        switch ($topic) {
            case 'orders_v2': processOrder($resource, $account);   break;
            case 'questions': processQuestion($resource, $account); break;
            case 'messages':  processMessage($resource, $account);  break;
            case 'payments':  processPayment($resource, $account);  break;
            case 'shipments': processShipment($resource, $account); break;
            case 'items':     processItem($resource, $account);     break;
            default: log_worker("Unknown topic: {$topic}");
        }

        db_update('queue_jobs', [
            'status'     => 'DONE',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id=?', [$job['id']]);

        $processed++;
        log_worker("[DONE] {$topic} | {$resource}");

    } catch (Throwable $e) {
        $attempts = (int)$job['attempts'] + 1;
        $newStatus = $attempts >= WORKER_MAX_ATTEMPTS ? 'FAILED' : 'PENDING';

        db_update('queue_jobs', [
            'status'        => $newStatus,
            'error_message' => $e->getMessage(),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], 'id=?', [$job['id']]);

        $failed++;
        log_worker("[{$newStatus}] {$topic} | {$e->getMessage()}");
    }
}

$elapsed = round(microtime(true) - $startTime, 2);
log_worker("Worker finished. Processed:{$processed} Failed:{$failed} Time:{$elapsed}s");

// Processar mensagens automáticas (a cada execução do worker)
try {
    $accounts = db_all(
        "SELECT ma.*, ma.id as id FROM meli_accounts ma
         INNER JOIN tenants t ON t.id=ma.tenant_id AND t.is_active=1
         WHERE ma.is_active=1",
        []
    );
    foreach ($accounts as $account) {
        try {
            process_auto_messages($account);
        } catch (Throwable $e) {
            log_worker("AUTO_MSG error for {$account['nickname']}: " . $e->getMessage());
        }
    }
} catch (Throwable $e) {}

exit(0);

// ════════════════════════════════════════════════════════
// HANDLERS POR TÓPICO
// ════════════════════════════════════════════════════════

/**
 * orders_v2 — Prioridade 10
 * Insere/atualiza pedido, baixa estoque, registra transação financeira.
 */
function processOrder(string $resource, array $account): void {
    $data = meli_get($resource, $account['access_token_enc']);
    if (!$data || empty($data['id'])) throw new Exception("Empty order data");

    $meliOrderId = '#' . $data['id'];
    $status      = strtoupper($data['status'] ?? 'PENDING');
    $shipStatus  = mapShipStatus($data['shipping']['status'] ?? '');
    $payStatus   = strtoupper($data['payments'][0]['status'] ?? 'PENDING');
    $buyer       = $data['buyer'] ?? [];
    $shipping    = $data['shipping']['receiver_address'] ?? [];
    $total       = (float)($data['total_amount'] ?? 0);
    $fee         = (float)($data['payments'][0]['marketplace_fee'] ?? 0);

    $existing = db_one(
        "SELECT id FROM orders WHERE meli_order_id=? AND tenant_id=?",
        [$meliOrderId, $account['tenant_id']]
    );

    if ($existing) {
        db_update('orders', [
            'status'         => $status,
            'payment_status' => $payStatus,
            'ship_status'    => $shipStatus,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id=?', [$existing['id']]);
        log_worker("Updated order {$meliOrderId}");
        return;
    }

    // Novo pedido — insere com transação atômica
    db_transaction(function() use ($account, $meliOrderId, $status, $payStatus, $shipStatus, $buyer, $shipping, $total, $fee, $data) {
        $ordId = db_upsert('orders', [
            'tenant_id'        => $account['tenant_id'],
            'meli_account_id'  => $account['id'],
            'meli_order_id'    => $meliOrderId,
            'status'           => $status,
            'buyer_meli_id'    => (string)($buyer['id'] ?? ''),
            'buyer_nickname'   => $buyer['nickname'] ?? '',
            'buyer_first_name' => $buyer['first_name'] ?? '',
            'buyer_last_name'  => $buyer['last_name'] ?? '',
            'buyer_email'      => $buyer['email'] ?? '',
            'ship_street'      => trim(($shipping['street_name'] ?? '') . ' ' . ($shipping['street_number'] ?? '')),
            'ship_city'        => $shipping['city']['name'] ?? '',
            'ship_state'       => $shipping['state']['name'] ?? '',
            'ship_zip'         => $shipping['zip_code'] ?? '',
            'total_amount'     => $total,
            'ml_fee_amount'    => $fee,
            'net_amount'       => $total - $fee,
            'payment_status'   => $payStatus,
            'ship_status'      => $shipStatus,
            'has_mediacao'     => 0,
            'idempotency_key'  => 'meli_' . $data['id'],
            'order_date'       => date('Y-m-d H:i:s', strtotime($data['date_created'] ?? 'now')),
        ], ['status', 'payment_status', 'ship_status', 'updated_at']);

        // Itens e baixa de estoque
        foreach ($data['order_items'] ?? [] as $item) {
            $prod = db_one(
                "SELECT id, stock_quantity FROM products WHERE meli_item_id=? AND tenant_id=?",
                [$item['item']['id'], $account['tenant_id']]
            );
            $qty = (int)($item['quantity'] ?? 1);

            db_insert_ignore('order_items', [
                'order_id'    => $ordId,
                'product_id'  => $prod['id'] ?? null,
                'meli_item_id'=> $item['item']['id'],
                'title'       => $item['item']['title'] ?? '',
                'quantity'    => $qty,
                'unit_price'  => (float)($item['unit_price'] ?? 0),
                'total_price' => (float)($item['unit_price'] ?? 0) * $qty,
                'sku'         => $item['item']['seller_sku'] ?? '',
            ]);

            // Baixa estoque
            if ($prod && $payStatus === 'APPROVED') {
                db_query(
                    "UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id=?",
                    [$qty, $prod['id']]
                );
                // Verificar estoque crítico (≤ 3 unidades)
                $novoEstoque = max(0, (int)$prod['stock_quantity'] - $qty);
                if ($novoEstoque <= 3) {
                    try {
                        require_once dirname(__DIR__) . '/whatsapp.php';
                        wpp_notify_estoque_critico($prod['title'] ?? 'Produto', $novoEstoque, 3);
                    } catch(Throwable $e) {}
                }
            }
        }

        // Transação financeira
        db_upsert('transactions', [
            'tenant_id'     => $account['tenant_id'],
            'order_id'      => $ordId,
            'type'          => 'SALE',
            'category'      => 'REVENUE',
            'description'   => "Venda {$meliOrderId}",
            'amount'        => $total,
            'direction'     => 'CREDIT',
            'dre_category'  => 'RECEITA_BRUTA',
            'reference_date'=> date('Y-m-d'),
        ], ['amount']);

        if ($fee > 0) {
            db_upsert('transactions', [
                'tenant_id'     => $account['tenant_id'],
                'order_id'      => $ordId,
                'type'          => 'ML_FEE',
                'category'      => 'MARKETPLACE_FEE',
                'description'   => "Taxa ML {$meliOrderId}",
                'amount'        => $fee,
                'direction'     => 'DEBIT',
                'dre_category'  => 'DEDUCOES',
                'reference_date'=> date('Y-m-d'),
            ], ['amount']);
        }
    });

    log_worker("Inserted order {$meliOrderId}");
}

/**
 * questions — Prioridade 8
 * Recebe pergunta pré-venda, salva no SAC e responde automaticamente com IA.
 */
function processQuestion(string $resource, array $account): void {
    $data = meli_get($resource, $account['access_token_enc']);
    if (!$data || empty($data['id'])) throw new Exception("Empty question data");

    $questionId   = (string)$data['id'];
    $questionText = $data['text'] ?? '';
    $itemId       = $data['item_id'] ?? null;
    $tenantId     = $account['tenant_id'];
    $token        = $account['access_token_enc'];

    log_worker("Received question #{$questionId}: \"{$questionText}\"");

    // Busca título do anúncio para enriquecer o cache
    $itemTitle = null;
    if ($itemId) {
        $itemRow = db_one("SELECT title FROM products WHERE meli_item_id=? AND tenant_id=?", [$itemId, $tenantId]);
        $itemTitle = $itemRow['title'] ?? null;
    }

    // Salva no banco local (cache) — idempotente via meli_question_id
    db_insert_ignore('questions', [
        'tenant_id'        => $tenantId,
        'meli_account_id'  => $account['id'],
        'meli_question_id' => $questionId,
        'meli_item_id'     => $itemId,
        'item_title'       => $itemTitle,
        'buyer_nickname'   => $data['from']['nickname'] ?? 'Comprador',
        'buyer_meli_id'    => (string)($data['from']['id'] ?? ''),
        'question_text'    => $questionText,
        'status'           => 'UNANSWERED',
        'created_at'       => date('Y-m-d H:i:s', strtotime($data['date_created'] ?? 'now')),
    ]);
    log_worker("Saved question #{$questionId} to local cache");

    // ── Robô IA — resposta automática ────────────────────────
    $botEnabled = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='ai_bot_questions'",
        [$tenantId]
    );
    if (($botEnabled['value'] ?? '0') !== '1') { log_worker("AI bot desabilitado para tenant {$tenantId}"); return; }

    // Contexto do anúncio
    $itemContext = '';
    if ($itemId) {
        $item = meli_get("/items/{$itemId}", $token);
        if ($item) {
            $itemContext  = "PRODUTO:\n- Título: " . ($item['title'] ?? '') . "\n";
            $itemContext .= "- Preço: R$ " . number_format($item['price'] ?? 0, 2, ',', '.') . "\n";
            $itemContext .= "- Condição: " . ($item['condition'] === 'new' ? 'Novo' : 'Usado') . "\n";
            if (!empty($item['shipping']['free_shipping'])) $itemContext .= "- Frete: Grátis\n";
            foreach (array_slice($item['attributes'] ?? [], 0, 8) as $attr) {
                if (!empty($attr['name']) && !empty($attr['value_name']))
                    $itemContext .= "- {$attr['name']}: {$attr['value_name']}\n";
            }
        }
    }

    // Perguntas anteriores respondidas
    $qaContext = '';
    if ($itemId) {
        $prev = meli_get("/questions/search?item_id={$itemId}&status=ANSWERED&limit=5", $token);
        if (!empty($prev['questions'])) {
            $qaContext = "\nPERGUNTAS E RESPOSTAS ANTERIORES:\n";
            foreach ($prev['questions'] as $q) {
                if (!empty($q['answer']['text']))
                    $qaContext .= "P: {$q['text']}\nR: {$q['answer']['text']}\n\n";
            }
        }
    }

    // Instruções customizadas do vendedor
    $instrRow   = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='ai_bot_instructions'", [$tenantId]);
    $customCtx  = ($instrRow['value'] ?? '') ? "\nINSTRUÇÕES DO VENDEDOR:\n{$instrRow['value']}\n" : '';

    $prompt = "Você é um assistente de vendas do Mercado Livre. Responda a pergunta do comprador de forma clara, objetiva e amigável em português brasileiro.

{$itemContext}{$qaContext}{$customCtx}
REGRAS:
- Seja direto e objetivo (máximo 3 frases)
- Não use saudações como 'Olá' ou 'Bom dia'
- Não mencione que você é uma IA
- Se não souber a resposta com certeza, oriente o comprador a entrar em contato antes de comprar

PERGUNTA DO COMPRADOR: \"{$questionText}\"

Responda apenas com o texto da resposta, sem aspas e sem explicações adicionais.";

    $aiResult = ai_generate_for($tenantId, 'perguntas', $prompt, 300);
    $aiAnswer  = $aiResult['text'];
    if (!$aiAnswer) { log_worker("AI question #{$questionId}: sem resposta da IA ({$aiResult['provider']})"); return; }
    log_worker("AI question #{$questionId}: usando provider {$aiResult['provider']}");

    // Publica resposta no ML
    $result = curl_ml("https://api.mercadolibre.com/answers", [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['question_id' => (int)$questionId, 'text' => $aiAnswer]),
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
        ],
    ]);

    if ($result['code'] >= 200 && $result['code'] < 300) {
        // Atualiza status no cache local
        db_update('questions',
            ['status' => 'ANSWERED', 'answer_text' => $aiAnswer, 'answer_by_robot' => 1, 'answered_at' => date('Y-m-d H:i:s')],
            'meli_question_id=? AND tenant_id=?',
            [$questionId, $tenantId]
        );
        audit_log('AI_QUESTION_ANSWERED', 'questions', $questionId, null, ['answer' => $aiAnswer]);
        log_worker("AI question #{$questionId}: respondida automaticamente — \"{$aiAnswer}\"");
    } else {
        log_worker("AI question #{$questionId}: falha ao publicar HTTP {$result['code']}");
    }
}

/**
 * messages — Prioridade 5
 * Insere mensagens do SAC. Idempotente via meli_message_id.
 */
function processMessage(string $resource, array $account): void {
    preg_match('/packs\/(\d+)/', $resource, $m);
    $packId = $m[1] ?? '';
    if (!$packId) return;

    $userId = $account['meli_user_id'];
    $data   = meli_get("/messages/packs/{$packId}/sellers/{$userId}?tag=post_sale", $account['access_token_enc']);
    if (!$data || empty($data['messages'])) return;

    $order = db_one(
        "SELECT id FROM orders WHERE meli_order_id LIKE ? AND tenant_id=?",
        ['%' . $packId . '%', $account['tenant_id']]
    );

    $inserted = 0;
    foreach ($data['messages'] as $msg) {
        $result = db_insert_ignore('sac_messages', [
            'tenant_id'       => $account['tenant_id'],
            'meli_account_id' => $account['id'],
            'order_id'        => $order['id'] ?? null,
            'meli_pack_id'    => $packId,
            'meli_message_id' => $msg['id'],
            'from_role'       => ($msg['from']['user_id'] == $userId) ? 'SELLER' : 'BUYER',
            'from_nickname'   => $msg['from']['name'] ?? '',
            'from_meli_id'    => (string)($msg['from']['user_id'] ?? ''),
            'message_text'    => $msg['text'] ?? '',
            'is_read'         => 0,
            'created_at'      => date('Y-m-d H:i:s', strtotime($msg['message_date']['received'] ?? 'now')),
        ]);
        if ($result) $inserted++;
    }

    log_worker("Messages pack#{$packId}: {$inserted} inserted");
}

/**
 * payments — Prioridade 5
 * Atualiza status de pagamento e baixa estoque se aprovado.
 */
function processPayment(string $resource, array $account): void {
    $data = meli_get($resource, $account['access_token_enc']);
    if (!$data) throw new Exception("Empty payment data");

    $meliOrderId = '#' . ($data['order']['id'] ?? '');
    $newStatus   = strtoupper($data['status'] ?? 'PENDING');

    $order = db_one(
        "SELECT id, payment_status FROM orders WHERE meli_order_id=? AND tenant_id=?",
        [$meliOrderId, $account['tenant_id']]
    );
    if (!$order) return;

    db_update('orders', ['payment_status' => $newStatus], 'id=?', [$order['id']]);

    // Baixa estoque quando pagamento é aprovado pela primeira vez
    if ($newStatus === 'APPROVED' && $order['payment_status'] !== 'APPROVED') {
        $items = db_all("SELECT product_id, quantity FROM order_items WHERE order_id=?", [$order['id']]);
        foreach ($items as $item) {
            if ($item['product_id']) {
                db_query(
                    "UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE id=?",
                    [$item['quantity'], $item['product_id']]
                );
            }
        }
    }

    log_worker("Payment {$meliOrderId} -> {$newStatus}");

    // Mensagens automáticas pós-venda — payment_approved
    if ($newStatus === 'APPROVED' && $order['payment_status'] !== 'APPROVED') {
        dispatch_auto_messages('payment_approved', $order['id'], $meliOrderId, $account);
    }
}

/**
 * shipments — Prioridade 3
 * Atualiza status de envio.
 */
function processShipment(string $resource, array $account): void {
    $data = meli_get($resource, $account['access_token_enc']);
    if (!$data) throw new Exception("Empty shipment data");

    $meliOrderId = '#' . ($data['order_id'] ?? '');
    $shipStatus  = mapShipStatus($data['status'] ?? '');

    db_query(
        "UPDATE orders SET ship_status=? WHERE meli_order_id=? AND tenant_id=?",
        [$shipStatus, $meliOrderId, $account['tenant_id']]
    );

    log_worker("Shipment {$meliOrderId} -> {$shipStatus}");

    // Mensagens automáticas pós-venda — shipped
    if ($shipStatus === 'SHIPPED') {
        $order = db_one("SELECT id FROM orders WHERE meli_order_id=? AND tenant_id=?", [$meliOrderId,$account['tenant_id']]);
        if ($order) dispatch_auto_messages('shipped', $order['id'], $meliOrderId, $account);
    }
}

/**
 * items — Prioridade 1
 * Sincroniza preço, estoque e status do anúncio.
 */
function processItem(string $resource, array $account): void {
    $data = meli_get($resource, $account['access_token_enc']);
    if (!$data || empty($data['id'])) return;

    $update = [
        'ml_status'      => strtoupper($data['status'] ?? 'ACTIVE'),
        'price'          => (float)($data['price'] ?? 0),
        'stock_quantity' => (int)($data['available_quantity'] ?? 0),
    ];

    db_query(
        "UPDATE products SET ml_status=?, price=?, stock_quantity=?
         WHERE meli_item_id=? AND tenant_id=?",
        [...array_values($update), $data['id'], $account['tenant_id']]
    );

    log_worker("Item {$data['id']} synced");
}

// ════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════

function meli_get(string $resource, string $token, int $attempt = 0): ?array {
    $url    = str_starts_with($resource, 'http') ? $resource : 'https://api.mercadolibre.com' . $resource;
    $result = curl_ml($url, [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}", "User-Agent: SAM-ERP/1.0"],
        CURLOPT_TIMEOUT    => 10,
    ]);

    $body     = $result['body'];
    $httpCode = $result['code'];
    $err      = $result['error'];

    if ($err) throw new Exception("cURL: {$err}");

    // Rate limit — backoff exponencial (máx 3 tentativas)
    if ($httpCode === 429) {
        if ($attempt >= 2) throw new Exception("Rate limited após {$attempt} tentativas");
        $wait = pow(2, $attempt + 1); // 2s, 4s
        log_worker("Rate limited (429) — aguardando {$wait}s (tentativa " . ($attempt+1) . ")");
        sleep($wait);
        return meli_get($resource, $token, $attempt + 1);
    }

    // Token expirado durante o processamento
    if ($httpCode === 401) throw new Exception("Token expirado — será renovado pelo cron");

    // Erro do servidor ML — tenta uma vez mais
    if ($httpCode >= 500 && $attempt === 0) {
        sleep(2);
        return meli_get($resource, $token, 1);
    }

    return $body ? json_decode($body, true) : null;
}

/**
 * Dispara mensagens automáticas para um pedido em um evento específico
 */
function dispatch_auto_messages(string $event, string $orderId, string $meliOrderId, array $account): void {
    $tenantId = $account['tenant_id'];

    // Busca mensagens ativas para este evento
    $messages = db_all(
        "SELECT * FROM auto_messages WHERE tenant_id=? AND trigger_event=? AND is_active=1",
        [$tenantId, $event]
    );
    if (empty($messages)) return;

    // Busca dados do pedido para personalizar a mensagem
    $order = db_one(
        "SELECT o.*, oi.meli_item_id, oi.title as product_title
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.id=? LIMIT 1",
        [$orderId]
    );
    if (!$order) return;

    $token = $account['access_token_enc'];

    foreach ($messages as $msg) {
        // Verifica se já foi enviada para este pedido
        $alreadySent = db_one(
            "SELECT id FROM auto_messages_log WHERE auto_message_id=? AND order_id=?",
            [$msg['id'], $orderId]
        );
        if ($alreadySent) continue;

        // Personaliza a mensagem com variáveis
        $body = $msg['body'];
        $body = str_replace('{comprador}',      $order['buyer_nickname']   ?? 'comprador',     $body);
        $body = str_replace('{primeiro_nome}',  $order['buyer_first_name'] ?? 'comprador',     $body);
        $body = str_replace('{produto}',        $order['product_title']    ?? 'seu produto',   $body);
        $body = str_replace('{pedido}',         $meliOrderId,                                  $body);
        $body = str_replace('{valor}',          'R$ ' . number_format((float)$order['total_amount'], 2, ',', '.'), $body);

        // Envia via API ML — mensagem ao comprador
        $buyerId = $order['buyer_meli_id'] ?? '';
        if (!$buyerId) continue;

        $result = curl_ml("https://api.mercadolibre.com/messages/packs/{$meliOrderId}/sellers/{$account['meli_user_id']}", [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => json_encode([
                'from' => ['user_id' => (int)($account['meli_user_id'] ?? 0)],
                'to'   => ['user_id' => (int)$buyerId],
                'text' => $body,
            ]),
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
        ]);

        if ($result['code'] >= 200 && $result['code'] < 300) {
            // Registra no log
            db_insert('auto_messages_log', [
                'id'              => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff)),
                'tenant_id'       => $tenantId,
                'auto_message_id' => $msg['id'],
                'order_id'        => $orderId,
                'meli_order_id'   => $meliOrderId,
            ]);
            db_query("UPDATE auto_messages SET sent_count=sent_count+1 WHERE id=?", [$msg['id']]);
            log_worker("Auto message '{$msg['title']}' sent for order {$meliOrderId}");
        } else {
            log_worker("Auto message '{$msg['title']}' FAILED for order {$meliOrderId}: HTTP {$result['code']}");
        }
    }
}

function mapShipStatus(string $status): string {
    return match($status) {
        'handling','ready_to_ship' => 'READY_TO_SHIP',
        'shipped'                  => 'SHIPPED',
        'delivered'                => 'DELIVERED',
        'cancelled'                => 'CANCELLED',
        'returned'                 => 'RETURNED',
        default                    => 'PENDING',
    };
}

function log_worker(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] [WORKER] ' . $msg . PHP_EOL;
    echo $line;
    @file_put_contents(
        dirname(__DIR__) . '/storage/logs/worker.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}
