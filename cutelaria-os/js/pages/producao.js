import { db } from '../database/db.js';

import {
  calcularComposicao
} from '../services/calculator.service.js';

import {
  calcularEtapas
} from '../services/etapas.service.js';

import {
  showToast
} from '../modules/toast.js';

let itensTemporarios = [];

let etapasTemporarias = [];

export async function producaoPage() {

  const materiais =
    await db.materiais.toArray();

  const equipamentos =
    await db.equipamentos.toArray();

  return `
    <section>

      <div class="card">

        <h2 class="text-2xl font-bold mb-5">
          Nova Produção
        </h2>

        <form id="producaoForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome da faca"
            required
          />

          <!-- MATERIAIS -->

          <div class="mt-5">

            <h3 class="font-bold mb-3">
              Materiais
            </h3>

            <select
              class="select"
              id="materialId"
            >

              ${materiais.map(material => `
                <option value="${material.id}">
                  ${material.nome}
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

          <!-- EQUIPAMENTOS -->

          <div class="mt-8">

            <h3 class="font-bold mb-3">
              Equipamentos
            </h3>

            <div class="grid gap-2">

              ${equipamentos.map(item => `

                <label class="flex items-center gap-3">

                  <input
                    type="checkbox"
                    class="equipamento-check"
                    value="${item.id}"
                  />

                  <span>

                    ${item.nome}
                    —
                    R$ ${item.custoHora.toFixed(2)}/h

                  </span>

                </label>

              `).join('')}

            </div>

          </div>

          <!-- ETAPAS -->

          <div class="mt-8">

            <h3 class="font-bold mb-3">
              Etapas
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
              placeholder="Horas"
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
              placeholder="Energia"
            />

            <input
              class="input"
              type="number"
              step="0.01"
              id="etapaAbrasivos"
              placeholder="Abrasivos"
            />

            <button
              type="button"
              id="addEtapaBtn"
              class="primary-button mt-2"
            >
              Adicionar Etapa
            </button>

          </div>

          <!-- MARGEM -->

          <input
            class="input mt-8"
            type="number"
            step="0.01"
            id="margem"
            placeholder="Margem de lucro (%)"
          />

          <button
            class="primary-button"
            type="submit"
          >
            Salvar Produção
          </button>

        </form>

      </div>

    </section>
  `;
}

window.addEventListener('click', async (e) => {

  // MATERIAL

  if (e.target.id === 'addItemBtn') {

    const materialId =
      Number(
        document.getElementById(
          'materialId'
        ).value
      );

    const quantidade =
      parseFloat(
        document.getElementById(
          'quantidade'
        ).value
      );

    const material =
      await db.materiais.get(
        materialId
      );

    itensTemporarios.push({

      materialId,

      nome: material.nome,

      quantidade,

      valorUnitario:
        material.valor,

      subtotal:
        material.valor *
        quantidade

    });

    showToast(
      'Material adicionado!'
    );

  }

  // ETAPA

  if (e.target.id === 'addEtapaBtn') {

    const equipamentos =
      await db.equipamentos.toArray();

    const etapa = {

      nome:
        document.getElementById(
          'etapaNome'
        ).value,

      horas:
        parseFloat(
          document.getElementById(
            'etapaHoras'
          ).value
        ),

      valorHora:
        parseFloat(
          document.getElementById(
            'etapaValorHora'
          ).value
        ),

      custoEnergia:
        parseFloat(
          document.getElementById(
            'etapaEnergia'
          ).value
        ) || 0,

      custoAbrasivos:
        parseFloat(
          document.getElementById(
            'etapaAbrasivos'
          ).value
        ) || 0
    };

    const equipamentosSelecionados =
      Array.from(
        document.querySelectorAll(
          '.equipamento-check:checked'
        )
      ).map(check => {

        return equipamentos.find(
          item =>
            item.id ===
            Number(check.value)
        );

      });

    const [calculada] =
      calcularEtapas(
        [etapa],
        equipamentosSelecionados
      );

    etapasTemporarias.push(
      calculada
    );

    showToast(
      'Etapa adicionada!'
    );

  }

});

document.addEventListener(
  'submit',
  async (e) => {

    if (
      e.target.id ===
      'producaoForm'
    ) {

      e.preventDefault();

      const margemLucro =
        parseFloat(
          document.getElementById(
            'margem'
          ).value
        );

      const calculo =
        calcularComposicao({

          itens:
            itensTemporarios,

          etapas:
            etapasTemporarias,

          margemLucro

        });

      const composicaoId =
        await db.composicoes.add({

          nome:
            document.getElementById(
              'nome'
            ).value,

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

      for (const item of itensTemporarios) {

        await db.composicaoItens.add({

          composicaoId,

          ...item

        });

      }

      for (const etapa of etapasTemporarias) {

        await db.etapas.add({

          composicaoId,

          ...etapa

        });

      }

      itensTemporarios = [];

      etapasTemporarias = [];

      showToast(
        'Produção salva com sucesso!'
      );

      setTimeout(() => {

        location.reload();

      }, 800);

    }

  }
);