<?php
// admin/events.php — Gerenciar eventos
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$me = currentUser();
$year = (int)($_GET['year'] ?? date('Y'));
$eventId = (int)($_GET['event_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Buscar dados da moto do usuário
$motoStmt = $db->prepare("SELECT moto_kml, gas_preco FROM users WHERE id = ?");
$motoStmt->execute([$me['id']]);
$motoData = $motoStmt->fetch();
$consumoMoto = (float)($motoData['moto_kml'] ?? 0);
$precoGasolina = (float)($motoData['gas_preco'] ?? 0);

// Buscar lista de eventos para o filtro
$eventsListStmt = $db->prepare("SELECT id, title FROM events WHERE YEAR(event_date) = ? AND active = 1 ORDER BY event_date DESC");

// Lixeira - eventos desativados
$lixeira = $db->query("SELECT id, title, event_date, location, deleted_at FROM events WHERE active=0 ORDER BY deleted_at DESC")->fetchAll();
$eventsListStmt->execute([$year]);
$eventsList = $eventsListStmt->fetchAll();

// Buscar eventos
$total = $db->prepare("SELECT COUNT(*) FROM events WHERE YEAR(event_date)=? AND active=1");
$total->execute([$year]);
$totalCount = (int)$total->fetchColumn();
$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

$evStmt = $db->prepare("
    SELECT e.*, 
           COUNT(CASE WHEN a.status = 'confirmado' AND e.event_date <= CURDATE() THEN 1 END) AS presentes
    FROM events e 
    LEFT JOIN attendances a ON a.event_id = e.id
    WHERE YEAR(e.event_date)=? AND e.active=1
    GROUP BY e.id ORDER BY e.event_date DESC
    LIMIT ? OFFSET ?
");
$evStmt->execute([$year, $perPage, $offset]);
$events = $evStmt->fetchAll();

// Processar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $title      = sanitizeString($_POST['title'] ?? '', 200);
        $desc       = sanitizeString($_POST['description'] ?? '', 2000);
        $date       = sanitizeDate($_POST['event_date'] ?? '');
        $location   = sanitizeString($_POST['location'] ?? '', 255);
        $km_awarded = sanitizeFloat($_POST['km_awarded'] ?? 0, 0);
        $route_origin      = sanitizeString($_POST['route_origin'] ?? '', 255);
        $route_destination = sanitizeString($_POST['route_destination'] ?? '', 255);
        $route_waypoints   = sanitizeString($_POST['route_waypoints'] ?? '', 2000);
        $route_km          = sanitizeFloat($_POST['route_km'] ?? 0, 0);
        $route_dur         = (int)($_POST['route_duration_min'] ?? 0);
        $route_polyline    = $_POST['route_polyline'] ?? '';
        $route_origin_lat  = (float)($_POST['route_origin_lat'] ?? 0);
        $route_origin_lng  = (float)($_POST['route_origin_lng'] ?? 0);
        $route_dest_lat    = (float)($_POST['route_dest_lat'] ?? 0);
        $route_dest_lng    = (float)($_POST['route_dest_lng'] ?? 0);
        $litros_gastos     = (float)($_POST['litros_gastos'] ?? 0);
        $custo_combustivel = (float)($_POST['custo_combustivel'] ?? 0);
        $avoid_tolls       = isset($_POST['avoid_tolls']) && $_POST['avoid_tolls'] === '1' ? 1 : 0;
        $valor_pedagios    = $avoid_tolls ? 0.00 : (float)($_POST['valor_pedagios'] ?? 0);
        
        if ($route_km > 0) {
            $km_awarded = $route_km * 2;
        }

        if ($title && $date) {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare('INSERT INTO events
                        (title,description,event_date,location,km_awarded,created_by,
                         route_origin,route_destination,route_waypoints,route_km,
                         route_duration_min,route_polyline,
                         route_origin_lat,route_origin_lng,route_dest_lat,route_dest_lng,
                         litros_gastos,custo_combustivel,valor_pedagios)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([$title,$desc,$date,$location,$km_awarded,$me['id'],
                         $route_origin?:null,$route_destination?:null,
                         $route_waypoints?:null,$route_km?:null,
                         $route_dur?:null,$route_polyline?:null,
                         $route_origin_lat?:null,$route_origin_lng?:null,
                         $route_dest_lat?:null,$route_dest_lng?:null,
                         $litros_gastos,$custo_combustivel,$valor_pedagios]);
                    $_SESSION['flash_success'] = 'Evento criado com sucesso!';
                    // Notificar no WhatsApp
                    try {
                        require_once __DIR__ . '/../includes/evolution.php';
                        $stmt2 = $db->prepare('SELECT * FROM events ORDER BY id DESC LIMIT 1');
                        $stmt2->execute();
                        $novoEvento = $stmt2->fetch();
                        if ($novoEvento) Evolution::notificarNovoEvento($novoEvento);
                    } catch (Throwable $e) { error_log('Evolution events: ' . $e->getMessage()); }
                } else {
                    $eid = (int)($_POST['event_id'] ?? 0);
                    $stmt = $db->prepare('UPDATE events SET
                        title=?,description=?,event_date=?,location=?,km_awarded=?,
                        route_origin=?,route_destination=?,route_waypoints=?,
                        route_km=?,route_duration_min=?,route_polyline=?,
                        route_origin_lat=?,route_origin_lng=?,route_dest_lat=?,route_dest_lng=?,
                        litros_gastos=?,custo_combustivel=?,valor_pedagios=?
                        WHERE id=?');
                    $stmt->execute([$title,$desc,$date,$location,$km_awarded,
                         $route_origin?:null,$route_destination?:null,
                         $route_waypoints?:null,$route_km?:null,
                         $route_dur?:null,$route_polyline?:null,
                         $route_origin_lat?:null,$route_origin_lng?:null,
                         $route_dest_lat?:null,$route_dest_lng?:null,
                         $litros_gastos,$custo_combustivel,$valor_pedagios,$eid]);
                    $_SESSION['flash_success'] = 'Evento atualizado com sucesso!';
                }
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = 'Erro ao salvar evento: ' . $e->getMessage();
            }
        } else {
            $_SESSION['flash_error'] = 'Título e data são obrigatórios.';
        }
    } elseif ($action === 'toggle') {
        $eid = (int)($_POST['event_id'] ?? 0);
        $db->prepare('UPDATE events SET active=? WHERE id=?')
           ->execute([(int)($_POST['active']??0), $eid]);
        $_SESSION['flash_success'] = 'Status do evento atualizado.';
    } elseif ($action === 'excluir_permanente') {
        $eid = (int)($_POST['event_id'] ?? 0);
        if ($eid) {
            // Remove fotos, arquivos e presenças relacionadas
            $db->prepare('DELETE FROM event_photos WHERE event_id=?')->execute([$eid]);
            $db->prepare('DELETE FROM event_files WHERE event_id=?')->execute([$eid]);
            $db->prepare('DELETE FROM attendances WHERE event_id=?')->execute([$eid]);
            $db->prepare('DELETE FROM events WHERE id=? AND active=0')->execute([$eid]);
            $_SESSION['flash_success'] = 'Evento excluído permanentemente.';
        }
    } elseif ($action === 'esvaziar_lixeira') {
        $lixeiraIds = $db->query('SELECT id FROM events WHERE active=0')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($lixeiraIds as $eid) {
            $db->prepare('DELETE FROM event_photos WHERE event_id=?')->execute([$eid]);
            $db->prepare('DELETE FROM event_files WHERE event_id=?')->execute([$eid]);
            $db->prepare('DELETE FROM attendances WHERE event_id=?')->execute([$eid]);
        }
        $db->query('DELETE FROM events WHERE active=0');
        $_SESSION['flash_success'] = 'Lixeira esvaziada com sucesso.';
    }
    header('Location: ' . BASE_URL . '/admin/events.php');
    exit;
}

pageOpen("Eventos", "events", "Gerenciar Eventos");
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/api/assets/leaflet.css">
<script src="<?= BASE_URL ?>/api/assets/leaflet.js"></script>

<style>
/* ===== TODOS OS SEUS ESTILOS EXISTENTES ===== */
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
.card-table {
    background: var(--bg-card, #14161c);
    border-radius: 12px;
    border: 1px solid var(--border, #2a2f3a);
    overflow: hidden;
    margin-bottom: 0;
}
.table-wrap {
    overflow-x: auto;
}
.users-table {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden; 
    border-spacing: 0;
}
.users-table th,
.users-table td {
    padding: 12px;
    text-align: left;
    border: none !important;
    border-bottom: 1px solid var(--border) !important;
}
.users-table th {
    color: var(--text-muted);
    font-weight: 500;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
}
.badge-success {
    background: #28a745;
    color: white;
}
.badge-muted {
    background: var(--bg-input);
    color: var(--text-muted);
}
.badge-accent {
    background: var(--accent);
    color: #333;
}
.text-gold {
    color: var(--gold-light);
    font-weight: 600;
}
.btn-sm {
    padding: 4px 12px;
    font-size: 0.7rem;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    border: none;
}
.btn-ghost {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-secondary);
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-accent {
    background: var(--accent);
    color: #333;
}
.flex {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid var(--border);
}
.pagination a {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    color: var(--text-secondary);
    background: var(--bg-card2);
    border: 1px solid var(--border);
}
.pagination a.current {
    background: var(--gold);
    color: #0d0f14;
    border-color: var(--gold);
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
    .hide-mobile {
        display: none !important;
    }
}
/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
.modal-overlay.open {
    display: flex;
}
.modal {
    background: var(--bg-card);
    border-radius: 16px;
    width: 90%;
    max-width: 850px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    border: 1px solid var(--border);
}
.modal-lg {
    max-width: 950px;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--bg-card2);
    border-radius: 16px 16px 0 0;
}
.modal-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #f5b041;
}
.modal-header .btn-ghost {
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color:var(--text-muted);
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}
.modal-header .btn-ghost:hover {
    background: var(--bg-input);
    color:var(--text);
}
.modal form {
    padding: 24px;
}
.form-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    color:var(--text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color:var(--text);
    font-family: inherit;
    font-size: 0.85rem;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #f39c12;
}
.ac-list {
    position: absolute;
    z-index: 600;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 8px 8px;
    max-height: 220px;
    overflow-y: auto;
    width: 100%;
    display: none;
}
.ac-list.open {
    display: block;
}
.ac-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 0.84rem;
    color:var(--text-muted);
    border-bottom: 1px solid rgba(42,47,69,.4);
}
.ac-item:hover {
    background: var(--bg-input);
    color:var(--text);
}
.field-wrap {
    position: relative;
}
.route-box {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-top: 12px;
    padding: 14px;
    background: var(--bg-input);
    border-radius: 8px;
    border: 1px solid var(--border);
}
.route-box-item {
    text-align: center;
}
.route-box-val {
    font-family: var(--font-display);
    font-size: 1.2rem;
    font-weight: 700;
    color: #f5b041;
}
.route-box-lbl {
    font-size: 0.6rem;
    color:var(--text-dim);
    text-transform: uppercase;
    margin-top: 4px;
}
/* Padronização dos botões da tabela */
.btn-sm {
    padding: 6px 12px;
    min-width: 70px;
    font-size: 0.7rem;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.2s;
}

.btn-sm:hover {
    transform: translateY(-1px);
}

.btn-ghost {
    background: transparent;
    border: 1px solid var(--border);
    color:var(--text-muted);
}

.btn-ghost:hover {
    background: var(--bg-input);
    color:var(--text);
}

.btn-accent {
    background: #7b9fff;
    color: #0d0f14;
    border: none;
}

.btn-accent:hover {
    background: #6a8fe8;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #c82333;
}

/* Botão Novo Evento (mantido) */
.btn-primary {
    background: #f39c12;
    color: #0d0f14;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

.btn-primary:hover {
    background: #f5b041;
}
.alert-success {
    background: rgba(40, 167, 69, 0.15);
    border: 1px solid #28a745;
    color: #28a745;
    padding: 10px;
    border-radius: 8px;
    margin-top: 12px;
    font-size: 0.75rem;
}
/* Estilos para o mapa Leaflet */
#route-map {
    height: 400px;
    width: 100%;
    border-radius: 8px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    z-index: 1;
}
.map-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    z-index: 1000;
    font-size: 14px;
    pointer-events: none;
}
.leaflet-container {
    background: var(--bg-input);
    border-radius: 8px;
}
.leaflet-popup-content-wrapper {
    background: var(--bg-card);
    color:var(--text);
}
.leaflet-popup-tip {
    background: var(--bg-card);
}
@media (max-width: 768px) {
    #route-map {
        height: 300px;
    }
}
@media (max-width: 480px) {
    #route-map {
        height: 250px;
    }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div>
        <h2><?= $totalCount ?> eventos em <?= $year ?></h2>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" onclick="abrirCriar()">+ Novo Evento</button>
        </div>
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
                <a href="<?= BASE_URL ?>/admin/events.php?year=<?= $year ?>" class="btn-filter btn-filter-clear">Limpar filtros</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
<?php endif; ?>

<div class="card-table">
    <div class="table-wrap">
        <table class="users-table events-table" style="width:100%">
            <thead class="hide-mobile">
                <tr>
                    <th style="width:20%">Título</th>
                    <th style="width:9%">Data</th>
                    <th style="width:11%">Local</th>
                    <th style="width:8%">KM</th>
                    <th style="width:6%">Pres.</th>
                    <th style="width:8%">Status</th>
                    <th style="text-align:center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr class="event-row">
                    <td>
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <!-- Mobile only: data + local + km + status -->
                        <div class="show-mobile" style="display:none;margin-top:4px;font-size:.72rem;color:var(--text-dim)"><?= date('d/m/Y', strtotime($ev['event_date'])) ?><?= $ev['location'] ? ' · ' . htmlspecialchars($ev['location']) : '' ?></div>
                        <div class="show-mobile" style="display:none;margin-top:6px;gap:6px;align-items:center">
                            <span style="font-size:.78rem;color:#f39c12;font-weight:700"><?= number_format($ev['km_awarded'], 0, ',', '.') ?> km</span>
                            <span class="badge <?= $ev['active'] ? 'badge-success' : 'badge-muted' ?>" style="font-size:.65rem"><?= $ev['active'] ? 'Ativo' : 'Inativo' ?></span>
                        </div>
                    </td>
                    <td class="hide-mobile" style="white-space:nowrap"><?= date('d/m/Y', strtotime($ev['event_date'])) ?></td>
                    <td class="hide-mobile"><?= htmlspecialchars($ev['location'] ?: '—') ?></td>
                    <td class="hide-mobile" style="color:#f39c12;font-weight:700"><?= number_format($ev['km_awarded'], 0, ',', '.') ?> km</td>
                    <td class="hide-mobile"><span class="badge badge-accent"><?= $ev['presentes'] ?></span></td>
                    <td class="hide-mobile"><span class="badge <?= $ev['active'] ? 'badge-success' : 'badge-muted' ?>"><?= $ev['active'] ? 'Ativo' : 'Inativo' ?></span></td>
                    <td style="text-align:center;padding:8px">
    <!-- Desktop: flex row -->
    <div class="ev-btns-desktop" style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;align-items:center">
        <button style="padding:6px 12px;background:#3b82f6;color:white;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;height:30px" onclick='editarEvento(<?= json_encode($ev, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>✏️ Editar</button>
        <a href="<?= BASE_URL ?>/admin/attendances.php?event_id=<?= $ev['id'] ?>&year=<?= $year ?>" style="padding:6px 12px;background:#f39c12;color:#0d0f14;border:none;border-radius:6px;font-size:.78rem;text-decoration:none;display:inline-flex;align-items:center;height:30px">✅ Presenças</a>
        <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $ev['id'] ?>" style="padding:6px 12px;background:#8b5cf6;color:white;border:none;border-radius:6px;font-size:.78rem;text-decoration:none;display:inline-flex;align-items:center;height:30px">📸 Fotos</a>
        <?php if (!empty($ev['route_polyline']) && $ev['route_polyline'] !== '{}'): ?>
        <button style="padding:6px 12px;background:#e67e22;color:white;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;height:30px"
                onclick='verRotaAdmin(<?= json_encode([
                    "id"=>$ev["id"],"title"=>$ev["title"],"event_date"=>$ev["event_date"],
                    "route_km"=>(float)$ev["route_km"],"route_duration_min"=>(int)$ev["route_duration_min"],
                    "route_origin"=>$ev["route_origin"],"route_destination"=>$ev["route_destination"],
                    "route_origin_lat"=>(float)$ev["route_origin_lat"],"route_origin_lng"=>(float)$ev["route_origin_lng"],
                    "route_dest_lat"=>(float)$ev["route_dest_lat"],"route_dest_lng"=>(float)$ev["route_dest_lng"],
                    "route_polyline"=>$ev["route_polyline"],"valor_pedagios"=>(float)$ev["valor_pedagios"],
                    "avoid_tolls"=>(bool)$ev["avoid_tolls"],"litros_gastos"=>(float)$ev["litros_gastos"],
                    "custo_combustivel"=>(float)$ev["custo_combustivel"]
                ], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>🗺️ Rota</button>
        <?php endif; ?>
        <form method="POST" style="display:inline;margin:0">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
            <input type="hidden" name="active" value="<?= $ev['active'] ? 0 : 1 ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button style="padding:6px 12px;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;height:30px;<?= $ev['active'] ? 'background:#dc3545;color:white' : 'background:var(--border);color:var(--text)' ?>">
                <?= $ev['active'] ? 'Desativar' : 'Ativar' ?>
            </button>
        </form>
    </div>
    <!-- Mobile: 2x2 grid -->
    <div class="ev-btns-mobile" style="display:none;grid-template-columns:1fr 1fr;gap:5px;width:100%">
        <button style="padding:5px 8px;background:#3b82f6;color:white;border:none;border-radius:6px;font-size:.72rem;cursor:pointer;height:28px" onclick='editarEvento(<?= json_encode($ev, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>✏️ Editar</button>
        <a href="<?= BASE_URL ?>/admin/attendances.php?event_id=<?= $ev['id'] ?>&year=<?= $year ?>" style="padding:5px 8px;background:#f39c12;color:#0d0f14;border-radius:6px;font-size:.72rem;text-decoration:none;display:flex;align-items:center;justify-content:center;height:28px">✅ Presenças</a>
        <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $ev['id'] ?>" style="padding:5px 8px;background:#8b5cf6;color:white;border-radius:6px;font-size:.72rem;text-decoration:none;display:flex;align-items:center;justify-content:center;height:28px">📸 Fotos</a>
        <?php if (!empty($ev['route_polyline']) && $ev['route_polyline'] !== '{}'): ?>
        <button style="padding:5px 8px;background:#e67e22;color:white;border:none;border-radius:6px;font-size:.72rem;cursor:pointer;height:28px"
                onclick='verRotaAdmin(<?= json_encode([
                    "id"=>$ev["id"],"title"=>$ev["title"],"event_date"=>$ev["event_date"],
                    "route_km"=>(float)$ev["route_km"],"route_duration_min"=>(int)$ev["route_duration_min"],
                    "route_origin"=>$ev["route_origin"],"route_destination"=>$ev["route_destination"],
                    "route_origin_lat"=>(float)$ev["route_origin_lat"],"route_origin_lng"=>(float)$ev["route_origin_lng"],
                    "route_dest_lat"=>(float)$ev["route_dest_lat"],"route_dest_lng"=>(float)$ev["route_dest_lng"],
                    "route_polyline"=>$ev["route_polyline"],"valor_pedagios"=>(float)$ev["valor_pedagios"],
                    "avoid_tolls"=>(bool)$ev["avoid_tolls"],"litros_gastos"=>(float)$ev["litros_gastos"],
                    "custo_combustivel"=>(float)$ev["custo_combustivel"]
                ], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>🗺️ Rota</button>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        <form method="POST" style="grid-column:1/-1;margin:0">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
            <input type="hidden" name="active" value="<?= $ev['active'] ? 0 : 1 ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button style="width:100%;padding:5px 8px;border:none;border-radius:6px;font-size:.72rem;cursor:pointer;height:28px;<?= $ev['active'] ? 'background:#dc3545;color:white' : 'background:var(--border);color:var(--text)' ?>">
                <?= $ev['active'] ? 'Desativar' : 'Ativar' ?>
            </button>
        </form>
    </div>
</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr>
                    <td colspan="7" class="text-muted text-center" style="padding:28px">Nenhum evento encontrado.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>&year=<?= $year ?>" class="<?= $p == $page ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<!-- MODAL EVENTO -->
<div class="modal-overlay" id="modal-ev">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="modal-titulo">Novo Evento</span>
            <button class="btn btn-ghost btn-icon" onclick="fecharModal()">✕</button>
        </div>
        <form method="POST" id="form-ev" style="padding: 24px;">
            <input type="hidden" name="action" id="f-action" value="create">
            <input type="hidden" name="event_id" id="f-eid">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="route_km" id="f-rkm">
            <input type="hidden" name="route_duration_min" id="f-rdur">
            <input type="hidden" name="route_polyline" id="f-rpoly">
            <input type="hidden" name="route_origin_lat" id="f-olat">
            <input type="hidden" name="route_origin_lng" id="f-olng">
            <input type="hidden" name="route_dest_lat" id="f-dlat">
            <input type="hidden" name="route_dest_lng" id="f-dlng">
            <input type="hidden" name="route_waypoints" id="f-wps">
            <input type="hidden" name="litros_gastos" id="f-litros">
            <input type="hidden" name="custo_combustivel" id="f-custo">
            <input type="hidden" name="valor_pedagios" id="f-pedagios">
            <input type="hidden" name="avoid_tolls" id="f-avoid-tolls" value="0">

            <div class="form-row">
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="title" id="f-title" required maxlength="200">
                </div>
                <div class="form-group">
                    <label>Data *</label>
                    <input type="date" name="event_date" id="f-date" required>
                </div>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="description" id="f-desc" rows="3"></textarea>
            </div>

            <!-- ROTA -->
            <div style="background:var(--bg-card2);border-radius:12px;padding:20px;margin-bottom:20px;border:1px solid var(--border)">
                <div style="display:flex;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
                    <div>
                        <div style="font-weight:600;color:var(--gold-light);">🏍️ Rota de Motocicleta</div>
                        <div style="font-size:.7rem;color:var(--text-muted)">Cálculo via GraphHopper (ida e volta)</div>
                    </div>
                    <button type="button" id="btn-calc" class="btn btn-accent btn-sm" onclick="calcularRota()">Calcular Rota</button>
                </div>

                <div class="form-row">
                    <div class="form-group field-wrap">
                        <label>Ponto de Partida</label>
                        <input type="text" name="route_origin" id="in-origin" autocomplete="off">
                        <div class="ac-list" id="ac-origin"></div>
                    </div>
                    <div class="form-group field-wrap">
                        <label>Destino</label>
                        <input type="text" name="route_destination" id="in-dest" autocomplete="off">
                        <div class="ac-list" id="ac-dest"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Paradas intermediárias <span style="font-weight:400">(opcional, separadas por vírgula)</span></label>
                    <input type="text" id="in-wps" placeholder="Ex: Campinas SP, São José dos Campos SP">
                </div>

                <div id="route-result" style="display:none">
                    <div class="route-box">
                        <div class="route-box-item"><div class="route-box-val" id="rb-km-ida">—</div><div class="route-box-lbl">Distância (ida)</div></div>
                        <div class="route-box-item"><div class="route-box-val" id="rb-km-total">—</div><div class="route-box-lbl">Distância (ida+volta)</div></div>
                        <div class="route-box-item"><div class="route-box-val" id="rb-dur">—</div><div class="route-box-lbl">Tempo estimado</div></div>
                        <div class="route-box-item"><div class="route-box-val" id="rb-litros">—</div><div class="route-box-lbl">⛽ Combustível</div></div>
                        <div class="route-box-item" id="rb-pedagio-box">
                            <div class="route-box-val" id="rb-pedagios">—</div>
                            <div class="route-box-lbl">💰 Pedágio estimado</div>
                        </div>
                    </div>
                    <div class="alert alert-success" style="margin-top:12px;padding:10px;font-size:0.75rem">
                        ✓ Rota calculada! KM total (ida+volta) preenchido automaticamente.
                    </div>
                    <!-- Checkbox sem pedágios -->
                    <label style="display:flex;align-items:center;gap:10px;margin-top:12px;cursor:pointer;
                                  background:var(--bg-body);border:1px solid var(--border);border-radius:8px;padding:10px 14px;">
                        <input type="checkbox" id="cb-sem-pedagio" onchange="togglePedagio(this.checked)"
                               style="accent-color:#f39c12;width:18px;height:18px;flex-shrink:0">
                        <span style="font-size:.85rem;font-weight:600;color:var(--text-muted)">
                            🚫 Sem pedágios neste trecho
                        </span>
                        <span style="font-size:.75rem;color:var(--text-dim);margin-left:auto">
                            Pedágio estimado será zerado
                        </span>
                    </label>
                </div>
            </div>

            <!-- Mapa Leaflet -->
            <div style="background:var(--bg-card2);border-radius:12px;padding:16px;margin-bottom:18px;border:1px solid var(--border)">
                <div style="font-weight:600;margin-bottom:12px;color:var(--gold-light);">🗺️ Visualização da Rota</div>
                <div id="map-container" style="position:relative;">
                    <div id="route-map"></div>
                    <div id="map-loading" class="map-loading" style="display:none;">🔄 Carregando mapa...</div>
                </div>
                <div id="map-info" style="margin-top:8px;font-size:0.7rem;color:var(--text-muted);text-align:center;"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Local / Ponto de encontro</label>
                    <input type="text" name="location" id="f-loc" maxlength="255">
                </div>
                <div class="form-group">
                    <label>KM por presença (ida+volta)</label>
                    <input type="number" name="km_awarded" id="f-km" step="0.1" min="0" value="0" readonly style="background:var(--bg-card);color:var(--gold-light);font-weight:bold">
                    <small class="text-muted">Preenchido automaticamente com o dobro da rota</small>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;padding-top:8px;border-top:1px solid var(--border)">
                <button type="submit" class="btn btn-primary">Salvar evento</button>
                <button type="button" class="btn-cancel" onclick="fecharModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Leaflet via CDN (confiável) -->
<link rel="stylesheet" href="<?= BASE_URL ?>/api/assets/leaflet.css" />
<script src="<?= BASE_URL ?>/api/assets/leaflet.js"></script>

<script>
// URL da API - caminho absoluto
const API_URL = '/api/route.php';
const CONSUMO_MOTO = <?= $consumoMoto ?: 0 ?>;
const PRECO_GASOLINA = <?= $precoGasolina ?: 0 ?>;
let acTimers = {};
let routeMap = null;
let currentRouteLayer = null;

function calcularPedagioEstimado(km) {
    if (document.getElementById('cb-sem-pedagio') && document.getElementById('cb-sem-pedagio').checked) return 0;
    return km * 0.12;
}
function togglePedagio(semPedagio) {
    document.getElementById('f-avoid-tolls').value = semPedagio ? '1' : '0';
    const kmTotal = parseFloat(document.getElementById('f-km').value) || 0;
    const valorPedagios = semPedagio ? 0 : calcularPedagioEstimado(kmTotal);
    document.getElementById('f-pedagios').value = valorPedagios.toFixed(2);
    document.getElementById('rb-pedagios').textContent = semPedagio ? 'R$ 0,00' : 'R$ ' + valorPedagios.toFixed(2);
    document.getElementById('rb-pedagio-box').style.opacity = semPedagio ? '0.4' : '1';
}

// Função para desenhar a rota no mapa
function drawRouteOnMap(points, originLat, originLng, destLat, destLng, km) {
    const container = document.getElementById('route-map');
    if (!container) {
        console.error('Elemento route-map não encontrado');
        return;
    }
    
    const loadingDiv = document.getElementById('map-loading');
    if (loadingDiv) loadingDiv.style.display = 'block';
    
    // Aguarda o Leaflet carregar
    if (typeof L === 'undefined') {
        console.log('Aguardando Leaflet carregar...');
        setTimeout(function() { drawRouteOnMap(points, originLat, originLng, destLat, destLng, km); }, 500);
        return;
    }
    
    console.log('Desenhando rota no mapa...', points);
    
    // Se já existe mapa, remove layers antigos
    if (routeMap) {
        routeMap.eachLayer(function(layer) {
            if (layer instanceof L.TileLayer) return;
            routeMap.removeLayer(layer);
        });
    } else {
        routeMap = L.map('route-map').setView([originLat, originLng], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(routeMap);
    }
    
    // Desenha a rota se tiver pontos
    if (points && points.length > 0) {
        const latlngs = points.map(function(p) { return L.latLng(p[0], p[1]); });
        currentRouteLayer = L.polyline(latlngs, { color: '#f39c12', weight: 5, opacity: 0.8 }).addTo(routeMap);
        routeMap.fitBounds(currentRouteLayer.getBounds());
    }
    
    // Adiciona marcadores
    L.marker([originLat, originLng]).addTo(routeMap).bindPopup('🚩 Partida');
    L.marker([destLat, destLng]).addTo(routeMap).bindPopup('🏁 Destino');
    
    const mapInfo = document.getElementById('map-info');
    if (mapInfo) mapInfo.innerHTML = `📍 Rota calculada: ${km} km. Arraste o mapa para explorar.`;
    
    if (loadingDiv) loadingDiv.style.display = 'none';
}

// Função para limpar o mapa
function clearMap() {
    if (routeMap) {
        routeMap.eachLayer(function(layer) {
            if (layer instanceof L.TileLayer) return;
            routeMap.removeLayer(layer);
        });
    }
    const mapInfo = document.getElementById('map-info');
    if (mapInfo) mapInfo.innerHTML = '⚠️ Calcule a rota primeiro para visualizar o mapa.';
}

function setupAutocomplete(inputId, listId) {
    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    if (!input || !list) return;
    
    input.addEventListener('input', function() {
        clearTimeout(acTimers[inputId]);
        const query = this.value.trim();
        if (query.length < 3) {
            list.classList.remove('open');
            return;
        }
        
        acTimers[inputId] = setTimeout(function() {
            const url = API_URL + '?action=autocomplete&q=' + encodeURIComponent(query);
            console.log('🔍 Buscando:', url);
            
            fetch(url)
                .then(function(res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function(data) {
                    list.innerHTML = '';
                    if (!data || !data.length) {
                        list.classList.remove('open');
                        return;
                    }
                    data.forEach(function(item) {
                        if (!item.label) return;
                        var div = document.createElement('div');
                        div.className = 'ac-item';
                        div.textContent = item.label;
                        div.onclick = function() {
                            input.value = item.label;
                            list.classList.remove('open');
                        };
                        list.appendChild(div);
                    });
                    list.classList.add('open');
                })
                .catch(function(e) { 
                    console.warn('Autocomplete error:', e); 
                    list.classList.remove('open');
                });
        }, 400);
    });
    input.addEventListener('blur', function() { setTimeout(function() { list.classList.remove('open'); }, 200); });
}

window.calcularRota = async function() {
    const origin = document.getElementById('in-origin').value.trim();
    const dest = document.getElementById('in-dest').value.trim();
    const wpsRaw = document.getElementById('in-wps').value.trim();
    
    if (!origin || !dest) {
        alert('Preencha a origem e o destino.');
        return;
    }
    
    const btn = document.getElementById('btn-calc');
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Calculando rota...';
    btn.disabled = true;
    
    const waypoints = wpsRaw ? wpsRaw.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s; }) : [];
    
    console.log('📡 Enviando requisição para:', API_URL);
    console.log('📦 Dados:', { origin: origin, destination: dest, waypoints: waypoints });
    
    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'calculate_route', 
                origin: origin, 
                destination: dest, 
                waypoints: waypoints
            })
        });
        
        if (!res.ok) throw new Error('HTTP ' + res.status);
        
        const data = await res.json();
        console.log('📥 Resposta da API:', data);
        
        if (data.error) {
            alert('Erro: ' + data.error);
            return;
        }
        if (!data.ok) {
            alert('Erro ao calcular rota');
            return;
        }
        
        const kmIda = data.km;
        const kmTotal = kmIda * 2;
        
        let litrosGastos = 0;
        let custoCombustivel = 0;
        
        if (CONSUMO_MOTO > 0) {
            litrosGastos = kmTotal / CONSUMO_MOTO;
            custoCombustivel = litrosGastos * PRECO_GASOLINA;
        }
        
        const valorPedagios = calcularPedagioEstimado(kmTotal);
        
        document.getElementById('f-rkm').value = kmIda;
        document.getElementById('f-rdur').value = data.duration_min;
        document.getElementById('f-olat').value = data.origin.lat;
        document.getElementById('f-olng').value = data.origin.lon;
        document.getElementById('f-dlat').value = data.destination.lat;
        document.getElementById('f-dlng').value = data.destination.lon;
        document.getElementById('f-km').value = kmTotal;
        // Converter data.points para formato geojson para compatibilidade com Leaflet
        var geoJsonPoly = {};
        if (data.points && data.points.length > 0) {
            geoJsonPoly = {
                type: 'LineString',
                coordinates: data.points.map(function(p) { return [p[1], p[0]]; })
            };
        }
        document.getElementById('f-rpoly').value = JSON.stringify(geoJsonPoly);
        document.getElementById('f-litros').value = litrosGastos.toFixed(1);
        document.getElementById('f-custo').value = custoCombustivel.toFixed(2);
        document.getElementById('f-pedagios').value = valorPedagios.toFixed(2);
        
        if (!document.getElementById('f-loc').value) {
            document.getElementById('f-loc').value = data.destination.label;
        }
        
        const hours = Math.floor(data.duration_min / 60);
const mins = data.duration_min % 60;

let durationText = '';
if (hours > 0 && mins > 0) {
    durationText = hours + 'h ' + mins + 'min';
} else if (hours > 0) {
    durationText = hours + 'h';
} else {
    durationText = mins + 'min';
}
        
        document.getElementById('rb-km-ida').textContent = kmIda.toFixed(1) + ' km';
        document.getElementById('rb-km-total').textContent = kmTotal.toFixed(1) + ' km';
        document.getElementById('rb-dur').textContent = durationText;
        document.getElementById('rb-litros').textContent = litrosGastos > 0 ? litrosGastos.toFixed(1) + ' L' : '—';
        document.getElementById('rb-pedagios').textContent = 'R$ ' + valorPedagios.toFixed(2);
        document.getElementById('route-result').style.display = 'block';
        
        // Desenhar a rota no mapa
        if (data.points && data.points.length > 0) {
            drawRouteOnMap(data.points, data.origin.lat, data.origin.lon, data.destination.lat, data.destination.lon, kmIda);
        } else {
            console.warn('⚠️ API não retornou pontos da rota');
        }
        
        const kmField = document.getElementById('f-km');
        kmField.style.animation = 'highlight 0.5s ease';
        setTimeout(function() { kmField.style.animation = ''; }, 500);
        
    } catch(err) {
        console.error('❌ Erro na requisição:', err);
        alert('Erro ao calcular rota: ' + err.message);
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
};

window.abrirCriar = function() {
    document.getElementById('modal-titulo').textContent = 'Novo Evento';
    document.getElementById('f-action').value = 'create';
    document.getElementById('f-eid').value = '';
    document.getElementById('form-ev').reset();
    document.getElementById('route-result').style.display = 'none';
    document.getElementById('in-origin').value = '';
    document.getElementById('in-dest').value = '';
    document.getElementById('in-wps').value = '';
    clearMap();
    document.getElementById('modal-ev').classList.add('open');
};

// Previsão do tempo no admin
window.buscarPrevisaoAdmin = function() {
    var date = document.getElementById('ev-date').value;
    var lat  = parseFloat(document.getElementById('ev-origin-lat')?.value || 0);
    var lng  = parseFloat(document.getElementById('ev-origin-lng')?.value || 0);

    if (!date) return;
    if (!lat || !lng) {
        document.getElementById('admin-weather').style.display = 'block';
        document.getElementById('admin-w-loading').textContent = 'Defina a rota para ver a previsão do tempo.';
        return;
    }

    document.getElementById('admin-weather').style.display = 'block';
    document.getElementById('admin-w-loading').style.display = 'block';
    document.getElementById('admin-w-data').style.display   = 'none';
    document.getElementById('admin-w-error').style.display  = 'none';

    fetch('<?= BASE_URL ?>/api/weather.php?lat=' + lat + '&lng=' + lng + '&date=' + date)
        .then(r => r.json())
        .then(w => {
            document.getElementById('admin-w-loading').style.display = 'none';
            if (w.ok) {
                document.getElementById('admin-w-emoji').textContent = w.emoji;
                document.getElementById('admin-w-desc').textContent  = w.description;
                document.getElementById('admin-w-temp').textContent  = w.temp_min + '°C – ' + w.temp_max + '°C';
                document.getElementById('admin-w-rain').textContent  = w.rain_prob + '% chuva (' + w.rain_mm + 'mm)';
                document.getElementById('admin-w-wind').textContent  = w.wind_max + ' km/h';
                var alert = document.getElementById('admin-w-alert');
                if (w.rain_prob >= 60) {
                    alert.textContent = '⚠️ Alta chance de chuva!';
                    alert.style.display = 'block';
                } else if (w.rain_prob >= 30) {
                    alert.style.background = '#f39c1220';
                    alert.style.borderColor = '#f39c1240';
                    alert.style.color = '#f39c12';
                    alert.textContent = '🌂 Leve chance de chuva';
                    alert.style.display = 'block';
                }
                document.getElementById('admin-w-data').style.display = 'flex';
            } else if (w.error === 'Fora do alcance') {
                document.getElementById('admin-w-error').textContent = '📅 Previsão disponível apenas para os próximos 16 dias.';
                document.getElementById('admin-w-error').style.display = 'block';
            } else {
                document.getElementById('admin-w-error').textContent = 'Previsão indisponível.';
                document.getElementById('admin-w-error').style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('admin-w-loading').style.display = 'none';
            document.getElementById('admin-w-error').textContent = 'Erro ao carregar previsão.';
            document.getElementById('admin-w-error').style.display = 'block';
        });
};

window.fecharModal = function() {
    document.getElementById('modal-ev').classList.remove('open');
};

window.editarEvento = function(ev) {
    document.getElementById('modal-titulo').textContent = 'Editar Evento';
    document.getElementById('f-action').value = 'edit';
    document.getElementById('f-eid').value = ev.id || '';
    document.getElementById('f-title').value = ev.title || '';
    document.getElementById('f-date').value = ev.event_date || '';
    document.getElementById('f-desc').value = ev.description || '';
    document.getElementById('f-loc').value = ev.location || '';
    document.getElementById('f-km').value = ev.km_awarded || 0;
    document.getElementById('in-origin').value = ev.route_origin || '';
    document.getElementById('in-dest').value = ev.route_destination || '';
    document.getElementById('f-rkm').value = ev.route_km || '';
    document.getElementById('f-rdur').value = ev.route_duration_min || '';
    document.getElementById('in-wps').value = ev.route_waypoints || '';
    
    if (ev.route_km && ev.route_km > 0) {
        const kmIda = parseFloat(ev.route_km);
        const kmTotal = kmIda * 2;
        const hours = Math.floor((ev.route_duration_min || 0) / 60);
        const mins = (ev.route_duration_min || 0) % 60;
        const durationText = hours > 0 ? hours + 'h ' + mins + 'min' : mins + 'min';
        
        document.getElementById('rb-km-ida').textContent = kmIda.toFixed(1) + ' km';
        document.getElementById('rb-km-total').textContent = kmTotal.toFixed(1) + ' km';
        document.getElementById('rb-dur').textContent = durationText;
        
        if (CONSUMO_MOTO > 0 && kmTotal > 0) {
            document.getElementById('rb-litros').textContent = (kmTotal / CONSUMO_MOTO).toFixed(1) + ' L';
        }
        
        // Populate sem pedagio checkbox
        if (ev.avoid_tolls == 1) {
            document.getElementById('cb-sem-pedagio').checked = true;
            document.getElementById('f-avoid-tolls').value = '1';
            document.getElementById('rb-pedagios').textContent = 'R$ 0,00';
            document.getElementById('rb-pedagio-box').style.opacity = '0.4';
        }
        if (ev.valor_pedagios && ev.valor_pedagios > 0) {
            document.getElementById('rb-pedagios').textContent = 'R$ ' + parseFloat(ev.valor_pedagios).toFixed(2);
        } else {
            const valorPedagios = calcularPedagioEstimado(kmTotal);
            document.getElementById('rb-pedagios').textContent = 'R$ ' + valorPedagios.toFixed(2);
        }
        
        document.getElementById('route-result').style.display = 'block';
        
        // Para edição, mostrar apenas os marcadores
        if (ev.route_origin_lat && ev.route_origin_lng && ev.route_dest_lat && ev.route_dest_lng) {
            setTimeout(function() {
                if (typeof L !== 'undefined') {
                    if (routeMap) clearMap();
                    else {
                        routeMap = L.map('route-map').setView([ev.route_origin_lat, ev.route_origin_lng], 10);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(routeMap);
                    }
                    L.marker([ev.route_origin_lat, ev.route_origin_lng]).addTo(routeMap).bindPopup('🚩 Partida: ' + (ev.route_origin || 'Origem'));
                    L.marker([ev.route_dest_lat, ev.route_dest_lng]).addTo(routeMap).bindPopup('🏁 Destino: ' + (ev.route_destination || 'Destino'));
                    // Draw polyline if available
                    var bounds = [[ev.route_origin_lat, ev.route_origin_lng], [ev.route_dest_lat, ev.route_dest_lng]];
                    try {
                        var poly = ev.route_polyline ? JSON.parse(ev.route_polyline) : null;
                        if (poly && poly.coordinates && poly.coordinates.length > 0) {
                            var ll = poly.coordinates.map(function(c){return[c[1],c[0]];});
                            currentRouteLayer = L.polyline(ll, {color:'#f39c12',weight:5,opacity:0.8}).addTo(routeMap);
                            bounds = ll;
                            document.getElementById('map-info').innerHTML = '✅ Rota carregada do banco.';
                        } else {
                            document.getElementById('map-info').innerHTML = '📍 Pontos de origem e destino marcados.';
                        }
                    } catch(e) {
                        document.getElementById('map-info').innerHTML = '📍 Pontos de origem e destino marcados.';
                    }
                    routeMap.fitBounds(bounds, {padding:[20,20]});
                    // Restore polyline value
                    document.getElementById('f-rpoly').value = ev.route_polyline || '{}';
                    const loadingDiv = document.getElementById('map-loading');
                    if (loadingDiv) loadingDiv.style.display = 'none';
                }
            }, 100);
        }
    } else {
        document.getElementById('route-result').style.display = 'none';
        clearMap();
    }
    
    document.getElementById('modal-ev').classList.add('open');
};

const style = document.createElement('style');
style.textContent = `
    @keyframes highlight {
        0% { background-color: rgba(243,156,18,0); border-color: var(--border); }
        50% { background-color: rgba(243,156,18,0.3); border-color: var(--gold); }
        100% { background-color: rgba(243,156,18,0); border-color: var(--border); }
    }
`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', function() {
    setupAutocomplete('in-origin', 'ac-origin');
    setupAutocomplete('in-dest', 'ac-dest');
    
    if (typeof L !== 'undefined') {
        console.log('✅ Leaflet carregado com sucesso');
    } else {
        console.error('❌ Leaflet não carregou!');
    }
    
    const modal = document.getElementById('modal-ev');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) fecharModal();
        });
    }
});
</script>


<!-- Modal Ver Rota Admin -->
<div id="modalRotaAdmin" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;overflow-y:auto">
    <div style="background:var(--bg-card,#14161c);border:1px solid var(--border,#2a2f3a);border-radius:14px;max-width:860px;margin:20px auto;padding:0;overflow:hidden;width:95%">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px;border-bottom:1px solid var(--border,#2a2f3a)">
            <div>
                <h3 id="adm-modal-titulo" style="margin:0;font-size:1.05rem;color:var(--text,#eef0f8)"></h3>
                <div id="adm-modal-data" style="font-size:.78rem;color:var(--text-dim,#6e7485);margin-top:2px"></div>
            </div>
            <button onclick="fecharRotaAdmin()" style="background:none;border:none;color:var(--text-dim,#6e7485);font-size:1.4rem;cursor:pointer;padding:4px 8px">✕</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:1px solid var(--border,#2a2f3a)" class="adm-modal-stats">
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border,#2a2f3a)">
                <div id="adm-m-km" style="font-size:1.3rem;font-weight:700;color:#f5b041">—</div>
                <div style="font-size:.7rem;color:var(--text-dim,#6e7485);margin-top:2px">KM ida+volta</div>
            </div>
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border,#2a2f3a)">
                <div id="adm-m-dur" style="font-size:1.3rem;font-weight:700;color:#f5b041">—</div>
                <div style="font-size:.7rem;color:var(--text-dim,#6e7485);margin-top:2px">Tempo estimado</div>
            </div>
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border,#2a2f3a)">
                <div id="adm-m-comb" style="font-size:1.3rem;font-weight:700;color:#28a745">—</div>
                <div style="font-size:.7rem;color:var(--text-dim,#6e7485);margin-top:2px">⛽ Combustível médio</div>
            </div>
            <div style="padding:16px;text-align:center">
                <div id="adm-m-ped" style="font-size:1.3rem;font-weight:700;color:#e67e22">—</div>
                <div style="font-size:.7rem;color:var(--text-dim,#6e7485);margin-top:2px">💰 Pedágio</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:12px 24px;border-bottom:1px solid var(--border,#2a2f3a);font-size:.82rem">
            <span style="color:#28a745">📍</span>
            <span id="adm-m-origem" style="color:var(--text-muted,#a0a5b5)"></span>
            <span style="color:var(--text-dim,#6e7485)">→</span>
            <span id="adm-m-destino" style="color:var(--text-muted,#a0a5b5)"></span>
        </div>
        <!-- Previsão do Tempo -->
        <div id="adm-weather-section" style="display:none;padding:14px 20px;border-top:1px solid var(--border,#2a2f3a);background:var(--bg-card,#14161c);color:var(--text,#eef0f8)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <div style="font-size:.72rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em">🌤️ Previsão do Tempo</div>
                <button onclick="atualizarPrevisaoAdmin()" style="background:none;border:1px solid var(--border);border-radius:6px;color:var(--text-dim);font-size:.7rem;padding:3px 10px;cursor:pointer">🔄 Atualizar</button>
            </div>
            <div id="adm-weather-loading" style="font-size:.8rem;color:var(--text-dim)">Carregando previsão...</div>
            <div id="adm-weather-data" style="display:none">
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <div style="font-size:2.5rem" id="adm-w-emoji">☀️</div>
                    <div>
                        <div id="adm-w-desc" style="font-size:.9rem;font-weight:600;color:var(--text)"></div>
                        <div style="font-size:.78rem;color:var(--text-dim);margin-top:2px">
                            🌡️ <span id="adm-w-temp"></span> &nbsp;
                            🌧️ <span id="adm-w-rain"></span> &nbsp;
                            💨 <span id="adm-w-wind"></span>
                        </div>
                    </div>
                    <div id="adm-w-alert" style="display:none"></div>
                </div>
            </div>
            <div id="adm-weather-error" style="display:none;font-size:.78rem;color:var(--text-dim)"></div>
        </div>
        <div id="mapaRotaAdmin" style="height:380px;width:100%"></div>
        <div style="padding:16px 24px;border-top:1px solid var(--border,#2a2f3a);display:flex;justify-content:flex-end;background:var(--bg-card,#14161c)">
            <button onclick="fecharRotaAdmin()" class="btn btn-ghost btn-sm">Fechar</button>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .adm-modal-stats { grid-template-columns: repeat(2,1fr) !important; }
    .adm-modal-stats > div:nth-child(2) { border-right: none !important; }
}
@media (max-width: 768px) {
    /* Events table mobile */
    .events-table thead { display: none; }
    .events-table .event-row { display: block; border-bottom: 1px solid var(--border); padding: 12px 16px; }
    .events-table .event-row td { display: block; border: none; padding: 0; }
    .events-table .hide-mobile { display: none !important; }
    .events-table .show-mobile { display: flex !important; }
    .events-table .event-row td:last-child { margin-top: 10px; }
    .ev-btns-desktop { display: none !important; }
    .ev-btns-mobile { display: grid !important; }
    /* Lixeira table mobile */
    .users-table .lixeira-row td.hide-mobile { display: none !important; }
    .users-table .lixeira-row .show-mobile { display: block !important; }
    /* General table mobile — hide non-essential columns */
    .hide-mobile { display: none !important; }
}
</style>
<script>
var mapaAdminInstance = null;
function verRotaAdmin(ev) {
    document.getElementById('adm-modal-titulo').textContent = ev.title;
    document.getElementById('adm-modal-data').textContent = new Date(ev.event_date + 'T12:00:00').toLocaleDateString('pt-BR',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    document.getElementById('adm-m-origem').textContent  = ev.route_origin || '—';
    document.getElementById('adm-m-destino').textContent = ev.route_destination || '—';
    var kmTotal = ev.route_km * 2;
    document.getElementById('adm-m-km').textContent = kmTotal.toLocaleString('pt-BR',{maximumFractionDigits:0}) + ' km';
    var dur = ev.route_duration_min * 2;
    var h = Math.floor(dur/60), m = dur%60;
    document.getElementById('adm-m-dur').textContent = h + 'h' + (m > 0 ? m + 'min' : '');
    document.getElementById('adm-m-comb').textContent = ev.custo_combustivel > 0 ? 'R$ ' + parseFloat(ev.custo_combustivel).toFixed(2).replace('.',',') : '—';
    if (ev.avoid_tolls) {
        document.getElementById('adm-m-ped').textContent = 'Sem pedágio';
        document.getElementById('adm-m-ped').style.color = '#6e7485';
    } else {
        document.getElementById('adm-m-ped').textContent = ev.valor_pedagios > 0 ? 'R$ ' + parseFloat(ev.valor_pedagios).toFixed(2).replace('.',',') : '—';
    }
    document.getElementById('modalRotaAdmin').style.display = 'block';

    // Previsão do tempo
    window._admWLat  = ev.route_origin_lat || ev.route_dest_lat;
    window._admWLng  = ev.route_origin_lng || ev.route_dest_lng;
    window._admWDate = ev.event_date;

    window.atualizarPrevisaoAdmin = function() {
        var ws = document.getElementById('adm-weather-section');
        var wl = document.getElementById('adm-weather-loading');
        var wd = document.getElementById('adm-weather-data');
        var we = document.getElementById('adm-weather-error');
        var wa = document.getElementById('adm-w-alert');
        if (!window._admWLat || !window._admWLng || !window._admWDate) { if(ws) ws.style.display='none'; return; }
        ws.style.display='block'; wl.style.display='block';
        wd.style.display='none'; we.style.display='none'; wa.style.display='none';
        fetch(window.BASE_URL+'/api/weather.php?lat='+window._admWLat+'&lng='+window._admWLng+'&date='+window._admWDate+'&t='+Date.now())
            .then(r=>r.json())
            .then(w=>{
                wl.style.display='none';
                if(w.ok){
                    document.getElementById('adm-w-emoji').textContent=w.emoji;
                    document.getElementById('adm-w-desc').textContent=w.description;
                    document.getElementById('adm-w-temp').textContent=w.temp_min+'°C – '+w.temp_max+'°C';
                    document.getElementById('adm-w-rain').textContent=w.rain_prob+'% chuva ('+w.rain_mm+'mm)';
                    document.getElementById('adm-w-wind').textContent=w.wind_max+' km/h';
                    if(w.rain_prob>=60){wa.textContent='⚠️ Alta chance de chuva!';wa.style.cssText='display:block;margin-left:auto;background:#dc354520;border:1px solid #dc354540;border-radius:8px;padding:6px 12px;font-size:.75rem;color:#dc3545';}
                    else if(w.rain_prob>=30){wa.textContent='🌂 Leve chance de chuva';wa.style.cssText='display:block;margin-left:auto;background:#f39c1220;border:1px solid #f39c1240;border-radius:8px;padding:6px 12px;font-size:.75rem;color:#f39c12';}
                    wd.style.display='flex';
                } else if(w.error==='Fora do alcance'){
                    we.textContent='📅 Previsão disponível apenas para os próximos 16 dias.';we.style.display='block';
                } else { we.textContent='Previsão indisponível.';we.style.display='block'; }
            })
            .catch(()=>{ wl.style.display='none'; we.textContent='Erro ao carregar previsão.';we.style.display='block'; });
    };
    if(window._admWLat && window._admWLng) atualizarPrevisaoAdmin();
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
        if (mapaAdminInstance) { mapaAdminInstance.remove(); mapaAdminInstance = null; }
        mapaAdminInstance = L.map('mapaRotaAdmin');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(mapaAdminInstance);
        var bounds = [];
        if (ev.route_origin_lat) {
            var iO = L.divIcon({html:'<div style="background:#28a745;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,.4)"></div>',iconSize:[14,14],iconAnchor:[7,7],className:''});
            L.marker([ev.route_origin_lat, ev.route_origin_lng],{icon:iO}).addTo(mapaAdminInstance).bindPopup('📍 '+ev.route_origin);
            bounds.push([ev.route_origin_lat, ev.route_origin_lng]);
        }
        if (ev.route_dest_lat) {
            var iD = L.divIcon({html:'<div style="background:#f39c12;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,.4)"></div>',iconSize:[14,14],iconAnchor:[7,7],className:''});
            L.marker([ev.route_dest_lat, ev.route_dest_lng],{icon:iD}).addTo(mapaAdminInstance).bindPopup('🏁 '+ev.route_destination);
            bounds.push([ev.route_dest_lat, ev.route_dest_lng]);
        }
        try {
            var poly = JSON.parse(ev.route_polyline);
            if (poly && poly.coordinates && poly.coordinates.length > 0) {
                var ll = poly.coordinates.map(function(c){return[c[1],c[0]];});
                L.polyline(ll,{color:'#f39c12',weight:4,opacity:.8}).addTo(mapaAdminInstance);
                bounds = ll;
            }
        } catch(e){}
        if (bounds.length > 0) mapaAdminInstance.fitBounds(bounds,{padding:[20,20]});
        else if (ev.route_origin_lat) mapaAdminInstance.setView([ev.route_origin_lat,ev.route_origin_lng],8);
    }, 200);
}
function fecharRotaAdmin() {
    document.getElementById('modalRotaAdmin').style.display = 'none';
    document.body.style.overflow = '';
    if (mapaAdminInstance) { mapaAdminInstance.remove(); mapaAdminInstance = null; }
}
document.getElementById('modalRotaAdmin').addEventListener('click', function(e){ if(e.target===this) fecharRotaAdmin(); });
</script>

<!-- Lixeira -->
<?php if (!empty($lixeira)): ?>
<div style="margin-top:24px">
    <div class="card-table">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
        <h3 style="font-size:.9rem;font-weight:700;color:#dc3545;margin:0">🗑️ Lixeira <span style="font-size:.72rem;background:#dc354520;color:#dc3545;padding:2px 8px;border-radius:20px;margin-left:6px"><?= count($lixeira) ?> evento(s)</span></h3>
        <form method="POST" onsubmit="return confirm('Esvaziar a lixeira? Esta ação não pode ser desfeita.')">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="esvaziar_lixeira">
            <button type="submit" style="padding:5px 14px;background:#dc354520;border:1px solid #dc354540;border-radius:6px;color:#dc3545;font-size:.72rem;cursor:pointer;font-weight:600">🗑️ Esvaziar lixeira</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="users-table" style="width:100%">
            <thead class="hide-mobile">
                <tr>
                    <th style="color:#dc3545;width:35%">Título</th>
                    <th style="color:#dc3545;width:12%">Data</th>
                    <th style="color:#dc3545;width:15%">Local</th>
                    <th style="color:#dc3545;width:18%">Desativado em</th>
                    <th style="color:#dc3545;text-align:center;width:20%">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lixeira as $ev): ?>
            <tr class="lixeira-row">
                <td>
                    <?= htmlspecialchars($ev['title']) ?>
                    <div class="show-mobile" style="display:none;font-size:.72rem;color:var(--text-dim);margin-top:3px">
                        <?= date('d/m/Y', strtotime($ev['event_date'])) ?>
                        <?= $ev['location'] ? ' · ' . htmlspecialchars($ev['location']) : '' ?>
                        <?= $ev['deleted_at'] ? ' · Desativado: ' . date('d/m/Y', strtotime($ev['deleted_at'])) : '' ?>
                    </div>
                </td>
                <td class="hide-mobile"><?= date('d/m/Y', strtotime($ev['event_date'])) ?></td>
                <td class="hide-mobile"><?= htmlspecialchars($ev['location'] ?: '—') ?></td>
                <td class="hide-mobile"><?= $ev['deleted_at'] ? date('d/m/Y H:i', strtotime($ev['deleted_at'])) : '—' ?></td>
                <td style="text-align:center">
                    <div style="display:flex;gap:6px;justify-content:center">
                        <!-- Restaurar -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <input type="hidden" name="active" value="1">
                            <button type="submit" style="padding:5px 12px;background:#28a74520;border:1px solid #28a74540;border-radius:6px;color:#28a745;font-size:.72rem;cursor:pointer">♻️ Restaurar</button>
                        </form>
                        <!-- Excluir permanente -->
                        <form method="POST" style="display:inline" onsubmit="return confirm('Excluir PERMANENTEMENTE o evento '<?= htmlspecialchars(addslashes($ev['title'])) ?>'?

Esta ação não pode ser desfeita!')">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="excluir_permanente">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" style="padding:5px 12px;background:#dc354520;border:1px solid #dc354540;border-radius:6px;color:#dc3545;font-size:.72rem;cursor:pointer">🗑️ Excluir</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
</div>
<?php endif; ?>

<?php pageClose(); ?>