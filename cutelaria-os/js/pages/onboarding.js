import { db } from '../database/db.js';

export async function onboardingPage() {

  return `

    <section class="pb-32">

      <!-- HERO -->

      <div class="
        text-center
        mb-10
      ">

        <div class="
          w-24
          h-24
          rounded-[28px]
          mx-auto
          mb-6

          flex
          items-center
          justify-center

          bg-gradient-to-br
          from-orange-500
          to-orange-700

          shadow-2xl
        ">

          <i
            data-lucide="anvil"
            class="w-12 h-12 text-white"
          ></i>

        </div>

        <h1 class="
          text-4xl
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
          text-lg
          leading-relaxed
        ">

          Configure sua oficina
          para iniciar o ERP
          industrial de cutelaria.

        </p>

      </div>

      <!-- CARD -->

      <div class="card">

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
              id="oficinaNome"
              type="text"
              placeholder="
                Ex:
                Barcelos Knives
              "
            />

          </div>

          <!-- CUTELEIRO -->

          <div>

            <label>

              Nome do cuteleiro

            </label>

            <input
              id="cuteleiroNome"
              type="text"
              placeholder="
                Ex:
                Tiburcio Barcelos
              "
            />

          </div>

          <!-- MARGEM -->

          <div>

            <label>

              Margem padrão (%)

            </label>

            <input
              id="margemPadrao"
              type="number"
              value="100"
            />

          </div>

          <!-- CUSTO HORA -->

          <div>

            <label>

              Custo/hora da oficina

            </label>

            <input
              id="custoHora"
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
              id="metaMensal"
              type="number"
              value="10000"
            />

          </div>

          <!-- TIPO -->

          <div>

            <label>

              Perfil da oficina

            </label>

            <select id="perfilOficina">

              <option value="artesanal">

                Artesanal

              </option>

              <option value="premium">

                Premium

              </option>

              <option value="industrial">

                Industrial

              </option>

            </select>

          </div>

          <!-- BOTAO -->

          <button
            id="saveOnboarding"
            class="
              primary-button
              mt-4
            "
          >

            Finalizar configuração

          </button>

        </div>

      </div>

      <!-- BENEFICIOS -->

      <div class="
        grid
        gap-5
        mt-8
      ">

        <div class="card">

          <div class="
            flex
            items-start
            gap-4
          ">

            <div class="
              w-14
              h-14
              rounded-2xl

              flex
              items-center
              justify-center

              bg-orange-500/20
              text-orange-400
            ">

              <i data-lucide="wallet"></i>

            </div>

            <div>

              <h2 class="
                text-xl
                font-bold
                mb-2
              ">

                Precificação inteligente

              </h2>

              <p class="
                text-slate-400
                leading-relaxed
              ">

                Descubra o custo real
                das suas facas e aumente
                sua margem com precisão.

              </p>

            </div>

          </div>

        </div>

        <div class="card">

          <div class="
            flex
            items-start
            gap-4
          ">

            <div class="
              w-14
              h-14
              rounded-2xl

              flex
              items-center
              justify-center

              bg-orange-500/20
              text-orange-400
            ">

              <i data-lucide="bar-chart-3"></i>

            </div>

            <div>

              <h2 class="
                text-xl
                font-bold
                mb-2
              ">

                Dashboard industrial

              </h2>

              <p class="
                text-slate-400
                leading-relaxed
              ">

                Controle produção,
                lucro, materiais e
                crescimento da oficina.

              </p>

            </div>

          </div>

        </div>

      </div>

    </section>

  `;

}

// SALVAR

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.id ===
      'saveOnboarding'
    ) {

      const settings = {

        oficinaNome:

          document.getElementById(
            'oficinaNome'
          ).value,

        cuteleiroNome:

          document.getElementById(
            'cuteleiroNome'
          ).value,

        margemPadrao:

          Number(
            document.getElementById(
              'margemPadrao'
            ).value
          ),

        custoHora:

          Number(
            document.getElementById(
              'custoHora'
            ).value
          ),

        metaMensal:

          Number(
            document.getElementById(
              'metaMensal'
            ).value
          ),

        perfilOficina:

          document.getElementById(
            'perfilOficina'
          ).value,

        onboardingCompleto: true,

        createdAt:
          new Date().toISOString()

      };

      try {

        await db.settings.clear();

        await db.settings.add(
          settings
        );

        alert(
          'Configuração salva com sucesso.'
        );

        window.location.hash =
          '#dashboard';

      } catch (error) {

        console.error(error);

        alert(
          'Erro ao salvar configurações.'
        );

      }

    }

  }
);