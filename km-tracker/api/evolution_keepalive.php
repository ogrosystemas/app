<?php
/**
 * api/evolution_keepalive.php
 * Mantém a conexão Evolution API ativa
 * 
 * Configure no crontab para rodar a cada 5 minutos:
 * */5 * * * * curl -s "https://mutanteskmtracker.com.br/api/evolution_keepalive.php?token=MotoClub@2024#Evolution!19962026" > /dev/null 2>&1
 */

$token = $_GET['token'] ?? '';
$isCliCall = php_sapi_name() === 'cli';

require_once __DIR__ . '/../includes/bootstrap.php';

if (!$isCliCall && $token !== APP_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db  = db();
    $cfg = $db->query("SELECT * FROM evolution_config LIMIT 1")->fetch();

    if (!$cfg || empty($cfg['evolution_url'])) {
        echo json_encode(['status' => 'unconfigured']);
        exit;
    }

    $url      = rtrim($cfg['evolution_url'], '/');
    $key      = $cfg['evolution_key'];
    $instance = $cfg['instance_name'];

    // Verificar estado da instância
    $ch = curl_init("{$url}/instance/connectionState/{$instance}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ["apikey: {$key}"],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data  = json_decode($res, true) ?? [];
    $state = $data['instance']['state'] ?? $data['state'] ?? 'unknown';

    // Atualizar status no banco
    $db->prepare("UPDATE evolution_config SET instance_status=? WHERE id=?")
       ->execute([$state === 'open' ? 'connected' : 'disconnected', $cfg['id']]);

    if ($state === 'open') {
        echo json_encode(['status' => 'connected', 'state' => $state, 'time' => date('Y-m-d H:i:s')]);
    } else {
        // Tentar reconectar
        $ch2 = curl_init("{$url}/instance/connect/{$instance}");
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ["apikey: {$key}"],
        ]);
        $res2 = curl_exec($ch2);
        curl_close($ch2);
        $data2 = json_decode($res2, true) ?? [];

        echo json_encode([
            'status'   => 'reconnecting',
            'state'    => $state,
            'response' => $data2,
            'time'     => date('Y-m-d H:i:s'),
        ]);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
