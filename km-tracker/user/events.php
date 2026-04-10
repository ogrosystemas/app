<?php
// user/events.php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db = db();
$me = currentUser();
$uid = $me['id'];
$year = (int)($_GET['year'] ?? date('Y'));

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);
    
    if ($action === 'interesse') {
        $checkStmt = $db->prepare("SELECT id, status FROM attendances WHERE user_id = ? AND event_id = ?");
        $checkStmt->execute([$uid, $eventId]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            if ($existing['status'] === 'cancelado') {
                $stmt = $db->prepare("UPDATE attendances SET status = 'pendente', interested_at = NOW() WHERE id = ?");
                $stmt->execute([$existing['id']]);
                $message = 'Interesse registrado novamente! Aguarde confirmação.';
            } elseif ($existing['status'] === 'pendente') {
                $error = 'Você já manifestou interesse. Aguarde confirmação.';
            } elseif ($existing['status'] === 'confirmado') {
                $error = 'Sua participação já foi confirmada.';
            }
        } else {
            $stmt = $db->prepare("INSERT INTO attendances (user_id, event_id, status, registered_by, interested_at) VALUES (?, ?, 'pendente', ?, NOW())");
            $stmt->execute([$uid, $eventId, $uid]);
            $message = 'Interesse registrado! Aguarde confirmação.';
        }
    } elseif ($action === 'cancelar_interesse') {
        $stmt = $db->prepare("UPDATE attendances SET status = 'cancelado' WHERE user_id = ? AND event_id = ? AND status = 'pendente'");
        $stmt->execute([$uid, $eventId]);
        $message = 'Interesse cancelado.';
    }
}

$evStmt = $db->prepare("
    SELECT e.*,
           a.id as attendance_id,
           a.status as attendance_status,
           CASE WHEN a.id IS NOT NULL AND a.status != 'cancelado' THEN 1 ELSE 0 END AS tem_interesse,
           e.route_km,
           e.litros_gastos,
           e.valor_pedagios
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ? AND a.status != 'cancelado'
    WHERE e.active = 1 AND YEAR(e.event_date) = ?
    ORDER BY e.event_date DESC
");
$evStmt->execute([$uid, $year]);
$events = $evStmt->fetchAll();

pageOpen("Eventos", "events", "Eventos");
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
    border: 1px solid #2a2f3a;
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
.events-table {
    width: 100%;
    border-collapse: collapse;
}
.events-table th, .events-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #2a2f3a;
}
.events-table th {
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
.badge-disponivel {
    background: #7b9fff;
    color: #0d0f14;
}
.badge-ausente {
    background: #6e7485;
    color: white;
}
.text-gold {
    color: #f5b041;
    font-weight: 600;
}
.btn-sm {
    padding: 4px 12px;
    font-size: 0.7rem;
    border-radius: 6px;
    cursor: pointer;
    border: none;
}
.btn-primary {
    background: #f39c12;
    color: #0d0f14;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-success {
    background: #28a745;
    color: white;
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
.alert-error {
    background: rgba(220, 53, 69, 0.15);
    border: 1px solid #dc3545;
    color: #dc3545;
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
             <h2>Todos os eventos de <?= $year ?></h2>
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

<?php if ($message): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card-table">
    <div class="table-responsive">
        <table class="events-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Data</th>
                    <th class="hide-mobile">Local</th>
                    <th>KM</th>
                    <th class="hide-mobile">Combustível</th>
                    <th class="hide-mobile">Pedágio</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $ev): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <?php if ($ev['description']): ?>
                            <div style="font-size:.73rem;color:#6e7485;margin-top:1px"><?= htmlspecialchars(mb_substr($ev['description'], 0, 50)) ?>…</div>
                        <?php endif; ?>
                     </n></td>
                    <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($ev['event_date'])) ?> </n></td>
                    <td class="hide-mobile"><?= htmlspecialchars($ev['location'] ?: '—') ?> </n></td>
                    <td class="text-gold"><?= number_format($ev['route_km'] * 2, 0, ',', '.') ?> km</n></td>
                    <td class="hide-mobile"><?= $ev['litros_gastos'] ? number_format($ev['litros_gastos'], 1, ',', '.') . ' L' : '—' ?> </n></td>
                    <td class="hide-mobile"><?= $ev['valor_pedagios'] ? 'R$ ' . number_format($ev['valor_pedagios'], 2, ',', '.') : '—' ?> </n></td>
                    <td>
                        <?php if ($ev['tem_interesse']): ?>
                            <?php if ($ev['attendance_status'] == 'confirmado'): ?>
                                <span class="badge badge-confirmado">Confirmado</span>
                            <?php elseif ($ev['attendance_status'] == 'pendente'): ?>
                                <span class="badge badge-pendente">Pendente</span>
                            <?php endif; ?>
                        <?php elseif ($ev['event_date'] < date('Y-m-d')): ?>
                            <span class="badge badge-ausente">Ausente</span>
                        <?php else: ?>
                            <span class="badge badge-disponivel">Disponível</span>
                        <?php endif; ?>
                     </n></td>
                    <td>
                        <?php if ($ev['tem_interesse'] && $ev['attendance_status'] == 'pendente'): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="cancelar_interesse">
                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button class="btn-sm btn-danger">Cancelar</button>
                            </form>
                        <?php elseif (!$ev['tem_interesse'] && $ev['event_date'] >= date('Y-m-d')): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="interesse">
                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button class="btn-sm btn-primary">Participar</button>
                            </form>
                        <?php elseif ($ev['attendance_status'] == 'confirmado'): ?>
                            <button class="btn-sm btn-success" disabled>Confirmado</button>
                        <?php endif; ?>
                     </n></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" class="text-muted text-center" style="padding:28px">Nenhum evento encontrado.<?= e($row['name'] ?? '') ?></strong></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php pageClose(); ?>