<?php
/**
 * pages/precos_massa.php
 * Alteração de preços em massa nos anúncios
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

// POST: aplicar preços
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'preview') {
        $tipo      = $_POST['tipo']      ?? 'percentual';
        $valor     = (float)($_POST['valor'] ?? 0);
        $operacao  = $_POST['operacao']  ?? 'aumentar';
        $minFilter = (float)($_POST['min_price'] ?? 0);
        $maxFilter = (float)($_POST['max_price'] ?? 0);
        $ids       = json_decode($_POST['ids'] ?? '[]', true) ?: [];

        if ($valor <= 0) { echo json_encode(['ok'=>false,'error'=>'Valor deve ser maior que zero']); exit; }

        $acctSql = $acctId ? " AND meli_account_id=?" : "";
        $acctP   = $acctId ? [$acctId] : [];

        $idsSql = '';
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $idsSql = " AND id IN ({$placeholders})";
        }

        $priceSql = '';
        $priceP   = [];
        if ($minFilter > 0) { $priceSql .= " AND price >= ?"; $priceP[] = $minFilter; }
        if ($maxFilter > 0) { $priceSql .= " AND price <= ?"; $priceP[] = $maxFilter; }

        $products = db_all(
            "SELECT id, meli_item_id, title, price, ml_status
             FROM products
             WHERE tenant_id=?{$acctSql}{$idsSql}{$priceSql}
               AND meli_item_id IS NOT NULL
               AND ml_status IN ('ACTIVE','PAUSED')
             ORDER BY title ASC LIMIT 200",
            array_merge([$tenantId], $acctP, $ids, $priceP)
        );

        $preview = array_map(function($p) use ($tipo, $valor, $operacao) {
            $old = (float)$p['price'];
            if ($tipo === 'percentual') {
                $delta = $old * ($valor / 100);
            } else {
                $delta = $valor;
            }
            $new = $operacao === 'aumentar' ? $old + $delta : $old - $delta;
            $new = max(0.01, round($new, 2));
            return [
                'id'           => $p['id'],
                'meli_item_id' => $p['meli_item_id'],
                'title'        => $p['title'],
                'old_price'    => $old,
                'new_price'    => $new,
                'diff'         => round($new - $old, 2),
                'ml_status'    => $p['ml_status'],
            ];
        }, $products);

        echo json_encode(['ok'=>true, 'preview'=>$preview, 'total'=>count($preview)]);
        exit;
    }

    if ($action === 'apply') {
        $items = json_decode($_POST['items'] ?? '[]', true) ?: [];
        if (empty($items)) { echo json_encode(['ok'=>false,'error'=>'Nenhum item para atualizar']); exit; }

        $acct = $acctId ? db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId]) : null;
        if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada']); exit; }

        $token   = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);
        $success = 0;
        $errors  = [];

        foreach ($items as $item) {
            $itemId  = $item['meli_item_id'] ?? '';
            $newPrice = (float)($item['new_price'] ?? 0);
            $localId  = $item['id'] ?? '';

            if (!$itemId || $newPrice <= 0) continue;

            $result = curl_ml("https://api.mercadolibre.com/items/{$itemId}", [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS    => json_encode(['price' => $newPrice]),
                CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
                CURLOPT_TIMEOUT       => 10,
            ]);

            if ($result['code'] === 200) {
                if ($localId) db_update('products', ['price'=>$newPrice], 'id=? AND tenant_id=?', [$localId,$tenantId]);
                audit_log('PRICE_MASS_UPDATE', 'products', $localId, ['price'=>$item['old_price']], ['price'=>$newPrice]);
                $success++;
            } else {
                $err = json_decode($result['body'], true)['message'] ?? "HTTP {$result['code']}";
                $errors[] = "{$item['title']}: {$err}";
            }
        }

        echo json_encode(['ok'=>true, 'success'=>$success, 'errors'=>$errors]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

$title = 'Preços em Massa';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="margin-bottom:20px">
    <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Alterar Preços em Massa</h1>
    <p style="font-size:11px;color:#5E5E5A">Aumente ou diminua preços de vários anúncios ao mesmo tempo</p>
  </div>

  <!-- Configuração -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

    <!-- Tipo de alteração -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px">
      <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:12px;display:flex;align-items:center;gap:6px">
        <i data-lucide="percent" style="width:13px;height:13px;color:#FFE600"></i>
        Tipo de alteração
      </div>
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#E8E8E6">
          <input type="radio" name="tipo" value="percentual" checked onchange="updateTipoLabel()" style="accent-color:#3483FA"> Percentual (%)
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#E8E8E6">
          <input type="radio" name="tipo" value="fixo" onchange="updateTipoLabel()" style="accent-color:#3483FA"> Valor fixo (R$)
        </label>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <div style="display:flex;gap:8px">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#22c55e">
            <input type="radio" name="operacao" value="aumentar" checked style="accent-color:#22c55e"> ↑ Aumentar
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;color:#ef4444">
            <input type="radio" name="operacao" value="diminuir" style="accent-color:#ef4444"> ↓ Diminuir
          </label>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex:1">
          <input type="number" id="valor-input" min="0.01" step="0.01" placeholder="0"
            style="flex:1;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:14px;font-weight:600;outline:none;text-align:center">
          <span id="tipo-label" style="font-size:13px;color:#5E5E5A;font-weight:600">%</span>
        </div>
      </div>
    </div>

    <!-- Filtros de preço -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px">
      <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:12px;display:flex;align-items:center;gap:6px">
        <i data-lucide="filter" style="width:13px;height:13px;color:#3483FA"></i>
        Filtrar por faixa de preço <span style="font-size:10px;color:#5E5E5A">(opcional)</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
        <div>
          <label style="display:block;font-size:10px;color:#5E5E5A;margin-bottom:4px">Preço mínimo (R$)</label>
          <input type="number" id="min-price" min="0" step="0.01" placeholder="0,00"
            style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>
        <div>
          <label style="display:block;font-size:10px;color:#5E5E5A;margin-bottom:4px">Preço máximo (R$)</label>
          <input type="number" id="max-price" min="0" step="0.01" placeholder="Sem limite"
            style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>
      </div>
      <button onclick="previewPrices()" class="btn-primary" style="width:100%;justify-content:center;font-size:12px">
        <i data-lucide="eye" style="width:12px;height:12px"></i> Visualizar alterações
      </button>
    </div>
  </div>

  <!-- Preview -->
  <div id="preview-section" style="display:none">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6">
        <span id="preview-count">0</span> anúncios serão alterados
      </div>
      <div style="display:flex;gap:8px">
        <button onclick="selectAll(true)" class="btn-secondary" style="font-size:11px;padding:6px 12px">
          <i data-lucide="check-square" style="width:11px;height:11px"></i> Todos
        </button>
        <button onclick="selectAll(false)" class="btn-secondary" style="font-size:11px;padding:6px 12px">
          <i data-lucide="square" style="width:11px;height:11px"></i> Nenhum
        </button>
        <button onclick="applyPrices()" id="apply-btn" class="btn-primary" style="font-size:12px">
          <i data-lucide="check" style="width:12px;height:12px"></i> Aplicar selecionados
        </button>
      </div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="border-bottom:0.5px solid #2E2E33">
              <th style="padding:10px 14px;text-align:left;width:32px">
                <input type="checkbox" id="select-all-cb" onchange="selectAll(this.checked)" style="accent-color:#3483FA">
              </th>
              <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Anúncio</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500;white-space:nowrap">Preço atual</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500;white-space:nowrap">Novo preço</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Diferença</th>
            </tr>
          </thead>
          <tbody id="preview-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Loading -->
  <div id="preview-loading" style="display:none;text-align:center;padding:40px;color:#5E5E5A">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    Calculando preços...
  </div>
</div>

<script>
lucide.createIcons();
let previewData = [];

function updateTipoLabel() {
  const tipo = document.querySelector('input[name="tipo"]:checked').value;
  document.getElementById('tipo-label').textContent = tipo === 'percentual' ? '%' : 'R$';
}

async function previewPrices() {
  const valor = parseFloat(document.getElementById('valor-input').value);
  if (!valor || valor <= 0) { toast('Informe um valor válido', 'error'); return; }

  document.getElementById('preview-section').style.display = 'none';
  document.getElementById('preview-loading').style.display = 'block';

  const fd = new FormData();
  fd.append('action',    'preview');
  fd.append('tipo',      document.querySelector('input[name="tipo"]:checked').value);
  fd.append('valor',     valor);
  fd.append('operacao',  document.querySelector('input[name="operacao"]:checked').value);
  fd.append('min_price', document.getElementById('min-price').value || '0');
  fd.append('max_price', document.getElementById('max-price').value || '0');

  const r = await fetch('/pages/precos_massa.php', {method:'POST', body:fd});
  const d = await r.json();

  document.getElementById('preview-loading').style.display = 'none';

  if (!d.ok) { toast(d.error || 'Erro', 'error'); return; }
  if (!d.preview?.length) { toast('Nenhum anúncio encontrado para os filtros', 'warning'); return; }

  previewData = d.preview;
  document.getElementById('preview-count').textContent = d.total;

  const tbody = document.getElementById('preview-tbody');
  tbody.innerHTML = d.preview.map(p => {
    const diffColor = p.diff > 0 ? '#22c55e' : '#ef4444';
    const diffSign  = p.diff > 0 ? '+' : '';
    return `<tr style="border-bottom:0.5px solid #2E2E33" data-id="${p.id}">
      <td style="padding:10px 14px">
        <input type="checkbox" checked class="item-cb" data-id="${p.id}" style="accent-color:#3483FA">
      </td>
      <td style="padding:10px 14px;color:#E8E8E6;max-width:300px">
        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${p.title}</div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:1px">${p.meli_item_id}</div>
      </td>
      <td style="padding:10px 14px;text-align:right;color:#9A9A96;white-space:nowrap">R$ ${p.old_price.toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
      <td style="padding:10px 14px;text-align:right;font-weight:600;color:#E8E8E6;white-space:nowrap">R$ ${p.new_price.toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
      <td style="padding:10px 14px;text-align:right;color:${diffColor};white-space:nowrap">${diffSign}R$ ${Math.abs(p.diff).toLocaleString('pt-BR',{minimumFractionDigits:2})}</td>
    </tr>`;
  }).join('');

  document.getElementById('preview-section').style.display = 'block';
  lucide.createIcons();
}

function selectAll(checked) {
  document.querySelectorAll('.item-cb').forEach(cb => cb.checked = checked);
  const selectAllCb = document.getElementById('select-all-cb');
  if (selectAllCb) selectAllCb.checked = checked;
}

async function applyPrices() {
  const selected = [...document.querySelectorAll('.item-cb:checked')].map(cb => cb.dataset.id);
  if (!selected.length) { toast('Selecione pelo menos um anúncio', 'error'); return; }

  const items = previewData.filter(p => selected.includes(p.id));
  if (!await dialog({title:'Alterar Preços',message:`Alterar preço de <strong>${items.length}</strong> anúncio(s)?<br>Esta ação não pode ser desfeita.`,confirmText:'Alterar',danger:true})) return;

  const btn = document.getElementById('apply-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Aplicando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('action', 'apply');
  fd.append('items', JSON.stringify(items));

  const r = await fetch('/pages/precos_massa.php', {method:'POST', body:fd});
  const d = await r.json();

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="check" style="width:12px;height:12px"></i> Aplicar selecionados';
  lucide.createIcons();

  if (d.ok) {
    toast(`${d.success} preço(s) atualizado(s)${d.errors?.length ? ` · ${d.errors.length} erro(s)` : ''}`, d.errors?.length ? 'warning' : 'success');
    if (d.errors?.length) {
      console.warn('Erros:', d.errors);
    }
    // Atualiza a tabela removendo os itens aplicados
    selected.forEach(id => {
      const row = document.querySelector(`tr[data-id="${id}"]`);
      if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
    });
  } else {
    toast(d.error || 'Erro ao aplicar', 'error');
  }
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
