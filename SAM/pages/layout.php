<?php
// pages/layout.php
// Inclua no topo de cada pagina: $title='X'; include 'layout.php';
// No final: include 'layout_end.php';

$user    = auth_user();
$initials = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1) . mb_substr(explode(' ', $user['name'] ?? 'U ')[1] ?? '', 0, 1));
$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Seletor de conta ML ativa
$meliAccounts = db_all(
    "SELECT id, nickname, meli_user_id, is_active, token_expires_at FROM meli_accounts WHERE tenant_id=? AND is_active=1 ORDER BY nickname",
    [$user['tenant_id']]
);

// IDs das contas ativas do tenant (is_active=1 apenas)
$validAccountIds = array_column($meliAccounts, 'id');

// Detecta contas com token expirado ou prestes a expirar (< 1 hora)
// Também busca contas que foram desativadas por invalid_grant
$contasProblema = [];
foreach ($meliAccounts as $acc) {
    $expTs = strtotime($acc['token_expires_at'] ?? '2000-01-01');
    if ($expTs < time()) {
        $contasProblema[] = ['conta' => $acc['nickname'], 'motivo' => 'expirado'];
    } elseif ($expTs < time() + 3600) {
        $contasProblema[] = ['conta' => $acc['nickname'], 'motivo' => 'expirando'];
    }
}
// Contas desativadas por invalid_grant (is_active=0 com token não vazio)
$contasRevogadas = db_all(
    "SELECT nickname FROM meli_accounts
     WHERE tenant_id=? AND is_active=0
       AND refresh_token_enc IS NOT NULL
       AND refresh_token_enc NOT IN ('demo_refresh','')
       AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    [$user['tenant_id']]
);
foreach ($contasRevogadas as $r) {
    $contasProblema[] = ['conta' => $r['nickname'], 'motivo' => 'revogado'];
}

// Troca de conta via header
if (isset($_GET['switch_account'])) {
    $switchId = $_GET['switch_account'];
    if (in_array($switchId, $validAccountIds, true)) {
        $_SESSION['active_meli_account_id'] = $switchId;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// Se a sessão está presa em uma conta inativa/demo (is_active=0), limpa
if (!empty($_SESSION['active_meli_account_id'])
    && !in_array($_SESSION['active_meli_account_id'], $validAccountIds, true)) {
    unset($_SESSION['active_meli_account_id']);
}

// Auto-seleciona a primeira conta ativa se nenhuma selecionada
if (empty($_SESSION['active_meli_account_id']) && !empty($meliAccounts)) {
    $_SESSION['active_meli_account_id'] = $meliAccounts[0]['id'];
}

$activeAccountId = $_SESSION['active_meli_account_id'] ?? null;
$activeAccount   = null;
foreach ($meliAccounts as $acc) {
    if ($acc['id'] === $activeAccountId) { $activeAccount = $acc; break; }
}

// Mensagens não lidas do SAC — filtrado pela conta ativa validada
$unreadCount  = 0;
$readyToShip  = 0;
$questionsNR  = 0; // perguntas pré-venda não respondidas
try {
    if ($activeAccountId) {
        $unreadRow = db_one(
            "SELECT COUNT(*) as cnt FROM sac_messages
             WHERE tenant_id=? AND meli_account_id=? AND is_read=0 AND from_role='BUYER'
             AND (sentiment_label IS NULL OR sentiment_label != 'PRE_SALE')",
            [$user['tenant_id'], $activeAccountId]
        );
        $unreadCount = (int)($unreadRow['cnt'] ?? 0);

        $readyRow = db_one(
            "SELECT COUNT(*) as cnt FROM orders
             WHERE tenant_id=? AND meli_account_id=?
               AND ship_status IN ('READY_TO_SHIP','ready_to_ship')
               AND payment_status IN ('APPROVED','approved')",
            [$user['tenant_id'], $activeAccountId]
        );
        $readyToShip = (int)($readyRow['cnt'] ?? 0);
        // questionsNR é calculado dinamicamente via API ML na página de perguntas
        // não mantemos contador local para evitar dessincronização
        $questionsNR = 0;
    }
} catch (Throwable $e) { $unreadCount = 0; $readyToShip = 0; $questionsNR = 0; }

$totalAlerts = $unreadCount + $readyToShip + $questionsNR;

function nav_active(string $path): string {
    global $uri;
    return str_starts_with($uri, $path) ? 'active' : '';
}

// License check done in banner below
$licDaysLeft = 999; // fallback
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<?php session_write_close(); // libera lock da sessão — todas as escritas já foram feitas ?>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon.png">
<link rel="apple-touch-icon" sizes="192x192" href="/assets/icons/icon-192.png">
<link rel="shortcut icon" href="/assets/favicon.ico">
<title><?= htmlspecialchars($title ?? 'Dashboard') ?> — <?= APP_NAME ?></title>

<!-- PWA -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0F0F10">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SAM">
<meta name="mobile-web-app-capable" content="yes">

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: { colors: { s0:'#0F0F10',s1:'#1A1A1C',s2:'#252528',b0:'#2E2E33',b1:'#3E3E45',t0:'#E8E8E6',t1:'#9A9A96',t2:'#5E5E5A',mly:'#FFE600',mlb:'#3483FA' } } }
}
</script>

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<!-- Chart.js global -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.Charts = {};
window.registerChart = function(id, instance) { window.Charts[id] = instance; return instance; };
window.updateChartData = function(id, labels, datasets) {
  const chart = window.Charts[id];
  if (!chart) return;
  if (labels) chart.data.labels = labels;
  if (datasets) datasets.forEach((d,i) => { if (chart.data.datasets[i]) chart.data.datasets[i].data = d; });
  chart.update('none');
};
window.PAGE_DATA_API = null;
window.refreshCharts = async function() {
  if (!window.PAGE_DATA_API) return;
  try {
    const r = await fetch(window.PAGE_DATA_API + (window.PAGE_DATA_API.includes('?')?'&':'?') + '_t=' + Date.now());
    const d = await r.json();
    if (typeof window.onChartsData === 'function') window.onChartsData(d);
  } catch(e) { console.warn('refreshCharts:', e); }
};
setInterval(() => { if (window.PAGE_DATA_API) window.refreshCharts(); }, 30000);

// ── Sistema centralizado de badges — atualiza a cada 30s ──
window._updateBadges = async function() {
  try {
    const r = await fetch('/api/badges.php?_t=' + Date.now());
    const d = await r.json();
    if (!d.ok) return;

    // Sino no header
    const sino     = document.getElementById('sino-badge');
    const sinoLink = document.getElementById('sino-link');
    if (sino) {
      if (d.total_alerts > 0) {
        sino.textContent    = d.total_alerts > 99 ? '99+' : d.total_alerts;
        sino.style.display  = 'flex';
        if (sinoLink) sinoLink.title = [
          d.unread_sac  > 0 ? `${d.unread_sac} msg não lida${d.unread_sac!==1?'s':''}` : '',
          d.ready_ship  > 0 ? `${d.ready_ship} pedido${d.ready_ship!==1?'s':''} p/ enviar` : '',
        ].filter(Boolean).join(' · ');
      } else {
        sino.style.display  = 'none';
        if (sinoLink) sinoLink.title = 'Sem notificações';
      }
    }

    // Badge SAC no menu
    const sacBadge = document.getElementById('menu-badge-sac');
    if (sacBadge) {
      sacBadge.textContent   = d.unread_sac;
      sacBadge.style.display = d.unread_sac > 0 ? 'flex' : 'none';
    }

    // Badge Expedição no menu
    const shipBadge = document.getElementById('menu-badge-ship');
    if (shipBadge) {
      shipBadge.textContent   = d.ready_ship;
      shipBadge.style.display = d.ready_ship > 0 ? 'flex' : 'none';
    }

  } catch(e) { console.warn('badges poll:', e); }
};

// Polling a cada 30s + executa imediatamente após 2s (página já carregada)
setTimeout(window._updateBadges, 2000);
setInterval(window._updateBadges, 30000);

// Account selector toggle
// ── Service Worker ──────────────────────────────────────
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js')
    .then(reg => console.log('SW registered:', reg.scope))
    .catch(err => console.warn('SW error:', err));
}

// ── PWA Install prompt ──────────────────────────────────
let _deferredPrompt = null;
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  _deferredPrompt = e;
  const btn = document.getElementById('pwa-install-btn');
  if (btn) btn.style.display = 'flex';
});
window.installPWA = function() {
  if (!_deferredPrompt) return;
  _deferredPrompt.prompt();
  _deferredPrompt.userChoice.then(() => { _deferredPrompt = null; });
};


// ── Custom Dialog (substitui confirm() nativo) ──────────
window.dialog = function(opts) {
  return new Promise(resolve => {
    const {
      title       = 'Confirmar',
      message     = '',
      confirmText = 'Confirmar',
      cancelText  = 'Cancelar',
      danger      = false,
      confirmColor= null,
      icon        = null,
    } = opts;

    const btnColor = confirmColor || (danger ? '#ef4444' : '#3483FA');
    const iconName = icon || (danger ? 'alert-triangle' : 'help-circle');

    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9998;display:flex;align-items:center;justify-content:center;padding:16px;animation:fadeIn .15s ease;backdrop-filter:blur(2px)';
    overlay.innerHTML = `
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:0;width:100%;max-width:380px;animation:slideUp .18s ease;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6)">
        <div style="padding:20px 24px 0">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
            <div style="width:32px;height:32px;border-radius:8px;background:${btnColor}18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i data-lucide="${iconName}" style="width:16px;height:16px;color:${btnColor}"></i>
            </div>
            <span style="font-size:15px;font-weight:600;color:#E8E8E6">${title}</span>
          </div>
          <div style="font-size:13px;color:#9A9A96;line-height:1.6;padding-left:42px">${message}</div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:20px 24px;background:#1A1A1C">
          <button id="dlg-cancel" style="padding:8px 18px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#9A9A96;font-size:13px;cursor:pointer;font-family:inherit;transition:all .15s"
            onmouseover="this.style.background='#2E2E33';this.style.color='#E8E8E6'"
            onmouseout="this.style.background='#252528';this.style.color='#9A9A96'">${cancelText}</button>
          <button id="dlg-confirm" style="padding:8px 18px;background:${btnColor};border:none;border-radius:8px;color:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:opacity .15s"
            onmouseover="this.style.opacity='.85'"
            onmouseout="this.style.opacity='1'">${confirmText}</button>
        </div>
      </div>`;

    document.body.appendChild(overlay);
    if (window.lucide) lucide.createIcons();

    overlay.querySelector('#dlg-confirm').onclick = () => { overlay.remove(); resolve(true); };
    overlay.querySelector('#dlg-cancel').onclick  = () => { overlay.remove(); resolve(false); };
    overlay.onclick = e => { if (e.target === overlay) { overlay.remove(); resolve(false); } };

    // Fechar com ESC
    const onKey = e => { if (e.key === 'Escape') { overlay.remove(); resolve(false); document.removeEventListener('keydown', onKey); } };
    document.addEventListener('keydown', onKey);
  });
};

// Adiciona animações ao CSS
const animStyle = document.createElement('style');
animStyle.textContent = '@keyframes fadeIn{from{opacity:0}to{opacity:1}} @keyframes slideUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}';
document.head.appendChild(animStyle);

window.toggleAccountMenu = function() {
  const menu = document.getElementById('account-menu');
  if (!menu) return;
  menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
};

window.toggleUserMenu = function() {
  const menu = document.getElementById('user-menu');
  if (!menu) return;
  menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
};

// Fecha dropdowns ao clicar fora
document.addEventListener('click', function(e) {
  const acctSel = document.getElementById('account-selector');
  if (acctSel && !acctSel.contains(e.target)) {
    const m = document.getElementById('account-menu');
    if (m) m.style.display = 'none';
  }
  const userWrap = document.getElementById('user-menu-wrap');
  if (userWrap && !userWrap.contains(e.target)) {
    const m = document.getElementById('user-menu');
    if (m) m.style.display = 'none';
  }
});

// ── Trocar senha ────────────────────────────────────────
window.openChangePassword = function() {
  document.getElementById('cp-modal').style.display = 'flex';
  document.getElementById('cp-current').value = '';
  document.getElementById('cp-new').value = '';
  document.getElementById('cp-confirm').value = '';
  setTimeout(() => document.getElementById('cp-current').focus(), 100);
};

window.closeChangePassword = function() {
  document.getElementById('cp-modal').style.display = 'none';
};

window.submitChangePassword = async function() {
  const current = document.getElementById('cp-current').value;
  const novo    = document.getElementById('cp-new').value;
  const confirm = document.getElementById('cp-confirm').value;

  if (!current || !novo || !confirm) { toast('Preencha todos os campos', 'error'); return; }
  if (novo.length < 8) { toast('Nova senha precisa ter pelo menos 8 caracteres', 'error'); return; }
  if (novo !== confirm) { toast('As senhas não coincidem', 'error'); return; }

  const btn = document.getElementById('cp-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  try {
    const fd = new FormData();
    fd.append('current_password', current);
    fd.append('new_password',     novo);
    const r = await fetch('/api/change_password.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      toast('Senha alterada com sucesso!', 'success');
      closeChangePassword();
    } else {
      toast(d.error || 'Erro ao alterar senha', 'error');
    }
  } catch(e) {
    toast('Erro de conexão', 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Salvar senha';
  }
};
</script>

<style>
  * { box-sizing: border-box; }
  body { background: #0F0F10; color: #E8E8E6; font-family: system-ui, -apple-system, sans-serif; }

  /* Transições suaves em todas as interações */
  a, button { transition: opacity .15s ease, background .15s ease, color .15s ease, transform .1s ease, border-color .15s ease; }
  button:active { transform: scale(0.97); }
  a:active { opacity: .7; }

  /* Page transition */
  .page-content { animation: pageIn .2s ease; }
  @keyframes pageIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
  @keyframes spin { to { transform: rotate(360deg); } }
  @keyframes pulse-red { 0%,100% { opacity:1; box-shadow:0 0 0 0 rgba(239,68,68,.4); } 50% { opacity:.7; box-shadow:0 0 0 4px rgba(239,68,68,0); } }

  /* Topnav */
  #topnav a { transition: color .15s, border-bottom-color .15s; }

  /* Card hover */
  .card-hover { transition: box-shadow .15s, transform .15s; }
  .card-hover:hover { box-shadow: 0 4px 20px rgba(0,0,0,.3); transform: translateY(-1px); }

  /* Grids */
  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
  .config-ml-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
  .fin-charts-grid { grid-template-columns: 2fr 1fr; }

  /* NAV link (compatibilidade) */
  .nav-link { display:flex;align-items:center;gap:8px;padding:7px 16px;text-decoration:none;color:#9A9A96;font-size:13px;transition:color .15s,background .15s }
  .nav-link:hover,.nav-link.active { color:#E8E8E6;background:rgba(52,131,250,.08) }
  .nav-link.active { color:#3483FA }

  /* Botões */
  .btn-primary { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#3483FA;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;transition:opacity .15s;text-decoration:none }
  .btn-primary:hover { opacity:.9 }
  .btn-primary:disabled { opacity:.5;cursor:not-allowed }
  .btn-secondary { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:transparent;color:#9A9A96;border:0.5px solid #2E2E33;border-radius:8px;font-size:13px;cursor:pointer;transition:all .15s;text-decoration:none }
  .btn-secondary:hover { background:#252528;color:#E8E8E6 }

  /* Input */
  .input { width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;transition:border .15s;box-sizing:border-box }
  .input:focus { border-color:#3483FA }

  /* SAC layout */
  .sac-col1 { display:flex; }
  .sac-col2 { display:flex; }

  /* ── MOBILE ── */
  @media (max-width: 768px) {
    /* ── Geral ── */
    .hide-mobile { display: none !important; }
    body { overflow-x: hidden; }
    img { max-width: 100%; }
    input, select, textarea { max-width: 100%; box-sizing: border-box; font-size: 16px !important; } /* 16px evita zoom no iOS */

    /* ── Padding e containers ── */
    #fin-main { padding: 12px !important; }
    #main-content { padding-bottom: 80px !important; }

    /* ── Grids de página (não modais) ── */
    .kpi-grid { grid-template-columns: 1fr 1fr !important; gap: 8px !important; }
    .config-ml-grid { grid-template-columns: 1fr !important; }
    .fin-charts-grid { grid-template-columns: 1fr !important; }
    .bank-cards > div { min-width: calc(50% - 5px) !important; flex: 1 1 calc(50% - 5px) !important; }

    /* ── Tabelas ── */
    .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table { min-width: 500px; }

    /* ── Botões ── */
    .btn-primary, .btn-secondary { min-height: 44px; font-size: 14px !important; }
    button { min-height: 36px; }

    /* ── Modais — fullscreen bottom sheet ── */
    [id^="modal-"] {
      align-items: flex-end !important;
      padding: 0 !important;
    }
    [id^="modal-"] > div:not([id]) {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 !important;
      border-radius: 20px 20px 0 0 !important;
      max-height: 92vh !important;
      overflow-y: auto !important;
    }
    /* Grid de 2 colunas dentro de modais vira 1 coluna */
    [id^="modal-"] div[style*="grid-template-columns:1fr 1fr"],
    [id^="modal-"] div[style*="grid-template-columns: 1fr 1fr"] {
      grid-template-columns: 1fr !important;
    }
    /* Grid interno do modal (dados + compatibilidade) */
    [id^="modal-"] > div > div[style*="grid-template-columns"] {
      grid-template-columns: 1fr !important;
    }

    /* ── SAC ── */
    #sac-inbox { height: calc(100dvh - 52px - 170px) !important; position: relative !important; overflow: hidden !important; }
    .sac-col1 { position:absolute !important; inset:0 !important; width:100% !important; z-index:2 !important; border-right:none !important; background:#0F0F10 !important; transition:transform .25s ease !important; }
    .sac-col1.slide-out { transform: translateX(-100%) !important; }
    .sac-col2 { position:absolute !important; inset:0 !important; width:100% !important; z-index:1 !important; background:#0F0F10 !important; transform:translateX(100%) !important; transition:transform .25s ease !important; }
    .sac-col2.slide-in { transform: translateX(0) !important; z-index:3 !important; }
    #order-ctx { display: none !important; }
    #sac-back-btn { display: flex !important; }
    #sac-reply-bar { position: sticky !important; bottom: 0 !important; z-index:10 !important; }

    /* ── PWA ── */
    #top-navbar, #top-navbar-menu { display: none !important; }
    body { padding-bottom: env(safe-area-inset-bottom); }

    /* ═══════════════════════════════════════════════════════
     RESPONSIVO MOBILE — PWA
     ═══════════════════════════════════════════════════════ */
  @media (max-width: 768px) {

    /* ── Base ─────────────────────────────────────────── */
    body { overflow-x: hidden; }
    img  { max-width: 100%; height: auto; }

    /* Inputs: 16px evita zoom automático no iOS */
    input, select, textarea, button {
      font-size: 16px !important;
      -webkit-text-size-adjust: 100%;
    }

    /* ── PWA: esconder navbar, padding para bottom nav ── */
    #top-navbar, #top-navbar-menu { display: none !important; }
    #main-content { padding-bottom: 80px !important; }
    body { padding-bottom: env(safe-area-inset-bottom); }

    /* ── Padding de páginas ───────────────────────────── */
    #fin-main,
    div[style*="padding:24px"],
    div[style*="padding: 24px"],
    div[style*="padding:20px"],
    div[style*="padding: 20px"] {
      padding: 12px !important;
    }

    /* ── Fontes: escalar para legibilidade ────────────── */
    *[style*="font-size:9px"]  { font-size: 12px !important; }
    *[style*="font-size:10px"] { font-size: 12px !important; }
    *[style*="font-size:11px"] { font-size: 13px !important; }

    /* ── Grids: colapsar para 1 coluna ───────────────── */
    /* auto-fit e auto-fill: deixar o browser decidir */
    div[style*="grid-template-columns:1fr 1fr"],
    div[style*="grid-template-columns: 1fr 1fr"],
    div[style*="grid-template-columns:2fr 1fr"],
    div[style*="grid-template-columns:1fr 2fr"],
    div[style*="grid-template-columns:1fr 320px"],
    div[style*="grid-template-columns:1fr auto 1fr"],
    div[style*="grid-template-columns:280px 1fr"],
    div[style*="grid-template-columns:200px 1fr"],
    div[style*="grid-template-columns:140px 1fr"],
    div[style*="grid-template-columns:1fr 1fr 1fr"],
    div[style*="grid-template-columns:1fr 1fr 1fr auto"],
    div[style*="grid-template-columns:1fr 1fr auto"],
    div[style*="grid-template-columns:2fr 1fr 1fr"],
    div[style*="grid-template-columns:repeat(3,1fr)"],
    div[style*="grid-template-columns:repeat(4,1fr)"],
    div[style*="grid-template-columns:repeat(5,1fr)"],
    div[style*="grid-template-columns:repeat(6,1fr)"],
    div[style*="grid-template-columns:repeat(5,1fr) 2fr"] {
      grid-template-columns: 1fr !important;
    }

    /* KPI grids: 2 colunas no mobile */
    .kpi-grid,
    div[style*="grid-template-columns:repeat(auto-fit,minmax(1"],
    div[style*="grid-template-columns:repeat(auto-fit,minmax(15"],
    div[style*="grid-template-columns:repeat(auto-fit,minmax(16"],
    div[style*="grid-template-columns:repeat(auto-fit,minmax(18"] {
      grid-template-columns: 1fr 1fr !important;
      gap: 8px !important;
    }

    /* Cards auto-fill: min 140px */
    div[style*="grid-template-columns:repeat(auto-fill"] {
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important;
    }

    /* ── Modais: bottom sheet universal ──────────────── */
    div[style*="position:fixed;inset:0"][style*="align-items:center"] {
      align-items: flex-end !important;
      padding: 0 !important;
    }
    div[style*="position:fixed;inset:0"][style*="align-items:flex-start"] {
      align-items: flex-end !important;
      padding: 0 !important;
    }
    /* Container interno do modal */
    div[style*="position:fixed;inset:0"] > div[style*="border-radius:16px"],
    div[style*="position:fixed;inset:0"] > div[style*="border-radius:14px"],
    div[style*="position:fixed;inset:0"] > div[style*="border-radius:12px"] {
      max-width: 100% !important;
      width: 100% !important;
      margin: 0 !important;
      border-radius: 20px 20px 0 0 !important;
      max-height: 92dvh !important;
      overflow-y: auto !important;
    }
    /* Exceção: modais pequenos de confirmação ficam centralizados */
    div[style*="position:fixed;inset:0"] > div[style*="max-width:380px"],
    div[style*="position:fixed;inset:0"] > div[style*="max-width:420px"] {
      border-radius: 16px !important;
      max-width: calc(100% - 32px) !important;
      margin: auto 16px !important;
    }

    /* ── Grids dentro de modais: border separadores ── */
    div[style*="min-height:500px"] > div:first-child {
      border-right: none !important;
      border-bottom: 0.5px solid #2E2E33 !important;
    }
    div[style*="min-height:500px"] {
      min-height: auto !important;
    }

    /* ── Sticky panels: desativar ─────────────────────── */
    div[style*="position:sticky"] {
      position: static !important;
    }

    /* ── Tabelas: scroll horizontal ───────────────────── */
    table {
      display: block;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      white-space: nowrap;
    }

    /* ── Botões: altura mínima touch ─────────────────── */
    .btn-primary, .btn-secondary { min-height: 44px; }
    button[onclick], a[href] { min-height: 36px; }

    /* ── SAC ──────────────────────────────────────────── */
    #sac-inbox { height: calc(100dvh - 52px - 170px) !important; position: relative !important; overflow: hidden !important; }
    .sac-col1 { position:absolute !important; inset:0 !important; width:100% !important; z-index:2 !important; border-right:none !important; background:#0F0F10 !important; transition:transform .25s ease !important; }
    .sac-col1.slide-out { transform: translateX(-100%) !important; }
    .sac-col2 { position:absolute !important; inset:0 !important; width:100% !important; z-index:1 !important; background:#0F0F10 !important; transform:translateX(100%) !important; transition:transform .25s ease !important; }
    .sac-col2.slide-in { transform: translateX(0) !important; z-index:3 !important; }
    #order-ctx { display: none !important; }
    #sac-back-btn { display: flex !important; }
    #sac-reply-bar { position: sticky !important; bottom: 0 !important; z-index:10 !important; }

    /* ── Scrollbar ────────────────────────────────────── */
    ::-webkit-scrollbar { display: none; }
  }

  @media (max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr !important; }
  }

  /* Grids de modais 2 colunas: borda vertical vira horizontal */
  @media (max-width: 768px) {
    .ap-modal-grid, .kit-modal-grid {
      grid-template-columns: 1fr !important;
      min-height: auto !important;
    }
    .ap-modal-grid > div:first-child,
    .kit-modal-grid > div:first-child {
      border-right: none !important;
      border-bottom: 0.5px solid #2E2E33 !important;
    }
  }

  ::-webkit-scrollbar { width: 4px; height: 4px; }
  ::-webkit-scrollbar-thumb { background: #3E3E45; border-radius: 4px; }
  ::-webkit-scrollbar-track { background: transparent; }
  [x-cloak] { display: none !important; }
  .btn-danger { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; }

  .card { background: #1A1A1C; border: 0.5px solid #2E2E33; border-radius: 12px; }
  .input { width: 100%; padding: 9px 12px; background: #252528; border: 0.5px solid #2E2E33; border-radius: 8px; color: #E8E8E6; font-size: 13px; outline: none; transition: border-color 0.15s; font-family: inherit; }
  .input:focus { border-color: #3483FA; box-shadow: 0 0 0 3px rgba(52,131,250,0.12); }
  .input::placeholder { color: #5E5E5A; }
  textarea.input { resize: vertical; min-height: 80px; }

  .badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 500; }
  .badge-green  { background: rgba(34,197,94,0.12);  color: #22c55e; }
  .badge-red    { background: rgba(239,68,68,0.12);  color: #ef4444; }
  .badge-amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
  .badge-blue   { background: rgba(52,131,250,0.12); color: #3483FA; }

  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th { padding: 8px 14px; text-align: left; font-size: 10px; font-weight: 500; color: #5E5E5A; border-bottom: 0.5px solid #2E2E33; text-transform: uppercase; letter-spacing: 0.6px; white-space: nowrap; }
  td { padding: 10px 14px; border-bottom: 0.5px solid #2E2E33; color: #E8E8E6; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #252528; }

  .kpi { background: #252528; border-radius: 10px; padding: 14px 16px; }
  .kpi-label { font-size: 11px; color: #5E5E5A; margin-bottom: 4px; }
  .kpi-value { font-size: 22px; font-weight: 500; color: #E8E8E6; line-height: 1.2; }
  .kpi-delta { font-size: 11px; margin-top: 3px; }
  .pos { color: #22c55e; }
  .neg { color: #ef4444; }

  .toggle { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
  .toggle input { opacity: 0; width: 0; height: 0; }
  .toggle-slider { position: absolute; inset: 0; background: #2E2E33; border-radius: 10px; cursor: pointer; transition: .2s; }
  .toggle-slider:before { content: ''; position: absolute; width: 14px; height: 14px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: .2s; }
  .toggle input:checked + .toggle-slider { background: #3483FA; }
  .toggle input:checked + .toggle-slider:before { transform: translateX(16px); }

  .page-in { animation: pageIn 0.18s ease-out; }
  @keyframes pageIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }

  /* Modal overlay — z-index acima do header (100) e sidebar (200) */
  .modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
  .modal-box { background: #1A1A1C; border: 0.5px solid #2E2E33; border-radius: 16px; padding: 24px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
  /* Todos os modais inline (position:fixed) */
  div[style*='position:fixed'][style*='z-index:50'],
  div[style*='position:fixed'][style*='z-index: 50'] { z-index: 1000 !important; }
</style>
<script>
window.ACTIVE_MELI_ACCOUNT_ID = '<?= $activeAccountId ?? "" ?>';
window.ACTIVE_MELI_NICKNAME = '<?= htmlspecialchars($activeAccount["nickname"] ?? "", ENT_QUOTES) ?>';
</script>
</head>

<body class="flex flex-col" style="min-height:100vh;min-height:100dvh;overflow-x:hidden" x-data>

<!-- Toast — disponível globalmente para todos os scripts da página -->
<div id="toast" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:none;align-items:center;gap:10px;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.4);transition:all .2s"></div>
<script>
function toast(msg, type='success') {
  const el = document.getElementById('toast');
  const colors = {
    success: ['#22c55e','rgba(34,197,94,.15)','rgba(34,197,94,.3)'],
    error:   ['#ef4444','rgba(239,68,68,.15)','rgba(239,68,68,.3)'],
    warning: ['#f59e0b','rgba(245,158,11,.15)','rgba(245,158,11,.3)'],
    info:    ['#3483FA','rgba(52,131,250,.15)','rgba(52,131,250,.3)'],
  };
  const [color, bg, border] = colors[type] || colors.info;
  el.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;align-items:center;gap:10px;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.4);background:${bg};border:0.5px solid ${border};color:${color}`;
  el.textContent = msg;
  el.style.display = 'flex';
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => el.style.display = 'none', 3500);
}
</script>

<?php
$licStatus = $user['license_status'] ?? 'TRIAL';
if (!empty($user['license_expiry'])) {
    $licExpiry = strtotime($user['license_expiry']);
} elseif (!empty($user['trial_started'])) {
    $licExpiry = strtotime($user['trial_started']) + (15 * 86400);
} else {
    $licExpiry = strtotime($user['created_at'] ?? 'now') + (15 * 86400);
}
$licDaysLeft = max(0, (int)ceil(($licExpiry - time()) / 86400));
$isTrial     = $licStatus === 'TRIAL';
?>
<?php if ($isTrial || $licDaysLeft <= 7): ?>
<div style="background:<?= $isTrial ? 'rgba(52,131,250,0.08)' : 'rgba(245,158,11,0.1)' ?>;border-bottom:0.5px solid #2E2E33;color:<?= $isTrial ? '#3483FA' : '#f59e0b' ?>;padding:7px 16px;font-size:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
  <i data-lucide="<?= $isTrial ? 'clock' : 'alert-triangle' ?>" style="width:12px;height:12px;flex-shrink:0"></i>
  <?php if ($isTrial): ?>
    <span><strong>Período de trial:</strong> <?= $licDaysLeft ?> dia<?= $licDaysLeft !== 1 ? 's' : '' ?> restante<?= $licDaysLeft !== 1 ? 's' : '' ?> de 15.
    Ative sua licença para continuar usando após o trial.</span>
  <?php else: ?>
    <span>Sua licença expira em <strong><?= $licDaysLeft ?> dia<?= $licDaysLeft !== 1 ? 's' : '' ?></strong>. Renove para não perder o acesso.</span>
  <?php endif; ?>
  <a href="/pages/config_ml.php#licenca" style="color:inherit;font-weight:600;text-decoration:underline;margin-left:auto">Ativar agora →</a>
</div>
<?php endif; ?>

<?php if (!empty($contasProblema)): ?>
<?php foreach ($contasProblema as $prob):
  $isRevogado  = $prob['motivo'] === 'revogado';
  $isExpirando = $prob['motivo'] === 'expirando';
  $bgColor     = $isRevogado  ? 'rgba(239,68,68,0.08)'   : 'rgba(245,158,11,0.08)';
  $bdColor     = $isRevogado  ? '#2E2E33'                 : '#2E2E33';
  $txtColor    = $isRevogado  ? '#ef4444'                 : '#f59e0b';
  $icon        = $isRevogado  ? 'wifi-off'                : 'alert-triangle';
  $msg         = $isRevogado
    ? "A conta <strong>{$prob['conta']}</strong> foi desconectada do Mercado Livre. Reconecte para continuar recebendo pedidos e mensagens."
    : ($isExpirando
        ? "O token da conta <strong>{$prob['conta']}</strong> expira em menos de 1 hora. O sistema irá renovar automaticamente."
        : "O token da conta <strong>{$prob['conta']}</strong> expirou. O sistema tentará renovar automaticamente.");
?>
<div style="background:<?= $bgColor ?>;border-bottom:0.5px solid <?= $bdColor ?>;color:<?= $txtColor ?>;padding:7px 16px;font-size:12px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
  <i data-lucide="<?= $icon ?>" style="width:12px;height:12px;flex-shrink:0"></i>
  <span><?= $msg ?></span>
  <?php if ($isRevogado): ?>
  <a href="/pages/config_ml.php" style="color:#ef4444;font-weight:700;text-decoration:underline;margin-left:auto;white-space:nowrap">
    Reconectar agora →
  </a>
  <?php elseif (!$isExpirando): ?>
  <a href="/api/meli_refresh_token.php?force=1&secret=<?= MASTER_SECRET ?>" style="color:#f59e0b;font-weight:700;text-decoration:underline;margin-left:auto;white-space:nowrap" target="_blank">
    Forçar renovação →
  </a>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Header -->
<header id="top-navbar" style="height:52px;background:#1A1A1C;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;position:sticky;top:0;z-index:100">

  <!-- Logo -->
  <a href="/pages/dashboard.php" style="display:flex;align-items:center;gap:8px;flex-shrink:0;text-decoration:none">
    <img src="/assets/logo.png" alt="SAM" style="height:28px;width:auto;object-fit:contain" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
    <span style="display:none;font-size:16px;font-weight:700;color:#E8E8E6;letter-spacing:-0.5px"><?= defined('APP_NAME') ? APP_NAME : 'SAM' ?></span>
  </a>

  <!-- Separador -->
  <div style="width:0.5px;height:20px;background:#2E2E33;flex-shrink:0"></div>

  <div style="flex:1;min-width:0"></div>

  <!-- PWA Install — só ícone no mobile -->
  <button id="pwa-install-btn" onclick="installPWA()" title="Instalar app"
    style="display:none;align-items:center;gap:5px;padding:5px 8px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;border-radius:8px;cursor:pointer;color:#3483FA;font-size:11px;flex-shrink:0">
    <i data-lucide="download" style="width:13px;height:13px"></i>
    <span class="hide-mobile">Instalar</span>
  </button>

  <!-- Seletor de conta ML -->
  <?php if (!empty($meliAccounts)): ?>
  <div style="position:relative" id="account-selector">
    <?php
    $activeTokenExp  = $activeAccount ? strtotime($activeAccount['token_expires_at'] ?? '2000-01-01') : 0;
    $activeTokenOk   = $activeTokenExp > time() + 3600; // mais de 1h restante
    $activeTokenWarn = $activeTokenExp > time() && !$activeTokenOk; // menos de 1h
    $activeTokenDead = $activeTokenExp <= time();
    $dotColor = $activeTokenDead ? '#ef4444' : ($activeTokenWarn ? '#f59e0b' : '#22c55e');
    ?>
    <button onclick="toggleAccountMenu()" style="display:flex;align-items:center;gap:6px;padding:5px 10px;background:#252528;border:0.5px solid <?= $activeTokenDead ? '#ef4444' : ($activeTokenWarn ? '#f59e0b' : '#2E2E33') ?>;border-radius:8px;cursor:pointer;color:#E8E8E6;font-size:12px">
      <span style="width:7px;height:7px;border-radius:50%;background:<?= $dotColor ?>;flex-shrink:0<?= $activeTokenDead ? ';animation:pulse-red 1.5s infinite' : '' ?>"></span>
      <span class="acct-name hide-mobile" style="font-weight:500;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= htmlspecialchars($activeAccount['nickname'] ?? 'Selecionar conta') ?>
      </span>
      <i data-lucide="chevron-down" style="width:12px;height:12px;color:#5E5E5A;flex-shrink:0"></i>
    </button>
    <div id="account-menu" style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;min-width:220px;z-index:100;box-shadow:0 8px 24px rgba(0,0,0,.4);overflow:hidden">
      <div style="padding:8px 12px;font-size:10px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.6px;border-bottom:0.5px solid #2E2E33">Contas ML</div>
      <?php foreach ($meliAccounts as $acc):
        $isActive  = $acc['id'] === $activeAccountId;
        $expTs     = strtotime($acc['token_expires_at'] ?? '2000-01-01');
        $tokenDead = $expTs <= time();
        $tokenWarn = $expTs > time() && $expTs < time() + 3600;
        $tokenOk   = !$tokenDead && !$tokenWarn;
        $statusColor = $tokenDead ? '#ef4444' : ($tokenWarn ? '#f59e0b' : '#22c55e');
        $statusLabel = $tokenDead ? '✗ Token expirado — clique para reconectar' : ($tokenWarn ? '⚠ Expira em breve' : ($isActive ? '✓ Conectada' : 'Clique para ativar'));
      ?>
      <a href="<?= $tokenDead ? '/pages/config_ml.php' : '?switch_account='.$acc['id'] ?>"
        style="display:flex;align-items:center;gap:10px;padding:10px 14px;text-decoration:none;background:<?= $isActive?'rgba(52,131,250,.08)':'transparent' ?>;border-bottom:0.5px solid #2E2E33"
        onmouseover="this.style.background='#252528'" onmouseout="this.style.background='<?= $isActive?'rgba(52,131,250,.08)':'transparent' ?>'">
        <div style="width:28px;height:28px;border-radius:50%;background:<?= $isActive?'rgba(52,131,250,.2)':'#252528' ?>;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:<?= $isActive?'#3483FA':'#9A9A96' ?>;flex-shrink:0;position:relative">
          <?= strtoupper(mb_substr($acc['nickname'],0,2)) ?>
          <?php if ($tokenDead): ?>
          <span style="position:absolute;top:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:#ef4444;border:1.5px solid #1A1A1C"></span>
          <?php elseif ($tokenWarn): ?>
          <span style="position:absolute;top:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:#f59e0b;border:1.5px solid #1A1A1C"></span>
          <?php endif; ?>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;color:<?= $tokenDead ? '#ef4444' : '#E8E8E6' ?>;font-weight:<?= $isActive?'600':'400' ?>;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= htmlspecialchars($acc['nickname']) ?>
          </div>
          <div style="font-size:10px;color:<?= $statusColor ?>">
            <?= $statusLabel ?>
          </div>
        </div>
        <?php if ($isActive && $tokenOk): ?>
        <i data-lucide="check" style="width:12px;height:12px;color:#22c55e;flex-shrink:0"></i>
        <?php elseif ($tokenDead): ?>
        <i data-lucide="wifi-off" style="width:12px;height:12px;color:#ef4444;flex-shrink:0"></i>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      <a href="/pages/config_ml.php" style="display:flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;color:#5E5E5A;font-size:11px"
        onmouseover="this.style.background='#252528'" onmouseout="this.style.background='transparent'">
        <i data-lucide="plus" style="width:11px;height:11px"></i> Conectar nova conta
      </a>
    </div>
  </div>
  <?php elseif ($user['can_access_admin'] ?? false): ?>
  <a href="/pages/config_ml.php" style="display:flex;align-items:center;gap:6px;padding:5px 10px;background:rgba(255,230,0,.1);border:0.5px solid #FFE600;border-radius:8px;cursor:pointer;color:#FFE600;font-size:11px;text-decoration:none">
    <i data-lucide="plug" style="width:11px;height:11px"></i> Conectar ML
  </a>
  <?php endif; ?>

  <!-- Notificações -->
  <?php
    $tooltipParts = [];
    if ($unreadCount > 0) $tooltipParts[] = "{$unreadCount} msg não lida" . ($unreadCount !== 1 ? 's' : '');
    if ($readyToShip > 0) $tooltipParts[] = "{$readyToShip} pedido" . ($readyToShip !== 1 ? 's' : '') . " p/ enviar";
    if ($questionsNR  > 0) $tooltipParts[] = "{$questionsNR} pergunta" . ($questionsNR !== 1 ? 's' : '') . " s/ resposta";
    $tooltip  = $tooltipParts ? implode(' · ', $tooltipParts) : 'Sem notificações';
    $sinoHref = $readyToShip > 0 && $unreadCount === 0 && $questionsNR === 0
        ? '/pages/logistica.php'
        : ($questionsNR > 0 && $unreadCount === 0 && $readyToShip === 0
            ? '/pages/perguntas.php'
            : '/pages/sac.php');
  ?>
  <a id="sino-link" href="<?= $sinoHref ?>" class="hide-mobile"
     style="padding:6px;border-radius:6px;background:transparent;color:#9A9A96;position:relative;display:flex;align-items:center;text-decoration:none"
     title="<?= htmlspecialchars($tooltip) ?>">
    <i data-lucide="bell" style="width:15px;height:15px;color:#f59e0b"></i>
    <span id="sino-badge" style="position:absolute;top:2px;right:2px;min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;display:<?= $totalAlerts > 0 ? 'flex' : 'none' ?>;align-items:center;justify-content:center;line-height:1">
      <?= $totalAlerts > 99 ? '99+' : $totalAlerts ?>
    </span>
  </a>

  <!-- Avatar + menu do usuário -->
  <div style="position:relative;display:flex;align-items:center;gap:6px;flex-shrink:0" id="user-menu-wrap">
    <button onclick="toggleUserMenu()" style="display:flex;align-items:center;gap:6px;background:transparent;border:none;cursor:pointer;padding:3px">
      <div style="width:28px;height:28px;border-radius:50%;background:#3483FA;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:600;flex-shrink:0"><?= $initials ?></div>
      <div class="hide-mobile">
        <div style="font-size:12px;font-weight:500;color:#E8E8E6;line-height:1.2;text-align:left"><?= htmlspecialchars(explode(' ', $user['name'] ?? '')[0]) ?></div>
        <div style="font-size:10px;color:#5E5E5A;line-height:1.2;text-align:left"><?= htmlspecialchars($user['role'] ?? '') ?></div>
      </div>
    </button>

    <!-- Dropdown menu -->
    <div id="user-menu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;min-width:180px;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,.5);overflow:hidden">
      <!-- Info do usuário -->
      <div style="padding:12px 14px;border-bottom:0.5px solid #2E2E33">
        <div style="font-size:12px;font-weight:600;color:#E8E8E6"><?= htmlspecialchars($user['name'] ?? '') ?></div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        <div style="margin-top:6px">
          <span style="font-size:9px;padding:1px 7px;border-radius:8px;background:rgba(52,131,250,.15);color:#3483FA"><?= htmlspecialchars($user['role'] ?? '') ?></span>
        </div>
      </div>
      <!-- Trocar senha -->
      <button onclick="openChangePassword();toggleUserMenu()"
        style="width:100%;text-align:left;padding:10px 14px;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;color:#9A9A96;font-size:12px;border-bottom:0.5px solid #2E2E33"
        onmouseover="this.style.background='#252528'" onmouseout="this.style.background='none'">
        <i data-lucide="key" style="width:13px;height:13px;color:#3483FA"></i>
        Trocar senha
      </button>
      <!-- Sair -->
      <a href="/api/auth.php?action=logout"
        style="display:flex;align-items:center;gap:8px;padding:10px 14px;text-decoration:none;color:#ef4444;font-size:12px"
        onmouseover="this.style.background='#252528'" onmouseout="this.style.background='none'">
        <i data-lucide="log-out" style="width:13px;height:13px;color:#ef4444"></i>
        Sair
      </a>
    </div>
  </div>
</header>

<!-- Modal: Trocar Senha -->
<div id="cp-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:380px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <div style="width:32px;height:32px;border-radius:8px;background:rgba(52,131,250,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="key" style="width:14px;height:14px;color:#3483FA"></i>
      </div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#E8E8E6">Trocar senha</div>
        <div style="font-size:11px;color:#5E5E5A">Mínimo 8 caracteres</div>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Senha atual</label>
        <input type="password" id="cp-current" class="input" placeholder="••••••••"
          onkeydown="if(event.key==='Enter')document.getElementById('cp-new').focus()">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Nova senha</label>
        <input type="password" id="cp-new" class="input" placeholder="Mínimo 8 caracteres"
          onkeydown="if(event.key==='Enter')document.getElementById('cp-confirm').focus()">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Confirmar nova senha</label>
        <input type="password" id="cp-confirm" class="input" placeholder="Repita a nova senha"
          onkeydown="if(event.key==='Enter')submitChangePassword()">
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:20px">
      <button id="cp-submit-btn" onclick="submitChangePassword()" class="btn-primary" style="flex:1">
        Salvar senha
      </button>
      <button onclick="closeChangePassword()" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<!-- Navbar horizontal com dropdowns -->
<nav id="top-navbar-menu" style="background:#1A1A1C;border-bottom:0.5px solid #2E2E33;height:48px;flex-shrink:0;display:flex;align-items:center;padding:0 12px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;position:relative;z-index:90">
<style>
#topnav::-webkit-scrollbar{display:none}
.nav-item{position:relative;display:flex;align-items:center;height:48px;flex-shrink:0}
.nav-btn{display:flex;align-items:center;gap:6px;padding:0 14px;height:48px;font-size:12px;font-weight:500;color:#5E5E5A;text-decoration:none;border:none;background:none;cursor:pointer;white-space:nowrap;border-bottom:2px solid transparent;transition:color .15s,border-color .15s;font-family:inherit}
.nav-btn:hover,.nav-item.open .nav-btn{color:#E8E8E6}
.nav-btn.active{color:#E8E8E6;border-bottom-color:#3483FA;font-weight:600}
.nav-btn .chevron{transition:transform .2s}
.nav-item.open .nav-btn .chevron{transform:rotate(180deg)}
.nav-dropdown{display:none;position:fixed;top:101px;min-width:210px;background:#1E1E21;border:0.5px solid #2E2E33;border-radius:10px;padding:6px;box-shadow:0 8px 32px rgba(0,0,0,.6);z-index:999}
.nav-item.open .nav-dropdown{display:block}
.nav-dd-item{display:flex;align-items:center;gap:8px;padding:9px 12px;color:#9A9A96;font-size:12px;text-decoration:none;border-radius:7px;transition:background .12s,color .12s;white-space:nowrap;width:100%;font-family:inherit;border:none;background:none;cursor:pointer;box-sizing:border-box}
.nav-dd-item:hover{background:#2E2E35;color:#E8E8E6}
.nav-dd-item.active{background:rgba(52,131,250,.12);color:#3483FA}
.nav-dd-item i{flex-shrink:0}
.nav-dd-sep{height:0.5px;background:#2E2E33;margin:4px 6px}
</style>

<?php
$sacActive    = str_starts_with($uri, '/pages/sac') || str_starts_with($uri, '/pages/respostas') || str_starts_with($uri, '/pages/crm');
$anuncActive  = (bool)array_filter(['/pages/anuncios','/pages/perguntas','/pages/renovacoes','/pages/precos','/pages/mensagens','/pages/anuncios_plus','/pages/corrida','/pages/metricas','/pages/clonagem','/pages/kits','/pages/promocoes'], fn($p)=>str_starts_with($uri,$p));
$logActive    = (bool)array_filter(['/pages/logistica','/pages/estoque','/pages/rastreamento'], fn($p)=>str_starts_with($uri,$p));
$configActive = (bool)array_filter(['/pages/config_ml','/pages/usuarios'], fn($p)=>str_starts_with($uri,$p));
$concActive   = str_starts_with($uri,'/pages/concorrentes');
$finActive    = str_starts_with($uri,'/pages/financeiro') || str_starts_with($uri,'/pages/relatorios') || str_starts_with($uri,'/pages/lucratividade');
$dashActive   = $uri === '/pages/dashboard.php';
?>

<!-- Dashboard -->
<div class="nav-item">
  <a href="/pages/dashboard.php" class="nav-btn <?= $dashActive?'active':'' ?>">
    <i data-lucide="layout-dashboard" style="width:15px;height:15px;color:#3483FA"></i> Dashboard
  </a>
</div>

<?php if ($user['can_access_sac']??false): ?>
<div class="nav-item" data-dropdown>
  <button class="nav-btn <?= $sacActive?'active':'' ?>" onclick="toggleNav(this)">
    <i data-lucide="headphones" style="width:15px;height:15px;color:#22c55e"></i> SAC
    <?php if ($unreadCount??0): ?>
    <span id="menu-badge-sac" style="min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center"><?= $unreadCount ?></span>
    <?php else: ?>
    <span id="menu-badge-sac" style="display:none;min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;align-items:center;justify-content:center">0</span>
    <?php endif; ?>
    <i data-lucide="chevron-down" class="chevron" style="width:12px;height:12px;opacity:.5;margin-left:2px"></i>
  </button>
  <div class="nav-dropdown">
    <a href="/pages/sac.php" class="nav-dd-item <?= $uri==='/pages/sac.php'&&!str_contains($_SERVER['QUERY_STRING']??'','tab=')?'active':'' ?>">
      <i data-lucide="inbox" style="width:13px;height:13px;color:#22c55e"></i> Inbox
    </a>
    <a href="/pages/sac.php?tab=reclamacoes" class="nav-dd-item <?= str_contains($_SERVER['QUERY_STRING']??'','tab=reclamacoes')?'active':'' ?>">
      <i data-lucide="alert-triangle" style="width:13px;height:13px;color:#ef4444"></i> Reclamações
    </a>
    <a href="/pages/sac.php?tab=avaliacoes" class="nav-dd-item <?= str_contains($_SERVER['QUERY_STRING']??'','tab=avaliacoes')?'active':'' ?>">
      <i data-lucide="star" style="width:13px;height:13px;color:#f59e0b"></i> Avaliações
    </a>
    <div class="nav-dd-sep"></div>
    <a href="/pages/respostas.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/respostas')?'active':'' ?>">
      <i data-lucide="message-square-reply" style="width:13px;height:13px;color:#22c55e"></i> Respostas Prontas
    </a>
    <a href="/pages/crm.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/crm')?'active':'' ?>">
      <i data-lucide="users" style="width:13px;height:13px;color:#3483FA"></i> CRM Compradores
    </a>
  </div>
</div>
<?php endif; ?>

<?php if ($user['can_access_anuncios']??false): ?>
<div class="nav-item" data-dropdown>
  <button class="nav-btn <?= $anuncActive?'active':'' ?>" onclick="toggleNav(this)">
    <i data-lucide="tag" style="width:15px;height:15px;color:#FFE600"></i> Anúncios
    <i data-lucide="chevron-down" class="chevron" style="width:12px;height:12px;opacity:.5;margin-left:2px"></i>
  </button>
  <div class="nav-dropdown">
    <a href="/pages/anuncios.php" class="nav-dd-item <?= $uri==='/pages/anuncios.php'?'active':'' ?>">
      <i data-lucide="layout-grid" style="width:13px;height:13px;color:#FFE600"></i> Gerenciar
    </a>
    <a href="/pages/anuncios.php?tab=renovar" class="nav-dd-item <?= ($uri=='/pages/anuncios.php'&&($_GET['tab']??'')=='renovar')?'active':'' ?>">
      <i data-lucide="refresh-cw" style="width:13px;height:13px;color:#3483FA"></i> Renovação Manual
    </a>
    <a href="/pages/perguntas.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/perguntas')?'active':'' ?>">
      <i data-lucide="help-circle" style="width:13px;height:13px;color:#f59e0b"></i> Perguntas Pré-venda
      <?php if ($questionsNR??0): ?>
      <span style="min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center;margin-left:auto"><?= $questionsNR ?></span>
      <?php endif; ?>
    </a>
    <a href="/pages/renovacoes.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/renovacoes')?'active':'' ?>">
      <i data-lucide="clock" style="width:13px;height:13px;color:#a855f7"></i> Renovação Automática
    </a>
    <div class="nav-dd-sep"></div>
    <a href="/pages/precos_massa.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/precos')?'active':'' ?>">
      <i data-lucide="dollar-sign" style="width:13px;height:13px;color:#22c55e"></i> Preços em Massa
    </a>
    <a href="/pages/promocoes.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/promocoes')?'active':'' ?>">
      <i data-lucide="zap" style="width:13px;height:13px;color:#22c55e"></i> Promoções em Massa
    </a>
    <a href="/pages/mensagens_auto.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/mensagens')?'active':'' ?>">
      <i data-lucide="send" style="width:13px;height:13px;color:#3483FA"></i> MSG Automáticas
    </a>
    <div class="nav-dd-sep"></div>
    <a href="/pages/anuncios_plus.php?tab=ferias" class="nav-dd-item <?= ($uri==='/pages/anuncios_plus.php'&&($_GET['tab']??'')==='ferias')?'active':'' ?>">
      <i data-lucide="umbrella" style="width:13px;height:13px;color:#f59e0b"></i> Férias
    </a>
    <a href="/pages/anuncios_plus.php?tab=saude" class="nav-dd-item <?= ($uri==='/pages/anuncios_plus.php'&&($_GET['tab']??'')==='saude')?'active':'' ?>">
      <i data-lucide="heart-pulse" style="width:13px;height:13px;color:#ef4444"></i> Saúde dos Anúncios
    </a>
    <a href="/pages/anuncios_plus.php?tab=visitas" class="nav-dd-item <?= ($uri==='/pages/anuncios_plus.php'&&($_GET['tab']??'')==='visitas')?'active':'' ?>">
      <i data-lucide="eye" style="width:13px;height:13px;color:#3483FA"></i> Visitas por Anúncio
    </a>
    <a href="/pages/metricas_ads.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/metricas')?'active':'' ?>">
      <i data-lucide="bar-chart-2" style="width:13px;height:13px;color:#a855f7"></i> Métricas ADS
    </a>
    <a href="/pages/corrida_precos.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/corrida')?'active':'' ?>">
      <i data-lucide="trending-down" style="width:13px;height:13px;color:#22c55e"></i> Corrida de Preços
    </a>
    <div class="nav-dd-sep"></div>
    <a href="/pages/clonagem.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/clonagem')?'active':'' ?>">
      <i data-lucide="copy" style="width:13px;height:13px;color:#a855f7"></i> Clonagem de Anúncios
    </a>
    <a href="/pages/kits.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/kits')?'active':'' ?>">
      <i data-lucide="package-plus" style="width:13px;height:13px;color:#a855f7"></i> Kits e Composições
    </a>
  </div>
</div>
<?php endif; ?>

<?php if ($user['can_access_financeiro']??false): ?>
<div class="nav-item" data-dropdown>
  <button class="nav-btn <?= $finActive?'active':'' ?>" onclick="toggleNav(this)">
    <i data-lucide="wallet" style="width:15px;height:15px;color:#a855f7"></i> Financeiro
    <i data-lucide="chevron-down" class="chevron" style="width:12px;height:12px;opacity:.5;margin-left:2px"></i>
  </button>
  <div class="nav-dropdown">
    <a href="/pages/financeiro.php" class="nav-dd-item <?= $uri==='/pages/financeiro.php'?'active':'' ?>">
      <i data-lucide="wallet" style="width:13px;height:13px;color:#a855f7"></i> Extrato & DRE
    </a>
    <a href="/pages/lucratividade.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/lucratividade')?'active':'' ?>">
      <i data-lucide="trending-up" style="width:13px;height:13px;color:#22c55e"></i> Lucratividade
    </a>
    <a href="/pages/relatorios.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/relatorios')?'active':'' ?>">
      <i data-lucide="bar-chart-2" style="width:13px;height:13px;color:#3483FA"></i> Relatórios
    </a>
  </div>
</div>
<?php endif; ?>

<!-- Concorrentes — item standalone -->
<div class="nav-item">
  <a href="/pages/concorrentes.php" class="nav-btn <?= $concActive?'active':'' ?>">
    <i data-lucide="radar" style="width:15px;height:15px;color:#ef4444"></i> Concorrentes
  </a>
</div>

<!-- AutoParts — item standalone -->
<div class="nav-item">
  <a href="/pages/autoparts.php" class="nav-btn <?= str_starts_with($uri,'/pages/autoparts')?'active':'' ?>">
    <i data-lucide="car" style="width:15px;height:15px;color:#3483FA"></i> AutoParts
  </a>
</div>

<!-- Tendências — item standalone -->
<div class="nav-item">
  <a href="/pages/tendencias.php" class="nav-btn <?= str_starts_with($uri,'/pages/tendencias')?'active':'' ?>">
    <i data-lucide="flame" style="width:15px;height:15px;color:#f97316"></i> Tendências
  </a>
</div>

<?php if ($user['can_access_logistica']??false): ?>
<div class="nav-item" data-dropdown>
  <button class="nav-btn <?= $logActive?'active':'' ?>" onclick="toggleNav(this)">
    <i data-lucide="truck" style="width:15px;height:15px;color:#f97316"></i> Logística
    <?php if ($readyToShip??0): ?>
    <span id="menu-badge-ship" style="min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:center"><?= $readyToShip ?></span>
    <?php else: ?>
    <span id="menu-badge-ship" style="display:none;min-width:16px;height:16px;padding:0 4px;background:#ef4444;border-radius:8px;font-size:9px;font-weight:700;color:#fff;align-items:center;justify-content:center">0</span>
    <?php endif; ?>
    <i data-lucide="chevron-down" class="chevron" style="width:12px;height:12px;opacity:.5;margin-left:2px"></i>
  </button>
  <div class="nav-dropdown">
    <a href="/pages/logistica.php" class="nav-dd-item <?= $uri==='/pages/logistica.php'?'active':'' ?>">
      <i data-lucide="package-check" style="width:13px;height:13px;color:#f97316"></i> Expedição
    </a>
    <a href="/pages/estoque.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/estoque')?'active':'' ?>">
      <i data-lucide="package" style="width:13px;height:13px;color:#f59e0b"></i> Estoque
    </a>
    <a href="/pages/rastreamento.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/rastreamento')?'active':'' ?>">
      <i data-lucide="map-pin" style="width:13px;height:13px;color:#3483FA"></i> Rastreamento
    </a>
  </div>
</div>
<?php endif; ?>

<?php if ($user['can_access_admin']??false): ?>
<div class="nav-item" data-dropdown>
  <button class="nav-btn <?= $configActive?'active':'' ?>" onclick="toggleNav(this)">
    <i data-lucide="settings" style="width:15px;height:15px;color:#9A9A96"></i> Configurações
    <i data-lucide="chevron-down" class="chevron" style="width:12px;height:12px;opacity:.5;margin-left:2px"></i>
  </button>
  <div class="nav-dropdown">
    <a href="/pages/config_ml.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/config_ml')?'active':'' ?>">
      <i data-lucide="plug" style="width:13px;height:13px;color:#FFE600"></i> Integração ML
    </a>
    <a href="/pages/usuarios.php" class="nav-dd-item <?= str_starts_with($uri,'/pages/usuarios')?'active':'' ?>">
      <i data-lucide="users" style="width:13px;height:13px;color:#22c55e"></i> Usuários
    </a>
  </div>
</div>
<?php endif; ?>

</nav>

<script>
// ── Dropdown do menu ──────────────────────────────────────
function toggleNav(btn) {
  const item = btn.closest('.nav-item');
  const dd   = item.querySelector('.nav-dropdown');
  const isOpen = item.classList.contains('open');

  // Fecha todos
  document.querySelectorAll('.nav-item.open').forEach(el => el.classList.remove('open'));

  if (!isOpen) {
    item.classList.add('open');
    // Posiciona o dropdown sob o botão
    const rect = btn.getBoundingClientRect();
    dd.style.left = rect.left + 'px';
    dd.style.top  = (rect.bottom + 2) + 'px';
  }
}

// Fecha ao clicar fora
document.addEventListener('click', function(e) {
  if (!e.target.closest('[data-dropdown]')) {
    document.querySelectorAll('.nav-item.open').forEach(el => el.classList.remove('open'));
  }
});

// Recalcula posição ao rolar a topnav
document.getElementById('topnav')?.addEventListener('scroll', function() {
  const open = document.querySelector('.nav-item.open');
  if (open) {
    const btn  = open.querySelector('.nav-btn');
    const dd   = open.querySelector('.nav-dropdown');
    const rect = btn.getBoundingClientRect();
    dd.style.left = rect.left + 'px';
    dd.style.top  = (rect.bottom + 2) + 'px';
  }
});
</script>


<!-- Conteúdo -->
<main id="main-content" style="flex:1;min-width:0;overflow-y:auto;overflow-x:hidden;-webkit-overflow-scrolling:touch">
