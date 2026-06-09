// ============================================================
// pages/configuracoes.js
// ============================================================

import { render, toast, reloadConfig, State } from '../js/app.js';
import { getAllConfig, setConfig, getAll } from '../js/db.js';
import { calcularValorMinuto, moeda } from '../js/calculadora.js';

export default async function configuracoesPage() {
  const cfg       = await getAllConfig();
  const profissoes = await getAll('profissoes');
  const ativas    = new Set(cfg.profissoesAtivas || []);

  let formData = {
    metaSalarial:     cfg.metaSalarial     || 5000,
    horasTrabalhadas: cfg.horasTrabalhadas || 160,
    margemReserva:    cfg.margemReserva    || 0.2,
    taxaDeslocamento: cfg.taxaDeslocamento || 50,
    validadePadrao:   cfg.validadePadrao   || 30,
  };

  function calcVm() {
    return calcularValorMinuto(formData.metaSalarial, formData.horasTrabalhadas, 1.0);
  }

  render(`
    <div class="page-content pb-5">
      <h4 class="fw-bold mb-4">Configurações</h4>

      <!-- Meta salarial -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2 text-primary"></i>Financeiro</h6>

          <div class="mb-3">
            <label class="form-label fw-semibold">Meta salarial mensal (R$)</label>
            <input type="number" class="form-control" id="inp-meta"
              value="${formData.metaSalarial}" min="500" oninput="cfgPreview()">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Horas trabalhadas por mês</label>
            <input type="number" class="form-control" id="inp-horas"
              value="${formData.horasTrabalhadas}" min="40" max="300" oninput="cfgPreview()">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">
              Margem de reserva: <span id="lbl-margem">${Math.round(formData.margemReserva * 100)}%</span>
            </label>
            <input type="range" class="form-range" id="inp-margem"
              min="0" max="0.5" step="0.05" value="${formData.margemReserva}" oninput="cfgPreview()">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Taxa de deslocamento (R$)</label>
            <input type="number" class="form-control" id="inp-desloc"
              value="${formData.taxaDeslocamento}" min="0" oninput="cfgPreview()">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Validade padrão dos orçamentos</label>
            <div class="d-flex gap-2 flex-wrap">
              ${[1,5,15,30].map(d => `
                <button class="btn btn-sm ${formData.validadePadrao === d ? 'btn-primary' : 'btn-outline-secondary'}"
                  id="btn-val-${d}" onclick="setValidadePadrao(${d})">${d} dia${d > 1 ? 's' : ''}</button>`).join('')}
            </div>
          </div>

          <div class="alert alert-primary d-flex align-items-center gap-3">
            <i class="bi bi-calculator fs-3"></i>
            <div>
              <div class="small text-muted">Seu valor por minuto</div>
              <div class="fw-bold fs-4" id="preview-vm">${moeda(calcVm())}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Profissões ativas -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="bi bi-briefcase me-2 text-primary"></i>Profissões Ativas</h6>
          <div class="row g-2" id="grid-profs">
            ${profissoes.map(p => `
              <div class="col-6">
                <button class="btn btn-prof w-100 text-start p-2 rounded-3 border
                  ${ativas.has(p.id) ? 'btn-primary text-white' : 'btn-outline-secondary'}"
                  id="btn-prof-${p.id}" onclick="toggleProfCfg(${p.id})">
                  <i class="bi ${p.icone} me-1"></i>${p.nome}
                </button>
              </div>`).join('')}
          </div>
          <div class="text-muted small mt-2">Selecione as profissões que você exerce.</div>
        </div>
      </div>

      <!-- Botão salvar -->
      <button class="btn btn-primary w-100 py-3 fw-semibold" onclick="salvarConfiguracoes()">
        <i class="bi bi-check-lg me-2"></i>Salvar Configurações
      </button>

      <!-- Zona de perigo -->
      <div class="card border-danger mt-4">
        <div class="card-body">
          <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Zona de Perigo</h6>
          <p class="small text-muted mb-3">Apagar todos os dados do aplicativo. Esta ação não pode ser desfeita.</p>
          <button class="btn btn-outline-danger btn-sm" onclick="resetarApp()">
            Apagar todos os dados
          </button>
        </div>
      </div>
    </div>
  `);

  // estado local das profissões ativas (mutável)
  let _ativas = new Set(ativas);

  window.cfgPreview = () => {
    formData.metaSalarial     = parseFloat(document.getElementById('inp-meta')?.value) || 5000;
    formData.horasTrabalhadas = parseFloat(document.getElementById('inp-horas')?.value) || 160;
    formData.margemReserva    = parseFloat(document.getElementById('inp-margem')?.value) || 0.2;
    formData.taxaDeslocamento = parseFloat(document.getElementById('inp-desloc')?.value) || 50;
    const vm = calcularValorMinuto(formData.metaSalarial, formData.horasTrabalhadas, 1.0);
    const el = document.getElementById('preview-vm');
    const lbl = document.getElementById('lbl-margem');
    if (el) el.textContent = moeda(vm);
    if (lbl) lbl.textContent = Math.round(formData.margemReserva * 100) + '%';
  };

  window.setValidadePadrao = (d) => {
    formData.validadePadrao = d;
    [1,5,15,30].forEach(v => {
      const b = document.getElementById(`btn-val-${v}`);
      if (b) b.className = `btn btn-sm ${v === d ? 'btn-primary' : 'btn-outline-secondary'}`;
    });
  };

  window.toggleProfCfg = (id) => {
    if (_ativas.has(id)) _ativas.delete(id);
    else _ativas.add(id);
    const btn = document.getElementById(`btn-prof-${id}`);
    const prof = profissoes.find(p => p.id === id);
    if (btn && prof) {
      btn.className = `btn btn-prof w-100 text-start p-2 rounded-3 border ${_ativas.has(id) ? 'btn-primary text-white' : 'btn-outline-secondary'}`;
    }
  };

  window.salvarConfiguracoes = async () => {
    formData.metaSalarial     = parseFloat(document.getElementById('inp-meta')?.value) || 5000;
    formData.horasTrabalhadas = parseFloat(document.getElementById('inp-horas')?.value) || 160;
    formData.margemReserva    = parseFloat(document.getElementById('inp-margem')?.value) || 0.2;
    formData.taxaDeslocamento = parseFloat(document.getElementById('inp-desloc')?.value) || 50;

    if (_ativas.size === 0) { toast('Selecione pelo menos uma profissão.', 'warning'); return; }

    await setConfig('metaSalarial',     formData.metaSalarial);
    await setConfig('horasTrabalhadas', formData.horasTrabalhadas);
    await setConfig('margemReserva',    formData.margemReserva);
    await setConfig('taxaDeslocamento', formData.taxaDeslocamento);
    await setConfig('validadePadrao',   formData.validadePadrao);
    await setConfig('profissoesAtivas', [..._ativas]);

    await reloadConfig();
    toast('Configurações salvas!');
  };

  window.resetarApp = async () => {
    if (!confirm('Apagar TODOS os dados? Clientes, orçamentos, serviços e configurações serão perdidos.')) return;
    if (!confirm('Tem certeza? Isso não pode ser desfeito.')) return;
    indexedDB.deleteDatabase('MaoDeObraPro');
    toast('Dados apagados. Reiniciando...', 'danger', 2000);
    setTimeout(() => window.location.reload(), 2000);
  };
}
