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

  const materiais =
    await db.composicaoItens.toArray();

  const etapas =
    await db.etapas.toArray();

  // MÉTRICAS

  const totalProducoes =
    composicoes.length;

  const faturamentoTotal =
    composicoes.reduce((total, item) => {
      return total + item.valorFinal;
    }, 0);

  const custoTotal =
    composicoes.reduce((total, item) => {
      return total + item.custoTotal;
    }, 0);

  const lucroTotal =
    faturamentoTotal - custoTotal;

  const ticketMedio =
    totalProducoes > 0
      ? faturamentoTotal / totalProducoes
      : 0;

  const lucroMedio =
    totalProducoes > 0
      ? lucroTotal / totalProducoes
      : 0;

  // MATERIAL MAIS USADO

  const materiaisAgrupados = {};

  materiais.forEach(item => {

    if (!materiaisAgrupados[item.nome]) {
      materiaisAgrupados[item.nome] = 0;
    }

    materiaisAgrupados[item.nome] += item.quantidade;

  });

  let materialMaisUsado = '-';
  let materialMaiorQtd = 0;

  for (const nome in materiaisAgrupados) {

    if (
      materiaisAgrupados[nome] >
      materialMaiorQtd
    ) {

      materialMaiorQtd =
        materiaisAgrupados[nome];

      materialMaisUsado = nome;
    }

  }

  // ETAPA MAIS CARA

  const etapasAgrupadas = {};

  etapas.forEach(etapa => {

    if (!etapasAgrupadas[etapa.nome]) {
      etapasAgrupadas[etapa.nome] = 0;
    }

    etapasAgrupadas[etapa.nome] += etapa.custoTotal;

  });

  let etapaMaisCara = '-';
  let maiorCustoEtapa = 0;

  for (const nome in etapasAgrupadas) {

    if (
      etapasAgrupadas[nome] >
      maiorCustoEtapa
    ) {

      maiorCustoEtapa =
        etapasAgrupadas[nome];

      etapaMaisCara = nome;
    }

  }

  setTimeout(() => {

    renderCharts(composicoes);

  }, 100);

  return `
    <section class="dashboard-grid">

      <div class="grid grid-cols-2 gap-4">

        <div class="card">

          <div class="metric-label">
            Faturamento
          </div>

          <div class="metric-value text-orange-400">
            R$ ${faturamentoTotal.toFixed(2)}
          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Lucro Total
          </div>

          <div class="metric-value text-green-400">
            R$ ${lucroTotal.toFixed(2)}
          </div>

        </div>

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
            Ticket Médio
          </div>

          <div class="metric-value">
            R$ ${ticketMedio.toFixed(2)}
          </div>

        </div>

      </div>

      <div class="card">

        <h3 class="font-bold text-lg mb-4">
          Evolução Financeira
        </h3>

        <canvas id="financeChart"></canvas>

      </div>

      <div class="card">

        <h3 class="font-bold text-lg mb-5">
          Insights Operacionais
        </h3>

        <div class="grid gap-4">

          <div class="flex justify-between">

            <span class="text-slate-400">
              Lucro Médio
            </span>

            <span class="font-bold text-green-400">
              R$ ${lucroMedio.toFixed(2)}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Material mais usado
            </span>

            <span class="font-bold">
              ${materialMaisUsado}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Etapa mais cara
            </span>

            <span class="font-bold text-orange-400">
              ${etapaMaisCara}
            </span>

          </div>

        </div>

      </div>

      <div class="grid gap-4">

        ${composicoes.reverse().map(item => `

          <div class="card">

            <div class="flex justify-between items-start mb-5">

              <div>

                <h3 class="text-xl font-bold">
                  ${item.nome}
                </h3>

                <p class="text-slate-400 text-sm mt-1">

                  ${new Date(item.createdAt)
                    .toLocaleDateString()}

                </p>

              </div>

              <div class="text-right">

                <p class="text-slate-400 text-sm">
                  Valor Final
                </p>

                <h2 class="text-2xl font-bold text-orange-400">

                  R$ ${item.valorFinal.toFixed(2)}

                </h2>

              </div>

            </div>

            <div class="grid gap-2 mb-5">

              <div class="flex justify-between">

                <span class="text-slate-400">
                  Materiais
                </span>

                <span>
                  R$ ${item.custoMateriais.toFixed(2)}
                </span>

              </div>

              <div class="flex justify-between">

                <span class="text-slate-400">
                  Etapas
                </span>

                <span>
                  R$ ${item.custoEtapas.toFixed(2)}
                </span>

              </div>

              <div class="flex justify-between">

                <span class="text-slate-400">
                  Total
                </span>

                <span class="font-bold">
                  R$ ${item.custoTotal.toFixed(2)}
                </span>

              </div>

            </div>

            <div class="flex justify-end">

              <button
                class="primary-button export-btn"
                data-id="${item.id}"
              >
                Exportar PDF
              </button>

            </div>

          </div>

        `).join('')}

      </div>

    </section>
  `;
}

function renderCharts(composicoes) {

  const canvas =
    document.getElementById(
      'financeChart'
    );

  if (!canvas) return;

  const ctx =
    canvas.getContext('2d');

  const labels =
    composicoes.map((_, index) => {
      return `#${index + 1}`;
    });

  const faturamento =
    composicoes.map(
      item => item.valorFinal
    );

  const custos =
    composicoes.map(
      item => item.custoTotal
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
          borderColor: '#22c55e',
          tension: 0.4
        }

      ]

    },

    options: {

      responsive: true

    }

  });

}

window.addEventListener('click', async (e) => {

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
      'PDF gerado com sucesso!'
    );

  }

});