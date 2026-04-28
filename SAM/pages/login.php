<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

session_start_secure();
if (auth_user()) { header('Location: /pages/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar — <?= APP_NAME ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background:#0F0F10; font-family:system-ui,-apple-system,sans-serif; margin:0; }
  .field { width:100%; padding:10px 14px; background:#252528; border:1px solid #2E2E33; border-radius:8px; color:#E8E8E6; font-size:14px; outline:none; box-sizing:border-box; }
  .field:focus { border-color:#3483FA; }
  .field::placeholder { color:#5E5E5A; }
  .btn-login { width:100%; padding:13px; background:#3483FA; color:#fff; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; }
  .btn-login:hover { background:#2970d6; }
  .btn-login:disabled { opacity:0.6; cursor:not-allowed; }
  .error-box { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:8px; padding:10px 14px; color:#ef4444; font-size:13px; margin-bottom:14px; display:none; }
  label { display:block; font-size:12px; color:#9A9A96; margin-bottom:6px; }
</style>
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px">

<div style="width:100%;max-width:360px">

  <!-- Logo -->
  <div style="text-align:center;margin-bottom:28px">
    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;margin-bottom:6px">
      <img src="/assets/logo.png" alt="SAM" style="width:65%;max-width:180px;height:auto;object-fit:contain;margin:0 auto;display:block">
      <span style="font-size:20px;font-weight:700;color:#E8E8E6;letter-spacing:-0.5px"><?= APP_NAME ?></span>
    </div>
    <p style="font-size:12px;color:#5E5E5A;margin:0">Sistema de gestão Mercado Livre</p>
  </div>

  <!-- Card -->
  <div style="background:#1A1A1C;border:1px solid #2E2E33;border-radius:16px;padding:28px">

    <div class="error-box" id="error-box"></div>

    <div style="margin-bottom:16px">
      <label>E-mail</label>
      <input type="email" id="email" class="field" placeholder="seu@email.com.br" autocomplete="email" autofocus>
    </div>

    <div style="margin-bottom:20px">
      <label>Senha</label>
      <input type="password" id="password" class="field" placeholder="••••••••" autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
    </div>

    <button class="btn-login" id="btn-login" onclick="doLogin()">Entrar</button>


  </div>

  <p style="text-align:center;font-size:11px;color:#5E5E5A;margin-top:16px">Todos os acessos são auditados</p>
</div>

<script>
async function doLogin() {
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = document.getElementById('btn-login');
  const errorBox = document.getElementById('error-box');

  if (!email || !password) { showError('Preencha e-mail e senha'); return; }

  btn.disabled = true;
  btn.textContent = 'Entrando...';
  errorBox.style.display = 'none';

  try {
    const resp = await fetch('/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'login', email, password })
    });
    const data = await resp.json();
    if (data.success) {
      const redirect = new URLSearchParams(location.search).get('redirect') || '/pages/dashboard.php';
      location.href = redirect;
    } else {
      showError(data.error || 'Credenciais inválidas');
    }
  } catch(e) {
    showError('Erro de conexão. Tente novamente.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Entrar';
  }
}

function showError(msg) {
  const box = document.getElementById('error-box');
  box.textContent = msg;
  box.style.display = 'block';
}
</script>
</body>
</html>
