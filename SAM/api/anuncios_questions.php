<?php
/**
 * api/anuncios_questions.php
 * GET:  lista perguntas pré-venda da conta ativa
 * POST: responde a uma pergunta publicamente
 *
 * GET params: filter=all|unanswered|answered
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();
session_write_close(); // libera lock da sessão antes das chamadas lentas ao ML

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

if (!$acctId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML selecionada','questions'=>[]]);
    exit;
}

$acct = db_one(
    "SELECT access_token_enc, meli_user_id FROM meli_accounts WHERE id=? AND tenant_id=?",
    [$acctId, $tenantId]
);
if (!$acct) {
    echo json_encode(['ok'=>false,'error'=>'Conta não encontrada','questions'=>[]]);
    exit;
}

$token  = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);
$userId = $acct['meli_user_id'];

// ── POST: responder pergunta ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionId = trim($_POST['question_id'] ?? '');
    $answer     = trim($_POST['answer']      ?? '');

    if (!$questionId || !$answer) {
        echo json_encode(['ok'=>false,'error'=>'Parâmetros obrigatórios']);
        exit;
    }

    if (mb_strlen($answer) > 2000) {
        echo json_encode(['ok'=>false,'error'=>'Resposta muito longa (máx 2000 caracteres)']);
        exit;
    }

    $url     = "https://api.mercadolibre.com/answers";
    $payload = json_encode([
        'question_id' => (int)$questionId,
        'text'        => $answer,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300) {
        // Atualiza cache local
        $localId = trim($_POST['local_id'] ?? '');
        if ($localId) {
            db_update('questions',
                ['status'=>'ANSWERED','answer_text'=>$answer,'answer_by_robot'=>0,'answered_at'=>date('Y-m-d H:i:s')],
                'id=? AND tenant_id=?',
                [$localId, $tenantId]
            );
        }
        audit_log('QUESTION_ANSWER', 'questions', $localId ?: $questionId);
        echo json_encode(['ok'=>true]);
    } else {
        $err = json_decode($body, true);
        echo json_encode(['ok'=>false,'error'=>$err['message'] ?? "Erro ML HTTP {$code}"]);
    }
    exit;
}

// ── GET: lista perguntas ──────────────────────────────────
$filter = in_array($_GET['filter'] ?? 'all', ['all','unanswered','answered'])
    ? $_GET['filter'] : 'all';

$statusMap = ['unanswered' => 'UNANSWERED', 'answered' => 'ANSWERED'];
$statusQs  = $statusMap[$filter] ?? null;

$url = "https://api.mercadolibre.com/questions/search?seller_id={$userId}&limit=40&sort_fields=date_created&sort_types=DESC";
if ($statusQs) $url .= "&status={$statusQs}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    CURLOPT_TIMEOUT        => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
]);
$body = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$body || $code !== 200) {
    echo json_encode([
        'ok'         => false,
        'error'      => 'ML indisponível — tente novamente',
        'questions'  => [],
        'unanswered' => 0,
    ]);
    exit;
}

$data      = json_decode($body, true);
$questions = $data['questions'] ?? [];

// Enriquecer com título do anúncio via batch (agrupado por item_id)
$itemIds = array_unique(array_filter(array_column($questions, 'item_id')));
$itemTitles = [];

if (!empty($itemIds)) {
    // Buscar localmente primeiro (mais rápido)
    $ph = implode(',', array_fill(0, count($itemIds), '?'));
    $localItems = db_all(
        "SELECT meli_item_id, title FROM products WHERE meli_item_id IN ({$ph}) AND tenant_id=?",
        array_merge($itemIds, [$tenantId])
    );
    foreach ($localItems as $item) {
        $itemTitles[$item['meli_item_id']] = $item['title'];
    }

    // Para itens não encontrados localmente, busca na API ML
    $missing = array_diff($itemIds, array_keys($itemTitles));
    if (!empty($missing)) {
        $ids = implode(',', array_slice($missing, 0, 20));
        $ch  = curl_init("https://api.mercadolibre.com/items?ids={$ids}&attributes=id,title");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
            CURLOPT_TIMEOUT        => 10,
        ]);
        $itemsBody = curl_exec($ch);
        curl_close($ch);

        $itemsData = json_decode($itemsBody, true) ?? [];
        foreach ($itemsData as $entry) {
            if (($entry['code'] ?? 0) === 200 && isset($entry['body']['id'])) {
                $itemTitles[$entry['body']['id']] = $entry['body']['title'];
            }
        }
    }
}

// Normaliza resultado
$unanswered = 0;
$result = array_map(function($q) use ($itemTitles, &$unanswered) {
    $answered = !empty($q['answer']['text']);
    if (!$answered) $unanswered++;
    return [
        'id'           => (string)($q['id'] ?? ''),
        'item_id'      => $q['item_id'] ?? null,
        'item_title'   => $itemTitles[$q['item_id'] ?? ''] ?? null,
        'text'         => $q['text'] ?? '',
        'status'       => $q['status'] ?? 'UNANSWERED',
        'date_created' => $q['date_created'] ?? null,
        'from'         => ['nickname' => $q['from']['nickname'] ?? 'Comprador'],
        'answer'       => $answered ? [
            'text'         => $q['answer']['text'],
            'date_created' => $q['answer']['date_created'] ?? null,
        ] : null,
    ];
}, $questions);

echo json_encode([
    'ok'        => true,
    'questions' => $result,
    'total'     => count($result),
    'unanswered'=> $unanswered,
]);
