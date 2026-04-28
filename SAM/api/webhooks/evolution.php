<?php
/**
 * api/webhooks/evolution.php
 * Recebe eventos da Evolution API — apenas para uso futuro
 * Por ora apenas registra o recebimento no log
 */
require_once dirname(__DIR__, 2) . '/config.php';

// Verificar payload
$body    = file_get_contents('php://input');
$payload = json_decode($body, true) ?: [];

// Log simples
$logDir = dirname(__DIR__, 2) . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
file_put_contents(
    $logDir . '/evolution.log',
    date('[Y-m-d H:i:s] ') . substr($body, 0, 500) . "\n",
    FILE_APPEND
);

http_response_code(200);
echo json_encode(['ok' => true]);
