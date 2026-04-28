<?php
// admin/whatsapp.php — Painel WhatsApp / Evolution API
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();
$tab = $_GET['tab'] ?? 'notificacoes';

// ── Processar POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar_config') {
        $url      = trim($_POST['evolution_url'] ?? '');
        $key      = trim($_POST['evolution_key'] ?? '');
        $instance = trim($_POST['instance_name'] ?? '');
        $itoken   = trim($_POST['instance_token'] ?? '');
        $phone    = preg_replace('/\D/', '', $_POST['admin_phone'] ?? '');
        $n_evento = isset($_POST['notif_novo_evento']) ? 1 : 0;
        $n_pres   = isset($_POST['notif_presenca_confirmada']) ? 1 : 0;
        $n_lemb   = isset($_POST['notif_lembrete']) ? 1 : 0;
        $n_enq    = isset($_POST['notif_enquete']) ? 1 : 0;

        $exists = $db->query("SELECT id FROM evolution_config LIMIT 1")->fetchColumn();
        if ($exists) {
            $db->prepare("UPDATE evolution_config SET evolution_url=?, evolution_key=?, instance_name=?, instance_token=?, admin_phone=?,
                notif_novo_evento=?, notif_presenca_confirmada=?, notif_lembrete=?, notif_enquete=? WHERE id=?")
               ->execute([$url, $key, $instance, $itoken ?: null, $phone, $n_evento, $n_pres, $n_lemb, $n_enq, $exists]);
        } else {
            $db->prepare("INSERT INTO evolution_config (evolution_url, evolution_key, instance_name, instance_token, admin_phone,
                notif_novo_evento, notif_presenca_confirmada, notif_lembrete, notif_enquete) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$url, $key, $instance, $itoken ?: null, $phone, $n_evento, $n_pres, $n_lemb, $n_enq]);
        }
        $_SESSION['flash_success'] = 'Configurações salvas com sucesso!';
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=config');
        exit;
    }

    if ($action === 'criar_notificacao') {
        $tipo      = $_POST['tipo'] ?? 'manual';
        $titulo    = trim($_POST['titulo'] ?? '');
        $mensagem  = trim($_POST['mensagem'] ?? '');
        $dest      = $_POST['destinatario'] ?? 'todos';
        $userId    = (int)($_POST['user_id']  ?? 0) ?: null;
        $grupoId   = (int)($_POST['grupo_id'] ?? 0) ?: null;
        $eventId   = (int)($_POST['event_id'] ?? 0) ?: null;
        $agendado  = !empty($_POST['agendado_para']) ? $_POST['agendado_para'] : null;
        $enviarNow = isset($_POST['enviar_agora']);

        if ($titulo && $mensagem) {
            $db->prepare("INSERT INTO notificacoes (tipo, titulo, mensagem, destinatario, user_id, grupo_id, event_id, agendado_para, status, criado_por) VALUES (?,?,?,?,?,?,?,?,?,?)")
               ->execute([$tipo, $titulo, $mensagem, $dest, $userId, $grupoId, $eventId, $agendado, 'pendente', $me['id']]);
            $notifId = (int)$db->lastInsertId();

            if ($enviarNow || !$agendado) {
                try {
                    require_once __DIR__ . '/../includes/evolution.php';
                    $evo = new Evolution();
                    $res = $evo->dispararNotificacao($notifId);
                    $_SESSION['flash_success'] = "Enviado! {$res['enviados']} mensagem(ns) enviada(s), {$res['erros']} erro(s).";
                } catch (Throwable $e) {
                    $_SESSION['flash_error'] = 'Erro ao enviar: ' . $e->getMessage();
                }
            } else {
                $_SESSION['flash_success'] = 'Notificação agendada para ' . date('d/m/Y H:i', strtotime($agendado)) . '.';
            }
        } else {
            $_SESSION['flash_error'] = 'Preencha o título e a mensagem.';
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=notificacoes');
        exit;
    }

    if ($action === 'criar_enquete') {
        $titulo      = trim($_POST['titulo'] ?? '');
        $pergunta    = trim($_POST['pergunta'] ?? '');
        $opcoes      = array_values(array_filter(array_map('trim', explode("\n", $_POST['opcoes'] ?? ''))));
        $eventId     = (int)($_POST['event_id']  ?? 0) ?: null;
        $encerra     = !empty($_POST['encerra_em']) ? $_POST['encerra_em'] : null;
        $enviarNow   = isset($_POST['enviar_agora']);
        $destEnquete = $_POST['destinatario_enquete'] ?? 'todos';
        $grupoEnquete = (int)($_POST['grupo_id_enquete'] ?? 0) ?: null;

        if ($titulo && $pergunta && count($opcoes) >= 2) {
            try {
                $db->prepare("INSERT INTO enquetes (titulo, pergunta, opcoes, event_id, status, encerra_em, criado_por) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$titulo, $pergunta, json_encode($opcoes), $eventId, 'ativa', $encerra, $me['id']]);
                $enqueteId = (int)$db->lastInsertId();

                if ($enviarNow) {
                    // Sem emojis no título para compatibilidade com latin1
                    $msg = "*{$titulo}*\n\n{$pergunta}\n\n";
                    foreach ($opcoes as $i => $op) $msg .= ($i+1) . ". {$op}\n";
                    $msg .= "\nResponda em: " . BASE_URL . "/user/enquetes.php";
                    // Se destinatario é grupo mas migration não rodou, usar 'todos' como fallback
                    $destFinal = in_array($destEnquete, ['todos','admins','usuarios','individual','grupo']) ? $destEnquete : 'todos';
                    try {
                        $db->prepare("INSERT INTO notificacoes (tipo, titulo, mensagem, destinatario, grupo_id, status, criado_por) VALUES (?,?,?,?,?,?,?)")
                           ->execute(['enquete', "Enquete: {$titulo}", $msg, $destFinal, $grupoEnquete, 'pendente', $me['id']]);
                    } catch (Throwable $dbEx) {
                        // grupo_id column may not exist yet - try without it
                        $db->prepare("INSERT INTO notificacoes (tipo, titulo, mensagem, destinatario, status, criado_por) VALUES (?,?,?,?,?,?)")
                           ->execute(['enquete', "Enquete: {$titulo}", $msg, 'todos', 'pendente', $me['id']]);
                    }
                    $notifId = (int)$db->lastInsertId();
                    $db->prepare("UPDATE enquetes SET notificacao_id=? WHERE id=?")->execute([$notifId, $enqueteId]);
                    if ($notifId > 0) {
                        try {
                            require_once __DIR__ . '/../includes/evolution.php';
                            $evo = new Evolution();
                            $res = $evo->dispararNotificacao($notifId);
                            if ($res['success']) {
                                $_SESSION['flash_success'] = 'Enquete criada e enviada! ' . ($res['enviados'] ?? 0) . ' mensagem(ns).';
                            } else {
                                $_SESSION['flash_error'] = 'Enquete criada mas erro no envio: ' . ($res['message'] ?? 'desconhecido');
                            }
                        } catch (Throwable $e) {
                            $_SESSION['flash_error'] = 'Enquete criada. Erro WhatsApp: ' . $e->getMessage();
                        }
                    } else {
                        $_SESSION['flash_error'] = 'Enquete criada mas falha ao registrar notificação (verifique charset do banco).';
                    }
                } else {
                    $_SESSION['flash_success'] = 'Enquete criada com sucesso!';
                }
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'Erro ao criar enquete: ' . $e->getMessage();
            }
        } else {
            $_SESSION['flash_error'] = 'Preencha todos os campos e informe pelo menos 2 opções.';
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=enquetes');
        exit;
    }

    if ($action === 'reenviar') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        $db->prepare("UPDATE notificacoes SET status='pendente', total_enviados=0, total_erros=0 WHERE id=?")->execute([$notifId]);
        try {
            require_once __DIR__ . '/../includes/evolution.php';
            $evo = new Evolution();
            $res = $evo->dispararNotificacao($notifId);
            $_SESSION['flash_success'] = "Reenviado! {$res['enviados']} mensagem(ns).";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=notificacoes');
        exit;
    }

    if ($action === 'configurar_webhook') {
        try {
            require_once __DIR__ . '/../includes/evolution.php';
            $evo = new Evolution();
            $webhookUrl = BASE_URL . '/api/webhook_evolution.php';
            $res = $evo->configurarWebhook($webhookUrl);
            if (isset($res['webhook']) || isset($res['url']) || ($res['_http_code'] ?? 0) < 300) {
                $_SESSION['flash_success'] = 'Webhook configurado! URL: ' . $webhookUrl;
            } else {
                $_SESSION['flash_error'] = 'Erro ao configurar webhook: ' . json_encode($res);
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=config');
        exit;
    }

    if ($action === 'cancelar_notif') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        $db->prepare("UPDATE notificacoes SET status='cancelado' WHERE id=? AND status='pendente'")->execute([$notifId]);
        $_SESSION['flash_success'] = 'Notificação cancelada.';
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=notificacoes');
        exit;
    }

    if ($action === 'excluir_enquete') {
        $eqId = (int)($_POST['eq_id'] ?? 0);
        if ($eqId) {
            $db->prepare("DELETE FROM enquetes_respostas WHERE enquete_id=?")->execute([$eqId]);
            $db->prepare("DELETE FROM enquetes WHERE id=?")->execute([$eqId]);
            $_SESSION['flash_success'] = 'Enquete removida.';
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=enquetes');
        exit;
    }

    if ($action === 'encerrar_enquete') {
        $eqId = (int)($_POST['eq_id'] ?? 0);
        if ($eqId) {
            $db->prepare("UPDATE enquetes SET status='encerrada' WHERE id=?")->execute([$eqId]);
            $_SESSION['flash_success'] = 'Enquete encerrada.';
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=enquetes');
        exit;
    }

    if ($action === 'salvar_grupo') {
        $nome      = trim($_POST['nome'] ?? '');
        $group_id  = trim($_POST['group_id'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $grads     = $_POST['graduacoes'] ?? [];
        $gradsJson = !empty($grads) ? json_encode(array_values($grads)) : null;
        $gid       = (int)($_POST['gid'] ?? 0);

        if ($nome && $group_id) {
            if ($gid) {
                $db->prepare("UPDATE whatsapp_grupos SET nome=?, group_id=?, descricao=?, graduacoes=? WHERE id=?")
                   ->execute([$nome, $group_id, $descricao, $gradsJson, $gid]);
                $_SESSION['flash_success'] = 'Grupo atualizado!';
            } else {
                $db->prepare("INSERT INTO whatsapp_grupos (nome, group_id, descricao, graduacoes) VALUES (?,?,?,?)")
                   ->execute([$nome, $group_id, $descricao, $gradsJson]);
                $_SESSION['flash_success'] = 'Grupo cadastrado!';
            }
        } else {
            $_SESSION['flash_error'] = 'Nome e ID do grupo são obrigatórios.';
        }
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=grupos');
        exit;
    }

    if ($action === 'excluir_grupo') {
        $gid = (int)($_POST['gid'] ?? 0);
        $db->prepare("DELETE FROM whatsapp_grupos WHERE id=?")->execute([$gid]);
        $_SESSION['flash_success'] = 'Grupo removido.';
        header('Location: ' . BASE_URL . '/admin/whatsapp.php?tab=grupos');
        exit;
    }
}

// ── Carregar dados ────────────────────────────────────────────────────────────
$cfg          = $db->query("SELECT * FROM evolution_config LIMIT 1")->fetch() ?: [];
$notificacoes = $db->query("SELECT n.*, u.name as criado_nome, e.title as evento_titulo
    FROM notificacoes n
    LEFT JOIN users u ON u.id = n.criado_por
    LEFT JOIN events e ON e.id = n.event_id
    ORDER BY n.criado_em DESC LIMIT 50")->fetchAll();
$enquetes     = $db->query("SELECT eq.*, u.name as criado_nome, e.title as evento_titulo
    FROM enquetes eq
    LEFT JOIN users u ON u.id = eq.criado_por
    LEFT JOIN events e ON e.id = eq.event_id
    ORDER BY eq.criado_em DESC LIMIT 30")->fetchAll();
$eventos      = $db->query("SELECT id, title, event_date FROM events WHERE active=1 ORDER BY event_date DESC LIMIT 30")->fetchAll();
$usuarios     = $db->query("SELECT id, name, whatsapp FROM users WHERE active=1 ORDER BY name")->fetchAll();
$totalUsers   = $db->query("SELECT COUNT(*) FROM users WHERE active=1")->fetchColumn();
$comWpp       = $db->query("SELECT COUNT(*) FROM users WHERE active=1 AND whatsapp IS NOT NULL AND whatsapp != ''")->fetchColumn();
$totalEnvios  = $db->query("SELECT COALESCE(SUM(total_enviados),0) FROM notificacoes")->fetchColumn();
try {
    $grupos = $db->query("SELECT * FROM whatsapp_grupos ORDER BY nome")->fetchAll();
} catch (Throwable $e) {
    $grupos = [];
}

// Status da conexão Evolution
$connStatus = 'unconfigured';
$qrCode     = null;
if (!empty($cfg['evolution_url'])) {
    try {
        require_once __DIR__ . '/../includes/evolution.php';
        $evo  = new Evolution();
        $st   = $evo->status();
        $connStatus = $st['instance']['state'] ?? ($st['state'] ?? 'unknown');
        if ($connStatus !== 'open') {
            $qr     = $evo->qrCode();
            $qrCode = $qr['base64'] ?? $qr['qrcode']['base64'] ?? null;
        }
    } catch (Throwable $e) {
        $connStatus = 'error';
    }
}

pageOpen('WhatsApp', 'whatsapp', '📱 WhatsApp');
?>

<?php if (isset($_SESSION['flash_success'])): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
<div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>📱 WhatsApp</h2>
            <p>Notificações via Evolution API</p>
        </div>
    </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:24px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:5px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;max-width:100%">
    <?php
    $tabs = ['notificacoes'=>'🔔 Notificações','enquetes'=>'📊 Enquetes','grupos'=>'👥 Grupos','logs'=>'📋 Logs'];
    foreach ($tabs as $t => $l):
    ?>
    <a href="?tab=<?= $t ?>" style="padding:8px 18px;border-radius:7px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .15s;
        <?= $tab===$t ? 'background:#f39c12;color:#0d0f14;' : 'color:var(--text-dim);' ?>">
        <?= $l ?>
    </a>
    <?php endforeach; ?>
</div>

<style>
.wpp-form-group { margin-bottom:16px; }
.wpp-form-group label { display:block;font-size:.8rem;font-weight:600;color:var(--text-muted);margin-bottom:6px; }
.wpp-form-group input,
.wpp-form-group select,
.wpp-form-group textarea {
    width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;
    padding:9px 12px;color:var(--text);font-size:.85rem;font-family:inherit;
    outline:none;transition:border-color .15s;
}
.wpp-form-group input:focus,
.wpp-form-group select:focus,
.wpp-form-group textarea:focus { border-color:#f39c12; }
.wpp-form-group small { font-size:.72rem;color:var(--text-dim);margin-top:4px;display:block; }
.wpp-grid { display:grid;grid-template-columns:1fr 1fr;gap:20px; }
.wpp-grid-3 { display:grid;grid-template-columns:1fr 1.6fr;gap:20px; }
.status-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:700; }
.notif-row { padding:14px 20px;border-bottom:1px solid var(--border); }
.notif-row:last-child { border-bottom:none; }
@media(max-width:768px){ .wpp-grid,.wpp-grid-3{grid-template-columns:1fr;} }
</style>

<script>
function toggleDestinatario(val) {
    var u = document.getElementById('selectUser');
    var g = document.getElementById('selectGrupo');
    if (u) u.style.display = val === 'individual' ? '' : 'none';
    if (g) g.style.display = val === 'grupo'      ? '' : 'none';
}
function toggleEnqueteDest(val) {
    var g = document.getElementById('selectGrupoEnquete');
    if (g) g.style.display = val === 'grupo' ? '' : 'none';
}
</script>

<?php if ($tab === 'config'): ?>
<!-- ── CONFIGURAÇÃO ──────────────────────────────────────────────────────────── -->
<div class="wpp-grid">

    <div class="card">
        <div class="card-header"><h3 class="card-title">🔧 Evolution API</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="salvar_config">

                <div class="wpp-form-group">
                    <label>URL da instância *</label>
                    <input type="url" name="evolution_url" required
                           value="<?= htmlspecialchars($cfg['evolution_url'] ?? '') ?>"
                           placeholder="http://IP_DA_VPS:8080">
                </div>
                <div class="wpp-form-group">
                    <label>API Key *</label>
                    <input type="text" name="evolution_key" required
                           value="<?= htmlspecialchars($cfg['evolution_key'] ?? '') ?>"
                           placeholder="sua-api-key-secreta">
                </div>
                <div class="wpp-form-group">
                    <label>Nome da instância *</label>
                    <input type="text" name="instance_name" required
                           value="<?= htmlspecialchars($cfg['instance_name'] ?? '') ?>"
                           placeholder="motoclub">
                </div>
                <div class="wpp-form-group">
                    <label>Token da instância</label>
                    <input type="text" name="instance_token"
                           value="<?= htmlspecialchars($cfg['instance_token'] ?? '') ?>"
                           placeholder="ogzm5ygsn4n9j77sg95q7s">
                    <small>Token específico da instância — encontrado no Manager → instância → Token</small>
                </div>
                <div class="wpp-form-group">
                    <label>Número do admin (WhatsApp)</label>
                    <input type="text" name="admin_phone"
                           value="<?= htmlspecialchars($cfg['admin_phone'] ?? '') ?>"
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
                               <?= ($cfg[$k] ?? 1) ? 'checked' : '' ?>
                               style="accent-color:#f39c12;width:16px;height:16px">
                        <?= $l ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%">💾 Salvar Configurações</button>
            </form>
            <!-- Configurar Webhook -->
            <form method="POST" style="margin-top:12px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="configurar_webhook">
                <button type="submit" class="btn btn-ghost" style="width:100%">
                    🔗 Configurar Webhook Automaticamente
                </button>
                <small style="display:block;margin-top:4px;color:var(--text-dim);font-size:.72rem;text-align:center">
                    Configura a Evolution para receber votos das enquetes
                </small>
            </form>
            <form style="display:none">
            </form>
        </div>
    </div>

    <div>
        <!-- Status -->
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3 class="card-title">📡 Status da Conexão</h3></div>
            <div class="card-body" style="text-align:center;padding:28px 20px">
                <?php if ($connStatus === 'unconfigured'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">⚙️</div>
                    <div style="font-weight:700;color:var(--text-dim)">Não configurado</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:4px">Preencha e salve as configurações</div>
                <?php elseif ($connStatus === 'open'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">✅</div>
                    <div style="font-weight:700;color:#28a745;font-size:1.1rem">Conectado</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:4px">WhatsApp ativo e pronto para enviar</div>
                <?php elseif ($connStatus === 'error'): ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">❌</div>
                    <div style="font-weight:700;color:#dc3545">Erro de conexão</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin-top:4px">Verifique a URL e a API Key</div>
                <?php else: ?>
                    <div style="font-size:2.5rem;margin-bottom:10px">📱</div>
                    <div style="font-weight:700;color:#f39c12;font-size:1rem">Desconectado</div>
                    <div style="font-size:.78rem;color:var(--text-dim);margin:8px 0 16px">Escaneie o QR Code com o WhatsApp do admin</div>
                    <?php if ($qrCode): ?>
                        <img src="data:image/png;base64,<?= $qrCode ?>"
                             style="width:200px;height:200px;border-radius:8px;margin:0 auto;display:block">
                    <?php endif; ?>
                <?php endif; ?>
                <button onclick="location.reload()" class="btn btn-ghost btn-sm" style="margin-top:16px">🔄 Atualizar status</button>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">📊 Resumo</h3></div>
            <div class="card-body">
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
                        <div style="font-size:.72rem;color:var(--text-dim);margin-top:2px">Mensagens enviadas no total</div>
                    </div>
                </div>
                <?php if ($totalUsers - $comWpp > 0): ?>
                <a href="<?= BASE_URL ?>/admin/users.php" style="display:block;text-align:center;margin-top:12px;font-size:.78rem;color:#f39c12;text-decoration:none">
                    ⚠️ <?= $totalUsers - $comWpp ?> integrante(s) sem WhatsApp cadastrado →
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'notificacoes'): ?>
<!-- ── NOTIFICAÇÕES ──────────────────────────────────────────────────────────── -->
<div class="wpp-grid-3">

    <div class="card">
        <div class="card-header"><h3 class="card-title">➕ Nova Notificação</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="criar_notificacao">

                <div class="wpp-form-group">
                    <label>Tipo</label>
                    <select name="tipo">
                        <option value="manual">📝 Manual</option>
                        <option value="lembrete">⏰ Lembrete de evento</option>
                        <option value="novo_evento">🏍️ Novo evento</option>
                    </select>
                </div>
                <div class="wpp-form-group">
                    <label>Título (interno) *</label>
                    <input type="text" name="titulo" required placeholder="Ex: Lembrete rolê sexta">
                </div>
                <div class="wpp-form-group">
                    <label>Mensagem *</label>
                    <textarea name="mensagem" rows="5" required
                        placeholder="Digite a mensagem que será enviada no WhatsApp...&#10;&#10;Use *texto* para negrito"></textarea>
                </div>
                <div class="wpp-form-group">
                    <label>Enviar para</label>
                    <select name="destinatario" onchange="toggleDestinatario(this.value)">
                        <option value="todos">👥 Todos os integrantes</option>
                        <option value="admins">🔑 Apenas admins</option>
                        <option value="usuarios">🏍️ Apenas usuários</option>
                        <option value="individual">👤 Integrante específico</option>
                        <option value="grupo">💬 Grupo do WhatsApp</option>
                    </select>
                </div>
                <div class="wpp-form-group" id="selectUser" style="display:none">
                    <label>Integrante</label>
                    <select name="user_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> <?= $u['whatsapp'] ? '✅' : '❌' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpp-form-group" id="selectGrupo" style="display:none">
                    <label>Grupo</label>
                    <select name="grupo_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpp-form-group">
                    <label>Evento relacionado (opcional)</label>
                    <select name="event_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($eventos as $ev): ?>
                        <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['title']) ?> — <?= date('d/m/Y', strtotime($ev['event_date'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpp-form-group">
                    <label>Agendar para (vazio = envio imediato)</label>
                    <input type="datetime-local" name="agendado_para">
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" name="enviar_agora" value="1" class="btn btn-primary" style="flex:1">📤 Enviar Agora</button>
                    <button type="submit" class="btn btn-ghost" style="flex:1">📅 Agendar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Histórico -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">📋 Histórico</h3></div>
        <div style="overflow-y:auto;max-height:620px">
            <?php if (empty($notificacoes)): ?>
            <div style="padding:40px;text-align:center;color:var(--text-dim)">Nenhuma notificação ainda.</div>
            <?php endif; ?>
            <?php foreach ($notificacoes as $n):
                $sc = match($n['status']) { 'enviado'=>'#28a745','enviando'=>'#3b82f6','erro'=>'#dc3545','cancelado'=>'#6b7280',default=>'#f39c12' };
                $ti = match($n['tipo']) { 'novo_evento'=>'🏍️','presenca_confirmada'=>'✅','lembrete'=>'⏰','enquete'=>'📊',default=>'📝' };
            ?>
            <div class="notif-row">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px">
                    <span style="font-weight:600;font-size:.85rem"><?= $ti ?> <?= htmlspecialchars($n['titulo']) ?></span>
                    <span style="font-size:.7rem;font-weight:700;color:<?= $sc ?>;background:<?= $sc ?>20;padding:2px 8px;border-radius:20px;white-space:nowrap;margin-left:8px">
                        <?= strtoupper($n['status']) ?>
                    </span>
                </div>
                <div style="font-size:.76rem;color:var(--text-dim);margin-bottom:6px">
                    <?= htmlspecialchars(mb_substr($n['mensagem'], 0, 80)) ?>...
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:.72rem;color:var(--text-dim)">
                        ✅ <?= $n['total_enviados'] ?> · ❌ <?= $n['total_erros'] ?>
                        · <?= date('d/m H:i', strtotime($n['criado_em'])) ?>
                        <?= $n['agendado_para'] ? ' · 📅 '.date('d/m H:i', strtotime($n['agendado_para'])) : '' ?>
                    </span>
                    <div style="display:flex;gap:8px">
                        <?php if (in_array($n['status'], ['enviado','erro'])): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="reenviar">
                            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#f39c12;padding:0">🔄 Reenviar</button>
                        </form>
                        <?php endif; ?>
                        <?php if ($n['status'] === 'pendente'): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="cancelar_notif">
                            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#dc3545;padding:0">✕ Cancelar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php elseif ($tab === 'enquetes'): ?>
<!-- ── ENQUETES ──────────────────────────────────────────────────────────────── -->
<div class="wpp-grid-3">

    <div class="card">
        <div class="card-header"><h3 class="card-title">➕ Nova Enquete</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="criar_enquete">
                <div class="wpp-form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" required placeholder="Ex: Rolê do próximo fim de semana">
                </div>
                <div class="wpp-form-group">
                    <label>Pergunta *</label>
                    <textarea name="pergunta" rows="3" required placeholder="Você vai no próximo rolê?"></textarea>
                </div>
                <div class="wpp-form-group">
                    <label>Opções * (uma por linha, mínimo 2)</label>
                    <textarea name="opcoes" rows="4" placeholder="Sim, vou!&#10;Não posso&#10;Talvez"></textarea>
                </div>
                <div class="wpp-form-group">
                    <label>Evento relacionado (opcional)</label>
                    <select name="event_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($eventos as $ev): ?>
                        <option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['title']) ?> — <?= date('d/m/Y', strtotime($ev['event_date'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpp-form-group">
                    <label>Enviar para</label>
                    <select name="destinatario_enquete" onchange="toggleEnqueteDest(this.value)">
                        <option value="todos">👥 Todos os integrantes</option>
                        <option value="admins">🔑 Apenas admins</option>
                        <option value="usuarios">🏍️ Apenas usuários</option>
                        <option value="grupo">💬 Grupo do WhatsApp</option>
                    </select>
                </div>
                <div class="wpp-form-group" id="selectGrupoEnquete" style="display:none">
                    <label>Grupo</label>
                    <select name="grupo_id_enquete">
                        <option value="">Selecione...</option>
                        <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="wpp-form-group">
                    <label>Encerra em (opcional)</label>
                    <input type="datetime-local" name="encerra_em">
                </div>
                <button type="submit" name="enviar_agora" value="1" class="btn btn-primary" style="width:100%;margin-bottom:8px">📊 Criar e Enviar no WhatsApp</button>
                <button type="submit" class="btn btn-ghost" style="width:100%">💾 Salvar sem enviar</button>
            </form>
        </div>
    </div>

    <!-- Lista enquetes -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">📊 Enquetes</h3></div>
        <?php if (empty($enquetes)): ?>
        <div style="padding:40px;text-align:center;color:var(--text-dim)">Nenhuma enquete ainda.</div>
        <?php endif; ?>
        <?php foreach ($enquetes as $eq):
            $opcoes = json_decode($eq['opcoes'], true) ?? [];
        ?>
        <div class="notif-row">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px">
                <span style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($eq['titulo']) ?></span>
                <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px;
                    background:<?= $eq['status']==='ativa'?'#28a74520':'#6b728020' ?>;
                    color:<?= $eq['status']==='ativa'?'#28a745':'#6b7280' ?>">
                    <?= strtoupper($eq['status']) ?>
                </span>
            </div>
            <div style="font-size:.78rem;color:var(--text-dim);margin-bottom:8px"><?= htmlspecialchars($eq['pergunta']) ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
                <?php foreach ($opcoes as $op): ?>
                <span style="font-size:.72rem;background:var(--border);color:var(--text-muted);padding:3px 10px;border-radius:20px">
                    <?= htmlspecialchars($op) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                <span style="font-size:.7rem;color:var(--text-dim)">
                    📅 <?= date('d/m/Y H:i', strtotime($eq['criado_em'])) ?>
                    <?= $eq['evento_titulo'] ? ' · 🏍️ ' . htmlspecialchars($eq['evento_titulo']) : '' ?>
                </span>
                <div style="display:flex;gap:8px">
                    <?php if ($eq['status'] === 'ativa'): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="encerrar_enquete">
                        <input type="hidden" name="eq_id" value="<?= $eq['id'] ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.72rem;color:#f39c12;padding:0">⏹ Encerrar</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta enquete?')">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="excluir_enquete">
                        <input type="hidden" name="eq_id" value="<?= $eq['id'] ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.72rem;color:#dc3545;padding:0">🗑️ Remover</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php elseif ($tab === 'grupos'): ?>
<!-- ── GRUPOS ────────────────────────────────────────────────────────────────── -->
<div class="wpp-grid">

    <div class="card">
        <div class="card-header"><h3 class="card-title">➕ Cadastrar / Editar Grupo</h3></div>
        <div class="card-body">
            <form method="POST" id="formGrupo">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="salvar_grupo">
                <input type="hidden" name="gid" id="gid" value="0">

                <div class="wpp-form-group">
                    <label>Nome do grupo *</label>
                    <input type="text" name="nome" id="g-nome" required placeholder="Ex: Mutantes Itajaí">
                </div>
                <div class="wpp-form-group">
                    <label>ID do grupo no WhatsApp *</label>
                    <input type="text" name="group_id" id="g-gid" required placeholder="554788093840-1429934076@g.us">
                    <small>Obtido no manager da Evolution API → Chats/Groups</small>
                </div>
                <div class="wpp-form-group">
                    <label>Descrição</label>
                    <input type="text" name="descricao" id="g-desc" placeholder="Ex: Somente escudos e diretores">
                </div>
                <div class="wpp-form-group">
                    <label>Graduações que participam (vazio = todos)</label>
                    <?php
                    $gradsOpcoes = [
                        'diretor'           => 'Diretor',
                        'subdiretor'        => 'Subdiretor',
                        'escudo_fechado'    => 'Escudo Fechado',
                        'meio_escudo_maior' => 'Meio Escudo Maior',
                        'meio_escudo_menor' => 'Meio Escudo Menor',
                        'pp'                => 'PP',
                        'veterano'          => 'Veterano',
                    ];
                    foreach ($gradsOpcoes as $val => $label): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:6px;cursor:pointer;font-size:.85rem;color:var(--text-muted)">
                        <input type="checkbox" name="graduacoes[]" value="<?= $val ?>" class="grad-check"
                               style="accent-color:#f39c12;width:15px;height:15px">
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary" style="flex:1">💾 Salvar Grupo</button>
                    <button type="button" onclick="limparFormGrupo()" class="btn btn-ghost" style="flex:1">✕ Limpar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de grupos -->
    <div class="card">
        <div class="card-header"><h3 class="card-title">💬 Grupos Cadastrados</h3></div>
        <?php if (empty($grupos)): ?>
        <div style="padding:40px;text-align:center;color:var(--text-dim)">Nenhum grupo cadastrado ainda.</div>
        <?php endif; ?>
        <?php foreach ($grupos as $g):
            $gGrads = json_decode($g['graduacoes'] ?? '[]', true) ?? [];
        ?>
        <div class="notif-row">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px">
                <span style="font-weight:600;font-size:.9rem">💬 <?= htmlspecialchars($g['nome']) ?></span>
                <span style="font-size:.7rem;padding:2px 8px;border-radius:20px;background:<?= $g['ativo']?'#28a74520':'#dc354520' ?>;color:<?= $g['ativo']?'#28a745':'#dc3545' ?>;font-weight:700">
                    <?= $g['ativo'] ? 'ATIVO' : 'INATIVO' ?>
                </span>
            </div>
            <div style="font-size:.75rem;font-family:monospace;color:var(--text-dim);margin-bottom:4px"><?= htmlspecialchars($g['group_id']) ?></div>
            <?php if ($g['descricao']): ?>
            <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:6px"><?= htmlspecialchars($g['descricao']) ?></div>
            <?php endif; ?>
            <?php if (!empty($gGrads)): ?>
            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
                <?php foreach ($gGrads as $grad): ?>
                <span style="font-size:.68rem;background:#f39c1220;color:#f39c12;padding:2px 8px;border-radius:20px">
                    <?= $gradsOpcoes[$grad] ?? $grad ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="font-size:.75rem;color:var(--text-dim);margin-bottom:8px">👥 Todos os integrantes</div>
            <?php endif; ?>
            <div style="display:flex;gap:10px">
                <button onclick="editarGrupo(<?= htmlspecialchars(json_encode($g)) ?>)"
                        style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#f39c12;padding:0">
                    ✏️ Editar
                </button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Remover este grupo?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="excluir_grupo">
                    <input type="hidden" name="gid" value="<?= $g['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#dc3545;padding:0">🗑️ Remover</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function editarGrupo(g) {
    document.getElementById('gid').value    = g.id;
    document.getElementById('g-nome').value = g.nome;
    document.getElementById('g-gid').value  = g.group_id;
    document.getElementById('g-desc').value = g.descricao || '';
    var grads = g.graduacoes ? JSON.parse(g.graduacoes) : [];
    document.querySelectorAll('.grad-check').forEach(function(cb) {
        cb.checked = grads.includes(cb.value);
    });
    window.scrollTo(0, 0);
}
function limparFormGrupo() {
    document.getElementById('gid').value    = '0';
    document.getElementById('g-nome').value = '';
    document.getElementById('g-gid').value  = '';
    document.getElementById('g-desc').value = '';
    document.querySelectorAll('.grad-check').forEach(function(cb) { cb.checked = false; });
}
</script>

<?php elseif ($tab === 'logs'): ?>
<!-- ── LOGS ──────────────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><h3 class="card-title">📋 Log de Envios</h3></div>
    <div class="table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Notificação</th>
                    <th>Integrante</th>
                    <th>WhatsApp</th>
                    <th>Status</th>
                    <th>Detalhe</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logs = $db->query("SELECT l.*, n.titulo as notif_titulo, u.name as user_nome
                    FROM notificacoes_log l
                    LEFT JOIN notificacoes n ON n.id = l.notificacao_id
                    LEFT JOIN users u ON u.id = l.user_id
                    ORDER BY l.enviado_em DESC LIMIT 100")->fetchAll();
                foreach ($logs as $log):
                ?>
                <tr>
                    <td style="font-size:.78rem"><?= date('d/m H:i', strtotime($log['enviado_em'])) ?></td>
                    <td style="font-size:.8rem"><?= htmlspecialchars($log['notif_titulo'] ?? '—') ?></td>
                    <td style="font-size:.8rem"><?= htmlspecialchars($log['user_nome'] ?? '—') ?></td>
                    <td style="font-size:.75rem;font-family:monospace"><?= htmlspecialchars($log['whatsapp']) ?></td>
                    <td>
                        <span style="font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:20px;
                            background:<?= $log['status']==='enviado'?'#28a74520':'#dc354520' ?>;
                            color:<?= $log['status']==='enviado'?'#28a745':'#dc3545' ?>">
                            <?= strtoupper($log['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:.72rem;color:var(--text-dim)"><?= htmlspecialchars($log['erro_msg'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-dim)">Nenhum envio registrado ainda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php pageClose(); ?>
