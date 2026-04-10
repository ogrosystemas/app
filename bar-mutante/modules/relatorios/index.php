<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();
$_tema = class_exists('DB') ? DB::cfg('tema','dark') : 'dark';
$_cor  = class_exists('DB') ? DB::cfg('cor_primaria','#f59e0b') : '#f59e0b';
$_cor2 = class_exists('DB') ? DB::cfg('cor_secundaria','#d97706') : '#d97706';

$tab = $_GET['tab'] ?? 'financeiro';
$mes = (int)($_GET['mes'] ?? date('m'));
$ano = (int)($_GET['ano'] ?? date('Y'));
$ini = sprintf('%04d-%02d-01 00:00:00', $ano, $mes);
$fim = date('Y-m-t', mktime(0,0,0,$mes,1,$ano)) . ' 23:59:59';

// ── Dados Financeiros ──
$fat_total  = DB::row("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as n FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ?", [$ini,$fim]);
$fat_forma  = DB::all("SELECT forma_pagamento, COUNT(*) as n, SUM(total) as total FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ? GROUP BY forma_pagamento ORDER BY total DESC", [$ini,$fim]);
$fat_dia    = DB::all("SELECT DATE(data_venda) as dia, SUM(total) as total, COUNT(*) as n FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ? GROUP BY DATE(data_venda) ORDER BY dia", [$ini,$fim]);
$top_prods  = DB::all("SELECT vi.descricao, SUM(vi.quantidade) as qtd, SUM(vi.total) as total FROM venda_itens vi JOIN vendas v ON vi.venda_id=v.id WHERE v.status='pago' AND v.data_venda BETWEEN ? AND ? GROUP BY vi.produto_id ORDER BY total DESC LIMIT 10", [$ini,$fim]);
$vendas_dia_atual = DB::row("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as n FROM vendas WHERE status='pago' AND DATE(data_venda)=CURDATE()");
$caixa_aberto = caixaAberto();

// ── Dados Estoque ──
$movs_est   = DB::all("SELECT m.*,p.nome as produto_nome,p.unidade_estoque FROM estoque_movimentacoes m LEFT JOIN produtos p ON m.produto_id=p.id WHERE m.created_at BETWEEN ? AND ? ORDER BY m.created_at DESC LIMIT 100", [$ini,$fim]);
$valor_estoque = DB::row("SELECT COALESCE(SUM(estoque_atual*preco_custo),0) as t FROM produtos WHERE ativo=1");
$sem_estoque = DB::count('produtos',"ativo=1 AND estoque_atual<=0 AND tipo NOT IN ('chopp_barril','dose','drink','combo')");

$meses_nome = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

// Evolução últimos 6 meses
$evolucao = [];
for ($i=5;$i>=0;$i--) {
    $ts = strtotime("-{$i} months", mktime(0,0,0,date('m'),1,date('Y')));
    $m_ini = date('Y-m-01 00:00:00',$ts);
    $m_fim = date('Y-m-t 23:59:59',$ts);
    $r = DB::row("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as n FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ?",[$m_ini,$m_fim]);
    $evolucao[] = ['label'=>$meses_nome[(int)date('m',$ts)].'/'.date('y',$ts),'total'=>(float)$r['t'],'n'=>(int)$r['n']];
}
$max_evo = max(1,...array_column($evolucao,'total'));
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatórios — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>
.bar-chart{display:flex;align-items:flex-end;gap:6px;height:140px;padding:0 4px;}
.bar-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;}
.bar-fill{width:100%;background:linear-gradient(180deg,var(--amber),var(--amber-dark));border-radius:4px 4px 0 0;min-height:4px;transition:height .5s;}
.bar-lbl{font-size:.6rem;color:var(--text-muted);white-space:nowrap;}
.bar-val{font-size:.62rem;color:var(--amber);font-weight:700;white-space:nowrap;}
.forma-row{display:flex;align-items:center;gap:.5rem;padding:.35rem 0;border-bottom:1px solid var(--border);}
.forma-bar{flex:1;height:6px;background:var(--bg-card2);border-radius:3px;overflow:hidden;}
.forma-bar-fill{height:100%;background:var(--amber);border-radius:3px;}
</style>
<style>:root{--amber:<?= $_cor ?>;--amber-dark:<?= $_cor2 ?>;}</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/nav.php'; ?>
<div class="admin-content">
<div class="page-header">
  <h4><i class="ph-bold ph-chart-bar me-2"></i>Relatórios</h4>
  <div class="d-flex gap-2 no-print">

    <?php if ($tab === 'financeiro'): ?>
    <a href="<?= BASE_URL ?>api/pdf.php?tipo=financeiro&mes=<?= $mes ?>&ano=<?= $ano ?>"
       target="_blank" class="btn btn-sm btn-pdf">
      <i class="ph-bold ph-file-pdf me-1"></i>Baixar PDF
    </a>
    <?php elseif ($tab === 'estoque'): ?>
    <a href="<?= BASE_URL ?>api/pdf.php?tipo=estoque"
       target="_blank" class="btn btn-sm btn-pdf">
      <i class="ph-bold ph-file-pdf me-1"></i>Baixar PDF
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Filtro período -->
<div class="admin-card mb-3 no-print">
  <form class="d-flex gap-3 align-items-end flex-wrap">
    <input type="hidden" name="tab" value="<?= h($tab) ?>">
    <div>
      <label class="form-label">Mês</label>
      <select name="mes" class="form-select form-select-sm" style="width:110px">
        <?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$mes==$m?'selected':''?>><?=$meses_nome[$m]?></option><?php endfor; ?>
      </select>
    </div>
    <div>
      <label class="form-label">Ano</label>
      <input type="number" name="ano" class="form-control form-control-sm" value="<?=$ano?>" style="width:90px" min="2020">
    </div>
    <button class="btn btn-amber btn-sm"><i class="ph-bold ph-magnifying-glass me-1"></i>Filtrar</button>
  </form>
</div>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3 no-print">
  <a href="?tab=financeiro&mes=<?=$mes?>&ano=<?=$ano?>" class="btn btn-sm <?=$tab==='financeiro'?'btn-amber':'btn-outline-secondary'?>"><i class="ph-bold ph-coins me-1"></i>Financeiro</a>
  <a href="?tab=estoque&mes=<?=$mes?>&ano=<?=$ano?>" class="btn btn-sm <?=$tab==='estoque'?'btn-amber':'btn-outline-secondary'?>"><i class="ph-bold ph-warehouse me-1"></i>Estoque</a>
  <a href="?tab=vendas&mes=<?=$mes?>&ano=<?=$ano?>" class="btn btn-sm <?=$tab==='vendas'?'btn-amber':'btn-outline-secondary'?>"><i class="ph-bold ph-list me-1"></i>Vendas Detalhadas</a>
</div>

<?php if ($tab === 'financeiro'): ?>
<!-- ── FINANCEIRO ── -->
<!-- Cards resumo -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="s-val" style="color:var(--amber)"><?= 'R$ '.number_format($fat_total['t'],2,',','.') ?></div>
      <div class="s-lbl">Faturamento <?= $meses_nome[$mes] ?>/<?= $ano ?></div>
      <div class="mt-1" style="font-size:.75rem;color:var(--text-muted)"><?= $fat_total['n'] ?> venda(s)</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="s-val" style="color:var(--success)"><?= 'R$ '.number_format($vendas_dia_atual['t'],2,',','.') ?></div>
      <div class="s-lbl">Hoje</div>
      <div class="mt-1" style="font-size:.75rem;color:var(--text-muted)"><?= $vendas_dia_atual['n'] ?> venda(s)</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="s-val" style="color:var(--text)"><?= $fat_total['n']>0?'R$ '.number_format($fat_total['t']/$fat_total['n'],2,',','.'):'R$ 0,00' ?></div>
      <div class="s-lbl">Ticket Médio</div>
      <div class="mt-1" style="font-size:.75rem;color:var(--text-muted)">por venda</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="s-val" style="color:<?= $caixa_aberto?'var(--success)':'var(--danger)' ?>"><?= $caixa_aberto?'Aberto':'Fechado' ?></div>
      <div class="s-lbl">Caixa Atual</div>
      <?php if ($caixa_aberto): ?><div class="mt-1" style="font-size:.75rem;color:var(--text-muted)">desde <?= date('H:i',strtotime($caixa_aberto['data_abertura'])) ?></div><?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <!-- Gráfico evolução -->
  <div class="col-md-7">
    <div class="admin-card">
      <div class="card-section-title">Faturamento — Últimos 6 Meses</div>
      <div class="bar-chart">
        <?php foreach ($evolucao as $ev): ?>
        <div class="bar-item">
          <div class="bar-val"><?= $ev['total']>0?'R$ '.number_format($ev['total']/1000,1,',','.').'k':'' ?></div>
          <div class="bar-fill" style="height:<?= $max_evo>0?max(4,round($ev['total']/$max_evo*100)).'%':'4px' ?>"></div>
          <div class="bar-lbl"><?= $ev['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Formas de pagamento -->
  <div class="col-md-5">
    <div class="admin-card">
      <div class="card-section-title">Por Forma de Pagamento</div>
      <?php
      $total_fat = (float)$fat_total['t'];
      $formas_label = ['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro'];
      foreach ($fat_forma as $f):
        $pct = $total_fat>0 ? ($f['total']/$total_fat*100) : 0;
      ?>
      <div class="forma-row">
        <div style="width:80px;font-size:.78rem"><?= $formas_label[$f['forma_pagamento']]??$f['forma_pagamento'] ?></div>
        <div class="forma-bar"><div class="forma-bar-fill" style="width:<?= $pct ?>%"></div></div>
        <div style="font-size:.78rem;font-weight:700;color:var(--amber);min-width:80px;text-align:right">R$ <?= number_format($f['total'],2,',','.') ?></div>
        <div style="font-size:.68rem;color:var(--text-muted);min-width:35px;text-align:right"><?= number_format($pct,0) ?>%</div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($fat_forma)): ?><div class="text-center py-3" style="color:var(--text-muted)">Sem dados.</div><?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Top produtos -->
  <div class="col-md-6">
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-trophy me-2" style="color:var(--amber)"></i>Top Produtos — <?= $meses_nome[$mes] ?>/<?= $ano ?></div>
      <table class="admin-table">
        <thead><tr><th>#</th><th>Produto</th><th class="text-center">Qtd</th><th class="text-end">Faturado</th></tr></thead>
        <tbody>
          <?php foreach ($top_prods as $i=>$p): ?>
          <tr>
            <td><span class="badge-<?= $i<3?'amber':'muted' ?>"><?= $i+1 ?></span></td>
            <td style="font-size:.82rem"><?= h($p['descricao']) ?></td>
            <td class="text-center"><?= number_format($p['qtd'],0,',','.') ?></td>
            <td class="text-end fw-bold" style="color:var(--amber)">R$ <?= number_format($p['total'],2,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($top_prods)): ?><tr><td colspan="4" class="text-center py-3" style="color:var(--text-muted)">Sem dados.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Vendas por dia -->
  <div class="col-md-6">
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-calendar me-2"></i>Vendas por Dia — <?= $meses_nome[$mes] ?>/<?= $ano ?></div>
      <div style="max-height:320px;overflow-y:auto">
        <table class="admin-table">
          <thead><tr><th>Data</th><th class="text-center">Vendas</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            <?php foreach (array_reverse($fat_dia) as $fd): ?>
            <tr>
              <td style="font-size:.82rem"><?= dataBR($fd['dia']) ?></td>
              <td class="text-center"><span class="badge-muted"><?= $fd['n'] ?></span></td>
              <td class="text-end fw-bold" style="color:var(--amber)">R$ <?= number_format($fd['total'],2,',','.') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($fat_dia)): ?><tr><td colspan="3" class="text-center py-3" style="color:var(--text-muted)">Sem dados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'estoque'): ?>
<!-- ── ESTOQUE ── -->
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="stat-card"><div class="s-val" style="color:var(--amber)">R$ <?= number_format($valor_estoque['t'],2,',','.') ?></div><div class="s-lbl">Valor em Estoque (Custo)</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="s-val" style="color:<?= $sem_estoque>0?'var(--danger)':'var(--success)' ?>"><?= $sem_estoque ?></div><div class="s-lbl">Produtos Sem Estoque</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="s-val"><?= count(alertasEstoque()) ?></div><div class="s-lbl">Alertas Estoque Baixo</div></div></div>
  <div class="col-md-3"><div class="stat-card"><div class="s-val"><?= DB::count('barris',"status='em_uso'") ?></div><div class="s-lbl">Barris em Uso</div></div></div>
</div>

<div class="admin-card">
  <div class="card-section-title">Movimentações de Estoque — <?= $meses_nome[$mes] ?>/<?= $ano ?></div>
  <table class="admin-table">
    <thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Est. Anterior</th><th>Est. Novo</th><th>Motivo</th></tr></thead>
    <tbody>
      <?php foreach ($movs_est as $m):
        $cor = match($m['tipo']){'entrada'=>'success','abertura_barril'=>'success','saida'=>'danger','perda'=>'danger','ajuste'=>'muted',default=>'muted'};
        $sinal = in_array($m['tipo'],['entrada','abertura_barril'])?'+':'-';
      ?>
      <tr>
        <td style="font-size:.72rem;white-space:nowrap"><?= dataHoraBR($m['created_at']) ?></td>
        <td style="font-size:.82rem"><?= h($m['produto_nome']) ?></td>
        <td><span class="badge-<?= $cor ?>"><?= ucfirst(str_replace('_',' ',$m['tipo'])) ?></span></td>
        <td class="fw-bold" style="color:<?= $sinal==='+'?'var(--success)':'var(--danger)' ?>"><?= $sinal ?><?= number_format($m['quantidade'],3,',','.') ?> <?= h($m['unidade']??'') ?></td>
        <td style="font-size:.78rem;color:var(--text-muted)"><?= number_format($m['estoque_anterior']??0,2,',','.') ?></td>
        <td style="font-size:.82rem"><?= number_format($m['estoque_novo']??0,2,',','.') ?></td>
        <td style="font-size:.72rem;color:var(--text-muted)"><?= h($m['motivo']??'') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($movs_est)): ?><tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted)">Nenhuma movimentação neste período.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php elseif ($tab === 'vendas'): ?>
<!-- ── VENDAS DETALHADAS ── -->
<?php
$vendas = DB::all("SELECT v.*,cx.operador as cx_operador FROM vendas v LEFT JOIN caixas cx ON v.caixa_id=cx.id WHERE v.status='pago' AND v.data_venda BETWEEN ? AND ? ORDER BY v.data_venda DESC", [$ini,$fim]);
$total_tab = DB::count('vendas',"status='pago'");
?>
<div class="admin-card">
  <div class="card-section-title d-flex justify-content-between align-items-center">
    <span>Vendas — <?= $meses_nome[$mes] ?>/<?= $ano ?> (<?= count($vendas) ?> de <?= $total_tab ?> registros)</span>
    <?php if(count($vendas)==0 && $total_tab>0): ?>
    <small style="color:var(--amber)">⚠ Há <?= $total_tab ?> venda(s) mas não neste mês/ano — altere o filtro acima</small>
    <?php endif; ?>
  </div>
  <div style="max-height:600px;overflow-y:auto">
  <table class="admin-table">
    <thead><tr><th>Nº</th><th>Data/Hora</th><th>Mesa</th><th>Forma Pgto.</th><th>Caixa</th><th class="text-end">Desconto</th><th class="text-end">Total</th><th>Mercado Pago</th></tr></thead>
    <tbody>
      <?php foreach ($vendas as $v): ?>
      <tr>
        <td class="fw-bold" style="color:var(--amber)"><?= h($v['numero']) ?></td>
        <td style="font-size:.75rem;white-space:nowrap"><?= dataHoraBR($v['data_venda']) ?></td>
        <td style="font-size:.8rem"><?= h($v['mesa']??'—') ?></td>
        <td>
          <?php $formas_label=['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro']; ?>
          <span class="badge-muted"><?= $formas_label[$v['forma_pagamento']]??$v['forma_pagamento'] ?></span>
        </td>
        <td style="font-size:.78rem;color:var(--text-muted)"><?= h($v['cx_operador']??'—') ?></td>
        <td class="text-end" style="font-size:.8rem;color:var(--danger)"><?= $v['desconto']>0?'- R$ '.number_format($v['desconto'],2,',','.'):'—' ?></td>
        <td class="text-end fw-bold" style="color:var(--amber)">R$ <?= number_format($v['total'],2,',','.') ?></td>
        <td style="font-size:.68rem">
          <?php if ($v['mp_order_id']): ?><span class="badge-success"><?= h(substr($v['mp_order_id'],0,12)).'...' ?></span><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($vendas)): ?><tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted)">Nenhuma venda.</td></tr><?php endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="6" class="text-end fw-bold">TOTAL:</td>
        <td class="text-end fw-bold" style="color:var(--amber);font-family:'Syne',sans-serif">R$ <?= number_format(array_sum(array_column($vendas,'total')),2,',','.') ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  </div>
</div>
<?php endif; ?>

</div><!-- admin-content -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
