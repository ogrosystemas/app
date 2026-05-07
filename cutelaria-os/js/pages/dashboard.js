import { db } from '../database/db.js';

export async function dashboardPage() {

  const composicoes =
    await db.composicoes.toArray();

  const materiais =
    await db.materiais.toArray();

  const financeiro =
    await db.financeiro.toArray();

  const pedidos =
    await db.pedidos.toArray();

  // KPIS

  const producoes =
    composicoes.length;

  const receita =
    composicoes.reduce(
      (total, item) =>
        total + (item.valorFinal || 0),
      0
    );

  const custos =
    composicoes.reduce(
      (total, item) =>
        total + (item.custoTotal || 0),
      0
    );

  const lucro =
    receita - custos;

  const margem =
    receita > 0
      ? ((lucro / receita) * 100)
      : 0;

  const ticketMedio =
    producoes > 0
      ? receita / producoes
      : 0;

  const estoque =
    materiais.reduce(
      (total, item) => {

        return total +
          (
            (item.valor || 0) *
            (item.estoqueAtual || 0)
          );

      },
      0
    );

  // RANKING

  const ranking =
    [...composicoes]
      .sort(
        (a, b) =>
          (b.valorFinal || 0) -
          (a.valorFinal || 0)
      )
      .slice(0, 5);

  // PEDIDOS EM PRODUÇÃO

  const producaoAtiva =
    pedidos.filter(
      item =>
        item.status !== 'entrega'
    );

  // DESPESAS

  const despesas =
    financeiro
      .filter(
        item =>
          item.tipo === 'despesa'
      )
      .reduce(
        (total, item) =>
          total + item.valor,
        0
      );

  // RECEITAS

  const receitas =
    financeiro
      .filter(
        item =>
          item.tipo === 'receita'
      )
      .reduce(
        (total, item) =>
          total + item.valor,
        0
      );

  return `

    <section class="pb-32">

      <!-- HERO -->

      <div class="mb-8">

        <div class="
          flex
          justify-between
          items-start
        ">

          <div>

            <h1 class="
              text-4xl
              font-black
              tracking-tight
            ">

              Cutelaria OS

            </h1>

            <p class="
              text-slate-400
              mt-2
            ">

              Central operacional da oficina

            </p>

          </div>

          <div class="
            bg-orange-500/10
            border
            border-orange-500/20
            text-orange-400
            px-4
            py-2
            rounded-2xl
            text-sm
            font-bold
          ">

            ERP INDUSTRIAL

          </div>

        </div>

      </div>

      <!-- KPIS -->

      <div class="
        grid
        grid-cols-2
        gap-4
        mb-5
      ">

        <div class="card">

          <div class="metric-label">

            Receita total

          </div>

          <div class="
            metric-value
            text-green-400
          ">

            R$ ${receita.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">

            Lucro líquido

          </div>

          <div class="
            metric-value
            text-orange-400
          ">

            R$ ${lucro.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">

            Produções

          </div>

          <div class="metric-value">

            ${producoes}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">

            Ticket médio

          </div>

          <div class="
            metric-value
            text-cyan-400
          ">

            R$ ${ticketMedio.toFixed(2)}

          </div>

        </div>

      </div>

      <!-- KPIS 2 -->

      <div class="
        grid
        grid-cols-2
        gap-4
        mb-5
      ">

        <div class="card">

          <div class="metric-label">

            Valor em estoque

          </div>

          <div class="
            metric-value
            text-yellow-400
          ">

            R$ ${estoque.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">

            Margem líquida

          </div>

          <div class="
            metric-value
            text-emerald-400
          ">

            ${margem.toFixed(1)}%

          </div>

        </div>

      </div>

      <!-- PRODUÇÃO -->

      <div class="card mb-5">

        <div class="
          flex
          justify-between
          items-center
          mb-5
        ">

          <h2 class="
            text-2xl
            font-bold
          ">

            Produção ativa

          </h2>

          <div class="
            text-orange-400
            font-bold
          ">

            ${producaoAtiva.length}

          </div>

        </div>

        <div class="grid gap-4">

          ${
            producaoAtiva.length

              ? producaoAtiva.map(item => `

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
                    items-center
                    mb-3
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
                      ">

                        Status:
                        ${item.status}

                      </p>

                    </div>

                    <div class="
                      text-orange-400
                      font-black
                    ">

                      R$ ${item.valor.toFixed(2)}

                    </div>

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

              `).join('')

              : `

                <div class="
                  text-slate-500
                  text-center
                  py-8
                ">

                  Nenhuma produção ativa

                </div>

              `
          }

        </div>

      </div>

      <!-- RANKING -->

      <div class="card mb-5">

        <div class="
          flex
          justify-between
          items-center
          mb-5
        ">

          <h2 class="
            text-2xl
            font-bold
          ">

            Ranking Premium

          </h2>

          <div class="
            text-slate-400
            text-sm
          ">

            Mais lucrativas

          </div>

        </div>

        <div class="grid gap-4">

          ${
            ranking.length

              ? ranking.map(
                (item, index) => `

                  <div class="
                    flex
                    justify-between
                    items-center
                    bg-slate-900/60
                    border
                    border-slate-700
                    rounded-2xl
                    p-4
                  ">

                    <div class="
                      flex
                      items-center
                      gap-4
                    ">

                      <div class="
                        w-10
                        h-10
                        rounded-full
                        bg-orange-500/20
                        flex
                        items-center
                        justify-center
                        text-orange-400
                        font-black
                      ">

                        ${index + 1}

                      </div>

                      <div>

                        <div class="
                          font-bold
                        ">

                          ${item.nome}

                        </div>

                        <div class="
                          text-slate-400
                          text-sm
                        ">

                          ${item.tipoAco || '-'}

                        </div>

                      </div>

                    </div>

                    <div class="
                      text-orange-400
                      font-black
                    ">

                      R$ ${(item.valorFinal || 0).toFixed(2)}

                    </div>

                  </div>

                `
              ).join('')

              : `

                <div class="
                  text-slate-500
                  text-center
                  py-8
                ">

                  Sem produções ainda

                </div>

              `
          }

        </div>

      </div>

      <!-- FINANCEIRO -->

      <div class="
        grid
        grid-cols-2
        gap-4
      ">

        <div class="card">

          <div class="
            text-slate-400
            text-sm
            mb-3
          ">

            Receitas extras

          </div>

          <div class="
            text-3xl
            font-black
            text-green-400
          ">

            R$ ${receitas.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="
            text-slate-400
            text-sm
            mb-3
          ">

            Despesas extras

          </div>

          <div class="
            text-3xl
            font-black
            text-red-400
          ">

            R$ ${despesas.toFixed(2)}

          </div>

        </div>

      </div>

    </section>

  `;

}