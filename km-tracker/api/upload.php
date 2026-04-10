<?php
// api/upload.php — Upload de fotos e vídeos de eventos
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método não permitido']); exit;
}

$eventId = (int)($_POST['event_id'] ?? 0);
$caption = sanitizeString($_POST['caption'] ?? '', 500);
$me      = currentUser();
$db      = db();

if (!$eventId) {
    echo json_encode(['error' => 'event_id inválido']); exit;
}

// Verifica se evento existe
$ev = $db->prepare('SELECT id FROM events WHERE id=? AND active=1');
$ev->execute([$eventId]);
if (!$ev->fetch()) {
    echo json_encode(['error' => 'Evento não encontrado']); exit;
}

// Verifica se usuário tem presença (ou é admin)
if (!isAdmin()) {
    $att = $db->prepare('SELECT id FROM attendances WHERE event_id=? AND user_id=?');
    $att->execute([$eventId, $me['id']]);
    if (!$att->fetch()) {
        echo json_encode(['error' => 'Você não está registrado neste evento']); exit;
    }
}

if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'Nenhum arquivo enviado']); exit;
}

$file     = $_FILES['file'];
$mime     = mime_content_type($file['tmp_name']);
$maxBytes = UPLOAD_MAX_MB * 1024 * 1024;

if (!in_array($mime, UPLOAD_ALLOWED)) {
    echo json_encode(['error' => 'Tipo de arquivo não permitido. Use JPG, PNG, WebP, MP4 ou MOV']); exit;
}

if ($file['size'] > $maxBytes) {
    echo json_encode(['error' => 'Arquivo muito grande. Máximo ' . UPLOAD_MAX_MB . 'MB']); exit;
}

// Cria pasta do evento
$eventDir = UPLOAD_DIR . $eventId . '/';
if (!is_dir($eventDir)) @mkdir($eventDir, 0755, true);

// Nome único
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('m_', true) . '.' . $ext;
$destPath = $eventDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['error' => 'Falha ao salvar arquivo']); exit;
}

$type = str_starts_with($mime, 'video/') ? 'video' : 'photo';

// Gera thumbnail para imagens
if ($type === 'photo') {
    generateThumb($destPath, $eventDir . 'thumb_' . $filename, 400, 300);
}

// Salva no banco
$db->prepare('INSERT INTO event_media (event_id, user_id, filename, original_name, mime_type, size_bytes, type, caption)
              VALUES (?,?,?,?,?,?,?,?)')
   ->execute([$eventId, $me['id'], $filename, $file['name'], $mime, $file['size'], $type, $caption ?: null]);

$mediaId = $db->lastInsertId();

echo json_encode([
    'ok'        => true,
    'id'        => $mediaId,
    'filename'  => $filename,
    'url'       => UPLOAD_URL . $eventId . '/' . $filename,
    'thumb_url' => $type === 'photo' ? UPLOAD_URL . $eventId . '/thumb_' . $filename : null,
    'type'      => $type,
]);

// ── Helpers ──────────────────────────────────────────────────

function generateThumb(string $src, string $dest, int $maxW, int $maxH): void
{
    if (!extension_loaded('gd')) return;

    $info = @getimagesize($src);
    if (!$info) return;

    [$w, $h, $type] = [$info[0], $info[1], $info[2]];

    $img = match($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        default        => null,
    };
    if (!$img) return;

    // Calcula dimensões mantendo proporção
    $ratio  = min($maxW / $w, $maxH / $h);
    $newW   = (int)($w * $ratio);
    $newH   = (int)($h * $ratio);
    $thumb  = imagecreatetruecolor($newW, $newH);

    // Fundo branco para PNG transparente
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);

    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagejpeg($thumb, $dest, 85);
    imagedestroy($img);
    imagedestroy($thumb);
}
