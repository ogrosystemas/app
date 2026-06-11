let deferredInstallPrompt = null;

// ============================================
// INIT
// ============================================

export function initPWA() {
  initInstallPrompt();
  initServiceWorker();
}

// ============================================
// SERVICE WORKER — com detecção de atualização
// ============================================

function initServiceWorker() {
  if (!('serviceWorker' in navigator)) return;

  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('./sw.js');

      // Verifica se já existe um SW esperando (atualização pendente ao carregar)
      if (registration.waiting) {
        showUpdateBanner(registration.waiting);
      }

      // Detecta quando uma nova versão termina de instalar
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        if (!newWorker) return;

        newWorker.addEventListener('statechange', () => {
          // O novo SW instalou e está aguardando — informa o usuário
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            showUpdateBanner(newWorker);
          }
        });
      });

      // Quando o novo SW assume, recarrega a página
      let reloading = false;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (reloading) return;
        reloading = true;
        window.location.reload();
      });

    } catch (err) {
      console.error('Erro SW:', err);
    }
  });
}

// ============================================
// BANNER DE ATUALIZAÇÃO
// ============================================

function showUpdateBanner(worker) {
  // Evita criar dois banners
  if (document.getElementById('updateBanner')) return;

  const banner = document.createElement('div');
  banner.id = 'updateBanner';

  Object.assign(banner.style, {
    position:        'fixed',
    top:             '12px',
    left:            '50%',
    transform:       'translateX(-50%)',
    zIndex:          '99999',
    display:         'flex',
    alignItems:      'center',
    gap:             '12px',
    padding:         '12px 16px',
    borderRadius:    '18px',
    background:      'rgba(10,18,40,.96)',
    border:          '1px solid rgba(249,115,22,.4)',
    boxShadow:       '0 8px 32px rgba(0,0,0,.5)',
    backdropFilter:  'blur(16px)',
    maxWidth:        'calc(100vw - 32px)',
    fontSize:        '14px',
    color:           '#f8fafc',
    fontFamily:      'Inter, system-ui, sans-serif',
    fontWeight:      '500',
  });

  banner.innerHTML = `
    <span style="flex:1">🔄 Nova versão disponível!</span>
    <button id="updateNowBtn" style="
      background: linear-gradient(135deg, #f97316, #ea580c);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 8px 16px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      font-family: inherit;
      white-space: nowrap;
    ">Atualizar agora</button>
    <button id="dismissUpdateBtn" style="
      background: rgba(255,255,255,.07);
      color: #94a3b8;
      border: none;
      border-radius: 10px;
      padding: 8px 10px;
      cursor: pointer;
      font-size: 13px;
      font-family: inherit;
    ">✕</button>
  `;

  document.body.appendChild(banner);

  document.getElementById('updateNowBtn').addEventListener('click', () => {
    worker.postMessage('SKIP_WAITING');
    banner.remove();
  });

  document.getElementById('dismissUpdateBtn').addEventListener('click', () => {
    banner.remove();
  });
}

// ============================================
// BOTÃO INSTALAR APP
// ============================================

function initInstallPrompt() {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    createInstallButton();
  });

  window.addEventListener('appinstalled', () => {
    document.getElementById('installPwaButton')?.remove();
    deferredInstallPrompt = null;
  });
}

function createInstallButton() {
  if (document.getElementById('installPwaButton')) return;

  const btn = document.createElement('button');
  btn.id = 'installPwaButton';

  Object.assign(btn.style, {
    position:       'fixed',
    bottom:         '96px',
    right:          '16px',
    zIndex:         '9998',
    display:        'flex',
    alignItems:     'center',
    gap:            '8px',
    padding:        '12px 16px',
    borderRadius:   '18px',
    background:     'linear-gradient(135deg, #f97316, #ea580c)',
    color:          'white',
    border:         'none',
    fontWeight:     '700',
    fontSize:       '14px',
    cursor:         'pointer',
    boxShadow:      '0 8px 24px rgba(249,115,22,.35)',
    fontFamily:     'Inter, system-ui, sans-serif',
  });

  btn.innerHTML = `
    <i data-lucide="smartphone" style="width:18px;height:18px"></i>
    Instalar App
  `;

  document.body.appendChild(btn);
  if (window.lucide) lucide.createIcons();

  btn.addEventListener('click', async () => {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    btn.remove();
  });
}
