<?php
require_once 'config/config.php';
if (!$auth->isLoggedIn()) { header('Location: login.php'); exit; }
$usuario = $auth->getCurrentUser();

$os_abertas    = $db->query("SELECT COUNT(*) FROM ordens_servico WHERE status='aberta'")->fetchColumn();
$os_andamento  = $db->query("SELECT COUNT(*) FROM ordens_servico WHERE status='em_andamento'")->fetchColumn();
$os_aguardando = $db->query("SELECT COUNT(*) FROM ordens_servico WHERE status='aguardando_pecas'")->fetchColumn();
$os_finalizadas= $db->query("SELECT COUNT(*) FROM ordens_servico WHERE status='finalizada'")->fetchColumn();
$os_canceladas = $db->query("SELECT COUNT(*) FROM ordens_servico WHERE status='cancelada'")->fetchColumn();
$orc_ativos    = $db->query("SELECT COUNT(*) FROM orcamentos WHERE status='ativo'")->fetchColumn();
$orc_aprovados = $db->query("SELECT COUNT(*) FROM orcamentos WHERE status='aprovado'")->fetchColumn();
$orc_rejeitados= $db->query("SELECT COUNT(*) FROM orcamentos WHERE status='rejeitado'")->fetchColumn();
$orc_convertidos=$db->query("SELECT COUNT(*) FROM orcamentos WHERE status='convertido'")->fetchColumn();
$est_critico   = $db->query("SELECT COUNT(*) FROM produtos WHERE estoque_atual <= estoque_minimo")->fetchColumn();
$est_medio     = $db->query("SELECT COUNT(*) FROM produtos WHERE estoque_atual > estoque_minimo AND estoque_atual <= estoque_minimo*2")->fetchColumn();
$est_bom       = $db->query("SELECT COUNT(*) FROM produtos WHERE estoque_atual > estoque_minimo*2")->fetchColumn();
$vendas_hoje   = $db->query("SELECT COALESCE(SUM(total),0) FROM vendas WHERE DATE(data_venda)=CURDATE()")->fetchColumn();
$total_clientes= $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$os_abertas_total = $os_abertas + $os_andamento + $os_aguardando;

$vendas_mensal = [];
for ($i=1; $i<=12; $i++) {
    $vendas_mensal[] = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM vendas WHERE MONTH(data_venda)=$i AND YEAR(data_venda)=YEAR(CURDATE())")->fetchColumn();
}

$produtos_top = $db->query("SELECT p.nome, COALESCE(SUM(vi.quantidade),0) as qtd FROM produtos p LEFT JOIN venda_itens vi ON p.id=vi.produto_id LEFT JOIN vendas v ON vi.venda_id=v.id AND MONTH(v.data_venda)=MONTH(CURDATE()) GROUP BY p.id ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$os_recentes  = $db->query("SELECT os.numero_os, c.nome as cliente, m.modelo as moto, os.status, os.data_abertura FROM ordens_servico os JOIN clientes c ON os.cliente_id=c.id JOIN motos m ON os.moto_id=m.id ORDER BY os.data_abertura DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$pagamentos   = $db->query("SELECT forma_pagamento, COUNT(*) as qtd FROM vendas WHERE MONTH(data_venda)=MONTH(CURDATE()) GROUP BY forma_pagamento")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/sidebar.php'; ?>

<!-- Topbar -->
<header class="os-topbar">
  <div class="topbar-title">Dashboard <span>·</span> Visão Geral</div>
  <div class="topbar-actions">
    <span style="font-size:.8rem;color:var(--text-muted)">
      <?= date('d/m/Y, H:i') ?>
    </span>
  </div>
</header>

<main class="os-content">

<?php if (isset($_GET['erro']) && $_GET['erro'] == 'permissao'): ?>
<div class="os-alert os-alert-danger">
  <i class="ph-bold ph-warning"></i> Acesso negado. Você não tem permissão para esta área.
</div>
<?php endif; ?>

<?php if ($est_critico > 0): ?>
<div class="os-alert os-alert-warning">
  <i class="ph-bold ph-warning-circle"></i>
  <strong><?= $est_critico ?> produto(s)</strong> com estoque abaixo do mínimo.
  <a href="<?= BASE_URL ?>/modules/estoque/estoque.php" style="color:var(--accent);font-weight:600;margin-left:4px">Ver estoque →</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid-4" style="margin-bottom:28px">
  <div class="stat-card" style="--stat-color:#f59e0b" onclick="location.href='<?= BASE_URL ?>/modules/os/os.php'" class="cursor-pointer">
    <div class="stat-icon"><i class="ph-bold ph-wrench"></i></div>
    <div class="stat-info">
      <div class="stat-label">OS em Aberto</div>
      <div class="stat-value"><?= $os_abertas_total ?></div>
      <div class="stat-sub"><?= $os_andamento ?> em andamento</div>
    </div>
  </div>
  <div class="stat-card" style="--stat-color:#22c55e">
    <div class="stat-icon"><i class="ph-bold ph-money"></i></div>
    <div class="stat-info">
      <div class="stat-label">Vendas Hoje</div>
      <div class="stat-value" style="font-size:1.3rem">R$ <?= number_format($vendas_hoje,2,',','.') ?></div>
      <div class="stat-sub">caixa do dia</div>
    </div>
  </div>
  <div class="stat-card" style="--stat-color:#38bdf8" onclick="location.href='<?= BASE_URL ?>/modules/orcamentos/orcamentos.php'">
    <div class="stat-icon"><i class="ph-bold ph-file-text"></i></div>
    <div class="stat-info">
      <div class="stat-label">Orçamentos Ativos</div>
      <div class="stat-value"><?= $orc_ativos ?></div>
      <div class="stat-sub"><?= $orc_aprovados ?> aprovados</div>
    </div>
  </div>
  <div class="stat-card" style="--stat-color:#a855f7" onclick="location.href='<?= BASE_URL ?>/modules/clientes/clientes.php'">
    <div class="stat-icon"><i class="ph-bold ph-users"></i></div>
    <div class="stat-info">
      <div class="stat-label">Clientes</div>
      <div class="stat-value"><?= $total_clientes ?></div>
      <div class="stat-sub">cadastrados</div>
    </div>
  </div>
</div>

<!-- Charts row 1 -->
<div class="grid-2" style="margin-bottom:20px">
  <div class="os-card">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-chart-pie"></i> Ordens de Serviço</div>
    </div>
    <div class="os-card-body">
      <div class="chart-wrap"><canvas id="chartOS"></canvas></div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:14px">
        <span class="status-badge aberta"><i class="ph-bold ph-clock"></i> Abertas: <?= $os_abertas ?></span>
        <span class="status-badge em_andamento"><i class="ph-bold ph-gear"></i> Andamento: <?= $os_andamento ?></span>
        <span class="status-badge aguardando"><i class="ph-bold ph-package"></i> Aguarda: <?= $os_aguardando ?></span>
        <span class="status-badge finalizada"><i class="ph-bold ph-check-circle"></i> Final.: <?= $os_finalizadas ?></span>
        <span class="status-badge cancelada"><i class="ph-bold ph-x-circle"></i> Cancel.: <?= $os_canceladas ?></span>
      </div>
    </div>
  </div>
  <div class="os-card">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-chart-pie"></i> Orçamentos</div>
    </div>
    <div class="os-card-body">
      <div class="chart-wrap"><canvas id="chartOrc"></canvas></div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:14px">
        <span class="status-badge ativo"><i class="ph-bold ph-play"></i> Ativos: <?= $orc_ativos ?></span>
        <span class="status-badge aprovado"><i class="ph-bold ph-thumbs-up"></i> Aprovados: <?= $orc_aprovados ?></span>
        <span class="status-badge rejeitado"><i class="ph-bold ph-thumbs-down"></i> Rejeitados: <?= $orc_rejeitados ?></span>
        <span class="status-badge convertido"><i class="ph-bold ph-arrows-clockwise"></i> Convertidos: <?= $orc_convertidos ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Charts row 2 -->
<div class="grid-2" style="margin-bottom:20px">
  <div class="os-card">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-warehouse"></i> Situação do Estoque</div>
    </div>
    <div class="os-card-body">
      <div class="chart-wrap"><canvas id="chartEst"></canvas></div>
      <div style="display:flex;gap:8px;margin-top:14px">
        <span class="status-badge critico"><i class="ph-bold ph-warning"></i> Crítico: <?= $est_critico ?></span>
        <span class="status-badge medio"><i class="ph-bold ph-minus-circle"></i> Médio: <?= $est_medio ?></span>
        <span class="status-badge bom"><i class="ph-bold ph-check-circle"></i> Bom: <?= $est_bom ?></span>
      </div>
    </div>
  </div>
  <div class="os-card">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-credit-card"></i> Formas de Pagamento</div>
    </div>
    <div class="os-card-body">
      <div class="chart-wrap" id="pagWrap"><canvas id="chartPag"></canvas></div>
    </div>
  </div>
</div>

<!-- Bar chart vendas -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-chart-bar"></i> Vendas Mensais — <?= date('Y') ?></div>
  </div>
  <div class="os-card-body">
    <div style="position:relative;height:280px"><canvas id="chartVendas"></canvas></div>
  </div>
</div>

<!-- Tables -->
<div class="grid-2">
  <div class="os-card">
    <div class="os-card-header" style="padding-bottom:12px">
      <div class="os-card-title"><i class="ph-bold ph-trophy"></i> Mais Vendidos (mês)</div>
    </div>
    <table class="os-table">
      <thead><tr><th>Produto</th><th>Qtd</th></tr></thead>
      <tbody>
        <?php $tem = false; foreach ($produtos_top as $p): if ($p['qtd'] > 0): $tem = true; ?>
        <tr>
          <td><?= htmlspecialchars($p['nome']) ?></td>
          <td><span class="status-badge ativo"><?= $p['qtd'] ?> un</span></td>
        </tr>
        <?php endif; endforeach; if (!$tem): ?>
        <tr><td colspan="2" style="text-align:center;color:var(--text-muted);padding:24px">Nenhuma venda no mês</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="os-card">
    <div class="os-card-header" style="padding-bottom:12px">
      <div class="os-card-title"><i class="ph-bold ph-clock-counter-clockwise"></i> Últimas OS</div>
    </div>
    <table class="os-table">
      <thead><tr><th>Nº</th><th>Cliente</th><th>Moto</th><th>Status</th></tr></thead>
      <tbody>
        <?php
        $sBadge = ['aberta'=>'aberta','em_andamento'=>'em_andamento','aguardando_pecas'=>'aguardando','finalizada'=>'finalizada','cancelada'=>'cancelada'];
        $sLabel = ['aberta'=>'Aberta','em_andamento'=>'Andamento','aguardando_pecas'=>'Aguard. Peças','finalizada'=>'Finalizada','cancelada'=>'Cancelada'];
        foreach ($os_recentes as $os): ?>
        <tr onclick="location.href='<?= BASE_URL ?>/modules/os/os_detalhes.php?id=<?= $os['numero_os'] ?>'" style="cursor:pointer">
          <td><strong style="font-family:var(--font-display)"><?= $os['numero_os'] ?></strong></td>
          <td><?= htmlspecialchars($os['cliente']) ?></td>
          <td style="color:var(--text-muted);font-size:.8rem"><?= htmlspecialchars($os['moto']) ?></td>
          <td><span class="status-badge <?= $sBadge[$os['status']] ?>"><?= $sLabel[$os['status']] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
var gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
var textColor = isDark ? '#7a8aaa' : '#64748b';
var AMBER = '#f59e0b';

Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color = textColor;

function pieChart(id, labels, data, colors) {
  new Chart(document.getElementById(id), {
    type: 'doughnut',
    data: { labels: labels, datasets: [{ data: data, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '60%',
      plugins: { legend: { position: 'bottom', labels: { padding: 16, boxWidth: 12, borderRadius: 6, usePointStyle: true, pointStyle: 'circle' } } }
    }
  });
}

pieChart('chartOS',
  ['Abertas','Em Andamento','Aguardando Peças','Finalizadas','Canceladas'],
  [<?= $os_abertas ?>,<?= $os_andamento ?>,<?= $os_aguardando ?>,<?= $os_finalizadas ?>,<?= $os_canceladas ?>],
  ['#f59e0b','#38bdf8','#ef4444','#22c55e','#64748b']
);

pieChart('chartOrc',
  ['Ativos','Aprovados','Rejeitados','Convertidos'],
  [<?= $orc_ativos ?>,<?= $orc_aprovados ?>,<?= $orc_rejeitados ?>,<?= $orc_convertidos ?>],
  ['#22c55e','#38bdf8','#ef4444','#a855f7']
);

pieChart('chartEst',
  ['Crítico','Médio','Bom'],
  [<?= $est_critico ?>,<?= $est_medio ?>,<?= $est_bom ?>],
  ['#ef4444','#f59e0b','#22c55e']
);

var pagData = <?= json_encode($pagamentos) ?>;
if (pagData.length > 0) {
  var nomes = { dinheiro:'Dinheiro', pix:'PIX', cartao_credito:'Crédito', cartao_debito:'Débito', boleto:'Boleto' };
  pieChart('chartPag',
    pagData.map(function(p){ return nomes[p.forma_pagamento] || p.forma_pagamento; }),
    pagData.map(function(p){ return p.qtd; }),
    ['#22c55e','#38bdf8','#f59e0b','#a855f7','#ef4444']
  );
} else {
  document.getElementById('pagWrap').innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);flex-direction:column;gap:8px"><i class="ph-bold ph-chart-pie" style="font-size:2rem"></i><span style="font-size:.85rem">Nenhuma venda no mês</span></div>';
}

new Chart(document.getElementById('chartVendas'), {
  type: 'bar',
  data: {
    labels: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
    datasets: [{
      label: 'Vendas (R$)',
      data: <?= json_encode($vendas_mensal) ?>,
      backgroundColor: function(ctx) {
        var g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
        g.addColorStop(0,'rgba(245,158,11,.8)'); g.addColorStop(1,'rgba(245,158,11,.2)');
        return g;
      },
      borderRadius: 8, borderSkipped: false
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: gridColor }, ticks: { color: textColor } },
      y: { grid: { color: gridColor }, ticks: { color: textColor, callback: function(v){ return 'R$ ' + v.toLocaleString('pt-BR'); } }, beginAtZero: true }
    }
  }
});
</script>

<?php include 'includes/footer.php'; ?>
