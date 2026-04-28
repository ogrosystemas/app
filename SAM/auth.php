<?php
/**
 * auth.php — Autenticação e autorização
 * SAM v2.0
 * 
 * IMPORTANTE: Sessão gerenciada por PHP sessions ($_SESSION).
 * A tabela sessions NÃO é usada no login — só PHP nativo.
 * login_attempts usa db_query direto (INT AUTO_INCREMENT).
 */

// ── Inicialização segura de sessão ───────────────────────
function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('SESSION_NAME') && SESSION_NAME) {
            session_name(SESSION_NAME);
        }
        session_set_cookie_params([
            'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Versão read-only: abre, lê e fecha o lock imediatamente
// Usar em APIs e endpoints que não precisam escrever na sessão
function session_start_readonly(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (defined('SESSION_NAME') && SESSION_NAME) {
            session_name(SESSION_NAME);
        }
        session_set_cookie_params([
            'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 28800,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start(['read_and_close' => true]);
    }
}

// ── Retorna usuário logado ────────────────────────────────
function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

// ── Requer autenticação ───────────────────────────────────
function auth_require(): void {
    session_start_secure();
    if (empty($_SESSION['user'])) {
        $url = urlencode($_SERVER['REQUEST_URI'] ?? '/pages/dashboard.php');
        header("Location: /pages/login.php?redirect={$url}");
        exit;
    }
}

// ── Requer permissão de módulo ────────────────────────────
function auth_module(string $permission): void {
    auth_require();
    $user = $_SESSION['user'];
    if (empty($user[$permission]) && ($user['role'] ?? '') !== 'ADMIN') {
        header('Location: /pages/errors/403.php');
        exit;
    }
}

// ── Login ─────────────────────────────────────────────────
function auth_login(string $email, string $password): array|false {
    $user = db_one(
        "SELECT u.*, t.license_status, t.license_expiry, t.trial_started, t.plan
         FROM users u
         JOIN tenants t ON t.id = u.tenant_id
         WHERE u.email = ? AND u.is_active = 1 AND t.is_active = 1",
        [strtolower(trim($email))]
    );

    if (!$user) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    // Verifica licença
    $status = $user['license_status'] ?? 'TRIAL';

    // Calcula expiry: usa o campo do banco se existir, senão trial_started + 15 dias
    if (!empty($user['license_expiry'])) {
        $expiry = strtotime($user['license_expiry']);
    } elseif (!empty($user['trial_started'])) {
        $expiry = strtotime($user['trial_started']) + (15 * 86400);
    } else {
        $expiry = strtotime($user['created_at'] ?? 'now') + (15 * 86400);
    }

    if ($status === 'BLOCKED') return false;

    if ($status === 'EXPIRED' || $expiry < time()) {
        // Marca como expirado no banco (ignora erro silenciosamente)
        try {
            db_update('tenants', ['license_status' => 'EXPIRED'], 'id = ?', [$user['tenant_id']]);
        } catch (Throwable $e) {}
        return false;
    }

    // Remove campos sensíveis antes de salvar na sessão
    unset($user['password_hash']);

    // Salva na sessão PHP
    $_SESSION['user'] = $user;

    // Atualiza last_login (ignora erro silenciosamente)
    try {
        db_update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    } catch (Throwable $e) {}

    return $user;
}

// ── Logout ────────────────────────────────────────────────
function auth_logout(): void {
    session_start_secure();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
    header('Location: /pages/login.php');
    exit;
}

// ── Verificação de licença ────────────────────────────────
function license_check(): void {
    $user   = $_SESSION['user'] ?? null;
    if (!$user) return;

    $status = $user['license_status'] ?? 'TRIAL';

    if (!empty($user['license_expiry'])) {
        $expiry = strtotime($user['license_expiry']);
    } elseif (!empty($user['trial_started'])) {
        $expiry = strtotime($user['trial_started']) + (15 * 86400);
    } else {
        $expiry = time() + (15 * 86400); // fallback seguro
    }

    if ($status === 'BLOCKED') {
        header('Location: /pages/errors/bloqueado.php?reason=BLOCKED'); exit;
    }
    if ($status === 'EXPIRED' || $expiry < time()) {
        header('Location: /pages/errors/bloqueado.php?reason=EXPIRED'); exit;
    }
    // TRIAL ou ACTIVE: OK
}


