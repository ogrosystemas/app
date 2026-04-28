<?php
/**
 * api/health.php
 * Health check endpoint — usado pelo ML DPP e monitoramento.
 * Retorna status do sistema sem expor dados sensíveis.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store');
header('X-Content-Type-Options: nosniff');

$start = microtime(true);
$status = 'ok';
$checks = [];

// DB check
try {
    $row = db_one("SELECT 1 as ping, NOW() as ts");
    $checks['database'] = ['status'=>'ok', 'ts'=>$row['ts']];
} catch (Throwable $e) {
    $checks['database'] = ['status'=>'error'];
    $status = 'degraded';
}

// Queue check — jobs travados em PROCESSING por mais de 5 min
try {
    $stuck = db_one("SELECT COUNT(*) as cnt FROM queue_jobs WHERE status='PROCESSING' AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $pending = db_one("SELECT COUNT(*) as cnt FROM queue_jobs WHERE status='PENDING'");
    $checks['queue'] = [
        'status'  => (int)$stuck['cnt'] > 10 ? 'warn' : 'ok',
        'pending' => (int)$pending['cnt'],
        'stuck'   => (int)$stuck['cnt'],
    ];
    // Auto-recover jobs travados
    if ((int)$stuck['cnt'] > 0) {
        db_query("UPDATE queue_jobs SET status='PENDING' WHERE status='PROCESSING' AND updated_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    }
} catch (Throwable $e) {
    $checks['queue'] = ['status'=>'error'];
}

// Token expiry check
try {
    $expiring = db_one("SELECT COUNT(*) as cnt FROM meli_accounts WHERE is_active=1 AND token_expires_at < DATE_ADD(NOW(), INTERVAL 1 HOUR)");
    $checks['tokens'] = ['status'=> (int)$expiring['cnt'] > 0 ? 'warn' : 'ok', 'expiring_soon'=> (int)$expiring['cnt']];
} catch (Throwable $e) {
    $checks['tokens'] = ['status'=>'unknown'];
}

$elapsed = round((microtime(true) - $start) * 1000, 1);

http_response_code($status === 'ok' ? 200 : 503);
echo json_encode([
    'status'      => $status,
    'app'         => APP_NAME,
    'version'     => '1.0.0',
    'timestamp'   => date('c'),
    'response_ms' => $elapsed,
    'checks'      => $checks,
], JSON_PRETTY_PRINT);
