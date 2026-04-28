<?php
/**
 * api/logistica_data.php — Pedidos, etiquetas, NF
 * Fix: (array)$acctP em todos os array_merge (linha 49 original)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();
auth_require();
session_write_close();
license_check();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND o.meli_account_id = ?" : "";
$acctP    = $acctId ? [$acctId] : []; // NUNCA null

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── GET actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Listagem de pedidos
    if ($action === 'list') {
        $shipStatus = $_GET['ship_status'] ?? 'all';
        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $limit      = 25;
        $offset     = ($page - 1) * $limit;

        $shipSql = '';
        $shipP   = [];
        if ($shipStatus !== 'all') {
            $shipSql = " AND o.ship_status = ?";
            $shipP   = [$shipStatus];
        }

        $searchSql = '';
        $searchP   = [];
        if ($search !== '') {
            $searchSql = " AND (o.buyer_nickname LIKE ? OR o.meli_order_id LIKE ? OR o.nf_number LIKE ?)";
            $searchP   = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }

        // FIX: (array)$acctP previne TypeError quando $acctP=[]
        $params = array_merge(
            [$tenantId],
            (array)$acctP,
            $shipP,
            $searchP
        );

        $rows = db_all(
            "SELECT o.id, o.meli_order_id, o.buyer_nickname,
                    o.ship_status, o.payment_status,
                    o.ship_city, o.ship_state, o.has_mediacao,
                    o.total_amount, o.order_date, o.status,
                    o.nf_path, o.nf_number, o.nf_key, o.nf_fetched_at,
                    o.pdf_printed, o.zpl_printed, o.label_printed,
                    ma.nickname AS account_nickname
             FROM orders o
             LEFT JOIN meli_accounts ma ON ma.id = o.meli_account_id
             WHERE o.tenant_id = ?{$acctSql}{$shipSql}{$searchSql}
             ORDER BY o.order_date DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $countParams = array_merge([$tenantId], (array)$acctP, $shipP, $searchP);
        $total = db_one(
            "SELECT COUNT(*) AS cnt
             FROM orders o
             WHERE o.tenant_id = ?{$acctSql}{$shipSql}{$searchSql}",
            $countParams
        );

        // KPIs resumo de envio
        $kpiParams = array_merge([$tenantId], (array)$acctP);
        $kpis = db_all(
            "SELECT ship_status, COUNT(*) AS cnt
             FROM orders o
             WHERE o.tenant_id = ?{$acctSql}
             GROUP BY ship_status",
            $kpiParams
        );

        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'total'   => (int)($total['cnt'] ?? 0),
            'page'    => $page,
            'pages'   => ceil(($total['cnt'] ?? 0) / $limit),
            'kpis'    => $kpis,
        ]);
        exit;
    }

    // Detalhe de um pedido
    if ($action === 'detail') {
        $orderId = $_GET['order_id'] ?? '';
        if (!$orderId) { echo json_encode(['success'=>false,'error'=>'order_id obrigatório']); exit; }

        $order = db_one(
            "SELECT o.*, ma.nickname AS account_nickname, ma.meli_user_id
             FROM orders o
             LEFT JOIN meli_accounts ma ON ma.id = o.meli_account_id
             WHERE o.id = ? AND o.tenant_id = ?",
            [$orderId, $tenantId]
        );
        if (!$order) { echo json_encode(['success'=>false,'error'=>'Pedido não encontrado']); exit; }

        $items = db_all(
            "SELECT oi.*, p.title, p.gtin
             FROM order_items oi
             LEFT JOIN products p ON p.meli_item_id = oi.meli_item_id AND p.tenant_id = oi.tenant_id
             WHERE oi.order_id = ? AND oi.tenant_id = ?",
            [$orderId, $tenantId]
        );

        echo json_encode(['success'=>true, 'order'=>$order, 'items'=>$items]);
        exit;
    }

    // Buscar NF de um pedido
    if ($action === 'fetch_nf') {
        $orderId = $_GET['order_id'] ?? '';
        if (!$orderId) { echo json_encode(['success'=>false,'error'=>'order_id obrigatório']); exit; }

        // Delega ao endpoint dedicado
        header('Location: /api/fiscal_note.php?order_id=' . urlencode($orderId));
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Ação desconhecida']);
    exit;
}

// ─── POST actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;

    // Marcar etiqueta impressa
    if ($action === 'mark_printed') {
        $orderId = $body['order_id'] ?? '';
        $type    = $body['type']     ?? ''; // pdf | zpl | label

        if (!$orderId || !in_array($type, ['pdf','zpl','label'])) {
            echo json_encode(['success'=>false,'error'=>'Parâmetros inválidos']); exit;
        }

        $col = $type . '_printed';
        db_update('orders', [$col => 1], 'id=? AND tenant_id=?', [$orderId, $tenantId]);
        audit_log($tenantId, $user['id'], 'LABEL_PRINTED', 'orders', $orderId, null, ['type'=>$type]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // Atualizar status do pedido manualmente
    if ($action === 'update_status') {
        $orderId = $body['order_id'] ?? '';
        $status  = $body['status']   ?? '';
        $allowed = ['pending','processing','shipped','delivered','cancelled'];

        if (!$orderId || !in_array($status, $allowed)) {
            echo json_encode(['success'=>false,'error'=>'Status inválido']); exit;
        }

        $before = db_one("SELECT status FROM orders WHERE id=? AND tenant_id=?", [$orderId, $tenantId]);
        db_update('orders', ['status'=>$status], 'id=? AND tenant_id=?', [$orderId, $tenantId]);
        audit_log($tenantId, $user['id'], 'ORDER_STATUS', 'orders', $orderId,
            ['status'=>$before['status'] ?? null], ['status'=>$status]);
        echo json_encode(['success'=>true]);
        exit;
    }

    // Gerar etiqueta ZPL
    if ($action === 'get_label') {
        $orderId = $body['order_id'] ?? '';
        if (!$orderId) { echo json_encode(['success'=>false,'error'=>'order_id obrigatório']); exit; }

        $order = db_one(
            "SELECT o.meli_order_id, ma.access_token_enc, ma.meli_user_id
             FROM orders o
             JOIN meli_accounts ma ON ma.id = o.meli_account_id
             WHERE o.id = ? AND o.tenant_id = ?",
            [$orderId, $tenantId]
        );
        if (!$order) { echo json_encode(['success'=>false,'error'=>'Pedido não encontrado']); exit; }

        require_once __DIR__ . '/../crypto.php';
        $token = crypto_decrypt_token($order['access_token_enc']);

        $ch = curl_init("https://api.mercadolibre.com/shipments/{$order['meli_order_id']}/labels?response_type=zpl2");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            db_update('orders', ['zpl_printed'=>1], 'id=? AND tenant_id=?', [$orderId, $tenantId]);
            // Retorna ZPL como base64 para o frontend imprimir
            echo json_encode(['success'=>true, 'zpl'=>base64_encode($resp)]);
        } else {
            echo json_encode(['success'=>false,'error'=>"Erro ML HTTP {$httpCode}"]);
        }
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Ação desconhecida']);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Método não suportado']);
