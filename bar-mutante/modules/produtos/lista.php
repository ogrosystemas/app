<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();
$_tema = class_exists('DB') ? DB::cfg('tema','dark') : 'dark';
$_cor  = class_exists('DB') ? DB::cfg('cor_primaria','#f59e0b') : '#f59e0b';
$_cor2 = class_exists('DB') ? DB::cfg('cor_secundaria','#d97706') : '#d97706';

if (isset($_GET['del'])) { DB::q("UPDATE produtos SET ativo=0 WHERE id=?",[(int)$_GET['del']]); setFlash('success','Produto desativado.'); redirect(BASE_URL.'modules/produtos/lista.php'); }

$busca   = $_GET['q'] ?? '';
$cat_f   = (int)($_GET['cat'] ?? 0);
$tipo_f  = $_GET['tipo'] ?? '';
$where   = 'p.ativo=1'; $params=[];
if ($busca)  { $where.=' AND (p.nome LIKE ? OR p.codigo_barras LIKE ?)'; $params=array_merge($params,["%$busca%","%$busca%"]); }
if ($cat_f)  { $where.=' AND p.categoria_id=?'; $params[]=$cat_f; }
if ($tipo_f) { $where.=' AND p.tipo=?'; $params[]=$tipo_f; }

$total   = DB::row("SELECT COUNT(*) as n FROM produtos p WHERE $where",$params)['n'];
$page    = max(1,(int)($_GET['page']??1)); $per=30;
$offset  = ($page-1)*$per;
$produtos= DB::all("SELECT p.*,c.nome as cat_nome,c.cor as cat_cor FROM produtos p LEFT JOIN categorias c ON p.categoria_id=c.id WHERE $where ORDER BY p.nome LIMIT $per OFFSET $offset",$params);
$cats    = DB::all("SELECT * FROM categorias WHERE ativo=1 ORDER BY nome");
$alertas = alertasEstoque();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produtos — Bar System Pro</title>
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
<div class="page-header">
  <h4><i class="ph-bold ph-package me-2"></i>Produtos <span class="badge-muted ms-2"><?= $total ?></span></h4>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php" class="btn btn-outline-secondary btn-sm"><i class="ph-bold ph-plus me-1"></i>Entrada Estoque</a>
    <a href="<?= BASE_URL ?>modules/produtos/form.php" class="btn btn-amber btn-sm"><i class="ph-bold ph-plus me-1"></i>Novo Produto</a>
  </div>
</div>
<?= flash('success') ?><?= flash('error') ?>

<?php if (!empty($alertas)): ?>
<div class="admin-card mb-3" style="border-color:#92400e;background:rgba(146,64,14,.08)">
  <div class="d-flex align-items-center gap-2 mb-2"><i class="ph-bold ph-warning text-amber"></i><strong><?= count($alertas) ?> produto(s) com estoque baixo ou zerado</strong></div>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($alertas as $al): ?>
    <span class="badge-danger"><strong><?= h($al['nome']) ?></strong> — <?= number_format($al['estoque_atual'],2,',','.') ?> <?= h($al['unidade_estoque']) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Filtros -->
<form class="d-flex gap-2 flex-wrap mb-3" method="GET">
  <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar produto..." value="<?= h($busca) ?>" style="width:220px">
  <select name="cat" class="form-select form-select-sm" style="width:160px">
    <option value="">Todas categorias</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $cat_f==$c['id']?'selected':'' ?>><?= h($c['nome']) ?></option><?php endforeach; ?>
  </select>
  <select name="tipo" class="form-select form-select-sm" style="width:150px">
    <option value="">Todos tipos</option>
    <?php foreach (['unidade'=>'Unidade','dose'=>'Destilado (Dose)','chopp_lata'=>'Chopp Lata','chopp_barril'=>'Chopp Barril','garrafa'=>'Garrafa','drink'=>'Drink','combo'=>'Combo'] as $v=>$l): ?>
    <option value="<?= $v ?>" <?= $tipo_f===$v?'selected':'' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-amber btn-sm"><i class="ph-bold ph-magnifying-glass"></i></button>
  <a href="?" class="btn btn-outline-secondary btn-sm"><i class="ph-bold ph-x"></i></a>
</form>

<div class="admin-card">
<table class="admin-table">
  <thead><tr><th>Imagem</th><th>Nome</th><th>Categoria</th><th>Tipo</th><th>Preço</th><th>Estoque</th><th>PDV</th><th>Ações</th></tr></thead>
  <tbody>
  <?php foreach ($produtos as $p):
    $alerta = $p['estoque_minimo']>0 && $p['estoque_atual']<=$p['estoque_minimo'];
    $zero   = $p['estoque_atual']<=0 && in_array($p['tipo'],['unidade','chopp_lata','garrafa']);
  ?>
  <tr>
    <td style="width:52px">
      <?php if ($p['imagem']): ?>
      <img src="<?= UPLOAD_URL.'produtos/'.h($p['imagem']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
      <?php else: ?>
      <div style="width:44px;height:44px;background:var(--bg-card2);border-radius:8px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:.9rem"><i class="ph-bold ph-image"></i></div>
      <?php endif; ?>
    </td>
    <td>
      <div class="fw-semibold"><?= h($p['nome']) ?></div>
      <?php if ($p['codigo_barras']): ?><div class="font-mono" style="font-size:.68rem;color:var(--text-muted)"><?= h($p['codigo_barras']) ?></div><?php endif; ?>
    </td>
    <td><?php if ($p['cat_nome']): ?><span class="badge-muted" style="background:<?= h($p['cat_cor']) ?>22;border-color:<?= h($p['cat_cor']) ?>44;color:<?= h($p['cat_cor']) ?>"><?= h($p['cat_nome']) ?></span><?php endif; ?></td>
    <td class="text-muted" style="font-size:.78rem"><?= h(['unidade'=>'Unidade','dose'=>'Destilado (Dose)','chopp_lata'=>'Chopp Lata','chopp_barril'=>'Chopp Barril','garrafa'=>'Garrafa','drink'=>'Drink','combo'=>'Combo'][$p['tipo']]??$p['tipo']) ?></td>
    <td class="fw-bold" style="color:var(--amber)">R$ <?= number_format($p['preco_venda'],2,',','.') ?></td>
    <td>
      <?php if ($zero): ?>
      <span class="badge-danger"><i class="ph-bold ph-x-circle me-1"></i>Sem estoque</span>
      <?php elseif ($alerta): ?>
      <span class="badge-amber"><i class="ph-bold ph-warning me-1"></i><?= number_format($p['estoque_atual'],2,',','.') ?> <?= h($p['unidade_estoque']) ?></span>
      <?php elseif ($p['tipo'] === 'chopp_barril'): ?>
      <span class="badge-muted">Barril</span>
      <?php else: ?>
      <span class="badge-success"><?= number_format($p['estoque_atual'],2,',','.') ?> <?= h($p['unidade_estoque']) ?></span>
      <?php endif; ?>
    </td>
    <td>
      <?php if ($p['disponivel_pdv']): ?>
      <span class="badge-success"><i class="ph-bold ph-check"></i></span>
      <?php else: ?>
      <span class="badge-muted"><i class="ph-bold ph-x"></i></span>
      <?php endif; ?>
      <?php if ($p['destaque']): ?>
      <span class="badge-amber ms-1"><i class="ph-bold ph-star"></i></span>
      <?php endif; ?>
    </td>
    <td>
      <div class="d-flex gap-1">
        <a href="<?= BASE_URL ?>modules/produtos/form.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="ph-bold ph-pencil-simple"></i></a>
        <a href="?del=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm py-0" onclick="return swalConfirm(event,'Desativar produto?','Desativar',this.href)"><i class="ph-bold ph-trash"></i></a>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($produtos)):?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted)"><i class="ph-bold ph-package-open d-block fs-2 mb-2 opacity-30"></i>Nenhum produto encontrado.</td></tr><?php endif; ?>
  </tbody>
</table>
<!-- Paginação -->
<?php if (ceil($total/$per) > 1): ?>
<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">
<?php
$tp = ceil($total/$per);
for ($i=1;$i<=$tp;$i++): ?>
<li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul></nav>
<?php endif; ?>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function swalConfirm(e, msg, btnTxt, href) {
  e.preventDefault();
  Swal.fire({icon:'warning',title:msg,showCancelButton:true,confirmButtonText:btnTxt,
    cancelButtonText:'Cancelar',confirmButtonColor:'#f59e0b',background:'#1e2330',color:'#f0f2f7'})
    .then(r=>{ if(r.isConfirmed) window.location.href=href; });
}
</script>
</body></html>
