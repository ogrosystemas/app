// ============================================================
// pages/catalogo.js
// ============================================================

import { render, toast, showModal, hideModal, State } from '../js/app.js';
import { getAll, add, put, remove, getById } from '../js/db.js';
import { moeda, tempo, calcularPrecoServico } from '../js/calculadora.js';

let _servicos = [];
let _profissoes = [];
let _profFiltro = null;
let _editando = null;

export default async function catalogoPage() {
  _profissoes = await getAll('profissoes');
  _profFiltro = State.profissoesAtivas[0]?.id || _profissoes[0]?.id || null;
  await recarregarServicos();

  render(`
    <div class="page-content pb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Catálogo</h4>
        <button class="btn btn-primary btn-sm" onclick="abrirNovoServico()">
          <i class="bi bi-plus-lg me-1"></i>Novo
        </button>
      </div>

      <!-- Filtro por profissão -->
      <div class="d-flex gap-2 flex-wrap mb-3" id="filtro-prof">
        ${_profissoes.map(p => `
          <button class="btn btn-sm ${_profFiltro === p.id ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill"
            onclick="filtrarPorProfissao(${p.id})">
            <i class="bi ${p.icone} me-1"></i>${p.nome}
          </button>`).join('')}
      </div>

      <div id="lista-servicos">${renderLista()}</div>
    </div>

    <!-- Modal Serviço -->
    <div class="modal fade" id="modal-servico" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modal-servico-titulo">Novo Serviço</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Nome *</label>
              <input type="text" class="form-control" id="inp-srv-nome">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Profissão *</label>
              <select class="form-select" id="inp-srv-prof">
                ${_profissoes.map(p => `<option value="${p.id}">${p.nome}</option>`).join('')}
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Categoria</label>
              <input type="text" class="form-control" id="inp-srv-categoria" placeholder="Ex: Elétrica, Hidráulica...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Tempo padrão (minutos)</label>
              <input type="number" class="form-control" id="inp-srv-tempo" value="60" min="1">
            </div>
            <div class="mb-3">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="inp-srv-fixo-toggle" onchange="togglePrecoFixo()">
                <label class="form-check-label" for="inp-srv-fixo-toggle">Preço fixo (não calculado por tempo)</label>
              </div>
              <div id="bloco-preco-fixo" class="d-none">
                <input type="number" class="form-control" id="inp-srv-preco" placeholder="Preço fixo (R$)" step="0.01" min="0">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarServico()">Salvar</button>
          </div>
        </div>
      </div>
    </div>
  `);

  window.filtrarPorProfissao = async (id) => {
    _profFiltro = id;
    await recarregarServicos();
    document.getElementById('lista-servicos').innerHTML = renderLista();
    document.querySelectorAll('#filtro-prof button').forEach(btn => {
      const bid = parseInt(btn.getAttribute('onclick')?.match(/\d+/)?.[0]);
      btn.className = `btn btn-sm ${bid === id ? 'btn-primary' : 'btn-outline-secondary'} rounded-pill`;
    });
  };

  window.togglePrecoFixo = () => {
    const show = document.getElementById('inp-srv-fixo-toggle')?.checked;
    document.getElementById('bloco-preco-fixo')?.classList.toggle('d-none', !show);
  };

  window.abrirNovoServico = () => {
    _editando = null;
    document.getElementById('modal-servico-titulo').textContent = 'Novo Serviço';
    document.getElementById('inp-srv-nome').value = '';
    document.getElementById('inp-srv-prof').value = _profFiltro || _profissoes[0]?.id;
    document.getElementById('inp-srv-categoria').value = '';
    document.getElementById('inp-srv-tempo').value = '60';
    document.getElementById('inp-srv-fixo-toggle').checked = false;
    document.getElementById('inp-srv-preco').value = '';
    document.getElementById('bloco-preco-fixo').classList.add('d-none');
    showModal('modal-servico');
  };

  window.editarServico = async (id) => {
    _editando = await getById('servicos', id);
    if (!_editando) return;
    document.getElementById('modal-servico-titulo').textContent = 'Editar Serviço';
    document.getElementById('inp-srv-nome').value = _editando.nome || '';
    document.getElementById('inp-srv-prof').value = _editando.profissaoId;
    document.getElementById('inp-srv-categoria').value = _editando.categoria || '';
    document.getElementById('inp-srv-tempo').value = _editando.tempoPadrao || 60;
    const temFixo = !!_editando.precoFixo;
    document.getElementById('inp-srv-fixo-toggle').checked = temFixo;
    document.getElementById('inp-srv-preco').value = _editando.precoFixo || '';
    document.getElementById('bloco-preco-fixo').classList.toggle('d-none', !temFixo);
    showModal('modal-servico');
  };

  window.salvarServico = async () => {
    const nome = document.getElementById('inp-srv-nome')?.value.trim();
    if (!nome) { toast('Nome é obrigatório.', 'warning'); return; }

    const profissaoId = parseInt(document.getElementById('inp-srv-prof')?.value);
    const temFixo = document.getElementById('inp-srv-fixo-toggle')?.checked;

    const dados = {
      nome,
      profissaoId,
      categoria:   document.getElementById('inp-srv-categoria')?.value.trim() || '',
      tempoPadrao: parseInt(document.getElementById('inp-srv-tempo')?.value) || 60,
      precoFixo:   temFixo ? (parseFloat(document.getElementById('inp-srv-preco')?.value) || null) : null,
    };

    if (_editando) {
      await put('servicos', { ..._editando, ...dados });
      toast('Serviço atualizado!');
    } else {
      await add('servicos', dados);
      toast('Serviço cadastrado!');
    }

    hideModal('modal-servico');
    await recarregarServicos();
    document.getElementById('lista-servicos').innerHTML = renderLista();
  };

  window.excluirServico = async (id) => {
    if (!confirm('Excluir este serviço?')) return;
    await remove('servicos', id);
    _servicos = _servicos.filter(s => s.id !== id);
    document.getElementById('lista-servicos').innerHTML = renderLista();
    toast('Serviço removido.', 'danger');
  };
}

async function recarregarServicos() {
  if (_profFiltro) {
    _servicos = await getAll('servicos', 'profissaoId', IDBKeyRange.only(_profFiltro));
  } else {
    _servicos = await getAll('servicos');
  }
}

function renderLista() {
  if (_servicos.length === 0) return `
    <div class="empty-state text-center py-5">
      <i class="bi bi-tools display-4 text-muted"></i>
      <p class="text-muted mt-3">Nenhum serviço nesta profissão.</p>
    </div>`;

  // Agrupa por categoria
  const grupos = {};
  for (const s of _servicos) {
    const cat = s.categoria || 'Geral';
    if (!grupos[cat]) grupos[cat] = [];
    grupos[cat].push(s);
  }

  return Object.entries(grupos).map(([cat, servs]) => `
    <div class="mb-3">
      <div class="text-muted small fw-semibold text-uppercase mb-1 px-1">${cat}</div>
      ${servs.map(s => `
        <div class="card border-0 shadow-sm mb-2">
          <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">${s.nome}</div>
                <div class="small text-muted">
                  ${s.precoFixo
                    ? `<span class="badge bg-success-subtle text-success">Fixo ${moeda(s.precoFixo)}</span>`
                    : `<i class="bi bi-clock me-1"></i>${tempo(s.tempoPadrao)}`
                  }
                </div>
              </div>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-secondary" onclick="editarServico(${s.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-outline-danger" onclick="excluirServico(${s.id})"><i class="bi bi-trash"></i></button>
              </div>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `).join('');
}
