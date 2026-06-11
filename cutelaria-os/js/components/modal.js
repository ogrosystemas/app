let activeModal = null;

// ========================================
// OPEN MODAL
// ========================================

export function openModal({

  title = 'Modal',

  content = '',

  size = 'md'

}) {

  closeModal();

  const modal =
    document.createElement(
      'div'
    );

  modal.id =
    'globalModal';

  modal.className = `

    fixed
    inset-0

    z-[99999]

    flex
    items-center
    justify-center

    bg-black/70

    backdrop-blur-sm

    p-4

  `;

  const width = {

    sm: 'max-w-md',

    md: 'max-w-2xl',

    lg: 'max-w-4xl',

    xl: 'max-w-6xl'

  };

  modal.innerHTML = `

    <div class="
      w-full
      ${width[size] || width.md}

      bg-slate-950

      border
      border-white/10

      rounded-[32px]

      shadow-2xl

      overflow-hidden

      animate-[modalIn_.2s_ease]
    ">

      <!-- HEADER -->

      <div class="
        flex
        items-center
        justify-between

        px-6
        py-5

        border-b
        border-white/5
      ">

        <div>

          <h2 class="
            text-2xl
            font-black
          ">

            ${title}

          </h2>

        </div>

        <button
          id="closeModalButton"
          class="
            w-12
            h-12

            rounded-2xl

            flex
            items-center
            justify-center

            bg-white/5

            hover:bg-white/10

            transition-all
          "
        >

          <i
            data-lucide="x"
            class="w-5 h-5"
          ></i>

        </button>

      </div>

      <!-- CONTENT -->

      <div class="
        p-6
        max-h-[80vh]
        overflow-auto
      ">

        ${content}

      </div>

    </div>

  `;

  document.body.appendChild(
    modal
  );

  activeModal = modal;

  if (
    window.lucide
  ) {

    lucide.createIcons();

  }

  document
    .getElementById(
      'closeModalButton'
    )
    .addEventListener(
      'click',
      closeModal
    );

  modal.addEventListener(
    'click',
    (event) => {

      if (
        event.target === modal
      ) {

        closeModal();

      }

    }
  );

}

// ========================================
// CLOSE MODAL
// ========================================

export function closeModal() {

  if (
    activeModal
  ) {

    activeModal.remove();

    activeModal = null;

  }

}