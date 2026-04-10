<?php
/**
 * config/config.php — Bar System Pro
 * CyberPanel + LiteSpeed compatible
 */

// ── Banco de Dados ────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'barm_barmutante');
define('DB_PASS',    'Bar147369#');  // ← COLOQUE SUA SENHA AQUI
define('DB_NAME',    'barm_barmutante');  // Altere se o nome do banco for diferente
define('DB_CHARSET', 'utf8mb4');

// ── BASE_URL: detecção robusta (LiteSpeed / CyberPanel / Apache / Nginx) ──
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    // Suporte a proxy reverso (CyberPanel usa X-Forwarded-Proto)
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Calcular o caminho base a partir do __FILE__ real do config.php
    // config/config.php está em {root}/config/config.php
    // então a raiz do projeto é dirname(dirname(__FILE__))
    $project_root = str_replace('\\', '/', dirname(__DIR__)); // raiz do projeto no filesystem
    $doc_root     = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

    if ($doc_root && strpos($project_root, $doc_root) === 0) {
        // Derivar path web a partir do doc_root
        $web_path = substr($project_root, strlen($doc_root));
        $web_path = '/' . ltrim($web_path, '/');
        if (substr($web_path, -1) !== '/') $web_path .= '/';
    } else {
        // Fallback: raiz do domínio
        $web_path = '/';
    }

    define('BASE_URL', $scheme . '://' . $host . $web_path);
}

// ── Constantes do sistema ─────────────────────────────────────────────
define('SISTEMA_NOME',    'Bar System Pro');
define('SISTEMA_VERSAO',  '1.0.0');
define('BASE_PATH',       dirname(__DIR__) . '/');
define('UPLOAD_PATH',     BASE_PATH . 'assets/uploads/');
define('UPLOAD_URL',      BASE_URL  . 'assets/uploads/');

// ── Mercado Pago ─────────────────────────────────────────────────────────
// Documentação: https://www.mercadopago.com.br/developers/pt/docs/mp-point
define('MP_API_URL', 'https://api.mercadopago.com'); // URL única (sandbox é via token de teste)

// ── Timezone ──────────────────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');

// ── Sessão ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => 86400 * 7,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
