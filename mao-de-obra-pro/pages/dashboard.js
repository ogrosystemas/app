// ============================================================
// pages/dashboard.js
// ============================================================

import { render, State, toast } from '../js/app.js';
import { getAll, getById, remove, put } from '../js/db.js';
import { moeda, dataLocal } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

export default async function dashboardPage() {
  const orcamentos = await getAll('orcamentos', 'data');
  orcamentos.reverse();

  const clienteIds = [...new Set(orcamentos.map(o => o.clienteId))];
  const clienteMap = {};
  await Promise.all(clienteIds.map(async id => {
    const c = await getById('clientes', id);
    if (c) clienteMap[id] = c.nome;
  }));

  const totalOrcamentos = orcamentos.length;
  const totalValor      = orcamentos.reduce((s, o) => s + (o.total || 0), 0);
  const totalAprovados  = orcamentos.filter(o => o.status === 'aprovado').length;
  const totalClientes   = (await getAll('clientes')).length;

  const badgeStatus = {
    pendente:  'warning',
    aprovado:  'success',
    recusado:  'danger',
    cancelado: 'secondary',
  };

  render(`
    <div class="page-content pb-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 class="fw-bold mb-0">Dashboard</h4>
          <div class="text-muted small">${new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long' })}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end" style="max-width:60%">
          ${State.profissoesAtivas.map(p =>
            `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
              <i class="bi ${p.icone} me-1"></i>${p.nome}
            </span>`
          ).join('')}
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Total Orçado</div>
              <div class="fw-bold fs-5 text-primary">${moeda(totalValor)}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Aprovados</div>
              <div class="fw-bold fs-5 text-success">${totalAprovados} <span class="fs-6 text-muted">/ ${totalOrcamentos}</span></div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Orçamentos</div>
              <div class="fw-bold fs-5">${totalOrcamentos}</div>
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

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Orçamentos Recentes</h6>
      </div>

      ${orcamentos.length === 0 ? `
        <div class="empty-state text-center py-5">
          <i class="bi bi-file-earmark-text display-4 text-muted"></i>
          <p class="text-muted mt-3">Nenhum orçamento ainda.<br>Toque no <strong>+</strong> para criar.</p>
        </div>
      ` : `
        <div id="lista-orcamentos">
          ${orcamentos.map(o => `
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
                    <span class="badge bg-${badgeStatus[o.status] || 'secondary'} mt-1">${o.status}</span>
                  </div>
                </div>
                <div class="d-flex gap-2 mt-2" onclick="event.stopPropagation()">
                  <button class="btn btn-sm btn-outline-primary flex-fill" onclick="verOrcamento(${o.id})">
                    <i class="bi bi-eye me-1"></i>Ver
                  </button>
                  <button class="btn btn-sm btn-outline-success flex-fill" onclick="aprovarOrcamento(${o.id})">
                    <i class="bi bi-check me-1"></i>Aprovar
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="excluirOrcamento(${o.id}, event)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      `}
    </div>
  `);

  window.verOrcamento = (id) => navigate('/visualizar', { id });

  window.aprovarOrcamento = async (id) => {
    const o = await getById('orcamentos', id);
    if (!o) return;
    o.status = 'aprovado';
    await put('orcamentos', o);
    toast('Orçamento aprovado!');
    dashboardPage();
  };

  // FIX: usa remove importado no topo, sem reimport dinâmico
  window.excluirOrcamento = async (id, e) => {
    e.stopPropagation();
    if (!confirm('Excluir este orçamento?')) return;
    await remove('orcamentos', id);
    const fotos = await getAll('fotos', 'orcamentoId', IDBKeyRange.only(parseInt(id)));
    for (const f of fotos) await remove('fotos', f.id);
    toast('Orçamento excluído.', 'danger');
    dashboardPage();
  };
}
