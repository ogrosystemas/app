<?php
// user/upload_photo.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

$db = db();
$me = currentUser();
$uid = $me['id'];
$eventId = (int)($_POST['event_id'] ?? $_GET['event_id'] ?? 0);

if (!$eventId) {
    $_SESSION['flash_error'] = 'ID do evento não informado';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Verificar se o evento existe e está ativo
$eventStmt = $db->prepare("
    SELECT e.*, 
           (SELECT status FROM attendances WHERE event_id = e.id AND user_id = ?) AS user_status
    FROM events e 
    WHERE e.id = ? AND e.active = 1
");
$eventStmt->execute([$uid, $eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    $_SESSION['flash_error'] = 'Evento não encontrado';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Verificar se o evento está acontecendo (data atual)
$eventDate = strtotime($event['event_date']);
$today = strtotime(date('Y-m-d'));
$isEventDay = ($eventDate == $today);
$isAdmin = ($me['role'] === 'admin');
$isParticipant = ($event['user_status'] === 'confirmado');

// Permitir upload apenas se:
// - É admin OU participante confirmado
// - E o evento está acontecendo hoje
if (!$isAdmin && !$isParticipant) {
    $_SESSION['flash_error'] = 'Você precisa estar confirmado no evento para enviar fotos';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

if (!$isEventDay && !$isAdmin) {
    $_SESSION['flash_error'] = 'O upload de fotos só é permitido no dia do evento';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    verifyCsrf();
    
    $uploadDir = __DIR__ . '/../uploads/event_photos/';
    $description = trim($_POST['description'] ?? '');
    
    // Criar diretório por evento
    $eventUploadDir = $uploadDir . $eventId . '/';
    if (!file_exists($eventUploadDir)) {
        mkdir($eventUploadDir, 0777, true);
    }
    
    $file = $_FILES['photo'];
    $fileName = time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
    $destination = $eventUploadDir . $fileName;
    
    // Validar arquivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['flash_error'] = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WEBP.';
        header('Location: ' . BASE_URL . '/user/upload_photo_form.php?event_id=' . $eventId);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        $_SESSION['flash_error'] = 'Arquivo muito grande. Máximo 5MB.';
        header('Location: ' . BASE_URL . '/user/upload_photo_form.php?event_id=' . $eventId);
        exit;
    }
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Criar thumbnail
        $thumbnail = createThumbnail($destination, $eventUploadDir . 'thumb_' . $fileName);
        
        $stmt = $db->prepare("
            INSERT INTO event_photos (event_id, user_id, photo_path, photo_thumbnail, description, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$eventId, $uid, 'uploads/event_photos/' . $eventId . '/' . $fileName, $thumbnail, $description]);
        
        $_SESSION['flash_success'] = 'Foto enviada com sucesso!';
    } else {
        $_SESSION['flash_error'] = 'Erro ao fazer upload da foto.';
    }
    
    header('Location: ' . BASE_URL . '/user/event_gallery.php?event_id=' . $eventId);
    exit;
}

function createThumbnail($source, $destination, $width = 200) {
    if (!file_exists($source)) return null;
    
    list($origWidth, $origHeight, $type) = getimagesize($source);
    
    $ratio = $origWidth / $origHeight;
    $height = $width / $ratio;
    
    $thumb = imagecreatetruecolor($width, $height);
    
    // Manter transparência para PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
    }
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImg = imagecreatefromjpeg($source);
            imagecopyresampled($thumb, $sourceImg, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
            imagejpeg($thumb, $destination, 80);
            break;
        case IMAGETYPE_PNG:
            $sourceImg = imagecreatefrompng($source);
            imagecopyresampled($thumb, $sourceImg, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
            imagepng($thumb, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            $sourceImg = imagecreatefromgif($source);
            imagecopyresampled($thumb, $sourceImg, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
            imagegif($thumb, $destination);
            break;
        case IMAGETYPE_WEBP:
            $sourceImg = imagecreatefromwebp($source);
            imagecopyresampled($thumb, $sourceImg, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
            imagewebp($thumb, $destination, 80);
            break;
        default:
            imagedestroy($thumb);
            return null;
    }
    
    imagedestroy($thumb);
    imagedestroy($sourceImg);
    
    return 'uploads/event_photos/' . basename(dirname($source)) . '/thumb_' . basename($source);
}
?>