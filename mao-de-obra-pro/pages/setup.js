// ============================================================
// pages/setup.js
// ============================================================

import { render, toast, reloadConfig, State } from '../js/app.js';
import { setConfig, getAll, getAllConfig } from '../js/db.js';
import { calcularValorMinuto, moeda } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

export default async function setupPage() {
  const profissoes = await getAll('profissoes');
  const cfg = await getAllConfig();

  let step = 1;
  let profSelecionadas = new Set(cfg.profissoesAtivas || []);
  let formValues = {
    metaSalarial:     cfg.metaSalarial     || 5000,
    horasTrabalhadas: cfg.horasTrabalhadas || 160,
    margemReserva:    cfg.margemReserva    || 0.2,
    taxaDeslocamento: cfg.taxaDeslocamento || 50,
    validadePadrao:   cfg.validadePadrao   || 30,
  };

  function renderStep() {
    if (step === 1) renderStep1();
    else renderStep2();
  }

  function renderStep1() {
    render(`
      <div class="setup-wrapper d-flex flex-column align-items-center justify-content-center min-vh-100 p-3">
        <div class="setup-card card shadow-lg w-100" style="max-width:520px">
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <div class="setup-icon mb-3"><i class="bi bi-lightning-charge-fill text-primary fs-1"></i></div>
              <h2 class="fw-bold">Mão de Obra PRO</h2>
              <p class="text-muted">Vamos configurar seu perfil. Primeiro, quais são suas profissões?</p>
            </div>
            <p class="fw-semibold mb-3">Selecione uma ou mais profissões:</p>
            <div class="row g-2 mb-4" id="prof-grid">
              ${profissoes.map(p => `
                <div class="col-6">
                  <button class="btn btn-prof w-100 text-start p-3 rounded-3 border ${profSelecionadas.has(p.id) ? 'btn-primary text-white' : 'btn-outline-secondary'}"
                    data-id="${p.id}" onclick="toggleProf(${p.id})">
                    <i class="bi ${p.icone} me-2"></i>${p.nome}
                    <div class="small mt-1 opacity-75">${p.descricao.substring(0, 35)}...</div>
                  </button>
                </div>`).join('')}
            </div>
            <div id="prof-error" class="text-danger small mb-2 d-none">Selecione pelo menos uma profissão.</div>
            <button class="btn btn-primary w-100 py-3 fw-semibold" onclick="goStep2()">
              Próximo <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>
        </div>
      </div>
    `);
  }

  function renderStep2() {
    const vm = calcularValorMinuto(formValues.metaSalarial, formValues.horasTrabalhadas, 1.0);
    render(`
      <div class="setup-wrapper d-flex flex-column align-items-center justify-content-center min-vh-100 p-3">
        <div class="setup-card card shadow-lg w-100" style="max-width:520px">
          <div class="card-body p-4">
            <button class="btn btn-link p-0 mb-3 text-decoration-none" onclick="backStep1()">
              <i class="bi bi-arrow-left me-1"></i> Voltar
            </button>
            <h4 class="fw-bold mb-1">Configuração Financeira</h4>
            <p class="text-muted small mb-4">Esses valores definem o preço dos seus serviços automaticamente.</p>

            <div class="mb-3">
              <label class="form-label fw-semibold">Meta salarial mensal (R$)</label>
              <input type="number" class="form-control form-control-lg" id="inp-meta"
                value="${formValues.metaSalarial}" min="500" oninput="updatePreview()">
              <div class="form-text">Quanto você quer ganhar por mês.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Horas trabalhadas por mês</label>
              <input type="number" class="form-control form-control-lg" id="inp-horas"
                value="${formValues.horasTrabalhadas}" min="40" max="300" oninput="updatePreview()">
              <div class="form-text">Média de horas produtivas (sem tempo de deslocamento).</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Margem de reserva: <span id="label-margem">${Math.round(formValues.margemReserva*100)}%</span></label>
              <input type="range" class="form-range" id="inp-margem"
                min="0" max="0.5" step="0.05" value="${formValues.margemReserva}" oninput="updatePreview()">
              <div class="form-text">Para cobrir imprevistos, ferramentas e impostos.</div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Taxa de deslocamento (R$)</label>
              <input type="number" class="form-control" id="inp-desloc"
                value="${formValues.taxaDeslocamento}" min="0" oninput="updatePreview()">
              <div class="form-text">Cobrada por visita, independente do serviço.</div>
            </div>

            <div class="alert alert-primary d-flex align-items-center gap-3 mb-4">
              <i class="bi bi-calculator fs-4"></i>
              <div>
                <div class="fw-semibold">Seu valor por minuto</div>
                <div class="fs-4 fw-bold" id="preview-vm">${moeda(vm)}</div>
                <div class="small text-muted">Sem ajuste de risco da profissão</div>
              </div>
            </div>

            <button class="btn btn-success w-100 py-3 fw-semibold" onclick="finalizarSetup()">
              <i class="bi bi-check-lg me-2"></i>Começar a usar
            </button>
          </div>
        </div>
      </div>
    `);
  }

  // expõe funções ao escopo global (chamadas via onclick inline)
  window.toggleProf = (id) => {
    if (profSelecionadas.has(id)) profSelecionadas.delete(id);
    else profSelecionadas.add(id);
    renderStep1();
  };

  window.goStep2 = () => {
    if (profSelecionadas.size === 0) {
      document.getElementById('prof-error')?.classList.remove('d-none');
      return;
    }
    step = 2;
    renderStep2();
  };

  window.backStep1 = () => { step = 1; renderStep1(); };

  window.updatePreview = () => {
    formValues.metaSalarial     = parseFloat(document.getElementById('inp-meta')?.value) || 5000;
    formValues.horasTrabalhadas = parseFloat(document.getElementById('inp-horas')?.value) || 160;
    formValues.margemReserva    = parseFloat(document.getElementById('inp-margem')?.value) || 0.2;
    formValues.taxaDeslocamento = parseFloat(document.getElementById('inp-desloc')?.value) || 50;
    const vm = calcularValorMinuto(formValues.metaSalarial, formValues.horasTrabalhadas, 1.0);
    const el = document.getElementById('preview-vm');
    const lbl = document.getElementById('label-margem');
    if (el) el.textContent = moeda(vm);
    if (lbl) lbl.textContent = Math.round(formValues.margemReserva * 100) + '%';
  };

  window.finalizarSetup = async () => {
    formValues.metaSalarial     = parseFloat(document.getElementById('inp-meta')?.value) || 5000;
    formValues.horasTrabalhadas = parseFloat(document.getElementById('inp-horas')?.value) || 160;
    formValues.margemReserva    = parseFloat(document.getElementById('inp-margem')?.value) || 0.2;
    formValues.taxaDeslocamento = parseFloat(document.getElementById('inp-desloc')?.value) || 50;

    await setConfig('metaSalarial',     formValues.metaSalarial);
    await setConfig('horasTrabalhadas', formValues.horasTrabalhadas);
    await setConfig('margemReserva',    formValues.margemReserva);
    await setConfig('taxaDeslocamento', formValues.taxaDeslocamento);
    await setConfig('profissoesAtivas', [...profSelecionadas]);
    await setConfig('setupConcluido',   1);

    await reloadConfig();
    toast('Perfil configurado com sucesso!');
    navigate('/');
  };

  renderStep();
}
