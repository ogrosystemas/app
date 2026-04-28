<?php
// user/ranking.php — Ranking do Clube
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();
require_once __DIR__ . '/../includes/layout.php';

$db   = db();
$me   = currentUser();
$ano  = (int)($_GET['ano'] ?? date('Y'));
$anos = $db->query("SELECT DISTINCT YEAR(event_date) as ano FROM events WHERE active=1 ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($anos)) $anos = [date('Y')];

// ── Ranking de KM ─────────────────────────────────────────────────────────────
$rankKm = $db->prepare("
    SELECT u.id, u.name, u.graduacao,
           COALESCE(SUM(e.km_awarded), 0) as total_km,
           COUNT(DISTINCT a.event_id) as total_eventos
    FROM users u
    JOIN attendances a ON a.user_id = u.id AND a.status = 'confirmado'
    JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = ? AND e.active = 1
    WHERE u.active = 1
    GROUP BY u.id, u.name, u.graduacao
    ORDER BY total_km DESC, total_eventos DESC
    LIMIT 20
");
$rankKm->execute([$ano]);
$rankingKm = $rankKm->fetchAll();

// ── Ranking de Presença ───────────────────────────────────────────────────────
$rankPresenca = $db->prepare("
    SELECT u.id, u.name, u.graduacao,
           COUNT(DISTINCT a.event_id) as total_eventos,
           COALESCE(SUM(e.km_awarded), 0) as total_km
    FROM users u
    JOIN attendances a ON a.user_id = u.id AND a.status = 'confirmado'
    JOIN events e ON e.id = a.event_id AND YEAR(e.event_date) = ? AND e.active = 1
    WHERE u.active = 1
    GROUP BY u.id, u.name, u.graduacao
    ORDER BY total_eventos DESC, total_km DESC
    LIMIT 20
");
$rankPresenca->execute([$ano]);
$rankingPresenca = $rankPresenca->fetchAll();

// ── Ranking das Sextas ────────────────────────────────────────────────────────
$rankSextas = $db->prepare("
    SELECT u.id, u.name, u.graduacao,
           COUNT(DISTINCT sc.data_sexta) as total_sextas
    FROM users u
    JOIN sextas_confirmacoes sc ON sc.user_id = u.id AND sc.status = 'confirmado' AND YEAR(sc.data_sexta) = ?
    WHERE u.active = 1
    GROUP BY u.id, u.name, u.graduacao
    ORDER BY total_sextas DESC
    LIMIT 20
");
$rankSextas->execute([$ano]);
$rankingSextas = $rankSextas->fetchAll();

$gradLabels = ['diretor'=>'Diretor','subdiretor'=>'Subdiretor','escudo_fechado'=>'Escudo Fechado','meio_escudo_maior'=>'Meio Escudo Maior','meio_escudo_menor'=>'Meio Escudo Menor','pp'=>'PP','veterano'=>'Veterano'];

pageOpen('Ranking', 'ranking', '🏆 Ranking do Clube');
?>

<style>
.rank-tabs { display:flex;gap:4px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:5px;margin-bottom:24px;flex-wrap:wrap }
.rank-tab { padding:8px 18px;border-radius:7px;font-size:.82rem;font-weight:600;cursor:pointer;border:none;background:none;color:var(--text-dim);transition:all .15s }
.rank-tab.active { background:#f39c12;color:#0d0f14 }
.rank-panel { display:none }
.rank-panel.active { display:block }
.podio { display:flex;justify-content:center;align-items:flex-end;gap:16px;margin-bottom:32px;padding:20px 0 }
.podio-item { display:flex;flex-direction:column;align-items:center;gap:8px }
.podio-place { display:flex;flex-direction:column;align-items:center;justify-content:flex-end;border-radius:12px 12px 0 0;width:100px;padding:16px 8px 12px }
.podio-1 { background:linear-gradient(180deg,#f39c12,#e67e22);height:140px }
.podio-2 { background:linear-gradient(180deg,#95a5a6,#7f8c8d);height:110px }
.podio-3 { background:linear-gradient(180deg,#cd7f32,#b8692a);height:90px }
.podio-medal { font-size:1.8rem;margin-bottom:4px }
.podio-value { font-size:.95rem;font-weight:800;color:#0d0f14 }
.podio-label { font-size:.65rem;color:rgba(0,0,0,.7);font-weight:600 }
.podio-name { font-size:.78rem;font-weight:700;text-align:center;color:var(--text);max-width:100px }
.podio-grad { font-size:.65rem;color:var(--text-dim);text-align:center }
.rank-table { width:100%;border-collapse:collapse }
.rank-table tr { border-bottom:1px solid var(--border);transition:background .1s }
.rank-table tr:hover { background:var(--bg-hover) }
.rank-table td { padding:12px 16px;font-size:.85rem }
.rank-pos { font-weight:800;color:var(--text-dim);width:40px }
.rank-pos.top3 { color:#f39c12 }
.rank-name { font-weight:600;color:var(--text) }
.rank-grad { font-size:.7rem;color:var(--text-dim) }
.rank-val { font-weight:700;color:#f5b041;text-align:right }
.rank-sub { font-size:.72rem;color:var(--text-dim);text-align:right }
.rank-bar-wrap { width:100px;height:5px;background:var(--border);border-radius:3px;margin-left:auto }
.rank-bar { height:5px;background:linear-gradient(90deg,#f39c12,#e67e22);border-radius:3px }
.my-row { background:#f39c1208!important;border-left:3px solid #f39c12 }
.ano-select { background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 14px;color:var(--text);font-size:.82rem;cursor:pointer }
@media(max-width:600px){
    .podio-place{width:80px}
    .podio-1{height:110px}
    .podio-2{height:88px}
    .podio-3{height:70px}
    .rank-bar-wrap{display:none}
}
</style>

<div class="page-header">
    <div class="page-header-row">
        <div><h2>🏆 Ranking do Clube</h2></div>
        <div class="page-header-actions">
            <select class="ano-select" onchange="location.href='?ano='+this.value">
                <?php foreach ($anos as $a): ?>
                <option value="<?= $a ?>" <?= $a == $ano ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="rank-tabs">
    <button class="rank-tab active" onclick="showTab('km',this)">🏍️ KM Rodados</button>
    <button class="rank-tab" onclick="showTab('presenca',this)">🎯 Presença</button>
    <button class="rank-tab" onclick="showTab('sextas',this)">🍺 Sextas</button>
</div>

<!-- ── KM ── -->
<div id="tab-km" class="rank-panel active">
<?php if (empty($rankingKm)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-dim)">Nenhum dado para <?= $ano ?>.</div>
<?php else:
    $maxKm = max(array_column($rankingKm, 'total_km')) ?: 1;
?>
    <!-- Pódio -->
    <?php if (count($rankingKm) >= 3): ?>
    <div class="podio">
        <!-- 2º lugar -->
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingKm[1]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingKm[1]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-2">
                <span class="podio-medal">🥈</span>
                <span class="podio-value"><?= number_format($rankingKm[1]['total_km'],0,',','.') ?></span>
                <span class="podio-label">km</span>
            </div>
        </div>
        <!-- 1º lugar -->
        <div class="podio-item">
            <span style="font-size:1.5rem">👑</span>
            <span class="podio-name" style="color:#f5b041;font-size:.85rem"><?= htmlspecialchars(explode(' ', $rankingKm[0]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingKm[0]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-1">
                <span class="podio-medal">🥇</span>
                <span class="podio-value"><?= number_format($rankingKm[0]['total_km'],0,',','.') ?></span>
                <span class="podio-label">km</span>
            </div>
        </div>
        <!-- 3º lugar -->
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingKm[2]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingKm[2]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-3">
                <span class="podio-medal">🥉</span>
                <span class="podio-value"><?= number_format($rankingKm[2]['total_km'],0,',','.') ?></span>
                <span class="podio-label">km</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabela completa -->
    <div class="card">
        <table class="rank-table">
            <?php foreach ($rankingKm as $i => $r): ?>
            <tr class="<?= $r['id'] == $me['id'] ? 'my-row' : '' ?>">
                <td class="rank-pos <?= $i < 3 ? 'top3' : '' ?>"><?= $i+1 ?>º</td>
                <td>
                    <div class="rank-name"><?= htmlspecialchars($r['name']) ?> <?= $r['id']==$me['id'] ? '<span style="font-size:.65rem;background:#f39c1230;color:#f39c12;padding:1px 6px;border-radius:10px">você</span>' : '' ?></div>
                    <div class="rank-grad"><?= $gradLabels[$r['graduacao'] ?? ''] ?? 'Sem graduação' ?></div>
                </td>
                <td>
                    <div class="rank-val"><?= number_format($r['total_km'],0,',','.') ?> km</div>
                    <div class="rank-sub"><?= $r['total_eventos'] ?> evento(s)</div>
                </td>
                <td style="width:120px">
                    <div class="rank-bar-wrap">
                        <div class="rank-bar" style="width:<?= round($r['total_km']/$maxKm*100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
</div>

<!-- ── PRESENÇA ── -->
<div id="tab-presenca" class="rank-panel">
<?php if (empty($rankingPresenca)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-dim)">Nenhum dado para <?= $ano ?>.</div>
<?php else:
    $maxEv = max(array_column($rankingPresenca, 'total_eventos')) ?: 1;
?>
    <?php if (count($rankingPresenca) >= 3): ?>
    <div class="podio">
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingPresenca[1]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingPresenca[1]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-2">
                <span class="podio-medal">🥈</span>
                <span class="podio-value"><?= $rankingPresenca[1]['total_eventos'] ?></span>
                <span class="podio-label">eventos</span>
            </div>
        </div>
        <div class="podio-item">
            <span style="font-size:1.5rem">👑</span>
            <span class="podio-name" style="color:#f5b041;font-size:.85rem"><?= htmlspecialchars(explode(' ', $rankingPresenca[0]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingPresenca[0]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-1">
                <span class="podio-medal">🥇</span>
                <span class="podio-value"><?= $rankingPresenca[0]['total_eventos'] ?></span>
                <span class="podio-label">eventos</span>
            </div>
        </div>
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingPresenca[2]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingPresenca[2]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-3">
                <span class="podio-medal">🥉</span>
                <span class="podio-value"><?= $rankingPresenca[2]['total_eventos'] ?></span>
                <span class="podio-label">eventos</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="card">
        <table class="rank-table">
            <?php foreach ($rankingPresenca as $i => $r): ?>
            <tr class="<?= $r['id'] == $me['id'] ? 'my-row' : '' ?>">
                <td class="rank-pos <?= $i < 3 ? 'top3' : '' ?>"><?= $i+1 ?>º</td>
                <td>
                    <div class="rank-name"><?= htmlspecialchars($r['name']) ?> <?= $r['id']==$me['id'] ? '<span style="font-size:.65rem;background:#f39c1230;color:#f39c12;padding:1px 6px;border-radius:10px">você</span>' : '' ?></div>
                    <div class="rank-grad"><?= $gradLabels[$r['graduacao'] ?? ''] ?? 'Sem graduação' ?></div>
                </td>
                <td>
                    <div class="rank-val"><?= $r['total_eventos'] ?> evento(s)</div>
                    <div class="rank-sub"><?= number_format($r['total_km'],0,',','.') ?> km</div>
                </td>
                <td style="width:120px">
                    <div class="rank-bar-wrap">
                        <div class="rank-bar" style="width:<?= round($r['total_eventos']/$maxEv*100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
</div>

<!-- ── SEXTAS ── -->
<div id="tab-sextas" class="rank-panel">
<?php if (empty($rankingSextas)): ?>
    <div style="text-align:center;padding:60px;color:var(--text-dim)">Nenhuma foto de sexta em <?= $ano ?>.</div>
<?php else:
    $maxSex = max(array_column($rankingSextas, 'total_sextas')) ?: 1;
?>
    <?php if (count($rankingSextas) >= 3): ?>
    <div class="podio">
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingSextas[1]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingSextas[1]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-2">
                <span class="podio-medal">🥈</span>
                <span class="podio-value"><?= $rankingSextas[1]['total_sextas'] ?></span>
                <span class="podio-label">sextas</span>
            </div>
        </div>
        <div class="podio-item">
            <span style="font-size:1.5rem">👑</span>
            <span class="podio-name" style="color:#f5b041;font-size:.85rem"><?= htmlspecialchars(explode(' ', $rankingSextas[0]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingSextas[0]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-1">
                <span class="podio-medal">🥇</span>
                <span class="podio-value"><?= $rankingSextas[0]['total_sextas'] ?></span>
                <span class="podio-label">sextas</span>
            </div>
        </div>
        <div class="podio-item">
            <span class="podio-name"><?= htmlspecialchars(explode(' ', $rankingSextas[2]['name'])[0]) ?></span>
            <span class="podio-grad"><?= $gradLabels[$rankingSextas[2]['graduacao'] ?? ''] ?? '' ?></span>
            <div class="podio-place podio-3">
                <span class="podio-medal">🥉</span>
                <span class="podio-value"><?= $rankingSextas[2]['total_sextas'] ?></span>
                <span class="podio-label">sextas</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="card">
        <table class="rank-table">
            <?php foreach ($rankingSextas as $i => $r): ?>
            <tr class="<?= $r['id'] == $me['id'] ? 'my-row' : '' ?>">
                <td class="rank-pos <?= $i < 3 ? 'top3' : '' ?>"><?= $i+1 ?>º</td>
                <td>
                    <div class="rank-name"><?= htmlspecialchars($r['name']) ?> <?= $r['id']==$me['id'] ? '<span style="font-size:.65rem;background:#f39c1230;color:#f39c12;padding:1px 6px;border-radius:10px">você</span>' : '' ?></div>
                    <div class="rank-grad"><?= $gradLabels[$r['graduacao'] ?? ''] ?? 'Sem graduação' ?></div>
                </td>
                <td>
                    <div class="rank-val"><?= $r['total_sextas'] ?> sexta(s)</div>
                    <div class="rank-sub">presenças confirmadas</div>
                </td>
                <td style="width:120px">
                    <div class="rank-bar-wrap">
                        <div class="rank-bar" style="width:<?= round($r['total_sextas']/$maxSex*100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>
</div>

<script>
function showTab(id, btn) {
    document.querySelectorAll('.rank-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.rank-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php pageClose(); ?>
