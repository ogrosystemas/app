<?php
// admin/users.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$year = (int)($_GET['year'] ?? date('Y'));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $active = isset($_POST['active']) ? 1 : 0;
        
        // Dados da moto
        $moto_modelo = trim($_POST['moto_modelo'] ?? '');
        $moto_kml = (float)($_POST['moto_kml'] ?? 0);
        $moto_tanque = (float)($_POST['moto_tanque'] ?? 0);
        $gas_preco = (float)($_POST['gas_preco'] ?? 0);
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Preencha todos os campos obrigatórios.';
        } else {
            // Verificar se email já existe
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $_SESSION['flash_error'] = 'Este e-mail já está cadastrado.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (name, email, password, role, active, whatsapp, moto_modelo, moto_kml, moto_tanque, gas_preco, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $email, $hashedPassword, $role, $active, $whatsapp ?: null, $moto_modelo, $moto_kml, $moto_tanque, $gas_preco]);
                $_SESSION['flash_success'] = 'Usuário criado com sucesso!';
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    } elseif ($action === 'edit_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $active = isset($_POST['active']) ? 1 : 0;
        
        // Dados da moto
        $moto_modelo = trim($_POST['moto_modelo'] ?? '');
        $moto_kml = (float)($_POST['moto_kml'] ?? 0);
        $moto_tanque = (float)($_POST['moto_tanque'] ?? 0);
        $gas_preco = (float)($_POST['gas_preco'] ?? 0);
        $whatsapp_edit = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['flash_error'] = 'Preencha os campos obrigatórios.';
        } else {
            // Verificar se email já existe para outro usuário
            $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $userId]);
            if ($check->fetch()) {
                $_SESSION['flash_error'] = 'Este e-mail já está cadastrado para outro usuário.';
            } else {
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users SET name = ?, email = ?, password = ?, role = ?, active = ?, whatsapp = ?,
                        moto_modelo = ?, moto_kml = ?, moto_tanque = ?, gas_preco = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $hashedPassword, $role, $active, $whatsapp_edit ?: null, $moto_modelo, $moto_kml, $moto_tanque, $gas_preco, $userId]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users SET name = ?, email = ?, role = ?, active = ?, whatsapp = ?,
                        moto_modelo = ?, moto_kml = ?, moto_tanque = ?, gas_preco = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $role, $active, $whatsapp_edit ?: null, $moto_modelo, $moto_kml, $moto_tanque, $gas_preco, $userId]);
                }
                $_SESSION['flash_success'] = 'Usuário atualizado com sucesso!';
            }
        }
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    } elseif ($action === 'toggle_active') {
        $stmt = $db->prepare("UPDATE users SET active = NOT active WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash_success'] = 'Status do usuário alterado.';
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    } elseif ($action === 'toggle_role') {
        $stmt = $db->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash_success'] = 'Permissão do usuário alterada.';
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }
}

// Montar WHERE dinâmico
$whereConditions = [];
$params = [];

if ($roleFilter !== 'all') {
    $whereConditions[] = "role = ?";
    $params[] = $roleFilter;
}
if ($statusFilter === 'active') {
    $whereConditions[] = "active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "active = 0";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Buscar usuários
$total = $db->prepare("SELECT COUNT(*) FROM users $whereClause");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

$usersStmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT a.id) AS total_presencas,
           COALESCE(SUM(e.km_awarded + a.km_extra), 0) AS total_km
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = ?
    $whereClause
    GROUP BY u.id
    ORDER BY u.name ASC
    LIMIT ? OFFSET ?
");
$paramsWithYear = array_merge([$year], $params, [$perPage, $offset]);
$usersStmt->execute($paramsWithYear);
$users = $usersStmt->fetchAll();

// Estatísticas para cards
$totalAdmins = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1")->fetchColumn();
$totalActiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE active = 1")->fetchColumn();
$totalInactiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE active = 0")->fetchColumn();

pageOpen("Integrantes", "users", "Integrantes");
?>

<style>
/* Filtros */
.filter-bar {
    background: #14161c;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid #2a2f3a;
}
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 180px;
}
.filter-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #6e7485;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.filter-select {
    padding: 10px 32px 10px 12px;
    border-radius: 8px;
    border: 1px solid #2a2f3a;
    background: white;
    color: #0d0f14;
    font-size: 0.85rem;
    font-family: inherit;
    font-weight: 500;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236e7485' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}
.filter-select:hover {
    border-color: #f39c12;
}
.filter-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-left: auto;
}
.btn-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #2a2f3a;
    font-family: inherit;
    text-decoration: none;
    background: white;
    color: #0d0f14;
}
.btn-filter:hover {
    background: #f5f5f5;
    transform: translateY(-1px);
    border-color: #f39c12;
}
.btn-filter-clear {
    background: white;
    color: #dc3545;
    border-color: #dc3545;
}
.btn-filter-clear:hover {
    background: #dc3545;
    color: white;
}

/* Cards de estatísticas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: #14161c;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #2a2f3a;
    transition: all 0.3s ease;
}
.stat-card:nth-child(1) { border-top: 3px solid #f39c12; }
.stat-card:nth-child(2) { border-top: 3px solid #28a745; }
.stat-card:nth-child(3) { border-top: 3px solid #7b9fff; }
.stat-card:nth-child(4) { border-top: 3px solid #dc3545; }
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #f5b041;
}
.stat-text {
    font-size: 0.7rem;
    color: #6e7485;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 8px;
}

/* Card da tabela */
.card-table {
    background: #14161c;
    border-radius: 12px;
    border: 1px solid #2a2f3a;
    overflow: hidden;
}
.table-responsive {
    overflow-x: auto;
}
.users-table {
    width: 100%;
    border-collapse: collapse;
}
.users-table th, .users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #2a2f3a;
}
.users-table th {
    color: #6e7485;
    font-weight: 500;
    font-size: 0.7rem;
    text-transform: uppercase;
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.badge-admin {
    background: #f39c12;
    color: #0d0f14;
}
.badge-user {
    background: #7b9fff;
    color: #0d0f14;
}
.badge-active {
    background: #28a745;
    color: white;
}
.badge-inactive {
    background: #dc3545;
    color: white;
}
.text-gold {
    color: #f5b041;
    font-weight: 600;
}
.btn-sm {
    padding: 4px 12px;
    font-size: 0.7rem;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
.btn-ghost {
    background: transparent;
    border: 1px solid #2a2f3a;
    color: #a0a5b5;
    padding: 8px 16px;
    font-size: 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-ghost:hover {
    background: #1f2229;
    color: #eef0f8;
}
.btn-danger {
    background: #dc3545;
    color: white;
    padding: 8px 16px;
    font-size: 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-danger:hover {
    background: #c82333;
}
.btn-success {
    background: #28a745;
    color: white;
    padding: 8px 16px;
    font-size: 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-success:hover {
    background: #218838;
}
.btn-accent {
    background: #7b9fff;
    color: #0d0f14;
    padding: 8px 16px;
    font-size: 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-accent:hover {
    background: #6a8fe8;
}
.btn-primary {
    background: #f39c12;
    color: #0d0f14;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-primary:hover {
    background: #f5b041;
}
.flex {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid #2a2f3a;
}
.pagination a {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: #a0a5b5;
    background: #1f2229;
    border: 1px solid #2a2f3a;
}
.pagination a.current {
    background: #f39c12;
    color: #0d0f14;
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

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
.modal-overlay.open {
    display: flex;
}
.modal {
    background: #14161c;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    border: 1px solid #2a2f3a;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #2a2f3a;
    background: #1a1d24;
}
.modal-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #f5b041;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: #a0a5b5;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}
.modal-close:hover {
    background: #1f2229;
    color: #eef0f8;
}
.modal form {
    padding: 24px;
}
.form-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6e7485;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #2a2f3a;
    background: #1f2229;
    color: #eef0f8;
    font-family: inherit;
    font-size: 0.85rem;
}
.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #f39c12;
}
.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
}
.form-check input {
    width: auto;
}
.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 8px;
    border-top: 1px solid #2a2f3a;
}
.text-muted {
    color: #6e7485;
    font-size: 0.7rem;
}
.section-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #f5b041;
    margin: 16px 0 12px 0;
    padding-bottom: 6px;
    border-bottom: 1px solid #2a2f3a;
}
@media (max-width: 1000px) {
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-group {
        width: 100%;
    }
    .filter-select {
        width: 100%;
    }
    .filter-actions {
        margin-left: 0;
        flex-direction: column;
    }
    .btn-filter {
        justify-content: center;
        width: 100%;
    }
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
@media (max-width: 480px) {
    .grid-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Gerencie os integrantes</h2>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="abrirModalCadastro()">+ Novo Usuário</button>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<!-- Cards de estatísticas -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-number"><?= $totalCount ?></div>
        <div class="stat-text">Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totalActiveUsers ?></div>
        <div class="stat-text">Ativos</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totalInactiveUsers ?></div>
        <div class="stat-text">Inativos</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totalAdmins ?></div>
        <div class="stat-text">Administradores</div>
    </div>
</div>

<!-- Filtros -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label class="filter-label">Perfil</label>
            <select name="role" onchange="this.form.submit()" class="filter-select">
                <option value="all" <?= $roleFilter == 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="admin" <?= $roleFilter == 'admin' ? 'selected' : '' ?>>Administradores</option>
                <option value="user" <?= $roleFilter == 'user' ? 'selected' : '' ?>>Integrantes</option>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Status</label>
            <select name="status" onchange="this.form.submit()" class="filter-select">
                <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Ativos</option>
                <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inativos</option>
            </select>
        </div>
        <div class="filter-actions">
            <?php if ($roleFilter != 'all' || $statusFilter != 'all'): ?>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn-filter btn-filter-clear">Limpar filtros</a>
            <?php endif; ?>
            <button type="submit" class="btn-filter">Aplicar filtros</button>
        </div>
    </form>
</div>

<!-- Tabela de usuários -->
<div class="card-table">
    <div class="table-responsive">
        <table class="users-table">
            <thead>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Moto</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Presenças</th>
                    <th>KM Total</th>
                    <th>Ações</th>
                </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <td><strong><?= htmlspecialchars($user['name'] ?? '') ?></strong>
                    <td><?= htmlspecialchars($user['email'] ?? '') ?>
                    <td>
                        <?php if (!empty($user['moto_modelo'])): ?>
                            <span title="Consumo: <?= $user['moto_kml'] ?? 0 ?> km/L | Tanque: <?= $user['moto_tanque'] ?? 0 ?> L">
                                🏍️ <?= htmlspecialchars($user['moto_modelo']) ?>
                            </span>
                            <div style="font-size: 0.65rem; color: #6e7485;">
                                <?= $user['moto_kml'] ?? 0 ?> km/L | <?= $user['moto_tanque'] ?? 0 ?> L
                            </div>
                        <?php else: ?>
                            <span class="text-muted" style="font-size: 0.7rem;">—</span>
                        <?php endif; ?>
                    <td>
                        <span class="badge <?= ($user['role'] ?? 'user') === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                            <?= ($user['role'] ?? 'user') === 'admin' ? 'Administrador' : 'Integrante' ?>
                        </span>
                    <td>
                        <span class="badge <?= ($user['active'] ?? 1) ? 'badge-active' : 'badge-inactive' ?>">
                            <?= ($user['active'] ?? 1) ? 'Ativo' : 'Inativo' ?>
                        </span>
                    <td><?= (int)($user['total_presencas'] ?? 0) ?>
                    <td class="text-gold"><?= number_format($user['total_km'] ?? 0, 0, ',', '.') ?> km
                    <td style="white-space: nowrap;">
                        <div class="flex">
                            <button class="btn-sm btn-ghost" onclick="editarUsuario(<?= htmlspecialchars(json_encode($user)) ?>)">Editar</button>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button class="btn-sm <?= ($user['active'] ?? 1) ? 'btn-danger' : 'btn-success' ?>">
                                    <?= ($user['active'] ?? 1) ? 'Desativar' : 'Ativar' ?>
                                </button>
                            </form>
                            <?php if (($user['role'] ?? 'user') !== 'admin'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button class="btn-sm btn-accent">Tornar Admin</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-muted text-center" style="padding:28px">Nenhum usuário encontrado.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&year=<?= $year ?>&role=<?= $roleFilter ?>&status=<?= $statusFilter ?>" class="<?= $p == $page ? 'current' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL CADASTRO/EDIÇÃO DE USUÁRIO -->
<div class="modal-overlay" id="modal-user">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="modal-title">Novo Usuário</span>
            <button class="modal-close" onclick="fecharModal()">✕</button>
        </div>
        <form method="POST" id="form-user">
            <input type="hidden" name="action" id="form-action" value="create_user">
            <input type="hidden" name="user_id" id="form-user-id" value="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="name" id="form-name" required>
                </div>
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" name="email" id="form-email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="grid-column:span 2">
                    <label>📱 WhatsApp</label>
                    <input type="text" name="whatsapp" id="form-whatsapp" placeholder="Ex: 5547999990000 (DDI+DDD+número)">
                    <small class="text-muted">Somente números com DDI. Ex: 5547999990000</small>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="password" id="form-password" placeholder="Deixe em branco para manter a mesma">
                    <small class="text-muted">Mínimo 6 caracteres</small>
                </div>
                <div class="form-group">
                    <label>Perfil</label>
                    <select name="role" id="form-role">
                        <option value="user">Usuário</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
            
            <div class="section-title">🏍️ Dados da Moto</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Marca / Modelo</label>
                    <input type="text" name="moto_modelo" id="form-moto-modelo" placeholder="Ex: Honda CB 500X">
                </div>
                <div class="form-group">
                    <label>Consumo (km/L)</label>
                    <input type="number" name="moto_kml" id="form-moto-kml" step="0.1" value="0" placeholder="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Capacidade do tanque (L)</label>
                    <input type="number" name="moto_tanque" id="form-moto-tanque" step="0.1" value="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Preço da gasolina (R$/L)</label>
                    <input type="number" name="gas_preco" id="form-gas-preco" step="0.01" value="0" placeholder="0">
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" name="active" id="form-active" value="1" checked>
                <label for="form-active">Usuário ativo</label>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-danger" onclick="fecharModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCadastro() {
    document.getElementById('modal-title').textContent = 'Novo Usuário';
    document.getElementById('form-action').value = 'create_user';
    document.getElementById('form-user-id').value = '';
    document.getElementById('form-name').value = '';
    document.getElementById('form-email').value = '';
    document.getElementById('form-whatsapp').value = '';
    document.getElementById('form-password').value = '';
    document.getElementById('form-role').value = 'user';
    document.getElementById('form-moto-modelo').value = '';
    document.getElementById('form-moto-kml').value = '0';
    document.getElementById('form-moto-tanque').value = '0';
    document.getElementById('form-gas-preco').value = '0';
    document.getElementById('form-active').checked = true;
    document.getElementById('modal-user').classList.add('open');
}

function editarUsuario(user) {
    document.getElementById('modal-title').textContent = 'Editar Usuário';
    document.getElementById('form-action').value = 'edit_user';
    document.getElementById('form-user-id').value = user.id;
    document.getElementById('form-name').value = user.name || '';
    document.getElementById('form-email').value = user.email || '';
    document.getElementById('form-whatsapp').value = user.whatsapp || '';
    document.getElementById('form-password').value = '';
    document.getElementById('form-role').value = user.role || 'user';
    document.getElementById('form-moto-modelo').value = user.moto_modelo || '';
    document.getElementById('form-moto-kml').value = user.moto_kml || 0;
    document.getElementById('form-moto-tanque').value = user.moto_tanque || 0;
    document.getElementById('form-gas-preco').value = user.gas_preco || 0;
    document.getElementById('form-active').checked = user.active == 1;
    document.getElementById('modal-user').classList.add('open');
}

function fecharModal() {
    document.getElementById('modal-user').classList.remove('open');
}

// Fechar modal ao clicar fora
document.getElementById('modal-user').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>

<?php pageClose(); ?>