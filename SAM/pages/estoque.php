<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('can_access_logistica');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

// POST: atualizar estoque manualmente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'update_stock') {
        $productId = $_POST['product_id'] ?? '';
        $qty       = (int)($_POST['qty'] ?? 0);
        $qtyMin    = isset($_POST['qty_min']) ? (int)$_POST['qty_min'] : null;
        $cost      = isset($_POST['cost_price']) ? (float)str_replace(',','.', $_POST['cost_price']) : null;

        if (!$productId) { echo json_encode(['ok'=>false,'error'=>'product_id obrigatório']); exit; }

        $before = db_one("SELECT stock_quantity, stock_min, cost_price FROM products WHERE id=? AND tenant_id=?", [$productId, $tenantId]);
        if (!$before) { echo json_encode(['ok'=>false,'error'=>'Produto não encontrado']); exit; }

        $upd = ['stock_quantity' => $qty];
        if ($qtyMin !== null) $upd['stock_min']   = $qtyMin;
        if ($cost   !== null) $upd['cost_price']  = $cost;

        db_update('products', $upd, 'id=? AND tenant_id=?', [$productId, $tenantId]);
        audit_log('STOCK_UPDATE', 'products', $productId,
            ['qty'=>$before['stock_quantity'],'min'=>$before['stock_min'],'cost'=>$before['cost_price']],
            $upd
        );
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação desconhecida']);
    exit;
}

// ── Dados de estoque ─────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$status   = $_GET['status'] ?? 'all'; // all | critico | ok | zerado
$orderBy  = $_GET['order']  ?? 'title';

$where  = "WHERE tenant_id=?{$acctSql} AND ml_status != 'CLOSED'";
$params = array_merge([$tenantId], (array)$acctP);

if ($search !== '') {
    $where   .= " AND (title LIKE ? OR sku LIKE ? OR meli_item_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$orderMap = [
    'title'    => 'title ASC',
    'qty_asc'  => 'stock_quantity ASC',
    'qty_desc' => 'stock_quantity DESC',
    'value'    => '(cost_price * stock_quantity) DESC',
    'critico'  => '(stock_quantity - stock_min) ASC',
];
$orderSql = $orderMap[$orderBy] ?? 'title ASC';

// Busca todos para filtrar status no PHP (mais simples que CASE no SQL)
$allProducts = db_all(
    "SELECT id, meli_item_id, sku, title, price, cost_price,
            stock_quantity, stock_min, ml_status, ml_health
     FROM products {$where}
     ORDER BY {$orderSql}",
    $params
);

// Filtra por status
$products = array_filter($allProducts, function($p) use ($status) {
    $qty = (int)$p['stock_quantity'];
    $min = (int)$p['stock_min'];
    return match($status) {
        'critico' => $qty > 0 && $qty <= $min,
        'zerado'  => $qty <= 0,
        'ok'      => $qty > $min,
        default   => true,
    };
});
$products = array_values($products);

// KPIs
$totalProdutos  = count($allProducts);
$criticos       = count(array_filter($allProducts, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] <= $p['stock_min']));
$zerados        = count(array_filter($allProducts, fn($p) => $p['stock_quantity'] <= 0));
$valorEstoque   = array_sum(array_map(fn($p) => (float)$p['cost_price'] * max(0,(int)$p['stock_quantity']), $allProducts));
$valorVenda     = array_sum(array_map(fn($p) => (float)$p['price']      * max(0,(int)$p['stock_quantity']), $allProducts));

$title = 'Estoque';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- Título + busca -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Gestão de Estoque</h1>
      <p style="font-size:12px;color:#5E5E5A;margin-top:2px"><?= $totalProdutos ?> produtos cadastrados</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <form method="GET" style="display:flex;gap:6px">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
          placeholder="Buscar produto ou SKU..."
          style="padding:7px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;width:200px">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <input type="hidden" name="order"  value="<?= htmlspecialchars($orderBy) ?>">
        <button type="submit" style="padding:7px 14px;background:#3483FA;border:none;border-radius:8px;color:#fff;font-size:12px;cursor:pointer">Buscar</button>
      </form>
      <button onclick="window.open('/api/pdf_estoque.php?status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&order=<?= urlencode($orderBy) ?>','_blank')"
        style="padding:7px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#9A9A96;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i data-lucide="file-down" style="width:13px;height:13px"></i> Exportar PDF
      </button>
    </div>
  </div>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px" class="kpi-grid">
    <a href="?status=all" style="text-decoration:none">
    <div style="background:#1A1A1C;border:0.5px solid <?= $status==='all'?'#3483FA':'#2E2E33' ?>;border-top:3px solid #3483FA;border-radius:12px;padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="font-size:10px;color:#5E5E5A">Total de produtos</div>
      <div style="font-size:22px;font-weight:600;color:#E8E8E6"><?= $totalProdutos ?></div>
      <div style="font-size:10px;color:#5E5E5A">cadastrados</div>
    </div></a>

    <a href="?status=critico" style="text-decoration:none">
    <div style="background:#1A1A1C;border:0.5px solid <?= $status==='critico'?'#f59e0b':'#2E2E33' ?>;border-top:3px solid #f59e0b;border-radius:12px;padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="font-size:10px;color:#5E5E5A">⚠ Estoque crítico</div>
      <div style="font-size:22px;font-weight:600;color:<?= $criticos>0?'#f59e0b':'#22c55e' ?>"><?= $criticos ?></div>
      <div style="font-size:10px;color:#5E5E5A">abaixo do mínimo</div>
    </div></a>

    <a href="?status=zerado" style="text-decoration:none">
    <div style="background:#1A1A1C;border:0.5px solid <?= $status==='zerado'?'#ef4444':'#2E2E33' ?>;border-top:3px solid #ef4444;border-radius:12px;padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="font-size:10px;color:#5E5E5A">🔴 Sem estoque</div>
      <div style="font-size:22px;font-weight:600;color:<?= $zerados>0?'#ef4444':'#22c55e' ?>"><?= $zerados ?></div>
      <div style="font-size:10px;color:#5E5E5A">produtos zerados</div>
    </div></a>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:12px;padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="font-size:10px;color:#5E5E5A">Valor em estoque</div>
      <div style="font-size:16px;font-weight:600;color:#22c55e">R$ <?= number_format($valorEstoque,2,',','.') ?></div>
      <div style="font-size:10px;color:#5E5E5A">pelo custo</div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:12px;padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="font-size:10px;color:#5E5E5A">Potencial de venda</div>
      <div style="font-size:16px;font-weight:600;color:#3483FA">R$ <?= number_format($valorVenda,2,',','.') ?></div>
      <div style="font-size:10px;color:#5E5E5A">pelo preço ML</div>
    </div>
  </div>

  <!-- Filtros de status + ordenação -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
    <div style="display:flex;gap:4px">
      <?php foreach (['all'=>'Todos','ok'=>'✓ OK','critico'=>'⚠ Crítico','zerado'=>'🔴 Zerado'] as $k=>$v): ?>
      <a href="?status=<?= $k ?>&search=<?= urlencode($search) ?>&order=<?= urlencode($orderBy) ?>"
        style="padding:5px 12px;border-radius:7px;font-size:11px;text-decoration:none;border:0.5px solid <?= $status===$k?'#3483FA':'#2E2E33' ?>;background:<?= $status===$k?'rgba(52,131,250,.1)':'transparent' ?>;color:<?= $status===$k?'#3483FA':'#5E5E5A' ?>">
        <?= $v ?>
      </a>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;align-items:center;gap:6px">
      <span style="font-size:11px;color:#5E5E5A">Ordenar:</span>
      <select onchange="window.location='?status=<?= $status ?>&search=<?= urlencode($search) ?>&order='+this.value"
        style="padding:5px 8px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:11px;cursor:pointer">
        <option value="title"    <?= $orderBy==='title'   ?'selected':'' ?>>Nome A-Z</option>
        <option value="qty_asc"  <?= $orderBy==='qty_asc' ?'selected':'' ?>>Qtd ↑</option>
        <option value="qty_desc" <?= $orderBy==='qty_desc'?'selected':'' ?>>Qtd ↓</option>
        <option value="value"    <?= $orderBy==='value'   ?'selected':'' ?>>Valor ↓</option>
        <option value="critico"  <?= $orderBy==='critico' ?'selected':'' ?>>Mais crítico</option>
      </select>
    </div>
  </div>

  <!-- Tabela de produtos -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table>
        <thead>
          <tr>
            <th>Produto / SKU</th>
            <th style="text-align:center">Qtd atual</th>
            <th style="text-align:center">Mínimo</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:right">Custo unit.</th>
            <th style="text-align:right">Preço ML</th>
            <th style="text-align:right">Valor total</th>
            <th style="text-align:center">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
          <tr><td colspan="8" style="text-align:center;color:#5E5E5A;padding:32px">Nenhum produto encontrado</td></tr>
          <?php else: foreach ($products as $p):
            $qty    = (int)$p['stock_quantity'];
            $min    = (int)$p['stock_min'];
            $custo  = (float)$p['cost_price'];
            $preco  = (float)$p['price'];
            $valor  = $custo * max(0, $qty);

            if ($qty <= 0)       { $stColor='#ef4444'; $stBg='rgba(239,68,68,.1)';  $stLabel='Zerado'; }
            elseif ($qty <= $min){ $stColor='#f59e0b'; $stBg='rgba(245,158,11,.1)'; $stLabel='Crítico'; }
            else                  { $stColor='#22c55e'; $stBg='rgba(34,197,94,.1)';  $stLabel='OK'; }
          ?>
          <tr style="<?= $qty<=0?'background:rgba(239,68,68,.02)':($qty<=$min?'background:rgba(245,158,11,.02)':'') ?>">
            <td style="max-width:240px">
              <div style="font-size:12px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_substr($p['title'],0,50)) ?></div>
              <div style="font-size:10px;color:#5E5E5A;margin-top:1px">SKU: <?= htmlspecialchars($p['sku']) ?> · <?= htmlspecialchars($p['meli_item_id']) ?></div>
            </td>
            <td style="text-align:center">
              <span style="font-size:16px;font-weight:600;color:<?= $stColor ?>"><?= $qty ?></span>
            </td>
            <td style="text-align:center;font-size:12px;color:#5E5E5A"><?= $min ?></td>
            <td style="text-align:center">
              <span style="font-size:10px;padding:2px 8px;border-radius:8px;background:<?= $stBg ?>;color:<?= $stColor ?>"><?= $stLabel ?></span>
            </td>
            <td style="text-align:right;font-size:12px;color:#9A9A96">R$ <?= number_format($custo,2,',','.') ?></td>
            <td style="text-align:right;font-size:12px;color:#9A9A96">R$ <?= number_format($preco,2,',','.') ?></td>
            <td style="text-align:right;font-size:12px;color:#E8E8E6;font-weight:500">R$ <?= number_format($valor,2,',','.') ?></td>
            <td style="text-align:center">
              <button onclick='openStockEdit(<?= json_encode(['id'=>$p['id'],'title'=>mb_substr($p['title'],0,40),'qty'=>$qty,'min'=>$min,'cost'=>$custo]) ?>)'
                style="padding:5px 10px;background:#252528;border:0.5px solid #2E2E33;color:#9A9A96;border-radius:7px;font-size:11px;cursor:pointer;display:inline-flex;align-items:center;gap:4px">
                <i data-lucide="pencil" style="width:11px;height:11px"></i> Editar
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (count($products) > 0): ?>
  <div style="text-align:right;padding:10px 0;font-size:11px;color:#5E5E5A">
    Exibindo <?= count($products) ?> de <?= $totalProdutos ?> produtos
  </div>
  <?php endif; ?>

</div>

<!-- Modal edição de estoque -->
<div id="stock-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:380px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
      <i data-lucide="package" style="width:14px;height:14px;color:#3483FA"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Atualizar estoque</span>
    </div>
    <div id="stock-product-title" style="font-size:11px;color:#5E5E5A;margin-bottom:16px"></div>
    <input type="hidden" id="stock-product-id">

    <div style="display:grid;gap:12px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Quantidade atual</label>
        <input type="number" id="stock-qty" min="0"
          style="width:100%;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:16px;font-weight:600;text-align:center;outline:none">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Estoque mínimo</label>
          <input type="number" id="stock-min" min="0"
            style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Custo unitário (R$)</label>
          <input type="text" id="stock-cost" placeholder="0,00"
            style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none">
        </div>
      </div>
    </div>

    <div style="display:flex;gap:8px;margin-top:20px">
      <button onclick="saveStock()" class="btn-primary" style="flex:1">Salvar</button>
      <button onclick="document.getElementById('stock-modal').style.display='none'" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function openStockEdit(p) {
  document.getElementById('stock-product-id').value = p.id;
  document.getElementById('stock-product-title').textContent = p.title;
  document.getElementById('stock-qty').value  = p.qty;
  document.getElementById('stock-min').value  = p.min;
  document.getElementById('stock-cost').value = p.cost.toFixed(2).replace('.',',');
  document.getElementById('stock-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('stock-qty').select(), 100);
}

async function saveStock() {
  const id   = document.getElementById('stock-product-id').value;
  const qty  = document.getElementById('stock-qty').value;
  const min  = document.getElementById('stock-min').value;
  const cost = document.getElementById('stock-cost').value;

  const fd = new FormData();
  fd.append('action',     'update_stock');
  fd.append('product_id', id);
  fd.append('qty',        qty);
  fd.append('qty_min',    min);
  fd.append('cost_price', cost);

  const r = await fetch('/pages/estoque.php', { method: 'POST', body: fd });
  const d = await r.json();

  if (d.ok) {
    toast('Estoque atualizado!', 'success');
    document.getElementById('stock-modal').style.display = 'none';
    setTimeout(() => location.reload(), 400);
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>

<style>
@media print {
  /* Esconde tudo exceto o conteúdo principal */
  header, #sidebar, #sidebar-overlay, .hamburger-btn,
  form, button, select, .kpi-grid a,
  [id*="stock-modal"], [id*="pager"] { display: none !important; }

  body, .page-content { background: #fff !important; color: #000 !important; }

  /* Cabeçalho de impressão */
  .print-header { display: block !important; }

  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  th, td { border: 1px solid #ddd !important; padding: 5px 8px !important; color: #000 !important; }

  /* KPIs em linha para impressão */
  .kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
  .kpi-grid > div { border: 1px solid #ddd !important; background: #fff !important; }

  @page { size: A4 landscape; margin: 10mm; }
}

/* Cabeçalho visível só na impressão */
.print-header { display: none; }
</style>
