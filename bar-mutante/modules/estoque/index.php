<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();
$_tema = class_exists('DB') ? DB::cfg('tema','dark') : 'dark';
$_cor  = class_exists('DB') ? DB::cfg('cor_primaria','#f59e0b') : '#f59e0b';
$_cor2 = class_exists('DB') ? DB::cfg('cor_secundaria','#d97706') : '#d97706';

$tab = $_GET['tab'] ?? 'estoque';

// Alertas
$alertas = alertasEstoque();

// Dados por tab
$produtos_estoque = DB::all("
    SELECT p.*,c.nome as cat_nome,c.cor as cat_cor
    FROM produtos p
    LEFT JOIN categorias c ON p.categoria_id=c.id
    WHERE p.ativo=1
    ORDER BY p.categoria_id, p.nome");

$barris = DB::all("
    SELECT b.*,p.nome as produto_nome,p.ml_por_dose
    FROM barris b
    LEFT JOIN produtos p ON b.produto_id=p.id
    ORDER BY FIELD(b.status,'em_uso','fechado','vazio','descartado'), b.id DESC
    LIMIT 50");

$movimentacoes = DB::all("
    SELECT m.*,p.nome as produto_nome,p.unidade_estoque
    FROM estoque_movimentacoes m
    LEFT JOIN produtos p ON m.produto_id=p.id
    ORDER BY m.created_at DESC
    LIMIT 100");

$produtos_chopp = DB::all("SELECT id,nome FROM produtos WHERE tipo='chopp_barril' AND ativo=1 ORDER BY nome");

// Salvar barril
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='salvar_barril') {
    $prod_id  = (int)$_POST['produto_id'];
    $cap      = (float)$_POST['capacidade_ml'];
    $rend     = (float)($_POST['rendimento_pct']??85);
    $custo    = parseMoeda($_POST['custo_barril']??'0');
    $dt_aber  = $_POST['data_abertura'] ?? date('Y-m-d');
    $dt_venc  = !empty($_POST['data_vencimento']) ? $_POST['data_vencimento'] : null;
    $status   = $_POST['status']??'fechado';
    $num_serie= trim($_POST['numero_serie']??'');

    if ($prod_id && $cap > 0) {
        $bid = DB::insert('barris',[
            'produto_id'    => $prod_id,
            'numero_serie'  => $num_serie,
            'capacidade_ml' => $cap,
            'rendimento_pct'=> $rend,
            'data_abertura' => $status==='em_uso' ? $dt_aber : null,
            'data_vencimento'=> $dt_venc,
            'status'        => $status,
            'custo_barril'  => $custo,
        ]);
        // Se aberto, registra entrada de doses no estoque
        if ($status === 'em_uso') {
            $ml_util = $cap * ($rend/100);
            $prod    = DB::row("SELECT ml_por_dose FROM produtos WHERE id=?",[$prod_id]);
            $doses   = $prod['ml_por_dose'] > 0 ? floor($ml_util/$prod['ml_por_dose']) : 0;
            if ($doses > 0) movEstoque($prod_id,'abertura_barril',$doses,$custo>0?($custo/$doses):0,"Abertura barril #{$bid}",'barril',$bid,$bid);
            DB::update('barris',['ml_consumido'=>0],'id=?',[$bid]);
        }
        setFlash('success','Barril cadastrado.');
    } else {
        setFlash('error','Preencha os campos obrigatórios.');
    }
    redirect(BASE_URL.'modules/estoque/index.php?tab=barris');
}

// Abrir/Fechar barril
if (isset($_GET['abrir'])) {
    $bid  = (int)$_GET['abrir'];
    $b    = DB::row("SELECT * FROM barris WHERE id=?",[$bid]);
    if ($b && $b['status']==='fechado') {
        DB::update('barris',['status'=>'em_uso','data_abertura'=>date('Y-m-d')],'id=?',[$bid]);
        $ml_util = $b['capacidade_ml'] * ($b['rendimento_pct']/100);
        $prod    = DB::row("SELECT ml_por_dose FROM produtos WHERE id=?",[$b['produto_id']]);
        $doses   = ($prod['ml_por_dose']??300) > 0 ? floor($ml_util/($prod['ml_por_dose']??300)) : 0;
        if ($doses > 0) movEstoque($b['produto_id'],'abertura_barril',$doses,0,"Abertura barril #{$bid}",'barril',$bid,$bid);
        setFlash('success',"Barril #{$bid} aberto. {$doses} doses adicionadas ao estoque.");
    }
    redirect(BASE_URL.'modules/estoque/index.php?tab=barris');
}
if (isset($_GET['fechar'])) {
    $bid = (int)$_GET['fechar'];
    DB::update('barris',['status'=>'vazio'],'id=?',[$bid]);
    setFlash('success','Barril marcado como vazio.');
    redirect(BASE_URL.'modules/estoque/index.php?tab=barris');
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Estoque — Bar System Pro</title>
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
  <h4><i class="ph-bold ph-warehouse me-2"></i>Controle de Estoque</h4>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=chopp_barril" class="btn btn-amber btn-sm">
      <i class="ph-bold ph-beer-bottle me-1"></i>Barril de Chopp
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=chopp_lata" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-wine me-1"></i>Chopps/Cervejas em Lata
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=garrafa" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-wine me-1"></i>Garrafas
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=dose" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-tumbler me-1"></i>Destilados (Dose)
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=drink" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-martini me-1"></i>Drinks
    </a>
    <a href="<?= BASE_URL ?>modules/estoque/entrada.php?tipo=unidade" class="btn btn-outline-secondary btn-sm">
      <i class="ph-bold ph-squares-four me-1"></i>Unidades / Outros
    </a>
  </div>
</div>

<?= flash('success') ?><?= flash('error') ?>

<?php if (!empty($alertas)): ?>
<div class="admin-card mb-3" style="border-color:#92400e">
  <div class="d-flex align-items-center gap-2 mb-2">
    <i class="ph-bold ph-warning" style="color:var(--amber)"></i>
    <strong><?= count($alertas) ?> produto(s) com estoque abaixo do mínimo</strong>
  </div>
  <?php foreach ($alertas as $al): ?>
  <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="border-color:var(--border)!important">
    <span><?= h($al['nome']) ?></span>
    <span class="badge-amber"><?= number_format($al['estoque_atual'],2,',','.') ?> / mín <?= number_format($al['estoque_minimo'],2,',','.') ?> <?= h($al['unidade_estoque']) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="d-flex gap-2 mb-3">
  <?php foreach (['estoque'=>['warehouse','Produtos'],'barris'=>['beer-bottle','Barris de Chopp'],'movimentacoes'=>['arrow-right-arrow-left','Movimentações']] as $t=>[$ico,$lbl]): ?>
  <a href="?tab=<?= $t ?>" class="btn btn-sm <?= $tab===$t?'btn-amber':'btn-outline-secondary' ?>">
    <i class="ph-bold ph-<?= $ico ?> me-1"></i><?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'estoque'): ?>
<!-- ── Tab Produtos ── -->
<div class="admin-card">
<table class="admin-table">
  <thead>
    <tr><th>Produto</th><th>Tipo</th><th>Estoque Atual</th><th>Mínimo</th><th>Situação</th><th>Custo Unit.</th><th>Valor em Estoque</th></tr>
  </thead>
  <tbody>
  <?php foreach ($produtos_estoque as $p):
    $zero   = $p['estoque_atual']<=0 && in_array($p['tipo'],['unidade','chopp_lata','garrafa','dose']);
    $alerta = $p['estoque_minimo']>0 && $p['estoque_atual']<=$p['estoque_minimo'];
    $val_est= $p['estoque_atual'] * $p['preco_custo'];
  ?>
  <tr>
    <td>
      <div class="fw-semibold"><?= h($p['nome']) ?></div>
      <?php if ($p['cat_nome']): ?><span style="font-size:.68rem;color:<?= h($p['cat_cor']??'#888') ?>"><?= h($p['cat_nome']) ?></span><?php endif; ?>
    </td>
    <td class="text-muted" style="font-size:.78rem"><?= h(['unidade'=>'Unidade','dose'=>'Destilado (Dose)','chopp_lata'=>'Chopp Lata','chopp_barril'=>'Chopp Barril','garrafa'=>'Garrafa','drink'=>'Drink','combo'=>'Combo'][$p['tipo']]??$p['tipo']) ?></td>
    <td>
      <?php if ($p['tipo']==='chopp_barril'): ?>
        <?php $barril_ativo = DB::row("SELECT b.*,p2.ml_por_dose FROM barris b JOIN produtos p2 ON b.produto_id=p2.id WHERE b.produto_id=? AND b.status='em_uso' LIMIT 1",[$p['id']]); ?>
        <?php if ($barril_ativo): ?>
        <div style="font-size:.78rem">
          <div class="barril-progress mb-1" style="width:120px">
            <?php $pct_rest = max(0,min(100,(($barril_ativo['capacidade_ml']*$barril_ativo['rendimento_pct']/100)-$barril_ativo['ml_consumido'])/($barril_ativo['capacidade_ml']*$barril_ativo['rendimento_pct']/100)*100)); ?>
            <div class="barril-progress-bar" style="width:<?= $pct_rest ?>%"></div>
          </div>
          <span style="color:var(--amber)"><?= number_format($pct_rest,0) ?>%</span> restante
        </div>
        <?php else: ?><span class="badge-muted">Sem barril ativo</span><?php endif; ?>
      <?php else: ?>
      <span class="fw-bold <?= $zero?'text-danger':($alerta?'':'') ?>" style="<?= $alerta&&!$zero?'color:var(--amber)':'' ?>">
        <?= number_format($p['estoque_atual'],2,',','.') ?> <?= h($p['unidade_estoque']) ?>
      </span>
      <?php endif; ?>
    </td>
    <td class="text-muted" style="font-size:.82rem"><?= $p['estoque_minimo']>0?number_format($p['estoque_minimo'],2,',','.').' '.h($p['unidade_estoque']):'—' ?></td>
    <td>
      <?php if ($p['tipo']==='chopp_barril'): ?>
        <span class="badge-muted">Barril</span>
      <?php elseif ($zero): ?>
        <span class="badge-danger"><i class="ph-bold ph-x me-1"></i>Zerado</span>
      <?php elseif ($alerta): ?>
        <span class="badge-amber"><i class="ph-bold ph-warning me-1"></i>Baixo</span>
      <?php else: ?>
        <span class="badge-success"><i class="ph-bold ph-check me-1"></i>OK</span>
      <?php endif; ?>
    </td>
    <td class="text-muted">R$ <?= number_format($p['preco_custo'],2,',','.') ?></td>
    <td class="fw-semibold" style="color:var(--amber)">R$ <?= number_format($val_est,2,',','.') ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="6" class="text-end fw-bold" style="padding:.875rem">Valor Total em Estoque:</td>
      <td class="fw-bold" style="color:var(--amber);font-family:'Syne',sans-serif;font-size:1rem">
        R$ <?= number_format(array_sum(array_map(fn($p)=>$p['estoque_atual']*$p['preco_custo'],$produtos_estoque)),2,',','.') ?>
      </td>
    </tr>
  </tfoot>
</table>
</div>

<?php elseif ($tab === 'barris'): ?>
<!-- ── Tab Barris ── -->
<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="card-section-title mb-0">Barris Cadastrados</div>
        <button class="btn btn-amber btn-sm" data-bs-toggle="modal" data-bs-target="#modalBarril">
          <i class="ph-bold ph-plus me-1"></i>Novo Barril
        </button>
      </div>
      <?php foreach ($barris as $b):
        $ml_util  = $b['capacidade_ml'] * ($b['rendimento_pct']/100);
        $ml_rest  = max(0,$ml_util - $b['ml_consumido']);
        $pct_rest = $ml_util > 0 ? ($ml_rest/$ml_util*100) : 0;
        $doses_tot= ($b['ml_por_dose']??300)>0 ? floor($ml_util/($b['ml_por_dose']??300)) : 0;
        $doses_rest=($b['ml_por_dose']??300)>0 ? floor($ml_rest/($b['ml_por_dose']??300)) : 0;
        $cor_status= match($b['status']){'em_uso'=>'success','fechado'=>'muted','vazio'=>'danger',default=>'muted'};
        $lbl_status= match($b['status']){'em_uso'=>'Em Uso','fechado'=>'Fechado','vazio'=>'Vazio','descartado'=>'Descartado',default=>$b['status']};
      ?>
      <div class="barril-card mb-2">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="fw-bold"><?= h($b['produto_nome']) ?></div>
            <div class="text-muted" style="font-size:.75rem">
              <?= number_format($b['capacidade_ml']/1000,0) ?>L | Rend. <?= $b['rendimento_pct'] ?>% | <?= $b['ml_por_dose']??300 ?>ml/dose
              <?php if ($b['numero_serie']): ?> | Série: <?= h($b['numero_serie']) ?><?php endif; ?>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-<?= $cor_status ?>"><?= $lbl_status ?></span>
            <?php if ($b['status']==='fechado'): ?>
            <a href="?abrir=<?= $b['id'] ?>&tab=barris" class="btn btn-outline-secondary btn-sm py-0" onclick="return swalConfirm(event,'Abrir barril e adicionar doses ao estoque?','Abrir',this.href)">
              <i class="ph-bold ph-lock-open me-1"></i>Abrir
            </a>
            <?php elseif ($b['status']==='em_uso'): ?>
            <a href="?fechar=<?= $b['id'] ?>&tab=barris" class="btn btn-outline-danger btn-sm py-0" onclick="return swalConfirm(event,'Marcar barril como vazio?','Marcar Vazio',this.href)">
              <i class="ph-bold ph-lock me-1"></i>Vazio
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($b['status']==='em_uso'): ?>
        <div class="barril-progress mb-1">
          <div class="barril-progress-bar" style="width:<?= max(2,$pct_rest) ?>%"></div>
        </div>
        <div class="d-flex justify-content-between" style="font-size:.75rem;color:var(--text-muted)">
          <span><?= number_format($ml_rest/1000,2,',','.') ?>L restantes (<?= number_format($pct_rest,0) ?>%)</span>
          <span style="color:var(--amber)"><?= $doses_rest ?> doses restantes de <?= $doses_tot ?></span>
        </div>
        <?php endif; ?>
        <div class="d-flex gap-3 mt-2" style="font-size:.72rem;color:var(--text-muted)">
          <?php if ($b['data_abertura']): ?><span><i class="ph-bold ph-calendar me-1"></i>Aberto: <?= dataBR($b['data_abertura']) ?></span><?php endif; ?>
          <?php if ($b['data_vencimento']): ?><span><i class="ph-bold ph-clock me-1"></i>Vence: <?= dataBR($b['data_vencimento']) ?></span><?php endif; ?>
          <?php if ($b['custo_barril']>0): ?><span><i class="ph-bold ph-tag me-1"></i>Custo: R$ <?= number_format($b['custo_barril'],2,',','.') ?></span><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if(empty($barris)): ?>
      <div class="text-center py-4" style="color:var(--text-muted)"><i class="ph-bold ph-beer-bottle d-block fs-2 mb-2 opacity-30"></i>Nenhum barril cadastrado.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Card calculadora barril -->
  <div class="col-lg-4">
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-calculator me-2"></i>Calculadora de Barril</div>
      <div class="mb-2">
        <label class="form-label">Capacidade (Litros)</label>
        <select id="calcCap" class="form-select" onchange="atualizarCalc()">
          <option value="5">Minibarril 5L</option>
          <option value="10">10 Litros</option>
          <option value="20">20 Litros</option>
          <option value="30" selected>Barril 30L</option>
          <option value="50">Barril 50L</option>
        </select>
      </div>
      <div class="mb-2">
        <label class="form-label">Rendimento útil (%)</label>
        <input type="range" class="form-range" id="calcRend" min="70" max="98" value="85" oninput="document.getElementById('calcRendVal').textContent=this.value+'%';atualizarCalc()">
        <div class="text-center" style="color:var(--amber);font-weight:700" id="calcRendVal">85%</div>
      </div>
      <div class="mb-3">
        <label class="form-label">ML por copo/dose</label>
        <select id="calcDose" class="form-select" onchange="atualizarCalc()">
          <option value="200">200ml (copo pequeno)</option>
          <option value="300" selected>300ml (copo médio)</option>
          <option value="400">400ml (copo grande)</option>
          <option value="500">500ml (caneca)</option>
        </select>
      </div>
      <div class="barril-calc">
        <div class="calc-row"><span>Litros úteis:</span><span id="res_litros" style="color:var(--amber)">25,5 L</span></div>
        <div class="calc-row"><span>Perda estimada:</span><span id="res_perda" class="text-danger">4,5 L</span></div>
        <div class="calc-row destaque"><span>Doses estimadas:</span><span id="res_doses" style="color:var(--amber);font-family:'Syne',sans-serif;font-size:1.1rem">85</span></div>
        <div class="calc-row"><span>Custo do barril:</span><div class="input-group input-group-sm" style="width:130px"><span class="input-group-text">R$</span><input type="number" id="calcCusto" class="form-control" placeholder="0" oninput="atualizarCalc()"></div></div>
        <div class="calc-row"><span>Custo por dose:</span><span id="res_custo_dose" style="color:var(--success)">—</span></div>
        <div class="calc-row"><span>Preço venda/dose:</span><div class="input-group input-group-sm" style="width:130px"><span class="input-group-text">R$</span><input type="number" id="calcPreco" class="form-control" placeholder="0" oninput="atualizarCalc()"></div></div>
        <div class="calc-row destaque"><span>Lucro por barril:</span><span id="res_lucro" style="color:var(--success);font-family:'Syne',sans-serif">—</span></div>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'movimentacoes'): ?>
<!-- ── Tab Movimentações ── -->
<div class="admin-card">
<table class="admin-table">
  <thead><tr><th>Data/Hora</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Est. Ant.</th><th>Est. Novo</th><th>Custo Unit.</th><th>Motivo</th></tr></thead>
  <tbody>
  <?php foreach ($movimentacoes as $m):
    $cor = match($m['tipo']){'entrada'=>'success','abertura_barril'=>'success','saida'=>'danger','perda'=>'danger','ajuste'=>'muted',default=>'muted'};
    $sinal = in_array($m['tipo'],['entrada','abertura_barril']) ? '+' : '-';
  ?>
  <tr>
    <td class="text-muted" style="font-size:.75rem;white-space:nowrap"><?= dataHoraBR($m['created_at']) ?></td>
    <td class="fw-semibold" style="font-size:.82rem"><?= h($m['produto_nome']) ?></td>
    <td><span class="badge-<?= $cor ?>"><?= ucfirst(str_replace('_',' ',$m['tipo'])) ?></span></td>
    <td class="fw-bold" style="color:<?= $sinal==='+'?'var(--success)':'var(--danger)' ?>"><?= $sinal ?><?= number_format($m['quantidade'],0,',','.') ?> <?= h($m['unidade']??'') ?></td>
    <td class="text-muted" style="font-size:.78rem"><?= number_format($m['estoque_anterior']??0,2,',','.') ?></td>
    <td style="font-size:.82rem"><?= number_format($m['estoque_novo']??0,2,',','.') ?></td>
    <td class="text-muted" style="font-size:.78rem"><?= $m['custo_unitario']>0?'R$ '.number_format($m['custo_unitario'],2,',','.'):'—' ?></td>
    <td class="text-muted" style="font-size:.75rem"><?= h($m['motivo']??'') ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if(empty($movimentacoes)): ?><tr><td colspan="8" class="text-center py-5" style="color:var(--text-muted)">Nenhuma movimentação.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php endif; ?>

</div><!-- admin-content -->

<!-- Modal Novo Barril -->
<div class="modal fade" id="modalBarril" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border);color:var(--text)">
      <div class="modal-header" style="border-color:var(--border)">
        <h5 class="modal-title"><i class="ph-bold ph-beer-bottle me-2"></i>Cadastrar Barril</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="salvar_barril">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Produto (tipo chopp) *</label>
              <select name="produto_id" class="form-select" required>
                <option value="">Selecionar...</option>
                <?php foreach ($produtos_chopp as $pc): ?>
                <option value="<?= $pc['id'] ?>"><?= h($pc['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Capacidade *</label>
              <select name="capacidade_ml" class="form-select" required>
                <option value="5000">Minibarril 5L</option>
                <option value="10000">10 Litros</option>
                <option value="20000">20 Litros</option>
                <option value="30000" selected>30 Litros</option>
                <option value="50000">50 Litros</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Rendimento (%)</label>
              <input type="number" name="rendimento_pct" class="form-control" value="85" min="50" max="100" step="0.5">
            </div>
            <div class="col-md-4">
              <label class="form-label">Nº de Série / Lote</label>
              <input type="text" name="numero_serie" class="form-control" placeholder="Opcional">
            </div>
            <div class="col-md-4">
              <label class="form-label">Custo do Barril (R$)</label>
              <div class="input-group"><span class="input-group-text">R$</span>
              <input type="number" name="custo_barril" class="form-control" placeholder="0,00" step="0.01"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="fechado">Fechado (em estoque)</option>
                <option value="em_uso">Em uso (abrir agora)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Data de Abertura</label>
              <input type="date" name="data_abertura" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Data de Vencimento</label>
              <input type="date" name="data_vencimento" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-color:var(--border)">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-amber fw-bold">Cadastrar Barril</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function atualizarCalc(){
  const cap   = parseFloat(document.getElementById('calcCap').value)||30;
  const rend  = parseFloat(document.getElementById('calcRend').value)||85;
  const dose  = parseFloat(document.getElementById('calcDose').value)||300;
  const custo = parseFloat(document.getElementById('calcCusto').value)||0;
  const preco = parseFloat(document.getElementById('calcPreco').value)||0;
  const ml_util  = cap*1000*(rend/100);
  const ml_perda = cap*1000 - ml_util;
  const doses    = Math.floor(ml_util/dose);
  const cDose    = doses>0&&custo>0 ? custo/doses : 0;
  const lucro    = doses>0&&preco>0 ? (preco*doses)-custo : 0;
  const fmt = v => v.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('res_litros').textContent = fmt(ml_util/1000)+' L';
  document.getElementById('res_perda').textContent  = fmt(ml_perda/1000)+' L';
  document.getElementById('res_doses').textContent  = doses;
  document.getElementById('res_custo_dose').textContent = cDose>0?'R$ '+fmt(cDose)+'/dose':'—';
  document.getElementById('res_lucro').textContent  = lucro>0?'R$ '+fmt(lucro):(lucro<0?'- R$ '+fmt(Math.abs(lucro)):'—');
}
atualizarCalc();
</script>

<script>
function swalConfirm(e, msg, btnTxt, href) {
  e.preventDefault();
  Swal.fire({icon:'warning',title:msg,showCancelButton:true,confirmButtonText:btnTxt,
    cancelButtonText:'Cancelar',confirmButtonColor:'#f59e0b',background:'#1e2330',color:'#f0f2f7'})
    .then(r=>{ if(r.isConfirmed) window.location.href=href; });
}
</script>
</body>
</html>
