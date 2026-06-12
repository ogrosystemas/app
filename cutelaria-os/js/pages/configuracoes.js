import { db, fmtDate } from '../database/db.js';
import { exportarBackup, importarBackup } from '../services/backup.service.js';
import { showToast } from '../components/toast.js';
import { navigate  } from '../core/router.js';

export async function configuracoesPage() {
  const settings = await db.settings.toCollection().first();

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Configurações</h1>
          <p>Oficina e dados</p>
        </div>
      </div>

      <!-- OFICINA -->
      <div class="card" style="margin-bottom:16px">
        <h2 style="font-size:16px;font-weight:800;margin-bottom:18px">
          <i class="ph ph-user" style="width:16px;height:16px;vertical-align:-2px;margin-right:6px"></i>
          Minha Oficina
        </h2>
        <form id="settingsForm" class="grid-stack">
          <div>
            <label>Nome da oficina</label>
            <input id="workshopName" type="text" placeholder="Ex: Cutelaria do João" value="${settings?.oficinaNome||''}" />
          </div>
          <div>
            <label>Nome do cuteleiro</label>
            <input id="cutlerName" type="text" placeholder="Seu nome" value="${settings?.cuteleiroNome||''}" />
          </div>
          <div class="grid-2" style="gap:12px">
            <div>
              <label>Margem padrão (%)</label>
              <input id="defaultMargin" type="number" min="0" max="500" value="${settings?.margemPadrao||100}" />
            </div>
            <div>
              <label>Custo/hora (R$)</label>
              <input id="hourCost" type="number" min="0" step="0.01" value="${settings?.custoHora||50}" />
            </div>
          </div>
          <button type="submit" class="primary-button">Salvar configurações</button>
        </form>
      </div>

      <!-- BACKUP -->
      <div class="card" style="margin-bottom:16px">
        <h2 style="font-size:16px;font-weight:800;margin-bottom:8px">
          <i class="ph ph-download-simple" style="width:16px;height:16px;vertical-align:-2px;margin-right:6px"></i>
          Backup
        </h2>
        <p style="color:var(--muted);font-size:14px;margin-bottom:16px">Exporte todos os dados da oficina como arquivo JSON.</p>
        <button id="exportBackupButton" class="btn btn-ghost btn-full">
          <i class="ph ph-download-simple" style="width:16px;height:16px"></i> Exportar backup
        </button>
      </div>

      <!-- RESTAURAR -->
      <div class="card">
        <h2 style="font-size:16px;font-weight:800;margin-bottom:8px">
          <i class="ph ph-upload-simple" style="width:16px;height:16px;vertical-align:-2px;margin-right:6px"></i>
          Restaurar
        </h2>
        <p style="color:var(--muted);font-size:14px;margin-bottom:16px">Importe um backup salvo anteriormente.</p>
        <label for="restoreBackupInput" style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--radius-md);padding:13px 16px;cursor:pointer;font-weight:600;font-size:14px">
          <i class="ph ph-upload-simple" style="width:16px;height:16px;color:var(--accent)"></i>
          Selecionar arquivo .json
        </label>
        <input id="restoreBackupInput" type="file" accept=".json" style="position:absolute;opacity:0;pointer-events:none" />
      </div>

    </section>
  `;
}

// ============================================
// SALVAR
// ============================================

window.addEventListener('submit', async (e) => {
  if (e.target.id !== 'settingsForm') return;
  e.preventDefault();

  try {
    await db.settings.clear();
    const data = {
      oficinaNome:  document.getElementById('workshopName').value.trim(),
      cuteleiroNome:document.getElementById('cutlerName').value.trim(),
      margemPadrao: Number(document.getElementById('defaultMargin').value) || 100,
      custoHora:    Number(document.getElementById('hourCost').value) || 50,
      createdAt:    new Date().toISOString()
    };
    await db.settings.add(data);
    localStorage.setItem('cutelaria_workshop_name', data.oficinaNome);
    localStorage.setItem('cutelaria_hour_cost', data.custoHora);
    showToast({ message: 'Configurações salvas!' });
  } catch (err) {
    console.error(err);
    showToast({ type:'error', message: 'Erro ao salvar.' });
  }
});

// ============================================
// EVENTOS
// ============================================

window.addEventListener('click', async (e) => {
  if (e.target.id === 'exportBackupButton' || e.target.closest('#exportBackupButton')) {
    exportarBackup();
  }
});

window.addEventListener('change', async (e) => {
  if (e.target.id === 'restoreBackupInput') {
    const file = e.target.files[0];
    if (file) importarBackup(file);
  }
});
