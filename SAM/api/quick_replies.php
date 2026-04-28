<?php
/**
 * api/quick_replies.php
 * GET ?context=sac|perguntas&q=busca  — lista respostas prontas
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$context  = in_array($_GET['context']??'', ['sac','perguntas']) ? $_GET['context'] : 'sac';
$search   = trim($_GET['q'] ?? '');

try {
    $sql    = "SELECT id, title, body, tags, uses_count FROM quick_replies WHERE tenant_id=? AND context IN (?,?) ";
    $params = [$tenantId, $context, 'ambos'];
    if ($search) {
        $sql .= " AND (title LIKE ? OR body LIKE ? OR tags LIKE ?)";
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s]);
    }
    $sql .= " ORDER BY uses_count DESC, title ASC LIMIT 20";
    $replies = db_all($sql, $params);
    echo json_encode(['ok' => true, 'replies' => $replies]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'replies' => []]);
}
