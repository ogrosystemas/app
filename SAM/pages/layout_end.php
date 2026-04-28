</main><!-- /main-content -->

<!-- ── Bottom Navigation Mobile (PWA) ────────────────── -->
<nav id="bottom-nav" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1A1A1C;border-top:0.5px solid #2E2E33;z-index:200;padding:8px 0 calc(8px + env(safe-area-inset-bottom));justify-content:space-around;align-items:center">
  <?php
  $bnUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $bnItems = [
    ['icon'=>'layout-dashboard', 'label'=>'Dashboard', 'href'=>'/pages/dashboard.php',  'match'=>'/pages/dashboard'],
    ['icon'=>'headphones',       'label'=>'SAC',        'href'=>'/pages/sac.php',         'match'=>'/pages/sac'],
    ['icon'=>'tag',              'label'=>'Anúncios',   'href'=>'/pages/anuncios.php',    'match'=>'/pages/anuncios'],
    ['icon'=>'wallet',           'label'=>'Financeiro', 'href'=>'/pages/financeiro.php',  'match'=>'/pages/financeiro'],
    ['icon'=>'menu',             'label'=>'Mais',       'href'=>'#',                      'match'=>'__more__', 'onclick'=>'toggleMoreMenu()'],
  ];
  foreach ($bnItems as $item):
    $active = str_starts_with($bnUri, $item['match']);
    $color  = $active ? '#3483FA' : '#E8E8E6';
    $onclick = $item['onclick'] ?? '';
  ?>
  <a href="<?= $item['href'] ?>" <?= $onclick ? "onclick=\"{$onclick};return false\"" : '' ?>
    style="display:flex;flex-direction:column;align-items:center;gap:3px;text-decoration:none;padding:4px 12px;border-radius:10px;min-width:52px;background:<?= $active ? 'rgba(52,131,250,.1)' : 'transparent' ?>">
    <i data-lucide="<?= $item['icon'] ?>" style="width:20px;height:20px;color:<?= $active ? '#3483FA' : '#E8E8E6' ?>"></i>
    <span style="font-size:9px;color:<?= $active ? '#3483FA' : '#E8E8E6' ?>;font-weight:<?= $active ? '600' : '400' ?>"><?= $item['label'] ?></span>
  </a>
  <?php endforeach; ?>

  <!-- Seletor de conta ML no mobile -->
  <?php if (!empty($meliAccounts)): ?>
  <div style="position:relative">
    <button onclick="toggleAccountMenuMobile()" style="display:flex;flex-direction:column;align-items:center;gap:3px;background:transparent;border:none;cursor:pointer;padding:4px 8px">
      <div style="width:28px;height:28px;border-radius:50%;background:rgba(52,131,250,.15);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#3483FA;position:relative">
        <?= strtoupper(mb_substr($activeAccount['nickname'] ?? 'ML', 0, 2)) ?>
        <span style="position:absolute;bottom:-1px;right:-1px;width:8px;height:8px;border-radius:50%;background:<?= $dotColor ?? '#22c55e' ?>;border:1.5px solid #1A1A1C"></span>
      </div>
      <span style="font-size:9px;color:#E8E8E6;max-width:52px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_substr($activeAccount['nickname'] ?? 'ML', 0, 6)) ?></span>
    </button>
  </div>
  <?php endif; ?>
</nav>

<!-- ── More Menu Mobile ───────────────────────────────── -->
<div id="more-menu" style="display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.7);backdrop-filter:blur(4px)" onclick="toggleMoreMenu()">
  <div onclick="event.stopPropagation()" style="position:absolute;bottom:0;left:0;right:0;background:#1A1A1C;border-radius:20px 20px 0 0;padding:16px 16px calc(16px + env(safe-area-inset-bottom));border-top:0.5px solid #2E2E33">
    <div style="width:36px;height:4px;background:#2E2E33;border-radius:2px;margin:0 auto 16px"></div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;max-height:60vh;overflow-y:auto">
      <?php
      $moreItems = [
        ['icon'=>'package',       'label'=>'Estoque',     'href'=>'/pages/estoque.php',       'color'=>'#3483FA'],
        ['icon'=>'truck',         'label'=>'Expedição',   'href'=>'/pages/logistica.php',     'color'=>'#f97316'],
        ['icon'=>'map-pin',       'label'=>'Rastreio',    'href'=>'/pages/rastreamento.php',  'color'=>'#f97316'],
        ['icon'=>'zap',           'label'=>'Promoções',   'href'=>'/pages/promocoes.php',     'color'=>'#22c55e'],
        ['icon'=>'trending-up',   'label'=>'Lucrat.',     'href'=>'/pages/lucratividade.php', 'color'=>'#22c55e'],
        ['icon'=>'bar-chart-2',   'label'=>'Relatórios',  'href'=>'/pages/relatorios.php',    'color'=>'#22c55e'],
        ['icon'=>'package-plus',  'label'=>'Kits',        'href'=>'/pages/kits.php',          'color'=>'#a855f7'],
        ['icon'=>'copy',          'label'=>'Clonagem',    'href'=>'/pages/clonagem.php',      'color'=>'#a855f7'],
        ['icon'=>'car',           'label'=>'AutoParts',   'href'=>'/pages/autoparts.php',     'color'=>'#3483FA'],
        ['icon'=>'radar',         'label'=>'Concorr.',    'href'=>'/pages/concorrentes.php',  'color'=>'#ef4444'],
        ['icon'=>'flame',         'label'=>'Tendências',  'href'=>'/pages/tendencias.php',    'color'=>'#f97316'],
        ['icon'=>'users',         'label'=>'Usuários',    'href'=>'/pages/usuarios.php',      'color'=>'#9A9A96'],
        ['icon'=>'settings',      'label'=>'Config.',     'href'=>'/pages/config_ml.php',     'color'=>'#9A9A96'],
      ];
      foreach ($moreItems as $m):
      ?>
      <a href="<?= $m['href'] ?>" onclick="document.getElementById('more-menu').style.display='none'"
        style="display:flex;flex-direction:column;align-items:center;gap:5px;text-decoration:none;padding:12px 6px;background:#252528;border-radius:12px;border:0.5px solid #2E2E33">
        <i data-lucide="<?= $m['icon'] ?>" style="width:22px;height:22px;color:<?= $m['color'] ?>"></i>
        <span style="font-size:10px;color:#9A9A96;text-align:center"><?= $m['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
// ── Modal helpers ────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-bg').forEach(m => m.style.display = 'none');
  }
});

// ── Bottom nav: mostrar só em mobile ─────────────────────
function checkMobile() {
  const isMobile = window.innerWidth <= 768;
  const nav = document.getElementById('bottom-nav');
  if (nav) nav.style.display = isMobile ? 'flex' : 'none';
  // Padding bottom para não sobrepor conteúdo
  const main = document.getElementById('main-content');
  if (main) main.style.paddingBottom = isMobile ? '72px' : '0';
}
checkMobile();
window.addEventListener('resize', checkMobile);

// ── More menu ─────────────────────────────────────────────
function toggleMoreMenu() {
  const menu = document.getElementById('more-menu');
  menu.style.display = menu.style.display === 'none' ? 'flex' : 'none';
  if (menu.style.display === 'flex') lucide.createIcons();
}

// ── Account menu mobile ───────────────────────────────────
function toggleAccountMenuMobile() {
  const menu = document.getElementById('account-menu-mobile');
  if (!menu) return;
  menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Garante ícones renderizados em todas as páginas
if (window.lucide) lucide.createIcons();

// Chart system moved to layout.php
<?php // Export active account to JS ?>
</script>
<!-- ── Account Menu Mobile ────────────────────────────── -->
<?php if (!empty($meliAccounts)): ?>
<div id="account-menu-mobile" style="display:none;position:fixed;inset:0;z-index:400;background:rgba(0,0,0,.7);backdrop-filter:blur(4px)" onclick="toggleAccountMenuMobile()">
  <div onclick="event.stopPropagation()" style="position:absolute;bottom:0;left:0;right:0;background:#1A1A1C;border-radius:20px 20px 0 0;padding:16px 16px calc(80px + env(safe-area-inset-bottom));border-top:0.5px solid #2E2E33">
    <div style="width:36px;height:4px;background:#2E2E33;border-radius:2px;margin:0 auto 16px"></div>
    <div style="font-size:11px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px">Contas ML</div>
    <?php foreach ($meliAccounts as $acc):
      $isActive  = $acc['id'] === $activeAccountId;
      $expTs     = strtotime($acc['token_expires_at'] ?? '2000-01-01');
      $tokenDead = $expTs <= time();
      $tokenWarn = $expTs > time() && $expTs < time() + 3600;
      $statusColor = $tokenDead ? '#ef4444' : ($tokenWarn ? '#f59e0b' : '#22c55e');
    ?>
    <a href="<?= $tokenDead ? '/pages/config_ml.php' : '?switch_account='.$acc['id'] ?>"
      style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;text-decoration:none;background:<?= $isActive ? 'rgba(52,131,250,.08)' : 'transparent' ?>;margin-bottom:4px;border:0.5px solid <?= $isActive ? 'rgba(52,131,250,.2)' : 'transparent' ?>">
      <div style="width:36px;height:36px;border-radius:50%;background:<?= $isActive ? 'rgba(52,131,250,.2)' : '#252528' ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:<?= $isActive ? '#3483FA' : '#9A9A96' ?>;flex-shrink:0;position:relative">
        <?= strtoupper(mb_substr($acc['nickname'], 0, 2)) ?>
        <span style="position:absolute;bottom:0;right:0;width:10px;height:10px;border-radius:50%;background:<?= $statusColor ?>;border:2px solid #1A1A1C"></span>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:13px;color:#E8E8E6;font-weight:<?= $isActive ? '600' : '400' ?>"><?= htmlspecialchars($acc['nickname']) ?></div>
        <div style="font-size:11px;color:<?= $statusColor ?>"><?= $tokenDead ? 'Token expirado — reconectar' : ($isActive ? 'Conta ativa' : 'Toque para ativar') ?></div>
      </div>
      <?php if ($isActive): ?>
      <i data-lucide="check-circle" style="width:16px;height:16px;color:#22c55e;flex-shrink:0"></i>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <a href="/pages/config_ml.php" style="display:flex;align-items:center;gap:10px;padding:12px;border-radius:10px;text-decoration:none;color:#5E5E5A;margin-top:4px">
      <i data-lucide="plus-circle" style="width:16px;height:16px"></i>
      <span style="font-size:13px">Conectar nova conta</span>
    </a>
  </div>
</div>
<?php endif; ?>

</body>
</html>
