import { db } from '../database/db.js';

import {
  showToast
} from '../modules/toast.js';

export async function clientesPage() {

  const clientes =
    await db.clientes
      .orderBy('createdAt')
      .reverse()
      .toArray();

  const pedidos =
    await db.pedidos.toArray();

  return `
    <section class="pb-32">

      <!-- KPIS -->

      <div class="grid grid-cols-3 gap-3 mb-5">

        <div class="card">

          <div class="metric-label">
            Clientes
          </div>

          <div class="metric-value">

            ${clientes.length}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Pedidos
          </div>

          <div class="metric-value">

            ${pedidos.length}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Em produção
          </div>

          <div class="metric-value text-orange-400">

            ${
              pedidos.filter(
                item =>
                  item.status ===
                  'producao'
              ).length
            }

          </div>

        </div>

      </div>

      <!-- FORM -->

      <div class="card mb-5">

        <h2 class="text-2xl font-bold mb-5">

          Novo Cliente

        </h2>

        <form id="clienteForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome"
            required
          />

          <input
            class="input"
            type="text"
            id="telefone"
            placeholder="WhatsApp"
          />

          <input
            class="input"
            type="email"
            id="email"
            placeholder="E-mail"
          />

          <input
            class="input"
            type="text"
            id="instagram"
            placeholder="Instagram"
          />

          <input
            class="input"
            type="text"
            id="cidade"
            placeholder="Cidade"
          />

          <textarea
            class="input"
            id="observacoes"
            placeholder="Observações"
          ></textarea>

          <button
            class="primary-button mt-4"
            type="submit"
          >
            Salvar Cliente
          </button>

        </form>

      </div>

      <!-- LISTA -->

      <div class="grid gap-4">

        ${await Promise.all(

          clientes.map(
            async cliente => {

              const pedidosCliente =
                pedidos.filter(
                  item =>
                    item.clienteId ===
                    cliente.id
                );

              const total =
                pedidosCliente.reduce(
                  (sum, item) =>
                    sum + item.valor,
                  0
                );

              return `

                <div class="card">

                  <div class="flex justify-between items-start mb-4">

                    <div>

                      <h3 class="text-2xl font-bold">

                        ${cliente.nome}

                      </h3>

                      <p class="text-slate-400 text-sm mt-1">

                        ${cliente.cidade || '-'}

                      </p>

                    </div>

                    <div class="text-right">

                      <div class="text-slate-400 text-sm">

                        Total em pedidos

                      </div>

                      <div class="text-orange-400 font-bold text-xl mt-1">

                        R$ ${total.toFixed(2)}

                      </div>

                    </div>

                  </div>

                  <div class="grid gap-2 text-sm">

                    <div class="flex justify-between">

                      <span class="text-slate-400">
                        WhatsApp
                      </span>

                      <span>
                        ${cliente.telefone || '-'}
                      </span>

                    </div>

                    <div class="flex justify-between">

                      <span class="text-slate-400">
                        Instagram
                      </span>

                      <span>
                        ${cliente.instagram || '-'}
                      </span>

                    </div>

                    <div class="flex justify-between">

                      <span class="text-slate-400">
                        Pedidos
                      </span>

                      <span>

                        ${pedidosCliente.length}

                      </span>

                    </div>

                  </div>

                  <div class="flex gap-3 mt-5">

                    ${
                      cliente.telefone
                        ? `
                          <a
                            href="https://wa.me/55${cliente.telefone.replace(/\D/g,'')}"
                            target="_blank"
                            class="primary-button flex-1 text-center"
                          >
                            WhatsApp
                          </a>
                        `
                        : ''
                    }

                    <button
                      class="primary-button pedido-btn"
                      data-id="${cliente.id}"
                    >
                      Novo Pedido
                    </button>

                  </div>

                </div>

              `;

            }
          )

        ).then(
          items => items.join('')
        )}

      </div>

    </section>
  `;
}

window.addEventListener(
  'submit',
  async (e) => {

    if (
      e.target.id ===
      'clienteForm'
    ) {

      e.preventDefault();

      await db.clientes.add({

        nome:
          document.getElementById(
            'nome'
          ).value,

        telefone:
          document.getElementById(
            'telefone'
          ).value,

        email:
          document.getElementById(
            'email'
          ).value,

        instagram:
          document.getElementById(
            'instagram'
          ).value,

        cidade:
          document.getElementById(
            'cidade'
          ).value,

        observacoes:
          document.getElementById(
            'observacoes'
          ).value,

        createdAt:
          new Date().toISOString()

      });

      showToast(
        'Cliente salvo!'
      );

      setTimeout(() => {

        location.reload();

      }, 700);

    }

  }
);

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.classList.contains(
        'pedido-btn'
      )
    ) {

      const clienteId =
        Number(
          e.target.dataset.id
        );

      const titulo =
        prompt(
          'Nome do pedido'
        );

      if (!titulo) return;

      const valor =
        parseFloat(
          prompt(
            'Valor do pedido'
          ) || 0
        );

      await db.pedidos.add({

        clienteId,

        titulo,

        valor,

        entrada: 0,

        restante: valor,

        status: 'producao',

        entregaPrevista: '',

        createdAt:
          new Date().toISOString()

      });

      showToast(
        'Pedido criado!'
      );

      setTimeout(() => {

        location.reload();

      }, 700);

    }

  }
);