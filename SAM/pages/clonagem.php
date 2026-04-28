<?php
/**
 * pages/clonagem.php
 * Clonagem de anúncios entre contas ML
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// Buscar todas as contas ativas
$contas = db_all(
    "SELECT id, nickname, email, meli_user_id, is_active
     FROM meli_accounts WHERE tenant_id=? AND is_active=1
     ORDER BY nickname ASC",
    [$tenantId]
);

$title = 'Clonagem de Anúncios';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <div style="margin-bottom:20px">
    <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Clonagem de Anúncios</h1>
    <p style="font-size:11px;color:#5E5E5A">Copie anúncios de uma conta ML para outra com um clique</p>
  </div>

  <?php if (count($contas) < 2): ?>
  <div style="background:rgba(245,158,11,.06);border:0.5px solid rgba(245,158,11,.3);border-radius:12px;padding:32px;text-align:center">
    <i data-lucide="alert-triangle" style="width:28px;height:28px;color:#f59e0b;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:6px">Você precisa de pelo menos 2 contas ML conectadas</div>
    <p style="font-size:12px;color:#5E5E5A;margin-bottom:16px">Conecte uma segunda conta em Configurações → Integração ML</p>
    <a href="/pages/config_ml.php" class="btn-primary" style="text-decoration:none;font-size:12px">Conectar conta</a>
  </div>
  <?php else: ?>

  <!-- Seletor de contas -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:20px;margin-bottom:20px">
    <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:16px;align-items:end">

      <!-- Conta origem -->
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:6px;font-weight:500">
          <i data-lucide="log-out" style="width:11px;height:11px;color:#3483FA;vertical-align:middle"></i> Conta origem
        </label>
        <select id="conta-origem" onchange="carregarAnuncios()" class="input" style="font-size:13px">
          <option value="">Selecione a conta de origem</option>
          <?php foreach ($contas as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nickname']) ?> — <?= htmlspecialchars($c['email']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Seta -->
      <div style="display:flex;align-items:center;justify-content:center;padding-bottom:2px">
        <div style="width:36px;height:36px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);border-radius:50%;display:flex;align-items:center;justify-content:center">
          <i data-lucide="arrow-right" style="width:16px;height:16px;color:#3483FA"></i>
        </div>
      </div>

      <!-- Conta destino -->
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:6px;font-weight:500">
          <i data-lucide="log-in" style="width:11px;height:11px;color:#22c55e;vertical-align:middle"></i> Conta destino
        </label>
        <select id="conta-destino" class="input" style="font-size:13px">
          <option value="">Selecione a conta de destino</option>
          <?php foreach ($contas as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nickname']) ?> — <?= htmlspecialchars($c['email']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Opções de clonagem -->
    <div style="margin-top:16px;padding-top:16px;border-top:0.5px solid #2E2E33;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;color:#9A9A96">
        <input type="checkbox" id="opt-manter-preco" checked style="accent-color:#3483FA">
        Manter preço original
      </label>
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;color:#9A9A96">
        <input type="checkbox" id="opt-manter-estoque" checked style="accent-color:#3483FA">
        Manter estoque
      </label>
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;color:#9A9A96">
        <input type="checkbox" id="opt-pausar" style="accent-color:#3483FA">
        Publicar pausado (ativar manualmente depois)
      </label>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <span id="sel-count" style="font-size:11px;color:#5E5E5A">0 selecionados</span>
        <button onclick="clonarSelecionados()" id="btn-clonar" disabled
          style="padding:8px 18px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;opacity:.5;transition:opacity .15s">
          <i data-lucide="copy" style="width:13px;height:13px"></i> Clonar selecionados
        </button>
      </div>
    </div>
  </div>

  <!-- Busca e filtros -->
  <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
    <div style="position:relative;flex:1;min-width:200px">
      <i data-lucide="search" style="width:13px;height:13px;color:#5E5E5A;position:absolute;left:10px;top:50%;transform:translateY(-50%)"></i>
      <input type="text" id="busca-anuncio" placeholder="Buscar anúncio por título ou SKU..."
        oninput="filtrarAnuncios(this.value)"
        style="width:100%;padding:8px 12px 8px 32px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
    </div>
    <button onclick="selecionarTodos(true)" class="btn-secondary" style="font-size:11px;padding:7px 12px">Selecionar todos</button>
    <button onclick="selecionarTodos(false)" class="btn-secondary" style="font-size:11px;padding:7px 12px">Desmarcar todos</button>
  </div>

  <!-- Lista de anúncios -->
  <div id="anuncios-loading" style="display:none;text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A;font-size:13px">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    Buscando anúncios da conta...
  </div>

  <div id="anuncios-empty" style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="package" style="width:32px;height:32px;color:#2E2E33;margin:0 auto 12px;display:block"></i>
    <div style="font-size:13px;color:#5E5E5A">Selecione a conta de origem para ver os anúncios</div>
  </div>

  <div id="anuncios-grid" style="display:none;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px"></div>

  <!-- Log de clonagem -->
  <div id="clone-log" style="display:none;margin-top:20px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;font-size:12px;font-weight:500;color:#E8E8E6;display:flex;align-items:center;gap:6px">
      <i data-lucide="activity" style="width:13px;height:13px;color:#3483FA"></i> Log de clonagem
    </div>
    <div id="clone-log-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto"></div>
  </div>

  <?php endif; ?>
</div>

<script>
lucide.createIcons();

let todosAnuncios  = [];
let anunciosFiltrados = [];

// ── Carregar anúncios da conta origem ────────────────────
async function carregarAnuncios() {
  const contaId = document.getElementById('conta-origem').value;
  if (!contaId) return;

  document.getElementById('anuncios-empty').style.display   = 'none';
  document.getElementById('anuncios-grid').style.display    = 'none';
  document.getElementById('anuncios-loading').style.display = 'block';

  const r = await fetch(`/api/clonagem.php?action=listar&conta_id=${contaId}`);
  const d = await r.json();

  document.getElementById('anuncios-loading').style.display = 'none';

  if (!d.ok || !d.anuncios?.length) {
    document.getElementById('anuncios-empty').style.display = 'block';
    document.getElementById('anuncios-empty').innerHTML = `
      <i data-lucide="package" style="width:32px;height:32px;color:#2E2E33;margin:0 auto 12px;display:block"></i>
      <div style="font-size:13px;color:#5E5E5A">${d.error || 'Nenhum anúncio encontrado nesta conta'}</div>`;
    lucide.createIcons();
    return;
  }

  todosAnuncios = d.anuncios;
  renderAnuncios(todosAnuncios);
}

function renderAnuncios(lista) {
  const grid = document.getElementById('anuncios-grid');
  grid.style.display = 'grid';
  document.getElementById('anuncios-empty').style.display = 'none';

  grid.innerHTML = lista.map(a => {
    const statusColor = a.status === 'active' ? '#22c55e' : '#5E5E5A';
    const statusLabel = a.status === 'active' ? 'Ativo' : a.status === 'paused' ? 'Pausado' : a.status;
    const thumb = a.thumbnail || '';
    return `
    <div class="anuncio-card" data-id="${a.id}" data-title="${a.title.toLowerCase()}"
      style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;overflow:hidden;transition:border-color .15s;cursor:pointer"
      onclick="toggleCard(this)">
      <div style="display:flex;gap:10px;padding:12px">
        <!-- Thumbnail -->
        <div style="width:52px;height:52px;border-radius:6px;background:#252528;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center">
          ${thumb
            ? `<img src="${thumb}" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none'">`
            : `<i data-lucide="package" style="width:20px;height:20px;color:#2E2E33"></i>`}
        </div>
        <!-- Info -->
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:3px">${a.title}</div>
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
            <span style="font-size:9px;padding:1px 6px;border-radius:5px;background:${statusColor}15;color:${statusColor};font-weight:600">${statusLabel}</span>
            ${a.sku ? `<span style="font-size:9px;color:#5E5E5A;font-family:monospace">${a.sku}</span>` : ''}
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-size:13px;font-weight:700;color:#E8E8E6">R$ ${parseFloat(a.price).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
            <span style="font-size:10px;color:#5E5E5A">${a.available_quantity} em estoque</span>
          </div>
        </div>
        <!-- Checkbox -->
        <div style="flex-shrink:0;display:flex;align-items:flex-start;padding-top:2px">
          <div class="card-check" style="width:18px;height:18px;border-radius:5px;border:1.5px solid #2E2E33;background:transparent;transition:all .15s;display:flex;align-items:center;justify-content:center">
          </div>
        </div>
      </div>
    </div>`;
  }).join('');

  atualizarContador();
  lucide.createIcons();
}

function toggleCard(el) {
  const check = el.querySelector('.card-check');
  const selecionado = el.dataset.selecionado === '1';
  if (selecionado) {
    el.dataset.selecionado = '0';
    el.style.borderColor = '#2E2E33';
    check.style.background = 'transparent';
    check.style.borderColor = '#2E2E33';
    check.innerHTML = '';
  } else {
    el.dataset.selecionado = '1';
    el.style.borderColor = '#3483FA';
    check.style.background = '#3483FA';
    check.style.borderColor = '#3483FA';
    check.innerHTML = '<svg width="10" height="10" viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2.5" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }
  atualizarContador();
}

function atualizarContador() {
  const sel = document.querySelectorAll('.anuncio-card[data-selecionado="1"]').length;
  document.getElementById('sel-count').textContent = `${sel} selecionado${sel!==1?'s':''}`;
  const btn = document.getElementById('btn-clonar');
  btn.disabled = sel === 0;
  btn.style.opacity = sel > 0 ? '1' : '.5';
  btn.style.cursor  = sel > 0 ? 'pointer' : 'default';
}

function selecionarTodos(marcar) {
  document.querySelectorAll('.anuncio-card').forEach(card => {
    if (marcar !== (card.dataset.selecionado === '1')) toggleCard(card);
  });
}

function filtrarAnuncios(q) {
  const termo = q.toLowerCase().trim();
  document.querySelectorAll('.anuncio-card').forEach(card => {
    const titulo = card.dataset.title || '';
    card.style.display = (!termo || titulo.includes(termo)) ? '' : 'none';
  });
}

// ── Clonar ───────────────────────────────────────────────
async function clonarSelecionados() {
  const origem  = document.getElementById('conta-origem').value;
  const destino = document.getElementById('conta-destino').value;

  if (!origem || !destino) { toast('Selecione conta origem e destino', 'error'); return; }
  if (origem === destino)  { toast('Origem e destino devem ser contas diferentes', 'error'); return; }

  const selecionados = [...document.querySelectorAll('.anuncio-card[data-selecionado="1"]')]
    .map(el => el.dataset.id);

  if (!selecionados.length) { toast('Selecione ao menos um anúncio', 'error'); return; }

  const opManterPreco   = document.getElementById('opt-manter-preco').checked;
  const opManterEstoque = document.getElementById('opt-manter-estoque').checked;
  const opPausar        = document.getElementById('opt-pausar').checked;

  const btn = document.getElementById('btn-clonar');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:13px;height:13px;animation:spin 1s linear infinite"></i> Clonando...';
  lucide.createIcons();

  // Mostrar log
  const logEl = document.getElementById('clone-log');
  const logBody = document.getElementById('clone-log-body');
  logEl.style.display = 'block';
  logBody.innerHTML = '';

  let ok = 0, erros = 0;

  for (const itemId of selecionados) {
    const anuncio = todosAnuncios.find(a => a.id === itemId);
    const nomeAnuncio = anuncio?.title?.substring(0,50) || itemId;

    logBody.innerHTML += `<div id="log-${itemId}" style="display:flex;align-items:center;gap:8px;font-size:11px;color:#5E5E5A;padding:5px 0;border-bottom:0.5px solid #2E2E33">
      <i data-lucide="loader-2" style="width:11px;height:11px;animation:spin 1s linear infinite;flex-shrink:0;color:#3483FA"></i>
      <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${nomeAnuncio}</span>
      <span style="flex-shrink:0;color:#5E5E5A">Clonando...</span>
    </div>`;
    logBody.scrollTop = logBody.scrollHeight;
    lucide.createIcons();

    const fd = new FormData();
    fd.append('action',         'clonar');
    fd.append('item_id',        itemId);
    fd.append('conta_origem',   origem);
    fd.append('conta_destino',  destino);
    fd.append('manter_preco',   opManterPreco   ? '1' : '0');
    fd.append('manter_estoque', opManterEstoque ? '1' : '0');
    fd.append('pausar',         opPausar        ? '1' : '0');

    try {
      const r = await fetch('/api/clonagem.php', {method:'POST', body:fd});
      const d = await r.json();
      const logRow = document.getElementById(`log-${itemId}`);

      if (d.ok) {
        ok++;
        logRow.innerHTML = `
          <i data-lucide="check-circle" style="width:11px;height:11px;color:#22c55e;flex-shrink:0"></i>
          <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#E8E8E6">${nomeAnuncio}</span>
          <a href="https://www.mercadolivre.com.br/item/${d.new_item_id}" target="_blank"
            style="font-size:10px;color:#3483FA;text-decoration:none;flex-shrink:0">${d.new_item_id}</a>`;
      } else {
        erros++;
        logRow.innerHTML = `
          <i data-lucide="x-circle" style="width:11px;height:11px;color:#ef4444;flex-shrink:0"></i>
          <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9A9A96">${nomeAnuncio}</span>
          <span style="flex-shrink:0;color:#ef4444;font-size:10px">${d.error?.substring(0,40)||'Erro'}</span>`;
      }
    } catch(e) {
      erros++;
    }

    lucide.createIcons();
    // Pequena pausa para não sobrecarregar a API ML
    await new Promise(res => setTimeout(res, 600));
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="copy" style="width:13px;height:13px"></i> Clonar selecionados';
  lucide.createIcons();

  if (ok > 0 && erros === 0) toast(`✅ ${ok} anúncio${ok>1?'s':''} clonado${ok>1?'s':''}!`, 'success');
  else if (ok > 0) toast(`✅ ${ok} clonado${ok>1?'s':''} · ❌ ${erros} com erro`, 'warning');
  else toast(`❌ Todos os ${erros} falharam — verifique o log`, 'error');
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
