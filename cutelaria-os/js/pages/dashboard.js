import { db } from '../database/db.js';

// ========================================
// FORMAT
// ========================================

function formatMoney(value = 0) {

  return Number(value)
    .toLocaleString(
      'pt-BR',
      {

        style: 'currency',

        currency: 'BRL'

      }
    );

}

// ========================================
// PAGE
// ========================================

export async function dashboardPage() {

  // ========================================
  // LOAD DATA
  // ========================================

  const pedidos =
    db.pedidos
      ? await db.pedidos.toArray()
      : [];

  const producao =
    db.producao
      ? await db.producao.toArray()
      : [];

  const clientes =
    db.clientes
      ? await db.clientes.toArray()
      : [];

  const financeiro =
    db.financeiro
      ? await db.financeiro.toArray()
      : [];

  const composicoes =
    db.composicoes
      ? await db.composicoes.toArray()
      : [];

  // ========================================
  // METRICS
  // ========================================

  const faturamento =

    financeiro.reduce(
      (acc, item) => {

        return (
          acc +
          Number(
            item.valor || 0
          )
        );

      },
      0
    );

  const lucroEstimado =

    faturamento * 0.42;

  const producoesAtivas =

    producao.filter(item =>

      item.status !==
      'Finalizada'

    ).length;

  const pedidosPendentes =

    pedidos.filter(item =>

      item.status !==
      'Entregue'

    ).length;

  // ========================================
  // RECENTES
  // ========================================

  const producoesRecentes =

    [...producao]
      .reverse()
      .slice(0, 5);

  // ========================================
  // RETURN
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

            Dashboard

          </h1>

          <p class="
            text-slate-400
            text-lg
          ">

            Visão geral da oficina

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
            data-lucide="layout-dashboard"
            class="
              w-10
              h-10
              text-white
            "
          ></i>

        </div>

      </div>

      <!-- STATS -->

      <div class="
        grid
        md:grid-cols-2
        xl:grid-cols-4
        gap-5
        mb-8
      ">

        <!-- FATURAMENTO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-4
          ">

            <div class="
              w-14
              h-14

              rounded-2xl

              bg-emerald-500/10

              border
              border-emerald-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="wallet"
                class="
                  w-7
                  h-7
                  text-emerald-400
                "
              ></i>

            </div>

            <span class="
              text-xs
              text-emerald-400
              font-bold
            ">

              Receita

            </span>

          </div>

          <p class="
            text-slate-400
            text-sm
            mb-2
          ">

            Faturamento total

          </p>

          <h2 class="
            text-3xl
            font-black
          ">

            ${formatMoney(
              faturamento
            )}

          </h2>

        </div>

        <!-- LUCRO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-4
          ">

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
                data-lucide="badge-dollar-sign"
                class="
                  w-7
                  h-7
                  text-orange-400
                "
              ></i>

            </div>

            <span class="
              text-xs
              text-orange-400
              font-bold
            ">

              Lucro

            </span>

          </div>

          <p class="
            text-slate-400
            text-sm
            mb-2
          ">

            Lucro estimado

          </p>

          <h2 class="
            text-3xl
            font-black
          ">

            ${formatMoney(
              lucroEstimado
            )}

          </h2>

        </div>

        <!-- PRODUÇÃO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-4
          ">

            <div class="
              w-14
              h-14

              rounded-2xl

              bg-blue-500/10

              border
              border-blue-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="hammer"
                class="
                  w-7
                  h-7
                  text-blue-400
                "
              ></i>

            </div>

            <span class="
              text-xs
              text-blue-400
              font-bold
            ">

              Produção

            </span>

          </div>

          <p class="
            text-slate-400
            text-sm
            mb-2
          ">

            Produções ativas

          </p>

          <h2 class="
            text-3xl
            font-black
          ">

            ${producoesAtivas}

          </h2>

        </div>

        <!-- PEDIDOS -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-4
          ">

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

            <span class="
              text-xs
              text-purple-400
              font-bold
            ">

              Pedidos

            </span>

          </div>

          <p class="
            text-slate-400
            text-sm
            mb-2
          ">

            Pendentes

          </p>

          <h2 class="
            text-3xl
            font-black
          ">

            ${pedidosPendentes}

          </h2>

        </div>

      </div>

      <!-- GRID -->

      <div class="
        grid
        xl:grid-cols-3
        gap-5
      ">

        <!-- PRODUÇÃO RECENTE -->

        <div class="
          card
          xl:col-span-2
        ">

          <div class="
            flex
            items-center
            justify-between
            mb-6
          ">

            <div>

              <h2 class="
                text-2xl
                font-black
                mb-1
              ">

                Produção recente

              </h2>

              <p class="
                text-slate-400
              ">

                Últimas movimentações

              </p>

            </div>

            <div class="
              w-12
              h-12

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
                  w-6
                  h-6
                  text-orange-400
                "
              ></i>

            </div>

          </div>

          <div class="
            grid
            gap-4
          ">

            ${producoesRecentes.length
              ? producoesRecentes.map(item => `

                <div class="
                  border
                  border-white/5

                  rounded-2xl

                  p-5

                  bg-white/[0.02]
                ">

                  <div class="
                    flex
                    items-center
                    justify-between
                    mb-4
                  ">

                    <div>

                      <h3 class="
                        font-bold
                        text-lg
                        mb-1
                      ">

                        ${item.nome || 'Faca artesanal'}

                      </h3>

                      <p class="
                        text-slate-400
                        text-sm
                      ">

                        ${item.status || 'Em produção'}

                      </p>

                    </div>

                    <span class="
                      text-orange-400
                      font-bold
                    ">

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

              `).join('')
              : `

                <div class="
                  text-center
                  py-16
                ">

                  <i
                    data-lucide="anvil"
                    class="
                      w-14
                      h-14
                      text-slate-600
                      mx-auto
                      mb-4
                    "
                  ></i>

                  <h3 class="
                    text-xl
                    font-bold
                    mb-2
                  ">

                    Nenhuma produção

                  </h3>

                  <p class="
                    text-slate-400
                  ">

                    Crie sua primeira produção.

                  </p>

                </div>

              `
            }

          </div>

        </div>

        <!-- RESUMO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-6
          ">

            <div>

              <h2 class="
                text-2xl
                font-black
                mb-1
              ">

                Resumo

              </h2>

              <p class="
                text-slate-400
              ">

                Oficina

              </p>

            </div>

            <div class="
              w-12
              h-12

              rounded-2xl

              bg-orange-500/10

              border
              border-orange-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="activity"
                class="
                  w-6
                  h-6
                  text-orange-400
                "
              ></i>

            </div>

          </div>

          <div class="
            grid
            gap-5
          ">

            <div>

              <p class="
                text-slate-400
                text-sm
                mb-2
              ">

                Clientes cadastrados

              </p>

              <h3 class="
                text-3xl
                font-black
              ">

                ${clientes.length}

              </h3>

            </div>

            <div>

              <p class="
                text-slate-400
                text-sm
                mb-2
              ">

                Composições criadas

              </p>

              <h3 class="
                text-3xl
                font-black
              ">

                ${composicoes.length}

              </h3>

            </div>

            <div>

              <p class="
                text-slate-400
                text-sm
                mb-2
              ">

                Produções registradas

              </p>

              <h3 class="
                text-3xl
                font-black
              ">

                ${producao.length}

              </h3>

            </div>

          </div>

        </div>

      </div>

    </section>

  `;

}