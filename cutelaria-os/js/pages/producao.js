import { db } from '../database/db.js';

import {
  calcularComposicao
} from '../services/calculator.service.js';

import {
  calcularEtapas
} from '../services/etapas.service.js';

let itensTemporarios = [];

export async function producaoPage() {

  const materiais =
    await db.materiais.toArray();

  const composicoes =
    await db.composicoes.reverse().toArray();

  return `
    <section>

      <!-- FORM PRINCIPAL -->

      <div class="card">

        <h2 class="text-2xl font-bold mb-5">
          Nova Produção
        </h2>

        <form id="producaoForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome da composição"
            required
          />

          <!-- ADICIONAR ITEM -->

          <div class="mt-5 mb-5">

            <h3 class="font-bold mb-3">
              Materiais da composição
            </h3>

            <select
              class="select"
              id="materialId"
            >

              ${materiais.map(material => `
                <option value="${material.id}">
                  ${material.nome}
                  —
                  R$ ${material.valor}
                </option>
              `).join('')}

            </select>

            <input
              class="input"
              type="number"
              step="0.01"
              id="quantidade"
              placeholder="Quantidade"
            />

            <button
              type="button"
              id="addItemBtn"
              class="primary-button mt-2"
            >
              Adicionar Material
            </button>

          </div>

          <!-- LISTA -->

          <div id="itensLista">

            ${itensTemporarios.map(item => `
              <div class="card">

                <div class="flex justify-between">

                  <div>

                    <h4 class="font-bold">
                      ${item.nome}
                    </h4>

                    <p class="text-sm text-slate-400">
                      ${item.quantidade}
                      x
                      R$ ${item.valorUnitario}
                    </p>

                  </div>

                  <div class="text-right">

                    <p class="font-bold text-orange-400">
                      R$ ${item.subtotal.toFixed(2)}
                    </p>

                  </div>

                </div>

              </div>
            `).join('')}

          </div>

          <!-- CUSTOS -->

          <input
            class="input"
            type="number"
            step="0.01"
            id="horas"
            placeholder="Horas trabalhadas"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="valorHora"
            placeholder="Valor da hora"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="energia"
            placeholder="Custo energia/gás/carvão"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="margem"
            placeholder="Margem de lucro (%)"
          />

          <button
            class="primary-button"
            type="submit"
          >
            Calcular Produção
          </button>

        </form>

      </div>

      <!-- HISTÓRICO -->

      <div class="mt-6">

        ${composicoes.map(item => `
          <div class="card">

            <div class="flex justify-between">

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

                <p class="text-sm text-slate-400">
                  Valor Final
                </p>

                <h2 class="text-2xl font-bold text-orange-400">
                  R$ ${item.valorFinal.toFixed(2)}
                </h2>

              </div>

            </div>

            <div class="mt-5 grid gap-2">

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
                  Mão de obra
                </span>

                <span>
                  R$ ${item.custoMaoObra.toFixed(2)}
                </span>
              </div>

              <div class="flex justify-between">
                <span class="text-slate-400">
                  Energia
                </span>

                <span>
                  R$ ${item.custoEnergia.toFixed(2)}
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

          </div>
        `).join('')}

      </div>

    </section>
  `;
}

window.addEventListener('click', async (e) => {

  if (e.target.id === 'addItemBtn') {

    const materialId =
      Number(
        document.getElementById('materialId').value
      );

    const quantidade =
      parseFloat(
        document.getElementById('quantidade').value
      );

    const material =
      await db.materiais.get(materialId);

    const subtotal =
      material.valor * quantidade;

    itensTemporarios.push({
      materialId,
      nome: material.nome,
      quantidade,
      valorUnitario: material.valor,
      subtotal
    });

    location.reload();
  }

});

document.addEventListener('submit', async (e) => {

  if (e.target.id === 'producaoForm') {

    e.preventDefault();

    const horasTrabalho =
      parseFloat(
        document.getElementById('horas').value
      );

    const valorHora =
      parseFloat(
        document.getElementById('valorHora').value
      );

    const margemLucro =
      parseFloat(
        document.getElementById('margem').value
      );

    const custoEnergia =
      parseFloat(
        document.getElementById('energia').value
      ) || 0;

    const calculo =
      calcularComposicao({
        itens: itensTemporarios,
        horasTrabalho,
        valorHora,
        margemLucro,
        custoEnergia
      });

    const composicaoId =
      await db.composicoes.add({

        nome:
          document.getElementById('nome').value,

        categoria: 'faca',

        custoMateriais:
          calculo.custoMateriais,

        custoMaoObra:
          calculo.custoMaoObra,

        custoEnergia,

        custoTotal:
          calculo.custoTotal,

        margemLucro,

        valorFinal:
          calculo.valorFinal,

        createdAt:
          new Date().toISOString()
      });

    for (const item of itensTemporarios) {

      await db.composicaoItens.add({
        composicaoId,
        ...item
      });

    }

    let itensTemporarios = [];
    let etapasTemporarias = [];

    location.reload();
  }

});