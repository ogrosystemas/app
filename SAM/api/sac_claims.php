<?php
/**
 * api/sac_claims.php
 * GET  ?action=list          — lista reclamações abertas
 * GET  ?action=detail&id=X  — detalhes + mensagens de uma reclamação
 * POST action=reply          — responde a reclamação via ML
 * POST action=save_note      — salva nota interna
 * POST action=set_status     — atualiza status interno (em_andamento, aguardando, resolvido)
 * POST action=ai_suggest     — sugere resposta via IA
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';
require_once __DIR__ . '/../ai.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$action   = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Criar tabela de notas/status interno se não existir
try {
    db_query("CREATE TABLE IF NOT EXISTS claim_notes (
        id          VARCHAR(36)   NOT NULL,
        tenant_id   VARCHAR(36)   NOT NULL,
        claim_id    VARCHAR(50)   NOT NULL,
        note        TEXT          NULL,
        status      ENUM('aberta','em_andamento','aguardando_comprador','aguardando_ml','resolvida','encerrada') NOT NULL DEFAULT 'aberta',
        created_by  VARCHAR(36)   NULL,
        created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_claim (tenant_id, claim_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Throwable $e) {}

if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML selecionada']); exit; }

$acct = db_one("SELECT access_token_enc, meli_user_id FROM meli_accounts WHERE id=? AND tenant_id=?", [$acctId, $tenantId]);
if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta não encontrada']); exit; }

$token  = crypto_decrypt_token($acct['access_token_enc']);
$userId = $acct['meli_user_id'];

// ── POST: Salvar nota interna ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claimId = $_POST['claim_id'] ?? '';

    if ($action === 'save_note') {
        $note   = trim($_POST['note'] ?? '');
        $status = $_POST['status'] ?? 'em_andamento';

        if (!$claimId) { echo json_encode(['ok'=>false,'error'=>'claim_id obrigatório']); exit; }

        $existing = db_one("SELECT id FROM claim_notes WHERE tenant_id=? AND claim_id=?", [$tenantId, $claimId]);
        if ($existing) {
            db_update('claim_notes',
                ['note'=>$note, 'status'=>$status, 'created_by'=>$user['id']],
                'tenant_id=? AND claim_id=?', [$tenantId, $claimId]);
        } else {
            db_insert('claim_notes', [
                'id'         => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                    mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                    mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff)),
                'tenant_id'  => $tenantId,
                'claim_id'   => $claimId,
                'note'       => $note,
                'status'     => $status,
                'created_by' => $user['id'],
            ]);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'reply') {
        $message = trim($_POST['message'] ?? '');
        if (!$claimId || !$message) { echo json_encode(['ok'=>false,'error'=>'Campos obrigatórios']); exit; }

        $res = curl_ml(
            "https://api.mercadolibre.com/post-purchase/v1/claims/{$claimId}/messages",
            [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'from' => ['role' => 'respondent', 'user_id' => (int)$userId],
                    'message' => $message,
                    'attachments' => []
                ]),
                CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
                CURLOPT_TIMEOUT    => 15,
            ]
        );

        if ($res['code'] === 201 || $res['code'] === 200) {
            // Atualiza status para em_andamento
            $existing = db_one("SELECT id FROM claim_notes WHERE tenant_id=? AND claim_id=?", [$tenantId, $claimId]);
            $data = ['status'=>'em_andamento','created_by'=>$user['id']];
            if ($existing) db_update('claim_notes', $data, 'tenant_id=? AND claim_id=?', [$tenantId, $claimId]);
            echo json_encode(['ok'=>true]);
        } else {
            $err = json_decode($res['body'], true);
            echo json_encode(['ok'=>false,'error'=>$err['message'] ?? "Erro ML HTTP {$res['code']}"]);
        }
        exit;
    }

    if ($action === 'ai_suggest') {
        $reason  = trim($_POST['reason']  ?? '');
        $context = trim($_POST['context'] ?? '');

        $prompt = "Você é um especialista em atendimento pós-venda do Mercado Livre Brasil.
O vendedor precisa responder uma reclamação do tipo: \"{$reason}\".
Contexto adicional: {$context}

Escreva uma resposta profissional, empática e objetiva em português brasileiro.
A resposta deve:
- Reconhecer o problema do comprador
- Propor uma solução concreta (reembolso, reenvio, troca)
- Ser cordial e manter o tom de quem quer resolver
- Ter no máximo 3 parágrafos curtos

Responda APENAS com o texto da mensagem, sem explicações.";

        $result = ai_generate_for($tenantId, 'sac', $prompt, 400);
        if ($result['text']) {
            echo json_encode(['ok'=>true, 'suggestion'=>$result['text']]);
        } else {
            echo json_encode(['ok'=>false, 'error'=>'Configure um provedor de IA em Integração ML.']);
        }
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

// ── GET: Detalhes de uma reclamação ──────────────────────
if ($action === 'detail') {
    $claimId = $_GET['id'] ?? '';
    if (!$claimId) { echo json_encode(['ok'=>false,'error'=>'id obrigatório']); exit; }

    // Busca detalhes
    $detail = curl_ml("https://api.mercadolibre.com/post-purchase/v1/claims/{$claimId}",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 10]);

    // Busca mensagens
    $msgs = curl_ml("https://api.mercadolibre.com/post-purchase/v1/claims/{$claimId}/messages",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 10]);

    // Nota interna
    $note = db_one("SELECT * FROM claim_notes WHERE tenant_id=? AND claim_id=?", [$tenantId, $claimId]);

    $claim    = $detail['code'] === 200 ? json_decode($detail['body'], true) : null;
    $messages = $msgs['code'] === 200   ? (json_decode($msgs['body'], true)['messages'] ?? []) : [];

    echo json_encode([
        'ok'       => (bool)$claim,
        'claim'    => $claim,
        'messages' => $messages,
        'note'     => $note,
    ]);
    exit;
}

// ── GET: Lista de reclamações ─────────────────────────────
$statusFilter = $_GET['status'] ?? 'opened';
$url = "https://api.mercadolibre.com/post-purchase/v1/claims/search?caller.id={$userId}&role=respondent&status={$statusFilter}&limit=20";

$res  = curl_ml($url, [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 15]);
if ($res['code'] !== 200) {
    $err = json_decode($res['body'], true);
    echo json_encode(['ok'=>false,'error'=>$err['message'] ?? "Erro ML HTTP {$res['code']}"]); exit;
}

$data   = json_decode($res['body'], true);
$claims = $data['data'] ?? [];

// Busca notas internas de todas as reclamações de uma vez
$claimIds = array_column($claims, 'id');
$notes    = [];
if (!empty($claimIds)) {
    $ph    = implode(',', array_fill(0, count($claimIds), '?'));
    $rows  = db_all(
        "SELECT claim_id, status, note FROM claim_notes WHERE tenant_id=? AND claim_id IN ({$ph})",
        array_merge([$tenantId], $claimIds)
    );
    foreach ($rows as $r) $notes[$r['claim_id']] = $r;
}

// Enriquece com detalhes (limitado a 8 para não demorar)
$result = [];
foreach (array_slice($claims, 0, 8) as $claim) {
    $dr = curl_ml("https://api.mercadolibre.com/post-purchase/v1/claims/{$claim['id']}",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 8]);
    $detail = $dr['code'] === 200 ? json_decode($dr['body'], true) : $claim;
    $detail['_note'] = $notes[$claim['id']] ?? null;
    $result[] = $detail;
}

echo json_encode(['ok'=>true, 'claims'=>$result, 'total'=>$data['paging']['total'] ?? count($claims)]);
