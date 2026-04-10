<?php
require_once '../../config/config.php';
checkAuth();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $cpf_cnpj = $_POST['cpf_cnpj'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    
    try {
        if($id) {
            $stmt = $db->prepare("UPDATE clientes SET nome=?, cpf_cnpj=?, telefone=?, email=?, endereco=? WHERE id=?");
            $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco, $id]);
            $_SESSION['mensagem'] = 'Cliente atualizado com sucesso!';
        } else {
            $stmt = $db->prepare("INSERT INTO clientes (nome, cpf_cnpj, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $cpf_cnpj, $telefone, $email, $endereco]);
            $_SESSION['mensagem'] = 'Cliente cadastrado com sucesso!';
        }
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao salvar cliente: ' . $e->getMessage();
    }
    header('Location: clientes.php');
    exit;
}

// Excluir cliente
if(isset($_GET['excluir_cliente'])) {
    $id = $_GET['excluir_cliente'];
    try {
        // Excluir motos primeiro
        $db->prepare("DELETE FROM motos WHERE cliente_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM clientes WHERE id = ?")->execute([$id]);
        $_SESSION['mensagem'] = 'Cliente excluído com sucesso!';
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao excluir cliente: ' . $e->getMessage();
    }
    header('Location: clientes.php');
    exit;
}

$clientes = $db->query("SELECT * FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Clientes</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-users"></i> Clientes</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="limparFormulario()">
                <i class="ph-bold ph-plus-circle"></i> Novo Cliente
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
                                <th>CPF/CNPJ</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Motos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($clientes as $cliente): 
                                $stmt_m = $db->prepare("SELECT * FROM motos WHERE cliente_id = ?"); $stmt_m->execute([$cliente['id']]); $motos = $stmt_m->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <td><?php echo $cliente['nome']; ?></td>
                                <td><?php echo $cliente['cpf_cnpj']; ?></td>
                                <td><?php echo $cliente['telefone']; ?></td>
                                <td><?php echo $cliente['email']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verMotos(<?php echo $cliente['id']; ?>, '<?php echo addslashes($cliente['nome']); ?>')">
                                        <i class="ph-bold ph-motorcycle"></i> <?php echo count($motos); ?> moto(s)
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)">
                                        <i class="ph-bold ph-pencil-simple"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="abrirModalMoto(<?php echo $cliente['id']; ?>)">
                                        <i class="ph-bold ph-plus-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoCliente(<?php echo $cliente['id']; ?>)">
                                        <i class="ph-bold ph-trash"></i>
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
    
    <!-- Modal Cliente -->
    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="cliente_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Nome *</label>
                                <input type="text" name="nome" id="nome" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>CPF/CNPJ</label>
                                <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Telefone</label>
                                <input type="text" name="telefone" id="telefone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Endereço</label>
                                <textarea name="endereco" id="endereco" class="form-control" rows="2"></textarea>
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
    
    <!-- Modal Lista de Motos -->
    <div class="modal fade" id="modalMotos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Motos do Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="listaMotos"></div>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Moto -->
    <div class="modal fade" id="modalAdicionarMoto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Moto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="salvar_moto.php" method="POST">
                    <input type="hidden" name="cliente_id" id="moto_cliente_id">
                    <div class="modal-body">
                        <div class="mb-3"><label>Placa *</label><input type="text" name="placa" class="form-control" required></div>
                        <div class="mb-3"><label>Modelo *</label><input type="text" name="modelo" class="form-control" required></div>
                        <div class="mb-3"><label>Marca</label><input type="text" name="marca" class="form-control"></div>
                        <div class="mb-3"><label>Ano</label><input type="number" name="ano" class="form-control"></div>
                        <div class="mb-3"><label>Cor</label><input type="text" name="cor" class="form-control"></div>
                        <div class="mb-3"><label>Chassi</label><input type="text" name="chassi" class="form-control"></div>
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
        function editarCliente(cliente) {
            $('#modalTitulo').text('Editar Cliente');
            $('#cliente_id').val(cliente.id);
            $('#nome').val(cliente.nome);
            $('#cpf_cnpj').val(cliente.cpf_cnpj);
            $('#telefone').val(cliente.telefone);
            $('#email').val(cliente.email);
            $('#endereco').val(cliente.endereco);
            $('#modalCliente').modal('show');
        }
        
        function limparFormulario() {
            $('#modalTitulo').text('Novo Cliente');
            $('#cliente_id').val('');
            $('#nome').val('');
            $('#cpf_cnpj').val('');
            $('#telefone').val('');
            $('#email').val('');
            $('#endereco').val('');
        }
        
        function abrirModalMoto(cliente_id) {
            $('#moto_cliente_id').val(cliente_id);
            $('#modalAdicionarMoto').modal('show');
        }
        
        function verMotos(cliente_id, nome_cliente) {
            $.ajax({
                url: '../../api/motos.php',
                method: 'GET',
                data: { cliente_id: cliente_id },
                success: function(motos) {
                    let html = `<h6>Cliente: ${nome_cliente}</h6><hr>`;
                    if(motos.length === 0) {
                        html += '<p class="text-muted">Nenhuma moto cadastrada</p>';
                    } else {
                        html += '<div class="list-group">';
                        motos.forEach(moto => {
                            html += `
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${moto.modelo}</strong><br>
                                            Placa: ${moto.placa}<br>
                                            Marca: ${moto.marca || '-'} | Ano: ${moto.ano || '-'}
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoMoto(${moto.id})">
                                                <i class="ph-bold ph-trash"></i> Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    $('#listaMotos').html(html);
                    $('#modalMotos').modal('show');
                }
            });
        }
        
        function confirmarExclusaoCliente(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Este cliente e todas as suas motos serão excluídos permanentemente!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?excluir_cliente=' + id;
                }
            });
        }
        
        function confirmarExclusaoMoto(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta moto será removida permanentemente!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'excluir_moto.php?id=' + id;
                }
            });
        }
    </script>

<?php include '../../includes/footer.php'; ?>
