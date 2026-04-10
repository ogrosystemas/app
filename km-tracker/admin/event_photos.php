<?php
// admin/event_photos.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) {
    header('Location: ' . BASE_URL . '/admin/events.php');
    exit;
}

// Buscar evento
$eventStmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/admin/events.php');
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    verifyCsrf();
    $photoId = (int)($_POST['photo_id'] ?? 0);
    
    $photoStmt = $db->prepare("SELECT photo_path, photo_thumbnail FROM event_photos WHERE id = ?");
    $photoStmt->execute([$photoId]);
    $photo = $photoStmt->fetch();
    
    if ($photo) {
        // Remover arquivos
        $fullPath = __DIR__ . '/../' . $photo['photo_path'];
        $thumbPath = __DIR__ . '/../' . $photo['photo_thumbnail'];
        
        if (file_exists($fullPath)) unlink($fullPath);
        if (file_exists($thumbPath)) unlink($thumbPath);
        
        $stmt = $db->prepare("DELETE FROM event_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        
        $_SESSION['flash_success'] = 'Foto removida com sucesso!';
    }
    header('Location: ' . BASE_URL . '/admin/event_photos.php?event_id=' . $eventId);
    exit;
}

// Buscar fotos do evento
$photosStmt = $db->prepare("
    SELECT p.*, u.name as user_name 
    FROM event_photos p 
    JOIN users u ON u.id = p.user_id 
    WHERE p.event_id = ? 
    ORDER BY p.created_at DESC
");
$photosStmt->execute([$eventId]);
$photos = $photosStmt->fetchAll();

pageOpen("Fotos do Evento", "events", "Fotos - " . htmlspecialchars($event['title']));
?>

<style>
.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.photo-card {
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
}
.photo-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    cursor: pointer;
}
.photo-info {
    padding: 12px;
}
.photo-user {
    font-size: 0.8rem;
    color: var(--gold-light);
    margin-bottom: 4px;
}
.photo-date {
    font-size: 0.7rem;
    color: var(--text-muted);
}
.photo-desc {
    font-size: 0.75rem;
    margin-top: 8px;
    color: var(--text-secondary);
}
.btn-delete {
    margin-top: 8px;
    padding: 4px 12px;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.7rem;
}
.modal-photo {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 1000;
    cursor: pointer;
}
.modal-photo img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
}
.modal-photo-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 30px;
    cursor: pointer;
}
.actions-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Fotos do Evento</h2>
            <p><?= htmlspecialchars($event['title']) ?> - <?= date('d/m/Y', strtotime($event['event_date'])) ?></p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/admin/events.php" class="btn btn-ghost">← Voltar</a>
        </div>
    </div>
</div>

<div class="actions-bar">
    <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $eventId ?>" class="btn btn-accent" target="_blank">👁️ Ver galeria pública</a>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>

<?php if (empty($photos)): ?>
    <div class="card">
        <div class="card-body text-center">
            <p class="text-muted">Nenhuma foto enviada para este evento ainda.</p>
            <p class="text-muted">Os participantes podem enviar fotos apenas no dia do evento.</p>
        </div>
    </div>
<?php else: ?>
    <div class="photos-grid">
        <?php foreach ($photos as $photo): ?>
            <div class="photo-card">
                <img src="<?= BASE_URL . '/' . $photo['photo_path'] ?>" 
                     alt="Foto do evento" 
                     class="photo-img"
                     onclick="openModal('<?= BASE_URL . '/' . $photo['photo_path'] ?>')">
                <div class="photo-info">
                    <div class="photo-user">📸 <?= htmlspecialchars($photo['user_name']) ?></div>
                    <div class="photo-date">📅 <?= date('d/m/Y H:i', strtotime($photo['created_at'])) ?></div>
                    <?php if ($photo['description']): ?>
                        <div class="photo-desc"><?= htmlspecialchars($photo['description']) ?></div>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('Excluir esta foto?')">
                        <input type="hidden" name="action" value="delete_photo">
                        <input type="hidden" name="photo_id" value="<?= $photo['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn-delete">🗑️ Excluir</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="modal-photo" class="modal-photo" onclick="closeModal()">
    <span class="modal-photo-close">&times;</span>
    <img id="modal-img" src="">
</div>

<script>
function openModal(src) {
    document.getElementById('modal-img').src = src;
    document.getElementById('modal-photo').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('modal-photo').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>

<?php pageClose(); ?>