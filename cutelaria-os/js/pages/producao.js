import { db } from '../database/db.js';

import {
  emptyState
} from '../components/empty-state.js';

import {
  showToast
} from '../components/toast.js';

import {
  openModal,
  closeModal
} from '../components/modal.js';

// ========================================
// PAGE
// ========================================

export async function producaoPage() {

  const producao =
    db.producao
      ? await db.producao.toArray()
      : [];

  // ========================================
  // EMPTY
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

          <button
            id="newProductionButton"
            class="primary-button"
          >

            Nova produção

          </button>

        </div>

        ${emptyState({

          icon: 'anvil',

          title:
            'Nenhuma faca em produção',

          description:
            'Inicie uma nova produção para acompanhar o andamento da oficina.'

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

            <div class="mb-4">

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
              flex
              items-center
              justify-between
              gap-3
            ">

              <small class="
                text-slate-500
              ">

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
                  details-production-button

                  px-4
                  py-2

                  rounded-xl

                  bg-orange-500/10

                  border
                  border-orange-500/20

                  text-orange-400

                  font-semibold

                  transition-all

                  hover:bg-orange-500/20
                "
                data-id="${item.id}"
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
// CREATE MODAL
// ========================================

function createProductionModal() {

  openModal({

    title:
      'Nova Produção',

    size:
      'md',

    content: `

      <form
        id="newProductionForm"
        class="
          grid
          gap-5
        "
      >

        <div>

          <label class="
            block
            mb-2
            font-semibold
          ">

            Nome da produção

          </label>

          <input
            type="text"
            id="productionName"

            class="
              w-full

              bg-slate-900

              border
              border-white/10

              rounded-2xl

              px-4
              py-4

              outline-none
            "

            placeholder="
              Ex:
              Bowie artesanal
            "
          >

        </div>

        <div>

          <label class="
            block
            mb-2
            font-semibold
          ">

            Status

          </label>

          <select
            id="productionStatus"

            class="
              w-full

              bg-slate-900

              border
              border-white/10

              rounded-2xl

              px-4
              py-4

              outline-none
            "
          >

            <option>
              Iniciada
            </option>

            <option>
              Forjamento
            </option>

            <option>
              Têmpera
            </option>

            <option>
              Acabamento
            </option>

            <option>
              Finalizada
            </option>

          </select>

        </div>

        <div>

          <label class="
            block
            mb-2
            font-semibold
          ">

            Progresso inicial

          </label>

          <input
            type="number"

            id="productionProgress"

            value="5"

            min="0"
            max="100"

            class="
              w-full

              bg-slate-900

              border
              border-white/10

              rounded-2xl

              px-4
              py-4

              outline-none
            "
          >

        </div>

        <button
          type="submit"

          class="
            primary-button
            w-full
          "
        >

          Criar produção

        </button>

      </form>

    `

  });

  const form =
    document.getElementById(
      'newProductionForm'
    );

  form.addEventListener(
    'submit',
    async (event) => {

      event.preventDefault();

      try {

        const nome =
          document
            .getElementById(
              'productionName'
            )
            .value
            .trim();

        const status =
          document
            .getElementById(
              'productionStatus'
            )
            .value;

        const progresso =
          Number(
            document
              .getElementById(
                'productionProgress'
              )
              .value
          );

        if (!nome) {

          showToast({

            type: 'error',

            message:
              'Informe o nome da produção.'

          });

          return;

        }

        await db.producao.add({

          nome,

          status,

          progresso,

          createdAt:
            new Date().toISOString()

        });

        closeModal();

        showToast({

          message:
            'Produção criada com sucesso.'

        });

        setTimeout(() => {

          window.location.reload();

        }, 500);

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

}

// ========================================
// EVENTS
// ========================================

window.addEventListener(
  'click',
  async (event) => {

    // NOVA PRODUÇÃO

    if (
      event.target.id ===
      'newProductionButton'
    ) {

      createProductionModal();

    }

    // DETALHES

    if (
      event.target.classList.contains(
        'details-production-button'
      )
    ) {

      const id =
        Number(
          event.target.dataset.id
        );

      const item =
        await db.producao.get(id);

      if (!item) {

        return;

      }

      openModal({

        title:
          item.nome ||

          'Produção',

        size:
          'md',

        content: `

          <div class="
            grid
            gap-5
          ">

            <div class="
              card
            ">

              <p class="
                text-slate-400
                mb-2
              ">

                Status

              </p>

              <h3 class="
                text-2xl
                font-black
              ">

                ${item.status}

              </h3>

            </div>

            <div class="
              card
            ">

              <p class="
                text-slate-400
                mb-3
              ">

                Progresso

              </p>

              <div class="
                h-4

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
                  "
                  style="
                    width:
                    ${item.progresso || 0}%
                  "
                ></div>

              </div>

              <div class="
                mt-3

                text-right

                font-bold
                text-orange-400
              ">

                ${item.progresso || 0}%

              </div>

            </div>

            <div class="
              card
            ">

              <p class="
                text-slate-400
                mb-2
              ">

                Criado em

              </p>

              <h3 class="
                text-xl
                font-bold
              ">

                ${
                  item.createdAt
                    ? new Date(
                        item.createdAt
                      ).toLocaleString(
                        'pt-BR'
                      )
                    : '--'
                }

              </h3>

            </div>

          </div>

        `

      });

    }

  }
);