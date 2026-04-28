<?php
/**
 * pages/renovacoes.php
 * Painel de acompanhamento das renovações automáticas de anúncios.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

$acctSql = $acctId ? " AND rl.meli_account_id=?" : "";
$acctP   = $acctId ? [$acctId] : [];

// Cria tabela automaticamente se não existir
try {
    db_query("CREATE TABLE IF NOT EXISTS renovacoes_log (
        id               VARCHAR(36)   NOT NULL,
        tenant_id        VARCHAR(36)   NOT NULL,
        meli_account_id  VARCHAR(36)   NULL,
        product_id       VARCHAR(36)   NOT NULL,
        product_title    VARCHAR(255)  NULL,
        old_item_id      VARCHAR(30)   NULL,
        new_item_id      VARCHAR(30)   NULL,
        dias_ativo       INT           NULL,
        status           ENUM('SUCCESS','FAILED','SKIPPED') NOT NULL DEFAULT 'FAILED',
        error_message    TEXT          NULL,
        gemini_changes   JSON          NULL,
        created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant  (tenant_id),
        KEY idx_status  (tenant_id, status),
        KEY idx_date    (tenant_id, created_at),
        KEY idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// KPIs
$totalRenovados = 0; $totalErros = 0; $hoje = 0; $proximosRenovar = 0; $logs = [];
try {
$totalRenovados = (int)(db_one(
    "SELECT COUNT(*) as cnt FROM renovacoes_log WHERE tenant_id=?{$acctSql} AND status='SUCCESS'",
    array_merge([$tenantId], $acctP)
)['cnt'] ?? 0);

$totalErros = (int)(db_one(
    "SELECT COUNT(*) as cnt FROM renovacoes_log WHERE tenant_id=?{$acctSql} AND status='FAILED'",
    array_merge([$tenantId], $acctP)
)['cnt'] ?? 0);

$hoje = (int)(db_one(
    "SELECT COUNT(*) as cnt FROM renovacoes_log WHERE tenant_id=?{$acctSql} AND status='SUCCESS' AND DATE(created_at)=CURDATE()",
    array_merge([$tenantId], $acctP)
)['cnt'] ?? 0);
} catch (Throwable $e) {}

// Próximos a renovar — sem JOIN, sem ambiguidade
$proxAcctSql = $acctId ? " AND meli_account_id=?" : "";
try {
$proximosRenovar = (int)(db_one(
    "SELECT COUNT(*) as cnt FROM products
     WHERE tenant_id=?{$proxAcctSql}
       AND meli_item_id IS NOT NULL
       AND ml_status IN ('ACTIVE','PAUSED')
       AND DATEDIFF(NOW(), created_at) >= 120
       AND category_id IS NOT NULL AND price > 0",
    array_merge([$tenantId], $acctP)
)['cnt'] ?? 0);
} catch (Throwable $e) {}

// Filtro
$filtro    = in_array($_GET['status'] ?? 'all', ['all','SUCCESS','FAILED']) ? ($_GET['status'] ?? 'all') : 'all';
$statusSql = $filtro !== 'all' ? " AND rl.status=?" : "";
$statusP   = $filtro !== 'all' ? [$filtro] : [];

// Log recente — prefixar todas as colunas ambíguas
try {
$logs = db_all(
    "SELECT rl.id, rl.product_id, rl.product_title, rl.old_item_id, rl.new_item_id,
            rl.dias_ativo, rl.status, rl.error_message, rl.gemini_changes, rl.created_at
     FROM renovacoes_log rl
     WHERE rl.tenant_id=?{$acctSql}{$statusSql}
     ORDER BY rl.created_at DESC
     LIMIT 100",
    array_merge([$tenantId], $acctP, $statusP)
);
} catch (Throwable $e) { $logs = []; }

$title = 'Renovação Automática';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <!-- Cabeçalho -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Renovação Automática</h1>
      <p style="font-size:11px;color:#5E5E5A">Log de anúncios renovados automaticamente pelo cron às 06:00 — validados por IA</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;border-radius:8px">
        <i data-lucide="bot" style="width:12px;height:12px;color:#22c55e"></i>
        <span style="font-size:11px;font-weight:600;color:#22c55e">Cron 06:00 diário</span>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid rgba(34,197,94,.3);border-radius:10px;padding:14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Renovados hoje</div>
      <div style="font-size:24px;font-weight:700;color:#22c55e"><?= $hoje ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Total renovados</div>
      <div style="font-size:24px;font-weight:700;color:#E8E8E6"><?= $totalRenovados ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid rgba(239,68,68,.3);border-radius:10px;padding:14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Com erro</div>
      <div style="font-size:24px;font-weight:700;color:#ef4444"><?= $totalErros ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid rgba(245,158,11,.3);border-radius:10px;padding:14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Aguardando renovar</div>
      <div style="font-size:24px;font-weight:700;color:#f59e0b"><?= $proximosRenovar ?></div>
      <div style="font-size:9px;color:#5E5E5A;margin-top:2px">120+ dias</div>
    </div>
  </div>

  <!-- Filtros -->
  <div style="display:flex;gap:6px;margin-bottom:16px">
    <?php foreach (['all'=>'Todos','SUCCESS'=>'Sucesso','FAILED'=>'Com erro'] as $k=>$label): ?>
    <a href="?status=<?= $k ?>" style="padding:6px 14px;border-radius:8px;font-size:11px;font-weight:500;text-decoration:none;border:0.5px solid <?= $filtro===$k?'#3483FA':'#2E2E33' ?>;background:<?= $filtro===$k?'rgba(52,131,250,.1)':'transparent' ?>;color:<?= $filtro===$k?'#3483FA':'#5E5E5A' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Log -->
  <?php if (empty($logs)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="refresh-cw" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma renovação ainda</div>
    <div style="font-size:11px;color:#5E5E5A">O processo roda automaticamente todo dia às 06:00</div>
  </div>
  <?php else: ?>
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;white-space:nowrap">Data</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Produto</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;white-space:nowrap">ID antigo</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;white-space:nowrap">ID novo</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;white-space:nowrap">Dias</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Status</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">IA ajustou</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log):
            $isOk     = $log['status'] === 'SUCCESS';
            $changes  = json_decode($log['gemini_changes'] ?? '[]', true) ?: [];
            $hasChanges = !empty($changes) && $changes !== ['Gemini indisponível — payload original usado'];
          ?>
          <tr style="border-bottom:0.5px solid #2E2E33;<?= !$isOk ? 'background:rgba(239,68,68,.03)' : '' ?>">
            <td style="padding:10px 14px;color:#5E5E5A;white-space:nowrap">
              <?= date('d/m H:i', strtotime($log['created_at'])) ?>
            </td>
            <td style="padding:10px 14px;color:#E8E8E6;max-width:250px">
              <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($log['product_title'] ?? $log['p_title'] ?? '—') ?>
              </div>
            </td>
            <td style="padding:10px 14px;white-space:nowrap">
              <?php if ($log['old_item_id']): ?>
              <a href="https://produto.mercadolivre.com.br/<?= $log['old_item_id'] ?>" target="_blank"
                style="color:#5E5E5A;text-decoration:none;font-family:monospace;font-size:11px">
                <?= $log['old_item_id'] ?>
              </a>
              <?php else: ?>
              <span style="color:#5E5E5A">—</span>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px;white-space:nowrap">
              <?php if ($log['new_item_id']): ?>
              <a href="https://produto.mercadolivre.com.br/<?= $log['new_item_id'] ?>" target="_blank"
                style="color:#3483FA;text-decoration:none;font-family:monospace;font-size:11px;display:flex;align-items:center;gap:3px">
                <?= $log['new_item_id'] ?>
                <i data-lucide="external-link" style="width:9px;height:9px"></i>
              </a>
              <?php else: ?>
              <span style="color:#5E5E5A">—</span>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px;color:#5E5E5A;text-align:center">
              <?= $log['dias_ativo'] ?? '—' ?>
            </td>
            <td style="padding:10px 14px">
              <?php if ($isOk): ?>
              <span style="font-size:9px;padding:2px 8px;border-radius:8px;background:rgba(34,197,94,.1);color:#22c55e;font-weight:600">✓ Sucesso</span>
              <?php else: ?>
              <span style="font-size:9px;padding:2px 8px;border-radius:8px;background:rgba(239,68,68,.1);color:#ef4444;font-weight:600"
                title="<?= htmlspecialchars($log['error_message'] ?? '') ?>">✗ Erro</span>
              <?php if ($log['error_message']): ?>
              <div style="font-size:10px;color:#ef4444;margin-top:3px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars(mb_substr($log['error_message'], 0, 80)) ?>
              </div>
              <?php endif; ?>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px">
              <?php if ($hasChanges): ?>
              <button onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'"
                style="font-size:10px;padding:2px 8px;background:rgba(255,230,0,.1);border:0.5px solid rgba(255,230,0,.2);color:#FFE600;border-radius:6px;cursor:pointer">
                <i data-lucide="sparkles" style="width:9px;height:9px"></i> Ver ajustes
              </button>
              <div style="display:none;margin-top:6px;font-size:10px;color:#9A9A96;background:#0F0F10;border-radius:6px;padding:8px;max-width:250px">
                <?php foreach ($changes as $c): ?>
                <div style="margin-bottom:2px">· <?= htmlspecialchars($c) ?></div>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <span style="font-size:10px;color:#3E3E45">Sem ajustes</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:10px 14px;border-top:0.5px solid #2E2E33;text-align:right;font-size:11px;color:#5E5E5A">
      <?= count($logs) ?> registro<?= count($logs)!==1?'s':'' ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>lucide.createIcons();</script>

<?php include __DIR__ . '/layout_end.php'; ?>
