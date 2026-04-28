<?php
/**
 * pages/relatorios.php
 * Central de Relatórios — Excel/PDF de vendas por período, produto e categoria
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_financeiro');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND o.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

// Período padrão: mês atual
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$groupBy  = in_array($_GET['group']??'', ['dia','produto','categoria']) ? $_GET['group'] : 'dia';

// ── Dados do resumo ───────────────────────────────────────
$pBase = array_merge([$tenantId], $acctP, [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

$totais = db_one(
    "SELECT COUNT(DISTINCT o.id) as pedidos,
            SUM(o.total_amount) as receita,
            SUM(o.ml_fee_amount) as taxas,
            SUM(o.net_amount) as liquido,
            COUNT(DISTINCT o.buyer_nickname) as compradores
     FROM orders o
     WHERE o.tenant_id=?{$acctSql}
       AND o.payment_status IN ('approved','APPROVED')
       AND o.order_date BETWEEN ? AND ?",
    $pBase
);

// Ticket médio
$ticketMedio = ($totais['pedidos'] ?? 0) > 0
    ? (float)$totais['receita'] / (int)$totais['pedidos']
    : 0;

// ── Por dia ───────────────────────────────────────────────
$porDia = [];
if ($groupBy === 'dia') {
    $porDia = db_all(
        "SELECT DATE(o.order_date) as data,
                COUNT(DISTINCT o.id) as pedidos,
                SUM(o.total_amount) as receita,
                SUM(o.ml_fee_amount) as taxas,
                SUM(o.net_amount) as liquido
         FROM orders o
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY DATE(o.order_date)
         ORDER BY data ASC",
        $pBase
    );
}

// ── Por produto ───────────────────────────────────────────
$porProduto = [];
if ($groupBy === 'produto') {
    $porProduto = db_all(
        "SELECT oi.title,
                oi.sku,
                oi.meli_item_id,
                SUM(oi.quantity) as unidades,
                SUM(oi.total_price) as receita,
                AVG(oi.unit_price) as preco_medio,
                COUNT(DISTINCT o.id) as pedidos
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY oi.meli_item_id, oi.title, oi.sku
         ORDER BY receita DESC
         LIMIT 100",
        $pBase
    );
}

// ── Por categoria ─────────────────────────────────────────
$porCategoria = [];
if ($groupBy === 'categoria') {
    $porCategoria = db_all(
        "SELECT COALESCE(p.category_id, 'Sem categoria') as categoria,
                SUM(oi.quantity) as unidades,
                SUM(oi.total_price) as receita,
                COUNT(DISTINCT o.id) as pedidos
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         LEFT JOIN products p ON p.meli_item_id = oi.meli_item_id AND p.tenant_id = o.tenant_id
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY categoria
         ORDER BY receita DESC",
        $pBase
    );
}

// ── Top 5 produtos para gráfico ───────────────────────────
$topProdutos = db_all(
    "SELECT oi.title, SUM(oi.total_price) as receita
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.tenant_id=?{$acctSql}
       AND o.payment_status IN ('approved','APPROVED')
       AND o.order_date BETWEEN ? AND ?
     GROUP BY oi.meli_item_id, oi.title
     ORDER BY receita DESC LIMIT 5",
    $pBase
);

$title = 'Relatórios';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Relatórios de Vendas</h1>
      <p style="font-size:11px;color:#5E5E5A">Exporte dados por período, produto ou categoria</p>
    </div>

    <!-- Botões de exportação -->
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="/api/relatorio_export.php?formato=excel&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&group=<?= $groupBy ?>"
        style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s"
        onmouseover="this.style.background='rgba(34,197,94,.2)'" onmouseout="this.style.background='rgba(34,197,94,.1)'">
        <i data-lucide="file-spreadsheet" style="width:13px;height:13px"></i> Exportar Excel
      </a>
      <a href="/api/relatorio_export.php?formato=pdf&from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&group=<?= $groupBy ?>"
        style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .15s"
        onmouseover="this.style.background='rgba(239,68,68,.2)'" onmouseout="this.style.background='rgba(239,68,68,.1)'">
        <i data-lucide="file-text" style="width:13px;height:13px"></i> Exportar PDF
      </a>
    </div>
  </div>

  <!-- Filtros -->
  <form method="GET" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:20px">
    <div class="rel-filtros" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end">
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Data início</label>
        <input type="date" name="from" value="<?= $dateFrom ?>" class="input">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Data fim</label>
        <input type="date" name="to" value="<?= $dateTo ?>" class="input">
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#5E5E5A;margin-bottom:5px">Agrupar por</label>
        <select name="group" class="input">
          <option value="dia"       <?= $groupBy==='dia'      ?'selected':'' ?>>Por dia</option>
          <option value="produto"   <?= $groupBy==='produto'  ?'selected':'' ?>>Por produto</option>
          <option value="categoria" <?= $groupBy==='categoria'?'selected':'' ?>>Por categoria</option>
        </select>
      </div>
      <button type="submit" class="btn-primary" style="font-size:12px;padding:9px 18px">
        <i data-lucide="search" style="width:12px;height:12px"></i> Filtrar
      </button>
    </div>

    <!-- Atalhos de período -->
    <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap">
      <?php
      $atalhos = [
        'Hoje'          => [date('Y-m-d'), date('Y-m-d')],
        'Esta semana'   => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
        'Este mês'      => [date('Y-m-01'), date('Y-m-d')],
        'Mês passado'   => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month'))],
        'Últimos 30d'   => [date('Y-m-d', strtotime('-30 days')), date('Y-m-d')],
        'Últimos 90d'   => [date('Y-m-d', strtotime('-90 days')), date('Y-m-d')],
        'Este ano'      => [date('Y-01-01'), date('Y-m-d')],
      ];
      foreach ($atalhos as $label => [$f, $t]):
        $active = $dateFrom === $f && $dateTo === $t;
      ?>
      <a href="?from=<?= $f ?>&to=<?= $t ?>&group=<?= $groupBy ?>"
        style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:500;text-decoration:none;border:0.5px solid <?= $active?'#3483FA':'#2E2E33' ?>;background:<?= $active?'rgba(52,131,250,.15)':'transparent' ?>;color:<?= $active?'#3483FA':'#5E5E5A' ?>;transition:all .15s">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>
  </form>

  <!-- KPIs do período -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:20px">
    <?php foreach ([
      ['Pedidos',      number_format((int)($totais['pedidos']??0),0,',','.'),     '#3483FA', 'shopping-bag'],
      ['Receita Bruta','R$ '.number_format((float)($totais['receita']??0),2,',','.'), '#22c55e', 'trending-up'],
      ['Taxas ML',     'R$ '.number_format((float)($totais['taxas']??0),2,',','.'),   '#ef4444', 'percent'],
      ['Receita Líq.', 'R$ '.number_format((float)($totais['liquido']??0),2,',','.'), '#a855f7', 'wallet'],
      ['Ticket Médio', 'R$ '.number_format($ticketMedio,2,',','.'),                   '#f59e0b', 'tag'],
      ['Compradores',  number_format((int)($totais['compradores']??0),0,',','.'), '#f97316', 'users'],
    ] as [$l,$v,$c,$ico]): ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid <?= $c ?>;border-radius:10px;padding:12px 14px">
      <div style="display:flex;align-items:center;gap:5px;margin-bottom:6px">
        <i data-lucide="<?= $ico ?>" style="width:11px;height:11px;color:<?= $c ?>"></i>
        <span style="font-size:10px;color:#5E5E5A"><?= $l ?></span>
      </div>
      <div style="font-size:18px;font-weight:700;color:#E8E8E6"><?= $v ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gráfico de linha (por dia) ou pizza (top produtos) -->
  <?php if ($groupBy === 'dia' && !empty($porDia)): ?>
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px;margin-bottom:20px">
    <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:14px">Receita por dia</div>
    <canvas id="chart-dia" style="max-height:200px"></canvas>
  </div>
  <?php endif; ?>

  <?php if (in_array($groupBy, ['produto','categoria']) && !empty($topProdutos)): ?>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:14px">Top 5 produtos por receita</div>
      <canvas id="chart-top" style="max-height:200px"></canvas>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
      <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:14px">Distribuição</div>
      <canvas id="chart-pizza" style="max-height:200px"></canvas>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabela de dados -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:12px;font-weight:500;color:#E8E8E6">
        <?= $groupBy==='dia'?'Vendas por dia':($groupBy==='produto'?'Vendas por produto':'Vendas por categoria') ?>
        — <?= date('d/m/Y', strtotime($dateFrom)) ?> a <?= date('d/m/Y', strtotime($dateTo)) ?>
      </span>
    </div>
    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px">

        <?php if ($groupBy === 'dia'): ?>
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Data</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Pedidos</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Receita Bruta</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Taxas ML</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Receita Líquida</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Ticket Médio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($porDia as $row):
            $ticket = (int)$row['pedidos'] > 0 ? (float)$row['receita'] / (int)$row['pedidos'] : 0;
          ?>
          <tr style="border-bottom:0.5px solid #2E2E33" onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px;color:#E8E8E6;font-weight:500"><?= date('d/m/Y', strtotime($row['data'])) ?></td>
            <td style="padding:10px 14px;text-align:right;color:#3483FA"><?= $row['pedidos'] ?></td>
            <td style="padding:10px 14px;text-align:right;color:#22c55e">R$ <?= number_format($row['receita'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#ef4444">R$ <?= number_format($row['taxas'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#a855f7;font-weight:600">R$ <?= number_format($row['liquido'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#f59e0b">R$ <?= number_format($ticket,2,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($porDia)): ?>
          <tr><td colspan="6" style="padding:32px;text-align:center;color:#5E5E5A">Nenhuma venda no período</td></tr>
          <?php endif; ?>
        </tbody>

        <?php elseif ($groupBy === 'produto'): ?>
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Produto</th>
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">SKU</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Pedidos</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Unidades</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Preço Médio</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Receita</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">% do Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $receitaTotal = (float)($totais['receita'] ?? 1);
          foreach ($porProduto as $row):
            $pct = $receitaTotal > 0 ? round((float)$row['receita'] / $receitaTotal * 100, 1) : 0;
          ?>
          <tr style="border-bottom:0.5px solid #2E2E33" onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px;max-width:260px">
              <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#E8E8E6"><?= htmlspecialchars($row['title']) ?></div>
              <?php if ($row['meli_item_id']): ?>
              <div style="font-size:10px;color:#3483FA;font-family:monospace"><?= $row['meli_item_id'] ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:10px 14px;color:#5E5E5A;font-family:monospace;font-size:11px"><?= htmlspecialchars($row['sku']??'—') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#3483FA"><?= $row['pedidos'] ?></td>
            <td style="padding:10px 14px;text-align:right;color:#E8E8E6"><?= number_format($row['unidades'],0,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#f59e0b">R$ <?= number_format($row['preco_medio'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#22c55e;font-weight:600">R$ <?= number_format($row['receita'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right">
              <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
                <div style="width:40px;height:5px;background:#2E2E33;border-radius:3px;overflow:hidden">
                  <div style="width:<?= min($pct,100) ?>%;height:100%;background:#3483FA;border-radius:3px"></div>
                </div>
                <span style="color:#9A9A96;font-size:11px"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($porProduto)): ?>
          <tr><td colspan="7" style="padding:32px;text-align:center;color:#5E5E5A">Nenhuma venda no período</td></tr>
          <?php endif; ?>
        </tbody>

        <?php else: // categoria ?>
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Categoria ML</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Pedidos</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Unidades</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Receita</th>
            <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">% do Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $receitaTotal = (float)($totais['receita'] ?? 1);
          foreach ($porCategoria as $row):
            $pct = $receitaTotal > 0 ? round((float)$row['receita'] / $receitaTotal * 100, 1) : 0;
          ?>
          <tr style="border-bottom:0.5px solid #2E2E33" onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
            <td style="padding:10px 14px;color:#E8E8E6"><?= htmlspecialchars($row['categoria']) ?></td>
            <td style="padding:10px 14px;text-align:right;color:#3483FA"><?= $row['pedidos'] ?></td>
            <td style="padding:10px 14px;text-align:right;color:#E8E8E6"><?= number_format($row['unidades'],0,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right;color:#22c55e;font-weight:600">R$ <?= number_format($row['receita'],2,',','.') ?></td>
            <td style="padding:10px 14px;text-align:right">
              <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
                <div style="width:60px;height:5px;background:#2E2E33;border-radius:3px;overflow:hidden">
                  <div style="width:<?= min($pct,100) ?>%;height:100%;background:#a855f7;border-radius:3px"></div>
                </div>
                <span style="color:#9A9A96;font-size:11px"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($porCategoria)): ?>
          <tr><td colspan="5" style="padding:32px;text-align:center;color:#5E5E5A">Nenhuma venda no período</td></tr>
          <?php endif; ?>
        </tbody>
        <?php endif; ?>

      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
lucide.createIcons();

<?php if ($groupBy === 'dia' && !empty($porDia)): ?>
new Chart(document.getElementById('chart-dia').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['data'])), $porDia)) ?>,
    datasets: [
      {
        label: 'Receita Bruta',
        data: <?= json_encode(array_map(fn($r) => round((float)$r['receita'],2), $porDia)) ?>,
        borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.08)',
        tension: 0.4, fill: true, pointRadius: 3,
      },
      {
        label: 'Receita Líquida',
        data: <?= json_encode(array_map(fn($r) => round((float)$r['liquido'],2), $porDia)) ?>,
        borderColor: '#a855f7', backgroundColor: 'transparent',
        tension: 0.4, pointRadius: 3, borderDash: [4,3],
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color:'#9A9A96', font:{size:11} } } },
    scales: {
      x: { ticks:{color:'#5E5E5A',font:{size:10}}, grid:{color:'#2E2E33'} },
      y: { ticks:{color:'#5E5E5A',font:{size:10},callback:v=>'R$ '+v.toLocaleString('pt-BR')}, grid:{color:'#2E2E33'} }
    }
  }
});
<?php endif; ?>

<?php if (in_array($groupBy, ['produto','categoria']) && !empty($topProdutos)):
  $labels   = array_map(fn($r) => mb_substr($r['title'],0,25).'…', $topProdutos);
  $values   = array_map(fn($r) => round((float)$r['receita'],2), $topProdutos);
  $colors   = ['#3483FA','#22c55e','#f59e0b','#a855f7','#f97316'];
?>
new Chart(document.getElementById('chart-top').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{ label:'Receita', data:<?= json_encode($values) ?>, backgroundColor:<?= json_encode($colors) ?>, borderRadius:4 }]
  },
  options: {
    responsive:true, maintainAspectRatio:false, indexAxis:'y',
    plugins:{legend:{display:false}},
    scales:{
      x:{ticks:{color:'#5E5E5A',font:{size:10},callback:v=>'R$ '+v.toLocaleString('pt-BR')},grid:{color:'#2E2E33'}},
      y:{ticks:{color:'#9A9A96',font:{size:10}},grid:{display:false}}
    }
  }
});

new Chart(document.getElementById('chart-pizza').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{ data:<?= json_encode($values) ?>, backgroundColor:<?= json_encode($colors) ?>, borderWidth:0 }]
  },
  options: {
    responsive:true, maintainAspectRatio:false, cutout:'65%',
    plugins:{ legend:{ position:'bottom', labels:{color:'#9A9A96',font:{size:10},boxWidth:10} } }
  }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
