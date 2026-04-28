<?php
// includes/layout.php — layout responsivo
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$currentUser = currentUser();
$userInitial = mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1));

function renderSidebar(string $activePage = ''): void
{
    global $currentUser, $userInitial;
    $isAdmin = isAdmin();
    $base    = BASE_URL;
    $role    = $isAdmin ? 'Administrador' : 'Membro';
?>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="logo-mark">
      <div>
        <div class="logo-text"><?= htmlspecialchars(setting('sistema_nome','KM Tracker')) ?></div>
        <div class="logo-sub">Sistema de Gestão</div>
      </div>
    </div>
    <button class="sidebar-close" id="sidebar-close">✕</button>
  </div>
  <nav class="sidebar-nav">
    <?php if ($isAdmin): ?>
    <div class="nav-section-label">Administração</div>
    <a href="<?= $base ?>/admin/dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">📊</span> Painel Adm
    </a>
    <a href="<?= $base ?>/admin/users.php" class="nav-item <?= $activePage === 'users' ? 'active' : '' ?>">
      <span class="nav-icon">👥</span> Integrantes
    </a>
    <a href="<?= $base ?>/admin/events.php" class="nav-item <?= $activePage === 'events' ? 'active' : '' ?>">
      <span class="nav-icon">📅</span> Eventos
    </a>
    <a href="<?= $base ?>/admin/attendances.php" class="nav-item <?= $activePage === 'attendances' ? 'active' : '' ?>">
      <span class="nav-icon">✅</span> Presenças
    </a>
    <a href="<?= $base ?>/admin/reports.php" class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
      <span class="nav-icon">📈</span> Relatórios
    </a>
    <a href="<?= $base ?>/user/ranking.php" class="nav-item <?= $activePage === 'ranking' ? 'active' : '' ?>">
      <span class="nav-icon">🏆</span> Ranking
    </a>
    <a href="<?= $base ?>/user/sextas_gallery.php" class="nav-item <?= $activePage === 'sextas' ? 'active' : '' ?>">
      <span class="nav-icon">📸</span> Galeria Sextas
    </a>
    <div class="nav-section-label">Configurações</div>
    <a href="<?= $base ?>/admin/whatsapp.php" class="nav-item <?= $activePage === 'whatsapp' ? 'active' : '' ?>">
      <span class="nav-icon">📱</span> WhatsApp
    </a>
    <a href="<?= $base ?>/admin/escalas.php" class="nav-item <?= $activePage === 'escalas' ? 'active' : '' ?>">
      <span class="nav-icon">📋</span> Escalas
    </a>
    <a href="<?= $base ?>/admin/sistema.php" class="nav-item <?= $activePage === 'sistema' ? 'active' : '' ?>">
      <span class="nav-icon">⚙️</span> Sistema
    </a>
    <div class="nav-section-label">Conta</div>
    <?php else: ?>
    <div class="nav-section-label">Minha Área</div>
    <a href="<?= $base ?>/user/dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
      <span class="nav-icon">🏠</span> Meu Painel
    </a>
    <a href="<?= $base ?>/user/events.php" class="nav-item <?= $activePage === 'events' ? 'active' : '' ?>">
      <span class="nav-icon">📅</span> Eventos
    </a>
    <a href="<?= $base ?>/user/ranking.php" class="nav-item <?= $activePage === 'ranking' ? 'active' : '' ?>">
      <span class="nav-icon">🏆</span> Ranking
    </a>
    <a href="<?= $base ?>/user/sextas_gallery.php" class="nav-item <?= $activePage === 'sextas' ? 'active' : '' ?>">
      <span class="nav-icon">📸</span> Galeria Sextas
    </a>
    <a href="<?= $base ?>/user/history.php" class="nav-item <?= $activePage === 'history' ? 'active' : '' ?>">
      <span class="nav-icon">🗂️</span> Histórico
    </a>
    <div class="nav-section-label">Conta</div>
    <?php endif; ?>
    <a href="<?= $base ?>/profile.php" class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
      <span class="nav-icon">👤</span> Meu Perfil
    </a>
    <a href="<?= $base ?>/logout.php" class="nav-item" style="color:#dc3545">
      <span class="nav-icon">🚪</span> Sair
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar" style="overflow:hidden;padding:0">
        <?php if (!empty($currentUser['avatar'])): ?>
        <img src="<?= BASE_URL . '/' . $currentUser['avatar'] ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
        <?= htmlspecialchars($userInitial) ?>
        <?php endif; ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'Integrante') ?></div>
        <div class="user-role"><?= htmlspecialchars($role) ?></div>
      </div>
    </div>
  </div>
</aside>
<?php
}

function renderTopbar(string $pageTitle): void
{
    global $currentUser;
    $initial = mb_strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1));
?>
<header class="topbar">
  <button class="topbar-hamburger" id="sidebar-toggle">☰</button>
  <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
  <a href="<?= BASE_URL ?>/profile.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;padding:4px 8px;border-radius:8px;transition:background .15s" onmouseover="this.style.background='var(--bg-hover,#1f2229)'" onmouseout="this.style.background='transparent'">
    <div style="text-align:right;display:none" class="topbar-userinfo">
      <div style="font-size:.82rem;font-weight:600;color:var(--text)"><?= htmlspecialchars($currentUser['name'] ?? '') ?></div>
      <div style="font-size:.68rem;color:var(--text-dim)"><?= htmlspecialchars($role ?? 'Integrante') ?></div>
    </div>
    <div class="topbar-avatar" style="overflow:hidden;padding:0">
      <?php if (!empty($currentUser['avatar'])): ?>
      <img src="<?= BASE_URL . '/' . $currentUser['avatar'] ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
      <?php else: ?>
      <?= htmlspecialchars($initial) ?>
      <?php endif; ?>
    </div>
  </a>
</header>
<?php
}

function pageOpen(string $title, string $activePage, string $pageTitle = ''): void
{
    if (!$pageTitle) $pageTitle = $title;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($title) ?> — <?= htmlspecialchars(setting('sistema_nome','KM Tracker')) ?></title>

    <!-- PWA -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
    <meta name="theme-color" content="#0d0f14">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(setting('sistema_nome','KM Tracker')) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?= htmlspecialchars(setting('sistema_nome','KM Tracker')) ?>">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/logo.png">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tema.css">

    <style>
        /* TODO O SEU CSS AQUI (MANTENHA O CSS ORIGINAL) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        /* ── Tema Escuro (padrão) ── */
        :root {
            --bg-body:    #0d0f14;
            --bg-card:    #14161c;
            --bg-input:   #1f2229;
            --bg-hover:   #1f2229;
            --border:     #2a2f3a;
            --text:       #eef0f8;
            --text-muted: #a0a5b5;
            --text-dim:   #6e7485;
            --accent:     #f39c12;
            --sidebar-bg: #14161c;
            --topbar-bg:  #14161c;
        }
        /* ── Tema Claro ── */
        body.tema-light {
            --bg-body:    #f0f2f5;
            --bg-card:    #ffffff;
            --bg-input:   #f8f9fa;
            --bg-hover:   #f0f2f5;
            --border:     #dee2e6;
            --text:       #1a1d23;
            --text-muted: #495057;
            --text-dim:   #6c757d;
            --accent:     #f39c12;
            --sidebar-bg: #ffffff;
            --topbar-bg:  #ffffff;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: var(--bg-body);
            color: var(--text);
            line-height: 1.5;
        }
        .shell {
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: calc(100% - 260px);
            margin-left: 260px;
        }
        .page-body {
            padding: 20px;
            width: 100%;
            overflow-x: hidden;
        }
        /* Tabelas responsivas */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            height: 100dvh;
            z-index: 100;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-text {
            font-weight: 700;
            font-size: 1.1rem;
            color: #f5b041;
        }
        .logo-sub {
            font-size: 0.7rem;
            color: var(--text-dim);
        }
        .sidebar-nav {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }
        .nav-section-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-dim);
            margin: 16px 0 8px 0;
            letter-spacing: 0.05em;
        }
        .nav-section-label:first-child {
            margin-top: 0;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text);
        }
        .nav-item.active {
            background: rgba(243, 156, 18, 0.15);
            color: #f5b041;
        }
        .nav-icon {
            font-size: 1.2rem;
        }
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
        }
        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(243, 156, 18, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #f5b041;
        }
        .user-name {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .user-role {
            font-size: 0.7rem;
            color: var(--text-dim);
        }
        .sidebar-close {
            display: none;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
        }
        .topbar {
            background: var(--topbar-bg);
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 99;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 8px;
        }
        .topbar-title {
            font-weight: 600;
            font-size: 1rem;
        }
        .topbar-avatar {
            width: 36px;
            height: 36px;
            background: rgba(243, 156, 18, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #f5b041;
            font-weight: 600;
        }
        .card {
            background: var(--bg-card, #14161c);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-title {
            font-weight: 600;
            font-size: 1rem;
        }
        .card-body {
            padding: 20px;
            background: var(--bg-card, #14161c);
            color: var(--text, #eef0f8);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--border);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #f5b041;
        }
        .stat-label {
            font-size: 0.7rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-sub {
            font-size: 0.7rem;
            color: var(--text-dim);
            margin-top: 4px;
        }
        .table-wrap {
            overflow-x: auto;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .users-table th {
            color: var(--text-dim);
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary {
            background: #f39c12;
            color: #0d0f14;
        }
        .btn-primary:hover {
            background: #f5b041;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-sm {
            padding: 4px 12px;
            font-size: 0.7rem;
        }
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
        }
        .btn-accent {
            background: #f39c12;
            color: #0d0f14;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-accent {
            background: #f39c12;
            color: #0d0f14;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .badge-muted {
            background: var(--bg-input);
            color: var(--text-dim);
        }
        .badge-gold {
            background: #f39c12;
            color: #0d0f14;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border: 1px solid #28a745;
            color: #28a745;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        .page-header {
            margin-bottom: 24px;
        }
        .page-header h2 {
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .page-header p {
            color: var(--text-dim);
            font-size: 0.85rem;
        }
        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .sexta-card {
            background: linear-gradient(135deg, var(--bg-card), var(--bg-card2));
            border: 1px solid #f39c12;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .sexta-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #f5b041;
        }
        .sexta-grid {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .sexta-item {
            background: var(--bg-input);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .sexta-item-title {
            font-weight: 700;
            font-size: 1rem;
            color: #f5b041;
        }
        .sexta-item-date {
            font-size: 0.85rem;
            margin-top: 4px;
            color: var(--text-muted);
        }
        .sexta-item-countdown {
            font-size: 0.7rem;
            color: var(--text-dim);
            margin-top: 4px;
        }
        .btn-sexta {
            background: #f39c12;
            color: #0d0f14;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-sexta-confirmado {
            background: #28a745;
            color: white;
            cursor: default;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .text-muted {
            color: var(--text-dim);
        }
        .text-center {
            text-align: center;
        }
        .text-gold {
            color: #f5b041;
        }
        .flex {
            display: flex;
        }
        .gap-2 {
            gap: 8px;
        }
        .mb-6 {
            margin-bottom: 24px;
        }
        .filter-bar {
            margin-bottom: 20px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            border-top: 1px solid var(--border);
        }
        .pagination a {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-muted);
            background: var(--bg-input);
            border: 1px solid var(--border);
        }
        .pagination a.current {
            background: #f39c12;
            color: #0d0f14;
            border-color: #f39c12;
        }
        @media (min-width: 769px) {
            .sidebar {
                position: fixed !important;
                left: 0 !important;
                overflow-y: auto;
            }
            .sidebar-overlay {
                display: none !important;
            }
            .topbar-hamburger {
                display: none !important;
            }
            .sidebar-close {
                display: none !important;
            }
            .topbar-userinfo {
                display: block !important;
            }
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                height: 100%;
                height: 100dvh;
                z-index: 1000;
                transition: left 0.3s ease;
                overflow-y: auto;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
            }
            .sidebar-footer {
                position: relative;
                flex-shrink: 0;
            }
            .sidebar.open {
                left: 0;
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            }
            .sidebar-overlay.open {
                display: block;
            }
            .sidebar-close {
                display: block;
            }
            .topbar-hamburger {
                display: block;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .page-body {
                padding: 16px;
            }
            .hide-mobile {
                display: none !important;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
            }
            .grid-2 {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
        @media (max-width: 480px) {
            /* Tabs responsivos */
            [style*="width:fit-content"] {
                max-width: 100%;
                overflow-x: auto;
            }
            .page-body {
                padding: 12px;
            }
            .stats-grid {
                grid-template-columns: 1fr !important;
            }
            .page-header-row {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .page-header-actions {
                width: 100%;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .btn-sm {
                padding: 6px 12px;
            }
        }
		
		/* Botão Cancelar padrão - garantia de consistência */
.btn-cancel {
    background: #dc3545 !important;
    color: white !important;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-cancel:hover {
    background: #c82333 !important;
    transform: translateY(-1px);
}
    </style>
</head>
<body class="<?= setting('tema','dark') === 'light' ? 'tema-light' : '' ?>">
<div class="shell">
<?php renderSidebar($activePage); ?>
  <div class="main-content">
    <?php renderTopbar($pageTitle); ?>
    <div class="page-body">
<?php
}

function pageClose(): void
{
?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
                .then(function(reg) { console.log('[PWA] SW registrado:', reg.scope); })
                .catch(function(err) { console.warn('[PWA] SW falhou:', err); });
        });
    }
    </script>
    </div>
  </div>
</div>

<script>
(function() {
    var tog = document.getElementById('sidebar-toggle');
    var sb = document.getElementById('sidebar');
    var ov = document.getElementById('sidebar-overlay');
    var cls = document.getElementById('sidebar-close');
    if (!sb) return;
    function openSidebar() { sb.classList.add('open'); if (ov) ov.classList.add('open'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sb.classList.remove('open'); if (ov) ov.classList.remove('open'); document.body.style.overflow = ''; }
    if (tog) tog.addEventListener('click', openSidebar);
    if (ov) ov.addEventListener('click', closeSidebar);
    if (cls) cls.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeSidebar(); });
})();
</script>
</body>
</html>
<?php
}
?>