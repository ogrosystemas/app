<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

$tab = in_array($_GET['tab']??'', ['respostas','mensagens','precos']) ? $_GET['tab'] : 'respostas';

$title = 'Ferramentas';
include __DIR__ . '/layout.php';
?>

<!-- ═══ ABA: RESPOSTAS PRONTAS ═══ -->
<?php if ($tab === 'respostas'): ?>
<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Banco de Respostas Prontas</h1>
      <p style="font-size:11px;color:#5E5E5A">Respostas frequentes para perguntas pré-venda — use no módulo Perguntas</p>
    </div>
    <button onclick="openReplyModal()" class="btn-primary">
      <i data-lucide="plus" style="width:12px;height:12px"></i> Nova resposta
    </button>
  </div>

  <!-- Busca -->
  <div style="position:relative;margin-bottom:16px">
    <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:#5E5E5A"></i>
    <input type="text" id="reply-search" placeholder="Buscar respostas..." oninput="loadReplies()"
      style="width:100%;padding:9px 12px 9px 36px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
  </div>

  <div id="replies-list">
    <div style="text-align:center;padding:48px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
      Carregando...
    </div>
  </div>
</div>

<!-- Modal de resposta -->
<div id="reply-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto">
    <div style="font-size:14px;font-weight:600;color:#E8E8E6;margin-bottom:16px" id="reply-modal-title">Nova resposta pronta</div>
    <input type="hidden" id="reply-id">
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Título (uso interno)</label>
      <input type="text" id="reply-title" placeholder="Ex: Prazo de entrega" class="input">
    </div>
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Resposta</label>
      <textarea id="reply-body" placeholder="Escreva a resposta que o comprador vai ver..."
        style="width:100%;height:120px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;box-sizing:border-box;line-height:1.5"></textarea>
    </div>
    <div style="margin-bottom:16px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Tags (opcional, separadas por vírgula)</label>
      <input type="text" id="reply-tags" placeholder="Ex: frete, prazo, garantia" class="input">
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="saveReply()" class="btn-primary" style="flex:1">Salvar</button>
      <button onclick="document.getElementById('reply-modal').style.display='none'" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

async function loadReplies() {
  const q   = document.getElementById('reply-search').value;
  const r   = await fetch(`/api/sprint1.php?action=list_replies&q=${encodeURIComponent(q)}`);
  const d   = await r.json();
  const el  = document.getElementById('replies-list');

  if (!d.replies?.length) {
    el.innerHTML = `<div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
      <i data-lucide="book-open" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
      <div style="font-size:14px;color:#E8E8E6;margin-bottom:4px">Nenhuma resposta cadastrada</div>
      <div style="font-size:11px;color:#5E5E5A">Crie respostas prontas para agilizar o atendimento</div>
    </div>`;
    lucide.createIcons();
    return;
  }

  el.innerHTML = `<div style="display:flex;flex-direction:column;gap:8px">` + d.replies.map(r => `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:14px 16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px">
        <div style="flex:1">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:3px">${r.title}</div>
          ${r.tags ? `<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:6px">${r.tags.split(',').map(t=>`<span style="font-size:9px;padding:1px 7px;background:rgba(52,131,250,.1);color:#3483FA;border-radius:8px">${t.trim()}</span>`).join('')}</div>` : ''}
          <div style="font-size:11px;color:#9A9A96;line-height:1.5">${r.body}</div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <span style="font-size:9px;color:#5E5E5A">${r.uso}x usado</span>
          <button onclick="editReply(${JSON.stringify(r).replace(/"/g,'&quot;')})" style="padding:4px 8px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;cursor:pointer">Editar</button>
          <button onclick="deleteReply('${r.id}')" style="padding:4px 8px;background:transparent;border:0.5px solid rgba(239,68,68,.3);color:#ef4444;border-radius:6px;font-size:10px;cursor:pointer">Excluir</button>
        </div>
      </div>
    </div>`).join('') + `</div>`;
  lucide.createIcons();
}

function openReplyModal(reply = null) {
  document.getElementById('reply-id').value    = reply?.id    || '';
  document.getElementById('reply-title').value = reply?.title || '';
  document.getElementById('reply-body').value  = reply?.body  || '';
  document.getElementById('reply-tags').value  = reply?.tags  || '';
  document.getElementById('reply-modal-title').textContent = reply ? 'Editar resposta' : 'Nova resposta pronta';
  document.getElementById('reply-modal').style.display = 'flex';
}

function editReply(r) { openReplyModal(r); }

async function saveReply() {
  const fd = new FormData();
  fd.append('action', 'save_reply');
  fd.append('id',     document.getElementById('reply-id').value);
  fd.append('title',  document.getElementById('reply-title').value.trim());
  fd.append('body',   document.getElementById('reply-body').value.trim());
  fd.append('tags',   document.getElementById('reply-tags').value.trim());
  const r = await fetch('/api/sprint1.php', {method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) {
    toast('Resposta salva!', 'success');
    document.getElementById('reply-modal').style.display = 'none';
    loadReplies();
  } else toast(d.error||'Erro ao salvar','error');
}

async function deleteReply(id) {
  if (!await dialog({title:'Excluir Resposta',message:'Deseja excluir esta resposta pronta?',confirmText:'Excluir',danger:true})) return;
  const fd = new FormData(); fd.append('action','delete_reply'); fd.append('id',id);
  const r = await fetch('/api/sprint1.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Excluída','info'); loadReplies(); }
}

loadReplies();
</script>

<?php elseif ($tab === 'mensagens'): ?>
<!-- ═══ ABA: MENSAGENS AUTOMÁTICAS ═══ -->
<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Mensagens Automáticas</h1>
      <p style="font-size:11px;color:#5E5E5A">Enviadas automaticamente ao comprador após eventos de venda</p>
    </div>
    <button onclick="openMsgModal()" class="btn-primary">
      <i data-lucide="plus" style="width:12px;height:12px"></i> Nova mensagem
    </button>
  </div>

  <!-- Variáveis disponíveis -->
  <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:11px;color:#9A9A96;line-height:1.8">
    <strong style="color:#3483FA">Variáveis disponíveis na mensagem:</strong><br>
    <code style="color:#E8E8E6">{comprador}</code> · <code style="color:#E8E8E6">{primeiro_nome}</code> · <code style="color:#E8E8E6">{produto}</code> · <code style="color:#E8E8E6">{pedido}</code> · <code style="color:#E8E8E6">{valor}</code>
  </div>

  <div id="msgs-list">
    <div style="text-align:center;padding:48px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    </div>
  </div>
</div>

<!-- Modal mensagem -->
<div id="msg-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto">
    <div style="font-size:14px;font-weight:600;color:#E8E8E6;margin-bottom:16px" id="msg-modal-title">Nova mensagem automática</div>
    <input type="hidden" id="msg-id">
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Evento</label>
      <select id="msg-event" style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
        <option value="payment_approved">✅ Pagamento aprovado</option>
        <option value="shipped">🚚 Pedido enviado</option>
        <option value="delivered">📦 Pedido entregue</option>
      </select>
    </div>
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Título (uso interno)</label>
      <input type="text" id="msg-title" placeholder="Ex: Confirmação de pagamento" class="input">
    </div>
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Mensagem</label>
      <textarea id="msg-body" placeholder="Olá {primeiro_nome}! Seu pedido {pedido} foi confirmado..."
        style="width:100%;height:140px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;box-sizing:border-box;line-height:1.5"></textarea>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="saveMsg()" class="btn-primary" style="flex:1">Salvar</button>
      <button onclick="document.getElementById('msg-modal').style.display='none'" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

const EVENT_LABELS = {
  payment_approved: '✅ Pagamento aprovado',
  shipped:          '🚚 Pedido enviado',
  delivered:        '📦 Pedido entregue',
};

async function loadMsgs() {
  const r = await fetch('/api/sprint1.php?action=list_auto_messages');
  const d = await r.json();
  const el = document.getElementById('msgs-list');

  if (!d.messages?.length) {
    el.innerHTML = `<div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
      <i data-lucide="send" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
      <div style="font-size:14px;color:#E8E8E6;margin-bottom:4px">Nenhuma mensagem automática</div>
      <div style="font-size:11px;color:#5E5E5A">Crie mensagens que serão enviadas automaticamente após eventos de venda</div>
    </div>`;
    lucide.createIcons();
    return;
  }

  el.innerHTML = `<div style="display:flex;flex-direction:column;gap:8px">` + d.messages.map(m => `
    <div style="background:#1A1A1C;border:0.5px solid ${m.is_active?'#2E2E33':'rgba(94,94,90,.3)'};border-radius:12px;padding:14px 16px;opacity:${m.is_active?'1':'0.6'}">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px">
        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <span style="font-size:13px;font-weight:500;color:#E8E8E6">${m.title}</span>
            <span style="font-size:9px;padding:1px 7px;border-radius:8px;background:rgba(52,131,250,.1);color:#3483FA">${EVENT_LABELS[m.trigger_event]||m.trigger_event}</span>
          </div>
          <div style="font-size:11px;color:#9A9A96;line-height:1.5;margin-bottom:6px">${m.body}</div>
          <div style="font-size:10px;color:#5E5E5A">${m.sent_count} envio${m.sent_count!==1?'s':''}</div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
          <button onclick="toggleMsg('${m.id}',this)" style="padding:4px 10px;background:${m.is_active?'rgba(34,197,94,.1)':'rgba(94,94,90,.1)'};border:0.5px solid ${m.is_active?'#22c55e':'#5E5E5A'};color:${m.is_active?'#22c55e':'#5E5E5A'};border-radius:6px;font-size:10px;cursor:pointer">
            ${m.is_active?'Ativa':'Inativa'}
          </button>
          <button onclick="editMsg(${JSON.stringify(m).replace(/"/g,'&quot;')})" style="padding:4px 8px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;cursor:pointer">Editar</button>
          <button onclick="deleteMsg('${m.id}')" style="padding:4px 8px;background:transparent;border:0.5px solid rgba(239,68,68,.3);color:#ef4444;border-radius:6px;font-size:10px;cursor:pointer">×</button>
        </div>
      </div>
    </div>`).join('') + `</div>`;
  lucide.createIcons();
}

function openMsgModal(msg = null) {
  document.getElementById('msg-id').value      = msg?.id            || '';
  document.getElementById('msg-title').value   = msg?.title         || '';
  document.getElementById('msg-body').value    = msg?.body          || '';
  document.getElementById('msg-event').value   = msg?.trigger_event || 'payment_approved';
  document.getElementById('msg-modal-title').textContent = msg ? 'Editar mensagem' : 'Nova mensagem automática';
  document.getElementById('msg-modal').style.display = 'flex';
}

function editMsg(m) { openMsgModal(m); }

async function saveMsg() {
  const fd = new FormData();
  fd.append('action',        'save_auto_message');
  fd.append('id',            document.getElementById('msg-id').value);
  fd.append('title',         document.getElementById('msg-title').value.trim());
  fd.append('body',          document.getElementById('msg-body').value.trim());
  fd.append('trigger_event', document.getElementById('msg-event').value);
  fd.append('is_active',     '1');
  const r = await fetch('/api/sprint1.php', {method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Mensagem salva!','success'); document.getElementById('msg-modal').style.display='none'; loadMsgs(); }
  else toast(d.error||'Erro ao salvar','error');
}

async function toggleMsg(id, btn) {
  const fd = new FormData(); fd.append('action','toggle_auto_message'); fd.append('id',id);
  const r = await fetch('/api/sprint1.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast(d.is_active?'Ativada':'Desativada', d.is_active?'success':'info'); loadMsgs(); }
}

async function deleteMsg(id) {
  if (!await dialog({title:'Excluir Mensagem',message:'Deseja excluir esta mensagem automática?',confirmText:'Excluir',danger:true})) return;
  const fd = new FormData(); fd.append('action','delete_auto_message'); fd.append('id',id);
  const r = await fetch('/api/sprint1.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Excluída','info'); loadMsgs(); }
}

loadMsgs();
</script>

<?php else: ?>
<!-- ═══ ABA: PREÇOS EM MASSA ═══ -->
<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Preços em Massa</h1>
      <p style="font-size:11px;color:#5E5E5A">Atualize o preço de vários anúncios de uma vez</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button onclick="selectAll(true)" class="btn-secondary" style="font-size:11px;padding:6px 12px">Selecionar todos</button>
      <button onclick="selectAll(false)" class="btn-secondary" style="font-size:11px;padding:6px 12px">Desmarcar todos</button>
    </div>
  </div>

  <!-- Ajuste em massa -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:16px">
    <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:12px;display:flex;align-items:center;gap:6px">
      <i data-lucide="sliders" style="width:13px;height:13px;color:#3483FA"></i>
      Ajuste em massa nos selecionados
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <select id="bulk-op" style="padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px">
        <option value="increase">Aumentar</option>
        <option value="decrease">Diminuir</option>
      </select>
      <input type="number" id="bulk-value" placeholder="Valor" min="0.01" step="0.01"
        style="width:100px;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
      <select id="bulk-type" style="padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px">
        <option value="percent">%</option>
        <option value="fixed">R$</option>
      </select>
      <button onclick="applyBulk()" style="padding:8px 16px;background:#f59e0b;border:none;color:#1A1A1A;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i data-lucide="zap" style="width:11px;height:11px"></i> Aplicar
      </button>
    </div>
  </div>

  <!-- Busca -->
  <div style="position:relative;margin-bottom:12px">
    <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:#5E5E5A"></i>
    <input type="text" id="price-search" placeholder="Buscar anúncios..." oninput="loadProducts()"
      style="width:100%;padding:9px 12px 9px 36px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
  </div>

  <!-- Botão salvar alterações individuais -->
  <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <button onclick="saveIndividualPrices()" class="btn-primary" id="save-prices-btn" style="display:none">
      <i data-lucide="save" style="width:12px;height:12px"></i> Salvar alterações de preço
    </button>
  </div>

  <div id="products-price-list">
    <div style="text-align:center;padding:48px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    </div>
  </div>
</div>

<script>
lucide.createIcons();
let allProducts = [];
let changedPrices = {};

async function loadProducts() {
  const q  = document.getElementById('price-search').value;
  const r  = await fetch(`/api/sprint1.php?action=list_products_price&q=${encodeURIComponent(q)}`);
  const d  = await r.json();
  allProducts = d.products || [];
  renderProducts();
}

function renderProducts() {
  const el = document.getElementById('products-price-list');
  if (!allProducts.length) {
    el.innerHTML = `<div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
      <i data-lucide="tag" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
      <div style="font-size:14px;color:#E8E8E6">Nenhum anúncio encontrado</div>
    </div>`;
    lucide.createIcons();
    return;
  }

  el.innerHTML = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="border-bottom:0.5px solid #2E2E33">
              <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;width:32px">
                <input type="checkbox" id="check-all" onchange="selectAll(this.checked)" style="cursor:pointer">
              </th>
              <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Anúncio</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500;white-space:nowrap">Preço atual</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500;white-space:nowrap">Novo preço</th>
              <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Status</th>
            </tr>
          </thead>
          <tbody id="products-tbody">
            ${allProducts.map(p => `
              <tr style="border-bottom:0.5px solid #2E2E33" id="row-${p.id}">
                <td style="padding:10px 14px">
                  <input type="checkbox" class="prod-check" data-id="${p.id}" onchange="onCheckChange()" style="cursor:pointer">
                </td>
                <td style="padding:10px 14px;color:#E8E8E6;max-width:300px">
                  <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.title}</div>
                  <div style="font-size:10px;color:#5E5E5A;margin-top:2px">${p.meli_item_id} · ${p.stock_quantity} em estoque</div>
                </td>
                <td style="padding:10px 14px;text-align:right;color:#E8E8E6;white-space:nowrap;font-family:monospace">
                  R$ ${parseFloat(p.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}
                </td>
                <td style="padding:10px 14px;text-align:right">
                  <input type="number" class="price-input" data-id="${p.id}" data-original="${p.price}"
                    value="${parseFloat(p.price).toFixed(2)}" min="0.01" step="0.01"
                    style="width:100px;padding:5px 8px;background:#252528;border:0.5px solid #2E2E33;border-radius:6px;color:#E8E8E6;font-size:12px;text-align:right;outline:none;font-family:monospace"
                    oninput="onPriceChange(this)">
                </td>
                <td style="padding:10px 14px;text-align:center">
                  <span id="status-${p.id}" style="font-size:10px;color:#5E5E5A">—</span>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>
      <div style="padding:10px 14px;border-top:0.5px solid #2E2E33;font-size:11px;color:#5E5E5A;text-align:right">
        ${allProducts.length} anúncio${allProducts.length!==1?'s':''}
      </div>
    </div>`;
  lucide.createIcons();
}

function onPriceChange(input) {
  const id  = input.dataset.id;
  const orig = parseFloat(input.dataset.original);
  const val  = parseFloat(input.value);
  if (val !== orig && !isNaN(val) && val > 0) {
    changedPrices[id] = val;
    input.style.borderColor = '#f59e0b';
    document.getElementById('save-prices-btn').style.display = 'flex';
  } else {
    delete changedPrices[id];
    input.style.borderColor = '#2E2E33';
    if (!Object.keys(changedPrices).length) document.getElementById('save-prices-btn').style.display = 'none';
  }
}

function selectAll(checked) {
  document.querySelectorAll('.prod-check').forEach(c => c.checked = checked);
  const ca = document.getElementById('check-all');
  if (ca) ca.checked = checked;
  onCheckChange();
}

function onCheckChange() {
  const total   = document.querySelectorAll('.prod-check').length;
  const checked = document.querySelectorAll('.prod-check:checked').length;
  const ca = document.getElementById('check-all');
  if (ca) ca.indeterminate = checked > 0 && checked < total;
}

async function applyBulk() {
  const ids = [...document.querySelectorAll('.prod-check:checked')].map(c => c.dataset.id);
  if (!ids.length) { toast('Selecione ao menos um anúncio','warning'); return; }
  const op    = document.getElementById('bulk-op').value;
  const type  = document.getElementById('bulk-type').value;
  const value = parseFloat(document.getElementById('bulk-value').value);
  if (!value || value <= 0) { toast('Informe um valor válido','warning'); return; }

  // Aplica localmente nos inputs primeiro
  allProducts.filter(p => ids.includes(p.id)).forEach(p => {
    const input = document.querySelector(`.price-input[data-id="${p.id}"]`);
    if (!input) return;
    const current = parseFloat(input.value);
    let newPrice;
    if (type === 'percent') newPrice = op === 'increase' ? current*(1+value/100) : current*(1-value/100);
    else newPrice = op === 'increase' ? current+value : current-value;
    newPrice = Math.max(0.01, Math.round(newPrice*100)/100);
    input.value = newPrice.toFixed(2);
    input.style.borderColor = '#f59e0b';
    changedPrices[p.id] = newPrice;
  });
  document.getElementById('save-prices-btn').style.display = 'flex';
  toast(`Valor ajustado em ${ids.length} anúncio(s) — clique em Salvar para confirmar`, 'info');
}

async function saveIndividualPrices() {
  if (!Object.keys(changedPrices).length) { toast('Nenhuma alteração','warning'); return; }
  const btn = document.getElementById('save-prices-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Salvando...';
  lucide.createIcons();

  const updates = Object.entries(changedPrices).map(([id,price]) => ({id,price}));
  const fd = new FormData();
  fd.append('action','update_prices');
  fd.append('updates', JSON.stringify(updates));
  const r = await fetch('/api/sprint1.php',{method:'POST',body:fd});
  const d = await r.json();

  if (d.ok) {
    toast(`${d.updated} preço(s) atualizado(s)${d.errors.length?` · ${d.errors.length} erro(s)`:''}`, d.errors.length?'warning':'success');
    // Atualiza os inputs
    updates.forEach(u => {
      const input = document.querySelector(`.price-input[data-id="${u.id}"]`);
      if (input) { input.dataset.original = u.price; input.style.borderColor = '#22c55e'; }
      document.getElementById(`status-${u.id}`).innerHTML = `<span style="color:#22c55e">✓</span>`;
    });
    changedPrices = {};
    setTimeout(() => {
      document.querySelectorAll('.price-input').forEach(i => i.style.borderColor = '#2E2E33');
      document.querySelectorAll('[id^="status-"]').forEach(s => s.textContent = '—');
    }, 3000);
  } else {
    toast(d.error||'Erro ao atualizar','error');
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="save" style="width:12px;height:12px"></i> Salvar alterações de preço';
  lucide.createIcons();
  if (!Object.keys(changedPrices).length) btn.style.display = 'none';
}

loadProducts();
</script>
<?php endif; ?>

<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
