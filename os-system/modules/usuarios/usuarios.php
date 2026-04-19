<?php
require_once '../../config/config.php';
checkAuth(['admin']);

// Processar exclusão de usuário
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Não permitir excluir o próprio usuário logado
    if($id == $_SESSION['usuario_id']) {
        $_SESSION['erro'] = 'Você não pode excluir seu próprio usuário!';
        header('Location: usuarios.php');
        exit;
    }
    
    try {
        $db->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
        $_SESSION['mensagem'] = 'Usuário excluído com sucesso!';
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao excluir usuário: ' . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $perfil = $_POST['perfil'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if($id) {
        if(!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE usuarios SET nome=?, email=?, senha=?, perfil=?, ativo=? WHERE id=?")->execute([$nome, $email, $senha, $perfil, $ativo, $id]);
        } else {
            $db->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, ativo=? WHERE id=?")->execute([$nome, $email, $perfil, $ativo, $id]);
        }
        $_SESSION['mensagem'] = 'Usuário atualizado com sucesso!';
    } else {
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (?,?,?,?,?)")->execute([$nome, $email, $senha, $perfil, $ativo]);
        $_SESSION['mensagem'] = 'Usuário cadastrado com sucesso!';
    }
    header('Location: usuarios.php');
    exit;
}

$usuarios = $db->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Usuários</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-user-gear"></i> Usuários</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limparFormulario()"><i class="ph-bold ph-plus-circle"></i> Novo Usuário</button>
        </div>
        
        <?php if($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($usuarios as $u): ?>
                        <tr>
                            <td><?php echo $u['nome']; ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td><span class="badge bg-<?php echo $u['perfil']=='admin'?'success':($u['perfil']=='gerente'?'primary':($u['perfil']=='caixa'?'warning':($u['perfil']=='mecanico'?'danger':'secondary'))); ?>"><?php echo ucfirst($u['perfil']); ?></span></td>
                            <td><?php echo $u['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='editarUsuario(<?php echo json_encode($u); ?>)'>
                                    <i class="ph-bold ph-pencil-simple"></i> Editar
                                </button>
                                <?php if($u['id'] != $_SESSION['usuario_id']): ?>
                                <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $u['id']; ?>)">
                                    <i class="ph-bold ph-trash"></i> Excluir
                                </button>
                                <?php endif; ?>
                             </tr ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
</main>

<div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST"><input type="hidden" name="id" id="usuario_id">
                <div class="modal-body">
                    <div class="mb-3"><label>Nome</label><input type="text" name="nome" id="nome" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" id="email" class="form-control" required></div>
                    <div class="mb-3"><label>Senha</label><input type="password" name="senha" id="senha" class="form-control"><small class="text-muted">Deixe em branco para manter</small></div>
                    <div class="mb-3"><label>Perfil</label><select name="perfil" id="perfil" class="form-select"><option value="admin">Admin</option><option value="gerente">Gerente</option><option value="mecanico">Mecânico</option><option value="caixa">Caixa</option><option value="vendedor">Vendedor</option></select></div>
                    <div class="form-check mb-3"><input type="checkbox" name="ativo" id="ativo" class="form-check-input" checked><label class="form-check-label">Ativo</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
            </form>
        </div></div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function editarUsuario(u) { 
            $('#usuario_id').val(u.id); 
            $('#nome').val(u.nome); 
            $('#email').val(u.email); 
            $('#perfil').val(u.perfil); 
            $('#ativo').prop('checked', u.ativo == 1); 
            $('#senha').val(''); 
            $('#modalUsuario').modal('show'); 
        }
        
        function limparFormulario() { 
            $('#usuario_id').val(''); 
            $('#nome').val(''); 
            $('#email').val(''); 
            $('#perfil').val('vendedor'); 
            $('#ativo').prop('checked', true); 
            $('#senha').val(''); 
        }
        
        function confirmarExclusao(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Este usuário será excluído permanentemente!",
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
