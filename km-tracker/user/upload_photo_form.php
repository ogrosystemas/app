<?php
// user/upload_photo_form.php — Upload de foto para evento (Google Drive)
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db      = db();
$me      = currentUser();
$uid     = $me['id'];
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) {
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

$eventStmt = $db->prepare("SELECT e.*, a.status as user_status FROM events e LEFT JOIN attendances a ON a.event_id=e.id AND a.user_id=? WHERE e.id=? AND e.active=1");
$eventStmt->execute([$uid, $eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

$isAdmin = ($me['role'] === 'admin');
$eventDate = strtotime($event['event_date']);
$today     = strtotime(date('Y-m-d'));
$isEventDay = ($eventDate == $today);
if (!$isAdmin && !$isEventDay) {
    $_SESSION['flash_error'] = 'Upload permitido apenas no dia do evento.';
    header('Location: ' . BASE_URL . '/user/event_gallery.php?event_id=' . $eventId);
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!empty($_FILES['photo']['tmp_name'])) {
        $file     = $_FILES['photo'];
        $maxSize  = 10 * 1024 * 1024; // 10MB
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($file['size'] > $maxSize) {
            $error = 'Arquivo muito grande. Máximo 10MB.';
        } elseif (!in_array($mimeType, $allowed)) {
            $error = 'Formato não permitido. Use JPG, PNG, WebP ou GIF.';
        } else {
            try {
                require_once __DIR__ . '/../includes/google_drive.php';
                $drive = new GoogleDrive();

                $ano      = date('Y', strtotime($event['event_date']));
                $nomeEvento = preg_replace('/[^a-zA-Z0-9 áéíóúâêîôûãõàèìòùç]/u', '', $event['title']);
                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $fileName = date('Ymd_His') . '_' . $uid . '.' . $ext;

                $driveData = $drive->uploadFotoEvento($file['tmp_name'], $fileName, $ano, $nomeEvento);

                $desc = trim($_POST['description'] ?? '');
                $fileId = $driveData['id'];
                $db->prepare("INSERT INTO event_photos (event_id, user_id, photo_path, drive_file_id, drive_view_url, drive_thumb_url, description, created_at) VALUES (?,?,?,?,?,?,?,NOW())")
                   ->execute([
                       $eventId,
                       $uid,
                       'drive:' . $fileId,           // photo_path indica origem Drive
                       $fileId,                       // drive_file_id — ID puro do arquivo
                       GoogleDrive::viewUrl($fileId), // URL direta para lightbox
                       GoogleDrive::thumbnailUrl($fileId), // URL de thumbnail
                       $desc ?: null,
                   ]);

                $_SESSION['flash_success'] = 'Foto enviada com sucesso!';
                header('Location: ' . BASE_URL . '/user/event_gallery.php?event_id=' . $eventId);
                exit;

            } catch (Throwable $e) {
                $error = 'Erro ao enviar foto: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Selecione uma foto.';
    }
}

pageOpen('Upload de Foto', 'events', '📸 Enviar Foto');
?>

<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>📸 Enviar Foto</h2><p><?= htmlspecialchars($event['title']) ?></p></div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $eventId ?>" class="btn btn-ghost">← Voltar</a>
        </div>
    </div>
</div>

<div style="max-width:520px;margin:0 auto">
    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div style="margin-bottom:20px">
                    <label style="display:block;font-size:.8rem;font-weight:600;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">📷 Foto *</label>
                    <input type="file" name="photo" accept="image/*" required
                           style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);font-size:.85rem;cursor:pointer">
                    <small style="font-size:.72rem;color:var(--text-dim);margin-top:4px;display:block">JPG, PNG, WebP ou GIF — máximo 10MB</small>
                </div>

                <div style="margin-bottom:20px">
                    <label style="display:block;font-size:.8rem;font-weight:600;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em">💬 Descrição (opcional)</label>
                    <input type="text" name="description" maxlength="255" placeholder="Ex: Chegada em Erechim"
                           style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text);font-size:.85rem">
                </div>

                <div style="background:#f39c1215;border:1px solid #f39c1240;border-radius:8px;padding:12px 16px;margin-bottom:24px;font-size:.78rem;color:#f5b041;line-height:1.5">
                    📁 A foto será salva no Google Drive em:<br>
                    <strong>Mutantes KM / Eventos / <?= date('Y', strtotime($event['event_date'])) ?> / <?= htmlspecialchars($event['title']) ?></strong>
                </div>

                <div style="text-align:center">
                    <button type="submit" class="btn btn-primary" style="min-width:200px;padding:12px 32px;font-size:.95rem;font-weight:700">
                        📤 Enviar Foto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php pageClose(); ?>
