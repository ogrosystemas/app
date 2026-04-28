<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/licenca.php';

// Se ainda estiver válida, redirecionar para home
$status = licenca_status($db);
if ($status['status'] !== 'expirado') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$dominio = licenca_dominio();
?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Licença Expirada — OS-System</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/os-theme.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; background:var(--bg); }
    .bloqueio-wrap {
      text-align:center; padding:48px 40px; max-width:480px; width:90%;
      background:var(--bg-card); border:1px solid var(--border); border-radius:20px;
      box-shadow:0 24px 60px rgba(0,0,0,.4);
    }
    .bloqueio-icon {
      width:72px; height:72px; border-radius:50%;
      background:rgba(239,68,68,.12); border:2px solid rgba(239,68,68,.25);
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 24px; font-size:2rem; color:#ef4444;
    }
    .bloqueio-titulo { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; color:var(--text); margin-bottom:8px; }
    .bloqueio-sub { color:var(--text-muted); font-size:.92rem; margin-bottom:28px; line-height:1.6; }
    .bloqueio-dominio {
      display:inline-block; background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.25);
      color:var(--accent); border-radius:8px; padding:6px 16px; font-size:.85rem;
      font-family:monospace; margin-bottom:28px; letter-spacing:.04em;
    }
    .bloqueio-form { display:flex; flex-direction:column; gap:12px; }
    .bloqueio-form input {
      background:var(--bg-muted); border:1px solid var(--border); color:var(--text);
      border-radius:10px; padding:12px 16px; font-size:.9rem; font-family:monospace;
      text-align:center; letter-spacing:.08em; text-transform:uppercase;
      outline:none; transition:border-color .2s;
    }
    .bloqueio-form input:focus { border-color:var(--accent); }
    .bloqueio-form button {
      background:var(--accent); color:#000; font-weight:700; font-size:.9rem;
      border:none; border-radius:10px; padding:13px; cursor:pointer;
      font-family:'Syne',sans-serif; transition:opacity .15s;
    }
    .bloqueio-form button:hover { opacity:.85; }
    .bloqueio-erro { color:#ef4444; font-size:.82rem; margin-top:4px; }
    .bloqueio-config { margin-top:20px; }
    .bloqueio-config a { color:var(--text-muted); font-size:.8rem; text-decoration:none; }
    .bloqueio-config a:hover { color:var(--accent); }
  </style>
</head>
<body>
<div class="bloqueio-wrap">
  <div class="bloqueio-icon"><i class="ph-bold ph-lock"></i></div>
  <div class="bloqueio-titulo">Licença Expirada</div>
  <p class="bloqueio-sub">
    O período de uso deste sistema foi encerrado.<br>
    Insira uma chave de licença válida para continuar.
  </p>

  <div class="bloqueio-dominio"><i class="ph-bold ph-globe"></i> <?= htmlspecialchars($dominio) ?></div>

  <?php if (!empty($_SESSION['licenca_erro'])): ?>
  <div class="bloqueio-erro" style="margin-bottom:12px">
    <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($_SESSION['licenca_erro']) ?>
  </div>
  <?php unset($_SESSION['licenca_erro']); endif; ?>

  <form method="POST" action="<?= BASE_URL ?>/modules/configuracoes/ativar_licenca.php" class="bloqueio-form">
    <?= csrfField() ?>
    <input type="text" name="chave_licenca" placeholder="OSSYS-XXXXXXXX-XXXX-XXXX-XXXX-XXXX"
           maxlength="34" required autocomplete="off" spellcheck="false">
    <button type="submit"><i class="ph-bold ph-key"></i> Ativar Licença</button>
  </form>

  <div class="bloqueio-config">
    <a href="<?= BASE_URL ?>/modules/configuracoes/configuracoes.php">
      <i class="ph-bold ph-gear"></i> Acessar configurações
    </a>
  </div>
</div>
</body>
</html>
