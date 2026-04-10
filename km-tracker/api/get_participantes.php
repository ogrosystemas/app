<?php
// api/get_participantes.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isAdmin()) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$db = db();
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) {
    echo json_encode(['error' => 'ID do evento não informado']);
    exit;
}

$stmt = $db->prepare("
    SELECT 
        u.id, u.name, u.email,
        a.id as attendance_id, a.status, a.interested_at, a.confirmed_at
    FROM attendances a
    JOIN users u ON u.id = a.user_id
    WHERE a.event_id = ?
    ORDER BY 
        FIELD(a.status, 'confirmado', 'pendente', 'cancelado'),
        a.interested_at ASC
");
$stmt->execute([$eventId]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($participants);
?>