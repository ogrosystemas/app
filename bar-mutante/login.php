<?php
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/config/config.php';

// Função local para redirect seguro (imune ao SCRIPT_NAME do LiteSpeed)
function loginRedirect(string $page): never {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projRoot= rtrim(str_replace('\\', '/', __DIR__), '/');
    $base    = ($docRoot && strpos($projRoot, $docRoot) === 0)
               ? rtrim(substr($projRoot, strlen($docRoot)), '/') . '/'
               : '/';
    http_response_code(302);
    header('Location: ' . $scheme . '://' . $host . $base . $page);
    exit;
}
require_once __DIR__ . '/includes/DB.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Auth.php';

// Já logado → vai para o PDV
if (Auth::logado()) {
    loginRedirect('index.php');
    exit;
}

$erro = '';
$cfg  = [];
try {
    $rows = DB::all("SELECT chave,valor FROM configuracoes WHERE chave IN ('logo_login','nome_estabelecimento','tema','cor_primaria','cor_secundaria')");
    foreach ($rows as $r) $cfg[$r['chave']] = $r['valor'];
} catch (\Throwable $e) {
    // banco ainda não configurado
}

$nome_est  = $cfg['nome_estabelecimento'] ?? 'Bar System Pro';
$logo_url  = !empty($cfg['logo_login']) ? UPLOAD_URL . 'logos/' . $cfg['logo_login'] : '';
$tema      = $cfg['tema'] ?? 'dark';
$cor       = $cfg['cor_primaria'] ?? '#f59e0b';
$cor2      = $cfg['cor_secundaria'] ?? '#d97706';

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $res   = Auth::login($login, $senha);
    if ($res['ok']) {
        loginRedirect('index.php');
        exit;
    }
    $erro = $res['msg'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="<?= h($tema) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= h($nome_est) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --c1: <?= h($cor) ?>;
  --c2: <?= h($cor2) ?>;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Dark theme (padrão) ── */
[data-tema="dark"] {
  --bg:       #0d0f14;
  --surface:  #161a23;
  --card:     #1e2330;
  --border:   #2d3447;
  --text:     #f0f2f7;
  --muted:    #8892a4;
  --input-bg: #252b38;
}
/* ── Light theme ── */
[data-tema="light"] {
  --bg:       #f0f2f5;
  --surface:  #ffffff;
  --card:     #ffffff;
  --border:   #dee2e6;
  --text:     #1a1a2e;
  --muted:    #6c757d;
  --input-bg: #f8f9fa;
}

body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

/* Fundo animado */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background: radial-gradient(ellipse at 20% 50%, color-mix(in srgb, var(--c1) 8%, transparent), transparent 60%),
              radial-gradient(ellipse at 80% 20%, color-mix(in srgb, var(--c2) 5%, transparent), transparent 50%);
  pointer-events: none;
}

.login-wrap {
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
}

.login-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,.35);
}

.login-header {
  background: linear-gradient(135deg, var(--c1), var(--c2));
  padding: 2.5rem 2rem 2rem;
  text-align: center;
}

.login-logo {
  width: 80px;
  height: 80px;
  object-fit: contain;
  margin-bottom: .75rem;
  border-radius: 16px;
}

.login-logo-icon {
  width: 72px;
  height: 72px;
  background: rgba(0,0,0,.15);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto .75rem;
}

.login-title {
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: 1.4rem;
  color: #000;
  margin-bottom: .25rem;
}

.login-sub {
  font-size: .82rem;
  color: rgba(0,0,0,.55);
}

.login-body {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  font-size: .78rem;
  font-weight: 600;
  color: var(--muted);
  margin-bottom: .4rem;
  text-transform: uppercase;
  letter-spacing: .4px;
}

.input-wrap {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: .9rem;
  pointer-events: none;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  background: var(--input-bg);
  border: 1.5px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  padding: .75rem .875rem .75rem 2.5rem;
  font-size: .95rem;
  font-family: 'DM Sans', sans-serif;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}

input:focus {
  border-color: var(--c1);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--c1) 20%, transparent);
}

.eye-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  font-size: .9rem;
  padding: 4px;
}

.eye-btn:hover { color: var(--text); }

.btn-login {
  width: 100%;
  padding: .875rem;
  background: linear-gradient(135deg, var(--c1), var(--c2));
  color: #000;
  font-weight: 800;
  font-family: 'Syne', sans-serif;
  font-size: 1rem;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all .2s;
  letter-spacing: .3px;
  margin-top: .5rem;
}

.btn-login:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px color-mix(in srgb, var(--c1) 35%, transparent);
}

.btn-login:active { transform: translateY(0); }

.btn-login:disabled {
  opacity: .6;
  cursor: not-allowed;
  transform: none;
}

.erro-box {
  background: rgba(239,68,68,.12);
  border: 1px solid rgba(239,68,68,.3);
  color: #fca5a5;
  border-radius: 10px;
  padding: .75rem 1rem;
  font-size: .85rem;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}

.footer-note {
  text-align: center;
  font-size: .72rem;
  color: var(--muted);
  margin-top: 1.25rem;
}

/* Ícone SVG inline simples */
.icon { display: inline-block; width: 1em; height: 1em; vertical-align: middle; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-header">
      <?php if ($logo_url): ?>
        <img src="<?= h($logo_url) ?>" alt="Logo" class="login-logo">
      <?php else: ?>
        <div class="login-logo-icon">🍺</div>
      <?php endif; ?>
      <div class="login-title"><?= h($nome_est) ?></div>
      <div class="login-sub">Sistema de Gestão de Bar</div>
    </div>

    <div class="login-body">
      <?php if ($erro): ?>
      <div class="erro-box">
        <svg viewBox="0 0 20 20" fill="currentColor" class="icon" style="width:16px;height:16px;flex-shrink:0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <?= h($erro) ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" autocomplete="off">
        <div class="form-group">
          <label for="login">Login</label>
          <div class="input-wrap">
            <svg viewBox="0 0 20 20" fill="currentColor" class="icon input-icon" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
            <input type="text" id="login" name="login" placeholder="Seu login"
                   value="<?= h($_POST['login'] ?? '') ?>" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label for="senha">Senha</label>
          <div class="input-wrap">
            <svg viewBox="0 0 20 20" fill="currentColor" class="icon input-icon" style="width:16px;height:16px"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <input type="password" id="senha" name="senha" placeholder="••••••••" required>
            <button type="button" class="eye-btn" onclick="toggleSenha()" title="Mostrar/ocultar">
              <svg id="eyeIcon" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
          Entrar no Sistema
        </button>
      </form>

      <div class="footer-note">Bar System Pro v<?= SISTEMA_VERSAO ?></div>
    </div>
  </div>
</div>

<script>
function toggleSenha() {
  const input = document.getElementById('senha');
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.innerHTML = '<path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>';
  } else {
    input.type = 'password';
    icon.innerHTML = '<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>';
  }
}

document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnLogin');
  btn.disabled = true;
  btn.textContent = 'Entrando...';
});
</script>
</body>
</html>
