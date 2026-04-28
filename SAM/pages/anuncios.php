<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_anuncios');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$accountId = $_SESSION['active_meli_account_id'] ?? null;
$acctSql   = $accountId ? " AND meli_account_id=?" : "";
$acctP     = $accountId ? [$accountId] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = $_POST['id'] ?? '';
        $data = [
            'tenant_id'          => $tenantId,
            'sku'                => trim($_POST['sku'] ?? ''),
            'title'              => trim($_POST['title'] ?? ''),
            'description'        => trim($_POST['description'] ?? ''),
            'price'              => (float)($_POST['price'] ?? 0),
            'cost_price'         => (float)($_POST['cost_price'] ?? 0),
            'ipi_valor'          => (float)($_POST['ipi_valor'] ?? 0),
            'ml_fee_percent'     => (float)($_POST['ml_fee_percent'] ?? 14),
            'stock_quantity'     => (int)($_POST['stock_quantity'] ?? 0),
            'stock_min'          => (int)($_POST['stock_min'] ?? 5),
            'category_id'        => $_POST['category_id'] ?? null,
            'listing_type_id'    => $_POST['listing_type_id'] ?? 'gold_special',
            'item_condition'          => $_POST['item_condition'] ?? 'new',
            'gtin'               => trim($_POST['gtin'] ?? '') ?: null,
            'catalog_product_id' => trim($_POST['catalog_product_id'] ?? '') ?: null,
            'picture_ids'        => $_POST['picture_ids'] ?? '[]',
            'ml_attributes'      => $_POST['ml_attributes'] ?? '[]',
            'ml_status'          => 'ACTIVE',
        ];
        if (!$data['title'] || !$data['sku'] || $data['price'] <= 0) {
            echo json_encode(['ok'=>false,'error'=>'Título, SKU e preço são obrigatórios']);
            exit;
        }
        if ($id) {
            db_update('products', $data, 'id=? AND tenant_id=?', [$id, $tenantId]);
            echo json_encode(['ok'=>true,'id'=>$id]);
        } else {
            $newId = db_insert('products', $data);
            echo json_encode(['ok'=>true,'id'=>$newId]);
        }
        exit;
    }

    if ($action === 'delete') {
        audit_log('DELETE_PRODUCT', 'products', $_POST['id']??'');
    db_query("DELETE FROM products WHERE id=? AND tenant_id=?", [$_POST['id']??'', $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'toggle') {
        $p = db_one("SELECT ml_status FROM products WHERE id=? AND tenant_id=?", [$_POST['id']??'', $tenantId]);
        $new = $p['ml_status'] === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';
        db_update('products', ['ml_status'=>$new], 'id=? AND tenant_id=?', [$_POST['id'], $tenantId]);
        echo json_encode(['ok'=>true,'status'=>$new]);
        exit;
    }

    if ($action === 'search_category') {
        $q = $_POST['q'] ?? '';
        $ctx = stream_context_create(['http'=>['timeout'=>8,'header'=>'User-Agent: LupaERP/1.0']]);
        $res = @file_get_contents("https://api.mercadolibre.com/sites/MLB/domain_discovery/search?limit=8&q=".urlencode($q), false, $ctx);
        if ($res) {
            echo json_encode(['ok'=>true,'categories'=>json_decode($res, true)??[]]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'Não foi possível buscar categorias']);
        }
        exit;
    }

    if ($action === 'get_attributes') {
        $catId = $_POST['category_id'] ?? '';
        $ctx = stream_context_create(['http'=>['timeout'=>8,'header'=>'User-Agent: LupaERP/1.0']]);
        $res = @file_get_contents("https://api.mercadolibre.com/categories/{$catId}/attributes", false, $ctx);
        if ($res) {
            $attrs    = json_decode($res, true) ?? [];
            $required = array_filter($attrs, fn($a) => ($a['tags']['required']??false) || ($a['tags']['catalog_required']??false));
            echo json_encode(['ok'=>true,'attributes'=>array_values($required)]);
        } else {
            echo json_encode(['ok'=>false,'attributes'=>[]]);
        }
        exit;
    }
}

$products = db_all("SELECT * FROM products WHERE tenant_id=?{$acctSql} ORDER BY title", array_merge([$tenantId], (array)$acctP));

// Dados para gráficos
$ativos   = array_filter($products, fn($p) => $p['ml_status']==='ACTIVE');
$pausados = array_filter($products, fn($p) => $p['ml_status']==='PAUSED');
$criticos = array_filter($products, fn($p) => $p['stock_quantity'] <= $p['stock_min']);
$avgPrice = count($products) ? array_sum(array_column($products,'price'))/count($products) : 0;
$stockVal = array_sum(array_map(fn($p)=>$p['cost_price']*$p['stock_quantity'],$products));

// Top 5 por margem (cópia separada para não afetar tabela)
$prodsCopy = $products;
usort($prodsCopy, function($a, $b) {
    $mA = $a['price'] > 0 ? ($a['price'] - $a['cost_price'] - $a['price']*$a['ml_fee_percent']/100) / $a['price'] * 100 : 0;
    $mB = $b['price'] > 0 ? ($b['price'] - $b['cost_price'] - $b['price']*$b['ml_fee_percent']/100) / $b['price'] * 100 : 0;
    return $mB <=> $mA;
});
$topByMargin = array_slice($prodsCopy, 0, 5);

// Distribuição de saúde ML
$healthDist = ['Alta (70+)'=>0, 'Média (40-70)'=>0, 'Baixa (<40)'=>0];
foreach ($products as $p) {
    $h = (int)($p['ml_health']??0);
    if ($h >= 70)      $healthDist['Alta (70+)']++;
    elseif ($h >= 40)  $healthDist['Média (40-70)']++;
    else               $healthDist['Baixa (<40)']++;
}

$title = 'Anúncios';
$anTab = in_array($_GET['tab'] ?? '', ['anuncios','renovar']) ? $_GET['tab'] : 'anuncios';
include __DIR__ . '/layout.php';
?>

<!-- Navegação por abas -->

<?php if ($anTab === 'anuncios'): ?>

<div style="padding:24px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Gestão de anúncios</h1>
      <p style="font-size:12px;color:#5E5E5A;margin-top:2px"><?= count($products) ?> anúncios cadastrados</p>
    </div>
    <button onclick="openNew()" class="btn-primary">
      <i data-lucide="plus" style="width:13px;height:13px"></i> Novo anúncio
    </button>
  </div>

  <!-- KPIs com bordas coloridas -->
  <div class="kpi-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:11px;color:#5E5E5A">Ativos</span>
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(34,197,94,.15);display:flex;align-items:center;justify-content:center">
          <i data-lucide="check-circle" style="width:13px;height:13px;color:#22c55e"></i>
        </div>
      </div>
      <div id="an-ativos-val" style="font-size:24px;font-weight:600;color:#22c55e"><?= count($ativos) ?></div>
      <div id="an-ativos-delta" style="font-size:11px;color:#5E5E5A;margin-top:3px"><?= count($pausados) ?> pausados</div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:11px;color:#5E5E5A">Estoque crítico</span>
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.15);display:flex;align-items:center;justify-content:center">
          <i data-lucide="alert-triangle" style="width:13px;height:13px;color:#ef4444"></i>
        </div>
      </div>
      <div id="an-criticos-val" style="font-size:24px;font-weight:600;color:<?= count($criticos)>0?'#ef4444':'#22c55e' ?>"><?= count($criticos) ?></div>
      <div id="an-criticos-delta" style="font-size:11px;color:#5E5E5A;margin-top:3px"><?= count($criticos)>0?'abaixo do mínimo':'tudo OK' ?></div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:11px;color:#5E5E5A">Preço médio</span>
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(52,131,250,.15);display:flex;align-items:center;justify-content:center">
          <i data-lucide="tag" style="width:13px;height:13px;color:#3483FA"></i>
        </div>
      </div>
      <div id="an-preco-val" style="font-size:20px;font-weight:600;color:#3483FA">R$ <?= number_format($avgPrice,2,',','.') ?></div>
      <div id="an-preco-delta" style="font-size:11px;color:#5E5E5A;margin-top:3px"><?= count($products) ?> produtos</div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #f59e0b;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span style="font-size:11px;color:#5E5E5A">Valor em estoque</span>
        <div style="width:28px;height:28px;border-radius:7px;background:rgba(245,158,11,.15);display:flex;align-items:center;justify-content:center">
          <i data-lucide="package" style="width:13px;height:13px;color:#f59e0b"></i>
        </div>
      </div>
      <div id="an-estoque-val" style="font-size:18px;font-weight:600;color:#f59e0b">R$ <?= number_format($stockVal,2,',','.') ?></div>
      <div style="font-size:11px;color:#5E5E5A;margin-top:3px">custo total</div>
    </div>
  </div>

  <!-- Gráficos -->
  <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;margin-bottom:20px">

    <!-- Barras: preço x margem dos top produtos -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Margem líquida por produto</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:14px">Top 5 — % de margem</div>
      <canvas id="marginChart" style="height:130px!important;max-height:130px"></canvas>
    </div>

    <!-- Donut: status ativos x pausados -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Status</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Ativos vs pausados</div>
      <canvas id="statusDonut" style="height:100px!important;max-height:100px"></canvas>
      <div style="margin-top:10px;display:flex;flex-direction:column;gap:5px">
        <div style="display:flex;justify-content:space-between;font-size:11px"><span style="display:flex;align-items:center;gap:5px;color:#9A9A96"><span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block"></span>Ativos</span><span style="color:#E8E8E6;font-weight:600"><?= count($ativos) ?></span></div>
        <div style="display:flex;justify-content:space-between;font-size:11px"><span style="display:flex;align-items:center;gap:5px;color:#9A9A96"><span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block"></span>Pausados</span><span style="color:#E8E8E6;font-weight:600"><?= count($pausados) ?></span></div>
      </div>
    </div>

    <!-- Donut: saúde ML -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Saúde ML</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Qualidade dos anúncios</div>
      <canvas id="healthDonut" style="height:100px!important;max-height:100px"></canvas>
      <div style="margin-top:10px;display:flex;flex-direction:column;gap:5px">
        <?php foreach ($healthDist as $label => $cnt): $color = str_contains($label,'Alta')?'#22c55e':(str_contains($label,'Média')?'#f59e0b':'#ef4444'); ?>
        <div style="display:flex;justify-content:space-between;font-size:11px"><span style="display:flex;align-items:center;gap:5px;color:#9A9A96"><span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block"></span><?= $label ?></span><span style="color:#E8E8E6;font-weight:600"><?= $cnt ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Busca -->
  <div style="margin-bottom:16px">
    <input type="text" id="search-input" placeholder="Buscar por título ou SKU..." oninput="filterTable(this.value)"
      style="width:320px;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none">
  </div>

  <!-- Tabela -->
  <div class="card" style="margin-bottom:20px;overflow:hidden">
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table>
        <thead><tr>
          <th>Produto / SKU</th><th>Categoria ML</th><th>Preço</th><th>Margem</th><th>Estoque</th><th>Saúde</th><th>Status</th><th>Ações</th>
        </tr></thead>
        <tbody id="tbody">
          <?php foreach ($products as $p):
            $margin = $p['price'] > 0 ? (($p['price'] - $p['cost_price'] - $p['price'] * $p['ml_fee_percent'] / 100) / $p['price']) * 100 : 0;
            $mColor = $margin > 20 ? '#22c55e' : ($margin > 10 ? '#f59e0b' : '#ef4444');
            $stockLow = $p['stock_quantity'] <= $p['stock_min'];
            $health = (int)($p['ml_health'] ?? 0);
            $hColor = $health >= 70 ? '#22c55e' : ($health >= 40 ? '#f59e0b' : '#ef4444');
          ?>
          <tr data-title="<?= strtolower(htmlspecialchars($p['title'])) ?>" data-sku="<?= strtolower(htmlspecialchars($p['sku'])) ?>">
            <td>
              <div style="font-weight:500;font-size:12px"><?= htmlspecialchars($p['title']) ?></div>
              <div style="font-family:monospace;font-size:10px;color:#5E5E5A"><?= htmlspecialchars($p['sku']) ?></div>
            </td>
            <td style="font-size:11px;color:#5E5E5A"><?= $p['category_id'] ? htmlspecialchars($p['category_id']) : '—' ?></td>
            <td style="font-weight:500">R$ <?= number_format((float)$p['price'],2,',','.') ?></td>
            <td style="color:<?= $mColor ?>;font-weight:500"><?= number_format($margin,1,',','.') ?>%</td>
            <td style="color:<?= $stockLow?'#ef4444':'#E8E8E6' ?>;font-weight:<?= $stockLow?'500':'400' ?>">
              <?= $p['stock_quantity'] ?> un.
              <?php if ($stockLow): ?><div style="font-size:9px;color:#ef4444">abaixo do mín.</div><?php endif; ?>
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:6px">
                <div style="width:48px;height:5px;border-radius:3px;background:#2E2E33;overflow:hidden">
                  <div style="width:<?= $health ?>%;height:100%;background:<?= $hColor ?>;border-radius:3px"></div>
                </div>
                <span style="font-size:10px;color:<?= $hColor ?>"><?= $health ?>%</span>
              </div>
            </td>
            <td>
              <button onclick="toggleStatus('<?= $p['id'] ?>',this)" class="badge <?= $p['ml_status']==='ACTIVE'?'badge-green':'badge-amber' ?>" style="cursor:pointer;border:none">
                <?= $p['ml_status']==='ACTIVE'?'Ativo':'Pausado' ?>
              </button>
            </td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap;min-width:140px">
                <button onclick='openEdit(<?= json_encode($p) ?>)' class="btn-secondary" style="padding:6px 10px;font-size:11px;display:flex;align-items:center;gap:4px">
                  <i data-lucide="pencil" style="width:11px;height:11px"></i> Editar
                </button>
                <button onclick="sendML('<?= $p['id'] ?>')" style="padding:6px 10px;font-size:11px;background:rgba(255,230,0,.1);border:0.5px solid #FFE600;color:#FFE600;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:4px">
                  <i data-lucide="send" style="width:11px;height:11px"></i> Publicar
                </button>
                <button onclick="delProduct('<?= $p['id'] ?>')" style="padding:6px 10px;font-size:11px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;border-radius:6px;cursor:pointer;display:flex;align-items:center;gap:4px">
                  <i data-lucide="trash-2" style="width:11px;height:11px"></i> Excluir
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?>
          <tr><td colspan="8" style="text-align:center;color:#5E5E5A;padding:24px">Nenhum anúncio cadastrado</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Calculadora -->
  <div class="card" style="padding:16px">
    <div style="font-size:10px;font-weight:500;color:#5E5E5A;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px">Calculadora de margem</div>
    <!-- Grid: 3 inputs + 2 resultados -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Custo (R$)</label>
        <input type="number" id="c-cost" value="80" oninput="calcM()" style="width:100%;padding:8px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:13px;outline:none;box-sizing:border-box">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Preço venda (R$)</label>
        <input type="number" id="c-sell" value="189.90" oninput="calcM()" style="width:100%;padding:8px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:13px;outline:none;box-sizing:border-box">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Taxa ML (%)</label>
        <input type="number" id="c-fee" value="14" oninput="calcM()" style="width:100%;padding:8px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:13px;outline:none;box-sizing:border-box">
      </div>
    </div>
    <!-- Resultados -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div style="background:#252528;border-radius:8px;padding:12px">
        <div style="font-size:11px;color:#9A9A96;margin-bottom:4px">Margem</div>
        <div id="c-margin" style="font-size:24px;font-weight:600">—</div>
      </div>
      <div style="background:#252528;border-radius:8px;padding:12px">
        <div style="font-size:11px;color:#9A9A96;margin-bottom:4px">Lucro/unidade</div>
        <div id="c-profit" style="font-size:20px;font-weight:500;color:#E8E8E6">—</div>
      </div>
    </div>
  </div>
</div>

<!-- Modal criar/editar -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:flex-start;justify-content:center;z-index:200;overflow-y:auto;padding:20px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:1100px;margin:0 auto;box-shadow:0 24px 80px rgba(0,0,0,.7);overflow:hidden">

    <!-- Header do modal -->
    <div style="padding:18px 24px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:10px;background:#1E1E21;position:sticky;top:0;z-index:10">
      <i data-lucide="package-plus" style="width:18px;height:18px;color:#3483FA"></i>
      <h2 id="modal-title" style="font-size:15px;font-weight:600;color:#E8E8E6;margin:0">Novo anúncio</h2>
      <button onclick="closeModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;padding:4px;border-radius:6px;display:flex;align-items:center;transition:color .15s" onmouseover="this.style.color='#E8E8E6'" onmouseout="this.style.color='#5E5E5A'">
        <i data-lucide="x" style="width:18px;height:18px"></i>
      </button>
    </div>

    <input type="hidden" id="f-id">
    <input type="hidden" id="f-category">
    <input type="hidden" id="f-picture-ids" value="[]">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;min-height:600px">

      <!-- ── Coluna Esquerda ── -->
      <div style="padding:24px;border-right:0.5px solid #2E2E33;overflow-y:auto;display:flex;flex-direction:column;gap:18px">

        <!-- Seção: Identificação -->
        <div>
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="info" style="width:11px;height:11px"></i> Identificação
          </div>

          <!-- Título -->
          <div style="margin-bottom:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
              <label style="font-size:11px;color:#9A9A96">Título * <span style="color:#5E5E5A">(máx. 60 caracteres)</span></label>
              <span id="char-count" style="font-size:10px;color:#5E5E5A">0/60</span>
            </div>
            <input type="text" id="f-title" maxlength="60" class="input" placeholder="Nome do Produto + Marca + Modelo + Características" oninput="countChars()">
          </div>

          <!-- Categoria -->
          <div style="padding:12px 14px;background:#252528;border-radius:10px;border:0.5px solid #2E2E33;margin-bottom:12px">
            <div style="font-size:11px;font-weight:600;color:#FFE600;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
              <span style="display:flex;align-items:center;gap:5px"><i data-lucide="tag" style="width:11px;height:11px"></i> Categoria ML *</span>
              <span id="cat-required-badge" style="font-size:9px;color:#ef4444">Obrigatória</span>
            </div>
            <div id="cat-selected" style="display:none;margin-bottom:8px;padding:7px 10px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;border-radius:7px;font-size:12px;color:#3483FA"></div>
            <div style="display:flex;gap:8px">
              <input type="text" id="cat-search" placeholder="Buscar: Fone, Notebook, Cabo..."
                style="flex:1;padding:8px 12px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:12px;outline:none"
                onkeydown="if(event.key==='Enter'){event.preventDefault();searchCategory();}">
              <button onclick="searchCategory()" style="padding:8px 14px;background:#FFE600;color:#1A1A1A;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer">Buscar</button>
            </div>
            <div id="cat-results" style="margin-top:6px;display:none;max-height:160px;overflow-y:auto;border:0.5px solid #2E2E33;border-radius:8px;background:#1A1A1C"></div>
          </div>

          <!-- SKU + GTIN + Marca + Modelo -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">SKU</label>
              <input type="text" id="f-sku" class="input" placeholder="PROD-001">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">GTIN / EAN <span style="color:#3483FA">★</span></label>
              <input type="text" id="f-gtin" class="input" placeholder="7891234567890">
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Marca</label>
              <input type="text" id="f-brand" class="input" placeholder="Samsung, Apple...">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Modelo</label>
              <input type="text" id="f-model" class="input" placeholder="Galaxy S24, iPhone 15...">
            </div>
          </div>
        </div>

        <!-- Seção: Preço e Estoque -->
        <div>
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="dollar-sign" style="width:11px;height:11px"></i> Preço e Estoque
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Preço (R$) *</label>
              <input type="number" step="0.01" id="f-price" class="input" oninput="calcFormMargin()">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Custo (R$)</label>
              <input type="number" step="0.01" id="f-cost" class="input" oninput="calcFormMargin()">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Taxa ML (%)</label>
              <input type="number" step="0.1" id="f-fee" class="input" value="14" oninput="calcFormMargin()">
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
                IPI (R$)
                <span style="color:#5E5E5A">— valor da NF do fornecedor</span>
              </label>
              <input type="number" step="0.01" min="0" id="f-ipi" class="input" value="0" placeholder="0,00" oninput="calcFormMargin()">
            </div>
            <div style="display:flex;align-items:flex-end">
              <div id="ipi-preview" style="display:none;background:#252528;border-radius:8px;padding:8px 12px;font-size:11px;color:#f59e0b;width:100%;box-sizing:border-box">
                <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Custo real com IPI</div>
                <div id="ipi-custo-real" style="font-weight:700">—</div>
              </div>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Estoque</label>
              <input type="number" id="f-stock" class="input" value="0">
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Estoque mínimo</label>
              <input type="number" id="f-stock-min" class="input" value="5">
            </div>
          </div>

          <!-- Preview margem -->
          <div id="form-margin" style="display:none;background:#252528;border-radius:8px;padding:12px;flex-direction:row;gap:20px">
            <div><div style="font-size:10px;color:#5E5E5A;margin-bottom:2px">Margem</div><div id="fm-margin" style="font-size:18px;font-weight:500"></div></div>
            <div><div style="font-size:10px;color:#5E5E5A;margin-bottom:2px">Lucro/un.</div><div id="fm-profit" style="font-size:18px;font-weight:500;color:#E8E8E6"></div></div>
          </div>
        </div>

        <!-- Seção: Configurações do anúncio -->
        <div>
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="settings" style="width:11px;height:11px"></i> Configurações
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Tipo de anúncio</label>
              <select id="f-listing-type" class="input">
                <option value="gold_special">Premium (Gold Special)</option>
                <option value="gold_pro">Gold Pro</option>
                <option value="free">Clássico (Gratuito)</option>
              </select>
            </div>
            <div>
              <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Condição</label>
              <select id="f-condition" class="input">
                <option value="new">Novo</option>
                <option value="used">Usado</option>
              </select>
            </div>
          </div>

          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Catalog Product ID</label>
            <input type="text" id="f-catalog-id" class="input" placeholder="MLB...">
          </div>
        </div>

        <!-- Atributos obrigatórios ML -->
        <div id="ml-attributes" style="display:none">
          <div style="font-size:10px;font-weight:700;color:#f59e0b;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="list-checks" style="width:11px;height:11px"></i> Atributos obrigatórios
          </div>
          <div id="ml-attrs-list" style="display:grid;gap:8px"></div>
        </div>

      </div>

      <!-- ── Coluna Direita ── -->
      <div style="padding:24px;display:flex;flex-direction:column;gap:18px">

        <!-- Fotos -->
        <div>
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="image" style="width:11px;height:11px"></i> Fotos <span style="color:#3483FA;font-weight:400;text-transform:none;letter-spacing:0">mínimo 1, ideal 5+ · fundo branco · 500×500px</span>
          </div>
          <div id="photos-container" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px"></div>
          <label for="photo-input" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;background:#252528;border:1.5px dashed #2E2E33;border-radius:8px;cursor:pointer;color:#5E5E5A;font-size:12px;transition:all .15s"
            onmouseover="this.style.borderColor='#3483FA';this.style.color='#3483FA'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
            <i data-lucide="upload-cloud" style="width:16px;height:16px"></i>
            Clique para adicionar fotos (máx. 6)
            <input type="file" id="photo-input" accept="image/jpeg,image/png" multiple style="display:none" onchange="handlePhotos(this.files)">
          </label>
          <div id="photo-upload-status" style="font-size:11px;color:#5E5E5A;margin-top:6px"></div>
        </div>

        <!-- Descrição com TinyMCE -->
        <div style="flex:1;display:flex;flex-direction:column">
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;display:flex;align-items:center;gap:5px">
            <i data-lucide="file-text" style="width:11px;height:11px"></i> Descrição detalhada
          </div>
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:8px;line-height:1.5">
            ⚠ Não inclua dados de contato, links externos ou informações de pagamento. Foque em características técnicas, dimensões e usos do produto.
          </div>
          <!-- Editor Quill -->
          <div id="quill-editor"></div>
          <textarea id="f-desc" style="display:none"></textarea>
        </div>

        <!-- Compradores recentes -->
        <div id="recent-buyers-wrap" style="display:none">
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;letter-spacing:.08em;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:5px">
            <i data-lucide="users" style="width:11px;height:11px;color:#3483FA"></i> Compradores recentes
          </div>
          <div id="recent-buyers-list" style="display:flex;flex-direction:column;gap:5px;max-height:140px;overflow-y:auto"></div>
        </div>

      </div>
    </div>

    <!-- Footer do modal -->
    <div style="padding:16px 24px;border-top:0.5px solid #2E2E33;display:flex;gap:8px;justify-content:flex-end;background:#1E1E21;position:sticky;bottom:0">
      <button onclick="closeModal()" class="btn-secondary" style="font-size:12px">Cancelar</button>
      <button onclick="saveProduct()" class="btn-primary" style="font-size:12px" id="btn-save">
        <i data-lucide="save" style="width:12px;height:12px"></i> Salvar
      </button>
      <button id="btn-send-ml" onclick="sendMLFromModal()" style="display:none;padding:9px 16px;background:rgba(255,230,0,.1);border:0.5px solid #FFE600;color:#FFE600;border-radius:8px;font-size:12px;cursor:pointer;align-items:center;gap:6px;font-weight:600">
        <i data-lucide="send" style="width:12px;height:12px"></i> Publicar no ML
      </button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<style>
#quill-editor{height:280px;background:#252528;color:#E8E8E6;border:0.5px solid #2E2E33;border-radius:0 0 8px 8px;font-size:13px}
#quill-editor .ql-editor{color:#E8E8E6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;line-height:1.6}
.ql-toolbar.ql-snow{background:#1E1E21;border:0.5px solid #2E2E33;border-radius:8px 8px 0 0}
.ql-toolbar.ql-snow .ql-stroke{stroke:#9A9A96}
.ql-toolbar.ql-snow .ql-fill{fill:#9A9A96}
.ql-toolbar.ql-snow .ql-picker{color:#9A9A96}
.ql-toolbar.ql-snow button:hover .ql-stroke,.ql-toolbar.ql-snow button.ql-active .ql-stroke{stroke:#3483FA}
.ql-toolbar.ql-snow button:hover .ql-fill,.ql-toolbar.ql-snow button.ql-active .ql-fill{fill:#3483FA}
.ql-container.ql-snow{border:none}
.ql-editor.ql-blank::before{color:#5E5E5A;font-style:normal}
</style>
<script>
lucide.createIcons();

// ── Quill Editor ──────────────────────────────────────────
let quillEditor = null;

function initQuill() {
  if (quillEditor) return;
  const container = document.getElementById('quill-editor');
  if (!container) return;
  quillEditor = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Descrição detalhada — características técnicas, dimensões, usos...',
    modules: {
      toolbar: [
        [{ header: [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ align: [] }],
        ['link', 'clean']
      ]
    }
  });
  lucide.createIcons();
}

function openModalWithTiny() {
  document.getElementById('modal').style.display = 'flex';
  setTimeout(initQuill, 80);
}

function getTinyContent() {
  if (quillEditor) return quillEditor.getText().trim();
  return document.getElementById('f-desc').value;
}

function setTinyContent(text) {
  document.getElementById('f-desc').value = text || '';
  if (quillEditor) {
    quillEditor.setText(text || '');
  } else {
    setTimeout(() => { if (quillEditor) quillEditor.setText(text || ''); }, 300);
  }
}


function openNew() {
  uploadedPictures = [];
  document.getElementById('modal-title').textContent='Novo anúncio';
  ['f-id','f-title','f-sku','f-price','f-cost','f-desc','f-category','cat-search','f-gtin','f-catalog-id','f-brand','f-model','f-ipi'].forEach(id=>{
    const el=document.getElementById(id); if(el) el.value='';
  });
  document.getElementById('f-fee').value='14';
  document.getElementById('f-stock').value='0';
  document.getElementById('f-stock-min').value='5';
  document.getElementById('f-listing-type').value='gold_special';
  document.getElementById('f-condition').value='new';
  document.getElementById('f-picture-ids').value='[]';
  document.getElementById('photos-container').innerHTML='';
  document.getElementById('photo-upload-status').textContent='';
  document.getElementById('cat-results').style.display='none';
  document.getElementById('cat-selected').style.display='none';
  document.getElementById('ml-attributes').style.display='none';
  document.getElementById('form-margin').style.display='none';
  document.getElementById('btn-send-ml').style.display='none';
  document.getElementById('char-count').textContent='0/60';
  document.getElementById('recent-buyers-wrap').style.display = 'none';
  document.getElementById('recent-buyers-list').innerHTML = '';
  setTinyContent('');
  openModalWithTiny();
  lucide.createIcons();
}

// ── Compradores recentes do anúncio ───────────────────────
async function loadRecentBuyers(meliItemId) {
  const wrap = document.getElementById('recent-buyers-wrap');
  const list = document.getElementById('recent-buyers-list');
  wrap.style.display = 'none';

  const r = await fetch(`/api/crm.php?action=list&q=&status=&page=1`);
  // Busca pedidos deste item diretamente
  const r2 = await fetch(`/api/anuncios_data.php?action=buyers&item_id=${encodeURIComponent(meliItemId)}`);
  if (!r2.ok) return;
  const d = await r2.json();
  if (!d.ok || !d.buyers?.length) return;

  wrap.style.display = 'block';
  list.innerHTML = d.buyers.map(b => `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;background:#252528;border-radius:7px">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="width:24px;height:24px;border-radius:50%;background:rgba(52,131,250,.2);color:#3483FA;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">
          ${b.nickname[0].toUpperCase()}
        </div>
        <div>
          <div style="font-size:11px;color:#E8E8E6">${b.nickname}</div>
          <div style="font-size:10px;color:#5E5E5A">${b.order_date} · R$ ${parseFloat(b.total_amount||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
        </div>
      </div>
      <a href="/pages/crm.php?open=${encodeURIComponent(b.nickname)}" target="_blank"
        style="font-size:9px;padding:3px 8px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:5px;text-decoration:none;white-space:nowrap">
        Ver CRM
      </a>
    </div>`).join('');
  lucide.createIcons();
}

function openEdit(p) {
  uploadedPictures = [];
  // Carrega fotos existentes
  try {
    const pics = JSON.parse(p.picture_ids||'[]');
    pics.forEach(id => uploadedPictures.push({id, url:''}));
    renderPhotoPreview();
  } catch(e){}

  document.getElementById('modal-title').textContent='Editar anúncio';
  document.getElementById('f-id').value=p.id;
  document.getElementById('f-title').value=p.title;
  document.getElementById('f-sku').value=p.sku;
  document.getElementById('f-price').value=p.price;
  document.getElementById('f-cost').value=p.cost_price;
  document.getElementById('f-ipi').value=p.ipi_valor||0;
  document.getElementById('f-fee').value=p.ml_fee_percent;
  document.getElementById('f-stock').value=p.stock_quantity;
  document.getElementById('f-stock-min').value=p.stock_min;
  setTinyContent(p.description||'');
  document.getElementById('f-gtin').value=p.gtin||'';
  document.getElementById('f-catalog-id').value=p.catalog_product_id||'';
  document.getElementById('f-listing-type').value=p.listing_type_id||'gold_special';
  document.getElementById('f-condition').value=p.item_condition||'new';
  document.getElementById('f-picture-ids').value=p.picture_ids||'[]';
  document.getElementById('cat-results').style.display='none';
  document.getElementById('ml-attributes').style.display='none';
  if (p.category_id){document.getElementById('f-category').value=p.category_id;document.getElementById('cat-selected').innerHTML='Categoria: '+p.category_id;document.getElementById('cat-selected').style.display='block';}
  else document.getElementById('cat-selected').style.display='none';
  document.getElementById('btn-send-ml').style.display='inline-flex';
  openModalWithTiny();
  countChars(); calcFormMargin();

  // Carrega compradores recentes deste anúncio
  if (p.meli_item_id) loadRecentBuyers(p.meli_item_id);

  lucide.createIcons();
}

function closeModal(){document.getElementById('modal').style.display='none';}

// ── Busca categoria ──────────────────────────────────────
async function searchCategory() {
  const q=document.getElementById('cat-search').value.trim();
  if (!q) return;
  const btn=document.querySelector('button[onclick="searchCategory()"]');
  btn.textContent='Buscando...'; btn.disabled=true;
  const fd=new FormData(); fd.append('action','search_category'); fd.append('q',q);
  const r=await fetch('/pages/anuncios.php',{method:'POST',body:fd});
  const d=await r.json();
  btn.textContent='Buscar'; btn.disabled=false;
  const results=document.getElementById('cat-results');
  if (!d.ok||!d.categories?.length){results.innerHTML='<div style="padding:10px;font-size:12px;color:#5E5E5A;text-align:center">Nenhuma categoria encontrada</div>';results.style.display='block';return;}
  results.innerHTML=d.categories.map(c=>`<div onclick="selectCategory('${c.category_id}','${(c.category_name||c.domain_name).replace(/'/g,"\'")}')" style="padding:10px 14px;cursor:pointer;border-bottom:0.5px solid #2E2E33;font-size:12px" onmouseover="this.style.background='#252528'" onmouseout="this.style.background='transparent'"><div style="color:#E8E8E6;font-weight:500">${c.category_name||c.domain_name}</div><div style="color:#5E5E5A;font-size:10px;margin-top:2px">${c.category_id}</div></div>`).join('');
  results.style.display='block';
}

async function selectCategory(id, name) {
  document.getElementById('f-category').value=id;
  const badge = document.getElementById('cat-required-badge');
  if (badge) { badge.textContent='✓ Selecionada'; badge.style.color='#22c55e'; }
  document.getElementById('cat-results').style.display='none';
  document.getElementById('cat-selected').innerHTML=`<i data-lucide="tag" style="width:12px;height:12px;display:inline"></i> ${name} <span style="color:#5E5E5A;font-size:10px">(${id})</span>`;
  document.getElementById('cat-selected').style.display='block';
  lucide.createIcons();
  const fd=new FormData(); fd.append('action','get_attributes'); fd.append('category_id',id);
  const r=await fetch('/pages/anuncios.php',{method:'POST',body:fd});
  const d=await r.json();
  if (d.attributes?.length){
    document.getElementById('ml-attrs-list').innerHTML=d.attributes.map(a=>`<div style="display:grid;grid-template-columns:140px 1fr;align-items:center;gap:8px"><label style="font-size:11px;color:#9A9A96">${a.name} *</label>${a.values?.length?`<select name="attr_${a.id}" class="input" style="font-size:12px;padding:7px 10px"><option value="">Selecione...</option>${a.values.map(v=>`<option value="${v.id}" data-name="${v.name}">${v.name}</option>`).join('')}</select>`:`<input type="text" name="attr_${a.id}" class="input" style="font-size:12px;padding:7px 10px" placeholder="${a.name}">`}</div>`).join('');
    document.getElementById('ml-attributes').style.display='block';
  }
}

// ── Salvar produto ───────────────────────────────────────
async function saveProduct() {
  const title=document.getElementById('f-title').value.trim();
  const sku=document.getElementById('f-sku').value.trim();
  const price=document.getElementById('f-price').value;
  if (!title||!sku||!price){toast('Preencha título, SKU e preço','error');return;}

  const fd=new FormData();
  fd.append('action','save');
  fd.append('id', document.getElementById('f-id').value);
  fd.append('title', title);
  fd.append('sku', sku);
  fd.append('price', price);
  fd.append('cost_price',  document.getElementById('f-cost').value);
  fd.append('ipi_valor',   document.getElementById('f-ipi').value || '0');
  fd.append('ml_fee_percent', document.getElementById('f-fee').value);
  fd.append('stock_quantity', document.getElementById('f-stock').value);
  fd.append('stock_min', document.getElementById('f-stock-min').value);
  fd.append('description', getTinyContent());
  fd.append('category_id', document.getElementById('f-category').value);
  fd.append('listing_type_id', document.getElementById('f-listing-type').value);
  fd.append('item_condition', document.getElementById('f-condition').value);
  fd.append('gtin', document.getElementById('f-gtin').value);
  fd.append('catalog_product_id', document.getElementById('f-catalog-id').value);
  fd.append('brand', document.getElementById('f-brand')?.value || '');
  fd.append('model', document.getElementById('f-model')?.value || '');
  fd.append('picture_ids', document.getElementById('f-picture-ids').value);

  // Coleta atributos ML
  const attrs = [];
  document.querySelectorAll('#ml-attrs-list [name^="attr_"]').forEach(el => {
    const attrId = el.name.replace('attr_','');
    const val = el.value;
    if (val) {
      const opt = el.tagName === 'SELECT' ? el.options[el.selectedIndex] : null;
      attrs.push({ id: attrId, value_id: opt?.value||null, value_name: opt?.dataset.name||val });
    }
  });
  fd.append('ml_attributes', JSON.stringify(attrs));

  const btn=document.getElementById('btn-save');
  btn.disabled=true; btn.textContent='Salvando...';
  const r=await fetch('/pages/anuncios.php',{method:'POST',body:fd});
  const d=await r.json();
  btn.disabled=false; btn.textContent='Salvar';
  if (d.ok){toast('Anúncio salvo!','success'); closeModal(); refreshCharts(); setTimeout(()=>location.reload(),500);}
  else toast(d.error,'error');
}

// ── Publicar no ML ───────────────────────────────────────
async function sendML(productId) {
  const fd=new FormData(); fd.append('product_id', productId);
  const r=await fetch('/api/publish_item.php',{method:'POST',body:fd});
  const d=await r.json();
  if (d.ok) {
    toast(`✓ Publicado no ML! ${d.action==='created'?'Novo anúncio':'Atualizado'}: ${d.meli_item_id}`, 'success');
    if (d.permalink) window.open(d.permalink, '_blank');
    setTimeout(()=>location.reload(), 1000);
  } else {
    toast('Erro: '+d.error, 'error');
  }
}

async function sendMLFromModal() {
  const id = document.getElementById('f-id').value;
  if (!id) { toast('Salve o produto primeiro', 'warning'); return; }
  await sendML(id);
}

async function toggleStatus(id,btn) {
  const fd=new FormData(); fd.append('action','toggle'); fd.append('id',id);
  const r=await fetch('/pages/anuncios.php',{method:'POST',body:fd});
  const d=await r.json();
  if (d.ok){btn.textContent=d.status==='ACTIVE'?'Ativo':'Pausado';btn.className='badge '+(d.status==='ACTIVE'?'badge-green':'badge-amber');toast('Status atualizado','success');}
}

async function delProduct(id) {
  if (!await dialog({title:'Excluir anúncio',message:'Esta ação não pode ser desfeita.',confirmText:'Excluir',danger:true})) return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
  const r=await fetch('/pages/anuncios.php',{method:'POST',body:fd});
  const d=await r.json();
  if (d.ok){toast('Removido','success'); refreshCharts(); location.reload();}
}

// Configura API dinâmica
window.PAGE_DATA_API = '/api/anuncios_data.php';

// ── Inicialização dos charts ──────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Chart margem
  const ctxMargin = document.getElementById('marginChart');
  if (ctxMargin) {
    window.registerChart('margin', new Chart(ctxMargin, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [{
          label: 'Margem %',
          data: [],
          backgroundColor: 'rgba(52,131,250,.7)',
          borderRadius: 4,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color:'#5E5E5A', font:{size:10}, callback: v => v+'%' }, grid: { color:'#2E2E33' } },
          y: { ticks: { color:'#9A9A96', font:{size:10} }, grid: { display:false } }
        }
      }
    }));
  }

  // Donut status
  const ctxStatus = document.getElementById('statusDonut');
  if (ctxStatus) {
    window.registerChart('status-donut', new Chart(ctxStatus, {
      type: 'doughnut',
      data: {
        labels: ['Ativos','Pausados'],
        datasets: [{ data: [0,0], backgroundColor: ['#22c55e','#f59e0b'], borderWidth: 0, hoverOffset: 4 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '70%'
      }
    }));
  }

  // Donut saúde
  const ctxHealth = document.getElementById('healthDonut');
  if (ctxHealth) {
    window.registerChart('health-donut', new Chart(ctxHealth, {
      type: 'doughnut',
      data: {
        labels: ['Alta','Média','Baixa'],
        datasets: [{ data: [0,0,0], backgroundColor: ['#22c55e','#f59e0b','#ef4444'], borderWidth: 0, hoverOffset: 4 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '70%'
      }
    }));
  }

  // Busca dados
  window.refreshCharts();
  initPagination('tbody', 10);
});
function initPagination(tableId, perPage=10) {
  const tbody = document.getElementById(tableId);
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll('tr[data-title]'));
  if (rows.length <= perPage) return;
  let page = 1;
  const total = rows.length, pages = Math.ceil(total/perPage);
  function render() {
    rows.forEach((r,i) => r.style.display=(i>=(page-1)*perPage&&i<page*perPage)?'':'none');
    const p = document.getElementById(tableId+'-pager'); if(!p) return;
    const s=(page-1)*perPage+1, e=Math.min(page*perPage,total);
    let h=`<span style="font-size:12px;color:#5E5E5A">${s}–${e} de ${total}</span><div style="display:flex;gap:4px">`;
    h+=`<button onclick="pg(${page-1})" ${page<=1?'disabled':''} style="padding:5px 10px;border-radius:6px;border:0.5px solid #2E2E33;background:${page<=1?'transparent':'#252528'};color:${page<=1?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:12px">←</button>`;
    for(let i=Math.max(1,page-2);i<=Math.min(pages,page+2);i++) h+=`<button onclick="pg(${i})" style="padding:5px 9px;border-radius:6px;border:0.5px solid ${i===page?'#3483FA':'#2E2E33'};background:${i===page?'#3483FA':'transparent'};color:${i===page?'#fff':'#9A9A96'};cursor:pointer;font-size:12px;min-width:30px">${i}</button>`;
    h+=`<button onclick="pg(${page+1})" ${page>=pages?'disabled':''} style="padding:5px 10px;border-radius:6px;border:0.5px solid #2E2E33;background:${page>=pages?'transparent':'#252528'};color:${page>=pages?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:12px">→</button></div>`;
    p.innerHTML=h;
  }
  window.pg = p => { if(p>=1&&p<=pages){page=p;render();} };
  render();
}
document.addEventListener('DOMContentLoaded', () => { /* charts já inicializam acima */ });
window.onChartsData = function(d) {
  if (d.kpis) {
    if (document.getElementById('an-ativos-val'))    document.getElementById('an-ativos-val').textContent    = d.kpis.ativos;
    if (document.getElementById('an-ativos-delta'))  document.getElementById('an-ativos-delta').textContent  = d.kpis.pausados+' pausados';
    if (document.getElementById('an-criticos-val'))  document.getElementById('an-criticos-val').textContent  = d.kpis.criticos;
    if (document.getElementById('an-preco-val'))     document.getElementById('an-preco-val').textContent     = 'R$ '+parseFloat(d.kpis.avg_price).toFixed(2).replace('.',',');
    if (document.getElementById('an-preco-delta'))   document.getElementById('an-preco-delta').textContent   = d.kpis.total+' produtos';
    if (document.getElementById('an-estoque-val'))   document.getElementById('an-estoque-val').textContent   = 'R$ '+parseFloat(d.kpis.stock_val).toFixed(2).replace('.',',');
  }
  if (d.top_by_margin && d.top_by_margin.length) {
    updateChartData('margin',
      d.top_by_margin.map(p => p.title.length > 25 ? p.title.substring(0,25)+'…' : p.title),
      [d.top_by_margin.map(p => parseFloat(p.margin)||0)]
    );
  }
  if (d.kpis) {
    updateChartData('status-donut', null, [[d.kpis.ativos, d.kpis.pausados]]);
  }
  if (d.health_dist) {
    updateChartData('health-donut', null, [[d.health_dist.Alta, d.health_dist.Media, d.health_dist.Baixa]]);
  }
};
</script>

<?php endif; // fim aba anuncios ?>

<!-- ═══ ABA: RENOVAR ANÚNCIOS ═══ -->
<?php if ($anTab === 'renovar'): ?>
<?php
// Buscar categorias únicas dos produtos para o filtro
$categorias = db_all(
    "SELECT DISTINCT category_id, COUNT(*) as total
     FROM products
     WHERE tenant_id=? AND meli_account_id=? AND category_id IS NOT NULL AND category_id != ''
     GROUP BY category_id ORDER BY total DESC",
    [$tenantId, $acctId ?? '']
);
?>
<div style="padding:20px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Renovação Manual</h1>
      <p style="font-size:11px;color:#5E5E5A">Selecione os anúncios e renove manualmente — sem IA, execução imediata</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <button onclick="renovarTodos()" id="btn-renovar-todos" style="display:none;padding:7px 14px;background:#f59e0b;border:none;color:#1A1A1A;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;align-items:center;gap:6px">
        <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Renovar todos
      </button>
    </div>
  </div>

  <!-- Filtros -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:16px">
    <div style="font-size:11px;font-weight:500;color:#9A9A96;margin-bottom:12px;display:flex;align-items:center;gap:5px">
      <i data-lucide="sliders-horizontal" style="width:12px;height:12px"></i> Filtros de renovação
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end">

      <!-- Período -->
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Anúncios com mais de</label>
        <div style="display:flex;gap:4px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;padding:3px">
          <?php foreach ([30=>'30d', 60=>'60d', 90=>'90d', 120=>'120d'] as $d => $label): ?>
          <button onclick="setDias(<?= $d ?>)" id="dias-btn-<?= $d ?>"
            style="flex:1;padding:5px 0;border:none;border-radius:6px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;
              background:<?= $d===120?'#3483FA':'transparent' ?>;
              color:<?= $d===120?'#fff':'#5E5E5A' ?>">
            <?= $label ?>
          </button>
          <?php endforeach; ?>
        </div>
        <input type="hidden" id="dias-filter" value="120">
      </div>

      <!-- Categoria -->
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Filtrar por categoria</label>
        <select id="cat-filter" style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
          <option value="">Todas as categorias</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?= htmlspecialchars($cat['category_id']) ?>">
            <?= htmlspecialchars($cat['category_id']) ?> (<?= $cat['total'] ?> anúncios)
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Botão buscar -->
      <button onclick="buscarCandidatos()" class="btn-primary" style="font-size:12px;padding:8px 18px;white-space:nowrap">
        <i data-lucide="search" style="width:12px;height:12px"></i> Buscar
      </button>
    </div>
  </div>

  <!-- Aviso importante -->
  <div style="background:rgba(245,158,11,.06);border:0.5px solid rgba(245,158,11,.3);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:11px;color:#f59e0b;line-height:1.6">
    ⚠ <strong>Atenção:</strong> A renovação fecha o anúncio atual e cria um novo. O histórico de visitas e avaliações do anúncio antigo não é transferido. Certifique-se de que o produto tem categoria, preço e fotos antes de renovar.
  </div>

  <div id="renovar-empty" style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="refresh-cw" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Buscar anúncios para renovar</div>
    <div style="font-size:11px;color:#5E5E5A">Configure os filtros acima e clique em Buscar</div>
  </div>

  <div id="renovar-loading" style="display:none;text-align:center;padding:48px;color:#5E5E5A;font-size:13px">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    Buscando anúncios...
  </div>

  <div id="renovar-content" style="display:none"></div>
</div>

<script>
lucide.createIcons();

function calcFormMargin() {
  const preco = parseFloat(document.getElementById('f-price')?.value) || 0;
  const custo = parseFloat(document.getElementById('f-cost')?.value)  || 0;
  const fee   = parseFloat(document.getElementById('f-fee')?.value)   || 14;
  const ipi   = parseFloat(document.getElementById('f-ipi')?.value)   || 0;

  // IPI é valor fixo em R$ — soma diretamente ao custo
  const custoReal = custo + ipi;

  // Preview custo real com IPI
  const ipiPreview  = document.getElementById('ipi-preview');
  const ipiCustoEl  = document.getElementById('ipi-custo-real');
  if (ipi > 0 && custo > 0 && ipiPreview && ipiCustoEl) {
    ipiPreview.style.display = 'block';
    ipiCustoEl.textContent   = 'R$ ' + custoReal.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' (custo + IPI da NF)';
  } else if (ipiPreview) {
    ipiPreview.style.display = 'none';
  }

  if (!preco || !custoReal) {
    document.getElementById('form-margin').style.display = 'none';
    return;
  }

  const comissao = preco * (fee / 100);
  const lucro    = preco - custoReal - comissao;
  const margem   = (lucro / preco) * 100;
  const color    = margem >= 20 ? '#22c55e' : margem >= 10 ? '#f59e0b' : '#ef4444';

  const fmt = v => 'R$ ' + Math.abs(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});

  document.getElementById('form-margin').style.display = 'flex';
  document.getElementById('fm-margin').textContent     = margem.toFixed(1) + '%';
  document.getElementById('fm-margin').style.color     = color;
  document.getElementById('fm-profit').textContent     = fmt(lucro);
  document.getElementById('fm-profit').style.color     = lucro >= 0 ? '#22c55e' : '#ef4444';
}


  document.getElementById('dias-filter').value = d;
  [30,60,90,120].forEach(v => {
    const btn = document.getElementById('dias-btn-'+v);
    if (!btn) return;
    btn.style.background = v===d ? '#3483FA' : 'transparent';
    btn.style.color      = v===d ? '#fff'    : '#5E5E5A';
  });
}

async function buscarCandidatos() {
  const dias = document.getElementById('dias-filter').value;
  const cat  = document.getElementById('cat-filter')?.value || '';
  document.getElementById('renovar-empty').style.display   = 'none';
  document.getElementById('renovar-loading').style.display = 'block';
  document.getElementById('renovar-content').style.display = 'none';
  document.getElementById('btn-renovar-todos').style.display = 'none';

  try {
    const url = `/api/renovar_anuncios.php?action=listar&dias=${dias}${cat?'&category_id='+encodeURIComponent(cat):''}`;
    const r = await fetch(url);
    const d = await r.json();

    document.getElementById('renovar-loading').style.display = 'none';
    const el = document.getElementById('renovar-content');
    el.style.display = 'block';

    if (!d.ok || !d.candidates?.length) {
      el.innerHTML = `
        <div style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
          <i data-lucide="check-circle" style="width:32px;height:32px;color:#22c55e;margin:0 auto 12px;display:block"></i>
          <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhum anúncio para renovar</div>
          <div style="font-size:11px;color:#5E5E5A">Não há anúncios com mais de ${dias} dias</div>
        </div>`;
      lucide.createIcons();
      return;
    }

    // Mostra botão renovar todos
    const btnTodos = document.getElementById('btn-renovar-todos');
    btnTodos.style.display = 'flex';
    btnTodos.setAttribute('data-dias', dias);

    let html = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <span style="font-size:12px;color:#9A9A96">${d.total} anúncio${d.total!==1?'s':''} com mais de ${dias} dias</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">`;

    for (const p of d.candidates) {
      const statusColor = p.ml_status === 'ACTIVE' ? '#22c55e' : '#f59e0b';
      html += `
        <div id="card-${p.id}" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px">
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px">${p.title}</div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <span style="font-size:10px;padding:1px 7px;border-radius:8px;background:${statusColor}15;color:${statusColor}">${p.ml_status}</span>
              <span style="font-size:10px;color:#5E5E5A">${p.dias_ativo} dias</span>
              <span style="font-size:10px;color:#5E5E5A">R$ ${parseFloat(p.price).toLocaleString('pt-BR',{minimumFractionDigits:2})}</span>
              <span style="font-size:10px;color:#5E5E5A">${p.stock_quantity} em estoque</span>
              ${p.ml_visits ? `<span style="font-size:10px;color:#5E5E5A">${p.ml_visits} visitas</span>` : ''}
              ${p.ml_health ? `<span style="font-size:10px;color:${p.ml_health >= 80?'#22c55e':p.ml_health>=50?'#f59e0b':'#ef4444'}">${p.ml_health}% saúde</span>` : ''}
            </div>
          </div>
          <button onclick="renovarUm('${p.id}', this)"
            style="padding:7px 14px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;color:#3483FA;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0;white-space:nowrap">
            <i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Renovar
          </button>
        </div>`;
    }
    html += `</div>`;
    el.innerHTML = html;
    lucide.createIcons();

  } catch(e) {
    document.getElementById('renovar-loading').style.display = 'none';
    document.getElementById('renovar-content').style.display = 'block';
    document.getElementById('renovar-content').innerHTML = `<div style="text-align:center;padding:32px;color:#ef4444;font-size:12px">Erro ao buscar anúncios</div>`;
  }
}

async function renovarUm(productId, btn) {
  const originalHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:11px;height:11px;animation:spin 1s linear infinite"></i> Renovando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action', 'renovar');
  fd.append('product_id', productId);

  try {
    const r = await fetch('/api/renovar_anuncios.php', {method:'POST', body:fd});
    const d = await r.json();

    if (d.ok && d.renovados > 0) {
      toast('Anúncio renovado com sucesso!', 'success');
      const card = document.getElementById('card-' + productId);
      if (card) {
        card.style.opacity = '0.4';
        card.style.pointerEvents = 'none';
        btn.innerHTML = '✓ Renovado';
        btn.style.background = 'rgba(34,197,94,.1)';
        btn.style.borderColor = '#22c55e';
        btn.style.color = '#22c55e';
      }
    } else {
      const erro = d.erros?.[0] || 'Erro ao renovar';
      toast(erro, 'error');
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      lucide.createIcons();
    }
  } catch(e) {
    toast('Erro de conexão', 'error');
    btn.disabled = false;
    btn.innerHTML = originalHtml;
    lucide.createIcons();
  }
}

async function renovarTodos() {
  const btn  = document.getElementById('btn-renovar-todos');
  const dias = btn.getAttribute('data-dias') || 120;
  const cards = document.querySelectorAll('[id^="card-"]');

  if (!await dialog({title:'Renovar Anúncios',message:`Renovar <strong>${cards.length}</strong> anúncio(s)?<br>Esta ação fechará os anúncios atuais e criará novos.`,confirmText:'Renovar',danger:true})) return;

  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Renovando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action', 'renovar_todos');
  fd.append('dias', dias);

  try {
    const r = await fetch('/api/renovar_anuncios.php', {method:'POST', body:fd});
    const d = await r.json();

    if (d.ok) {
      toast(`${d.renovados} anúncio(s) renovado(s)${d.erros.length?` · ${d.erros.length} erro(s)`:''}`, d.erros.length?'warning':'success');
      setTimeout(() => buscarCandidatos(), 1500);
    } else {
      toast(d.error || 'Erro ao renovar', 'error');
    }
  } catch(e) {
    toast('Erro de conexão', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Renovar todos';
  lucide.createIcons();
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
<?php endif; // fim aba renovar ?>

<?php include __DIR__ . '/layout_end.php'; ?>
