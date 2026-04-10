<?php
header('Content-Type: application/json');
$token = $_GET['token'] ?? '';
if (!$token) { echo json_encode(['success'=>false,'message'=>'Token não informado']); exit; }

$ch = curl_init('https://api.mercadopago.com/v1/payment_methods');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
    CURLOPT_TIMEOUT => 10,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code === 200) {
    echo json_encode(['success'=>true,'message'=>'Token válido e API acessível.']);
} else {
    $data = json_decode($res, true);
    echo json_encode(['success'=>false,'message'=>$data['message'] ?? "HTTP $code"]);
}
