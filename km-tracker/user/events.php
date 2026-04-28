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

// Buscar dados da moto do usuário
$motoStmt = $db->prepare("SELECT moto_modelo, moto_kml, gas_preco FROM users WHERE id=?");
$motoStmt->execute([$uid]);
$minha_moto = $motoStmt->fetch();

$evStmt = $db->prepare("
    SELECT e.*,
           a.id as attendance_id,
           a.status as attendance_status,
           CASE WHEN a.id IS NOT NULL AND a.status != 'cancelado' THEN 1 ELSE 0 END AS tem_interesse
    FROM events e
    LEFT JOIN attendances a ON a.event_id = e.id AND a.user_id = ? AND a.status != 'cancelado'
    WHERE e.active = 1 AND YEAR(e.event_date) = ?
    ORDER BY e.event_date DESC
");
$evStmt->execute([$uid, $year]);
$events = $evStmt->fetchAll();

pageOpen("Eventos", "events", "Eventos");
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/api/assets/leaflet.css">
<script src="<?= BASE_URL ?>/api/assets/leaflet.js"></script>

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
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text);
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
    background: var(--bg-card);
    border-radius: 12px;
    border: 1px solid var(--border);
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
    border-bottom: 1px solid var(--border);
}
.events-table th {
    color:var(--text-dim);
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
    .modal-stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .modal-stats-grid > div:nth-child(2) {
        border-right: none !important;
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
                            <div style="font-size:.73rem;color:var(--text-dim);margin-top:1px"><?= htmlspecialchars(mb_substr($ev['description'], 0, 50)) ?>…</div>
                        <?php endif; ?>
                     </n></td>
                    <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($ev['event_date'])) ?> </n></td>
                    <td class="hide-mobile"><?= htmlspecialchars($ev['location'] ?: '—') ?> </n></td>
                    <td class="text-gold"><?= number_format($ev['route_km'] * 2, 0, ',', '.') ?> km</n></td>
                    <td class="hide-mobile">
                        <?php
                        $kmTotal = ($ev['route_km'] ?? 0) * 2;
                        if ($kmTotal > 0 && !empty($minha_moto['moto_kml']) && $minha_moto['moto_kml'] > 0) {
                            $meuLitros = $kmTotal / $minha_moto['moto_kml'];
                            $meuCusto  = $meuLitros * ($minha_moto['gas_preco'] ?? 0);
                            echo '<span style="color:#f5b041;font-weight:600">R$ ' . number_format($meuCusto, 2, ',', '.') . '</span>';
                            echo '<div style="font-size:.68rem;color:var(--text-dim)">' . number_format($meuLitros, 1, ',', '.') . ' L</div>';
                        } elseif ($ev['litros_gastos']) {
                            echo number_format($ev['litros_gastos'], 1, ',', '.') . ' L';
                        } else {
                            echo '<span style="color:var(--text-dim)">—</span>';
                            if (empty($minha_moto['moto_kml'])) echo '<div style="font-size:.65rem;color:#dc3545">Cadastre sua moto</div>';
                        }
                        ?>
                    </td>
                    <td class="hide-mobile">
                        <?php if ($ev['avoid_tolls']): ?>
                            <span style="color:var(--text-dim);font-size:.8rem">Sem pedágio</span>
                        <?php elseif ($ev['valor_pedagios']): ?>
                            R$ <?= number_format($ev['valor_pedagios'], 2, ',', '.') ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
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
                        <?php if (!empty($ev['route_polyline']) && $ev['route_polyline'] !== '{}'): ?>
                        <button onclick="verRota(<?= $ev['id'] ?>)"
                                style="display:block;margin-top:5px;background:#1a2a3a;color:#f39c12;border:1px solid #f39c1240;border-radius:6px;padding:4px 10px;font-size:.75rem;cursor:pointer;width:100%">
                            🗺️ Rota
                        </button>
                        <?php endif; ?>
                        <?php if ($ev['attendance_status'] == 'confirmado' || $me['role'] === 'admin'): ?>
                        <a href="<?= BASE_URL ?>/user/event_gallery.php?event_id=<?= $ev['id'] ?>"
                           style="display:block;margin-top:5px;background:var(--bg-input);color:var(--text-muted);border:1px solid var(--border);border-radius:6px;padding:4px 10px;font-size:.75rem;text-align:center;text-decoration:none;width:100%">
                            📸 Galeria
                        </a>
                        <?php endif; ?>
                     </td>
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


<!-- Modal Ver Rota -->
<div id="modalRota" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;overflow-y:auto">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;max-width:860px;margin:20px auto;padding:0;overflow:hidden;width:95%;box-shadow:0 25px 60px rgba(0,0,0,.8)">
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px;border-bottom:1px solid var(--border)">
            <div>
                <h3 id="modal-titulo" style="margin:0;font-size:1.05rem;color:var(--text)"></h3>
                <div id="modal-data" style="font-size:.78rem;color:var(--text-dim);margin-top:2px"></div>
            </div>
            <button onclick="fecharRota()" style="background:none;border:none;color:var(--text-dim);font-size:1.4rem;cursor:pointer;padding:4px 8px">✕</button>
        </div>

        <!-- Stats personalizados -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:1px solid var(--border)" class="modal-stats-grid">
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border)">
                <div id="m-km" style="font-size:1.3rem;font-weight:700;color:#f5b041">—</div>
                <div style="font-size:.7rem;color:var(--text-dim);margin-top:2px">KM ida+volta</div>
            </div>
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border)">
                <div id="m-dur" style="font-size:1.3rem;font-weight:700;color:#f5b041">—</div>
                <div style="font-size:.7rem;color:var(--text-dim);margin-top:2px">Tempo estimado</div>
            </div>
            <div style="padding:16px;text-align:center;border-right:1px solid var(--border)">
                <div id="m-comb" style="font-size:1.3rem;font-weight:700;color:#28a745">—</div>
                <div style="font-size:.7rem;color:var(--text-dim);margin-top:2px">⛽ Combustível</div>
                <div id="m-moto" style="font-size:.65rem;color:var(--text-dim)"></div>
            </div>
            <div style="padding:16px;text-align:center">
                <div id="m-ped" style="font-size:1.3rem;font-weight:700;color:#e67e22">—</div>
                <div style="font-size:.7rem;color:var(--text-dim);margin-top:2px">💰 Pedágio est.</div>
            </div>
        </div>

        <!-- Rota origem/destino -->
        <div style="display:flex;align-items:center;gap:8px;padding:12px 24px;border-bottom:1px solid var(--border);font-size:.82rem">
            <span style="color:#28a745">📍</span>
            <span id="m-origem" style="color:var(--text-muted)"></span>
            <span style="color:var(--text-dim)">→</span>
            <span id="m-destino" style="color:var(--text-muted)"></span>
        </div>

        <!-- Mapa -->
        <!-- Previsão do Tempo -->
        <div id="weather-section" style="display:none;padding:14px 20px;border-top:1px solid var(--border,#2a2f3a)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <div style="font-size:.72rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:.05em">🌤️ Previsão do Tempo</div>
                <button onclick="atualizarPrevisao()" style="background:none;border:1px solid var(--border);border-radius:6px;color:var(--text-dim);font-size:.7rem;padding:3px 10px;cursor:pointer">🔄 Atualizar</button>
            </div>
            <div id="weather-loading" style="font-size:.8rem;color:var(--text-dim)">Carregando previsão...</div>
            <div id="weather-data" style="display:none">
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <div style="font-size:2.5rem" id="w-emoji">☀️</div>
                    <div>
                        <div id="w-desc" style="font-size:.9rem;font-weight:600;color:var(--text)"></div>
                        <div style="font-size:.78rem;color:var(--text-dim);margin-top:2px">
                            🌡️ <span id="w-temp"></span> &nbsp;
                            🌧️ <span id="w-rain"></span> &nbsp;
                            💨 <span id="w-wind"></span>
                        </div>
                    </div>
                    <div id="w-alert" style="display:none"></div>
                </div>
            </div>
            <div id="weather-error" style="display:none;font-size:.78rem;color:var(--text-dim)"></div>
        </div>
        <div id="mapaRota" style="height:380px;width:100%"></div>

        <!-- Alerta sem moto -->
        <div id="alerta-moto" style="display:none;padding:12px 24px;background:#dc354515;border-top:1px solid #dc354530">
            <span style="font-size:.82rem;color:#dc3545">⚠️ Cadastre os dados da sua moto no <a href="<?= BASE_URL ?>/profile.php" style="color:#f39c12">perfil</a> para ver o custo de combustível personalizado.</span>
        </div>

        <!-- Footer -->
        <div style="padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px">
            <button onclick="fecharRota()" class="btn btn-ghost btn-sm">Fechar</button>
        </div>
    </div>
</div>

<!-- Dados dos eventos para JS -->
<script>
var eventosData = <?php
    $evData = [];
    foreach ($events as $ev) {
        $evData[$ev['id']] = [
            'id'              => $ev['id'],
            'title'           => $ev['title'],
            'event_date'      => $ev['event_date'],
            'route_km'        => (float)($ev['route_km'] ?? 0),
            'route_duration_min' => (int)($ev['route_duration_min'] ?? 0),
            'route_origin'    => $ev['route_origin'] ?? '',
            'route_destination' => $ev['route_destination'] ?? '',
            'route_origin_lat' => (float)($ev['route_origin_lat'] ?? 0),
            'route_origin_lng' => (float)($ev['route_origin_lng'] ?? 0),
            'route_dest_lat'  => (float)($ev['route_dest_lat'] ?? 0),
            'route_dest_lng'  => (float)($ev['route_dest_lng'] ?? 0),
            'route_polyline'  => $ev['route_polyline'] ?? '{}',
            'valor_pedagios'  => (float)($ev['valor_pedagios'] ?? 0),
            'avoid_tolls'     => (bool)($ev['avoid_tolls'] ?? false),
        ];
    }
    echo json_encode($evData);
?>;

var minhaMoto = <?php echo json_encode([
    'modelo'   => $minha_moto['moto_modelo'] ?? null,
    'kml'      => (float)($minha_moto['moto_kml'] ?? 0),
    'gas_preco'=> (float)($minha_moto['gas_preco'] ?? 0),
]); ?>;

var mapaRotaInstance = null;

function verRota(eventId) {
    var ev = eventosData[eventId];
    if (!ev) return;

    document.getElementById('modal-titulo').textContent = ev.title;
    document.getElementById('modal-data').textContent = new Date(ev.event_date + 'T12:00:00').toLocaleDateString('pt-BR', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
    document.getElementById('m-origem').textContent = ev.route_origin || '—';
    document.getElementById('m-destino').textContent = ev.route_destination || '—';

    // KM
    var kmTotal = ev.route_km * 2;
    document.getElementById('m-km').textContent = kmTotal.toLocaleString('pt-BR', {maximumFractionDigits:0}) + ' km';

    // Duração
    var dur = ev.route_duration_min * 2;
    var h = Math.floor(dur / 60), m = dur % 60;
    document.getElementById('m-dur').textContent = h + 'h' + (m > 0 ? m + 'min' : '');

    // Combustível personalizado
    if (minhaMoto.kml > 0 && minhaMoto.gas_preco > 0) {
        var litros = kmTotal / minhaMoto.kml;
        var custo  = litros * minhaMoto.gas_preco;
        document.getElementById('m-comb').textContent = 'R$ ' + custo.toFixed(2).replace('.', ',');
        document.getElementById('m-moto').textContent = (minhaMoto.modelo || 'Sua moto') + ' • ' + litros.toFixed(1) + ' L';
        document.getElementById('alerta-moto').style.display = 'none';
    } else {
        document.getElementById('m-comb').textContent = '—';
        document.getElementById('m-moto').textContent = '';
        document.getElementById('alerta-moto').style.display = 'block';
    }

    // Pedágio
    if (ev.avoid_tolls) {
        document.getElementById('m-ped').textContent = 'Sem pedágio';
        document.getElementById('m-ped').style.color = '#6e7485';
    } else {
        document.getElementById('m-ped').textContent = 'R$ ' + parseFloat(ev.valor_pedagios).toFixed(2).replace('.', ',');
        document.getElementById('m-ped').style.color = '#e67e22';
    }

    document.getElementById('modalRota').style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Previsão do tempo
    var _wLat  = ev.route_origin_lat || ev.route_dest_lat;
    var _wLng  = ev.route_origin_lng || ev.route_dest_lng;
    var _wDate = ev.event_date;

    window.atualizarPrevisao = function() {
        var ws = document.getElementById('weather-section');
        var wl = document.getElementById('weather-loading');
        var wd = document.getElementById('weather-data');
        var we = document.getElementById('weather-error');
        var wa = document.getElementById('w-alert');
        if (!_wLat || !_wLng || !_wDate) { if(ws) ws.style.display='none'; return; }
        ws.style.display='block'; wl.style.display='block';
        wd.style.display='none'; we.style.display='none'; wa.style.display='none';
        fetch(window.BASE_URL+'/api/weather.php?lat='+_wLat+'&lng='+_wLng+'&date='+_wDate+'&t='+Date.now())
            .then(r=>r.json())
            .then(w=>{
                wl.style.display='none';
                if(w.ok){
                    document.getElementById('w-emoji').textContent=w.emoji;
                    document.getElementById('w-desc').textContent=w.description;
                    document.getElementById('w-temp').textContent=w.temp_min+'°C – '+w.temp_max+'°C';
                    document.getElementById('w-rain').textContent=w.rain_prob+'% chuva ('+w.rain_mm+'mm)';
                    document.getElementById('w-wind').textContent=w.wind_max+' km/h';
                    if(w.rain_prob>=60){wa.textContent='⚠️ Alta chance de chuva!';wa.style.cssText='display:block;margin-left:auto;background:#dc354520;border:1px solid #dc354540;border-radius:8px;padding:6px 12px;font-size:.75rem;color:#dc3545';}
                    else if(w.rain_prob>=30){wa.textContent='🌂 Leve chance de chuva';wa.style.cssText='display:block;margin-left:auto;background:#f39c1220;border:1px solid #f39c1240;border-radius:8px;padding:6px 12px;font-size:.75rem;color:#f39c12';}
                    wd.style.display='flex';
                } else if(w.error==='Fora do alcance'){
                    we.textContent='📅 Previsão disponível apenas para os próximos 16 dias.';we.style.display='block';
                } else { we.textContent='Previsão indisponível.';we.style.display='block'; }
            })
            .catch(()=>{ wl.style.display='none'; we.textContent='Erro ao carregar previsão.';we.style.display='block'; });
    };
    if(_wLat && _wLng) atualizarPrevisao();

    // Inicializar mapa
    setTimeout(function() {
        if (mapaRotaInstance) {
            mapaRotaInstance.remove();
            mapaRotaInstance = null;
        }
        mapaRotaInstance = L.map('mapaRota');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(mapaRotaInstance);

        var bounds = [];

        // Marcador origem
        if (ev.route_origin_lat && ev.route_origin_lng) {
            var iconOrigem = L.divIcon({html:'<div style="background:#28a745;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,.4)"></div>', iconSize:[14,14], iconAnchor:[7,7], className:''});
            L.marker([ev.route_origin_lat, ev.route_origin_lng], {icon:iconOrigem})
             .addTo(mapaRotaInstance).bindPopup('📍 ' + ev.route_origin);
            bounds.push([ev.route_origin_lat, ev.route_origin_lng]);
        }

        // Marcador destino
        if (ev.route_dest_lat && ev.route_dest_lng) {
            var iconDest = L.divIcon({html:'<div style="background:#f39c12;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,.4)"></div>', iconSize:[14,14], iconAnchor:[7,7], className:''});
            L.marker([ev.route_dest_lat, ev.route_dest_lng], {icon:iconDest})
             .addTo(mapaRotaInstance).bindPopup('🏁 ' + ev.route_destination);
            bounds.push([ev.route_dest_lat, ev.route_dest_lng]);
        }

        // Polyline da rota
        try {
            var poly = JSON.parse(ev.route_polyline);
            if (poly && poly.coordinates && poly.coordinates.length > 0) {
                var latlngs = poly.coordinates.map(function(c) { return [c[1], c[0]]; });
                L.polyline(latlngs, {color:'#f39c12', weight:4, opacity:0.8}).addTo(mapaRotaInstance);
                bounds = latlngs;
            }
        } catch(e) {}

        if (bounds.length > 0) {
            mapaRotaInstance.fitBounds(bounds, {padding:[20,20]});
        } else if (ev.route_origin_lat) {
            mapaRotaInstance.setView([ev.route_origin_lat, ev.route_origin_lng], 8);
        }
    }, 200);
}

function fecharRota() {
    document.getElementById('modalRota').style.display = 'none';
    document.body.style.overflow = '';
    if (mapaRotaInstance) { mapaRotaInstance.remove(); mapaRotaInstance = null; }
}

document.getElementById('modalRota').addEventListener('click', function(e) {
    if (e.target === this) fecharRota();
});
</script>

<?php pageClose(); ?>