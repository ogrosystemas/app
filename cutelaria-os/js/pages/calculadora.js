import {

  calculateKnifeCost

} from '../services/cost-engine.js';

export async function calculadoraPage() {

  return `

    <section class="pb-32">

      <!-- HEADER -->

      <div class="
        flex
        items-center
        justify-between
        mb-8
      ">

        <div>

          <h1 class="
            text-4xl
            font-black
            mb-2
          ">

            Calculadora

          </h1>

          <p class="
            text-slate-400
            text-lg
          ">

            Precificação industrial

          </p>

        </div>

        <div class="
          w-20
          h-20
          rounded-[28px]

          flex
          items-center
          justify-center

          bg-gradient-to-br
          from-orange-500
          to-orange-700

          shadow-2xl
        ">

          <i
            data-lucide="calculator"
            class="w-10 h-10 text-white"
          ></i>

        </div>

      </div>

      <!-- FORM -->

      <div class="card mb-6">

        <div class="
          grid
          gap-5
        ">

          <!-- MATERIAL -->

          <div>

            <label>

              Materiais (R$)

            </label>

            <input
              id="materialCost"
              type="number"
              value="200"
            />

          </div>

          <!-- GAS -->

          <div>

            <label>

              Gás / carvão (R$)

            </label>

            <input
              id="gasCost"
              type="number"
              value="35"
            />

          </div>

          <!-- ENERGIA -->

          <div>

            <label>

              Energia (R$)

            </label>

            <input
              id="energyCost"
              type="number"
              value="18"
            />

          </div>

          <!-- CONSUMIVEIS -->

          <div>

            <label>

              Consumíveis (R$)

            </label>

            <input
              id="consumablesCost"
              type="number"
              value="25"
            />

          </div>

          <!-- HORAS -->

          <div>

            <label>

              Horas trabalhadas

            </label>

            <input
              id="hoursWorked"
              type="number"
              value="6"
            />

          </div>

          <!-- CUSTO HORA -->

          <div>

            <label>

              Custo/hora

            </label>

            <input
              id="hourlyRate"
              type="number"
              value="50"
            />

          </div>

          <!-- DEPRECIACAO -->

          <div>

            <label>

              Depreciação (R$)

            </label>

            <input
              id="depreciationCost"
              type="number"
              value="20"
            />

          </div>

          <!-- MARGEM -->

          <div>

            <label>

              Margem (%)

            </label>

            <input
              id="marginPercent"
              type="number"
              value="120"
            />

          </div>

          <!-- BOTAO -->

          <button
            id="calculateKnifeButton"
            class="primary-button mt-4"
          >

            Calcular preço

          </button>

        </div>

      </div>

      <!-- RESULTADO -->

      <div
        id="calculatorResult"
        class="grid gap-5"
      >

      </div>

    </section>

  `;

}

// EVENTO

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.id ===
      'calculateKnifeButton'
    ) {

      const materialCost =

        Number(
          document.getElementById(
            'materialCost'
          ).value
        );

      const gasCost =

        Number(
          document.getElementById(
            'gasCost'
          ).value
        );

      const energyCost =

        Number(
          document.getElementById(
            'energyCost'
          ).value
        );

      const consumablesCost =

        Number(
          document.getElementById(
            'consumablesCost'
          ).value
        );

      const hoursWorked =

        Number(
          document.getElementById(
            'hoursWorked'
          ).value
        );

      const hourlyRate =

        Number(
          document.getElementById(
            'hourlyRate'
          ).value
        );

      const depreciationCost =

        Number(
          document.getElementById(
            'depreciationCost'
          ).value
        );

      const marginPercent =

        Number(
          document.getElementById(
            'marginPercent'
          ).value
        );

      const result =
        calculateKnifeCost({

          materials: [

            {
              quantity: 1,
              unitCost:
                materialCost
            }

          ],

          operational: {

            gasCost,

            energyCost,

            consumablesCost

          },

          labor: {

            hoursWorked,

            hourlyRate

          },

          equipments: [

            {

              purchaseValue:
                depreciationCost,

              usefulLifeMonths: 1,

              monthlyUsage: 1,

              knifeUsage: 1

            }

          ],

          marginPercent

        });

      document.getElementById(
        'calculatorResult'
      ).innerHTML = `

        <div class="card">

          <h2 class="
            text-2xl
            font-bold
            mb-6
          ">

            Resultado Financeiro

          </h2>

          <div class="
            grid
            gap-4
          ">

            ${createResultItem(
              'Materiais',
              result.materialCost
            )}

            ${createResultItem(
              'Operacional',
              result.operationalCost
            )}

            ${createResultItem(
              'Mão de obra',
              result.laborCost
            )}

            ${createResultItem(
              'Depreciação',
              result.depreciationCost
            )}

            <div class="
              h-px
              bg-slate-700
              my-2
            "></div>

            ${createHighlightItem(
              'Custo Total',
              result.totalCost
            )}

            ${createHighlightItem(
              'Preço sugerido',
              result.suggestedPrice
            )}

            ${createHighlightItem(
              'Lucro líquido',
              result.netProfit
            )}

            ${createHighlightItem(
              'Margem',
              result.margin + '%'
            )}

          </div>

        </div>

      `;

      if (window.lucide) {

        lucide.createIcons();

      }

    }

  }
);

// RESULT ITEM

function createResultItem(
  label,
  value
) {

  return `

    <div class="
      flex
      items-center
      justify-between
    ">

      <span class="
        text-slate-400
      ">

        ${label}

      </span>

      <strong>

        ${formatCurrency(value)}

      </strong>

    </div>

  `;

}

// HIGHLIGHT

function createHighlightItem(
  label,
  value
) {

  return `

    <div class="
      flex
      items-center
      justify-between

      bg-orange-500/10

      border
      border-orange-500/20

      rounded-2xl

      px-5
      py-4
    ">

      <span class="
        text-orange-300
        font-semibold
      ">

        ${label}

      </span>

      <strong class="
        text-orange-400
        text-xl
      ">

        ${
          typeof value === 'number'

            ? formatCurrency(value)

            : value
        }

      </strong>

    </div>

  `;

}

// FORMAT

function formatCurrency(
  value
) {

  return new Intl.NumberFormat(
    'pt-BR',
    {

      style: 'currency',

      currency: 'BRL'

    }
  ).format(value);

}