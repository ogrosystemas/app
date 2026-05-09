let deferredPrompt = null;

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
    top-5
    right-5

    z-[9999]

    flex
    items-center
    gap-3

    px-5
    py-4

    rounded-2xl

    border
    border-orange-500/20

    bg-orange-500/10

    backdrop-blur-xl

    shadow-2xl

    text-orange-300

    font-semibold

    transition-all
    duration-200

    hover:scale-[1.03]
    hover:bg-orange-500/20

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

  if (window.lucide) {

    lucide.createIcons();

  }

  button.addEventListener(
    'click',
    async () => {

      if (!deferredPrompt) {

        return;

      }

      deferredPrompt.prompt();

      const result =
        await deferredPrompt.userChoice;

      if (
        result.outcome ===
        'accepted'
      ) {

        button.remove();

      }

      deferredPrompt = null;

    }
  );

}

// ========================================
// BEFORE INSTALL PROMPT
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
// UPDATE AVAILABLE
// ========================================

if (
  'serviceWorker' in navigator
) {

  navigator.serviceWorker
    .register(
      './sw.js'
    )
    .then((registration) => {

      registration.addEventListener(
        'updatefound',
        () => {

          const newWorker =
            registration.installing;

          if (!newWorker) {

            return;

          }

          newWorker.addEventListener(
            'statechange',
            () => {

              if (
                newWorker.state ===
                'installed'
              ) {

                if (
                  navigator.serviceWorker.controller
                ) {

                  showUpdateToast();

                }

              }

            }
          );

        }
      );

    })
    .catch((error) => {

      console.error(
        'Erro SW:',
        error
      );

    });

}

// ========================================
// UPDATE TOAST
// ========================================

function showUpdateToast() {

  if (
    document.getElementById(
      'updateToast'
    )
  ) {

    return;

  }

  const toast =
    document.createElement(
      'div'
    );

  toast.id =
    'updateToast';

  toast.className = `

    fixed
    top-24
    right-5

    z-[9999]

    w-[320px]

    rounded-3xl

    border
    border-blue-500/20

    bg-slate-950/90

    backdrop-blur-xl

    shadow-2xl

    p-5

  `;

  toast.innerHTML = `

    <div class="
      flex
      items-start
      gap-4
    ">

      <div class="
        w-12
        h-12

        rounded-2xl

        bg-blue-500/10

        border
        border-blue-500/20

        flex
        items-center
        justify-center
      ">

        <i
          data-lucide="download"
          class="
            w-6
            h-6
            text-blue-400
          "
        ></i>

      </div>

      <div class="
        flex-1
      ">

        <h3 class="
          font-bold
          text-lg
          mb-1
        ">

          Nova versão disponível

        </h3>

        <p class="
          text-slate-400
          text-sm
          mb-4
        ">

          Atualize o app para aplicar melhorias e correções.

        </p>

        <button
          id="reloadAppButton"
          class="
            primary-button
            w-full
          "
        >

          Atualizar agora

        </button>

      </div>

    </div>

  `;

  document.body.appendChild(
    toast
  );

  if (window.lucide) {

    lucide.createIcons();

  }

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