<?php
// ============================================================
// login.php
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/settings.php';

// Inicia sessão ANTES de qualquer header
if (session_status() === PHP_SESSION_NONE) session_start();

// Aplica headers de segurança
applySecurityHeaders();

// Rate limit na página de login
checkRateLimit('login_page', 30, 60);

// Se já está logado, redireciona para o dashboard correto
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/user/dashboard.php');
    }
    exit;
}

$error       = '';
$lockMinutes = 0;
$ip          = getClientIp();

// Mensagens de sessão/timeout vindas da URL
if (!empty($_GET['err'])) {
    $err = $_GET['err'];
    if ($err === 'timeout') $error = 'Sua sessão expirou por inatividade. Faça login novamente.';
    if ($err === 'session') $error = 'Sessão inválida. Por segurança, faça login novamente.';
}

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Rate limit mais restrito para tentativas de POST
    checkRateLimit('login_attempt', 10, 900);

    $email    = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Preencha e-mail e senha.';
    } else {
        $result = attemptLogin($email, $password);

        if ($result === true) {
            // Redireciona baseado no role salvo NA SESSÃO
            $role = $_SESSION['user_role'] ?? '';
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/user/dashboard.php');
            }
            exit;

        } elseif (is_string($result) && str_starts_with($result, 'bloqueado:')) {
            $lockMinutes = (int)explode(':', $result)[1];
            $error = "Muitas tentativas. Aguarde {$lockMinutes} minuto(s) para tentar novamente.";

        } else {
            usleep(500000);
            $bfDir  = __DIR__ . '/logs/bf/';
            $bfFile = $bfDir . preg_replace('/[^a-zA-Z0-9_]/', '_', $ip) . '.json';
            $attempts = 0;
            if (file_exists($bfFile)) {
                $raw  = @file_get_contents($bfFile);
                $data = $raw ? (json_decode($raw, true) ?? []) : [];
                $attempts = count(array_filter($data, fn($t) => $t > time() - 900));
            }
            $remaining = max(0, 5 - $attempts);
            $error = $remaining > 0
                ? "E-mail ou senha incorretos. {$remaining} tentativa(s) restante(s)."
                : 'Conta temporariamente bloqueada por excesso de tentativas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Login — <?= htmlspecialchars(setting('sistema_nome', 'KM Tracker')) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
</head>
<body>
<div class="login-box">
  <div class="login-brand">
    <img src="<?= BASE_URL ?>/assets/logo.png" alt="Mutantes MC">
    <h1><?= htmlspecialchars(setting('sistema_nome', 'KM Tracker')) ?></h1>
    <p><?= htmlspecialchars(setting('clube_nome','KM Tracker')) ?> — Gestão de Quilometragem</p>
  </div>

  <div class="login-card">
    <h2>Bem-vindo de volta</h2>

    <?php if ($error): ?>
      <div class="alert" style="background:rgba(224,92,92,.12);border:1px solid rgba(224,92,92,.3);color:#e05c5c;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.865rem">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($lockMinutes > 0): ?>
      <div class="alert" style="background:rgba(224,92,92,.12);border:1px solid rgba(224,92,92,.3);color:#e05c5c;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:.865rem">
        🔒 <strong>IP Bloqueado.</strong> Tente novamente em <?= $lockMinutes ?> minuto(s).
      </div>
    <?php else: ?>
    <form method="POST" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email"
               placeholder="seu@email.com"
               value="<?= htmlspecialchars(sanitizeEmail($_POST['email'] ?? '')) ?>"
               maxlength="180" required autofocus>
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" maxlength="128" required>
      </div>

      <button type="submit">Entrar no sistema</button>
    </form>
    <?php endif; ?>

    <div class="login-footer">
      <p>🔒 Acesso restrito a membros autorizados.</p>
    </div>
  </div>

  <div class="login-footer" style="margin-top: 16px; border-top: none;">
    <p><?= htmlspecialchars(setting('sistema_nome', 'KM Tracker')) ?> v<?= APP_VERSION ?> &mdash; <?= date('Y') ?></p>
  </div>
</div>
</body>
</html>