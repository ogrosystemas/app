<?php
/**
 * api/meli_connect.php
 * Inicia o fluxo OAuth com parâmetro state anti-CSRF (boas práticas ML).
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_secure();
auth_require();

// Gera state seguro e armazena na sessão
$state = bin2hex(random_bytes(16));
$_SESSION['meli_oauth_state'] = $state;
$_SESSION['meli_oauth_ts']    = time();

$tenantId = auth_user()['tenant_id'];
$appId    = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='meli_app_id'", [$tenantId])['value']
            ?? MELI_APP_ID ?? '';

if (!$appId) {
    header('Location: /pages/config_ml.php?error=no_credentials');
    exit;
}

$redirectUri = APP_URL . '/meli/callback.php';
$authUrl     = 'https://auth.mercadolivre.com.br/authorization?' . http_build_query([
    'response_type' => 'code',
    'client_id'     => $appId,
    'redirect_uri'  => $redirectUri,
    'state'         => $state,
]);

header('Location: ' . $authUrl);
exit;
