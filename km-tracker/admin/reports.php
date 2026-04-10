<?php
// admin/reports.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$year = (int)($_GET['year'] ?? date('Y'));
$userId = (int)($_GET['user_id'] ?? 0);
$eventId = (int)($_GET['event_id'] ?? 0);

// Função para retornar o nome do mês em português
function getMesEmPortugues($mesNumero = null) {
    $meses = array(
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    );
    
    if ($mesNumero === null) {
        $mesNumero = (int)date('m');
    }
    
    return $meses[$mesNumero];
}

// Ranking completo - incluindo administradores
$ranking = $db->query("
    SELECT u.name, u.id, u.role,
           COUNT(CASE WHEN a.status = 'confirmado' AND e.event_date <= CURDATE() THEN 1 END) AS presencas,
           COALESCE(SUM(CASE WHEN a.status = 'confirmado' AND e.event_date <= CURDATE() THEN e.km_awarded + a.km_extra ELSE 0 END), 0) AS total_km
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = $year
    WHERE u.active = 1
    GROUP BY u.id, u.name, u.role
    ORDER BY total_km DESC
")->fetchAll();

// Eventos do ano
$events = $db->query("
    SELECT e.*, 
           COUNT(CASE WHEN a.status = 'confirmado' THEN 1 END) AS presentes,
           COALESCE(SUM(CASE WHEN a.status = 'confirmado' THEN e.km_awarded + a.km_extra ELSE 0 END), 0) AS total_km_evento
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id
    WHERE YEAR(e.event_date) = $year
    GROUP BY e.id
    ORDER BY e.event_date DESC
")->fetchAll();

// ============================================================
// PARTICIPAÇÃO NAS SEXTAS-FEIRAS (APENAS MÊS ATUAL)
// ============================================================

// Buscar confirmações de Sextas do mês atual
$mesAtual = date('m');
$anoAtual = date('Y');

$sextasStats = $db->query("
    SELECT 
        sc.data_sexta,
        COUNT(sc.id) as total_confirmacoes
    FROM sextas_confirmacoes sc
    WHERE MONTH(sc.data_sexta) = $mesAtual AND YEAR(sc.data_sexta) = $anoAtual
    GROUP BY sc.data_sexta
    ORDER BY sc.data_sexta ASC
")->fetchAll();

// Criar array com as datas do mês
$sextasMap = array();
foreach ($sextasStats as $s) {
    $sextasMap[$s['data_sexta']] = $s['total_confirmacoes'];
}

// Gerar todas as Sextas do mês atual
$sextasDoMes = array();
$data = new DateTime("first friday of $anoAtual-$mesAtual-01");
while ($data->format('m') == $mesAtual) {
    $sextasDoMes[] = $data->format('Y-m-d');
    $data->modify('+7 days');
}

$totalParticipantes = 0;

// Listas para filtros
$usersList = $db->query("SELECT id, name FROM users WHERE active = 1 ORDER BY name")->fetchAll();
$eventsList = $db->query("SELECT id, title FROM events WHERE YEAR(event_date) = $year ORDER BY event_date DESC")->fetchAll();

pageOpen("Relatórios", "reports", "Relatórios do Sistema");
?>

<style>
.filter-bar {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 1px solid var(--border);
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
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.filter-select {
    padding: 10px 32px 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
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
    transition: all 0.2s;
}
.filter-select:hover {
    border-color: var(--gold);
}
.filter-select:focus {
    outline: none;
    border-color: var(--gold);
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
    border: 1px solid var(--border);
    font-family: inherit;
    text-decoration: none;
    background: white;
    color: #0d0f14;
}
.btn-filter:hover {
    background: #f5f5f5;
    transform: translateY(-1px);
    border-color: var(--gold);
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
.badge-admin {
    background: #6c5ce7;
    color: white;
    font-size: 0.6rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
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
}
@media print {
    .sidebar, .topbar, .filter-bar, .page-header-actions, .btn, .pagination {
        display: none !important;
    }
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Análise de dados do ano <?= $year ?></h2>
        </div>
    </div>
</div>

<!-- Filtros padronizados -->
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
            <label class="filter-label">Integrantes</label>
            <select name="user_id" class="filter-select">
                <option value="0">Todos os integrantes</option>
                <?php foreach ($usersList as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="filter-label">Evento</label>
            <select name="event_id" class="filter-select">
                <option value="0">Todos os eventos</option>
                <?php foreach ($eventsList as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $eventId == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <?php if ($userId > 0 || $eventId > 0): ?>
                <a href="<?= BASE_URL ?>/admin/reports.php?year=<?= $year ?>" class="btn-filter btn-filter-clear">Limpar filtros</a>
            <?php endif; ?>
            <button type="submit" class="btn-filter">Aplicar filtros</button>
            <a href="<?= BASE_URL ?>/admin/report_pdf.php?year=<?= $year ?>&type=complete<?= $userId > 0 ? '&user_id=' . $userId : '' ?><?= $eventId > 0 ? '&event_id=' . $eventId : '' ?>" class="btn-filter" target="_blank">Exportar PDF</a>
           
        </div>
    </form>
</div>

<!-- ============================================================
     NOVO CARD: PARTICIPAÇÃO NAS SEXTAS-FEIRAS
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Participação nas Sextas-feiras - <?= getMesEmPortugues() ?> de <?= date('Y') ?></span>
        <span class="badge" style="background:#f39c12; color:#0d0f14;">Total: 0 confirmações</span>
    </div>
    <div class="table-wrap">
        <table class="users-table">
            <thead>
                
                    <th>Data</th>
                    <th>Dia da Semana</th>
                    <th>Integrantes</th>
                    <th>Status</th>
                </thead>
            <tbody>
                <tr>
                    <tbody>
    <?php foreach ($sextasDoMes as $data): 
        $dataObj = new DateTime($data);
        $dataFormatada = $dataObj->format('d/m/Y');
        $diaSemana = $dataObj->format('l');
        $diasSemana = array(
            'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        );
        $nomeDia = $diasSemana[$diaSemana];
        $participantes = isset($sextasMap[$data]) ? $sextasMap[$data] : 0;
        $totalParticipantes += $participantes;
    ?>
    <tr>
        <td style="font-weight:600;"><?= $dataFormatada ?></td>
        <td><?= $nomeDia ?></td>
        <td>
            <?php if ($participantes > 0): ?>
                <span class="badge" style="background:#f39c12; color:#0d0f14; padding:6px 14px;"><?= $participantes ?></span>
            <?php else: ?>
                <span class="badge badge-muted">0</span>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($participantes > 0): ?>
                <span style="color:#28a745;">Confirmada</span>
            <?php else: ?>
                <span style="color:#f39c12;">Aguardando</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($sextasDoMes)): ?>
    <tr>
        <td colspan="4" class="text-muted text-center">Nenhuma Sexta-feira no mês atual.</td>
    </tr>
    <?php endif; ?>
</tbody>
<tfoot>
    <tr style="background: var(--bg-card2);">
        <td colspan="2" style="text-align:right; font-weight:700;">TOTAL:</td>
        <td style="font-weight:700; color:var(--gold-light);"><?= $totalParticipantes ?> confirmações</td>
        <td></td>
    </tr>
</tfoot>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Ranking Geral -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Ranking Geral de KM - <?= $year ?></span>
        <a href="<?= BASE_URL ?>/admin/report_pdf.php?year=<?= $year ?>&type=ranking<?= $userId > 0 ? '&user_id=' . $userId : '' ?><?= $eventId > 0 ? '&event_id=' . $eventId : '' ?>" class="btn btn-sm btn-accent" target="_blank">Exportar Ranking PDF</a>
    </div>
    <div class="table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Integrante</th>
                    <th>Perfil</th>
                    <th>Presenças</th>
                    <th>KM Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $i => $row): ?>
                <tr>
                    <td><span class="badge <?= $i === 0 ? 'badge-gold' : ($i === 1 ? 'badge-accent' : 'badge-muted') ?>"><?= $i + 1 ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($row['name']) ?></strong>
                        <?php if (($row['role'] ?? 'user') === 'admin'): ?>
                            <span class="badge-admin">Admin</span>
                        <?php endif; ?>
                     </n></td>
                    <td style="text-align: center;">
    <span class="badge" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 500; min-width: 100px; text-align: center; background: <?= ($row['role'] ?? 'user') === 'admin' ? '#6c5ce7' : '#f39c12' ?>; color: <?= ($row['role'] ?? 'user') === 'admin' ? 'white' : '#0d0f14' ?>;">
        <?= ($row['role'] ?? 'user') === 'admin' ? 'Administrador' : 'Integrante' ?>
    </span>
</td>
                    <td><?= $row['presencas'] ?> </n></td>
                    <td class="text-gold"><?= number_format($row['total_km'], 0, ',', '.') ?> km</n></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ranking)): ?>
                <tr><td colspan="5" class="text-muted text-center">Nenhum dado encontrado.</strong></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Eventos do Ano -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Eventos Realizados - <?= $year ?></span>
    </div>
    <div class="table-wrap">
        <table class="users-table">
            <thead>
                    <th>Evento</th>
                    <th>Data</th>
                    <th class="hide-mobile">Local</th>
                    <th>KM</th>
                    <th>Presentes</th>
                    <th class="hide-mobile">KM Total</th>
                </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ev['title']) ?></strong></td>
                    <td><?= date('d/m/Y', strtotime($ev['event_date'])) ?></td>
                    <td class="hide-mobile"><?= htmlspecialchars($ev['location'] ?: '—') ?></td>
                    <td class="text-gold"><?= number_format($ev['km_awarded'], 0, ',', '.') ?> km</td>
                    <td><span class="badge badge-accent"><?= $ev['presentes'] ?></span></td>
                    <td class="hide-mobile text-gold"><?= number_format($ev['total_km_evento'], 0, ',', '.') ?> km</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr><td colspan="6" class="text-muted text-center">Nenhum evento encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageClose(); ?>