<?php
/**
 * api/etiqueta_ml.php
 * Proxy puro — baixa a etiqueta oficial do ML e serve ao browser sem modificação.
 * PDF: application/pdf  |  ZPL: text/plain
 * Erros retornam JSON — nunca HTML.
 *
 * GET: order_id=ID  type=pdf|zpl
 *      ids=ID1,ID2  type=pdf|zpl  (lote)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();

// Auth — sem redirect, retorna JSON
$user = auth_user();
if (!$user) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'error'=>'Sessao expirada']);
    exit;
}

$tenantId = $user['tenant_id'];
$type     = ($_GET['type'] ?? 'pdf') === 'zpl' ? 'zpl' : 'pdf';

// IDs dos pedidos
$orderIds = [];
if (!empty($_GET['ids'])) {
    $orderIds = array_values(array_filter(array_map('trim', explode(',', $_GET['ids']))));
} elseif (!empty($_GET['order_id'])) {
    $orderIds = [trim($_GET['order_id'])];
}

if (empty($orderIds)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'error'=>'order_id obrigatorio']);
    exit;
}

// Busca pedidos
$ph     = implode(',', array_fill(0, count($orderIds), '?'));
$orders = db_all(
    "SELECT o.id, o.meli_order_id, o.meli_shipment_id, o.ship_status,
            ma.access_token_enc
     FROM orders o
     JOIN meli_accounts ma ON ma.id = o.meli_account_id
     WHERE o.id IN ({$ph}) AND o.tenant_id=?",
    array_merge($orderIds, [$tenantId])
);

if (empty($orders)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'error'=>'Pedidos nao encontrados']);
    exit;
}

// Garante shipment_id para cada pedido
foreach ($orders as &$o) {
    if (!empty($o['meli_shipment_id'])) continue;

    $token = crypto_decrypt_token($o['access_token_enc']);
    $ctx   = stream_context_create(['http'=>[
        'header'  => "Authorization: Bearer $token",
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    $raw  = @file_get_contents("https://api.mercadolibre.com/orders/{$o['meli_order_id']}", false, $ctx);
    $data = $raw ? json_decode($raw, true) : null;
    $sid  = $data['shipping']['id'] ?? null;

    if ($sid) {
        $o['meli_shipment_id'] = (string)$sid;
        db_update('orders', ['meli_shipment_id'=>(string)$sid], 'id=?', [$o['id']]);
    }
}
unset($o);

// Baixa etiqueta do ML
function ml_label(string $shipmentId, string $token, string $type): array {
    $rt  = $type === 'zpl' ? 'zpl2' : 'pdf2';
    $url = "https://api.mercadolibre.com/shipments/{$shipmentId}/labels?response_type={$rt}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['ok'=>($code===200 && strlen($body)>100), 'code'=>$code, 'body'=>$body];
}

// Coleta etiquetas
$results = [];
$failed  = [];

foreach ($orders as $o) {
    if (empty($o['meli_shipment_id'])) {
        $failed[] = $o['meli_order_id'];
        continue;
    }
    $token  = crypto_decrypt_token($o['access_token_enc']);
    $result = ml_label($o['meli_shipment_id'], $token, $type);

    if (!$result['ok']) {
        $failed[] = $o['meli_order_id'];
        continue;
    }

    // Marca impresso
    $col = $type === 'pdf' ? 'pdf_printed' : 'zpl_printed';
    db_update('orders', [$col=>1], 'id=? AND tenant_id=?', [$o['id'], $tenantId]);

    $results[] = ['order'=>$o['meli_order_id'], 'body'=>$result['body']];
}

// Nenhuma etiqueta disponível
if (empty($results)) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode([
        'ok'     => false,
        'error'  => 'Etiqueta ainda nao liberada pelo ML',
        'orders' => $failed,
        'info'   => 'O ML libera a etiqueta quando o pedido fica PRONTO PARA ENVIO',
    ]);
    exit;
}

// ── Serve o arquivo puro ──────────────────────────────────

if ($type === 'zpl') {
    // ZPL: concatena todos (cada etiqueta é um bloco ^XA...^XZ)
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="Etiquetas_' . date('Ymd_Hi') . '.zpl"');
    foreach ($results as $r) {
        echo $r['body'] . "\n";
    }
    exit;
}

// PDF individual
if (count($results) === 1) {
    $filename = 'Etiqueta_' . $results[0]['order'] . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($results[0]['body']));
    echo $results[0]['body'];
    exit;
}

// PDF lote — tenta mesclar com pdftk ou gs
$tmpDir   = sys_get_temp_dir();
$tmpFiles = [];

foreach ($results as $i => $r) {
    $f = $tmpDir . '/ml_lbl_' . $i . '_' . time() . '.pdf';
    file_put_contents($f, $r['body']);
    $tmpFiles[] = $f;
}

// Tenta pdftk
$merged  = false;
$outFile = $tmpDir . '/ml_lbl_merged_' . time() . '.pdf';

if (shell_exec('which pdftk 2>/dev/null')) {
    $cmd = 'pdftk ' . implode(' ', array_map('escapeshellarg', $tmpFiles))
         . ' cat output ' . escapeshellarg($outFile) . ' 2>/dev/null';
    exec($cmd, $out, $rc);
    $merged = ($rc === 0 && file_exists($outFile) && filesize($outFile) > 100);
}

// Tenta ghostscript se pdftk falhou
if (!$merged && shell_exec('which gs 2>/dev/null')) {
    $cmd = 'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile='
         . escapeshellarg($outFile) . ' '
         . implode(' ', array_map('escapeshellarg', $tmpFiles)) . ' 2>/dev/null';
    exec($cmd, $out, $rc);
    $merged = ($rc === 0 && file_exists($outFile) && filesize($outFile) > 100);
}

if ($merged) {
    $filename = 'Etiquetas_Lote_' . date('Ymd_Hi') . '_' . count($results) . 'pcs.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($outFile));
    readfile($outFile);
    foreach ($tmpFiles as $f) @unlink($f);
    @unlink($outFile);
    exit;
}

// Fallback: serve PDFs um a um via multipart (browser baixa sequencialmente)
// Na prática: serve o primeiro e os demais como downloads separados via JS
foreach ($tmpFiles as $f) @unlink($f);

// Serve o primeiro diretamente
$filename = 'Etiqueta_1de' . count($results) . '_' . $results[0]['order'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Total-Labels: ' . count($results));
header('Content-Length: ' . strlen($results[0]['body']));
echo $results[0]['body'];
