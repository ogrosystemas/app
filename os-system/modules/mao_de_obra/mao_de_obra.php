<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

// Create table if not exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS mao_de_obra (
        id INT AUTO_INCREMENT PRIMARY KEY,
        valor_hora DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        descricao VARCHAR(255),
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch(Exception $e) {}

$mensagem = $erro = '';

// Salvar valor hora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $valor_hora = str_replace(['.', ','], ['', '.'], $_POST['valor_hora'] ?? '0');
    $descricao  = trim($_POST['descricao'] ?? '');

    try {
        // Check if exists
        $existe = $db->query("SELECT COUNT(*) FROM mao_de_obra")->fetchColumn();
        if ($existe > 0) {
            $db->prepare("UPDATE mao_de_obra SET valor_hora = ?, descricao = ? ORDER BY id DESC LIMIT 1")
               ->execute([(float)$valor_hora, $descricao]);
        } else {
            $db->prepare("INSERT INTO mao_de_obra (valor_hora, descricao) VALUES (?, ?)")
               ->execute([(float)$valor_hora, $descricao]);
        }
        $mensagem = 'Valor de mão de obra atualizado com sucesso!';
    } catch(Exception $e) {
        $erro = 'Erro ao salvar: ' . $e->getMessage();
    }
}

// Load current value
$mao = ['valor_hora' => 0, 'descricao' => ''];
try {
    $stmt = $db->query("SELECT * FROM mao_de_obra ORDER BY id DESC LIMIT 1");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $mao = $row;
} catch(Exception $e) {}
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Mão de Obra <span style="color:var(--accent)">·</span> Valor por Hora</div>
</header>

<main class="os-content">
<div style="max-width:640px;margin:0 auto">

<?php if ($mensagem): ?>
<div class="os-alert os-alert-success" style="margin-bottom:20px">
  <i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?>
</div>
<?php endif; ?>
<?php if ($erro): ?>
<div class="os-alert os-alert-danger" style="margin-bottom:20px">
  <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<div class="os-card">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-wrench"></i> Configuração de Mão de Obra</div>
  </div>
  <div class="os-card-body">
    <form method="POST">
      <input type="hidden" name="salvar" value="1">

      <div class="os-form-group" style="margin-bottom:20px">
        <label class="os-label">Valor por Hora (R$)</label>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="position:relative;flex:1">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600">R$</span>
            <input type="text" name="valor_hora" id="valorHora" class="os-input"
                   style="padding-left:36px;font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:var(--accent)"
                   value="<?= number_format((float)$mao['valor_hora'], 2, ',', '.') ?>"
                   placeholder="0,00" required>
          </div>
          <div style="font-size:.8rem;color:var(--text-muted);white-space:nowrap">por hora</div>
        </div>
        <small style="color:var(--text-muted);font-size:.75rem;margin-top:6px;display:block">
          O sistema usa o tempo estimado de cada serviço para calcular automaticamente o valor da mão de obra nos orçamentos e OS.
        </small>
      </div>

      <div class="os-form-group" style="margin-bottom:20px">
        <label class="os-label">Descrição (opcional)</label>
        <input type="text" name="descricao" class="os-input"
               value="<?= htmlspecialchars($mao['descricao'] ?? '') ?>"
               placeholder="Ex: Mão de obra padrão oficina">
      </div>

      <!-- Preview de cálculo -->
      <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:20px">
        <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">Exemplo de Cálculo</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;text-align:center">
          <div>
            <div style="font-size:.75rem;color:var(--text-muted)">Serviço (60 min)</div>
            <div id="ex1h" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--accent);font-size:1rem">R$ <?= number_format((float)$mao['valor_hora'], 2, ',', '.') ?></div>
          </div>
          <div>
            <div style="font-size:.75rem;color:var(--text-muted)">Serviço (90 min)</div>
            <div id="ex15h" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--accent);font-size:1rem">R$ <?= number_format((float)$mao['valor_hora'] * 1.5, 2, ',', '.') ?></div>
          </div>
          <div>
            <div style="font-size:.75rem;color:var(--text-muted)">Serviço (120 min)</div>
            <div id="ex2h" style="font-family:'Syne',sans-serif;font-weight:800;color:var(--accent);font-size:1rem">R$ <?= number_format((float)$mao['valor_hora'] * 2, 2, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-os btn-os-primary" style="width:100%;padding:12px">
        <i class="ph-bold ph-floppy-disk"></i> Salvar Valor de Mão de Obra
      </button>
    </form>
  </div>
</div>

<!-- Info sobre tempo estimado nos serviços -->
<div class="os-card" style="margin-top:16px">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-info"></i> Como Funciona</div>
  </div>
  <div class="os-card-body" style="font-size:.85rem;color:var(--text-muted);line-height:1.7">
    <p>O valor de mão de obra é calculado automaticamente com base no <strong style="color:var(--text)">tempo estimado</strong> de cada serviço cadastrado em <strong style="color:var(--text)">Gestão → Serviços</strong>.</p>
    <p>Fórmula: <code style="background:var(--bg-card2);padding:2px 6px;border-radius:4px;color:var(--accent)">Tempo (horas) × R$/hora = Mão de Obra</code></p>
    <p>Exemplo: Serviço de 90 minutos com R$ 80,00/hora = <strong style="color:var(--accent)">R$ 120,00</strong> de mão de obra.</p>
  </div>
</div>

</div>
</main>

<script>
document.getElementById('valorHora').addEventListener('input', function() {
  var v = parseFloat(this.value.replace(/\./g,'').replace(',','.')) || 0;
  document.getElementById('ex1h').textContent  = 'R$ ' + (v * 1).toFixed(2).replace('.',',');
  document.getElementById('ex15h').textContent = 'R$ ' + (v * 1.5).toFixed(2).replace('.',',');
  document.getElementById('ex2h').textContent  = 'R$ ' + (v * 2).toFixed(2).replace('.',',');
});
</script>

<?php include '../../includes/footer.php'; ?>
