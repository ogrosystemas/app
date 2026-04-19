<?php
/**
 * api/mp_payment.php
 * Cria intenção de pagamento no Mercado Pago
 * POST { tipo: 'point'|'pix', valor: 150.00, external_reference: 'VENDA-...' }
 */
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/config.php';
checkAuth(['admin','gerente','caixa','vendedor']);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$tipo  = $input['tipo']               ?? '';
$valor = (float)($input['valor']      ?? 0);
$ref   = $input['external_reference'] ?? '';
$tipo_cartao = $input['tipo_cartao']  ?? 'credit_card'; // credit_card | debit_card

if ($valor <= 0) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Valor inválido']);
    exit;
}

// Carregar credenciais MP
$mpCfgFile = __DIR__ . '/../config/mercadopago.php';
if (!file_exists($mpCfgFile)) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Mercado Pago não configurado. Acesse Admin → Mercado Pago.']);
    exit;
}
$mpCfg    = include $mpCfgFile;
$token    = $mpCfg['mp_access_token'] ?? '';
$deviceId = $mpCfg['mp_device_id']    ?? '';

if (!$token) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Access Token não configurado.']);
    exit;
}

// ── PIX ──────────────────────────────────────────────────────────────────────
if ($tipo === 'pix') {
    // Se tem terminal Point com tela, usar Point Payment Intent com PIX
    // Caso contrário, criar PIX via API e retornar QR code para exibir no PDV
    if ($deviceId) {
        // Point com suporte a PIX (Point Smart / Pro 2)
        $payload = [
            'amount'             => $valor,
            'additional_info'    => ['external_reference' => $ref],
            'payment_method_id'  => 'pix',
            'description'        => $ref ?: 'Venda OS-System',
        ];
        $ch = curl_init("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
            ],
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($res, true);

        ob_end_clean();
        if ($code === 200 || $code === 201) {
            echo json_encode(['success'=>true,'tipo'=>'point_pix','message'=>'QR Code enviado para o terminal. Peça ao cliente escanear na maquininha.','id'=>$data['id']??'']);
        } else {
            echo json_encode(['success'=>false,'message'=>$data['message']??$data['error']??"Erro HTTP {$code} ao enviar PIX para terminal"]);
        }
    } else {
        // Sem terminal — gerar QR Code via API para exibir no PDV
        $payload = [
            'transaction_amount' => $valor,
            'description'        => $ref ?: 'Venda OS-System',
            'payment_method_id'  => 'pix',
            'payer'              => ['email' => 'cliente@ossystem.com'],
            'external_reference' => $ref,
        ];
        $ch = curl_init('https://api.mercadopago.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$token}",
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid('pix_', true),
            ],
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($res, true);

        ob_end_clean();
        if (in_array($code, [200, 201])) {
            $qr      = $data['point_of_interaction']['transaction_data']['qr_code']        ?? '';
            $qr_b64  = $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '';
            $pid     = $data['id'] ?? '';
            echo json_encode(['success'=>true,'tipo'=>'pix_qr','qr_code'=>$qr,'qr_code_base64'=>$qr_b64,'payment_id'=>$pid,'message'=>'QR Code gerado com sucesso']);
        } else {
            echo json_encode(['success'=>false,'message'=>$data['message']??$data['error']??"Erro HTTP {$code} ao gerar PIX"]);
        }
    }
    exit;
}

// ── CARTÃO via Terminal Point ─────────────────────────────────────────────────
if ($tipo === 'point') {
    if (!$deviceId) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Device ID do terminal não configurado. Acesse Admin → Mercado Pago.']);
        exit;
    }

    $payload = [
        'amount'          => $valor,
        'additional_info' => ['external_reference' => $ref],
        'description'     => $ref ?: 'Venda OS-System',
    ];
    // Tipo de cartão (crédito ou débito)
    if ($tipo_cartao === 'debit_card') {
        $payload['payment_method_id'] = 'debit_card';
    }
    // Para crédito não precisa especificar — terminal pergunta ao cliente

    $ch = curl_init("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/payment-intents");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . uniqid('point_', true),
        ],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);

    ob_end_clean();
    if (in_array($code, [200, 201])) {
        echo json_encode([
            'success' => true,
            'tipo'    => 'point_card',
            'id'      => $data['id'] ?? '',
            'message' => 'Terminal acordado! Peça ao cliente passar o cartão na maquininha.',
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $data['message'] ?? $data['error'] ?? "Erro HTTP {$code} ao acionar terminal",
        ]);
    }
    exit;
}

ob_end_clean();
echo json_encode(['success'=>false,'message'=>'Tipo de pagamento inválido']);
