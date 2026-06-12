import { db, fmtBRL } from '../database/db.js';
import { calculateKnifeCost } from '../services/cost-engine.js';
import { knifeTemplates }     from '../database/templates.js';
import { applyKnifeTemplate } from '../services/template.service.js';
import { generatePremiumPDF } from '../services/pdf.service.js';
import { showToast }          from '../components/toast.js';
import { navigate }           from '../core/router.js';
import { STATUS_PEDIDO, TIPO_FINANCEIRO } from '../database/db.js';

export async function calculadoraPage() {
  const settings = await db.settings.toCollection().first();
  const defaultMargin   = settings?.margemPadrao || 100;
  const defaultHourCost = settings?.custoHora || 50;

  return `
    <section>
      <div class="page-header">
        <div>
          <h1>Calculadora</h1>
          <p>Precificação inteligente</p>
        </div>
      </div>

      <!-- TEMPLATE RÁPIDO -->
      <div class="card" style="margin-bottom:16px">
        <label style="margin-bottom:10px">
          <i class="ph ph-lightning" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px;color:var(--accent)"></i>
          Template rápido
        </label>
        <select id="knifeTemplateSelect">
          <option value="">— Escolha um modelo base —</option>
          ${knifeTemplates.map(t => `<option value="${t.id}">${t.nome} (${t.tipoAco||''})</option>`).join('')}
        </select>
      </div>

      <!-- FORMULÁRIO -->
      <div class="card" style="margin-bottom:16px">
        <h2 style="font-size:16px;font-weight:800;margin-bottom:18px">Custos</h2>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Materiais (R$)</label>
            <input id="materialCost" type="number" min="0" step="0.01" value="200" />
          </div>
          <div>
            <label>Gás / carvão (R$)</label>
            <input id="gasCost" type="number" min="0" step="0.01" value="35" />
          </div>
          <div>
            <label>Energia (R$)</label>
            <input id="energyCost" type="number" min="0" step="0.01" value="18" />
          </div>
          <div>
            <label>Consumíveis (R$)</label>
            <input id="consumablesCost" type="number" min="0" step="0.01" value="25" />
          </div>
          <div>
            <label>Horas trabalhadas</label>
            <input id="hoursWorked" type="number" min="0" step="0.5" value="6" />
          </div>
          <div>
            <label>Custo/hora (R$)</label>
            <input id="hourlyRate" type="number" min="0" step="0.01" value="${defaultHourCost}" />
          </div>
          <div>
            <label>Depreciação de ferramentas (R$)</label>
            <input id="depreciationCost" type="number" min="0" step="0.01" value="20" />
          </div>
          <div>
            <label>Margem de lucro (%)</label>
            <input id="marginPercent" type="number" min="0" max="500" value="${defaultMargin}" />
          </div>
        </div>

        <button id="calculateKnifeButton" class="primary-button" style="margin-top:20px">
          Calcular preço
        </button>
      </div>

      <!-- RESULTADO (injetado aqui) -->
      <div id="calculatorResult"></div>
    </section>
  `;
}

// ============================================
// HELPERS
// ============================================

function getInputVal(id) { return Number(document.getElementById(id)?.value) || 0; }

function resultRow(label, value, highlight = false) {
  if (highlight) return `
    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--accent-soft);border:1px solid rgba(249,115,22,.18);border-radius:var(--radius-sm);padding:12px 16px">
      <span style="font-weight:600;color:#fb923c">${label}</span>
      <strong style="font-size:18px;color:var(--accent)">${typeof value==='number'?fmtBRL(value):value}</strong>
    </div>`;
  return `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
      <span style="color:var(--muted);font-size:14px">${label}</span>
      <span style="font-weight:600">${fmtBRL(value)}</span>
    </div>`;
}

// ============================================
// TEMPLATE SELECT
// ============================================

window.addEventListener('change', (e) => {
  if (e.target.id === 'knifeTemplateSelect' && e.target.value) {
    applyKnifeTemplate(e.target.value);
  }
});

// ============================================
// CALCULAR
// ============================================

window.addEventListener('click', async (e) => {
  if (e.target.id !== 'calculateKnifeButton') return;

  const materialCost    = getInputVal('materialCost');
  const gasCost         = getInputVal('gasCost');
  const energyCost      = getInputVal('energyCost');
  const consumablesCost = getInputVal('consumablesCost');
  const hoursWorked     = getInputVal('hoursWorked');
  const hourlyRate      = getInputVal('hourlyRate');
  const depreciationCost= getInputVal('depreciationCost');
  const marginPercent   = getInputVal('marginPercent');

  // Persiste custo/hora
  localStorage.setItem('cutelaria_hour_cost', hourlyRate);

  const result = calculateKnifeCost({
    materials:   [{ quantity: 1, unitCost: materialCost }],
    operational: { gasCost, energyCost, consumablesCost },
    labor:       { hoursWorked, hourlyRate },
    equipments:  [{ purchaseValue: depreciationCost, usefulLifeMonths: 1, monthlyUsage: 1, knifeUsage: 1 }],
    marginPercent
  });

  window.lastKnifeResult = result;

  const pctBar = Math.min(100, result.margin);
  const marginColor = result.margin >= 50 ? '#34d399' : result.margin >= 20 ? '#fb923c' : '#f87171';

  document.getElementById('calculatorResult').innerHTML = `
    <div class="card" style="margin-bottom:16px">
      <h2 style="font-size:16px;font-weight:800;margin-bottom:16px">Resultado</h2>

      ${resultRow('Materiais',   result.materialCost)}
      ${resultRow('Operacional', result.operationalCost)}
      ${resultRow('Mão de obra', result.laborCost)}
      ${resultRow('Depreciação', result.depreciationCost)}

      <div style="margin:12px 0 16px">
        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
          <span style="color:var(--muted)">Margem de lucro</span>
          <strong style="color:${marginColor}">${result.margin}%</strong>
        </div>
        <div class="progress-bar">
          <div class="progress-bar__fill" style="width:${pctBar}%;background:${marginColor}"></div>
        </div>
      </div>

      ${resultRow('Custo total',     result.totalCost,     true)}
      ${resultRow('Preço sugerido',  result.suggestedPrice, true)}
      ${resultRow('Lucro líquido',   result.netProfit,      true)}

      <!-- AÇÕES -->
      <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
        <button id="generatePdfButton" class="btn btn-ghost" style="flex:1">
          <i class="ph ph-file-text" style="width:16px;height:16px"></i> PDF
        </button>
        <button id="saveAsPedidoBtn" class="btn btn-primary" style="flex:2">
          <i class="ph ph-shopping-bag" style="width:16px;height:16px"></i> Criar pedido com este preço
        </button>
      </div>
    </div>
  `;
  document.getElementById('calculatorResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});

// ============================================
// PDF
// ============================================

window.addEventListener('click', async (e) => {
  if (e.target.id !== 'generatePdfButton' && !e.target.closest('#generatePdfButton')) return;
  const result = window.lastKnifeResult;
  if (!result) { showToast({ type:'error', message:'Calcule primeiro.' }); return; }
  const sel = document.getElementById('knifeTemplateSelect');
  const templateName = sel?.options[sel.selectedIndex]?.text || 'Faca Artesanal';
  const workshopName = localStorage.getItem('cutelaria_workshop_name') || 'Cutelaria';
  const selectedTemplate = sel?.value ? knifeTemplates.find(t => t.id === sel.value) : null;
  generatePremiumPDF({ templateName,
    steelType:      selectedTemplate?.tipoAco       || 'Aço artesanal',
    handleMaterial: selectedTemplate?.materialCabo  || 'Material artesanal',
    totalCost: result.totalCost, suggestedPrice: result.suggestedPrice,
    profit: result.netProfit, margin: result.margin, workshopName });
});

// ============================================
// CRIAR PEDIDO A PARTIR DO ORÇAMENTO
// ============================================

window.addEventListener('click', async (e) => {
  if (e.target.id !== 'saveAsPedidoBtn' && !e.target.closest('#saveAsPedidoBtn')) return;
  const result = window.lastKnifeResult;
  if (!result) { showToast({ type:'error', message:'Calcule primeiro.' }); return; }

  const sel = document.getElementById('knifeTemplateSelect');
  const nome = sel?.value
    ? (sel.options[sel.selectedIndex]?.text || 'Faca artesanal')
    : 'Faca artesanal';

  await db.pedidos.add({
    nome,
    cliente:    '',
    valor:      result.suggestedPrice,
    prazo:      '',
    status:     STATUS_PEDIDO.ABERTO,
    observacao: `Orçamento: custo ${fmtBRL(result.totalCost)}, margem ${result.margin}%`,
    createdAt:  new Date().toISOString()
  });

  showToast({ message: 'Pedido criado com o preço calculado!' });
  setTimeout(() => navigate('pedidos'), 500);
});
