<?php
ob_start();
ini_set('display_errors','0');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();

$UPLOAD_PROD     = UPLOAD_PATH . 'produtos' . DIRECTORY_SEPARATOR;
$UPLOAD_URL_PROD = UPLOAD_URL  . 'produtos/';

$id      = (int)($_GET['id'] ?? 0);
$produto = $id ? DB::row("SELECT * FROM produtos WHERE id=?", [$id]) : null;
if ($id && !$produto) { setFlash('error','Produto não encontrado.'); redirect(BASE_URL.'modules/produtos/lista.php'); }

$categorias     = DB::all("SELECT * FROM categorias WHERE ativo=1 ORDER BY nome");
$todos_produtos = DB::all("SELECT id,nome,tipo,unidade_estoque FROM produtos WHERE ativo=1 AND tipo NOT IN ('drink','combo') ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'unidade';

    // Capacidade/rendimento para tipos dosados
    $cap_ml = $rend_pct = $ml_por_dose = null;
    if (in_array($tipo, ['chopp_barril','dose','garrafa'])) {
        $cr = $_POST['capacidade_ml'] ?? '';
        $cap_ml  = $cr === 'custom' ? ((float)($_POST['capacidade_ml_custom']??0)?:null) : (is_numeric($cr)&&(float)$cr>0?(float)$cr:null);
        $rend_pct    = (float)($_POST['rendimento_pct'] ?? 100);
        $ml_por_dose = (float)($_POST['ml_por_dose'] ?? 0) ?: null;
    }

    // Unidade automática
    $unidade = match($tipo) {
        'chopp_barril','dose','garrafa' => 'dose',
        'drink','combo'       => 'unidade',
        default               => $_POST['unidade_estoque'] ?: 'unidade',
    };

    // Composição drink/combo
    $composicao = null;
    if (in_array($tipo, ['drink','combo'])) {
        $ids = $_POST['comp_produto_id'] ?? [];
        $qtys= $_POST['comp_quantidade'] ?? [];
        $comp = [];
        foreach ($ids as $k => $pid) {
            $pid=(int)$pid; $qty=(float)($qtys[$k]??1);
            if ($pid>0 && $qty>0) $comp[] = ['produto_id'=>$pid,'quantidade'=>$qty];
        }
        if (!empty($comp)) $composicao = json_encode($comp);
    }

    $data = [
        'categoria_id'    => (int)($_POST['categoria_id']??0)?:null,
        'nome'            => trim($_POST['nome']??''),
        'descricao'       => trim($_POST['descricao']??''),
        'tipo'            => $tipo,
        'capacidade_ml'   => $cap_ml,
        'rendimento_pct'  => $rend_pct,
        'ml_por_dose'     => $ml_por_dose,
        'preco_custo'     => parseMoeda($_POST['preco_custo']??'0'),
        'preco_venda'     => parseMoeda($_POST['preco_venda']??'0'),
        'estoque_minimo'  => (int)($_POST['estoque_minimo']??0),
        'unidade_estoque' => $unidade,
        'codigo_barras'   => trim($_POST['codigo_barras']??''),
        'ativo'           => (int)($_POST['ativo']??1),
        'destaque'        => isset($_POST['destaque'])?1:0,
        'disponivel_pdv'  => isset($_POST['disponivel_pdv'])?1:0,
        'ordem_pdv'       => (int)($_POST['ordem_pdv']??0),
        'composicao'      => $composicao,
    ];
    if (empty($data['nome'])) { setFlash('error','Nome obrigatório.'); goto render; }

    // Upload imagem
    if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error']===UPLOAD_ERR_OK) {
        $imgName = uploadImagem($_FILES['imagem'], $UPLOAD_PROD);
        if ($imgName) {
            if ($produto&&$produto['imagem']&&file_exists($UPLOAD_PROD.$produto['imagem'])) @unlink($UPLOAD_PROD.$produto['imagem']);
            $data['imagem'] = $imgName;
        } else {
            setFlash('error','Falha no upload. Certifique-se que o arquivo é JPG/PNG/WebP com menos de 5MB. (mime:'.$_FILES['imagem']['type'].' size:'.$_FILES['imagem']['size'].')');
            goto render;
        }
    } elseif (!empty($_POST['remove_imagem']) && $produto && $produto['imagem']) {
        if (file_exists($UPLOAD_PROD.$produto['imagem'])) @unlink($UPLOAD_PROD.$produto['imagem']);
        $data['imagem'] = null;
    }

    if ($id) {
        // ── EDIÇÃO: salvar dados do produto ──────────────────────────────
        DB::update('produtos', $data, 'id=?', [$id]);

        // Ajuste de estoque
        $qtd_un_post  = isset($_POST['estoque_atual_un']) ? (int)$_POST['estoque_atual_un'] : -1;
        $estoque_db   = (int)($produto['estoque_atual'] ?? 0); // valor ANTES do update

        // Usar cap/ml_dose do FORM (recém submetido) — mais atual que o DB
        // $cap_ml e $ml_por_dose já foram extraídos do POST no topo
        $cap_save   = $cap_ml   ?? (float)($produto['capacidade_ml'] ?? 0);
        $mld_save   = $ml_por_dose ?? (float)($produto['ml_por_dose'] ?? 0);
        $rend_save  = $rend_pct  ?? (float)($produto['rendimento_pct'] ?: 100);
        $tipo_save  = $tipo;
        $un_save    = $unidade;
        $tem_conv   = $cap_save > 0 && $mld_save > 0
                      && in_array($tipo_save, ['chopp_barril','dose','garrafa']);

        if ($qtd_un_post >= 0) {
            if ($tem_conv) {
                // ── Produto com configuração de dose ──────────────────────
                // Campo = unidades físicas a ADICIONAR, converter em doses
                $dpu        = (int) floor($cap_save * ($rend_save/100) / $mld_save);
                $dosesAdd   = $qtd_un_post * $dpu;
                $novoEst    = $estoque_db + $dosesAdd;
                $motivo_s   = "Entrada: $qtd_un_post un × $dpu $un_save = $dosesAdd $un_save adicionados";
            } else {
                // ── Produto sem conversão ─────────────────────────────────
                // Campo = valor absoluto total desejado
                $novoEst  = $qtd_un_post;
                $motivo_s = "Ajuste direto: $estoque_db → $novoEst $un_save";
            }

            if ($novoEst !== $estoque_db) {
                DB::insert('estoque_movimentacoes', [
                    'produto_id'       => $id,
                    'tipo'             => 'ajuste',
                    'quantidade'       => (float) abs($novoEst - $estoque_db),
                    'estoque_anterior' => (float) $estoque_db,
                    'estoque_novo'     => (float) $novoEst,
                    'unidade'          => $un_save,
                    'custo_unitario'   => 0,
                    'motivo'           => $motivo_s,
                    'referencia'       => 'ajuste_cadastro',
                    'referencia_id'    => $id,
                    'operador'         => $_SESSION['operador'] ?? 'sistema',
                ]);
                DB::update('produtos', ['estoque_atual' => (float)$novoEst], 'id=?', [$id]);
            }
        }

        setFlash('success', 'Produto atualizado.');

    } else {
        // ── NOVO PRODUTO: estoque inicial ─────────────────────────────────
        $qtd_un  = (int)($_POST['estoque_inicial_un'] ?? 0);
        $est_ini = 0.0;

        if (!in_array($tipo, ['drink','combo'])) {
            if (in_array($tipo, ['chopp_barril','dose','garrafa'])
                && $cap_ml > 0 && $ml_por_dose > 0) {
                // Converter unidades em doses
                $dpu_novo = (int) floor($cap_ml * (($rend_pct ?: 100) / 100) / $ml_por_dose);
                $est_ini  = (float)($qtd_un * $dpu_novo);
                $mot_ini  = "Estoque inicial: $qtd_un un × $dpu_novo doses = $est_ini doses";
            } else {
                // Sem conversão: valor direto
                $est_ini = (float)$qtd_un;
                $mot_ini = "Estoque inicial: $est_ini";
            }
        }

        // Inserir com estoque_atual = 0 para movEstoque registrar corretamente
        $data['estoque_atual'] = 0.0;
        $newId = DB::insert('produtos', $data);
        if ($est_ini > 0) {
            // movEstoque lê estoque_atual=0 do DB, soma $est_ini → salva $est_ini corretamente
            movEstoque($newId, 'entrada', $est_ini, $data['preco_custo'],
                $mot_ini ?? 'Estoque inicial', 'manual', 0);
        }
        setFlash('success', 'Produto cadastrado.');
    }
    redirect(BASE_URL.'modules/produtos/lista.php');
}

render:
$composicao_arr = [];
if ($produto && $produto['composicao']) $composicao_arr = json_decode($produto['composicao'],true)??[];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id?'Editar':'Novo' ?> Produto</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
<?php include __DIR__.'/nav.php'; ?>
<div class="admin-content">
<div class="page-header">
  <h4><i class="ph-bold ph-<?= $id?'pencil-simple':'plus-circle' ?> me-2"></i><?= $id?'Editar Produto':'Novo Produto' ?></h4>
  <a href="<?= BASE_URL ?>modules/produtos/lista.php" class="btn btn-outline-secondary btn-sm"><i class="ph-bold ph-arrow-left me-1"></i>Voltar</a>
</div>
<?= flash('error') ?><?= flash('success') ?>

<form method="POST" enctype="multipart/form-data" id="formProduto">
<div class="row g-3">
<div class="col-lg-8">

  <!-- Identificação -->
  <div class="admin-card mb-3">
    <div class="card-section-title">Identificação</div>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Nome *</label><input type="text" name="nome" class="form-control" required value="<?= h($produto['nome']??'') ?>"></div>
      <div class="col-md-4">
        <label class="form-label">Tipo *</label>
        <select name="tipo" id="selTipo" class="form-select" onchange="onTipoChange(this.value)">
          <option value="unidade"      <?= ($produto['tipo']??'unidade')==='unidade'     ?'selected':'' ?>>🥫 Lata / Unidade</option>
          <option value="chopp_barril" <?= ($produto['tipo']??'')==='chopp_barril'       ?'selected':'' ?>>🍺 Chopp (Barril→Doses)</option>
          <option value="dose"         <?= ($produto['tipo']??'')==='dose'               ?'selected':'' ?>>🥃 Destilado (Garrafa→Doses)</option>
          <option value="garrafa"      <?= ($produto['tipo']??'')==='garrafa'            ?'selected':'' ?>>🍾 Garrafa (unidade inteira)</option>
          <option value="drink"        <?= ($produto['tipo']??'')==='drink'              ?'selected':'' ?>>🍹 Drink (composição)</option>
          <option value="combo"        <?= ($produto['tipo']??'')==='combo'              ?'selected':'' ?>>📦 Combo</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Categoria</label>
        <select name="categoria_id" class="form-select">
          <option value="">Sem categoria</option>
          <?php foreach ($categorias as $cat): ?>
          <option value="<?=$cat['id']?>" <?= ($produto['categoria_id']??0)==$cat['id']?'selected':'' ?>><?=h($cat['nome'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Unidade de Estoque</label>
        <select name="unidade_estoque" id="selUnidade" class="form-select">
          <option value="unidade" <?= ($produto['unidade_estoque']??'unidade')==='unidade'?'selected':'' ?>>Unidade</option>
          <option value="dose"    <?= ($produto['unidade_estoque']??'')==='dose'          ?'selected':'' ?>>Dose</option>
          <option value="garrafa" <?= ($produto['unidade_estoque']??'')==='garrafa'       ?'selected':'' ?>>Garrafa</option>
          <option value="litro"   <?= ($produto['unidade_estoque']??'')==='litro'         ?'selected':'' ?>>Litro</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Código de Barras</label><input type="text" name="codigo_barras" class="form-control" value="<?=h($produto['codigo_barras']??'')?>"></div>
      <div class="col-12"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"><?=h($produto['descricao']??'')?></textarea></div>
    </div>
  </div>

  <!-- Configuração de Capacidade -->
  <div class="admin-card mb-3" id="cardCapacidade" style="display:none">
    <div class="card-section-title" id="titCapacidade">⚙ Configuração de Capacidade</div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" id="lblCap">Capacidade</label>
        <select name="capacidade_ml" id="selCap" class="form-select" onchange="onCapChange()">
          <option value="">Selecionar...</option>
          <optgroup label="Barril de Chopp" id="grpBarril">
            <option value="5000"  <?=($produto['capacidade_ml']??0)==5000 ?'selected':''?>>Minibarril 5L</option>
            <option value="10000" <?=($produto['capacidade_ml']??0)==10000?'selected':''?>>Barril 10L</option>
            <option value="20000" <?=($produto['capacidade_ml']??0)==20000?'selected':''?>>Barril 20L</option>
            <option value="30000" <?=($produto['capacidade_ml']??0)==30000?'selected':''?>>Barril 30L</option>
            <option value="50000" <?=($produto['capacidade_ml']??0)==50000?'selected':''?>>Barril 50L</option>
          </optgroup>
          <optgroup label="Garrafa de Destilado" id="grpDose">
            <option value="375"  <?=($produto['capacidade_ml']??0)==375 ?'selected':''?>>375ml (meia garrafa)</option>
            <option value="700"  <?=($produto['capacidade_ml']??0)==700 ?'selected':''?>>700ml</option>
            <option value="750"  <?=($produto['capacidade_ml']??0)==750 ?'selected':''?>>750ml</option>
            <option value="1000" <?=($produto['capacidade_ml']??0)==1000?'selected':''?>>1L (1000ml)</option>
            <option value="1750" <?=($produto['capacidade_ml']??0)==1750?'selected':''?>>1,75L</option>
            <option value="2000" <?=($produto['capacidade_ml']??0)==2000?'selected':''?>>2L</option>
          </optgroup>
          <option value="custom">📐 Personalizado</option>
        </select>
        <input type="number" name="capacidade_ml_custom" id="capCustom" class="form-control mt-1 d-none"
               placeholder="Digite em ML" oninput="calcDoses()" value="<?=($produto['capacidade_ml']??'')?>">
      </div>
      <div class="col-md-4" id="colRend">
        <label class="form-label">Rendimento útil <strong id="rendLabel" style="color:var(--amber)"><?=$produto['rendimento_pct']??85?>%</strong></label>
        <input type="range" name="rendimento_pct" id="sliderRend" class="form-range"
               min="50" max="100" step="0.5" value="<?=$produto['rendimento_pct']??85?>"
               oninput="document.getElementById('rendLabel').textContent=this.value+'%';calcDoses()">
        <small style="color:var(--text-muted);font-size:.68rem">Padrão chopp: 85% | Destilado: 100%</small>
      </div>
      <div class="col-md-4">
        <label class="form-label" id="lblDose">ML por Dose/Copo</label>
        <select name="ml_por_dose" id="selDose" class="form-select" onchange="calcDoses()">
          <option value="">Selecionar...</option>
          <optgroup label="Destilados / Doses">
            <option value="30"  <?=($produto['ml_por_dose']??0)==30 ?'selected':''?>>30ml – Shot</option>
            <option value="50"  <?=($produto['ml_por_dose']??0)==50 ?'selected':''?>>50ml – Dose padrão</option>
            <option value="60"  <?=($produto['ml_por_dose']??0)==60 ?'selected':''?>>60ml – Dose dupla</option>
          </optgroup>
          <optgroup label="Chopp / Chope">
            <option value="100" <?=($produto['ml_por_dose']??0)==100?'selected':''?>>100ml</option>
            <option value="150" <?=($produto['ml_por_dose']??0)==150?'selected':''?>>150ml</option>
            <option value="200" <?=($produto['ml_por_dose']??0)==200?'selected':''?>>200ml – Copo pequeno</option>
            <option value="250" <?=($produto['ml_por_dose']??0)==250?'selected':''?>>250ml</option>
            <option value="300" <?=($produto['ml_por_dose']??0)==300?'selected':''?>>300ml – Copo médio</option>
            <option value="350" <?=($produto['ml_por_dose']??0)==350?'selected':''?>>350ml</option>
            <option value="400" <?=($produto['ml_por_dose']??0)==400?'selected':''?>>400ml – Copo grande</option>
            <option value="500" <?=($produto['ml_por_dose']??0)==500?'selected':''?>>500ml – Pint</option>
          </optgroup>
          <option value="500" <?=($produto['ml_por_dose']??0)==500?'selected':''?>>500ml – Caneca</option>
        </select>
      </div>
    </div>
    <!-- Resultado -->
    <div id="calcRes" class="mt-3 d-none">
      <div style="background:var(--bg-card2);border-radius:8px;padding:1rem;display:flex;gap:2rem;flex-wrap:wrap;align-items:center">
        <div><div style="font-size:.65rem;color:var(--text-muted)">ML ÚTEIS</div><div style="font-size:1.1rem;font-weight:700;color:var(--amber)" id="rUtil">—</div></div>
        <div><div style="font-size:.65rem;color:var(--text-muted)">PERDA</div><div style="font-size:1.1rem;font-weight:700;color:#ef4444" id="rPerda">—</div></div>
        <div style="border-left:2px solid var(--amber);padding-left:2rem">
          <div style="font-size:.65rem;color:var(--text-muted)" id="lblResD">DOSES ESTIMADAS</div>
          <div style="font-size:2.5rem;font-weight:800;color:var(--amber);font-family:'Syne',sans-serif;line-height:1" id="rDoses">—</div>
          <div style="font-size:.7rem;color:var(--text-muted)">→ serão inseridas no estoque</div>
        </div>
        <div><div style="font-size:.65rem;color:var(--text-muted)">CUSTO/DOSE</div><div style="font-size:1.1rem;font-weight:700;color:#22c55e" id="rCusto">—</div></div>
      </div>
    </div>
  </div>

  <!-- Composição (Drink/Combo) -->
  <div class="admin-card mb-3" id="cardComp" style="display:none">
    <div class="card-section-title" id="titComp">🍹 Composição</div>
    <p class="small mb-3" id="descComp" style="color:var(--text-muted)">Produtos que compõem este item. O estoque de cada um será baixado automaticamente a cada venda.</p>
    <div id="listaComp">
      <?php foreach ($composicao_arr as $ci => $c): ?>
      <div class="comp-row d-flex gap-2 mb-2 align-items-center">
        <select name="comp_produto_id[]" class="form-select" style="flex:1">
          <option value="">— Produto —</option>
          <?php foreach ($todos_produtos as $tp): ?>
          <option value="<?=$tp['id']?>" <?=$tp['id']==$c['produto_id']?'selected':''?>><?=h($tp['nome'])?> (<?=h($tp['unidade_estoque'])?>)</option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="comp_quantidade[]" class="form-control" style="width:90px" value="<?=(int)$c['quantidade']?>" min="1" step="1">
        <span style="font-size:.72rem;color:var(--text-muted);white-space:nowrap" class="comp-un"></span>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.comp-row').remove()"><i class="ph-bold ph-x"></i></button>
      </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-1" onclick="addComp()">
      <i class="ph-bold ph-plus me-1"></i>Adicionar Ingrediente
    </button>
  </div>

  <!-- Preços e Estoque -->
  <div class="admin-card">
    <div class="card-section-title">Preços</div>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Custo (R$)</label><div class="input-group"><span class="input-group-text">R$</span><input type="text" name="preco_custo" id="pCusto" class="form-control" value="<?=number_format($produto['preco_custo']??0,2,',','.')?>" oninput="calcMargem()"></div></div>
      <div class="col-md-4"><label class="form-label">Venda (R$) *</label><div class="input-group"><span class="input-group-text">R$</span><input type="text" name="preco_venda" id="pVenda" class="form-control" required value="<?=number_format($produto['preco_venda']??0,2,',','.')?>" oninput="calcMargem()"></div></div>
      <div class="col-md-4"><label class="form-label">Margem</label><div class="input-group"><input type="text" id="pMargem" class="form-control" readonly><span class="input-group-text">%</span></div></div>
    </div>
    <div class="card-section-title mt-3">Estoque</div>
    <div class="row g-3">
      <?php
      $cap_atual  = (float)($produto['capacidade_ml'] ?? 0);
      $ml_atual   = (float)($produto['ml_por_dose']   ?? 0);
      $rend_atual = (float)($produto['rendimento_pct'] ?: 100);
      $dpu        = ($cap_atual > 0 && $ml_atual > 0)
                    ? (int) floor($cap_atual * ($rend_atual/100) / $ml_atual) : 0;
      $est_db     = (int)($produto['estoque_atual'] ?? 0);
      $tipo_prod  = $produto['tipo'] ?? 'unidade';
      $un_prod    = $produto['unidade_estoque'] ?? 'un';
      // Tipos que usam conversão unidade→dose (sempre mostra o layout com preview)
      $eh_dosado  = in_array($tipo_prod, ['chopp_barril','dose','garrafa']);
      // Label da unidade física (o que o operador está inserindo)
      $un_fisica  = match($tipo_prod) {
        'chopp_barril' => 'barril(s)',
        'garrafa'      => 'garrafa(s)',
        'dose'         => 'garrafa(s)',
        default        => 'un',
      };
      // Label do que aparece no PDV/copo
      $un_copo    = match($tipo_prod) {
        'chopp_barril' => 'copo(s)',
        default        => $un_prod,
      };
      ?>

      <?php if (!$id): ?>
      <!-- ── NOVO PRODUTO ── -->
      <div class="col-md-6" id="colEstIni">
        <label class="form-label fw-semibold" id="lblEstIni">
          <?= $eh_dosado ? 'Quantos ' . $un_fisica . ' entram?' : 'Estoque Inicial' ?>
        </label>
        <div class="d-flex gap-3 align-items-start">
          <div style="flex:1">
            <div class="input-group">
              <input type="number" name="estoque_inicial_un" id="estIniUn"
                     class="form-control" step="1" min="0" value="0"
                     oninput="recalcEstIni()" style="font-size:1.1rem;font-weight:700">
              <span class="input-group-text" id="spUnNovo" style="min-width:80px;font-size:.82rem">
                <?= $eh_dosado ? $un_fisica : ($un_prod ?: 'un') ?>
              </span>
            </div>
          </div>
          <!-- Preview de copos/doses (aparece via JS quando tem cap configurada) -->
          <div id="previewConvNovo" style="display:none;text-align:center;min-width:90px">
            <div style="font-size:.68rem;color:var(--text-muted)">equivale a</div>
            <div id="previewDosesNovo" style="font-size:1.5rem;font-weight:900;color:var(--amber)">0</div>
            <div id="previewUnNovo" style="font-size:.72rem;color:var(--text-muted)"><?= $un_copo ?></div>
          </div>
        </div>
        <input type="hidden" name="estoque_inicial" id="estIniDoses" value="0">
        <div id="hintEst" style="font-size:.75rem;color:var(--text-muted);margin-top:3px"></div>
      </div>

      <?php else: ?>
      <!-- ── EDIÇÃO ── -->
      <div class="col-md-6">
        <div class="d-flex gap-3 align-items-start">

          <?php if ($eh_dosado): ?>
          <!-- Layout igual ao destilado: estoque atual em destaque + campo de unidades -->
          <div style="flex:1">
            <!-- Estoque atual em destaque (amber, leitura) -->
            <div style="margin-bottom:.625rem">
              <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;margin-bottom:2px">
                Estoque atual
              </div>
              <div style="font-size:1.5rem;font-weight:900;color:var(--amber);line-height:1">
                <?= $est_db ?> <span style="font-size:.85rem;font-weight:400;color:var(--text-muted)"><?= h($un_prod) ?></span>
              </div>
            </div>

            <!-- Campo: unidades físicas a adicionar -->
            <div style="font-size:.78rem;color:var(--text-muted);font-weight:600;margin-bottom:4px">
              Adicionar (em <?= h($un_fisica) ?>):
            </div>
            <div class="input-group">
              <input type="number" name="estoque_atual_un" id="estAtualUn"
                     class="form-control" step="1" min="0" value="0"
                     oninput="recalcEstEdit(<?= $dpu ?>, <?= $est_db ?>, <?= json_encode($un_copo) ?>)">
              <span class="input-group-text"><?= h($un_fisica) ?></span>
            </div>

            <!-- Preview conversão -->
            <?php if ($dpu > 0): ?>
            <div id="previewEstEdit"
                 style="font-size:.82rem;margin-top:5px;color:var(--text-muted);min-height:1.2rem">
              1 <?= h($un_fisica) ?> = <?= $dpu ?> <?= h($un_copo) ?>
            </div>
            <?php else: ?>
            <div style="font-size:.75rem;margin-top:5px;color:#ef4444">
              ⚠️ Configure a <strong>Capacidade</strong> e <strong>ML por Copo</strong> acima para ver a conversão.
            </div>
            <?php endif; ?>

            <input type="hidden" name="estoque_atual_ajuste" value="<?= $est_db ?>">
          </div>

          <?php else: ?>
          <!-- Tipos simples (lata, unidade): campo direto -->
          <div style="flex:1">
            <div style="font-size:.72rem;color:var(--text-muted);font-weight:600;margin-bottom:4px">
              Estoque atual em <?= h($un_prod) ?>:
            </div>
            <div class="input-group">
              <input type="number" name="estoque_atual_un" id="estAtualUn"
                     class="form-control" step="1" min="0" value="<?= $est_db ?>">
              <span class="input-group-text"><?= h($un_prod) ?></span>
            </div>
            <small style="color:var(--text-muted);font-size:.72rem">
              Define o valor total de estoque diretamente
            </small>
            <input type="hidden" name="estoque_atual_ajuste" value="<?= $est_db ?>">
          </div>
          <?php endif; ?>

        </div>
      </div>
      <?php endif; ?>

            <div class="col-md-3"><label class="form-label">Estoque Mínimo</label><input type="text" name="estoque_minimo" class="form-control" value="<?=(int)($produto['estoque_minimo']??0)?>"></div>
      <div class="col-md-3"><label class="form-label">Ordem PDV</label><input type="number" name="ordem_pdv" class="form-control" value="<?=$produto['ordem_pdv']??0?>" min="0"></div>
    </div>
  </div>
</div><!-- col-lg-8 -->

<!-- Sidebar -->
<div class="col-lg-4">
  <!-- Imagem -->
  <div class="admin-card mb-3">
    <div class="card-section-title">Imagem</div>
    <div class="img-upload-wrap" onclick="document.getElementById('imgInput').click()" style="cursor:pointer">
      <?php if ($produto && $produto['imagem']): ?>
        <img src="<?=$UPLOAD_URL_PROD.h($produto['imagem'])?>" id="prevImg" class="img-preview" alt="">
      <?php else: ?>
        <div class="img-placeholder" id="imgPh">
          <i class="ph-bold ph-cloud-arrow-up d-block mb-1" style="font-size:2rem"></i>
          Clique para enviar<br><small>JPG/PNG/WebP até 5MB</small>
        </div>
        <img src="" id="prevImg" class="img-preview d-none" alt="">
      <?php endif; ?>
    </div>
    <input type="file" name="imagem" id="imgInput" accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" class="d-none" onchange="showPreview(this)">
    <input type="hidden" name="remove_imagem" id="rmImg" value="">
    <?php if ($produto && $produto['imagem']): ?>
    <button type="button" class="btn btn-outline-danger btn-sm w-100 mt-2" onclick="rmImagem()">
      <i class="ph-bold ph-trash me-1"></i>Remover
    </button>
    <?php endif; ?>
  </div>
  <!-- Config PDV -->
  <div class="admin-card mb-3">
    <div class="card-section-title">PDV</div>
    <div class="form-check form-switch mb-2">
      <input type="checkbox" name="disponivel_pdv" class="form-check-input" id="swPdv" value="1" <?=($produto['disponivel_pdv']??1)?'checked':''?>>
      <label class="form-check-label" for="swPdv">Disponível no PDV</label>
    </div>
    <div class="form-check form-switch mb-2">
      <input type="checkbox" name="destaque" class="form-check-input" id="swDest" value="1" <?=($produto['destaque']??0)?'checked':''?>>
      <label class="form-check-label" for="swDest">⭐ Destaque</label>
    </div>
    <div class="form-check form-switch">
      <input type="checkbox" name="ativo" class="form-check-input" id="swAtivo" value="1" <?=($produto['ativo']??1)?'checked':''?>>
      <label class="form-check-label" for="swAtivo">Ativo</label>
    </div>
  </div>
  <!-- Salvar -->
  <div class="admin-card">
    <button type="submit" class="btn btn-amber w-100 btn-lg fw-bold mb-2">
      <i class="ph-bold ph-check me-2"></i><?=$id?'Salvar Alterações':'Cadastrar'?>
    </button>
    <a href="<?=BASE_URL?>modules/produtos/lista.php" class="btn btn-outline-secondary w-100">Cancelar</a>
  </div>
</div>
</div>
</form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const TODOS = <?= json_encode($todos_produtos, JSON_UNESCAPED_UNICODE) ?>;

function pM(s){ return parseFloat((s||'0').replace(/\./g,'').replace(',','.'))||0; }
function fmtN(v){ return v.toLocaleString('pt-BR',{minimumFractionDigits:2}); }

function onTipoChange(t) {
  const hasCap  = t==='chopp_barril'||t==='dose'||t==='garrafa';
  const hasComp = t==='drink'||t==='combo';
  document.getElementById('cardCapacidade').style.display = hasCap?'':'none';
  // Mostrar grupo correto de capacidades
  const grpB = document.getElementById('grpBarril');
  const grpG = document.getElementById('grpGarrafa');
  if (grpB) grpB.style.display = (t==='chopp_barril'||t==='dose') ? '' : 'none';
  if (grpG) grpG.style.display = (t==='garrafa') ? '' : 'none';
  document.getElementById('cardComp').style.display       = hasComp?'':'none';

  // Unidade automática
  const un = {'chopp_barril':'dose','dose':'dose','garrafa':'unidade','drink':'unidade','combo':'unidade','unidade':'unidade'}[t]||'unidade';
  document.getElementById('selUnidade').value = un;

  // Labels por tipo
  if (t==='chopp_barril') {
    document.getElementById('titCapacidade').textContent='🍺 Configuração do Barril';
    document.getElementById('lblCap').textContent='Capacidade do Barril';
    document.getElementById('lblDose').textContent='ML por Copo';
    document.getElementById('grpBarril').style.display='';
    document.getElementById('grpDose').style.display='none';
    document.getElementById('colRend').style.display='';
    document.getElementById('sliderRend').value=85;
    document.getElementById('rendLabel').textContent='85%';
    document.getElementById('lblResD').textContent='COPOS ESTIMADOS';
  } else if (t==='dose') {
    document.getElementById('titCapacidade').textContent='🥃 Configuração da Garrafa';
    document.getElementById('lblCap').textContent='Capacidade da Garrafa';
    document.getElementById('lblDose').textContent='ML por Dose';
    document.getElementById('grpBarril').style.display='none';
    document.getElementById('grpDose').style.display='';
    document.getElementById('colRend').style.display='none';
    document.getElementById('sliderRend').value=100;
    document.getElementById('rendLabel').textContent='100%';
    const lblD = document.getElementById('lblResD');
    if(lblD) lblD.textContent = t==='garrafa' ? 'DOSES POR GARRAFA' : 'DOSES ESTIMADAS';
  }

  if (hasComp) {
    const isCombo = t==='combo';
    document.getElementById('titComp').textContent = isCombo?'📦 Composição do Combo':'🍹 Composição do Drink';
    document.getElementById('descComp').textContent = isCombo
      ? 'Produtos que compõem o combo. Cada produto terá seu estoque baixado na venda.'
      : 'Ingredientes do drink. O estoque de cada ingrediente é baixado automaticamente.';
  }

  // Estoque inicial
  const colEst = document.getElementById('colEstIni');
  if (colEst) {
    if (hasCap) {
      const hint = t==='garrafa' ? '← doses por garrafa (preencha capacidade acima)' : '← calculado automaticamente (preencha capacidade acima)';
      document.getElementById('hintEst').textContent = hint;
      document.getElementById('estIni').readOnly = false;
      document.getElementById('estIni').style.opacity = '1';
      colEst.style.display = '';
    } else if (hasComp) {
      colEst.style.display = 'none';
    } else {
      document.getElementById('hintEst').textContent = '';
      document.getElementById('estIni').readOnly = false;
      document.getElementById('estIni').style.opacity = '1';
      colEst.style.display = '';
    }
  }
  calcDoses();
}

function onCapChange() {
  const sel = document.getElementById('selCap');
  const inp = document.getElementById('capCustom');
  sel.value==='custom' ? inp.classList.remove('d-none') : inp.classList.add('d-none');
  calcDoses();
}

function getCapML() {
  const sel = document.getElementById('selCap');
  if (!sel) return 0;
  if (sel.value==='custom') return parseFloat(document.getElementById('capCustom').value)||0;
  return parseFloat(sel.value)||0;
}

function calcDoses() {
  const t = document.getElementById('selTipo').value;
  if (t!=='chopp_barril' && t!=='dose' && t!=='garrafa') return;
  const cap   = getCapML();
  const rend  = parseFloat(document.getElementById('sliderRend').value)||100;
  const dose  = parseFloat(document.getElementById('selDose').value)||0;
  const custo = pM(document.getElementById('pCusto').value);
  const res   = document.getElementById('calcRes');
  if (!cap || !dose) { res.classList.add('d-none'); return; }
  const mlU   = cap*(rend/100);
  const mlP   = cap-mlU;
  const doses = Math.floor(mlU/dose);
  const cDose = doses>0&&custo>0 ? custo/doses : 0;
  document.getElementById('rUtil').textContent  = (mlU/1000).toFixed(2).replace('.',',')+' L';
  document.getElementById('rPerda').textContent = (mlP/1000).toFixed(2).replace('.',',')+' L';
  document.getElementById('rDoses').textContent = doses;
  document.getElementById('rCusto').textContent = cDose>0?'R$ '+fmtN(cDose)+'/dose':'—';
  res.classList.remove('d-none');
  // Também atualizar o preview do campo de unidades
  recalcEstIni();
}

/* ── Recalcular estoque inicial (novo produto) ─────────────── */
function recalcEstIni() {
  var t    = document.getElementById('selTipo').value;
  var cap  = getCapML();
  var rend = parseFloat((document.getElementById('sliderRend') || {value:100}).value) || 100;
  var dose = parseFloat((document.getElementById('selDose') || {value:0}).value) || 0;
  var qtd  = parseInt((document.getElementById('estIniUn') || {value:0}).value) || 0;

  var podeConv = cap > 0 && dose > 0 && ['chopp_barril','dose','garrafa'].includes(t);

  var hint     = document.getElementById('hintEst');
  var lbl      = document.getElementById('lblEstIni');
  var prevDiv  = document.getElementById('previewConvNovo');
  var prevDose = document.getElementById('previewDosesNovo');
  var spUn     = document.getElementById('spUnNovo');
  var hdnDoses = document.getElementById('estIniDoses');

  if (podeConv) {
    var dpu   = Math.floor(cap * (rend/100) / dose);
    var total = qtd * dpu;
    // Rótulos por tipo
    var t     = document.getElementById('selTipo').value;
    var unFis = t === 'chopp_barril' ? 'barris' : 'garrafas';
    var unCop = t === 'chopp_barril' ? 'copo(s)' : 'dose(s)';
    if (lbl)      lbl.textContent  = 'Quantos ' + unFis + ' entram?';
    if (prevDiv)  prevDiv.style.display  = qtd > 0 ? '' : 'none';
    if (prevDose) prevDose.textContent   = total;
    var prevUnEl = document.getElementById('previewUnNovo');
    if (prevUnEl) prevUnEl.textContent   = unCop;
    var spUnEl   = document.getElementById('spUnNovo');
    if (spUnEl)   spUnEl.textContent     = unFis;
    if (hint)     hint.innerHTML = '<span style="color:var(--text-muted)">1 ' + unFis.slice(0,-1) + ' = ' + dpu + ' ' + unCop + '</span>';
    if (hdnDoses) hdnDoses.value = total;
  } else {
    if (lbl)     lbl.textContent  = 'Estoque Inicial';
    if (prevDiv) prevDiv.style.display = 'none';
    if (hint)    hint.textContent = '';
    if (hdnDoses && document.getElementById('estIniUn'))
      hdnDoses.value = document.getElementById('estIniUn').value || 0;
  }
}

/* ── Recalcular preview no modo edição ──────────────────────── */
function recalcEstEdit(dpu, estAtual, unCopo) {
  var qtd     = parseInt((document.getElementById('estAtualUn') || {value:0}).value) || 0;
  var preview = document.getElementById('previewEstEdit');
  if (!preview) return;
  if (dpu > 0) {
    var add      = qtd * dpu;
    var novoTot  = estAtual + add;
    if (qtd > 0) {
      preview.innerHTML =
        '<strong style="color:#22c55e">+' + add + ' ' + unCopo + '</strong>' +
        ' → total: <strong style="color:var(--amber)">' + novoTot + ' ' + unCopo + '</strong>';
    } else {
      preview.innerHTML =
        '<span style="color:var(--text-muted)">Digite quantos barris/garrafas para ver o total</span>';
    }
  }
}

function addComp() {
  const div = document.createElement('div');
  div.className = 'comp-row d-flex gap-2 mb-2 align-items-center';
  let opts = '<option value="">— Produto —</option>';
  TODOS.forEach(p=>{ opts+=`<option value="${p.id}">${p.nome} (${p.unidade_estoque})</option>`; });
  div.innerHTML = `<select name="comp_produto_id[]" class="form-select" style="flex:1">${opts}</select>
    <input type="number" name="comp_quantidade[]" class="form-control" style="width:90px" value="1" min="1" step="1" placeholder="1">
    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.comp-row').remove()"><i class="ph-bold ph-x"></i></button>`;
  document.getElementById('listaComp').appendChild(div);
}

function showPreview(input) {
  if (!input.files?.[0]) return;
  const r = new FileReader();
  r.onload = e => {
    const img = document.getElementById('prevImg');
    img.src = e.target.result; img.classList.remove('d-none');
    const ph = document.getElementById('imgPh'); if(ph) ph.style.display='none';
  };
  r.readAsDataURL(input.files[0]);
}
function rmImagem() {
  document.getElementById('rmImg').value='1';
  document.getElementById('imgInput').value='';
  const img=document.getElementById('prevImg'); if(img){img.src='';img.classList.add('d-none');}
  const ph=document.getElementById('imgPh'); if(ph) ph.style.display='';
}

function calcMargem() {
  const c=pM(document.getElementById('pCusto').value);
  const v=pM(document.getElementById('pVenda').value);
  const m=c>0?((v-c)/c*100).toFixed(1):'—';
  const el=document.getElementById('pMargem'); if(el) el.value=m!=='—'?m:'';
  calcDoses();
}

// Init
(function(){
  const t=document.getElementById('selTipo').value;
  onTipoChange(t);
  <?php if($produto&&$produto['capacidade_ml']): ?>
  const sv=document.getElementById('selCap');
  const strVal='<?=(int)($produto['capacidade_ml']??0)?>';
  let found=false;
  for(let o of sv.options){if(o.value===strVal){sv.value=strVal;found=true;break;}}
  if(!found&&strVal!=='0'){sv.value='custom';document.getElementById('capCustom').value=strVal;document.getElementById('capCustom').classList.remove('d-none');}
  const sd=document.getElementById('selDose');
  if(sd) sd.value='<?=(int)($produto['ml_por_dose']??0)?>';
  <?php endif; ?>
  calcDoses(); calcMargem();
})();
</script>
</body>
</html>
