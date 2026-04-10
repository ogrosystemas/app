<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente', 'caixa']);

// Buscar caixa aberto
$query = "SELECT * FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$caixa = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$caixa) {
    header('Location: pdv.php');
    exit;
}

$saldo_atual = $caixa['saldo_inicial'] + $caixa['total_vendas'] - $caixa['total_sangrias'] + $caixa['total_suprimentos'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saldo_final = str_replace(',', '.', str_replace('.', '', $_POST['saldo_final']));
    
    $query = "UPDATE caixa SET data_fechamento = NOW(), saldo_final = :saldo_final, 
              status = 'fechado', usuario_fechamento = :usuario WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':saldo_final' => $saldo_final,
        ':usuario' => $_SESSION['usuario_id'],
        ':id' => $caixa['id']
    ]);
    
    $_SESSION['mensagem'] = 'Caixa fechado com sucesso!';
    header('Location: ../relatorios/relatorios.php');
    exit;
}

// Buscar vendas do dia para conferência
$vendas_hoje = $db->query("SELECT COUNT(*) as total_vendas, SUM(total) as valor_total FROM vendas WHERE DATE(data_venda) = CURDATE() AND status = 'finalizada'")->fetch(PDO::FETCH_ASSOC);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Fechar Caixa</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-lock"></i> Fechar Caixa</h2>
            <a href="pdv.php" class="btn btn-secondary"><i class="ph-bold ph-arrow-left"></i> Voltar</a>
        </div>
        
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="ph-bold ph-money"></i> Fechamento do Caixa</h4>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <h6><i class="ph-bold ph-info"></i> Resumo do Caixa</h6>
                            <hr>
                            <p><strong>Data de Abertura:</strong> <?php echo date('d/m/Y H:i', strtotime($caixa['data_abertura'])); ?></p>
                            <p><strong>Saldo Inicial:</strong> R$ <?php echo number_format($caixa['saldo_inicial'], 2, ',', '.'); ?></p>
                            <p><strong>Total de Vendas:</strong> R$ <?php echo number_format($caixa['total_vendas'], 2, ',', '.'); ?></p>
                            <p><strong>Total de Sangrias:</strong> R$ <?php echo number_format($caixa['total_sangrias'], 2, ',', '.'); ?></p>
                            <p><strong>Total de Suprimentos:</strong> R$ <?php echo number_format($caixa['total_suprimentos'], 2, ',', '.'); ?></p>
                            <hr>
                            <h5 class="text-primary">Saldo Calculado: R$ <?php echo number_format($saldo_atual, 2, ',', '.'); ?></h5>
                        </div>
                        
                        <div class="info-box">
                            <h6><i class="ph-bold ph-chart-line-up"></i> Vendas do Dia</h6>
                            <hr>
                            <p><strong>Quantidade de Vendas:</strong> <?php echo $vendas_hoje['total_vendas']; ?></p>
                            <p><strong>Valor Total Vendido:</strong> R$ <?php echo number_format($vendas_hoje['valor_total'], 2, ',', '.'); ?></p>
                        </div>
                        
                        <form method="POST" id="formFechamento">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Saldo Final em Caixa (R$)</label>
                                <input type="text" name="saldo_final" id="saldo_final" class="form-control form-control-lg money" 
                                       value="<?php echo number_format($saldo_atual, 2, ',', '.'); ?>" required>
                                <small class="text-muted">Confirme o valor que está no caixa</small>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="ph-bold ph-warning"></i> 
                                <strong>Atenção!</strong> Após fechar o caixa, não será possível fazer novas vendas até abrir um novo caixa.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-danger btn-lg" onclick="confirmarFechamento()">
                                    <i class="ph-bold ph-check-circle"></i> Fechar Caixa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</main>

<script>
        $(document).ready(function() {
            // Máscara para valores monetários
            $('#saldo_final').mask('000.000.000,00', {reverse: true});
        });
        
        function confirmarFechamento() {
            var saldoFinal = $('#saldo_final').val();
            
            if(!saldoFinal || saldoFinal === '') {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Informe o saldo final do caixa!',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            Swal.fire({
                title: 'Fechar caixa?',
                text: 'Após fechar o caixa, não será possível fazer novas vendas!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, fechar caixa!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formFechamento').submit();
                }
            });
        }
    </script>

<?php include '../../includes/footer.php'; ?>
