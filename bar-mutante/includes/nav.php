<?php
// Shared navigation — included AFTER config + DB + helpers + Auth are loaded
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$nome_est    = class_exists('DB') ? DB::cfg('nome_estabelecimento','Bar System Pro') : 'Bar System Pro';
$logo_pdv_nav= class_exists('DB') ? DB::cfg('logo_pdv','') : '';
$logo_nav_url= $logo_pdv_nav ? UPLOAD_URL . 'logos/' . $logo_pdv_nav : '';
$isAdmin     = class_exists('Auth') && Auth::isAdmin();
?>
<nav class="admin-nav">
  <a href="<?= BASE_URL ?>" class="brand">
    <?php if ($logo_nav_url): ?>
      <img src="<?= h($logo_nav_url) ?>" alt="Logo" style="height:28px;object-fit:contain;border-radius:5px">
    <?php else: ?>
      <i class="ph-bold ph-beer-bottle"></i>
    <?php endif; ?>
    <span><?= h($nome_est) ?></span>
  </a>
  <div class="nav-links">
    <a href="<?= BASE_URL ?>" class="<?= $currentFile==='index.php'&&$currentDir!=='modules'?'active':'' ?>">
      <i class="ph-bold ph-monitor me-1"></i>PDV
    </a>
    <?php if ($isAdmin): ?>
    <a href="<?= BASE_URL ?>modules/produtos/lista.php" class="<?= $currentDir==='produtos'?'active':'' ?>">
      <i class="ph-bold ph-package me-1"></i>Produtos
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/index.php" class="<?= $currentDir==='estoque'?'active':'' ?>">
      <i class="ph-bold ph-warehouse me-1"></i>Estoque
    </a>
    <a href="<?= BASE_URL ?>modules/caixa/historico.php" class="<?= $currentDir==='caixa'?'active':'' ?>">
      <i class="ph-bold ph-cash-register me-1"></i>Caixa
    </a>
    <a href="<?= BASE_URL ?>modules/relatorios/index.php" class="<?= $currentDir==='relatorios'?'active':'' ?>">
      <i class="ph-bold ph-chart-bar me-1"></i>Relatórios
    </a>
    <a href="<?= BASE_URL ?>modules/configuracoes/index.php" class="<?= $currentDir==='configuracoes'?'active':'' ?>">
      <i class="ph-bold ph-gear me-1"></i>Config
    </a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <?php if (class_exists('Auth') && Auth::logado()): ?>
    <span class="nav-user-info">
      <i class="ph-bold ph-user-circle me-1"></i><?= h(Auth::nome()) ?>
      <small><?= h(Auth::labelPerfil()) ?></small>
    </span>
    <a href="<?= BASE_URL ?>logout.php" class="nav-logout" title="Sair">
      <i class="ph-bold ph-sign-out"></i>
    </a>
    <?php endif; ?>
  </div>
</nav>
