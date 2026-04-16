<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente', 'caixa']);

$stmt = $db->prepare("SELECT * FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$caixa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caixa) { header('Location: pdv.php'); exit; }

$saldo_esperado = $caixa['saldo_inicial'] + $caixa['total_vendas']
                + $caixa['total_suprimentos'] - $caixa['total_sangrias'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $saldo_final = (float)str_replace(['.', ','], ['', '.'], $_POST['saldo_final'] ?? $saldo_esperado);
    $db->prepare("UPDATE caixa SET data_fechamento=NOW(), saldo_final=?, status='fechado', usuario_fechamento=? WHERE id=?")
       ->execute([$saldo_final, $_SESSION['usuario_id'], $caixa['id']]);
    $_SESSION['mensagem'] = 'Caixa fechado! Saldo final: R$ ' . number_format($saldo_final, 2, ',', '.');
    header('Location: ' . BASE_URL . '/modules/relatorios/relatorios.php'); exit;
}

// Vendas do caixa por forma de pagamento
$stmt = $db->prepare("SELECT forma_pagamento, COUNT(*) as qtd, SUM(total) as total FROM vendas WHERE caixa_id = ? AND status = 'finalizada' GROUP BY forma_pagamento");
$stmt->execute([$caixa['id']]);
$por_forma = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM caixa_movimentacoes WHERE caixa_id = ? AND tipo IN ('sangria','suprimento') ORDER BY created_at DESC");
$stmt->execute([$caixa['id']]);
$movs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formas_label = ['dinheiro'=>'Dinheiro','pix'=>'PIX','cartao_credito'=>'Crédito','cartao_debito'=>'Débito','boleto'=>'Boleto','mix'=>'Misto'];
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Fechar Caixa <span style="color:var(--accent)">·</span> #<?= $caixa['id'] ?></div>
  <div class="topbar-actions">
    <a href="pdv.php" class="btn-os btn-os-ghost"><i class="ph-bold ph-arrow-left"></i> Voltar ao PDV</a>
  </div>
</header>

<main class="os-content">
<div style="max-width:720px;margin:0 auto">

  <!-- Resumo -->
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-vault"></i> Resumo do Caixa</div>
      <span style="font-size:.8rem;color:var(--text-muted)">Aberto em <?= date('d/m/Y H:i', strtotime($caixa['data_abertura'])) ?></span>
    </div>
    <div class="os-card-body">
      <div class="grid-4" style="gap:12px;margin-bottom:20px">
        <?php
        $stats = [
          ['Saldo Inicial',   $caixa['saldo_inicial'],    'ph-piggy-bank',    '#38bdf8'],
          ['Total Vendas',    $caixa['total_vendas'],     'ph-trending-up',   '#22c55e'],
          ['Suprimentos',     $caixa['total_suprimentos'],'ph-arrow-up',      '#a78bfa'],
          ['Sangrias',        $caixa['total_sangrias'],   'ph-arrow-down',    '#f87171'],
        ];
        foreach ($stats as [$label, $val, $icon, $color]):
        ?>
        <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
          <i class="ph-bold <?= $icon ?>" style="font-size:1.4rem;color:<?= $color ?>;display:block;margin-bottom:6px"></i>
          <div style="font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><?= $label ?></div>
          <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;color:var(--text)">R$ <?= number_format($val, 2, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Saldo esperado destaque -->
      <div style="background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05));border:1px solid rgba(245,158,11,.3);border-radius:12px;padding:20px;display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-size:.75rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.05em">Saldo Esperado no Caixa</div>
          <div style="font-size:.82rem;color:var(--text-muted);margin-top:3px">Inicial + Vendas + Suprimentos - Sangrias</div>
        </div>
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent)">
          R$ <?= number_format($saldo_esperado, 2, ',', '.') ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Por forma de pagamento -->
  <?php if (!empty($por_forma)): ?>
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-credit-card"></i> Vendas por Forma de Pagamento</div></div>
    <div class="os-card-body" style="padding:0">
      <table class="os-table">
        <thead><tr><th>Forma</th><th style="text-align:center">Qtd.</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php foreach ($por_forma as $f): ?>
          <tr>
            <td><strong><?= htmlspecialchars($formas_label[$f['forma_pagamento']] ?? $f['forma_pagamento']) ?></strong></td>
            <td style="text-align:center"><?= $f['qtd'] ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($f['total'], 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Movimentações -->
  <?php if (!empty($movs)): ?>
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-arrows-down-up"></i> Sangrias / Suprimentos</div></div>
    <div class="os-card-body" style="padding:0">
      <table class="os-table">
        <thead><tr><th>Tipo</th><th>Descrição</th><th style="text-align:right">Valor</th><th>Horário</th></tr></thead>
        <tbody>
          <?php foreach ($movs as $m): ?>
          <tr>
            <td><span class="os-badge <?= $m['tipo']==='sangria'?'os-badge-red':'os-badge-green' ?>"><?= ucfirst($m['tipo']) ?></span></td>
            <td><?= htmlspecialchars($m['descricao'] ?? '') ?></td>
            <td style="text-align:right;font-weight:600">R$ <?= number_format($m['valor'], 2, ',', '.') ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= date('H:i', strtotime($m['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Fechar -->
  <div class="os-card">
    <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-lock"></i> Confirmar Fechamento</div></div>
    <div class="os-card-body">
      <form method="POST" id="formFechamento">
        <?= csrfField() ?>
        <div class="os-form-group" style="margin-bottom:16px">
          <label class="os-label">Saldo Físico Contado (R$)</label>
          <input type="number" name="saldo_final" class="os-input"
                 style="font-size:1.4rem;font-family:'Syne',sans-serif;font-weight:700;text-align:center;padding:12px"
                 step="0.01" min="0" value="<?= number_format($saldo_esperado, 2, '.', '') ?>" required>
          <div style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:4px">
            Informe o valor físico contado no caixa. Diferenças serão registradas.
          </div>
        </div>
        <button type="submit" class="btn-os" onclick="return confirm('Confirma o fechamento do caixa?')"
                style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ef4444;width:100%;justify-content:center;padding:13px;font-size:.95rem;font-weight:700">
          <i class="ph-bold ph-lock"></i> Fechar Caixa Definitivamente
        </button>
      </form>
    </div>
  </div>

</div>
</main>

<?php include '../../includes/footer.php'; ?>
