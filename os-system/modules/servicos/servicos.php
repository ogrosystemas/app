<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

// Processar formulário
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $tempo_estimado = $_POST['tempo_estimado'];
    $garantia_dias = $_POST['garantia_dias'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    try {
        if($id) {
            $stmt = $db->prepare("UPDATE servicos SET nome=?, descricao=?, tempo_estimado=?, garantia_dias=?, ativo=? WHERE id=?");
            $stmt->execute([$nome, $descricao, $tempo_estimado, $garantia_dias, $ativo, $id]);
            $_SESSION['mensagem'] = 'Serviço atualizado com sucesso!';
        } else {
            $stmt = $db->prepare("INSERT INTO servicos (nome, descricao, tempo_estimado, garantia_dias, ativo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $tempo_estimado, $garantia_dias, $ativo]);
            $_SESSION['mensagem'] = 'Serviço cadastrado com sucesso!';
        }
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao salvar serviço: ' . $e->getMessage();
    }
    header('Location: servicos.php');
    exit;
}

// Excluir serviço
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    try {
        $stmt = $db->prepare("DELETE FROM servicos WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['mensagem'] = 'Serviço excluído com sucesso!';
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao excluir serviço: ' . $e->getMessage();
    }
    header('Location: servicos.php');
    exit;
}

$servicos = $db->query("SELECT * FROM servicos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Serviços</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-toolbox"></i> Serviços</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServico" onclick="limparFormulario()">
                <i class="ph-bold ph-plus-circle"></i> Novo Serviço
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
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Tempo Estimado</th>
                                <th>Garantia</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($servicos as $s): ?>
                            <tr>
                                <td><?php echo $s['nome']; ?></td>
                                <td><?php echo $s['descricao']; ?></td>
                                <td><?php echo $s['tempo_estimado'] ? $s['tempo_estimado'] . ' min' : '-'; ?></td>
                                <td><?php echo $s['garantia_dias'] ? $s['garantia_dias'] . ' dias' : '-'; ?></td>
                                <td><?php echo $s['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editarServico(<?php echo json_encode($s); ?>)'>
                                        <i class="ph-bold ph-pencil-simple"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoServico(<?php echo $s['id']; ?>, '<?php echo addslashes($s['nome']); ?>')">
                                        <i class="ph-bold ph-trash"></i> Excluir
                                    </button>
                                 </tr ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </div>
                </div>
            </div>
</main>

</div>
    
    <!-- Modal Serviço -->
    <div class="modal fade" id="modalServico" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="servico_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Nome do Serviço *</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Descrição</label>
                            <textarea name="descricao" id="descricao" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Tempo Estimado (minutos)</label>
                                <input type="number" name="tempo_estimado" id="tempo_estimado" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Garantia (dias)</label>
                                <input type="number" name="garantia_dias" id="garantia_dias" class="form-control" value="30">
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="ativo" id="ativo" class="form-check-input" checked>
                            <label class="form-check-label">Serviço Ativo</label>
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
        
        function editarServico(s) {
            $('#modalTitulo').text('Editar Serviço');
            $('#servico_id').val(s.id);
            $('#nome').val(s.nome);
            $('#descricao').val(s.descricao);
            $('#tempo_estimado').val(s.tempo_estimado);
            $('#garantia_dias').val(s.garantia_dias);
            $('#ativo').prop('checked', s.ativo == 1);
            $('#modalServico').modal('show');
        }
        
        function limparFormulario() {
            $('#modalTitulo').text('Novo Serviço');
            $('#servico_id').val('');
            $('#nome').val('');
            $('#descricao').val('');
            $('#tempo_estimado').val('');
            $('#garantia_dias').val('30');
            $('#ativo').prop('checked', true);
        }
        
        function confirmarExclusaoServico(id, nome) {
            Swal.fire({
                title: 'Tem certeza?',
                text: `Deseja realmente excluir o serviço "${nome}"? Esta ação não pode ser desfeita!`,
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

<?php include '../../includes/footer.php'; ?>
