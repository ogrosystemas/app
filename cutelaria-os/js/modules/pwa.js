let deferredPrompt = null;

// ========================================
// CREATE INSTALL BUTTON
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

    shadow-2xl

    font-semibold
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

  // LUCIDE

  if (
    window.lucide
  ) {

    lucide.createIcons();

  }

  // CLICK

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
// INSTALL PROMPT
// ========================================

window.addEventListener(
  'beforeinstallprompt',
  (event) => {

    event.preventDefault();

    deferredPrompt =
      event;

    createInstallButton();

  }
);

// ========================================
// APP INSTALLED
// ========================================

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

// ========================================
// SERVICE WORKER
// ========================================

if (
  'serviceWorker' in navigator
) {

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