<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();

$_cor  = DB::cfg('cor_primaria',  '#f59e0b');
$_cor2 = DB::cfg('cor_secundaria','#d97706');

/* ── POST: registrar entrada ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itens = $_POST['itens'] ?? [];
    $ok    = 0;

    foreach ($itens as $it) {
        $pid    = (int)($it['produto_id'] ?? 0); if (!$pid) continue;
        $qtdEnt = (int)($it['quantidade'] ?? 0); if ($qtdEnt <= 0) continue;
        $custo  = parseMoeda($it['custo_unitario'] ?? '0');
        $motivo = trim($it['motivo'] ?? 'Entrada de estoque');

        // Busca dados do produto para conversão unidades → doses
        $prod = DB::row(
            "SELECT tipo, capacidade_ml, ml_por_dose, rendimento_pct FROM produtos WHERE id=?",
            [$pid]
        );

        $qtdFinal = $qtdEnt; // padrão: sem conversão

        $cap    = (float)($prod['capacidade_ml'] ?? 0);
        $mlDose = (float)($prod['ml_por_dose']   ?? 0);
        $rend   = (float)($prod['rendimento_pct'] ?: 100) / 100;
        $tipo   = $prod['tipo'] ?? '';

        // Produtos com capacidade + dose: converte unidades → doses
        if ($cap > 0 && $mlDose > 0 && in_array($tipo, ['chopp_barril', 'dose', 'garrafa'])) {
            $dosesPorUn = (int) floor($cap * $rend / $mlDose);
            if ($dosesPorUn > 0) {
                $qtdFinal = $qtdEnt * $dosesPorUn;
                $motivo  .= " | $qtdEnt un × $dosesPorUn doses = $qtdFinal doses";
            }
        }

        if ($custo > 0) DB::update('produtos', ['preco_custo' => $custo], 'id=?', [$pid]);
        movEstoque($pid, 'entrada', (float)$qtdFinal, $custo, $motivo, 'manual', 0);
        $ok++;
    }

    if ($ok > 0) setFlash('success', "$ok item(ns) lançado(s) no estoque com sucesso.");
    else         setFlash('error', 'Nenhum item válido informado.');
    redirect(BASE_URL . 'modules/estoque/index.php');
}

/* ── Dados para o formulário ─────────────────────────────── */
$produto_presel = (int)($_GET['produto_id'] ?? 0);
$tipo_filtro    = trim($_GET['tipo'] ?? '');

$tipoLabel = [
    'chopp_barril' => '🍺 Barril de Chopp',
    'chopp_lata'   => '🍺 Cervejas em Lata',
    'garrafa'      => '🍾 Garrafas / Destilados',
    'dose'         => '🥃 Destilados (Dose)',
    'drink'        => '🍹 Drinks',
    'unidade'      => '📦 Unidades / Outros',
];

if ($tipo_filtro) {
    $produtos = DB::all(
        "SELECT p.*, c.nome as cat_nome
         FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id
         WHERE p.ativo = 1 AND p.tipo = ?
         ORDER BY p.nome",
        [$tipo_filtro]
    );
} else {
    $produtos = DB::all(
        "SELECT p.*, c.nome as cat_nome
         FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id
         WHERE p.ativo = 1
         ORDER BY p.tipo, p.nome"
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entrada de Estoque — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>
:root { --amber: <?= $_cor ?>; --amber-dark: <?= $_cor2 ?>; }
.conversao-badge {
    display: inline-block;
    background: rgba(245,158,11,.15);
    border: 1px solid var(--amber);
    color: var(--amber);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: .8rem;
    font-weight: 700;
    margin-top: 4px;
}
</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/nav.php'; ?>
<div class="admin-content">

<div class="page-header">
  <h4><i class="ph-bold ph-package-arrow-in-down me-2"></i>Entrada de Estoque
    <?= $tipo_filtro ? ' — ' . ($tipoLabel[$tipo_filtro] ?? $tipo_filtro) : '' ?>
  </h4>
  <a href="<?= BASE_URL ?>modules/estoque/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="ph-bold ph-arrow-left me-1"></i>Voltar
  </a>
</div>

<?= flash('error') ?><?= flash('success') ?>

<form method="POST" id="entradaForm">
<div class="admin-card mb-3">
  <div class="card-section-title">Adicionar Produto</div>
  <div class="row g-3 align-items-start p-2 rounded" style="background:var(--bg-card2)">

    <!-- Produto -->
    <div class="col-md-4">
      <label class="form-label fw-semibold">Produto</label>
      <select id="selProduto" class="form-select">
        <option value="">— Selecionar produto —</option>
        <?php foreach ($produtos as $p):
          $cap    = (float)($p['capacidade_ml'] ?? 0);
          $mlDose = (float)($p['ml_por_dose']   ?? 0);
          $rend   = (float)($p['rendimento_pct'] ?: 100);
          $dosesPorUn = ($cap > 0 && $mlDose > 0)
            ? (int) floor($cap * ($rend / 100) / $mlDose) : 0;
        ?>
        <?php
        $needsConfig = $dosesPorUn === 0
          && in_array($p['tipo'], ['chopp_barril','dose','garrafa'])
          && ($cap === 0.0 || $mlDose === 0.0);
        ?>
        <option
          value="<?= $p['id'] ?>"
          data-nome="<?= h($p['nome']) ?>"
          data-tipo="<?= h($p['tipo']) ?>"
          data-custo="<?= number_format($p['preco_custo'], 2, '.', '') ?>"
          data-un="<?= h($p['unidade_estoque'] ?? 'un') ?>"
          data-est="<?= (int)$p['estoque_atual'] ?>"
          data-cap="<?= $cap ?>"
          data-mldose="<?= $mlDose ?>"
          data-rend="<?= $rend ?>"
          data-doses-por-un="<?= $dosesPorUn ?>"
          data-needs-config="<?= $needsConfig ? '1' : '0' ?>"
          data-edit-url="<?= BASE_URL ?>modules/produtos/form.php?id=<?= $p['id'] ?>"
          <?= $produto_presel == $p['id'] ? 'selected' : '' ?>
        >
          <?= h($p['nome']) ?> — <?= h($p['cat_nome'] ?? '') ?>
          <?php if ($dosesPorUn > 0): ?>
            (<?= $dosesPorUn ?> <?= h($p['unidade_estoque']??'dose') ?>/un)
          <?php elseif ($needsConfig): ?>
            ⚠️ sem capacidade configurada
          <?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
      <!-- Info do produto selecionado -->
      <div id="infoProduto" style="display:none;margin-top:6px;font-size:.78rem;color:var(--text-muted)">
        Estoque atual: <strong id="infoEst">0</strong> <span id="infoUn"></span>
      </div>
    </div>

    <!-- Quantidade -->
    <div class="col-md-2">
      <label class="form-label fw-semibold" id="labelQtd">Quantidade</label>
      <input type="number" id="addQty" class="form-control form-control-lg"
             placeholder="1" step="1" min="1" value="1"
             style="font-size:1.3rem;font-weight:700;text-align:center">
      <!-- Dica de conversão (aparece quando produto tem doses) -->
      <div id="hintConversao" style="display:none;margin-top:5px"></div>
    </div>

    <!-- Custo -->
    <div class="col-md-2">
      <label class="form-label fw-semibold">Custo Unit. (R$)</label>
      <input type="number" id="addCusto" class="form-control"
             placeholder="0,00" step="0.01" min="0">
    </div>

    <!-- Motivo -->
    <div class="col-md-3">
      <label class="form-label fw-semibold">Motivo</label>
      <input type="text" id="addMotivo" class="form-control"
             value="Compra fornecedor" placeholder="Ex: Compra fornecedor">
    </div>

    <!-- Botão adicionar -->
    <div class="col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-amber w-100 fw-bold" onclick="addItem()">
        <i class="ph-bold ph-plus"></i>
      </button>
    </div>
  </div>
</div>

<!-- Tabela de itens -->
<div class="admin-card">
  <div class="card-section-title">Itens a Lançar</div>
  <div style="overflow-x:auto">
    <table class="admin-table" id="tabelaItens">
      <thead>
        <tr>
          <th>#</th>
          <th>Produto</th>
          <th>Entrada</th>
          <th>Resultado no Estoque</th>
          <th>Custo Unit.</th>
          <th>Total Custo</th>
          <th>Motivo</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="tbodyItens">
        <tr id="rowVazio">
          <td colspan="8" class="text-center py-4" style="color:var(--text-muted)">
            Selecione um produto e clique em + para adicionar
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div style="color:var(--amber);font-family:'Syne',sans-serif;font-size:1rem">
      Total Custo: <strong id="totalEntrada">R$ 0,00</strong>
    </div>
    <button type="submit" class="btn btn-amber btn-lg fw-bold px-5" id="btnSalvar" disabled>
      <i class="ph-bold ph-check me-2"></i>Registrar Entrada no Estoque
    </button>
  </div>
</div>
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var icount = 0;
var totalGlobal = 0;

function fmtMoeda(v) {
  return 'R$ ' + parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
}

/* ── Ao selecionar produto ──────────────────────── */
function onProdutoChange() {
  var sel  = document.getElementById('selProduto');
  var opt  = sel.options[sel.selectedIndex];
  var pid  = parseInt(opt.value || '0');

  var infoDiv  = document.getElementById('infoProduto');
  var hintDiv  = document.getElementById('hintConversao');
  var labelQtd = document.getElementById('labelQtd');

  if (!pid) {
    if (infoDiv)  infoDiv.style.display  = 'none';
    if (hintDiv)  hintDiv.style.display  = 'none';
    if (labelQtd) labelQtd.textContent   = 'Quantidade';
    return;
  }

  // Custo atual
  var custoEl = document.getElementById('addCusto');
  if (custoEl) custoEl.value = opt.dataset.custo || '';

  // Estoque atual
  var estAt = parseInt(opt.dataset.est || '0');
  var un    = opt.dataset.un || 'un';
  if (document.getElementById('infoEst')) document.getElementById('infoEst').textContent = estAt;
  if (document.getElementById('infoUn'))  document.getElementById('infoUn').textContent  = un;
  if (infoDiv) infoDiv.style.display = '';

  // Conversão unidade → doses
  var dosesPorUn   = parseInt(opt.dataset.dosesPorUn || '0');
  var needsConfig  = opt.dataset.needsConfig === '1';
  var editUrl      = opt.dataset.editUrl || '';

  if (dosesPorUn > 0) {
    // ✅ Produto com conversão configurada
    if (labelQtd) labelQtd.textContent = 'Quantas unidades físicas? (barris/garrafas)';
    if (hintDiv) {
      hintDiv.style.display = '';
      hintDiv.innerHTML =
        '<div class="conversao-badge">' +
        '<i class="ph-bold ph-arrows-left-right me-1"></i>' +
        '1 unidade = ' + dosesPorUn + ' ' + un + ' no estoque' +
        '</div>';
    }
  } else if (needsConfig) {
    // ⚠️ Produto que deveria ter conversão mas não está configurado
    if (labelQtd) labelQtd.textContent = 'Quantidade:';
    if (hintDiv) {
      hintDiv.style.display = '';
      hintDiv.innerHTML =
        '<div style="background:rgba(239,68,68,.1);border:1px solid #ef4444;border-radius:6px;padding:6px 10px;font-size:.78rem;color:#ef4444">' +
        '⚠️ Capacidade e dose não configuradas. <a href="' + editUrl + '" target="_blank" style="color:#f59e0b;font-weight:700">Configurar agora →</a>' +
        '<br><span style="color:var(--text-muted)">Sem configuração, 1 unidade = 1 ' + un + ' (sem conversão)</span>' +
        '</div>';
    }
  } else {
    // Produto simples sem conversão (lata, unidade)
    if (labelQtd) labelQtd.textContent = 'Quantidade:';
    if (hintDiv)  hintDiv.style.display = 'none';
  }
}

document.getElementById('selProduto').addEventListener('change', onProdutoChange);

// Disparar para produto pré-selecionado via URL
<?php if ($produto_presel): ?>
onProdutoChange();
<?php endif; ?>

/* ── Adicionar item à lista ─────────────────────── */
function addItem() {
  var sel = document.getElementById('selProduto');
  var opt = sel.options[sel.selectedIndex];
  var pid = parseInt(sel.value || '0');

  if (!pid) {
    Swal.fire({ icon: 'warning', title: 'Selecione um produto.', background: '#1e2330', color: '#f0f2f7' });
    return;
  }

  var qtdEl = document.getElementById('addQty');
  var qty   = parseInt(qtdEl.value || '0');
  if (qty <= 0) {
    Swal.fire({ icon: 'warning', title: 'Informe a quantidade.', background: '#1e2330', color: '#f0f2f7' });
    return;
  }

  var custo       = parseFloat(document.getElementById('addCusto').value || '0');
  var motivo      = document.getElementById('addMotivo').value || 'Entrada';
  var nome        = opt.dataset.nome || '';
  var un          = opt.dataset.un   || 'un';
  var estAt       = parseInt(opt.dataset.est || '0');
  var dosesPorUn  = parseInt(opt.dataset.dosesPorUn || '0');
  var totalCusto  = qty * custo;

  // Calcular quantas unidades de estoque serão adicionadas
  var qtdEstoque  = (dosesPorUn > 0) ? qty * dosesPorUn : qty;
  var novoEst     = estAt + qtdEstoque;

  icount++;
  totalGlobal += totalCusto;

  document.getElementById('rowVazio').style.display = 'none';

  var tr = document.createElement('tr');
  tr.id  = 'irow' + icount;

  // Descrição da entrada
  var entradaDesc;
  if (dosesPorUn > 0) {
    entradaDesc = qty + ' un × ' + dosesPorUn + ' = <strong style="color:var(--amber)">' + qtdEstoque + ' ' + un + '</strong>';
  } else {
    entradaDesc = '<strong>' + qty + ' ' + un + '</strong>';
  }

  tr.innerHTML =
    '<td>' + icount + '</td>' +
    '<td>' +
      '<strong>' + nome + '</strong>' +
      '<br><small style="color:var(--text-muted)">Estoque atual: ' + estAt + ' ' + un + '</small>' +
      '<input type="hidden" name="itens[' + icount + '][produto_id]" value="' + pid + '">' +
      '<input type="hidden" name="itens[' + icount + '][quantidade]" value="' + qty + '">' +
      '<input type="hidden" name="itens[' + icount + '][custo_unitario]" value="' + custo.toFixed(2) + '">' +
      '<input type="hidden" name="itens[' + icount + '][motivo]" value="' + motivo + '">' +
    '</td>' +
    '<td>' + entradaDesc + '</td>' +
    '<td style="color:var(--success);font-weight:700">' + novoEst + ' ' + un + '</td>' +
    '<td>' + fmtMoeda(custo) + '/un</td>' +
    '<td style="color:var(--amber)">' + fmtMoeda(totalCusto) + '</td>' +
    '<td style="color:var(--text-muted);font-size:.78rem">' + motivo + '</td>' +
    '<td>' +
      '<button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="rmItem(' + icount + ',' + totalCusto + ')">' +
        '<i class="ph-bold ph-x"></i>' +
      '</button>' +
    '</td>';

  document.getElementById('tbodyItens').appendChild(tr);
  document.getElementById('totalEntrada').textContent = fmtMoeda(totalGlobal);
  document.getElementById('btnSalvar').disabled = false;

  // Limpar campos
  sel.value = '';
  qtdEl.value = 1;
  document.getElementById('addCusto').value = '';
  document.getElementById('infoProduto').style.display = 'none';
  document.getElementById('hintConversao').style.display = 'none';
  document.getElementById('labelQtd').textContent = 'Quantidade';
  onProdutoChange(); // reset hint
}

/* ── Remover item ───────────────────────────────── */
function rmItem(i, val) {
  var el = document.getElementById('irow' + i);
  if (el) el.remove();
  totalGlobal -= val;
  document.getElementById('totalEntrada').textContent = fmtMoeda(totalGlobal);
  var rows = document.querySelectorAll('#tbodyItens tr:not(#rowVazio)');
  if (rows.length === 0) {
    document.getElementById('rowVazio').style.display = '';
    document.getElementById('btnSalvar').disabled = true;
  }
}
</script>
</body>
</html>
