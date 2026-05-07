import { db } from '../database/db.js';

import {
  showToast
} from '../modules/toast.js';

const STATUS = [
  'fila',
  'projeto',
  'forja',
  'desbaste',
  'acabamento',
  'entrega'
];

const STATUS_LABEL = {

  fila: 'Fila',

  projeto: 'Projeto',

  forja: 'Forja',

  desbaste: 'Desbaste',

  acabamento: 'Acabamento',

  entrega: 'Entrega'

};

export async function timelinePage() {

  const pedidos =
    await db.pedidos
      .orderBy('createdAt')
      .reverse()
      .toArray();

  return `
    <section class="pb-32">

      <div class="mb-6">

        <h1 class="text-3xl font-black">

          Produção

        </h1>

        <p class="text-slate-400 mt-2">

          Timeline operacional da oficina

        </p>

      </div>

      <div class="grid gap-5">

        ${STATUS.map(status => {

          const itens =
            pedidos.filter(
              item =>
                item.status === status
            );

          return `

            <div class="card">

              <div class="
                flex
                justify-between
                items-center
                mb-5
              ">

                <h2 class="
                  text-xl
                  font-bold
                ">

                  ${STATUS_LABEL[status]}

                </h2>

                <div class="
                  bg-orange-500/20
                  text-orange-400
                  px-3
                  py-1
                  rounded-full
                  text-sm
                  font-bold
                ">

                  ${itens.length}

                </div>

              </div>

              <div class="grid gap-4">

                ${itens.length
                  ? itens.map(item => `

                    <div class="
                      bg-slate-900/60
                      border
                      border-slate-700
                      rounded-2xl
                      p-4
                    ">

                      <div class="
                        flex
                        justify-between
                        items-start
                        mb-4
                      ">

                        <div>

                          <h3 class="
                            text-lg
                            font-bold
                          ">

                            ${item.titulo}

                          </h3>

                          <p class="
                            text-slate-400
                            text-sm
                            mt-1
                          ">

                            Pedido #${item.id}

                          </p>

                        </div>

                        <div class="
                          text-orange-400
                          font-black
                          text-lg
                        ">

                          R$ ${item.valor.toFixed(2)}

                        </div>

                      </div>

                      <!-- PROGRESS -->

                      <div class="mb-4">

                        <div class="
                          flex
                          justify-between
                          text-sm
                          mb-2
                        ">

                          <span class="text-slate-400">
                            Progresso
                          </span>

                          <span>

                            ${item.progresso || 0}%

                          </span>

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
                              bg-orange-500
                            "
                            style="
                              width:
                              ${item.progresso || 0}%
                            "
                          ></div>

                        </div>

                      </div>

                      <!-- INFO -->

                      <div class="
                        grid
                        gap-2
                        text-sm
                        mb-5
                      ">

                        <div class="
                          flex
                          justify-between
                        ">

                          <span class="
                            text-slate-400
                          ">
                            Prioridade
                          </span>

                          <span>

                            ${
                              item.prioridade ||
                              'Normal'
                            }

                          </span>

                        </div>

                        <div class="
                          flex
                          justify-between
                        ">

                          <span class="
                            text-slate-400
                          ">
                            Entrega
                          </span>

                          <span>

                            ${
                              item.entregaPrevista ||
                              '-'
                            }

                          </span>

                        </div>

                      </div>

                      <!-- ACTIONS -->

                      <div class="
                        grid
                        grid-cols-2
                        gap-3
                      ">

                        <button
                          class="
                            primary-button
                            avancar-btn
                          "
                          data-id="${item.id}"
                        >
                          Avançar
                        </button>

                        <button
                          class="
                            primary-button
                            timeline-btn
                          "
                          data-id="${item.id}"
                        >
                          Timeline
                        </button>

                      </div>

                    </div>

                  `).join('')
                  : `
                    <div class="
                      text-slate-500
                      text-center
                      py-6
                    ">

                      Nenhum pedido

                    </div>
                  `
                }

              </div>

            </div>

          `;

        }).join('')}

      </div>

    </section>
  `;
}

window.addEventListener(
  'click',
  async (e) => {

    // AVANÇAR STATUS

    if (
      e.target.classList.contains(
        'avancar-btn'
      )
    ) {

      const pedidoId =
        Number(
          e.target.dataset.id
        );

      const pedido =
        await db.pedidos.get(
          pedidoId
        );

      const atual =
        STATUS.indexOf(
          pedido.status
        );

      if (
        atual <
        STATUS.length - 1
      ) {

        const novoStatus =
          STATUS[atual + 1];

        const progresso =
          Math.round(
            (
              (atual + 1) /
              (
                STATUS.length - 1
              )
            ) * 100
          );

        await db.pedidos.update(
          pedidoId,
          {

            status:
              novoStatus,

            progresso

          }
        );

        await db.timeline.add({

          pedidoId,

          etapa:
            STATUS_LABEL[
              novoStatus
            ],

          descricao:
            `Pedido avançou para ${STATUS_LABEL[novoStatus]}`,

          concluido: true,

          createdAt:
            new Date().toISOString()

        });

        showToast(
          'Status atualizado!'
        );

        setTimeout(() => {

          location.reload();

        }, 600);

      }

    }

    // TIMELINE

    if (
      e.target.classList.contains(
        'timeline-btn'
      )
    ) {

      const pedidoId =
        Number(
          e.target.dataset.id
        );

      const timeline =
        await db.timeline
          .where('pedidoId')
          .equals(pedidoId)
          .reverse()
          .sortBy('createdAt');

      const html =
        timeline.map(item => `

          <div style="
            margin-bottom:20px;
            padding-bottom:20px;
            border-bottom:
            1px solid #334155;
          ">

            <div style="
              font-weight:700;
              margin-bottom:8px;
            ">

              ${item.etapa}

            </div>

            <div style="
              color:#94a3b8;
              margin-bottom:8px;
            ">

              ${item.descricao}

            </div>

            <div style="
              color:#64748b;
              font-size:12px;
            ">

              ${
                new Date(
                  item.createdAt
                ).toLocaleString()
              }

            </div>

          </div>

        `).join('');

      const modal =
        document.createElement(
          'div'
        );

      modal.innerHTML = `
        <div style="
          position:fixed;
          inset:0;
          background:rgba(0,0,0,.8);
          z-index:9999;
          padding:24px;
          overflow:auto;
        ">

          <div style="
            background:#0f172a;
            border-radius:24px;
            padding:24px;
            max-width:700px;
            margin:auto;
          ">

            <div style="
              display:flex;
              justify-content:space-between;
              align-items:center;
              margin-bottom:24px;
            ">

              <h2 style="
                font-size:24px;
                font-weight:800;
              ">

                Timeline

              </h2>

              <button
                id="closeTimeline"
                style="
                  color:white;
                  font-size:28px;
                "
              >
                ×
              </button>

            </div>

            ${html || 'Sem registros'}

          </div>

        </div>
      `;

      document.body.appendChild(
        modal
      );

      document
        .getElementById(
          'closeTimeline'
        )
        .onclick = () =>
          modal.remove();

    }

  }
);