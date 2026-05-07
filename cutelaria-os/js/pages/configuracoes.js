import { db } from '../database/db.js';

export async function configuracoesPage() {

  const equipamentos =
    await db.equipamentos.toArray();

  return `
    <section>

      <!-- FORM -->

      <div class="card">

        <h2 class="text-2xl font-bold mb-5">
          Equipamentos
        </h2>

        <form id="equipamentoForm">

          <input
            class="input"
            type="text"
            id="nomeEquipamento"
            placeholder="Nome do equipamento"
            required
          />

          <input
            class="input"
            type="number"
            step="0.01"
            id="valorCompra"
            placeholder="Valor de compra"
          />

          <input
            class="input"
            type="number"
            step="1"
            id="vidaUtil"
            placeholder="Vida útil (meses)"
          />

          <input
            class="input"
            type="number"
            step="1"
            id="horasMes"
            placeholder="Horas de uso/mês"
          />

          <button
            class="primary-button"
            type="submit"
          >
            Salvar Equipamento
          </button>

        </form>

      </div>

      <!-- LISTA -->

      <div class="mt-6">

        ${equipamentos.map(item => `
          <div class="card">

            <div class="flex justify-between">

              <div>

                <h3 class="text-xl font-bold">
                  ${item.nome}
                </h3>

                <p class="text-slate-400 text-sm mt-1">
                  Vida útil:
                  ${item.vidaUtil} meses
                </p>

              </div>

              <div class="text-right">

                <p class="text-sm text-slate-400">
                  Custo/Hora
                </p>

                <h2 class="text-xl font-bold text-orange-400">
                  R$ ${item.custoHora.toFixed(2)}
                </h2>

              </div>

            </div>

          </div>
        `).join('')}

      </div>

    </section>
  `;
}

document.addEventListener('submit', async (e) => {

  if (e.target.id === 'equipamentoForm') {

    e.preventDefault();

    const valorCompra =
      parseFloat(
        document.getElementById('valorCompra').value
      );

    const vidaUtil =
      parseFloat(
        document.getElementById('vidaUtil').value
      );

    const horasMes =
      parseFloat(
        document.getElementById('horasMes').value
      );

    // DEPRECIAÇÃO

    const custoHora =
      valorCompra / (vidaUtil * horasMes);

    await db.equipamentos.add({

      nome:
        document.getElementById('nomeEquipamento').value,

      valorCompra,

      vidaUtil,

      horasMes,

      custoHora

    });

    location.reload();
  }

});