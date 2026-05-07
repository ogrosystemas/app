import {
  exportarBackup,
  importarBackup
} from '../services/backup.service.js';

import {
  showToast
} from '../modules/toast.js';

export async function configuracoesPage() {

  return `
    <section>

      <div class="card">

        <h2 class="text-2xl font-bold mb-6">

          Configurações

        </h2>

        <!-- BACKUP -->

        <div class="mb-8">

          <h3 class="font-bold text-lg mb-3">
            Backup
          </h3>

          <button
            id="exportBackupBtn"
            class="primary-button w-full"
          >
            Exportar Backup
          </button>

        </div>

        <!-- RESTORE -->

        <div class="mb-8">

          <h3 class="font-bold text-lg mb-3">
            Restaurar Backup
          </h3>

          <input
            type="file"
            id="restoreInput"
            accept=".json"
            class="input"
          />

        </div>

        <!-- APP -->

        <div>

          <h3 class="font-bold text-lg mb-3">
            Sobre
          </h3>

          <div class="text-slate-400 text-sm leading-7">

            <p>
              Cutelaria OS
            </p>

            <p>
              Sistema profissional para cuteleiros.
            </p>

            <p class="mt-3">
              Versão: 1.0.0
            </p>

          </div>

        </div>

      </div>

    </section>
  `;
}

window.addEventListener(
  'click',
  async (e) => {

    if (
      e.target.id ===
      'exportBackupBtn'
    ) {

      await exportarBackup();

      showToast(
        'Backup exportado!'
      );

    }

  }
);

window.addEventListener(
  'change',
  async (e) => {

    if (
      e.target.id ===
      'restoreInput'
    ) {

      const file =
        e.target.files[0];

      if (!file) return;

      try {

        await importarBackup(
          file
        );

        showToast(
          'Backup restaurado!'
        );

        setTimeout(() => {

          location.reload();

        }, 1200);

      } catch {

        showToast(
          'Erro ao restaurar backup'
        );

      }

    }

  }
);