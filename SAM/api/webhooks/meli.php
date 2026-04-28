<?php
/**
 * api/webhooks/meli.php
 * REGRA #1: Recebe, valida, enfileira e responde 200 em < 50ms.
 * NUNCA processa aqui. O worker.php faz o processamento pesado.
 */
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__, 2) . '/db.php';

// ── Responde 200 IMEDIATAMENTE ───────────────────────────
http_response_code(200);
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
echo '{"ok":true}';

// Flush para o ML — libera a conexão antes de qualquer processamento
if (ob_get_level()) ob_end_flush();
flush();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

// ── A partir daqui o ML já recebeu o 200 ────────────────

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (empty($payload) || empty($payload['topic'])) exit;

// Boas práticas ML: loga origem para auditoria
$sourceIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$topic     = $payload['topic']    ?? '';
$resource  = $payload['resource'] ?? '';
$userId    = (string)($payload['user_id'] ?? '');
$appId     = (string)($payload['application_id'] ?? '');
$attempts  = (int)($payload['attempts'] ?? 1);

// ── REGRA #2: Valida user_id ─────────────────────────────
$account = db_one(
    "SELECT id, tenant_id FROM meli_accounts
     WHERE meli_user_id=? AND is_active=1 LIMIT 1",
    [$userId]
);
if (!$account) exit; // user_id desconhecido — ignora silenciosamente

// ── REGRA #3: Idempotência por janela de 60s ─────────────
$idemKey = hash('sha256', $topic . '|' . $resource . '|' . $userId);

$recent = db_one(
    "SELECT id, status FROM queue_jobs
     WHERE idempotency_key=?
       AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
     LIMIT 1",
    [$idemKey]
);

// Se chegou duplicata nos últimos 60s e já foi processado — descarta
if ($recent && $recent['status'] === 'DONE') exit;

// Se chegou duplicata ainda pendente — não cria novo job, apenas incrementa attempts
if ($recent && in_array($recent['status'], ['PENDING', 'PROCESSING'])) {
    db_update('queue_jobs', ['attempts' => $attempts], 'id=?', [$recent['id']]);
    exit;
}

// ── REGRA #4: Prioridade por tópico ─────────────────────
$priority = match($topic) {
    'orders_v2' => 10,   // Máxima — baixa estoque, gera etiqueta
    'questions' => 8,    // Alta    — pré-venda, cliente não pode esfriar
    'messages'  => 5,    // Média   — SAC/IA
    'payments'  => 5,    // Média   — confirmação de pagamento
    'shipments' => 3,    // Normal  — atualização de envio
    'items'     => 1,    // Baixa   — alterações de preço/foto
    default     => 2,
};

// ── Enfileira o job ──────────────────────────────────────
db_upsert('queue_jobs', [
    'tenant_id'       => $account['tenant_id'],
    'meli_account_id' => $account['id'],
    'topic'           => $topic,
    'resource'        => $resource,
    'payload'         => $raw,
    'status'          => 'PENDING',
    'priority'        => $priority,
    'attempts'        => 0,
    'idempotency_key' => $idemKey,
], ['status', 'attempts']); // Se já existir por outro motivo, não reprocessa

exit;
