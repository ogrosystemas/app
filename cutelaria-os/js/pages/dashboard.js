import { db } from '../database/db.js';

import {
  gerarPDF
} from '../services/pdf.service.js';

import {
  showLoading,
  hideLoading
} from '../modules/loading.js';

import {
  showToast
} from '../modules/toast.js';

export async function dashboardPage() {

  const composicoes =
    await db.composicoes.toArray();

  const financeiro =
    await db.financeiro.toArray();

  // PRODUÇÃO

  const totalProducoes =
    composicoes.length;

  const faturamentoProducoes =
    composicoes.reduce(
      (total, item) =>
        total + item.valorFinal,
      0
    );

  const custoProducoes =
    composicoes.reduce(
      (total, item) =>
        total + item.custoTotal,
      0
    );

  const lucroProducoes =
    faturamentoProducoes -
    custoProducoes;

  // FINANCEIRO

  const receitasExtras =
    financeiro
      .filter(
        item =>
          item.tipo ===
          'receita'
      )
      .reduce(
        (total, item) =>
          total + item.valor,
        0
      );

  const despesasExtras =
    financeiro
      .filter(
        item =>
          item.tipo ===
          'despesa'
      )
      .reduce(
        (total, item) =>
          total + item.valor,
        0
      );

  // DRE

  const receitaTotal =
    faturamentoProducoes +
    receitasExtras;

  const despesasTotais =
    custoProducoes +
    despesasExtras;

  const lucroLiquido =
    receitaTotal -
    despesasTotais;

  const margemLiquida =
    receitaTotal > 0
      ? (
          (lucroLiquido /
            receitaTotal) *
          100
        ).toFixed(1)
      : 0;

  // META

  const metaMensal = 10000;

  const percentualMeta =
    (
      (receitaTotal /
        metaMensal) *
      100
    ).toFixed(0);

  // MAIS LUCRATIVA

  let facaMaisLucrativa = null;

  composicoes.forEach(item => {

    const lucro =
      item.valorFinal -
      item.custoTotal;

    if (
      !facaMaisLucrativa ||
      lucro >
        facaMaisLucrativa.lucro
    ) {

      facaMaisLucrativa = {
        nome: item.nome,
        lucro
      };

    }

  });

  setTimeout(() => {

    renderFinanceChart(
      composicoes,
      financeiro
    );

  }, 100);

  return `
    <section class="pb-32">

      <!-- HERO KPI -->

      <div class="card mb-5">

        <div class="flex justify-between items-center">

          <div>

            <div class="text-slate-400 text-sm">
              Receita total
            </div>

            <div class="text-4xl font-black text-orange-400 mt-2">

              R$ ${receitaTotal.toFixed(2)}

            </div>

          </div>

          <div class="text-right">

            <div class="text-slate-400 text-sm">
              Lucro líquido
            </div>

            <div class="
              text-2xl
              font-bold
              ${
                lucroLiquido >= 0
                  ? 'text-green-400'
                  : 'text-red-400'
              }
            ">

              R$ ${lucroLiquido.toFixed(2)}

            </div>

          </div>

        </div>

      </div>

      <!-- KPIS -->

      <div class="grid grid-cols-2 gap-4 mb-5">

        <div class="card">

          <div class="metric-label">
            Produções
          </div>

          <div class="metric-value">

            ${totalProducoes}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Margem líquida
          </div>

          <div class="metric-value text-green-400">

            ${margemLiquida}%

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Custos totais
          </div>

          <div class="metric-value text-red-400">

            R$ ${despesasTotais.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Receita produção
          </div>

          <div class="metric-value">

            R$ ${faturamentoProducoes.toFixed(2)}

          </div>

        </div>

      </div>

      <!-- META -->

      <div class="card mb-5">

        <div class="flex justify-between mb-3">

          <span class="font-bold">
            Meta mensal
          </span>

          <span class="text-orange-400 font-bold">

            ${percentualMeta}%

          </span>

        </div>

        <div class="w-full h-4 bg-slate-700 rounded-full overflow-hidden">

          <div
            class="h-full bg-orange-500"
            style="width:${Math.min(percentualMeta,100)}%"
          ></div>

        </div>

        <div class="text-slate-400 text-sm mt-3">

          Meta atual:
          R$ ${metaMensal.toFixed(2)}

        </div>

      </div>

      <!-- CHART -->

      <div class="card mb-5">

        <div class="flex justify-between items-center mb-5">

          <h3 class="font-bold text-lg">

            Fluxo Financeiro

          </h3>

          <div class="text-slate-400 text-sm">

            DRE simplificada

          </div>

        </div>

        <canvas id="financeChart"></canvas>

      </div>

      <!-- INSIGHTS -->

      <div class="card mb-5">

        <h3 class="font-bold text-lg mb-5">

          Insights Operacionais

        </h3>

        <div class="grid gap-4">

          <div class="flex justify-between">

            <span class="text-slate-400">
              Melhor produção
            </span>

            <span class="text-green-400 font-bold">

              ${
                facaMaisLucrativa
                  ? facaMaisLucrativa.nome
                  : '-'
              }

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Receita extra
            </span>

            <span>

              R$ ${receitasExtras.toFixed(2)}

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Despesas extras
            </span>

            <span class="text-red-400">

              R$ ${despesasExtras.toFixed(2)}

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Lucro operacional
            </span>

            <span class="text-green-400 font-bold">

              R$ ${lucroProducoes.toFixed(2)}

            </span>

          </div>

        </div>

      </div>

      <!-- PRODUÇÕES -->

      <div class="grid gap-4">

        ${composicoes.reverse().map(item => {

          const lucro =
            item.valorFinal -
            item.custoTotal;

          const margem =
            (
              (lucro /
                item.valorFinal) *
              100
            ).toFixed(1);

          return `

            <div class="card overflow-hidden">

              ${
                item.fotoCapa
                  ? `
                    <img
                      src="${item.fotoCapa}"
                      class="w-full h-52 object-cover"
                    />
                  `
                  : ''
              }

              <div class="p-5">

                <div class="flex justify-between items-start mb-5">

                  <div>

                    <h3 class="text-xl font-bold">

                      ${item.nome}

                    </h3>

                    <p class="text-slate-400 text-sm mt-1">

                      ${item.tipoFaca || 'Faca'}

                    </p>

                  </div>

                  <div class="text-right">

                    <div class="text-slate-400 text-sm">

                      Valor final

                    </div>

                    <div class="text-2xl font-black text-orange-400">

                      R$ ${item.valorFinal.toFixed(2)}

                    </div>

                  </div>

                </div>

                <div class="grid gap-2 text-sm mb-5">

                  <div class="flex justify-between">

                    <span class="text-slate-400">
                      Aço
                    </span>

                    <span>
                      ${item.tipoAco || '-'}
                    </span>

                  </div>

                  <div class="flex justify-between">

                    <span class="text-slate-400">
                      Cabo
                    </span>

                    <span>
                      ${item.tipoCabo || '-'}
                    </span>

                  </div>

                  <div class="flex justify-between">

                    <span class="text-slate-400">
                      Lucro
                    </span>

                    <span class="text-green-400 font-bold">

                      R$ ${lucro.toFixed(2)}

                    </span>

                  </div>

                  <div class="flex justify-between">

                    <span class="text-slate-400">
                      Margem
                    </span>

                    <span>

                      ${margem}%

                    </span>

                  </div>

                </div>

                <div class="flex gap-3">

                  <a
                    href="#orcamento/${item.id}"
                    class="primary-button flex-1 text-center"
                  >
                    Visualizar
                  </a>

                  <button
                    class="primary-button export-btn"
                    data-id="${item.id}"
                  >
                    PDF
                  </button>

                </div>

              </div>

            </div>

          `;

        }).join('')}

      </div>

    </section>
  `;
}

function renderFinanceChart(
  composicoes,
  financeiro
) {

  const canvas =
    document.getElementById(
      'financeChart'
    );

  if (!canvas) return;

  const ctx =
    canvas.getContext('2d');

  const labels =
    composicoes.map(
      (_, index) =>
        `#${index + 1}`
    );

  const faturamento =
    composicoes.map(
      item => item.valorFinal
    );

  const custos =
    composicoes.map(
      item => item.custoTotal
    );

  const lucro =
    composicoes.map(
      item =>
        item.valorFinal -
        item.custoTotal
    );

  new Chart(ctx, {

    type: 'line',

    data: {

      labels,

      datasets: [

        {
          label: 'Faturamento',
          data: faturamento,
          borderColor: '#f97316',
          tension: 0.4
        },

        {
          label: 'Custos',
          data: custos,
          borderColor: '#ef4444',
          tension: 0.4
        },

        {
          label: 'Lucro',
          data: lucro,
          borderColor: '#22c55e',
          tension: 0.4
        }

      ]

    },

    options: {

      responsive: true,

      plugins: {

        legend: {

          labels: {
            color: '#cbd5e1'
          }

        }

      },

      scales: {

        x: {

          ticks: {
            color: '#94a3b8'
          }

        },

        y: {

          ticks: {
            color: '#94a3b8'
          }

        }

      }

    }

  });

}

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.classList.contains(
        'export-btn'
      )
    ) {

      showLoading();

      const composicaoId =
        Number(
          e.target.dataset.id
        );

      const composicao =
        await db.composicoes.get(
          composicaoId
        );

      const itens =
        await db.composicaoItens
          .where('composicaoId')
          .equals(composicaoId)
          .toArray();

      const etapas =
        await db.etapas
          .where('composicaoId')
          .equals(composicaoId)
          .toArray();

      await gerarPDF({

        composicao,
        itens,
        etapas

      });

      hideLoading();

      showToast(
        'PDF gerado!'
      );

    }

  }
);