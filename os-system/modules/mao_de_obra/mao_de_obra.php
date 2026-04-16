<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

// Create table if not exists
try { $db->exec("CREATE TABLE IF NOT EXISTS mao_de_obra (id INT AUTO_INCREMENT PRIMARY KEY, valor_hora DECIMAL(10,2) NOT NULL DEFAULT 0.00, descricao VARCHAR(255), ativo TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)"); } catch(Exception $e) {}

$mensagem = $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    csrfVerify();
    // Converte valor brasileiro (formato 100,00 ou 100.00) para float
    $valor_bruto = trim($_POST['valor_hora'] ?? '0');
    $valor_bruto = str_replace('.', '', $valor_bruto); // remove pontos de milhar
    $valor_bruto = str_replace(',', '.', $valor_bruto); // vírgula vira ponto
    $valor_hora = (float)$valor_bruto;
    $descricao  = trim($_POST['descricao'] ?? '');
    try {
        $existe = (int)$db->query("SELECT COUNT(*) FROM mao_de_obra")->fetchColumn();
        if ($existe > 0) {
            $db->prepare("UPDATE mao_de_obra SET valor_hora=?, descricao=? ORDER BY id DESC LIMIT 1")->execute([$valor_hora, $descricao]);
        } else {
            $db->prepare("INSERT INTO mao_de_obra (valor_hora, descricao) VALUES (?,?)")->execute([$valor_hora, $descricao]);
        }
        $mensagem = 'Mão de obra atualizada!';
    } catch (Exception $e) { $erro = $e->getMessage(); }
}

$atual = $db->query("SELECT * FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$historico = $db->query("SELECT os.numero_os, os.data_abertura, SUM(oss.quantidade * oss.valor_unitario) as total, SUM(s.tempo_estimado * oss.quantidade) as total_min FROM os_servicos oss JOIN ordens_servico os ON oss.os_id=os.id JOIN servicos s ON oss.servico_id=s.id GROUP BY os.id ORDER BY os.data_abertura DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Mão de Obra <span style="color:var(--accent)">·</span> Valor por Hora</div>
</header>

<main class="os-content">
<?php if ($mensagem): ?><div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="os-alert os-alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:340px 1fr;gap:20px">

  <div class="os-card">
    <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-clock"></i> Configurar Valor/Hora</div></div>
    <div class="os-card-body">
      <div style="text-align:center;padding:16px 0 20px">
        <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:600;margin-bottom:8px">Valor Atual por Hora</div>
        <div style="font-family:'Syne',sans-serif;font-size:3rem;font-weight:800;color:var(--accent)">
          R$ <?= number_format($atual['valor_hora'] ?? 0, 2, ',', '.') ?>
        </div>
      </div>
      <form method="POST">
        <input type="hidden" name="salvar" value="1">
        <?= csrfField() ?>
        <div class="os-form-group" style="margin-bottom:14px">
          <label class="os-label">Novo Valor por Hora (R$)</label>
          <input type="text" name="valor_hora" class="os-input" 
                 value="<?= number_format($atual['valor_hora'] ?? 0, 2, ',', '.') ?>" 
                 placeholder="0,00"
                 style="font-size:1.1rem;text-align:center;font-family:'Syne',sans-serif;font-weight:700">
        </div>
        <div class="os-form-group" style="margin-bottom:16px">
          <label class="os-label">Observação</label>
          <input type="text" name="descricao" class="os-input" value="<?= htmlspecialchars($atual['descricao'] ?? '') ?>" placeholder="Ex: Revisão de tabela Jan/2025">
        </div>
        <button type="submit" class="btn-os btn-os-primary" style="width:100%;justify-content:center"><i class="ph-bold ph-floppy-disk"></i> Salvar</button>
      </form>
    </div>
  </div>

  <div class="os-card">
    <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-list-checks"></i> Últimas OS com Mão de Obra</div></div>
    <div class="os-card-body" style="padding:0">
      <table class="os-table">
        <thead><tr><th>OS</th><th>Data</th><th style="text-align:center">Horas</th><th style="text-align:right">M.O. Calculada</th></tr></thead>
        <tbody>
          <?php if (empty($historico)): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:24px">Nenhum registro ainda.</td></tr><?php endif; ?>
          <?php foreach ($historico as $h):
            $horas = round($h['total_min'] / 60, 1);
            $mo    = $horas * ($atual['valor_hora'] ?? 0);
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($h['numero_os']) ?></strong></td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= date('d/m/Y', strtotime($h['data_abertura'])) ?></td>
            <td style="text-align:center"><?= $horas ?>h</td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($mo, 2, ',', '.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
       </div>
    </div>
  </div>

</div>
</main>

<script>
// Adiciona máscara de dinheiro ao campo valor_hora
document.addEventListener('DOMContentLoaded', function() {
    var inputValor = document.querySelector('input[name="valor_hora"]');
    if (inputValor) {
        inputValor.addEventListener('input', function(e) {
            var valor = this.value;
            // Remove tudo que não for número ou vírgula
            valor = valor.replace(/[^0-9,]/g, '');
            // Separa centavos
            var partes = valor.split(',');
            if (partes.length > 2) {
                valor = partes[0] + ',' + partes.slice(1).join('');
            }
            this.value = valor;
        });
        
        // Ao perder o foco, formata para 2 casas decimais
        inputValor.addEventListener('blur', function() {
            var valor = this.value;
            if (valor === '') {
                this.value = '0,00';
                return;
            }
            // Remove pontos e mantém apenas números e vírgula
            valor = valor.replace(/[^0-9,]/g, '');
            var partes = valor.split(',');
            var inteiro = partes[0].replace(/^0+/, '') || '0';
            var centavos = partes[1] ? partes[1].substring(0, 2) : '00';
            if (centavos.length === 1) centavos += '0';
            this.value = inteiro + ',' + centavos;
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>