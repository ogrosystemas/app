<?php
// user/upload_photo_form.php
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

// Verificar evento
$eventStmt = $db->prepare("
    SELECT e.*, a.status as user_status
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

// Verificar se pode enviar
$eventDate = strtotime($event['event_date']);
$today = strtotime(date('Y-m-d'));
$isEventDay = ($eventDate == $today);
$canUpload = ($isEventDay && $event['user_status'] == 'confirmado');
$isAdmin = ($me['role'] === 'admin');

if (!$canUpload && !$isAdmin) {
    $_SESSION['flash_error'] = 'O upload de fotos só é permitido no dia do evento para participantes confirmados.';
    header('Location: ' . BASE_URL . '/user/events.php');
    exit;
}

pageOpen("Enviar Foto", "events", "Enviar Foto - " . htmlspecialchars($event['title']));
?>

<style>
.upload-area {
    background: var(--bg-card);
    border-radius: 12px;
    border: 2px dashed var(--border);
    padding: 40px;
    text-align: center;
    margin-bottom: 24px;
    cursor: pointer;
    transition: all 0.2s;
}
.upload-area:hover {
    border-color: var(--gold);
    background: var(--bg-card2);
}
.upload-area.dragover {
    border-color: var(--gold);
    background: rgba(243, 156, 18, 0.1);
}
.upload-icon {
    font-size: 48px;
    margin-bottom: 16px;
}
.upload-text {
    color: var(--text-muted);
    margin-bottom: 8px;
}
.upload-hint {
    font-size: 0.7rem;
    color: var(--text-muted);
}
#file-input {
    display: none;
}
.preview-area {
    display: none;
    margin-top: 20px;
    text-align: center;
}
.preview-img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.form-group {
    margin-bottom: 16px;
    text-align: left;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    color: var(--text-secondary);
    font-size: 0.85rem;
}
.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-primary);
    font-family: inherit;
    resize: vertical;
}
.btn-group {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 16px;
}
.btn-primary {
    background: var(--gold);
    color: #0d0f14;
    border: none;
    padding: 10px 24px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
}
.btn-ghost {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
    padding: 10px 24px;
    border-radius: 30px;
    cursor: pointer;
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Enviar Foto</h2>
            <p><?= htmlspecialchars($event['title']) ?> - <?= date('d/m/Y', strtotime($event['event_date'])) ?></p>
        </div>
        <div class="page-header-actions">
            <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $eventId ?>" class="btn btn-ghost">← Voltar para galeria</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">📸 Enviar foto do evento</span>
    </div>
    <div class="card-body">
        <form method="POST" action="upload_photo.php" enctype="multipart/form-data" id="upload-form">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            
            <div class="upload-area" id="upload-area">
                <div class="upload-icon">📷</div>
                <div class="upload-text">Clique ou arraste uma foto aqui</div>
                <div class="upload-hint">Formatos: JPG, PNG, GIF, WEBP (máx. 5MB)</div>
            </div>
            <input type="file" name="photo" id="file-input" accept="image/jpeg,image/png,image/gif,image/webp">
            
            <div class="preview-area" id="preview-area">
                <img class="preview-img" id="preview-img">
                <div class="form-group">
                    <label>Descrição (opcional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Digite uma descrição para a foto..."></textarea>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn-primary">📤 Enviar foto</button>
                    <button type="button" class="btn-ghost" id="cancel-preview">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const uploadArea = document.getElementById('upload-area');
const fileInput = document.getElementById('file-input');
const previewArea = document.getElementById('preview-area');
const previewImg = document.getElementById('preview-img');
const cancelPreview = document.getElementById('cancel-preview');

uploadArea.addEventListener('click', () => fileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        handleFile(file);
    } else {
        alert('Por favor, envie um arquivo de imagem.');
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files[0]) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    if (file.size > 5 * 1024 * 1024) {
        alert('Arquivo muito grande. Máximo 5MB.');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImg.src = e.target.result;
        uploadArea.style.display = 'none';
        previewArea.style.display = 'block';
    };
    reader.readAsDataURL(file);
    
    // Criar novo FileList com o arquivo
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    fileInput.files = dataTransfer.files;
}

cancelPreview.addEventListener('click', () => {
    uploadArea.style.display = 'block';
    previewArea.style.display = 'none';
    fileInput.value = '';
});
</script>

<?php pageClose(); ?>