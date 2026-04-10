<?php
/**
 * api/mercadopago.php — API endpoint para Mercado Pago Point
 */
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/MercadoPago.php';

Auth::requireLogin();

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {

    case 'cobrar':
        // Enviar cobrança para o terminal Point
        $deviceId  = trim($input['device_id'] ?? DB::cfg('mp_device_id', ''));
        $valor     = (float)($input['valor'] ?? 0);
        $referencia= trim($input['referencia'] ?? '');
        $descricao = trim($input['descricao'] ?? 'Venda Bar');

        if (!$deviceId) jsonRes(['success'=>false,'message'=>'Device ID não configurado. Configure em Configurações → Mercado Pago.']);
        if ($valor <= 0) jsonRes(['success'=>false,'message'=>'Valor inválido.']);

        $mp     = new MercadoPago();
        $result = $mp->criarCobranca($deviceId, $valor, $referencia, $descricao);
        $code   = $result['http_code'] ?? 0;

        if ($code >= 200 && $code < 300) {
            $orderId  = $result['order_id'] ?? $result['id'] ?? null;
            $intentId = $result['id'] ?? null;
            jsonRes([
                'success'   => true,
                'order_id'  => $orderId,
                'intent_id' => $intentId,
                'status'    => $result['state'] ?? $result['status'] ?? 'created',
                'message'   => 'Cobrança enviada para o terminal.',
            ]);
        }
        $msg = $result['message'] ?? ($result['error'] ?? "HTTP $code");
        jsonRes(['success'=>false,'message'=>'Erro ao cobrar: '.$msg,'http_code'=>$code,'raw'=>$result['_raw']??'']);

    case 'status':
        $orderId  = trim($input['order_id'] ?? $_GET['order_id'] ?? '');
        $deviceId = trim($input['device_id'] ?? DB::cfg('mp_device_id',''));
        $intentId = trim($input['intent_id'] ?? '');

        if (!$orderId && !$intentId) jsonRes(['success'=>false,'message'=>'ID não informado.']);

        $mp = new MercadoPago();

        if ($intentId && $deviceId) {
            $r = $mp->consultarIntent($deviceId, $intentId);
        } else {
            $r = $mp->consultarCobranca($orderId);
        }

        $code   = $r['http_code'] ?? 0;
        $status = $r['state'] ?? $r['status'] ?? 'unknown';
        $pago   = in_array($status, ['paid', 'approved', 'finished']);

        jsonRes([
            'success'   => $code >= 200 && $code < 300,
            'status'    => $status,
            'pago'      => $pago,
            'http_code' => $code,
        ]);

    case 'cancelar':
        $orderId  = trim($input['order_id'] ?? '');
        $deviceId = trim($input['device_id'] ?? DB::cfg('mp_device_id',''));
        $mp = new MercadoPago();

        if ($deviceId) $mp->cancelarIntent($deviceId);
        if ($orderId)  $mp->cancelarCobranca($orderId);

        jsonRes(['success'=>true,'message'=>'Cobrança cancelada.']);

    case 'terminais':
        $mp     = new MercadoPago();
        $result = $mp->listarTerminais();
        $code   = $result['http_code'] ?? 0;
        $lista  = $result['data']['terminals'] ?? $result['terminals'] ?? [];
        jsonRes(['success'=>$code>=200&&$code<300, 'terminais'=>$lista, 'http_code'=>$code]);

    default:
        jsonRes(['success'=>false,'message'=>'Ação inválida: '.htmlspecialchars($action)], 400);
}
