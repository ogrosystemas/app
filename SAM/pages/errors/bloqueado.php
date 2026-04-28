<?php
require_once dirname(__DIR__, 2) . '/config.php';
$reason = $_GET['reason'] ?? 'MISSING';
$msgs = [
    'EXPIRED' => ['título' => 'Trial expirado', 'desc' => 'Seu período de 15 dias gratuitos encerrou.', 'icon' => 'clock', 'color' => '#f59e0b'],
    'BLOCKED' => ['título' => 'Acesso bloqueado', 'desc' => 'Sua conta foi bloqueada. Entre em contato com o suporte.', 'icon' => 'ban', 'color' => '#ef4444'],
    'MISSING' => ['título' => 'Sistema bloqueado', 'desc' => 'Licença não configurada.', 'icon' => 'lock', 'color' => '#ef4444'],
];
$m = $msgs[$reason] ?? $msgs['MISSING'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Acesso restrito — <?= APP_NAME ?></title>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:#0F0F10; color:#E8E8E6; font-family:system-ui,-apple-system,sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
</style>
</head>
<body>
<div style="width:100%;max-width:420px;text-align:center">

  <!-- Logo -->
  <img src="/assets/logo.png" alt="Ogro" style="width:56px;height:56px;object-fit:contain;margin:0 auto 20px;display:block;opacity:.8">

  <!-- Ícone de status -->
  <div style="width:72px;height:72px;border-radius:50%;background:<?= $m['color'] ?>18;border:1px solid <?= $m['color'] ?>40;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
    <i data-lucide="<?= $m['icon'] ?>" style="width:28px;height:28px;color:<?= $m['color'] ?>"></i>
  </div>

  <h1 style="font-size:22px;font-weight:700;margin-bottom:8px"><?= $m['título'] ?></h1>
  <p style="font-size:14px;color:#9A9A96;margin-bottom:24px;line-height:1.6"><?= $m['desc'] ?></p>

  <?php if ($reason === 'EXPIRED'): ?>
  <!-- Card de ativação inline -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:14px;padding:20px;margin-bottom:20px;text-align:left">
    <div style="font-size:13px;font-weight:600;color:#E8E8E6;margin-bottom:4px;display:flex;align-items:center;gap:7px">
      <i data-lucide="key" style="width:14px;height:14px;color:#FFE600"></i>
      Ativar licença
    </div>
    <p style="font-size:12px;color:#5E5E5A;margin-bottom:14px">Insira sua chave para continuar usando o SAM</p>
    <input type="text" id="lic-input" placeholder="XXXX-XXXX-XXXX-XXXX"
      style="width:100%;padding:11px 14px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;font-family:monospace;letter-spacing:1px;margin-bottom:10px"
      oninput="let v=this.value.replace(/[^A-Za-z0-9]/g,'').toUpperCase();this.value=(v.match(/.{1,4}/g)||[]).join('-').substring(0,39)">
    <button onclick="activateNow()"
      style="width:100%;padding:12px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer">
      Ativar agora
    </button>
    <div id="lic-msg" style="margin-top:8px;font-size:12px;text-align:center;display:none"></div>
  </div>
  <?php endif; ?>

  <div style="display:flex;flex-direction:column;gap:10px">
    <?php if ($reason !== 'BLOCKED'): ?>
    <a href="/pages/config_ml.php#licenca" style="display:block;padding:12px;background:#3483FA;color:#fff;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none">
      Inserir chave de ativação
    </a>
    <?php endif; ?>
    <a href="/api/auth.php?action=logout" style="display:block;padding:12px;background:#252528;border:0.5px solid #2E2E33;color:#9A9A96;border-radius:8px;font-size:13px;text-decoration:none">
      Sair da conta
    </a>
    <p style="font-size:11px;color:#5E5E5A">
      Suporte: <a href="mailto:contato@ogrosystemas.com.br" style="color:#3483FA">contato@ogrosystemas.com.br</a>
    </p>
  </div>

  <p style="font-size:10px;color:#3E3E45;margin-top:24px">Código: <?= htmlspecialchars($reason) ?></p>
</div>

<script>
lucide.createIcons();

async function activateNow() {
  const key = document.getElementById('lic-input').value.trim();
  const msg = document.getElementById('lic-msg');
  if (!key) return;
  const fd = new FormData(); fd.append('license_key', key);
  try {
    const r = await fetch('/api/activate_license.php', {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) {
      msg.textContent = '✓ Ativado! Redirecionando...';
      msg.style.cssText = 'margin-top:8px;font-size:12px;text-align:center;display:block;color:#22c55e';
      setTimeout(()=>location.href='/pages/dashboard.php', 1500);
    } else {
      msg.textContent = '✗ ' + d.error;
      msg.style.cssText = 'margin-top:8px;font-size:12px;text-align:center;display:block;color:#ef4444';
    }
  } catch(e) {
    msg.textContent = '✗ Erro de conexão.';
    msg.style.cssText = 'margin-top:8px;font-size:12px;text-align:center;display:block;color:#ef4444';
  }
}
</script>
</body>
</html>
