<?php
/**
 * api/rastreamento.php
 * GET ?shipment_id=xxx — busca histórico de rastreamento via ML
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$acctId    = $_SESSION['active_meli_account_id'] ?? null;
$shipId    = trim($_GET['shipment_id'] ?? '');

if (!$shipId) { echo json_encode(['ok'=>false,'error'=>'shipment_id obrigatório']); exit; }
if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa']); exit; }

$acct = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId]);
if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta não encontrada']); exit; }

$token = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);

// Busca dados do envio
$shipResult = curl_ml("https://api.mercadolibre.com/shipments/{$shipId}", [
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
    CURLOPT_TIMEOUT    => 10,
]);

if ($shipResult['code'] !== 200) {
    echo json_encode(['ok'=>false,'error'=>"Envio não encontrado (HTTP {$shipResult['code']})"]);
    exit;
}

$ship = json_decode($shipResult['body'], true);

// Busca histórico de rastreamento
$histResult = curl_ml("https://api.mercadolibre.com/shipments/{$shipId}/history", [
    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
    CURLOPT_TIMEOUT    => 10,
]);

$history = [];
if ($histResult['code'] === 200) {
    $histData = json_decode($histResult['body'], true);
    foreach (array_reverse($histData ?? []) as $event) {
        $history[] = [
            'status'      => $event['status'] ?? '',
            'description' => translateShipStatus($event['status'] ?? ''),
            'date'        => isset($event['date']) ? date('d/m/Y H:i', strtotime($event['date'])) : null,
            'location'    => $event['city'] ?? ($event['address']['city'] ?? null),
        ];
    }
}

function translateShipStatus(string $status): string {
    return match(strtolower($status)) {
        'handling'        => 'Preparando para envio',
        'ready_to_ship'   => 'Pronto para envio',
        'shipped'         => 'Enviado ao transportador',
        'in_transit'      => 'Em trânsito',
        'out_for_delivery'=> 'Saiu para entrega',
        'delivered'       => 'Entregue ao destinatário',
        'returned'        => 'Devolvido ao remetente',
        'not_delivered'   => 'Não entregue',
        'lost'            => 'Extraviado',
        default           => ucfirst(str_replace('_',' ',$status)),
    };
}

// Estima data de entrega
$estimated = null;
if (!empty($ship['date_delivered'])) {
    $estimated = date('d/m/Y', strtotime($ship['date_delivered']));
} elseif (!empty($ship['shipping_option']['estimated_delivery_time']['date'])) {
    $estimated = date('d/m/Y', strtotime($ship['shipping_option']['estimated_delivery_time']['date']));
}

echo json_encode([
    'ok'       => true,
    'shipment' => [
        'id'                => $shipId,
        'status'            => translateShipStatus($ship['status'] ?? ''),
        'tracking_number'   => $ship['tracking_number'] ?? null,
        'estimated_delivery'=> $estimated,
        'service'           => $ship['shipping_option']['name'] ?? null,
        'carrier'           => $ship['shipping_items'][0]['description'] ?? null,
    ],
    'history'  => $history,
]);
