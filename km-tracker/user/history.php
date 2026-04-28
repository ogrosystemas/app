<?php
// user/history.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db      = db();
$me      = currentUser();
$uid     = $me['id'];
$year    = (int)($_GET['year'] ?? date('Y'));
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// Total de registros (apenas confirmados e com data já passada)
$total = $db->prepare('
    SELECT COUNT(*) FROM attendances a
    JOIN events e ON e.id = a.event_id
    WHERE a.user_id = ? 
    AND YEAR(e.event_date) = ?
    AND a.status = "confirmado"
    AND e.event_date <= CURDATE()
');
$total->execute([$uid, $year]);
$pag = paginate((int)$total->fetchColumn(), $perPage, $page);

// Buscar histórico
$rows = $db->prepare("
    SELECT e.title, e.event_date, e.km_awarded, e.location,
           e.route_km, e.route_duration_min,
           a.km_extra, a.notes, a.created_at,
           (e.km_awarded + a.km_extra) AS km_total
    FROM attendances a
    JOIN events e ON e.id = a.event_id
    WHERE a.user_id = ? 
    AND YEAR(e.event_date) = ?
    AND a.status = 'confirmado'
    AND e.event_date <= CURDATE()
    ORDER BY e.event_date DESC
    LIMIT {$pag['perPage']} OFFSET {$pag['offset']}
");
$rows->execute([$uid, $year]);
$history = $rows->fetchAll();

// Totais do ano
$summary = $db->prepare('
    SELECT COUNT(*) AS presencas,
           COALESCE(SUM(e.km_awarded + a.km_extra), 0) AS total_km
    FROM attendances a
    JOIN events e ON e.id = a.event_id
    WHERE a.user_id = ? 
    AND YEAR(e.event_date) = ?
    AND a.status = "confirmado"
    AND e.event_date <= CURDATE()
');
$summary->execute([$uid, $year]);
$summary = $summary->fetch();

// Dados da moto
$motoRow = $db->prepare('SELECT moto_kml, gas_preco FROM users WHERE id=?');
$motoRow->execute([$uid]);
$moto  = $motoRow->fetch();
$kml   = (float)($moto['moto_kml']  ?? 0);
$preco = (float)($moto['gas_preco'] ?? 0);

pageOpen('Histórico', 'history', 'Histórico de KM');
?>

<style>
/* Filtros */
.filter-bar {
    background: none;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    border: 0;
    display: inline-block;
}
.filter-select {
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
.filter-select:hover {
    border-color: #f39c12;
}

/* Cards de estatísticas com bordas coloridas */
.grid-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Cards com bordas coloridas e efeito hover */
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
    border-top: 3px solid #28a745; /* Participações - Verde */
}
.stat-card:nth-child(3) {
    border-top: 3px solid #7b9fff; /* Litros Gastos - Azul */
}
.stat-card:nth-child(4) {
    border-top: 3px solid #dc3545; /* Custo Combustível - Vermelho */
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

/* Card da tabela */
.card-table {
    background:var(--bg-card);
    border-radius: 12px;
    border:1px solid var(--border);
    overflow: hidden;
}
.table-responsive {
    overflow-x: auto;
}
.history-table {
    width: 100%;
    border-collapse: collapse;
}
.history-table th, .history-table td {
    padding: 12px;
    text-align: left;
    border-bottom:1px solid var(--border);
}
.history-table th {
    color:var(--text-dim);
    font-weight: 500;
    font-size: 0.7rem;
    text-transform: uppercase;
}
.text-gold {
    color: #f5b041;
    font-weight: 600;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    border-top:1px solid var(--border);
}
.pagination a {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    color:var(--text-muted);
    background:var(--bg-input);
    border:1px solid var(--border);
}
.pagination a.current {
    background: #f39c12;
    color: #0d0f14;
    border-color: #f39c12;
}
@media (max-width: 768px) {
    .hide-mobile {
        display: none !important;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h2>Todas as suas participações registradas</h2>
        </div>
        <div class="page-header-actions">
            <div class="filter-bar">
                <form method="GET">
                    <select name="year" onchange="this.form.submit()" class="filter-select">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cards de estatísticas COM BORDAS COLORIDAS -->
<div class="grid-stats">
    <div class="stat-card">
        <div class="stat-number"><?= number_format($summary['total_km'], 0, ',', '.') ?></div>
        <div class="stat-text">KM Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $summary['presencas'] ?></div>
        <div class="stat-text">Participações</div>
    </div>
    <?php if ($kml > 0 && $summary['total_km'] > 0): ?>
        <div class="stat-card">
            <div class="stat-number"><?= number_format(round($summary['total_km'] / $kml, 1), 1, ',', '.') ?> L</div>
            <div class="stat-text">Litros Gastos</div>
        </div>
        <?php if ($preco > 0): ?>
            <div class="stat-card">
                <div class="stat-number">R$ <?= number_format(round(($summary['total_km'] / $kml) * $preco, 0), 0, ',', '.') ?></div>
                <div class="stat-text">Custo Combustível</div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Tabela de histórico -->
<div class="card-table">
    <div class="table-responsive">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data</th>
                    <th class="hide-mobile">Local</th>
                    <th>KM</th>
                    <?php if ($kml > 0): ?><th class="hide-mobile">Litros</th><?php endif; ?>
                    <?php if ($preco > 0): ?><th class="hide-mobile">Custo</th><?php endif; ?>
                    <th class="hide-mobile">Obs.</th>
                </thead>
            <tbody>
                <?php foreach ($history as $row):
                    $kmTot   = (float)$row['km_total'];
                    $litEv   = ($kml > 0 && $kmTot > 0) ? round($kmTot / $kml, 1) : 0;
                    $custoEv = ($preco > 0 && $litEv > 0) ? round($litEv * $preco, 2) : 0;
                ?>
                早\d+
                    <td>
                        <strong><?= htmlspecialchars($row['title']) ?></strong>
                        <?php if ($row['route_km']): ?>
                            <div style="font-size:.72rem;color:#28a745;margin-top:1px">🗺️ Rota: <?= number_format($row['route_km'], 0, ',', '.') ?> km</div>
                        <?php endif; ?>
                     </n>
                    <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($row['event_date'])) ?> </n>
                    <td class="hide-mobile"><?= htmlspecialchars($row['location'] ?: '—') ?> </n>
                    <td class="text-gold"><?= number_format($kmTot, 0, ',', '.') ?> km</n>
                    <?php if ($kml > 0): ?>
                        <td class="hide-mobile"><?= $litEv > 0 ? number_format($litEv, 1, ',', '.') . ' L' : '—' ?> </n>
                    <?php endif; ?>
                    <?php if ($preco > 0): ?>
                        <td class="hide-mobile"><?= $custoEv > 0 ? 'R$ ' . number_format($custoEv, 2, ',', '.') : '—' ?> </n>
                    <?php endif; ?>
                    <td class="hide-mobile"><?= htmlspecialchars($row['notes'] ?: '—') ?> </n>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="<?= 4 + ($kml > 0 ? 1 : 0) + ($preco > 0 ? 1 : 0) ?>" class="text-muted text-center" style="padding:28px">
                            Nenhuma participação confirmada em <?= $year ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pag['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $pag['pages']; $p++): ?>
                <a href="?page=<?= $p ?>&year=<?= $year ?>" class="<?= $p == $pag['current'] ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php pageClose(); ?>