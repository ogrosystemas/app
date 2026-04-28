<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// KM Tracker — paths corretos
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$db = db();
$me = currentUser();
$uid = $me['id'];
$year = (int)date('Y');
$calMes = (int)date('m');
$calAno = (int)date('Y');

echo "<pre style='font-family:monospace;background:#111;color:#eee;padding:20px;font-size:13px'>";
echo "=== DIAGNÓSTICO KM TRACKER DASHBOARD ===\n\n";
echo "Usuário: " . ($me['name'] ?? '?') . " (id={$uid}, role=" . ($me['role'] ?? '?') . ")\n\n";

// ── 1. Tabelas do KM Tracker ──
echo "--- Tabelas ---\n";
$tabelas = [
    'users', 'events', 'attendances', 'sextas_confirmacoes',
    'escala_bar', 'escala_churrasco', 'churrasco_grupos', 'churrasco_grupo_membros',
    'system_settings', 'event_photos', 'motos'
];
foreach ($tabelas as $t) {
    try {
        $n = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "✓ $t — $n registros\n";
    } catch (Throwable $e) {
        echo "✗ $t — " . $e->getMessage() . "\n";
    }
}

// ── 2. Queries do dashboard ──
echo "\n--- Queries principais ---\n";

// totals
try {
    $s = $db->prepare('SELECT COUNT(CASE WHEN a.status="confirmado" AND e.event_date<=CURDATE() THEN 1 END) AS presencas, COALESCE(SUM(CASE WHEN a.status="confirmado" AND e.event_date<=CURDATE() THEN e.km_awarded+a.km_extra ELSE 0 END),0) AS total_km FROM attendances a JOIN events e ON e.id=a.event_id WHERE a.user_id=? AND YEAR(e.event_date)=?');
    $s->execute([$uid, $year]);
    $r = $s->fetch();
    echo "✓ totals — km=" . ($r['total_km']??0) . " presencas=" . ($r['presencas']??0) . "\n";
} catch (Throwable $e) { echo "✗ totals — " . $e->getMessage() . "\n"; }

// proximaSexta
try {
    $ps = getProximaSexta($db, $uid, $year);
    echo "✓ getProximaSexta() — " . ($ps ? $ps['data'] : 'null') . "\n";
} catch (Throwable $e) { echo "✗ getProximaSexta() — " . $e->getMessage() . "\n"; }

// ranking
try {
    $s = $db->prepare("SELECT u.id, COALESCE(SUM(CASE WHEN a.status='confirmado' AND e.event_date<=CURDATE() THEN e.km_awarded+a.km_extra ELSE 0 END),0) AS total_km FROM users u LEFT JOIN attendances a ON a.user_id=u.id LEFT JOIN events e ON e.id=a.event_id WHERE u.active=1 GROUP BY u.id ORDER BY total_km DESC");
    $s->execute();
    $rows = $s->fetchAll();
    $rank = 1; foreach ($rows as $r) { if ($r['id']==$uid) break; $rank++; }
    echo "✓ ranking — posição $rank de " . count($rows) . "\n";
} catch (Throwable $e) { echo "✗ ranking — " . $e->getMessage() . "\n"; }

// calEventos
try {
    $s = $db->prepare("SELECT e.*, a.status as user_status FROM events e LEFT JOIN attendances a ON a.event_id=e.id AND a.user_id=? WHERE e.active=1 AND MONTH(e.event_date)=? AND YEAR(e.event_date)=? ORDER BY e.event_date ASC");
    $s->execute([$uid, $calMes, $calAno]);
    echo "✓ calEventos — " . count($s->fetchAll()) . " eventos\n";
} catch (Throwable $e) { echo "✗ calEventos — " . $e->getMessage() . "\n"; }

// moto
try {
    $s = $db->prepare('SELECT moto_marca, moto_kml, moto_tanque, gas_preco FROM users WHERE id=?');
    $s->execute([$uid]);
    $r = $s->fetch();
    echo "✓ moto — " . ($r['moto_marca'] ?: '(sem moto)') . "\n";
} catch (Throwable $e) { echo "✗ moto — " . $e->getMessage() . "\n"; }

// proximos eventos
try {
    $s = $db->prepare("SELECT e.*, a.status as user_status FROM events e LEFT JOIN attendances a ON a.event_id=e.id AND a.user_id=? WHERE e.active=1 AND e.event_date >= CURDATE() ORDER BY e.event_date ASC LIMIT 5");
    $s->execute([$uid]);
    echo "✓ proximos eventos — " . count($s->fetchAll()) . " eventos\n";
} catch (Throwable $e) { echo "✗ proximos eventos — " . $e->getMessage() . "\n"; }

// escala bar
try {
    $s = $db->prepare("SELECT semana_inicio FROM escala_bar WHERE (user1_id=? OR user2_id=?) AND MONTH(semana_inicio)=? AND YEAR(semana_inicio)=?");
    $s->execute([$uid, $uid, $calMes, $calAno]);
    echo "✓ escala_bar — " . count($s->fetchAll()) . " registros\n";
} catch (Throwable $e) { echo "✗ escala_bar — " . $e->getMessage() . "\n"; }

// escala churrasco
try {
    $s = $db->prepare("SELECT ec.semana_inicio FROM escala_churrasco ec JOIN churrasco_grupo_membros cgm ON cgm.grupo_id=ec.grupo_id WHERE cgm.user_id=? AND MONTH(ec.semana_inicio)=? AND YEAR(ec.semana_inicio)=?");
    $s->execute([$uid, $calMes, $calAno]);
    echo "✓ escala_churrasco — " . count($s->fetchAll()) . " registros\n";
} catch (Throwable $e) { echo "✗ escala_churrasco — " . $e->getMessage() . "\n"; }

// ── 3. Helpers ──
echo "\n--- Funções helpers do KM Tracker ---\n";
foreach (['getProximaSexta','confirmarSexta','getSextasDoAno','countSextasConfirmadas','movEstoque','setting'] as $fn) {
    echo (function_exists($fn) ? "✓" : "✗") . " $fn()\n";
}

// ── 4. Settings ──
echo "\n--- system_settings ---\n";
try {
    $tema = setting('tema', 'dark');
    echo "✓ setting() OK — tema=$tema\n";
} catch (Throwable $e) { echo "✗ setting() — " . $e->getMessage() . "\n"; }

echo "\n=== FIM ===\n</pre>";
