import { db } from '../database/db.js';

import {
  emptyState
} from '../components/empty-state.js';

import {
  showToast
} from '../components/toast.js';

// ========================================
// PAGE
// ========================================

export async function producaoPage() {

  const producao =
    db.producao
      ? await db.producao.toArray()
      : [];

  // ========================================
  // EMPTY STATE
  // ========================================

  if (!producao.length) {

    return `

      <section class="pb-32">

        <!-- HERO -->

        <div class="
          flex
          items-center
          justify-between
          mb-8
        ">

          <div>

            <h1 class="
              text-4xl
              font-black
              mb-2
            ">

              Produção

            </h1>

            <p class="
              text-slate-400
              text-lg
            ">

              Controle da oficina

            </p>

          </div>

          <div class="
            w-20
            h-20
            rounded-[28px]

            flex
            items-center
            justify-center

            bg-gradient-to-br
            from-orange-500
            to-orange-700

            shadow-2xl
          ">

            <i
              data-lucide="hammer"
              class="w-10 h-10 text-white"
            ></i>

          </div>

        </div>

        ${emptyState({

          icon: 'anvil',

          title:
            'Nenhuma faca em produção',

          description:
            'Inicie uma nova produção para acompanhar o andamento da oficina.',

          buttonText:
            'Nova produção',

          buttonId:
            'newProductionButton'

        })}

      </section>

    `;

  }

  // ========================================
  // LISTA
  // ========================================

  return `

    <section class="pb-32">

      <!-- HERO -->

      <div class="
        flex
        items-center
        justify-between
        mb-8
      ">

        <div>

          <h1 class="
            text-4xl
            font-black
            mb-2
          ">

            Produção

          </h1>

          <p class="
            text-slate-400
            text-lg
          ">

            Controle da oficina

          </p>

        </div>

        <button
          id="newProductionButton"
          class="primary-button"
        >

          Nova produção

        </button>

      </div>

      <!-- GRID -->

      <div class="
        grid
        gap-5
      ">

        ${producao.map(item => `

          <div class="card">

            <!-- HEADER -->

            <div class="
              flex
              items-center
              justify-between
              mb-5
            ">

              <div>

                <h2 class="
                  text-2xl
                  font-bold
                  mb-1
                ">

                  ${item.nome || 'Faca artesanal'}

                </h2>

                <p class="
                  text-slate-400
                ">

                  ${item.status || 'Em produção'}

                </p>

              </div>

              <div class="
                w-14
                h-14

                rounded-2xl

                bg-orange-500/10

                border
                border-orange-500/20

                flex
                items-center
                justify-center
              ">

                <i
                  data-lucide="flame"
                  class="
                    w-7
                    h-7
                    text-orange-400
                  "
                ></i>

              </div>

            </div>

            <!-- PROGRESS -->

            <div class="mb-3">

              <div class="
                flex
                items-center
                justify-between

                text-sm

                mb-2
              ">

                <span class="
                  text-slate-400
                ">

                  Progresso

                </span>

                <strong>

                  ${item.progresso || 0}%

                </strong>

              </div>

              <div class="
                h-3

                bg-slate-800

                rounded-full

                overflow-hidden
              ">

                <div
                  class="
                    h-full

                    bg-gradient-to-r
                    from-orange-500
                    to-orange-400

                    rounded-full
                  "
                  style="
                    width:
                    ${item.progresso || 0}%
                  "
                ></div>

              </div>

            </div>

            <!-- FOOTER -->

            <div class="
              mt-5

              flex
              items-center
              justify-between
            ">

              <small class="
                text-slate-500
              ">

                Criado em:
                ${
                  item.createdAt
                    ? new Date(
                        item.createdAt
                      ).toLocaleDateString(
                        'pt-BR'
                      )
                    : '--'
                }

              </small>

              <button
                class="
                  text-orange-400
                  text-sm
                  font-semibold
                "
              >

                Ver detalhes

              </button>

            </div>

          </div>

        `).join('')}

      </div>

    </section>

  `;

}

// ========================================
// NOVA PRODUÇÃO
// ========================================

window.addEventListener(
  'click',
  async (event) => {

    if (
      event.target.id !==
      'newProductionButton'
    ) {

      return;

    }

    try {

      const nome = prompt(
        'Nome da produção:'
      );

      if (!nome) {

        return;

      }

      await db.producao.add({

        nome,

        status:
          'Iniciada',

        progresso: 5,

        createdAt:
          new Date().toISOString()

      });

      showToast({

        message:
          'Produção criada com sucesso.'

      });

      setTimeout(() => {

        window.location.reload();

      }, 600);

    } catch (error) {

      console.error(
        error
      );

      showToast({

        type: 'error',

        message:
          'Erro ao criar produção.'

      });

    }

  }
);