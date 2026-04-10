<?php
/**
 * modules/tickets/index.php — Gestão de Tickets de Consumo
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();

$_cor  = DB::cfg('cor_primaria',  '#f59e0b');
$_cor2 = DB::cfg('cor_secundaria','#d97706');



$tickets_recentes = DB::all(
    "SELECT t.*, v.numero as venda_numero FROM tickets t
     LEFT JOIN vendas v ON t.venda_id=v.id
     WHERE DATE(t.criado_em)=CURDATE() ORDER BY t.criado_em DESC LIMIT 200"
);
$resumo = DB::row(
    "SELECT COUNT(*) as total, SUM(status='pendente') as pendentes,
     SUM(status='utilizado') as utilizados, SUM(status='cancelado') as cancelados
     FROM tickets WHERE DATE(criado_em)=CURDATE()"
);
$nome_est = DB::cfg('nome_estabelecimento', 'Bar System Pro');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tickets — <?= h($nome_est) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>
:root{--amber:<?= $_cor ?>;--amber-dark:<?= $_cor2 ?>;}
.scan-input{font-size:1.4rem;font-family:monospace;text-align:center;letter-spacing:.15em;text-transform:uppercase;height:60px;font-weight:700}
.ticket-row{display:flex;align-items:center;gap:.75rem;padding:.625rem .75rem;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;margin-bottom:.4rem}
.t-code{font-family:monospace;font-weight:800;font-size:.95rem;color:var(--amber);min-width:110px}
.stat-box{background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:.875rem;text-align:center}
.stat-num{font-size:2rem;font-weight:800;font-family:'Syne',sans-serif}
</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/../../includes/nav.php'; ?>
<div class="admin-content">
<div class="page-header d-flex justify-content-between align-items-center">
  <h4><i class="ph-bold ph-ticket me-2"></i>Tickets de Consumo</h4>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>modules/tickets/layout.php" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-palette me-1"></i>Configurar Layout
    </a>
    <a href="<?= BASE_URL ?>modules/tickets/imprimir.php?demo=1" target="_blank" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-printer me-1"></i>Demo Impressão
    </a>
  </div>
</div>


<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num"><?= $resumo['total']??0 ?></div><small style="color:var(--text-muted)">Gerados hoje</small></div></div>
  <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num" style="color:var(--amber)"><?= $resumo['pendentes']??0 ?></div><small style="color:var(--text-muted)">Pendentes</small></div></div>
  <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num" style="color:#22c55e"><?= $resumo['utilizados']??0 ?></div><small style="color:var(--text-muted)">Utilizados</small></div></div>
  <div class="col-6 col-md-3"><div class="stat-box"><div class="stat-num" style="color:#ef4444"><?= $resumo['cancelados']??0 ?></div><small style="color:var(--text-muted)">Cancelados</small></div></div>
</div>

<div class="row g-3">
  </div>

  <div class="col-12">
    <div class="admin-card">
      <div class="card-section-title d-flex justify-content-between">
        <span>Tickets de Hoje (<?= count($tickets_recentes) ?>)</span>
        <select id="filtro" class="form-select form-select-sm" style="width:auto" onchange="filtrar()">
          <option value="">Todos</option>
          <option value="pendente">Pendentes</option>
          <option value="utilizado">Utilizados</option>
          <option value="cancelado">Cancelados</option>
        </select>
      </div>
      <div style="max-height:520px;overflow-y:auto;margin-top:.5rem" id="lista">
        <?php foreach ($tickets_recentes as $t):
          $cor = match($t['status']){'utilizado'=>'#22c55e','cancelado'=>'#ef4444',default=>'var(--amber)'};
          $icon = match($t['status']){'utilizado'=>'check','cancelado'=>'x',default=>'circle'};
        ?>
        <div class="ticket-row" data-status="<?= $t['status'] ?>">
          <div class="t-code"><?= h($t['codigo']) ?></div>
          <div style="flex:1">
            <div class="fw-semibold" style="font-size:.88rem"><?= h($t['produto_nome']) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted)">Venda #<?= $t['venda_numero']??'?' ?> · <?= date('H:i', strtotime($t['criado_em'])) ?></div>
          </div>
          <div style="color:<?= $cor ?>;font-size:.78rem;font-weight:600;text-align:right">
            <i class="ph-bold ph-<?= $icon ?>"></i> <?= ucfirst($t['status']) ?>
            <?php if ($t['utilizado_em']): ?><br><span style="font-size:.68rem;color:var(--text-muted)"><?= date('H:i', strtotime($t['utilizado_em'])) ?></span><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($tickets_recentes)): ?>
        <p class="text-center py-4" style="color:var(--text-muted)">Nenhum ticket gerado hoje.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
</div>`;return;}
  const t=d.ticket;
  const cors={pendente:'var(--amber)',utilizado:'#22c55e',cancelado:'#ef4444'};
  div.innerHTML=`<div class="admin-card" style="padding:.75rem;margin-top:.5rem">
    <div class="t-code">${t.codigo}</div>
    <div class="fw-semibold">${t.produto_nome}</div>
    <div style="font-size:.72rem;color:var(--text-muted)">Venda #${t.venda_numero||'?'}</div>
    <div style="color:${cors[t.status]};font-size:.8rem;font-weight:700;margin-top:.25rem">${t.status.toUpperCase()}</div>
  </div>`;
}
</script>
</body>
</html>
