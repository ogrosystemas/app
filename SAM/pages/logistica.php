<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_logistica');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$accountId = $_SESSION['active_meli_account_id'] ?? null;
$acctSql   = $accountId ? " AND meli_account_id=?" : "";
$acctP     = $accountId ? [$accountId] : []; // NUNCA null — fix TypeError array_merge

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action  = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';
    $type    = $_POST['type'] ?? 'pdf';

    if ($action === 'print') {
        $col = $type === 'pdf' ? 'pdf_printed' : 'zpl_printed';
        db_update('orders', [$col => 1], 'id=? AND tenant_id=?', [$orderId, $tenantId]);
        $o = db_one("SELECT pdf_printed, zpl_printed FROM orders WHERE id=?", [$orderId]);
        if ($o && $o['pdf_printed'] && $o['zpl_printed']) {
            db_update('orders', ['label_printed' => 1], 'id=?', [$orderId]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'print_batch') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $col = $type === 'pdf' ? 'pdf_printed' : 'zpl_printed';
        foreach ($ids as $id) {
            db_update('orders', [$col => 1], 'id=? AND tenant_id=?', [$id, $tenantId]);
        }
        echo json_encode(['ok' => true, 'count' => count($ids)]);
        exit;
    }
}

$orders = db_all(
    "SELECT o.*, GROUP_CONCAT(oi.title SEPARATOR ', ') as products, SUM(oi.quantity) as total_items
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.tenant_id=? AND o.payment_status='APPROVED' AND o.ship_status IN ('READY_TO_SHIP','PENDING','SHIPPED'){$acctSql}
     GROUP BY o.id ORDER BY o.order_date ASC",
    array_merge([$tenantId], (array)$acctP)
);
// LGPD: mascara dados pessoais para usuários sem permissão financeiro
array_walk($orders, 'lgpd_apply');

$late     = array_filter($orders, fn($o) => strtotime($o['order_date']) < strtotime('-2 days'));
$printed  = db_one("SELECT COUNT(*) as c FROM orders WHERE tenant_id=? AND label_printed=1 AND DATE(updated_at)=CURDATE()", [$tenantId])['c'] ?? 0;
$mediacao = array_filter($orders, fn($o) => $o['has_mediacao']);

$title = 'Expedição';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Expedição</h1>
      <p style="font-size:12px;color:#5E5E5A;margin-top:2px"><?= count($orders) ?> pedidos · <?= count($late) ?> atrasados</p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="selectAll()" class="btn-secondary" style="font-size:12px;padding:6px 12px">Selecionar todos</button>
      <button onclick="printBatch('pdf')" class="btn-secondary" style="font-size:12px;padding:6px 12px">
        <i data-lucide="file-text" style="width:12px;height:12px"></i> PDF em massa
      </button>
      <button onclick="printBatch('zpl')" class="btn-primary" style="font-size:12px;padding:6px 12px">
        <i data-lucide="printer" style="width:12px;height:12px"></i> Zebra em massa
      </button>
    </div>
  </div>

  <!-- 4 KPIs -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #f59e0b;border-radius:12px;padding:16px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Atrasados</div>
      <div style="font-size:24px;font-weight:600;color:<?= count($late)>0?'#ef4444':'#22c55e' ?>"><?= count($late) ?></div>
      <div style="font-size:11px;color:<?= count($late)>0?'#ef4444':'#22c55e' ?>;margin-top:3px"><?= count($late)>0?'enviar hoje!':'em dia' ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:12px;padding:16px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Na fila</div>
      <div style="font-size:24px;font-weight:600;color:#E8E8E6"><?= count($orders) ?></div>
      <div style="font-size:11px;color:#9A9A96;margin-top:3px">pedidos prontos</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:12px;padding:16px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Impressos hoje</div>
      <div style="font-size:24px;font-weight:600;color:#22c55e"><?= $printed ?></div>
      <div style="font-size:11px;color:#22c55e;margin-top:3px">etiquetas geradas</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:12px;padding:16px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:6px">Em mediação</div>
      <div style="font-size:24px;font-weight:600;color:<?= count($mediacao)>0?'#ef4444':'#22c55e' ?>"><?= count($mediacao) ?></div>
      <div style="font-size:11px;color:<?= count($mediacao)>0?'#ef4444':'#22c55e' ?>;margin-top:3px"><?= count($mediacao)>0?'atenção urgente':'sem mediações' ?></div>
    </div>
  </div>

  <!-- Lista de pedidos -->
  <div id="orders-list" style="display:flex;flex-direction:column;gap:8px">
    <?php if (empty($orders)): ?>
    <div class="card" style="padding:32px;text-align:center;color:#5E5E5A">
      <i data-lucide="package-check" style="width:32px;height:32px;margin:0 auto 8px;display:block;opacity:.4"></i>
      Nenhum pedido na fila de expedição
    </div>
    <?php else: foreach ($orders as $o):
      $isLate    = strtotime($o['order_date']) < strtotime('-2 days');
      $hasMed    = (bool)$o['has_mediacao'];

      // Cor da borda esquerda por status/urgência
      if ($hasMed)        $borderColor = '#ef4444';
      elseif ($isLate)    $borderColor = '#f59e0b';
      elseif ($o['pdf_printed'] && $o['zpl_printed']) $borderColor = '#22c55e';
      else                $borderColor = '#3483FA';

      $bgColor = $hasMed ? 'rgba(239,68,68,.04)' : ($isLate ? 'rgba(245,158,11,.04)' : 'transparent');
    ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-left:3px solid <?= $borderColor ?>;background:<?= $bgColor ?>;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:12px"
      data-id="<?= $o['id'] ?>">

      <!-- Checkbox -->
      <input type="checkbox" class="order-check" value="<?= $o['id'] ?>"
        style="width:15px;height:15px;accent-color:#3483FA;cursor:pointer;flex-shrink:0">

      <!-- Info (esquerda) -->
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap">
          <span style="font-family:monospace;font-size:12px;font-weight:600;color:#E8E8E6"><?= htmlspecialchars($o['meli_order_id']) ?></span>
          <?php if ($hasMed): ?><span class="badge badge-red">MEDIAÇÃO</span><?php endif; ?>
          <?php if ($isLate && !$hasMed): ?><span class="badge badge-amber">ATRASADO</span><?php endif; ?>
          <?php if ($o['pdf_printed'] && $o['zpl_printed']): ?><span class="badge badge-green">✓ Impresso</span><?php endif; ?>
          <span style="font-size:10px;color:<?= $isLate?'#ef4444':'#5E5E5A' ?>;margin-left:auto"><?= date_ptbr('d/m', strtotime($o['order_date'])) ?></span>
        </div>
        <div style="font-size:12px;margin-bottom:2px">
          <strong style="color:#E8E8E6"><?= htmlspecialchars($o['buyer_nickname']) ?></strong>
          <?php if ($o['ship_city']): ?><span style="color:#5E5E5A"> · <?= htmlspecialchars($o['ship_city']) ?>/<?= htmlspecialchars($o['ship_state']) ?></span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= htmlspecialchars(mb_substr($o['products'] ?? '', 0, 60)) ?>
        </div>
      </div>

      <!-- Botões (direita) — lado a lado, centralizados verticalmente -->
      <div class="order-actions">
        <button onclick="printLabel('<?= $o['id'] ?>','pdf',this)"
          class="order-btn <?= $o['pdf_printed'] ? 'order-btn-done' : 'order-btn-default' ?>">
          <i data-lucide="file-text" style="width:12px;height:12px"></i>
          <span class="btn-label"><?= $o['pdf_printed'] ? 'PDF ✓' : 'Etiqueta PDF' ?></span>
        </button>
        <button onclick="printLabel('<?= $o['id'] ?>','zpl',this)"
          class="order-btn <?= $o['zpl_printed'] ? 'order-btn-done' : 'order-btn-primary' ?>">
          <i data-lucide="printer" style="width:12px;height:12px"></i>
          <span class="btn-label"><?= $o['zpl_printed'] ? 'ZPL ✓' : 'Imprimir ZPL' ?></span>
        </button>
        <button onclick="fetchNF('<?= $o['id'] ?>','<?= htmlspecialchars($o['meli_order_id']) ?>',this)"
          id="nf-btn-<?= $o['id'] ?>"
          class="order-btn <?= $o['nf_path'] ? 'order-btn-done' : 'order-btn-nf' ?>">
          <i data-lucide="receipt" style="width:12px;height:12px"></i>
          <span class="btn-label"><?= $o['nf_path'] ? 'NF ✓' : 'Buscar NF' ?></span>
        </button>
        <button onclick="showNFUploadModal('<?= $o['id'] ?>','<?= htmlspecialchars($o['meli_order_id'],ENT_QUOTES) ?>','<?= htmlspecialchars($o['nf_number'] ?? '',ENT_QUOTES) ?>')"
          class="order-btn <?= $o['nf_path'] ? 'order-btn-done' : 'order-btn-default' ?>"
          title="Vincular NF emitida manualmente">
          <i data-lucide="upload" style="width:12px;height:12px"></i>
          <span class="btn-label">Upload NF</span>
        </button>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>
  <div id="orders-pager" style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;margin-top:4px"></div>
</div>

<style>
/* Desktop: botões compactos à direita do card */
.order-actions {
  display: flex;
  flex-direction: column;
  gap: 5px;
  flex-shrink: 0;
}
.order-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  font-size: 11px;
  border-radius: 7px;
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
  border: 0.5px solid transparent;
  min-width: 120px;
  justify-content: center;
}
.order-btn-default { background:#252528; border-color:#2E2E33; color:#9A9A96; }
.order-btn-primary { background:#3483FA; border-color:transparent; color:#fff; }
.order-btn-nf      { background:rgba(255,230,0,.1); border-color:#FFE600; color:#FFE600; }
.order-btn-done    { background:rgba(34,197,94,.1); border-color:#22c55e; color:#22c55e; }

/* Mobile: card empilha, botões em grid */
@media (max-width: 768px) {
  div[data-id] { flex-wrap: wrap !important; }
  .order-actions {
    flex-direction: row !important;
    width: 100%;
    display: grid !important;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
  }
  .order-btn {
    min-width: unset;
    padding: 9px 6px;
    justify-content: center;
  }
}
</style>

<script>
lucide.createIcons();

// ── Paginação pedidos ─────────────────────────────────────
function initOrdersPag(perPage=10) {
  const list = document.getElementById('orders-list'); if(!list) return;
  const cards = Array.from(list.children).filter(el=>el.tagName==='DIV'&&el.dataset.id);
  if(cards.length<=perPage) return;
  let page=1; const total=cards.length, pages=Math.ceil(total/perPage);
  function render() {
    cards.forEach((c,i)=>c.style.display=(i>=(page-1)*perPage&&i<page*perPage)?'':'none');
    const p=document.getElementById('orders-pager'); if(!p) return;
    const s=(page-1)*perPage+1, e=Math.min(page*perPage,total);
    let h=`<span style="font-size:12px;color:#5E5E5A">${s}–${e} de ${total} pedidos</span><div style="display:flex;gap:4px">`;
    h+=`<button onclick="ordPg(${page-1})" ${page<=1?'disabled':''} style="padding:6px 12px;border-radius:6px;border:0.5px solid #2E2E33;background:${page<=1?'transparent':'#252528'};color:${page<=1?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:13px">←</button>`;
    for(let i=Math.max(1,page-2);i<=Math.min(pages,page+2);i++) h+=`<button onclick="ordPg(${i})" style="padding:6px 11px;border-radius:6px;border:0.5px solid ${i===page?'#3483FA':'#2E2E33'};background:${i===page?'#3483FA':'transparent'};color:${i===page?'#fff':'#9A9A96'};cursor:pointer;font-size:13px;min-width:34px">${i}</button>`;
    h+=`<button onclick="ordPg(${page+1})" ${page>=pages?'disabled':''} style="padding:6px 12px;border-radius:6px;border:0.5px solid #2E2E33;background:${page>=pages?'transparent':'#252528'};color:${page>=pages?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:13px">→</button></div>`;
    p.innerHTML=h;
  }
  window.ordPg = p => { if(p>=1&&p<=pages){page=p;render();window.scrollTo(0,0);} };
  render();
}
document.addEventListener('DOMContentLoaded', ()=>initOrdersPag(10));

async function printLabel(orderId, type, btn) {
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:11px;height:11px;animation:spin 1s linear infinite"></i>';
  btn.disabled = true;
  lucide.createIcons();

  try {
    const r = await fetch(`/api/etiqueta_ml.php?order_id=${orderId}&type=${type}`);

    if (r.ok) {
      // Etiqueta disponível — cria blob e abre sem nova chamada ao servidor
      const blob     = await r.blob();
      const blobUrl  = URL.createObjectURL(blob);
      const a        = document.createElement('a');
      a.href         = blobUrl;
      a.target       = '_blank';
      a.rel          = 'noopener';
      if (type === 'zpl') a.download = `Etiqueta_${orderId}.zpl`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      setTimeout(() => URL.revokeObjectURL(blobUrl), 5000);

      btn.innerHTML = type === 'pdf'
        ? '<i data-lucide="file-text" style="width:11px;height:11px"></i> <span class="btn-label">PDF ✓</span>'
        : '<i data-lucide="printer" style="width:11px;height:11px"></i> <span class="btn-label">ZPL ✓</span>';
      btn.style.background = 'rgba(34,197,94,.1)';
      btn.style.border = '0.5px solid #22c55e';
      btn.style.color = '#22c55e';
      toast(`Etiqueta ${type.toUpperCase()} carregada!`, 'success');
      refreshCharts();
    } else {
      const d = await r.json().catch(() => ({}));
      toast(d.error || 'Etiqueta ainda nao liberada pelo ML', 'warning');
      btn.innerHTML = originalHtml;
      btn.style.cssText = '';
    }
  } catch(e) {
    toast('Erro ao buscar etiqueta', 'error');
    btn.innerHTML = originalHtml;
    btn.style.cssText = '';
  }

  btn.disabled = false;
  lucide.createIcons();
}

// ── Nota Fiscal ─────────────────────────────────────────
async function fetchNF(orderId, meliOrderId, btn) {
  btn.innerHTML = '<i data-lucide="loader-2" style="width:11px;height:11px;animation:spin 1s linear infinite"></i>';
  lucide.createIcons();

  try {
    const r = await fetch(`/api/fiscal_note.php?order_id=${orderId}&action=get`);
    const d = await r.json();

    if (d.ok) {
      showNFModal(orderId, meliOrderId, d);
      btn.innerHTML = '<i data-lucide="receipt" style="width:11px;height:11px"></i> NF ✓';
      btn.style.background = 'rgba(34,197,94,.1)';
      btn.style.borderColor = '#22c55e';
      btn.style.color = '#22c55e';
    } else {
      // ML não tem NF — oferece upload manual
      showNFUploadModal(orderId, meliOrderId);
      btn.innerHTML = '<i data-lucide="receipt" style="width:11px;height:11px"></i> NF';
    }
    lucide.createIcons();
  } catch(e) {
    toast('Erro ao buscar NF', 'error');
    btn.innerHTML = '<i data-lucide="receipt" style="width:11px;height:11px"></i> NF';
    lucide.createIcons();
  }
}

function showNFModal(orderId, meliOrderId, nfData) {
  const existing = document.getElementById('nf-modal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'nf-modal';
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px';
  modal.innerHTML = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:440px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
        <i data-lucide="receipt" style="width:16px;height:16px;color:#FFE600"></i>
        <h3 style="font-size:15px;font-weight:500;color:#E8E8E6">Nota Fiscal — ${meliOrderId}</h3>
        <span style="margin-left:auto;font-size:9px;padding:2px 7px;border-radius:8px;background:rgba(52,131,250,.15);color:#3483FA">ML</span>
      </div>

      <div style="background:#252528;border-radius:10px;padding:14px;margin-bottom:16px;display:flex;flex-direction:column;gap:8px">
        ${nfData.invoice_number ? `
        <div style="display:flex;justify-content:space-between;font-size:12px">
          <span style="color:#5E5E5A">Número NF</span>
          <span style="color:#E8E8E6;font-weight:500">${nfData.invoice_number}${nfData.series?` — Série ${nfData.series}`:''}</span>
        </div>` : ''}
        ${nfData.invoice_key ? `
        <div style="display:flex;flex-direction:column;gap:3px;font-size:11px">
          <span style="color:#5E5E5A">Chave de acesso</span>
          <code style="color:#3483FA;word-break:break-all;font-size:10px">${nfData.invoice_key}</code>
        </div>` : ''}
        ${nfData.issue_date ? `
        <div style="display:flex;justify-content:space-between;font-size:12px">
          <span style="color:#5E5E5A">Emissão</span>
          <span style="color:#E8E8E6">${new Date(nfData.issue_date).toLocaleDateString('pt-BR')}</span>
        </div>` : ''}
        <div style="display:flex;justify-content:space-between;font-size:12px">
          <span style="color:#5E5E5A">Shipment ID</span>
          <span style="color:#9A9A96">${nfData.shipment_id}</span>
        </div>
      </div>

      <div style="display:flex;gap:8px">
        <button onclick="downloadNF('${orderId}','${meliOrderId}')"
          style="flex:1;padding:10px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px">
          <i data-lucide="download" style="width:13px;height:13px"></i> Baixar PDF
        </button>
        ${nfData.invoice_key ? `
        <button onclick="copyNFKey('${nfData.invoice_key}')"
          style="padding:10px 14px;background:#252528;border:0.5px solid #2E2E33;color:#9A9A96;border-radius:8px;font-size:12px;cursor:pointer" title="Copiar chave">
          <i data-lucide="copy" style="width:13px;height:13px"></i>
        </button>` : ''}
        <button onclick="document.getElementById('nf-modal').remove()"
          style="padding:10px 14px;background:#252528;border:0.5px solid #2E2E33;color:#9A9A96;border-radius:8px;font-size:12px;cursor:pointer">
          Fechar
        </button>
      </div>

      <!-- Opção de substituir por upload manual -->
      <div style="margin-top:12px;padding-top:12px;border-top:0.5px solid #2E2E33;text-align:center">
        <button onclick="document.getElementById('nf-modal').remove();showNFUploadModal('${orderId}','${meliOrderId}','${nfData.invoice_number||''}')"
          style="background:none;border:none;color:#5E5E5A;font-size:10px;cursor:pointer;text-decoration:underline">
          Substituir por NF própria (upload manual)
        </button>
      </div>
    </div>`;
  document.body.appendChild(modal);
  lucide.createIcons();
}

// ── Upload manual de NF ──────────────────────────────────
function showNFUploadModal(orderId, meliOrderId, nfNumberExistente = '') {
  const existing = document.getElementById('nf-modal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'nf-modal';
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px';
  modal.innerHTML = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:460px">

      <!-- Cabeçalho com número do pedido em destaque -->
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
        <div style="width:36px;height:36px;border-radius:10px;background:rgba(255,230,0,.1);border:0.5px solid #FFE600;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i data-lucide="upload" style="width:16px;height:16px;color:#FFE600"></i>
        </div>
        <div>
          <div style="font-size:14px;font-weight:600;color:#E8E8E6">Vincular Nota Fiscal</div>
          <div style="font-size:11px;color:#5E5E5A;margin-top:1px">
            Pedido <span style="color:#3483FA;font-family:monospace;font-weight:600">${meliOrderId}</span>
          </div>
        </div>
      </div>

      <!-- Aviso identificação -->
      <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:8px;padding:10px 12px;margin-bottom:16px;font-size:11px;color:#9A9A96;line-height:1.5">
        O arquivo será salvo com o número do pedido <strong style="color:#3483FA">${meliOrderId}</strong> para identificação.
        Informe o número da NF para facilitar a busca futura.
      </div>

      <div style="display:flex;flex-direction:column;gap:12px">

        <!-- Número e série -->
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px">
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Número da NF *</label>
            <input type="text" id="nf-number" placeholder="Ex: 000123" value="${nfNumberExistente}"
              style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;font-weight:500;outline:none">
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Série</label>
            <input type="text" id="nf-serie" placeholder="Ex: 1" maxlength="5"
              style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none">
          </div>
        </div>

        <!-- Chave de acesso -->
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Chave de acesso NF-e (44 dígitos)</label>
          <input type="text" id="nf-key" placeholder="00000000000000000000000000000000000000000000" maxlength="44"
            oninput="this.value=this.value.replace(/\D/g,'')"
            style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#9A9A96;font-size:11px;font-family:monospace;outline:none;letter-spacing:.5px">
        </div>

        <!-- Upload arquivo -->
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
            Arquivo PDF ou XML da NF
            <span style="color:#5E5E5A;font-weight:400">(máx. 5MB)</span>
          </label>
          <label for="nf-file" id="nf-drop-zone"
            style="display:flex;align-items:center;gap:10px;padding:14px;background:#252528;border:0.5px dashed #3E3E45;border-radius:8px;cursor:pointer;transition:border-color .15s"
            onmouseover="this.style.borderColor='#3483FA'" onmouseout="this.style.borderColor='#3E3E45'">
            <i data-lucide="file-plus" style="width:20px;height:20px;color:#5E5E5A;flex-shrink:0"></i>
            <div>
              <div id="nf-file-label" style="font-size:12px;color:#5E5E5A">Clique para selecionar PDF ou XML</div>
              <div style="font-size:10px;color:#3E3E45;margin-top:1px">O arquivo será salvo como NF_${meliOrderId}.pdf/xml</div>
            </div>
          </label>
          <input type="file" id="nf-file" accept=".pdf,.xml" style="display:none"
            onchange="
              const f=this.files[0];
              if(f){
                document.getElementById('nf-file-label').textContent=f.name;
                document.getElementById('nf-file-label').style.color='#22c55e';
              }
            ">
        </div>
      </div>

      <div style="display:flex;gap:8px;margin-top:20px">
        <button onclick="uploadNF('${orderId}','${meliOrderId}')" id="nf-upload-btn"
          style="flex:1;padding:11px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px">
          <i data-lucide="upload" style="width:13px;height:13px"></i> Vincular NF ao pedido
        </button>
        <button onclick="document.getElementById('nf-modal').remove()"
          style="padding:11px 14px;background:#252528;border:0.5px solid #2E2E33;color:#9A9A96;border-radius:8px;font-size:12px;cursor:pointer">
          Cancelar
        </button>
      </div>
    </div>`;
  document.body.appendChild(modal);
  lucide.createIcons();
  // Foca no número da NF
  setTimeout(() => document.getElementById('nf-number').focus(), 100);
}

async function uploadNF(orderId, meliOrderId) {
  const file    = document.getElementById('nf-file').files[0];
  const number  = document.getElementById('nf-number').value.trim();
  const serie   = document.getElementById('nf-serie').value.trim();
  const key     = document.getElementById('nf-key').value.replace(/\D/g,'');

  if (!file && !number && !key) {
    toast('Informe pelo menos o número da NF ou faça o upload do arquivo', 'error');
    return;
  }

  const btn = document.getElementById('nf-upload-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:13px;height:13px;animation:spin 1s linear infinite"></i> Enviando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('order_id',  orderId);
  fd.append('nf_number', number);
  fd.append('nf_serie',  serie);
  fd.append('nf_key',    key);
  if (file) fd.append('nf_file', file);

  try {
    const r = await fetch('/api/fiscal_note.php?action=upload', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
      toast('NF vinculada ao pedido!', 'success');
      document.getElementById('nf-modal').remove();
      // Atualiza o botão do pedido
      const btn2 = document.getElementById(`nf-btn-${orderId}`);
      if (btn2) {
        btn2.innerHTML = '<i data-lucide="receipt" style="width:11px;height:11px"></i> <span class="btn-label">NF ✓</span>';
        btn2.style.background = 'rgba(34,197,94,.1)';
        btn2.style.borderColor = '#22c55e';
        btn2.style.color = '#22c55e';
        lucide.createIcons();
      }
    } else {
      toast(d.error || 'Erro ao salvar NF', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="upload" style="width:13px;height:13px"></i> Enviar NF';
      lucide.createIcons();
    }
  } catch(e) {
    toast('Erro de conexão', 'error');
    btn.disabled = false;
  }
}

async function downloadNF(orderId, meliOrderId) {
  toast('Baixando NF...', 'info');
  const link = document.createElement('a');
  link.href = `/api/fiscal_note.php?order_id=${orderId}&action=download`;
  link.download = `NF_${meliOrderId}.pdf`;
  link.click();
}

function copyNFKey(key) {
  navigator.clipboard.writeText(key).then(() => toast('Chave copiada!', 'success'));
}

function selectAll() {
  const checks = document.querySelectorAll('.order-check');
  const all = [...checks].every(c => c.checked);
  checks.forEach(c => c.checked = !all);
}

async function printBatch(type) {
  const ids = [...document.querySelectorAll('.order-check:checked')].map(c => c.value);
  if (!ids.length) { toast('Selecione pelo menos um pedido', 'warning'); return; }

  toast(`Buscando ${ids.length} etiqueta(s) no ML...`, 'info');

  try {
    const r = await fetch(`/api/etiqueta_ml.php?ids=${ids.join(',')}&type=${type}`);

    if (r.ok) {
      const blob    = await r.blob();
      const blobUrl = URL.createObjectURL(blob);
      const a       = document.createElement('a');
      a.href        = blobUrl;
      a.target      = '_blank';
      a.rel         = 'noopener';
      if (type === 'zpl') a.download = `Etiquetas_Lote.zpl`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      setTimeout(() => URL.revokeObjectURL(blobUrl), 5000);
      toast(`${ids.length} etiqueta(s) ${type.toUpperCase()} carregadas!`, 'success');
      setTimeout(() => refreshCharts(), 1000);
    } else {
      const d = await r.json().catch(() => ({}));
      toast(d.error || 'Etiquetas ainda nao liberadas pelo ML', 'warning');
    }
  } catch(e) {
    toast('Erro ao buscar etiquetas', 'error');
  }
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
