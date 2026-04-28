<?php
/**
 * webhook.php — Mercado Pago Webhook Handler
 *
 * URL para cadastrar no Mercado Pago:
 * https://barmutante.ogrosystemas.com.br/webhook.php
 *
 * Configurar em: mercadopago.com.br/developers → Sua aplicação → Webhooks
 * Tópico: order
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DB.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/MercadoPago.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$data    = json_decode($payload, true) ?? [];

// Mercado Pago envia GET para validar a URL (handshake)
if ($_SERVER['REQUEST_METHOD'] === 'GET' || empty($payload)) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'service' => 'Bar System Pro']);
    exit;
}

// Validar assinatura (opcional mas recomendado)
$xSignature  = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$xRequestId  = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
$secret      = DB::cfg('mp_webhook_secret', '');

if ($secret && $xSignature) {
    // Extrair ts e v1 do header
    $parts = [];
    foreach (explode(',', $xSignature) as $part) {
        [$k, $v] = explode('=', trim($part), 2) + ['', ''];
        $parts[$k] = $v;
    }
    $ts        = $parts['ts'] ?? '';
    $hash      = $parts['v1'] ?? '';
    $dataId    = $data['data']['id'] ?? '';
    $toSign    = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
    $expected  = hash_hmac('sha256', $toSign, $secret);
    if (!hash_equals($expected, $hash)) {
        http_response_code(401);
        echo json_encode(['error' => 'Assinatura inválida']);
        exit;
    }
}

// Processar evento
try {
    $topic = $data['topic'] ?? $data['type'] ?? '';
    // Só processar tópico "order" ou "payment"
    if (in_array($topic, ['order', 'payment', 'merchant_order'])) {
        MercadoPago::processarWebhook($data);
        DB::setCfg('mp_ultimo_webhook', date('Y-m-d H:i:s') . ' | ' . $topic . ' | ' . ($data['data']['id'] ?? '?'));
    }
} catch (\Throwable $e) {
    // Nunca retornar 5xx — o MP retenviará infinitamente
}

http_response_code(200);
echo json_encode(['received' => true]);
