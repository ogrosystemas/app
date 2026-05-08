let deferredPrompt = null;

function createInstallButton() {

  if (
    document.getElementById('installPwaButton')
  ) {
    return;
  }

  const button = document.createElement('button');

  button.id = 'installPwaButton';

  button.innerHTML = `

    <div class="install-button-content">

      <i data-lucide="smartphone"></i>

      <span>Instalar App</span>

    </div>

  `;

  button.className = 'install-pwa-button';

  button.addEventListener(
    'click',
    async () => {

      if (!deferredPrompt) {

        showManualInstallInstructions();

        return;

      }

      deferredPrompt.prompt();

      const result =
        await deferredPrompt.userChoice;

      if (
        result.outcome === 'accepted'
      ) {

        console.log(
          'PWA instalado com sucesso'
        );

      } else {

        console.log(
          'Usuário cancelou instalação'
        );

      }

      deferredPrompt = null;

      hideInstallButton();

    }
  );

  document.body.appendChild(button);

  if (window.lucide) {

    lucide.createIcons();

  }

}

function hideInstallButton() {

  const button =
    document.getElementById(
      'installPwaButton'
    );

  if (button) {

    button.remove();

  }

}

function showManualInstallInstructions() {

  const isIOS =
    /iphone|ipad|ipod/i.test(
      navigator.userAgent
    );

  let message = '';

  if (isIOS) {

    message = `

      Para instalar no iPhone:

      1. Toque no botão Compartilhar
      2. Depois em "Adicionar à Tela de Início"

    `;

  } else {

    message = `

      Seu navegador não liberou a instalação automática.

      Use o menu do navegador e escolha:
      "Instalar aplicativo"

    `;

  }

  alert(message);

}

function isStandaloneMode() {

  return (
    window.matchMedia(
      '(display-mode: standalone)'
    ).matches
    ||
    window.navigator.standalone === true
  );

}

function registerInstallPrompt() {

  window.addEventListener(
    'beforeinstallprompt',
    (event) => {

      event.preventDefault();

      deferredPrompt = event;

      if (!isStandaloneMode()) {

        createInstallButton();

      }

    }
  );

}

function registerInstalledEvent() {

  window.addEventListener(
    'appinstalled',
    () => {

      console.log(
        'PWA instalado'
      );

      hideInstallButton();

    }
  );

}

function registerServiceWorker() {

  if (
    'serviceWorker' in navigator
  ) {

    window.addEventListener(
      'load',
      async () => {

        try {

          const registration =
            await navigator.serviceWorker.register(
              './sw.js'
            );

          console.log(
            'Service Worker registrado:',
            registration
          );

        } catch (error) {

          console.error(
            'Erro ao registrar Service Worker:',
            error
          );

        }

      }
    );

  }

}

function createUpdateBanner() {

  if (
    document.getElementById(
      'updateBanner'
    )
  ) {
    return;
  }

  const banner =
    document.createElement('div');

  banner.id = 'updateBanner';

  banner.className =
    'update-banner';

  banner.innerHTML = `

    <div class="update-banner-content">

      <div>

        <strong>
          Nova versão disponível
        </strong>

        <p>
          Atualize o Cutelaria OS
        </p>

      </div>

      <button id="reloadAppButton">

        Atualizar

      </button>

    </div>

  `;

  document.body.appendChild(
    banner
  );

  document
    .getElementById(
      'reloadAppButton'
    )
    .addEventListener(
      'click',
      () => {

        window.location.reload();

      }
    );

}

function watchServiceWorkerUpdates() {

  if (
    !('serviceWorker' in navigator)
  ) {
    return;
  }

  navigator.serviceWorker
    .getRegistrations()
    .then((registrations) => {

      registrations.forEach(
        (registration) => {

          registration.addEventListener(
            'updatefound',
            () => {

              createUpdateBanner();

            }
          );

        }
      );

    });

}

export function initPWA() {

  registerServiceWorker();

  registerInstallPrompt();

  registerInstalledEvent();

  watchServiceWorkerUpdates();

}