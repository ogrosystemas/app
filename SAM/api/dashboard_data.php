<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$today    = date('Y-m-d');
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

// Build safe parameterized filter
$acctSql    = $acctId ? " AND meli_account_id = ?" : "";
$acctSqlO   = $acctId ? " AND o.meli_account_id = ?" : "";
$acctP      = $acctId ? [$acctId] : [];

$sales = db_one(
    "SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as cnt
     FROM orders WHERE tenant_id=? AND DATE(order_date)=? AND status!='CANCELLED'{$acctSql}",
    array_merge([$tenantId, $today], (array)$acctP)
);

$sac = db_one(
    "SELECT COUNT(*) as cnt FROM sac_messages
     WHERE tenant_id=? AND is_read=0 AND from_role='BUYER'",
    [$tenantId]
);

$toShip = db_one(
    "SELECT COUNT(*) as cnt FROM orders
     WHERE tenant_id=? AND ship_status='READY_TO_SHIP'{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);

$late = db_one(
    "SELECT COUNT(*) as cnt FROM orders
     WHERE tenant_id=? AND ship_status='READY_TO_SHIP'
     AND order_date < DATE_SUB(NOW(), INTERVAL 2 DAY){$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);

$lowStock = db_one(
    "SELECT COUNT(*) as cnt FROM products
     WHERE tenant_id=? AND stock_quantity <= stock_min{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);

$chart7 = db_all(
    "SELECT DATE(order_date) as day, SUM(total_amount) as total
     FROM orders
     WHERE tenant_id=? AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     AND status != 'CANCELLED'{$acctSql}
     GROUP BY DATE(order_date) ORDER BY day",
    array_merge([$tenantId], (array)$acctP)
);

$byStatus = db_all(
    "SELECT ship_status, COUNT(*) as cnt FROM orders
     WHERE tenant_id=?{$acctSql} GROUP BY ship_status",
    array_merge([$tenantId], (array)$acctP)
);

echo json_encode([
    'ok'       => true,
    'sales'    => ['total' => (float)$sales['total'], 'cnt' => (int)$sales['cnt']],
    'sac'      => (int)$sac['cnt'],
    'to_ship'  => (int)$toShip['cnt'],
    'late'     => (int)$late['cnt'],
    'low_stock'=> (int)$lowStock['cnt'],
    'chart7'   => $chart7,
    'by_status'=> $byStatus,
]);
