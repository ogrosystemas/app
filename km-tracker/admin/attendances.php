<?php
// admin/attendances.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$year = (int)($_GET['year'] ?? date('Y'));
$eventId = (int)($_GET['event_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $attendanceId = (int)($_POST['attendance_id'] ?? 0);
    
    if ($action === 'confirmar') {
        $stmt = $db->prepare("UPDATE attendances SET status = 'confirmado', confirmed_at = NOW() WHERE id = ?");
        $stmt->execute([$attendanceId]);
        $_SESSION['flash_success'] = 'Participação confirmada!';
        // Notificar no WhatsApp
        try {
            require_once __DIR__ . '/../includes/evolution.php';
            $stmtAtt = $db->prepare("SELECT a.user_id, e.id, e.title, e.event_date, e.location FROM attendances a JOIN events e ON e.id=a.event_id WHERE a.id=?");
            $stmtAtt->execute([$attendanceId]);
            $attData = $stmtAtt->fetch();
            if ($attData) Evolution::notificarPresencaConfirmada((int)$attData['user_id'], $attData);
        } catch (Throwable $e) { error_log('Evolution attendances: ' . $e->getMessage()); }
    } elseif ($action === 'cancelar') {
        $stmt = $db->prepare("UPDATE attendances SET status = 'cancelado' WHERE id = ?");
        $stmt->execute([$attendanceId]);
        $_SESSION['flash_success'] = 'Participação cancelada.';
    }
    header("Location: " . BASE_URL . "/admin/attendances.php?year=$year" . ($eventId ? "&event_id=$eventId" : ""));
    exit;
}

$eventsStmt = $db->prepare("SELECT id, title, event_date FROM events WHERE YEAR(event_date) = ? AND active = 1 ORDER BY event_date DESC");
$eventsStmt->execute([$year]);
$eventsList = $eventsStmt->fetchAll();

$sql = "
    SELECT 
        a.id as attendance_id,
        a.status,
        a.interested_at,
        u.id as user_id,
        u.name,
        u.email,
        e.id as event_id,
        e.title as event_title,
        e.event_date,
        e.km_awarded
    FROM attendances a
    JOIN users u ON u.id = a.user_id
    JOIN events e ON e.id = a.event_id
    WHERE YEAR(e.event_date) = ?
    AND a.status != 'cancelado'
";
$params = [$year];

if ($eventId > 0) {
    $sql .= " AND e.id = ?";
    $params[] = $eventId;
}

$sql .= " ORDER BY e.event_date DESC, FIELD(a.status, 'pendente', 'confirmado')";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$attendances = $stmt->fetchAll();

$totalPendentes = 0;
$totalConfirmados = 0;
foreach ($attendances as $att) {
    if ($att['status'] === 'pendente') $totalPendentes++;
    elseif ($att['status'] === 'confirmado') $totalConfirmados++;
}

// ============================================================
// FUNÇÃO PARA CALCULAR A PRÓXIMA SEXTA-FEIRA
// ============================================================
function getProximaSextaData() {
    $hoje = new DateTime();
    $diaSemana = (int)$hoje->format('N'); // 1=Segunda, 7=Domingo
    
    if ($diaSemana == 5) { // Se hoje é Sexta
        return $hoje->format('Y-m-d');
    } elseif ($diaSemana < 5) { // Antes da Sexta
        $diasAteSexta = 5 - $diaSemana;
        $hoje->modify("+$diasAteSexta days");
        return $hoje->format('Y-m-d');
    } else { // Depois da Sexta (sábado ou domingo)
        $diasAteSexta = (5 + 7) - $diaSemana;
        $hoje->modify("+$diasAteSexta days");
        return $hoje->format('Y-m-d');
    }
}

$proximaSexta = getProximaSextaData();

// Buscar confirmações APENAS da próxima Sexta-feira
$sextasConfirmacoes = $db->prepare("
    SELECT sc.*, u.name as user_name, u.email as user_email, u.role
    FROM sextas_confirmacoes sc
    JOIN users u ON u.id = sc.user_id
    WHERE sc.data_sexta = ?
    ORDER BY sc.confirmed_at DESC
");
$sextasConfirmacoes->execute([$proximaSexta]);
$sextasList = $sextasConfirmacoes->fetchAll();

// Agrupar confirmações por data (agora só terá uma data)
$sextasAgrupadas = [];
foreach ($sextasList as $sc) {
    $data = $sc['data_sexta'];
    if (!isset($sextasAgrupadas[$data])) {
        $sextasAgrupadas[$data] = [];
    }
    $sextasAgrupadas[$data][] = $sc;
}

pageOpen("Presenças", "attendances", "Gerenciar Presenças");
?>

<style>
/* Filtros */
.filter-bar {
    background: #14161c;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid #2a2f3a;
}
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 180px;
}
.filter-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #6e7485;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.filter-select {
    padding: 10px 32px 10px 12px;
    border-radius: 8px;
    border: 1px solid #2a2f3a;
    background: white;
    color: #0d0f14;
    font-size: 0.85rem;
    font-family: inherit;
    font-weight: 500;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236e7485' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}
.filter-select:hover {
    border-color: #f39c12;
}
.filter-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-left: auto;
}
.btn-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #2a2f3a;
    font-family: inherit;
    text-decoration: none;
    background: white;
    color: #0d0f14;
}
.btn-filter:hover {
    background: #f5f5f5;
    transform: translateY(-1px);
    border-color: #f39c12;
}
.btn-filter-clear {
    background: white;
    color: #dc3545;
    border-color: #dc3545;
}
.btn-filter-clear:hover {
    background: #dc3545;
    color: white;
}

/* Cards de estatísticas com bordas coloridas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

/* Cards com bordas coloridas e efeito hover */
.stat-card {
    background: #14161c;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 1px solid #2a2f3a;
    transition: all 0.3s ease;
}

/* Borda superior colorida por card */
.stat-card:nth-child(1) {
    border-top: 3px solid #f39c12; /* Pendentes - Dourado */
}
.stat-card:nth-child(2) {
    border-top: 3px solid #28a745; /* Confirmados - Verde */
}
.stat-card:nth-child(3) {
    border-top: 3px solid #7b9fff; /* Total - Azul */
}

/* Efeito hover */
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

/* Cores de sombra específicas por card no hover */
.stat-card:nth-child(1):hover {
    box-shadow: 0 8px 24px rgba(243,156,18,0.2);
}
.stat-card:nth-child(2):hover {
    box-shadow: 0 8px 24px rgba(40,167,69,0.2);
}
.stat-card:nth-child(3):hover {
    box-shadow: 0 8px 24px rgba(123,159,255,0.2);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #f5b041;
}
.stat-text {
    font-size: 0.7rem;
    color: #6e7485;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 8px;
}

/* Card de Sextas Confirmadas - Próxima Sexta */
.card-sextas {
    background: #14161c;
    border-radius: 12px;
    border: 1px solid #2a2f3a;
    overflow: hidden;
    margin-bottom: 24px;
}
.card-sextas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #2a2f3a;
    background: linear-gradient(135deg, #1a0f05, #14161c);
}
.card-sextas-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #f5b041;
}
.sextas-grid {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.sexta-group {
    border-bottom: 1px solid #2a2f3a;
}
.sexta-date {
    background: #1f2229;
    padding: 12px 20px;
    font-weight: 600;
    color: #f5b041;
    font-size: 0.85rem;
}
.sexta-date span {
    color: #6e7485;
    font-weight: normal;
}
.sexta-users {
    padding: 8px 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.sexta-user {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #1f2229;
    padding: 8px 16px;
    border-radius: 30px;
    border: 1px solid #2a2f3a;
    transition: all 0.2s;
}
.sexta-user:hover {
    transform: translateY(-2px);
    border-color: #f39c12;
    background: #2a2f3a;
}
.sexta-user-avatar {
    width: 28px;
    height: 28px;
    background: rgba(243, 156, 18, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #f5b041;
    font-size: 0.7rem;
}
.sexta-user-info {
    display: flex;
    flex-direction: column;
}
.sexta-user-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: #eef0f8;
}
.sexta-user-role {
    font-size: 0.6rem;
    color: #6e7485;
}
.empty-sextas {
    padding: 40px;
    text-align: center;
    color: #6e7485;
}

/* Card da tabela */
.card-table {
    background: #14161c;
    border-radius: 12px;
    border: 1px solid #2a2f3a;
    overflow: hidden;
}
.table-responsive {
    overflow-x: auto;
}
.users-table {
    width: 100%;
    border-collapse: collapse;
}
.users-table th, .users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #2a2f3a;
}
.users-table th {
    color: #6e7485;
    font-weight: 500;
    font-size: 0.7rem;
    text-transform: uppercase;
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.badge-confirmado {
    background: #28a745;
    color: white;
}
.badge-pendente {
    background: #f39c12;
    color: #0d0f14;
}
.text-gold {
    color: #f5b041;
    font-weight: 600;
}
/* Padronização de botões na tabela */
.btn-table-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 70px;
    padding: 6px 12px;
    font-size: 0.7rem;
    font-weight: 500;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-align: center;
    white-space: nowrap;
}

.btn-confirmar {
    background: #28a745;
    color: white;
}
.btn-confirmar:hover {
    background: #218838;
    transform: translateY(-1px);
}

.btn-cancel {
    background: #dc3545;
    color: white;
}
.btn-cancel:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.btn-warning {
    background: #f39c12;
    color: #0d0f14;
}
.btn-warning:hover {
    background: #e67e22;
    transform: translateY(-1px);
}

/* Garantir que a coluna de ações tenha largura suficiente */
.users-table td:last-child {
    min-width: 150px;
    white-space: nowrap;
}

/* Container dos botões */
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: nowrap;
}

/* Formulários inline */
.action-buttons form {
    display: inline-block;
    margin: 0;
    padding: 0;
}
.flex {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success {
    background: rgba(40, 167, 69, 0.15);
    border: 1px solid #28a745;
    color: #28a745;
}
@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-group {
        width: 100%;
    }
    .filter-select {
        width: 100%;
    }
    .filter-actions {
        margin-left: 0;
        flex-direction: column;
    }
    .btn-filter {
        justify-content: center;
        width: 100%;
    }
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .sexta-users {
        padding: 12px;
    }
    .sexta-user {
        width: calc(50% - 6px);
    }
}
@media (max-width: 480px) {
    .grid-stats {
        grid-template-columns: 1fr;
    }
    .sexta-user {
        width: 100%;
    }
}
</style>

<div class="page-header">
    <h2>Confirme ou cancele participações nos eventos</h2>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>

<!-- Cards de estatísticas COM BORDAS COLORIDAS -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-number"><?= $totalPendentes ?></div>
        <div class="stat-text">Pendentes</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totalConfirmados ?></div>
        <div class="stat-text">Confirmados</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= count($attendances) ?></div>
        <div class="stat-text">Total</div>
    </div>
</div>

<!-- Filtros -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label class="filter-label">Ano</label>
            <select name="year" onchange="this.form.submit()" class="filter-select">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Evento</label>
            <select name="event_id" onchange="this.form.submit()" class="filter-select">
                <option value="0">Todos os eventos</option>
                <?php foreach ($eventsList as $ev): ?>
                    <option value="<?= $ev['id'] ?>" <?= $eventId == $ev['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ev['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <?php if ($eventId): ?>
                <a href="<?= BASE_URL ?>/admin/attendances.php?year=<?= $year ?>" class="btn-filter btn-filter-clear">Limpar filtros</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- CONFIRMAÇÕES DE SEXTA-FEIRA - APENAS PRÓXIMA -->
<div class="card-sextas">
    <div class="card-sextas-header">
        <h3>🎯 Confirmações para a Próxima Sexta-feira - <?= date('d/m/Y', strtotime($proximaSexta)) ?></h3>
        <span class="badge" style="background:#f39c12; color:#0d0f14;">Total: <?= count($sextasList) ?> confirmações</span>
    </div>
    <div class="sextas-grid">
        <?php if (empty($sextasAgrupadas)): ?>
            <div class="empty-sextas">
                <div style="font-size:2rem; margin-bottom:10px;">📅</div>
                <p>Nenhuma confirmação para a próxima Sexta-feira (<?= date('d/m/Y', strtotime($proximaSexta)) ?>).</p>
                <p style="font-size:0.7rem;">Os membros podem confirmar presença pelo dashboard.</p>
            </div>
        <?php else: ?>
            <?php foreach ($sextasAgrupadas as $data => $confirmacoes): 
                $dataFormatada = date('d/m/Y', strtotime($data));
            ?>
                <div class="sexta-group">
                    <div class="sexta-date">
                        📅 <?= $dataFormatada ?> 
                        <span>(Sexta-feira)</span>
                        <span style="float:right;">👥 <?= count($confirmacoes) ?> confirmados</span>
                    </div>
                    <div class="sexta-users">
                        <?php foreach ($confirmacoes as $conf): ?>
                            <div class="sexta-user">
                                <div class="sexta-user-avatar">
                                    <?= mb_strtoupper(mb_substr($conf['user_name'], 0, 1)) ?>
                                </div>
                                <div class="sexta-user-info">
                                    <div class="sexta-user-name">
                                        <?= htmlspecialchars($conf['user_name']) ?>
                                        <?php if ($conf['role'] === 'admin'): ?>
                                            <span style="color:#f39c12; font-size:0.6rem;"> (Admin)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sexta-user-role">
                                        Confirmado em: <?= date('d/m/Y H:i', strtotime($conf['confirmed_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de presenças -->
<div class="card-table">
    <div class="table-responsive">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data</th>
                    <th>Integrante</th>
                    <th>Email</th>
                    <th>KM</th>
                    <th>Status</th>
                    <th>Ações</th>
                 </thead>
            <tbody>
    <?php if (empty($attendances)): ?>
        <tr>
            <td colspan="7" class="text-muted text-center" style="padding:28px">Nenhuma participação encontrada.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($attendances as $att): ?>
            <tr>
                <td><strong><?= htmlspecialchars($att['event_title']) ?></strong></td>
                <td><?= date('d/m/Y', strtotime($att['event_date'])) ?></td>
                <td><?= htmlspecialchars($att['name']) ?></td>
                <td><?= htmlspecialchars($att['email']) ?></td>
                <td class="text-gold"><?= number_format($att['km_awarded'], 0, ',', '.') ?> km</td>
                <td>
                    <?php if ($att['status'] === 'confirmado'): ?>
                        <span class="badge badge-confirmado">Confirmado</span>
                    <?php else: ?>
                        <span class="badge badge-pendente">Pendente</span>
                    <?php endif; ?>
                </td>
               <td>
    <div class="action-buttons">
        <?php if ($att['status'] === 'pendente'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="confirmar">
                <input type="hidden" name="attendance_id" value="<?= $att['attendance_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-table-action btn-confirmar">Confirmar</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="cancelar">
                <input type="hidden" name="attendance_id" value="<?= $att['attendance_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-table-action btn-cancel">Cancelar</button>
            </form>
        <?php elseif ($att['status'] === 'confirmado'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="cancelar">
                <input type="hidden" name="attendance_id" value="<?= $att['attendance_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <button type="submit" class="btn-table-action btn-warning">Cancelar</button>
            </form>
        <?php endif; ?>
    </div>
</td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>
         
    </div>
</div>

<?php pageClose(); ?>