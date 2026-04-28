<?php
/**
 * pages/promocoes.php
 * Promoções em massa — PRICE_DISCOUNT via API ML
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

$title = 'Promoções em Massa';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Promoções em Massa</h1>
      <p style="font-size:11px;color:#5E5E5A">Aplique ou remova descontos em múltiplos anúncios de uma vez via API ML</p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="selecionarTodos(true)" class="btn-secondary" style="font-size:11px">Selecionar todos</button>
      <button onclick="selecionarTodos(false)" class="btn-secondary" style="font-size:11px">Desmarcar todos</button>
    </div>
  </div>

  <?php if (!$acctId): ?>
  <div style="background:rgba(245,158,11,.06);border:0.5px solid rgba(245,158,11,.3);border-radius:12px;padding:32px;text-align:center">
    <i data-lucide="alert-triangle" style="width:28px;height:28px;color:#f59e0b;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;color:#E8E8E6;margin-bottom:6px">Nenhuma conta ML selecionada</div>
    <p style="font-size:12px;color:#5E5E5A">Selecione uma conta ML no seletor no topo da página</p>
  </div>
  <?php else: ?>

  <div class="promo-grid" style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

    <!-- Esquerda: lista de produtos -->
    <div>
      <!-- Busca + filtros -->
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <div style="position:relative;flex:1">
          <i data-lucide="search" style="width:13px;height:13px;color:#5E5E5A;position:absolute;left:10px;top:50%;transform:translateY(-50%)"></i>
          <input type="text" id="busca-prod" placeholder="Buscar produto..."
            oninput="filtrarProdutos(this.value)"
            style="width:100%;padding:8px 12px 8px 32px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>
        <select id="filtro-promo" onchange="filtrarPorPromo(this.value)"
          style="padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
          <option value="">Todos</option>
          <option value="com">Com promoção ativa</option>
          <option value="sem">Sem promoção</option>
        </select>
      </div>

      <!-- Contador de seleção -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <span id="sel-info" style="font-size:11px;color:#5E5E5A">Carregando produtos...</span>
        <span id="promo-info" style="font-size:11px;color:#5E5E5A"></span>
      </div>

      <!-- Loading -->
      <div id="prod-loading" style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A">
        <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
        Carregando produtos e verificando promoções...
      </div>

      <div id="prod-lista" style="display:none;flex-direction:column;gap:6px"></div>
    </div>

    <!-- Direita: painel de ação -->
    <div class="promo-painel" style="position:sticky;top:80px;display:flex;flex-direction:column;gap:14px">

      <!-- Card: Aplicar desconto -->
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
          <div style="width:28px;height:28px;border-radius:7px;background:rgba(34,197,94,.1);display:flex;align-items:center;justify-content:center">
            <i data-lucide="tag" style="width:13px;height:13px;color:#22c55e"></i>
          </div>
          <span style="font-size:13px;font-weight:600;color:#E8E8E6">Aplicar desconto</span>
        </div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:12px">
          <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:8px;padding:10px 12px;font-size:11px;color:#9A9A96;line-height:1.6">
            Aplica <strong style="color:#E8E8E6">PRICE_DISCOUNT</strong> via API ML. O produto aparece com preço riscado e badge de desconto. Mínimo 5% de desconto.
          </div>

          <!-- Tipo de desconto -->
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px">Tipo de desconto</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
              <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;background:#252528;border:0.5px solid #3483FA;border-radius:8px;cursor:pointer" id="tipo-pct-label">
                <input type="radio" name="tipo-desc" value="percentual" checked onchange="setTipo('percentual')" style="accent-color:#3483FA">
                <span style="font-size:12px;color:#E8E8E6">% Percentual</span>
              </label>
              <label style="display:flex;align-items:center;gap:7px;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;cursor:pointer" id="tipo-fix-label">
                <input type="radio" name="tipo-desc" value="fixo" onchange="setTipo('fixo')" style="accent-color:#3483FA">
                <span style="font-size:12px;color:#E8E8E6">R$ Fixo</span>
              </label>
            </div>
          </div>

          <!-- Valor -->
          <div id="campo-pct">
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Desconto (%)</label>
            <div style="display:flex;align-items:center;gap:6px">
              <input type="range" id="slider-desc" min="5" max="80" value="10" step="1"
                oninput="document.getElementById('inp-desc-pct').value=this.value;atualizarPreview()"
                style="flex:1;accent-color:#22c55e">
              <input type="number" id="inp-desc-pct" min="5" max="80" value="10"
                oninput="document.getElementById('slider-desc').value=this.value;atualizarPreview()"
                style="width:52px;padding:5px 8px;background:#252528;border:0.5px solid #2E2E33;border-radius:6px;color:#E8E8E6;font-size:13px;font-weight:700;text-align:center;outline:none">
              <span style="font-size:13px;color:#5E5E5A">%</span>
            </div>
          </div>

          <div id="campo-fix" style="display:none">
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Novo preço fixo (R$)</label>
            <input type="number" id="inp-desc-fix" min="1" step="0.01" placeholder="0,00" oninput="atualizarPreview()"
              style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;box-sizing:border-box">
          </div>

          <!-- Preview -->
          <div id="preview-desconto" style="display:none;background:#252528;border-radius:8px;padding:10px 12px">
            <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Preview (média dos selecionados)</div>
            <div style="display:flex;align-items:center;gap:8px">
              <span id="prev-orig" style="font-size:12px;color:#5E5E5A;text-decoration:line-through"></span>
              <span id="prev-novo" style="font-size:16px;font-weight:700;color:#22c55e"></span>
              <span id="prev-badge" style="font-size:10px;padding:2px 6px;background:rgba(34,197,94,.15);color:#22c55e;border-radius:5px;font-weight:700"></span>
            </div>
          </div>

          <button onclick="aplicarDesconto()" id="btn-aplicar"
            style="padding:10px;background:#22c55e;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:opacity .15s">
            <i data-lucide="zap" style="width:14px;height:14px"></i>
            Aplicar em <span id="btn-count">0</span> produto(s)
          </button>
        </div>
      </div>

      <!-- Card: Remover promoção -->
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
          <div style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.1);display:flex;align-items:center;justify-content:center">
            <i data-lucide="x-circle" style="width:13px;height:13px;color:#ef4444"></i>
          </div>
          <span style="font-size:13px;font-weight:600;color:#E8E8E6">Remover promoção</span>
        </div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:10px">
          <p style="font-size:11px;color:#9A9A96;line-height:1.6;margin:0">
            Remove o desconto dos produtos selecionados, restaurando o preço original no ML.
          </p>
          <button onclick="removerPromocao()" id="btn-remover"
            style="padding:10px;background:rgba(239,68,68,.1);border:0.5px solid rgba(239,68,68,.3);color:#ef4444;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .15s"
            onmouseover="this.style.background='rgba(239,68,68,.2)'" onmouseout="this.style.background='rgba(239,68,68,.1)'">
            <i data-lucide="x-circle" style="width:14px;height:14px"></i>
            Remover de <span id="btn-rem-count">0</span> produto(s)
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- Log de execução -->
  <div id="log-section" style="display:none;margin-top:20px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#E8E8E6">
        <i data-lucide="activity" style="width:13px;height:13px;color:#3483FA"></i> Log de execução
      </div>
      <div id="log-summary" style="font-size:11px;color:#5E5E5A"></div>
    </div>
    <div id="log-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:5px;max-height:320px;overflow-y:auto"></div>
  </div>

  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  carregarProdutos();
});

let todosProdutos = [];
let selecionados  = new Set();
let tipoDesc      = 'percentual';

// ── Carregar produtos ─────────────────────────────────────
async function carregarProdutos() {
  const r = await fetch('/api/promocoes.php?action=listar_produtos');
  const d = await r.json();

  document.getElementById('prod-loading').style.display = 'none';
  const lista = document.getElementById('prod-lista');
  lista.style.display = 'flex';

  if (!d.ok || !d.produtos?.length) {
    lista.innerHTML = `<div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A">
      <i data-lucide="package" style="width:28px;height:28px;margin:0 auto 10px;display:block"></i>
      Nenhum produto ativo encontrado
    </div>`;
    lucide.createIcons();
    return;
  }

  todosProdutos = d.produtos;
  const comPromo = d.produtos.filter(p => p.promocoes?.length > 0).length;
  document.getElementById('promo-info').textContent =
    `${comPromo} com promoção ativa · ${d.total - comPromo} sem promoção`;

  renderProdutos(todosProdutos);
}

function renderProdutos(lista) {
  const el = document.getElementById('prod-lista');
  el.innerHTML = lista.map(p => {
    const temPromo = p.promocoes?.length > 0;
    const promo    = p.promocoes?.[0];
    const promoBadge = temPromo
      ? `<span style="font-size:9px;padding:2px 7px;border-radius:5px;background:rgba(34,197,94,.15);color:#22c55e;font-weight:700;white-space:nowrap">
           🏷️ ${promo.discount?.toFixed(0) ?? '?'}% OFF ativo
         </span>`
      : `<span style="font-size:9px;padding:2px 7px;border-radius:5px;background:#252528;color:#5E5E5A;white-space:nowrap">Sem promoção</span>`;

    return `
    <div class="prod-card" data-id="${p.id}" data-price="${p.price}" data-has-promo="${temPromo?'1':'0'}"
      data-title="${p.title.toLowerCase()}"
      onclick="toggleCard('${p.id}', ${p.price})"
      style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;cursor:pointer;transition:all .15s"
      onmouseover="this.style.background='#1E1E21'" onmouseout="if(!this.dataset.sel)this.style.background='#1A1A1C'">

      <!-- Checkbox -->
      <div class="card-check" style="width:20px;height:20px;border-radius:5px;border:1.5px solid #2E2E33;background:transparent;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .15s"></div>

      <!-- Info -->
      <div style="flex:1;min-width:0">
        <div style="font-size:12px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.title}</div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:3px;flex-wrap:wrap">
          <span style="font-size:13px;font-weight:700;color:${temPromo?'#22c55e':'#E8E8E6'}">
            R$ ${parseFloat(p.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}
          </span>
          ${temPromo ? `<span style="font-size:10px;color:#5E5E5A;text-decoration:line-through">R$ ${parseFloat(promo.original||p.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>` : ''}
          ${promoBadge}
          <span style="font-size:10px;color:#5E5E5A">${p.stock_quantity} un.</span>
          <span style="font-size:9px;color:#5E5E5A;font-family:monospace">${p.meli_item_id}</span>
        </div>
      </div>
    </div>`;
  }).join('');

  atualizarContadores();
  lucide.createIcons();
}

function toggleCard(id, price) {
  const el = document.querySelector(`.prod-card[data-id="${id}"]`);
  if (!el) return;

  if (selecionados.has(id)) {
    selecionados.delete(id);
    el.dataset.sel = '';
    el.style.borderColor = '#2E2E33';
    el.style.background  = '#1A1A1C';
    el.querySelector('.card-check').style.background   = 'transparent';
    el.querySelector('.card-check').style.borderColor  = '#2E2E33';
    el.querySelector('.card-check').innerHTML          = '';
  } else {
    selecionados.add(id);
    el.dataset.sel = '1';
    el.style.borderColor = '#3483FA';
    el.style.background  = 'rgba(52,131,250,.05)';
    el.querySelector('.card-check').style.background  = '#3483FA';
    el.querySelector('.card-check').style.borderColor = '#3483FA';
    el.querySelector('.card-check').innerHTML         =
      '<svg width="11" height="11" viewBox="0 0 11 11"><polyline points="1.5,5.5 4.5,8.5 9.5,2.5" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }
  atualizarContadores();
}

function selecionarTodos(marcar) {
  document.querySelectorAll('.prod-card').forEach(el => {
    const id    = el.dataset.id;
    const price = parseFloat(el.dataset.price);
    const isSel = selecionados.has(id);
    if (marcar !== isSel) toggleCard(id, price);
  });
}

function atualizarContadores() {
  const n   = selecionados.size;
  const tot = document.querySelectorAll('.prod-card').length;
  document.getElementById('sel-info').textContent      = `${n} de ${tot} selecionado${n!==1?'s':''}`;
  document.getElementById('btn-count').textContent     = n;
  document.getElementById('btn-rem-count').textContent = n;
  atualizarPreview();
}

function filtrarProdutos(q) {
  const termo = q.toLowerCase().trim();
  document.querySelectorAll('.prod-card').forEach(el => {
    el.style.display = (!termo || el.dataset.title.includes(termo)) ? '' : 'none';
  });
}

function filtrarPorPromo(val) {
  document.querySelectorAll('.prod-card').forEach(el => {
    if (!val) { el.style.display = ''; return; }
    const tem = el.dataset.hasPromo === '1';
    el.style.display = (val === 'com' ? tem : !tem) ? '' : 'none';
  });
}

// ── Tipo de desconto ──────────────────────────────────────
function setTipo(tipo) {
  tipoDesc = tipo;
  document.getElementById('campo-pct').style.display = tipo === 'percentual' ? 'block' : 'none';
  document.getElementById('campo-fix').style.display = tipo === 'fixo'       ? 'block' : 'none';
  document.getElementById('tipo-pct-label').style.borderColor = tipo === 'percentual' ? '#3483FA' : '#2E2E33';
  document.getElementById('tipo-fix-label').style.borderColor = tipo === 'fixo'       ? '#3483FA' : '#2E2E33';
  atualizarPreview();
}

function atualizarPreview() {
  if (!selecionados.size) {
    document.getElementById('preview-desconto').style.display = 'none';
    return;
  }
  // Média dos preços selecionados
  const precos = [...selecionados].map(id => {
    const el = document.querySelector(`.prod-card[data-id="${id}"]`);
    return el ? parseFloat(el.dataset.price) : 0;
  }).filter(v => v > 0);
  if (!precos.length) return;

  const mediaPreco = precos.reduce((s,v) => s+v, 0) / precos.length;
  let novoPreco, descPct;

  if (tipoDesc === 'percentual') {
    descPct   = parseFloat(document.getElementById('inp-desc-pct').value) || 0;
    novoPreco = mediaPreco * (1 - descPct/100);
  } else {
    novoPreco = parseFloat(document.getElementById('inp-desc-fix').value) || 0;
    descPct   = novoPreco > 0 ? ((mediaPreco - novoPreco) / mediaPreco) * 100 : 0;
  }

  const fmt = v => 'R$ ' + v.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('preview-desconto').style.display = 'block';
  document.getElementById('prev-orig').textContent  = fmt(mediaPreco);
  document.getElementById('prev-novo').textContent  = fmt(novoPreco);
  document.getElementById('prev-badge').textContent = `-${descPct.toFixed(0)}%`;
}

// ── Aplicar desconto ──────────────────────────────────────
async function aplicarDesconto() {
  if (!selecionados.size) { toast('Selecione ao menos um produto', 'error'); return; }

  const descPct = parseFloat(document.getElementById('inp-desc-pct').value) || 0;
  const descFix = parseFloat(document.getElementById('inp-desc-fix').value) || 0;

  if (tipoDesc === 'percentual' && (descPct < 5 || descPct > 80)) {
    toast('Desconto deve ser entre 5% e 80%', 'error'); return;
  }
  if (tipoDesc === 'fixo' && descFix <= 0) {
    toast('Informe o novo preço', 'error'); return;
  }

  const ok = await dialog({
    title: 'Confirmar promoção',
    message: `Aplicar ${tipoDesc === 'percentual' ? descPct+'% de desconto' : 'preço fixo R$'+descFix.toFixed(2)} em ${selecionados.size} produto(s)?`,
    confirmText: 'Aplicar'
  });
  if (!ok) return;

  const btn = document.getElementById('btn-aplicar');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:14px;height:14px;animation:spin 1s linear infinite"></i> Aplicando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action',       'aplicar');
  fd.append('ids',          JSON.stringify([...selecionados]));
  fd.append('tipo',         tipoDesc);
  fd.append('desconto_pct', descPct);
  fd.append('valor_fixo',   descFix);

  const r = await fetch('/api/promocoes.php', {method:'POST', body:fd});
  const d = await r.json();

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="zap" style="width:14px;height:14px"></i> Aplicar em <span id="btn-count">'+selecionados.size+'</span> produto(s)';
  lucide.createIcons();

  exibirLog(d.resultados, d.ok_count, d.err_count, 'aplicação');
  if (d.ok_count > 0) {
    toast(`✅ ${d.ok_count} promoção(ões) aplicada(s)!`, 'success');
    setTimeout(() => { selecionados.clear(); carregarProdutos(); }, 2000);
  } else {
    toast(`❌ Nenhuma promoção foi aplicada`, 'error');
  }
}

// ── Remover promoção ──────────────────────────────────────
async function removerPromocao() {
  if (!selecionados.size) { toast('Selecione ao menos um produto', 'error'); return; }

  const ok = await dialog({
    title: 'Remover promoções',
    message: `Remover promoções de ${selecionados.size} produto(s)?`,
    confirmText: 'Remover',
    danger: true
  });
  if (!ok) return;

  const btn = document.getElementById('btn-remover');
  btn.disabled = true;

  const fd = new FormData();
  fd.append('action', 'remover');
  fd.append('ids',    JSON.stringify([...selecionados]));
  fd.append('tipo',   'PRICE_DISCOUNT');

  const r = await fetch('/api/promocoes.php', {method:'POST', body:fd});
  const d = await r.json();

  btn.disabled = false;
  exibirLog(d.resultados, d.ok_count, d.err_count, 'remoção');

  if (d.ok_count > 0) {
    toast(`✅ ${d.ok_count} promoção(ões) removida(s)!`, 'success');
    setTimeout(() => { selecionados.clear(); carregarProdutos(); }, 2000);
  } else {
    toast('❌ Nenhuma promoção foi removida', 'error');
  }
}

// ── Log de resultados ─────────────────────────────────────
function exibirLog(resultados, okCount, errCount, tipo) {
  if (!resultados?.length) return;

  document.getElementById('log-section').style.display = 'block';
  document.getElementById('log-summary').textContent   =
    `${okCount} sucesso · ${errCount} erro · ${tipo}`;

  const body = document.getElementById('log-body');
  body.innerHTML = resultados.map(r => `
    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:0.5px solid #2E2E33;font-size:11px">
      <i data-lucide="${r.ok?'check-circle':'x-circle'}"
        style="width:13px;height:13px;color:${r.ok?'#22c55e':'#ef4444'};flex-shrink:0"></i>
      <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:${r.ok?'#E8E8E6':'#9A9A96'}">${r.title}</span>
      ${r.ok && r.preco_promo ? `
        <span style="color:#5E5E5A;text-decoration:line-through;flex-shrink:0">R$${parseFloat(r.preco_orig).toFixed(2).replace('.',',')}</span>
        <span style="color:#22c55e;font-weight:700;flex-shrink:0">R$${parseFloat(r.preco_promo).toFixed(2).replace('.',',')} (-${r.desconto}%)</span>
      ` : ''}
      ${!r.ok ? `<span style="color:#ef4444;flex-shrink:0;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.error||'Erro'}</span>` : ''}
      <a href="https://www.mercadolivre.com.br/item/${r.item_id}" target="_blank"
        style="color:#3483FA;font-size:10px;text-decoration:none;flex-shrink:0">${r.item_id}</a>
    </div>`).join('');

  lucide.createIcons();
  body.scrollTop = body.scrollHeight;
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
