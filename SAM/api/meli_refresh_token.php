<?php
/**
 * api/meli_refresh_token.php
 * Renova tokens ML expirados ou prestes a expirar.
 *
 * Execução via cron (recomendado a cada 30 min):
 *   /usr/local/lsws/lsphp83/bin/lsphp /home/www/lupa/api/meli_refresh_token.php
 *
 * Execução manual via browser (admin logado):
 *   GET /api/meli_refresh_token.php?force=1&secret=MASTER_SECRET
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/crypto.php';

$isCli = PHP_SAPI === 'cli';

// Via web: requer MASTER_SECRET para não expor endpoint aberto
if (!$isCli) {
    $secret = $_GET['secret'] ?? '';
    if (!hash_equals(MASTER_SECRET, $secret)) {
        http_response_code(403);
        exit(json_encode(['ok' => false, 'error' => 'Acesso negado']));
    }
    header('Content-Type: text/plain');
}

function log_token(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    // Também grava no log mesmo quando chamado via web
    @file_put_contents(
        dirname(__DIR__) . '/storage/logs/token_refresh.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}

function tenant_get_cred(string $tenantId, string $key): string {
    $row = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?",
        [$tenantId, $key]
    );
    return $row['value'] ?? '';
}

function try_refresh(string $appId, string $secret, string $refreshToken): ?array {
    $postData = http_build_query([
        'grant_type'    => 'refresh_token',
        'client_id'     => $appId,
        'client_secret' => $secret,
        'refresh_token' => $refreshToken,
    ]);

    // Usa proxy Cloudflare se configurado (necessário em servidores bloqueados pelo ML)
    if (defined('ML_PROXY_URL') && ML_PROXY_URL) {
        $url     = ML_PROXY_URL . '?path=' . urlencode('/oauth/token');
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: SAM-ERP/1.0',
            'X-Proxy-Secret: ' . ML_PROXY_SECRET,
        ];
    } else {
        $url     = 'https://api.mercadolibre.com/oauth/token';
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: SAM-ERP/1.0',
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);
    return (!empty($data['access_token'])) ? $data : null;
}

// ── Busca contas que precisam de refresh ─────────────────────────
// Renova quando faltam menos de 2 horas para expirar
// --force via CLI ou ?force=1 via web renova todas as contas ativas
$force = ($isCli && in_array('--force', $argv ?? [])) || (!$isCli && ($_GET['force'] ?? '') === '1');

if ($force) {
    $accounts = db_all("SELECT * FROM meli_accounts WHERE is_active=1", []);
    log_token("Modo forçado: renovando todos os " . count($accounts) . " tokens ativos");
} else {
    $accounts = db_all(
        "SELECT * FROM meli_accounts
         WHERE is_active=1
           AND token_expires_at <= DATE_ADD(NOW(), INTERVAL 2 HOUR)",
        []
    );
}

if (empty($accounts)) {
    log_token("Nenhum token precisa de renovação agora.");
    exit(0);
}

log_token(count($accounts) . " token(s) para renovar...");

$ok     = 0;
$falhou = 0;

foreach ($accounts as $acc) {

    // Tokens de demo não têm credenciais reais — pula
    if (in_array($acc['refresh_token_enc'], ['demo_token', 'demo_refresh', ''])) {
        log_token("Pulando conta demo: {$acc['nickname']}");
        continue;
    }

    if (empty($acc['refresh_token_enc'])) {
        log_token("Sem refresh token para {$acc['nickname']} — pulando");
        continue;
    }

    // Pega credenciais do tenant ou usa as globais do config.php
    $appId     = tenant_get_cred($acc['tenant_id'], 'meli_app_id')        ?: (defined('MELI_APP_ID')        ? MELI_APP_ID        : '');
    $appSecret = tenant_get_cred($acc['tenant_id'], 'meli_client_secret') ?: (defined('MELI_CLIENT_SECRET') ? MELI_CLIENT_SECRET : '');

    if (!$appId || !$appSecret) {
        log_token("Sem credenciais ML para tenant {$acc['tenant_id']} ({$acc['nickname']}) — pulando");
        continue;
    }

    // Descriptografa refresh token
    $refreshToken = $acc['refresh_token_enc'];
    try {
        $refreshToken = crypto_decrypt_token($refreshToken);
    } catch (Throwable $e) {
        // Token não estava criptografado — usa como está
    }

    // Tenta renovar (com secret novo se houver rotação de chave)
    $token = try_refresh($appId, $appSecret, $refreshToken);

    if (!$token) {
        $appSecretNew = tenant_get_cred($acc['tenant_id'], 'meli_client_secret_new');
        if ($appSecretNew) {
            log_token("Tentando com secret rotacionado para {$acc['nickname']}...");
            $token = try_refresh($appId, $appSecretNew, $refreshToken);
        }
    }

    if (!$token || empty($token['access_token'])) {
        $falhou++;
        $errMsg = $token['message'] ?? $token['error'] ?? 'sem resposta';
        log_token("FALHOU refresh para {$acc['nickname']}: {$errMsg}");

        // Se o refresh token é inválido (usuário revogou acesso no ML)
        // marca a conta como inativa para não tentar mais
        if (($token['error'] ?? '') === 'invalid_grant') {
            db_update('meli_accounts', ['is_active' => 0], 'id=?', [$acc['id']]);
            log_token("Conta {$acc['nickname']} DESATIVADA — refresh token revogado pelo usuário no ML");
            log_token("Ação necessária: reconectar a conta em Integração ML");
            // Notifica proprietário via WhatsApp
            require_once dirname(__DIR__) . '/whatsapp.php';
            wpp_notify_conta_desconectada($acc['nickname']);
        }
        continue;
    }

    // Criptografa e salva os novos tokens
    $newAccess  = crypto_encrypt_token($token['access_token']);
    $newRefresh = crypto_encrypt_token($token['refresh_token'] ?? $refreshToken);
    $expiresIn  = (int)($token['expires_in'] ?? 21600); // padrão 6h

    db_update('meli_accounts', [
        'access_token_enc'  => $newAccess,
        'refresh_token_enc' => $newRefresh,
        'token_expires_at'  => date('Y-m-d H:i:s', time() + $expiresIn),
        'last_sync_at'      => date('Y-m-d H:i:s'),
    ], 'id=?', [$acc['id']]);

    $ok++;
    $expiresAt = date('H:i', time() + $expiresIn);
    log_token("OK — {$acc['nickname']} renovado, expira às {$expiresAt}");
}

log_token("Concluído: {$ok} renovados, {$falhou} com falha.");
exit($falhou > 0 ? 1 : 0);
