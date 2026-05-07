import { db } from '../database/db.js';

import {
  calcularComposicao
} from '../services/calculator.service.js';

import {
  calcularEtapas
} from '../services/etapas.service.js';

let itensTemporarios = [];
let etapasTemporarias = [];

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

          <!-- MATERIAIS -->

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

          <!-- LISTA MATERIAIS -->

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

          <!-- ETAPAS -->

          <div class="mt-8 mb-5">

            <h3 class="font-bold mb-3">
              Etapas de Produção
            </h3>

            <input
              class="input"
              type="text"
              id="etapaNome"
              placeholder="Nome da etapa"
            />

            <input
              class="input"
              type="number"
              step="0.01"
              id="etapaHoras"
              placeholder="Horas da etapa"
            />

            <input
              class="input"
              type="number"
              step="0.01"
              id="etapaValorHora"
              placeholder="Valor hora"
            />

            <input
              class="input"
              type="number"
              step="0.01"
              id="etapaEnergia"
              placeholder="Custo energia"
            />

            <input
              class="input"
              type="number"
              step="0.01"
              id="etapaAbrasivos"
              placeholder="Custo abrasivos"
            />

            <button
              type="button"
              id="addEtapaBtn"
              class="primary-button mt-2"
            >
              Adicionar Etapa
            </button>

          </div>

          <!-- LISTA ETAPAS -->

          <div>

            ${etapasTemporarias.map(etapa => `
              <div class="card">

                <div class="flex justify-between">

                  <div>

                    <h4 class="font-bold">
                      ${etapa.nome}
                    </h4>

                    <p class="text-sm text-slate-400">
                      ${etapa.horas}h
                    </p>

                  </div>

                  <div class="text-right">

                    <p class="text-orange-400 font-bold">
                      R$ ${etapa.custoTotal.toFixed(2)}
                    </p>

                  </div>

                </div>

              </div>
            `).join('')}

          </div>

          <!-- LUCRO -->

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

          </div>
        `).join('')}

      </div>

    </section>
  `;
}

window.addEventListener('click', async (e) => {

  // ADICIONAR ETAPA

  if (e.target.id === 'addEtapaBtn') {

    const etapa = {
      nome:
        document.getElementById('etapaNome').value,

      horas:
        parseFloat(
          document.getElementById('etapaHoras').value
        ),

      valorHora:
        parseFloat(
          document.getElementById('etapaValorHora').value
        ),

      custoEnergia:
        parseFloat(
          document.getElementById('etapaEnergia').value
        ) || 0,

      custoAbrasivos:
        parseFloat(
          document.getElementById('etapaAbrasivos').value
        ) || 0
    };

    const [calculada] =
      calcularEtapas([etapa]);

    etapasTemporarias.push(calculada);

    location.reload();
  }

  // ADICIONAR MATERIAL

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

    const margemLucro =
      parseFloat(
        document.getElementById('margem').value
      );

    const calculo =
      calcularComposicao({
        itens: itensTemporarios,
        etapas: etapasTemporarias,
        margemLucro
      });

    const composicaoId =
      await db.composicoes.add({

        nome:
          document.getElementById('nome').value,

        categoria: 'faca',

        custoMateriais:
          calculo.custoMateriais,

        custoEtapas:
          calculo.custoEtapas,

        custoTotal:
          calculo.custoTotal,

        margemLucro,

        valorFinal:
          calculo.valorFinal,

        createdAt:
          new Date().toISOString()
      });

    // SALVAR ITENS

    for (const item of itensTemporarios) {

      await db.composicaoItens.add({
        composicaoId,
        ...item
      });

    }

    // SALVAR ETAPAS

    for (const etapa of etapasTemporarias) {

      await db.etapas.add({
        composicaoId,
        ...etapa
      });

    }

    itensTemporarios = [];
    etapasTemporarias = [];

    location.reload();
  }

});