import { db } from '../database/db.js';

import {

  exportBackup,
  importBackup

} from '../services/backup.service.js';

// =========================
// PAGE
// =========================

export async function configuracoesPage() {

  const settings =
    await db.settings
      .toCollection()
      .first();

  return `

    <section class="pb-32">

      <!-- HERO -->

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

            Configurações

          </h1>

          <p class="
            text-slate-400
            text-lg
          ">

            Oficina e segurança

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
            data-lucide="settings"
            class="w-10 h-10 text-white"
          ></i>

        </div>

      </div>

      <!-- OFICINA -->

      <div class="
        card
        mb-6
      ">

        <h2 class="
          text-2xl
          font-bold
          mb-6
        ">

          Oficina

        </h2>

        <div class="
          grid
          gap-5
        ">

          <div>

            <label>

              Nome da oficina

            </label>

            <input
              id="workshopName"
              type="text"
              value="${settings?.oficinaNome || ''}"
            />

          </div>

          <div>

            <label>

              Nome do cuteleiro

            </label>

            <input
              id="cutlerName"
              type="text"
              value="${settings?.cuteleiroNome || ''}"
            />

          </div>

          <div>

            <label>

              Margem padrão (%)

            </label>

            <input
              id="defaultMargin"
              type="number"
              value="${settings?.margemPadrao || 100}"
            />

          </div>

          <div>

            <label>

              Custo/hora

            </label>

            <input
              id="hourCost"
              type="number"
              value="${settings?.custoHora || 50}"
            />

          </div>

          <button
            id="saveSettingsButton"
            class="primary-button mt-4"
          >

            Salvar configurações

          </button>

        </div>

      </div>

      <!-- BACKUP -->

      <div class="
        card
        mb-6
      ">

        <h2 class="
          text-2xl
          font-bold
          mb-4
        ">

          Backup

        </h2>

        <p class="
          text-slate-400
          mb-6
        ">

          Exporte todos os dados
          da oficina para segurança.

        </p>

        <button
          id="exportBackupButton"
          class="primary-button"
        >

          Exportar Backup

        </button>

      </div>

      <!-- RESTORE -->

      <div class="card">

        <h2 class="
          text-2xl
          font-bold
          mb-4
        ">

          Restaurar Backup

        </h2>

        <p class="
          text-slate-400
          mb-6
        ">

          Importe um backup salvo.

        </p>

        <input
          id="restoreBackupInput"
          type="file"
          accept=".json"
        />

      </div>

    </section>

  `;

}

// =========================
// SAVE SETTINGS
// =========================

window.addEventListener(
  'click',
  async (event) => {

    if (
      event.target.id !==
      'saveSettingsButton'
    ) {

      return;

    }

    try {

      await db.settings.clear();

      const oficinaNome =

        document.getElementById(
          'workshopName'
        ).value;

      const cuteleiroNome =

        document.getElementById(
          'cutlerName'
        ).value;

      const margemPadrao =

        Number(
          document.getElementById(
            'defaultMargin'
          ).value
        );

      const custoHora =

        Number(
          document.getElementById(
            'hourCost'
          ).value
        );

      await db.settings.add({

        oficinaNome,

        cuteleiroNome,

        margemPadrao,

        custoHora,

        createdAt:
          new Date().toISOString()

      });

      localStorage.setItem(
        'cutelaria_workshop_name',
        oficinaNome
      );

      localStorage.setItem(
        'cutelaria_hour_cost',
        custoHora
      );

      alert(
        'Configurações salvas.'
      );

    } catch (error) {

      console.error(
        error
      );

      alert(
        'Erro ao salvar.'
      );

    }

  }
);

// =========================
// EXPORT BACKUP
// =========================

window.addEventListener(
  'click',
  async (event) => {

    if (
      event.target.id !==
      'exportBackupButton'
    ) {

      return;

    }

    exportBackup();

  }
);

// =========================
// IMPORT BACKUP
// =========================

window.addEventListener(
  'change',
  async (event) => {

    if (
      event.target.id !==
      'restoreBackupInput'
    ) {

      return;

    }

    const file =
      event.target.files[0];

    if (!file) {

      return;

    }

    importBackup(
      file
    );

  }
);