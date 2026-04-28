<?php
/**
 * api/sac_data.php — SAC: conversas, mensagens, envio, IA Gemini
 * Fix: (array)$acctP em todos os array_merge
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();
auth_require();
session_write_close();
license_check();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND sc.meli_account_id = ?" : "";
$acctP    = $acctId ? [$acctId] : []; // NUNCA null

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── GET actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Lista de conversas
    if ($action === 'list') {
        $status = $_GET['status'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $statusSql = '';
        $statusP   = [];
        if ($status !== 'all') {
            $statusSql = " AND COALESCE(sc.status,'OPEN') = ?";
            $statusP   = [$status];
        }

        $searchSql = '';
        $searchP   = [];
        if ($search !== '') {
            $searchSql = " AND (o.buyer_nickname LIKE ? OR o.meli_order_id LIKE ?)";
            $searchP   = ["%{$search}%", "%{$search}%"];
        }

        $acctSqlSm = $acctId ? " AND sm.meli_account_id = ?" : "";

        $params = array_merge([$tenantId], (array)$acctP, $statusP, $searchP);

        $rows = db_all(
            "SELECT sm.order_id, o.meli_order_id, o.meli_account_id, o.buyer_nickname,
                    o.has_mediacao, COALESCE(sc.status,'OPEN') AS status,
                    MAX(sm.created_at) AS last_message_at,
                    COUNT(CASE WHEN sm.is_read=0 AND sm.from_role='BUYER' THEN 1 END) AS unread,
                    ma.nickname AS account_nickname
             FROM sac_messages sm
             JOIN orders o ON o.id = sm.order_id
             LEFT JOIN sac_conversations sc ON sc.order_id = sm.order_id
             LEFT JOIN meli_accounts ma ON ma.id = o.meli_account_id
             WHERE sm.tenant_id = ?{$acctSqlSm}{$statusSql}{$searchSql}
             GROUP BY sm.order_id
             ORDER BY last_message_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $total = db_one(
            "SELECT COUNT(DISTINCT sm.order_id) AS cnt
             FROM sac_messages sm
             JOIN orders o ON o.id = sm.order_id
             LEFT JOIN sac_conversations sc ON sc.order_id = sm.order_id
             WHERE sm.tenant_id = ?{$acctSqlSm}{$statusSql}{$searchSql}",
            $params
        );

        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'total'   => (int)($total['cnt'] ?? 0),
            'page'    => $page,
            'pages'   => (int)ceil(($total['cnt'] ?? 0) / $limit),
        ]);
        exit;
    }

    // Mensagens de uma conversa
    if ($action === 'messages') {
        $convId = $_GET['conv_id'] ?? '';
        if (!$convId) { echo json_encode(['success'=>false,'error'=>'conv_id obrigatório']); exit; }

        $conv = db_one(
            "SELECT * FROM sac_conversations WHERE id = ? AND tenant_id = ?",
            [$convId, $tenantId]
        );
        if (!$conv) { echo json_encode(['success'=>false,'error'=>'Conversa não encontrada']); exit; }

        // Marca mensagens do comprador como lidas
        db_query(
            "UPDATE sac_messages SET is_read=1 WHERE order_id=? AND tenant_id=? AND from_role='BUYER'",
            [$conv['order_id'], $tenantId]
        );

        $msgs = db_all(
            "SELECT id, from_role, from_nickname, message_text, is_read, created_at
             FROM sac_messages
             WHERE order_id = ? AND tenant_id = ?
             ORDER BY created_at ASC",
            [$conv['order_id'], $tenantId]
        );

        echo json_encode(['success'=>true, 'conv'=>$conv, 'messages'=>$msgs]);
        exit;
    }

    // Sugestão IA — usa o provedor configurado pelo tenant (multi-IA)
    if ($action === 'suggest') {
        $convId = $_GET['conv_id'] ?? '';
        if (!$convId) { echo json_encode(['success'=>false,'error'=>'conv_id obrigatório']); exit; }

        $conv = db_one(
            "SELECT * FROM sac_conversations WHERE id = ? AND tenant_id = ?",
            [$convId, $tenantId]
        );
        if (!$conv) { echo json_encode(['success'=>false,'error'=>'Não encontrado']); exit; }

        $msgs = db_all(
            "SELECT from_role, from_nickname, message_text, created_at
             FROM sac_messages
             WHERE order_id = ? AND tenant_id = ?
             ORDER BY created_at ASC LIMIT 20",
            [$conv['order_id'], $tenantId]
        );

        $history = '';
        foreach ($msgs as $m) {
            $role    = $m['from_role'] === 'SELLER' ? 'Vendedor' : 'Comprador';
            $history .= "[{$role} - {$m['from_nickname']}]: {$m['message_text']}\n";
        }

        $prompt = "Você é um assistente de atendimento ao cliente para um vendedor no Mercado Livre.\n"
                . "Analise o histórico abaixo e sugira uma resposta profissional, cordial e objetiva em português brasileiro.\n"
                . "Responda APENAS com o texto da mensagem sugerida, sem explicações.\n\n"
                . "Histórico:\n{$history}\n\nResposta sugerida:";

        require_once dirname(__DIR__) . '/ai.php';
        $result = ai_generate_for($tenantId, 'sac', $prompt, 400);

        if ($result['text']) {
            echo json_encode(['success'=>true, 'suggestion'=>$result['text']]);
        } else {
            echo json_encode(['success'=>false, 'suggestion'=>'Configure a API Key em Integração ML → IA Provedor de Linguagem.']);
        }
        exit;
    }

    // Contadores para badges
    if ($action === 'counts') {
        $params    = array_merge([$tenantId], (array)$acctP);
        $acctSqlSc = $acctId ? " AND meli_account_id = ?" : "";

        $unread = db_one(
            "SELECT COUNT(*) AS cnt FROM sac_messages
             WHERE tenant_id = ?{$acctSqlSc} AND is_read=0 AND from_role='BUYER'",
            $params
        );
        $mediacao = db_one(
            "SELECT COUNT(*) AS cnt FROM orders
             WHERE tenant_id = ?{$acctSqlSc} AND has_mediacao = 1",
            $params
        );

        echo json_encode([
            'success'  => true,
            'unread'   => (int)($unread['cnt'] ?? 0),
            'mediacao' => (int)($mediacao['cnt'] ?? 0),
        ]);
        exit;
    }

    // Handler padrão (sem action) — refresh dos gráficos do SAC
    $params    = array_merge([$tenantId], (array)$acctP);
    $acctSqlSc = $acctId ? " AND meli_account_id = ?" : "";
    $acctSqlSm = $acctId ? " AND sm.meli_account_id = ?" : "";

    // KPIs
    $open     = db_one("SELECT COUNT(*) AS cnt FROM sac_conversations sc WHERE sc.tenant_id=?{$acctSqlSc} AND sc.status='OPEN'",     $params)['cnt'] ?? 0;
    $waiting  = db_one("SELECT COUNT(*) AS cnt FROM sac_conversations sc WHERE sc.tenant_id=?{$acctSqlSc} AND sc.status='WAITING'",  $params)['cnt'] ?? 0;
    $resolved = db_one("SELECT COUNT(*) AS cnt FROM sac_conversations sc WHERE sc.tenant_id=?{$acctSqlSc} AND sc.status='RESOLVED'", $params)['cnt'] ?? 0;
    $unread   = db_one("SELECT COUNT(*) AS cnt FROM sac_messages sm WHERE sm.tenant_id=?{$acctSqlSm} AND sm.is_read=0 AND sm.from_role='BUYER'", $params)['cnt'] ?? 0;
    $mediacao = db_one("SELECT COUNT(*) AS cnt FROM orders WHERE tenant_id=?{$acctSqlSc} AND has_mediacao=1", $params)['cnt'] ?? 0;

    // Histórico 7 dias
    $weekRaw = db_all(
        "SELECT DATE(sm.created_at) as day, COUNT(*) as cnt
         FROM sac_messages sm
         WHERE sm.tenant_id=?{$acctSqlSm} AND sm.from_role='BUYER'
           AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY DATE(sm.created_at) ORDER BY day ASC",
        $params
    );

    echo json_encode([
        'success'      => true,
        'kpis'         => [
            'open'     => (int)$open,
            'waiting'  => (int)$waiting,
            'resolved' => (int)$resolved,
            'unread'   => (int)$unread,
            'mediacao' => (int)$mediacao,
        ],
        'week_history' => $weekRaw,
    ]);
    exit;
}

// ─── POST actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;

    // Enviar mensagem via API ML
    if ($action === 'send') {
        $convId  = $body['conv_id']  ?? '';
        $message = trim($body['message'] ?? '');

        if (!$convId || !$message) {
            echo json_encode(['success'=>false,'error'=>'Parâmetros obrigatórios']); exit;
        }

        $conv = db_one(
            "SELECT sc.*, o.meli_order_id, o.meli_account_id,
                    ma.access_token_enc, ma.meli_user_id
             FROM sac_conversations sc
             JOIN orders o ON o.id = sc.order_id
             JOIN meli_accounts ma ON ma.id = o.meli_account_id
             WHERE sc.id = ? AND sc.tenant_id = ?",
            [$convId, $tenantId]
        );
        if (!$conv) { echo json_encode(['success'=>false,'error'=>'Conversa não encontrada']); exit; }

        require_once __DIR__ . '/../crypto.php';
        $accessToken = crypto_decrypt_token($conv['access_token_enc']);
        if (!$accessToken) {
            echo json_encode(['success'=>false,'error'=>'Token ML inválido']); exit;
        }

        // Envia mensagem via ML API
        $payload = json_encode([
            'from' => ['user_id' => (int)$conv['meli_user_id']],
            'to'   => ['user_id' => 0],
            'text' => $message,
        ]);

        $orderId = $conv['meli_order_id'];
        $url     = "https://api.mercadolibre.com/messages/packs/{$orderId}/sellers/{$conv['meli_user_id']}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$accessToken}",
                "Content-Type: application/json",
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            // Salva mensagem local
            db_insert('sac_messages', [
                'tenant_id'       => $tenantId,
                'meli_account_id' => $conv['meli_account_id'],
                'order_id'        => $conv['order_id'],
                'from_role'       => 'SELLER',
                'from_nickname'   => $user['name'],
                'message_text'    => $message,
                'is_read'         => 1,
            ]);

            db_update('sac_conversations',
                ['status' => 'OPEN'],
                'id=? AND tenant_id=?',
                [$convId, $tenantId]
            );

            audit_log('SAC_SEND', 'sac_conversations', $convId, null, ['msg_len'=>strlen($message)]);
            echo json_encode(['success'=>true]);
        } else {
            $err = json_decode($resp, true);
            echo json_encode(['success'=>false, 'error'=> $err['message'] ?? "HTTP {$httpCode}"]);
        }
        exit;
    }

    // Fechar conversa
    if ($action === 'close') {
        $convId = $body['conv_id'] ?? '';
        if (!$convId) { echo json_encode(['success'=>false,'error'=>'conv_id obrigatório']); exit; }

        db_update('sac_conversations',
            ["status" => "CLOSED"],
            'id=? AND tenant_id=?',
            [$convId, $tenantId]
        );
        audit_log('SAC_CLOSE', 'sac_conversations', $convId, null, null);
        echo json_encode(['success'=>true]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Ação desconhecida']);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Método não suportado']);
