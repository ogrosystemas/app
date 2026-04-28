<?php
/**
 * api/sprint1.php
 * Sprint 1: Banco de respostas prontas + Mensagens automáticas + Preços em massa
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$method   = $_SERVER['REQUEST_METHOD'];
$action   = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Garante tabelas ───────────────────────────────────────
try {
    db_query("CREATE TABLE IF NOT EXISTS quick_replies (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        title VARCHAR(100) NOT NULL, body TEXT NOT NULL,
        tags VARCHAR(255) NULL, uso INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db_query("CREATE TABLE IF NOT EXISTS auto_messages (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        trigger_event ENUM('payment_approved','shipped','delivered') NOT NULL,
        title VARCHAR(100) NOT NULL, body TEXT NOT NULL,
        is_active TINYINT NOT NULL DEFAULT 1, delay_hours INT NOT NULL DEFAULT 0,
        sent_count INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_tenant (tenant_id),
        KEY idx_event (tenant_id, trigger_event, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db_query("CREATE TABLE IF NOT EXISTS auto_messages_log (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        auto_message_id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL,
        meli_order_id VARCHAR(30) NOT NULL,
        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_msg_order (auto_message_id, order_id),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

function gen_uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
}

// ════════════════════════════════════════════════════════
// RESPOSTAS PRONTAS
// ════════════════════════════════════════════════════════

if ($action === 'list_replies') {
    $q    = trim($_GET['q'] ?? '');
    $sql  = "SELECT * FROM quick_replies WHERE tenant_id=?";
    $params = [$tenantId];
    if ($q) { $sql .= " AND (title LIKE ? OR body LIKE ? OR tags LIKE ?)"; $params = array_merge($params, ["%$q%","%$q%","%$q%"]); }
    $sql .= " ORDER BY uso DESC, updated_at DESC LIMIT 50";
    echo json_encode(['ok'=>true, 'replies'=>db_all($sql, $params)]);
    exit;
}

if ($action === 'save_reply') {
    $id    = trim($_POST['id']    ?? '');
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body']  ?? '');
    $tags  = trim($_POST['tags']  ?? '');
    if (!$title || !$body) { echo json_encode(['ok'=>false,'error'=>'Título e resposta obrigatórios']); exit; }
    if ($id) {
        db_update('quick_replies', ['title'=>$title,'body'=>$body,'tags'=>$tags], 'id=? AND tenant_id=?', [$id,$tenantId]);
    } else {
        $id = gen_uuid();
        db_insert('quick_replies', ['id'=>$id,'tenant_id'=>$tenantId,'title'=>$title,'body'=>$body,'tags'=>$tags]);
    }
    echo json_encode(['ok'=>true,'id'=>$id]);
    exit;
}

if ($action === 'delete_reply') {
    $id = trim($_POST['id'] ?? '');
    if ($id) db_query("DELETE FROM quick_replies WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'use_reply') {
    $id = trim($_POST['id'] ?? '');
    if ($id) db_query("UPDATE quick_replies SET uso=uso+1 WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    $reply = db_one("SELECT body FROM quick_replies WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    echo json_encode(['ok'=>true,'body'=>$reply['body']??'']);
    exit;
}

// ════════════════════════════════════════════════════════
// MENSAGENS AUTOMÁTICAS
// ════════════════════════════════════════════════════════

if ($action === 'list_auto_messages') {
    $msgs = db_all("SELECT * FROM auto_messages WHERE tenant_id=? ORDER BY trigger_event, created_at", [$tenantId]);
    echo json_encode(['ok'=>true,'messages'=>$msgs]);
    exit;
}

if ($action === 'save_auto_message') {
    $id      = trim($_POST['id']            ?? '');
    $event   = trim($_POST['trigger_event'] ?? '');
    $title   = trim($_POST['title']         ?? '');
    $body    = trim($_POST['body']          ?? '');
    $active  = ($_POST['is_active'] ?? '1') === '1' ? 1 : 0;
    $delay   = max(0, (int)($_POST['delay_hours'] ?? 0));
    $events  = ['payment_approved','shipped','delivered'];
    if (!in_array($event,$events)) { echo json_encode(['ok'=>false,'error'=>'Evento inválido']); exit; }
    if (!$title || !$body) { echo json_encode(['ok'=>false,'error'=>'Título e mensagem obrigatórios']); exit; }
    if ($id) {
        db_update('auto_messages', ['trigger_event'=>$event,'title'=>$title,'body'=>$body,'is_active'=>$active,'delay_hours'=>$delay], 'id=? AND tenant_id=?', [$id,$tenantId]);
    } else {
        $id = gen_uuid();
        db_insert('auto_messages', ['id'=>$id,'tenant_id'=>$tenantId,'trigger_event'=>$event,'title'=>$title,'body'=>$body,'is_active'=>$active,'delay_hours'=>$delay]);
    }
    echo json_encode(['ok'=>true,'id'=>$id]);
    exit;
}

if ($action === 'delete_auto_message') {
    $id = trim($_POST['id'] ?? '');
    if ($id) db_query("DELETE FROM auto_messages WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'toggle_auto_message') {
    $id = trim($_POST['id'] ?? '');
    if ($id) db_query("UPDATE auto_messages SET is_active = 1-is_active WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    $msg = db_one("SELECT is_active FROM auto_messages WHERE id=? AND tenant_id=?", [$id,$tenantId]);
    echo json_encode(['ok'=>true,'is_active'=>$msg['is_active']??0]);
    exit;
}

// ════════════════════════════════════════════════════════
// PREÇOS EM MASSA
// ════════════════════════════════════════════════════════

if ($action === 'list_products_price') {
    $acctSql = $acctId ? " AND meli_account_id=?" : "";
    $acctP   = $acctId ? [$acctId] : [];
    $q       = trim($_GET['q'] ?? '');
    $search  = $q ? " AND title LIKE ?" : "";
    $searchP = $q ? ["%$q%"] : [];
    $products = db_all(
        "SELECT id, meli_item_id, title, price, stock_quantity, ml_status
         FROM products
         WHERE tenant_id=?{$acctSql}{$search}
           AND meli_item_id IS NOT NULL
           AND ml_status IN ('ACTIVE','PAUSED')
         ORDER BY title ASC LIMIT 100",
        array_merge([$tenantId], $acctP, $searchP)
    );
    echo json_encode(['ok'=>true,'products'=>$products]);
    exit;
}

if ($action === 'update_prices') {
    if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Selecione uma conta ML']); exit; }

    $updates = json_decode($_POST['updates'] ?? '[]', true);
    if (empty($updates)) { echo json_encode(['ok'=>false,'error'=>'Nenhum produto selecionado']); exit; }

    $acct = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId,$tenantId]);
    if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada']); exit; }
    $token = crypto_decrypt_token($acct['access_token_enc']);

    $ok = 0; $errors = [];

    foreach ($updates as $u) {
        $productId = $u['id']    ?? '';
        $newPrice  = (float)($u['price'] ?? 0);
        if (!$productId || $newPrice <= 0) continue;

        $product = db_one("SELECT meli_item_id FROM products WHERE id=? AND tenant_id=?", [$productId,$tenantId]);
        if (!$product) continue;

        $result = curl_ml("https://api.mercadolibre.com/items/{$product['meli_item_id']}", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS    => json_encode(['price' => $newPrice]),
            CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
        ]);

        if ($result['code'] === 200) {
            db_update('products', ['price'=>$newPrice], 'id=? AND tenant_id=?', [$productId,$tenantId]);
            audit_log('PRICE_UPDATE', 'products', $productId, null, ['price'=>$newPrice]);
            $ok++;
        } else {
            $err = json_decode($result['body'],true);
            $errors[] = ($product['meli_item_id'] ?? $productId) . ': ' . ($err['message'] ?? "HTTP {$result['code']}");
        }
    }

    echo json_encode(['ok'=>true,'updated'=>$ok,'errors'=>$errors]);
    exit;
}

if ($action === 'bulk_price_adjust') {
    // Ajuste percentual ou fixo em todos os produtos filtrados
    if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Selecione uma conta ML']); exit; }

    $type    = $_POST['type']    ?? 'percent'; // percent | fixed
    $value   = (float)($_POST['value'] ?? 0);
    $op      = $_POST['op']      ?? 'increase'; // increase | decrease
    $ids     = json_decode($_POST['ids'] ?? '[]', true);
    if (empty($ids) || $value <= 0) { echo json_encode(['ok'=>false,'error'=>'Selecione produtos e valor']); exit; }

    $acct  = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId,$tenantId]);
    $token = crypto_decrypt_token($acct['access_token_enc']);

    $ok = 0; $errors = [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $products = db_all("SELECT id, meli_item_id, price FROM products WHERE id IN ($placeholders) AND tenant_id=?",
        array_merge($ids, [$tenantId]));

    foreach ($products as $p) {
        $current = (float)$p['price'];
        if ($type === 'percent') {
            $newPrice = $op === 'increase' ? $current * (1 + $value/100) : $current * (1 - $value/100);
        } else {
            $newPrice = $op === 'increase' ? $current + $value : $current - $value;
        }
        $newPrice = max(0.01, round($newPrice, 2));

        $result = curl_ml("https://api.mercadolibre.com/items/{$p['meli_item_id']}", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS    => json_encode(['price' => $newPrice]),
            CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
        ]);

        if ($result['code'] === 200) {
            db_update('products', ['price'=>$newPrice], 'id=? AND tenant_id=?', [$p['id'],$tenantId]);
            $ok++;
        } else {
            $errors[] = $p['meli_item_id'];
        }
    }

    echo json_encode(['ok'=>true,'updated'=>$ok,'errors'=>$errors]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
