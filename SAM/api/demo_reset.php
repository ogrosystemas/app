<?php
/**
 * api/demo_reset.php
 * Reseta os dados demo para "agora" sem precisar de terminal.
 * Protegido: só ADMIN + MASTER_SECRET no header ou query.
 *
 * USO via browser (logado como admin):
 *   GET /api/demo_reset.php?secret=SEU_MASTER_SECRET
 *
 * USO via cURL:
 *   curl "https://lupa.ogrosystemas.com.br/api/demo_reset.php?secret=SEU_MASTER_SECRET"
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();

// Dupla proteção: sessão admin + MASTER_SECRET
$secret = $_GET['secret'] ?? $_POST['secret'] ?? '';
if (!hash_equals(MASTER_SECRET, $secret)) {
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'Acesso negado']));
}

$user = auth_user();
if (!$user || $user['role'] !== 'ADMIN') {
    http_response_code(403);
    die(json_encode(['ok' => false, 'error' => 'Requer login como ADMIN']));
}

header('Content-Type: application/json');

$tenantId = 'tenant-demo-0001-0000-000000000001';
$acctId   = 'meli-acc-0001-0000-000000000001';

// Verifica se tenant demo existe
if (!db_exists('tenants', 'id=?', [$tenantId])) {
    echo json_encode(['ok'=>false,'error'=>'Tenant demo não encontrado. Execute demo_activate.sql primeiro.']);
    exit;
}

try {
    db_transaction(function() use ($tenantId, $acctId) {

        // 1. Garante conta ativa
        db_update('meli_accounts',
            ['is_active'=>1, 'token_expires_at'=>date('Y-m-d H:i:s', strtotime('+30 days'))],
            'id=?', [$acctId]
        );

        // 2. Pedidos — distribui nos últimos 7 dias
        $orderDates = [
            'order-010' => 'now',
            'order-001' => '-1 day', 'order-008' => '-1 day',
            'order-002' => '-2 days','order-003' => '-2 days',
            'order-004' => '-3 days',
            'order-005' => '-4 days',
            'order-006' => '-5 days',
            'order-007' => '-6 days',
            'order-009' => '-7 days',
        ];
        foreach ($orderDates as $orderId => $offset) {
            $dt = $offset === 'now' ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($offset));
            db_query("UPDATE orders SET order_date=? WHERE id=? AND tenant_id=?", [$dt, $orderId, $tenantId]);
        }

        // 3. SAC mensagens — recentes
        $sacDates = [
            'order-005' => '-2 hours',
            'order-001' => '-3 hours',
            'order-003' => '-5 hours',
            'order-006' => '-23 hours',
        ];
        foreach ($sacDates as $orderId => $offset) {
            $dt = date('Y-m-d H:i:s', strtotime($offset));
            db_query("UPDATE sac_messages SET created_at=? WHERE order_id=? AND tenant_id=?", [$dt, $orderId, $tenantId]);
        }

        // Marca mensagens não lidas
        db_query(
            "UPDATE sac_messages SET is_read=0
             WHERE order_id IN ('order-005','order-001','order-003')
               AND tenant_id=? AND from_role='BUYER'",
            [$tenantId]
        );

        // 4. Conversas SAC
        $convDates = [
            'conv-demo-001' => '-2 hours',
            'conv-demo-002' => '-3 hours',
            'conv-demo-003' => '-5 hours',
            'conv-demo-004' => '-23 hours',
        ];
        foreach ($convDates as $convId => $offset) {
            if (db_exists('sac_conversations', 'id=?', [$convId])) {
                $dt = date('Y-m-d H:i:s', strtotime($offset));
                db_query("UPDATE sac_conversations SET last_message_at=? WHERE id=?", [$dt, $convId]);
            }
        }

        // 5. Transactions — mês atual
        $mes = date('Y-m');
        db_query(
            "UPDATE transactions SET reference_date=DATE_SUB(CURDATE(), INTERVAL 1 DAY)
             WHERE order_id IN ('order-001','order-008') AND tenant_id=?",
            [$tenantId]
        );
        db_query(
            "UPDATE transactions SET reference_date=DATE_SUB(CURDATE(), INTERVAL 5 DAY)
             WHERE order_id='order-006' AND tenant_id=?",
            [$tenantId]
        );
        db_query(
            "UPDATE transactions SET reference_date=DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             WHERE order_id='order-007' AND tenant_id=?",
            [$tenantId]
        );

        // 6. Financial entries — mês atual
        $m = date('Y-m');
        $finDates = [
            'fin-demo-001' => ['10','10','10'],
            'fin-demo-002' => ['14','14','14'],
            'fin-demo-003' => ['17','17','17'],
            'fin-demo-010' => ['05','05','05'],
            'fin-demo-011' => ['08','08','08'],
            'fin-demo-012' => ['11','11','11'],
            'fin-demo-013' => ['13','13','13'],
            'fin-demo-014' => ['15','15','15'],
        ];
        foreach ($finDates as $fid => [$ed,$dd,$pd]) {
            db_query(
                "UPDATE financial_entries SET entry_date=?, due_date=?, paid_date=? WHERE id=?",
                ["{$m}-{$ed}", "{$m}-{$dd}", "{$m}-{$pd}", $fid]
            );
        }
        // Pendentes
        db_query("UPDATE financial_entries SET entry_date=?,due_date=?,paid_date=NULL WHERE id='fin-demo-020'",
            ["{$m}-25","{$m}-25"]);
        db_query("UPDATE financial_entries SET entry_date=?,due_date=?,paid_date=NULL WHERE id='fin-demo-021'",
            ["{$m}-22","{$m}-28"]);
    });

    // Coleta stats
    $stats = [
        'pedidos_hoje'    => db_one("SELECT COUNT(*) c FROM orders WHERE tenant_id=? AND DATE(order_date)=CURDATE()", [$tenantId])['c'],
        'pedidos_7dias'   => db_one("SELECT COUNT(*) c FROM orders WHERE tenant_id=? AND order_date >= DATE_SUB(NOW(),INTERVAL 7 DAY)", [$tenantId])['c'],
        'msgs_nao_lidas'  => db_one("SELECT COUNT(*) c FROM sac_messages WHERE tenant_id=? AND is_read=0 AND from_role='BUYER'", [$tenantId])['c'],
        'receitas_mes'    => db_one("SELECT COALESCE(SUM(amount),0) t FROM financial_entries WHERE tenant_id=? AND direction='CREDIT' AND status='PAID'", [$tenantId])['t'],
        'conta_ativa'     => db_one("SELECT is_active FROM meli_accounts WHERE id=?", [$acctId])['is_active'],
    ];

    echo json_encode(['ok'=>true, 'message'=>'Demo resetada com sucesso!', 'stats'=>$stats]);

} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
