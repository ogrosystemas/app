import { db } from '../database/db.js';

export async function orcamentoPage(id) {

  const composicao =
    await db.composicoes.get(
      Number(id)
    );

  if (!composicao) {

    return `
      <section>

        <div class="card">

          Produção não encontrada.

        </div>

      </section>
    `;

  }

  const lucro =
    composicao.valorFinal -
    composicao.custoTotal;

  const margem =
    (
      (lucro /
        composicao.valorFinal) *
      100
    ).toFixed(1);

  return `
    <section class="pb-32">

      <!-- HERO -->

      <div class="card overflow-hidden">

        ${
          composicao.fotoCapa
            ? `
              <img
                src="${composicao.fotoCapa}"
                class="w-full h-80 object-cover"
              />
            `
            : ''
        }

        <div class="p-6">

          <div class="flex justify-between items-start">

            <div>

              <h1 class="text-3xl font-bold">

                ${composicao.nome}

              </h1>

              <p class="text-slate-400 mt-2">

                ${composicao.tipoFaca}

              </p>

            </div>

            <div class="text-right">

              <div class="text-slate-400 text-sm">
                Valor final
              </div>

              <div class="text-4xl font-black text-orange-400 mt-1">

                R$ ${composicao.valorFinal.toFixed(2)}

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- FICHA -->

      <div class="card mt-5">

        <h3 class="text-xl font-bold mb-5">
          Ficha Técnica
        </h3>

        <div class="grid gap-3 text-sm">

          <div class="flex justify-between">

            <span class="text-slate-400">
              Tipo de aço
            </span>

            <span>
              ${composicao.tipoAco || '-'}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              HRC
            </span>

            <span>
              ${composicao.hrc || '-'}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Espessura
            </span>

            <span>
              ${composicao.espessura || '-'} mm
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Comprimento
            </span>

            <span>
              ${composicao.comprimento || '-'} cm
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Peso
            </span>

            <span>
              ${composicao.peso || '-'} g
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Acabamento
            </span>

            <span>
              ${composicao.acabamento || '-'}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Cabo
            </span>

            <span>
              ${composicao.tipoCabo || '-'}
            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Bainha
            </span>

            <span>

              ${
                composicao.possuiBainha
                  ? 'Sim'
                  : 'Não'
              }

            </span>

          </div>

        </div>

      </div>

      <!-- OBSERVAÇÕES -->

      ${
        composicao.observacoes
          ? `
            <div class="card mt-5">

              <h3 class="text-xl font-bold mb-4">
                Observações
              </h3>

              <p class="text-slate-300 leading-7">

                ${composicao.observacoes}

              </p>

            </div>
          `
          : ''
      }

      <!-- RESUMO -->

      <div class="card mt-5">

        <h3 class="text-xl font-bold mb-5">
          Resumo Financeiro
        </h3>

        <div class="grid gap-3">

          <div class="flex justify-between">

            <span class="text-slate-400">
              Materiais
            </span>

            <span>

              R$ ${composicao.custoMateriais.toFixed(2)}

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Produção
            </span>

            <span>

              R$ ${composicao.custoEtapas.toFixed(2)}

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Lucro
            </span>

            <span class="text-green-400">

              R$ ${lucro.toFixed(2)}

            </span>

          </div>

          <div class="flex justify-between">

            <span class="text-slate-400">
              Margem
            </span>

            <span>

              ${margem}%

            </span>

          </div>

        </div>

      </div>

      <!-- SHARE -->

      <div class="card mt-5 text-center">

        <button
          id="shareBtn"
          data-id="${composicao.id}"
          class="primary-button w-full"
        >
          Compartilhar orçamento
        </button>

      </div>

    </section>
  `;
}

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.id ===
      'shareBtn'
    ) {

      const composicaoId =
        Number(
          e.target.dataset.id
        );

      const url =
        `${location.origin}${location.pathname}#orcamento/${composicaoId}`;

      if (navigator.share) {

        navigator.share({

          title: 'Orçamento',
          text: 'Confira esta faca personalizada',
          url

        });

      } else {

        await navigator.clipboard.writeText(
          url
        );

        alert(
          'Link copiado!'
        );

      }

    }

  }
);