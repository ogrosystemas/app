import { db, fmtBRL, fmtDate, STATUS_PRODUCAO, STATUS_PEDIDO } from '../database/db.js';
import { emptyState } from '../components/empty-state.js';
import { showToast  } from '../components/toast.js';
import { openModal, closeModal } from '../components/modal.js';
import { navigate } from '../core/router.js';

const STATUS_LIST = Object.values(STATUS_PRODUCAO);

const STATUS_PROGRESS = {
  [STATUS_PRODUCAO.INICIADA]:   5,
  [STATUS_PRODUCAO.FORJAMENTO]: 30,
  [STATUS_PRODUCAO.TEMPERA]:    60,
  [STATUS_PRODUCAO.ACABAMENTO]: 85,
  [STATUS_PRODUCAO.FINALIZADA]: 100,
};

const statusBadge = (status) => {
  const map = {
    [STATUS_PRODUCAO.INICIADA]:   'badge-gray',
    [STATUS_PRODUCAO.FORJAMENTO]: 'badge-orange',
    [STATUS_PRODUCAO.TEMPERA]:    'badge-blue',
    [STATUS_PRODUCAO.ACABAMENTO]: 'badge-purple',
    [STATUS_PRODUCAO.FINALIZADA]: 'badge-green',
  };
  return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
};

export async function producaoPage() {
  const [producao, pedidos] = await Promise.all([
    db.producao ? db.producao.orderBy('id').reverse().toArray() : [],
    db.pedidos  ? db.pedidos.toArray() : [],
  ]);

  // Mapa rápido id → pedido para exibir vínculo nos cards
  const pedidoMap = Object.fromEntries(pedidos.map(p => [p.id, p]));

  const ativas     = producao.filter(i => i.status !== STATUS_PRODUCAO.FINALIZADA).length;
  const finalizadas = producao.filter(i => i.status === STATUS_PRODUCAO.FINALIZADA).length;

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Produção</h1>
          <p>Controle da oficina</p>
        </div>
        <button id="newProductionButton" class="btn btn-primary btn-sm">+ Nova</button>
      </div>

      <div class="grid-2" style="gap:10px;margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">Em andamento</div>
          <div class="kpi-card__value" style="color:var(--accent)">${ativas}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Finalizadas</div>
          <div class="kpi-card__value" style="color:#34d399">${finalizadas}</div>
        </div>
      </div>

      ${!producao.length ? emptyState({
        icon: 'hammer',
        title: 'Nenhuma faca em produção',
        description: 'Inicie uma produção para acompanhar cada etapa da fabricação.'
      }) : `
        <div class="grid-stack">
          ${producao.map(item => {
            const pct     = item.progresso ?? STATUS_PROGRESS[item.status] ?? 0;
            const pedido  = item.pedidoId ? pedidoMap[item.pedidoId] : null;

            return `
              <div class="card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
                  <div style="flex:1;min-width:0">
                    <h2 style="font-size:18px;font-weight:800;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.nome || 'Faca artesanal'}</h2>
                    <p style="color:var(--muted);font-size:13px">${fmtDate(item.createdAt)}</p>
                  </div>
                  ${statusBadge(item.status)}
                </div>

                ${pedido ? `
                  <div style="display:flex;align-items:center;gap:8px;background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.2);border-radius:10px;padding:8px 12px;margin-bottom:12px">
                    <i data-lucide="shopping-bag" style="width:14px;height:14px;color:#c084fc;flex-shrink:0"></i>
                    <span style="font-size:13px;color:#c084fc;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                      Pedido: ${pedido.nome}${pedido.cliente ? ' — ' + pedido.cliente : ''}
                    </span>
                    ${pedido.prazo ? `<span style="font-size:11px;color:var(--muted);margin-left:auto;flex-shrink:0">prazo ${fmtDate(pedido.prazo+'T00:00:00')}</span>` : ''}
                  </div>
                ` : ''}

                ${item.acoTipo || item.caboMaterial ? `
                  <div style="display:flex;gap:16px;margin-bottom:12px">
                    ${item.acoTipo        ? `<div><div style="font-size:11px;color:var(--muted)">Aço</div><div style="font-size:14px;font-weight:600">${item.acoTipo}</div></div>` : ''}
                    ${item.caboMaterial   ? `<div><div style="font-size:11px;color:var(--muted)">Cabo</div><div style="font-size:14px;font-weight:600">${item.caboMaterial}</div></div>` : ''}
                    ${item.comprimentoLamina ? `<div><div style="font-size:11px;color:var(--muted)">Lâmina</div><div style="font-size:14px;font-weight:600">${item.comprimentoLamina}cm</div></div>` : ''}
                  </div>
                ` : ''}

                <div style="margin-bottom:14px">
                  <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
                    <span style="color:var(--muted)">Progresso</span>
                    <strong style="color:var(--accent)">${pct}%</strong>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-bar__fill" style="width:${pct}%"></div>
                  </div>
                </div>

                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
                  ${STATUS_LIST.filter(s => s !== item.status).map(s => `
                    <button class="advance-status-btn btn btn-ghost btn-sm" data-id="${item.id}" data-status="${s}" style="font-size:11px;padding:5px 10px">
                      → ${s}
                    </button>
                  `).join('')}
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap">
                  <button class="btn btn-ghost btn-sm edit-prod-btn" data-id="${item.id}">
                    <i data-lucide="pencil" style="width:14px;height:14px"></i> Editar
                  </button>
                  <button class="btn btn-danger btn-sm delete-prod-btn" data-id="${item.id}" style="margin-left:auto">
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
// MODAL — abre com lista de pedidos abertos
// ============================================

async function openProducaoModal(existing = null) {
  // Carrega pedidos abertos para o select
  const pedidosAbertos = db.pedidos
    ? await db.pedidos
        .filter(p => p.status === STATUS_PEDIDO.ABERTO || p.status === STATUS_PEDIDO.EM_PRODUCAO)
        .toArray()
    : [];

  const pedidoOptions = [
    `<option value="">— Sem vínculo —</option>`,
    ...pedidosAbertos.map(p => `
      <option value="${p.id}" ${existing?.pedidoId === p.id ? 'selected' : ''}>
        ${p.nome}${p.cliente ? ' — ' + p.cliente : ''} (${fmtBRL(p.valor)})
      </option>
    `)
  ].join('');

  openModal({
    title: existing ? 'Editar Produção' : 'Nova Produção',
    size: 'md',
    content: `
      <form id="producaoForm" class="grid-stack">
        <div>
          <label>Nome da peça *</label>
          <input type="text" id="prodNome" placeholder="Ex: Bowie artesanal" value="${existing?.nome||''}" />
        </div>

        <div>
          <label>Pedido relacionado</label>
          <select id="prodPedidoId">${pedidoOptions}</select>
        </div>

        <div class="grid-2" style="gap:12px">
          <div>
            <label>Tipo de aço</label>
            <input type="text" id="prodAco" placeholder="Ex: 1070, D2..." value="${existing?.acoTipo||''}" />
          </div>
          <div>
            <label>Cabo</label>
            <input type="text" id="prodCabo" placeholder="Madeira, micarta..." value="${existing?.caboMaterial||''}" />
          </div>
        </div>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Comprimento lâmina (cm)</label>
            <input type="number" id="prodComp" placeholder="14" value="${existing?.comprimentoLamina||''}" />
          </div>
          <div>
            <label>Espessura (mm)</label>
            <input type="number" id="prodEsp" placeholder="4" value="${existing?.espessura||''}" />
          </div>
        </div>
        <div>
          <label>Status</label>
          <select id="prodStatus">
            ${STATUS_LIST.map(s => `<option value="${s}" ${(existing?.status||STATUS_PRODUCAO.INICIADA)===s?'selected':''}>${s}</option>`).join('')}
          </select>
        </div>
        <div>
          <label>Observações</label>
          <textarea id="prodObs" placeholder="Detalhes, observações de processo...">${existing?.observacao||''}</textarea>
        </div>
        <button type="submit" class="primary-button">${existing ? 'Salvar' : 'Criar produção'}</button>
      </form>
    `
  });

  document.getElementById('producaoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('prodNome').value.trim();
    if (!nome) { showToast({ type:'error', message:'Informe o nome.' }); return; }

    const status     = document.getElementById('prodStatus').value;
    const pedidoIdRaw = document.getElementById('prodPedidoId').value;

    const data = {
      nome,
      pedidoId:         pedidoIdRaw ? Number(pedidoIdRaw) : null,
      acoTipo:          document.getElementById('prodAco').value.trim(),
      caboMaterial:     document.getElementById('prodCabo').value.trim(),
      comprimentoLamina:Number(document.getElementById('prodComp').value)||null,
      espessura:        Number(document.getElementById('prodEsp').value)||null,
      status,
      progresso:        STATUS_PROGRESS[status] || 0,
      observacao:       document.getElementById('prodObs').value.trim(),
    };

    if (existing) {
      await db.producao.update(existing.id, data);
      showToast({ message: 'Produção atualizada.' });
    } else {
      data.createdAt = new Date().toISOString();
      await db.producao.add(data);

      // Se vinculou a um pedido, marcar como Em produção automaticamente
      if (data.pedidoId) {
        await db.pedidos.update(data.pedidoId, { status: STATUS_PEDIDO.EM_PRODUCAO });
      }

      showToast({ message: 'Produção iniciada!' });
    }
    closeModal();
    setTimeout(() => navigate('producao'), 300);
  });
}

// ============================================
// EVENTS
// ============================================

window.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-id]');
  const id  = btn ? Number(btn.dataset.id) : null;

  if (e.target.id === 'newProductionButton' || e.target.closest('#newProductionButton')) {
    openProducaoModal(); return;
  }

  if (e.target.closest('.edit-prod-btn') && id) {
    const item = await db.producao.get(id);
    if (item) openProducaoModal(item);
    return;
  }

  if (e.target.closest('.advance-status-btn') && id) {
    const newStatus = e.target.closest('.advance-status-btn').dataset.status;
    await db.producao.update(id, { status: newStatus, progresso: STATUS_PROGRESS[newStatus]||0 });
    showToast({ message: `Status: ${newStatus}` });
    setTimeout(() => navigate('producao'), 250);
    return;
  }

  if (e.target.closest('.delete-prod-btn') && id) {
    if (!confirm('Excluir esta produção?')) return;
    await db.producao.delete(id);
    showToast({ message: 'Produção excluída.' });
    setTimeout(() => navigate('producao'), 250);
    return;
  }
});
