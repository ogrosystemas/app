import { db } from '../database/db.js';

// ========================================
// PAGE
// ========================================

export async function dashboardPage() {

  // ========================================
  // SAFE LOAD
  // ========================================

  const producao =
    db.producao
      ? await db.producao.toArray()
      : [];

  const pedidos =
    db.pedidos
      ? await db.pedidos.toArray()
      : [];

  const financeiro =
    db.financeiro
      ? await db.financeiro.toArray()
      : [];

  const materiais =
    db.materiais
      ? await db.materiais.toArray()
      : [];

  // ========================================
  // STATS
  // ========================================

  const producaoAtiva =
    producao.filter(item =>
      item.status !==
      'Finalizada'
    ).length;

  const pedidosAbertos =
    pedidos.filter(item =>
      item.status !==
      'Concluído'
    ).length;

  const faturamento =
    financeiro.reduce(
      (total, item) => {

        if (
          item.tipo ===
          'entrada'
        ) {

          return (
            total +
            Number(
              item.valor || 0
            )
          );

        }

        return total;

      },
      0
    );

  // ========================================
  // RECENTES
  // ========================================

  const producaoRecente =
    [...producao]
      .reverse()
      .slice(0, 4);

  const pedidosRecentes =
    [...pedidos]
      .reverse()
      .slice(0, 4);

  // ========================================
  // TEMPLATE
  // ========================================

  return `

    <section class="
      pb-32
    ">

      <!-- HERO -->

      <div class="
        mb-10
      ">

        <h1 class="
          text-5xl
          font-black
          mb-3
        ">

          Dashboard

        </h1>

        <p class="
          text-slate-400
          text-lg
        ">

          Controle completo da oficina.

        </p>

      </div>

      <!-- STATS -->

      <div class="
        grid
        md:grid-cols-2
        xl:grid-cols-4
        gap-5
        mb-10
      ">

        <!-- PRODUÇÃO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-5
          ">

            <div>

              <p class="
                text-slate-400
                mb-2
              ">

                Produção ativa

              </p>

              <h2 class="
                text-5xl
                font-black
              ">

                ${producaoAtiva}

              </h2>

            </div>

            <div class="
              w-16
              h-16

              rounded-3xl

              bg-orange-500/10

              border
              border-orange-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="hammer"
                class="
                  w-8
                  h-8
                  text-orange-400
                "
              ></i>

            </div>

          </div>

        </div>

        <!-- PEDIDOS -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-5
          ">

            <div>

              <p class="
                text-slate-400
                mb-2
              ">

                Pedidos abertos

              </p>

              <h2 class="
                text-5xl
                font-black
              ">

                ${pedidosAbertos}

              </h2>

            </div>

            <div class="
              w-16
              h-16

              rounded-3xl

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
                  w-8
                  h-8
                  text-purple-400
                "
              ></i>

            </div>

          </div>

        </div>

        <!-- MATERIAIS -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-5
          ">

            <div>

              <p class="
                text-slate-400
                mb-2
              ">

                Materiais

              </p>

              <h2 class="
                text-5xl
                font-black
              ">

                ${materiais.length}

              </h2>

            </div>

            <div class="
              w-16
              h-16

              rounded-3xl

              bg-blue-500/10

              border
              border-blue-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="package"
                class="
                  w-8
                  h-8
                  text-blue-400
                "
              ></i>

            </div>

          </div>

        </div>

        <!-- FATURAMENTO -->

        <div class="card">

          <div class="
            flex
            items-center
            justify-between
            mb-5
          ">

            <div>

              <p class="
                text-slate-400
                mb-2
              ">

                Receita total

              </p>

              <h2 class="
                text-3xl
                font-black
              ">

                ${Number(
                  faturamento
                ).toLocaleString(
                  'pt-BR',
                  {

                    style: 'currency',

                    currency: 'BRL'

                  }
                )}

              </h2>

            </div>

            <div class="
              w-16
              h-16

              rounded-3xl

              bg-green-500/10

              border
              border-green-500/20

              flex
              items-center
              justify-center
            ">

              <i
                data-lucide="wallet"
                class="
                  w-8
                  h-8
                  text-green-400
                "
              ></i>

            </div>

          </div>

        </div>

      </div>

      <!-- PRODUÇÃO RECENTE -->

      <div class="
        card
        mb-6
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

              Últimas atividades

            </p>

          </div>

          <i
            data-lucide="flame"
            class="
              w-7
              h-7
              text-orange-400
            "
          ></i>

        </div>

        ${
          producaoRecente.length

            ? `

              <div class="
                grid
                gap-4
              ">

                ${producaoRecente.map(item => `

                  <div class="
                    bg-slate-900

                    border
                    border-white/5

                    rounded-2xl

                    p-5
                  ">

                    <div class="
                      flex
                      items-center
                      justify-between
                      mb-3
                    ">

                      <div>

                        <h3 class="
                          text-xl
                          font-bold
                          mb-1
                        ">

                          ${item.nome}

                        </h3>

                        <p class="
                          text-slate-400
                        ">

                          ${item.status}

                        </p>

                      </div>

                      <strong class="
                        text-orange-400
                      ">

                        ${
                          item.progresso || 0
                        }%

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
                        "
                        style="
                          width:
                          ${
                            item.progresso || 0
                          }%
                        "
                      ></div>

                    </div>

                  </div>

                `).join('')}

              </div>

            `

            : `

              <div class="
                text-center
                py-12
              ">

                <p class="
                  text-slate-500
                ">

                  Nenhuma produção encontrada.

                </p>

              </div>

            `

        }

      </div>

      <!-- PEDIDOS -->

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

              Pedidos recentes

            </h2>

            <p class="
              text-slate-400
            ">

              Últimos pedidos cadastrados

            </p>

          </div>

          <i
            data-lucide="shopping-bag"
            class="
              w-7
              h-7
              text-purple-400
            "
          ></i>

        </div>

        ${
          pedidosRecentes.length

            ? `

              <div class="
                grid
                gap-4
              ">

                ${pedidosRecentes.map(item => `

                  <div class="
                    bg-slate-900

                    border
                    border-white/5

                    rounded-2xl

                    p-5
                  ">

                    <div class="
                      flex
                      items-center
                      justify-between
                    ">

                      <div>

                        <h3 class="
                          text-xl
                          font-bold
                          mb-1
                        ">

                          ${item.nome}

                        </h3>

                        <p class="
                          text-slate-400
                        ">

                          ${item.cliente}

                        </p>

                      </div>

                      <strong class="
                        text-purple-400
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

                      </strong>

                    </div>

                  </div>

                `).join('')}

              </div>

            `

            : `

              <div class="
                text-center
                py-12
              ">

                <p class="
                  text-slate-500
                ">

                  Nenhum pedido encontrado.

                </p>

              </div>

            `

        }

      </div>

    </section>

  `;

}