<?php
/**
 * api/sync_orders.php
 * Busca pedidos históricos via GET /orders/search?seller=USER_ID
 * Conta: BUTOBARCELOS (id: b1d51b1c-0db1-4ade-955a-8558ae27ed47)
 *
 * Uso via cron ou chamada direta (autenticado):
 *   php sync_orders.php [--account_id=UUID] [--days=30]
 *
 * Via HTTP (requer login):
 *   POST /api/sync_orders.php  {"account_id":"...", "days":30}
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

// Permite rodar via CLI (cron) ou HTTP
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    session_start_secure();
    auth_require();
    license_check();
    header('Content-Type: application/json');
}

// ─── Parâmetros ─────────────────────────────────────────────────
if ($isCli) {
    $opts       = getopt('', ['account_id::', 'days::']);
    $accountId  = $opts['account_id'] ?? null;
    $days       = (int)($opts['days'] ?? 30);
    $tenantId   = null; // será preenchido a partir da conta
} else {
    $user       = auth_user();
    $tenantId   = $user['tenant_id'];
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $accountId  = $body['account_id'] ?? $_GET['account_id'] ?? null;
    $days       = (int)($body['days'] ?? $_GET['days'] ?? 30);
}

$days = max(1, min($days, 365));

// ─── Buscar conta(s) ────────────────────────────────────────────
if ($accountId) {
    $where  = $tenantId
        ? "WHERE id = ? AND tenant_id = ? AND is_active = 1"
        : "WHERE id = ? AND is_active = 1";
    $params = $tenantId ? [$accountId, $tenantId] : [$accountId];
    $accounts = db_all("SELECT * FROM meli_accounts {$where}", $params);
} else {
    // Todas as contas ativas do tenant (ou do sistema se CLI sem filtro)
    $where    = $tenantId ? "WHERE tenant_id = ? AND is_active = 1" : "WHERE is_active = 1";
    $params   = $tenantId ? [$tenantId] : [];
    $accounts = db_all("SELECT * FROM meli_accounts {$where}", $params);
}

if (empty($accounts)) {
    $msg = 'Nenhuma conta ML ativa encontrada.';
    _out(false, $msg);
    exit;
}

// ─── Sincronizar cada conta ──────────────────────────────────────
$results = [];
foreach ($accounts as $acct) {
    $results[] = sync_account($acct, $days);
}

_out(true, 'Sincronização concluída.', ['accounts' => $results]);

// ════════════════════════════════════════════════════════════════
function sync_account(array $acct, int $days): array {
    $token = crypto_decrypt_token($acct['access_token_enc'] ?? '');
    if (!$token) {
        return ['account'=>$acct['nickname'],'error'=>'Token inválido ou não descriptografado'];
    }

    $sellerId  = $acct['meli_user_id'];
    $tenantId  = $acct['tenant_id'];
    $acctId    = $acct['id'];
    $dateFrom  = date('Y-m-d', strtotime("-{$days} days")) . 'T00:00:00.000-03:00';
    $offset    = 0;
    $limit     = 50;
    $total     = null;
    $imported  = 0;
    $skipped   = 0;
    $errors    = 0;

    _log("[{$acct['nickname']}] Iniciando sync — últimos {$days} dias (a partir de {$dateFrom})");

    do {
        $url = "https://api.mercadolibre.com/orders/search"
             . "?seller={$sellerId}"
             . "&order.date_created.from=" . urlencode($dateFrom)
             . "&sort=date_asc"
             . "&offset={$offset}"
             . "&limit={$limit}";

        $resp = ml_get($url, $token);
        if (!$resp['ok']) {
            _log("[{$acct['nickname']}] ERRO HTTP {$resp['code']}: {$resp['body']}");
            $errors++;
            break;
        }

        $data   = json_decode($resp['body'], true);
        $orders = $data['results'] ?? [];

        if ($total === null) {
            $total = (int)($data['paging']['total'] ?? 0);
            _log("[{$acct['nickname']}] Total de pedidos no período: {$total}");
        }

        foreach ($orders as $o) {
            $r = upsert_order($o, $tenantId, $acctId);
            if ($r === 'inserted') $imported++;
            elseif ($r === 'skipped') $skipped++;
            else $errors++;
        }

        $offset += $limit;
        usleep(200000); // 200ms — evitar rate limit

    } while ($offset < $total && count($orders) > 0);

    // Atualizar last_sync_at
    db_update('meli_accounts',
        ['last_sync_at' => date('Y-m-d H:i:s')],
        'id = ?',
        [$acctId]
    );

    $summary = [
        'account'  => $acct['nickname'],
        'total_ml' => $total ?? 0,
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
    ];
    _log("[{$acct['nickname']}] Concluído: importados={$imported}, já existiam={$skipped}, erros={$errors}");
    return $summary;
}

// ─── Upsert de um pedido ─────────────────────────────────────────
function upsert_order(array $o, string $tenantId, string $acctId): string {
    $meliOrderId = (string)($o['id'] ?? '');
    if (!$meliOrderId) return 'error';

    // Idempotência — atualiza status/ship em pedidos existentes
    if (db_exists('orders', 'tenant_id=? AND meli_order_id=?', [$tenantId, $meliOrderId])) {
        $shipStatus    = normalize_ship_status($o);
        $paymentStatus = normalize_payment_status($o);
        $shipmentId    = (string)($o['shipping']['id'] ?? '');
        $upd = ['ship_status'=>$shipStatus, 'payment_status'=>$paymentStatus];
        if ($shipmentId) $upd['meli_shipment_id'] = $shipmentId;
        db_update('orders', $upd, 'tenant_id=? AND meli_order_id=?', [$tenantId, $meliOrderId]);
        return 'skipped';
    }

    $shipping = $o['shipping'] ?? [];
    $buyer    = $o['buyer']    ?? [];
    $tags     = $o['tags']     ?? [];

    $row = [
        'tenant_id'          => $tenantId,
        'meli_account_id'    => $acctId,
        'meli_order_id'      => $meliOrderId,
        'meli_shipment_id'   => (string)($o['shipping']['id'] ?? ''),
        'buyer_nickname'     => $buyer['nickname'] ?? '',
        'ship_status'        => normalize_ship_status($o),
        'payment_status'     => normalize_payment_status($o),
        'ship_city'          => $shipping['receiver_address']['city']['name']    ?? '',
        'ship_state'         => $shipping['receiver_address']['state']['name']   ?? '',
        'has_mediacao'       => in_array('mediation_required', $tags) ? 1 : 0,
        'total_amount'       => (float)($o['total_amount'] ?? 0),
        'order_date'         => date('Y-m-d H:i:s', strtotime($o['date_created'] ?? 'now')),
        'status'             => $o['status'] ?? 'unknown',
        'pdf_printed'        => 0,
        'zpl_printed'        => 0,
        'label_printed'      => 0,
    ];

    try {
        $orderId = db_insert('orders', $row);
    } catch (Throwable $e) {
        _log("ERRO upsert_order {$meliOrderId}: " . $e->getMessage());
        return 'error';
    }

    // Notificar proprietário via WhatsApp se pedido aprovado
    if (in_array($row['payment_status'], ['approved','APPROVED'])) {
        try {
            require_once dirname(__DIR__) . '/whatsapp.php';
            $nickname = db_one("SELECT nickname FROM meli_accounts WHERE id=?", [$acctId])['nickname'] ?? $acctId;
            wpp_notify_novo_pedido($nickname, $meliOrderId, (float)$row['total_amount']);
        } catch(Throwable $e) {}
    }

    // Inserir itens do pedido
    foreach ($o['order_items'] ?? [] as $item) {
        $itemData = $item['item'] ?? [];
        try {
            db_insert('order_items', [
                'tenant_id'    => $tenantId,
                'order_id'     => $orderId,
                'meli_item_id' => $itemData['id'] ?? '',
                'title'        => $itemData['title'] ?? '',
                'quantity'     => (int)($item['quantity'] ?? 1),
                'unit_price'   => (float)($item['unit_price'] ?? 0),
            ]);
        } catch (Throwable $e) {
            _log("ERRO order_item {$meliOrderId}/{$itemData['id']}: " . $e->getMessage());
        }
    }

    return 'inserted';
}

// ─── Helpers ML ─────────────────────────────────────────────────
function ml_get(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Rate limit: espera e tenta novamente
    if ($code === 429) {
        _log("Rate limit 429 — aguardando 2s");
        sleep(2);
        return ml_get($url, $token);
    }

    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $body];
}

function normalize_ship_status(array $o): string {
    $s = $o['shipping']['status'] ?? ($o['order_status'] ?? 'unknown');
    $map = [
        'ready_to_ship' => 'ready_to_ship',
        'shipped'       => 'shipped',
        'delivered'     => 'delivered',
        'not_delivered' => 'not_delivered',
        'cancelled'     => 'cancelled',
        'pending'       => 'pending',
    ];
    return $map[$s] ?? $s;
}

function normalize_payment_status(array $o): string {
    foreach ($o['payments'] ?? [] as $p) {
        if ($p['status'] === 'approved') return 'approved';
    }
    return $o['payments'][0]['status'] ?? 'pending';
}

// ─── Output ─────────────────────────────────────────────────────
function _out(bool $ok, string $msg, array $extra = []): void {
    global $isCli;
    if ($isCli) {
        echo ($ok ? '[OK]' : '[ERRO]') . " {$msg}\n";
        if (!empty($extra['accounts'])) {
            foreach ($extra['accounts'] as $a) {
                $err = $a['error'] ?? null;
                if ($err) {
                    echo "  {$a['account']}: ERRO — {$err}\n";
                } else {
                    echo "  {$a['account']}: importados={$a['imported']}, skipped={$a['skipped']}, erros={$a['errors']}\n";
                }
            }
        }
    } else {
        echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra));
    }
}

function _log(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    $path = __DIR__ . '/../storage/logs/sync_orders.log';
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    global $isCli;
    if ($isCli) echo $line;
}
