<?php
/**
 * api/upload_picture.php
 * Recebe foto do browser, envia para a API do ML e retorna o picture_id.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/crypto.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_secure();
auth_require();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$accountId = $_SESSION['active_meli_account_id'] ?? null;

if (!$accountId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa. Conecte uma conta em Integração ML.']);
    exit;
}

$account = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$accountId, $tenantId]);

    // Descriptografa token ML se necessário
    if (TOKEN_KEY) {
        try { $account['access_token_enc'] = crypto_decrypt_token($account['access_token_enc']); }
        catch (Throwable $e) { /* não criptografado ainda */ }
    }

    if (!$account) {
    echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada.']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['ok'=>false,'error'=>'Nenhum arquivo enviado.']);
    exit;
}

$file = $_FILES['file'];

// Validações locais
$allowedTypes = ['image/jpeg','image/jpg','image/png'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['ok'=>false,'error'=>'Formato inválido. Use JPG ou PNG.']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['ok'=>false,'error'=>'Arquivo muito grande. Máximo 10MB.']);
    exit;
}

// Verifica dimensões mínimas
$img = getimagesize($file['tmp_name']);
if ($img && ($img[0] < 500 || $img[1] < 500)) {
    echo json_encode(['ok'=>false,'error'=>"Imagem muito pequena ({$img[0]}x{$img[1]}px). Mínimo 500x500px."]);
    exit;
}

// Upload para a API do ML
$ch = curl_init('https://api.mercadolibre.com/pictures/items/upload');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['file' => new CURLFile($file['tmp_name'], $mime, $file['name'])],
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $account['access_token_enc']],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo json_encode(['ok'=>false,'error'=>'Erro de conexão: '.$err]);
    exit;
}

$data = json_decode($res, true);

if ($httpCode !== 201 && $httpCode !== 200) {
    $msg = $data['message'] ?? $data['error'] ?? 'Erro ao enviar imagem ao ML';
    echo json_encode(['ok'=>false,'error'=>$msg]);
    exit;
}

echo json_encode([
    'ok'         => true,
    'picture_id' => $data['id'],
    'url'        => $data['variations'][0]['url'] ?? '',
    'max_size'   => $data['max_size'] ?? '',
]);
