<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_require();
license_check();

$user     = auth_user();
$tenantId = $user['tenant_id'];
$today    = date('Y-m-d');

// Conta ML selecionada no header
$acctId  = $_SESSION['active_meli_account_id'] ?? null;
$acctSql = $acctId ? " AND meli_account_id=?" : "";
$acctP   = $acctId ? [$acctId] : [];

// Busca nickname da conta ativa para exibir nos cards
$activeAcctInfo = $acctId
    ? db_one("SELECT nickname FROM meli_accounts WHERE id=? AND tenant_id=?", [$acctId, $tenantId])
    : null;
$activeAcctNickname = $activeAcctInfo['nickname'] ?? 'conta selecionada';

$sales = db_one(
    "SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as cnt
     FROM orders WHERE tenant_id=? AND DATE(order_date)=?{$acctSql} AND status!='CANCELLED'",
    array_merge([$tenantId, $today], (array)$acctP)
);
$sac = db_one(
    "SELECT COUNT(*) as cnt FROM sac_messages
     WHERE tenant_id=? AND is_read=0 AND from_role='BUYER'{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);
$toShip = db_one(
    "SELECT COUNT(*) as cnt FROM orders
     WHERE tenant_id=? AND ship_status='READY_TO_SHIP'{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);
$late = db_one(
    "SELECT COUNT(*) as cnt FROM orders
     WHERE tenant_id=? AND ship_status='READY_TO_SHIP'
     AND order_date < DATE_SUB(NOW(), INTERVAL 2 DAY){$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);
$lowStock = db_one(
    "SELECT COUNT(*) as cnt FROM products
     WHERE tenant_id=? AND stock_quantity <= stock_min{$acctSql}",
    array_merge([$tenantId], (array)$acctP)
);
$orders = db_all(
    "SELECT o.*, GROUP_CONCAT(oi.title SEPARATOR ', ') as products
     FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id
     WHERE o.tenant_id=?{$acctSql}
     GROUP BY o.id ORDER BY o.order_date DESC LIMIT 10",
    array_merge([$tenantId], (array)$acctP)
);
$chart7 = db_all(
    "SELECT DATE(order_date) as day, SUM(total_amount) as total
     FROM orders WHERE tenant_id=?{$acctSql}
     AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status!='CANCELLED'
     GROUP BY DATE(order_date) ORDER BY day",
    array_merge([$tenantId], (array)$acctP)
);
$chart1 = db_all(
    "SELECT DATE_FORMAT(order_date,'%H:00') as day, SUM(total_amount) as total
     FROM orders WHERE tenant_id=?{$acctSql}
     AND DATE(order_date)=CURDATE() AND status!='CANCELLED'
     GROUP BY DATE_FORMAT(order_date,'%H:00') ORDER BY day",
    array_merge([$tenantId], (array)$acctP)
);
$chart30 = db_all(
    "SELECT DATE(order_date) as day, SUM(total_amount) as total
     FROM orders WHERE tenant_id=?{$acctSql}
     AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status!='CANCELLED'
     GROUP BY DATE(order_date) ORDER BY day",
    array_merge([$tenantId], (array)$acctP)
);
$byStatus = db_all(
    "SELECT ship_status, COUNT(*) as cnt FROM orders
     WHERE tenant_id=?{$acctSql} GROUP BY ship_status",
    array_merge([$tenantId], (array)$acctP)
);
$topProducts = db_all(
    "SELECT p.title, SUM(oi.total_price) as total_rev
     FROM order_items oi JOIN products p ON p.id=oi.product_id
     WHERE p.tenant_id=?{$acctSql}
     GROUP BY p.id ORDER BY total_rev DESC LIMIT 5",
    array_merge([$tenantId], (array)$acctP)
);

// ── Resultado ML da conta selecionada no mês atual ───────────────
$mesAtual  = date('Y-m');
$mesLabel  = date_ptbr('F \d\e Y', strtotime($mesAtual . '-01'));

$finConta = [
    'gmv'        => 0, // Receita bruta (total_amount)
    'taxa_ml'    => 0, // Taxas ML (ml_fee_amount)
    'net'        => 0, // Receita líquida ML (net_amount)
    'pedidos'    => 0,
    'ticket_med' => 0,
];
if ($acctId) {
    $finContaRow = db_one(
        "SELECT
            COALESCE(SUM(total_amount),0)    as gmv,
            COALESCE(SUM(ml_fee_amount),0)   as taxa_ml,
            COALESCE(SUM(net_amount),0)      as net,
            COUNT(*)                         as pedidos
         FROM orders
         WHERE tenant_id=? AND meli_account_id=?
           AND payment_status IN ('APPROVED','approved')
           AND DATE_FORMAT(order_date,'%Y-%m')=?
           AND status != 'CANCELLED'",
        [$tenantId, $acctId, $mesAtual]
    );
    $finConta['gmv']        = (float)($finContaRow['gmv']     ?? 0);
    $finConta['taxa_ml']    = (float)($finContaRow['taxa_ml'] ?? 0);
    $finConta['net']        = (float)($finContaRow['net']     ?? 0);
    $finConta['pedidos']    = (int)  ($finContaRow['pedidos'] ?? 0);
    $finConta['ticket_med'] = $finConta['pedidos'] > 0
        ? $finConta['gmv'] / $finConta['pedidos'] : 0;

    // Se net_amount não está populado, estima via total - taxa
    if ($finConta['net'] == 0 && $finConta['gmv'] > 0) {
        $finConta['net'] = $finConta['gmv'] - $finConta['taxa_ml'];
    }
}

// Evolução de GMV dos últimos 6 meses para a conta selecionada
$finContaChart = [];
if ($acctId) {
    $finContaChart = db_all(
        "SELECT DATE_FORMAT(order_date,'%Y-%m') as mes,
                SUM(total_amount) as gmv,
                SUM(net_amount)   as net,
                COUNT(*)          as pedidos
         FROM orders
         WHERE tenant_id=? AND meli_account_id=?
           AND payment_status IN ('APPROVED','approved')
           AND status != 'CANCELLED'
           AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY DATE_FORMAT(order_date,'%Y-%m')
         ORDER BY mes ASC",
        [$tenantId, $acctId]
    );
}
// Mostrado no dashboard independente da conta selecionada no header

// Contas ML ativas para filtrar transactions
$activeAcctIds    = db_all("SELECT id FROM meli_accounts WHERE tenant_id=? AND is_active=1", [$tenantId]);
$activeAcctIdList = array_column($activeAcctIds, 'id');
$activeAcctIn     = count($activeAcctIdList)
    ? implode(',', array_fill(0, count($activeAcctIdList), '?'))
    : "'__none__'";
$txFilter = count($activeAcctIdList) ? " AND meli_account_id IN ({$activeAcctIn})" : " AND 1=0";

$finKpis = db_one(
    "SELECT
        COALESCE(SUM(CASE WHEN direction='CREDIT' AND status = 'PAID' THEN amount ELSE 0 END),0) as receitas,
        COALESCE(SUM(CASE WHEN direction='DEBIT'  AND status = 'PAID' THEN amount ELSE 0 END),0) as despesas,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='CREDIT' AND due_date >= CURDATE() THEN amount ELSE 0 END),0) as a_receber,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='DEBIT'  AND due_date >= CURDATE() THEN amount ELSE 0 END),0) as a_pagar,
        COALESCE(SUM(CASE WHEN status='PENDING' AND due_date < CURDATE() THEN amount ELSE 0 END),0) as vencidos
     FROM financial_entries WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?",
    [$tenantId, $mesAtual]
);

$vendasMLMes = db_one(
    "SELECT COALESCE(SUM(amount),0) as total FROM transactions
     WHERE tenant_id=? AND direction='CREDIT' AND DATE_FORMAT(reference_date,'%Y-%m')=?{$txFilter}",
    array_merge([$tenantId, $mesAtual], $activeAcctIdList)
);

$finReceitas = (float)$finKpis['receitas'] + (float)$vendasMLMes['total'];
$finDespesas = (float)$finKpis['despesas'];
$finSaldo    = $finReceitas - $finDespesas;
$finMargem   = $finReceitas > 0 ? round($finSaldo / $finReceitas * 100, 1) : 0;
$finAReceber = (float)$finKpis['a_receber'];
$finAPagar   = (float)$finKpis['a_pagar'];
$finVencidos = (float)$finKpis['vencidos'];

// Fluxo dos últimos 14 dias — financial_entries manuais + vendas ML (sem taxas ML que já estão no DRE)
$finFlow14 = db_all(
    "SELECT day, SUM(credits) as credits, SUM(debits) as debits FROM (
       SELECT DATE(entry_date) as day,
              SUM(CASE WHEN direction='CREDIT' THEN amount ELSE 0 END) as credits,
              SUM(CASE WHEN direction='DEBIT'  THEN amount ELSE 0 END) as debits
       FROM financial_entries
       WHERE tenant_id=? AND entry_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND status = 'PAID'
       GROUP BY DATE(entry_date)
       UNION ALL
       SELECT DATE(reference_date) as day,
              SUM(CASE WHEN direction='CREDIT' AND type='SALE' THEN amount ELSE 0 END) as credits,
              0 as debits
       FROM transactions
       WHERE tenant_id=? AND type='SALE' AND reference_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY){$txFilter}
       GROUP BY DATE(reference_date)
     ) t GROUP BY day ORDER BY day",
    array_merge([$tenantId, $tenantId], $activeAcctIdList)
);

$title = 'Dashboard';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- KPIs -->
  <div class="kpi-grid" style="display:grid;gap:12px;margin-bottom:20px;grid-template-columns:repeat(5,1fr)">
    <?php
    $kpis = [
      ['label'=>'Vendas hoje',       'value'=>'R$ '.number_format((float)$sales['total'],2,',','.'), 'sub'=>($sales['cnt'] ?? 0).' pedidos',         'icon'=>'shopping-cart',   'color'=>'#3483FA'],
      ['label'=>'Prontos p/ enviar', 'value'=>(int)$toShip['cnt'],  'sub'=>'pedidos aguardando',                  'icon'=>'package',         'color'=>'#f59e0b'],
      ['label'=>'Atrasados',         'value'=>(int)$late['cnt'],    'sub'=>'acima de 2 dias',                     'icon'=>'clock',           'color'=>'#ef4444'],
      ['label'=>'Msgs não lidas',    'value'=>(int)$sac['cnt'],     'sub'=>'no SAC',                              'icon'=>'message-circle',  'color'=>'#22c55e'],
      ['label'=>'Estoque baixo',     'value'=>(int)$lowStock['cnt'],'sub'=>'abaixo do mínimo',                    'icon'=>'alert-triangle',  'color'=>'#f59e0b'],
    ];
    foreach ($kpis as $k):
    ?>
    <div class="kpi" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid <?= $k['color'] ?>;border-radius:12px;padding:14px;position:relative;overflow:hidden;min-height:88px;display:flex;flex-direction:column;justify-content:space-between">
      <div style="display:flex;align-items:flex-start;justify-content:space-between">
        <div style="font-size:11px;color:#5E5E5A"><?= $k['label'] ?></div>
        <div style="width:30px;height:30px;border-radius:8px;background:<?= $k['color'] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i data-lucide="<?= $k['icon'] ?>" style="width:14px;height:14px;color:<?= $k['color'] ?>"></i>
        </div>
      </div>
      <div style="font-size:24px;font-weight:600;color:#E8E8E6;line-height:1.1"><?= $k['value'] ?></div>
      <div style="font-size:11px;color:#5E5E5A"><?= $k['sub'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── Resultado ML da conta selecionada ────────────────────── -->
  <?php
  $activeAcctLabel = htmlspecialchars($activeAcctNickname);
  $netColor  = $finConta['net'] >= 0 ? '#22c55e' : '#ef4444';
  $taxaPct   = $finConta['gmv'] > 0 ? round($finConta['taxa_ml'] / $finConta['gmv'] * 100, 1) : 0;
  ?>
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:14px 18px;margin-bottom:14px">

    <!-- Cabeçalho -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="width:6px;height:6px;border-radius:50%;background:#22c55e"></div>
        <span style="font-size:12px;font-weight:500;color:#E8E8E6">
          Resultado ML — <span style="color:#3483FA"><?= $activeAcctLabel ?></span>
        </span>
        <span style="font-size:10px;color:#5E5E5A;background:#252528;padding:2px 8px;border-radius:10px"><?= htmlspecialchars($mesLabel) ?></span>
      </div>
      <?php if (!$acctId): ?>
      <span style="font-size:10px;color:#5E5E5A">Selecione uma conta no header para ver os dados</span>
      <?php else: ?>
      <a href="/pages/financeiro.php" style="font-size:11px;color:#5E5E5A;text-decoration:none">Ver DRE →</a>
      <?php endif; ?>
    </div>

    <!-- KPIs em linha -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr) 2fr;gap:0;align-items:stretch">

      <div style="border-right:0.5px solid #2E2E33;padding:0 14px 0 0">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">GMV bruto</div>
        <div style="font-size:16px;font-weight:600;color:#E8E8E6">
          <?= $acctId ? 'R$ '.number_format($finConta['gmv'],2,',','.') : '—' ?>
        </div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:3px">
          <?= $acctId ? $finConta['pedidos'].' pedidos aprovados' : 'sem conta selecionada' ?>
        </div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding:0 14px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Taxa ML</div>
        <div style="font-size:16px;font-weight:600;color:#ef4444">
          <?= $acctId ? '- R$ '.number_format($finConta['taxa_ml'],2,',','.') : '—' ?>
        </div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:3px">
          <?= $acctId ? $taxaPct.'% do GMV' : '' ?>
        </div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding:0 14px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Receita líquida</div>
        <div style="font-size:16px;font-weight:600;color:<?= $netColor ?>">
          <?= $acctId ? 'R$ '.number_format($finConta['net'],2,',','.') : '—' ?>
        </div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:3px">após taxas ML</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding:0 14px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Ticket médio</div>
        <div style="font-size:16px;font-weight:600;color:#E8E8E6">
          <?= $acctId && $finConta['ticket_med'] > 0 ? 'R$ '.number_format($finConta['ticket_med'],2,',','.') : '—' ?>
        </div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:3px">por pedido</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding:0 14px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px">Taxa / Líquido</div>
        <?php if ($acctId && $finConta['gmv'] > 0): ?>
        <!-- Barra de composição GMV -->
        <div style="height:8px;background:#2E2E33;border-radius:4px;overflow:hidden;display:flex">
          <div style="width:<?= $taxaPct ?>%;background:#ef4444;border-radius:4px 0 0 4px"></div>
          <div style="flex:1;background:#22c55e;border-radius:0 4px 4px 0"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px">
          <span style="font-size:9px;color:#ef4444">Taxa <?= $taxaPct ?>%</span>
          <span style="font-size:9px;color:#22c55e">Líq. <?= round(100-$taxaPct,1) ?>%</span>
        </div>
        <?php else: ?>
        <div style="font-size:11px;color:#3E3E45">sem dados</div>
        <?php endif; ?>
      </div>

      <!-- Mini gráfico GMV 6 meses -->
      <div style="padding-left:14px;display:flex;flex-direction:column;justify-content:center">
        <div style="font-size:9px;color:#5E5E5A;margin-bottom:6px">GMV — 6 meses</div>
        <?php if ($acctId): ?>
        <div id="gmv-chart-wrap" style="width:100%;height:52px;position:relative">
          <canvas id="contaGmvChart"></canvas>
        </div>
        <?php else: ?>
        <div style="height:42px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#3E3E45">—</div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- ── Card Financeiro Consolidado ──────────────────────────── -->
  <?php
  $saldoColor   = $finSaldo >= 0 ? '#22c55e' : '#ef4444';
  $margemColor  = $finMargem >= 0 ? '#22c55e' : '#ef4444';
  ?>
  <a href="/pages/financeiro.php" style="text-decoration:none;display:block;margin-bottom:20px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px 20px;cursor:pointer;transition:border-color .15s"
    onmouseover="this.style.borderColor='#3483FA44'" onmouseout="this.style.borderColor='#2E2E33'">

    <!-- Título -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:8px">
        <div style="width:28px;height:28px;border-radius:8px;background:rgba(52,131,250,.12);display:flex;align-items:center;justify-content:center">
          <i data-lucide="bar-chart-2" style="width:13px;height:13px;color:#3483FA"></i>
        </div>
        <div>
          <div style="font-size:13px;font-weight:500;color:#E8E8E6">Movimentação financeira</div>
          <div style="font-size:10px;color:#5E5E5A">Todas as contas · <?= htmlspecialchars($mesLabel) ?></div>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <?php if ($finVencidos > 0): ?>
        <span style="font-size:10px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;padding:2px 8px;border-radius:10px">
          ⚠ R$ <?= number_format($finVencidos,2,',','.') ?> vencidos
        </span>
        <?php endif; ?>
        <span style="font-size:11px;color:#5E5E5A">Ver extrato →</span>
      </div>
    </div>

    <!-- KPIs financeiros em linha -->
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;align-items:center">

      <div style="border-right:0.5px solid #2E2E33;padding-right:10px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Receitas</div>
        <div style="font-size:15px;font-weight:600;color:#22c55e">R$ <?= number_format($finReceitas,2,',','.') ?></div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:2px">pagas no mês</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding-right:10px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Despesas</div>
        <div style="font-size:15px;font-weight:600;color:#ef4444">R$ <?= number_format($finDespesas,2,',','.') ?></div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:2px">pagas no mês</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding-right:10px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Saldo</div>
        <div style="font-size:15px;font-weight:600;color:<?= $saldoColor ?>"><?= $finSaldo >= 0 ? '+' : '' ?>R$ <?= number_format($finSaldo,2,',','.') ?></div>
        <div style="font-size:9px;color:<?= $margemColor ?>;margin-top:2px">margem <?= number_format($finMargem,1,',','.') ?>%</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding-right:10px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">A receber</div>
        <div style="font-size:15px;font-weight:600;color:#3483FA">R$ <?= number_format($finAReceber,2,',','.') ?></div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:2px">pendente</div>
      </div>

      <div style="border-right:0.5px solid #2E2E33;padding-right:10px">
        <div style="font-size:9px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">A pagar</div>
        <div style="font-size:15px;font-weight:600;color:#f59e0b">R$ <?= number_format($finAPagar,2,',','.') ?></div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:2px">pendente</div>
      </div>

      <!-- Mini gráfico de fluxo 14 dias -->
      <div style="padding-left:4px">
        <div style="font-size:9px;color:#5E5E5A;margin-bottom:6px">Fluxo — 14 dias</div>
        <canvas id="finMiniChart" style="height:40px!important;max-height:40px"></canvas>
      </div>

    </div>
  </div>
  </a>

  <!-- Gráficos de vendas -->
  <div class="fin-charts-grid" style="display:grid;gap:16px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
        <div style="font-size:13px;font-weight:500;color:#E8E8E6">Vendas</div>
        <div style="display:flex;gap:4px">
          <?php foreach (['1d'=>'Hoje','7d'=>'7 dias','30d'=>'30 dias'] as $k=>$label): ?>
          <button onclick="setSalesPeriod('<?= $k ?>')" id="sales-btn-<?= $k ?>"
            style="padding:3px 9px;border-radius:6px;font-size:10px;font-weight:500;cursor:pointer;border:0.5px solid <?= $k==='7d'?'#3483FA':'#2E2E33' ?>;background:<?= $k==='7d'?'rgba(52,131,250,.1)':'transparent' ?>;color:<?= $k==='7d'?'#3483FA':'#5E5E5A' ?>;transition:all .15s">
            <?= $label ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:14px" id="sales-period-label">Receita diária em R$ — últimos 7 dias</div>
      <canvas id="salesChart" style="max-height:160px"></canvas>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:14px">Status de envio</div>
      <canvas id="statusChart" style="max-height:160px"></canvas>
    </div>
  </div>

  <!-- Top produtos e pedidos recentes -->
  <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">

    <!-- Top produtos -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33">
        <span style="font-size:13px;font-weight:500;color:#E8E8E6">Top 5 produtos</span>
      </div>
      <?php if (empty($topProducts)): ?>
      <div style="padding:24px;text-align:center;color:#5E5E5A;font-size:12px">Sem dados</div>
      <?php else: ?>
      <?php
      $maxRev = max(array_column($topProducts, 'total_rev')) ?: 1;
      foreach ($topProducts as $p):
        $pct = round((float)$p['total_rev'] / $maxRev * 100);
      ?>
      <div style="padding:10px 18px;border-bottom:0.5px solid #2E2E33">
        <div style="font-size:12px;color:#E8E8E6;margin-bottom:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(mb_substr($p['title'],0,35)) ?></div>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="flex:1;height:4px;background:#2E2E33;border-radius:2px">
            <div style="width:<?= $pct ?>%;height:100%;background:#3483FA;border-radius:2px"></div>
          </div>
          <span style="font-size:11px;color:#5E5E5A;white-space:nowrap">R$ <?= number_format((float)$p['total_rev'],0,',','.') ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pedidos recentes -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:0.5px solid #2E2E33">
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-size:13px;font-weight:500;color:#E8E8E6">Pedidos recentes</span>
          <span style="font-size:11px;color:#5E5E5A;background:#252528;padding:2px 8px;border-radius:10px"><?= count($orders) ?> últimos</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
          <a href="/pages/logistica.php" style="font-size:12px;color:#f59e0b;text-decoration:none;display:flex;align-items:center;gap:4px">
            <i data-lucide="truck" style="width:11px;height:11px"></i> Expedição
          </a>
          <a href="/pages/logistica.php" style="font-size:12px;color:#3483FA;text-decoration:none">Ver todos →</a>
        </div>
      </div>
      <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
        <table>
          <thead><tr><th>Pedido</th><th>Comprador</th><th>Produto</th><th>Valor</th><th>Pagamento</th><th>Envio</th></tr></thead>
          <tbody>
            <?php if (empty($orders)): ?>
            <tr><td colspan="6" style="text-align:center;color:#5E5E5A;padding:24px">Nenhum pedido encontrado</td></tr>
            <?php else: foreach ($orders as $o):
              $payBadge  = match($o['payment_status'] ?? '')  { 'APPROVED'=>'badge-green','REJECTED'=>'badge-red',default=>'badge-amber' };
              $payLabel  = match($o['payment_status'] ?? '')  { 'APPROVED'=>'Pago','REJECTED'=>'Rejeitado',default=>'Pendente' };
              $shipBadge = match($o['ship_status'] ?? '')     { 'SHIPPED'=>'badge-blue','DELIVERED'=>'badge-green','READY_TO_SHIP'=>'badge-amber',default=>'badge-amber' };
              $shipLabel = match($o['ship_status'] ?? '')     { 'READY_TO_SHIP'=>'Pronto','SHIPPED'=>'Enviado','DELIVERED'=>'Entregue',default=>'Pendente' };
            ?>
            <tr>
              <td style="font-family:monospace;font-size:11px;color:#5E5E5A"><?= htmlspecialchars($o['meli_order_id'] ?? $o['id']) ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($o['buyer_nickname'] ?? '—') ?></td>
              <td style="color:#9A9A96;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['products'] ?? '—') ?></td>
              <td style="font-weight:500">R$ <?= number_format((float)$o['total_amount'],2,',','.') ?></td>
              <td><span class="badge <?= $payBadge ?>"><?= $payLabel ?></span></td>
              <td><span class="badge <?= $shipBadge ?>"><?= $shipLabel ?></span><?php if ($o['has_mediacao'] ?? false): ?> <span style="font-size:9px;color:#ef4444">⚠</span><?php endif; ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// ── Mini gráfico financeiro (fluxo 14 dias) ──────────────
const finFlow14 = <?= json_encode(array_map(fn($r) => [
    'day'     => substr($r['day'], 5),
    'credits' => (float)$r['credits'],
    'debits'  => (float)$r['debits'],
], $finFlow14)) ?>;

(function() {
  const canvas = document.getElementById('finMiniChart');
  if (!canvas) return;

  // Preenche os últimos 14 dias (inclusive vazios)
  const days = [], credits = [], debits = [];
  for (let i = 13; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const key = (d.getMonth()+1).toString().padStart(2,'0')+'/'+d.getDate().toString().padStart(2,'0');
    const found = finFlow14.find(r => r.day === key);
    days.push(key);
    credits.push(found ? found.credits : 0);
    debits.push(found ? found.debits  : 0);
  }

  window.registerChart('finMiniChart', new Chart(canvas, {
    type: 'bar',
    data: {
      labels: days,
      datasets: [
        { data: credits, backgroundColor: 'rgba(34,197,94,0.7)',  borderRadius: 2 },
        { data: debits,  backgroundColor: 'rgba(239,68,68,0.6)',  borderRadius: 2 },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: {
        x: { display: false, stacked: false },
        y: { display: false }
      },
      animation: false,
    }
  }));
})();

// ── Gráfico GMV 6 meses da conta selecionada ────────────
const contaGmvData = <?= json_encode(array_map(fn($r) => [
    'mes' => substr($r['mes'], 5), // MM
    'gmv' => (float)$r['gmv'],
    'net' => (float)$r['net'],
], $finContaChart)) ?>;

(function() {
  const canvas = document.getElementById('contaGmvChart');
  if (!canvas || !contaGmvData.length) return;

  const labels = [], gmvVals = [], netVals = [];
  for (let i = 5; i >= 0; i--) {
    const d = new Date(); d.setDate(1); d.setMonth(d.getMonth() - i);
    const key = (d.getMonth()+1).toString().padStart(2,'0');
    const found = contaGmvData.find(r => r.mes === key);
    labels.push(key+'/'+d.getFullYear().toString().slice(2));
    gmvVals.push(found ? found.gmv : 0);
    netVals.push(found ? found.net : 0);
  }

  const isMobile = window.innerWidth <= 768;
  const tooltipCfg = {
    backgroundColor: '#252528', borderColor: '#2E2E33', borderWidth: 1,
    titleColor: '#E8E8E6', bodyColor: '#9A9A96',
  };

  let config;
  if (isMobile) {
    // Doughnut no mobile/PWA
    canvas.style.width  = '64px';
    canvas.style.height = '64px';
    canvas.parentElement.style.width  = '64px';
    canvas.parentElement.style.height = '64px';
    config = {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data: gmvVals,
          backgroundColor: [
            'rgba(52,131,250,0.5)','rgba(52,131,250,0.65)',
            'rgba(52,131,250,0.75)','rgba(52,131,250,0.85)',
            'rgba(34,197,94,0.65)','rgba(34,197,94,0.9)',
          ],
          borderColor: '#1A1A1C',
          borderWidth: 2,
          hoverOffset: 4,
        }]
      },
      options: {
        responsive: false,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: { ...tooltipCfg, callbacks: {
            title: ctx => ctx[0].label,
            label: ctx => ` R$ ${ctx.parsed.toLocaleString('pt-BR',{minimumFractionDigits:2})}`,
          }}
        },
        animation: { duration: 400 },
      }
    };
  } else {
    // Bar no desktop
    canvas.style.width  = '100%';
    canvas.style.height = '52px';
    canvas.parentElement.style.width  = '100%';
    canvas.parentElement.style.height = '52px';
    config = {
      type: 'bar',
      data: {
        labels,
        datasets: [
          { label:'GMV', data: gmvVals, backgroundColor:'rgba(52,131,250,0.35)', borderColor:'#3483FA', borderWidth:1, borderRadius:2 },
          { label:'Líq', data: netVals, backgroundColor:'rgba(34,197,94,0.5)',   borderColor:'#22c55e', borderWidth:1, borderRadius:2 },
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { ...tooltipCfg, callbacks: {
            label: ctx => ` R$ ${ctx.parsed.y.toLocaleString('pt-BR',{minimumFractionDigits:2})}`,
          }}
        },
        scales: { x: { display: false }, y: { display: false } },
        animation: { duration: 400 },
      }
    };
  }

  window.registerChart('contaGmvChart', new Chart(canvas, config));
})();
const salesData = <?= json_encode(array_map(fn($r) => ['day'=>substr($r['day'],5),'total'=>(float)$r['total']], $chart7)) ?>;
const salesData1d  = <?= json_encode(array_map(fn($r) => ['day'=>$r['day'],'total'=>(float)$r['total']], $chart1)) ?>;
const salesData30d = <?= json_encode(array_map(fn($r) => ['day'=>substr($r['day'],5),'total'=>(float)$r['total']], $chart30)) ?>;

document.addEventListener('DOMContentLoaded', () => {

const periodLabels = {
  '1d':  { data: salesData1d,  label: 'Receita por hora hoje (R$)',        days: 24, fmt: h => h },
  '7d':  { data: salesData,    label: 'Receita diária — últimos 7 dias',   days: 7,  fmt: d => d },
  '30d': { data: salesData30d, label: 'Receita diária — últimos 30 dias',  days: 30, fmt: d => d },
};

function setSalesPeriod(period) {
  // Atualiza botões
  ['1d','7d','30d'].forEach(k => {
    const btn = document.getElementById('sales-btn-' + k);
    if (!btn) return;
    btn.style.border     = k === period ? '0.5px solid #3483FA' : '0.5px solid #2E2E33';
    btn.style.background = k === period ? 'rgba(52,131,250,.1)' : 'transparent';
    btn.style.color      = k === period ? '#3483FA' : '#5E5E5A';
  });

  const cfg = periodLabels[period];
  document.getElementById('sales-period-label').textContent = cfg.label;

  // Reconstrói labels e dados para o período
  const labels = [], vals = [];
  if (period === '1d') {
    // Horas do dia
    for (let h = 0; h < 24; h++) {
      const key = h.toString().padStart(2,'0') + ':00';
      const found = cfg.data.find(r => r.day === key);
      labels.push(key);
      vals.push(found ? found.total : 0);
    }
  } else {
    const days = period === '7d' ? 7 : 30;
    for (let i = days - 1; i >= 0; i--) {
      const d = new Date(); d.setDate(d.getDate() - i);
      const key = (d.getMonth()+1).toString().padStart(2,'0') + '/' + d.getDate().toString().padStart(2,'0');
      const found = cfg.data.find(r => r.day === key);
      labels.push(key);
      vals.push(found ? found.total : 0);
    }
  }

  const chart = window.Charts['salesChart'];
  if (chart) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = vals;
    chart.update('none');
  }
}

if (document.getElementById('salesChart')) {
  // Monta labels dos 7 dias
  const init7labels = [], init7vals = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const key = (d.getMonth()+1).toString().padStart(2,'0') + '/' + d.getDate().toString().padStart(2,'0');
    const found = salesData.find(r => r.day === key);
    init7labels.push(key);
    init7vals.push(found ? found.total : 0);
  }
  window.registerChart('salesChart', new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels: init7labels,
      datasets: [{
        data: init7vals,
        borderColor: '#3483FA', backgroundColor: 'rgba(52,131,250,.1)',
        tension: 0.4, fill: true, pointRadius: 3
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#5E5E5A', font: { size: 10 } }, grid: { color: '#2E2E33' } },
        y: { ticks: { color: '#5E5E5A', font: { size: 10 }, callback: v => 'R$'+v.toLocaleString('pt-BR') }, grid: { color: '#2E2E33' } }
      }
    }
  }));
}

// Gráfico status envio
const statusData = <?= json_encode(array_values($byStatus)) ?>;
const statusLabels = { PENDING:'Pendente', READY_TO_SHIP:'Pronto', SHIPPED:'Enviado', DELIVERED:'Entregue', CANCELLED:'Cancelado' };
const statusColors = { PENDING:'#5E5E5A', READY_TO_SHIP:'#f59e0b', SHIPPED:'#3483FA', DELIVERED:'#22c55e', CANCELLED:'#ef4444' };
if (statusData.length > 0) {
  window.registerChart('statusChart', new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: statusData.map(r => statusLabels[r.ship_status] || r.ship_status),
      datasets: [{ data: statusData.map(r => r.cnt), backgroundColor: statusData.map(r => statusColors[r.ship_status] || '#5E5E5A'), borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { color: '#9A9A96', font: { size: 10 }, padding: 8 } } }
    }
  }));
}

}); // fim DOMContentLoaded
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
