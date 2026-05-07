import { db } from '../database/db.js';

export async function materiaisPage() {

  const materiais = await db.materiais.toArray();

  return `
    <section>

      <div class="card">

        <h2 class="text-2xl font-bold mb-5">
          Materiais
        </h2>

        <form id="materialForm">

          <input
            class="input"
            type="text"
            id="nome"
            placeholder="Nome do material"
            required
          />

          <select
            class="select"
            id="categoria"
          >
            <option value="aco">
              Aço
            </option>

            <option value="madeira">
              Madeira
            </option>

            <option value="abrasivo">
              Abrasivo
            </option>

            <option value="insumo">
              Insumo
            </option>
          </select>

          <input
            class="input"
            type="text"
            id="unidade"
            placeholder="Unidade (cm, folha, kg...)"
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
            type="number"
            step="0.01"
            id="estoque"
            placeholder="Estoque"
          />

          <button
            class="primary-button"
            type="submit"
          >
            Salvar Material
          </button>

        </form>

      </div>

      <div id="listaMateriais">

        ${materiais.map(material => `
          <div class="card">

            <div class="flex justify-between items-start">

              <div>

                <h3 class="text-lg font-bold">
                  ${material.nome}
                </h3>

                <p class="text-slate-400 text-sm mt-1">
                  ${material.categoria}
                </p>

                <p class="mt-3">
                  R$ ${material.valor || 0}
                </p>

              </div>

              <button
                onclick="window.deleteMaterial(${material.id})"
                class="text-red-400"
              >
                Excluir
              </button>

            </div>

          </div>
        `).join('')}

      </div>

    </section>
  `;
}

window.deleteMaterial = async function(id) {

  await db.materiais.delete(id);

  location.reload();
}

document.addEventListener('submit', async (e) => {

  if (e.target.id === 'materialForm') {

    e.preventDefault();

    const material = {
      nome:
        document.getElementById('nome').value,

      categoria:
        document.getElementById('categoria').value,

      unidade:
        document.getElementById('unidade').value,

      valor:
        parseFloat(
          document.getElementById('valor').value
        ),

      estoque:
        parseFloat(
          document.getElementById('estoque').value
        )
    };

    await db.materiais.add(material);

    location.reload();
  }

});