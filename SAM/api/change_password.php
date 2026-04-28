<?php
/**
 * api/change_password.php
 * Permite que o usuário logado troque a própria senha.
 * POST: current_password, new_password
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();
auth_require();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método não permitido']);
    exit;
}

$user    = auth_user();
$userId  = $user['id'];
$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';

// Validações básicas
if (!$current || !$new) {
    echo json_encode(['ok' => false, 'error' => 'Preencha todos os campos']);
    exit;
}

if (strlen($new) < 8) {
    echo json_encode(['ok' => false, 'error' => 'A nova senha precisa ter pelo menos 8 caracteres']);
    exit;
}

// Busca o hash atual do banco (não vem na sessão por segurança)
$row = db_one('SELECT password_hash FROM users WHERE id=?', [$userId]);
if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Usuário não encontrado']);
    exit;
}

// Verifica senha atual
if (!password_verify($current, $row['password_hash'])) {
    echo json_encode(['ok' => false, 'error' => 'Senha atual incorreta']);
    exit;
}

// Verifica se nova senha é diferente da atual
if (password_verify($new, $row['password_hash'])) {
    echo json_encode(['ok' => false, 'error' => 'A nova senha deve ser diferente da atual']);
    exit;
}

// Atualiza no banco
$newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
db_update('users', ['password_hash' => $newHash], 'id=?', [$userId]);

audit_log('CHANGE_PASSWORD', 'users', $userId);

echo json_encode(['ok' => true]);
