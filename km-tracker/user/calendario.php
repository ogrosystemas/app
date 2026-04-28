<?php
// user/calendario.php — Calendário de Eventos
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db  = db();
$me  = currentUser();

$mes = (int)($_GET['mes'] ?? date('m'));
$ano = (int)($_GET['ano'] ?? date('Y'));

if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$prevMes = $mes - 1; $prevAno = $ano;
if ($prevMes < 1) { $prevMes = 12; $prevAno--; }
$nextMes = $mes + 1; $nextAno = $ano;
if ($nextMes > 12) { $nextMes = 1; $nextAno++; }

$mesesNomes = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

// Buscar eventos do mês
$stmt = $db->prepare("
    SELECT e.*,
           a.status as user_status,
           CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as tem_interesse
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ?
    WHERE e.active = 1 AND MONTH(e.event_date) = ? AND YEAR(e.event_date) = ?
    ORDER BY e.event_date ASC
");
$stmt->execute([$me['id'], $mes, $ano]);
$eventos = $stmt->fetchAll();

// Indexar por dia
$eventosPorDia = [];
foreach ($eventos as $ev) {
    $dia = (int)date('d', strtotime($ev['event_date']));
    $eventosPorDia[$dia][] = $ev;
}

// Calcular dias do mês
$primeiroDia = mktime(0,0,0,$mes,1,$ano);
$diasNoMes   = (int)date('t', $primeiroDia);
$diaSemanaInicio = (int)date('N', $primeiroDia); // 1=seg, 7=dom

pageOpen('Calendário', 'calendario', '📅 Calendário de Eventos');
?>

<style>
.cal-nav { display:flex;align-items:center;justify-content:space-between;margin-bottom:24px }
.cal-nav-btn { background:var(--bg-card);border:1px solid var(--border);color:var(--text);padding:8px 16px;border-radius:8px;cursor:pointer;text-decoration:none;font-size:.85rem;font-weight:600;transition:all .15s }
.cal-nav-btn:hover { border-color:#f39c12;color:#f39c12 }
.cal-month { font-size:1.3rem;font-weight:800;color:var(--text) }
.cal-grid { display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:28px }
.cal-dow { text-align:center;font-size:.72rem;font-weight:700;color:var(--text-dim);padding:8px 0;text-transform:uppercase }
.cal-day { min-height:90px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px;transition:border-color .15s;position:relative }
.cal-day:hover { border-color:#3a3f4a }
.cal-day.today { border-color:#f39c12;background:#f39c1208 }
.cal-day.other-month { opacity:.3 }
.cal-day.has-event { cursor:pointer }
.cal-day-num { font-size:.85rem;font-weight:700;color:var(--text-dim);margin-bottom:6px }
.cal-day.today .cal-day-num { color:#f39c12 }
.cal-event-dot { font-size:.78rem;background:none;color:var(--text);border-radius:4px;padding:2px 0;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;font-weight:600 }
.cal-event-dot:hover { color:#f39c12 }
.cal-event-dot.confirmado { color:#28a745 }
.cal-event-dot.cancelado { color:#dc3545;text-decoration:line-through }
.events-list { display:flex;flex-direction:column;gap:12px }
.event-card-cal { background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;display:flex;gap:16px;align-items:flex-start;transition:border-color .15s;text-decoration:none }
.event-card-cal:hover { border-color:#f39c12 }
.event-date-badge { background:linear-gradient(135deg,#f39c12,#e67e22);border-radius:8px;padding:8px 12px;text-align:center;min-width:52px;flex-shrink:0 }
.event-date-badge .day { font-size:1.4rem;font-weight:800;color:#0d0f14;line-height:1 }
.event-date-badge .month { font-size:.65rem;font-weight:700;color:#0d0f14;text-transform:uppercase }
.event-info { flex:1 }
.event-info-title { font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:4px }
.event-info-loc { font-size:.78rem;color:var(--text-dim);margin-bottom:6px }
.event-status-badge { display:inline-block;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:20px }
@media(max-width:600px){
    .cal-day { min-height:50px;padding:4px }
    .cal-event-dot { display:none }
    .cal-day.has-event::after { content:'•';color:#f39c12;position:absolute;bottom:4px;right:6px;font-size:1rem }
    .cal-dow { font-size:.6rem;padding:4px 0 }
}
@media(max-width:480px){
    .cal-nav-btn { padding:6px 10px;font-size:.75rem }
    .cal-month { font-size:1rem }
    .event-card-cal { flex-direction:column;gap:10px }
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>📅 Calendário de Eventos</h2></div>
    </div>
</div>

<!-- Navegação -->
<div class="cal-nav">
    <a href="?mes=<?= $prevMes ?>&ano=<?= $prevAno ?>" class="cal-nav-btn">← <?= $mesesNomes[$prevMes] ?></a>
    <span class="cal-month"><?= $mesesNomes[$mes] ?> <?= $ano ?></span>
    <a href="?mes=<?= $nextMes ?>&ano=<?= $nextAno ?>" class="cal-nav-btn"><?= $mesesNomes[$nextMes] ?> →</a>
</div>

<!-- Grade do calendário -->
<div class="cal-grid">
    <?php foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $d): ?>
    <div class="cal-dow"><?= $d ?></div>
    <?php endforeach; ?>

    <?php
    // Dias vazios antes do primeiro dia
    for ($i = 1; $i < $diaSemanaInicio; $i++):
    ?>
    <div class="cal-day other-month"></div>
    <?php endfor; ?>

    <?php for ($dia = 1; $dia <= $diasNoMes; $dia++):
        $isToday = ($dia == date('d') && $mes == date('m') && $ano == date('Y'));
        $hasEvent = isset($eventosPorDia[$dia]);
    ?>
    <div class="cal-day <?= $isToday ? 'today' : '' ?> <?= $hasEvent ? 'has-event' : '' ?>"
         <?= $hasEvent ? "onclick=\"document.getElementById('events-list').scrollIntoView({behavior:'smooth'})\"" : '' ?>>
        <div class="cal-day-num"><?= $dia ?></div>
        <?php if ($hasEvent): ?>
            <?php foreach ($eventosPorDia[$dia] as $ev): ?>
            <div class="cal-event-dot <?= $ev['user_status'] ?? '' ?>"
                 onclick="event.stopPropagation();location.href='<?= BASE_URL ?>/user/events.php'">
                <?= htmlspecialchars(mb_substr($ev['title'], 0, 18)) ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endfor; ?>

    <?php
    // Dias vazios após o último dia
    $ultimoDiaSemana = (int)date('N', mktime(0,0,0,$mes,$diasNoMes,$ano));
    for ($i = $ultimoDiaSemana; $i < 7; $i++):
    ?>
    <div class="cal-day other-month"></div>
    <?php endfor; ?>
</div>

<!-- Lista de eventos do mês -->
<div id="events-list">
    <h3 style="font-size:1rem;font-weight:700;color:var(--text-muted);margin-bottom:16px">
        <?= count($eventos) ?> evento(s) em <?= $mesesNomes[$mes] ?>
    </h3>

    <?php if (empty($eventos)): ?>
    <div style="text-align:center;padding:40px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;color:var(--text-dim)">
        Nenhum evento em <?= $mesesNomes[$mes] ?> <?= $ano ?>.
    </div>
    <?php else: ?>
    <div class="events-list">
        <?php foreach ($eventos as $ev):
            $isPast = $ev['event_date'] < date('Y-m-d');
            $status = $ev['user_status'] ?? null;
            $statusLabel = ['confirmado'=>'✅ Confirmado','pendente'=>'⏳ Pendente','cancelado'=>'❌ Cancelado'];
            $statusColor = ['confirmado'=>'#28a74520;color:#28a745','pendente'=>'#f39c1220;color:#f39c12','cancelado'=>'#dc354520;color:#dc3545'];
        ?>
        <a href="<?= BASE_URL ?>/user/events.php" class="event-card-cal" style="opacity:<?= $isPast ? '.6' : '1' ?>">
            <div class="event-date-badge">
                <div class="day"><?= date('d', strtotime($ev['event_date'])) ?></div>
                <div class="month"><?= $mesesNomes[(int)date('m', strtotime($ev['event_date']))] ?></div>
            </div>
            <div class="event-info">
                <div class="event-info-title"><?= htmlspecialchars($ev['title']) ?></div>
                <?php if ($ev['location']): ?>
                <div class="event-info-loc">📍 <?= htmlspecialchars($ev['location']) ?></div>
                <?php endif; ?>
                <?php if ($ev['km_awarded']): ?>
                <div class="event-info-loc">🏍️ <?= number_format($ev['km_awarded'],0,',','.') ?> km</div>
                <?php endif; ?>
                <?php if ($status): ?>
                <span class="event-status-badge" style="background:<?= $statusColor[$status] ?? '#6b728020;color:#6b7280' ?>">
                    <?= $statusLabel[$status] ?? $status ?>
                </span>
                <?php endif; ?>
            </div>
            <?php if ($isPast): ?>
            <span style="font-size:.7rem;color:var(--text-dim);flex-shrink:0">Realizado</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php pageClose(); ?>
