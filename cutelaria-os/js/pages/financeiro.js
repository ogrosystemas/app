import { db } from '../database/db.js';

import {
  showToast
} from '../modules/toast.js';

export async function financeiroPage() {

  const registros =
    await db.financeiro
      .orderBy('createdAt')
      .reverse()
      .toArray();

  const receitas =
    registros
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

  const despesas =
    registros
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

  const saldo =
    receitas - despesas;

  return `
    <section>

      <!-- KPIs -->

      <div class="grid grid-cols-3 gap-3 mb-5">

        <div class="card">

          <div class="text-slate-400 text-sm">
            Receitas
          </div>

          <div class="text-green-400 font-bold text-xl mt-2">

            R$ ${receitas.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="text-slate-400 text-sm">
            Despesas
          </div>

          <div class="text-red-400 font-bold text-xl mt-2">

            R$ ${despesas.toFixed(2)}

          </div>

        </div>

        <div class="card">

          <div class="text-slate-400 text-sm">
            Saldo
          </div>

          <div class="font-bold text-xl mt-2">

            R$ ${saldo.toFixed(2)}

          </div>

        </div>

      </div>

      <!-- FORM -->

      <div class="card mb-5">

        <h2 class="text-xl font-bold mb-5">

          Novo lançamento

        </h2>

        <form id="financeiroForm">

          <select
            class="select"
            id="tipo"
          >

            <option value="receita">
              Receita
            </option>

            <option value="despesa">
              Despesa
            </option>

          </select>

          <input
            class="input"
            type="text"
            id="categoria"
            placeholder="Categoria"
          />

          <input
            class="input"
            type="text"
            id="descricao"
            placeholder="Descrição"
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
            type="date"
            id="vencimento"
          />

          <button
            class="primary-button mt-4"
            type="submit"
          >
            Salvar lançamento
          </button>

        </form>

      </div>

      <!-- HISTÓRICO -->

      <div class="grid gap-4">

        ${registros.map(item => `

          <div class="card">

            <div class="flex justify-between items-start">

              <div>

                <h3 class="font-bold text-lg">

                  ${item.descricao}

                </h3>

                <p class="text-slate-400 text-sm mt-1">

                  ${item.categoria}

                </p>

              </div>

              <div class="text-right">

                <div class="
                  font-bold
                  text-xl

                  ${
                    item.tipo === 'receita'
                      ? 'text-green-400'
                      : 'text-red-400'
                  }
                ">

                  ${
                    item.tipo === 'receita'
                      ? '+'
                      : '-'
                  }

                  R$ ${item.valor.toFixed(2)}

                </div>

                <div class="text-slate-400 text-sm mt-1">

                  ${item.vencimento || '-'}

                </div>

              </div>

            </div>

          </div>

        `).join('')}

      </div>

    </section>
  `;
}

window.addEventListener(
  'submit',
  async (e) => {

    if (
      e.target.id ===
      'financeiroForm'
    ) {

      e.preventDefault();

      await db.financeiro.add({

        tipo:
          document.getElementById(
            'tipo'
          ).value,

        categoria:
          document.getElementById(
            'categoria'
          ).value,

        descricao:
          document.getElementById(
            'descricao'
          ).value,

        valor:
          parseFloat(
            document.getElementById(
              'valor'
            ).value
          ),

        vencimento:
          document.getElementById(
            'vencimento'
          ).value,

        status: 'pendente',

        createdAt:
          new Date().toISOString()

      });

      showToast(
        'Lançamento salvo!'
      );

      setTimeout(() => {

        location.reload();

      }, 700);

    }

  }
);