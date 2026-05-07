import { db } from '../database/db.js';

import {
  showToast
} from '../modules/toast.js';

export async function materiaisPage() {

  const materiais =
    await db.materiais.toArray();

  const valorEstoque =
    materiais.reduce(
      (total, item) => {

        return total +
          (
            item.valor *
            item.estoqueAtual
          );

      },
      0
    );

  const materiaisCriticos =
    materiais.filter(item => {

      return (
        item.estoqueAtual <=
        item.estoqueMinimo
      );

    });

  return `
    <section>

      <!-- KPIS -->

      <div class="grid grid-cols-2 gap-4 mb-5">

        <div class="card">

          <div class="metric-label">
            Valor em estoque
          </div>

          <div class="metric-value text-orange-400">

            R$ ${valorEstoque.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="metric-label">
            Itens críticos
          </div>

          <div class="metric-value text-red-400">

            ${materiaisCriticos.length}

          </div>

        </div>

      </div>

      <!-- ALERTA -->

      ${
        materiaisCriticos.length
          ? `
            <div class="card border border-red-500/30 mb-5">

              <h3 class="text-red-400 font-bold mb-4">

                ⚠️ Materiais críticos

              </h3>

              <div class="grid gap-3">

                ${materiaisCriticos.map(item => `

                  <div class="flex justify-between">

                    <span>
                      ${item.nome}
                    </span>

                    <span class="text-red-400 font-bold">

                      ${item.estoqueAtual}
                      ${item.unidade}

                    </span>

                  </div>

                `).join('')}

              </div>

            </div>
          `
          : ''
      }

      <!-- FORM -->

      <div class="card mb-5">

        <h2 class="text-xl font-bold mb-5">

          Novo Material

        </h2>

        <form id="materialForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome"
            required
          />

          <input
            class="input"
            type="text"
            id="categoria"
            placeholder="Categoria"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="valor"
            placeholder="Valor"
          />

          <input
            class="input"
            type="text"
            id="unidade"
            placeholder="Unidade"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="estoqueAtual"
            placeholder="Estoque atual"
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="estoqueMinimo"
            placeholder="Estoque mínimo"
          />

          <button
            class="primary-button mt-4"
            type="submit"
          >
            Salvar Material
          </button>

        </form>

      </div>

      <!-- LISTA -->

      <div class="grid gap-4">

        ${materiais.map(item => {

          const critico =
            item.estoqueAtual <=
            item.estoqueMinimo;

          return `

            <div class="
              card

              ${
                critico
                  ? 'border border-red-500/30'
                  : ''
              }
            ">

              <div class="flex justify-between items-start">

                <div>

                  <h3 class="text-xl font-bold">

                    ${item.nome}

                  </h3>

                  <p class="text-slate-400 text-sm mt-1">

                    ${item.categoria}

                  </p>

                </div>

                <div class="text-right">

                  <div class="text-orange-400 font-bold text-xl">

                    R$ ${item.valor.toFixed(2)}

                  </div>

                </div>

              </div>

              <div class="grid gap-2 mt-5 text-sm">

                <div class="flex justify-between">

                  <span class="text-slate-400">
                    Estoque
                  </span>

                  <span class="
                    ${
                      critico
                        ? 'text-red-400 font-bold'
                        : ''
                    }
                  ">

                    ${item.estoqueAtual}
                    ${item.unidade}

                  </span>

                </div>

                <div class="flex justify-between">

                  <span class="text-slate-400">
                    Estoque mínimo
                  </span>

                  <span>

                    ${item.estoqueMinimo}
                    ${item.unidade}

                  </span>

                </div>

              </div>

            </div>

          `;

        }).join('')}

      </div>

    </section>
  `;
}

window.addEventListener(
  'submit',
  async (e) => {

    if (
      e.target.id ===
      'materialForm'
    ) {

      e.preventDefault();

      await db.materiais.add({

        nome:
          document.getElementById(
            'nome'
          ).value,

        categoria:
          document.getElementById(
            'categoria'
          ).value,

        valor:
          parseFloat(
            document.getElementById(
              'valor'
            ).value
          ),

        unidade:
          document.getElementById(
            'unidade'
          ).value,

        estoqueAtual:
          parseFloat(
            document.getElementById(
              'estoqueAtual'
            ).value
          ),

        estoqueMinimo:
          parseFloat(
            document.getElementById(
              'estoqueMinimo'
            ).value
          ),

        createdAt:
          new Date().toISOString()

      });

      showToast(
        'Material salvo!'
      );

      setTimeout(() => {

        location.reload();

      }, 700);

    }

  }
);