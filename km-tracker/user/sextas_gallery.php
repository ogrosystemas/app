<?php
// user/sextas_gallery.php — Galeria das Sextas-Feiras
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();
$uid = $me['id'];

// Filtros
$filtroMes  = $_GET['mes'] ?? '';
$filtroData = $_GET['data'] ?? '';

$error = $success = '';

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!empty($_FILES['photo']['tmp_name'])) {
        $file    = $_FILES['photo'];
        $maxSize = 10 * 1024 * 1024;
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($file['size'] > $maxSize) { $error = 'Arquivo muito grande. Máximo 10MB.'; }
        elseif (!in_array($mime, $allowed)) { $error = 'Formato não permitido.'; }
        else {
            try {
                require_once __DIR__ . '/../includes/google_drive.php';
                $drive = new GoogleDrive();

                // Registrar como sexta-feira mais próxima
                $diaSemana = date('N'); // 1=seg, 5=sex
                if ($diaSemana <= 5) {
                    $dataSexta = date('Y-m-d', strtotime('friday this week'));
                } else {
                    $dataSexta = date('Y-m-d', strtotime('friday last week'));
                }

                $ano  = date('Y', strtotime($dataSexta));
                $mes  = strftime('%B', strtotime($dataSexta));
                // Fallback para strftime
                $meses = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
                $mes  = $meses[date('m', strtotime($dataSexta))];

                $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
                $fileName = 'sexta_' . $dataSexta . '_' . $uid . '_' . time() . '.' . $ext;
                $driveData = $drive->uploadFotoSexta($file['tmp_name'], $fileName, $ano, $mes);

                $desc = trim($_POST['description'] ?? '');
                $db->prepare("INSERT INTO sexta_photos (data_sexta, user_id, drive_file_id, drive_view_url, drive_thumb_url, original_name, description) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$dataSexta, $uid, $driveData['id'], GoogleDrive::viewUrl($driveData['id']), GoogleDrive::thumbnailUrl($driveData['id']), $file['name'], $desc ?: null]);

                $_SESSION['flash_success'] = 'Foto enviada! Registrada na sexta ' . date('d/m/Y', strtotime($dataSexta));
                header('Location: ' . BASE_URL . '/user/sextas_gallery.php');
                exit;
            } catch (Throwable $e) {
                $error = 'Erro: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Selecione uma foto.';
    }
}

// Buscar fotos
$where = "1=1";
$params = [];
if ($filtroData) { $where .= " AND sp.data_sexta = ?"; $params[] = $filtroData; }
elseif ($filtroMes) { $where .= " AND DATE_FORMAT(sp.data_sexta, '%Y-%m') = ?"; $params[] = $filtroMes; }

$stmt = $db->prepare("SELECT sp.*, u.name as user_name FROM sexta_photos sp JOIN users u ON u.id=sp.user_id WHERE $where ORDER BY sp.data_sexta DESC, sp.created_at DESC");
$stmt->execute($params);
$photos = $stmt->fetchAll();

// Datas disponíveis para filtro
$datas = $db->query("SELECT DISTINCT data_sexta FROM sexta_photos ORDER BY data_sexta DESC")->fetchAll(PDO::FETCH_COLUMN);
$meses = $db->query("SELECT DISTINCT DATE_FORMAT(data_sexta,'%Y-%m') as mes, DATE_FORMAT(data_sexta,'%M %Y') as label FROM sexta_photos ORDER BY mes DESC")->fetchAll();

pageOpen('Galeria das Sextas', 'sextas', '📸 Galeria das Sextas');
?>

<?php if (isset($_SESSION['flash_success'])): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<style>
@media (max-width: 768px) {
    .sextas-top-grid {
        grid-template-columns: 1fr !important;
    }
}
@media (max-width: 480px) {
    .sextas-top-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
<div class="page-header">
    <div class="page-header-row">
        <div><h2>📸 Galeria das Sextas</h2><p>Momentos das sextas na sede</p></div>
    </div>
</div>

<!-- Upload + Filtros -->
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;margin-bottom:24px" class="sextas-top-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">📤 Enviar Foto</h3></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div style="margin-bottom:12px">
                    <input type="file" name="photo" accept="image/*" required
                           style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.82rem">
                </div>
                <div style="margin-bottom:12px">
                    <input type="text" name="description" placeholder="Descrição (opcional)" maxlength="255"
                           style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.82rem">
                </div>
                <div style="font-size:.72rem;color:var(--text-dim);margin-bottom:10px">
                    📁 Será salvo em: Mutantes KM / Sextas / <?= date('Y') ?> / <?= ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'][date('m')] ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">📤 Enviar</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">🔍 Filtrar</h3></div>
        <div class="card-body">
            <div style="margin-bottom:12px">
                <label style="font-size:.78rem;color:var(--text-muted);margin-bottom:5px;display:block">Por mês</label>
                <select onchange="location.href='?mes='+this.value" style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.82rem">
                    <option value="">Todos os meses</option>
                    <?php foreach ($meses as $m): ?>
                    <option value="<?= $m['mes'] ?>" <?= $filtroMes === $m['mes'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.78rem;color:var(--text-muted);margin-bottom:5px;display:block">Por data de sexta</label>
                <select onchange="location.href='?data='+this.value" style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.82rem">
                    <option value="">Todas as sextas</option>
                    <?php foreach ($datas as $d): ?>
                    <option value="<?= $d ?>" <?= $filtroData === $d ? 'selected' : '' ?>>
                        Sexta <?= date('d/m/Y', strtotime($d)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filtroMes || $filtroData): ?>
            <a href="?" style="display:block;text-align:center;margin-top:10px;font-size:.78rem;color:var(--text-dim);text-decoration:none">✕ Limpar filtro</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Galeria -->
<?php if (empty($photos)): ?>
<div style="text-align:center;padding:60px;background:var(--bg-card);border:1px solid var(--border);border-radius:12px">
    <div style="font-size:3rem;margin-bottom:12px">📸</div>
    <div style="color:var(--text-dim)">Nenhuma foto ainda. Seja o primeiro a compartilhar!</div>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
    <?php foreach ($photos as $p): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:transform .15s" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <img src="<?= htmlspecialchars($p['drive_thumb_url']) ?>"
             alt="Sexta <?= date('d/m/Y', strtotime($p['data_sexta'])) ?>"
             style="width:100%;height:200px;object-fit:cover;cursor:pointer;display:block"
             onclick="openModal('<?= htmlspecialchars($p['drive_view_url']) ?>', '<?= htmlspecialchars($p['user_name']) ?>', '<?= htmlspecialchars(addslashes($p['description'] ?? '')) ?>', '<?= date('d/m/Y', strtotime($p['data_sexta'])) ?>')">
        <div style="padding:10px 12px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:.78rem;font-weight:600;color:#f5b041">🏍️ <?= htmlspecialchars($p['user_name']) ?></span>
                <span style="font-size:.68rem;color:var(--text-dim)">Sexta <?= date('d/m/Y', strtotime($p['data_sexta'])) ?></span>
            </div>
            <?php if ($p['description']): ?>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:4px"><?= htmlspecialchars($p['description']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div id="modal-photo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.95);z-index:9999;cursor:pointer" onclick="closeModal()">
    <span onclick="closeModal()" style="position:absolute;top:20px;right:30px;color:white;font-size:35px;cursor:pointer;z-index:10000">&times;</span>
    <img id="modal-img" src="" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);max-width:90%;max-height:85%;object-fit:contain">
    <div id="modal-info" style="position:absolute;bottom:20px;left:0;right:0;text-align:center;color:white;background:rgba(0,0,0,.7);padding:12px;font-size:.85rem"></div>
</div>

<script>
function openModal(src, user, desc, data) {
    document.getElementById('modal-img').src = src;
    var info = '<span style="color:#f5b041;font-weight:600">📸 ' + user + '</span>';
    if (data) info += ' · Sexta ' + data;
    if (desc) info += ' · ' + desc;
    document.getElementById('modal-info').innerHTML = info;
    document.getElementById('modal-photo').style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('modal-photo').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });
</script>

<?php pageClose(); ?>
