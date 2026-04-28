<?php
/**
 * api/badges.php
 * Retorna todos os contadores/badges do sistema em uma única chamada.
 * Chamado a cada 30s pelo polling do layout.php para atualizar badges dinamicamente.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();

header('Content-Type: application/json');

$user = auth_user();
if (!$user) {
    echo json_encode(['ok' => false]);
    exit;
}

$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

$result = [
    'ok'          => true,
    'unread_sac'  => 0,  // msgs não lidas no SAC
    'ready_ship'  => 0,  // pedidos prontos para envio
    'total_alerts'=> 0,  // sino = soma de todos
];

try {
    if ($acctId) {
        // Mensagens SAC não lidas
        $r = db_one(
            "SELECT COUNT(*) as cnt FROM sac_messages
             WHERE tenant_id=? AND meli_account_id=? AND is_read=0 AND from_role='BUYER'
             AND (sentiment_label IS NULL OR sentiment_label != 'PRE_SALE')",
            [$tenantId, $acctId]
        );
        $result['unread_sac'] = (int)($r['cnt'] ?? 0);

        // Pedidos prontos para envio
        $r = db_one(
            "SELECT COUNT(*) as cnt FROM orders
             WHERE tenant_id=? AND meli_account_id=?
               AND ship_status IN ('READY_TO_SHIP','ready_to_ship')
               AND payment_status IN ('APPROVED','approved')",
            [$tenantId, $acctId]
        );
        $result['ready_ship'] = (int)($r['cnt'] ?? 0);

        $result['total_alerts'] = $result['unread_sac'] + $result['ready_ship'];
    }
} catch (Throwable $e) {}

echo json_encode($result);
