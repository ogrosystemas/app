import { db, fmtBRL, fmtDate, TIPO_FINANCEIRO, STATUS_PEDIDO } from '../database/db.js';
import { emptyState } from '../components/empty-state.js';
import { showToast  } from '../components/toast.js';
import { openModal, closeModal } from '../components/modal.js';
import { navigate } from '../core/router.js';

const STATUS_LIST = Object.values(STATUS_PEDIDO);

const statusBadge = (status) => {
  const map = {
    [STATUS_PEDIDO.ABERTO]:       'badge-orange',
    [STATUS_PEDIDO.EM_PRODUCAO]:  'badge-blue',
    [STATUS_PEDIDO.CONCLUIDO]:    'badge-green',
    [STATUS_PEDIDO.CANCELADO]:    'badge-red',
  };
  return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
};

const prazoColor = (prazo, status) => {
  if (!prazo || status === STATUS_PEDIDO.CONCLUIDO) return 'var(--muted)';
  const diff = new Date(prazo+'T00:00:00') - new Date();
  if (diff < 0)            return '#f87171';
  if (diff < 7*86400*1000) return '#fb923c';
  return 'var(--muted)';
};

// Pode arquivar pedidos concluídos ou cancelados
const podeArquivar = (p) =>
  p.status === STATUS_PEDIDO.CONCLUIDO || p.status === STATUS_PEDIDO.CANCELADO;

export async function pedidosPage() {
  const todos   = db.pedidos ? await db.pedidos.orderBy('id').reverse().toArray() : [];
  const ativos  = todos.filter(p => !p.arquivado);
  const arquivo = todos.filter(p =>  p.arquivado);

  const abertos    = ativos.filter(p => p.status === STATUS_PEDIDO.ABERTO).length;
  const emProd     = ativos.filter(p => p.status === STATUS_PEDIDO.EM_PRODUCAO).length;
  const concluidos = ativos.filter(p => p.status === STATUS_PEDIDO.CONCLUIDO).length;
  const totalReceber = ativos
    .filter(p => p.status !== STATUS_PEDIDO.CANCELADO && p.status !== STATUS_PEDIDO.CONCLUIDO)
    .reduce((s,p) => s + Number(p.valor||0), 0);

  const renderCard = (item, isArquivado = false) => {
    const pColor = prazoColor(item.prazo, item.status);
    return `
      <div class="card" style="${isArquivado ? 'opacity:.7' : ''}">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px">
          <div style="flex:1;min-width:0">
            <h2 style="font-size:18px;font-weight:800;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.nome || 'Pedido'}</h2>
            <p style="color:var(--muted);font-size:14px">${item.cliente || 'Cliente não informado'}</p>
          </div>
          ${statusBadge(item.status)}
        </div>

        <div style="display:flex;gap:20px;margin-bottom:14px">
          <div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:2px">Valor</div>
            <div style="font-weight:700;font-size:16px">${fmtBRL(item.valor)}</div>
          </div>
          ${item.prazo ? `
            <div>
              <div style="font-size:11px;color:var(--muted);margin-bottom:2px">Prazo</div>
              <div style="font-weight:700;font-size:14px;color:${pColor}">${fmtDate(item.prazo+'T00:00:00')}</div>
            </div>
          ` : ''}
          <div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:2px">Criado</div>
            <div style="font-size:13px;color:var(--muted)">${fmtDate(item.createdAt)}</div>
          </div>
        </div>

        ${item.observacao ? `<p style="font-size:13px;color:var(--muted);background:rgba(255,255,255,.04);border-radius:10px;padding:10px 12px;margin-bottom:14px">${item.observacao}</p>` : ''}

        <div style="display:flex;gap:8px;flex-wrap:wrap">
          ${!isArquivado ? `
            <button class="btn btn-ghost btn-sm edit-pedido-btn" data-id="${item.id}">
              <i data-lucide="pencil" style="width:14px;height:14px"></i> Editar
            </button>
            ${item.status !== STATUS_PEDIDO.CONCLUIDO ? `
              <button class="btn btn-success btn-sm conclude-pedido-btn" data-id="${item.id}">
                <i data-lucide="check" style="width:14px;height:14px"></i> Concluir
              </button>
            ` : ''}
            ${podeArquivar(item) ? `
              <button class="btn btn-ghost btn-sm archive-pedido-btn" data-id="${item.id}"
                style="color:#94a3b8;border-color:rgba(148,163,184,.2)">
                <i data-lucide="archive" style="width:14px;height:14px"></i> Arquivar
              </button>
            ` : ''}
          ` : `
            <button class="btn btn-ghost btn-sm unarchive-pedido-btn" data-id="${item.id}">
              <i data-lucide="archive-restore" style="width:14px;height:14px"></i> Desarquivar
            </button>
          `}
          <button class="btn btn-danger btn-sm delete-pedido-btn" data-id="${item.id}" style="margin-left:auto">
            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
          </button>
        </div>
      </div>
    `;
  };

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Pedidos</h1>
          <p>Gestão comercial</p>
        </div>
        <button id="newPedidoButton" class="btn btn-primary btn-sm">+ Novo</button>
      </div>

      <!-- KPIs -->
      <div class="grid-2" style="gap:10px;margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">A receber</div>
          <div class="kpi-card__value" style="font-size:18px;color:#34d399">${fmtBRL(totalReceber)}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Status</div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px">
            <span class="badge badge-orange">${abertos} aberto${abertos!==1?'s':''}</span>
            <span class="badge badge-blue">${emProd} prod.</span>
            <span class="badge badge-green">${concluidos} concluído${concluidos!==1?'s':''}</span>
          </div>
        </div>
      </div>

      <!-- ATIVOS -->
      ${!ativos.length
        ? emptyState({ icon:'shopping-bag', title:'Nenhum pedido ativo', description:'Crie pedidos para organizar suas vendas.' })
        : `<div class="grid-stack">${ativos.map(p => renderCard(p, false)).join('')}</div>`
      }

      <!-- ARQUIVADOS -->
      ${arquivo.length ? `
        <div style="margin-top:32px">
          <button id="toggleArquivoPedidos" style="
            display:flex;align-items:center;gap:8px;width:100%;
            background:none;border:none;padding:10px 0;cursor:pointer;
            color:var(--muted);font-size:14px;font-weight:600;font-family:inherit;
          ">
            <i data-lucide="archive" style="width:16px;height:16px"></i>
            Arquivo (${arquivo.length} pedido${arquivo.length!==1?'s':''})
            <i data-lucide="chevron-down" id="arquivoPedidosChevron" style="width:16px;height:16px;margin-left:auto;transition:transform .2s"></i>
          </button>
          <div id="arquivoPedidosLista" style="display:none;margin-top:8px">
            <div class="grid-stack">${arquivo.map(p => renderCard(p, true)).join('')}</div>
          </div>
        </div>
      ` : ''}
    </section>
  `;
}

// ============================================
// MODAL CRIAR / EDITAR
// ============================================

function openPedidoModal(existing = null) {
  openModal({
    title: existing ? 'Editar Pedido' : 'Novo Pedido',
    size: 'md',
    content: `
      <form id="pedidoForm" class="grid-stack">
        <div>
          <label>Nome do pedido *</label>
          <input type="text" id="pedNome" placeholder="Ex: Bowie artesanal" value="${existing?.nome||''}" />
        </div>
        <div>
          <label>Cliente</label>
          <input type="text" id="pedCliente" placeholder="Nome do cliente" value="${existing?.cliente||''}" />
        </div>
        <div>
          <label>Valor (R$)</label>
          <input type="number" id="pedValor" placeholder="0,00" step="0.01" min="0" value="${existing?.valor||''}" />
        </div>
        <div>
          <label>Prazo de entrega</label>
          <input type="date" id="pedPrazo" value="${existing?.prazo||''}" />
        </div>
        <div>
          <label>Status</label>
          <select id="pedStatus">
            ${STATUS_LIST.map(s => `<option value="${s}" ${(existing?.status||STATUS_PEDIDO.ABERTO)===s?'selected':''}>${s}</option>`).join('')}
          </select>
        </div>
        <div>
          <label>Observações</label>
          <textarea id="pedObs" placeholder="Detalhes do pedido...">${existing?.observacao||''}</textarea>
        </div>
        <button type="submit" class="primary-button">${existing ? 'Salvar alterações' : 'Criar pedido'}</button>
      </form>
    `
  });

  document.getElementById('pedidoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('pedNome').value.trim();
    if (!nome) { showToast({ type:'error', message:'Informe o nome do pedido.' }); return; }
    const data = {
      nome,
      cliente:    document.getElementById('pedCliente').value.trim(),
      valor:      Number(document.getElementById('pedValor').value) || 0,
      prazo:      document.getElementById('pedPrazo').value,
      status:     document.getElementById('pedStatus').value,
      observacao: document.getElementById('pedObs').value.trim(),
    };
    if (existing) {
      await db.pedidos.update(existing.id, data);
      showToast({ message: 'Pedido atualizado.' });
    } else {
      data.createdAt = new Date().toISOString();
      data.arquivado = false;
      await db.pedidos.add(data);
      showToast({ message: 'Pedido criado!' });
    }
    closeModal();
    setTimeout(() => navigate('pedidos'), 300);
  });
}

// ============================================
// EVENTS
// ============================================

window.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-id]');
  const id  = btn ? Number(btn.dataset.id) : null;

  // Toggle arquivo
  if (e.target.id === 'toggleArquivoPedidos' || e.target.closest('#toggleArquivoPedidos')) {
    const lista   = document.getElementById('arquivoPedidosLista');
    const chevron = document.getElementById('arquivoPedidosChevron');
    const aberto  = lista.style.display !== 'none';
    lista.style.display    = aberto ? 'none' : 'block';
    chevron.style.transform= aberto ? '' : 'rotate(180deg)';
    return;
  }

  if (e.target.id === 'newPedidoButton' || e.target.closest('#newPedidoButton')) {
    openPedidoModal(); return;
  }

  if (e.target.closest('.edit-pedido-btn') && id) {
    const item = await db.pedidos.get(id);
    if (item) openPedidoModal(item);
    return;
  }

  if (e.target.closest('.conclude-pedido-btn') && id) {
    await db.pedidos.update(id, { status: STATUS_PEDIDO.CONCLUIDO });
    const pedido = await db.pedidos.get(id);
    if (pedido?.valor > 0) {
      const jaLancado = await db.financeiro.where('pedidoId').equals(id).count();
      if (!jaLancado) {
        await db.financeiro.add({
          tipo:      TIPO_FINANCEIRO.RECEITA,
          descricao: `Pedido concluído: ${pedido.nome}`,
          categoria: 'Vendas',
          valor:     pedido.valor,
          pedidoId:  id,
          createdAt: new Date().toISOString()
        });
      }
    }
    showToast({ message: 'Pedido concluído! Receita lançada automaticamente.' });
    setTimeout(() => navigate('pedidos'), 300);
    return;
  }

  if (e.target.closest('.archive-pedido-btn') && id) {
    await db.pedidos.update(id, { arquivado: true, arquivadoEm: new Date().toISOString() });
    showToast({ message: 'Pedido arquivado.' });
    setTimeout(() => navigate('pedidos'), 300);
    return;
  }

  if (e.target.closest('.unarchive-pedido-btn') && id) {
    await db.pedidos.update(id, { arquivado: false, arquivadoEm: null });
    showToast({ message: 'Pedido desarquivado.' });
    setTimeout(() => navigate('pedidos'), 300);
    return;
  }

  if (e.target.closest('.delete-pedido-btn') && id) {
    if (!confirm('Excluir permanentemente este pedido?')) return;
    await db.pedidos.delete(id);
    showToast({ message: 'Pedido excluído.' });
    setTimeout(() => navigate('pedidos'), 300);
    return;
  }
});
