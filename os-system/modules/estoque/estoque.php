<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

$mensagem = $_SESSION['mensagem'] ?? null;
$erro     = $_SESSION['erro']     ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Entrada de estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrada_estoque'])) {
    csrfVerify();
    $produto_id = (int)$_POST['produto_id'];
    $quantidade = (float)($_POST['quantidade'] ?? 0);
    $motivo     = trim($_POST['motivo'] ?? 'Entrada manual');
    $documento  = trim($_POST['documento'] ?? '');

    if (!$produto_id || $quantidade <= 0) {
        $_SESSION['erro'] = 'Produto e quantidade são obrigatórios.';
    } else {
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ?")->execute([$quantidade, $produto_id]);
            $db->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, documento, created_by) VALUES (?, 'entrada', ?, ?, ?, ?)")
               ->execute([$produto_id, $quantidade, $motivo, $documento, $_SESSION['usuario_id']]);
            $db->commit();
            $_SESSION['mensagem'] = 'Entrada registrada com sucesso!';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['erro'] = 'Erro: ' . $e->getMessage();
        }
    }
    header('Location: estoque.php'); exit;
}

// Ajuste de estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajuste_estoque'])) {
    csrfVerify();
    $produto_id    = (int)$_POST['produto_id'];
    $nova_qtd      = (float)($_POST['nova_quantidade'] ?? 0);
    $motivo        = trim($_POST['motivo_ajuste'] ?? 'Ajuste de inventário');

    if (!$produto_id) { $_SESSION['erro'] = 'Produto não informado.'; }
    else {
        $db->beginTransaction();
        try {
            $prod = $db->prepare("SELECT estoque_atual FROM produtos WHERE id=?");
            $prod->execute([$produto_id]);
            $atual = (float)$prod->fetchColumn();
            $diff  = $nova_qtd - $atual;
            $tipo  = $diff >= 0 ? 'entrada' : 'saida';

            $db->prepare("UPDATE produtos SET estoque_atual=? WHERE id=?")->execute([$nova_qtd, $produto_id]);
            $db->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, created_by) VALUES (?,?,?,?,?)")
               ->execute([$produto_id, $tipo, abs($diff), "Ajuste: $motivo", $_SESSION['usuario_id']]);
            $db->commit();
            $_SESSION['mensagem'] = 'Estoque ajustado!';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['erro'] = $e->getMessage();
        }
    }
    header('Location: estoque.php'); exit;
}

// Dados
$produto_id_filtro = (int)($_GET['produto_id'] ?? 0);
$tipo_filtro       = $_GET['tipo'] ?? '';
$busca             = trim($_GET['busca'] ?? '');

$produtos = $db->query("SELECT id, nome, estoque_atual, estoque_minimo, unidade FROM produtos WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$alertas  = array_filter($produtos, fn($p) => $p['estoque_atual'] <= $p['estoque_minimo']);

$whereMovs = 'WHERE 1=1'; $paramsMovs = [];
if ($produto_id_filtro) { $whereMovs .= ' AND m.produto_id=?'; $paramsMovs[] = $produto_id_filtro; }
if ($tipo_filtro)       { $whereMovs .= ' AND m.tipo=?';       $paramsMovs[] = $tipo_filtro; }
if ($busca)             { $whereMovs .= ' AND p.nome LIKE ?';  $paramsMovs[] = "%$busca%"; }

$movimentacoes = $db->prepare(
    "SELECT m.*, p.nome as produto_nome, p.unidade, u.nome as usuario_nome
     FROM movimentacoes_estoque m
     JOIN produtos p ON m.produto_id = p.id
     LEFT JOIN usuarios u ON m.created_by = u.id
     $whereMovs ORDER BY m.created_at DESC LIMIT 100"
);
$movimentacoes->execute($paramsMovs);
$movimentacoes = $movimentacoes->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Controle de Estoque</div>
  <div class="topbar-actions">
    <button class="btn-os btn-os-primary" onclick="document.getElementById('modalEntrada').style.display='flex'">
      <i class="ph-bold ph-arrow-fat-down"></i> Entrada
    </button>
    <button class="btn-os btn-os-ghost" onclick="document.getElementById('modalAjuste').style.display='flex'">
      <i class="ph-bold ph-sliders"></i> Ajuste
    </button>
  </div>
</header>

<main class="os-content">
<?php if ($mensagem): ?><div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro):     ?><div class="os-alert os-alert-danger"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>

<?php if (!empty($alertas)): ?>
<div class="os-alert os-alert-warning" style="flex-wrap:wrap;gap:6px">
  <i class="ph-bold ph-warning-circle" style="flex-shrink:0"></i>
  <strong><?= count($alertas) ?> produto(s) abaixo do estoque mínimo:</strong>
  <?php foreach ($alertas as $a): ?>
  <span style="background:rgba(245,158,11,.2);padding:2px 8px;border-radius:6px;font-size:.78rem;white-space:nowrap">
    <?= htmlspecialchars($a['nome']) ?>: <?= number_format($a['estoque_atual'],0) ?>/<?= number_format($a['estoque_minimo'],0) ?>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filtros movimentações -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-body" style="padding:12px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <div style="position:relative;flex:1;min-width:180px">
        <i class="ph-bold ph-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
        <input type="text" name="busca" class="os-input" style="padding-left:38px" placeholder="Produto..." value="<?= htmlspecialchars($busca) ?>">
      </div>
      <select name="tipo" class="os-select" style="width:150px">
        <option value="">Todos os tipos</option>
        <option value="entrada" <?= $tipo_filtro==='entrada'?'selected':''?>>Entradas</option>
        <option value="saida"   <?= $tipo_filtro==='saida'  ?'selected':''?>>Saídas</option>
      </select>
      <button type="submit" class="btn-os btn-os-primary">Filtrar</button>
      <?php if ($busca||$tipo_filtro): ?><a href="estoque.php" class="btn-os btn-os-ghost">Limpar</a><?php endif; ?>
    </form>
  </div>
</div>

<!-- Movimentações -->
<div class="os-card">
  <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-arrows-down-up"></i> Movimentações de Estoque</div></div>
  <div class="os-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="os-table">
        <thead><tr><th>Data/Hora</th><th>Produto</th><th>Tipo</th><th style="text-align:center">Qtd</th><th>Motivo</th><th>Documento</th><th>Usuário</th></tr></thead>
        <tbody>
          <?php if (empty($movimentacoes)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:32px">Nenhuma movimentação encontrada.</td></tr>
          <?php endif; ?>
          <?php foreach ($movimentacoes as $m):
            $cor = match($m['tipo']) { 'entrada' => '#22c55e', 'saida' => '#ef4444', default => '#f59e0b' };
          ?>
          <tr>
            <td style="font-size:.78rem;color:var(--text-muted);white-space:nowrap"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
            <td><strong><?= htmlspecialchars($m['produto_nome']) ?></strong></td>
            <td>
              <span style="background:<?= $m['tipo']==='entrada'?'rgba(34,197,94,.15)':'rgba(239,68,68,.15)' ?>;color:<?= $cor ?>;padding:2px 8px;border-radius:6px;font-size:.78rem;font-weight:700">
                <?= ucfirst($m['tipo']) ?>
              </span>
            </td>
            <td style="text-align:center;font-weight:700;color:<?= $cor ?>">
              <?= $m['tipo']==='entrada'?'+':'-' ?><?= number_format($m['quantidade'],0) ?> <?= htmlspecialchars($m['unidade']??'') ?>
            </td>
            <td style="font-size:.82rem"><?= htmlspecialchars($m['motivo']??'') ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($m['documento']??'—') ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($m['usuario_nome']??'') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>

<!-- Modal Entrada -->
<div id="modalEntrada" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:500px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0;color:var(--success)"><i class="ph-bold ph-arrow-fat-down"></i> Entrada de Estoque</h5>
      <button onclick="document.getElementById('modalEntrada').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST" style="padding:20px 24px">
      <input type="hidden" name="entrada_estoque" value="1">
      <?= csrfField() ?>
      <div class="os-form-group" style="margin-bottom:14px">
        <label class="os-label">Produto *</label>
        <select name="produto_id" class="os-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($produtos as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $produto_id_filtro==$p['id']?'selected':''?>>
            <?= htmlspecialchars($p['nome']) ?> (Est: <?= number_format($p['estoque_atual'],0) ?> <?= $p['unidade'] ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="os-form-group"><label class="os-label">Quantidade *</label><input type="number" name="quantidade" class="os-input" min="0.01" step="0.01" required></div>
        <div class="os-form-group"><label class="os-label">Documento (NF)</label><input type="text" name="documento" class="os-input" placeholder="NF-123456"></div>
      </div>
      <div class="os-form-group" style="margin-bottom:16px"><label class="os-label">Motivo</label><input type="text" name="motivo" class="os-input" value="Compra de fornecedor"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn-os btn-os-ghost" onclick="document.getElementById('modalEntrada').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-check"></i> Registrar Entrada</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ajuste -->
<div id="modalAjuste" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:460px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0"><i class="ph-bold ph-sliders"></i> Ajuste de Estoque</h5>
      <button onclick="document.getElementById('modalAjuste').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST" style="padding:20px 24px">
      <input type="hidden" name="ajuste_estoque" value="1">
      <?= csrfField() ?>
      <div class="os-form-group" style="margin-bottom:14px">
        <label class="os-label">Produto *</label>
        <select name="produto_id" class="os-select" required onchange="mostrarAtual(this)">
          <option value="">Selecione...</option>
          <?php foreach ($produtos as $p): ?>
          <option value="<?= $p['id'] ?>" data-atual="<?= $p['estoque_atual'] ?>">
            <?= htmlspecialchars($p['nome']) ?> (atual: <?= number_format($p['estoque_atual'],0) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div class="os-form-group"><label class="os-label">Estoque Atual</label><input type="text" id="ajusteAtual" class="os-input" readonly style="background:var(--bg-card2);color:var(--text-muted)"></div>
        <div class="os-form-group"><label class="os-label">Nova Quantidade *</label><input type="number" name="nova_quantidade" class="os-input" min="0" step="0.01" required></div>
      </div>
      <div class="os-form-group" style="margin-bottom:16px"><label class="os-label">Motivo *</label><input type="text" name="motivo_ajuste" class="os-input" placeholder="Ex: Inventário físico" required></div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn-os btn-os-ghost" onclick="document.getElementById('modalAjuste').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-check"></i> Ajustar</button>
      </div>
    </form>
  </div>
</div>

<script>
function mostrarAtual(sel){const opt=sel.options[sel.selectedIndex];document.getElementById('ajusteAtual').value=opt.value?opt.dataset.atual:'';}
</script>

<?php include '../../includes/footer.php'; ?>
