<?php
/**
 * api/fiscal_note.php
 * Busca e baixa a nota fiscal de um pedido no ML.
 * A NF fica disponível quando ship_status = READY_TO_SHIP ou SHIPPED.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/crypto.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$orderId   = $_GET['order_id'] ?? $_POST['order_id'] ?? '';
$action    = $_GET['action']   ?? 'get';

if (!$orderId) {
    echo json_encode(['ok'=>false,'error'=>'order_id obrigatório']);
    exit;
}

// ── Upload manual de NF ──────────────────────────────────
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $order = db_one(
        "SELECT id, tenant_id, meli_order_id FROM orders WHERE id=? AND tenant_id=?",
        [$orderId, $tenantId]
    );
    if (!$order) { echo json_encode(['ok'=>false,'error'=>'Pedido não encontrado']); exit; }

    $nfNumber = trim($_POST['nf_number'] ?? '');
    $nfSerie  = trim($_POST['nf_serie']  ?? '');
    $nfKey    = preg_replace('/\D/', '', $_POST['nf_key'] ?? '');

    $nfPath = null;

    // Processa upload do arquivo se enviado
    if (!empty($_FILES['nf_file']['tmp_name'])) {
        $file     = $_FILES['nf_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['pdf', 'xml'])) {
            echo json_encode(['ok'=>false,'error'=>'Apenas PDF ou XML são aceitos']); exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok'=>false,'error'=>'Arquivo muito grande (máx 5MB)']); exit;
        }

        $dir = ROOT_PATH . '/storage/nf/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'NF_' . $order['meli_order_id'] . '_' . $tenantId . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $nfPath = 'storage/nf/' . $filename;
        } else {
            echo json_encode(['ok'=>false,'error'=>'Falha ao salvar o arquivo']); exit;
        }
    }

    if (!$nfNumber && !$nfKey && !$nfPath) {
        echo json_encode(['ok'=>false,'error'=>'Informe pelo menos o número da NF ou envie um arquivo']); exit;
    }

    $upd = ['nf_fetched_at' => date('Y-m-d H:i:s')];
    if ($nfNumber) $upd['nf_number'] = $nfNumber . ($nfSerie ? "-{$nfSerie}" : '');
    if ($nfKey)    $upd['nf_key']    = $nfKey;
    if ($nfPath)   $upd['nf_path']   = $nfPath;

    db_update('orders', $upd, 'id=? AND tenant_id=?', [$orderId, $tenantId]);
    audit_log('NF_UPLOAD_MANUAL', 'orders', $orderId, null, $upd);

    echo json_encode(['ok'=>true, 'nf_path'=>$nfPath, 'nf_number'=>$nfNumber]);
    exit;
}

$order = db_one(
    "SELECT o.*, ma.access_token_enc, ma.meli_user_id
     FROM orders o
     JOIN meli_accounts ma ON ma.id = o.meli_account_id
     WHERE o.id=? AND o.tenant_id=?",
    [$orderId, $tenantId]
);

if (!$order) {
    echo json_encode(['ok'=>false,'error'=>'Pedido não encontrado']);
    exit;
}

// Descriptografa token ML
if (TOKEN_KEY) {
    try { $order['access_token_enc'] = crypto_decrypt_token($order['access_token_enc']); }
    catch (Throwable $e) { /* não criptografado */ }
}

if (!in_array($order['ship_status'], ['READY_TO_SHIP','SHIPPED','DELIVERED'])) {
    echo json_encode(['ok'=>false,'error'=>'NF disponível apenas para pedidos prontos para envio ou enviados']);
    exit;
}

$token    = $order['access_token_enc'];
$meliId   = str_replace('#','',$order['meli_order_id']);

// ── Busca NF via API ML ──────────────────────────────────
function meli_request(string $url, string $token): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ? json_decode($res, true) : null;
}

// Tenta buscar NF pelo shipment_id do pedido
$orderData = meli_request("https://api.mercadolibre.com/orders/{$meliId}", $token);

if (!$orderData) {
    echo json_encode(['ok'=>false,'error'=>'Não foi possível buscar dados do pedido no ML']);
    exit;
}

$shipmentId = $orderData['shipping']['id'] ?? null;

if (!$shipmentId) {
    echo json_encode(['ok'=>false,'error'=>'Pedido sem shipment_id — NF não disponível']);
    exit;
}

// Busca NF pelo shipment
$nfData = meli_request(
    "https://api.mercadolibre.com/shipments/{$shipmentId}/fiscal_data",
    $token
);

if (!$nfData || empty($nfData['invoice_number'])) {
    // Tenta endpoint alternativo
    $nfData = meli_request(
        "https://api.mercadolibre.com/packs/{$shipmentId}/fiscal_documents",
        $token
    );
}

if ($action === 'download') {
    // Download do PDF da NF
    $pdfUrl = $nfData['pdf_url'] ?? $nfData['url'] ?? null;

    if (!$pdfUrl) {
        // ML às vezes retorna URL de download diferente
        $pdfUrl = "https://api.mercadolibre.com/shipments/{$shipmentId}/fiscal_document";
    }

    $ch = curl_init($pdfUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $pdf = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($pdf && str_contains($contentType, 'pdf')) {
        // Salva localmente e marca no pedido
        $dir  = ROOT_PATH . '/storage/nf/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = "NF_{$order['meli_order_id']}_{$tenantId}.pdf";
        file_put_contents($dir . $filename, $pdf);
        db_update('orders', ['nf_path' => 'storage/nf/'.$filename], 'id=?', [$orderId]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.strlen($pdf));
        echo $pdf;
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'PDF da NF não disponível ainda. Tente após o envio ser confirmado.']);
    exit;
}

// GET: retorna dados da NF
echo json_encode([
    'ok'             => true,
    'invoice_number' => $nfData['invoice_number']  ?? null,
    'invoice_key'    => $nfData['invoice_key']      ?? null,
    'series'         => $nfData['series']           ?? null,
    'pdf_url'        => $nfData['pdf_url']          ?? null,
    'issue_date'     => $nfData['issue_date']       ?? null,
    'shipment_id'    => $shipmentId,
    'raw'            => $nfData,
]);
