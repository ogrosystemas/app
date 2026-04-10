<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente', 'caixa']);

// Verificar se já existe caixa aberto
$caixa_aberto = $db->query("SELECT * FROM caixa WHERE status = 'aberto'")->fetch();
if($caixa_aberto) { 
    header('Location: pdv.php'); 
    exit; 
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $saldo = str_replace(',', '.', str_replace('.', '', $_POST['saldo_inicial']));
    
    $stmt = $db->prepare("INSERT INTO caixa (saldo_inicial, usuario_abertura, status) VALUES (?, ?, 'aberto')");
    $stmt->execute([$saldo, $_SESSION['usuario_id']]);
    
    $_SESSION['mensagem'] = 'Caixa aberto com sucesso!';
    header('Location: pdv.php');
    exit;
}
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Página</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="ph-bold ph-money"></i> Abrir Caixa</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="ph-bold ph-info"></i> Para iniciar as vendas, é necessário abrir o caixa com o saldo inicial.
                        </div>
                        
                        <form method="POST" id="formAbertura">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Saldo Inicial (R$)</label>
                                <input type="text" name="saldo_inicial" id="saldo_inicial" class="form-control form-control-lg money" value="0,00" required autofocus>
                                <small class="text-muted">Digite o valor inicial do caixa (ex: 100,00)</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-lg" onclick="confirmarAbertura()">
                                    <i class="ph-bold ph-check-circle"></i> Abrir Caixa
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
            $('#saldo_inicial').mask('000.000.000,00', {reverse: true});
        });
        
        function confirmarAbertura() {
            var saldoInicial = $('#saldo_inicial').val();
            
            if(!saldoInicial || saldoInicial === '') {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Informe o saldo inicial do caixa!',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            Swal.fire({
                title: 'Abrir caixa?',
                text: 'Confirme o saldo inicial para abrir o caixa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, abrir caixa!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formAbertura').submit();
                }
            });
        }
    </script>

<?php include '../../includes/footer.php'; ?>
