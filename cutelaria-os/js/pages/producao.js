import { db } from '../database/db.js';

import {
  calcularComposicao
} from '../services/calculator.service.js';

export async function producaoPage() {

  const materiais =
    await db.materiais.toArray();

  const composicoes =
    await db.composicoes.reverse().toArray();

  return `
    <section>

      <!-- FORM -->

      <div class="card">

        <h2 class="text-2xl font-bold mb-5">
          Nova Produção
        </h2>

        <form id="producaoForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome da faca/composição"
            required
          />

          <label class="text-sm text-slate-400">
            Material
          </label>

          <select
            class="select"
            id="materialId"
          >

            ${materiais.map(material => `
              <option value="${material.id}">
                ${material.nome}
                -
                R$ ${material.valor}
              </option>
            `).join('')}

          </select>

          <input
            class="input"
            type="number"
            step="0.01"
            id="quantidade"
            placeholder="Quantidade utilizada"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="horas"
            placeholder="Horas de trabalho"
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
                  Custo total
                </span>

                <span>
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

document.addEventListener('submit', async (e) => {

  if (e.target.id === 'producaoForm') {

    e.preventDefault();

    const materialId =
      Number(
        document.getElementById('materialId').value
      );

    const material =
      await db.materiais.get(materialId);

    const quantidade =
      parseFloat(
        document.getElementById('quantidade').value
      );

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

    const custoMateriais =
      material.valor * quantidade;

    const calculo =
      calcularComposicao({
        custoMateriais,
        horasTrabalho,
        valorHora,
        margemLucro
      });

    await db.composicoes.add({

      nome:
        document.getElementById('nome').value,

      categoria: 'faca',

      custoMateriais,

      custoMaoObra:
        calculo.custoMaoObra,

      custoTotal:
        calculo.custoTotal,

      margemLucro,

      valorFinal:
        calculo.valorFinal,

      createdAt:
        new Date().toISOString()
    });

    location.reload();
  }

});