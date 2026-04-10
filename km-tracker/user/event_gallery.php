<?php
// user/event_gallery.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$me = currentUser();
$uid = $me['id'];
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) {
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Buscar evento
$eventStmt = $db->prepare("
    SELECT e.*, 
           a.status as user_status,
           CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END AS tem_interesse
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ?
    WHERE e.id = ? AND e.active = 1
");
$eventStmt->execute([$uid, $eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Verificar se usuário pode ver a galeria (participante confirmado ou admin)
$isAdmin = ($me['role'] === 'admin');
$canView = ($isAdmin || $event['user_status'] == 'confirmado');

if (!$canView) {
    $_SESSION['flash_error'] = 'Apenas participantes confirmados podem ver a galeria do evento.';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

// Buscar fotos do evento
$photosStmt = $db->prepare("
    SELECT p.*, u.name as user_name, u.id as user_id
    FROM event_photos p 
    JOIN users u ON u.id = p.user_id 
    WHERE p.event_id = ? 
    ORDER BY p.created_at DESC
");
$photosStmt->execute([$eventId]);
$photos = $photosStmt->fetchAll();

pageOpen("Galeria", "events", "Galeria - " . htmlspecialchars($event['title']));
?>

<style>
.event-header {
    background: linear-gradient(135deg, var(--bg-card), #1a0f05);
    border: 1px solid var(--gold);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}
.event-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--gold-light);
    margin-bottom: 8px;
}
.event-date {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.event-location {
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.event-stats {
    display: flex;
    gap: 20px;
    margin-top: 16px;
    flex-wrap: wrap;
}
.event-stat {
    background: var(--bg-card2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.75rem;
}
.event-stat span {
    color: var(--gold-light);
    font-weight: 600;
}
.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.photo-card {
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.photo-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.photo-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    cursor: pointer;
    background: var(--bg-card2);
}
.photo-info {
    padding: 12px;
}
.photo-user {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.user-avatar-sm {
    width: 28px;
    height: 28px;
    background: rgba(243, 156, 18, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: var(--gold-light);
    font-size: 0.7rem;
}
.user-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--gold-light);
}
.photo-date {
    font-size: 0.65rem;
    color: var(--text-muted);
    margin-left: auto;
}
.photo-desc {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 8px;
    line-height: 1.4;
}
.modal-photo {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.95);
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
    object-fit: contain;
}
.modal-photo-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 35px;
    cursor: pointer;
    transition: color 0.2s;
    z-index: 1001;
}
.modal-photo-close:hover {
    color: var(--gold);
}
.modal-info {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    text-align: center;
    color: white;
    background: rgba(0,0,0,0.7);
    padding: 12px;
    font-size: 0.85rem;
}
.modal-info .user {
    color: var(--gold-light);
    font-weight: 600;
}
.btn-upload {
    background: var(--gold);
    color: #0d0f14;
    border: none;
    padding: 10px 24px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.btn-upload:hover {
    background: var(--gold-light);
    transform: translateY(-1px);
}
.empty-gallery {
    text-align: center;
    padding: 60px 20px;
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border);
}
.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
}
.empty-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
}
.empty-text {
    color: var(--text-muted);
    margin-bottom: 20px;
}
@media (max-width: 768px) {
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 12px;
    }
    .photo-img {
        height: 180px;
    }
}
@media (max-width: 480px) {
    .photos-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>📸 Galeria do Evento</h2>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/user/events.php" class="btn btn-ghost">← Voltar para eventos</a>
        </div>
    </div>
</div>

<div class="event-header">
    <div class="event-title"><?= htmlspecialchars($event['title']) ?></div>
    <div class="event-date">📅 <?= date('d/m/Y', strtotime($event['event_date'])) ?></div>
    <?php if ($event['location']): ?>
        <div class="event-location">📍 <?= htmlspecialchars($event['location']) ?></div>
    <?php endif; ?>
    <div class="event-stats">
        <div class="event-stat">📸 <span><?= count($photos) ?></span> fotos</div>
        <div class="event-stat">✅ <span><?= $event['user_status'] == 'confirmado' ? 'Confirmado' : 'Pendente' ?></span></div>
        <?php if ($event['km_awarded']): ?>
            <div class="event-stat">🏍️ <span><?= number_format($event['km_awarded'], 0, ',', '.') ?> km</span></div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<?php
$eventDate = strtotime($event['event_date']);
$today = strtotime(date('Y-m-d'));
$isEventDay = ($eventDate == $today);
$canUpload = ($isEventDay && $event['user_status'] == 'confirmado');

if ($canUpload || $isAdmin):
?>
    <div style="margin-bottom: 24px; text-align: right;">
        <a href="<?= BASE_URL ?>/user/upload_photo_form.php?event_id=<?= $eventId ?>" class="btn-upload">
            📤 Enviar foto
        </a>
    </div>
<?php endif; ?>

<?php if (empty($photos)): ?>
    <div class="empty-gallery">
        <div class="empty-icon">📸</div>
        <div class="empty-title">Nenhuma foto ainda</div>
        <div class="empty-text">Seja o primeiro a compartilhar um momento deste evento!</div>
        <?php if ($canUpload || $isAdmin): ?>
            <a href="<?= BASE_URL ?>/user/upload_photo_form.php?event_id=<?= $eventId ?>" class="btn-upload">Enviar primeira foto</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="photos-grid">
        <?php foreach ($photos as $photo): ?>
            <div class="photo-card">
                <img src="<?= BASE_URL . '/' . $photo['photo_path'] ?>" 
                     alt="Foto do evento" 
                     class="photo-img"
                     onclick="openModal('<?= BASE_URL . '/' . $photo['photo_path'] ?>', '<?= htmlspecialchars($photo['user_name']) ?>', '<?= htmlspecialchars(addslashes($photo['description'])) ?>')">
                <div class="photo-info">
                    <div class="photo-user">
                        <div class="user-avatar-sm"><?= mb_strtoupper(mb_substr($photo['user_name'], 0, 1)) ?></div>
                        <span class="user-name"><?= htmlspecialchars($photo['user_name']) ?></span>
                        <span class="photo-date"><?= date('d/m/Y H:i', strtotime($photo['created_at'])) ?></span>
                    </div>
                    <?php if ($photo['description']): ?>
                        <div class="photo-desc"><?= htmlspecialchars($photo['description']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div id="modal-photo" class="modal-photo" onclick="closeModal()">
    <span class="modal-photo-close">&times;</span>
    <img id="modal-img" src="">
    <div id="modal-info" class="modal-info"></div>
</div>

<script>
function openModal(src, userName, description) {
    document.getElementById('modal-img').src = src;
    let infoHtml = '<span class="user">📸 ' + userName + '</span>';
    if (description && description !== 'null' && description !== '') {
        infoHtml += ' | ' + description;
    }
    document.getElementById('modal-info').innerHTML = infoHtml;
    document.getElementById('modal-photo').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('modal-photo').style.display = 'none';
    document.body.style.overflow = 'auto';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php pageClose(); ?>