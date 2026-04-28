<?php
/**
 * pages/metricas_ads.php
 * Métricas de anúncios patrocinados (ADS) do Mercado Livre
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

// Período
$period = in_array((int)($_GET['period'] ?? 7), [7,14,30]) ? (int)($_GET['period'] ?? 7) : 7;

$title = 'Métricas ADS';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Métricas de ADS</h1>
      <p style="font-size:11px;color:#5E5E5A">Desempenho dos anúncios patrocinados no Mercado Livre</p>
    </div>
    <div style="display:flex;gap:6px;align-items:center">
      <?php foreach (['7'=>'7 dias','14'=>'14 dias','30'=>'30 dias'] as $k=>$label): ?>
      <a href="?period=<?= $k ?>" style="padding:6px 12px;border-radius:8px;font-size:11px;font-weight:500;text-decoration:none;border:0.5px solid <?= $period===$k?'#3483FA':'#2E2E33' ?>;background:<?= $period===$k?'rgba(52,131,250,.1)':'transparent' ?>;color:<?= $period===$k?'#3483FA':'#5E5E5A' ?>">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
      <button onclick="loadAds()" id="btn-load-ads" class="btn-primary" style="font-size:12px;padding:6px 12px">
        <i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Carregar
      </button>
    </div>
  </div>

  <!-- KPIs -->
  <div id="ads-kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:20px">
    <?php foreach ([
      ['impressions','Impressões','eye','#3483FA'],
      ['clicks','Cliques','mouse-pointer','#f59e0b'],
      ['ctr','CTR (%)','percent','#22c55e'],
      ['conversions','Vendas','shopping-bag','#22c55e'],
      ['spend','Gasto (R$)','credit-card','#ef4444'],
      ['acos','ACoS (%)','trending-up','#a855f7'],
    ] as [$key,$label,$icon,$color]): ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:12px 14px">
      <div style="display:flex;align-items:center;gap:5px;margin-bottom:6px">
        <i data-lucide="<?= $icon ?>" style="width:11px;height:11px;color:<?= $color ?>"></i>
        <span style="font-size:10px;color:#5E5E5A"><?= $label ?></span>
      </div>
      <div id="kpi-<?= $key ?>" style="font-size:20px;font-weight:700;color:#E8E8E6">—</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gráfico de performance -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px;margin-bottom:20px">
    <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:14px">Performance diária</div>
    <canvas id="ads-chart" style="max-height:180px"></canvas>
  </div>

  <!-- Tabela por anúncio -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33">
      <span style="font-size:12px;font-weight:500;color:#E8E8E6">Performance por anúncio</span>
    </div>
    <div id="ads-table-wrap" style="overflow-x:auto">
      <div style="text-align:center;padding:48px;color:#5E5E5A;font-size:12px">
        <i data-lucide="bar-chart-2" style="width:28px;height:28px;margin:0 auto 10px;display:block"></i>
        Clique em Carregar para buscar as métricas do ML
      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

let adsChart = null;
const period = <?= (int)$period ?>;

async function loadAds() {
  const btn = document.getElementById('btn-load-ads');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Carregando...';
  lucide.createIcons();

  try {
    const r = await fetch(`/api/metricas_ads.php?period=${period}`);
    const d = await r.json();

    if (!d.ok) {
      toast(d.error || 'Erro ao buscar métricas', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Carregar';
      lucide.createIcons();
      return;
    }

    // KPIs
    const fmt = (v,dec=0) => v != null ? Number(v).toLocaleString('pt-BR',{minimumFractionDigits:dec,maximumFractionDigits:dec}) : '—';
    document.getElementById('kpi-impressions').textContent = fmt(d.totals?.impressions);
    document.getElementById('kpi-clicks').textContent      = fmt(d.totals?.clicks);
    document.getElementById('kpi-ctr').textContent         = fmt(d.totals?.ctr, 2) + '%';
    document.getElementById('kpi-conversions').textContent = fmt(d.totals?.conversions);
    document.getElementById('kpi-spend').textContent       = 'R$ ' + fmt(d.totals?.spend, 2);
    document.getElementById('kpi-acos').textContent        = fmt(d.totals?.acos, 2) + '%';

    // Gráfico
    const daily = d.daily || [];
    if (daily.length && typeof Chart !== 'undefined') {
      const ctx = document.getElementById('ads-chart').getContext('2d');
      if (adsChart) adsChart.destroy();
      adsChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: daily.map(r => r.date),
          datasets: [
            { label: 'Cliques', data: daily.map(r => r.clicks), backgroundColor: 'rgba(52,131,250,.6)', borderRadius: 4 },
            { label: 'Conversões', data: daily.map(r => r.conversions), backgroundColor: 'rgba(34,197,94,.6)', borderRadius: 4 },
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { labels: { color: '#9A9A96', font: { size: 11 } } } },
          scales: {
            x: { ticks: { color: '#5E5E5A', font: { size: 10 } }, grid: { color: '#2E2E33' } },
            y: { ticks: { color: '#5E5E5A', font: { size: 10 } }, grid: { color: '#2E2E33' } }
          }
        }
      });
    }

    // Tabela
    const items = d.items || [];
    if (!items.length) {
      document.getElementById('ads-table-wrap').innerHTML =
        '<div style="text-align:center;padding:32px;color:#5E5E5A;font-size:12px">Nenhum anúncio patrocinado encontrado no período</div>';
    } else {
      document.getElementById('ads-table-wrap').innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:12px">
          <thead>
            <tr style="border-bottom:0.5px solid #2E2E33">
              <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Anúncio</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Impressões</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Cliques</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">CTR</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Conversões</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Gasto</th>
              <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">ACoS</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(it => `
              <tr style="border-bottom:0.5px solid #2E2E33">
                <td style="padding:10px 14px;max-width:250px">
                  <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#E8E8E6">${it.title || it.item_id}</div>
                  <div style="font-size:10px;color:#5E5E5A;font-family:monospace">${it.item_id}</div>
                </td>
                <td style="padding:10px 14px;text-align:right;color:#9A9A96">${fmt(it.impressions)}</td>
                <td style="padding:10px 14px;text-align:right;color:#3483FA">${fmt(it.clicks)}</td>
                <td style="padding:10px 14px;text-align:right;color:#f59e0b">${fmt(it.ctr,2)}%</td>
                <td style="padding:10px 14px;text-align:right;color:#22c55e">${fmt(it.conversions)}</td>
                <td style="padding:10px 14px;text-align:right;color:#ef4444">R$ ${fmt(it.spend,2)}</td>
                <td style="padding:10px 14px;text-align:right;color:${it.acos > 20 ? '#ef4444' : '#22c55e'}">${fmt(it.acos,2)}%</td>
              </tr>`).join('')}
          </tbody>
        </table>`;
    }

    toast('Métricas carregadas!', 'success');
  } catch(e) {
    toast('Erro de conexão', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '<i data-lucide="refresh-cw" style="width:12px;height:12px"></i> Carregar';
  lucide.createIcons();
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
