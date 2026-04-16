<?php
/**
 * Webhook Mercado Pago — OS-System
 *
 * URL para configurar no painel MP:
 *   https://seudominio.com.br/api/webhook_mercadopago.php
 *
 * Funciona para Sandbox e Produção (mesmo endpoint).
 * No painel MP defina os eventos: payment, merchant_order, point_integration_wh
 */

// ── 0. Bootstrap mínimo (sem session, sem output) ──────────────────────────
define('WEBHOOK_MODE', true);

// Carregar apenas DB e config sem iniciar sessão
$configPath = __DIR__ . '/../config/config.php';
if (file_exists($configPath)) {
    // Iniciar sessão não é necessário para webhook
    // Incluir apenas o que precisamos
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db       = $database->getConnection();
}

// ── 1. Log helper ───────────────────────────────────────────────────────────
$logFile = __DIR__ . '/../logs/webhook_mp.log';
if (!is_dir(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}

function wlog(string $msg, array $data = []): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data) $line .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    @file_put_contents($logFile, $line . "\n", FILE_APPEND);
}

// ── 2. Ler payload ──────────────────────────────────────────────────────────
$rawBody = file_get_contents('php://input');
$headers = getallheaders();
$payload = json_decode($rawBody, true);

wlog('Webhook recebido', [
    'type'    => $payload['type']    ?? $_GET['type']   ?? 'unknown',
    'topic'   => $payload['topic']   ?? $_GET['topic']  ?? '',
    'data_id' => $payload['data']['id'] ?? $_GET['data.id'] ?? '',
]);

// ── 3. Validar assinatura (x-signature) ─────────────────────────────────────
function validarAssinatura(string $rawBody, array $headers): bool {
    // Carregar token salvo
    $mpCfgFile = __DIR__ . '/../config/mercadopago.php';
    if (!file_exists($mpCfgFile)) return false;
    $mpCfg     = include $mpCfgFile;
    $secret    = $mpCfg['mp_webhook_secret'] ?? '';

    if (!$secret) {
        // Sem secret configurado — aceitar (modo desenvolvimento)
        wlog('AVISO: webhook_secret não configurado, validação de assinatura ignorada');
        return true;
    }

    $xSignature = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
    $xRequestId = $headers['x-request-id'] ?? $headers['X-Request-Id'] ?? '';

    if (!$xSignature) {
        wlog('ERRO: header x-signature ausente');
        return false;
    }

    // Extrair ts e v1 do header
    $parts = [];
    foreach (explode(',', $xSignature) as $part) {
        [$k, $v] = explode('=', trim($part), 2) + ['', ''];
        $parts[$k] = $v;
    }

    $ts = $parts['ts'] ?? '';
    $v1 = $parts['v1'] ?? '';

    if (!$ts || !$v1) {
        wlog('ERRO: x-signature malformado', ['signature' => $xSignature]);
        return false;
    }

    // Construir string de template para HMAC
    // MP usa: id:{data.id};request-id:{x-request-id};ts:{ts}
    $dataId  = $_GET['data.id'] ?? $payload['data']['id'] ?? '';
    $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts}";
    $expected = hash_hmac('sha256', $manifest, $secret);

    if (!hash_equals($expected, $v1)) {
        wlog('ERRO: assinatura inválida', ['expected' => $expected, 'received' => $v1]);
        return false;
    }

    return true;
}

// Validar apenas em produção (em sandbox pode não enviar signature)
$isProduction = !str_contains(
    $payload['api_version'] ?? '',
    'sandbox'
);
// Heurística: se não tem secret configurado ou é sandbox, não bloquear
if (!validarAssinatura($rawBody, $headers)) {
    wlog('Assinatura inválida — ignorando evento');
    http_response_code(200); // Sempre 200 para MP não retentar indefinidamente
    echo json_encode(['status' => 'signature_invalid']);
    exit;
}

// ── 4. Responder imediatamente (MP exige resposta < 500ms) ──────────────────
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'received']);

// Processar de forma assíncrona (se possível fechar conexão antes)
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ── 5. Identificar tipo de evento ───────────────────────────────────────────
$type    = $payload['type']  ?? $_GET['type']  ?? '';
$topic   = $payload['topic'] ?? $_GET['topic'] ?? '';
$dataId  = $payload['data']['id'] ?? $_GET['data.id'] ?? '';

// Suporte aos dois formatos de notificação do MP
if (!$type && $topic) {
    $type = $topic; // formato antigo usa 'topic'
}

wlog("Processando evento tipo=$type id=$dataId");

// ── 6. Buscar detalhes do pagamento na API do MP ────────────────────────────
function buscarPagamentoMP(string $paymentId): ?array {
    $mpCfgFile = __DIR__ . '/../config/mercadopago.php';
    $mpCfg     = file_exists($mpCfgFile) ? include $mpCfgFile : [];
    $token     = $mpCfg['mp_access_token'] ?? '';

    if (!$token || !$paymentId) return null;

    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        wlog("ERRO ao buscar pagamento $paymentId HTTP=$code");
        return null;
    }

    return json_decode($res, true);
}

function buscarOrdemMP(string $orderId): ?array {
    $mpCfgFile = __DIR__ . '/../config/mercadopago.php';
    $mpCfg     = file_exists($mpCfgFile) ? include $mpCfgFile : [];
    $token     = $mpCfg['mp_access_token'] ?? '';
    if (!$token) return null;

    $ch = curl_init("https://api.mercadopago.com/merchant_orders/{$orderId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code === 200 ? json_decode($res, true) : null;
}

// ── 7. Processar eventos ────────────────────────────────────────────────────
try {
    switch ($type) {

        // ── Pagamento (PIX, cartão, etc.) ───────────────────────────────────
        case 'payment':
            $pagamento = buscarPagamentoMP((string)$dataId);
            if (!$pagamento) break;

            $mpStatus    = $pagamento['status']           ?? '';
            $mpStatusDet = $pagamento['status_detail']    ?? '';
            $externalRef = $pagamento['external_reference'] ?? '';
            $valorPago   = (float)($pagamento['transaction_amount'] ?? 0);
            $metodoPag   = $pagamento['payment_method_id'] ?? '';

            wlog("Pagamento #$dataId status=$mpStatus detalhe=$mpStatusDet ref=$externalRef valor=$valorPago");

            if (!$db) break;

            // Registrar/atualizar na tabela mp_pagamentos
            $db->exec("CREATE TABLE IF NOT EXISTS mp_pagamentos (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                mp_payment_id   VARCHAR(64) NOT NULL UNIQUE,
                external_ref    VARCHAR(128),
                status          VARCHAR(32),
                status_detail   VARCHAR(64),
                valor           DECIMAL(10,2),
                metodo          VARCHAR(64),
                payload         LONGTEXT,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");

            $stmt = $db->prepare("INSERT INTO mp_pagamentos
                (mp_payment_id, external_ref, status, status_detail, valor, metodo, payload)
                VALUES (?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    status=VALUES(status),
                    status_detail=VALUES(status_detail),
                    valor=VALUES(valor),
                    payload=VALUES(payload),
                    updated_at=NOW()");
            $stmt->execute([
                $dataId, $externalRef, $mpStatus, $mpStatusDet,
                $valorPago, $metodoPag, json_encode($pagamento)
            ]);

            // Se aprovado e tem referência de venda, atualizar venda
            if ($mpStatus === 'approved' && $externalRef) {
                $stmt = $db->prepare("UPDATE vendas SET mp_payment_id = ?, status = 'finalizada'
                                      WHERE numero_venda = ? OR id = ?");
                $stmt->execute([$dataId, $externalRef, $externalRef]);
                if ($stmt->rowCount() > 0) {
                    wlog("Venda $externalRef marcada como finalizada via MP");
                }
            }
            break;

        // ── Point Integration (terminal físico) ─────────────────────────────
        case 'point_integration_wh':
        case 'point_integration_ipn':
            $mpStatus    = $payload['status']           ?? '';
            $paymentId   = $payload['payment_id']       ?? $dataId;
            $externalRef = $payload['external_reference'] ?? '';
            $deviceId    = $payload['device_id']        ?? '';

            wlog("Point terminal status=$mpStatus payment_id=$paymentId device=$deviceId ref=$externalRef");

            if (!$db) break;

            // Criar tabela de logs do terminal Point se não existir
            $db->exec("CREATE TABLE IF NOT EXISTS mp_point_eventos (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                device_id    VARCHAR(128),
                payment_id   VARCHAR(64),
                external_ref VARCHAR(128),
                status       VARCHAR(32),
                payload      LONGTEXT,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $db->prepare("INSERT INTO mp_point_eventos
                (device_id, payment_id, external_ref, status, payload)
                VALUES (?,?,?,?,?)")
               ->execute([$deviceId, $paymentId, $externalRef, $mpStatus, $rawBody]);

            // Pagamento aprovado no terminal → atualizar venda
            if (in_array($mpStatus, ['finished', 'approved']) && $externalRef) {
                $stmt = $db->prepare("UPDATE vendas SET mp_payment_id = ?, status = 'finalizada'
                                      WHERE numero_venda = ? OR id = ?");
                $stmt->execute([$paymentId, $externalRef, $externalRef]);
                if ($stmt->rowCount() > 0) {
                    wlog("Venda $externalRef aprovada pelo terminal Point");
                }
            }

            // Pagamento cancelado/erro no terminal
            if (in_array($mpStatus, ['error', 'canceled', 'cancelled'])) {
                wlog("AVISO: Pagamento no terminal cancelado/erro ref=$externalRef status=$mpStatus");
            }
            break;

        // ── Merchant Order ───────────────────────────────────────────────────
        case 'merchant_order':
            $ordem = buscarOrdemMP((string)$dataId);
            if (!$ordem) break;

            $externalRef  = $ordem['external_reference']  ?? '';
            $orderStatus  = $ordem['order_status']        ?? '';
            $totalPago    = array_sum(array_column($ordem['payments'] ?? [], 'transaction_amount'));

            wlog("MerchantOrder #$dataId status=$orderStatus ref=$externalRef pago=$totalPago");

            if ($orderStatus === 'paid' && $externalRef && $db) {
                $db->prepare("UPDATE vendas SET status = 'finalizada' WHERE numero_venda = ? OR id = ?")
                   ->execute([$externalRef, $externalRef]);
            }
            break;

        default:
            wlog("Tipo de evento não tratado: $type");
    }

} catch (Throwable $e) {
    wlog('EXCEÇÃO no processamento: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}

wlog("Evento $type processado com sucesso");
