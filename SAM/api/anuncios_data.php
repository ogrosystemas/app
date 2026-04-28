<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];
$p        = array_merge([$tenantId], $acctP);

// ── Compradores recentes de um anúncio ────────────────────
$action = $_GET['action'] ?? '';
if ($action === 'buyers') {
    $itemId = trim($_GET['item_id'] ?? '');
    if (!$itemId) { echo json_encode(['ok'=>false,'error'=>'item_id obrigatório']); exit; }

    $buyers = db_all(
        "SELECT DISTINCT o.buyer_nickname, o.order_date, o.total_amount
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         WHERE o.tenant_id=? AND oi.meli_item_id=?
           AND o.payment_status IN ('approved','APPROVED')
         ORDER BY o.order_date DESC LIMIT 8",
        [$tenantId, $itemId]
    );

    foreach ($buyers as &$b) {
        $b['order_date'] = $b['order_date']
            ? date('d/m/Y', strtotime($b['order_date'])) : '—';
    }

    echo json_encode(['ok'=>true, 'buyers'=>$buyers]);
    exit;
}

// KPIs
$total    = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql}", $p)['c']??0);
$ativos   = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND ml_status='ACTIVE'", $p)['c']??0);
$pausados = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND ml_status='PAUSED'", $p)['c']??0);
$criticos = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND stock_quantity <= stock_min AND stock_min > 0", $p)['c']??0);
$avgPrice = (float)(db_one("SELECT AVG(price) as v FROM products WHERE tenant_id=?{$acctSql} AND price > 0", $p)['v']??0);
$stockVal = (float)(db_one("SELECT SUM(price * stock_quantity) as v FROM products WHERE tenant_id=?{$acctSql}", $p)['v']??0);

// Top 5 por margem
$topByMargin = db_all(
    "SELECT title,
            ROUND((price - cost_price - price * ml_fee_percent / 100) / price * 100, 1) as margin
     FROM products
     WHERE tenant_id=?{$acctSql} AND price > 0 AND cost_price > 0
     ORDER BY margin DESC LIMIT 5",
    $p
);

// Distribuição de saúde
$alta  = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND ml_health >= 70", $p)['c']??0);
$media = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND ml_health >= 40 AND ml_health < 70", $p)['c']??0);
$baixa = (int)(db_one("SELECT COUNT(*) as c FROM products WHERE tenant_id=?{$acctSql} AND ml_health < 40 AND ml_health IS NOT NULL", $p)['c']??0);

// Low stock
$lowStock = db_all(
    "SELECT title, stock_quantity, stock_min FROM products
     WHERE tenant_id=?{$acctSql} AND stock_quantity <= stock_min AND stock_min > 0
     ORDER BY stock_quantity ASC LIMIT 5",
    $p
);

echo json_encode([
    'ok'          => true,
    'kpis'        => [
        'total'     => $total,
        'ativos'    => $ativos,
        'pausados'  => $pausados,
        'criticos'  => $criticos,
        'avg_price' => $avgPrice,
        'stock_val' => $stockVal,
    ],
    'top_by_margin' => $topByMargin,
    'health_dist'   => ['Alta' => $alta, 'Media' => $media, 'Baixa' => $baixa],
    'low_stock'     => $lowStock,
]);
