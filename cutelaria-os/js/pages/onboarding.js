import { db } from '../database/db.js';

export async function onboardingPage() {

  return `

    <section class="
      min-h-screen
      flex
      flex-col
      items-center
      justify-center

      pb-24
    ">

      <!-- HERO -->

      <div class="
        text-center
        mb-10
      ">

        <div class="
          w-24
          h-24
          mx-auto
          mb-6

          rounded-[32px]

          bg-gradient-to-br
          from-orange-500
          to-orange-700

          flex
          items-center
          justify-center

          shadow-2xl
        ">

          <i
            data-lucide="trophy"
            class="w-12 h-12 text-white"
          ></i>

        </div>

        <h1 class="
          text-5xl
          font-black
          mb-4
        ">

          Bem-vindo ao

          <span class="text-orange-400">

            Cutelaria OS

          </span>

        </h1>

        <p class="
          text-slate-400
          text-xl
          max-w-2xl
        ">

          Configure sua oficina
          para iniciar o ERP
          industrial de cutelaria.

        </p>

      </div>

      <!-- FORM -->

      <div class="
        card
        w-full
        max-w-3xl
        mb-8
      ">

        <div class="
          grid
          gap-5
        ">

          <!-- OFICINA -->

          <div>

            <label>

              Nome da oficina

            </label>

            <input
              id="onboardingWorkshop"
              type="text"
              placeholder="Ex.: Barcelos Knives"
            />

          </div>

          <!-- CUTELEIRO -->

          <div>

            <label>

              Nome do cuteleiro

            </label>

            <input
              id="onboardingOwner"
              type="text"
              placeholder="Ex.: Tiburcio Barcelos"
            />

          </div>

          <!-- MARGEM -->

          <div>

            <label>

              Margem padrão (%)

            </label>

            <input
              id="onboardingMargin"
              type="number"
              value="100"
            />

          </div>

          <!-- HORA -->

          <div>

            <label>

              Custo/hora da oficina

            </label>

            <input
              id="onboardingHourCost"
              type="number"
              value="50"
            />

          </div>

          <!-- META -->

          <div>

            <label>

              Meta mensal (R$)

            </label>

            <input
              id="onboardingGoal"
              type="number"
              value="10000"
            />

          </div>

          <!-- PERFIL -->

          <div>

            <label>

              Perfil da oficina

            </label>

            <select
              id="onboardingProfile"
            >

              <option value="Artesanal">

                Artesanal

              </option>

              <option value="Premium">

                Premium

              </option>

              <option value="Industrial">

                Industrial

              </option>

            </select>

          </div>

          <!-- BOTAO -->

          <button
            id="finishOnboardingButton"
            class="primary-button mt-4"
          >

            Finalizar configuração

          </button>

        </div>

      </div>

      <!-- FEATURES -->

      <div class="
        grid
        gap-5
        w-full
        max-w-3xl
      ">

        ${featureCard(
          'wallet',
          'Precificação inteligente',
          'Descubra o custo real das suas facas e aumente sua margem com precisão.'
        )}

        ${featureCard(
          'chart-column',
          'Dashboard industrial',
          'Controle produção, lucro, materiais e crescimento da oficina.'
        )}

      </div>

    </section>

  `;

}

// ======================
// FEATURE CARD
// ======================

function featureCard(
  icon,
  title,
  description
) {

  return `

    <div class="
      card
      flex
      items-start
      gap-5
    ">

      <div class="
        w-14
        h-14

        rounded-2xl

        bg-orange-500/10

        flex
        items-center
        justify-center
      ">

        <i
          data-lucide="${icon}"
          class="
            w-6
            h-6
            text-orange-400
          "
        ></i>

      </div>

      <div>

        <h3 class="
          text-2xl
          font-bold
          mb-2
        ">

          ${title}

        </h3>

        <p class="
          text-slate-400
          leading-relaxed
        ">

          ${description}

        </p>

      </div>

    </div>

  `;

}

// ======================
// SAVE CONFIG
// ======================

window.addEventListener(
  'click',
  async (event) => {

    if (
      event.target.id !==
      'finishOnboardingButton'
    ) {

      return;

    }

    try {

      const oficinaNome =

        document.getElementById(
          'onboardingWorkshop'
        ).value.trim();

      const cuteleiroNome =

        document.getElementById(
          'onboardingOwner'
        ).value.trim();

      const margemPadrao =

        Number(
          document.getElementById(
            'onboardingMargin'
          ).value
        );

      const custoHora =

        Number(
          document.getElementById(
            'onboardingHourCost'
          ).value
        );

      const metaMensal =

        Number(
          document.getElementById(
            'onboardingGoal'
          ).value
        );

      const perfilOficina =

        document.getElementById(
          'onboardingProfile'
        ).value;

      // VALIDACAO

      if (
        !oficinaNome
        ||
        !cuteleiroNome
      ) {

        alert(
          'Preencha todos os campos.'
        );

        return;

      }

      // LIMPA SETTINGS ANTIGO

      await db.settings.clear();

      // SALVA CONFIG

      await db.settings.add({

        oficinaNome,

        cuteleiroNome,

        margemPadrao,

        custoHora,

        metaMensal,

        perfilOficina,

        createdAt:
          new Date().toISOString()

      });

      console.log(
        'Onboarding salvo'
      );

      // REDIRECIONA

      window.location.hash =
        '#dashboard';

      // FORÇA RENDER

      setTimeout(() => {

        window.dispatchEvent(
          new HashChangeEvent(
            'hashchange'
          )
        );

      }, 100);

    } catch (error) {

      console.error(error);

      alert(
        'Erro ao salvar configuração.'
      );

    }

  }
);