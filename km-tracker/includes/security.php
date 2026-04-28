<?php
// ============================================================
// includes/security.php  —  Camada de segurança completa
// v1.2 — otimizado para Locaweb (Linux + Apache + HTTPS + compartilhado)
// ============================================================

// ── 1. CABEÇALHOS DE SEGURANÇA HTTP ─────────────────────────
function applySecurityHeaders(): void
{
    if (headers_sent()) return;

    // Impede MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // Bloqueia iframes externos (anti-clickjacking)
    header('X-Frame-Options: SAMEORIGIN');

    // Filtro XSS do navegador
    header('X-XSS-Protection: 1; mode=block');

    // Controle de referrer
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // HSTS — força HTTPS por 1 ano (seguro pois temos certificado SSL)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // Permissões de recursos do browser
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://*.tile.openstreetmap.org https://lh3.googleusercontent.com https://drive.google.com https://*.googleusercontent.com; connect-src 'self' https://nominatim.openstreetmap.org https://graphhopper.com;");

    // Remove headers que expõem informações do servidor
    header_remove('X-Powered-By');
    header_remove('Server');
}

// ── 2. RATE LIMITING por arquivo ────────────────────────────
// Usa o diretório logs/ do projeto (escrita garantida na Locaweb)
function checkRateLimit(string $action = 'global', int $maxReq = 60, int $window = 60): void
{
    $ip   = getClientIp();
    $key  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', "rl_{$action}_{$ip}");
    $dir  = __DIR__ . '/../logs/rl/';
    $file = $dir . $key . '.json';

    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $now  = time();
    $data = [];

    if (file_exists($file)) {
        $raw  = @file_get_contents($file);
        $data = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    $data = array_values(array_filter($data, fn($t) => $t > $now - $window));

    if (count($data) >= $maxReq) {
        http_response_code(429);
        header('Retry-After: ' . $window);
        die(renderSecurityBlock(
            '429 — Muitas Requisições',
            'Você fez requisições demais em pouco tempo.',
            "Aguarde $window segundos e tente novamente.",
            $ip
        ));
    }

    $data[] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

// ── 3. PROTEÇÃO CONTRA BRUTE FORCE ──────────────────────────
function recordFailedLogin(string $ip): void
{
    $dir  = __DIR__ . '/../logs/bf/';
    $file = $dir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';

    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $now  = time();
    $data = [];

    if (file_exists($file)) {
        $raw  = @file_get_contents($file);
        $data = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    $data = array_values(array_filter($data, fn($t) => $t > $now - 900));
    $data[] = $now;

    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function isIpBlocked(string $ip, int $maxAttempts = 5, int $window = 900): bool
{
    $dir  = __DIR__ . '/../logs/bf/';
    $file = $dir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';

    if (!file_exists($file)) return false;

    $raw    = @file_get_contents($file);
    $data   = $raw ? (json_decode($raw, true) ?? []) : [];
    $now    = time();
    $recent = array_filter($data, fn($t) => $t > $now - $window);

    return count($recent) >= $maxAttempts;
}

function clearFailedLogins(string $ip): void
{
    $dir  = __DIR__ . '/../logs/bf/';
    $file = $dir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';
    if (file_exists($file)) @unlink($file);
}

function getRemainingLockTime(string $ip, int $window = 900): int
{
    $dir    = __DIR__ . '/../logs/bf/';
    $file   = $dir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';

    if (!file_exists($file)) return 0;

    $raw    = @file_get_contents($file);
    $data   = $raw ? (json_decode($raw, true) ?? []) : [];
    $now    = time();
    $recent = array_filter($data, fn($t) => $t > $now - $window);

    if (empty($recent)) return 0;
    return max(0, $window - ($now - min($recent)));
}

// ── 4. VALIDAÇÃO E SANITIZAÇÃO ──────────────────────────────
function sanitizeString(string $value, int $maxLen = 255): string
{
    return mb_substr(strip_tags(trim($value)), 0, $maxLen);
}

function sanitizeInt(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int
{
    $v = filter_var($value, FILTER_VALIDATE_INT);
    if ($v === false) return $min;
    return max($min, min($max, (int)$v));
}

function sanitizeFloat(mixed $value, float $min = 0.0): float
{
    $v = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($v === false) return $min;
    return max($min, (float)$v);
}

function sanitizeEmail(string $value): string
{
    $v = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    return $v === false ? '' : $v;
}

function sanitizeDate(string $value): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return '';
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : '';
}

function validatePasswordStrength(string $password): array
{
    $errors = [];
    if (mb_strlen($password) < 8)          $errors[] = 'Mínimo 8 caracteres';
    if (!preg_match('/[A-Z]/', $password))  $errors[] = 'Ao menos uma letra maiúscula';
    if (!preg_match('/[a-z]/', $password))  $errors[] = 'Ao menos uma letra minúscula';
    if (!preg_match('/[0-9]/', $password))  $errors[] = 'Ao menos um número';
    return $errors;
}

// ── 5. DETECÇÃO DE ATAQUES ───────────────────────────────────
function detectAttackPatterns(array $inputs): bool
{
    $patterns = [
        '/\b(union\s+select|select\s+\*|select\s+\w+\s+from|insert\s+into|drop\s+table|drop\s+database|alter\s+table)\b/i',
        '/(--\s|\/\*[\s\S]*?\*\/|;\s*drop|;\s*delete|;\s*update|;\s*insert)/i',
        '/<\s*(script|iframe|object|embed)\b/i',
        '/\bon(load|error|click|mouseover|focus|blur)\s*=\s*["\']?\s*\w/i',
        '/javascript\s*:\s*\w/i',
        '/\.\.[\/\\\\]\.\.[\/\\\\]/i',
        '/\x00/',
    ];

    foreach ($inputs as $value) {
        if (!is_string($value) || strlen($value) === 0) continue;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
    }
    return false;
}

function scanRequestInputs(): void
{
    // Escaneia GET e POST apenas — cookies excluídos para evitar falsos positivos
    $flat = [];
    array_walk_recursive(array_merge(
        array_values($_GET),
        array_values($_POST)
    ), function($v) use (&$flat) { $flat[] = $v; });

    if (detectAttackPatterns($flat)) {
        $ip = getClientIp();
        logSecurityEvent('attack_detected', $ip, 'Padrão de ataque detectado');
        http_response_code(400);
        die(renderSecurityBlock(
            '400 — Requisição Bloqueada',
            'Sua requisição contém padrões não permitidos.',
            'Se acredita que isso é um erro, entre em contato com o suporte.',
            $ip
        ));
    }
}

// ── 6. INTEGRIDADE DE SESSÃO ─────────────────────────────────
// Em servidor compartilhado Locaweb com HTTPS o IP é estável.
// Mantemos fingerprint com UA + APP_SECRET (sem IP para robustez).
function bindSessionToClient(): void
{
    $ua          = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $secret      = defined('APP_SECRET') ? APP_SECRET : 'km_fallback_secret';
    $fingerprint = hash('sha256', $ua . $secret);

    if (isset($_SESSION['_fingerprint'])) {
        if (!hash_equals($_SESSION['_fingerprint'], $fingerprint)) {
            $ip = getClientIp();
            logSecurityEvent('session_hijack_attempt', $ip,
                'Fingerprint não confere — possível session hijacking');
            _destroyAndRedirect('/login.php?err=session');
        }
    } else {
        $_SESSION['_fingerprint'] = $fingerprint;
    }

    // Timeout de inatividade — 2 horas
    if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > 7200) {
        _destroyAndRedirect('/login.php?err=timeout');
    }
    $_SESSION['_last_activity'] = time();

    // Regenera ID de sessão a cada 15 minutos (anti session fixation)
    if (!isset($_SESSION['_regenerated_at'])) {
        $_SESSION['_regenerated_at'] = time();
    } elseif (time() - $_SESSION['_regenerated_at'] > 900) {
        session_regenerate_id(true);
        $_SESSION['_regenerated_at'] = time();
    }
}

function _destroyAndRedirect(string $path): never
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . $path);
    exit;
}

// ── 7. LOG DE SEGURANÇA ──────────────────────────────────────
function logSecurityEvent(string $type, string $ip, string $detail = ''): void
{
    $dir  = __DIR__ . '/../logs/security/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $file = $dir . 'security_' . date('Y-m') . '.log';
    $line = date('Y-m-d H:i:s')
          . ' | ' . strtoupper($type)
          . ' | IP:' . $ip
          . ' | UA:' . mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 80)
          . ' | URI:' . mb_substr($_SERVER['REQUEST_URI'] ?? '-', 0, 120)
          . ($detail ? ' | ' . $detail : '')
          . PHP_EOL;

    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

// ── HELPERS ──────────────────────────────────────────────────

// Em servidor compartilhado Locaweb, o IP real do cliente pode vir via
// X-Forwarded-For quando há balanceador de carga interno.
// Locaweb usa balanceadores em alguns planos — verificamos os headers nessa ordem.
function getClientIp(): string
{
    // Na Locaweb, REMOTE_ADDR é confiável para planos sem balanceador.
    // Para planos com balanceador, HTTP_X_FORWARDED_FOR traz o IP real.
    $trustedProxies = ['127.0.0.1', '::1'];

    // Tenta X-Forwarded-For apenas se o request vier de proxy confiável
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (in_array($remoteAddr, $trustedProxies, true)) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // Cloudflare (caso use no futuro)
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }

    // Padrão: REMOTE_ADDR (sempre confiável quando não há proxy)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function renderSecurityBlock(string $title, string $msg, string $hint, string $ip = ''): string
{
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    return '<!DOCTYPE html><html lang="pt-BR"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
      *{margin:0;padding:0;box-sizing:border-box}
      body{font-family:Arial,sans-serif;background:var(--bg-body);color:var(--text);
           min-height:100vh;display:grid;place-items:center}
      .box{max-width:460px;text-align:center;padding:40px}
      .icon{font-size:3rem;margin-bottom:20px}
      h1{font-size:1.4rem;color:#e05c5c;margin-bottom:12px}
      p{color:#8890b0;margin-bottom:8px;font-size:.9rem;line-height:1.6}
      .hint{font-size:.8rem;color:#555e7e;margin-top:16px}
      a{color:#c9a84c;text-decoration:none}
    </style></head><body>
    <div class="box">
      <div class="icon">&#128274;</div>
      <h1>' . htmlspecialchars($title) . '</h1>
      <p>' . htmlspecialchars($msg) . '</p>
      <p class="hint">' . htmlspecialchars($hint) . '</p>
      <p class="hint" style="margin-top:24px">
        <a href="' . $baseUrl . '/login.php">&#8592; Ir para o login</a>
      </p>
    </div></body></html>';
}
