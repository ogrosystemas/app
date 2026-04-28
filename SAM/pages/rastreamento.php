<?php
/**
 * pages/rastreamento.php
 * Rastreamento de envios — status ML + Correios
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_logistica');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND o.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

// Filtro de status
$statusFilter = $_GET['status'] ?? 'transit';
$validStatus  = ['all','transit','delivered','returned','unknown'];
if (!in_array($statusFilter, $validStatus)) $statusFilter = 'transit';

// Busca pedidos com envio
$whereParts = ["o.tenant_id=?", "o.meli_shipment_id IS NOT NULL", "o.payment_status IN ('APPROVED','approved')"];
$params     = array_merge([$tenantId], $acctP);

if ($acctId) $whereParts[] = "o.meli_account_id=?";

$statusMap = [
    'transit'   => ["o.ship_status IN ('shipped','SHIPPED','handling','HANDLING','to_be_agreed','TO_BE_AGREED')"],
    'delivered' => ["o.ship_status IN ('delivered','DELIVERED')"],
    'returned'  => ["o.ship_status IN ('returned','RETURNED','cancelled','CANCELLED')"],
];
if (isset($statusMap[$statusFilter])) {
    $whereParts[] = $statusMap[$statusFilter][0];
}

$where  = implode(' AND ', $whereParts);
$orders = db_all(
    "SELECT o.id, o.meli_order_id, o.meli_shipment_id, o.buyer_nickname,
            o.ship_status, o.order_date, o.total_amount,
            oi.title as product_title
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE {$where}
     GROUP BY o.id
     ORDER BY o.order_date DESC
     LIMIT 100",
    $params
);

// Garante que a coluna existe
try {
    db_query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS meli_shipment_id VARCHAR(30) NULL");
    db_query("ALTER TABLE orders ADD INDEX IF NOT EXISTS idx_ship (meli_shipment_id)");
} catch (Throwable $e) {}

// KPIs
$kpiTransit   = (int)(db_one("SELECT COUNT(*) as c FROM orders o WHERE o.tenant_id=?{$acctSql} AND o.ship_status IN ('shipped','SHIPPED','handling','HANDLING') AND o.meli_shipment_id IS NOT NULL", array_merge([$tenantId],$acctP))['c']??0);
$kpiDelivered = (int)(db_one("SELECT COUNT(*) as c FROM orders o WHERE o.tenant_id=?{$acctSql} AND o.ship_status IN ('delivered','DELIVERED') AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)", array_merge([$tenantId],$acctP))['c']??0);
$kpiReturned  = (int)(db_one("SELECT COUNT(*) as c FROM orders o WHERE o.tenant_id=?{$acctSql} AND o.ship_status IN ('returned','RETURNED')", array_merge([$tenantId],$acctP))['c']??0);

$title = 'Rastreamento';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Rastreamento de Envios</h1>
      <p style="font-size:11px;color:#5E5E5A">Acompanhe o status de entrega dos seus pedidos</p>
    </div>
    <div style="display:flex;gap:8px">
      <input type="text" id="search-track" placeholder="Buscar pedido ou comprador..."
        oninput="filterTable(this.value)"
        style="padding:7px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;width:220px">
    </div>
  </div>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:16px">
    <a href="?status=transit" style="text-decoration:none;background:#1A1A1C;border:0.5px solid <?= $statusFilter==='transit'?'#f59e0b':'#2E2E33' ?>;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Em trânsito</div>
      <div style="font-size:22px;font-weight:700;color:#f59e0b"><?= $kpiTransit ?></div>
    </a>
    <a href="?status=delivered" style="text-decoration:none;background:#1A1A1C;border:0.5px solid <?= $statusFilter==='delivered'?'#22c55e':'#2E2E33' ?>;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Entregues (30d)</div>
      <div style="font-size:22px;font-weight:700;color:#22c55e"><?= $kpiDelivered ?></div>
    </a>
    <a href="?status=returned" style="text-decoration:none;background:#1A1A1C;border:0.5px solid <?= $statusFilter==='returned'?'#ef4444':'#2E2E33' ?>;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Devolvidos</div>
      <div style="font-size:22px;font-weight:700;color:#ef4444"><?= $kpiReturned ?></div>
    </a>
    <a href="?status=all" style="text-decoration:none;background:#1A1A1C;border:0.5px solid <?= $statusFilter==='all'?'#3483FA':'#2E2E33' ?>;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Todos</div>
      <div style="font-size:22px;font-weight:700;color:#3483FA">Ver todos</div>
    </a>
  </div>

  <?php if (empty($orders)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="package-search" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhum pedido encontrado</div>
    <div style="font-size:11px;color:#5E5E5A">Tente outro filtro</div>
  </div>
  <?php else: ?>
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="overflow-x:auto">
      <table id="track-table" style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Pedido</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Comprador</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Produto</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Data</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Status</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Valor</th>
            <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o):
            $status = strtolower($o['ship_status'] ?? 'unknown');
            [$sColor, $sLabel, $sIcon] = match(true) {
              in_array($status, ['delivered'])                            => ['#22c55e','Entregue','check-circle'],
              in_array($status, ['shipped'])                             => ['#3483FA','Enviado','truck'],
              in_array($status, ['handling'])                            => ['#f59e0b','Manuseio','package'],
              in_array($status, ['returned'])                            => ['#ef4444','Devolvido','rotate-ccw'],
              in_array($status, ['cancelled'])                           => ['#5E5E5A','Cancelado','x-circle'],
              default                                                     => ['#9A9A96','Pendente','clock'],
            };
          ?>
          <tr class="track-row" style="border-bottom:0.5px solid #2E2E33"
              data-search="<?= strtolower($o['buyer_nickname'].' '.$o['meli_order_id'].' '.($o['product_title']??'')) ?>">
            <td style="padding:10px 14px">
              <div style="font-family:monospace;font-size:11px;color:#3483FA">#<?= $o['meli_order_id'] ?></div>
              <?php if ($o['meli_shipment_id']): ?>
              <div style="font-size:9px;color:#5E5E5A">Ship: <?= $o['meli_shipment_id'] ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px;color:#E8E8E6"><?= htmlspecialchars($o['buyer_nickname'] ?? '—') ?></td>
            <td style="padding:10px 14px;max-width:200px">
              <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9A9A96;font-size:11px">
                <?= htmlspecialchars($o['product_title'] ?? '—') ?>
              </div>
            </td>
            <td style="padding:10px 14px;color:#5E5E5A;white-space:nowrap">
              <?= $o['order_date'] ? date('d/m/y', strtotime($o['order_date'])) : '—' ?>
            </td>
            <td style="padding:10px 14px">
              <span style="display:inline-flex;align-items:center;gap:4px;font-size:9px;padding:3px 8px;border-radius:8px;background:<?= $sColor ?>15;color:<?= $sColor ?>;font-weight:600">
                <i data-lucide="<?= $sIcon ?>" style="width:9px;height:9px"></i>
                <?= $sLabel ?>
              </span>
            </td>
            <td style="padding:10px 14px;text-align:right;color:#E8E8E6;white-space:nowrap">
              R$ <?= number_format((float)($o['total_amount']??0),2,',','.') ?>
            </td>
            <td style="padding:10px 14px;text-align:center">
              <div style="display:flex;align-items:center;justify-content:center;gap:6px">
                <?php if ($o['meli_shipment_id']): ?>
                <button onclick="rastrearEnvio('<?= $o['meli_shipment_id'] ?>','<?= $o['meli_order_id'] ?>')"
                  style="padding:4px 10px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;color:#3483FA;border-radius:6px;font-size:10px;cursor:pointer;display:inline-flex;align-items:center;gap:3px">
                  <i data-lucide="map-pin" style="width:10px;height:10px"></i> Rastrear
                </button>
                <?php endif; ?>
                <a href="https://www.mercadolivre.com.br/vendas/<?= $o['meli_order_id'] ?>/detalhe" target="_blank"
                  style="padding:4px 10px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;text-decoration:none;display:inline-flex;align-items:center;gap:3px">
                  <i data-lucide="external-link" style="width:10px;height:10px"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:10px 14px;border-top:0.5px solid #2E2E33;font-size:11px;color:#5E5E5A;text-align:right">
      <?= count($orders) ?> pedido<?= count($orders)!==1?'s':'' ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal de rastreamento -->
<div id="track-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:480px;max-height:80vh;overflow-y:auto">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
      <i data-lucide="map-pin" style="width:16px;height:16px;color:#3483FA"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Rastreamento do Envio</span>
      <button onclick="document.getElementById('track-modal').style.display='none'" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:18px">✕</button>
    </div>
    <div id="track-modal-content">
      <div style="text-align:center;padding:24px;color:#5E5E5A">
        <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
        Buscando rastreamento...
      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function filterTable(q) {
  const rows = document.querySelectorAll('.track-row');
  const lq = q.toLowerCase();
  rows.forEach(r => {
    r.style.display = !q || r.dataset.search.includes(lq) ? '' : 'none';
  });
}

async function rastrearEnvio(shipmentId, orderId) {
  const modal = document.getElementById('track-modal');
  modal.style.display = 'flex';
  document.getElementById('track-modal-content').innerHTML = `
    <div style="text-align:center;padding:24px;color:#5E5E5A">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
      Buscando rastreamento...
    </div>`;
  lucide.createIcons();

  try {
    const r = await fetch(`/api/rastreamento.php?shipment_id=${shipmentId}`);
    const d = await r.json();

    if (!d.ok) {
      document.getElementById('track-modal-content').innerHTML =
        `<div style="color:#ef4444;font-size:12px;text-align:center;padding:16px">${d.error||'Erro ao buscar rastreamento'}</div>`;
      return;
    }

    const s = d.shipment;
    const steps = d.history || [];
    const statusColors = {
      delivered: '#22c55e', shipped: '#3483FA',
      handling: '#f59e0b', returned: '#ef4444'
    };
    const sColor = statusColors[s.status?.toLowerCase()] || '#9A9A96';

    let html = `
      <div style="background:#252528;border-radius:10px;padding:14px;margin-bottom:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <span style="font-size:11px;color:#5E5E5A">Pedido #${orderId}</span>
          <span style="font-size:10px;padding:2px 8px;border-radius:6px;background:${sColor}20;color:${sColor};font-weight:600">
            ${s.status || '—'}
          </span>
        </div>
        ${s.tracking_number ? `<div style="font-size:11px;color:#9A9A96">Código: <strong style="color:#E8E8E6;font-family:monospace">${s.tracking_number}</strong></div>` : ''}
        ${s.estimated_delivery ? `<div style="font-size:11px;color:#9A9A96;margin-top:4px">Entrega prevista: <strong style="color:#E8E8E6">${s.estimated_delivery}</strong></div>` : ''}
      </div>`;

    if (steps.length) {
      html += `<div style="display:flex;flex-direction:column;gap:0">`;
      steps.forEach((step, i) => {
        const isLast = i === steps.length - 1;
        html += `
          <div style="display:flex;gap:12px;padding-bottom:${isLast?'0':'14px'}">
            <div style="display:flex;flex-direction:column;align-items:center">
              <div style="width:10px;height:10px;border-radius:50%;background:${isLast?'#3483FA':'#2E2E33'};border:2px solid ${isLast?'#3483FA':'#3E3E45'};flex-shrink:0"></div>
              ${!isLast?`<div style="width:1px;flex:1;background:#2E2E33;margin-top:4px"></div>`:''}
            </div>
            <div style="flex:1;padding-bottom:${isLast?'0':'4px'}">
              <div style="font-size:12px;font-weight:500;color:${isLast?'#E8E8E6':'#9A9A96'}">${step.description || step.status || '—'}</div>
              ${step.date ? `<div style="font-size:10px;color:#5E5E5A;margin-top:2px">${step.date}</div>` : ''}
              ${step.location ? `<div style="font-size:10px;color:#5E5E5A">${step.location}</div>` : ''}
            </div>
          </div>`;
      });
      html += `</div>`;
    } else {
      html += `<div style="text-align:center;color:#5E5E5A;font-size:12px;padding:16px">Nenhum evento de rastreamento disponível</div>`;
    }

    document.getElementById('track-modal-content').innerHTML = html;
    lucide.createIcons();
  } catch(e) {
    document.getElementById('track-modal-content').innerHTML =
      `<div style="color:#ef4444;font-size:12px;text-align:center;padding:16px">Erro de conexão</div>`;
  }
}

document.getElementById('track-modal').addEventListener('click', function(e) {
  if (e.target === this) this.style.display = 'none';
});
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
