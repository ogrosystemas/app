<?php
// download_event_file.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = db();
$uid = $_SESSION['user_id'];

// Verificar se é admin
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userRole = $stmt->fetchColumn();
$isAdmin = ($userRole === 'admin');

$fileId = (int)($_GET['id'] ?? 0);

if (!$fileId) {
    die('Arquivo não encontrado');
}

// Buscar arquivo e verificar permissão
$stmt = $db->prepare("
    SELECT ef.*, e.id as event_id 
    FROM event_files ef
    JOIN events e ON e.id = ef.event_id
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ? AND a.status = 'confirmado'
    WHERE ef.id = ?
");
$stmt->execute([$uid, $fileId]);
$file = $stmt->fetch();

if (!$file) {
    die('Arquivo não encontrado');
}

// Verificar permissão
$hasAccess = false;

if ($isAdmin || $file['user_id'] == $uid) {
    $hasAccess = true;
} else {
    $checkStmt = $db->prepare("
        SELECT id FROM attendances 
        WHERE event_id = ? AND user_id = ? AND status = 'confirmado'
    ");
    $checkStmt->execute([$file['event_id'], $uid]);
    if ($checkStmt->fetch()) {
        $hasAccess = true;
    }
}

if (!$hasAccess) {
    die('Sem permissão para baixar este arquivo');
}

$filePath = __DIR__ . '/../' . $file['file_path'];
if (!file_exists($filePath)) {
    die('Arquivo não encontrado no servidor');
}

// Forçar download
header('Content-Type: ' . $file['file_type']);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . $file['file_size']);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Limpar buffer
ob_clean();
flush();
readfile($filePath);
exit;
?>