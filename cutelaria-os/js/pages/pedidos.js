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

export async function pedidosPage() {

  const pedidos =
    db.pedidos
      ? await db.pedidos.toArray()
      : [];

  // ========================================
  // EMPTY
  // ========================================

  if (!pedidos.length) {

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

              Pedidos

            </h1>

            <p class="
              text-slate-400
              text-lg
            ">

              Gestão comercial

            </p>

          </div>

          <button
            id="newPedidoButton"
            class="primary-button"
          >

            Novo pedido

          </button>

        </div>

        ${emptyState({

          icon: 'shopping-bag',

          title:
            'Nenhum pedido cadastrado',

          description:
            'Crie pedidos para organizar sua produção e vendas.'

        })}

      </section>

    `;

  }

  // ========================================
  // LIST
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

            Pedidos

          </h1>

          <p class="
            text-slate-400
            text-lg
          ">

            Gestão comercial

          </p>

        </div>

        <button
          id="newPedidoButton"
          class="primary-button"
        >

          Novo pedido

        </button>

      </div>

      <!-- GRID -->

      <div class="
        grid
        gap-5
      ">

        ${pedidos.map(item => `

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
                  font-black
                  mb-1
                ">

                  ${item.nome || 'Pedido'}

                </h2>

                <p class="
                  text-slate-400
                ">

                  ${item.cliente || 'Cliente não informado'}

                </p>

              </div>

              <div class="
                w-14
                h-14

                rounded-2xl

                bg-purple-500/10

                border
                border-purple-500/20

                flex
                items-center
                justify-center
              ">

                <i
                  data-lucide="shopping-bag"
                  class="
                    w-7
                    h-7
                    text-purple-400
                  "
                ></i>

              </div>

            </div>

            <!-- INFO -->

            <div class="
              grid
              md:grid-cols-3
              gap-4
              mb-5
            ">

              <div>

                <p class="
                  text-slate-400
                  text-sm
                  mb-1
                ">

                  Valor

                </p>

                <h3 class="
                  text-xl
                  font-black
                ">

                  ${Number(
                    item.valor || 0
                  ).toLocaleString(
                    'pt-BR',
                    {

                      style: 'currency',

                      currency: 'BRL'

                    }
                  )}

                </h3>

              </div>

              <div>

                <p class="
                  text-slate-400
                  text-sm
                  mb-1
                ">

                  Status

                </p>

                <h3 class="
                  text-xl
                  font-black
                ">

                  ${item.status || 'Aberto'}

                </h3>

              </div>

              <div>

                <p class="
                  text-slate-400
                  text-sm
                  mb-1
                ">

                  Prazo

                </p>

                <h3 class="
                  text-xl
                  font-black
                ">

                  ${item.prazo || '--'}

                </h3>

              </div>

            </div>

            <!-- FOOTER -->

            <div class="
              flex
              items-center
              justify-between
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
                  detailsPedidoButton

                  px-4
                  py-2

                  rounded-xl

                  bg-purple-500/10

                  border
                  border-purple-500/20

                  text-purple-400

                  font-semibold
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

function createPedidoModal() {

  openModal({

    title:
      'Novo Pedido',

    size:
      'md',

    content: `

      <form
        id="newPedidoForm"
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

            Nome do pedido

          </label>

          <input
            type="text"

            id="pedidoNome"

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

            Cliente

          </label>

          <input
            type="text"

            id="pedidoCliente"

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
              Nome do cliente
            "
          >

        </div>

        <div>

          <label class="
            block
            mb-2
            font-semibold
          ">

            Valor

          </label>

          <input
            type="number"

            id="pedidoValor"

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
              0.00
            "
          >

        </div>

        <div>

          <label class="
            block
            mb-2
            font-semibold
          ">

            Prazo

          </label>

          <input
            type="date"

            id="pedidoPrazo"

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

          Criar pedido

        </button>

      </form>

    `

  });

  const form =
    document.getElementById(
      'newPedidoForm'
    );

  form.addEventListener(
    'submit',
    async (event) => {

      event.preventDefault();

      try {

        const nome =
          document
            .getElementById(
              'pedidoNome'
            )
            .value
            .trim();

        const cliente =
          document
            .getElementById(
              'pedidoCliente'
            )
            .value
            .trim();

        const valor =
          Number(
            document
              .getElementById(
                'pedidoValor'
              )
              .value
          );

        const prazo =
          document
            .getElementById(
              'pedidoPrazo'
            )
            .value;

        if (!nome) {

          showToast({

            type: 'error',

            message:
              'Informe o nome do pedido.'

          });

          return;

        }

        await db.pedidos.add({

          nome,

          cliente,

          valor,

          prazo,

          status:
            'Aberto',

          createdAt:
            new Date().toISOString()

        });

        closeModal();

        showToast({

          message:
            'Pedido criado com sucesso.'

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
            'Erro ao criar pedido.'

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

    // NOVO PEDIDO

    if (
      event.target.id ===
      'newPedidoButton'
    ) {

      createPedidoModal();

    }

    // DETALHES

    if (
      event.target.classList.contains(
        'detailsPedidoButton'
      )
    ) {

      const id =
        Number(
          event.target.dataset.id
        );

      const item =
        await db.pedidos.get(id);

      if (!item) {

        return;

      }

      openModal({

        title:
          item.nome ||

          'Pedido',

        size:
          'md',

        content: `

          <div class="
            grid
            gap-5
          ">

            <div class="card">

              <p class="
                text-slate-400
                mb-2
              ">

                Cliente

              </p>

              <h3 class="
                text-2xl
                font-black
              ">

                ${item.cliente || '--'}

              </h3>

            </div>

            <div class="card">

              <p class="
                text-slate-400
                mb-2
              ">

                Valor

              </p>

              <h3 class="
                text-2xl
                font-black
              ">

                ${Number(
                  item.valor || 0
                ).toLocaleString(
                  'pt-BR',
                  {

                    style: 'currency',

                    currency: 'BRL'

                  }
                )}

              </h3>

            </div>

            <div class="card">

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

                ${item.status || '--'}

              </h3>

            </div>

            <div class="card">

              <p class="
                text-slate-400
                mb-2
              ">

                Prazo

              </p>

              <h3 class="
                text-2xl
                font-black
              ">

                ${item.prazo || '--'}

              </h3>

            </div>

          </div>

        `

      });

    }

  }
);