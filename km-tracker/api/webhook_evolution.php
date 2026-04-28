<?php
/**
 * api/webhook_evolution.php
 * Recebe notificações da Evolution API
 * - Votos em enquetes nativas do WhatsApp
 * 
 * Configure na Evolution API:
 * URL: https://kmtracker.ogrosystemas.com.br/api/webhook_evolution.php
 * Eventos: MESSAGES_UPDATE, SEND_MESSAGE
 */

ob_start();

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

// Ler payload
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

// Log para debug
$logFile = __DIR__ . '/../logs/webhook_evolution.log';
if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0755, true);
@file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $rawBody . "\n", FILE_APPEND);

// Responder 200 imediatamente
ob_end_clean();
http_response_code(200);
echo json_encode(['status' => 'received']);

if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

// Processar evento
$event = $payload['event'] ?? $payload['type'] ?? '';
$data  = $payload['data'] ?? $payload;

try {
    $db = db();

    // Voto em enquete nativa do WhatsApp
    if (in_array($event, ['messages.update', 'MESSAGES_UPDATE', 'message.update'])) {
        foreach ((array)$data as $msg) {
            $update = $msg['update'] ?? $msg;
            $pollVotes = $update['pollUpdates'] ?? $update['pollUpdate'] ?? null;

            if (!$pollVotes) continue;

            // Pegar o ID da mensagem original da enquete
            $originalMsgId = $msg['key']['id'] ?? $msg['id'] ?? null;
            $voterJid = $msg['key']['participant'] ?? $msg['key']['remoteJid'] ?? null;

            if (!$originalMsgId || !$voterJid) continue;

            // Extrair número do JID
            $voterPhone = preg_replace('/@.*/', '', $voterJid);

            // Buscar usuário pelo WhatsApp
            $stmt = $db->prepare("SELECT id FROM users WHERE whatsapp LIKE ?");
            $stmt->execute(['%' . substr($voterPhone, -9)]);
            $user = $stmt->fetch();
            if (!$user) continue;

            // Buscar enquete pelo msg_id
            $stmt = $db->prepare("SELECT eq.id, eq.opcoes FROM enquetes eq
                JOIN notificacoes_log nl ON nl.notificacao_id = eq.notificacao_id
                WHERE nl.whatsapp LIKE ? AND eq.status = 'ativa'
                ORDER BY eq.criado_em DESC LIMIT 1");
            $stmt->execute(['%' . substr($voterPhone, -9) . '%']);
            $enquete = $stmt->fetch();

            if (!$enquete) {
                // Tenta buscar pela enquete mais recente ativa
                $enquete = $db->query("SELECT id, opcoes FROM enquetes WHERE status='ativa' ORDER BY criado_em DESC LIMIT 1")->fetch();
            }
            if (!$enquete) continue;

            $opcoes = json_decode($enquete['opcoes'], true) ?? [];
            $votedOptions = [];

            foreach ((array)$pollVotes as $vote) {
                $optionHash = $vote['pollUpdateMessageKey']['id'] ?? $vote['name'] ?? null;
                if ($optionHash && in_array($optionHash, $opcoes)) {
                    $votedOptions[] = $optionHash;
                } elseif ($optionHash) {
                    // Tenta match por índice
                    foreach ($opcoes as $opcao) {
                        $votedOptions[] = $opcao;
                        break;
                    }
                }
            }

            if (empty($votedOptions)) {
                // Fallback: pega o primeiro voto
                $votedOptions = [reset($opcoes)];
            }

            $resposta = $votedOptions[0];
            $db->prepare("INSERT INTO enquetes_respostas (enquete_id, user_id, resposta)
                          VALUES (?,?,?)
                          ON DUPLICATE KEY UPDATE resposta=VALUES(resposta), respondido_em=NOW()")
               ->execute([$enquete['id'], $user['id'], $resposta]);

            @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Voto registrado: user={$user['id']} enquete={$enquete['id']} resposta={$resposta}\n", FILE_APPEND);
        }
    }

} catch (Throwable $e) {
    @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
}
