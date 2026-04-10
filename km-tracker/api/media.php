<?php
// api/media.php — Gerencia mídias (listagem, exclusão)
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$me  = currentUser();
$db  = db();
$method = $_SERVER['REQUEST_METHOD'];

// GET — lista mídias de um evento
if ($method === 'GET') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    if (!$eventId) { echo json_encode([]); exit; }

    $stmt = $db->prepare('
        SELECT m.*, u.name AS user_name
        FROM event_media m
        JOIN users u ON u.id = m.user_id
        WHERE m.event_id = ?
        ORDER BY m.created_at DESC
    ');
    $stmt->execute([$eventId]);
    $rows = $stmt->fetchAll();

    $result = [];
    foreach ($rows as $r) {
        $baseUrl  = UPLOAD_URL . $eventId . '/';
        $result[] = [
            'id'          => $r['id'],
            'type'        => $r['type'],
            'url'         => $baseUrl . $r['filename'],
            'thumb_url'   => $r['type'] === 'photo' ? $baseUrl . 'thumb_' . $r['filename'] : null,
            'caption'     => $r['caption'],
            'user_name'   => $r['user_name'],
            'user_id'     => $r['user_id'],
            'size_mb'     => round($r['size_bytes'] / 1048576, 1),
            'created_at'  => $r['created_at'],
        ];
    }
    echo json_encode($result);
    exit;
}

// DELETE — remove mídia
if ($method === 'DELETE' || ($method === 'POST' && ($_POST['_method'] ?? '') === 'DELETE')) {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $mediaId = (int)($body['id'] ?? $_POST['id'] ?? 0);

    if (!$mediaId) { echo json_encode(['error' => 'ID inválido']); exit; }

    // Busca o arquivo
    $stmt = $db->prepare('SELECT * FROM event_media WHERE id=?');
    $stmt->execute([$mediaId]);
    $media = $stmt->fetch();

    if (!$media) { echo json_encode(['error' => 'Mídia não encontrada']); exit; }

    // Só o dono ou admin pode deletar
    if ($media['user_id'] !== $me['id'] && !isAdmin()) {
        echo json_encode(['error' => 'Sem permissão']); exit;
    }

    // Remove arquivos físicos
    $dir   = UPLOAD_DIR . $media['event_id'] . '/';
    @unlink($dir . $media['filename']);
    @unlink($dir . 'thumb_' . $media['filename']);

    // Remove do banco
    $db->prepare('DELETE FROM event_media WHERE id=?')->execute([$mediaId]);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'Método não suportado']);
