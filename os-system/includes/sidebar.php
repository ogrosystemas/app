<?php
if (!isset($permissao)) $permissao = null;

// Carregar configurações do sistema
$_sistemaConfig = [];
$_sistemaConfigFile = defined('__DIR__') ? dirname(__DIR__, 2) . '/config/sistema.php' : '';
// Try relative path based on sidebar location
$_cfgPath = __DIR__ . '/../config/sistema.php';
if (file_exists($_cfgPath)) {
    $_sistemaConfig = include $_cfgPath;
    if (!is_array($_sistemaConfig)) $_sistemaConfig = [];
}
$_sistemaConfig = array_merge([
    'nome_sistema' => 'OS-System',
    'logo_path'    => '',
    'cor_primaria' => '#f59e0b',
], $_sistemaConfig);
$usuario = $auth->getCurrentUser();
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$tema    = $_COOKIE['os_tema'] ?? 'dark';
$current = basename($_SERVER['PHP_SELF']);

$iniciais = '';
if ($usuario && !empty($usuario['nome'])) {
    $p = explode(' ', $usuario['nome']);
    $iniciais = strtoupper(substr($p[0],0,1)) . (count($p)>1 ? strtoupper(substr(end($p),0,1)) : '');
}
?>
<!DOCTYPE html>
<html lang="pt-br" data-theme="<?= htmlspecialchars($tema) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OS-System</title>
  <meta name="description" content="OS-System — Gestão de Oficina">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="dns-prefetch" href="https://unpkg.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/os-theme.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
  /* Dynamic accent from config */
  :root { --accent: <?= htmlspecialchars($_sistemaConfig['cor_primaria'] ?? '#f59e0b') ?>; }
  </style>
  <style>
  /* ── Top Navbar ───────────────────────────────────── */
  .os-navbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: var(--sidebar-bg);
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    align-items: center;
    padding: 0 20px;
    gap: 4px;
    z-index: 1000;
    font-family: var(--font-body);
  }

  /* Brand */
  .nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-right: 16px;
    text-decoration: none;
    flex-shrink: 0;
  }
  .nav-brand-icon {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; color: #000;
  }
  .nav-brand-name {
    font-family: var(--font-display);
    font-size: .95rem;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
  }

  /* Nav items */
  .nav-item-top {
    position: relative;
  }
  .nav-link-top {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 7px;
    color: rgba(255,255,255,.65);
    font-size: .83rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: all .15s;
    border: none;
    background: none;
    font-family: var(--font-body);
  }
  .nav-link-top:hover,
  .nav-link-top.active {
    background: rgba(245,158,11,.15);
    color: var(--accent);
  }
  .nav-link-top i { font-size: .95rem; }
  .nav-link-top .chevron { font-size: .65rem; margin-left: 2px; opacity: .6; }

  /* Dropdown */
  .nav-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    background: var(--sidebar-bg);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px;
    padding: 6px;
    min-width: 200px;
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    display: none;
    z-index: 2000;
  }
  .nav-dropdown.open {
    display: block;
    animation: dropIn .12s ease;
  }
  @keyframes dropIn {
    from { opacity:0; transform:translateY(-4px); }
    to   { opacity:1; transform:translateY(0); }
  }
  .nav-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 7px;
    color: rgba(255,255,255,.7);
    text-decoration: none;
    font-size: .83rem;
    font-weight: 500;
    transition: all .15s;
    white-space: nowrap;
  }
  .nav-dropdown a:hover {
    background: rgba(245,158,11,.12);
    color: var(--accent);
  }
  .nav-dropdown a.active {
    color: var(--accent);
    font-weight: 600;
  }
  .nav-dropdown a i { font-size: .9rem; width: 18px; text-align: center; }
  .nav-dropdown-divider {
    height: 1px;
    background: rgba(255,255,255,.07);
    margin: 4px 0;
  }

  /* Right side */
  .nav-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
  }
  .nav-user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px;
    border-radius: 8px;
    cursor: default;
  }
  .nav-avatar {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--accent), var(--accent-dark));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700; color: #000;
    flex-shrink: 0;
  }
  .nav-user-name {
    font-size: .8rem;
    font-weight: 600;
    color: rgba(255,255,255,.8);
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .nav-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 7px;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,.1);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.7);
    font-family: var(--font-body);
    white-space: nowrap;
  }
  .nav-btn:hover { background: rgba(245,158,11,.15); color: var(--accent); border-color: rgba(245,158,11,.3); }
  .nav-btn.danger  { border-color: rgba(239,68,68,.2); color: #f87171; }
  .nav-btn.danger:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.35); color: #fca5a5; }

  /* Main content offset */
  .os-main {
    margin-left: 0 !important;
    padding-top: 56px;
  }
  .os-topbar {
    top: 56px;
  }
  .os-layout { display: block; }

  /* ── Hamburger / Mobile ─────────────────────────────── */
  .nav-hamburger {
    display: none;
    align-items: center;
    justify-content: center;
    width: 38px; height: 38px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px;
    cursor: pointer;
    color: rgba(255,255,255,.8);
    font-size: 1.2rem;
    margin-left: auto;
    flex-shrink: 0;
  }
  .nav-hamburger:hover { background: rgba(245,158,11,.15); color: var(--accent); }

  /* Mobile drawer */
  .nav-drawer {
    position: fixed;
    top: 0; left: 0;
    width: 280px; height: 100vh;
    background: var(--sidebar-bg, #1a2035);
    z-index: 2000;
    transform: translateX(-100%);
    transition: transform .25s cubic-bezier(.4,0,.2,1);
    overflow-y: auto;
    padding: 0 0 24px;
    box-shadow: 4px 0 24px rgba(0,0,0,.4);
  }
  .nav-drawer.open { transform: translateX(0); }

  .nav-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1999;
    display: none;
    backdrop-filter: blur(2px);
  }
  .nav-overlay.open { display: block; }

  /* Drawer header */
  .nav-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    margin-bottom: 8px;
  }
  .nav-drawer-close {
    width: 32px; height: 32px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 6px;
    cursor: pointer;
    color: rgba(255,255,255,.7);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
  }

  /* Drawer menu items */
  .nav-drawer a,
  .nav-drawer-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 18px;
    color: rgba(255,255,255,.7);
    text-decoration: none;
    font-size: .875rem;
    font-weight: 500;
    font-family: var(--font-body);
    transition: all .15s;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
  }
  .nav-drawer a:hover,
  .nav-drawer-btn:hover,
  .nav-drawer a.active { background: rgba(245,158,11,.1); color: var(--accent); }
  .nav-drawer a i, .nav-drawer-btn i { font-size: .95rem; width: 20px; text-align: center; }

  /* Drawer group label */
  .nav-drawer-group {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.3);
    padding: 14px 18px 4px;
  }
  .nav-drawer-divider {
    height: 1px;
    background: rgba(255,255,255,.07);
    margin: 6px 12px;
  }

  /* Drawer bottom actions */
  .nav-drawer-actions {
    padding: 12px 18px;
    border-top: 1px solid rgba(255,255,255,.08);
    margin-top: 8px;
    display: flex;
    gap: 8px;
  }
  .nav-drawer-actions button,
  .nav-drawer-actions a {
    flex: 1;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font-family: var(--font-body);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  @media (max-width: 768px) {
    /* Hide desktop nav items */
    .nav-link-top:not(.nav-brand *),
    .nav-item-top,
    .nav-right { display: none !important; }

    /* Show hamburger */
    .nav-hamburger { display: flex; }

    /* Navbar: brand + hamburger only */
    .os-navbar { padding: 0 16px; gap: 0; justify-content: space-between; }
  }
  </style>
</head>
<body>

<nav class="os-navbar">
  <!-- Brand -->
  <a href="<?= $baseUrl ?>/index.php" class="nav-brand">
    <?php if (!empty($_sistemaConfig['logo_path'])): ?>
      <img src="<?= rtrim($baseUrl, '/') . '/' . ltrim(htmlspecialchars($_sistemaConfig['logo_path']), '/') ?>"
           alt="Logo" style="height:36px;max-width:120px;object-fit:contain;border-radius:4px">
    <?php else: ?>
      <div class="nav-brand-icon"><i class="ph-bold ph-motorcycle"></i></div>
    <?php endif; ?>
    <span class="nav-brand-name"><?= htmlspecialchars($_sistemaConfig['nome_sistema']) ?></span>
  </a>

  <!-- Hamburger (mobile only) -->
  <button class="nav-hamburger" onclick="abrirDrawer()" aria-label="Menu">
    <i class="ph-bold ph-list"></i>
  </button>

  <!-- Principal (desktop) -->
  <a href="<?= $baseUrl ?>/index.php"
     class="nav-link-top <?= $current==='index.php'?'active':'' ?>">
    <i class="ph-bold ph-speedometer"></i> Dashboard
  </a>

  <a href="<?= $baseUrl ?>/modules/pdv/pdv.php"
     class="nav-link-top <?= $current==='pdv.php'?'active':'' ?>">
    <i class="ph-bold ph-cash-register"></i> PDV
  </a>

  <!-- Operação dropdown -->
  <div class="nav-item-top">
    <button class="nav-link-top <?= in_array($current,['os.php','orcamentos.php','clientes.php'])?'active':'' ?>">
      <i class="ph-bold ph-wrench"></i> Operação <span class="chevron">▼</span>
    </button>
    <div class="nav-dropdown">
      <a href="<?= $baseUrl ?>/modules/os/os.php" class="<?= $current==='os.php'?'active':'' ?>">
        <i class="ph-bold ph-wrench"></i> Ordens de Serviço
      </a>
      <a href="<?= $baseUrl ?>/modules/orcamentos/orcamentos.php" class="<?= $current==='orcamentos.php'?'active':'' ?>">
        <i class="ph-bold ph-file-text"></i> Orçamentos
      </a>
      <a href="<?= $baseUrl ?>/modules/clientes/clientes.php" class="<?= $current==='clientes.php'?'active':'' ?>">
        <i class="ph-bold ph-users"></i> Clientes
      </a>
    </div>
  </div>

  <!-- Gestão dropdown -->
  <div class="nav-item-top">
    <button class="nav-link-top <?= in_array($current,['produtos.php','servicos.php','estoque.php','mao_de_obra.php'])?'active':'' ?>">
      <i class="ph-bold ph-package"></i> Gestão <span class="chevron">▼</span>
    </button>
    <div class="nav-dropdown">
      <a href="<?= $baseUrl ?>/modules/mao_de_obra/mao_de_obra.php" class="<?= $current==='mao_de_obra.php'?'active':'' ?>">
        <i class="ph-bold ph-clock"></i> Mão de Obra
      </a>
      <a href="<?= $baseUrl ?>/modules/produtos/produtos.php" class="<?= $current==='produtos.php'?'active':'' ?>">
        <i class="ph-bold ph-package"></i> Produtos
      </a>
      <a href="<?= $baseUrl ?>/modules/servicos/servicos.php" class="<?= $current==='servicos.php'?'active':'' ?>">
        <i class="ph-bold ph-toolbox"></i> Serviços
      </a>
      <a href="<?= $baseUrl ?>/modules/estoque/estoque.php" class="<?= $current==='estoque.php'?'active':'' ?>">
        <i class="ph-bold ph-warehouse"></i> Estoque
      </a>
    </div>
  </div>

  <!-- Admin dropdown -->
  <div class="nav-item-top">
    <button class="nav-link-top <?= in_array($current,['relatorios.php','usuarios.php','mercadopago.php','configuracoes.php'])?'active':'' ?>">
      <i class="ph-bold ph-shield-check"></i> Admin <span class="chevron">▼</span>
    </button>
    <div class="nav-dropdown">
      <a href="<?= $baseUrl ?>/modules/relatorios/relatorios.php" class="<?= $current==='relatorios.php'?'active':'' ?>">
        <i class="ph-bold ph-chart-bar"></i> Relatórios
      </a>
      <a href="<?= $baseUrl ?>/modules/usuarios/usuarios.php" class="<?= $current==='usuarios.php'?'active':'' ?>">
        <i class="ph-bold ph-user-gear"></i> Usuários
      </a>
      <div class="nav-dropdown-divider"></div>
      <a href="<?= $baseUrl ?>/modules/mercadopago/mercadopago.php" class="<?= $current==='mercadopago.php'?'active':'' ?>">
        <i class="ph-bold ph-device-mobile"></i> Mercado Pago
      </a>
      <a href="<?= $baseUrl ?>/modules/configuracoes/configuracoes.php" class="<?= $current==='configuracoes.php'?'active':'' ?>">
        <i class="ph-bold ph-gear"></i> Configurações
      </a>
    </div>
  </div>

  <!-- Direita -->
  <div class="nav-right">
    <div class="nav-user">
      <div class="nav-avatar"><?= htmlspecialchars($iniciais) ?></div>
      <span class="nav-user-name"><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
    </div>
    <button class="nav-btn" onclick="toggleTema()">
      <i class="ph-bold" id="tema-icon"></i>
      <span id="tema-label"></span>
    </button>
    <a href="#" class="nav-btn danger"
       onclick="confirmarSaida(event)">
      <i class="ph-bold ph-sign-out"></i> Sair
    </a>
  </div>
</nav>

<!-- Mobile Overlay -->
<div class="nav-overlay" id="navOverlay" onclick="fecharDrawer()"></div>

<!-- Mobile Drawer -->
<div class="nav-drawer" id="navDrawer">
  <div class="nav-drawer-header">
    <a href="<?= $baseUrl ?>/index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none">
      <?php if (!empty($_sistemaConfig['logo_path'])): ?>
        <img src="<?= rtrim($baseUrl, '/') . '/' . ltrim(htmlspecialchars($_sistemaConfig['logo_path']), '/') ?>" alt="Logo" style="height:30px;max-width:100px;object-fit:contain">
      <?php else: ?>
        <div style="width:30px;height:30px;background:var(--accent);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#000;font-size:.85rem"><i class="ph-bold ph-motorcycle"></i></div>
      <?php endif; ?>
      <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.9rem;color:#fff"><?= htmlspecialchars($_sistemaConfig['nome_sistema']) ?></span>
    </a>
    <button class="nav-drawer-close" onclick="fecharDrawer()"><i class="ph-bold ph-x"></i></button>
  </div>

  <!-- Principal -->
  <a href="<?= $baseUrl ?>/index.php" class="<?= $current==='index.php'?'active':'' ?>">
    <i class="ph-bold ph-speedometer"></i> Dashboard
  </a>


  <!-- Operação -->
  <div class="nav-drawer-group">Operação</div>
  <a href="<?= $baseUrl ?>/modules/os/os.php" class="<?= $current==='os.php'?'active':'' ?>">
    <i class="ph-bold ph-wrench"></i> Ordens de Serviço
  </a>
  <a href="<?= $baseUrl ?>/modules/orcamentos/orcamentos.php" class="<?= $current==='orcamentos.php'?'active':'' ?>">
    <i class="ph-bold ph-file-text"></i> Orçamentos
  </a>
  <a href="<?= $baseUrl ?>/modules/clientes/clientes.php" class="<?= $current==='clientes.php'?'active':'' ?>">
    <i class="ph-bold ph-users"></i> Clientes
  </a>

  <!-- Gestão -->
  <div class="nav-drawer-group">Gestão</div>
  <a href="<?= $baseUrl ?>/modules/mao_de_obra/mao_de_obra.php" class="<?= $current==='mao_de_obra.php'?'active':'' ?>">
    <i class="ph-bold ph-clock"></i> Mão de Obra
  </a>
  <a href="<?= $baseUrl ?>/modules/produtos/produtos.php" class="<?= $current==='produtos.php'?'active':'' ?>">
    <i class="ph-bold ph-package"></i> Produtos
  </a>
  <a href="<?= $baseUrl ?>/modules/servicos/servicos.php" class="<?= $current==='servicos.php'?'active':'' ?>">
    <i class="ph-bold ph-toolbox"></i> Serviços
  </a>
  <a href="<?= $baseUrl ?>/modules/estoque/estoque.php" class="<?= $current==='estoque.php'?'active':'' ?>">
    <i class="ph-bold ph-warehouse"></i> Estoque
  </a>

  <!-- Admin -->
  <div class="nav-drawer-group">Admin</div>
  <a href="<?= $baseUrl ?>/modules/relatorios/relatorios.php" class="<?= $current==='relatorios.php'?'active':'' ?>">
    <i class="ph-bold ph-chart-bar"></i> Relatórios
  </a>
  <a href="<?= $baseUrl ?>/modules/usuarios/usuarios.php" class="<?= $current==='usuarios.php'?'active':'' ?>">
    <i class="ph-bold ph-user-gear"></i> Usuários
  </a>
  <div class="nav-drawer-divider"></div>
  <a href="<?= $baseUrl ?>/modules/mercadopago/mercadopago.php" class="<?= $current==='mercadopago.php'?'active':'' ?>">
    <i class="ph-bold ph-device-mobile"></i> Mercado Pago
  </a>
  <a href="<?= $baseUrl ?>/modules/configuracoes/configuracoes.php" class="<?= $current==='configuracoes.php'?'active':'' ?>">
    <i class="ph-bold ph-gear"></i> Configurações
  </a>

  <!-- Ações -->
  <div class="nav-drawer-actions">
    <button onclick="toggleTema();fecharDrawer()" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.7)">
      <i class="ph-bold" id="tema-icon-mobile"></i>
      <span id="tema-label-mobile"></span>
    </button>
    <a href="#" onclick="confirmarSaida(event)" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171">
      <i class="ph-bold ph-sign-out"></i> Sair
    </a>
  </div>
</div>

<div class="os-main">

<script>
(function(){
  var t = document.documentElement.getAttribute('data-theme');
  function syncTemaIcons(tema) {
    var cls = 'ph-bold ' + (tema === 'dark' ? 'ph-sun' : 'ph-moon-stars');
    var lbl = tema === 'dark' ? 'Claro' : 'Escuro';
    ['tema-icon','tema-icon-mobile'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.className = cls;
    });
    ['tema-label','tema-label-mobile'].forEach(function(id) {
      var el = document.getElementById(id); if (el) el.textContent = lbl;
    });
  }
  syncTemaIcons(t);
  window._syncTemaIcons = syncTemaIcons;
})();

// ── Dropdown hover com delay ──────────────────────────
document.querySelectorAll('.nav-item-top').forEach(function(item) {
  var timer;
  var dropdown = item.querySelector('.nav-dropdown');
  if (!dropdown) return;

  item.addEventListener('mouseenter', function() {
    clearTimeout(timer);
    // Fechar todos os outros
    document.querySelectorAll('.nav-dropdown.open').forEach(function(d) {
      if (d !== dropdown) d.classList.remove('open');
    });
    dropdown.classList.add('open');
  });

  item.addEventListener('mouseleave', function() {
    timer = setTimeout(function() {
      dropdown.classList.remove('open');
    }, 120); // pequeno delay para evitar fechamento acidental
  });
});

// Fechar dropdown ao clicar fora
document.addEventListener('click', function(e) {
  if (!e.target.closest('.nav-item-top')) {
    document.querySelectorAll('.nav-dropdown.open').forEach(function(d) {
      d.classList.remove('open');
    });
  }
});

function confirmarSaida(e) {
  e.preventDefault();
  var url = '<?= $baseUrl ?>/logout.php';
  Swal.fire({
    title: 'Sair do sistema?',
    text: 'Sua sessão será encerrada.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sim, sair',
    cancelButtonText: 'Cancelar',
    background: 'var(--bg-card, #1c2333)',
    color: 'var(--text, #f0f2f7)',
  }).then(function(result) {
    if (result.isConfirmed) window.location.href = url;
  });
}

function abrirDrawer() {
  document.getElementById('navDrawer').classList.add('open');
  document.getElementById('navOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function fecharDrawer() {
  document.getElementById('navDrawer').classList.remove('open');
  document.getElementById('navOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
// Fechar com ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') fecharDrawer();
});

function toggleTema() {
  var html = document.documentElement;
  var novo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', novo);
  document.cookie = 'os_tema=' + novo + ';path=/;max-age=' + (365*24*3600);
  if (window._syncTemaIcons) window._syncTemaIcons(novo);
  // Update chart colors if charts exist
  if (typeof Chart !== 'undefined') location.reload();
}
</script>
