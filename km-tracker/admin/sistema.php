<?php
// admin/sistema.php — Configurações do Sistema
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/google_drive.php';
require_once __DIR__ . '/../includes/evolution.php';

$db  = db();
$tab = $_GET['tab'] ?? 'geral';
$message = '';
$error   = '';

// Flash messages
if (isset($_SESSION['flash_success'])) { $message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (isset($_SESSION['flash_error']))   { $error   = $_SESSION['flash_error'];   unset($_SESSION['flash_error']); }

// ── Processar POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Geral
    if ($action === 'geral') {
        foreach (['sistema_nome','clube_nome','tema'] as $f) {
            saveSetting($f, sanitizeString($_POST[$f] ?? '', 200));
        }
        // Logo sistema
        if (!empty($_FILES['logo']['tmp_name'])) {
            $file    = $_FILES['logo'];
            $finfo   = finfo_open(FILEINFO_MIME_TYPE);
            $mime    = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg','image/png','image/webp','image/svg+xml'];
            if (!in_array($mime, $allowed)) {
                $error = 'Formato não permitido. Use JPG, PNG, WebP ou SVG.';
            } elseif ($file['size'] > 2*1024*1024) {
                $error = 'Logo muito grande. Máximo 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../assets/';
                foreach (['logo_sistema.png','logo_sistema.jpg','logo_sistema.webp','logo_sistema.svg'] as $old) {
                    if (file_exists($uploadDir.$old)) unlink($uploadDir.$old);
                }
                $ext  = $mime === 'image/svg+xml' ? 'svg' : 'png';
                $dest = $uploadDir . 'logo_sistema.' . $ext;
                if ($mime === 'image/svg+xml') {
                    move_uploaded_file($file['tmp_name'], $dest);
                } else {
                    $src = match($mime) {
                        'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                        'image/png'  => imagecreatefrompng($file['tmp_name']),
                        'image/webp' => imagecreatefromwebp($file['tmp_name']),
                        default      => null
                    };
                    if ($src) {
                        imagesavealpha($src, true);
                        imagepng($src, $dest);
                        imagedestroy($src);
                    }
                }
                saveSetting('logo_path', 'assets/logo_sistema.'.$ext);
            }
        }
        // Logo relatório
        if (!empty($_FILES['logo_relatorio']['tmp_name'])) {
            $file  = $_FILES['logo_relatorio'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, ['image/jpeg','image/png','image/webp']) && $file['size'] <= 5*1024*1024) {
                $dest = __DIR__ . '/../assets/logo_relatorio.png';
                $src  = match($mime) {
                    'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
                    'image/png'  => imagecreatefrompng($file['tmp_name']),
                    'image/webp' => imagecreatefromwebp($file['tmp_name']),
                    default      => null
                };
                if ($src) {
                    imagesavealpha($src, true);
                    imagepng($src, $dest, 0);
                    imagedestroy($src);
                }
                saveSetting('logo_relatorio_path', 'assets/logo_relatorio.png');
            }
        }
        if (!$error) { $_SESSION['flash_success'] = 'Configurações gerais salvas!'; header("Location: sistema.php?tab=geral"); exit; }
    }

    // Relatório
    if ($action === 'relatorio') {
        foreach (['relatorio_titulo','relatorio_rodape','relatorio_cor_primaria','relatorio_cor_secundaria','relatorio_orientacao','relatorio_max_ranking','relatorio_secoes'] as $f) {
            $val = is_array($_POST[$f] ?? null) ? implode(',', $_POST[$f]) : sanitizeString($_POST[$f] ?? '', 200);
            saveSetting($f, $val);
        }
        $_SESSION['flash_success'] = 'Configurações do relatório salvas!';
        header("Location: sistema.php?tab=relatorio"); exit;
    }

    // WhatsApp
    if ($action === 'salvar_config') {
        saveSetting('evo_url',       trim($_POST['evolution_url'] ?? ''));
        saveSetting('evo_apikey',    trim($_POST['evolution_key'] ?? ''));
        saveSetting('evo_instancia', trim($_POST['instance_name'] ?? ''));
        saveSetting('evo_token',     trim($_POST['instance_token'] ?? ''));
        saveSetting('evo_admin_phone', preg_replace('/\D/', '', $_POST['admin_phone'] ?? ''));
        foreach (['notif_novo_evento','notif_presenca_confirmada','notif_lembrete','notif_enquete'] as $n) {
            saveSetting($n, isset($_POST[$n]) ? '1' : '0');
        }
        // Also update evolution_config table for backward compat
        try {
            $url=$_POST['evolution_url']??''; $key=$_POST['evolution_key']??'';
            $inst=$_POST['instance_name']??''; $tok=$_POST['instance_token']??'';
            $ph=preg_replace('/\D/','',$_POST['admin_phone']??'');
            $ne=isset($_POST['notif_novo_evento'])?1:0; $np=isset($_POST['notif_presenca_confirmada'])?1:0;
            $nl=isset($_POST['notif_lembrete'])?1:0; $nq=isset($_POST['notif_enquete'])?1:0;
            $ex=$db->query("SELECT id FROM evolution_config LIMIT 1")->fetchColumn();
            if ($ex) {
                $db->prepare("UPDATE evolution_config SET evolution_url=?,evolution_key=?,instance_name=?,instance_token=?,admin_phone=?,notif_novo_evento=?,notif_presenca_confirmada=?,notif_lembrete=?,notif_enquete=? WHERE id=?")
                   ->execute([$url,$key,$inst,$tok?:null,$ph,$ne,$np,$nl,$nq,$ex]);
            } else {
                $db->prepare("INSERT INTO evolution_config (evolution_url,evolution_key,instance_name,instance_token,admin_phone,notif_novo_evento,notif_presenca_confirmada,notif_lembrete,notif_enquete) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$url,$key,$inst,$tok?:null,$ph,$ne,$np,$nl,$nq]);
            }
        } catch (Exception $e) { /* evolution_config table may not exist, ignore */ }
        $_SESSION['flash_success'] = 'Configurações do WhatsApp salvas!';
        header("Location: sistema.php?tab=whatsapp"); exit;
    }

    // Webhook
    if ($action === 'configurar_webhook') {
        try {
            $evo = new Evolution();
            $webhookUrl = BASE_URL . '/api/webhook_evolution.php';
            $res = $evo->configurarWebhook($webhookUrl);
            $_SESSION['flash_success'] = 'Webhook configurado!';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
        }
        header("Location: sistema.php?tab=whatsapp"); exit;
    }

    // Google Drive
    if ($action === 'google_drive') {
        saveSetting('google_drive_client_id',     trim($_POST['google_drive_client_id'] ?? ''));
        saveSetting('google_drive_client_secret', trim($_POST['google_drive_client_secret'] ?? ''));
        saveSetting('google_drive_folder_id',     trim($_POST['google_drive_folder_id'] ?? ''));
        $_SESSION['flash_success'] = 'Configurações do Google Drive salvas!';
        header("Location: sistema.php?tab=google_drive"); exit;
    }

    if ($action === 'revogar_drive') {
        try {
            $tokenFile = __DIR__ . '/../includes/google_token.json';
            if (file_exists($tokenFile)) unlink($tokenFile);
        } catch (Exception $e) {}
        $_SESSION['flash_success'] = 'Autorização revogada.';
        header("Location: sistema.php?tab=google_drive"); exit;
    }
}

// ── Carregar dados ────────────────────────────────────────
$s   = loadSettings();
try { $cfg = $db->query("SELECT * FROM evolution_config LIMIT 1")->fetch() ?: []; } catch (Exception $e) { $cfg = []; }

// Status Evolution
$connStatus = 'unconfigured';
if (!empty($s['evo_url']) && !empty($s['evo_instancia'])) {
    try {
        $evo = new Evolution();
        $st  = $evo->status();
        $connStatus = strtolower($st['instance']['state'] ?? $st['state'] ?? 'error');
        if ($connStatus === 'open') $connStatus = 'open';
        elseif (in_array($connStatus, ['close','closed'])) $connStatus = 'closed';
        else $connStatus = 'error';
    } catch (Exception $e) { $connStatus = 'error'; }
}

// QR Code
$qrCode = null;
if ($connStatus !== 'open' && $connStatus !== 'unconfigured') {
    try { $evo = new Evolution(); $qr = $evo->getQrCode(); $qrCode = $qr['qrcode']['base64'] ?? null; } catch (Exception $e) {}
}

// Stats WPP
try { $comWpp = (int)$db->query("SELECT COUNT(*) FROM users WHERE active=1 AND whatsapp IS NOT NULL AND whatsapp!=''")->fetchColumn(); } catch (Exception $e) { $comWpp = 0; }
try { $totalUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn(); } catch (Exception $e) { $totalUsers = 0; }
try { $totalEnvios = (int)$db->query("SELECT COUNT(*) FROM notificacoes WHERE status='enviado'")->fetchColumn(); } catch (Exception $e) { $totalEnvios = 0; }

// Google Drive status
try {
    $drive = new GoogleDrive();
    $driveAuthorized = $drive->isAuthorized();
    $driveAuthUrl    = $drive->getAuthUrl();
} catch (Exception $e) {
    $driveAuthorized = false;
    $driveAuthUrl    = '#';
}

pageOpen('Sistema', 'sistema', '⚙️ Sistema');
?>

<style>
.sys-tabs { display:flex;gap:4px;margin-bottom:24px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:5px;overflow-x:auto;scrollbar-width:none }
.sys-tabs::-webkit-scrollbar{display:none}
.sys-tab { padding:8px 16px;border-radius:7px;font-size:.82rem;font-weight:600;text-decoration:none;color:var(--text-dim);white-space:nowrap;cursor:pointer;transition:all .15s }
.sys-tab.active { background:#f39c12;color:#0d0f14 }
.sys-grid { display:grid;grid-template-columns:1fr 1fr;gap:24px }
.sys-card { background:var(--bg-card,#14161c);border:1px solid var(--border,#2a2f3a);border-radius:12px;overflow:hidden }
.sys-card-header { padding:16px 20px;border-bottom:1px solid var(--border,#2a2f3a);display:flex;align-items:center;gap:10px }
.sys-card-title { font-weight:700;font-size:.95rem;color:var(--text) }
.sys-card-body { padding:20px }
.fg { margin-bottom:16px }
.fg label { display:block;margin-bottom:6px;font-size:.82rem;color:var(--text-muted) }
.fc { width:100%;padding:10px;border-radius:7px;border:1px solid var(--border,#2a2f3a);background:var(--bg-input,#1f2229);color:var(--text,#eef0f8);font-size:.88rem }
.fc:focus { outline:none;border-color:#f39c12 }
.btn-save { background:#f39c12;color:#0d0f14;border:none;border-radius:7px;padding:9px 20px;font-size:.85rem;font-weight:700;cursor:pointer }
.btn-save:hover { background:#f5b041 }
.alert-s { background:rgba(40,167,69,.15);border:1px solid #28a745;color:#28a745;padding:12px 16px;border-radius:8px;margin-bottom:20px }
.alert-e { background:rgba(220,53,69,.15);border:1px solid #dc3545;color:#dc3545;padding:12px 16px;border-radius:8px;margin-bottom:20px }
.logo-prev { width:80px;height:80px;background:transparent;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:10px }
.logo-prev img { max-width:100%;max-height:100%;object-fit:contain;border:none;border-radius:0 }
.theme-btns { display:flex;gap:10px }
.theme-btn { flex:1;padding:10px;border-radius:8px;border:2px solid #2a2f3a;cursor:pointer;text-align:center;font-size:.8rem;font-weight:600;background:var(--bg-input);color:var(--text-muted);transition:all .15s }
.theme-btn.active { border-color:#f39c12;color:#f39c12 }
.wpp-form-group { margin-bottom:14px }
.wpp-form-group label { display:block;margin-bottom:5px;font-size:.8rem;color:var(--text-muted) }
.wpp-form-group input,.wpp-form-group select,.wpp-form-group textarea { width:100%;padding:9px 12px;border-radius:7px;border:1px solid var(--border);background:var(--bg-body);color:var(--text);font-size:.85rem }
.wpp-form-group small { font-size:.72rem;color:var(--text-dim);margin-top:3px;display:block }
.color-row { display:flex;align-items:center;gap:10px }
.color-row input[type=color] { width:42px;height:36px;border:1px solid var(--border);border-radius:6px;background:none;cursor:pointer;padding:2px }
@media(max-width:768px){
    .sys-grid{grid-template-columns:1fr}
    .sys-tabs{flex-wrap:nowrap;overflow-x:auto}
    .sys-tab{flex-shrink:0}
    .wpp-form-group input,.wpp-form-group select{font-size:.8rem}
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>⚙️ Configurações do Sistema</h2><p>Personalize o sistema para o seu clube</p></div>
    </div>
</div>

<?php if ($message): ?><div class="alert-s">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert-e">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Abas -->
<div class="sys-tabs">
    <?php
    $tabs = ['geral'=>'🏷️ Geral','relatorio'=>'📄 Relatório','whatsapp'=>'📱 WhatsApp','google_drive'=>'📁 Google Drive','info'=>'ℹ️ Sistema'];
    foreach ($tabs as $t => $l):
    ?>
    <a href="?tab=<?= $t ?>" class="sys-tab <?= $tab===$t?'active':'' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'geral'): ?>
<!-- ═══════════════ ABA GERAL ═══════════════ -->
<div class="sys-grid">
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">🏷️</span><span class="sys-card-title">Identidade</span></div>
        <div class="sys-card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="geral">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="fg">
                    <label>Nome do Sistema</label>
                    <input type="text" name="sistema_nome" class="fc" value="<?= htmlspecialchars($s['sistema_nome']??'KM Tracker') ?>" placeholder="KM Tracker">
                    <small style="color:var(--text-dim);font-size:.72rem">Aparece no título das páginas e no menu lateral</small>
                </div>
                <div class="fg">
                    <label>Nome do Clube</label>
                    <input type="text" name="clube_nome" class="fc" value="<?= htmlspecialchars($s['clube_nome']??'') ?>" placeholder="Ex: Mutantes MC Brasil">
                    <small style="color:var(--text-dim);font-size:.72rem">Usado em relatórios e mensagens WhatsApp</small>
                </div>
                <div class="fg">
                    <label>🎨 Tema</label>
                    <div class="theme-btns">
                        <button type="button" class="theme-btn <?= ($s['tema']??'dark')==='dark'?'active':'' ?>" onclick="setTema('dark')">🌙 Escuro</button>
                        <button type="button" class="theme-btn <?= ($s['tema']??'dark')==='light'?'active':'' ?>" onclick="setTema('light')">☀️ Claro</button>
                    </div>
                    <input type="hidden" name="tema" id="tema-input" value="<?= htmlspecialchars($s['tema']??'dark') ?>">
                </div>
                <div class="fg">
                    <label>🖼️ Logo do Sistema (menu lateral)</label>
                    <?php $lp = $s['logo_path']??''; ?>
                    <div class="logo-prev" style="background:transparent;border:none">
                        <?php if ($lp && file_exists(__DIR__.'/../'.$lp)): ?>
                        <img src="<?= BASE_URL.'/'.$lp ?>?v=<?= time() ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:0">
                        <?php else: ?><span style="font-size:1.5rem;color:var(--text-dim)">🏍️</span><?php endif; ?>
                    </div>
                    <input type="file" name="logo" accept="image/*" class="fc">
                    <small style="color:var(--text-dim);font-size:.72rem">JPG, PNG, WebP ou SVG — máx 2MB</small>
                </div>
                <div class="fg">
                    <label>🖼️ Logo do Relatório PDF (alta resolução)</label>
                    <?php $lr = $s['logo_relatorio_path']??''; ?>
                    <div class="logo-prev">
                        <?php if ($lr && file_exists(__DIR__.'/../'.$lr)): ?>
                        <img src="<?= BASE_URL.'/'.$lr ?>?v=<?= time() ?>" alt="Logo Relatório" style="border:none;border-radius:0">
                        <?php else: ?><span style="font-size:1.5rem;color:var(--text-dim)">📄</span><?php endif; ?>
                    </div>
                    <input type="file" name="logo_relatorio" accept="image/jpeg,image/png,image/webp" class="fc">
                    <small style="color:var(--text-dim);font-size:.72rem">JPG, PNG ou WebP — máx 5MB — recomendado 500x500px+</small>
                </div>
                <button type="submit" class="btn-save">💾 Salvar</button>
            </form>
        </div>
    </div>

    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">👁️</span><span class="sys-card-title">Pré-visualização</span></div>
        <div class="sys-card-body">
            <div style="background:var(--bg-body);border:1px solid var(--border);border-radius:10px;padding:20px;text-align:center">
                <?php $lp = $s['logo_path']??''; ?>
                <?php if ($lp && file_exists(__DIR__.'/../'.$lp)): ?>
                <img src="<?= BASE_URL.'/'.$lp ?>?v=<?= time() ?>" style="max-width:80px;max-height:80px;object-fit:contain;margin-bottom:10px;border:none;border-radius:0">
                <?php endif; ?>
                <div style="font-size:1.1rem;font-weight:700;color:#f39c12"><?= htmlspecialchars($s['sistema_nome']??'KM Tracker') ?></div>
                <div style="font-size:.72rem;color:var(--text-dim);margin-top:4px"><?= htmlspecialchars($s['clube_nome']??'Sistema de Gestão') ?></div>
            </div>
            <div style="margin-top:16px;font-size:.78rem;color:var(--text-dim);text-align:center">Assim aparecerá no menu lateral</div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'relatorio'): ?>
<!-- ═══════════════ ABA RELATÓRIO ═══════════════ -->
<div class="sys-grid">
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">📄</span><span class="sys-card-title">Layout do Relatório PDF</span></div>
        <div class="sys-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="relatorio">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="fg">
                    <label>Título do Relatório</label>
                    <input type="text" name="relatorio_titulo" class="fc"
                           value="<?= htmlspecialchars($s['relatorio_titulo']??'Relatório de Quilometragem e Presença em Eventos') ?>"
                           placeholder="Relatório Anual de KM">
                </div>
                <div class="fg">
                    <label>Rodapé personalizado</label>
                    <input type="text" name="relatorio_rodape" class="fc"
                           value="<?= htmlspecialchars($s['relatorio_rodape']??'') ?>"
                           placeholder="Ex: Documento confidencial — uso interno">
                </div>
                <div class="fg">
                    <label>🎨 Cor Primária</label>
                    <div class="color-row">
                        <input type="color" name="relatorio_cor_primaria"
                               value="<?= htmlspecialchars($s['relatorio_cor_primaria']??'#f39c12') ?>">
                        <span style="font-size:.82rem;color:var(--text-muted)">Títulos, barras e destaques</span>
                    </div>
                </div>
                <div class="fg">
                    <label>🎨 Cor Secundária</label>
                    <div class="color-row">
                        <input type="color" name="relatorio_cor_secundaria"
                               value="<?= htmlspecialchars($s['relatorio_cor_secundaria']??'#e67e22') ?>">
                        <span style="font-size:.82rem;color:var(--text-muted)">Cor de apoio nos gráficos</span>
                    </div>
                </div>
                <div class="fg">
                    <label>Orientação</label>
                    <select name="relatorio_orientacao" class="fc">
                        <option value="P" <?= ($s['relatorio_orientacao']??'P')==='P'?'selected':'' ?>>📄 Retrato (A4 vertical)</option>
                        <option value="L" <?= ($s['relatorio_orientacao']??'P')==='L'?'selected':'' ?>>📄 Paisagem (A4 horizontal)</option>
                    </select>
                </div>
                <div class="fg">
                    <label>Máx. membros no ranking</label>
                    <select name="relatorio_max_ranking" class="fc">
                        <?php foreach ([10,20,30,50,0] as $v): ?>
                        <option value="<?= $v ?>" <?= ($s['relatorio_max_ranking']??'20')==$v?'selected':'' ?>>
                            <?= $v===0 ? 'Todos' : "Top $v" ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Seções visíveis</label>
                    <?php
                    $secoes = explode(',', $s['relatorio_secoes'] ?? 'ranking,eventos,sextas');
                    $allSecoes = ['ranking'=>'🏆 Ranking de KM','eventos'=>'📅 Tabela de Eventos','sextas'=>'🍺 Participação nas Sextas'];
                    foreach ($allSecoes as $k => $l):
                    ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:.85rem;color:var(--text-muted);cursor:pointer">
                        <input type="checkbox" name="relatorio_secoes[]" value="<?= $k ?>"
                               <?= in_array($k, $secoes)?'checked':'' ?>
                               style="accent-color:#f39c12;width:16px;height:16px">
                        <?= $l ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-save">💾 Salvar relatório</button>
            </form>
        </div>
    </div>

    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">👁️</span><span class="sys-card-title">Pré-visualização</span></div>
        <div class="sys-card-body">
            <?php
            $corP = $s['relatorio_cor_primaria']??'#f39c12';
            $corS = $s['relatorio_cor_secundaria']??'#e67e22';
            $lr   = $s['logo_relatorio_path'] ?? ($s['logo_path']??'');
            ?>
            <div style="background:white;border-radius:10px;padding:20px;color:#333">
                <div style="background:<?= htmlspecialchars($corP) ?>;height:6px;border-radius:3px;margin-bottom:16px"></div>
                <?php if ($lr && file_exists(__DIR__.'/../'.$lr)): ?>
                <img src="<?= BASE_URL.'/'.$lr ?>?v=<?= time() ?>" style="height:50px;object-fit:contain;display:block;margin:0 auto 12px">
                <?php endif; ?>
                <div style="text-align:center;font-size:.9rem;font-weight:700;color:<?= htmlspecialchars($corP) ?>"><?= htmlspecialchars($s['clube_nome']??'KM Tracker') ?></div>
                <div style="text-align:center;font-size:.72rem;color:#666;margin-top:4px"><?= htmlspecialchars($s['relatorio_titulo']??'Relatório de Quilometragem') ?></div>
                <div style="margin-top:12px;background:#f5f5f5;border-radius:6px;padding:10px">
                    <div style="font-size:.72rem;color:#999;margin-bottom:6px">RANKING EXEMPLO</div>
                    <?php foreach (['Membro A','Membro B','Membro C'] as $i => $m): ?>
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px">
                        <span style="font-size:.7rem;color:#999;width:16px"><?= $i+1 ?></span>
                        <span style="font-size:.72rem;width:60px"><?= $m ?></span>
                        <div style="flex:1;height:8px;background:#eee;border-radius:4px">
                            <div style="width:<?= (3-$i)*30 ?>%;height:8px;background:<?= htmlspecialchars($i===0?$corP:$corS) ?>;border-radius:4px"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($s['relatorio_rodape'])): ?>
                <div style="text-align:center;font-size:.65rem;color:#aaa;margin-top:12px;border-top:1px solid #eee;padding-top:8px"><?= htmlspecialchars($s['relatorio_rodape']) ?></div>
                <?php endif; ?>
                <div style="background:<?= htmlspecialchars($corP) ?>;height:4px;border-radius:3px;margin-top:12px"></div>
            </div>
            <div style="margin-top:12px;text-align:center">
                <a href="<?= BASE_URL ?>/admin/report_pdf.php?year=<?= date('Y') ?>&type=complete" target="_blank"
                   style="font-size:.78rem;color:#f39c12;text-decoration:none">📄 Gerar PDF de teste →</a>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'whatsapp'): ?>
<!-- ═══════════════ ABA WHATSAPP ═══════════════ -->
<div class="sys-grid">
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">🔧</span><span class="sys-card-title">Evolution API</span></div>
        <div class="sys-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="salvar_config">
                <div class="wpp-form-group">
                    <label>URL da instância *</label>
                    <input type="url" name="evolution_url" required
                           value="<?= htmlspecialchars($s['evo_url']??$cfg['evolution_url']??'') ?>"
                           placeholder="http://IP_DA_VPS:8080">
                </div>
                <div class="wpp-form-group">
                    <label>API Key *</label>
                    <input type="text" name="evolution_key" required
                           value="<?= htmlspecialchars($s['evo_apikey']??$cfg['evolution_key']??'') ?>"
                           placeholder="sua-api-key-secreta">
                </div>
                <div class="wpp-form-group">
                    <label>Nome da instância *</label>
                    <input type="text" name="instance_name" required
                           value="<?= htmlspecialchars($s['evo_instancia']??$cfg['instance_name']??'') ?>"
                           placeholder="motoclub">
                </div>
                <div class="wpp-form-group">
                    <label>Token da instância</label>
                    <input type="text" name="instance_token"
                           value="<?= htmlspecialchars($s['evo_token']??$cfg['instance_token']??'') ?>"
                           placeholder="token-da-instancia">
                    <small>Encontrado no Manager → instância → Token</small>
                </div>
                <div class="wpp-form-group">
                    <label>Número do admin (WhatsApp)</label>
                    <input type="text" name="admin_phone"
                           value="<?= htmlspecialchars($s['evo_admin_phone']??$cfg['admin_phone']??'') ?>"
                           placeholder="5547999990000">
                    <small>DDI + DDD + número, somente dígitos</small>
                </div>
                <div style="margin-bottom:16px">
                    <div style="font-size:.8rem;font-weight:600;color:var(--text-muted);margin-bottom:10px">Notificações automáticas</div>
                    <?php foreach ([
                        'notif_novo_evento'         => '🏍️ Novo evento cadastrado',
                        'notif_presenca_confirmada' => '✅ Presença confirmada',
                        'notif_lembrete'            => '⏰ Lembretes agendados',
                        'notif_enquete'             => '📊 Enquetes',
                    ] as $k => $l): ?>
                    <label style="display:flex;align-items:center;gap:10px;margin-bottom:8px;cursor:pointer;font-size:.85rem;color:var(--text-muted)">
                        <input type="checkbox" name="<?= $k ?>" value="1"
                               <?= ($s[$k]??$cfg[$k]??1)?'checked':'' ?>
                               style="accent-color:#f39c12;width:16px;height:16px">
                        <?= $l ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-save" style="width:100%">💾 Salvar Configurações</button>
            </form>
            <form method="POST" style="margin-top:10px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="configurar_webhook">
                <button type="submit" class="btn btn-ghost" style="width:100%">🔗 Configurar Webhook Automaticamente</button>
                <small style="display:block;margin-top:4px;color:var(--text-dim);font-size:.72rem;text-align:center">Configura a Evolution para receber eventos do WhatsApp</small>
            </form>
        </div>
    </div>

    <div>
        <div class="sys-card" style="margin-bottom:20px">
            <div class="sys-card-header"><span style="font-size:1.2rem">📡</span><span class="sys-card-title">Status da Conexão</span></div>
            <div class="sys-card-body" style="text-align:center;padding:28px 20px">
                <?php if ($connStatus === 'unconfigured'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">⚙️</div>
                    <div style="font-weight:700;color:var(--text-dim)">Não configurado</div>
                <?php elseif ($connStatus === 'open'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">✅</div>
                    <div style="font-weight:700;color:#28a745;font-size:1.1rem">Conectado</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:4px">WhatsApp ativo e pronto</div>
                <?php elseif ($connStatus === 'error'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">❌</div>
                    <div style="font-weight:700;color:#dc3545">Erro de conexão</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:4px">Verifique a URL e a API Key</div>
                <?php else: ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">📱</div>
                    <div style="font-weight:700;color:#f39c12;font-size:1rem">Desconectado</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin:8px 0 16px">Escaneie o QR Code com o WhatsApp</div>
                    <?php if ($qrCode): ?>
                    <img src="data:image/png;base64,<?= $qrCode ?>" style="width:200px;height:200px;border-radius:8px;margin:0 auto;display:block">
                    <?php endif; ?>
                <?php endif; ?>
                <button onclick="location.reload()" class="btn btn-ghost btn-sm" style="margin-top:16px">🔄 Atualizar status</button>
            </div>
        </div>
        <div class="sys-card">
            <div class="sys-card-header"><span style="font-size:1.2rem">📊</span><span class="sys-card-title">Resumo</span></div>
            <div class="sys-card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div style="text-align:center;padding:14px;background:var(--bg-body);border-radius:8px;border:1px solid var(--border)">
                        <div style="font-size:1.6rem;font-weight:700;color:#28a745"><?= $comWpp ?></div>
                        <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Com WhatsApp</div>
                    </div>
                    <div style="text-align:center;padding:14px;background:var(--bg-body);border-radius:8px;border:1px solid var(--border)">
                        <div style="font-size:1.6rem;font-weight:700;color:#f39c12"><?= $totalUsers - $comWpp ?></div>
                        <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Sem WhatsApp</div>
                    </div>
                    <div style="text-align:center;padding:14px;background:var(--bg-body);border-radius:8px;border:1px solid var(--border);grid-column:span 2">
                        <div style="font-size:1.6rem;font-weight:700;color:#f5b041"><?= number_format($totalEnvios) ?></div>
                        <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Mensagens enviadas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'google_drive'): ?>
<!-- ═══════════════ ABA GOOGLE DRIVE ═══════════════ -->
<div class="sys-grid">
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">🔑</span><span class="sys-card-title">Credenciais OAuth2</span></div>
        <div class="sys-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="google_drive">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <div class="fg">
                    <label>Client ID</label>
                    <input type="text" name="google_drive_client_id" class="fc"
                           value="<?= htmlspecialchars($s['google_drive_client_id']??'') ?>"
                           placeholder="258020236528-...apps.googleusercontent.com">
                </div>
                <div class="fg">
                    <label>Client Secret</label>
                    <input type="password" name="google_drive_client_secret" class="fc"
                           value="<?= htmlspecialchars($s['google_drive_client_secret']??'') ?>"
                           placeholder="GOCSPX-...">
                    <small style="color:var(--text-dim);font-size:.72rem">Encontrado no Google Cloud Console → Credenciais</small>
                </div>
                <div class="fg">
                    <label>ID da Pasta Raiz</label>
                    <input type="text" name="google_drive_folder_id" class="fc"
                           value="<?= htmlspecialchars($s['google_drive_folder_id']??'') ?>"
                           placeholder="1uTI4Een7fhN3nE9ZIjyF3ga6PYsRSxBJ">
                    <small style="color:var(--text-dim);font-size:.72rem">ID da pasta raiz no Google Drive onde as fotos serão salvas</small>
                </div>
                <button type="submit" class="btn-save">💾 Salvar credenciais</button>
            </form>
        </div>
    </div>

    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">📡</span><span class="sys-card-title">Status da Autorização</span></div>
        <div class="sys-card-body" style="text-align:center;padding:28px 20px">
            <?php if ($driveAuthorized): ?>
                <div style="font-size:2.5rem;margin-bottom:12px">✅</div>
                <div style="font-size:1.1rem;font-weight:700;color:#28a745;margin-bottom:8px">Google Drive Autorizado</div>
                <div style="font-size:.82rem;color:var(--text-dim);margin-bottom:20px">Fotos serão salvas automaticamente no Drive</div>
                <?php if (!empty($s['google_drive_folder_id'])): ?>
                <a href="https://drive.google.com/drive/u/2/folders/<?= htmlspecialchars($s['google_drive_folder_id']) ?>" target="_blank"
                   class="btn btn-primary" style="display:block;margin-bottom:10px">📁 Abrir pasta no Drive</a>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="revogar_drive">
                    <button type="submit" onclick="return confirm('Revogar autorização?')"
                            class="btn btn-ghost" style="display:block;width:100%;font-size:.8rem">🔓 Revogar autorização</button>
                </form>
            <?php else: ?>
                <div style="font-size:2.5rem;margin-bottom:12px">📁</div>
                <div style="font-size:1.1rem;font-weight:700;color:#f39c12;margin-bottom:8px">Não autorizado</div>
                <div style="font-size:.82rem;color:var(--text-dim);margin-bottom:20px">
                    <?php if (empty($s['google_drive_client_id'])): ?>
                    Preencha as credenciais ao lado primeiro.
                    <?php else: ?>
                    Clique abaixo para autorizar o acesso ao Google Drive.
                    <?php endif; ?>
                </div>
                <?php if (!empty($s['google_drive_client_id'])): ?>
                <a href="<?= $driveAuthUrl ?>" class="btn btn-primary" style="display:block">🔐 Autorizar Google Drive</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($tab === 'info'): ?>
<!-- ═══════════════ ABA INFO ═══════════════ -->
<div class="sys-grid">
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">🖥️</span><span class="sys-card-title">Ambiente</span></div>
        <div class="sys-card-body">
            <?php foreach ([
                'Versão do Sistema' => APP_VERSION,
                'PHP'               => phpversion(),
                'Banco de Dados'    => DB_NAME,
                'URL Base'          => BASE_URL,
                'Timezone'          => date_default_timezone_get(),
                'Data/Hora atual'   => date('d/m/Y H:i:s'),
                'Servidor'          => php_uname('s') . ' ' . php_uname('r'),
            ] as $k => $v): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:.78rem;color:var(--text-dim)"><?= $k ?></span>
                <span style="font-size:.82rem;font-weight:600;color:<?= $k==='Data/Hora atual'?'#f39c12':'var(--text)' ?>"><?= htmlspecialchars($v) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="sys-card">
        <div class="sys-card-header"><span style="font-size:1.2rem">📦</span><span class="sys-card-title">Extensões PHP</span></div>
        <div class="sys-card-body">
            <?php foreach (['pdo','pdo_mysql','gd','curl','json','mbstring','openssl','zip','fileinfo'] as $ext): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:.82rem;color:var(--text-muted)"><?= $ext ?></span>
                <?php if (extension_loaded($ext)): ?>
                <span style="font-size:.72rem;background:#28a74520;color:#28a745;padding:2px 8px;border-radius:20px">✓ Ativo</span>
                <?php else: ?>
                <span style="font-size:.72rem;background:#dc354520;color:#dc3545;padding:2px 8px;border-radius:20px">✗ Inativo</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function setTema(t) {
    document.getElementById('tema-input').value = t;
    document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}
</script>

<?php pageClose(); ?>
