<?php
// ============================================================
// config.php — Edite aqui. Sem .env, sem terminal.
// ============================================================

define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'lupa_erp');
define('DB_USER',     'lupa_erp');
define('DB_PASS',     'Lupa2026');

define('REDIS_HOST',  '127.0.0.1');
define('REDIS_PORT',  6379);
define('REDIS_PASS',  '0186ad1e13f1373f');

define('APP_URL',     'https://lupa.ogrosystemas.com.br');
// APP_NAME updated
define('APP_NAME', 'SAM - Sistema de Acompanhamento Mercado Livre');
define('APP_DEBUG',   false);

define('SESSION_NAME',    'lupa_sess');
define('SESSION_SECRET',  'troque-por-string-aleatoria-longa-aqui');
define('SESSION_LIFETIME', 28800);

// Licença — gere em keygen-lupa.html
define('LICENSE_KEY',    '');
define('MASTER_SECRET',  '4bb6f3015f4649631c584aea8529e43220c7c20e9ab7d911ede8e24971cc1b12');

// Mercado Livre
define('MELI_APP_ID',        '');
define('MELI_CLIENT_SECRET', '');
define('MELI_REDIRECT_URI',  APP_URL . '/meli/callback.php');

// IA
define('GEMINI_API_KEY', '');
define('GROQ_API_KEY',   ''); // Fallback gratuito — console.groq.com

// Criptografia de tokens ML — gere com: bin2hex(random_bytes(32))
// TOKEN_KEY: Gere uma chave única com: python3 -c "import secrets; print(secrets.token_hex(32))"
// Cole o resultado abaixo. NUNCA compartilhe ou commite esta chave.
define('TOKEN_KEY', getenv('OGRO_TOKEN_KEY') ?: '45e8789373d55a70ea5a2153ea9c7771f2c5c400e25791032684960bfe863787');

define('ROOT_PATH', __DIR__);
define('LOG_FILE',  __DIR__ . '/storage/app.log');

// ── Helpers de data em pt-BR ─────────────────────────────
function date_ptbr(string $format, ?int $ts = null): string {
    if ($ts === null) $ts = time();
    $days   = ['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira',
               'Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'];
    $months = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril',
               'May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto',
               'September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro'];
    $days_short   = ['Sun'=>'Dom','Mon'=>'Seg','Tue'=>'Ter','Wed'=>'Qua','Thu'=>'Qui','Fri'=>'Sex','Sat'=>'Sáb'];
    $months_short = ['Jan'=>'Jan','Feb'=>'Fev','Mar'=>'Mar','Apr'=>'Abr','May'=>'Mai','Jun'=>'Jun',
                     'Jul'=>'Jul','Aug'=>'Ago','Sep'=>'Set','Oct'=>'Out','Nov'=>'Nov','Dec'=>'Dez'];
    $result = date($format, $ts);
    $result = str_replace(array_keys($days),   array_values($days),   $result);
    $result = str_replace(array_keys($months), array_values($months), $result);
    $result = str_replace(array_keys($days_short),   array_values($days_short),   $result);
    $result = str_replace(array_keys($months_short), array_values($months_short), $result);
    return $result;
}

function date_label(string $format = 'l, d \d\e F \d\e Y'): string {
    return date_ptbr($format);
}

// ── Audit log helper ─────────────────────────────────────
function audit_log(string $action, ?string $table = null, ?string $recordId = null, ?array $oldData = null, ?array $newData = null): void {
    try {
        $user     = $_SESSION['user'] ?? [];
        $tenantId = $user['tenant_id'] ?? 'system';
        $userId   = $user['id']        ?? null;
        $ip       = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'cli')[0];

        db_insert('audit_logs', [
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'action'     => $action,
            'table_name' => $table,
            'record_id'  => $recordId,
            'old_data'   => $oldData  ? json_encode($oldData,  JSON_UNESCAPED_UNICODE) : null,
            'new_data'   => $newData  ? json_encode($newData,  JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ip,
        ]);
    } catch (Throwable $e) {
        // Audit log nunca pode quebrar o fluxo principal
    }
}

// ── LGPD: Máscara de dados pessoais ─────────────────────
function lgpd_mask_email(string $email): string {
    if (!$email || !str_contains($email, '@')) return '***@***.***';
    [$local, $domain] = explode('@', $email, 2);
    return substr($local, 0, 2) . str_repeat('*', max(2, strlen($local)-2)) . '@' . $domain;
}

function lgpd_mask_name(string $name): string {
    $parts = explode(' ', trim($name));
    if (count($parts) <= 1) return substr($name, 0, 2) . str_repeat('*', max(2, strlen($name)-2));
    return $parts[0] . ' ' . str_repeat('*', strlen(implode(' ', array_slice($parts, 1))));
}

function lgpd_mask_zip(string $zip): string {
    return substr($zip, 0, 3) . '**-***';
}

function lgpd_can_see_pii(): bool {
    $user = $_SESSION['user'] ?? [];
    return in_array($user['role'] ?? '', ['ADMIN','MANAGER']) || ($user['can_access_financeiro'] ?? false);
}

function lgpd_apply(array &$row): void {
    if (lgpd_can_see_pii()) return;
    if (isset($row['buyer_email']))      $row['buyer_email']      = lgpd_mask_email($row['buyer_email']);
    if (isset($row['buyer_last_name']))  $row['buyer_last_name']  = str_repeat('*', strlen($row['buyer_last_name']??''));
    if (isset($row['ship_zip']))         $row['ship_zip']         = lgpd_mask_zip($row['ship_zip']);
    if (isset($row['ship_street']))      $row['ship_street']      = substr($row['ship_street']??'', 0, 10) . '...';
}

// ── Proxy Cloudflare Worker para API ML ──────────────────
// Necessário quando o IP do servidor está bloqueado pelo ML (ex: Contabo)
// Deixe em branco para chamar o ML diretamente
define('ML_PROXY_URL',    'https://mlproxy.ogrosystemas.com.br');
define('ML_PROXY_SECRET', 'sam2026xpto');
// ── Curl helper — força IPv4 e suporta proxy Cloudflare Worker ──
// Necessário em servidores com IP bloqueado pelo ML (ex: Contabo VPS)
function curl_ml(string $url, array $opts = []): array {
    // Roteia pelo Cloudflare Worker se configurado
    if (defined('ML_PROXY_URL') && ML_PROXY_URL) {
        $path     = str_replace('https://api.mercadolibre.com', '', $url);
        $finalUrl = ML_PROXY_URL . '?path=' . urlencode($path);
        $headers  = $opts[CURLOPT_HTTPHEADER] ?? [];
        $headers[] = 'X-Proxy-Secret: ' . ML_PROXY_SECRET;
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $url = $finalUrl;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, array_replace([
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ], $opts));
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['body' => $body, 'code' => $code, 'error' => $err];
}

// ── Evolution API (WhatsApp notificações) ─────────────────
define('EVOLUTION_URL',      'http://161.97.126.36:8080');
define('EVOLUTION_KEY',      '');   // preencher após configurar
define('EVOLUTION_INSTANCE', '');   // nome da instância criada
define('EVOLUTION_OWNER',    '');   // número do proprietário ex: 5511999999999
