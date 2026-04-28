<?php
/**
 * api/crm.php
 * GET  ?action=list&q=&status=&page=   — lista compradores
 * GET  ?action=get&nickname=           — perfil completo + histórico
 * POST action=save_note                — salva nota interna
 * POST action=save_tags                — salva tags
 * POST action=set_status               — muda status (ativo/vip/bloqueado)
 * POST action=sync                     — sincroniza comprador a partir das orders
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$action   = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Garantir tabela existe
try {
    db_query("CREATE TABLE IF NOT EXISTS customers (
        id              VARCHAR(36)   NOT NULL,
        tenant_id       VARCHAR(36)   NOT NULL,
        meli_user_id    VARCHAR(30)   NULL,
        nickname        VARCHAR(100)  NOT NULL,
        first_name      VARCHAR(80)   NULL,
        last_name       VARCHAR(80)   NULL,
        email           VARCHAR(150)  NULL,
        phone           VARCHAR(30)   NULL,
        city            VARCHAR(80)   NULL,
        state           VARCHAR(30)   NULL,
        zip             VARCHAR(15)   NULL,
        tags            JSON          NULL,
        notes           TEXT          NULL,
        status          ENUM('ativo','inativo','bloqueado','vip') NOT NULL DEFAULT 'ativo',
        total_orders    INT           NOT NULL DEFAULT 0,
        total_spent     DECIMAL(14,2) NOT NULL DEFAULT 0,
        avg_ticket      DECIMAL(12,2) NOT NULL DEFAULT 0,
        last_order_at   DATETIME      NULL,
        first_order_at  DATETIME      NULL,
        has_complaints  TINYINT       NOT NULL DEFAULT 0,
        complaint_count INT           NOT NULL DEFAULT 0,
        rating_given    DECIMAL(3,1)  NULL,
        created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_customer (tenant_id, nickname),
        KEY idx_status (tenant_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch(Throwable $e) {}

// ── GET: lista ────────────────────────────────────────────
if ($action === 'list') {
    $q      = trim($_GET['q'] ?? '');
    $status = $_GET['status'] ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 30;
    $offset = ($page - 1) * $limit;

    $where  = ["tenant_id=?"];
    $params = [$tenantId];

    if ($q) {
        $where[]  = "(nickname LIKE ? OR first_name LIKE ? OR email LIKE ?)";
        $like     = "%{$q}%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($status) {
        $where[]  = "status=?";
        $params[] = $status;
    }

    $whereStr = implode(' AND ', $where);

    $total = (int)(db_one("SELECT COUNT(*) as c FROM customers WHERE {$whereStr}", $params)['c'] ?? 0);
    $rows  = db_all(
        "SELECT * FROM customers WHERE {$whereStr}
         ORDER BY total_spent DESC, last_order_at DESC
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    foreach ($rows as &$r) {
        $r['tags'] = json_decode($r['tags'] ?? '[]', true) ?: [];
    }

    echo json_encode(['ok'=>true, 'customers'=>$rows, 'total'=>$total, 'pages'=>ceil($total/$limit)]);
    exit;
}

// ── GET: perfil completo ──────────────────────────────────
if ($action === 'get') {
    $nickname = trim($_GET['nickname'] ?? '');
    if (!$nickname) { echo json_encode(['ok'=>false,'error'=>'nickname obrigatório']); exit; }

    // Auto-sincroniza se não existir
    syncCustomer($tenantId, $nickname);

    $customer = db_one("SELECT * FROM customers WHERE tenant_id=? AND nickname=?", [$tenantId, $nickname]);
    if (!$customer) { echo json_encode(['ok'=>false,'error'=>'Comprador não encontrado']); exit; }
    $customer['tags'] = json_decode($customer['tags'] ?? '[]', true) ?: [];

    // Histórico de pedidos
    $orders = db_all(
        "SELECT o.id, o.meli_order_id, o.order_date, o.total_amount,
                o.ml_fee_amount, o.net_amount, o.payment_status, o.ship_status,
                o.has_mediacao,
                GROUP_CONCAT(oi.title SEPARATOR ' · ') as items
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.tenant_id=? AND o.buyer_nickname=?
         GROUP BY o.id
         ORDER BY o.order_date DESC LIMIT 20",
        [$tenantId, $nickname]
    );

    // Perguntas feitas
    $questions = db_all(
        "SELECT q.question_text, q.date_created, q.status, p.title as product_title
         FROM questions q
         LEFT JOIN products p ON p.meli_item_id = q.item_id AND p.tenant_id = q.tenant_id
         WHERE q.tenant_id=? AND q.from_nickname=?
         ORDER BY q.date_created DESC LIMIT 10",
        [$tenantId, $nickname]
    );

    echo json_encode([
        'ok'        => true,
        'customer'  => $customer,
        'orders'    => $orders,
        'questions' => $questions,
    ]);
    exit;
}

// ── POST ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    if (!$nickname) { echo json_encode(['ok'=>false,'error'=>'nickname obrigatório']); exit; }

    // Garante que o cliente existe
    syncCustomer($tenantId, $nickname);

    if ($action === 'save_note') {
        $note = trim($_POST['note'] ?? '');
        db_query("UPDATE customers SET notes=? WHERE tenant_id=? AND nickname=?", [$note, $tenantId, $nickname]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_tags') {
        $tags = json_decode($_POST['tags'] ?? '[]', true) ?: [];
        $tags = array_values(array_filter(array_map('trim', $tags)));
        db_query("UPDATE customers SET tags=? WHERE tenant_id=? AND nickname=?",
            [json_encode($tags), $tenantId, $nickname]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'set_status') {
        $status = in_array($_POST['status']??'', ['ativo','inativo','bloqueado','vip'])
            ? $_POST['status'] : 'ativo';
        db_query("UPDATE customers SET status=? WHERE tenant_id=? AND nickname=?", [$status, $tenantId, $nickname]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'sync') {
        syncCustomer($tenantId, $nickname, true);
        $customer = db_one("SELECT * FROM customers WHERE tenant_id=? AND nickname=?", [$tenantId, $nickname]);
        if ($customer) $customer['tags'] = json_decode($customer['tags'] ?? '[]', true) ?: [];
        echo json_encode(['ok'=>true, 'customer'=>$customer]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

// ── Sincronização interna ─────────────────────────────────
function syncCustomer(string $tenantId, string $nickname, bool $force = false): void {
    $existing = db_one("SELECT id, updated_at FROM customers WHERE tenant_id=? AND nickname=?", [$tenantId, $nickname]);

    // Só re-sincroniza se forçado ou se nunca foi feito
    if ($existing && !$force) return;

    $agg = db_one(
        "SELECT MAX(o.buyer_meli_id) as meli_user_id,
                MAX(o.buyer_first_name) as first_name,
                MAX(o.buyer_last_name) as last_name,
                MAX(o.buyer_email) as email,
                MAX(o.ship_city) as city,
                MAX(o.ship_state) as state,
                MAX(o.ship_zip) as zip,
                COUNT(DISTINCT o.id) as total_orders,
                SUM(CASE WHEN o.payment_status IN ('approved','APPROVED') THEN o.total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN o.payment_status IN ('approved','APPROVED') THEN o.total_amount ELSE NULL END) as avg_ticket,
                MAX(o.order_date) as last_order_at,
                MIN(o.order_date) as first_order_at,
                SUM(CASE WHEN o.has_mediacao=1 THEN 1 ELSE 0 END) as complaint_count
         FROM orders o
         WHERE o.tenant_id=? AND o.buyer_nickname=?",
        [$tenantId, $nickname]
    );

    if (!$agg || !$agg['total_orders']) return;

    $data = [
        'tenant_id'      => $tenantId,
        'meli_user_id'   => $agg['meli_user_id'] ?? null,
        'nickname'       => $nickname,
        'first_name'     => $agg['first_name'] ?? null,
        'last_name'      => $agg['last_name'] ?? null,
        'email'          => $agg['email'] ?? null,
        'city'           => $agg['city'] ?? null,
        'state'          => $agg['state'] ?? null,
        'zip'            => $agg['zip'] ?? null,
        'total_orders'   => (int)$agg['total_orders'],
        'total_spent'    => (float)$agg['total_spent'],
        'avg_ticket'     => (float)$agg['avg_ticket'],
        'last_order_at'  => $agg['last_order_at'],
        'first_order_at' => $agg['first_order_at'],
        'has_complaints' => (int)$agg['complaint_count'] > 0 ? 1 : 0,
        'complaint_count'=> (int)$agg['complaint_count'],
    ];

    if ($existing) {
        // Preserva tags, notes e status ao atualizar
        unset($data['tenant_id'], $data['nickname']);
        $sets   = implode(',', array_map(fn($k) => "`{$k}`=?", array_keys($data)));
        $params = array_merge(array_values($data), [$tenantId, $nickname]);
        db_query("UPDATE customers SET {$sets} WHERE tenant_id=? AND nickname=?", $params);
    } else {
        $data['id']     = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
        $data['status'] = 'ativo';
        $data['tags']   = '[]';
        $cols   = implode(',', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $phs    = implode(',', array_fill(0, count($data), '?'));
        db_query("INSERT IGNORE INTO customers ({$cols}) VALUES ({$phs})", array_values($data));
    }
}
