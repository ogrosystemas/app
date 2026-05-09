let toastTimeout = null;

// ========================================
// SHOW TOAST
// ========================================

export function showToast({

  message = 'Operação realizada.',

  type = 'success'

}) {

  const existing =
    document.getElementById(
      'globalToast'
    );

  if (existing) {

    existing.remove();

  }

  const toast =
    document.createElement(
      'div'
    );

  toast.id =
    'globalToast';

  toast.className = `

    fixed
    bottom-24
    left-1/2

    -translate-x-1/2

    z-[9999]

    px-5
    py-4

    rounded-2xl

    shadow-2xl

    border

    backdrop-blur-xl

    flex
    items-center
    gap-3

    min-w-[280px]
    max-w-[90vw]

    animate-[toastIn_.25s_ease]

    ${type === 'success'
      ? `
        bg-emerald-500/10
        border-emerald-500/20
        text-emerald-300
      `
      : `
        bg-red-500/10
        border-red-500/20
        text-red-300
      `
    }

  `;

  toast.innerHTML = `

    <div class="
      w-10
      h-10

      rounded-xl

      flex
      items-center
      justify-center

      ${type === 'success'
        ? 'bg-emerald-500/20'
        : 'bg-red-500/20'
      }
    ">

      <i
        data-lucide="${
          type === 'success'
            ? 'check'
            : 'x'
        }"
        class="w-5 h-5"
      ></i>

    </div>

    <div class="
      flex-1
    ">

      <strong class="
        block
        mb-1
      ">

        ${
          type === 'success'
            ? 'Sucesso'
            : 'Erro'
        }

      </strong>

      <p class="
        text-sm
        opacity-90
      ">

        ${message}

      </p>

    </div>

  `;

  document.body.appendChild(
    toast
  );

  if (window.lucide) {

    lucide.createIcons();

  }

  clearTimeout(
    toastTimeout
  );

  toastTimeout =
    setTimeout(() => {

      toast.style.opacity =
        '0';

      toast.style.transform =
        'translate(-50%,20px)';

      setTimeout(() => {

        toast.remove();

      }, 250);

    }, 2800);

}