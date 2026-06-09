// ============================================================
// pages/orcamento.js — Wizard 4 steps
// ============================================================

import { render, toast, State } from '../js/app.js';
import { getAll, add } from '../js/db.js';
import { calcularPrecoServico, calcularTotalOrcamento, moeda, tempo, DIFICULDADE, dataVencimento } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

// Estado do wizard (módulo-level, persiste entre re-renders do mesmo step)
let W = {};

function resetWizard() {
  W = {
    step: 1,
    profissao: null,
    cliente: null,
    itens: [],
    fotos: [],
    desconto: { tipo: 'valor', valor: 0 },
    validade: 30,
  };
}

// Cache de dados carregados (evita recarregar DB a cada re-render)
let _clientesCache = [];
let _servicosCache = [];

export default async function orcamentoPage() {
  resetWizard();
  _clientesCache = [];
  _servicosCache = [];

  if (State.profissoesAtivas.length === 1) W.profissao = State.profissoesAtivas[0];

  render(`<div id="wizard-container"></div>`);
  await renderStep();
}

async function renderStep() {
  const steps = { 1: renderStep1, 2: renderStep2, 3: renderStep3, 4: renderStep4 };
  await steps[W.step]();
  bindGlobals();
}

// ── STEP 1: Profissão + Cliente ──────────────────────────────

async function renderStep1() {
  // só carrega do DB na primeira vez
  if (_clientesCache.length === 0) {
    _clientesCache = await getAll('clientes', 'nome');
  }
  const profs = State.profissoesAtivas;

  document.getElementById('wizard-container').innerHTML = `
    <div class="page-content pb-5">
      ${stepHeader(1)}

      ${profs.length > 1 ? `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="fw-semibold mb-2">Profissão</div>
            <div class="row g-2">
              ${profs.map(p => `
                <div class="col-6">
                  <button class="btn w-100 ${W.profissao?.id === p.id ? 'btn-primary' : 'btn-outline-secondary'} py-2"
                    onclick="selecionarProfissao(${p.id})">
                    <i class="bi ${p.icone} d-block fs-4 mb-1"></i>
                    <small>${p.nome}</small>
                  </button>
                </div>`).join('')}
            </div>
          </div>
        </div>
      ` : W.profissao ? `
        <div class="alert alert-primary d-flex align-items-center gap-2 mb-3">
          <i class="bi ${W.profissao.icone} fs-4"></i>
          <strong>${W.profissao.nome}</strong>
        </div>
      ` : ''}

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">Cliente</div>
            <button class="btn btn-sm btn-outline-primary" onclick="abrirNovoClienteRapido()">
              <i class="bi bi-plus-lg me-1"></i>Novo
            </button>
          </div>
          <input type="text" class="form-control mb-2" id="busca-cli"
            placeholder="Buscar cliente..." oninput="filtrarClientesWizard()">
          <div id="lista-cli-wizard" style="max-height:280px;overflow-y:auto">
            ${renderClientesList(_clientesCache)}
          </div>
        </div>
      </div>

      <button class="btn btn-primary w-100 py-3 fw-semibold"
        onclick="irStep2()" ${!W.profissao || !W.cliente ? 'disabled' : ''}>
        Próximo <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>

    <!-- Modal cliente rápido -->
    <div class="modal fade" id="modal-cli-rapido" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Novo Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="text" class="form-control mb-2" id="rq-nome" placeholder="Nome *">
            <input type="tel" class="form-control mb-2" id="rq-whatsapp" placeholder="WhatsApp">
            <input type="text" class="form-control" id="rq-endereco" placeholder="Endereço">
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" onclick="salvarClienteRapido()">Salvar</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderClientesList(clientes) {
  if (!clientes.length) return '<div class="text-muted small text-center py-3">Nenhum cliente cadastrado.</div>';
  return clientes.map(c => {
    const nomeSafe     = (c.nome     || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    const whatsappSafe = (c.whatsapp || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    const enderecoSafe = (c.endereco || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    const selected = W.cliente?.id === c.id ? 'border-primary bg-primary-subtle' : '';
    return `
    <div class="list-group-item list-group-item-action rounded mb-1 border ${selected}"
      onclick="selecionarCliente(${c.id})"
      data-nome="${nomeSafe}"
      data-whatsapp="${whatsappSafe}"
      data-endereco="${enderecoSafe}">
      <div class="fw-semibold">${c.nome}</div>
      ${c.whatsapp ? \`<div class="small text-muted">${c.whatsapp}</div>\` : ''}
    </div>`;
  }).join('');
}

// ── STEP 2: Serviços ─────────────────────────────────────────

async function renderStep2() {
  // só carrega do DB se mudou de profissão ou ainda não carregou
  if (_servicosCache.length === 0 || _servicosCache[0]?.profissaoId !== W.profissao.id) {
    _servicosCache = await getAll('servicos', 'profissaoId', IDBKeyRange.only(W.profissao.id));
  }

  const vm  = State.valorMinutoPorProfissao[W.profissao.id] || 0;
  const cfg = State.config;

  document.getElementById('wizard-container').innerHTML = `
    <div class="page-content pb-5">
      ${stepHeader(2)}

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold">Serviços — ${W.profissao.nome}</div>
            <button class="btn btn-sm btn-outline-primary" onclick="abrirModalAddServico()">
              <i class="bi bi-plus-lg me-1"></i>Adicionar
            </button>
          </div>

          <div id="itens-wizard">
            ${W.itens.length === 0
              ? `<div class="text-muted small text-center py-4"><i class="bi bi-tools d-block fs-3 mb-2"></i>Nenhum serviço adicionado.</div>`
              : W.itens.map((item, idx) => renderItemWizard(item, idx)).join('')
            }
          </div>

          ${W.itens.length > 0 ? `
            <div class="border-top pt-2 mt-2 text-end">
              <span class="fw-bold text-primary fs-5">${moeda(W.itens.reduce((s,i) => s + i.precoTotal, 0))}</span>
              <div class="text-muted small">subtotal serviços</div>
            </div>
          ` : ''}
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary flex-fill py-3" onclick="irStep(1)">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <button class="btn btn-primary flex-fill py-3 fw-semibold"
          onclick="irStep3()" ${W.itens.length === 0 ? 'disabled' : ''}>
          Próximo <i class="bi bi-arrow-right ms-1"></i>
        </button>
      </div>
    </div>

    <!-- Modal add serviço -->
    <div class="modal fade" id="modal-add-srv" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Selecionar Serviço</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" style="max-height:60vh;overflow-y:auto">
            <input type="text" class="form-control mb-3" id="busca-srv-modal"
              placeholder="Buscar serviço..." oninput="filtrarServicosModal()">
            <div id="lista-srv-modal">
              ${renderListaServicosModal(_servicosCache, vm, cfg)}
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderListaServicosModal(servicos, vm, cfg) {
  if (!servicos.length) return '<div class="text-muted text-center py-3">Nenhum serviço cadastrado.</div>';
  return servicos.map(s => {
    const preco = s.precoFixo || calcularPrecoServico(s.tempoPadrao, vm, 1.0, cfg.margemReserva);
    return `
      <div class="list-group-item list-group-item-action rounded border mb-1" onclick="adicionarServico(${s.id})">
        <div class="d-flex justify-content-between">
          <div>
            <div class="fw-semibold">${s.nome}</div>
            <div class="small text-muted">${s.categoria || ''} · ${tempo(s.tempoPadrao)}</div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-primary">${moeda(preco)}</div>
            ${s.precoFixo ? '<div class="badge bg-success-subtle text-success small">Fixo</div>' : ''}
          </div>
        </div>
      </div>`;
  }).join('');
}

function renderItemWizard(item, idx) {
  return `
    <div class="border rounded-3 p-3 mb-2">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div class="fw-semibold">${item.nome}</div>
        <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="removerItem(${idx})">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      ${item.usaPrecoFixo ? `
        <div class="small text-success mb-2"><i class="bi bi-tag me-1"></i>Preço fixo: ${moeda(item.precoUnitario)}</div>
      ` : `
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small mb-1">Tempo (min)</label>
            <input type="number" class="form-control form-control-sm" value="${item.tempoAjustado}"
              min="1" onchange="atualizarItemTempo(${idx}, this.value)">
          </div>
          <div class="col-6">
            <label class="form-label small mb-1">Dificuldade</label>
            <select class="form-select form-select-sm" onchange="atualizarItemDificuldade(${idx}, this.value)">
              ${Object.entries(DIFICULDADE).map(([k,v]) =>
                `<option value="${k}" ${item.dificuldade === k ? 'selected' : ''}>${v.label} (${v.fator}x)</option>`
              ).join('')}
            </select>
          </div>
        </div>
      `}
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary btn-sm px-2" onclick="ajustarQtd(${idx}, -1)">−</button>
          <span class="fw-semibold px-1">${item.quantidade}</span>
          <button class="btn btn-outline-secondary btn-sm px-2" onclick="ajustarQtd(${idx}, +1)">+</button>
        </div>
        <div class="fw-bold text-primary">${moeda(item.precoTotal)}</div>
      </div>
    </div>`;
}

// ── STEP 3: Fotos ────────────────────────────────────────────

async function renderStep3() {
  document.getElementById('wizard-container').innerHTML = `
    <div class="page-content pb-5">
      ${stepHeader(3)}

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Fotos do serviço <span class="text-muted small">(opcional)</span></div>
            <div class="d-flex gap-2">
              <label class="btn btn-sm btn-outline-primary mb-0">
                <i class="bi bi-camera me-1"></i>Câmera
                <input type="file" accept="image/*" capture="environment" class="d-none" onchange="adicionarFoto(event)">
              </label>
              <label class="btn btn-sm btn-outline-secondary mb-0">
                <i class="bi bi-image me-1"></i>Galeria
                <input type="file" accept="image/*" multiple class="d-none" onchange="adicionarFoto(event)">
              </label>
            </div>
          </div>
          <div id="grid-fotos">${renderGridFotos()}</div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary flex-fill py-3" onclick="irStep(2)">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <button class="btn btn-primary flex-fill py-3 fw-semibold" onclick="irStep(4)">
          Próximo <i class="bi bi-arrow-right ms-1"></i>
        </button>
      </div>
    </div>
  `;
}

function renderGridFotos() {
  if (W.fotos.length === 0) return `
    <div class="text-muted small text-center py-4">
      <i class="bi bi-camera d-block fs-3 mb-2"></i>Nenhuma foto adicionada.
    </div>`;
  return `<div class="row g-2">
    ${W.fotos.map((f, i) => `
      <div class="col-4 position-relative">
        <img src="${f}" class="img-fluid rounded" style="height:90px;width:100%;object-fit:cover">
        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 lh-1"
          onclick="removerFoto(${i})"><i class="bi bi-x-lg"></i></button>
      </div>`).join('')}
  </div>`;
}

// ── STEP 4: Resumo ───────────────────────────────────────────

async function renderStep4() {
  // Usa W.desconto do state (atualizado via atualizarDesconto antes de re-render)
  const totais = calcularTotalOrcamento(W.itens, State.config.taxaDeslocamento, W.desconto);

  document.getElementById('wizard-container').innerHTML = `
    <div class="page-content pb-5">
      ${stepHeader(4)}

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-transparent fw-semibold">Resumo</div>
        <div class="card-body">
          <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Cliente</span>
            <span class="fw-semibold">${W.cliente.nome}</span>
          </div>
          <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">Profissão</span>
            <span class="fw-semibold">${W.profissao.nome}</span>
          </div>

          <div class="border-top pt-2 mb-2">
            ${W.itens.map(item => `
              <div class="d-flex justify-content-between py-1 border-bottom">
                <div>
                  <span>${item.nome} ×${item.quantidade}</span>
                  ${!item.usaPrecoFixo ? `<span class="badge bg-secondary ms-1 small">${DIFICULDADE[item.dificuldade].label}</span>` : ''}
                </div>
                <span class="fw-semibold">${moeda(item.precoTotal)}</span>
              </div>`).join('')}
          </div>

          <div class="d-flex justify-content-between text-muted py-1">
            <span>Deslocamento</span><span>${moeda(totais.taxaDeslocamento)}</span>
          </div>

          <!-- Desconto -->
          <div class="border rounded-3 p-3 mb-3 mt-2 bg-light">
            <div class="fw-semibold mb-2"><i class="bi bi-percent me-1"></i>Desconto</div>
            <div class="d-flex gap-2 mb-2">
              <button class="btn btn-sm ${W.desconto.tipo === 'valor' ? 'btn-primary' : 'btn-outline-secondary'}"
                onclick="setTipoDesconto('valor')">R$</button>
              <button class="btn btn-sm ${W.desconto.tipo === 'percentual' ? 'btn-primary' : 'btn-outline-secondary'}"
                onclick="setTipoDesconto('percentual')">%</button>
              <input type="number" class="form-control form-control-sm" id="inp-desconto"
                value="${W.desconto.valor}" min="0" step="0.01"
                placeholder="${W.desconto.tipo === 'percentual' ? 'Percentual' : 'Valor R$'}"
                oninput="atualizarDescontoLive()">
            </div>
          </div>

          <!-- Validade -->
          <div class="border rounded-3 p-3 mb-3 bg-light">
            <div class="fw-semibold mb-2"><i class="bi bi-calendar me-1"></i>Validade</div>
            <div class="d-flex gap-2 flex-wrap">
              ${[1,5,15,30].map(d => `
                <button class="btn btn-sm ${W.validade === d ? 'btn-primary' : 'btn-outline-secondary'}"
                  onclick="setValidade(${d})">${d} dia${d > 1 ? 's' : ''}</button>`).join('')}
            </div>
            <div class="text-muted small mt-2">Vence em: <strong>${new Date(dataVencimento(W.validade)).toLocaleDateString('pt-BR')}</strong></div>
          </div>

          <!-- Total -->
          <div class="border-top pt-3">
            <div class="d-flex justify-content-between mb-1 text-muted">
              <span>Subtotal</span><span>${moeda(totais.subtotal)}</span>
            </div>
            ${totais.desconto.valor > 0 ? `
              <div class="d-flex justify-content-between mb-1 text-danger">
                <span>Desconto</span>
                <span>− ${totais.desconto.tipo === 'percentual' ? totais.desconto.valor + '%' : moeda(totais.desconto.valor)}</span>
              </div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-5 mt-1">
              <span>Total</span>
              <span class="text-primary" id="total-preview">${moeda(totais.total)}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary flex-fill py-3" onclick="irStep(3)">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <button class="btn btn-success flex-fill py-3 fw-semibold" id="btn-salvar" onclick="salvarOrcamento()">
          <i class="bi bi-check-lg me-1"></i>Salvar Orçamento
        </button>
      </div>
    </div>
  `;
}

// ── Bind de funções globais ───────────────────────────────────

function bindGlobals() {
  window.irStep  = (n) => { W.step = n; renderStep(); };
  window.irStep2 = () => { if (!W.profissao || !W.cliente) return; W.step = 2; renderStep(); };
  window.irStep3 = () => { if (!W.itens.length) return; W.step = 3; renderStep(); };

  window.selecionarProfissao = (id) => {
    W.profissao = State.profissoesAtivas.find(p => p.id === id) || null;
    // reseta cache de serviços ao trocar profissão
    _servicosCache = [];
    renderStep();
  };

  window.selecionarCliente = (id) => {
    const el = document.querySelector('[onclick="selecionarCliente(' + id + ')"]');
    const nome     = el ? (el.dataset.nome     || '') : '';
    const whatsapp = el ? (el.dataset.whatsapp || '') : '';
    const endereco = el ? (el.dataset.endereco || '') : '';
    W.cliente = { id, nome, whatsapp, endereco };
    renderStep();
  };

  window.filtrarClientesWizard = () => {
    const q = (document.getElementById('busca-cli')?.value || '').toLowerCase();
    const filtrados = _clientesCache.filter(c =>
      c.nome.toLowerCase().includes(q) || (c.whatsapp || '').includes(q));
    document.getElementById('lista-cli-wizard').innerHTML = renderClientesList(filtrados);
  };

  window.abrirNovoClienteRapido = () =>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cli-rapido')).show();

  window.salvarClienteRapido = async () => {
    const nome = document.getElementById('rq-nome')?.value.trim();
    if (!nome) { toast('Nome é obrigatório.', 'warning'); return; }
    const dados = {
      nome,
      whatsapp: document.getElementById('rq-whatsapp')?.value.trim() || '',
      endereco: document.getElementById('rq-endereco')?.value.trim() || '',
      criadoEm: new Date().toISOString(),
    };
    const id = await add('clientes', dados);
    W.cliente = { id, ...dados };
    _clientesCache.push({ id, ...dados });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cli-rapido')).hide();
    toast('Cliente adicionado!');
    renderStep();
  };

  window.abrirModalAddServico = () =>
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-srv')).show();

  // FIX: filtrarServicosModal usa _servicosCache (sempre populado antes do modal abrir)
  window.filtrarServicosModal = () => {
    const q   = (document.getElementById('busca-srv-modal')?.value || '').toLowerCase();
    const vm  = State.valorMinutoPorProfissao[W.profissao.id] || 0;
    const cfg = State.config;
    const filtrados = _servicosCache.filter(s => s.nome.toLowerCase().includes(q));
    document.getElementById('lista-srv-modal').innerHTML =
      renderListaServicosModal(filtrados, vm, cfg);
  };

  window.adicionarServico = (id) => {
    const s = _servicosCache.find(x => x.id === id);
    if (!s) return;
    const vm  = State.valorMinutoPorProfissao[W.profissao.id] || 0;
    const cfg = State.config;
    const usaPrecoFixo  = !!(s.precoFixo);
    const precoUnitario = usaPrecoFixo
      ? s.precoFixo
      : calcularPrecoServico(s.tempoPadrao, vm, DIFICULDADE.NORMAL.fator, cfg.margemReserva);

    W.itens.push({
      servicoId: s.id, nome: s.nome,
      tempoAjustado: s.tempoPadrao,
      dificuldade: 'NORMAL',
      precoUnitario, quantidade: 1,
      precoTotal: precoUnitario,
      precoFixo: s.precoFixo || null,
      usaPrecoFixo,
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-add-srv')).hide();
    renderStep();
  };

  window.removerItem = (idx) => { W.itens.splice(idx, 1); renderStep(); };

  window.ajustarQtd = (idx, delta) => {
    W.itens[idx].quantidade = Math.max(1, W.itens[idx].quantidade + delta);
    W.itens[idx].precoTotal = W.itens[idx].precoUnitario * W.itens[idx].quantidade;
    renderStep();
  };

  // FIX: atualizarItem separado em tempo e dificuldade para evitar re-render no oninput
  window.atualizarItemTempo = (idx, val) => {
    W.itens[idx].tempoAjustado = parseInt(val) || 1;
    if (!W.itens[idx].usaPrecoFixo) recalcItem(idx);
    // Atualiza só o total do item sem re-render completo
    const el = document.querySelector(`#itens-wizard .border:nth-child(${idx + 1}) .text-primary`);
    if (el) el.textContent = moeda(W.itens[idx].precoTotal);
  };

  window.atualizarItemDificuldade = (idx, val) => {
    W.itens[idx].dificuldade = val;
    if (!W.itens[idx].usaPrecoFixo) recalcItem(idx);
    renderStep(); // re-render ok para select (não perde foco)
  };

  window.adicionarFoto = (e) => {
    const files = Array.from(e.target.files || []);
    files.forEach(file => {
      const reader = new FileReader();
      reader.onload = (ev) => {
        W.fotos.push(ev.target.result);
        document.getElementById('grid-fotos').innerHTML = renderGridFotos();
      };
      reader.readAsDataURL(file);
    });
  };

  window.removerFoto = (idx) => {
    W.fotos.splice(idx, 1);
    document.getElementById('grid-fotos').innerHTML = renderGridFotos();
  };

  // FIX: setTipoDesconto salva o valor atual antes de re-renderizar
  window.setTipoDesconto = (tipo) => {
    W.desconto.valor = parseFloat(document.getElementById('inp-desconto')?.value) || 0;
    W.desconto.tipo  = tipo;
    renderStep();
  };

  window.setValidade = (d) => { W.validade = d; renderStep(); };

  // FIX: atualiza W.desconto sem re-render (atualiza só o total na tela)
  window.atualizarDescontoLive = () => {
    W.desconto.valor = parseFloat(document.getElementById('inp-desconto')?.value) || 0;
    const totais = calcularTotalOrcamento(W.itens, State.config.taxaDeslocamento, W.desconto);
    const el = document.getElementById('total-preview');
    if (el) el.textContent = moeda(totais.total);
  };

  window.salvarOrcamento = async () => {
    const btn = document.getElementById('btn-salvar');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...'; }

    // garante que o desconto está atualizado com o valor do input
    W.desconto.valor = parseFloat(document.getElementById('inp-desconto')?.value) || W.desconto.valor;
    const totais = calcularTotalOrcamento(W.itens, State.config.taxaDeslocamento, W.desconto);

    try {
      const orcamento = {
        clienteId:      W.cliente.id,
        profissaoId:    W.profissao.id,
        profissaoNome:  W.profissao.nome,
        data:           new Date().toISOString(),
        validade:       W.validade,
        dataVencimento: dataVencimento(W.validade),
        itens: W.itens.map(i => ({
          servicoId:    i.servicoId,
          nome:         i.nome,
          tempoAjustado:i.tempoAjustado,
          dificuldade:  i.dificuldade,
          precoUnitario:i.precoUnitario,
          quantidade:   i.quantidade,
          precoTotal:   i.precoTotal,
          usaPrecoFixo: i.usaPrecoFixo,
        })),
        desconto:        W.desconto,
        taxaDeslocamento:totais.taxaDeslocamento,
        subtotal:        totais.subtotal,
        total:           totais.total,
        status:          'pendente',
      };

      const id = await add('orcamentos', orcamento);

      for (const blob of W.fotos) {
        await add('fotos', { orcamentoId: id, blob, criadoEm: new Date().toISOString() });
      }

      toast('Orçamento salvo!');
      navigate('/visualizar', { id });
    } catch (err) {
      console.error('Erro ao salvar:', err);
      toast('Erro ao salvar orçamento.', 'danger');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar Orçamento'; }
    }
  };
}

function recalcItem(idx) {
  const vm  = State.valorMinutoPorProfissao[W.profissao.id] || 0;
  const cfg = State.config;
  W.itens[idx].precoUnitario = calcularPrecoServico(
    W.itens[idx].tempoAjustado,
    vm,
    DIFICULDADE[W.itens[idx].dificuldade].fator,
    cfg.margemReserva
  );
  W.itens[idx].precoTotal = W.itens[idx].precoUnitario * W.itens[idx].quantidade;
}

function stepHeader(current) {
  const steps = [
    { n: 1, label: 'Cliente',  icon: 'bi-person' },
    { n: 2, label: 'Serviços', icon: 'bi-tools' },
    { n: 3, label: 'Fotos',    icon: 'bi-camera' },
    { n: 4, label: 'Resumo',   icon: 'bi-clipboard-check' },
  ];
  return `
    <div class="d-flex align-items-center justify-content-between mb-4">
      ${steps.map((s, i) => `
        <div class="d-flex align-items-center ${i < steps.length - 1 ? 'flex-fill' : ''}">
          <div class="text-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1
              ${current === s.n ? 'bg-primary text-white' : current > s.n ? 'bg-success text-white' : 'bg-light text-muted'}"
              style="width:36px;height:36px">
              <i class="bi ${current > s.n ? 'bi-check-lg' : s.icon} small"></i>
            </div>
            <div class="small d-none d-sm-block ${current === s.n ? 'fw-semibold text-primary' : 'text-muted'}">${s.label}</div>
          </div>
          ${i < steps.length - 1 ? `<div class="flex-fill border-top mx-2 ${current > s.n ? 'border-success' : 'border-secondary opacity-25'}"></div>` : ''}
        </div>
      `).join('')}
    </div>
    <h5 class="fw-bold mb-3">
      ${{ 1: 'Cliente e Profissão', 2: 'Serviços', 3: 'Fotos', 4: 'Revisar e Salvar' }[current]}
    </h5>`;
}
