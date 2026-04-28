<?php
// admin/escalas.php — Módulo de Escalas
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();
$tab = $_GET['tab'] ?? 'bar';

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Salvar escala do bar
    if ($action === 'salvar_bar') {
        $semana  = $_POST['semana_inicio'] ?? '';
        $user1   = (int)($_POST['user1_id'] ?? 0);
        $user2   = (int)($_POST['user2_id'] ?? 0);
        $obs     = trim($_POST['observacao'] ?? '');
        $id      = (int)($_POST['id'] ?? 0);

        if ($semana && $user1 && $user2 && $user1 !== $user2) {
            $dt = new DateTime($semana);
            $dt->modify('monday this week');
            $segunda = $dt->format('Y-m-d');

            try {
                if ($id) {
                    $db->prepare("UPDATE escala_bar SET semana_inicio=?, user1_id=?, user2_id=?, observacao=? WHERE id=?")
                       ->execute([$segunda, $user1, $user2, $obs ?: null, $id]);
                    $_SESSION['flash_success'] = 'Escala atualizada!';
                } else {
                    $db->prepare("INSERT INTO escala_bar (semana_inicio, user1_id, user2_id, observacao, criado_por) VALUES (?,?,?,?,?)")
                       ->execute([$segunda, $user1, $user2, $obs ?: null, $me['id']]);
                    $_SESSION['flash_success'] = 'Escala cadastrada!';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = 'Já existe escala para esta semana.';
            }
        } else {
            $_SESSION['flash_error'] = 'Preencha todos os campos e escolha pessoas diferentes.';
        }
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=bar');
        exit;
    }

    // Excluir escala do bar
    if ($action === 'excluir_bar') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM escala_bar WHERE id=?")->execute([$id]);
        $_SESSION['flash_success'] = 'Escala removida.';
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=bar');
        exit;
    }

    // Enviar escala do bar agora
    if ($action === 'enviar_bar') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT eb.*, u1.name as nome1, u2.name as nome2 FROM escala_bar eb JOIN users u1 ON u1.id=eb.user1_id JOIN users u2 ON u2.id=eb.user2_id WHERE eb.id=?");
        $stmt->execute([$id]);
        $escala = $stmt->fetch();
        if ($escala) {
            $sextaFeira = date('d/m/Y', strtotime('friday this week'));
            $msg = "🍺 *Escala do Bar - Sexta-feira {$sextaFeira}*\n\n";
            $msg .= "👤 {$escala['nome1']}\n";
            $msg .= "👤 {$escala['nome2']}\n";
            if ($escala['observacao']) $msg .= "\n📝 {$escala['observacao']}";
            $msg .= "\n\nContamos com vocês! 🤘";

            $tipo = $db->query("SELECT * FROM escalas_tipos WHERE nome='bar' LIMIT 1")->fetch();
            if ($tipo && $tipo['grupo_whatsapp_id']) {
                $grupo = $db->prepare("SELECT * FROM whatsapp_grupos WHERE id=?");
                $grupo->execute([$tipo['grupo_whatsapp_id']]);
                $grupo = $grupo->fetch();
                if ($grupo) {
                    try {
                        require_once __DIR__ . '/../includes/evolution.php';
                        $evo = new Evolution();
                        $res = $evo->enviarGrupo($grupo['group_id'], $msg);
                        $ok = isset($res['key']['id']) || isset($res['messageTimestamp']);
                        if ($ok) {
                            $db->prepare("UPDATE escala_bar SET enviado=1, enviado_em=NOW() WHERE id=?")->execute([$id]);
                            $_SESSION['flash_success'] = 'Escala enviada para o grupo!';
                        } else {
                            $_SESSION['flash_error'] = 'Erro ao enviar: ' . json_encode($res);
                        }
                    } catch (Throwable $e) {
                        $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
                    }
                } else {
                    $_SESSION['flash_error'] = 'Grupo do bar não configurado.';
                }
            } else {
                $_SESSION['flash_error'] = 'Configure o grupo do bar nas configurações.';
            }
        }
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=bar');
        exit;
    }

    // Salvar membros do grupo de churrasco
    if ($action === 'salvar_grupo_churrasco') {
        $grupoId = (int)($_POST['grupo_id'] ?? 0);
        $membros = $_POST['membros'] ?? [];
        if ($grupoId && !empty($membros)) {
            $db->prepare("DELETE FROM churrasco_grupo_membros WHERE grupo_id=?")->execute([$grupoId]);
            foreach ($membros as $userId) {
                $db->prepare("INSERT IGNORE INTO churrasco_grupo_membros (grupo_id, user_id) VALUES (?,?)")
                   ->execute([$grupoId, (int)$userId]);
            }
            $_SESSION['flash_success'] = 'Grupo atualizado!';
        } elseif ($grupoId && empty($membros)) {
            $_SESSION['flash_error'] = 'Selecione pelo menos um integrante.';
        }
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=churrasco');
        exit;
    }

    // Enviar escala do churrasco
    if ($action === 'enviar_churrasco') {
        $grupoId = (int)($_POST['grupo_id'] ?? 0);
        $stmt = $db->prepare("SELECT cgm.*, u.name FROM churrasco_grupo_membros cgm JOIN users u ON u.id=cgm.user_id WHERE cgm.grupo_id=? ORDER BY u.name");
        $stmt->execute([$grupoId]);
        $membros = $stmt->fetchAll();
        $grupo = $db->prepare("SELECT * FROM churrasco_grupos WHERE id=?");
        $grupo->execute([$grupoId]);
        $grupo = $grupo->fetch();

        if ($membros && $grupo) {
            $sextaFeira = date('d/m/Y', strtotime('friday this week'));
            $msg = "🔥 *Escala do Churrasco - Sexta-feira {$sextaFeira}*\n";
            $msg .= "👥 Grupo: {$grupo['nome']}\n\n";
            foreach ($membros as $m) $msg .= "👤 {$m['name']}\n";
            $msg .= "\nPreparar o churrasco! 🥩🤘";

            $tipo = $db->query("SELECT * FROM escalas_tipos WHERE nome='churrasco' LIMIT 1")->fetch();
            if ($tipo && $tipo['grupo_whatsapp_id']) {
                $grpWpp = $db->prepare("SELECT * FROM whatsapp_grupos WHERE id=?");
                $grpWpp->execute([$tipo['grupo_whatsapp_id']]);
                $grpWpp = $grpWpp->fetch();
                if ($grpWpp) {
                    try {
                        require_once __DIR__ . '/../includes/evolution.php';
                        $evo = new Evolution();
                        $res = $evo->enviarGrupo($grpWpp['group_id'], $msg);
                        $ok = isset($res['key']['id']) || isset($res['messageTimestamp']);
                        if ($ok) {
                            $_SESSION['flash_success'] = "Escala do {$grupo['nome']} enviada!";
                        } else {
                            $_SESSION['flash_error'] = 'Erro ao enviar: ' . json_encode($res);
                        }
                    } catch (Throwable $e) {
                        $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
                    }
                }
            } else {
                $_SESSION['flash_error'] = 'Configure o grupo do churrasco nas configurações.';
            }
        }
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=churrasco');
        exit;
    }

    // Salvar configurações dos grupos de envio e enquete
    if ($action === 'salvar_config_escalas') {
        foreach (['bar', 'churrasco', 'limpeza'] as $tipo) {
            $grupoId = (int)($_POST["grupo_{$tipo}"] ?? 0) ?: null;
            $db->prepare("UPDATE escalas_tipos SET grupo_whatsapp_id=? WHERE nome=?")
               ->execute([$grupoId, $tipo]);
        }

        // Salvar configurações da enquete de limpeza
        $enquetePergunta = $_POST['enquete_pergunta'] ?? null;
        $enqueteOpcoes = $_POST['enquete_opcoes'] ?? null;

        if ($enqueteOpcoes) {
            $opcoesArray = array_filter(array_map('trim', explode("\n", $enqueteOpcoes)));
            $enqueteOpcoesJson = json_encode(array_values($opcoesArray));
        } else {
            $enqueteOpcoesJson = null;
        }

        $db->prepare("UPDATE escalas_tipos SET enquete_pergunta=?, enquete_opcoes=? WHERE nome='limpeza'")
           ->execute([$enquetePergunta, $enqueteOpcoesJson]);

        $_SESSION['flash_success'] = 'Configurações salvas!';
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=config');
        exit;
    }

    // Enviar limpeza agora (como ENQUETE)
    if ($action === 'enviar_limpeza') {
        // Buscar configurações da enquete no banco
        $configEnquete = $db->query("SELECT enquete_pergunta, enquete_opcoes FROM escalas_tipos WHERE nome='limpeza' LIMIT 1")->fetch();

        $pergunta = $configEnquete['enquete_pergunta'] ?? "🧹 *Quarta - dia de limpeza da sede!*\n\nVocê consegue ajudar?";
        $opcoes = [];

        if ($configEnquete['enquete_opcoes']) {
            $opcoes = json_decode($configEnquete['enquete_opcoes'], true);
        }

        if (empty($opcoes)) {
            $opcoes = ["✅ Estou lá!", "❌ Não vou conseguir", "⚠️ Vou atrasar"];
        }

        $tipo = $db->query("SELECT * FROM escalas_tipos WHERE nome='limpeza' LIMIT 1")->fetch();
        if ($tipo && $tipo['grupo_whatsapp_id']) {
            $grpWpp = $db->prepare("SELECT * FROM whatsapp_grupos WHERE id=?");
            $grpWpp->execute([$tipo['grupo_whatsapp_id']]);
            $grpWpp = $grpWpp->fetch();
            if ($grpWpp) {
                try {
                    require_once __DIR__ . '/../includes/evolution.php';
                    $evo = new Evolution();
                    $res = $evo->enviarEnquete($grpWpp['group_id'], $pergunta, $opcoes);
                    $ok = isset($res['key']['id']) || isset($res['messageTimestamp']);
                    $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Enquete de limpeza enviada!' : 'Erro: ' . json_encode($res);
                } catch (Throwable $e) {
                    $_SESSION['flash_error'] = 'Erro: ' . $e->getMessage();
                }
            } else {
                $_SESSION['flash_error'] = 'Configure o grupo de limpeza nas configurações.';
            }
        } else {
            $_SESSION['flash_error'] = 'Configure o grupo de limpeza nas configurações.';
        }
        header('Location: ' . BASE_URL . '/admin/escalas.php?tab=limpeza');
        exit;
    }
}

// ── Carregar dados ─────────────────────────────────────────────────────────────
$usuarios    = $db->query("SELECT id, name, graduacao FROM users WHERE active=1 ORDER BY name")->fetchAll();
$grupos_wpp  = $db->query("SELECT * FROM whatsapp_grupos WHERE ativo=1 ORDER BY nome")->fetchAll();
$tipos       = $db->query("SELECT * FROM escalas_tipos ORDER BY id")->fetchAll();
$tiposMap    = array_column($tipos, null, 'nome');

// Escala do bar — próximas semanas
$escalasBar  = $db->query("SELECT eb.*, u1.name as nome1, u2.name as nome2 FROM escala_bar eb JOIN users u1 ON u1.id=eb.user1_id JOIN users u2 ON u2.id=eb.user2_id ORDER BY eb.semana_inicio DESC LIMIT 20")->fetchAll();

// Grupos do churrasco (usando ordem visual)
$gruposChurrasco = $db->query("SELECT * FROM churrasco_grupos WHERE ativo=1 ORDER BY ordem ASC")->fetchAll();

// Próxima semana do churrasco (rodízio - usa ordem_envio)
$ultimaEc = $db->query("SELECT grupo_id FROM escala_churrasco ORDER BY id DESC LIMIT 1")->fetchColumn();
$gruposPorEnvio = $db->query("SELECT * FROM churrasco_grupos WHERE ativo=1 ORDER BY ordem_envio ASC")->fetchAll();
$proximoGrupoIdx = 0;
if ($ultimaEc) {
    foreach ($gruposPorEnvio as $i => $g) {
        if ($g['id'] == $ultimaEc) { $proximoGrupoIdx = ($i + 1) % count($gruposPorEnvio); break; }
    }
}
$proximoGrupoNome = $gruposPorEnvio[$proximoGrupoIdx]['nome'] ?? '—';

// Limpeza — membros elegíveis
$excluirGrads = ['escudo_fechado', 'diretor', 'subdiretor', 'veterano'];
$ph = implode(',', array_fill(0, count($excluirGrads), '?'));
$stmtL = $db->prepare("SELECT id, name, graduacao FROM users WHERE active=1 AND (graduacao IS NULL OR graduacao NOT IN ($ph)) ORDER BY name");
$stmtL->execute($excluirGrads);
$membroLimpeza = $stmtL->fetchAll();

$gradLabels = ['diretor'=>'Diretor','subdiretor'=>'Subdiretor','escudo_fechado'=>'Escudo Fechado','meio_escudo_maior'=>'Meio Escudo Maior','meio_escudo_menor'=>'Meio Escudo Menor','pp'=>'PP','veterano'=>'Veterano'];

// Configurações atuais da enquete
$configEnquete = $db->query("SELECT enquete_pergunta, enquete_opcoes FROM escalas_tipos WHERE nome='limpeza' LIMIT 1")->fetch();
$enquetePergunta = $configEnquete['enquete_pergunta'] ?? "🧹 *Quarta - dia de limpeza da sede!*\n\nVocê consegue ajudar?";
$enqueteOpcoesTexto = "";
if ($configEnquete['enquete_opcoes']) {
    $opcoesArray = json_decode($configEnquete['enquete_opcoes'], true);
    $enqueteOpcoesTexto = implode("\n", $opcoesArray);
} else {
    $enqueteOpcoesTexto = "✅ Estou lá!\n❌ Não vou conseguir\n⚠️ Vou atrasar";
}

pageOpen('Escalas', 'escalas', '📋 Escalas');
?>

<?php if (isset($_SESSION['flash_success'])): ?>
<div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
<div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>📋 Escalas</h2><p>Gerencie as escalas do clube</p></div>
    </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:24px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:5px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;max-width:100%;flex-wrap:wrap">
    <?php foreach (['bar'=>'🍺 Bar','churrasco'=>'🔥 Churrasco','limpeza'=>'🧹 Limpeza','config'=>'⚙️ Config'] as $t => $l): ?>
    <a href="?tab=<?= $t ?>" style="padding:8px 18px;border-radius:7px;font-size:.82rem;font-weight:600;text-decoration:none;
        <?= $tab===$t ? 'background:#f39c12;color:#0d0f14;' : 'color:var(--text-dim);' ?>">
        <?= $l ?>
    </a>
    <?php endforeach; ?>
</div>

<style>
.esc-grid { display:grid;grid-template-columns:1fr 1.4fr;gap:20px; }
.esc-form-group { margin-bottom:14px; }
.esc-form-group label { display:block;font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:5px; }
.esc-form-group input, .esc-form-group select, .esc-form-group textarea {
    width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;
    padding:9px 12px;color:var(--text);font-size:.85rem;font-family:inherit;outline:none;
}
.esc-form-group input:focus, .esc-form-group select:focus { border-color:#f39c12; }
.escala-row { padding:14px 20px;border-bottom:1px solid var(--border); }
.escala-row:last-child { border-bottom:none; }
@media(max-width:768px){ .esc-grid{grid-template-columns:1fr;} }
</style>

<?php if ($tab === 'bar'): ?>
<!-- ── BAR ──────────────────────────────────────────────────────────────────── -->
<div class="esc-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">🍺 Nova Escala do Bar</h3></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="salvar_bar">
                <input type="hidden" name="id" id="bar-id" value="0">
                <div class="esc-form-group">
                    <label>Semana (qualquer dia da semana)</label>
                    <input type="date" name="semana_inicio" id="bar-semana" required>
                    <small style="font-size:.7rem;color:var(--text-dim)">O sistema ajusta automaticamente para segunda-feira</small>
                </div>
                <div class="esc-form-group">
                    <label>Integrante 1 *</label>
                    <select name="user1_id" id="bar-u1" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="esc-form-group">
                    <label>Integrante 2 *</label>
                    <select name="user2_id" id="bar-u2" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="esc-form-group">
                    <label>Observação (opcional)</label>
                    <input type="text" name="observacao" id="bar-obs" placeholder="Ex: Reposição de estoque">
                </div>
                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" style="flex:1">💾 Salvar</button>
                    <button type="button" onclick="limparBarForm()" class="btn btn-ghost" style="flex:1">✕ Limpar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Escalas Cadastradas</h3>
            <small style="color:var(--text-dim);font-size:.75rem">Quarta-feira às 20h → Grupo Bar Mutante</small>
        </div>
        <?php if (empty($escalasBar)): ?>
        <div style="padding:30px;text-align:center;color:var(--text-dim)">Nenhuma escala cadastrada.</div>
        <?php endif; ?>
        <?php foreach ($escalasBar as $eb):
            $sextaFeiraLista = date('d/m/Y', strtotime($eb['semana_inicio'] . ' +4 days'));
            $isPast = strtotime($eb['semana_inicio'] . ' +6 days') < strtotime('today');
        ?>
        <div class="escala-row" style="opacity:<?= $isPast ? '.6' : '1' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px">
                <span style="font-weight:600;font-size:.88rem">Sexta-feira <?= $sextaFeiraLista ?></span>
                <?php if ($eb['enviado']): ?>
                <span style="font-size:.68rem;background:#28a74520;color:#28a745;padding:2px 8px;border-radius:20px">✓ Enviado</span>
                <?php else: ?>
                <span style="font-size:.68rem;background:#f39c1220;color:#f39c12;padding:2px 8px;border-radius:20px">Pendente</span>
                <?php endif; ?>
            </div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:6px">
                👤 <?= htmlspecialchars($eb['nome1']) ?> &nbsp;·&nbsp; 👤 <?= htmlspecialchars($eb['nome2']) ?>
            </div>
            <?php if ($eb['observacao']): ?>
            <div style="font-size:.75rem;color:var(--text-dim);margin-bottom:6px">📝 <?= htmlspecialchars($eb['observacao']) ?></div>
            <?php endif; ?>
            <div style="display:flex;gap:10px;margin-top:6px">
                <button onclick="editarBar(<?= htmlspecialchars(json_encode($eb)) ?>)"
                        style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#f39c12;padding:0">✏️ Editar</button>
                <?php if (!$eb['enviado']): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="enviar_bar">
                    <input type="hidden" name="id" value="<?= $eb['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#3b82f6;padding:0">📤 Enviar agora</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta escala?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="excluir_bar">
                    <input type="hidden" name="id" value="<?= $eb['id'] ?>">
                    <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#dc3545;padding:0">🗑️ Remover</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function editarBar(eb) {
    document.getElementById('bar-id').value    = eb.id;
    document.getElementById('bar-semana').value = eb.semana_inicio;
    document.getElementById('bar-u1').value    = eb.user1_id;
    document.getElementById('bar-u2').value    = eb.user2_id;
    document.getElementById('bar-obs').value   = eb.observacao || '';
    window.scrollTo(0, 0);
}
function limparBarForm() {
    document.getElementById('bar-id').value    = '0';
    document.getElementById('bar-semana').value = '';
    document.getElementById('bar-u1').value    = '';
    document.getElementById('bar-u2').value    = '';
    document.getElementById('bar-obs').value   = '';
}
</script>

<?php elseif ($tab === 'churrasco'): ?>
<!-- ── CHURRASCO ─────────────────────────────────────────────────────────────── -->
<div style="margin-bottom:16px;background:#f39c1215;border:1px solid #f39c1240;border-radius:8px;padding:12px 16px;font-size:.82rem;color:#f5b041">
    🔥 Próximo grupo na escala: <strong><?= $proximoGrupoNome ?></strong>
    &nbsp;·&nbsp; Envio automático: quarta-feira às 20h
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">
    <?php foreach ($gruposChurrasco as $gc):
        $stmtM = $db->prepare("SELECT cgm.user_id, u.name, u.graduacao FROM churrasco_grupo_membros cgm JOIN users u ON u.id=cgm.user_id WHERE cgm.grupo_id=? ORDER BY u.name");
        $stmtM->execute([$gc['id']]);
        $membrosGc = $stmtM->fetchAll();
        $membrosIds = array_column($membrosGc, 'user_id');
    ?>
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h3 class="card-title">🔥 <?= htmlspecialchars($gc['nome']) ?></h3>
            <span style="font-size:.72rem;color:var(--text-dim)"><?= count($membrosGc) ?> integrante(s)</span>
        </div>
        <div class="card-body">
            <?php if (!empty($membrosGc)): ?>
            <div style="margin-bottom:14px">
                <table style="width:100%;border-collapse:collapse">
                    <?php foreach ($membrosGc as $m): ?>
                    <tr style="border-bottom:1px solid var(--border)">
                        <td style="padding:7px 0;font-size:.82rem;color:var(--text)">👤 <?= htmlspecialchars($m['name']) ?></td>
                        <td style="padding:7px 0;font-size:.7rem;color:var(--text-dim);text-align:right"><?= $gradLabels[$m['graduacao'] ?? ''] ?? '' ?></td>
                    <tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php else: ?>
            <div style="padding:10px 0;font-size:.8rem;color:var(--text-dim);margin-bottom:14px">Nenhum integrante cadastrado.</div>
            <?php endif; ?>

            <details style="margin-bottom:12px">
                <summary style="cursor:pointer;font-size:.78rem;color:#f39c12;font-weight:600;padding:6px 0">✏️ Editar integrantes</summary>
                <form method="POST" style="margin-top:10px">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="salvar_grupo_churrasco">
                    <input type="hidden" name="grupo_id" value="<?= $gc['id'] ?>">
                    <div style="max-height:200px;overflow-y:auto;margin-bottom:10px;border:1px solid var(--border);border-radius:6px;padding:8px">
                        <?php foreach ($usuarios as $u): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;font-size:.82rem;color:var(--text-muted)">
                            <input type="checkbox" name="membros[]" value="<?= $u['id'] ?>"
                                   <?= in_array($u['id'], $membrosIds) ? 'checked' : '' ?>
                                   style="accent-color:#f39c12">
                            <?= htmlspecialchars($u['name']) ?>
                            <?php if ($u['graduacao']): ?>
                            <span style="font-size:.65rem;color:var(--text-dim)"><?= $gradLabels[$u['graduacao']] ?? '' ?></span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%">💾 Salvar integrantes</button>
                </form>
            </details>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="enviar_churrasco">
                <input type="hidden" name="grupo_id" value="<?= $gc['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="width:100%">📤 Enviar escala agora</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif ($tab === 'limpeza'): ?>
<!-- ── LIMPEZA ───────────────────────────────────────────────────────────────── -->
<div class="esc-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">🧹 Enquete de Limpeza</h3></div>
        <div class="card-body">
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:16px;font-size:.8rem;color:var(--text-dim)">
                ℹ️ Será enviada uma <strong>ENQUETE NATIVA DO WHATSAPP</strong> para o grupo.
            </div>

            <!-- PRÉVIA DA ENQUETE CONFIGURADA -->
            <div style="margin-bottom:20px;background:var(--bg-body);border:1px solid var(--border);border-radius:10px;padding:15px">
                <div style="font-size:.78rem;font-weight:600;color:var(--text-muted);margin-bottom:10px">📋 PRÉVIA DA ENQUETE (como vai chegar no WhatsApp)</div>
                <div style="background:#075E54;color:white;border-radius:10px;padding:12px;max-width:300px;margin:0 auto">
                    <div style="font-weight:600;margin-bottom:8px">📊 <?= nl2br(htmlspecialchars($enquetePergunta)) ?></div>
                    <div style="margin-top:8px">
                        <?php
                        $opcoesPreview = explode("\n", $enqueteOpcoesTexto);
                        foreach ($opcoesPreview as $opcao):
                            if(trim($opcao)):
                        ?>
                        <div style="background:#128C7E;margin:5px 0;padding:6px 10px;border-radius:8px;font-size:.75rem">
                            ☐ <?= htmlspecialchars(trim($opcao)) ?>
                        </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <div style="font-size:.65rem;margin-top:8px;text-align:center;color:#ddd">⬆️ Clique para votar</div>
                </div>
            </div>

            <!-- FORM PARA ENVIO MANUAL -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="enviar_limpeza">
                <button type="submit" class="btn btn-primary" style="width:100%">📤 Enviar Enquete Agora</button>
            </form>

            <div style="font-size:.72rem;color:var(--text-dim);margin-top:12px;text-align:center">
                ⚙️ Para alterar a pergunta e as opções, vá para a aba <strong>Config</strong>.
            </div>
            <div style="font-size:.72rem;color:var(--text-dim);margin-top:5px;text-align:center">
                📅 Envio automático: quarta-feira às 20h
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'config'): ?>
<!-- ── CONFIG ───────────────────────────────────────────────────────────────── -->
<div class="card" style="max-width:600px">
    <div class="card-header"><h3 class="card-title">⚙️ Grupos de Envio</h3></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="salvar_config_escalas">

            <?php foreach (['bar'=>'🍺 Bar','churrasco'=>'🔥 Churrasco','limpeza'=>'🧹 Limpeza'] as $tipo => $label): ?>
            <div class="esc-form-group">
                <label><?= $label ?> → Grupo WhatsApp</label>
                <select name="grupo_<?= $tipo ?>">
                    <option value="">Selecione o grupo...</option>
                    <?php foreach ($grupos_wpp as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($tiposMap[$tipo]['grupo_whatsapp_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>

            <hr style="margin:20px 0">
            <h4 style="margin-bottom:15px">📋 Configurações da Enquete - Limpeza</h4>

            <div class="esc-form-group">
                <label>📝 Pergunta da Enquete</label>
                <textarea name="enquete_pergunta" rows="4" style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem;font-family:inherit"><?= htmlspecialchars($enquetePergunta) ?></textarea>
                <small style="font-size:.7rem;color:var(--text-dim)">Use \n para quebrar linha. O sistema interpreta automaticamente.</small>
            </div>

            <div class="esc-form-group">
                <label>✅ Opções da Enquete (uma por linha)</label>
                <textarea name="enquete_opcoes" rows="6" style="width:100%;background:var(--bg-body);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-size:.85rem;font-family:monospace"><?= htmlspecialchars($enqueteOpcoesTexto) ?></textarea>
                <small style="font-size:.7rem;color:var(--text-dim)">Cada opção em uma nova linha. Máximo de 12 opções.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">💾 Salvar Configurações</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php pageClose(); ?>