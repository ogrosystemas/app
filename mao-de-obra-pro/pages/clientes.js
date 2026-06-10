// ============================================================
// pages/clientes.js
// ============================================================

import { render, toast, showModal, hideModal } from '../js/app.js';
import { getAll, add, put, remove, getById } from '../js/db.js';
import { moeda, dataLocal } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

let _clientes = [];
let _editando = null;

const BADGE = {
  'pendente':     'warning',
  'aprovado':     'success',
  'em andamento': 'primary',
  'finalizado':   'info',
  'arquivado':    'secondary',
  'recusado':     'danger',
  'cancelado':    'secondary',
};

export default async function clientesPage() {
  _clientes = await getAll('clientes', 'nome');

  render(`
    <div class="page-content pb-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Clientes</h4>
        <button class="btn btn-primary btn-sm" onclick="abrirNovoCliente()">
          <i class="bi bi-plus-lg me-1"></i>Novo
        </button>
      </div>

      <div class="input-group mb-3">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" id="busca-cliente"
          placeholder="Buscar por nome ou WhatsApp..." oninput="filtrarClientes()">
      </div>

      <div id="lista-clientes">
        ${renderLista(_clientes)}
      </div>
    </div>

    <!-- Modal cliente -->
    <div class="modal fade" id="modal-cliente" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modal-cliente-titulo">Novo Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Nome *</label>
              <input type="text" class="form-control" id="inp-nome" placeholder="Nome completo">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                <input type="tel" class="form-control" id="inp-whatsapp" placeholder="(00) 00000-0000">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Endereço</label>
              <textarea class="form-control" id="inp-endereco" rows="2" placeholder="Rua, número, bairro..."></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold">Observações</label>
              <textarea class="form-control" id="inp-obs" rows="2" placeholder="Preferências, histórico..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarCliente()">
              <i class="bi bi-check-lg me-1"></i>Salvar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal perfil do cliente -->
    <div class="modal fade" id="modal-perfil" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="perfil-titulo">Perfil do Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="perfil-body">
            <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>
  `);

  window.filtrarClientes = () => {
    const q = document.getElementById('busca-cliente')?.value.toLowerCase() || '';
    const filtrados = _clientes.filter(c =>
      c.nome.toLowerCase().includes(q) ||
      (c.whatsapp || '').includes(q)
    );
    document.getElementById('lista-clientes').innerHTML = renderLista(filtrados);
  };

  window.abrirNovoCliente = () => {
    _editando = null;
    document.getElementById('modal-cliente-titulo').textContent = 'Novo Cliente';
    document.getElementById('inp-nome').value     = '';
    document.getElementById('inp-whatsapp').value = '';
    document.getElementById('inp-endereco').value = '';
    document.getElementById('inp-obs').value      = '';
    showModal('modal-cliente');
  };

  window.editarCliente = async (id) => {
    _editando = await getById('clientes', id);
    if (!_editando) return;
    document.getElementById('modal-cliente-titulo').textContent = 'Editar Cliente';
    document.getElementById('inp-nome').value     = _editando.nome     || '';
    document.getElementById('inp-whatsapp').value = _editando.whatsapp || '';
    document.getElementById('inp-endereco').value = _editando.endereco || '';
    document.getElementById('inp-obs').value      = _editando.obs      || '';
    showModal('modal-cliente');
  };

  window.salvarCliente = async () => {
    const nome = document.getElementById('inp-nome')?.value.trim();
    if (!nome) { toast('Nome é obrigatório.', 'warning'); return; }

    const dados = {
      nome,
      whatsapp:  document.getElementById('inp-whatsapp')?.value.trim() || '',
      endereco:  document.getElementById('inp-endereco')?.value.trim() || '',
      obs:       document.getElementById('inp-obs')?.value.trim()      || '',
      criadoEm:  _editando?.criadoEm || new Date().toISOString(),
    };

    if (_editando) {
      await put('clientes', { ..._editando, ...dados });
      toast('Cliente atualizado!');
    } else {
      await add('clientes', dados);
      toast('Cliente cadastrado!');
    }

    hideModal('modal-cliente');
    _clientes = await getAll('clientes', 'nome');
    document.getElementById('lista-clientes').innerHTML = renderLista(_clientes);
  };

  window.excluirCliente = async (id) => {
    if (!confirm('Excluir este cliente?')) return;
    await remove('clientes', id);
    _clientes = _clientes.filter(c => c.id !== id);
    document.getElementById('lista-clientes').innerHTML = renderLista(_clientes);
    toast('Cliente removido.', 'danger');
  };

  window.ligarWhatsApp = (whatsapp) => {
    const num = whatsapp.replace(/\D/g, '');
    window.open(`https://wa.me/55${num}`, '_blank');
  };

  window.verPerfil = async (id) => {
    const cliente = await getById('clientes', id);
    if (!cliente) return;

    document.getElementById('perfil-titulo').textContent = cliente.nome;
    document.getElementById('perfil-body').innerHTML =
      '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    showModal('modal-perfil');

    // Carrega orçamentos do cliente
    const orcamentos = await getAll('orcamentos', 'clienteId', IDBKeyRange.only(parseInt(id)));
    orcamentos.sort((a, b) => new Date(b.data) - new Date(a.data));

    const pagamentos = await getAll('pagamentos');
    const pagPorOrc = {};
    pagamentos.forEach(p => {
      const key = parseInt(p.orcamentoId);
      if (!pagPorOrc[key]) pagPorOrc[key] = [];
      pagPorOrc[key].push(p);
    });

    const totalGasto = orcamentos
      .filter(o => ['aprovado','em andamento','finalizado','arquivado'].includes(o.status))
      .reduce((s, o) => s + (o.total || 0), 0);

    document.getElementById('perfil-body').innerHTML = `
      <!-- Info do cliente -->
      <div class="mb-4">
        ${cliente.whatsapp ? `
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-whatsapp text-success"></i>
            <span>${cliente.whatsapp}</span>
            <button class="btn btn-sm btn-outline-success ms-auto" onclick="ligarWhatsApp('${cliente.whatsapp}')">
              Abrir
            </button>
          </div>` : ''}
        ${cliente.endereco ? `
          <div class="d-flex gap-2 mb-2">
            <i class="bi bi-geo-alt text-muted mt-1"></i>
            <span class="text-muted">${cliente.endereco}</span>
          </div>` : ''}
        ${cliente.obs ? `
          <div class="d-flex gap-2 mb-2">
            <i class="bi bi-chat-text text-muted mt-1"></i>
            <span class="text-muted">${cliente.obs}</span>
          </div>` : ''}
        <div class="d-flex gap-2 mt-3">
          <div class="card border-0 bg-light flex-fill text-center py-2">
            <div class="small text-muted">Orçamentos</div>
            <div class="fw-bold">${orcamentos.length}</div>
          </div>
          <div class="card border-0 bg-light flex-fill text-center py-2">
            <div class="small text-muted">Total gasto</div>
            <div class="fw-bold text-primary">${moeda(totalGasto)}</div>
          </div>
        </div>
      </div>

      <!-- Histórico de orçamentos -->
      <div class="fw-semibold mb-2">Histórico de Orçamentos</div>
      ${orcamentos.length === 0 ? `
        <div class="text-muted small text-center py-3">Nenhum orçamento ainda.</div>
      ` : orcamentos.map(o => {
        const pago = (pagPorOrc[o.id] || []).reduce((s, p) => s + p.valor, 0);
        const restante = Math.max(0, o.total - pago);
        return `
          <div class="card border-0 bg-light mb-2" onclick="verOrcamentoModal(${o.id})" style="cursor:pointer">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="small fw-semibold">#${o.id} · ${o.profissaoNome || '—'}</div>
                  <div class="small text-muted">${dataLocal(o.data)} · ${(o.itens || []).length} serviço(s)</div>
                </div>
                <div class="text-end">
                  <div class="fw-bold small text-primary">${moeda(o.total)}</div>
                  <span class="badge bg-${BADGE[o.status] || 'secondary'} text-capitalize">${o.status}</span>
                  ${restante > 0.01 ? `<div class="small text-danger">A receber: ${moeda(restante)}</div>` : ''}
                </div>
              </div>
            </div>
          </div>`;
      }).join('')}
    `;

    window.verOrcamentoModal = (orcId) => {
      hideModal('modal-perfil');
      setTimeout(() => navigate('/visualizar', { id: orcId }), 300);
    };
  };
}

function renderLista(clientes) {
  if (clientes.length === 0) return `
    <div class="empty-state text-center py-5">
      <i class="bi bi-people display-4 text-muted"></i>
      <p class="text-muted mt-3">Nenhum cliente cadastrado.</p>
    </div>`;

  return clientes.map(c => `
    <div class="card border-0 shadow-sm mb-2">
      <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1" onclick="verPerfil(${c.id})" style="cursor:pointer">
            <div class="fw-semibold">${c.nome}</div>
            ${c.whatsapp ? `<div class="small text-success"><i class="bi bi-whatsapp me-1"></i>${c.whatsapp}</div>` : ''}
            ${c.endereco ? `<div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>${c.endereco}</div>` : ''}
          </div>
          <div class="d-flex gap-1 ms-2">
            ${c.whatsapp ? `<button class="btn btn-sm btn-outline-success" onclick="ligarWhatsApp('${c.whatsapp}')"><i class="bi bi-whatsapp"></i></button>` : ''}
            <button class="btn btn-sm btn-outline-secondary" onclick="editarCliente(${c.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-sm btn-outline-danger" onclick="excluirCliente(${c.id})"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}
