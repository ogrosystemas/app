let deferredPrompt = null;

// ========================================
// INIT
// ========================================

export function initPWA() {

  initInstallPrompt();

  initServiceWorker();

}

// ========================================
// INSTALL PROMPT
// ========================================

function initInstallPrompt() {

  window.addEventListener(
    'beforeinstallprompt',
    (event) => {

      event.preventDefault();

      deferredPrompt =
        event;

      createInstallButton();

    }
  );

  window.addEventListener(
    'appinstalled',
    () => {

      const button =
        document.getElementById(
          'installPwaButton'
        );

      if (button) {

        button.remove();

      }

    }
  );

}

// ========================================
// INSTALL BUTTON
// ========================================

function createInstallButton() {

  if (
    document.getElementById(
      'installPwaButton'
    )
  ) {

    return;

  }

  const button =
    document.createElement(
      'button'
    );

  button.id =
    'installPwaButton';

  button.className = `

    fixed
    top-4
    right-4

    z-[9999]

    flex
    items-center
    gap-2

    px-4
    py-3

    rounded-2xl

    bg-orange-500
    text-white

    font-semibold

    shadow-2xl

  `;

  button.innerHTML = `

    <i
      data-lucide="smartphone"
      class="w-5 h-5"
    ></i>

    <span>

      Instalar App

    </span>

  `;

  document.body.appendChild(
    button
  );

  if (
    window.lucide
  ) {

    lucide.createIcons();

  }

  button.addEventListener(
    'click',
    async () => {

      if (
        !deferredPrompt
      ) {

        return;

      }

      deferredPrompt.prompt();

      await deferredPrompt.userChoice;

      deferredPrompt = null;

    }
  );

}

// ========================================
// SERVICE WORKER
// ========================================

function initServiceWorker() {

  if (
    !(
      'serviceWorker' in
      navigator
    )
  ) {

    return;

  }

  window.addEventListener(
    'load',
    async () => {

      try {

        const registration =
          await navigator
            .serviceWorker
            .register(
              './sw.js'
            );

        console.log(
          'SW registrado:',
          registration
        );

      } catch (error) {

        console.error(
          'Erro SW:',
          error
        );

      }

    }
  );

}