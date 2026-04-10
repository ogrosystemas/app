<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();
$_tema = class_exists('DB') ? DB::cfg('tema','dark') : 'dark';
$_cor  = class_exists('DB') ? DB::cfg('cor_primaria','#f59e0b') : '#f59e0b';
$_cor2 = class_exists('DB') ? DB::cfg('cor_secundaria','#d97706') : '#d97706';

$caixa_id = (int)($_GET['id']??0);
if ($caixa_id) {
    $caixa = DB::row("SELECT * FROM caixas WHERE id=?",[$caixa_id]);
    $movimentos = DB::all("SELECT * FROM caixa_movimentos WHERE caixa_id=? ORDER BY created_at DESC",[$caixa_id]);
    $vendas_cx  = DB::all("SELECT v.*,(SELECT COUNT(*) FROM venda_itens WHERE venda_id=v.id) as n_itens FROM vendas v WHERE v.caixa_id=? ORDER BY v.data_venda DESC",[$caixa_id]);
    $fat_por_forma = DB::all("SELECT forma_pagamento,COUNT(*) as n,SUM(total) as total FROM vendas WHERE caixa_id=? AND status='pago' GROUP BY forma_pagamento",[$caixa_id]);
} else {
    $caixas = DB::all("SELECT cx.*, (SELECT COUNT(*) FROM vendas WHERE caixa_id=cx.id AND status='pago') as n_vendas FROM caixas cx ORDER BY cx.id DESC LIMIT 50");
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Histórico de Caixa — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>:root{--amber:<?= $_cor ?>;--amber-dark:<?= $_cor2 ?>;}</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/nav.php'; ?>
<div class="admin-content">

<?php if ($caixa_id && isset($caixa)): ?>
<!-- ── Detalhes de um Caixa ── -->
<div class="page-header">
  <h4><i class="ph-bold ph-cash-register me-2"></i>Caixa #<?= $caixa['id'] ?> — <?= h($caixa['operador']) ?></h4>
  <div class="d-flex gap-2">
    <button style="display:none" class="btn btn-outline-secondary btn-sm no-print"><i class="ph-bold ph-printer me-1"></i>Imprimir</button>
    <a href="<?= BASE_URL ?>api/pdf.php?tipo=caixa&id=<?= $caixa_id ?>" target="_blank" class="btn btn-sm btn-pdf no-print"><i class="ph-bold ph-file-pdf me-1"></i>Baixar PDF</a>
    <a href="?" class="btn btn-outline-secondary btn-sm no-print"><i class="ph-bold ph-arrow-left me-1"></i>Voltar</a>
  </div>
</div>

<!-- Resumo do caixa -->
<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="admin-card">
      <div class="card-section-title">Resumo do Caixa</div>
      <div class="row g-3">
        <div class="col-6"><div style="font-size:.72rem;color:var(--text-muted)">Abertura</div><div class="fw-bold"><?= dataHoraBR($caixa['data_abertura']) ?></div></div>
        <div class="col-6"><div style="font-size:.72rem;color:var(--text-muted)">Fechamento</div><div class="fw-bold"><?= $caixa['data_fechamento']?dataHoraBR($caixa['data_fechamento']):'<span class="badge-success">Aberto</span>' ?></div></div>
        <div class="col-6"><div style="font-size:.72rem;color:var(--text-muted)">Operador</div><div class="fw-bold"><?= h($caixa['operador']) ?></div></div>
        <div class="col-6"><div style="font-size:.72rem;color:var(--text-muted)">Status</div><div><span class="badge-<?= $caixa['status']==='aberto'?'success':'muted' ?>"><?= ucfirst($caixa['status']) ?></span></div></div>
        <div class="col-12"><hr style="border-color:var(--border)"></div>
        <div class="col-6 col-md-3"><div style="font-size:.72rem;color:var(--text-muted)">Saldo Inicial</div><div class="fw-bold">R$ <?= number_format($caixa['saldo_inicial'],2,',','.') ?></div></div>
        <div class="col-6 col-md-3"><div style="font-size:.72rem;color:var(--text-muted)">Total Vendas</div><div class="fw-bold" style="color:var(--amber)">R$ <?= number_format($caixa['total_vendas'],2,',','.') ?></div></div>
        <div class="col-6 col-md-3"><div style="font-size:.72rem;color:var(--text-muted)">Suprimentos</div><div class="fw-bold" style="color:var(--success)">+ R$ <?= number_format($caixa['total_suprimentos'],2,',','.') ?></div></div>
        <div class="col-6 col-md-3"><div style="font-size:.72rem;color:var(--text-muted)">Sangrias</div><div class="fw-bold" style="color:var(--danger)">- R$ <?= number_format($caixa['total_sangrias'],2,',','.') ?></div></div>
        <div class="col-6">
          <?php $saldo_esp = $caixa['saldo_inicial']+$caixa['total_vendas']+$caixa['total_suprimentos']-$caixa['total_sangrias']; ?>
          <div style="font-size:.72rem;color:var(--text-muted)">Saldo Esperado</div>
          <div class="fw-bold" style="color:var(--amber);font-family:'Syne',sans-serif;font-size:1.2rem">R$ <?= number_format($saldo_esp,2,',','.') ?></div>
        </div>
        <?php if ($caixa['saldo_final_informado']!==null): ?>
        <div class="col-6">
          <div style="font-size:.72rem;color:var(--text-muted)">Saldo Informado</div>
          <div class="fw-bold" style="font-size:1.2rem">R$ <?= number_format($caixa['saldo_final_informado'],2,',','.') ?></div>
        </div>
        <?php if ($caixa['diferenca']!==null): ?>
        <div class="col-12">
          <div style="font-size:.72rem;color:var(--text-muted)">Diferença</div>
          <div class="fw-bold" style="color:<?= abs($caixa['diferenca'])<0.01?'var(--success)':($caixa['diferenca']>0?'var(--success)':'var(--danger)') ?>">
            <?= $caixa['diferenca']>=0?'+':'.' ?>R$ <?= number_format(abs($caixa['diferenca']),2,',','.') ?>
            <?= abs($caixa['diferenca'])<0.01?' ✓ Sem diferença':($caixa['diferenca']>0?' (sobra)':' (falta)') ?>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($caixa['observacoes']): ?>
        <div class="col-12"><div style="font-size:.72rem;color:var(--text-muted)">Obs</div><div style="font-size:.82rem"><?= h($caixa['observacoes']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card mb-3">
      <div class="card-section-title">Por Forma de Pagamento</div>
      <?php foreach ($fat_por_forma as $f): ?>
      <div class="d-flex justify-content-between mb-2" style="font-size:.82rem">
        <span><?= ['dinheiro'=>'Dinheiro','mercadopago'=>'Maquininha (MP)','cortesia'=>'Cortesia','ficha'=>'Ficha','outro'=>'Outro'][$f['forma_pagamento']]??$f['forma_pagamento'] ?> (<?= $f['n'] ?>x)</span>
        <strong style="color:var(--amber)">R$ <?= number_format($f['total'],2,',','.') ?></strong>
      </div>
      <?php endforeach; ?>
      <?php if(empty($fat_por_forma)):?><div class="text-center py-2" style="color:var(--text-muted);font-size:.8rem">Sem vendas.</div><?php endif;?>
    </div>
    <?php if (!empty($movimentos)): ?>
    <div class="admin-card">
      <div class="card-section-title">Sangrias / Suprimentos</div>
      <?php foreach ($movimentos as $mv): ?>
      <div class="d-flex justify-content-between mb-1" style="font-size:.78rem">
        <span><span class="badge-<?= $mv['tipo']==='sangria'?'danger':'success' ?>"><?= ucfirst($mv['tipo']) ?></span> <?= h($mv['motivo']??'') ?></span>
        <strong style="color:<?= $mv['tipo']==='sangria'?'var(--danger)':'var(--success)' ?>">R$ <?= number_format($mv['valor'],2,',','.') ?></strong>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Vendas do caixa -->
<div class="admin-card">
  <div class="card-section-title">Vendas do Caixa (<?= count($vendas_cx) ?>)</div>
  <div style="max-height:400px;overflow-y:auto">
  <table class="admin-table">
    <thead><tr><th>Nº</th><th>Hora</th><th>Mesa</th><th>Forma</th><th class="text-center">Itens</th><th class="text-end">Desconto</th><th class="text-end">Total</th></tr></thead>
    <tbody>
      <?php foreach ($vendas_cx as $v): ?>
      <tr>
        <td class="fw-bold" style="color:var(--amber)"><?= h($v['numero']) ?></td>
        <td style="font-size:.75rem"><?= date('H:i',strtotime($v['data_venda'])) ?></td>
        <td style="font-size:.8rem"><?= h($v['mesa']??'—') ?></td>
        <td><span class="badge-muted" style="font-size:.65rem"><?= h($v['forma_pagamento']) ?></span></td>
        <td class="text-center"><?= $v['n_itens'] ?></td>
        <td class="text-end" style="color:var(--danger)"><?= $v['desconto']>0?'R$ '.number_format($v['desconto'],2,',','.'):'—' ?></td>
        <td class="text-end fw-bold" style="color:var(--amber)">R$ <?= number_format($v['total'],2,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="6" class="text-end fw-bold">Total:</td><td class="text-end fw-bold" style="color:var(--amber);font-family:'Syne',sans-serif">R$ <?= number_format(array_sum(array_column($vendas_cx,'total')),2,',','.') ?></td></tr></tfoot>
  </table>
  </div>
</div>

<?php else: ?>
<!-- ── Lista de Caixas ── -->
<div class="page-header">
  <h4><i class="ph-bold ph-cash-register me-2"></i>Histórico de Caixas</h4>
</div>
<div class="admin-card">
<table class="admin-table">
  <thead><tr><th>#</th><th>Operador</th><th>Abertura</th><th>Fechamento</th><th class="text-center">Vendas</th><th class="text-end">Faturamento</th><th class="text-end">Saldo Esperado</th><th>Status</th><th>Ações</th></tr></thead>
  <tbody>
    <?php foreach (isset($caixas)?$caixas:[] as $cx):
      $saldo_esp = $cx['saldo_inicial']+$cx['total_vendas']+$cx['total_suprimentos']-$cx['total_sangrias'];
    ?>
    <tr>
      <td class="fw-bold" style="color:var(--amber)">#<?= $cx['id'] ?></td>
      <td><?= h($cx['operador']) ?></td>
      <td style="font-size:.78rem"><?= dataHoraBR($cx['data_abertura']) ?></td>
      <td style="font-size:.78rem;color:var(--text-muted)"><?= $cx['data_fechamento']?dataHoraBR($cx['data_fechamento']):'—' ?></td>
      <td class="text-center"><span class="badge-muted"><?= $cx['n_vendas'] ?></span></td>
      <td class="text-end fw-bold" style="color:var(--amber)">R$ <?= number_format($cx['total_vendas'],2,',','.') ?></td>
      <td class="text-end">R$ <?= number_format($saldo_esp,2,',','.') ?></td>
      <td><span class="badge-<?= $cx['status']==='aberto'?'success':'muted' ?>"><?= ucfirst($cx['status']) ?></span></td>
      <td><a href="?id=<?= $cx['id'] ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="ph-bold ph-eye"></i></a></td>
    </tr>
    <?php endforeach; ?>
    <?php if(empty($caixas??[])): ?><tr><td colspan="9" class="text-center py-4" style="color:var(--text-muted)">Nenhum caixa registrado.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
