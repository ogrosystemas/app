<?php
/**
 * api/activate_license.php
 * Ativa licença do tenant com chave fornecida.
 * A chave é validada via AES-256-GCM (crypto.php).
 * Formato da chave: gerada pelo keygen interno com tenant_id + plano + validade.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

session_start_secure();
auth_require();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$key      = trim($_POST['license_key'] ?? '');

if (!$key) {
    echo json_encode(['ok'=>false,'error'=>'Chave de ativação não informada']);
    exit;
}

// Valida a chave
try {
    $payload = crypto_decrypt_license($key, MASTER_SECRET ?: TOKEN_KEY);
} catch (Exception $e) {
    audit_log('LICENSE_INVALID_KEY', 'tenants', $tenantId, null, ['error'=>$e->getMessage()]);
    echo json_encode(['ok'=>false,'error'=>'Chave inválida ou corrompida. Verifique e tente novamente.']);
    exit;
}

// Verifica se a chave é para este tenant (ou é universal)
if (!empty($payload['tenant_id']) && $payload['tenant_id'] !== $tenantId) {
    audit_log('LICENSE_WRONG_TENANT', 'tenants', $tenantId);
    echo json_encode(['ok'=>false,'error'=>'Esta chave não é válida para esta conta.']);
    exit;
}

// Verifica validade
$expiry = strtotime($payload['expiryDate'] ?? '2000-01-01');
if ($expiry < time()) {
    echo json_encode(['ok'=>false,'error'=>'Chave expirada em '.date('d/m/Y',$expiry).'. Solicite uma nova.']);
    exit;
}

// Ativa!
db_update('tenants', [
    'license_status' => 'ACTIVE',
    'license_expiry' => date('Y-m-d H:i:s', $expiry),
    'license_key'    => $key,
    'activated_at'   => date('Y-m-d H:i:s'),
], 'id=?', [$tenantId]);

// Atualiza a sessão
$_SESSION['user']['license_status'] = 'ACTIVE';
$_SESSION['user']['license_expiry']  = date('Y-m-d H:i:s', $expiry);

audit_log('LICENSE_ACTIVATED', 'tenants', $tenantId, null, [
    'plan'   => $payload['plan'] ?? 'PRO',
    'expiry' => date('d/m/Y', $expiry),
]);

echo json_encode([
    'ok'     => true,
    'plan'   => $payload['plan']  ?? 'PRO',
    'expiry' => date('d/m/Y', $expiry),
    'days'   => ceil(($expiry - time()) / 86400),
]);
