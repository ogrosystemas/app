<?php
// ============================================================
// includes/auth.php  —  Autenticação e autorização
// ============================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

// ── Helpers de sessão ────────────────────────────────────────

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function isAdmin(): bool
{
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function currentUser(): array
{
    if (!isLoggedIn()) return [];
    return [
        'id'   => (int)$_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'],
    ];
}

// ── Proteção de páginas ──────────────────────────────────────

function requireLogin(): void
{
    // Sessão não iniciada ou sem dados de login
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // Verifica integridade da sessão (fingerprint + timeout)
    bindSessionToClient();
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        logSecurityEvent(
            'unauthorized_admin_access',
            getClientIp(),
            'User ID: ' . ($_SESSION['user_id'] ?? '?') . ' tentou acessar área admin'
        );
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
    }
}

// ── Login ────────────────────────────────────────────────────

function attemptLogin(string $email, string $password): bool|string
{
    $ip = getClientIp();

    // IP bloqueado por brute force?
    if (isIpBlocked($ip)) {
        $min = (int)ceil(getRemainingLockTime($ip) / 60);
        logSecurityEvent('brute_force_blocked', $ip, "Bloqueado ao tentar: $email");
        return "bloqueado:$min";
    }

    // Busca o usuário — e-mail em lowercase para consistência
    $stmt = db()->prepare(
        'SELECT id, name, password, role, active FROM users WHERE LOWER(email) = ? LIMIT 1'
    );
    $stmt->execute([mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    // Sempre roda password_verify (evita timing attack por enumeração de e-mail)
    $dummyHash = '$2y$12$dummyhashtopreventtimingattackXXXXXXXXXXXXXXXXXXXXXXXX';
    $hash      = $user ? $user['password'] : $dummyHash;

    if (!$user || !(bool)$user['active'] || !password_verify($password, $hash)) {
        recordFailedLogin($ip);
        logSecurityEvent('login_failed', $ip, "Tentativa com e-mail: $email");
        return false;
    }

    // ── Login bem-sucedido ───────────────────────────────────
    clearFailedLogins($ip);

    // Regenera o ID de sessão para prevenir session fixation
    session_regenerate_id(true);

    // Grava dados do usuário na sessão
    $_SESSION['user_id']         = (int)$user['id'];
    $_SESSION['user_name']       = $user['name'];
    $_SESSION['user_role']       = $user['role'];      // 'admin' ou 'user'
    $_SESSION['_last_activity']  = time();
    $_SESSION['_regenerated_at'] = time();

    // Fingerprint de sessão (UA + APP_SECRET, sem IP para robustez em produção)
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $_SESSION['_fingerprint'] = hash('sha256', $ua . APP_SECRET);

    // Logs
    logSecurityEvent('login_success', $ip,
        "User ID: {$user['id']} | Role: {$user['role']} | E-mail: $email");
    _logDbAction((int)$user['id'], 'login');

    // Atualiza o hash se o custo mudou (rehash automático)
    if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([$newHash, $user['id']]);
    }

    return true;
}

// ── Logout ───────────────────────────────────────────────────

function logout(): void
{
    if (isLoggedIn()) {
        logSecurityEvent('logout', getClientIp(), 'User ID: ' . $_SESSION['user_id']);
        _logDbAction((int)$_SESSION['user_id'], 'logout');
    }

    // Limpa a sessão completamente
    $_SESSION = [];

    // Remove o cookie de sessão
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

// ── Helpers internos ─────────────────────────────────────────

function _logDbAction(int $userId, string $action): void
{
    try {
        db()->prepare('INSERT INTO session_logs (user_id, action, ip) VALUES (?, ?, ?)')
            ->execute([$userId, $action, getClientIp()]);
    } catch (PDOException) {
        // Silencia — log de DB não deve quebrar o fluxo
    }
}

// Mantém compatibilidade com chamadas antigas de logDbAction()
function logDbAction(int $userId, string $action): void
{
    _logDbAction($userId, $action);
}

// ── CSRF ─────────────────────────────────────────────────────

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        logSecurityEvent('csrf_violation', getClientIp(), 'Token CSRF inválido ou ausente');
        http_response_code(403);
        die(renderSecurityBlock(
            '403 — Acesso Negado',
            'Token de segurança inválido ou expirado.',
            'Volte à página anterior e tente novamente.'
        ));
    }

    // Rotaciona o token após cada uso (one-time token)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
