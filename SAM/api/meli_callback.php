<?php
/**
 * api/meli_callback.php
 * Callback OAuth ML — valida state anti-CSRF, suporta dois Client Secrets simultâneos.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

session_start_secure();
auth_require();

$user     = auth_user();
$tenantId = $user['tenant_id'];

// ── Valida state anti-CSRF ───────────────────────────────
$stateReceived = $_GET['state'] ?? '';
$stateExpected = $_SESSION['meli_oauth_state'] ?? '';
$stateTs       = $_SESSION['meli_oauth_ts'] ?? 0;

// Limpa state da sessão imediatamente (one-time use)
unset($_SESSION['meli_oauth_state'], $_SESSION['meli_oauth_ts']);

if (!$stateReceived || !hash_equals($stateExpected, $stateReceived)) {
    header('Location: /pages/config_ml.php?error=invalid_state');
    exit;
}

// State não pode ter mais de 10 minutos
if (time() - $stateTs > 600) {
    header('Location: /pages/config_ml.php?error=expired_state');
    exit;
}

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

if ($error || !$code) {
    header('Location: /pages/config_ml.php?error=' . urlencode($error ?: 'no_code'));
    exit;
}

// ── Busca credenciais do tenant ──────────────────────────
function get_setting(string $tenantId, string $key): string {
    $row = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?", [$tenantId, $key]);
    return $row['value'] ?? '';
}

$appId      = get_setting($tenantId, 'meli_app_id')        ?: (defined('MELI_APP_ID')      ? MELI_APP_ID      : '');
$secret     = get_setting($tenantId, 'meli_client_secret') ?: (defined('MELI_CLIENT_SECRET')? MELI_CLIENT_SECRET: '');
$secretNew  = get_setting($tenantId, 'meli_client_secret_new'); // Segundo secret durante rotação
$redirectUri = APP_URL . '/meli/callback.php';

if (!$appId || !$secret) {
    header('Location: /pages/config_ml.php?error=no_credentials');
    exit;
}

// ── Troca code por token — tenta secret atual, depois o novo se existir ──
function exchange_code(string $appId, string $secret, string $code, string $redirectUri): ?array {
    $ch = curl_init('https://api.mercadolibre.com/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type'   => 'authorization_code',
            'client_id'    => $appId,
            'client_secret'=> $secret,
            'code'         => $code,
            'redirect_uri' => $redirectUri,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return (!empty($data['access_token'])) ? $data : null;
}

// Tenta com secret principal
$token = exchange_code($appId, $secret, $code, $redirectUri);

// Se falhou e há secret novo (rotação), tenta com ele
if (!$token && $secretNew) {
    $token = exchange_code($appId, $secretNew, $code, $redirectUri);
}

if (!$token) {
    header('Location: /pages/config_ml.php?error=token_exchange_failed');
    exit;
}

// ── Busca dados do usuário ML ────────────────────────────
$ch = curl_init('https://api.mercadolibre.com/users/me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$meliUser = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($meliUser['id'])) {
    header('Location: /pages/config_ml.php?error=user_fetch_failed');
    exit;
}

// ── Salva/atualiza conta ML ──────────────────────────────
// Criptografa tokens antes de salvar (AES-256-GCM)
$encAccess  = TOKEN_KEY ? crypto_encrypt_token($token['access_token'])          : $token['access_token'];
$encRefresh = TOKEN_KEY ? crypto_encrypt_token($token['refresh_token'] ?? '')   : ($token['refresh_token'] ?? '');

$accountId = db_upsert('meli_accounts', [
    'tenant_id'         => $tenantId,
    'meli_user_id'      => (string)$meliUser['id'],
    'nickname'          => $meliUser['nickname'] ?? '',
    'email'             => $meliUser['email'] ?? '',
    'access_token_enc'  => $encAccess,
    'refresh_token_enc' => $encRefresh,
    'token_expires_at'  => date('Y-m-d H:i:s', time() + ($token['expires_in'] ?? 21600)),
    'reputation_level'  => $meliUser['seller_reputation']['level_id'] ?? null,
    'is_active'         => 1,
    'last_sync_at'      => date('Y-m-d H:i:s'),
], ['access_token_enc', 'refresh_token_enc', 'token_expires_at', 'is_active', 'last_sync_at', 'nickname', 'email']);

// Define como conta ativa na sessão
$_SESSION['active_meli_account_id'] = $accountId;
audit_log('CONNECT_ML_ACCOUNT', 'meli_accounts', $accountId, null, ['nickname'=>$meliUser['nickname']??'']);

// ── Registra webhook automaticamente via API ML ──────────
// Webhook é OPCIONAL — falha aqui não significa que a conta não conectou
$webhookUrl = APP_URL . '/api/webhooks/meli.php';
$topics     = ['orders_v2', 'messages', 'questions', 'payments', 'shipments', 'items'];

$webhookOk = false;

try {
    // Tenta registrar/atualizar via PUT
    $ch = curl_init("https://api.mercadolibre.com/applications/{$appId}/notifications");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode(['callback_url'=>$webhookUrl,'topics'=>$topics]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token['access_token'],
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $notifBody = curl_exec($ch);
    $notifCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($notifCode >= 200 && $notifCode < 300) {
        $webhookOk = true;
    } else {
        // PUT falhou — verifica se já existe webhook via GET
        $chk = curl_init("https://api.mercadolibre.com/applications/{$appId}/notifications");
        curl_setopt_array($chk, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token['access_token']],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $chkBody = curl_exec($chk);
        $chkCode = (int)curl_getinfo($chk, CURLINFO_HTTP_CODE);
        curl_close($chk);

        if ($chkCode === 200) {
            $existing = json_decode($chkBody, true);
            // Webhook já configurado com qualquer URL = OK (foi feito manualmente)
            if (!empty($existing['callback_url'])) {
                $webhookOk = true;
            }
        }
    }
} catch (Throwable $e) {
    $webhookOk = false;
}

audit_log('REGISTER_WEBHOOK', 'meli_accounts', $accountId, null, [
    'url' => $webhookUrl,
    'ok'  => $webhookOk,
]);

// Conta conectada com sucesso — webhook é configurado manualmente no painel ML
$successParam = 'connected';
header('Location: /pages/config_ml.php?success=' . $successParam . '&nickname=' . urlencode($meliUser['nickname'] ?? ''));
exit;
