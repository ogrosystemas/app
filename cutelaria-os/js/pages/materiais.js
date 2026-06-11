import { db, fmtBRL, fmtDate } from '../database/db.js';
import { showToast } from '../components/toast.js';
import { openModal, closeModal } from '../components/modal.js';
import { navigate } from '../core/router.js';

export async function materiaisPage() {
  if (!db.materiais) return `<div class="card"><p style="color:var(--muted)">Tabela materiais não disponível. Recarregue o app.</p></div>`;

  const materiais      = await db.materiais.toArray();
  const valorEstoque   = materiais.reduce((s,i) => s + Number(i.valor||0) * Number(i.estoqueAtual||0), 0);
  const criticos       = materiais.filter(i => Number(i.estoqueAtual||0) <= Number(i.estoqueMinimo||0) && Number(i.estoqueMinimo||0) > 0);

  const stockColor = (item) => {
    const atual = Number(item.estoqueAtual||0);
    const min   = Number(item.estoqueMinimo||0);
    if (min <= 0)              return 'var(--text)';
    if (atual <= min)          return '#f87171';
    if (atual <= min * 1.5)    return '#fb923c';
    return '#34d399';
  };

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Materiais</h1>
          <p>Controle de estoque</p>
        </div>
        <button id="newMaterialBtn" class="btn btn-primary btn-sm">+ Novo</button>
      </div>

      <div class="grid-2" style="gap:10px;margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">Valor em estoque</div>
          <div class="kpi-card__value" style="font-size:18px;color:var(--accent)">${fmtBRL(valorEstoque)}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Itens críticos</div>
          <div class="kpi-card__value" style="color:${criticos.length?'#f87171':'#34d399'}">${criticos.length}</div>
          <div class="kpi-card__sub">${materiais.length} itens no total</div>
        </div>
      </div>

      ${criticos.length ? `
        <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:20px">
          <div style="font-weight:700;font-size:14px;color:#f87171;margin-bottom:8px">
            <i data-lucide="alert-triangle" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Estoque crítico
          </div>
          ${criticos.map(i => `
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-top:1px solid rgba(239,68,68,.1)">
              <span>${i.nome}</span>
              <span style="color:#f87171;font-weight:700">${i.estoqueAtual} ${i.unidade||''} <span style="color:var(--muted);font-weight:400">(mín: ${i.estoqueMinimo})</span></span>
            </div>
          `).join('')}
        </div>
      ` : ''}

      ${!materiais.length ? `
        <div class="card" style="text-align:center;padding:40px 20px">
          <p style="color:var(--muted)">Nenhum material cadastrado ainda.</p>
          <button id="newMaterialBtn2" class="btn btn-primary" style="margin-top:16px;width:auto;display:inline-flex">+ Adicionar material</button>
        </div>
      ` : `
        <div class="grid-stack">
          ${materiais.map(item => {
            const pctBarra = Number(item.estoqueMinimo||0) > 0
              ? Math.min(100, (Number(item.estoqueAtual||0) / Math.max(1, Number(item.estoqueMinimo||0) * 2)) * 100)
              : 0;
            const cor = stockColor(item);

            return `
              <div class="card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
                  <div style="flex:1;min-width:0">
                    <div style="font-size:16px;font-weight:700;margin-bottom:2px">${item.nome}</div>
                    <div style="font-size:13px;color:var(--muted)">${item.categoria||'—'}${item.unidade?' · '+item.unidade:''}</div>
                  </div>
                  <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:15px;font-weight:700;color:var(--accent)">${fmtBRL(item.valor)}</div>
                    <div style="font-size:13px;font-weight:600;color:${cor};margin-top:2px">${item.estoqueAtual||0} ${item.unidade||'un'}</div>
                  </div>
                </div>

                ${Number(item.estoqueMinimo||0) > 0 ? `
                  <div style="margin-bottom:12px">
                    <div class="progress-bar">
                      <div class="progress-bar__fill" style="width:${pctBarra}%;background:${cor}"></div>
                    </div>
                    <div style="font-size:11px;color:var(--muted);margin-top:3px">Mínimo: ${item.estoqueMinimo} ${item.unidade||'un'}</div>
                  </div>
                ` : '<div style="margin-bottom:12px"></div>'}

                <!-- LINHA DE AÇÕES -->
                <div style="display:flex;align-items:center;gap:6px">

                  <!-- AJUSTE RÁPIDO DE ESTOQUE -->
                  <button class="btn btn-ghost btn-sm stock-dec-btn" data-id="${item.id}" data-step="-1"
                    style="width:34px;height:34px;padding:0;font-size:18px;flex-shrink:0" title="Retirar 1 ${item.unidade||'un'}">−</button>

                  <span style="font-size:13px;font-weight:700;min-width:48px;text-align:center;color:${cor}">
                    ${item.estoqueAtual||0} ${item.unidade||'un'}
                  </span>

                  <button class="btn btn-ghost btn-sm stock-inc-btn" data-id="${item.id}" data-step="1"
                    style="width:34px;height:34px;padding:0;font-size:18px;flex-shrink:0" title="Adicionar 1 ${item.unidade||'un'}">+</button>

                  <div style="flex:1"></div>

                  <button class="btn btn-ghost btn-sm edit-material-btn" data-id="${item.id}">
                    <i data-lucide="pencil" style="width:14px;height:14px"></i>
                  </button>
                  <button class="btn btn-danger btn-sm delete-material-btn" data-id="${item.id}">
                    <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                  </button>
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `}
    </section>
  `;
}

// ============================================
// MODAL
// ============================================

function openMaterialModal(existing = null) {
  openModal({
    title: existing ? 'Editar Material' : 'Novo Material',
    size: 'md',
    content: `
      <form id="materialForm" class="grid-stack">
        <div>
          <label>Nome *</label>
          <input type="text" id="matNome" placeholder="Ex: Aço 1070" value="${existing?.nome||''}" />
        </div>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Categoria</label>
            <input type="text" id="matCat" placeholder="Aço, Cabo, Consumível..." value="${existing?.categoria||''}" />
          </div>
          <div>
            <label>Unidade</label>
            <input type="text" id="matUnidade" placeholder="kg, m, un, peça..." value="${existing?.unidade||''}" />
          </div>
        </div>
        <div>
          <label>Valor unitário (R$)</label>
          <input type="number" step="0.01" min="0" id="matValor" placeholder="0,00" value="${existing?.valor||''}" />
        </div>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Estoque atual</label>
            <input type="number" step="0.01" min="0" id="matAtual" placeholder="0" value="${existing?.estoqueAtual||''}" />
          </div>
          <div>
            <label>Estoque mínimo</label>
            <input type="number" step="0.01" min="0" id="matMinimo" placeholder="0" value="${existing?.estoqueMinimo||''}" />
          </div>
        </div>
        <button type="submit" class="primary-button">${existing ? 'Salvar' : 'Cadastrar material'}</button>
      </form>
    `
  });

  document.getElementById('materialForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('matNome').value.trim();
    if (!nome) { showToast({ type:'error', message:'Informe o nome.' }); return; }
    const data = {
      nome,
      categoria:    document.getElementById('matCat').value.trim(),
      unidade:      document.getElementById('matUnidade').value.trim(),
      valor:        parseFloat(document.getElementById('matValor').value)  || 0,
      estoqueAtual: parseFloat(document.getElementById('matAtual').value)  || 0,
      estoqueMinimo:parseFloat(document.getElementById('matMinimo').value) || 0,
    };
    if (existing) {
      await db.materiais.update(existing.id, data);
      showToast({ message: 'Material atualizado.' });
    } else {
      data.createdAt = new Date().toISOString();
      await db.materiais.add(data);
      showToast({ message: 'Material cadastrado!' });
    }
    closeModal();
    setTimeout(() => navigate('materiais'), 300);
  });
}

// ============================================
// EVENTS
// ============================================

window.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-id]');
  const id  = btn ? Number(btn.dataset.id) : null;

  // Botão novo
  if (e.target.id === 'newMaterialBtn'  || e.target.closest('#newMaterialBtn') ||
      e.target.id === 'newMaterialBtn2' || e.target.closest('#newMaterialBtn2')) {
    openMaterialModal(); return;
  }

  // Editar
  if (e.target.closest('.edit-material-btn') && id) {
    const item = await db.materiais.get(id);
    if (item) openMaterialModal(item);
    return;
  }

  // Excluir
  if (e.target.closest('.delete-material-btn') && id) {
    if (!confirm('Excluir este material?')) return;
    await db.materiais.delete(id);
    showToast({ message: 'Material excluído.' });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }

  // Ajuste rápido − (retirar)
  if (e.target.closest('.stock-dec-btn') && id) {
    const item = await db.materiais.get(id);
    if (!item) return;
    const novoEstoque = Math.max(0, Number(item.estoqueAtual||0) - 1);
    await db.materiais.update(id, { estoqueAtual: novoEstoque });
    showToast({ message: `${item.nome}: ${novoEstoque} ${item.unidade||'un'}` });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }

  // Ajuste rápido + (adicionar)
  if (e.target.closest('.stock-inc-btn') && id) {
    const item = await db.materiais.get(id);
    if (!item) return;
    const novoEstoque = Number(item.estoqueAtual||0) + 1;
    await db.materiais.update(id, { estoqueAtual: novoEstoque });
    showToast({ message: `${item.nome}: ${novoEstoque} ${item.unidade||'un'}` });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }
});
