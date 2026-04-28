<?php
// user/perfil.php — Meu Perfil + Minhas Motos
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();
$uid = $me['id'];

$error = $success = '';

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Adicionar/editar moto
    if ($action === 'salvar_moto') {
        $id     = (int)($_POST['moto_id'] ?? 0);
        $modelo = trim($_POST['modelo'] ?? '');
        $marca  = trim($_POST['marca'] ?? '');
        $ano    = (int)($_POST['ano'] ?? 0) ?: null;
        $placa  = strtoupper(trim($_POST['placa'] ?? ''));
        $cor    = trim($_POST['cor'] ?? '');

        if (empty($modelo)) {
            $error = 'Informe o modelo da moto.';
        } else {
            $fotoPath = null;

            // Upload de foto
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
                    $uploadDir = __DIR__ . '/../assets/motos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $ext      = 'jpg';
                    $filename = 'moto_' . $uid . '_' . time() . '.' . $ext;
                    $dest     = $uploadDir . $filename;

                    // Redimensionar para máx 800px
                    if (function_exists('imagecreatefromjpeg')) {
                        $src = match($mime) {
                            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                            'image/png'  => imagecreatefrompng($file['tmp_name']),
                            'image/webp' => imagecreatefromwebp($file['tmp_name']),
                            default      => null
                        };
                        if ($src) {
                            $w = imagesx($src); $h = imagesy($src);
                            $maxW = 800; $maxH = 800;
                            if ($w > $maxW || $h > $maxH) {
                                $ratio = min($maxW/$w, $maxH/$h);
                                $nw = (int)($w * $ratio); $nh = (int)($h * $ratio);
                                $dst = imagecreatetruecolor($nw, $nh);
                                imagecopyresampled($dst, $src, 0,0,0,0, $nw, $nh, $w, $h);
                                imagejpeg($dst, $dest, 85);
                                imagedestroy($dst);
                            } else {
                                imagejpeg($src, $dest, 85);
                            }
                            imagedestroy($src);
                            $fotoPath = 'assets/motos/' . $filename;
                        }
                    } else {
                        move_uploaded_file($file['tmp_name'], $dest);
                        $fotoPath = 'assets/motos/' . $filename;
                    }
                }
            }

            if (!$error) {
                if ($id) {
                    // Verificar que pertence ao usuário
                    $check = $db->prepare("SELECT foto_path FROM motos WHERE id=? AND user_id=?");
                    $check->execute([$id, $uid]);
                    $old = $check->fetch();
                    if ($old) {
                        if ($fotoPath) {
                            // Remove foto antiga
                            if ($old['foto_path'] && file_exists(__DIR__ . '/../' . $old['foto_path'])) {
                                unlink(__DIR__ . '/../' . $old['foto_path']);
                            }
                            $db->prepare("UPDATE motos SET modelo=?,marca=?,ano=?,placa=?,cor=?,foto_path=? WHERE id=? AND user_id=?")
                               ->execute([$modelo,$marca,$ano,$placa,$cor,$fotoPath,$id,$uid]);
                        } else {
                            $db->prepare("UPDATE motos SET modelo=?,marca=?,ano=?,placa=?,cor=? WHERE id=? AND user_id=?")
                               ->execute([$modelo,$marca,$ano,$placa,$cor,$id,$uid]);
                        }
                        $success = 'Moto atualizada!';
                    }
                } else {
                    $db->prepare("INSERT INTO motos (user_id,modelo,marca,ano,placa,cor,foto_path) VALUES (?,?,?,?,?,?,?)")
                       ->execute([$uid,$modelo,$marca,$ano,$placa,$cor,$fotoPath]);
                    $success = 'Moto cadastrada!';
                }
            }
        }
    }

    // Definir moto principal
    if ($action === 'set_principal') {
        $id = (int)($_POST['moto_id'] ?? 0);
        $db->prepare("UPDATE motos SET principal=0 WHERE user_id=?")->execute([$uid]);
        $db->prepare("UPDATE motos SET principal=1 WHERE id=? AND user_id=?")->execute([$id,$uid]);
        $success = 'Moto principal definida!';
    }

    // Excluir moto
    if ($action === 'excluir_moto') {
        $id = (int)($_POST['moto_id'] ?? 0);
        $stmt = $db->prepare("SELECT foto_path FROM motos WHERE id=? AND user_id=?");
        $stmt->execute([$id, $uid]);
        $moto = $stmt->fetch();
        if ($moto) {
            if ($moto['foto_path'] && file_exists(__DIR__ . '/../' . $moto['foto_path'])) {
                unlink(__DIR__ . '/../' . $moto['foto_path']);
            }
            $db->prepare("DELETE FROM motos WHERE id=? AND user_id=?")->execute([$id,$uid]);
            $success = 'Moto removida.';
        }
    }
}

// Carregar motos
$motos = $db->prepare("SELECT * FROM motos WHERE user_id=? ORDER BY principal DESC, criado_em DESC");
$motos->execute([$uid]);
$motos = $motos->fetchAll();

$gradLabels = ['diretor'=>'Diretor','subdiretor'=>'Subdiretor','escudo_fechado'=>'Escudo Fechado','meio_escudo_maior'=>'Meio Escudo Maior','meio_escudo_menor'=>'Meio Escudo Menor','pp'=>'PP','veterano'=>'Veterano'];

pageOpen('Meu Perfil', 'perfil', '👤 Meu Perfil');
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>👤 Meu Perfil</h2></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px">

    <!-- Info do integrante -->
    <div>
        <div class="card" style="margin-bottom:20px">
            <div class="card-body" style="text-align:center;padding:28px">
                <div style="width:72px;height:72px;background:linear-gradient(135deg,#f39c12,#e67e22);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#0d0f14;margin:0 auto 14px">
                    <?= mb_strtoupper(mb_substr($me['name'],0,1)) ?>
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:4px"><?= htmlspecialchars($me['name']) ?></div>
                <?php if ($me['graduacao']): ?>
                <div style="font-size:.78rem;background:#f39c1220;color:#f5b041;padding:3px 12px;border-radius:20px;display:inline-block;margin-bottom:8px">
                    <?= $gradLabels[$me['graduacao']] ?? $me['graduacao'] ?>
                </div>
                <?php endif; ?>
                <div style="font-size:.8rem;color:var(--text-dim)"><?= htmlspecialchars($me['email']) ?></div>
                <?php if ($me['whatsapp']): ?>
                <div style="font-size:.8rem;color:var(--text-dim);margin-top:4px">📱 <?= htmlspecialchars($me['whatsapp']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cadastrar moto -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">🏍️ Cadastrar Moto</h3></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="salvar_moto">
                    <input type="hidden" name="moto_id" id="moto-id" value="0">

                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">Modelo *</label>
                        <input type="text" name="modelo" id="moto-modelo" placeholder="Ex: CB 500F" maxlength="100"
                               style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                        <div>
                            <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">Marca</label>
                            <input type="text" name="marca" id="moto-marca" placeholder="Honda" maxlength="50"
                                   style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem">
                        </div>
                        <div>
                            <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">Ano</label>
                            <input type="number" name="ano" id="moto-ano" placeholder="2022" min="1950" max="2030"
                                   style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
                        <div>
                            <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">Placa</label>
                            <input type="text" name="placa" id="moto-placa" placeholder="ABC1D23" maxlength="10"
                                   style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem;text-transform:uppercase">
                        </div>
                        <div>
                            <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">Cor</label>
                            <input type="text" name="cor" id="moto-cor" placeholder="Preta" maxlength="50"
                                   style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem">
                        </div>
                    </div>
                    <div style="margin-bottom:14px">
                        <label style="display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px">📷 Foto (opcional)</label>
                        <input type="file" name="foto" accept="image/*"
                               style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.82rem">
                        <small style="font-size:.7rem;color:var(--text-dim)">JPG, PNG ou WebP — máx 5MB — redimensionado automaticamente</small>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary" style="flex:1">💾 Salvar</button>
                        <button type="button" onclick="limparMotoForm()" class="btn btn-ghost" style="flex:1">✕ Limpar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Minhas motos -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏍️ Minhas Motos</h3>
                <span style="font-size:.75rem;color:var(--text-dim)"><?= count($motos) ?> moto(s)</span>
            </div>
            <?php if (empty($motos)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-dim)">
                <div style="font-size:2.5rem;margin-bottom:8px">🏍️</div>
                <div>Nenhuma moto cadastrada ainda.</div>
            </div>
            <?php else: ?>
            <?php foreach ($motos as $moto): ?>
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;gap:14px;align-items:flex-start">
                <!-- Foto -->
                <div style="width:90px;height:70px;background:var(--bg-body);border:1px solid var(--border);border-radius:8px;overflow:hidden;flex-shrink:0">
                    <?php if ($moto['foto_path']): ?>
                    <img src="<?= BASE_URL . '/' . $moto['foto_path'] ?>" alt="<?= htmlspecialchars($moto['modelo']) ?>"
                         style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.8rem">🏍️</div>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                        <span style="font-weight:700;font-size:.92rem;color:var(--text)"><?= htmlspecialchars($moto['modelo']) ?></span>
                        <?php if ($moto['principal']): ?>
                        <span style="font-size:.65rem;background:#f39c1230;color:#f39c12;padding:1px 7px;border-radius:20px;font-weight:600">⭐ Principal</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.78rem;color:var(--text-dim)">
                        <?= $moto['marca'] ? htmlspecialchars($moto['marca']) . ' · ' : '' ?>
                        <?= $moto['ano'] ?? '' ?>
                        <?= $moto['cor'] ? ' · ' . htmlspecialchars($moto['cor']) : '' ?>
                    </div>
                    <?php if ($moto['placa']): ?>
                    <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;font-family:monospace;letter-spacing:1px">
                        🔤 <?= htmlspecialchars($moto['placa']) ?>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex;gap:12px;margin-top:8px">
                        <button onclick="editarMoto(<?= htmlspecialchars(json_encode($moto)) ?>)"
                                style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#f39c12;padding:0">✏️ Editar</button>
                        <?php if (!$moto['principal']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="set_principal">
                            <input type="hidden" name="moto_id" value="<?= $moto['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:var(--text-dim);padding:0">⭐ Principal</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta moto?')">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="excluir_moto">
                            <input type="hidden" name="moto_id" value="<?= $moto['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#dc3545;padding:0">🗑️ Remover</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
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
