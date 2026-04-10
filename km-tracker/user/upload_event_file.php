<?php
// upload_event_file.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

verifyCsrf();

$eventId = (int)($_POST['event_id'] ?? 0);
if (!$eventId) {
    $_SESSION['flash_error'] = 'ID do evento não informado';
    header('Location: events.php');
    exit;
}

// Verificar se o usuário tem permissão para fazer upload
$checkStmt = $db->prepare("
    SELECT a.status, e.user_id as creator_id 
    FROM attendances a 
    JOIN events e ON e.id = a.event_id 
    WHERE a.event_id = ? AND a.user_id = ? AND a.status = 'confirmado'
");
$checkStmt->execute([$eventId, $uid]);
$isConfirmed = $checkStmt->fetch();

$isCreator = false;
if (!$isConfirmed && !$isAdmin) {
    $creatorStmt = $db->prepare("SELECT user_id FROM events WHERE id = ?");
    $creatorStmt->execute([$eventId]);
    $event = $creatorStmt->fetch();
    $isCreator = ($event && $event['user_id'] == $uid);
    
    if (!$isCreator) {
        $_SESSION['flash_error'] = 'Você não tem permissão para fazer upload neste evento';
        header('Location: events.php');
        exit;
    }
}

// Configuração de upload
$uploadDir = __DIR__ . '/../uploads/events/' . $eventId . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

if (!isset($_FILES['event_file']) || $_FILES['event_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Erro no upload do arquivo';
    header('Location: events.php');
    exit;
}

$file = $_FILES['event_file'];
$originalName = $file['name'];
$tmpPath = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = mime_content_type($tmpPath);

// Validações
if ($fileSize > $maxFileSize) {
    $_SESSION['flash_error'] = 'Arquivo muito grande. Máximo 10MB';
    header('Location: events.php');
    exit;
}

if (!in_array($fileType, $allowedTypes)) {
    $_SESSION['flash_error'] = 'Tipo de arquivo não permitido';
    header('Location: events.php');
    exit;
}

// Gerar nome único
$extension = pathinfo($originalName, PATHINFO_EXTENSION);
$uniqueName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$destination = $uploadDir . $uniqueName;

if (move_uploaded_file($tmpPath, $destination)) {
    // Salvar no banco
    $stmt = $db->prepare("
        INSERT INTO event_files (event_id, user_id, filename, original_name, file_path, file_size, file_type, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $eventId,
        $uid,
        $uniqueName,
        $originalName,
        'uploads/events/' . $eventId . '/' . $uniqueName,
        $fileSize,
        $fileType
    ]);
    
    if ($result) {
        $_SESSION['flash_message'] = 'Arquivo enviado com sucesso!';
    } else {
        $_SESSION['flash_error'] = 'Erro ao salvar no banco de dados';
    }
    header('Location: events.php');
    exit;
} else {
    $_SESSION['flash_error'] = 'Erro ao salvar o arquivo no servidor';
    header('Location: events.php');
    exit;
}
?>