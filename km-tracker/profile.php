<?php
// profile.php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$db = db();
$me = currentUser();
$uid = $me['id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = sanitizeString($_POST['name'] ?? '', 100);
        $email = sanitizeEmail($_POST['email'] ?? '');
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        
        if ($name && $email) {
            try {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, whatsapp = ? WHERE id = ?");
                $stmt->execute([$name, $email, $whatsapp ?: null, $uid]);
                $message = 'Perfil atualizado com sucesso!';
                
                // Atualizar sessão
                $_SESSION['user_name'] = $name;
            } catch (PDOException $e) {
                $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
            }
        } else {
            $error = 'Nome e e-mail são obrigatórios.';
        }
    } elseif ($action === 'update_moto') {
        $moto_modelo = sanitizeString($_POST['moto_modelo'] ?? '', 100);
        $moto_kml = sanitizeFloat($_POST['moto_kml'] ?? 0);
        $moto_tanque = sanitizeFloat($_POST['moto_tanque'] ?? 0);
        $gas_preco = sanitizeFloat($_POST['gas_preco'] ?? 0);
        
        try {
            $stmt = $db->prepare("UPDATE users SET moto_modelo = ?, moto_kml = ?, moto_tanque = ?, gas_preco = ? WHERE id = ?");
            $stmt->execute([$moto_modelo, $moto_kml, $moto_tanque, $gas_preco, $uid]);
            $message = 'Dados da moto atualizados com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar dados da moto: ' . $e->getMessage();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Senha atual incorreta.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Nova senha deve ter no mínimo 6 caracteres.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Confirmação de senha não confere.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $uid]);
            $message = 'Senha alterada com sucesso!';
        }
    }
}

// Buscar dados atualizados
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

pageOpen('Meu Perfil', 'profile', 'Meu Perfil');
?>

<style>
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    color: #a0a5b5;
    font-size: 0.85rem;
}
.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #2a2f3a;
    background: #1f2229;
    color: #eef0f8;
    font-size: 0.9rem;
}
.form-control:focus {
    outline: none;
    border-color: #f39c12;
}
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success {
    background: rgba(40, 167, 69, 0.15);
    border: 1px solid #28a745;
    color: #28a745;
}
.alert-error {
    background: rgba(220, 53, 69, 0.15);
    border: 1px solid #dc3545;
    color: #dc3545;
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.badge-success {
    background: #28a745;
    color: white;
}
.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}
.card {
    background: #14161c;
    border-radius: 12px;
    border: 1px solid #2a2f3a;
    overflow: hidden;
}
.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #2a2f3a;
}
.card-title {
    font-weight: 600;
    font-size: 1rem;
}
.card-body {
    padding: 20px;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    border: none;
}
.btn-primary {
    background: #f39c12;
    color: #0d0f14;
}
.btn-primary:hover {
    background: #f5b041;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-danger:hover {
    background: #c82333;
}
@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Meu Perfil</h2>
            <p>Gerencie suas informações pessoais</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Dados Pessoais -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">👤 Dados Pessoais</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <div class="form-group">
                    <label>Nome completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>📱 WhatsApp</label>
                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>" class="form-control" placeholder="5547999990000 (DDI+DDD+número)">
                    <small class="text-muted">Necessário para receber notificações do clube</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </form>
        </div>
    </div>

    <!-- Dados da Moto -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">🏍️ Dados da Moto</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_moto">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <div class="form-group">
                    <label>Marca/Modelo</label>
                    <input type="text" name="moto_modelo" value="<?= htmlspecialchars($user['moto_modelo'] ?? '') ?>" class="form-control" placeholder="Ex: Honda CB500X">
                </div>
                
                <div class="form-group">
                    <label>Consumo (km/L)</label>
                    <input type="number" step="0.1" name="moto_kml" value="<?= htmlspecialchars($user['moto_kml'] ?? '') ?>" class="form-control" placeholder="Ex: 25.5">
                </div>
                
                <div class="form-group">
                    <label>Capacidade do tanque (L)</label>
                    <input type="number" step="0.1" name="moto_tanque" value="<?= htmlspecialchars($user['moto_tanque'] ?? '') ?>" class="form-control" placeholder="Ex: 17.5">
                </div>
                
                <div class="form-group">
                    <label>Preço da gasolina (R$/L)</label>
                    <input type="number" step="0.01" name="gas_preco" value="<?= htmlspecialchars($user['gas_preco'] ?? '') ?>" class="form-control" placeholder="Ex: 5.79">
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar dados da moto</button>
            </form>
        </div>
    </div>

    <!-- Alterar Senha -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">🔒 Alterar Senha</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <div class="form-group">
                    <label>Senha atual</label>
                    <input type="password" name="current_password" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Nova senha</label>
                    <input type="password" name="new_password" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Confirmar nova senha</label>
                    <input type="password" name="confirm_password" required class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Alterar senha</button>
            </form>
        </div>
    </div>

    <!-- Informações da Conta -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">ℹ️ Informações da Conta</span>
        </div>
        <div class="card-body">
            <p><strong>Usuário desde:</strong> <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            <p><strong>Tipo de conta:</strong> <?= $user['role'] === 'admin' ? 'Administrador' : 'Integrante' ?></p>
            <p><strong>Status:</strong> <span class="badge badge-success">Ativo</span></p>
            <hr style="border-color: #2a2f3a; margin: 15px 0;">
            <a href="logout.php" class="btn btn-danger">🚪 Sair do sistema</a>
        </div>
    </div>
</div>

<?php pageClose(); ?>