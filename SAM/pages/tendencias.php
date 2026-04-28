<?php
/**
 * pages/tendencias.php
 * Tendências de mercado via API ML
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$title = 'Tendências de Mercado';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- Header -->
  <div style="margin-bottom:20px">
    <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Tendências de Mercado</h1>
    <p style="font-size:11px;color:#5E5E5A">Descubra o que está em alta no ML e analise oportunidades de nicho</p>
  </div>

  <div class="tend-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <!-- Coluna esquerda: Termos em alta -->
    <div style="display:flex;flex-direction:column;gap:14px">

      <!-- Seletor de categoria -->
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px">
        <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;display:flex;align-items:center;gap:5px">
          <i data-lucide="trending-up" style="width:12px;height:12px;color:#3483FA"></i> Termos em alta no ML
        </div>
        <div style="display:flex;gap:8px;margin-bottom:12px">
          <select id="cat-select" class="input" style="flex:1;font-size:12px" onchange="carregarTrends()">
            <option value="">Todas as categorias</option>
          </select>
          <button onclick="carregarTrends()" class="btn-secondary" style="font-size:11px;padding:7px 12px;white-space:nowrap">
            <i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Atualizar
          </button>
        </div>
        <div id="trends-loading" style="text-align:center;padding:32px;color:#5E5E5A;font-size:12px">
          <i data-lucide="loader-2" style="width:18px;height:18px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
          Buscando tendências...
        </div>
        <div id="trends-list" style="display:none"></div>
      </div>
    </div>

    <!-- Coluna direita: Análise de nicho -->
    <div style="display:flex;flex-direction:column;gap:14px">

      <!-- Buscador de nicho -->
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px">
        <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;display:flex;align-items:center;gap:5px">
          <i data-lucide="search" style="width:12px;height:12px;color:#a855f7"></i> Analisar nicho / produto
        </div>
        <div style="display:flex;gap:8px">
          <input type="text" id="inp-nicho" placeholder="Ex: amortecedor dianteiro, vela de ignição..."
            onkeydown="if(event.key==='Enter') analisarNicho()"
            style="flex:1;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
          <button onclick="analisarNicho()" id="btn-analisar"
            style="padding:9px 18px;background:#a855f7;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px">
            <i data-lucide="search" style="width:12px;height:12px"></i> Analisar
          </button>
        </div>
        <p style="font-size:10px;color:#5E5E5A;margin-top:8px">
          Clique em qualquer termo em alta para analisar automaticamente
        </p>
      </div>

      <!-- Resultado da análise -->
      <div id="nicho-empty" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:48px;text-align:center">
        <i data-lucide="bar-chart-2" style="width:32px;height:32px;color:#2E2E33;margin:0 auto 12px;display:block"></i>
        <div style="font-size:13px;color:#5E5E5A">Digite um produto ou clique em um termo em alta</div>
      </div>

      <div id="nicho-loading" style="display:none;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:48px;text-align:center">
        <i data-lucide="loader-2" style="width:24px;height:24px;animation:spin 1s linear infinite;color:#a855f7;margin:0 auto 12px;display:block"></i>
        <div style="font-size:13px;color:#5E5E5A">Analisando mercado...</div>
        <div style="font-size:11px;color:#3E3E45;margin-top:6px">Buscando anúncios, preços e concorrência</div>
      </div>

      <div id="nicho-result" style="display:none"></div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  carregarCategorias();
  carregarTrends();
});

// ── Carregar categorias ───────────────────────────────────
async function carregarCategorias() {
  const r = await fetch('/api/tendencias.php?action=categorias');
  const d = await r.json();
  if (!d.ok || !d.categorias?.length) return;

  const sel = document.getElementById('cat-select');
  d.categorias.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.name;
    sel.appendChild(opt);
  });
}

// ── Carregar tendências ───────────────────────────────────
async function carregarTrends() {
  const catId = document.getElementById('cat-select').value;
  const loading = document.getElementById('trends-loading');
  const lista   = document.getElementById('trends-list');

  loading.style.display = 'block';
  lista.style.display   = 'none';

  const url = `/api/tendencias.php?action=trends${catId ? '&category_id='+catId : ''}`;
  const r   = await fetch(url);
  const d   = await r.json();

  loading.style.display = 'none';

  if (!d.ok || !d.trends?.length) {
    lista.style.display = 'block';
    lista.innerHTML = `<div style="text-align:center;padding:24px;color:#5E5E5A;font-size:12px">
      ${d.error || 'Nenhuma tendência encontrada'}
    </div>`;
    return;
  }

  lista.style.display = 'block';
  lista.innerHTML = `
    <div style="font-size:10px;color:#5E5E5A;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
      <span>${d.trends.length} termos em alta${d.cached ? ' · <span style="color:#3483FA">cache</span>' : ''}</span>
      <span style="color:#3483FA">Atualizado semanalmente pelo ML</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:4px">
      ${d.trends.map((t, i) => `
        <div onclick="analisarTermo('${t.keyword}')"
          style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:#252528;border-radius:8px;cursor:pointer;transition:all .15s;border:0.5px solid transparent"
          onmouseover="this.style.background='#2A2A2E';this.style.borderColor='#3483FA20'"
          onmouseout="this.style.background='#252528';this.style.borderColor='transparent'">
          <div style="width:24px;height:24px;border-radius:6px;background:${getRankColor(i)};display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0">
            ${i+1}
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;color:#E8E8E6;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${t.keyword}</div>
          </div>
          <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
            <a href="${t.url}" target="_blank" onclick="event.stopPropagation()"
              style="font-size:10px;color:#3483FA;text-decoration:none;padding:3px 7px;background:rgba(52,131,250,.1);border-radius:5px;white-space:nowrap"
              title="Ver no ML">
              <i data-lucide="external-link" style="width:9px;height:9px"></i>
            </a>
            <i data-lucide="search" style="width:11px;height:11px;color:#5E5E5A"></i>
          </div>
        </div>`).join('')}
    </div>`;

  lucide.createIcons();
}

function getRankColor(i) {
  if (i === 0) return '#f59e0b';
  if (i === 1) return '#9A9A96';
  if (i === 2) return '#b45309';
  if (i < 10)  return '#3483FA';
  return '#2E2E33';
}

// ── Analisar termo / nicho ────────────────────────────────
function analisarTermo(keyword) {
  document.getElementById('inp-nicho').value = keyword;
  analisarNicho();
}

async function analisarNicho() {
  const q = document.getElementById('inp-nicho').value.trim();
  if (!q) { toast('Digite um produto para analisar', 'error'); return; }

  document.getElementById('nicho-empty').style.display  = 'none';
  document.getElementById('nicho-result').style.display = 'none';
  document.getElementById('nicho-loading').style.display = 'block';

  const btn = document.getElementById('btn-analisar');
  btn.disabled = true;

  const r = await fetch(`/api/tendencias.php?action=analisar&q=${encodeURIComponent(q)}`);
  const d = await r.json();

  document.getElementById('nicho-loading').style.display = 'none';
  btn.disabled = false;

  if (!d.ok) {
    document.getElementById('nicho-empty').style.display = 'block';
    document.getElementById('nicho-empty').innerHTML = `
      <i data-lucide="search-x" style="width:32px;height:32px;color:#2E2E33;margin:0 auto 12px;display:block"></i>
      <div style="font-size:13px;color:#5E5E5A">${d.error}</div>`;
    lucide.createIcons();
    return;
  }

  const fmt = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  const el  = document.getElementById('nicho-result');
  el.style.display = 'flex';
  el.style.flexDirection = 'column';
  el.style.gap = '12px';

  el.innerHTML = `
    <!-- Header do resultado -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
        <div>
          <div style="font-size:14px;font-weight:600;color:#E8E8E6">"${d.query}"</div>
          <div style="font-size:11px;color:#5E5E5A;margin-top:2px">
            ${d.total_anuncios.toLocaleString('pt-BR')} anúncios encontrados
            ${d.cached ? '· <span style="color:#3483FA">cache</span>' : ''}
          </div>
        </div>
        <!-- Índice de oportunidade -->
        <div style="text-align:center;background:#252528;border-radius:10px;padding:12px 20px">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Índice de oportunidade</div>
          <div style="font-size:28px;font-weight:700;color:${d.oportunidade_color}">${d.oportunidade}</div>
          <div style="font-size:11px;font-weight:600;color:${d.oportunidade_color}">${d.oportunidade_label}</div>
        </div>
      </div>

      <!-- Métricas em grid -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Preço mínimo</div>
          <div style="font-size:14px;font-weight:700;color:#22c55e">${fmt(d.preco_min)}</div>
        </div>
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Ticket médio</div>
          <div style="font-size:14px;font-weight:700;color:#3483FA">${fmt(d.preco_medio)}</div>
        </div>
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Preço máximo</div>
          <div style="font-size:14px;font-weight:700;color:#f59e0b">${fmt(d.preco_max)}</div>
        </div>
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Vendedores únicos</div>
          <div style="font-size:14px;font-weight:700;color:#E8E8E6">${d.vendedores_unicos}</div>
        </div>
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Frete grátis</div>
          <div style="font-size:14px;font-weight:700;color:${d.frete_gratis_pct > 50 ? '#22c55e' : '#f59e0b'}">${d.frete_gratis_pct}%</div>
        </div>
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px">Total de anúncios</div>
          <div style="font-size:14px;font-weight:700;color:#a855f7">${d.total_anuncios.toLocaleString('pt-BR')}</div>
        </div>
      </div>

      <!-- Barra de oportunidade -->
      <div style="margin-top:14px">
        <div style="display:flex;justify-content:space-between;font-size:10px;color:#5E5E5A;margin-bottom:4px">
          <span>Saturado</span>
          <span>Oportunidade no mercado</span>
          <span>Alta oportunidade</span>
        </div>
        <div style="height:8px;background:#252528;border-radius:4px;overflow:hidden">
          <div style="height:100%;width:${d.oportunidade}%;background:${d.oportunidade_color};border-radius:4px;transition:width .8s ease"></div>
        </div>
      </div>
    </div>

    <!-- Top produtos -->
    ${d.top_produtos?.length ? `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;font-size:12px;font-weight:500;color:#E8E8E6;display:flex;align-items:center;gap:6px">
        <i data-lucide="trophy" style="width:13px;height:13px;color:#f59e0b"></i>
        Top produtos por vendas
      </div>
      <div style="display:flex;flex-direction:column">
        ${d.top_produtos.map((p, i) => `
          <a href="${p.permalink}" target="_blank" style="text-decoration:none"
            onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
            <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:0.5px solid #2E2E33;transition:background .12s">
              <div style="width:22px;height:22px;border-radius:6px;background:${getRankColor(i)};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">${i+1}</div>
              ${p.thumbnail ? `<img src="${p.thumbnail}" style="width:36px;height:36px;border-radius:6px;object-fit:cover;flex-shrink:0" onerror="this.style.display='none'">` : ''}
              <div style="flex:1;min-width:0">
                <div style="font-size:11px;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.title}</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:2px">
                  <span style="font-size:12px;font-weight:700;color:#22c55e">${fmt(p.price)}</span>
                  ${p.sold_quantity > 0 ? `<span style="font-size:10px;color:#5E5E5A">${p.sold_quantity} vendidos</span>` : ''}
                  ${p.free_shipping ? `<span style="font-size:9px;color:#22c55e;background:rgba(34,197,94,.1);padding:1px 5px;border-radius:4px">Frete grátis</span>` : ''}
                </div>
              </div>
              <i data-lucide="external-link" style="width:11px;height:11px;color:#5E5E5A;flex-shrink:0"></i>
            </div>
          </a>`).join('')}
      </div>
    </div>` : ''}`;

  lucide.createIcons();
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
