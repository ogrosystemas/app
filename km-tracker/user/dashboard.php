<?php
// user/dashboard.php - COM BORDAS COLORIDAS (mantendo layout original)
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

// Configurar localidade para português
setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese');

$db = db();
$me = currentUser();
$uid = $me['id'];
$year = (int)($_GET['year'] ?? date('Y'));

// Processar confirmação de sexta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmar_sexta') {
    verifyCsrf();
    $dataSexta = $_POST['data_sexta'] ?? '';
    
    if ($dataSexta && confirmarSexta($db, $uid, $dataSexta)) {
        $_SESSION['flash_success'] = 'Presença confirmada na Sexta-feira ' . date('d/m/Y', strtotime($dataSexta)) . '!';
    } else {
        $_SESSION['flash_error'] = 'Erro ao confirmar presença.';
    }
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

// Buscar apenas a próxima Sexta
$proximaSexta = getProximaSexta($db, $uid, $year);

// Totais do usuário no ano (apenas confirmados e com data passada)
$totalsStmt = $db->prepare('
    SELECT
        COUNT(CASE WHEN a.status = "confirmado" AND e.event_date <= CURDATE() THEN 1 END) AS presencas,
        COALESCE(SUM(CASE WHEN a.status = "confirmado" AND e.event_date <= CURDATE() THEN e.km_awarded + a.km_extra ELSE 0 END), 0) AS total_km
    FROM attendances a
    JOIN events e ON e.id = a.event_id
    WHERE a.user_id = ? AND YEAR(e.event_date) = ?
');
$totalsStmt->execute([$uid, $year]);
$totals = $totalsStmt->fetch();

// Total de eventos disponíveis no ano
$evStmt = $db->prepare('SELECT COUNT(*) FROM events WHERE active=1 AND YEAR(event_date)=? AND event_date <= CURDATE()');
$evStmt->execute([$year]);
$totalEventos = (int)$evStmt->fetchColumn();

// Taxa de participação
$txParticipacao = $totalEventos > 0 ? round(($totals['presencas'] / $totalEventos) * 100) : 0;

// Dados da moto
// Mini calendário - eventos do mês atual
$calMes = (int)date('m');
$calAno = (int)date('Y');
$calStmt = $db->prepare("
    SELECT e.*, a.status as user_status
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ?
    WHERE e.active = 1 AND MONTH(e.event_date) = ? AND YEAR(e.event_date) = ?
    ORDER BY e.event_date ASC
");
$calStmt->execute([$uid, $calMes, $calAno]);
$calEventos = $calStmt->fetchAll();
$calEventosPorDia = [];
foreach ($calEventos as $ev) {
    $calEventosPorDia[(int)date('d', strtotime($ev['event_date']))][] = $ev;
}

// Escalas do usuário no mês atual
$diasEscalaBar = [];
$diasEscalaChurrasco = [];
try {
    $escalaBar = $db->prepare("
        SELECT semana_inicio FROM escala_bar
        WHERE (user1_id = ? OR user2_id = ?)
        AND MONTH(semana_inicio) = ? AND YEAR(semana_inicio) = ?
    ");
    $escalaBar->execute([$uid, $uid, $calMes, $calAno]);
    foreach ($escalaBar->fetchAll() as $eb) {
        $dt = new DateTime($eb['semana_inicio']);
        $dow = (int)$dt->format('N');
        $diffParaSexta = (5 - $dow + 7) % 7;
        $dt->modify("+{$diffParaSexta} days");
        if ((int)$dt->format('m') === $calMes && (int)$dt->format('Y') === $calAno) {
            $diasEscalaBar[(int)$dt->format('d')] = true;
        }
    }

    $escalaChurrasco = $db->prepare("
        SELECT ec.semana_inicio FROM escala_churrasco ec
        JOIN churrasco_grupo_membros cgm ON cgm.grupo_id = ec.grupo_id
        WHERE cgm.user_id = ?
        AND MONTH(ec.semana_inicio) = ? AND YEAR(ec.semana_inicio) = ?
    ");
    $escalaChurrasco->execute([$uid, $calMes, $calAno]);
    foreach ($escalaChurrasco->fetchAll() as $ec) {
        for ($d = 0; $d <= 6; $d++) {
            $ts = strtotime($ec['semana_inicio'] . " +$d days");
            if ((int)date('m', $ts) === $calMes && (int)date('Y', $ts) === $calAno) {
                $diasEscalaChurrasco[(int)date('d', $ts)] = true;
            }
        }
    }
} catch (\Throwable $e) {
    // Tabelas de escala não existem ainda — ignorar silenciosamente
    error_log('KM-Tracker dashboard escala error: ' . $e->getMessage());
}

$motoStmt = $db->prepare('SELECT moto_marca, moto_kml, moto_tanque, gas_preco FROM users WHERE id=?');
$motoStmt->execute([$uid]);
$moto = $motoStmt->fetch();

$kml        = (float)($moto['moto_kml']    ?? 0);
$kmTotal    = (float)($totals['total_km']  ?? 0);
$temMoto    = $kml > 0;
$temKm      = $kmTotal > 0;

// Ranking
$rankStmt = $db->prepare('
    SELECT COUNT(*) + 1
    FROM (
        SELECT user_id, SUM(CASE WHEN a.status = "confirmado" AND e.event_date <= CURDATE() THEN e.km_awarded + a.km_extra ELSE 0 END) AS total_km
        FROM attendances a
        JOIN events e ON e.id = a.event_id
        WHERE YEAR(e.event_date) = ?
        GROUP BY user_id
    ) outros
    WHERE total_km > (
        SELECT COALESCE(SUM(CASE WHEN a2.status = "confirmado" AND e2.event_date <= CURDATE() THEN e2.km_awarded + a2.km_extra ELSE 0 END), 0)
        FROM attendances a2
        JOIN events e2 ON e2.id = a2.event_id
        WHERE a2.user_id = ? AND YEAR(e2.event_date) = ?
    )
');
$rankStmt->execute([$year, $uid, $year]);
$userRank = $rankStmt->fetchColumn() ?: '1';

$firstName = explode(' ', $me['name'])[0];
pageOpen("Olá, {$firstName}!", 'dashboard');
?>

<style>
/* Seletor de ano */
.year-box {
    background: none;
    border-radius: 12px;
    padding: 12px 20px;
    border: 0a;
    display: inline-block;
}
.year-select {
    padding: 8px 32px 8px 12px;
    border-radius: 8px;
    border:1px solid var(--border);
    background: white;
    color: #0d0f14;
    font-size: 0.85rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236e7485' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}
.year-select:hover {
    border-color: #f39c12;
}

/* Card Sexta */
.grid-sexta-cal {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
    align-items: stretch;
}
.grid-sexta-cal > div {
    height: 100%;
}

.card-sexta {
    background: linear-gradient(135deg, #14161c, #1a0f05);
    border: 1px solid #f39c12;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
}
.card-sexta-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #f5b041;
}
.card-sexta-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    background:var(--bg-input);
    border-radius: 8px;
    padding: 16px;
}
.card-sexta-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.card-sexta-name {
    font-weight: 700;
    font-size: 1rem;
    color: #f5b041;
}
.card-sexta-date {
    font-size: 0.85rem;
    color:var(--text-muted);
}
.card-sexta-days {
    font-size: 0.7rem;
    color:var(--text-dim);
}
.btn-sexta {
    background: #f39c12;
    color: #0d0f14;
    border: none;
    padding: 8px 20px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
}
.btn-sexta-confirmado {
    background: #28a745;
    color: white;
    cursor: default;
}

/* Grid de estatísticas com bordas coloridas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

/* Cards com bordas coloridas */
.stat-card {
    background:var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border:1px solid var(--border);
    transition: all 0.3s ease;
}

/* Borda superior colorida por card */
.stat-card:nth-child(1) {
    border-top: 3px solid #f39c12; /* KM Total - Dourado */
}
.stat-card:nth-child(2) {
    border-top: 3px solid #28a745; /* Eventos - Verde */
}
.stat-card:nth-child(3) {
    border-top: 3px solid #7b9fff; /* Participação - Azul */
}
.stat-card:nth-child(4) {
    border-top: 3px solid #dc3545; /* Ranking - Vermelho */
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
.stat-card:nth-child(4):hover {
    box-shadow: 0 8px 24px rgba(220,53,69,0.2);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #f5b041;
}
.stat-text {
    font-size: 0.7rem;
    color:var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 8px;
}

/* Card padrón */
.card-default {
    background:var(--bg-card);
    border-radius: 12px;
    border:1px solid var(--border);
    overflow: hidden;
    margin-bottom: 24px;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom:1px solid var(--border);
}
.card-header h3 {
    font-size: 1rem;
    font-weight: 600;
}
.card-body {
    padding: 20px;
}
.btn-link {
    background: transparent;
    border:1px solid var(--border);
    color:var(--text-muted);
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.7rem;
    text-decoration: none;
    display: inline-block;
}
.btn-link:hover {
    background:var(--bg-input);
}

/* Consumo */
.consumo-grid {
    display: flex;
    justify-content: space-around;
    gap: 20px;
    flex-wrap: wrap;
}
.consumo-item {
    text-align: center;
    flex: 1;
}
.consumo-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f5b041;
}
.consumo-label {
    font-size: 0.7rem;
    color:var(--text-dim);
    text-transform: uppercase;
    margin-top: 4px;
}

/* Progresso */
.progress-wrap {
    background:var(--bg-input);
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    margin-bottom: 16px;
}
.progress-fill {
    background: #f39c12;
    height: 100%;
    border-radius: 10px;
}
.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color:var(--text-muted);
}

/* Lista de eventos */
.event-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.event-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    background:var(--bg-input);
    border-radius: 8px;
    border:1px solid var(--border);
}
.event-date {
    text-align: center;
    min-width: 50px;
}
.event-day {
    font-size: 1.2rem;
    font-weight: 700;
    color: #f5b041;
}
.event-month {
    font-size: 0.6rem;
    color:var(--text-dim);
    text-transform: uppercase;
}
.event-info {
    flex: 1;
}
.event-title {
    font-weight: 600;
    font-size: 0.9rem;
}
.event-location {
    font-size: 0.7rem;
    color:var(--text-muted);
    margin-top: 2px;
}
.event-km {
    font-size: 0.7rem;
    color: #f5b041;
    margin-top: 4px;
}
.badge-confirmado {
    background: #28a745;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
}

/* Responsivo */
@media (max-width: 1000px) {
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .grid-sexta-cal {
        grid-template-columns: 1fr;
    }
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .stat-card:nth-child(1) { border-top: 3px solid #f39c12 !important; }
    .stat-card:nth-child(2) { border-top: 3px solid #28a745 !important; }
    .stat-card:nth-child(3) { border-top: 3px solid #7b9fff !important; }
    .stat-card:nth-child(4) { border-top: 3px solid #dc3545 !important; }
    .card-sexta-content {
        flex-direction: column;
        text-align: center;
    }
    .consumo-grid {
        flex-direction: column;
    }
}
@media (max-width: 480px) {
    .grid-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Acompanhe sua quilometragem e consumo de combustível</h2>
        </div>
        <div class="page-header-actions">
            <div class="year-box">
                <form method="GET">
                    <select name="year" onchange="this.form.submit()" class="year-select">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<!-- Card Confirmar Sexta + Mini Calendário lado a lado -->
<?php
$primeiroDia = mktime(0,0,0,$calMes,1,$calAno);
$diasNoMes = (int)date('t', $primeiroDia);
$diaSemanaInicio = (int)date('N', $primeiroDia);
$mesesNomes = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<div class="grid-sexta-cal">

    <!-- Card Confirmar Sexta -->
    <div class="card-sexta">
        <div class="card-sexta-title">🎯 Confirmar Sexta</div>
        <?php if (!$proximaSexta): ?>
            <div style="color:var(--text-dim); padding: 20px; text-align: center;">Nenhuma Sexta disponível para confirmar.</div>
        <?php else: 
            $hoje = new DateTime();
            $dataEvento = new DateTime($proximaSexta['data']);
            $diferenca = $hoje->diff($dataEvento)->days;
            $mensagem = $diferenca == 0 ? "Hoje é o dia! Confirme sua presença." : ($diferenca == 1 ? "Amanhã é a Sexta! Confirme já." : "Faltam {$diferenca} dias para a Sexta.");
        ?>
            <div class="card-sexta-content">
                <div class="card-sexta-info">
                    <div class="card-sexta-name">Sexta-feira do Mês</div>
                    <div class="card-sexta-date">📆 <?= date('d/m/Y', strtotime($proximaSexta['data'])) ?></div>
                    <div class="card-sexta-days"><?= $mensagem ?></div>
                </div>
                <div>
                    <?php if ($proximaSexta['ja_confirmou']): ?>
                        <span class="btn-sexta btn-sexta-confirmado">✅ Confirmado</span>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="confirmar_sexta">
                            <input type="hidden" name="data_sexta" value="<?= $proximaSexta['data'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <button type="submit" class="btn-sexta">✅ Confirmar Presença</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mini Calendário -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <span style="font-size:.9rem;font-weight:700;color:var(--text)">📅 <?= $mesesNomes[$calMes] ?> <?= $calAno ?></span>
            <a href="<?= BASE_URL ?>/user/calendario.php" style="font-size:.72rem;color:#f39c12;text-decoration:none">Ver tudo →</a>
        </div>
        <!-- Dias da semana -->
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:4px">
            <?php foreach (['S','T','Q','Q','S','S','D'] as $d): ?>
            <div style="text-align:center;font-size:.6rem;font-weight:700;color:var(--text-dim);padding:3px 0"><?= $d ?></div>
            <?php endforeach; ?>
        </div>
        <!-- Dias -->
        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px">
            <?php for ($i=1; $i < $diaSemanaInicio; $i++): ?><div></div><?php endfor; ?>
            <?php for ($dia=1; $dia<=$diasNoMes; $dia++):
                $isToday      = ($dia == date('d') && $calMes == date('m') && $calAno == date('Y'));
                $hasEvent     = isset($calEventosPorDia[$dia]);
                $hasBar       = isset($diasEscalaBar[$dia]);
                $hasChurrasco = isset($diasEscalaChurrasco[$dia]);
                $hasEscala    = $hasBar || $hasChurrasco;
                $evStatus     = $hasEvent ? ($calEventosPorDia[$dia][0]['user_status'] ?? '') : '';
                $dotColor     = $evStatus === 'confirmado' ? '#28a745' : '#20c997';
                $bgColor      = $isToday ? 'background:#f39c12;' : ($hasEvent ? 'background:#28a74510;' : ($hasEscala ? 'background:rgba(99,102,241,.08);' : ''));
            ?>
            <div style="text-align:center;padding:5px 1px;border-radius:5px;position:relative;<?= $bgColor ?>cursor:<?= ($hasEvent||$hasEscala)?'pointer':'default' ?>"
                 <?php if ($hasEvent): ?>onclick="location.href='<?= BASE_URL ?>/user/calendario.php?mes=<?= $calMes ?>&ano=<?= $calAno ?>'"<?php endif; ?>>
                <span style="font-size:.7rem;font-weight:<?= $isToday?'800':'500' ?>;color:<?= $isToday?'#0d0f14':(($hasEvent||$hasEscala)?'var(--text)':'var(--text-dim)') ?>">
                    <?= $dia ?>
                </span>
                <?php if (!$isToday && ($hasEvent||$hasBar||$hasChurrasco)): ?>
                <div style="display:flex;justify-content:center;gap:2px;margin-top:1px">
                    <?php if ($hasEvent): ?><div style="width:3px;height:3px;border-radius:50%;background:<?= $dotColor ?>"></div><?php endif; ?>
                    <?php if ($hasBar): ?><div style="width:3px;height:3px;border-radius:50%;background:#f97316"></div><?php endif; ?>
                    <?php if ($hasChurrasco): ?><div style="width:3px;height:3px;border-radius:50%;background:#8b5cf6"></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
        <!-- Escalas do mês -->
        <?php if (!empty($diasEscalaBar) || !empty($diasEscalaChurrasco)): ?>
        <div style="border-top:1px solid var(--border);margin-top:8px;padding-top:8px">
            <?php
            $escalaBarListaRows = [];
            $escalaChurrascoListaRows = [];
            try {
                $escalaBarLista = $db->prepare("SELECT semana_inicio FROM escala_bar WHERE (user1_id=? OR user2_id=?) AND MONTH(semana_inicio)=? AND YEAR(semana_inicio)=? ORDER BY semana_inicio ASC");
                $escalaBarLista->execute([$uid,$uid,$calMes,$calAno]);
                $escalaBarListaRows = $escalaBarLista->fetchAll();
                $escalaChurrascoLista2 = $db->prepare("SELECT ec.semana_inicio, cg.nome as grupo_nome FROM escala_churrasco ec JOIN churrasco_grupo_membros cgm ON cgm.grupo_id=ec.grupo_id JOIN churrasco_grupos cg ON cg.id=ec.grupo_id WHERE cgm.user_id=? AND MONTH(ec.semana_inicio)=? AND YEAR(ec.semana_inicio)=? ORDER BY ec.semana_inicio ASC");
                $escalaChurrascoLista2->execute([$uid,$calMes,$calAno]);
                $escalaChurrascoListaRows = $escalaChurrascoLista2->fetchAll();
            } catch (\Throwable $e) { error_log('escala list error: ' . $e->getMessage()); }
            foreach ($escalaBarListaRows as $eb):
                $dtBar = new DateTime($eb['semana_inicio']);
                $dowBar = (int)$dtBar->format('N');
                $diffBar = (5 - $dowBar + 7) % 7;
                $dtBar->modify('+' . $diffBar . ' days');
                $sexta = $dtBar->format('d/m');
            ?>
            <div style="display:flex;align-items:center;gap:6px;padding:3px 0;font-size:.72rem">
                <span style="width:6px;height:6px;border-radius:50%;background:#f97316;flex-shrink:0"></span>
                <span style="color:#f97316;font-weight:700">Sexta <?= $sexta ?></span>
                <span style="color:var(--text-dim)">Bar</span>
            </div>
            <?php endforeach; ?>
            <?php
            foreach ($escalaChurrascoListaRows as $ec):
                $dtC = new DateTime($ec['semana_inicio']); $ini = $dtC->format('d/m'); $dtC->modify('+6 days'); $fim = $dtC->format('d/m');
            ?>
            <div style="display:flex;align-items:center;gap:6px;padding:3px 0;font-size:.72rem">
                <span style="width:6px;height:6px;border-radius:50%;background:#8b5cf6;flex-shrink:0"></span>
                <span style="color:#8b5cf6;font-weight:700"><?= $ini ?>–<?= $fim ?></span>
                <span style="color:var(--text-dim)">Churrasco</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <!-- Legenda -->
        <div style="border-top:1px solid var(--border);margin-top:8px;padding-top:6px;display:flex;flex-wrap:wrap;gap:8px">
            <span style="font-size:.62rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:5px;height:5px;border-radius:50%;background:#28a745;display:inline-block"></span>Evento</span>
            <span style="font-size:.62rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:5px;height:5px;border-radius:50%;background:#f97316;display:inline-block"></span>Bar</span>
            <span style="font-size:.62rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:5px;height:5px;border-radius:50%;background:#8b5cf6;display:inline-block"></span>Churrasco</span>
        </div>
    </div>

</div>

<!-- Cards de estatísticas COM BORDAS COLORIDAS -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-number"><?= number_format($kmTotal, 0, ',', '.') ?></div>
        <div class="stat-text">KM Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $totals['presencas'] ?></div>
        <div class="stat-text">Eventos</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $txParticipacao ?>%</div>
        <div class="stat-text">Participação</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $userRank ?>º</div>
        <div class="stat-text">Ranking</div>
    </div>
</div>

<!-- Consumo de Combustível -->
<div class="card-default">
    <div class="card-header">
        <h3>⛽ Consumo de Combustível — <?= $year ?></h3>
        <a href="<?= BASE_URL ?>/profile.php" class="btn-link"><?= $temMoto ? '✏️ Editar moto' : '+ Cadastrar moto' ?></a>
    </div>
    <div class="card-body">
        <?php if (!$temMoto): ?>
            <div style="text-align:center;padding:20px;">
                <div style="font-size:2rem;">🏍️</div>
                <p style="margin:10px 0; color:var(--text-muted);">Cadastre os dados da sua moto para calcular o consumo.</p>
                <a href="<?= BASE_URL ?>/profile.php" class="btn-sexta" style="display:inline-block;">Cadastrar moto</a>
            </div>
        <?php elseif (!$temKm): ?>
            <div style="text-align:center;padding:20px;">
                <p style="color:var(--text-muted);">Nenhum KM registrado em <?= $year ?> ainda.</p>
            </div>
        <?php else: 
            $litrosGastos = round($kmTotal / $kml, 1);
        ?>
            <div class="consumo-grid">
                <div class="consumo-item">
                    <div class="consumo-value"><?= number_format($kmTotal, 0, ',', '.') ?></div>
                    <div class="consumo-label">KM Rodados</div>
                </div>
                <div class="consumo-item">
                    <div class="consumo-value"><?= number_format($kml, 1, ',', '.') ?> km/L</div>
                    <div class="consumo-label">Consumo</div>
                </div>
                <div class="consumo-item">
                    <div class="consumo-value"><?= number_format($litrosGastos, 1, ',', '.') ?> L</div>
                    <div class="consumo-label">Litros Gastos</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Participação -->
<div class="card-default">
    <div class="card-header">
        <h3>📊 Participação em <?= $year ?></h3>
    </div>
    <div class="card-body">
        <div class="progress-wrap">
            <div class="progress-fill" style="width: <?= $txParticipacao ?>%;"></div>
        </div>
        <div class="progress-stats">
            <span><?= $totals['presencas'] ?> participações</span>
            <span><?= $totalEventos ?> eventos no ano</span>
        </div>
    </div>
</div>

<!-- Próximos Eventos -->
<div class="card-default">
    <div class="card-header">
        <h3>🗓️ Próximos Eventos</h3>
        <a href="<?= BASE_URL ?>/user/events.php" class="btn-link">Ver todos</a>
        <a href="<?= BASE_URL ?>/user/calendario.php" class="btn-link" style="margin-left:8px">📅 Calendário</a>
    </div>
    <div class="card-body">
        <?php
        $upStmt = $db->prepare('
            SELECT e.*,
                   (SELECT COUNT(*) FROM attendances aa WHERE aa.event_id = e.id AND aa.user_id = ? AND aa.status = "confirmado") AS ja_presente
            FROM events e
            WHERE e.active = 1 AND e.event_date >= CURDATE()
            ORDER BY e.event_date ASC LIMIT 4
        ');
        $upStmt->execute([$uid]);
        $upcoming = $upStmt->fetchAll();
        ?>
        <?php if (empty($upcoming)): ?>
            <p class="text-muted text-center">Nenhum evento próximo.</p>
        <?php else: ?>
            <div class="event-list">
                <?php foreach ($upcoming as $ev): 
                    $timestamp = strtotime($ev['event_date']);
                    $dia = date('d', $timestamp);
                    $mesNumero = date('m', $timestamp);
                    
                    // Array com meses em português
                    $meses = [
                        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
                        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                        9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
                    ];
                    $mes = $meses[(int)$mesNumero];
                ?>
                    <div class="event-item">
                        <div class="event-date">
                            <div class="event-day"><?= $dia ?></div>
                            <div class="event-month"><?= $mes ?></div>
                        </div>
                        <div class="event-info">
                            <div class="event-title"><?= htmlspecialchars($ev['title']) ?></div>
                            <?php if ($ev['location']): ?>
                                <div class="event-location">📍 <?= htmlspecialchars($ev['location']) ?></div>
                            <?php endif; ?>
                            <div class="event-km"><?= number_format($ev['km_awarded'], 0, ',', '.') ?> km</div>
                        </div>
                        <?php if ($ev['ja_presente']): ?>
                            <span class="badge-confirmado">✓ Confirmado</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php pageClose(); ?>
