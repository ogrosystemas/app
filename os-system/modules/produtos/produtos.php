<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

$baseUrl  = defined('BASE_URL') ? BASE_URL : '';
$mensagem = $_SESSION['mensagem'] ?? null;
$erro     = $_SESSION['erro']     ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);

// ── Salvar categoria ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_categoria'])) {
    csrfVerify();
    $cat_id   = (int)($_POST['cat_id'] ?? 0);
    $cat_nome = trim($_POST['cat_nome'] ?? '');
    $cat_cor  = trim($_POST['cat_cor']  ?? '#94a3b8');
    if (!$cat_nome) { $_SESSION['erro'] = 'Nome da categoria é obrigatório.'; }
    else {
        try {
            if ($cat_id) {
                $db->prepare("UPDATE categorias_produtos SET nome=?, cor=? WHERE id=?")
                   ->execute([$cat_nome, $cat_cor, $cat_id]);
                $_SESSION['mensagem'] = 'Categoria atualizada!';
            } else {
                $db->prepare("INSERT INTO categorias_produtos (nome, cor) VALUES (?,?)")
                   ->execute([$cat_nome, $cat_cor]);
                $_SESSION['mensagem'] = 'Categoria criada!';
            }
        } catch (PDOException $e) { $_SESSION['erro'] = $e->getMessage(); }
    }
    header('Location: produtos.php?aba=categorias'); exit;
}

// ── Excluir categoria ─────────────────────────────────────────
if (isset($_GET['excluir_cat'])) {
    $cid = (int)$_GET['excluir_cat'];
    $uso = (int)$db->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id=?")->execute([$cid]) ? $db->query("SELECT COUNT(*) FROM produtos WHERE categoria_id=$cid")->fetchColumn() : 0;
    try {
        $uso = (int)$db->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id=?")->execute([$cid]);
        $stmt = $db->prepare("SELECT COUNT(*) FROM produtos WHERE categoria_id=?");
        $stmt->execute([$cid]);
        $uso = (int)$stmt->fetchColumn();
        if ($uso > 0) {
            $_SESSION['erro'] = "Não é possível excluir: categoria possui $uso produto(s) vinculado(s).";
        } else {
            $db->prepare("UPDATE categorias_produtos SET ativo=0 WHERE id=?")->execute([$cid]);
            $_SESSION['mensagem'] = 'Categoria desativada!';
        }
    } catch (PDOException $e) { $_SESSION['erro'] = $e->getMessage(); }
    header('Location: produtos.php?aba=categorias'); exit;
}

// ── Excluir produto ──────────────────────────────────────────
if (isset($_GET['excluir'])) {
    try {
        $db->prepare("UPDATE produtos SET ativo=0 WHERE id=?")->execute([(int)$_GET['excluir']]);
        $_SESSION['mensagem'] = 'Produto desativado!';
    } catch (PDOException $e) { $_SESSION['erro'] = $e->getMessage(); }
    header('Location: produtos.php'); exit;
}

// ── Salvar produto ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_produto'])) {
    csrfVerify();
    $id = (int)($_POST['id'] ?? 0);
    $toDecimal = fn($v) => (float)str_replace(['.', ','], ['', '.'], $v ?? '0');

    $campos = [
        'codigo_barras'  => trim($_POST['codigo_barras']  ?? '') ?: null,
        'nome'           => trim($_POST['nome']           ?? ''),
        'descricao'      => trim($_POST['descricao']      ?? ''),
        'preco_compra'   => $toDecimal($_POST['preco_compra']  ?? '0'),
        'preco_venda'    => $toDecimal($_POST['preco_venda']   ?? '0'),
        'estoque_minimo' => (int)($_POST['estoque_minimo'] ?? 0),
        'unidade'        => $_POST['unidade'] ?? 'UN',
        'ncm'            => trim($_POST['ncm'] ?? ''),
        'localizacao'    => trim($_POST['localizacao'] ?? ''),
        'categoria_id'   => (int)($_POST['categoria_id'] ?? 0) ?: null,
        'exibir_pdv'     => isset($_POST['exibir_pdv']) ? 1 : 0,
    ];

    if (!$campos['nome']) {
        $_SESSION['erro'] = 'O nome do produto é obrigatório.';
    } else {
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
                $db->prepare("UPDATE produtos SET $set WHERE id = ?")->execute([...array_values($campos), $id]);
                $_SESSION['mensagem'] = 'Produto atualizado!';
            } else {
                $campos['estoque_atual'] = (int)($_POST['estoque_inicial'] ?? 0);
                $campos['ativo']         = 1;
                $cols = implode(', ', array_keys($campos));
                $vals = implode(', ', array_fill(0, count($campos), '?'));
                $db->prepare("INSERT INTO produtos ($cols) VALUES ($vals)")->execute(array_values($campos));
                $new_id = $db->lastInsertId();
                if ($campos['estoque_atual'] > 0) {
                    try {
                        $db->prepare("INSERT INTO movimentacoes_estoque (produto_id,tipo,quantidade,motivo,created_by) VALUES (?,'entrada',?,'Estoque inicial',?)")
                           ->execute([$new_id, $campos['estoque_atual'], $_SESSION['usuario_id']]);
                    } catch (PDOException $ignored) {}
                }
                $_SESSION['mensagem'] = 'Produto cadastrado!';
            }
        } catch (PDOException $e) { $_SESSION['erro'] = 'Erro: ' . $e->getMessage(); }
    }
    header('Location: produtos.php'); exit;
}

// ── Listagem ─────────────────────────────────────────────────
$aba        = $_GET['aba']    ?? 'produtos';
$busca      = trim($_GET['busca']   ?? '');
$filtro     = $_GET['filtro']       ?? '';
$filtro_cat = (int)($_GET['cat']    ?? 0);

$where  = 'WHERE p.ativo = 1';
$params = [];
if ($busca) {
    $where .= ' AND (p.nome LIKE ? OR p.codigo_barras LIKE ?)';
    $params = ["%$busca%", "%$busca%"];
}
if ($filtro === 'critico') $where .= ' AND p.estoque_atual <= p.estoque_minimo';
if ($filtro === 'zerado')  $where .= ' AND p.estoque_atual <= 0';
if ($filtro_cat > 0) { $where .= ' AND p.categoria_id = ?'; $params[] = $filtro_cat; }
if ($filtro_cat === -1) $where .= ' AND p.categoria_id IS NULL';

$stmt = $db->prepare(
    "SELECT p.*, c.nome as cat_nome, c.cor as cat_cor
     FROM produtos p
     LEFT JOIN categorias_produtos c ON c.id = p.categoria_id
     $where ORDER BY p.nome"
);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$alerta_estoque = array_filter($produtos, fn($p) => $p['estoque_atual'] <= $p['estoque_minimo']);

$categorias = $db->query("SELECT * FROM categorias_produtos WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Contagens por categoria para sidebar
$stmt_count = $db->query("SELECT categoria_id, COUNT(*) as n FROM produtos WHERE ativo=1 GROUP BY categoria_id");
$conts = [];
foreach ($stmt_count->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $conts[(int)$r['categoria_id']] = (int)$r['n'];
}
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Produtos</div>
  <div class="topbar-actions">
    <?php if ($aba === 'categorias'): ?>
    <button class="btn-os btn-os-ghost" onclick="document.getElementById('modalCategoria').style.display='flex'">
      <i class="ph-bold ph-tag"></i> Nova Categoria
    </button>
    <a href="produtos.php" class="btn-os btn-os-primary"><i class="ph-bold ph-package"></i> Ver Produtos</a>
    <?php else: ?>
    <a href="produtos.php?aba=categorias" class="btn-os btn-os-ghost">
      <i class="ph-bold ph-tag"></i> Categorias
    </a>
    <button class="btn-os btn-os-primary" onclick="abrirModalProduto()">
      <i class="ph-bold ph-plus-circle"></i> Novo Produto
    </button>
    <?php endif; ?>
  </div>
</header>

<main class="os-content">

<?php if ($mensagem): ?><div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro):     ?><div class="os-alert os-alert-danger"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>

<?php if ($aba === 'categorias'): ?>
<!-- ════════════════════════════════════════════════════════ -->
<!-- ABA CATEGORIAS                                          -->
<!-- ════════════════════════════════════════════════════════ -->
<div class="os-card">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-tag"></i> Categorias de Produtos</div>
    <button class="btn-os btn-os-primary" onclick="document.getElementById('modalCategoria').style.display='flex'">
      <i class="ph-bold ph-plus"></i> Nova Categoria
    </button>
  </div>
  <div class="os-card-body" style="padding:0">
    <table class="os-table">
      <thead><tr><th>Cor</th><th>Nome</th><th style="text-align:center">Produtos</th><th style="text-align:center">Ações</th></tr></thead>
      <tbody>
        <?php if (empty($categorias)): ?>
        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px">
          Nenhuma categoria cadastrada.<br><small>Crie categorias para organizar seus produtos e filtrar no PDV.</small>
        </td></tr>
        <?php endif; ?>
        <?php foreach ($categorias as $cat): $n = $conts[$cat['id']] ?? 0; ?>
        <tr>
          <td>
            <div style="width:28px;height:28px;border-radius:8px;background:<?= htmlspecialchars($cat['cor']??'#94a3b8') ?>;border:2px solid rgba(255,255,255,.15)"></div>
          </td>
          <td>
            <strong><?= htmlspecialchars($cat['nome']) ?></strong>
          </td>
          <td style="text-align:center">
            <a href="produtos.php?cat=<?= $cat['id'] ?>" style="text-decoration:none">
              <span class="os-badge os-badge-blue"><?= $n ?> produto<?= $n!==1?'s':'' ?></span>
            </a>
          </td>
          <td style="text-align:center">
            <div style="display:flex;gap:6px;justify-content:center">
              <button class="btn-os btn-os-ghost" style="padding:5px 8px"
                      onclick='editarCategoria(<?= htmlspecialchars(json_encode($cat),ENT_QUOTES) ?>)'>
                <i class="ph-bold ph-pencil-simple"></i>
              </button>
              <?php if ($n === 0): ?>
              <a href="#"
                 class="btn-os" style="padding:5px 8px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;text-decoration:none"
                 onclick="confirmarDesativarCat(event,<?= $cat['id'] ?>)">
                <i class="ph-bold ph-trash"></i>
              </a>
              <?php else: ?>
              <span style="padding:5px 8px;color:var(--text-dim);font-size:.75rem" title="Remova os produtos primeiro">Em uso</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════ -->
<!-- ABA PRODUTOS                                            -->
<!-- ════════════════════════════════════════════════════════ -->

<?php if (!empty($alerta_estoque)): ?>
<div class="os-alert os-alert-warning">
  <i class="ph-bold ph-warning-circle"></i>
  <strong><?= count($alerta_estoque) ?> produto(s)</strong> com estoque abaixo do mínimo.
  <a href="?filtro=critico" style="color:var(--accent);font-weight:600;margin-left:4px">Ver →</a>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-body" style="padding:12px 18px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <div style="position:relative;flex:2;min-width:200px">
        <i class="ph-bold ph-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
        <input type="text" name="busca" class="os-input" style="padding-left:38px"
               placeholder="Nome ou código de barras..." value="<?= htmlspecialchars($busca) ?>">
      </div>
      <select name="cat" class="os-select" style="flex:1;min-width:160px">
        <option value="0" <?= !$filtro_cat?'selected':''?>>Todas as categorias</option>
        <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['id'] ?>" <?= $filtro_cat==$cat['id']?'selected':''?>>
          <?= htmlspecialchars($cat['nome']) ?> (<?= $conts[$cat['id']]??0 ?>)
        </option>
        <?php endforeach; ?>
        <option value="-1" <?= $filtro_cat===-1?'selected':''?>>Sem categoria</option>
      </select>
      <select name="filtro" class="os-select" style="width:160px">
        <option value="">Todo estoque</option>
        <option value="critico" <?= $filtro==='critico'?'selected':''?>>Crítico</option>
        <option value="zerado"  <?= $filtro==='zerado' ?'selected':''?>>Zerado</option>
      </select>
      <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-funnel"></i> Filtrar</button>
      <?php if ($busca || $filtro || $filtro_cat): ?>
      <a href="produtos.php" class="btn-os btn-os-ghost">Limpar</a>
      <?php endif; ?>
    </form>

    <!-- Chips de categoria rápida -->
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;align-items:center">
      <span style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em">Filtro rápido:</span>
      <a href="produtos.php" style="text-decoration:none">
        <span style="background:<?= !$filtro_cat?'rgba(245,158,11,.2)':'var(--bg-card2)'?>;color:<?= !$filtro_cat?'var(--accent)':'var(--text-muted)'?>;border:1px solid <?= !$filtro_cat?'rgba(245,158,11,.4)':'var(--border)'?>;border-radius:20px;padding:2px 10px;font-size:.75rem;font-weight:600">
          Todos
        </span>
      </a>
      <?php foreach ($categorias as $cat): $n=$conts[$cat['id']]??0; if(!$n) continue; ?>
      <a href="produtos.php?cat=<?= $cat['id'] ?>" style="text-decoration:none">
        <span style="background:<?= $filtro_cat==$cat['id']?'rgba(245,158,11,.2)':'var(--bg-card2)'?>;color:<?= $filtro_cat==$cat['id']?'var(--accent)':'var(--text-muted)'?>;border:1px solid <?= $filtro_cat==$cat['id']?'rgba(245,158,11,.4)':'var(--border)'?>;border-radius:20px;padding:2px 10px;font-size:.75rem;font-weight:600;display:inline-flex;align-items:center;gap:5px">
          <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($cat['cor']??'#94a3b8') ?>"></span>
          <?= htmlspecialchars($cat['nome']) ?>
          <span style="opacity:.65"><?= $n ?></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Tabela -->
<div class="os-card">
  <div class="os-card-header" style="justify-content:space-between">
    <div class="os-card-title"><i class="ph-bold ph-package"></i> <?= count($produtos) ?> produto(s)</div>
    <?php if ($filtro_cat): ?>
    <span style="font-size:.8rem;color:var(--accent)">
      Filtrado por: <?= htmlspecialchars(array_column($categorias,'nome','id')[$filtro_cat] ?? 'Sem categoria') ?>
    </span>
    <?php endif; ?>
  </div>
  <div class="os-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="os-table">
        <thead>
          <tr>
            <th>Cód. Barras</th>
            <th>Produto</th>
            <th>Categoria</th>
            <th style="text-align:center">PDV</th>
            <th style="text-align:right">Custo</th>
            <th style="text-align:right">Venda</th>
            <th style="text-align:center">Estoque</th>
            <th style="text-align:center">Mín.</th>
            <th style="text-align:center">Status</th>
            <th style="text-align:center">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($produtos)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:40px">Nenhum produto encontrado.</td></tr>
          <?php endif; ?>
          <?php foreach ($produtos as $p):
            $zerado  = $p['estoque_atual'] <= 0;
            $critico = !$zerado && $p['estoque_atual'] <= $p['estoque_minimo'];
          ?>
          <tr style="<?= $zerado?'background:rgba(239,68,68,.04)':($critico?'background:rgba(245,158,11,.04)':'') ?>">
            <td style="font-family:monospace;font-size:.76rem;color:var(--text-muted)">
              <?= htmlspecialchars($p['codigo_barras'] ?? '—') ?>
            </td>
            <td>
              <strong><?= htmlspecialchars($p['nome']) ?></strong>
              <?php if (!empty($p['descricao'])): ?>
              <div style="font-size:.73rem;color:var(--text-muted)"><?= htmlspecialchars(mb_strimwidth($p['descricao'],0,48,'...')) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($p['cat_nome']): ?>
              <span style="display:inline-flex;align-items:center;gap:5px;background:var(--bg-card2);border:1px solid var(--border);border-radius:20px;padding:2px 9px;font-size:.75rem;font-weight:600">
                <span style="width:7px;height:7px;border-radius:50%;background:<?= htmlspecialchars($p['cat_cor']??'#94a3b8') ?>;flex-shrink:0"></span>
                <?= htmlspecialchars($p['cat_nome']) ?>
              </span>
              <?php else: ?>
              <span style="color:var(--text-dim);font-size:.78rem">—</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <?php if ($p['exibir_pdv']): ?>
              <span style="color:#22c55e;font-size:.85rem"><i class="ph-bold ph-check-circle"></i></span>
              <?php else: ?>
              <span style="color:var(--text-dim);font-size:.85rem"><i class="ph-bold ph-minus-circle"></i></span>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-size:.84rem;color:var(--text-muted)">R$ <?= number_format($p['preco_compra']??0,2,',','.') ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($p['preco_venda'],2,',','.') ?></td>
            <td style="text-align:center;font-weight:700;color:<?= $zerado?'#ef4444':($critico?'#f59e0b':'#22c55e') ?>">
              <?= number_format($p['estoque_atual'],0) ?>
              <span style="font-weight:400;font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($p['unidade']??'') ?></span>
            </td>
            <td style="text-align:center;font-size:.82rem;color:var(--text-muted)"><?= number_format($p['estoque_minimo'],0) ?></td>
            <td style="text-align:center">
              <?php if ($zerado): ?>
              <span class="os-badge os-badge-red">Zerado</span>
              <?php elseif ($critico): ?>
              <span class="os-badge os-badge-yellow">Crítico</span>
              <?php else: ?>
              <span class="os-badge os-badge-green">OK</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center">
              <div style="display:flex;gap:4px;justify-content:center">
                <button class="btn-os btn-os-ghost" style="padding:5px 8px" title="Editar"
                        onclick='editarProduto(<?= htmlspecialchars(json_encode($p),ENT_QUOTES) ?>)'>
                  <i class="ph-bold ph-pencil-simple"></i>
                </button>
                <a href="<?= $baseUrl ?>/modules/estoque/estoque.php?produto_id=<?= $p['id'] ?>"
                   class="btn-os btn-os-ghost" style="padding:5px 8px" title="Estoque">
                  <i class="ph-bold ph-stack"></i>
                </a>
                <button style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;padding:5px 8px;border-radius:7px;cursor:pointer" title="Desativar"
                        onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nome'])) ?>')">
                  <i class="ph-bold ph-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php endif; // fim aba produtos ?>
</main>

<!-- ═══ MODAL PRODUTO ════════════════════════════════════════ -->
<div id="modalProduto" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:3000;align-items:center;justify-content:center;overflow-y:auto;padding:20px">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:700px;max-width:100%;margin:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalProdutoTitulo">Novo Produto</h5>
      <button onclick="fecharModalProduto()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST" id="formProduto">
      <input type="hidden" name="salvar_produto" value="1">
      <input type="hidden" name="id" id="pid">
      <?= csrfField() ?>
      <div style="padding:20px 24px">

        <!-- Nome + Unidade -->
        <div style="display:grid;grid-template-columns:1fr 1fr 110px;gap:14px;margin-bottom:14px">
          <div class="os-form-group" style="grid-column:span 2">
            <label class="os-label">Nome do Produto *</label>
            <input type="text" name="nome" id="pNome" class="os-input" required>
          </div>
          <div class="os-form-group">
            <label class="os-label">Unidade</label>
            <select name="unidade" id="pUnidade" class="os-select">
              <?php foreach (['UN'=>'Unidade','PC'=>'Peça','LT'=>'Litro','KG'=>'Kg','MT'=>'Metro','CX'=>'Caixa','PR'=>'Par'] as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Categoria + PDV -->
        <div style="display:grid;grid-template-columns:1fr auto;gap:14px;margin-bottom:14px;align-items:end">
          <div class="os-form-group">
            <label class="os-label">Categoria</label>
            <select name="categoria_id" id="pCategoria" class="os-select">
              <option value="">Sem categoria</option>
              <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>" data-cor="<?= htmlspecialchars($cat['cor']??'') ?>">
                <?= htmlspecialchars($cat['nome']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="os-form-group">
            <label class="os-label" style="white-space:nowrap">Exibir no PDV</label>
            <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg-input);border:1px solid var(--border);border-radius:8px">
              <input type="checkbox" name="exibir_pdv" id="pExibirPdv" value="1" style="width:18px;height:18px;accent-color:var(--accent);cursor:pointer">
              <label for="pExibirPdv" style="font-size:.82rem;color:var(--text);cursor:pointer;margin:0">Sim</label>
            </div>
          </div>
        </div>

        <!-- Código de barras + NCM -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
          <div class="os-form-group">
            <label class="os-label">Código de Barras</label>
            <div style="position:relative">
              <i class="ph-bold ph-barcode" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
              <input type="text" name="codigo_barras" id="pCodBarras" class="os-input" style="padding-left:38px;font-family:monospace" placeholder="EAN-8, EAN-13, QR...">
            </div>
          </div>
          <div class="os-form-group">
            <label class="os-label">NCM</label>
            <input type="text" name="ncm" id="pNcm" class="os-input" placeholder="0000.00.00">
          </div>
        </div>

        <!-- Descrição -->
        <div class="os-form-group" style="margin-bottom:14px">
          <label class="os-label">Descrição</label>
          <textarea name="descricao" id="pDesc" class="os-input" rows="2"></textarea>
        </div>

        <!-- Preços -->
        <div style="background:var(--bg-card2);border-radius:10px;padding:14px;border:1px solid var(--border);margin-bottom:14px">
          <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">
            <i class="ph-bold ph-currency-dollar"></i> Preços
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 110px;gap:12px">
            <div class="os-form-group">
              <label class="os-label">Custo (R$)</label>
              <input type="number" name="preco_compra" id="pCusto" class="os-input" step="0.01" min="0" value="0.00" oninput="calcMargem()">
            </div>
            <div class="os-form-group">
              <label class="os-label">Venda (R$)</label>
              <input type="number" name="preco_venda" id="pVenda" class="os-input" step="0.01" min="0" value="0.00" oninput="calcMargem()">
            </div>
            <div class="os-form-group">
              <label class="os-label">Margem</label>
              <div id="margemBox" style="background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:9px 12px;font-weight:700;text-align:center;font-family:'Syne',sans-serif;font-size:.95rem">—</div>
            </div>
          </div>
        </div>

        <!-- Estoque -->
        <div style="background:var(--bg-card2);border-radius:10px;padding:14px;border:1px solid var(--border)">
          <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">
            <i class="ph-bold ph-stack"></i> Estoque
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
            <div class="os-form-group">
              <label class="os-label" id="lblEstoque">Estoque Inicial</label>
              <input type="number" name="estoque_inicial" id="pEstoqueInicial" class="os-input" min="0" value="0">
              <div id="estoqueAtualDisplay" style="display:none;font-size:.73rem;color:var(--accent);margin-top:4px">
                <i class="ph-bold ph-info"></i> Use o módulo Estoque para ajustes.
              </div>
            </div>
            <div class="os-form-group">
              <label class="os-label">Estoque Mínimo</label>
              <input type="number" name="estoque_minimo" id="pEstMin" class="os-input" min="0" value="5">
            </div>
            <div class="os-form-group">
              <label class="os-label">Localização</label>
              <input type="text" name="localizacao" id="pLocal" class="os-input" placeholder="A-12, B-3...">
            </div>
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border)">
        <button type="button" class="btn-os btn-os-ghost" onclick="fecharModalProduto()">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-floppy-disk"></i> Salvar Produto</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL CATEGORIA ══════════════════════════════════════ -->
<div id="modalCategoria" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:420px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalCatTitulo">Nova Categoria</h5>
      <button onclick="document.getElementById('modalCategoria').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST" action="produtos.php?aba=categorias">
      <input type="hidden" name="salvar_categoria" value="1">
      <input type="hidden" name="cat_id" id="catId" value="0">
      <?= csrfField() ?>
      <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
        <div class="os-form-group">
          <label class="os-label">Nome da Categoria *</label>
          <input type="text" name="cat_nome" id="catNome" class="os-input" required placeholder="Ex: Óleo e Lubrificantes">
        </div>
        <div class="os-form-group">
          <label class="os-label">Cor de Identificação</label>
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <input type="color" name="cat_cor" id="catCor" value="#f59e0b"
                   style="width:48px;height:48px;border:2px solid var(--border);border-radius:8px;cursor:pointer;padding:2px;background:none">
            <!-- Paleta rápida -->
            <?php foreach (['#f59e0b','#22c55e','#ef4444','#3b82f6','#a855f7','#ec4899','#06b6d4','#f97316','#84cc16','#64748b'] as $cor): ?>
            <div onclick="document.getElementById('catCor').value='<?= $cor ?>'"
                 style="width:26px;height:26px;background:<?= $cor ?>;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:transform .1s"
                 onmouseenter="this.style.transform='scale(1.2)'" onmouseleave="this.style.transform=''">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border)">
        <button type="button" class="btn-os btn-os-ghost" onclick="document.getElementById('modalCategoria').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-floppy-disk"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Modal Produto ────────────────────────────────────────────
function abrirModalProduto() {
    document.getElementById('modalProdutoTitulo').textContent = 'Novo Produto';
    ['pid','pNome','pCodBarras','pNcm','pDesc','pLocal'].forEach(function(id) {
        var el = document.getElementById(id); if(el) el.value = '';
    });
    document.getElementById('pUnidade').value    = 'UN';
    document.getElementById('pCategoria').value  = '';
    document.getElementById('pExibirPdv').checked = true;
    document.getElementById('pCusto').value      = '0.00';
    document.getElementById('pVenda').value      = '0.00';
    document.getElementById('pEstMin').value     = '5';
    document.getElementById('pEstoqueInicial').value   = '0';
    document.getElementById('pEstoqueInicial').disabled = false;
    document.getElementById('pEstoqueInicial').name    = 'estoque_inicial';
    document.getElementById('lblEstoque').textContent  = 'Estoque Inicial';
    document.getElementById('estoqueAtualDisplay').style.display = 'none';
    calcMargem();
    document.getElementById('modalProduto').style.display = 'flex';
}

function fecharModalProduto() {
    document.getElementById('modalProduto').style.display = 'none';
}

function editarProduto(p) {
    document.getElementById('modalProdutoTitulo').textContent = 'Editar Produto';
    document.getElementById('pid').value          = p.id;
    document.getElementById('pNome').value        = p.nome;
    document.getElementById('pCodBarras').value   = p.codigo_barras || '';
    document.getElementById('pNcm').value         = p.ncm || '';
    document.getElementById('pDesc').value        = p.descricao || '';
    document.getElementById('pUnidade').value     = p.unidade || 'UN';
    document.getElementById('pCategoria').value   = p.categoria_id || '';
    document.getElementById('pExibirPdv').checked = p.exibir_pdv == 1;
    document.getElementById('pCusto').value       = parseFloat(p.preco_compra||0).toFixed(2);
    document.getElementById('pVenda').value       = parseFloat(p.preco_venda||0).toFixed(2);
    document.getElementById('pEstMin').value      = p.estoque_minimo || 0;
    document.getElementById('pLocal').value       = p.localizacao || '';
    // Estoque atual — somente leitura ao editar
    var estoqueEl = document.getElementById('pEstoqueInicial');
    estoqueEl.value    = p.estoque_atual;
    estoqueEl.disabled = true;
    estoqueEl.name     = '';
    document.getElementById('lblEstoque').textContent = 'Estoque Atual';
    document.getElementById('estoqueAtualDisplay').style.display = 'block';
    calcMargem();
    document.getElementById('modalProduto').style.display = 'flex';
}

function calcMargem() {
    var custo  = parseFloat(document.getElementById('pCusto').value) || 0;
    var venda  = parseFloat(document.getElementById('pVenda').value) || 0;
    var box    = document.getElementById('margemBox');
    if (!custo) { box.textContent = '—'; box.style.color = 'var(--text-muted)'; return; }
    var margem = (venda - custo) / custo * 100;
    box.textContent = margem.toFixed(1) + '%';
    box.style.color = margem < 0 ? '#ef4444' : margem < 10 ? '#f59e0b' : '#22c55e';
}

function confirmarExclusao(id, nome) {
    Swal.fire({
        title: 'Desativar produto?',
        text: '"' + nome + '" será desativado e ocultado do sistema.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, desativar',
        cancelButtonText: 'Cancelar',
        background: 'var(--bg-card)',
        color: 'var(--text)',
    }).then(function(r) { if(r.isConfirmed) location.href = 'produtos.php?excluir=' + id; });
}

// ── Modal Categoria ─────────────────────────────────────────
function editarCategoria(c) {
    document.getElementById('modalCatTitulo').textContent = 'Editar Categoria';
    document.getElementById('catId').value   = c.id;
    document.getElementById('catNome').value = c.nome;
    document.getElementById('catCor').value  = c.cor || '#94a3b8';
    document.getElementById('modalCategoria').style.display = 'flex';
}

function confirmarDesativarCat(e, id) {
    e.preventDefault();
    Swal.fire({
        title: 'Desativar categoria?',
        text: 'A categoria será removida da listagem.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, desativar',
        cancelButtonText: 'Cancelar',
        background: 'var(--bg-card, #1c2333)',
        color: 'var(--text, #f0f2f7)',
    }).then(function(r) {
        if (r.isConfirmed) window.location.href = 'produtos.php?excluir_cat=' + id + '&aba=categorias';
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
