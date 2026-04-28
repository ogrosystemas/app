<?php
// admin/google_auth.php — Callback OAuth2 Google Drive
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/google_drive.php';

// Processar callback ANTES de verificar login
// O Google redireciona aqui com ?code= e a sessão pode ter expirado
if (!empty($_GET['code'])) {
    $drive = new GoogleDrive();
    $result = $drive->exchangeCode($_GET['code']);
    // Inicia sessão para salvar flash message
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($result['access_token'])) {
        $_SESSION['flash_success'] = '✅ Google Drive autorizado com sucesso!';
    } else {
        $_SESSION['flash_error'] = 'Erro ao autorizar: ' . json_encode($result);
    }
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$drive = new GoogleDrive();

// Revogar autorização
if (($_GET['action'] ?? '') === 'revogar') {
    $tokenFile = __DIR__ . '/../includes/google_token.json';
    if (file_exists($tokenFile)) unlink($tokenFile);
    $_SESSION['flash_success'] = 'Autorização revogada.';
    header('Location: ' . BASE_URL . '/admin/google_auth.php');
    exit;
}

$isAuthorized = $drive->isAuthorized();
$authUrl      = $drive->getAuthUrl();

pageOpen('Google Drive', 'google_drive', '📁 Google Drive');
?>

<?php if (isset($_SESSION['flash_success'])): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
<div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h2>📁 Google Drive</h2>
    <p>Armazenamento de fotos do clube</p>
</div>

<div class="card" style="max-width:500px">
    <div class="card-body" style="text-align:center;padding:40px 30px">
        <?php if ($isAuthorized): ?>
            <div style="font-size:3rem;margin-bottom:12px">✅</div>
            <div style="font-size:1.1rem;font-weight:700;color:#28a745;margin-bottom:8px">Google Drive Autorizado</div>
            <div style="font-size:.82rem;color:var(--text-dim);margin-bottom:24px">
                Fotos serão salvas em:<br>
                <strong style="color:var(--text-muted)">Mutantes KM / Eventos</strong> e <strong style="color:var(--text-muted)">Mutantes KM / Sextas</strong>
            </div>
            <a href="https://drive.google.com/drive/folders/1uTI4Een7fhN3nE9ZIjyF3ga6PYsRSxBJ" target="_blank" class="btn btn-primary" style="margin-bottom:10px;display:block">
                📁 Abrir pasta no Drive
            </a>
            <a href="?action=revogar" onclick="return confirm('Revogar autorização?')" class="btn btn-ghost" style="display:block;font-size:.8rem">
                🔓 Revogar autorização
            </a>
        <?php else: ?>
            <div style="font-size:3rem;margin-bottom:12px">📁</div>
            <div style="font-size:1.1rem;font-weight:700;color:#f5b041;margin-bottom:8px">Google Drive não autorizado</div>
            <div style="font-size:.82rem;color:var(--text-dim);margin-bottom:24px">
                Autorize o acesso para que o sistema possa fazer upload de fotos para o Google Drive do clube.
            </div>
            <a href="<?= $authUrl ?>" class="btn btn-primary" style="display:block">
                🔐 Autorizar Google Drive
            </a>
        <?php endif; ?>
    </div>
</div>

<?php pageClose(); ?>
