<?php
/**
 * api/auth.php — Endpoint de login/logout
 * Rate limiting: max 5 tentativas/IP em 15min, bloqueia 30min
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_secure();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$action = $_REQUEST['action'] ?? '';

// ── Logout ───────────────────────────────────────────────
if ($action === 'logout') {
    auth_logout();
    // auth_logout faz redirect, nunca chega aqui
    exit;
}

// ── Login ────────────────────────────────────────────────
if ($action === 'login') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';
    $ip       = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0];
    $ip       = trim($ip);

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Preencha e-mail e senha']);
        exit;
    }

    // ── Rate limiting ────────────────────────────────────
    $windowStart = date('Y-m-d H:i:s', time() - 900); // 15 min
    try {
        $attempts = db_one(
            "SELECT COUNT(*) as cnt, MAX(created_at) as last_attempt
             FROM login_attempts
             WHERE ip_address = ? AND created_at > ? AND success = 0",
            [$ip, $windowStart]
        );
        if ((int)($attempts['cnt'] ?? 0) >= 5) {
            $wait = max(0, 1800 - (time() - strtotime($attempts['last_attempt'] ?? 'now')));
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error'   => 'Muitas tentativas. Aguarde ' . ceil($wait / 60) . ' minuto(s).',
            ]);
            exit;
        }
    } catch (Throwable $e) {
        // Se rate limiting falhar, continua sem bloquear
    }

    // ── Tenta login ──────────────────────────────────────
    $user = auth_login($email, $password);

    // ── Registra tentativa (INT AUTO_INCREMENT — sem db_insert) ──
    try {
        db_query(
            "INSERT INTO login_attempts (ip_address, email, success, user_agent, created_at) VALUES (?,?,?,?,?)",
            [$ip, $email, $user ? 1 : 0, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200), date('Y-m-d H:i:s')]
        );
    } catch (Throwable $e) {}

    if (!$user) {
        usleep(500000); // 500ms delay anti-timing attack
        echo json_encode(['success' => false, 'error' => 'E-mail ou senha inválidos']);
        exit;
    }

    // ── Login OK ─────────────────────────────────────────
    try {
        db_query("DELETE FROM login_attempts WHERE ip_address = ? AND success = 0", [$ip]);
    } catch (Throwable $e) {}

    try {
        audit_log('LOGIN', 'users', $user['id'] ?? null, null, ['ip' => $ip]);
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'name'    => $user['name'],
        'role'    => $user['role'],
    ]);
    exit;
}

echo json_encode(['error' => 'Ação inválida']);
