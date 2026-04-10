<?php
require_once '../../config/config.php';
checkAuth();

// Sanitize date inputs - only allow YYYY-MM-DD format
$data_inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_inicio'] ?? '') ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim']    ?? '') ? $_GET['data_fim']    : date('Y-m-t');

$stmt = $db->prepare("SELECT DATE(data_venda) as data, COUNT(*) as total_vendas, SUM(total) as valor_total FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada' GROUP BY DATE(data_venda) ORDER BY data");
$stmt->execute([$data_inicio, $data_fim]);
$vendas_periodo = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT p.nome, SUM(vi.quantidade) as qtd, SUM(vi.total) as valor FROM venda_itens vi JOIN produtos p ON vi.produto_id = p.id JOIN vendas v ON vi.venda_id = v.id WHERE DATE(v.data_venda) BETWEEN ? AND ? GROUP BY p.id ORDER BY qtd DESC LIMIT 10");
$stmt->execute([$data_inicio, $data_fim]);
$produtos_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) as total_vendas FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$resumo = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) FROM ordens_servico WHERE DATE(data_abertura) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_os = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM orcamentos WHERE DATE(data_criacao) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_orc = $stmt->fetchColumn();
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Relatórios Gerenciais</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<h2 class="mb-4"><i class="ph-bold ph-chart-line-up"></i> Relatórios Gerenciais</h2>
        
        <div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label>Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
            </div>
            <div class="col-md-4">
                <label>Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="gerarPDF()"><i class="bi bi-file-pdf"></i> Gerar PDF</button>
            </div>
        </form>
    </div>
</div>
        
        <div class="row mb-4">
            <div class="col-md-4 mb-3"><div class="card text-white bg-success"><div class="card-body"><h6>Total de Vendas</h6><h3>R$ <?php echo number_format($resumo['total_vendas'], 2, ',', '.'); ?></h3></div></div></div>
            <div class="col-md-4 mb-3"><div class="card text-white bg-info"><div class="card-body"><h6>Ordens de Serviço</h6><h3><?php echo $total_os; ?></h3></div></div></div>
            <div class="col-md-4 mb-3"><div class="card text-white bg-warning"><div class="card-body"><h6>Orçamentos</h6><h3><?php echo $total_orc; ?></h3></div></div></div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0">Vendas por Dia</h5></div><div class="card-body"><canvas id="graficoVendas" style="height: 300px;"></canvas></div></div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card"><div class="card-header bg-warning text-dark"><h5 class="mb-0">Produtos Mais Vendidos</h5></div><div class="card-body"><canvas id="graficoProdutos" style="height: 300px;"></canvas></div></div>
            </div>
        </div>
        
        <div class="card"><div class="card-header bg-secondary text-white"><h5 class="mb-0">Produtos Mais Vendidos</h5></div><div class="card-body"><table class="table table-striped"><thead><tr><th>Produto</th><th>Quantidade</th><th>Valor Total</th></tr></thead><tbody><?php foreach($produtos_top as $p): ?><tr><td><?php echo $p['nome']; ?></td><td><?php echo $p['qtd']; ?></td><td>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</main>



	<script>
function gerarPDF() {
    var data_inicio = document.querySelector('input[name="data_inicio"]').value;
    var data_fim = document.querySelector('input[name="data_fim"]').value;
    window.open('gerar_relatorio_pdf.php?data_inicio=' + data_inicio + '&data_fim=' + data_fim, '_blank');
}
</script>

<?php include '../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
  var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  var gridColor = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
  var textColor = isDark ? '#7a8aaa' : '#64748b';
  var accent    = '#f59e0b';

  Chart.defaults.font.family = "'DM Sans', sans-serif";
  Chart.defaults.color = textColor;

  var vendasData   = <?= json_encode($vendas_periodo) ?>;
  var produtosData = <?= json_encode($produtos_top) ?>;

  // ── Vendas por Dia — Vertical Bar Chart ─────────────
  var ctxV = document.getElementById('graficoVendas');
  if (ctxV) {
    new Chart(ctxV, {
      type: 'bar',
      data: {
        labels: vendasData.map(function(v) {
          var d = v.data.split('-'); return d[2]+'/'+d[1];
        }),
        datasets: [{
          label: 'Vendas (R$)',
          data: vendasData.map(function(v) { return parseFloat(v.valor_total) || 0; }),
          backgroundColor: function(ctx) {
            var chart = ctx.chart;
            var gradient = chart.ctx.createLinearGradient(0, 0, 0, chart.height);
            gradient.addColorStop(0, 'rgba(245,158,11,.85)');
            gradient.addColorStop(1, 'rgba(245,158,11,.15)');
            return gradient;
          },
          borderColor: accent,
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                return ' R$ ' + ctx.raw.toLocaleString('pt-BR', {minimumFractionDigits:2});
              }
            }
          }
        },
        scales: {
          x: { grid: { color: 'transparent' }, ticks: { color: textColor } },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
            },
            beginAtZero: true
          }
        }
      }
    });
  }

  // ── Produtos Mais Vendidos — Horizontal Bar ──────────
  var ctxP = document.getElementById('graficoProdutos');
  if (ctxP) {
    new Chart(ctxP, {
      type: 'bar',
      data: {
        labels: produtosData.map(function(p) { return p.nome; }),
        datasets: [{
          label: 'Quantidade',
          data: produtosData.map(function(p) { return parseInt(p.qtd) || 0; }),
          backgroundColor: 'rgba(245,158,11,.75)',
          borderColor: accent,
          borderWidth: 1,
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor }, beginAtZero: true },
          y: { grid: { color: 'transparent' }, ticks: { color: textColor, font: { size: 11 } } }
        }
      }
    });
  }
})();
</script>
