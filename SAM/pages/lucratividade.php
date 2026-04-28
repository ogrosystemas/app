<?php
/**
 * pages/lucratividade.php
 * Lucratividade real por produto — lógica completa ML
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_financeiro');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND p.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];


// ── POST: salvar configurações ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_luc_config') {
        $fields = ['regime','imposto_pct','embalagem','frete_pct','ads_pct','custo_op','margem_alvo'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                db_query("INSERT INTO tenant_settings (id,tenant_id,`key`,value) VALUES (UUID(),?,?,?)
                          ON DUPLICATE KEY UPDATE value=VALUES(value)",
                    [$tenantId, 'luc_'.$f, $_POST[$f]]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true]);
        exit;
    }
}

// ── Configurações globais de custo (salvas por tenant) ───
function luc_get(string $tenantId, string $key, mixed $default = 0): mixed {
    $r = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?", [$tenantId, $key]);
    return $r ? $r['value'] : $default;
}

$cfg = [
    'regime'         => luc_get($tenantId, 'luc_regime',          'simples'),
    'imposto_pct'    => (float)luc_get($tenantId, 'luc_imposto',   6.0),
    'embalagem'      => (float)luc_get($tenantId, 'luc_embalagem', 2.5),
    'custo_op'       => (float)luc_get($tenantId, 'luc_custo_op',  0),
    'frete_pct'      => (float)luc_get($tenantId, 'luc_frete_pct', 0),
    'ads_pct'        => (float)luc_get($tenantId, 'luc_ads_pct',   0),
    'margem_alvo'    => (float)luc_get($tenantId, 'luc_margem_alvo', 20),
];

// Pedidos do mês para ratear custo operacional
$pedidosMes = (int)(db_one(
    "SELECT COUNT(*) as c FROM orders WHERE tenant_id=?
     AND payment_status IN ('approved','APPROVED')
     AND order_date >= DATE_FORMAT(NOW(),'%Y-%m-01')",
    [$tenantId]
)['c'] ?? 1);
$custoOpUnit = $pedidosMes > 0 ? $cfg['custo_op'] / max($pedidosMes, 1) : 0;

// ── Produtos com dados de venda ──────────────────────────
$produtos = db_all(
    "SELECT p.id, p.meli_item_id, p.title, p.price, p.cost_price,
            p.ml_fee_percent, p.ipi_valor, p.ml_status, p.listing_type_id,
            p.stock_quantity, p.category_id,
            COALESCE(SUM(oi.quantity),0)    as unidades_vendidas,
            COALESCE(SUM(oi.total_price),0) as receita_total,
            COALESCE(AVG(oi.unit_price),p.price) as preco_medio_venda
     FROM products p
     LEFT JOIN order_items oi ON oi.meli_item_id = p.meli_item_id
     LEFT JOIN orders o ON o.id = oi.order_id
         AND o.tenant_id = p.tenant_id
         AND o.payment_status IN ('approved','APPROVED')
     WHERE p.tenant_id=?{$acctSql}
     GROUP BY p.id
     ORDER BY receita_total DESC, p.price DESC",
    array_merge([$tenantId], $acctP)
);

// ── Motor de cálculo de lucratividade ────────────────────
function calcLucratividade(array $p, array $cfg, float $custoOpUnit): array {
    $preco    = (float)$p['preco_medio_venda'] ?: (float)$p['price'];
    $custo    = (float)$p['cost_price'];
    $ipiValor  = (float)($p['ipi_valor'] ?? 0);
    $feePct    = (float)$p['ml_fee_percent'] ?: 14.0;

    // IPI é valor fixo em R$ vindo da NF — soma ao custo
    $custoReal = $custo + $ipiValor;

    // Taxa fixa ML
    $taxaFixa = $preco < 12.50 ? $preco * 0.5 : ($preco < 79.00 ? 6.75 : 0);

    $comissao  = $preco * ($feePct / 100);
    $frete     = $preco * ($cfg['frete_pct'] / 100);
    $ads       = $preco * ($cfg['ads_pct'] / 100);
    $imposto   = $preco * ($cfg['imposto_pct'] / 100);
    $embalagem = $cfg['embalagem'];
    $custoOp   = $custoOpUnit;

    $totalCustos = $custoReal + $comissao + $taxaFixa + $frete + $ads
                 + $imposto + $embalagem + $custoOp;

    $lucro  = $preco - $totalCustos;
    $margem = $preco > 0 ? ($lucro / $preco) * 100 : 0;
    $markup = $custoReal > 0 ? ($lucro / $custoReal) * 100 : 0;

    $divisor    = 1 - ($feePct/100) - ($cfg['frete_pct']/100)
                    - ($cfg['ads_pct']/100) - ($cfg['imposto_pct']/100);
    $precoMin   = $divisor > 0
        ? ($custoReal + $taxaFixa + $embalagem + $custoOp) / $divisor : $preco;
    $precoIdeal = $divisor - $cfg['margem_alvo']/100 > 0
        ? ($custoReal + $taxaFixa + $embalagem + $custoOp) / ($divisor - $cfg['margem_alvo']/100) : $preco;

    $score = max(0, min(100, round(($margem / 40) * 100)));

    return [
        'preco'        => $preco,
        'custo'        => $custo,
        'ipi_valor'    => round($ipiValor, 2),
        'custo_real'   => round($custoReal, 2),
        'comissao'     => round($comissao, 2),
        'taxa_fixa'    => round($taxaFixa, 2),
        'frete'        => round($frete, 2),
        'ads'          => round($ads, 2),
        'imposto'      => round($imposto, 2),
        'embalagem'    => round($embalagem, 2),
        'custo_op'     => round($custoOp, 2),
        'total_custos' => round($totalCustos, 2),
        'lucro'        => round($lucro, 2),
        'margem'       => round($margem, 1),
        'markup'       => round($markup, 1),
        'preco_min'    => round($precoMin, 2),
        'preco_ideal'  => round($precoIdeal, 2),
        'score'        => $score,
        'status'       => $lucro < 0 ? 'prejuizo'
                        : ($margem < ($cfg['margem_alvo'] / 2) ? 'critico'
                        : ($margem < $cfg['margem_alvo'] ? 'atencao' : 'ok')),
    ];
}
// Calcular para todos
$resultados = [];
$totais = ['receita'=>0,'lucro'=>0,'unidades'=>0];
foreach ($produtos as $p) {
    $luc = calcLucratividade($p, $cfg, $custoOpUnit);
    $luc['unidades_vendidas'] = (int)$p['unidades_vendidas'];
    $luc['receita_total']     = (float)$p['receita_total'];
    $luc['lucro_total']       = round($luc['lucro'] * $luc['unidades_vendidas'], 2);
    $luc['title']             = $p['title'];
    $luc['meli_item_id']      = $p['meli_item_id'];
    $luc['ml_status']         = $p['ml_status'];
    $luc['id']                = $p['id'];
    $resultados[] = $luc;

    $totais['receita']  += $luc['receita_total'];
    $totais['lucro']    += $luc['lucro_total'];
    $totais['unidades'] += $luc['unidades_vendidas'];
}

// Ordenar por lucro_total desc
usort($resultados, fn($a,$b) => $b['lucro_total'] <=> $a['lucro_total']);

$margemGeralPct = $totais['receita'] > 0
    ? round($totais['lucro'] / $totais['receita'] * 100, 1) : 0;

$statusColors = [
    'ok'       => ['#22c55e','OK'],
    'atencao'  => ['#f59e0b','Atenção'],
    'critico'  => ['#f97316','Crítico'],
    'prejuizo' => ['#ef4444','Prejuízo'],
];

$title = 'Lucratividade';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Lucratividade por Produto</h1>
      <p style="font-size:11px;color:#5E5E5A">Margem real após comissão ML, frete, impostos e custos operacionais</p>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="document.getElementById('modal-config').style.display='flex'" class="btn-secondary" style="font-size:12px">
        <i data-lucide="settings-2" style="width:12px;height:12px"></i> Configurar custos
      </button>
      <a href="/api/lucratividade_export.php" class="btn-secondary" style="font-size:12px;text-decoration:none;display:flex;align-items:center;gap:5px">
        <i data-lucide="file-spreadsheet" style="width:12px;height:12px"></i> Exportar Excel
      </a>
    </div>
  </div>

  <!-- KPIs gerais -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:20px">
    <?php
    $prejuizo  = count(array_filter($resultados, fn($r)=>$r['status']==='prejuizo'));
    $critico   = count(array_filter($resultados, fn($r)=>$r['status']==='critico'));
    $atencao   = count(array_filter($resultados, fn($r)=>$r['status']==='atencao'));
    $ok        = count(array_filter($resultados, fn($r)=>$r['status']==='ok'));
    ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #a855f7;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="trending-up" style="width:10px;height:10px;color:#a855f7"></i> Receita total
      </div>
      <div style="font-size:18px;font-weight:700;color:#E8E8E6">R$ <?= number_format($totais['receita'],2,',','.') ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="wallet" style="width:10px;height:10px;color:#22c55e"></i> Lucro líquido total
      </div>
      <div style="font-size:18px;font-weight:700;color:<?= $totais['lucro']>=0?'#22c55e':'#ef4444' ?>">R$ <?= number_format($totais['lucro'],2,',','.') ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="percent" style="width:10px;height:10px;color:#3483FA"></i> Margem geral
      </div>
      <div style="font-size:18px;font-weight:700;color:#3483FA"><?= $margemGeralPct ?>%</div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">Meta: <?= $cfg['margem_alvo'] ?>%</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="alert-triangle" style="width:10px;height:10px;color:#ef4444"></i> Em prejuízo
      </div>
      <div style="font-size:18px;font-weight:700;color:<?= $prejuizo>0?'#ef4444':'#22c55e' ?>"><?= $prejuizo ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px"><?= $critico ?> críticos · <?= $atencao ?> atenção</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="check-circle" style="width:10px;height:10px;color:#22c55e"></i> Margem saudável
      </div>
      <div style="font-size:18px;font-weight:700;color:#22c55e"><?= $ok ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">acima de <?= $cfg['margem_alvo'] ?>%</div>
    </div>
  </div>

  <!-- Aviso se sem custo cadastrado -->
  <?php $semCusto = count(array_filter($resultados, fn($r)=>$r['custo']<=0)); ?>
  <?php if ($semCusto > 0): ?>
  <div style="background:rgba(245,158,11,.06);border:0.5px solid rgba(245,158,11,.3);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:11px;color:#f59e0b;display:flex;align-items:center;gap:8px">
    <i data-lucide="alert-triangle" style="width:14px;height:14px;flex-shrink:0"></i>
    <span><strong><?= $semCusto ?> produto<?= $semCusto>1?'s':'' ?></strong> sem custo cadastrado — o cálculo de margem ficará incorreto. Edite os anúncios e preencha o campo "Custo".</span>
  </div>
  <?php endif; ?>

  <!-- Tabela principal -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <span style="font-size:12px;font-weight:500;color:#E8E8E6"><?= count($resultados) ?> produtos analisados</span>
      <!-- Filtro rápido por status -->
      <div style="display:flex;gap:4px;margin-left:auto;flex-wrap:wrap">
        <?php foreach ([''=>'Todos','ok'=>'✅ OK','atencao'=>'⚠️ Atenção','critico'=>'🔴 Crítico','prejuizo'=>'💸 Prejuízo'] as $s=>$l): ?>
        <button onclick="filtrarStatus('<?= $s ?>')" id="fst-<?= $s ?: 'all' ?>"
          style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:500;cursor:pointer;border:0.5px solid <?= $s===''?'#3483FA':'#2E2E33' ?>;background:<?= $s===''?'rgba(52,131,250,.15)':'transparent' ?>;color:<?= $s===''?'#3483FA':'#5E5E5A' ?>;transition:all .15s">
          <?= $l ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;font-size:12px" id="luc-table">
        <thead>
          <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
            <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500;min-width:200px">Produto</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Preço</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Custo</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Comissão</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Frete+</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Impostos</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500;border-left:0.5px solid #2E2E33">Lucro/un.</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Margem</th>
            <th style="padding:10px 10px;text-align:center;color:#5E5E5A;font-weight:500">Score</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Vendas</th>
            <th style="padding:10px 10px;text-align:right;color:#5E5E5A;font-weight:500">Lucro total</th>
            <th style="padding:10px 10px;text-align:center;color:#5E5E5A;font-weight:500">Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($resultados as $r):
          [$sColor, $sLabel] = $statusColors[$r['status']];
          $freteTotal = $r['frete'] + $r['ads'] + $r['embalagem'] + $r['custo_op'];
        ?>
        <tr class="luc-row" data-status="<?= $r['status'] ?>"
          style="border-bottom:0.5px solid #2E2E33;transition:background .12s"
          onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
          <td style="padding:10px 14px">
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:4px;height:32px;border-radius:2px;background:<?= $sColor ?>;flex-shrink:0"></div>
              <div style="min-width:0">
                <div style="font-size:11px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:220px"><?= htmlspecialchars($r['title']) ?></div>
                <div style="display:flex;align-items:center;gap:5px;margin-top:2px">
                  <span style="font-size:9px;padding:1px 6px;border-radius:5px;background:<?= $sColor ?>15;color:<?= $sColor ?>;font-weight:600"><?= $sLabel ?></span>
                  <?php if ($r['meli_item_id']): ?>
                  <span style="font-size:9px;color:#5E5E5A;font-family:monospace"><?= $r['meli_item_id'] ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </td>
          <td style="padding:10px 10px;text-align:right;color:#E8E8E6">R$ <?= number_format($r['preco'],2,',','.') ?></td>
          <td style="padding:10px 10px;text-align:right;color:<?= $r['custo']>0?'#9A9A96':'#ef4444' ?>">
            <?php if ($r['custo']>0): ?>
            R$ <?= number_format($r['custo'],2,',','.') ?>
            <?php if (($r['ipi_valor']??0)>0): ?>
            <div style="font-size:9px;color:#f59e0b">IPI R$<?= number_format($r['ipi_valor'],2,',','.') ?></div>
            <?php endif; ?>
            <?php else: ?>⚠ —<?php endif; ?>
          </td>
          <td style="padding:10px 10px;text-align:right;color:#ef4444">
            <span title="Taxa fixa: R$<?= $r['taxa_fixa'] ?>">R$ <?= number_format($r['comissao'],2,',','.') ?></span>
            <?php if ($r['taxa_fixa']>0): ?>
            <span style="font-size:9px;color:#5E5E5A" title="Taxa fixa ML">+<?= number_format($r['taxa_fixa'],2,',','.') ?></span>
            <?php endif; ?>
          </td>
          <td style="padding:10px 10px;text-align:right;color:#f59e0b" title="Frete R$<?= $r['frete'] ?> + ADS R$<?= $r['ads'] ?> + Embal. R$<?= $r['embalagem'] ?> + Op. R$<?= $r['custo_op'] ?>">
            R$ <?= number_format($freteTotal,2,',','.') ?>
          </td>
          <td style="padding:10px 10px;text-align:right;color:#a855f7">R$ <?= number_format($r['imposto'],2,',','.') ?></td>
          <td style="padding:10px 10px;text-align:right;border-left:0.5px solid #2E2E33;font-weight:700;color:<?= $r['lucro']>=0?'#22c55e':'#ef4444' ?>">
            R$ <?= number_format($r['lucro'],2,',','.') ?>
          </td>
          <td style="padding:10px 10px;text-align:right">
            <span style="font-size:13px;font-weight:700;color:<?= $r['margem']>=$cfg['margem_alvo']?'#22c55e':($r['margem']>0?'#f59e0b':'#ef4444') ?>">
              <?= $r['margem'] ?>%
            </span>
          </td>
          <td style="padding:10px 10px;text-align:center">
            <div style="position:relative;width:40px;height:40px;margin:0 auto" title="Score de rentabilidade: <?= $r['score'] ?>/100">
              <svg viewBox="0 0 36 36" style="width:40px;height:40px;transform:rotate(-90deg)">
                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#2E2E33" stroke-width="3"/>
                <circle cx="18" cy="18" r="15.9" fill="none"
                  stroke="<?= $r['score']>=70?'#22c55e':($r['score']>=40?'#f59e0b':'#ef4444') ?>"
                  stroke-width="3"
                  stroke-dasharray="<?= round($r['score']),100-round($r['score']) ?>"
                  stroke-linecap="round"/>
              </svg>
              <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#E8E8E6"><?= $r['score'] ?></div>
            </div>
          </td>
          <td style="padding:10px 10px;text-align:right;color:#3483FA"><?= number_format($r['unidades_vendidas'],0,',','.') ?></td>
          <td style="padding:10px 10px;text-align:right;font-weight:600;color:<?= $r['lucro_total']>=0?'#22c55e':'#ef4444' ?>">
            R$ <?= number_format($r['lucro_total'],2,',','.') ?>
          </td>
          <td style="padding:10px 10px;text-align:center">
            <button onclick="abrirDetalhes(<?= htmlspecialchars(json_encode($r)) ?>)"
              style="padding:4px 10px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;font-size:10px;cursor:pointer;white-space:nowrap">
              Detalhar
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─────────────────────────────────────────────
     Modal: Configuração de custos globais
───────────────────────────────────────────── -->
<div id="modal-config" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:center;justify-content:center;z-index:500;padding:16px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:520px;box-shadow:0 24px 64px rgba(0,0,0,.6)">
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
      <i data-lucide="settings-2" style="width:16px;height:16px;color:#3483FA"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Configurar custos globais</span>
      <button onclick="document.getElementById('modal-config').style.display='none'" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>
    <form id="form-config" style="padding:20px;display:grid;gap:14px">

      <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:8px;padding:10px 12px;font-size:11px;color:#9A9A96;line-height:1.5">
        Estes valores são aplicados a <strong style="color:#E8E8E6">todos os produtos</strong>. Configure uma vez e o cálculo é atualizado automaticamente.
      </div>

      <!-- Regime tributário -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Regime tributário</label>
        <select name="regime" class="input">
          <option value="mei"      <?= $cfg['regime']==='mei'     ?'selected':'' ?>>MEI (DAS fixo)</option>
          <option value="simples"  <?= $cfg['regime']==='simples' ?'selected':'' ?>>Simples Nacional</option>
          <option value="presumido"<?= $cfg['regime']==='presumido'?'selected':'' ?>>Lucro Presumido</option>
          <option value="real"     <?= $cfg['regime']==='real'    ?'selected':'' ?>>Lucro Real</option>
        </select>
      </div>

      <!-- Imposto % -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
          Alíquota de imposto sobre venda (%)
          <span style="color:#5E5E5A">— Simples: ~6% · Presumido: ~11.33% · MEI: ~0%</span>
        </label>
        <input type="number" name="imposto_pct" step="0.1" min="0" max="100" value="<?= $cfg['imposto_pct'] ?>" class="input" placeholder="6.0">
      </div>

      <!-- Embalagem -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Custo de embalagem por unidade (R$)</label>
        <input type="number" name="embalagem" step="0.1" min="0" value="<?= $cfg['embalagem'] ?>" class="input" placeholder="2.50">
      </div>

      <!-- Frete -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
          Custo de frete sobre venda (%)
          <span style="color:#5E5E5A">— parcela que o vendedor paga ao ML após subsídio</span>
        </label>
        <input type="number" name="frete_pct" step="0.1" min="0" value="<?= $cfg['frete_pct'] ?>" class="input" placeholder="0">
      </div>

      <!-- ADS -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
          Gasto com Mercado Ads (% sobre vendas)
        </label>
        <input type="number" name="ads_pct" step="0.1" min="0" value="<?= $cfg['ads_pct'] ?>" class="input" placeholder="0">
      </div>

      <!-- Custo operacional fixo mensal -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
          Custos fixos mensais (R$)
          <span style="color:#5E5E5A">— aluguel, internet, salários — rateados por pedido</span>
        </label>
        <input type="number" name="custo_op" step="10" min="0" value="<?= $cfg['custo_op'] ?>" class="input" placeholder="0">
      </div>

      <!-- Margem alvo -->
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
          Meta de margem líquida (%)
          <span style="color:#5E5E5A">— define alertas de produto abaixo do alvo</span>
        </label>
        <input type="number" name="margem_alvo" step="1" min="1" max="90" value="<?= $cfg['margem_alvo'] ?>" class="input" placeholder="20">
      </div>

      <button type="button" onclick="salvarConfig()" class="btn-primary" style="font-size:13px">
        <i data-lucide="save" style="width:13px;height:13px"></i> Salvar e recalcular
      </button>
    </form>
  </div>
</div>

<!-- ─────────────────────────────────────────────
     Modal: Detalhamento por produto
───────────────────────────────────────────── -->
<div id="modal-detalhe" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:center;justify-content:center;z-index:500;padding:16px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:560px;box-shadow:0 24px 64px rgba(0,0,0,.6)">
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
      <i data-lucide="bar-chart-2" style="width:16px;height:16px;color:#a855f7"></i>
      <span id="detalhe-title" style="font-size:13px;font-weight:600;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
      <button onclick="document.getElementById('modal-detalhe').style.display='none'" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px;flex-shrink:0">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:16px" id="detalhe-body"></div>
  </div>
</div>

<script>
lucide.createIcons();

// ── Filtro por status ─────────────────────────────────────
function filtrarStatus(status) {
  document.querySelectorAll('.luc-row').forEach(row => {
    row.style.display = (!status || row.dataset.status === status) ? '' : 'none';
  });
  ['all','ok','atencao','critico','prejuizo'].forEach(s => {
    const btn = document.getElementById('fst-'+s);
    if (!btn) return;
    const active = (s === (status || 'all'));
    btn.style.background = active ? 'rgba(52,131,250,.15)' : 'transparent';
    btn.style.borderColor = active ? '#3483FA' : '#2E2E33';
    btn.style.color = active ? '#3483FA' : '#5E5E5A';
  });
}

// ── Modal de detalhamento ─────────────────────────────────
function abrirDetalhes(r) {
  document.getElementById('detalhe-title').textContent = r.title;
  document.getElementById('modal-detalhe').style.display = 'flex';

  const fmt = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  const pct = v => parseFloat(v).toFixed(1) + '%';

  const itens = [
    ['Preço de venda',     fmt(r.preco),     '#E8E8E6', false],
    ['Custo do produto',   fmt(r.custo),       '#9A9A96', true],
    ['IPI (valor NF)',       fmt(r.ipi_valor||0), '#f59e0b', (r.ipi_valor||0) > 0],
    ['Custo real c/ IPI',  fmt(r.custo_real||r.custo), '#f59e0b', (r.ipi_pct||0) > 0],
    ['Comissão ML',        fmt(r.comissao),  '#ef4444', true],
    ['Taxa fixa ML',       fmt(r.taxa_fixa), '#ef4444', r.taxa_fixa > 0],
    ['Frete (parcela)',    fmt(r.frete),     '#f59e0b', true],
    ['Mercado Ads',        fmt(r.ads),       '#f59e0b', r.ads > 0],
    ['Embalagem',          fmt(r.embalagem), '#f59e0b', true],
    ['Custo operacional',  fmt(r.custo_op),  '#f59e0b', r.custo_op > 0],
    ['Impostos',           fmt(r.imposto),   '#a855f7', true],
  ];

  const statusColors = {ok:'#22c55e',atencao:'#f59e0b',critico:'#f97316',prejuizo:'#ef4444'};
  const statusLabels = {ok:'Saudável',atencao:'Atenção',critico:'Crítico',prejuizo:'Prejuízo'};
  const sColor = statusColors[r.status] || '#5E5E5A';

  let html = `
    <!-- Waterfall de custos -->
    <div style="background:#252528;border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:6px">
      <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Composição de custos</div>
      ${itens.map(([label,val,color,show]) => show ? `
        <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px">
          <span style="color:#9A9A96">${label}</span>
          <span style="color:${color};font-weight:500">${val}</span>
        </div>` : '').join('')}
      <div style="border-top:0.5px solid #2E2E33;margin-top:6px;padding-top:6px;display:flex;justify-content:space-between;font-size:13px;font-weight:700">
        <span style="color:#E8E8E6">Lucro líquido / un.</span>
        <span style="color:${r.lucro>=0?'#22c55e':'#ef4444'}">${fmt(r.lucro)} (${pct(r.margem)})</span>
      </div>
    </div>

    <!-- Métricas estratégicas -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div style="background:#252528;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Preço mínimo (zero lucro)</div>
        <div style="font-size:16px;font-weight:700;color:#ef4444">${fmt(r.preco_min)}</div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px">abaixo disso = prejuízo</div>
      </div>
      <div style="background:#252528;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Preço ideal (meta ${parseFloat('<?= $cfg['margem_alvo'] ?>').toFixed(0)}%)</div>
        <div style="font-size:16px;font-weight:700;color:#22c55e">${fmt(r.preco_ideal)}</div>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px">para atingir a meta</div>
      </div>
      <div style="background:#252528;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Markup sobre custo</div>
        <div style="font-size:16px;font-weight:700;color:#3483FA">${pct(r.markup)}</div>
      </div>
      <div style="background:#252528;border-radius:8px;padding:12px;text-align:center">
        <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Score de rentabilidade</div>
        <div style="font-size:16px;font-weight:700;color:${sColor}">${r.score}/100</div>
        <div style="font-size:10px;color:${sColor};margin-top:2px">${statusLabels[r.status]||r.status}</div>
      </div>
    </div>

    <!-- Performance real -->
    <div style="background:#252528;border-radius:10px;padding:14px">
      <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">Performance real (histórico)</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center">
        <div>
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:2px">Unidades vendidas</div>
          <div style="font-size:16px;font-weight:700;color:#3483FA">${parseInt(r.unidades_vendidas).toLocaleString('pt-BR')}</div>
        </div>
        <div>
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:2px">Receita gerada</div>
          <div style="font-size:16px;font-weight:700;color:#E8E8E6">${fmt(r.receita_total)}</div>
        </div>
        <div>
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:2px">Lucro total</div>
          <div style="font-size:16px;font-weight:700;color:${r.lucro_total>=0?'#22c55e':'#ef4444'}">${fmt(r.lucro_total)}</div>
        </div>
      </div>
    </div>`;

  document.getElementById('detalhe-body').innerHTML = html;
  lucide.createIcons();
}

// ── Salvar configurações ──────────────────────────────────
async function salvarConfig() {
  const form = document.getElementById('form-config');
  const fd = new FormData(form);
  fd.append('action', 'save_luc_config');
  const r = await fetch('/pages/lucratividade.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast('Configurações salvas! Recalculando...', 'success');
    document.getElementById('modal-config').style.display = 'none';
    setTimeout(() => location.reload(), 800);
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}

// Fechar modais ao clicar fora
['modal-config','modal-detalhe'].forEach(id => {
  document.getElementById(id).addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
