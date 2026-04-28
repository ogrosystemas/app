<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$me = currentUser();
$uid = $me['id'];
$year = (int)($_GET['year'] ?? date('Y'));

// Processar confirmação de sexta para admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmar_sexta') {
    verifyCsrf();
    $dataSexta = $_POST['data_sexta'] ?? '';
    
    if ($dataSexta && confirmarSexta($db, $uid, $dataSexta)) {
        $_SESSION['flash_success'] = 'Presença confirmada na Sexta-feira ' . date('d/m/Y', strtotime($dataSexta)) . '!';
    } else {
        $_SESSION['flash_error'] = 'Erro ao confirmar presença.';
    }
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// Buscar apenas a próxima Sexta
$proximaSexta = getProximaSexta($db, $uid, $year);

// Escalas do mês para o mini-calendário
$calMes = (int)date('m');
$calAno = (int)date('Y');
$mesesNomes = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

// Escalas do mês — protegidas contra tabelas ausentes no banco
$diasAdmBar = []; $diasAdmChurrasco = [];
$todasEscalasBar = []; $todasEscalasChurrasco = [];
$diasTodosBar = []; $diasTodosChurrasco = [];
try {
    // Bar — dias do admin
    $s = $db->prepare("SELECT semana_inicio FROM escala_bar WHERE (user1_id=? OR user2_id=?) AND MONTH(semana_inicio)=? AND YEAR(semana_inicio)=?");
    $s->execute([$uid, $uid, $calMes, $calAno]);
    foreach ($s->fetchAll() as $eb) {
        $dt = new DateTime($eb['semana_inicio']);
        $dow_dt = (int)$dt->format('N');
        $diff_dt = (5 - $dow_dt + 7) % 7;
        $dt->modify('+' . $diff_dt . ' days');
        if ((int)$dt->format('m')===$calMes && (int)$dt->format('Y')===$calAno) $diasAdmBar[(int)$dt->format('d')] = true;
    }
    // Churrasco — dias do admin
    $s = $db->prepare("SELECT ec.semana_inicio FROM escala_churrasco ec JOIN churrasco_grupo_membros cgm ON cgm.grupo_id=ec.grupo_id WHERE cgm.user_id=? AND MONTH(ec.semana_inicio)=? AND YEAR(ec.semana_inicio)=?");
    $s->execute([$uid, $calMes, $calAno]);
    foreach ($s->fetchAll() as $ec) {
        $dt = new DateTime($ec['semana_inicio']);
        $dow_dt = (int)$dt->format('N');
        $diff_dt = (5 - $dow_dt + 7) % 7;
        $dt->modify('+' . $diff_dt . ' days');
        if ((int)$dt->format('m')===$calMes && (int)$dt->format('Y')===$calAno) $diasAdmChurrasco[(int)$dt->format('d')] = true;
    }
    // Bar — todos do mês
    $s = $db->prepare("SELECT eb.semana_inicio, eb.user1_id, eb.user2_id, u1.name as nome1, u2.name as nome2 FROM escala_bar eb JOIN users u1 ON u1.id=eb.user1_id JOIN users u2 ON u2.id=eb.user2_id WHERE MONTH(eb.semana_inicio)=? AND YEAR(eb.semana_inicio)=? ORDER BY eb.semana_inicio");
    $s->execute([$calMes, $calAno]);
    $todasEscalasBar = $s->fetchAll();
    // Churrasco — todos do mês
    $s = $db->prepare("SELECT ec.semana_inicio, cg.nome as grupo_nome FROM escala_churrasco ec JOIN churrasco_grupos cg ON cg.id=ec.grupo_id WHERE MONTH(ec.semana_inicio)=? AND YEAR(ec.semana_inicio)=? ORDER BY ec.semana_inicio");
    $s->execute([$calMes, $calAno]);
    $todasEscalasChurrasco = $s->fetchAll();
    // Mapear dias do calendário
    foreach ($todasEscalasBar as $eb) {
        $dt = new DateTime($eb['semana_inicio']);
        $dow_dt = (int)$dt->format('N');
        $diff_dt = (5 - $dow_dt + 7) % 7;
        $dt->modify('+' . $diff_dt . ' days');
        if ((int)$dt->format('m')===$calMes && (int)$dt->format('Y')===$calAno) $diasTodosBar[(int)$dt->format('d')] = true;
    }
    foreach ($todasEscalasChurrasco as $ec) {
        for ($d=0; $d<=6; $d++) {
            $ts = strtotime($ec['semana_inicio']." +$d days");
            if ((int)date('m',$ts)===$calMes && (int)date('Y',$ts)===$calAno) $diasTodosChurrasco[(int)date('d',$ts)] = true;
        }
    }
} catch (\Throwable $e) {
    error_log('KM-Tracker admin escala error: '.$e->getMessage());
}

// ============================================================
// CÁLCULO DA META (80% do total de KM de todos os eventos)
// ============================================================

// Soma total de KM de TODOS os eventos (km_awarded já é ida+volta)
$totalEventosKmStmt = $db->prepare("
    SELECT COALESCE(SUM(km_awarded), 0) as total 
    FROM events 
    WHERE active = 1 AND YEAR(event_date) = ?
");
$totalEventosKmStmt->execute([$year]);
$totalEventosKm = (float)$totalEventosKmStmt->fetchColumn();

// Meta = 80% do total
$metaKm = $totalEventosKm * 0.8;

// Total de KM registrados (apenas confirmados e com data passada)
$totalKmRegistradoStmt = $db->prepare("
    SELECT COALESCE(SUM(e.km_awarded + a.km_extra), 0) as total 
    FROM attendances a 
    JOIN events e ON e.id = a.event_id 
    WHERE YEAR(e.event_date) = ? 
    AND a.status = 'confirmado'
    AND e.event_date <= CURDATE()
");
$totalKmRegistradoStmt->execute([$year]);
$totalKmRegistrado = (float)$totalKmRegistradoStmt->fetchColumn();

// Porcentagem atingida da meta
$porcentagemMeta = $metaKm > 0 ? min(100, round(($totalKmRegistrado / $metaKm) * 100)) : 0;

// O campo FALTAM agora mostra a META (80% do total), não o que falta
$faltaMeta = $metaKm;

// TOTAIS GERAIS
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE role='user' AND active=1")->fetchColumn();
$totalEvents = $db->query("SELECT COUNT(*) FROM events WHERE active=1 AND YEAR(event_date)=$year")->fetchColumn();
$totalPresencas = $db->query("
    SELECT COUNT(*) FROM attendances a 
    JOIN events e ON e.id = a.event_id 
    WHERE YEAR(e.event_date) = $year 
    AND a.status = 'confirmado'
    AND e.event_date <= CURDATE()
")->fetchColumn();

// Ranking
$ranking = $db->query("
    SELECT u.name, u.id,
           COUNT(CASE WHEN a.status = 'confirmado' AND e.event_date <= CURDATE() THEN 1 END) AS presencas,
           COALESCE(SUM(CASE WHEN a.status = 'confirmado' AND e.event_date <= CURDATE() THEN e.km_awarded + a.km_extra ELSE 0 END), 0) AS total_km
    FROM users u
    LEFT JOIN attendances a ON a.user_id = u.id
    LEFT JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = $year
    WHERE u.role = 'user' AND u.active = 1
    GROUP BY u.id, u.name
    ORDER BY total_km DESC
    LIMIT 10
")->fetchAll();

// Total de confirmações de Sextas
$totalSextasConfirmadas = countSextasConfirmadas($db, $year);

// Gráfico de participação por evento
$chartStmt = $db->query("
    SELECT e.title, e.event_date,
           COUNT(CASE WHEN a.status='confirmado' THEN 1 END) as confirmados,
           COUNT(CASE WHEN a.status='pendente' THEN 1 END) as pendentes
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id
    WHERE e.active=1 AND YEAR(e.event_date) = $year
    GROUP BY e.id, e.title, e.event_date
    ORDER BY e.event_date ASC
    LIMIT 10
");
$chartData = $chartStmt->fetchAll();

pageOpen("Painel Adm", "dashboard");
?>

<style>
/* Seletor de ano */
.year-box {
    background: var(--bg-card);
    border-radius: 12px;
    padding: 12px 20px;
    margin-bottom: 24px;
    border: 1px solid var(--border);
    display: inline-block;
}
.year-select {
    padding: 8px 32px 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
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
    border-color: var(--gold);
}

/* Grid 2 colunas para cards lado a lado */
.grid-meta-sexta {
    display: grid;
    grid-template-columns: 1fr 1fr 1.1fr;
    gap: 20px;
    margin-bottom: 30px;
    align-items: stretch;
}

/* Card Sexta */
.card-sexta {
    background: linear-gradient(135deg, #14161c, #1a0f05);
    border: 1px solid #f39c12;
    border-radius: 12px;
    padding: 20px;
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

/* Card Meta com gráfico pizza */
.card-meta-pizza {
    background:var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    border:1px solid var(--border);
}
.meta-pizza-header {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #f5b041;
}
.meta-pizza-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}
.pizza-container {
    flex-shrink: 0;
    position: relative;
    width: 150px;
    height: 150px;
}
.pizza-canvas {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(
        #28a745 0% <?= $porcentagemMeta ?>%,
        #2a2f3a <?= $porcentagemMeta ?>% 100%
    );
}
.pizza-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #f5b041;
}
.pizza-label {
    font-size: 0.7rem;
    color:var(--text-dim);
    margin-top: 4px;
}
.meta-stats {
    flex: 1;
}
.meta-stat-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 8px 0;
    border-bottom:1px solid var(--border);
}
.meta-stat-label {
    font-size: 0.75rem;
    color:var(--text-dim);
}
.meta-stat-value {
    font-weight: 600;
    color: #f5b041;
}
.meta-stat-value-danger {
    color: #dc3545;
}
.progress-wrap {
    background:var(--bg-input);
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
    margin-top: 12px;
}
.progress-fill {
    background: #f39c12;
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s;
}
.progress-percent {
    margin-top: 8px;
    text-align: center;
    font-size: 0.8rem;
    color:var(--text-muted);
}

/* Grid de estatísticas - 5 cards com bordas coloridas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

/* Cards com bordas coloridas e efeito hover */
.stat-item {
    background:var(--bg-card);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border:1px solid var(--border);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

/* Borda superior colorida por card */
.stat-item:nth-child(1) {
    border-top: 3px solid #f39c12; /* KM Registrado - Dourado */
}
.stat-item:nth-child(2) {
    border-top: 3px solid #28a745; /* Presenças - Verde */
}
.stat-item:nth-child(3) {
    border-top: 3px solid #7b9fff; /* Eventos - Azul */
}
.stat-item:nth-child(4) {
    border-top: 3px solid #dc3545; /* Sextas Confirmadas - Vermelho */
}
.stat-item:nth-child(5) {
    border-top: 3px solid #fd7e14; /* Usuários - Laranja */
}

/* Efeito hover */
.stat-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

/* Efeito de brilho no hover */
.stat-item:hover .stat-number {
    text-shadow: 0 0 8px currentColor;
}

/* Cores de sombra específicas por card */
.stat-item:nth-child(1):hover {
    border-color: #f39c12;
    box-shadow: 0 8px 24px rgba(243,156,18,0.2);
}
.stat-item:nth-child(2):hover {
    border-color: #28a745;
    box-shadow: 0 8px 24px rgba(40,167,69,0.2);
}
.stat-item:nth-child(3):hover {
    border-color: #7b9fff;
    box-shadow: 0 8px 24px rgba(123,159,255,0.2);
}
.stat-item:nth-child(4):hover {
    border-color: #dc3545;
    box-shadow: 0 8px 24px rgba(220,53,69,0.2);
}
.stat-item:nth-child(5):hover {
    border-color: #fd7e14;
    box-shadow: 0 8px 24px rgba(253,126,20,0.2);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #f5b041;
    transition: text-shadow 0.3s ease;
}
.stat-text {
    font-size: 0.7rem;
    color:var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 8px;
}

/* Grid 2 colunas */
.grid-two {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

/* Card padrão */
.card-default {
    background:var(--bg-card);
    border-radius: 12px;
    border:1px solid var(--border);
    overflow: hidden;
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

/* Tabela */
.table-responsive {
    overflow-x: auto;
}
.table-rank {
    width: 100%;
    border-collapse: collapse;
}
.table-rank th, .table-rank td {
    padding: 12px;
    text-align: left;
    border-bottom:1px solid var(--border);
}
.table-rank th {
    color:var(--text-dim);
    font-weight: 500;
    font-size: 0.7rem;
    text-transform: uppercase;
}
.rank-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.rank-1 {
    background: #f39c12;
    color: #0d0f14;
}
.rank-2 {
    background: #7b9fff;
    color: #0d0f14;
}
.rank-3 {
    background: #e07c4c;
    color: #0d0f14;
}
.rank-other {
    background:var(--bg-input);
    color:var(--text-muted);
}
.text-gold {
    color: #f5b041;
    font-weight: 600;
}

/* Botões */
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
.btn-block {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 10px;
}
.btn-gold {
    background: #f39c12;
    color: #0d0f14;
}
.btn-green {
    background: #28a745;
    color: white;
}
.btn-blue {
    background: #7b9fff;
    color: white;
}
.btn-red {
    background: #dc3545;
    color: white;
}
.btn-purple {
    background: #8b5cf6;
    color: white;
}
.btn-gray {
    background: transparent;
    border:1px solid var(--border);
    color:var(--text-muted);
}

/* Responsivo */
@media (max-width: 1000px) {
    .grid-stats {
        grid-template-columns: repeat(3, 1fr);
    }
    .grid-meta-sexta {
        grid-template-columns: 1fr;
        gap: 16px;
    }
}
@media (max-width: 768px) {
    .grid-meta-sexta {
        grid-template-columns: 1fr 1fr;
    }
    .grid-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .grid-two {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .meta-pizza-content {
        flex-direction: column;
        text-align: center;
    }
    .stat-item:nth-child(1) { border-top: 3px solid #f39c12 !important; }
    .stat-item:nth-child(2) { border-top: 3px solid #28a745 !important; }
    .stat-item:nth-child(3) { border-top: 3px solid #7b9fff !important; }
    .stat-item:nth-child(4) { border-top: 3px solid #dc3545 !important; }
    .stat-item:nth-child(5) { border-top: 3px solid #fd7e14 !important; }
}
@media (max-width: 480px) {
    .grid-meta-sexta {
        grid-template-columns: 1fr;
    }
    .grid-stats {
        grid-template-columns: 1fr;
    }
    .stat-item:nth-child(1) { border-top: 3px solid #f39c12 !important; }
    .stat-item:nth-child(2) { border-top: 3px solid #28a745 !important; }
    .stat-item:nth-child(3) { border-top: 3px solid #7b9fff !important; }
    .stat-item:nth-child(4) { border-top: 3px solid #dc3545 !important; }
    .stat-item:nth-child(5) { border-top: 3px solid #fd7e14 !important; }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Panorama completo do ano <?= $year ?></h2>
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

<!-- Grid com dois cards lado a lado: Sexta e Meta com Pizza -->
<div class="grid-meta-sexta">
    <!-- Card Confirmar Sexta -->
    <div class="card-sexta">
        <div class="card-sexta-title">🎯 Confirmar Sexta - Administrador</div>
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

    <!-- Card Meta com Gráfico Pizza -->
    <div class="card-meta-pizza">
        <div class="meta-pizza-header">🎯 Meta 80% do KM Total</div>
        <div class="meta-pizza-content">
            <div class="pizza-container">
                <div class="pizza-canvas"></div>
                <div class="pizza-text"><?= $porcentagemMeta ?>%</div>
            </div>
            <div class="meta-stats">
                <div class="meta-stat-item">
                    <span class="meta-stat-label">KM 100%</span>
                    <span class="meta-stat-value"><?= number_format($totalEventosKm, 0, ',', '.') ?> km</span>
                </div>
                <div class="meta-stat-item">
    <span class="meta-stat-label">KM Registrado</span>
    <span class="meta-stat-value" style="color: #28a745;"><?= number_format($totalKmRegistrado, 0, ',', '.') ?> km</span>
</div>
                <div class="meta-stat-item">
                    <span class="meta-stat-label">Meta (80%)</span>
                    <span class="meta-stat-value meta-stat-value-danger"><?= number_format($metaKm, 0, ',', '.') ?> km</span>
                </div>
                <div class="progress-wrap">
                    <div class="progress-fill" style="width: <?= $porcentagemMeta ?>%;"></div>
                </div>
                <div class="progress-percent"><?= $porcentagemMeta ?>% da meta atingida</div>
            </div>
        </div>
    </div>

    <!-- Mini Calendário de Escalas -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;display:flex;flex-direction:column;overflow:hidden">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
            <span style="font-size:.85rem;font-weight:700;color:var(--text)">📅 <?= $mesesNomes[$calMes] ?> <?= $calAno ?></span>
            <a href="<?= BASE_URL ?>/admin/escalas.php" style="font-size:.72rem;color:#f39c12;text-decoration:none">Gerenciar →</a>
        </div>
        <div style="padding:14px;flex:1;display:flex;flex-direction:column;gap:0">
            <!-- Dias da semana -->
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px">
                <?php foreach (['S','T','Q','Q','S','S','D'] as $d): ?>
                <div style="text-align:center;font-size:.58rem;font-weight:700;color:var(--text-dim);padding:2px 0"><?= $d ?></div>
                <?php endforeach; ?>
            </div>
            <!-- Dias -->
            <?php
            $primeiroDia = mktime(0,0,0,$calMes,1,$calAno);
            $diasNoMes = (int)date('t', $primeiroDia);
            $diaSemanaInicio = (int)date('N', $primeiroDia);
            ?>
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px">
                <?php for ($i=1; $i < $diaSemanaInicio; $i++): ?><div></div><?php endfor; ?>
                <?php for ($dia=1; $dia<=$diasNoMes; $dia++):
                    $isToday  = ($dia == date('d') && $calMes == date('m') && $calAno == date('Y'));
                    $meuBar   = isset($diasAdmBar[$dia]);
                    $meuChurr = isset($diasAdmChurrasco[$dia]);
                    $qualBar  = isset($diasTodosBar[$dia]);
                    $qualChurr= isset($diasTodosChurrasco[$dia]);
                    $hasAny   = $qualBar || $qualChurr;
                    $bg = $isToday ? 'background:#f39c12;' : ($meuBar||$meuChurr ? 'background:rgba(99,102,241,.15);outline:1px solid rgba(99,102,241,.4);' : ($hasAny ? 'background:rgba(255,255,255,.03);' : ''));
                ?>
                <div style="text-align:center;padding:4px 1px;border-radius:4px;<?= $bg ?>">
                    <span style="font-size:.65rem;font-weight:<?= $isToday?'800':'500' ?>;color:<?= $isToday?'#0d0f14':($hasAny?'var(--text)':'var(--text-dim)') ?>"><?= $dia ?></span>
                    <?php if (!$isToday && $hasAny): ?>
                    <div style="display:flex;justify-content:center;gap:1px;margin-top:1px">
                        <?php if ($qualBar): ?><div style="width:3px;height:3px;border-radius:50%;background:#f97316"></div><?php endif; ?>
                        <?php if ($qualChurr): ?><div style="width:3px;height:3px;border-radius:50%;background:#8b5cf6"></div><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            <!-- Legenda -->
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                <span style="font-size:.6rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:5px;height:5px;border-radius:50%;background:#f97316;display:inline-block"></span>Bar</span>
                <span style="font-size:.6rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:5px;height:5px;border-radius:50%;background:#8b5cf6;display:inline-block"></span>Churrasco</span>
                <span style="font-size:.6rem;color:var(--text-dim);display:flex;align-items:center;gap:3px"><span style="width:4px;height:4px;border-radius:50%;outline:1px solid rgba(99,102,241,.6);display:inline-block"></span>Sua semana</span>
            </div>
            <!-- Lista escalas do mês -->
            <?php if (!empty($todasEscalasBar) || !empty($todasEscalasChurrasco)): ?>
            <div style="border-top:1px solid var(--border);margin-top:8px;padding-top:8px;flex:1;overflow-y:auto">
                <?php foreach ($todasEscalasBar as $eb):
                    $dtL = new DateTime($eb['semana_inicio']);
                    $difL = (5-(int)$dtL->format('N')+7)%7;
                    $dtL->modify("+{$difL} days");
                    $sxtLbl = $dtL->format('d/m');
                    $ehMinha = in_array($uid, [(int)($eb['user1_id']??0),(int)($eb['user2_id']??0)]);
                ?>
                <div style="display:flex;align-items:center;gap:5px;padding:3px 0;font-size:.7rem;border-bottom:1px solid var(--border)">
                    <span style="width:6px;height:6px;border-radius:50%;background:#f97316;flex-shrink:0"></span>
                    <span style="color:var(--text-dim);white-space:nowrap">Sx <?= $sxtLbl ?></span>
                    <span style="color:var(--text);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($eb['nome1']) ?> & <?= htmlspecialchars($eb['nome2']) ?></span>
                    <?php if ($ehMinha): ?><span style="font-size:.58rem;background:#f9731620;color:#f97316;padding:1px 4px;border-radius:6px;flex-shrink:0">você</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($todasEscalasChurrasco as $ec):
                    $dtL2 = new DateTime($ec['semana_inicio']);
                    $ini2 = $dtL2->format('d/m');
                    $dtL2->modify('+6 days'); $fim2 = $dtL2->format('d/m');
                ?>
                <div style="display:flex;align-items:center;gap:5px;padding:3px 0;font-size:.7rem;border-bottom:1px solid var(--border)">
                    <span style="width:6px;height:6px;border-radius:50%;background:#8b5cf6;flex-shrink:0"></span>
                    <span style="color:var(--text-dim);white-space:nowrap"><?= $ini2 ?>–<?= $fim2 ?></span>
                    <span style="color:var(--text);flex:1">Churrasco</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>


<!-- Cards de estatísticas com bordas coloridas -->
<div class="grid-stats">
    <div class="stat-item">
        <div class="stat-number"><?= number_format($totalKmRegistrado, 0, ',', '.') ?></div>
        <div class="stat-text">KM Registrado</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= number_format($totalPresencas, 0, ',', '.') ?></div>
        <div class="stat-text">Presenças</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= $totalEvents ?></div>
        <div class="stat-text">Eventos</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= number_format($totalSextasConfirmadas, 0, ',', '.') ?></div>
        <div class="stat-text">Sextas Confirmadas</div>
    </div>
    <div class="stat-item">
        <div class="stat-number"><?= $totalUsers ?></div>
        <div class="stat-text">Integrantes</div>
    </div>
</div>

<!-- Ranking e Atalhos -->
<div class="grid-two">
    <!-- Ranking -->
    <div class="card-default">
        <div class="card-header">
            <h3>🏆 Ranking de KM</h3>
            <a href="<?= BASE_URL ?>/admin/reports.php?year=<?= $year ?>" class="btn-link">Ver relatório</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table-rank">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Integrante</th>
                            <th>Presenças</th>
                            <th>KM Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ranking as $i => $row): ?>
                        <tr>
                            <td>
                                <span class="rank-badge <?= $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : 'rank-other')) ?>">
                                    <?= $i + 1 ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= $row['presencas'] ?></td>
                            <td class="text-gold"><?= number_format($row['total_km'], 0, ',', '.') ?> km</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ranking)): ?>
                        <tr>
                            <td colspan="4" class="text-muted text-center">Nenhum dado encontrado.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Atalhos Rápidos -->
    <div class="card-default">
        <div class="card-header">
            <h3>⚡ Atalhos Rápidos</h3>
        </div>
        <div class="card-body">
            <a href="<?= BASE_URL ?>/admin/events.php" class="btn-block btn-gold">📅 Gerenciar Eventos</a>
            <a href="<?= BASE_URL ?>/user/calendario.php" class="btn-block btn-purple">📅 Ver Calendário</a>
            <a href="<?= BASE_URL ?>/admin/attendances.php" class="btn-block btn-green">👥 Gerenciar Presenças</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn-block btn-blue">👤 Gerenciar Integrantes</a>
            <a href="<?= BASE_URL ?>/admin/reports.php?year=<?= $year ?>" class="btn-block btn-red">📊 Relatórios</a>
        </div>
    </div>
</div>

<!-- Gráfico de Participação por Evento -->
<?php if (!empty($chartData)): ?>
<div style="margin-top:28px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <h3 style="font-size:1rem;font-weight:700;color:var(--text);margin:0">📊 Participação por Evento — <?= $year ?></h3>
        <a href="<?= BASE_URL ?>/admin/attendances.php" style="font-size:.78rem;color:#f39c12;text-decoration:none">Ver presenças →</a>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px">
        <?php
        $maxConf = max(array_column($chartData, 'confirmados')) ?: 1;
        foreach ($chartData as $ev):
            $pct  = round($ev['confirmados'] / $maxConf * 100);
            $data = date('d/m', strtotime($ev['event_date']));
            $nome = mb_substr($ev['title'], 0, 30) . (mb_strlen($ev['title']) > 30 ? '…' : '');
        ?>
        <div style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:.68rem;background:#f39c1220;color:#f5b041;padding:1px 7px;border-radius:20px;white-space:nowrap"><?= $data ?></span>
                    <span style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($nome) ?></span>
                </div>
                <span style="font-size:.82rem;font-weight:700;color:var(--text);margin-left:8px;white-space:nowrap">
                    <?= $ev['confirmados'] ?> ✅ <?= $ev['pendentes'] ? " · {$ev['pendentes']} ⏳" : '' ?>
                </span>
            </div>
            <div style="height:8px;background:var(--border);border-radius:4px;overflow:hidden">
                <div style="height:8px;border-radius:4px;width:<?= $pct ?>%;
                            background:linear-gradient(90deg,#f39c12,#e67e22);
                            transition:width .3s"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>


<?php pageClose(); ?>