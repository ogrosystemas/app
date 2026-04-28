<?php
/**
 * pages/autoparts.php
 * Módulo AutoParts — compatibilidade de peças por veículo
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];

$title = 'AutoParts';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
        <div style="width:32px;height:32px;border-radius:8px;background:rgba(52,131,250,.15);display:flex;align-items:center;justify-content:center">
          <i data-lucide="car" style="width:16px;height:16px;color:#3483FA"></i>
        </div>
        <h1 style="font-size:16px;font-weight:600;color:#E8E8E6">AutoParts</h1>
      </div>
      <p style="font-size:11px;color:#5E5E5A">Gerencie compatibilidade de peças por marca, modelo e ano do veículo</p>
    </div>
    <!-- Abas de modo -->
    <div style="display:flex;gap:4px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;padding:3px">
      <button onclick="setMode('catalog')" id="tab-catalog"
        style="padding:6px 14px;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;background:#3483FA;color:#fff">
        <i data-lucide="list" style="width:12px;height:12px;vertical-align:middle;margin-right:4px"></i>Catálogo
      </button>
      <button onclick="setMode('search')" id="tab-search"
        style="padding:6px 14px;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;transition:all .15s;background:transparent;color:#5E5E5A">
        <i data-lucide="search" style="width:12px;height:12px;vertical-align:middle;margin-right:4px"></i>Buscar por veículo
      </button>
    </div>
  </div>

  <!-- ── MODO: CATÁLOGO ── -->
  <div id="panel-catalog">
    <!-- Busca -->
    <div style="display:flex;gap:8px;margin-bottom:16px">
      <div style="position:relative;flex:1">
        <i data-lucide="search" style="width:13px;height:13px;color:#5E5E5A;position:absolute;left:10px;top:50%;transform:translateY(-50%)"></i>
        <input type="text" id="cat-search" placeholder="Buscar por título, código OEM ou part number..."
          oninput="debounce(loadCatalog, 400)()"
          style="width:100%;padding:9px 12px 9px 32px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
      </div>
    </div>

    <!-- Lista -->
    <div id="catalog-loading" style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
      Carregando catálogo...
    </div>
    <div id="catalog-content"></div>
  </div>

  <!-- ── MODO: BUSCA POR VEÍCULO ── -->
  <div id="panel-search" style="display:none">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:20px;margin-bottom:16px">
      <div style="font-size:11px;font-weight:600;color:#9A9A96;margin-bottom:14px;display:flex;align-items:center;gap:5px">
        <i data-lucide="car" style="width:12px;height:12px"></i> Buscar peças compatíveis com o veículo
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end">
        <div>
          <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Tipo</label>
          <select id="sv-tipo" class="input" onchange="loadBrands()">
            <option value="carros">Carros</option>
            <option value="motos">Motos</option>
            <option value="caminhoes">Caminhões</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Marca</label>
          <select id="sv-brand" class="input" onchange="loadModels('sv-brand','sv-model')">
            <option value="">Selecione...</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Modelo</label>
<datalist id="sv-model-list"></datalist>
          <input type="text" id="sv-model" list="sv-model-list" class="input"
            placeholder="Digite ou selecione o modelo..." autocomplete="off">
        </div>
        <button onclick="buscarPorVeiculo()" class="btn-primary" style="font-size:12px;padding:9px 18px;white-space:nowrap">
          <i data-lucide="search" style="width:12px;height:12px"></i> Buscar
        </button>
      </div>
    </div>

    <div id="sv-results"></div>
  </div>
</div>

<!-- ═══════════════════════════════════════
     Modal: Ficha da Peça
═══════════════════════════════════════ -->
<div id="modal-ap" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:flex-start;justify-content:center;z-index:500;padding:20px;overflow-y:auto;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:860px;margin:0 auto;box-shadow:0 24px 80px rgba(0,0,0,.6);overflow:hidden">

    <!-- Header -->
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px;background:#1E1E21;position:sticky;top:0;z-index:10">
      <i data-lucide="wrench" style="width:15px;height:15px;color:#3483FA"></i>
      <span id="ap-modal-title" style="font-size:14px;font-weight:600;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1"></span>
      <button onclick="closeApModal()" style="background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px;flex-shrink:0">✕</button>
    </div>

    <div class="ap-modal-grid" style="display:grid;grid-template-columns:1fr 1fr;min-height:500px">

      <!-- Esquerda: dados da peça -->
      <div style="padding:20px;border-right:0.5px solid #2E2E33;display:flex;flex-direction:column;gap:14px">
        <input type="hidden" id="ap-product-id">
        <input type="hidden" id="ap-id">

        <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em">
          Identificação da peça
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Código OEM / Original</label>
            <input type="text" id="ap-oem" class="input" placeholder="Ex: 1K0615301E">
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Part Number</label>
            <input type="text" id="ap-part" class="input" placeholder="Ex: 96626341">
          </div>
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Marca da peça</label>
          <input type="text" id="ap-brand" class="input" placeholder="Ex: Monroe, Cofap, Bosch, Delphi...">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Posição</label>
            <select id="ap-position" class="input">
              <option value="">Não aplicável</option>
              <option value="dianteiro">Dianteiro</option>
              <option value="traseiro">Traseiro</option>
              <option value="dianteiro_e_traseiro">Dianteiro e Traseiro</option>
              <option value="superior">Superior</option>
              <option value="inferior">Inferior</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Lado</label>
            <select id="ap-side" class="input">
              <option value="">Não aplicável</option>
              <option value="esquerdo">Esquerdo</option>
              <option value="direito">Direito</option>
              <option value="ambos">Ambos</option>
            </select>
          </div>
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Condição</label>
          <select id="ap-condition" class="input">
            <option value="novo">Novo</option>
            <option value="remontado">Remontado / Reman</option>
            <option value="original_usado">Original Usado</option>
          </select>
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Observações técnicas</label>
          <textarea id="ap-notes" class="input" style="height:70px;resize:none"
            placeholder="Informações adicionais sobre a peça, aplicação especial, etc."></textarea>
        </div>

        <button onclick="saveAutopart()" class="btn-primary" style="font-size:12px">
          <i data-lucide="save" style="width:12px;height:12px"></i> Salvar ficha da peça
        </button>
      </div>

      <!-- Direita: compatibilidade -->
      <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

        <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;justify-content:space-between">
          <span>Veículos compatíveis</span>
          <span id="compat-count" style="font-size:10px;color:#3483FA;font-weight:600;text-transform:none"></span>
        </div>

        <!-- Formulário adicionar compatibilidade -->
        <div id="compat-form" style="display:none;background:#252528;border-radius:10px;padding:14px;flex-direction:column;gap:10px">
          <div style="font-size:11px;font-weight:500;color:#E8E8E6;margin-bottom:2px;display:flex;align-items:center;gap:6px">
            <i data-lucide="plus-circle" style="width:12px;height:12px;color:#3483FA"></i> Adicionar veículo compatível
          </div>

          <div>
            <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">Tipo de veículo</label>
            <select id="cp-tipo" class="input" onchange="loadBrands('cp-tipo','cp-brand','cp-model')">
              <option value="carros">Carros</option>
              <option value="motos">Motos</option>
              <option value="caminhoes">Caminhões</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">
              Marca
              <span id="cp-brand-loading" style="display:none;color:#3483FA;font-size:9px">⟳ Carregando...</span>
            </label>
            <select id="cp-brand" class="input" onchange="loadModels('cp-brand','cp-model')">
              <option value="">Clique em "Atualizar marcas"</option>
            </select>
          </div>

          <div>
            <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">
              Modelo
              <span id="cp-model-loading" style="display:none;color:#3483FA;font-size:9px">⟳ Carregando...</span>
            </label>
<datalist id="cp-model-list"></datalist>
            <input type="text" id="cp-model" list="cp-model-list" class="input"
              placeholder="Digite ou selecione o modelo..." autocomplete="off">
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div>
              <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">Ano inicial</label>
              <input type="number" id="cp-year-from" class="input" placeholder="2010" min="1950" max="2030">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">Ano final</label>
              <input type="number" id="cp-year-to" class="input" placeholder="2024" min="1950" max="2030">
            </div>
          </div>

          <div>
            <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:4px">Motor (opcional)</label>
            <input type="text" id="cp-engine" class="input" placeholder="Ex: 1.6 Flex, 2.0 Turbo, 1.8i...">
          </div>

          <div style="display:flex;gap:8px">
            <button onclick="fetchBrands()" style="flex:1;padding:7px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:7px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all .15s"
              onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
              <i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Atualizar marcas
            </button>
            <button onclick="addCompat()" style="flex:1;padding:7px;background:#22c55e;border:none;color:#fff;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px">
              <i data-lucide="plus" style="width:11px;height:11px"></i> Adicionar
            </button>
          </div>
        </div>

        <button onclick="toggleCompatForm()" id="btn-show-compat"
          style="padding:8px;background:#252528;border:1.5px dashed #2E2E33;color:#5E5E5A;border-radius:8px;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s"
          onmouseover="this.style.borderColor='#3483FA';this.style.color='#3483FA'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
          <i data-lucide="plus" style="width:12px;height:12px"></i> Adicionar veículo compatível
        </button>

        <!-- Lista de compatibilidades -->
        <div id="compat-list" style="display:flex;flex-direction:column;gap:6px;max-height:340px;overflow-y:auto">
          <div id="compat-empty" style="text-align:center;padding:24px;color:#5E5E5A;font-size:11px;border:0.5px solid #2E2E33;border-radius:8px">
            Nenhum veículo cadastrado ainda
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  loadCatalog();
});

let currentMode   = 'catalog';
let currentApId   = null;
let brandsCache   = {};
let compatFormOpen = false;

// ── Debounce ──────────────────────────────────────────────
function debounce(fn, delay) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

// ── Alternância de modos ──────────────────────────────────
function setMode(mode) {
  currentMode = mode;
  document.getElementById('panel-catalog').style.display = mode === 'catalog' ? 'block' : 'none';
  document.getElementById('panel-search').style.display  = mode === 'search'  ? 'block' : 'none';
  document.getElementById('tab-catalog').style.background = mode === 'catalog' ? '#3483FA' : 'transparent';
  document.getElementById('tab-catalog').style.color      = mode === 'catalog' ? '#fff'    : '#5E5E5A';
  document.getElementById('tab-search').style.background  = mode === 'search'  ? '#3483FA' : 'transparent';
  document.getElementById('tab-search').style.color       = mode === 'search'  ? '#fff'    : '#5E5E5A';
  if (mode === 'search' && !document.getElementById('sv-brand').options.length > 1) loadBrands();
}

// ── Catálogo ──────────────────────────────────────────────
async function loadCatalog(page=1) {
  const q = document.getElementById('cat-search').value.trim();
  document.getElementById('catalog-loading').style.display = 'block';
  document.getElementById('catalog-content').innerHTML = '';

  const r = await fetch(`/api/autoparts.php?action=list&q=${encodeURIComponent(q)}&page=${page}`);
  const d = await r.json();

  document.getElementById('catalog-loading').style.display = 'none';

  if (!d.ok || !d.parts?.length) {
    document.getElementById('catalog-content').innerHTML = `
      <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
        <i data-lucide="package" style="width:32px;height:32px;color:#2E2E33;margin:0 auto 12px;display:block"></i>
        <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:6px">Nenhuma peça encontrada</div>
        <p style="font-size:11px;color:#5E5E5A">Clique em qualquer produto nos Anúncios para cadastrar a ficha AutoParts</p>
      </div>`;
    lucide.createIcons();
    return;
  }

  const html = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Produto</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">OEM / Part Number</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Posição / Lado</th>
            <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Compatíveis</th>
            <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">ML</th>
            <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Ações</th>
          </tr>
        </thead>
        <tbody>
          ${d.parts.map(p => {
            const posLabel = {
              dianteiro:'Dianteiro',traseiro:'Traseiro',
              dianteiro_e_traseiro:'Diant. e Tras.',superior:'Superior',inferior:'Inferior'
            }[p.position] || '';
            const sideLabel = {esquerdo:'Esq.',direito:'Dir.',ambos:'Ambos'}[p.side] || '';
            const mlColor = p.ml_status === 'ACTIVE' ? '#22c55e' : '#5E5E5A';
            const compatBadge = p.total_compat > 0
              ? `<span style="background:rgba(52,131,250,.15);color:#3483FA;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600">${p.total_compat} veículo${p.total_compat>1?'s':''}</span>`
              : `<span style="color:#5E5E5A;font-size:10px">—</span>`;

            return `<tr style="border-bottom:0.5px solid #2E2E33;transition:background .12s"
              onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
              <td style="padding:10px 14px">
                <div style="font-size:11px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px">${p.title}</div>
                ${p.sku ? `<div style="font-size:10px;color:#5E5E5A;font-family:monospace">${p.sku}</div>` : ''}
                ${p.ap_brand ? `<div style="font-size:10px;color:#9A9A96">${p.ap_brand}</div>` : ''}
              </td>
              <td style="padding:10px 14px">
                ${p.oem_code ? `<div style="font-size:11px;font-family:monospace;color:#FFE600">${p.oem_code}</div>` : ''}
                ${p.part_number ? `<div style="font-size:10px;color:#5E5E5A;font-family:monospace">${p.part_number}</div>` : ''}
                ${!p.oem_code && !p.part_number ? '<span style="color:#3E3E45;font-size:10px">Não cadastrado</span>' : ''}
              </td>
              <td style="padding:10px 14px">
                ${posLabel ? `<div style="font-size:11px;color:#E8E8E6">${posLabel}</div>` : ''}
                ${sideLabel ? `<div style="font-size:10px;color:#5E5E5A">${sideLabel}</div>` : ''}
                ${!posLabel && !sideLabel ? '<span style="color:#3E3E45;font-size:10px">—</span>' : ''}
              </td>
              <td style="padding:10px 14px;text-align:center">${compatBadge}</td>
              <td style="padding:10px 14px;text-align:center">
                <span style="font-size:9px;padding:2px 6px;border-radius:5px;background:${mlColor}15;color:${mlColor};font-weight:600">
                  ${p.ml_status === 'ACTIVE' ? '● Ativo' : p.ml_status || '—'}
                </span>
              </td>
              <td style="padding:10px 14px;text-align:center">
                <button onclick="openApModal('${p.id}')"
                  style="padding:5px 12px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;font-size:10px;cursor:pointer;white-space:nowrap;transition:all .15s"
                  onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
                  <i data-lucide="wrench" style="width:10px;height:10px"></i> Ficha
                </button>
              </td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>
    </div>`;

  document.getElementById('catalog-content').innerHTML = html;
  lucide.createIcons();
}

// ── Modal ficha da peça ───────────────────────────────────
async function openApModal(productId) {
  document.getElementById('modal-ap').style.display = 'flex';
  document.getElementById('ap-modal-title').textContent = 'Carregando...';
  document.getElementById('ap-product-id').value = productId;
  document.getElementById('ap-id').value = '';
  document.getElementById('compat-list').innerHTML = `<div style="text-align:center;padding:16px;color:#5E5E5A;font-size:11px">Carregando...</div>`;

  const r = await fetch(`/api/autoparts.php?action=get&product_id=${productId}`);
  const d = await r.json();
  if (!d.ok) { toast('Erro ao carregar', 'error'); closeApModal(); return; }

  document.getElementById('ap-modal-title').textContent = d.product.title;

  // Preencher campos
  const ap = d.autopart || {};
  document.getElementById('ap-id').value        = ap.id || '';
  document.getElementById('ap-oem').value        = ap.oem_code || '';
  document.getElementById('ap-part').value       = ap.part_number || '';
  document.getElementById('ap-brand').value      = ap.brand || '';
  document.getElementById('ap-position').value   = ap.position || '';
  document.getElementById('ap-side').value       = ap.side || '';
  document.getElementById('ap-condition').value  = ap.condition_part || 'novo';
  document.getElementById('ap-notes').value      = ap.notes || '';
  currentApId = ap.id || null;

  renderCompat(d.compatibility || []);
  lucide.createIcons();
}

function closeApModal() {
  document.getElementById('modal-ap').style.display = 'none';
  currentApId = null;
  loadCatalog();
}

function renderCompat(list) {
  const el    = document.getElementById('compat-list');
  const count = document.getElementById('compat-count');
  count.textContent = list.length ? `${list.length} veículo${list.length>1?'s':''}` : '';

  if (!list.length) {
    el.innerHTML = `<div id="compat-empty" style="text-align:center;padding:24px;color:#5E5E5A;font-size:11px;border:0.5px solid #2E2E33;border-radius:8px">
      Nenhum veículo cadastrado ainda
    </div>`;
    return;
  }

  el.innerHTML = list.map(c => `
    <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 12px;background:#252528;border-radius:8px;border:0.5px solid #2E2E33">
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:600;color:#E8E8E6">${c.brand} ${c.model}</div>
        <div style="font-size:10px;color:#3483FA;margin-top:2px">${c.year_from} – ${c.year_to}${c.engine?' · '+c.engine:''}</div>
      </div>
      <button onclick="removeCompat('${c.id}',this)"
        style="background:none;border:none;color:#5E5E5A;cursor:pointer;padding:2px;flex-shrink:0;transition:color .15s"
        onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#5E5E5A'">
        <i data-lucide="x" style="width:13px;height:13px"></i>
      </button>
    </div>`).join('');

  lucide.createIcons();
}

async function saveAutopart() {
  const productId = document.getElementById('ap-product-id').value;
  const fd = new FormData();
  fd.append('action',         'save');
  fd.append('product_id',     productId);
  fd.append('oem_code',       document.getElementById('ap-oem').value);
  fd.append('part_number',    document.getElementById('ap-part').value);
  fd.append('brand',          document.getElementById('ap-brand').value);
  fd.append('position',       document.getElementById('ap-position').value);
  fd.append('side',           document.getElementById('ap-side').value);
  fd.append('condition_part', document.getElementById('ap-condition').value);
  fd.append('notes',          document.getElementById('ap-notes').value);

  const r = await fetch('/api/autoparts.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    currentApId = d.id;
    document.getElementById('ap-id').value = d.id;
    document.getElementById('compat-form').style.display = 'flex';
    document.getElementById('btn-show-compat').style.display = 'none';
    toast('Ficha salva!', 'success');
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}

// ── Compatibilidade ───────────────────────────────────────
function toggleCompatForm() {
  compatFormOpen = !compatFormOpen;
  document.getElementById('compat-form').style.display = compatFormOpen ? 'flex' : 'none';
  document.getElementById('btn-show-compat').style.display = compatFormOpen ? 'none' : 'flex';
  if (compatFormOpen && !currentApId) {
    toast('Salve a ficha da peça primeiro', 'info');
    compatFormOpen = false;
    document.getElementById('compat-form').style.display = 'none';
    document.getElementById('btn-show-compat').style.display = 'flex';
    return;
  }
  if (compatFormOpen) fetchBrands();
}

async function fetchBrands() {
  const tipo  = document.getElementById('cp-tipo').value;
  const loadEl = document.getElementById('cp-brand-loading');
  loadEl.style.display = 'inline';

  const r = await fetch(`/api/autoparts.php?action=brands&tipo=${tipo}`);
  const d = await r.json();
  loadEl.style.display = 'none';

  if (!d.ok) { toast('Erro ao buscar marcas', 'error'); return; }

  const sel = document.getElementById('cp-brand');
  sel.innerHTML = '<option value="">Selecione a marca...</option>' +
    d.brands.map(b => `<option value="${b.code}" data-name="${b.name}">${b.name}</option>`).join('');
  const cpModel = document.getElementById('cp-model');
  if (cpModel) { cpModel.value = ''; }
  const cpList = document.getElementById('cp-model-list');
  if (cpList) { cpList.innerHTML = ''; }
  toast(`${d.brands.length} marcas carregadas!`, 'success');
}

async function loadBrands(tipoId='cp-tipo', brandId='cp-brand', modelId='cp-model') {
  const tipo = document.getElementById(tipoId)?.value || 'carros';
  const r = await fetch(`/api/autoparts.php?action=brands&tipo=${tipo}`);
  const d = await r.json();
  if (!d.ok) return;

  const sel = document.getElementById(brandId);
  sel.innerHTML = '<option value="">Selecione a marca...</option>' +
    d.brands.map(b => `<option value="${b.code}" data-name="${b.name}">${b.name}</option>`).join('');
  const modelEl = document.getElementById(modelId);
  if (modelEl) { modelEl.value = ''; }
  const modelList = document.getElementById(modelId + '-list');
  if (modelList) { modelList.innerHTML = ''; }
}

async function loadModels(brandId='cp-brand', modelId='cp-model') {
  const brandSel  = document.getElementById(brandId);
  const brandCode = brandSel.value;
  if (!brandCode) return;

  const tipoId = brandId === 'cp-brand' ? 'cp-tipo' : 'sv-tipo';
  const tipo   = document.getElementById(tipoId)?.value || 'carros';

  const loadId = brandId === 'cp-brand' ? 'cp-model-loading' : null;
  if (loadId) document.getElementById(loadId).style.display = 'inline';

  const inputEl   = document.getElementById(modelId);
  const datalistEl = document.getElementById(modelId + '-list');

  const r = await fetch(`/api/autoparts.php?action=models&tipo=${tipo}&brand=${brandCode}`);
  const d = await r.json();

  if (loadId) document.getElementById(loadId).style.display = 'none';

  // Limpa o input e o datalist
  inputEl.value = '';
  if (datalistEl) {
    if (d.ok && d.models?.length) {
      datalistEl.innerHTML = d.models.map(m => `<option value="${m.name}">`).join('');
    } else {
      datalistEl.innerHTML = '';
    }
  }
  inputEl.placeholder = d.ok && d.models?.length
    ? 'Digite ou selecione o modelo...'
    : 'Digite o modelo manualmente';
}

async function addCompat() {
  if (!currentApId) { toast('Salve a ficha da peça primeiro', 'info'); return; }

  const brandSel = document.getElementById('cp-brand');
  const brand    = brandSel.options[brandSel.selectedIndex]?.dataset?.name
                || brandSel.options[brandSel.selectedIndex]?.text || '';
  const model    = document.getElementById('cp-model').value.trim();
  const yearFrom = document.getElementById('cp-year-from').value;
  const yearTo   = document.getElementById('cp-year-to').value;
  const engine   = document.getElementById('cp-engine').value;

  if (!brand || !model || !yearFrom || !yearTo) {
    toast('Preencha marca, modelo e anos', 'error'); return;
  }

  const fd = new FormData();
  fd.append('action',      'save_compat');
  fd.append('autopart_id', currentApId);
  fd.append('brand',       brand);
  fd.append('model',       model);
  fd.append('year_from',   yearFrom);
  fd.append('year_to',     yearTo);
  fd.append('engine',      engine);

  const r = await fetch('/api/autoparts.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    toast('Compatibilidade adicionada!', 'success');
    // Recarrega a ficha
    const productId = document.getElementById('ap-product-id').value;
    const r2 = await fetch(`/api/autoparts.php?action=get&product_id=${productId}`);
    const d2 = await r2.json();
    if (d2.ok) renderCompat(d2.compatibility || []);
    // Limpa campos
    document.getElementById('cp-year-from').value = '';
    document.getElementById('cp-year-to').value   = '';
    document.getElementById('cp-engine').value     = '';
  } else {
    toast(d.error || 'Erro ao adicionar', 'error');
  }
}

async function removeCompat(id, btn) {
  const row = btn.closest('div[style]');
  const fd  = new FormData();
  fd.append('action', 'del_compat');
  fd.append('id',     id);
  const r = await fetch('/api/autoparts.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    row.remove();
    const list  = document.getElementById('compat-list');
    const count = document.getElementById('compat-count');
    const items = list.querySelectorAll('div[style]').length;
    count.textContent = items ? `${items} veículo${items>1?'s':''}` : '';
    if (!items) {
      list.innerHTML = `<div id="compat-empty" style="text-align:center;padding:24px;color:#5E5E5A;font-size:11px;border:0.5px solid #2E2E33;border-radius:8px">Nenhum veículo cadastrado ainda</div>`;
    }
    toast('Removido', 'info');
  }
}

// ── Busca por veículo ─────────────────────────────────────
async function buscarPorVeiculo() {
  const brandSel = document.getElementById('sv-brand');
  const brand    = brandSel.options[brandSel.selectedIndex]?.text || '';
  const model    = document.getElementById('sv-model').value.trim();

  if (!brand || brand === 'Selecione...') { toast('Selecione a marca', 'error'); return; }

  const r = await fetch(`/api/autoparts.php?action=search_vehicle&brand=${encodeURIComponent(brand)}&model=${encodeURIComponent(model)}`);
  const d = await r.json();

  const el = document.getElementById('sv-results');
  if (!d.ok || !d.parts?.length) {
    el.innerHTML = `<div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A">
      <i data-lucide="search-x" style="width:28px;height:28px;margin:0 auto 12px;display:block"></i>
      Nenhuma peça encontrada para este veículo
    </div>`;
    lucide.createIcons();
    return;
  }

  el.innerHTML = `
    <div style="margin-bottom:10px;font-size:12px;color:#9A9A96">${d.total} peça${d.total>1?'s':''} encontrada${d.total>1?'s':''} para <strong style="color:#E8E8E6">${brand} ${model}</strong></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px">
      ${d.parts.map(p => `
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:14px;transition:box-shadow .15s"
          onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.3)'" onmouseout="this.style.boxShadow=''">
          <div style="font-size:12px;font-weight:600;color:#E8E8E6;margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.title}</div>
          ${p.oem_code ? `<div style="font-size:10px;font-family:monospace;color:#FFE600;margin-bottom:4px">OEM: ${p.oem_code}</div>` : ''}
          ${p.part_number ? `<div style="font-size:10px;font-family:monospace;color:#5E5E5A;margin-bottom:4px">PN: ${p.part_number}</div>` : ''}
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:0.5px solid #2E2E33">
            <span style="font-size:13px;font-weight:700;color:#E8E8E6">R$ ${parseFloat(p.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
            <span style="font-size:10px;color:${p.stock_quantity>0?'#22c55e':'#ef4444'}">${p.stock_quantity} un.</span>
          </div>
          ${p.meli_item_id ? `<a href="https://www.mercadolivre.com.br/item/${p.meli_item_id}" target="_blank"
            style="display:flex;align-items:center;gap:4px;margin-top:8px;font-size:10px;color:#3483FA;text-decoration:none">
            <i data-lucide="external-link" style="width:10px;height:10px"></i> Ver no ML
          </a>` : ''}
        </div>`).join('')}
    </div>`;

  lucide.createIcons();
}

document.getElementById('modal-ap').addEventListener('click', function(e) {
  if (e.target === this) closeApModal();
});
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
