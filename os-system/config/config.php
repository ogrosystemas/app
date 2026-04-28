<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Security Headers ─────────────────────────────────────────────────────────
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    // Uncomment when HTTPS is active:
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Definir BASE_URL para Locaweb (sem a pasta, pois pode estar na raiz)
// Se estiver em uma subpasta, ajuste: define('BASE_URL', '/ossystem');
define('BASE_URL', '');  // Para Locaweb na raiz

// Mercado Pago — configure via painel Admin > Mercado Pago
// As credenciais são salvas em config/mercadopago.php
$_mpFile = __DIR__ . '/mercadopago.php';
if (file_exists($_mpFile)) {
    $_mpCfg = include $_mpFile;
    define('MP_TOKEN',  $_mpCfg['mp_access_token'] ?? '');
    define('MP_DEVICE', $_mpCfg['mp_device_id']    ?? '');
    unset($_mpCfg, $_mpFile);
} else {
    define('MP_TOKEN',  '');
    define('MP_DEVICE', '');
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../modules/auth/auth.php';
require_once __DIR__ . '/licenca.php';

$database = new Database();
$db       = $database->getConnection();
$auth     = new Auth($db);

// Inicializar objeto de permissão se usuário estiver logado
$permissao = null;
if ($auth->isLoggedIn()) {
    require_once __DIR__ . '/../modules/auth/permissao.php';
    $usuario   = $auth->getCurrentUser();
    $permissao = new Permissao($db, $usuario['perfil']);
}

function checkAuth($perfisPermitidos = []) {
    global $auth, $db;
    if (!$auth->isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if (!empty($perfisPermitidos) && !$auth->hasPermission($perfisPermitidos)) {
        header('Location: ' . BASE_URL . '/index.php?erro=permissao');
        exit;
    }
    licenca_check($db);
}

function checkPermissao($modulo, $acao = 'ver') {
    global $permissao;
    if ($permissao && !$permissao->temPermissao($modulo, $acao)) {
        header('Location: ' . BASE_URL . '/index.php?erro=permissao');
        exit;
    }
}

function formatMoney($value) {
    return 'R$ ' . number_format(floatval($value), 2, ',', '.');
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

// ── CSRF Protection ───────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): void {
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token inválido. Recarregue a página e tente novamente.');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken()) . '">';
}
