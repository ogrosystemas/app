<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

$mensagem = $_SESSION['mensagem'] ?? null;
$erro     = $_SESSION['erro']     ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Excluir
if (isset($_GET['excluir'])) {
    try {
        $db->prepare("UPDATE servicos SET ativo = 0 WHERE id = ?")->execute([(int)$_GET['excluir']]);
        $_SESSION['mensagem'] = 'Serviço desativado.';
    } catch (PDOException $e) { $_SESSION['erro'] = $e->getMessage(); }
    header('Location: servicos.php'); exit;
}

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    csrfVerify();
    $id = (int)($_POST['id'] ?? 0);
    $campos = [
        'nome'           => trim($_POST['nome'] ?? ''),
        'descricao'      => trim($_POST['descricao'] ?? ''),
        'valor'          => (float)str_replace(',', '.', $_POST['valor'] ?? '0'),
        'tempo_estimado' => (int)($_POST['tempo_estimado'] ?? 0) ?: null,
        'garantia_dias'  => (int)($_POST['garantia_dias'] ?? 0),
        'ativo'          => 1,
    ];
    if (!$campos['nome']) { $_SESSION['erro'] = 'Nome é obrigatório.'; }
    else {
        try {
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "$k=?", array_keys($campos)));
                $db->prepare("UPDATE servicos SET $set WHERE id=?")->execute([...array_values($campos), $id]);
                $_SESSION['mensagem'] = 'Serviço atualizado!';
            } else {
                $cols = implode(',', array_keys($campos));
                $vals = implode(',', array_fill(0, count($campos), '?'));
                $db->prepare("INSERT INTO servicos ($cols) VALUES ($vals)")->execute(array_values($campos));
                $_SESSION['mensagem'] = 'Serviço cadastrado!';
            }
        } catch (PDOException $e) { $_SESSION['erro'] = $e->getMessage(); }
    }
    header('Location: servicos.php'); exit;
}

$servicos = $db->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Serviços</div>
  <div class="topbar-actions">
    <button class="btn-os btn-os-primary" onclick="abrirModal()">
      <i class="ph-bold ph-plus-circle"></i> Novo Serviço
    </button>
  </div>
</header>

<main class="os-content">
<?php if ($mensagem): ?><div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro):     ?><div class="os-alert os-alert-danger"><i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="os-card">
  <div class="os-card-body" style="padding:0">
    <table class="os-table">
      <thead><tr><th>Nome</th><th>Descrição</th><th style="text-align:right">Valor</th><th style="text-align:center">Tempo (min)</th><th style="text-align:center">Garantia</th><th style="text-align:center">Ações</th></tr></thead>
      <tbody>
        <?php if (empty($servicos)): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px">Nenhum serviço cadastrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($servicos as $s): ?>
        <tr>
          <td><strong><?= htmlspecialchars($s['nome']) ?></strong></td>
          <td style="font-size:.82rem;color:var(--text-muted)"><?= htmlspecialchars(mb_strimwidth($s['descricao']??'',0,60,'...')) ?></td>
          <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($s['valor']??0,2,',','.') ?></td>
          <td style="text-align:center"><?= $s['tempo_estimado'] ? $s['tempo_estimado'].' min' : '—' ?></td>
          <td style="text-align:center"><?= $s['garantia_dias'] ? $s['garantia_dias'].' dias' : '—' ?></td>
          <td style="text-align:center">
            <div style="display:flex;gap:5px;justify-content:center">
              <button class="btn-os btn-os-ghost" style="padding:5px 8px" onclick='editarServico(<?= htmlspecialchars(json_encode($s),ENT_QUOTES) ?>)'><i class="ph-bold ph-pencil-simple"></i></button>
              <a href="?excluir=<?= $s['id'] ?>" class="btn-os" style="padding:5px 8px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444" onclick="return confirm('Desativar este serviço?')"><i class="ph-bold ph-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</main>

<!-- Modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:540px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalTitulo">Novo Serviço</h5>
      <button onclick="document.getElementById('modal').style.display='none'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="salvar" value="1">
      <input type="hidden" name="id" id="sId">
      <?= csrfField() ?>
      <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px">
        <div class="os-form-group"><label class="os-label">Nome *</label><input type="text" name="nome" id="sNome" class="os-input" required></div>
        <div class="os-form-group"><label class="os-label">Descrição</label><textarea name="descricao" id="sDesc" class="os-input" rows="2"></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
          <div class="os-form-group"><label class="os-label">Valor (R$)</label><input type="number" name="valor" id="sValor" class="os-input" step="0.01" min="0" value="0.00"></div>
          <div class="os-form-group"><label class="os-label">Tempo (min)</label><input type="number" name="tempo_estimado" id="sTempo" class="os-input" min="0"></div>
          <div class="os-form-group"><label class="os-label">Garantia (dias)</label><input type="number" name="garantia_dias" id="sGarantia" class="os-input" min="0" value="30"></div>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 24px;border-top:1px solid var(--border)">
        <button type="button" class="btn-os btn-os-ghost" onclick="document.getElementById('modal').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-os btn-os-primary"><i class="ph-bold ph-floppy-disk"></i> Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal(){document.getElementById('modalTitulo').textContent='Novo Serviço';['sId','sNome','sDesc','sTempo'].forEach(id=>{document.getElementById(id).value='';});document.getElementById('sValor').value='0.00';document.getElementById('sGarantia').value='30';document.getElementById('modal').style.display='flex';}
function editarServico(s){document.getElementById('modalTitulo').textContent='Editar Serviço';document.getElementById('sId').value=s.id;document.getElementById('sNome').value=s.nome;document.getElementById('sDesc').value=s.descricao||'';document.getElementById('sValor').value=parseFloat(s.valor||0).toFixed(2);document.getElementById('sTempo').value=s.tempo_estimado||'';document.getElementById('sGarantia').value=s.garantia_dias||30;document.getElementById('modal').style.display='flex';}
</script>
<?php include '../../includes/footer.php'; ?>
