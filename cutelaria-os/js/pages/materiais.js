import { db, fmtBRL, TIPO_MATERIAL, TIPO_MEDIDA, MEDIDA_CONFIG, SUBTIPO_CONSUMIVEL, SUBTIPO_MEDIDA, unidadeMaterial, passoMaterial } from '../database/db.js';
import { showToast } from '../components/toast.js';
import { openModal, closeModal } from '../components/modal.js';
import { navigate } from '../core/router.js';

// ============================================
// LABELS DE EXIBIÇÃO
// ============================================

const TIPO_LABEL = {
  [TIPO_MATERIAL.ACO]:        'Aço',
  [TIPO_MATERIAL.CABO]:       'Cabo',
  [TIPO_MATERIAL.CONSUMIVEL]: 'Consumível',
};

const MEDIDA_LABEL = {
  [TIPO_MEDIDA.COMPRIMENTO]: 'Comprimento (cm)',
  [TIPO_MEDIDA.PESO]:        'Peso (kg)',
  [TIPO_MEDIDA.TALA]:        'Tala (pares)',
  [TIPO_MEDIDA.BLOCO]:       'Bloco (un)',
  [TIPO_MEDIDA.UNIDADE]:     'Unidade (un)',
  [TIPO_MEDIDA.PINO_MM]:     'Comprimento (mm)',
  [TIPO_MEDIDA.ESPACADOR]:   'Unidade (un)',
};

const SUBTIPO_LABEL = {
  [SUBTIPO_CONSUMIVEL.GENERICO]:  'Genérico',
  [SUBTIPO_CONSUMIVEL.PINO]:      'Pino',
  [SUBTIPO_CONSUMIVEL.ESPACADOR]: 'Espaçador',
};

// Opções de tipoMedida por tipoMaterial — consumível é fixo pelo subtipo
const MEDIDAS_POR_TIPO = {
  [TIPO_MATERIAL.ACO]:        [TIPO_MEDIDA.COMPRIMENTO, TIPO_MEDIDA.PESO],
  [TIPO_MATERIAL.CABO]:       [TIPO_MEDIDA.TALA, TIPO_MEDIDA.BLOCO],
  [TIPO_MATERIAL.CONSUMIVEL]: [], // derivado do subtipo, não usado diretamente
};

const tipoBadgeClass = {
  [TIPO_MATERIAL.ACO]:        'badge-blue',
  [TIPO_MATERIAL.CABO]:       'badge-orange',
  [TIPO_MATERIAL.CONSUMIVEL]: 'badge-gray',
};

// ============================================
// PAGE
// ============================================

export async function materiaisPage() {
  if (!db.materiais) return `<div class="card"><p style="color:var(--muted)">Tabela materiais não disponível.</p></div>`;

  const materiais    = await db.materiais.toArray();
  const valorEstoque = materiais.reduce((s,i) => s + Number(i.valor||0) * Number(i.estoqueAtual||0), 0);
  const criticos     = materiais.filter(i => Number(i.estoqueAtual||0) <= Number(i.estoqueMinimo||0) && Number(i.estoqueMinimo||0) > 0);

  // Agrupar por tipo para exibição organizada
  const acos       = materiais.filter(i => i.tipoMaterial === TIPO_MATERIAL.ACO);
  const cabos      = materiais.filter(i => i.tipoMaterial === TIPO_MATERIAL.CABO);
  const consumiveis= materiais.filter(i => i.tipoMaterial === TIPO_MATERIAL.CONSUMIVEL);
  const semTipo    = materiais.filter(i => !i.tipoMaterial); // legados sem tipo

  const stockColor = (item) => {
    const atual = Number(item.estoqueAtual||0);
    const min   = Number(item.estoqueMinimo||0);
    if (min <= 0)           return 'var(--text)';
    if (atual <= min)       return '#f87171';
    if (atual <= min * 1.5) return '#fb923c';
    return '#34d399';
  };

  const renderCard = (item) => {
    const cor     = stockColor(item);
    const unidade = unidadeMaterial(item);
    const passo   = passoMaterial(item);
    const pctBarra= Number(item.estoqueMinimo||0) > 0
      ? Math.min(100, (Number(item.estoqueAtual||0) / Math.max(1, Number(item.estoqueMinimo||0) * 2)) * 100)
      : 0;

    return `
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
          <div style="flex:1;min-width:0">
            <div style="font-size:16px;font-weight:700;margin-bottom:4px">${item.nome}</div>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
              ${item.tipoMaterial ? `<span class="badge ${tipoBadgeClass[item.tipoMaterial]||'badge-gray'}" style="font-size:11px">${TIPO_LABEL[item.tipoMaterial]||item.tipoMaterial}</span>` : ''}
              ${item.subtipoConsumivel ? `<span class="badge badge-gray" style="font-size:11px">${SUBTIPO_LABEL[item.subtipoConsumivel]||item.subtipoConsumivel}</span>` : (item.tipoMedida ? `<span class="badge badge-gray" style="font-size:11px">${MEDIDA_LABEL[item.tipoMedida]||item.tipoMedida}</span>` : '')}
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:15px;font-weight:700;color:var(--accent)">${fmtBRL(item.valor)}<span style="font-size:11px;color:var(--muted);font-weight:400">/${unidade}</span></div>
            <div style="font-size:13px;font-weight:600;color:${cor};margin-top:2px">${item.estoqueAtual||0} ${unidade}</div>
          </div>
        </div>

        ${Number(item.estoqueMinimo||0) > 0 ? `
          <div style="margin-bottom:12px">
            <div class="progress-bar">
              <div class="progress-bar__fill" style="width:${pctBarra}%;background:${cor}"></div>
            </div>
            <div style="font-size:11px;color:var(--muted);margin-top:3px">Mínimo: ${item.estoqueMinimo} ${unidade}</div>
          </div>
        ` : '<div style="margin-bottom:12px"></div>'}

        <!-- AJUSTE RÁPIDO + AÇÕES -->
        <div style="display:flex;align-items:center;gap:6px">
          <button class="btn btn-ghost btn-sm stock-dec-btn"
            data-id="${item.id}" data-passo="${passo}"
            style="width:34px;height:34px;padding:0;font-size:18px;flex-shrink:0"
            title="−${passo} ${unidade}">−</button>

          <span style="font-size:13px;font-weight:700;min-width:56px;text-align:center;color:${cor}">
            ${item.estoqueAtual||0} ${unidade}
          </span>

          <button class="btn btn-ghost btn-sm stock-inc-btn"
            data-id="${item.id}" data-passo="${passo}"
            style="width:34px;height:34px;padding:0;font-size:18px;flex-shrink:0"
            title="+${passo} ${unidade}">+</button>

          <div style="flex:1"></div>

          <button class="btn btn-ghost btn-sm edit-material-btn" data-id="${item.id}">
            <i data-lucide="pencil" style="width:14px;height:14px"></i>
          </button>
          <button class="btn btn-danger btn-sm delete-material-btn" data-id="${item.id}">
            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
          </button>
        </div>
      </div>
    `;
  };

  const renderGrupo = (titulo, lista, icone) => {
    if (!lista.length) return '';
    return `
      <div style="margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
          <i data-lucide="${icone}" style="width:16px;height:16px;color:var(--accent)"></i>
          <h2 style="font-size:15px;font-weight:700;color:var(--muted)">${titulo} <span style="color:var(--text)">(${lista.length})</span></h2>
        </div>
        <div class="grid-stack">${lista.map(renderCard).join('')}</div>
      </div>
    `;
  };

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Materiais</h1>
          <p>Controle de estoque</p>
        </div>
        <button id="newMaterialBtn" class="btn btn-primary btn-sm">+ Novo</button>
      </div>

      <!-- KPIs -->
      <div class="grid-2" style="gap:10px;margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">Valor em estoque</div>
          <div class="kpi-card__value" style="font-size:18px;color:var(--accent)">${fmtBRL(valorEstoque)}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Itens críticos</div>
          <div class="kpi-card__value" style="color:${criticos.length?'#f87171':'#34d399'}">${criticos.length}</div>
          <div class="kpi-card__sub">${materiais.length} itens no total</div>
        </div>
      </div>

      <!-- ALERTA CRÍTICOS -->
      ${criticos.length ? `
        <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:20px">
          <div style="font-weight:700;font-size:14px;color:#f87171;margin-bottom:8px">
            <i data-lucide="alert-triangle" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Estoque crítico
          </div>
          ${criticos.map(i => `
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-top:1px solid rgba(239,68,68,.1)">
              <span>${i.nome}</span>
              <span style="color:#f87171;font-weight:700">${i.estoqueAtual} ${unidadeMaterial(i)} <span style="color:var(--muted);font-weight:400">(mín: ${i.estoqueMinimo})</span></span>
            </div>
          `).join('')}
        </div>
      ` : ''}

      <!-- LISTA AGRUPADA -->
      ${!materiais.length ? `
        <div class="card" style="text-align:center;padding:40px 20px">
          <p style="color:var(--muted)">Nenhum material cadastrado ainda.</p>
          <button id="newMaterialBtn2" class="btn btn-primary" style="margin-top:16px;width:auto;display:inline-flex">+ Adicionar material</button>
        </div>
      ` : `
        ${renderGrupo('Aços',       acos,        'hammer')}
        ${renderGrupo('Cabos',      cabos,       'grip-horizontal')}
        ${renderGrupo('Consumíveis',consumiveis, 'package')}
        ${semTipo.length ? renderGrupo('Sem classificação', semTipo, 'help-circle') : ''}
      `}
    </section>
  `;
}

// ============================================
// MODAL DE CADASTRO / EDIÇÃO
// ============================================

function buildMedidaOptions(tipoMaterial, tipoMedidaAtual) {
  const medidas = MEDIDAS_POR_TIPO[tipoMaterial] || [TIPO_MEDIDA.UNIDADE];
  return medidas.map(m =>
    `<option value="${m}" ${tipoMedidaAtual === m ? 'selected' : ''}>` + MEDIDA_LABEL[m] + `</option>`
  ).join('');
}

function atualizarLabels(tipoMedida) {
  const unidade = MEDIDA_CONFIG[tipoMedida]?.unidade || 'un';
  const vl = document.getElementById('valorLabel');
  const el = document.getElementById('estoqueLabel');
  if (vl) vl.textContent = `por ${unidade}`;
  if (el) el.textContent = `(${unidade})`;
}

function openMaterialModal(existing = null) {
  const tipoAtual     = existing?.tipoMaterial      || TIPO_MATERIAL.ACO;
  const subtipoAtual  = existing?.subtipoConsumivel || SUBTIPO_CONSUMIVEL.GENERICO;
  const medidaAtual   = existing?.tipoMedida        || TIPO_MEDIDA.COMPRIMENTO;
  const isConsumivel  = tipoAtual === TIPO_MATERIAL.CONSUMIVEL;
  const medidaEfetiva = isConsumivel
    ? (SUBTIPO_MEDIDA[subtipoAtual] || TIPO_MEDIDA.UNIDADE)
    : medidaAtual;
  const unidadeEfetiva = MEDIDA_CONFIG[medidaEfetiva]?.unidade || 'un';

  const tiposBtns = [
    { v: TIPO_MATERIAL.ACO,       l: 'Aço',       },
    { v: TIPO_MATERIAL.CABO,      l: 'Cabo',      },
    { v: TIPO_MATERIAL.CONSUMIVEL,l: 'Consumível',},
  ].map(opt => {
    const ativo = tipoAtual === opt.v;
    return `<label style="flex:1;cursor:pointer">
      <input type="radio" name="matTipo" value="${opt.v}" ${ativo ? 'checked' : ''} style="position:absolute;opacity:0;pointer-events:none" />
      <div class="tipo-btn" style="text-align:center;padding:10px 6px;border-radius:14px;border:1px solid ${ativo?'rgba(249,115,22,.3)':'var(--border)'};font-size:13px;font-weight:600;transition:all .15s;background:${ativo?'rgba(249,115,22,.12)':'rgba(255,255,255,.03)'};color:${ativo?'#fb923c':'var(--muted)'}">
        ${opt.l}
      </div>
    </label>`;
  }).join('');

  const subtiposBtns = Object.values(SUBTIPO_CONSUMIVEL).map(s => {
    const ativo = subtipoAtual === s;
    return `<label style="flex:1;cursor:pointer">
      <input type="radio" name="matSubtipo" value="${s}" ${ativo ? 'checked' : ''} style="position:absolute;opacity:0;pointer-events:none" />
      <div class="subtipo-btn" style="text-align:center;padding:9px 6px;border-radius:12px;border:1px solid ${ativo?'rgba(99,102,241,.35)':'var(--border)'};font-size:13px;font-weight:600;transition:all .15s;background:${ativo?'rgba(99,102,241,.12)':'rgba(255,255,255,.03)'};color:${ativo?'#818cf8':'var(--muted)'}">
        ` + (SUBTIPO_LABEL[s]||s) + `
      </div>
    </label>`;
  }).join('');

  openModal({
    title: existing ? 'Editar Material' : 'Novo Material',
    size: 'md',
    content: `
      <form id="materialForm" class="grid-stack">
        <div>
          <label>Nome *</label>
          <input type="text" id="matNome" placeholder="Ex: Aço 1070, Micarta verde, Pino latão..." value="${existing?.nome||''}" />
        </div>
        <div>
          <label>Tipo de material</label>
          <div style="display:flex;gap:8px">${tiposBtns}</div>
        </div>
        <div id="subtipoWrapper" style="display:${isConsumivel ? 'block' : 'none'}">
          <label>Subtipo</label>
          <div style="display:flex;gap:8px">${subtiposBtns}</div>
          <div id="medidaInfo" style="font-size:12px;color:var(--muted);margin-top:8px">
            Medida: <strong style="color:var(--text)">${MEDIDA_LABEL[medidaEfetiva]||''}</strong>
          </div>
        </div>
        <div id="medidaWrapper" style="display:${isConsumivel ? 'none' : 'block'}">
          <label>Como é medido</label>
          <select id="matMedida">${buildMedidaOptions(tipoAtual, medidaAtual)}</select>
        </div>
        <div>
          <label>Valor unitário (R$) <span id="valorLabel" style="color:var(--muted);font-weight:400;font-size:12px">por ${unidadeEfetiva}</span></label>
          <input type="number" step="0.01" min="0" id="matValor" placeholder="0,00" value="${existing?.valor||''}" />
        </div>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Estoque atual <span id="estoqueLabel" style="color:var(--muted);font-weight:400;font-size:12px">(${unidadeEfetiva})</span></label>
            <input type="number" step="0.01" min="0" id="matAtual" placeholder="0" value="${existing?.estoqueAtual||''}" />
          </div>
          <div>
            <label>Estoque mínimo <span style="color:var(--muted);font-weight:400;font-size:12px">(alerta)</span></label>
            <input type="number" step="0.01" min="0" id="matMinimo" placeholder="0" value="${existing?.estoqueMinimo||''}" />
          </div>
        </div>
        <button type="submit" class="primary-button">${existing ? 'Salvar alterações' : 'Cadastrar material'}</button>
      </form>
    `
  });

  // Troca tipo de material
  document.querySelectorAll('input[name="matTipo"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const tipo = radio.value;
      const isC  = tipo === TIPO_MATERIAL.CONSUMIVEL;
      document.getElementById('subtipoWrapper').style.display = isC ? 'block' : 'none';
      document.getElementById('medidaWrapper').style.display  = isC ? 'none'  : 'block';
      if (!isC) {
        const medidas  = MEDIDAS_POR_TIPO[tipo] || [TIPO_MEDIDA.UNIDADE];
        const primeira = medidas[0];
        document.getElementById('matMedida').innerHTML = buildMedidaOptions(tipo, primeira);
        atualizarLabels(primeira);
      } else {
        const sub = document.querySelector('input[name="matSubtipo"]:checked')?.value || SUBTIPO_CONSUMIVEL.GENERICO;
        const med = SUBTIPO_MEDIDA[sub] || TIPO_MEDIDA.UNIDADE;
        atualizarLabels(med);
        document.getElementById('medidaInfo').innerHTML = `Medida: <strong style="color:var(--text)">${MEDIDA_LABEL[med]}</strong>`;
      }
      document.querySelectorAll('.tipo-btn').forEach(btn => {
        const r = btn.closest('label').querySelector('input[type=radio]');
        const a = r.value === tipo;
        btn.style.background = a ? 'rgba(249,115,22,.12)' : 'rgba(255,255,255,.03)';
        btn.style.color      = a ? '#fb923c' : 'var(--muted)';
        btn.style.border     = `1px solid ${a ? 'rgba(249,115,22,.3)' : 'var(--border)'}`;
      });
    });
  });

  // Troca subtipo consumível
  document.querySelectorAll('input[name="matSubtipo"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const subtipo = radio.value;
      const med     = SUBTIPO_MEDIDA[subtipo] || TIPO_MEDIDA.UNIDADE;
      atualizarLabels(med);
      document.getElementById('medidaInfo').innerHTML = `Medida: <strong style="color:var(--text)">${MEDIDA_LABEL[med]}</strong>`;
      document.querySelectorAll('.subtipo-btn').forEach(btn => {
        const r = btn.closest('label').querySelector('input[type=radio]');
        const a = r.value === subtipo;
        btn.style.background = a ? 'rgba(99,102,241,.12)' : 'rgba(255,255,255,.03)';
        btn.style.color      = a ? '#818cf8' : 'var(--muted)';
        btn.style.border     = `1px solid ${a ? 'rgba(99,102,241,.35)' : 'var(--border)'}`;
      });
    });
  });

  // Troca medida (Aço/Cabo)
  document.getElementById('matMedida').addEventListener('change', (e) => {
    atualizarLabels(e.target.value);
  });

  // Submit
  document.getElementById('materialForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('matNome').value.trim();
    if (!nome) { showToast({ type:'error', message:'Informe o nome.' }); return; }

    const tipoMaterial      = document.querySelector('input[name="matTipo"]:checked')?.value || TIPO_MATERIAL.ACO;
    const isC               = tipoMaterial === TIPO_MATERIAL.CONSUMIVEL;
    const subtipoConsumivel = isC
      ? (document.querySelector('input[name="matSubtipo"]:checked')?.value || SUBTIPO_CONSUMIVEL.GENERICO)
      : null;
    const tipoMedida        = isC
      ? (SUBTIPO_MEDIDA[subtipoConsumivel] || TIPO_MEDIDA.UNIDADE)
      : document.getElementById('matMedida').value;
    const unidade           = MEDIDA_CONFIG[tipoMedida]?.unidade || 'un';

    const data = {
      nome, tipoMaterial, subtipoConsumivel, tipoMedida, unidade,
      valor:        parseFloat(document.getElementById('matValor').value)  || 0,
      estoqueAtual: parseFloat(document.getElementById('matAtual').value)  || 0,
      estoqueMinimo:parseFloat(document.getElementById('matMinimo').value) || 0,
    };

    if (existing) {
      await db.materiais.update(existing.id, data);
      showToast({ message: 'Material atualizado.' });
    } else {
      data.createdAt = new Date().toISOString();
      await db.materiais.add(data);
      showToast({ message: 'Material cadastrado!' });
    }
    closeModal();
    setTimeout(() => navigate('materiais'), 300);
  });
}
// ============================================
// EVENTS
// ============================================

window.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-id]');
  const id  = btn ? Number(btn.dataset.id) : null;

  if (e.target.id === 'newMaterialBtn'  || e.target.closest('#newMaterialBtn') ||
      e.target.id === 'newMaterialBtn2' || e.target.closest('#newMaterialBtn2')) {
    openMaterialModal(); return;
  }

  if (e.target.closest('.edit-material-btn') && id) {
    const item = await db.materiais.get(id);
    if (item) openMaterialModal(item);
    return;
  }

  if (e.target.closest('.delete-material-btn') && id) {
    if (!confirm('Excluir este material?')) return;
    await db.materiais.delete(id);
    showToast({ message: 'Material excluído.' });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }

  if (e.target.closest('.stock-dec-btn') && id) {
    const item  = await db.materiais.get(id);
    if (!item) return;
    const passo = parseFloat(e.target.closest('.stock-dec-btn').dataset.passo) || 1;
    const novo  = Math.max(0, Math.round((Number(item.estoqueAtual||0) - passo) * 100) / 100);
    await db.materiais.update(id, { estoqueAtual: novo });
    showToast({ message: `${item.nome}: ${novo} ${unidadeMaterial(item)}` });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }

  if (e.target.closest('.stock-inc-btn') && id) {
    const item  = await db.materiais.get(id);
    if (!item) return;
    const passo = parseFloat(e.target.closest('.stock-inc-btn').dataset.passo) || 1;
    const novo  = Math.round((Number(item.estoqueAtual||0) + passo) * 100) / 100;
    await db.materiais.update(id, { estoqueAtual: novo });
    showToast({ message: `${item.nome}: ${novo} ${unidadeMaterial(item)}` });
    setTimeout(() => navigate('materiais'), 250);
    return;
  }
});
