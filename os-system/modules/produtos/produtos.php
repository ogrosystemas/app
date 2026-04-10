<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

// Processar formulário
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $codigo_barras = $_POST['codigo_barras'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco_compra = str_replace(',', '.', $_POST['preco_compra']);
    $preco_venda = str_replace(',', '.', $_POST['preco_venda']);
    $estoque_atual = $_POST['estoque_atual'];
    $estoque_minimo = $_POST['estoque_minimo'];
    $unidade = $_POST['unidade'];
    
    try {
        if($id) {
            $query = "UPDATE produtos SET codigo_barras = :codigo_barras, nome = :nome, descricao = :descricao, 
                      preco_compra = :preco_compra, preco_venda = :preco_venda, estoque_atual = :estoque_atual,
                      estoque_minimo = :estoque_minimo, unidade = :unidade WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':codigo_barras' => $codigo_barras,
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':preco_compra' => $preco_compra,
                ':preco_venda' => $preco_venda,
                ':estoque_atual' => $estoque_atual,
                ':estoque_minimo' => $estoque_minimo,
                ':unidade' => $unidade,
                ':id' => $id
            ]);
            $_SESSION['mensagem'] = 'Produto atualizado com sucesso!';
        } else {
            $query = "INSERT INTO produtos (codigo_barras, nome, descricao, preco_compra, preco_venda, estoque_atual, estoque_minimo, unidade) 
                      VALUES (:codigo_barras, :nome, :descricao, :preco_compra, :preco_venda, :estoque_atual, :estoque_minimo, :unidade)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':codigo_barras' => $codigo_barras,
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':preco_compra' => $preco_compra,
                ':preco_venda' => $preco_venda,
                ':estoque_atual' => $estoque_atual,
                ':estoque_minimo' => $estoque_minimo,
                ':unidade' => $unidade
            ]);
            $_SESSION['mensagem'] = 'Produto cadastrado com sucesso!';
        }
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao salvar produto: ' . $e->getMessage();
    }
    
    header('Location: produtos.php');
    exit;
}

// Excluir produto
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    try {
        $query = "DELETE FROM produtos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $id]);
        $_SESSION['mensagem'] = 'Produto excluído com sucesso!';
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao excluir produto: ' . $e->getMessage();
    }
    header('Location: produtos.php');
    exit;
}

// Buscar produtos
$query = "SELECT * FROM produtos ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Produtos</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-package"></i> Produtos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProduto" onclick="limparFormulario()">
                <i class="ph-bold ph-plus-circle"></i> Novo Produto
            </button>
        </div>
        
        <?php if($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Código Barras</th>
                        <th>Nome</th>
                        <th>Preço Compra</th>
                        <th>Preço Venda</th>
                        <th>Estoque</th>
                        <th>Estoque Mínimo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($produtos as $produto): ?>
                    <tr>
                        <td><?php echo $produto['codigo_barras'] ?? '-'; ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td>R$ <?php echo number_format($produto['preco_compra'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($produto['preco_venda'], 2, ',', '.'); ?></td>
                        <td>
                            <?php echo $produto['estoque_atual']; ?>
                            <?php if($produto['estoque_atual'] <= $produto['estoque_minimo']): ?>
                                <span class="badge bg-danger">Baixo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $produto['estoque_minimo']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='editarProduto(<?php echo json_encode($produto, JSON_HEX_TAG); ?>)'>
                                <i class="ph-bold ph-pencil-simple"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoProduto(<?php echo $produto['id']; ?>, '<?php echo addslashes($produto['nome']); ?>')">
                                <i class="ph-bold ph-trash"></i> Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    
    <!-- Modal Produto -->
    <div class="modal fade" id="modalProduto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="produto_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label>Código de Barras</label>
                                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control">
                                <small class="text-muted">Use um leitor de código de barras</small>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Nome do Produto *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Descrição</label>
                                <textarea name="descricao" id="descricao" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Preço de Compra</label>
                                <input type="text" name="preco_compra" id="preco_compra" class="form-control money" value="0,00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Preço de Venda *</label>
                                <input type="text" name="preco_venda" id="preco_venda" class="form-control money" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Estoque Atual</label>
                                <input type="number" name="estoque_atual" id="estoque_atual" class="form-control" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Estoque Mínimo</label>
                                <input type="number" name="estoque_minimo" id="estoque_minimo" class="form-control" value="5">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Unidade</label>
                                <select name="unidade" id="unidade" class="form-select">
                                    <option value="UN">Unidade</option>
                                    <option value="PC">Peça</option>
                                    <option value="CX">Caixa</option>
                                    <option value="LT">Litro</option>
                                    <option value="KG">Quilograma</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('.money').mask('000.000.000,00', {reverse: true});
        });
        
        function editarProduto(produto) {
            $('#modalTitulo').text('Editar Produto');
            $('#produto_id').val(produto.id);
            $('#codigo_barras').val(produto.codigo_barras);
            $('#nome').val(produto.nome);
            $('#descricao').val(produto.descricao);
            $('#preco_compra').val(parseFloat(produto.preco_compra).toFixed(2).replace('.', ','));
            $('#preco_venda').val(parseFloat(produto.preco_venda).toFixed(2).replace('.', ','));
            $('#estoque_atual').val(produto.estoque_atual);
            $('#estoque_minimo').val(produto.estoque_minimo);
            $('#unidade').val(produto.unidade);
            $('#modalProduto').modal('show');
        }
        
        function limparFormulario() {
            $('#modalTitulo').text('Novo Produto');
            $('#produto_id').val('');
            $('#codigo_barras').val('');
            $('#nome').val('');
            $('#descricao').val('');
            $('#preco_compra').val('0,00');
            $('#preco_venda').val('');
            $('#estoque_atual').val('0');
            $('#estoque_minimo').val('5');
            $('#unidade').val('UN');
        }
        
        function confirmarExclusaoProduto(id, nome) {
            Swal.fire({
                title: 'Tem certeza?',
                text: `Deseja realmente excluir o produto "${nome}"? Esta ação não pode ser desfeita!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?excluir=' + id;
                }
            });
        }
    </script>
</main>

<?php include '../../includes/footer.php'; ?>
