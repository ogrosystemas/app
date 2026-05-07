import { db } from '../database/db.js';

import {
  gerarPDF
} from '../services/pdf.js';

export async function producaoPage() {

  const composicoes =
    await db.composicoes
      .orderBy('createdAt')
      .reverse()
      .toArray();

  return `

    <section class="pb-32">

      <!-- HEADER -->

      <div class="mb-8">

        <div class="
          flex
          justify-between
          items-center
          mb-4
        ">

          <div>

            <h1 class="
              text-3xl
              font-black
            ">

              Produções

            </h1>

            <p class="
              text-slate-400
              mt-2
            ">

              Gestão de facas e custos

            </p>

          </div>

          <button
            id="novaComposicao"
            class="primary-button"
            style="
              width:auto;
              padding-inline:20px;
            "
          >

            Nova

          </button>

        </div>

      </div>

      <!-- LISTA -->

      <div class="grid gap-5">

        ${
          composicoes.length

            ? composicoes.map(item => `

              <div class="card">

                <div class="
                  flex
                  justify-between
                  items-start
                  mb-5
                ">

                  <div>

                    <h2 class="
                      text-2xl
                      font-bold
                    ">

                      ${item.nome}

                    </h2>

                    <p class="
                      text-slate-400
                      mt-2
                    ">

                      ${
                        item.tipoFaca ||
                        'Faca personalizada'
                      }

                    </p>

                  </div>

                  <div class="
                    text-right
                  ">

                    <div class="
                      text-orange-400
                      text-2xl
                      font-black
                    ">

                      R$ ${(item.valorFinal || 0).toFixed(2)}

                    </div>

                    <div class="
                      text-slate-400
                      text-sm
                      mt-1
                    ">

                      venda final

                    </div>

                  </div>

                </div>

                <!-- GRID -->

                <div class="
                  grid
                  grid-cols-2
                  gap-4
                  mb-5
                ">

                  <div class="
                    bg-slate-900/60
                    border
                    border-slate-700
                    rounded-2xl
                    p-4
                  ">

                    <div class="
                      text-slate-400
                      text-sm
                      mb-2
                    ">

                      Custo

                    </div>

                    <div class="
                      text-xl
                      font-black
                      text-red-400
                    ">

                      R$ ${(item.custoTotal || 0).toFixed(2)}

                    </div>

                  </div>

                  <div class="
                    bg-slate-900/60
                    border
                    border-slate-700
                    rounded-2xl
                    p-4
                  ">

                    <div class="
                      text-slate-400
                      text-sm
                      mb-2
                    ">

                      Margem

                    </div>

                    <div class="
                      text-xl
                      font-black
                      text-green-400
                    ">

                      ${item.margemLucro || 0}%

                    </div>

                  </div>

                </div>

                <!-- DETALHES -->

                <div class="
                  grid
                  gap-2
                  mb-6
                  text-sm
                ">

                  <div class="
                    flex
                    justify-between
                  ">

                    <span class="
                      text-slate-400
                    ">
                      Tipo aço
                    </span>

                    <span>

                      ${item.tipoAco || '-'}

                    </span>

                  </div>

                  <div class="
                    flex
                    justify-between
                  ">

                    <span class="
                      text-slate-400
                    ">
                      Acabamento
                    </span>

                    <span>

                      ${item.acabamento || '-'}

                    </span>

                  </div>

                  <div class="
                    flex
                    justify-between
                  ">

                    <span class="
                      text-slate-400
                    ">
                      Cabo
                    </span>

                    <span>

                      ${item.tipoCabo || '-'}

                    </span>

                  </div>

                </div>

                <!-- AÇÕES -->

                <div class="
                  grid
                  grid-cols-2
                  gap-3
                ">

                  <button
                    class="
                      primary-button
                      detalhes-btn
                    "
                    data-id="${item.id}"
                  >

                    Detalhes

                  </button>

                  <button
                    class="
                      primary-button
                      pdf-btn
                    "
                    data-id="${item.id}"
                  >

                    PDF

                  </button>

                </div>

              </div>

            `).join('')

            : `

              <div class="card text-center">

                <div class="
                  text-3xl
                  mb-4
                ">

                  ⚒️

                </div>

                <h2 class="
                  text-2xl
                  font-bold
                  mb-2
                ">

                  Nenhuma produção

                </h2>

                <p class="
                  text-slate-400
                ">

                  Crie sua primeira faca personalizada

                </p>

              </div>

            `
        }

      </div>

    </section>

  `;

}

// PDF

window.addEventListener(
  'click',
  async (e) => {

    // EXPORTAR PDF

    if (
      e.target.classList.contains(
        'pdf-btn'
      )
    ) {

      const id =
        e.target.dataset.id;

      await gerarPDF(id);

    }

    // DETALHES

    if (
      e.target.classList.contains(
        'detalhes-btn'
      )
    ) {

      alert(
        'Detalhes avançados em desenvolvimento.'
      );

    }

  }
);