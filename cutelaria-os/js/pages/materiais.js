import { db } from '../database/db.js';

import {
  showToast
} from '../modules/toast.js';

export async function materiaisPage() {

  // ========================================
  // SAFE TABLE
  // ========================================

  if (!db.materiais) {

    return `

      <section class="pb-32">

        <div class="card">

          <h2 class="
            text-2xl
            font-bold
            mb-4
          ">

            Banco de dados não atualizado

          </h2>

          <p class="text-slate-400">

            A tabela "materiais"
            não existe no IndexedDB.

          </p>

        </div>

      </section>

    `;

  }

  const materiais =
    await db.materiais.toArray();

  const valorEstoque =
    materiais.reduce(
      (total, item) => {

        return total +
          (
            Number(item.valor || 0) *
            Number(item.estoqueAtual || 0)
          );

      },
      0
    );

  const materiaisCriticos =
    materiais.filter(item => {

      return (
        Number(item.estoqueAtual || 0) <=
        Number(item.estoqueMinimo || 0)
      );

    });

  return `
    <section class="pb-32">

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
            Number(item.estoqueAtual || 0) <=
            Number(item.estoqueMinimo || 0);

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

                    ${item.categoria || '-'}

                  </p>

                </div>

                <div class="text-right">

                  <div class="text-orange-400 font-bold text-xl">

                    R$ ${Number(item.valor || 0).toFixed(2)}

                  </div>

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

      if (!db.materiais) {

        return;

      }

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
            ).value || 0
          ),

        unidade:
          document.getElementById(
            'unidade'
          ).value,

        estoqueAtual:
          parseFloat(
            document.getElementById(
              'estoqueAtual'
            ).value || 0
          ),

        estoqueMinimo:
          parseFloat(
            document.getElementById(
              'estoqueMinimo'
            ).value || 0
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