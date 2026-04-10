<?php
/**
 * api/whatsapp_cron.php
 * Processa notificações agendadas
 * 
 * Configure no crontab para rodar a cada 5 minutos:
 * */5 * * * * curl -s https://mutanteskmtracker.ogrosystemas.com.br/api/whatsapp_cron.php > /dev/null 2>&1
 * 
 * Ou via painel de agendamentos do hosting.
 */

// Proteção: só pode ser chamado via CLI ou com token secreto
$token = $_GET['token'] ?? '';
$isCliCall = php_sapi_name() === 'cli';

require_once __DIR__ . '/../includes/bootstrap.php';

$cliToken = defined('APP_SECRET') ? APP_SECRET : 'km_cron_secret';
if (!$isCliCall && $token !== $cliToken) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$db = db();

// Buscar notificações pendentes com agendamento vencido
$pendentes = $db->query("
    SELECT id FROM notificacoes 
    WHERE status = 'pendente' 
    AND (agendado_para IS NULL OR agendado_para <= NOW())
    ORDER BY criado_em ASC
    LIMIT 10
")->fetchAll();

if (empty($pendentes)) {
    echo json_encode(['processed' => 0, 'message' => 'Nenhuma notificação pendente.']);
    exit;
}

$processed = 0;
$errors    = [];

try {
    $evo = new Evolution();
    foreach ($pendentes as $p) {
        $res = $evo->dispararNotificacao($p['id']);
        if ($res['success']) {
            $processed++;
        } else {
            $errors[] = "ID {$p['id']}: " . ($res['message'] ?? 'erro desconhecido');
        }
        sleep(1); // 1s entre disparos
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

echo json_encode([
    'processed' => $processed,
    'errors'    => $errors,
    'timestamp' => date('Y-m-d H:i:s'),
]);
