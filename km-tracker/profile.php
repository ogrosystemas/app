<?php
// profile.php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/includes/layout.php';

$db = db();
$me = currentUser();
$uid = $me['id'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = sanitizeString($_POST['name'] ?? '', 100);
        $email = sanitizeEmail($_POST['email'] ?? '');
        $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');

        if ($name && $email) {
            try {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, whatsapp = ? WHERE id = ?");
                $stmt->execute([$name, $email, $whatsapp ?: null, $uid]);
                $message = 'Perfil atualizado com sucesso!';
                $_SESSION['user_name'] = $name;
            } catch (PDOException $e) {
                $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
            }
        } else {
            $error = 'Nome e e-mail são obrigatórios.';
        }
    } elseif ($action === 'upload_avatar') {
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file    = $_FILES['avatar'];
            $allowed = ['image/jpeg','image/png','image/webp'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                $error = 'Formato não permitido. Use JPG, PNG ou WebP.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'Arquivo muito grande. Máximo 2MB.';
            } else {
                $uploadDir = __DIR__ . '/assets/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                // Remove avatar antigo
                $oldAvatar = $db->prepare("SELECT avatar FROM users WHERE id=?");
                $oldAvatar->execute([$uid]);
                $oldPath = $oldAvatar->fetchColumn();
                if ($oldPath && file_exists(__DIR__ . '/' . $oldPath)) unlink(__DIR__ . '/' . $oldPath);
                // Salva novo
                $filename = 'avatar_' . $uid . '_' . time() . '.jpg';
                $dest     = $uploadDir . $filename;
                $src = match($mime) {
                    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                    'image/png'  => imagecreatefrompng($file['tmp_name']),
                    'image/webp' => imagecreatefromwebp($file['tmp_name']),
                    default      => null
                };
                if ($src) {
                    // Crop quadrado centralizado
                    $w = imagesx($src); $h = imagesy($src);
                    $size = min($w, $h);
                    $x = (int)(($w - $size) / 2); $y = (int)(($h - $size) / 2);
                    $dst = imagecreatetruecolor(200, 200);
                    imagecopyresampled($dst, $src, 0, 0, $x, $y, 200, 200, $size, $size);
                    imagejpeg($dst, $dest, 90);
                    imagedestroy($src); imagedestroy($dst);
                    $avatarPath = 'assets/avatars/' . $filename;
                    $db->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$avatarPath, $uid]);
                    $_SESSION['user_avatar'] = $avatarPath;
                    // Atualiza currentUser na sessão
                    if (isset($_SESSION['user'])) {
                        $_SESSION['user']['avatar'] = $avatarPath;
                    }
                    $message = 'Foto de perfil atualizada!';
                }
            }
        }
    } elseif ($action === 'update_moto') {
        $moto_modelo = sanitizeString($_POST['moto_modelo'] ?? '', 100);
        $moto_kml = sanitizeFloat($_POST['moto_kml'] ?? 0);
        $moto_tanque = sanitizeFloat($_POST['moto_tanque'] ?? 0);
        $gas_preco = sanitizeFloat($_POST['gas_preco'] ?? 0);

        try {
            $stmt = $db->prepare("UPDATE users SET moto_modelo = ?, moto_kml = ?, moto_tanque = ?, gas_preco = ? WHERE id = ?");
            $stmt->execute([$moto_modelo, $moto_kml, $moto_tanque, $gas_preco, $uid]);
            $message = 'Dados da moto atualizados com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar dados da moto: ' . $e->getMessage();
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            $error = 'Senha atual incorreta.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Nova senha deve ter no mínimo 6 caracteres.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Confirmação de senha não confere.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $uid]);
            $message = 'Senha alterada com sucesso!';
        }
    }

    // Salvar moto
    if ($action === 'salvar_moto') {
        $id     = (int)($_POST['moto_id'] ?? 0);
        $modelo = sanitizeString($_POST['modelo'] ?? '', 100);
        $marca  = sanitizeString($_POST['marca'] ?? '', 50);
        $ano    = (int)($_POST['ano'] ?? 0) ?: null;
        $placa  = strtoupper(trim($_POST['placa'] ?? ''));
        $cor    = sanitizeString($_POST['cor'] ?? '', 50);

        if (empty($modelo)) {
            $error = 'Informe o modelo da moto.';
        } else {
            $fotoPath = null;
            if (!empty($_FILES['foto']['tmp_name'])) {
                $file    = $_FILES['foto'];
                $allowed = ['image/jpeg','image/png','image/webp'];
                $finfo   = finfo_open(FILEINFO_MIME_TYPE);
                $mime    = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowed)) {
                    $error = 'Formato não permitido. Use JPG, PNG ou WebP.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = 'Arquivo muito grande. Máximo 5MB.';
                } else {
                    $uploadDir = __DIR__ . '/assets/motos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $filename = 'moto_' . $uid . '_' . time() . '.jpg';
                    $dest     = $uploadDir . $filename;
                    $src = match($mime) {
                        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                        'image/png'  => imagecreatefrompng($file['tmp_name']),
                        'image/webp' => imagecreatefromwebp($file['tmp_name']),
                        default      => null
                    };
                    if ($src) {
                        $w = imagesx($src); $h = imagesy($src);
                        if ($w > 800 || $h > 800) {
                            $ratio = min(800/$w, 800/$h);
                            $nw = (int)($w*$ratio); $nh = (int)($h*$ratio);
                            $dst = imagecreatetruecolor($nw, $nh);
                            imagecopyresampled($dst, $src, 0,0,0,0,$nw,$nh,$w,$h);
                            imagejpeg($dst, $dest, 85);
                            imagedestroy($dst);
                        } else { imagejpeg($src, $dest, 85); }
                        imagedestroy($src);
                        $fotoPath = 'assets/motos/' . $filename;
                    }
                }
            }
            if (!$error) {
                if ($id) {
                    $check = $db->prepare("SELECT foto_path FROM motos WHERE id=? AND user_id=?");
                    $check->execute([$id, $uid]);
                    $old = $check->fetch();
                    if ($old) {
                        if ($fotoPath && $old['foto_path'] && file_exists(__DIR__ . '/' . $old['foto_path'])) {
                            unlink(__DIR__ . '/' . $old['foto_path']);
                        }
                        $sql = $fotoPath
                            ? "UPDATE motos SET modelo=?,marca=?,ano=?,placa=?,cor=?,foto_path=? WHERE id=? AND user_id=?"
                            : "UPDATE motos SET modelo=?,marca=?,ano=?,placa=?,cor=? WHERE id=? AND user_id=?";
                        $params = $fotoPath
                            ? [$modelo,$marca,$ano,$placa,$cor,$fotoPath,$id,$uid]
                            : [$modelo,$marca,$ano,$placa,$cor,$id,$uid];
                        $db->prepare($sql)->execute($params);
                        $message = 'Moto atualizada!';
                    }
                } else {
                    $db->prepare("INSERT INTO motos (user_id,modelo,marca,ano,placa,cor,foto_path) VALUES (?,?,?,?,?,?,?)")
                       ->execute([$uid,$modelo,$marca,$ano,$placa,$cor,$fotoPath]);
                    $message = 'Moto cadastrada!';
                }
            }
        }
    }

    // Definir moto principal
    if ($action === 'set_principal') {
        $id = (int)($_POST['moto_id'] ?? 0);
        $db->prepare("UPDATE motos SET principal=0 WHERE user_id=?")->execute([$uid]);
        $db->prepare("UPDATE motos SET principal=1 WHERE id=? AND user_id=?")->execute([$id,$uid]);
        $message = 'Moto principal definida!';
    }

    // Excluir moto
    if ($action === 'excluir_moto') {
        $id = (int)($_POST['moto_id'] ?? 0);
        $stmt = $db->prepare("SELECT foto_path FROM motos WHERE id=? AND user_id=?");
        $stmt->execute([$id, $uid]);
        $moto = $stmt->fetch();
        if ($moto) {
            if ($moto['foto_path'] && file_exists(__DIR__ . '/' . $moto['foto_path'])) {
                unlink(__DIR__ . '/' . $moto['foto_path']);
            }
            $db->prepare("DELETE FROM motos WHERE id=? AND user_id=?")->execute([$id,$uid]);
            $message = 'Moto removida.';
        }
    }
}

// Buscar dados atualizados
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?"); // avatar included
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Buscar motos
$stmtMotos = $db->prepare("SELECT * FROM motos WHERE user_id=? ORDER BY principal DESC, criado_em DESC");
$stmtMotos->execute([$uid]);
$minhasMotos = $stmtMotos->fetchAll();

// Estatísticas do integrante
$ano = date('Y');
$stats = $db->prepare("
    SELECT
        COUNT(DISTINCT a.event_id) as total_eventos,
        COALESCE(SUM(e.km_awarded), 0) as total_km,
        COALESCE(SUM(a.km_extra), 0) as km_extra,
        (SELECT COUNT(*) FROM sextas_confirmacoes sc WHERE sc.user_id=? AND sc.status='confirmado' AND YEAR(sc.data_sexta)=?) as total_sextas
    FROM attendances a
    JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = ? AND e.active = 1
    WHERE a.user_id = ? AND a.status = 'confirmado'
");
$stats->execute([$uid, $ano, $ano, $uid]);
$stats = $stats->fetch();

// Sistema de conquistas
$kmTotal   = (float)($stats['total_km'] + $stats['km_extra']);
$eventos   = (int)$stats['total_eventos'];
$sextas    = (int)$stats['total_sextas'];

// KM badges - só o maior conquistado
$kmBadge = null;
if ($kmTotal >= 10000)     $kmBadge = ['💎', '10.000 km Rodados',  'diamond'];
elseif ($kmTotal >= 5000)  $kmBadge = ['🏆', '5.000 km Rodados',   'gold'];
elseif ($kmTotal >= 1000)  $kmBadge = ['🥇', '1.000 km Rodados',   'gold'];
elseif ($kmTotal >= 500)   $kmBadge = ['🥈', '500 km Rodados',      'silver'];
elseif ($kmTotal >= 1)     $kmBadge = ['🥉', 'Primeiro Rolê',       'bronze'];

// Eventos badges - só o maior
$evBadge = null;
if ($eventos >= 20)      $evBadge = ['👑', 'Presença VIP',    'gold'];
elseif ($eventos >= 10)  $evBadge = ['💪', '10 Eventos',      'silver'];
elseif ($eventos >= 5)   $evBadge = ['🔥', '5 Eventos',       'bronze'];
elseif ($eventos >= 1)   $evBadge = ['⭐', 'Estreante',       'bronze'];

// Sextas badges - só o maior
$sextaBadge = null;
if ($sextas >= 30)      $sextaBadge = ['🏅', 'Rei da Sexta',   'gold'];
elseif ($sextas >= 15)  $sextaBadge = ['🎯', 'Assíduo',        'silver'];
elseif ($sextas >= 5)   $sextaBadge = ['🍺', 'Frequentador',   'bronze'];

$badges = array_filter([$kmBadge, $evBadge, $sextaBadge]);

pageOpen('Meu Perfil', 'profile', 'Meu Perfil');
?>

<style>
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; color:var(--text-muted); font-size: 0.85rem; }
.form-control { width: 100%; padding: 10px; border-radius: 6px; border:1px solid var(--border); background:var(--bg-input); color:var(--text); font-size: 0.9rem; }
.form-control:focus { outline: none; border-color: #f39c12; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
.alert-success { background: rgba(40, 167, 69, 0.15); border: 1px solid #28a745; color: #28a745; }
.alert-error { background: rgba(220, 53, 69, 0.15); border: 1px solid #dc3545; color: #dc3545; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 500; }
.badge-success { background: #28a745; color: white; }
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
.card { background:var(--bg-card); border-radius: 12px; border:1px solid var(--border); overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom:1px solid var(--border); }
.card-title { font-weight: 600; font-size: 1rem; }
.card-body { padding: 20px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer; text-decoration: none; border: none; }
.btn-primary { background: #f39c12; color: #0d0f14; }
.btn-primary:hover { background: #f5b041; }
.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; }
@media (max-width: 768px) {
    .grid-2 { grid-template-columns: 1fr; gap: 16px; }
    .profile-info-grid { grid-template-columns: 1fr !important; gap: 16px !important; }
}
@media (max-width: 480px) {
    .profile-moto-form-grid { grid-template-columns: 1fr !important; }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Meu Perfil</h2>
            <p>Gerencie suas informações pessoais</p>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid-2">
    <!-- Dados Pessoais -->
    <div class="card">
        <div class="card-header"><span class="card-title">👤 Dados Pessoais</span></div>
        <div class="card-body">
            <!-- Avatar -->
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)">
                <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:#f39c1230;flex-shrink:0;border:2px solid #f39c1240">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= BASE_URL . '/' . $user['avatar'] ?>?v=<?= time() ?>" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#f39c12">
                        <?= mb_strtoupper(mb_substr($user['name']??'U',0,1)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" style="flex:1">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="upload_avatar">
                    <div style="font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:6px">📷 Foto de perfil</div>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="file" name="avatar" accept="image/*" required
                               style="flex:1;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:6px 10px;color:var(--text);font-size:.78rem">
                        <button type="submit" class="btn btn-primary" style="white-space:nowrap;padding:6px 14px;font-size:.78rem">💾 Salvar</button>
                    </div>
                    <small style="font-size:.68rem;color:var(--text-dim)">JPG, PNG ou WebP — máx 2MB — cortado em círculo 200x200</small>
                </form>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="form-group">
                    <label>Nome completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="form-control">
                </div>
                <div class="form-group">
                    <label>📱 WhatsApp</label>
                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>" class="form-control" placeholder="5547999990000 (DDI+DDD+número)">
                    <small class="text-muted">Necessário para receber notificações do clube</small>
                </div>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </form>
        </div>
    </div>

    <!-- Alterar Senha -->
    <div class="card">
        <div class="card-header"><span class="card-title">🔒 Alterar Senha</span></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="form-group">
                    <label>Senha atual</label>
                    <input type="password" name="current_password" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Nova senha</label>
                    <input type="password" name="new_password" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Confirmar nova senha</label>
                    <input type="password" name="confirm_password" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Alterar senha</button>
            </form>
        </div>
    </div>
</div>

<!-- Motos -->
<div style="margin-top:24px">
    <div class="grid-2">
        <!-- Cadastrar Moto -->
        <div class="card">
            <div class="card-header"><span class="card-title">🏍️ Cadastrar Moto</span></div>
            <div class="card-body">
                <details style="margin-bottom:16px;background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:12px">
                    <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:#f39c12">⚙️ Consumo e Combustível</summary>
                    <form method="POST" style="margin-top:12px">
                        <input type="hidden" name="action" value="update_moto">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
                            <div class="form-group" style="margin:0">
                                <label>Consumo (km/L)</label>
                                <input type="number" step="0.1" name="moto_kml" value="<?= htmlspecialchars($user['moto_kml'] ?? '') ?>" class="form-control" placeholder="25.5">
                            </div>
                            <div class="form-group" style="margin:0">
                                <label>Tanque (L)</label>
                                <input type="number" step="0.1" name="moto_tanque" value="<?= htmlspecialchars($user['moto_tanque'] ?? '') ?>" class="form-control" placeholder="17.5">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:10px">
                            <label>Preço da gasolina (R$/L)</label>
                            <input type="number" step="0.01" name="gas_preco" value="<?= htmlspecialchars($user['gas_preco'] ?? '') ?>" class="form-control" placeholder="6.80">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%">💾 Salvar consumo</button>
                    </form>
                </details>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="salvar_moto">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="moto_id" id="moto-id" value="0">
                    <div class="form-group">
                        <label>Modelo *</label>
                        <input type="text" name="modelo" id="moto-modelo" class="form-control" placeholder="Ex: CB 500F" required>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="marca" id="moto-marca" class="form-control" placeholder="Honda">
                        </div>
                        <div class="form-group">
                            <label>Ano</label>
                            <input type="number" name="ano" id="moto-ano" class="form-control" placeholder="2022" min="1950" max="2030">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group">
                            <label>Placa</label>
                            <input type="text" name="placa" id="moto-placa" class="form-control" placeholder="ABC1D23" maxlength="10" style="text-transform:uppercase">
                        </div>
                        <div class="form-group">
                            <label>Cor</label>
                            <input type="text" name="cor" id="moto-cor" class="form-control" placeholder="Preta">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>📷 Foto (opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="form-control">
                        <small style="color:var(--text-dim);font-size:.72rem">JPG, PNG ou WebP — máx 5MB</small>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary" style="flex:1">💾 Salvar</button>
                        <button type="button" onclick="limparMotoForm()" class="btn" style="flex:1;background:var(--border);color:var(--text)">✕ Limpar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Minhas Motos -->
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span class="card-title">🏍️ Minhas Motos</span>
                <span style="font-size:.75rem;color:var(--text-dim)"><?= count($minhasMotos) ?> moto(s)</span>
            </div>
            <?php if (empty($minhasMotos)): ?>
            <div style="padding:30px;text-align:center;color:var(--text-dim)">Nenhuma moto cadastrada.</div>
            <?php else: ?>
            <?php foreach ($minhasMotos as $moto): ?>
            <div style="display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);align-items:flex-start">
                <div style="width:80px;height:60px;background:var(--bg-body);border:1px solid var(--border);border-radius:8px;overflow:hidden;flex-shrink:0">
                    <?php if ($moto['foto_path']): ?>
                    <img src="<?= BASE_URL . '/' . $moto['foto_path'] ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.5rem">🏍️</div>
                    <?php endif; ?>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px">
                        <span style="font-weight:700;color:var(--text);font-size:.9rem"><?= htmlspecialchars($moto['modelo']) ?></span>
                        <?php if ($moto['principal']): ?>
                        <span style="font-size:.62rem;background:#f39c1230;color:#f39c12;padding:1px 6px;border-radius:20px">⭐ Principal</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.75rem;color:var(--text-dim)">
                        <?= $moto['marca'] ? htmlspecialchars($moto['marca']) . ' · ' : '' ?><?= $moto['ano'] ?? '' ?><?= $moto['cor'] ? ' · ' . htmlspecialchars($moto['cor']) : '' ?>
                    </div>
                    <?php if ($moto['placa']): ?>
                    <div style="font-size:.7rem;color:var(--text-muted);font-family:monospace">🔤 <?= htmlspecialchars($moto['placa']) ?></div>
                    <?php endif; ?>
                    <div style="display:flex;gap:10px;margin-top:6px">
                        <button onclick="editarMoto(<?= htmlspecialchars(json_encode($moto)) ?>)"
                                style="background:none;border:none;cursor:pointer;font-size:.73rem;color:#f39c12;padding:0">✏️ Editar</button>
                        <?php if (!$moto['principal']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="set_principal">
                            <input type="hidden" name="moto_id" value="<?= $moto['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.73rem;color:var(--text-dim);padding:0">⭐ Principal</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta moto?')">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="excluir_moto">
                            <input type="hidden" name="moto_id" value="<?= $moto['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.73rem;color:#dc3545;padding:0">🗑️ Remover</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estatísticas + Informações da Conta -->
<div class="grid-2" style="margin-top:24px">
    <div class="card">
        <div class="card-header"><span class="card-title">📊 Meu <?= date('Y') ?></span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div style="background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#f39c12"><?= $stats['total_eventos'] ?></div>
                    <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Eventos</div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#f39c12"><?= number_format($stats['total_km'] + $stats['km_extra'], 0, ',', '.') ?></div>
                    <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">KM Rodados</div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#f39c12"><?= $stats['total_sextas'] ?></div>
                    <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Sextas</div>
                </div>
                <div style="background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:14px;text-align:center">
                    <div style="font-size:1.6rem;font-weight:800;color:#f39c12"><?= count($minhasMotos) ?></div>
                    <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Motos</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Informações da Conta -->
    <div class="card">
        <div class="card-header"><span class="card-title">ℹ️ Informações da Conta</span></div>
        <div class="card-body">
            <div class="profile-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <!-- Lado esquerdo: dados da conta -->
                <div>
                    <div style="margin-bottom:10px">
                        <div style="font-size:.72rem;color:var(--text-dim);margin-bottom:2px">Usuário desde</div>
                        <div style="font-size:.88rem;font-weight:600;color:var(--text)"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div style="margin-bottom:10px">
                        <div style="font-size:.72rem;color:var(--text-dim);margin-bottom:2px">Tipo de conta</div>
                        <div style="font-size:.88rem;font-weight:600;color:var(--text)"><?= $user['role'] === 'admin' ? 'Administrador' : 'Integrante' ?></div>
                    </div>
                    <div style="margin-bottom:20px">
                        <div style="font-size:.72rem;color:var(--text-dim);margin-bottom:4px">Status</div>
                        <span class="badge badge-success">Ativo</span>
                    </div>
                    <a href="logout.php" class="btn btn-danger" style="display:inline-flex">🚪 Sair do sistema</a>
                </div>
                <!-- Lado direito: conquistas -->
                <div>
                    <div style="font-size:.72rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px">🏅 Conquistas</div>
                    <?php if ($kmBadge): ?>
                    <div style="margin-bottom:8px">
                        <div style="font-size:.65rem;color:var(--text-dim);margin-bottom:4px">🏍️ Quilometragem</div>
                        <?php
                            $bg = match($kmBadge[2]) {
                                'diamond' => 'linear-gradient(135deg,#a8edea,#fed6e3)',
                                'gold'    => 'linear-gradient(135deg,#f39c12,#e67e22)',
                                'silver'  => 'linear-gradient(135deg,#bdc3c7,#95a5a6)',
                                default   => 'linear-gradient(135deg,#cd7f32,#a0522d)',
                            };
                        ?>
                        <div style="background:<?= $bg ?>;border-radius:20px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px">
                            <span style="font-size:.9rem"><?= $kmBadge[0] ?></span>
                            <span style="font-size:.7rem;font-weight:700;color:#0d0f14"><?= htmlspecialchars($kmBadge[1]) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($evBadge): ?>
                    <div style="margin-bottom:8px">
                        <div style="font-size:.65rem;color:var(--text-dim);margin-bottom:4px">📅 Eventos</div>
                        <?php
                            $bg = match($evBadge[2]) {
                                'gold'   => 'linear-gradient(135deg,#f39c12,#e67e22)',
                                'silver' => 'linear-gradient(135deg,#bdc3c7,#95a5a6)',
                                default  => 'linear-gradient(135deg,#cd7f32,#a0522d)',
                            };
                        ?>
                        <div style="background:<?= $bg ?>;border-radius:20px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px">
                            <span style="font-size:.9rem"><?= $evBadge[0] ?></span>
                            <span style="font-size:.7rem;font-weight:700;color:#0d0f14"><?= htmlspecialchars($evBadge[1]) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($sextaBadge): ?>
                    <div style="margin-bottom:8px">
                        <div style="font-size:.65rem;color:var(--text-dim);margin-bottom:4px">🍺 Sextas</div>
                        <?php
                            $bg = match($sextaBadge[2]) {
                                'gold'   => 'linear-gradient(135deg,#f39c12,#e67e22)',
                                'silver' => 'linear-gradient(135deg,#bdc3c7,#95a5a6)',
                                default  => 'linear-gradient(135deg,#cd7f32,#a0522d)',
                            };
                        ?>
                        <div style="background:<?= $bg ?>;border-radius:20px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px">
                            <span style="font-size:.9rem"><?= $sextaBadge[0] ?></span>
                            <span style="font-size:.7rem;font-weight:700;color:#0d0f14"><?= htmlspecialchars($sextaBadge[1]) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!$kmBadge && !$evBadge && !$sextaBadge): ?>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:8px">Participe de eventos e sextas para ganhar conquistas! 🏍️</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editarMoto(m) {
    document.getElementById('moto-id').value     = m.id;
    document.getElementById('moto-modelo').value = m.modelo;
    document.getElementById('moto-marca').value  = m.marca || '';
    document.getElementById('moto-ano').value    = m.ano || '';
    document.getElementById('moto-placa').value  = m.placa || '';
    document.getElementById('moto-cor').value    = m.cor || '';
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function limparMotoForm() {
    ['moto-id','moto-modelo','moto-marca','moto-ano','moto-placa','moto-cor'].forEach(id => {
        document.getElementById(id).value = id === 'moto-id' ? '0' : '';
    });
}
</script>

<?php pageClose(); ?>
