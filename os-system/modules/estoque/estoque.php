<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

// Registrar entrada de estoque
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['entrada_estoque'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    $motivo = $_POST['motivo'];
    $documento = $_POST['documento'];
    
    if(!$produto_id || $quantidade <= 0) {
        $_SESSION['erro'] = 'Selecione um produto e informe uma quantidade válida!';
        header('Location: estoque.php');
        exit;
    }
    
    $db->beginTransaction();
    try {
        // Atualizar estoque
        $stmt = $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id = ?");
        $stmt->execute([$quantidade, $produto_id]);
        
        // Registrar movimentação
        $stmt = $db->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, documento, created_by) VALUES (?, 'entrada', ?, ?, ?, ?)");
        $stmt->execute([$produto_id, $quantidade, $motivo, $documento, $_SESSION['usuario_id']]);
        
        $db->commit();
        $_SESSION['mensagem'] = 'Entrada de estoque registrada com sucesso!';
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['erro'] = 'Erro ao registrar entrada: ' . $e->getMessage();
    }
    header('Location: estoque.php');
    exit;
}

// Buscar produtos
$produtos = $db->query("SELECT * FROM produtos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Buscar movimentações recentes
$movimentacoes = $db->query("SELECT m.*, p.nome as produto_nome, u.nome as usuario_nome FROM movimentacoes_estoque m JOIN produtos p ON m.produto_id = p.id LEFT JOIN usuarios u ON m.created_by = u.id ORDER BY m.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Controle de Estoque</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<h2 class="mb-4"><i class="ph-bold ph-database"></i> Controle de Estoque</h2>
        
        <?php if($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="ph-bold ph-plus-circle"></i> Entrada de Estoque</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formEntrada">
                            <input type="hidden" name="entrada_estoque" value="1">
                            <div class="mb-3">
                                <label>Produto *</label>
                                <select name="produto_id" id="produto_id" class="form-select" required>
                                    <option value="">Selecione um produto</option>
                                    <?php foreach($produtos as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo $p['nome']; ?> (Estoque atual: <?php echo $p['estoque_atual']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Quantidade *</label>
                                <input type="number" name="quantidade" id="quantidade" class="form-control" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label>Motivo *</label>
                                <select name="motivo" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <option value="Compra">Compra</option>
                                    <option value="Devolução">Devolução</option>
                                    <option value="Ajuste">Ajuste de Estoque</option>
                                    <option value="Transferência">Transferência</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Documento (Nota Fiscal)</label>
                                <input type="text" name="documento" class="form-control" placeholder="NF-000000">
                            </div>
                            <button type="button" class="btn btn-primary w-100" onclick="confirmarEntrada()">
                                <i class="ph-bold ph-check-circle"></i> Registrar Entrada
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="ph-bold ph-warning"></i> Produtos com Estoque Baixo (≤ 10 unidades)</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $estoque_baixo = array_filter($produtos, function($p) {
                            return $p['estoque_atual'] <= 10;
                        });
                        ?>
                        <?php if(count($estoque_baixo) > 0): ?>
                            <div class="list-group">
                                <?php foreach($estoque_baixo as $p): ?>
                                    <?php 
                                    $classe = $p['estoque_atual'] <= 5 ? 'list-group-item-danger' : 'list-group-item-warning';
                                    $mensagem = $p['estoque_atual'] <= 5 ? 'Estoque CRÍTICO!' : 'Estoque BAIXO!';
                                    ?>
                                    <div class="list-group-item <?php echo $classe; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo $p['nome']; ?></strong><br>
                                                <small>Estoque atual: <?php echo $p['estoque_atual']; ?> unidades</small>
                                            </div>
                                            <div>
                                                <span class="badge bg-danger"><?php echo $mensagem; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="ph-bold ph-check-circle"></i> Todos os produtos com estoque adequado!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="ph-bold ph-clock-counter-clockwise"></i> Últimas Movimentações</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Produto</th>
                                <th>Tipo</th>
                                <th>Quantidade</th>
                                <th>Motivo</th>
                                <th>Documento</th>
                                <th>Usuário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($movimentacoes as $m): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                                <td><?php echo $m['produto_nome']; ?></td>
                                <td><?php echo $m['tipo'] == 'entrada' ? '<span class="badge bg-success">Entrada</span>' : '<span class="badge bg-danger">Saída</span>'; ?></td>
                                <td><?php echo $m['quantidade']; ?></td>
                                <td><?php echo $m['motivo']; ?></td>
                                <td><?php echo $m['documento']; ?></td>
                                <td><?php echo $m['usuario_nome']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </div>
                </div>
            </div>
</main>

</div>
    
    <script>
        function confirmarEntrada() {
            var produto = document.getElementById('produto_id').value;
            var quantidade = document.getElementById('quantidade').value;
            
            if(!produto) {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Selecione um produto!',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            if(!quantidade || quantidade <= 0) {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Informe uma quantidade válida!',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            Swal.fire({
                title: 'Confirmar entrada?',
                text: `Deseja realmente registrar a entrada de ${quantidade} unidade(s) no estoque?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, registrar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formEntrada').submit();
                }
            });
        }
    </script>

<script>
function confirmarEntrada() {
    var produto = document.getElementById('produto_id').value;
    var qtd = document.getElementById('quantidade').value;
    if (!produto || !qtd || qtd <= 0) {
        alert('Selecione um produto e informe uma quantidade válida!');
        return;
    }
    document.getElementById('formEntrada').submit();
}
</script>
<?php include '../../includes/footer.php'; ?>
