<?php
/**
 * api/sac_reviews.php
 * GET:  busca avaliações e reputação da conta ML ativa
 * POST: responde a uma avaliação publicamente
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

if (!$acctId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML selecionada','reviews'=>[]]);
    exit;
}

$acct = db_one(
    "SELECT access_token_enc, meli_user_id FROM meli_accounts WHERE id=? AND tenant_id=?",
    [$acctId, $tenantId]
);
if (!$acct) {
    echo json_encode(['ok'=>false,'error'=>'Conta nao encontrada','reviews'=>[]]);
    exit;
}

$token  = crypto_decrypt_token($acct['access_token_enc']);
$userId = $acct['meli_user_id'];

// ── POST: responder avaliação ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewId = trim($_POST['review_id'] ?? '');
    $reply    = trim($_POST['reply']     ?? '');

    if (!$reviewId || !$reply) {
        echo json_encode(['ok'=>false,'error'=>'Parâmetros obrigatórios']);
        exit;
    }

    if (mb_strlen($reply) > 500) {
        echo json_encode(['ok'=>false,'error'=>'Resposta muito longa (máx 500 caracteres)']);
        exit;
    }

    $url     = "https://api.mercadolibre.com/reviews/{$reviewId}/reply";
    $payload = json_encode(['reply' => $reply]);

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
        audit_log('REVIEW_REPLY', 'meli_accounts', $acctId, null, ['review_id'=>$reviewId]);
        echo json_encode(['ok'=>true]);
    } else {
        $err = json_decode($body, true);
        echo json_encode(['ok'=>false,'error'=>$err['message'] ?? "Erro ML HTTP {$code}"]);
    }
    exit;
}

// ── GET: busca reputação + avaliações ────────────────────
// Reputação geral
$repBody = null;
$ch = curl_init("https://api.mercadolibre.com/users/{$userId}/reputation");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    CURLOPT_TIMEOUT        => 10,
]);
$repRaw  = curl_exec($ch);
$repCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$reputation = null;
if ($repCode === 200) {
    $rep = json_decode($repRaw, true);
    $transactions = $rep['transactions'] ?? [];
    $total    = ($transactions['total']    ?? 0);
    $positive = ($transactions['ratings']['positive'] ?? 0);
    $negative = ($transactions['ratings']['negative'] ?? 0);
    $neutral  = ($transactions['ratings']['neutral']  ?? 0);

    $reputation = [
        'level'        => $rep['level_id'] ?? null,
        'power_seller' => $rep['power_seller_status'] ?? null,
        'rating'       => $total > 0 ? round(($positive * 5 + $neutral * 3) / $total, 1) : 0,
        'total'        => $total,
        'positive_pct' => $total > 0 ? round($positive / $total * 100) : 0,
        'negative_pct' => $total > 0 ? round($negative / $total * 100) : 0,
        'ratings'      => [
            5 => $positive,
            4 => max(0, $neutral),
            3 => 0,
            2 => 0,
            1 => $negative,
        ],
    ];
}

// Avaliações recentes
$ch = curl_init("https://api.mercadolibre.com/users/{$userId}/reviews?limit=20&offset=0");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
    CURLOPT_TIMEOUT        => 15,
]);
$revBody = curl_exec($ch);
$revCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($revCode !== 200) {
    $err = json_decode($revBody, true);
    echo json_encode([
        'ok'         => false,
        'error'      => $err['message'] ?? "Erro ML HTTP {$revCode}",
        'reviews'    => [],
        'reputation' => $reputation,
    ]);
    exit;
}

$data    = json_decode($revBody, true);
$reviews = $data['reviews'] ?? [];

// Normaliza campos
$result = array_map(function($rv) {
    return [
        'id'           => $rv['id'] ?? null,
        'rating'       => $rv['rating'] ?? 0,
        'comment'      => $rv['content'] ?? null,
        'date_created' => $rv['date_created'] ?? null,
        'order_id'     => $rv['order_id'] ?? null,
        'reviewer'     => ['nickname' => $rv['reviewer']['nickname'] ?? null],
        'reply'        => isset($rv['reply']['content'])
            ? ['comment' => $rv['reply']['content']]
            : null,
    ];
}, $reviews);

echo json_encode([
    'ok'         => true,
    'reviews'    => $result,
    'reputation' => $reputation,
    'total'      => $data['paging']['total'] ?? count($result),
]);
