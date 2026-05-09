import { db } from '../database/db.js';

import {

  exportarBackup,
  importarBackup

} from '../services/backup.service.js';

// =========================
// PAGE
// =========================

export async function configuracoesPage() {

  // ========================================
  // SAFE TABLE
  // ========================================

  if (!db.settings) {

    return `

      <section class="pb-32">

        <div class="card">

          <h2 class="
            text-2xl
            font-bold
            mb-4
          ">

            Banco de dados não atualizado

          </h2>

          <p class="text-slate-400">

            A tabela "settings"
            não existe no IndexedDB.

          </p>

        </div>

      </section>

    `;

  }

  const settings =
    await db.settings
      .toCollection()
      .first();

  return `

    <section class="pb-32">

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

      </div>

      <div class="card">

        <div class="grid gap-5">

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

    </section>

  `;

}