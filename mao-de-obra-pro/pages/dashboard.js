// ============================================================
// pages/dashboard.js
// ============================================================

import { render, State, toast } from '../js/app.js';
import { getAll, getById, remove, put } from '../js/db.js';
import { moeda, dataLocal } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

const BADGE = {
  'pendente':     'warning',
  'aprovado':     'success',
  'em andamento': 'primary',
  'finalizado':   'info',
  'arquivado':    'secondary',
  'recusado':     'danger',
  'cancelado':    'secondary',
};

// Botões de ação rápida por status
function botoesAcao(o) {
  const ver = `<button class="btn btn-sm btn-outline-primary flex-fill" onclick="verOrcamento(${o.id})">
    <i class="bi bi-eye me-1"></i>Ver
  </button>`;
  const excluir = `<button class="btn btn-sm btn-outline-danger" onclick="excluirOrcamento(${o.id}, event)">
    <i class="bi bi-trash"></i>
  </button>`;
  const arquivar = `<button class="btn btn-sm btn-outline-secondary flex-fill" onclick="arquivarOrcamento(${o.id})">
    <i class="bi bi-archive me-1"></i>Arquivar
  </button>`;
  const aprovar = `<button class="btn btn-sm btn-outline-success flex-fill" onclick="aprovarOrcamento(${o.id})">
    <i class="bi bi-check me-1"></i>Aprovar
  </button>`;

  switch (o.status) {
    case 'pendente':
      return `${ver}${aprovar}${excluir}`;
    case 'aprovado':
    case 'em andamento':
      return `${ver}${excluir}`;
    case 'finalizado':
      return `${ver}${arquivar}${excluir}`;
    case 'arquivado':
    case 'recusado':
    case 'cancelado':
      return `${ver}${excluir}`;
    default:
      return `${ver}${excluir}`;
  }
}

export default async function dashboardPage() {
  // Carrega orçamentos — arquivados ficam separados (não aparecem na lista principal)
  const todos      = await getAll('orcamentos', 'data');
  todos.reverse();
  const ativos     = todos.filter(o => o.status !== 'arquivado');
  const arquivados = todos.filter(o => o.status === 'arquivado');

  const clienteIds = [...new Set(todos.map(o => o.clienteId))];
  const clienteMap = {};
  await Promise.all(clienteIds.map(async id => {
    const c = await getById('clientes', id);
    if (c) clienteMap[id] = c.nome;
  }));

  const totalClientes   = (await getAll('clientes')).length;
  const emAndamento     = ativos.filter(o => o.status === 'em andamento').length;
  const pendentes       = ativos.filter(o => o.status === 'pendente').length;
  const totalValor      = ativos
    .filter(o => ['aprovado','em andamento','finalizado'].includes(o.status))
    .reduce((s, o) => s + (o.total || 0), 0);

  // Filtro de visualização
  let filtroAtivo = 'ativos';

  function renderLista(lista) {
    if (lista.length === 0) return `
      <div class="empty-state text-center py-5">
        <i class="bi bi-file-earmark-text display-4 text-muted"></i>
        <p class="text-muted mt-3">Nenhum orçamento aqui.</p>
      </div>`;

    return lista.map(o => `
      <div class="card border-0 shadow-sm mb-2">
        <div class="card-body py-2 px-3" onclick="verOrcamento(${o.id})" style="cursor:pointer">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1 me-2">
              <div class="fw-semibold">${clienteMap[o.clienteId] || 'Cliente removido'}</div>
              <div class="small text-muted">${o.profissaoNome || '—'} · ${dataLocal(o.data)}</div>
              <div class="small text-muted">${(o.itens || []).length} serviço(s)</div>
            </div>
            <div class="text-end">
              <div class="fw-bold text-primary">${moeda(o.total)}</div>
              <span class="badge bg-${BADGE[o.status] || 'secondary'} mt-1 text-capitalize">${o.status}</span>
            </div>
          </div>
          <div class="d-flex gap-2 mt-2" onclick="event.stopPropagation()">
            ${botoesAcao(o)}
          </div>
        </div>
      </div>
    `).join('');
  }

  render(`
    <div class="page-content pb-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bold mb-0">Dashboard</h4>
          <div class="text-muted small">${new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long' })}</div>
        </div>
        <div class="d-flex gap-1 flex-wrap justify-content-end" style="max-width:60%">
          ${State.profissoesAtivas.map(p =>
            `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
              <i class="bi ${p.icone} me-1"></i>${p.nome}
            </span>`
          ).join('')}
        </div>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Pipeline</div>
              <div class="fw-bold fs-5 text-primary">${moeda(totalValor)}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Em andamento</div>
              <div class="fw-bold fs-5 text-primary">${emAndamento}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Pendentes</div>
              <div class="fw-bold fs-5 text-warning">${pendentes}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Clientes</div>
              <div class="fw-bold fs-5">${totalClientes}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="d-flex gap-2 mb-3">
        <button class="btn btn-sm btn-primary rounded-pill" id="btn-f-ativos" onclick="filtrarDash('ativos')">
          Ativos <span class="badge bg-white text-primary ms-1">${ativos.length}</span>
        </button>
        <button class="btn btn-sm btn-outline-secondary rounded-pill" id="btn-f-arquivados" onclick="filtrarDash('arquivados')">
          <i class="bi bi-archive me-1"></i>Arquivados <span class="badge bg-secondary ms-1">${arquivados.length}</span>
        </button>
      </div>

      <!-- Lista -->
      <div id="lista-orcamentos">
        ${renderLista(ativos)}
      </div>
    </div>
  `);

  window.verOrcamento = (id) => navigate('/visualizar', { id });

  window.filtrarDash = (filtro) => {
    filtroAtivo = filtro;
    const lista = filtro === 'arquivados' ? arquivados : ativos;
    document.getElementById('lista-orcamentos').innerHTML = renderLista(lista);
    document.getElementById('btn-f-ativos').className     = `btn btn-sm ${filtro === 'ativos'     ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill`;
    document.getElementById('btn-f-arquivados').className = `btn btn-sm ${filtro === 'arquivados' ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill`;
  };

  window.aprovarOrcamento = async (id) => {
    const o = await getById('orcamentos', id);
    if (!o || o.status !== 'pendente') return;
    o.status = 'aprovado';
    await put('orcamentos', o);
    toast('Orçamento aprovado!');
    dashboardPage();
  };

  window.arquivarOrcamento = async (id) => {
    const o = await getById('orcamentos', id);
    if (!o) return;
    o.status = 'arquivado';
    await put('orcamentos', o);
    toast('Orçamento arquivado.');
    dashboardPage();
  };

  window.excluirOrcamento = async (id, e) => {
    e.stopPropagation();
    if (!confirm('Excluir este orçamento permanentemente?')) return;
    await remove('orcamentos', parseInt(id));
    const fotos = await getAll('fotos', 'orcamentoId', IDBKeyRange.only(parseInt(id)));
    for (const f of fotos) await remove('fotos', f.id);
    const pags = await getAll('pagamentos', 'orcamentoId', IDBKeyRange.only(parseInt(id)));
    for (const p of pags) await remove('pagamentos', p.id);
    toast('Orçamento excluído.', 'danger');
    dashboardPage();
  };
}
