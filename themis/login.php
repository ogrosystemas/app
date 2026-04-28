<?php
define('THEMIS_ROOT', __DIR__);
$_cfg = file_exists(THEMIS_ROOT . '/_app/config/app.php') ? require THEMIS_ROOT . '/_app/config/app.php' : [];
$_appUrl = rtrim($_cfg['app']['url'] ?? '', '/');
$_appName = $_cfg['app']['name'] ?? 'Themis Enterprise';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Themis Enterprise — Acesso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg:     #0b0e18;
    --sf:     #111827;
    --el:     #1a2235;
    --br:     #243047;
    --t1:     #e8edf5;
    --t2:     #8b95a9;
    --t3:     #4a556b;
    --blue:   #3b82f6;
    --blue2:  #1d4ed8;
    --teal:   #14b8a6;
    --rose:   #f43f5e;
    --glow:   rgba(59,130,246,.18);
    --font:   'DM Sans', sans-serif;
    --mono:   'JetBrains Mono', monospace;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--t1);
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    -webkit-font-smoothing: antialiased;
  }

  /* ── Left panel — decorativo ────────────────────── */
  .left {
    flex: 1;
    background: linear-gradient(145deg, #0d1a35 0%, #0a1628 40%, #061022 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 48px;
    position: relative;
    overflow: hidden;
  }
  .left::before {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,.12) 0%, transparent 65%);
    top: -100px; left: -100px;
    pointer-events: none;
  }
  .left::after {
    content: '';
    position: absolute;
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(20,184,166,.08) 0%, transparent 65%);
    bottom: -80px; right: -60px;
    pointer-events: none;
  }

  .left-content { position: relative; z-index: 1; max-width: 420px; }

  .brand-logo {
    margin-bottom: 36px;
  }
  .brand-logo img {
    height: 68px;
    width: auto;
    display: block;
    /* fallback se logo não existir ainda */
    filter: drop-shadow(0 4px 20px rgba(59,130,246,.35));
  }
  .brand-logo-fallback {
    display: none; /* escondido se a img carregar */
    align-items: center;
    gap: 14px;
  }
  .brand-logo img.error + .brand-logo-fallback { display: flex; }

  .logo-mark {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, var(--blue), var(--teal));
    border-radius: 14px;
    display: grid; place-items: center;
    font-size: 22px; font-weight: 800; color: #fff;
    letter-spacing: -1px;
    box-shadow: 0 4px 24px rgba(59,130,246,.4);
  }
  .logo-text-brand strong { font-size: 22px; font-weight: 800; display: block; }
  .logo-text-brand small  { font-size: 12px; color: var(--t2); font-weight: 400; }

  .left-tagline {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.25;
    letter-spacing: -.03em;
    color: #fff;
    margin-bottom: 16px;
  }
  .left-tagline span { color: var(--blue); }

  .left-desc {
    font-size: 14px;
    color: var(--t2);
    line-height: 1.65;
    margin-bottom: 40px;
  }

  .feature-list { display: flex; flex-direction: column; gap: 12px; }
  .feature {
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: var(--t2);
  }
  .feature-icon {
    width: 32px; height: 32px;
    background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.2);
    border-radius: 8px;
    display: grid; place-items: center;
    font-size: 14px;
    flex-shrink: 0;
  }

  /* ── Right panel — form ─────────────────────────── */
  .right {
    width: 460px;
    flex-shrink: 0;
    background: var(--sf);
    border-left: 1px solid var(--br);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 48px;
  }

  .form-card { width: 100%; max-width: 360px; }

  .form-header { margin-bottom: 32px; }
  .form-header h2 { font-size: 22px; font-weight: 700; letter-spacing: -.02em; }
  .form-header p  { font-size: 13px; color: var(--t2); margin-top: 5px; }

  .fg { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
  .flabel {
    font-size: 11.5px; font-weight: 600; color: var(--t2);
    text-transform: uppercase; letter-spacing: .06em;
  }
  .finput {
    background: var(--el);
    border: 1px solid var(--br);
    border-radius: 9px;
    padding: 11px 14px;
    font-size: 14px; color: var(--t1);
    font-family: var(--font);
    outline: none;
    transition: border-color .18s, box-shadow .18s;
    width: 100%;
  }
  .finput:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--glow);
  }
  .finput::placeholder { color: var(--t3); }

  .pass-wrap { position: relative; }
  .pass-wrap .finput { padding-right: 44px; }
  .pass-toggle {
    position: absolute; right: 13px; top: 50%;
    transform: translateY(-50%);
    cursor: pointer; color: var(--t3);
    font-size: 16px; user-select: none;
    transition: color .15s;
  }
  .pass-toggle:hover { color: var(--t2); }

  .totp-row {
    display: none; /* aparece via JS quando 2FA ativo */
  }

  .btn-login {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, var(--blue), var(--blue2));
    color: #fff; border: none;
    border-radius: 9px;
    font-size: 14.5px; font-weight: 700;
    font-family: var(--font);
    cursor: pointer;
    margin-top: 8px;
    transition: all .2s;
    box-shadow: 0 3px 16px rgba(59,130,246,.35);
    position: relative; overflow: hidden;
  }
  .btn-login:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(59,130,246,.45);
  }
  .btn-login:active { transform: translateY(0); }
  .btn-login .spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2.5px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .alert-box {
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    display: none;
    align-items: center;
    gap: 8px;
    border: 1px solid;
  }
  .alert-err  { background: rgba(244,63,94,.08); border-color: rgba(244,63,94,.25); color: #fb7185; }
  .alert-ok   { background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.25); color: #34d399; }

  .form-footer {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid var(--br);
    text-align: center;
    font-size: 12px;
    color: var(--t3);
  }
  .form-footer a { color: var(--blue); text-decoration: none; }
  .form-footer a:hover { text-decoration: underline; }

  .version-tag {
    font-family: var(--mono);
    font-size: 10px;
    color: var(--t3);
    text-align: center;
    margin-top: 24px;
  }

  /* ── Animações de entrada ────────────────────────── */
  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:none; } }
  .anim { animation: fadeUp .45s cubic-bezier(.4,0,.2,1) both; }
  .d1 { animation-delay:.05s } .d2 { animation-delay:.12s }
  .d3 { animation-delay:.19s } .d4 { animation-delay:.26s }

  /* ── Responsive ──────────────────────────────────── */
  @media (max-width: 860px) {
    body { flex-direction: column; }
    .left { padding: 40px 28px 32px; min-height: 260px; flex: none; }
    .left-tagline { font-size: 22px; }
    .feature-list { display: none; }
    .right { width: 100%; padding: 32px 24px 48px; border-left: none; border-top: 1px solid var(--br); }
  }
</style>
</head>
<body>

<!-- PAINEL ESQUERDO -->
<div class="left">
  <div class="left-content">

    <!-- Logo: usa themis_logo.png; fallback textual se arquivo ainda não foi colocado -->
    <div class="brand-logo">
      <img
        src="/assets/img/themis_logo.png"
        alt="Themis Enterprise"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
      >
      <div class="brand-logo-fallback">
        <div class="logo-mark">⚖</div>
        <div class="logo-text-brand">
          <strong>Themis</strong>
          <small>Enterprise Legal</small>
        </div>
      </div>
    </div>

    <div class="left-tagline">
      Gestão jurídica<br>
      de <span>alto desempenho</span>
    </div>

    <p class="left-desc">
      Plataforma enterprise para advocacia, perícia judicial e gestão processual.
      Segura, rápida e projetada para escritórios que não toleram falhas.
    </p>

    <div class="feature-list">
      <div class="feature">
        <div class="feature-icon">⚖</div>
        <span>Matter Management com workflow automático de status</span>
      </div>
      <div class="feature">
        <div class="feature-icon">🔬</div>
        <span>Motor pericial: IBUTG, Parecer Divergente, Laudo Adverso</span>
      </div>
      <div class="feature">
        <div class="feature-icon">🧮</div>
        <span>Cálculos SELIC/IPCA-E (Lei 14.905/2024) com memória</span>
      </div>
      <div class="feature">
        <div class="feature-icon">🛰</div>
        <span>Radar DataJud/CNJ com gatilho de alvará automático</span>
      </div>
      <div class="feature">
        <div class="feature-icon">🔐</div>
        <span>JWT + 2FA TOTP · Silo financeiro por sócio · Audit Trail</span>
      </div>
    </div>

  </div>
</div>

<!-- PAINEL DIREITO: FORMULÁRIO -->
<div class="right">
  <div class="form-card">

    <div class="form-header anim d1">
      <h2>Acessar o sistema</h2>
      <p>Entre com suas credenciais para continuar</p>
    </div>

    <div class="alert-box alert-err" id="alertErr">
      <span>⚠</span><span id="alertErrMsg">Credenciais inválidas.</span>
    </div>
    <div class="alert-box alert-ok" id="alertOk">
      <span>✓</span><span>Autenticado! Redirecionando…</span>
    </div>

    <form id="loginForm" novalidate>

      <div class="fg anim d2">
        <label class="flabel" for="email">E-mail</label>
        <input
          class="finput" type="email" id="email" name="email"
          placeholder="seu@email.com.br"
          autocomplete="email" required
        >
      </div>

      <div class="fg anim d3">
        <label class="flabel" for="password">Senha</label>
        <div class="pass-wrap">
          <input
            class="finput" type="password" id="password" name="password"
            placeholder="••••••••"
            autocomplete="current-password" required
          >
          <span class="pass-toggle" id="passToggle" title="Mostrar/ocultar senha">👁</span>
        </div>
      </div>

      <div class="fg totp-row anim d3" id="totpRow">
        <label class="flabel" for="totp">Código 2FA (6 dígitos)</label>
        <input
          class="finput" type="text" id="totp" name="totp"
          placeholder="000000" maxlength="6"
          inputmode="numeric" autocomplete="one-time-code"
          style="font-family:var(--mono);letter-spacing:.2em;font-size:18px;text-align:center"
        >
      </div>

      <button type="submit" class="btn-login anim d4" id="btnLogin">
        <span id="btnText">Entrar no Themis</span>
        <div class="spinner" id="btnSpinner"></div>
      </button>

    </form>

    <div class="form-footer anim d4">
      Portal do cliente? <a href="<?= $_appUrl ?>/portal">Clique aqui</a>
    </div>

    <div class="version-tag">Themis Enterprise v2.0 · PHP 8.3</div>

  </div>
</div>

<script>
// ── Toggle senha ─────────────────────────────────────────
document.getElementById('passToggle').addEventListener('click', function() {
  const inp = document.getElementById('password');
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  this.textContent = show ? '🙈' : '👁';
});

// ── Submit login ─────────────────────────────────────────
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const totp     = document.getElementById('totp').value.trim();
  const btn      = document.getElementById('btnLogin');
  const btnText  = document.getElementById('btnText');
  const spinner  = document.getElementById('btnSpinner');
  const errBox   = document.getElementById('alertErr');
  const okBox    = document.getElementById('alertOk');

  if (!email || !password) {
    showErr('Preencha e-mail e senha.');
    return;
  }

  // Loading state
  btn.disabled  = true;
  btnText.style.display  = 'none';
  spinner.style.display  = 'block';
  errBox.style.display   = 'none';
  okBox.style.display    = 'none';

  try {
    const API_BASE = '<?= $_appUrl ?>';

    // Detecta se o rewrite funciona; se não, usa api.php como fallback
    async function apiCall(path, opts = {}) {
      // Tenta primeiro com rewrite normal
      try {
        const r = await fetch(API_BASE + '/api' + path, opts);
        // Se retornou HTML (rewrite não funcionou), tenta fallback
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('json') && r.status === 404) throw new Error('no_rewrite');
        return r;
      } catch(e) {
        if (e.message === 'no_rewrite' || e.message === 'Failed to fetch') {
          // Fallback: usa api.php diretamente
          return fetch(API_BASE + '/api.php?r=' + path, opts);
        }
        throw e;
      }
    }

    const res  = await apiCall('/auth/login', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ email, password, totp: totp || undefined }),
    });
    const data = await res.json();

    if (!res.ok) {
      // Se precisa de 2FA
      if (data.message && data.message.toLowerCase().includes('2fa')) {
        document.getElementById('totpRow').style.display = 'flex';
        document.getElementById('totp').focus();
        showErr('Digite o código do seu aplicativo autenticador.');
      } else {
        showErr(data.message || 'Credenciais inválidas.');
      }
      return;
    }

    // Sucesso: armazena token e redireciona
    if (data.data?.token) {
      localStorage.setItem('themis_token', data.data.token);
      localStorage.setItem('themis_user',  JSON.stringify(data.data.user));
      // Cookie httpOnly-like (fallback para APIs que usam cookie)
      document.cookie = `themis_token=${data.data.token}; path=/; SameSite=Strict; Secure`;
    }

    okBox.style.display = 'flex';
    setTimeout(() => {
      const perfil = data.data?.user?.perfil;
      window.location.href = perfil === 'cliente' ? '<?= $_appUrl ?>/portal' : '<?= $_appUrl ?>/app';
    }, 800);

  } catch (err) {
    showErr('Erro de conexão com o servidor.');
  } finally {
    btn.disabled          = false;
    btnText.style.display = 'block';
    spinner.style.display = 'none';
  }
});

function showErr(msg) {
  const box = document.getElementById('alertErr');
  document.getElementById('alertErrMsg').textContent = msg;
  box.style.display = 'flex';
  box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Se já tem token válido, redireciona direto ───────────
(async function() {
  const token = localStorage.getItem('themis_token');
  if (!token) return;
  const base = API_BASE;
  const hdrs = { 'Authorization': 'Bearer ' + token };
  let user = null;
  try {
    const r = await fetch(base + '/api/auth/me', { headers: hdrs });
    if (r.ok) user = (await r.json()).data;
  } catch(e) {}
  // Fallback sem rewrite
  if (!user) {
    try {
      const r = await fetch(base + '/api.php?r=/auth/me', { headers: hdrs });
      if (r.ok) user = (await r.json()).data;
    } catch(e) {}
  }
  if (user) {
    window.location.href = user.perfil === 'cliente' ? base + '/portal' : base + '/app';
  }
})();
</script>
</body>
</html>
