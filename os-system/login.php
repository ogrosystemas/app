<?php
session_start();
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    header('Location: index.php'); exit;
}
require_once 'config/config.php';
$erro = '';

// Load system config for logo and name
$_sistemaConfig = [];
$_cfgPath = __DIR__ . '/config/sistema.php';
if (file_exists($_cfgPath)) {
    $_sistemaConfig = include $_cfgPath;
    if (!is_array($_sistemaConfig)) $_sistemaConfig = [];
}
$_sistemaConfig = array_merge(['nome_sistema' => 'OS-System', 'logo_path' => '', 'cor_primaria' => '#f59e0b'], $_sistemaConfig);
$_loginNome = $_sistemaConfig['nome_sistema'];
$_loginLogo = $_sistemaConfig['logo_path'];
$_loginCor  = $_sistemaConfig['cor_primaria'];

// ── Proteção contra brute force (rate limiting no login) ──────────────────
$_login_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$_attempts  = (int)($_SESSION[$_login_key . '_count'] ?? 0);
$_last_time = (int)($_SESSION[$_login_key . '_time']  ?? 0);

// Reset contador após 15 minutos
if (time() - $_last_time > 900) {
    $_attempts = 0;
    $_SESSION[$_login_key . '_count'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_attempts >= 5) {
        $erro = 'Muitas tentativas. Aguarde ' . max(1, (int)((900 - (time() - $_last_time)) / 60)) . ' minuto(s).';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($auth->login($_POST['email'], $_POST['senha'])) {
        header('Location: index.php'); exit;
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}
$tema = $_COOKIE['os_tema'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="<?= htmlspecialchars($tema) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — OS-System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="assets/css/os-theme.css">
<style>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  position: relative;
  overflow: hidden;
}
.login-page::before {
  content: '';
  position: absolute;
  top: -40%; left: -20%;
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(245,158,11,.12) 0%, transparent 70%);
  pointer-events: none;
}
.login-page::after {
  content: '';
  position: absolute;
  bottom: -30%; right: -10%;
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(245,158,11,.07) 0%, transparent 70%);
  pointer-events: none;
}
.login-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 20px;
  box-shadow: var(--shadow);
  padding: 48px 44px;
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
}
.login-brand {
  text-align: center;
  margin-bottom: 36px;
}
.login-brand-icon {
  width: 64px; height: 64px;
  background: linear-gradient(135deg, var(--accent), var(--accent-dark));
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  color: #000;
  margin-bottom: 16px;
}
.login-brand h1 {
  font-family: 'Syne', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 4px;
}
.login-brand p { color: var(--text-muted); font-size: .875rem; }

.login-input-group { margin-bottom: 16px; }
.login-input-group label {
  display: block;
  font-size: .75rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 6px;
}
.login-input {
  width: 100%;
  background: var(--bg-input);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 16px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: .9rem;
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.login-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(245,158,11,.15);
}
.login-input::placeholder { color: var(--text-dim); }

.btn-login-submit {
  width: 100%;
  padding: 13px;
  background: var(--accent);
  border: none;
  border-radius: 10px;
  color: #000;
  font-family: 'Syne', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s;
  margin-top: 8px;
}
.btn-login-submit:hover {
  background: var(--accent-dark);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(245,158,11,.35);
}

.btn-toggle-tema {
  position: fixed;
  top: 20px; right: 20px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 50px;
  padding: 8px 16px;
  color: var(--text-muted);
  font-family: 'DM Sans', sans-serif;
  font-size: .8rem;
  cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  transition: all .2s;
  z-index: 100;
}
.btn-toggle-tema:hover { color: var(--accent); border-color: var(--accent); }

.login-error {
  background: rgba(239,68,68,.1);
  border: 1px solid rgba(239,68,68,.2);
  color: #ef4444;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: .85rem;
  margin-bottom: 18px;
  display: flex; align-items: center; gap: 8px;
}
.login-footer {
  text-align: center;
  margin-top: 28px;
  font-size: .75rem;
  color: var(--text-dim);
}
  :root { --accent: <?= htmlspecialchars($_loginCor) ?>; }
</style>
</head>
<body>

<button class="btn-toggle-tema" onclick="toggleTema()">
  <i class="ph-bold" id="tema-icon"></i>
  <span id="tema-label"></span>
</button>

<div class="login-page">
  <div class="login-card">
    <div class="login-brand">
      <?php if (!empty($_loginLogo)): ?>
        <img src="<?= htmlspecialchars($_loginLogo) ?>" alt="Logo"
             style="max-height:70px;max-width:200px;object-fit:contain;margin-bottom:8px">
      <?php else: ?>
      <div class="login-brand-icon">
        <i class="ph-bold ph-motorcycle"></i>
      </div>
      <?php endif; ?>
      <h1><?= htmlspecialchars($_loginNome) ?></h1>
      <p>Gestão de Oficina Mecânica</p>
    </div>

    <?php if ($erro): ?>
    <div class="login-error">
      <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="login-input-group">
        <label>E-mail</label>
        <input type="email" name="email" class="login-input" placeholder="seu@email.com" required autofocus>
      </div>
      <div class="login-input-group">
        <label>Senha</label>
        <input type="password" name="senha" class="login-input" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login-submit">
        Entrar no Sistema
      </button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> OS-System — Todos os direitos reservados
    </div>
  </div>
</div>

<script>
(function(){
  var t = document.documentElement.getAttribute('data-theme');
  var icon = document.getElementById('tema-icon');
  var label = document.getElementById('tema-label');
  icon.className = 'ph-bold ' + (t === 'dark' ? 'ph-sun' : 'ph-moon-stars');
  label.textContent = t === 'dark' ? 'Tema Claro' : 'Tema Escuro';
})();

function toggleTema() {
  var html = document.documentElement;
  var novo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', novo);
  document.cookie = 'os_tema=' + novo + ';path=/;max-age=' + (365*24*3600);
  var icon = document.getElementById('tema-icon');
  var label = document.getElementById('tema-label');
  icon.className = 'ph-bold ' + (novo === 'dark' ? 'ph-sun' : 'ph-moon-stars');
  label.textContent = novo === 'dark' ? 'Tema Claro' : 'Tema Escuro';
}
</script>
</body>
</html>
