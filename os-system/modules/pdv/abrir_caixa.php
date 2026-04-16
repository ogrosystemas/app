<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente', 'caixa', 'vendedor']);

$caixa_aberto = $db->query("SELECT id FROM caixa WHERE status = 'aberto' LIMIT 1")->fetch();
if ($caixa_aberto) { header('Location: pdv.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfVerify();
    $saldo = (float)str_replace(['.', ','], ['', '.'], $_POST['saldo_inicial'] ?? '0');
    $db->prepare("INSERT INTO caixa (saldo_inicial, usuario_abertura, status) VALUES (?, ?, 'aberto')")
       ->execute([$saldo, $_SESSION['usuario_id']]);
    $_SESSION['mensagem'] = 'Caixa aberto! Saldo inicial: R$ ' . number_format($saldo, 2, ',', '.');
    header('Location: pdv.php'); exit;
}
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Abrir Caixa</div>
</header>

<main class="os-content" style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 112px)">
  <div style="width:440px">
    <div class="os-card">
      <div style="padding:32px 32px 24px;text-align:center;border-bottom:1px solid var(--border)">
        <div style="width:64px;height:64px;background:rgba(34,197,94,.15);border:2px solid rgba(34,197,94,.3);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;color:#22c55e">
          <i class="ph-bold ph-vault"></i>
        </div>
        <h4 style="font-family:'Syne',sans-serif;font-weight:800;margin:0 0 6px">Abrir Caixa</h4>
        <p style="color:var(--text-muted);font-size:.88rem;margin:0">Informe o saldo inicial para começar as vendas</p>
      </div>
      <div style="padding:28px 32px">
        <form method="POST">
          <?= csrfField() ?>
          <div class="os-form-group" style="margin-bottom:20px">
            <label class="os-label">Saldo Inicial (R$)</label>
            <input type="number" name="saldo_inicial" class="os-input"
                   style="font-size:1.8rem;font-family:'Syne',sans-serif;font-weight:800;text-align:center;padding:14px"
                   step="0.01" min="0" value="0.00" required autofocus>
          </div>
          <button type="submit" class="btn-os btn-os-primary"
                  style="width:100%;justify-content:center;padding:13px;font-size:1rem">
            <i class="ph-bold ph-check-circle"></i> Abrir Caixa
          </button>
        </form>
      </div>
    </div>
    <div style="text-align:center;margin-top:16px">
      <a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted);font-size:.82rem;text-decoration:none">
        <i class="ph-bold ph-arrow-left"></i> Voltar ao Dashboard
      </a>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
