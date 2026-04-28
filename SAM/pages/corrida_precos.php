<?php
/**
 * pages/corrida_precos.php
 * Corrida de Preços — vincula seu preço ao concorrente automaticamente
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

// Criar tabela de regras se não existir
try {
    db_query("CREATE TABLE IF NOT EXISTS price_rules (
        id               VARCHAR(36)   NOT NULL,
        tenant_id        VARCHAR(36)   NOT NULL,
        meli_account_id  VARCHAR(36)   NULL,
        product_id       VARCHAR(36)   NOT NULL,
        rule_type        ENUM('beat_lowest','match_lowest','beat_by_value','beat_by_percent') NOT NULL DEFAULT 'beat_lowest',
        value            DECIMAL(10,2) NOT NULL DEFAULT 0,
        min_price        DECIMAL(12,2) NOT NULL DEFAULT 0,
        max_price        DECIMAL(12,2) NOT NULL DEFAULT 0,
        is_active        TINYINT       NOT NULL DEFAULT 1,
        last_run         DATETIME      NULL,
        last_price_set   DECIMAL(12,2) NULL,
        created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_product (product_id),
        KEY idx_tenant (tenant_id),
        KEY idx_active (tenant_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_rule') {
        $productId = $_POST['product_id'] ?? '';
        $ruleType  = in_array($_POST['rule_type']??'', ['beat_lowest','match_lowest','beat_by_value','beat_by_percent'])
            ? $_POST['rule_type'] : 'beat_lowest';
        $value     = (float)($_POST['value'] ?? 0);
        $minPrice  = (float)($_POST['min_price'] ?? 0);
        $maxPrice  = (float)($_POST['max_price'] ?? 0);

        if (!$productId) { echo json_encode(['ok'=>false,'error'=>'Produto obrigatório']); exit; }
        if ($maxPrice > 0 && $minPrice > $maxPrice) { echo json_encode(['ok'=>false,'error'=>'Preço mínimo maior que máximo']); exit; }

        $existing = db_one("SELECT id FROM price_rules WHERE product_id=? AND tenant_id=?", [$productId, $tenantId]);
        if ($existing) {
            db_update('price_rules',
                ['rule_type'=>$ruleType,'value'=>$value,'min_price'=>$minPrice,'max_price'=>$maxPrice,'is_active'=>1],
                'id=? AND tenant_id=?', [$existing['id'], $tenantId]);
        } else {
            db_insert('price_rules', [
                'id' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff)),
                'tenant_id'       => $tenantId,
                'meli_account_id' => $acctId,
                'product_id'      => $productId,
                'rule_type'       => $ruleType,
                'value'           => $value,
                'min_price'       => $minPrice,
                'max_price'       => $maxPrice,
            ]);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'toggle_rule') {
        $id     = $_POST['id'] ?? '';
        $active = (int)($_POST['active'] ?? 0);
        db_update('price_rules', ['is_active'=>$active], 'id=? AND tenant_id=?', [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'delete_rule') {
        $id = $_POST['id'] ?? '';
        db_query("DELETE FROM price_rules WHERE id=? AND tenant_id=?", [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'run_now') {
        // Executa a corrida de preços manualmente
        require_once dirname(__DIR__) . '/api/run_price_rules.php';
        $result = run_price_rules($tenantId, $acctId);
        echo json_encode(['ok'=>true] + $result);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

// Busca regras ativas com dados do produto
$rules = db_all(
    "SELECT pr.*, p.title, p.price as current_price, p.meli_item_id
     FROM price_rules pr
     JOIN products p ON p.id = pr.product_id
     WHERE pr.tenant_id=? ORDER BY pr.created_at DESC",
    [$tenantId]
);

// Busca produtos para o seletor
$products = db_all(
    "SELECT id, title, price, meli_item_id FROM products
     WHERE tenant_id=?{$acctSql} AND ml_status='ACTIVE' AND meli_item_id IS NOT NULL
     ORDER BY title ASC LIMIT 200",
    array_merge([$tenantId], $acctP)
);

$ruleLabels = [
    'beat_lowest'     => 'Bater o menor preço',
    'match_lowest'    => 'Igualar o menor preço',
    'beat_by_value'   => 'Bater por valor fixo (R$)',
    'beat_by_percent' => 'Bater por percentual (%)',
];

$title = 'Corrida de Preços';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Corrida de Preços</h1>
      <p style="font-size:11px;color:#5E5E5A">Vincule seu preço ao concorrente — atualização automática via cron diário</p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="runNow()" class="btn-secondary" style="font-size:12px">
        <i data-lucide="play" style="width:12px;height:12px"></i> Executar agora
      </button>
      <button onclick="openRuleModal()" class="btn-primary" style="font-size:12px">
        <i data-lucide="plus" style="width:12px;height:12px"></i> Nova regra
      </button>
    </div>
  </div>

  <!-- Info -->
  <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:10px;padding:12px 14px;margin-bottom:20px;font-size:11px;color:#3483FA;line-height:1.6">
    💡 O sistema busca o menor preço de concorrentes via catálogo do ML e ajusta seu preço automaticamente dentro dos limites configurados.
    As regras são executadas todo dia às 08:00 via cron.
  </div>

  <?php if (empty($rules)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="trending-down" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma regra configurada</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Crie uma regra para automatizar a precificação</div>
    <button onclick="openRuleModal()" class="btn-primary" style="font-size:12px">
      <i data-lucide="plus" style="width:12px;height:12px"></i> Criar primeira regra
    </button>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:10px">
    <?php foreach ($rules as $rule):
      $isActive  = (bool)$rule['is_active'];
      $ruleLabel = $ruleLabels[$rule['rule_type']] ?? $rule['rule_type'];
      $valueStr  = '';
      if (in_array($rule['rule_type'], ['beat_by_value']))   $valueStr = ' de R$ '.number_format($rule['value'],2,',','.');
      if (in_array($rule['rule_type'], ['beat_by_percent'])) $valueStr = ' de '.$rule['value'].'%';
    ?>
    <div style="background:#1A1A1C;border:0.5px solid <?= $isActive?'rgba(34,197,94,.3)':'#2E2E33' ?>;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px">
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px">
            <?= htmlspecialchars($rule['title'] ?? '—') ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span style="font-size:10px;padding:2px 8px;border-radius:6px;background:rgba(52,131,250,.1);color:#3483FA">
              <?= $ruleLabel . $valueStr ?>
            </span>
            <?php if ($rule['min_price'] > 0): ?>
            <span style="font-size:10px;color:#5E5E5A">Mín: R$ <?= number_format($rule['min_price'],2,',','.') ?></span>
            <?php endif; ?>
            <?php if ($rule['max_price'] > 0): ?>
            <span style="font-size:10px;color:#5E5E5A">Máx: R$ <?= number_format($rule['max_price'],2,',','.') ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
          <!-- Toggle -->
          <label style="position:relative;display:inline-block;width:36px;height:20px;cursor:pointer">
            <input type="checkbox" <?= $isActive?'checked':'' ?> onchange="toggleRule('<?= $rule['id'] ?>',this.checked)"
              style="opacity:0;width:0;height:0">
            <span style="position:absolute;inset:0;background:<?= $isActive?'#22c55e':'#3E3E45' ?>;border-radius:10px;transition:.3s"></span>
            <span style="position:absolute;left:<?= $isActive?'18px':'2px' ?>;top:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.3s"></span>
          </label>
          <button onclick="deleteRule('<?= $rule['id'] ?>')"
            style="padding:4px 8px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;cursor:pointer">
            <i data-lucide="trash-2" style="width:11px;height:11px"></i>
          </button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:16px;font-size:11px;color:#5E5E5A">
        <span>Preço atual: <strong style="color:#E8E8E6">R$ <?= number_format($rule['current_price'],2,',','.') ?></strong></span>
        <?php if ($rule['last_price_set']): ?>
        <span>Último ajuste: <strong style="color:#3483FA">R$ <?= number_format($rule['last_price_set'],2,',','.') ?></strong></span>
        <?php endif; ?>
        <?php if ($rule['last_run']): ?>
        <span>Executado: <?= date('d/m H:i', strtotime($rule['last_run'])) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal nova regra -->
<div id="rule-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:480px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <i data-lucide="trending-down" style="width:16px;height:16px;color:#3483FA"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Nova Regra de Preço</span>
      <button onclick="closeRuleModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:18px">✕</button>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px">Anúncio</label>
        <select id="rule-product" style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
          <option value="">Selecione um anúncio...</option>
          <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
            <?= htmlspecialchars(mb_substr($p['title'],0,50)) ?> — R$ <?= number_format($p['price'],2,',','.') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px">Tipo de regra</label>
        <select id="rule-type" onchange="toggleValueField()" style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
          <?php foreach ($ruleLabels as $k=>$v): ?>
          <option value="<?= $k ?>"><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="rule-value-wrap" style="display:none">
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px" id="rule-value-label">Valor</label>
        <input type="number" id="rule-value" step="0.01" min="0" placeholder="0"
          style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px">Preço mínimo (R$)</label>
          <input type="number" id="rule-min" step="0.01" min="0" placeholder="0 = sem limite"
            style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:6px">Preço máximo (R$)</label>
          <input type="number" id="rule-max" step="0.01" min="0" placeholder="0 = sem limite"
            style="width:100%;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>
      </div>
    </div>

    <div style="display:flex;gap:8px;margin-top:20px">
      <button onclick="saveRule()" class="btn-primary" style="flex:1">
        <i data-lucide="save" style="width:12px;height:12px"></i> Salvar regra
      </button>
      <button onclick="closeRuleModal()" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function openRuleModal() {
  document.getElementById('rule-modal').style.display = 'flex';
}
function closeRuleModal() {
  document.getElementById('rule-modal').style.display = 'none';
}

function toggleValueField() {
  const type = document.getElementById('rule-type').value;
  const wrap = document.getElementById('rule-value-wrap');
  const lbl  = document.getElementById('rule-value-label');
  if (type === 'beat_by_value') {
    wrap.style.display = 'block';
    lbl.textContent = 'Quanto abaixo do menor preço (R$)';
  } else if (type === 'beat_by_percent') {
    wrap.style.display = 'block';
    lbl.textContent = 'Quanto abaixo do menor preço (%)';
  } else {
    wrap.style.display = 'none';
  }
}

async function saveRule() {
  const productId = document.getElementById('rule-product').value;
  const ruleType  = document.getElementById('rule-type').value;
  const value     = document.getElementById('rule-value').value || 0;
  const minPrice  = document.getElementById('rule-min').value || 0;
  const maxPrice  = document.getElementById('rule-max').value || 0;

  if (!productId) { toast('Selecione um anúncio', 'error'); return; }

  const fd = new FormData();
  fd.append('action',     'save_rule');
  fd.append('product_id', productId);
  fd.append('rule_type',  ruleType);
  fd.append('value',      value);
  fd.append('min_price',  minPrice);
  fd.append('max_price',  maxPrice);

  const r = await fetch('/pages/corrida_precos.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast('Regra salva!', 'success');
    closeRuleModal();
    setTimeout(() => location.reload(), 1000);
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}

async function toggleRule(id, active) {
  const fd = new FormData();
  fd.append('action', 'toggle_rule');
  fd.append('id',     id);
  fd.append('active', active ? '1' : '0');
  const r = await fetch('/pages/corrida_precos.php', {method:'POST', body:fd});
  const d = await r.json();
  if (!d.ok) toast('Erro ao atualizar', 'error');
  else toast(active ? 'Regra ativada' : 'Regra pausada', active ? 'success' : 'info');
}

async function deleteRule(id) {
  if (!await dialog({title:'Excluir Regra',message:'Deseja excluir esta regra de preço?',confirmText:'Excluir',danger:true})) return;
  const fd = new FormData();
  fd.append('action', 'delete_rule');
  fd.append('id', id);
  const r = await fetch('/pages/corrida_precos.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Regra excluída', 'info'); location.reload(); }
}

async function runNow() {
  if (!await dialog({title:'Executar Corrida',message:'Executar a corrida de preços agora para todos os anúncios com regras ativas?',confirmText:'Executar'})) return;
  const fd = new FormData(); fd.append('action', 'run_now');
  const r = await fetch('/pages/corrida_precos.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast(`Executado: ${d.updated||0} preço(s) ajustado(s)`, 'success');
    setTimeout(() => location.reload(), 1500);
  } else {
    toast(d.error || 'Erro', 'error');
  }
}

document.getElementById('rule-modal').addEventListener('click', function(e) {
  if (e.target === this) closeRuleModal();
});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
