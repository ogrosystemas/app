export function emptyState({

  icon = 'package',

  title = 'Nenhum registro encontrado',

  description = 'Comece adicionando informações ao sistema.',

  buttonText = '',

  buttonId = ''

}) {

  return `

    <div class="
      card

      flex
      flex-col
      items-center
      justify-center

      text-center

      py-14
      px-6
    ">

      <div class="
        w-24
        h-24

        rounded-[28px]

        flex
        items-center
        justify-center

        bg-orange-500/10

        border
        border-orange-500/20

        mb-6
      ">

        <i
          data-lucide="${icon}"
          class="
            w-12
            h-12
            text-orange-400
          "
        ></i>

      </div>

      <h2 class="
        text-2xl
        font-black
        mb-3
      ">

        ${title}

      </h2>

      <p class="
        text-slate-400
        max-w-md
        leading-relaxed
        mb-8
      ">

        ${description}

      </p>

      ${buttonText
        ? `

          <button
            id="${buttonId}"
            class="primary-button"
          >

            ${buttonText}

          </button>

        `
        : ''
      }

    </div>

  `;

}