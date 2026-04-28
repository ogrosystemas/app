<?php
/**
 * whatsapp.php
 * Helper para enviar notificações via Evolution API (WhatsApp)
 * Envia apenas para o número do proprietário configurado
 */

function wpp_send(string $message): bool {
    $url      = defined('EVOLUTION_URL')      ? EVOLUTION_URL      : '';
    $key      = defined('EVOLUTION_KEY')      ? EVOLUTION_KEY      : '';
    $instance = defined('EVOLUTION_INSTANCE') ? EVOLUTION_INSTANCE : '';
    $owner    = defined('EVOLUTION_OWNER')    ? EVOLUTION_OWNER    : '';

    // Não envia se não estiver configurado
    if (!$url || !$key || !$instance || !$owner) {
        return false;
    }

    $endpoint = rtrim($url, '/') . "/message/sendText/{$instance}";

    $payload = [
        'number'  => $owner,
        'text'    => $message,
        'options' => [
            'delay'    => 500,
            'presence' => 'composing',
        ],
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'apikey: ' . $key,
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);

    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 300;
}

// ── Notificações pré-formatadas ───────────────────────────

function wpp_notify_novo_pedido(string $nickname, string $orderId, float $valor): void {
    wpp_send(
        "🛒 *Novo pedido recebido!*\n" .
        "Conta: {$nickname}\n" .
        "Pedido: #{$orderId}\n" .
        "Valor: R$ " . number_format($valor, 2, ',', '.') . "\n" .
        "📦 Acesse o SAM para processar o envio."
    );
}

function wpp_notify_reclamacao(string $nickname, string $orderId, string $motivo): void {
    wpp_send(
        "⚠️ *Nova reclamação aberta!*\n" .
        "Conta: {$nickname}\n" .
        "Pedido: #{$orderId}\n" .
        "Motivo: {$motivo}\n" .
        "⏰ Responda em até 24h para evitar penalidade."
    );
}

function wpp_notify_estoque_critico(string $title, int $qty, int $minimo): void {
    wpp_send(
        "📦 *Estoque crítico!*\n" .
        "Produto: {$title}\n" .
        "Estoque atual: {$qty} unidades\n" .
        "Mínimo configurado: {$minimo}\n" .
        "Reabasteça o produto para não perder vendas."
    );
}

function wpp_notify_conta_desconectada(string $nickname): void {
    wpp_send(
        "🔴 *Conta ML desconectada!*\n" .
        "Conta: {$nickname}\n" .
        "O token expirou ou foi revogado.\n" .
        "Acesse Configurações → Integração ML para reconectar."
    );
}

function wpp_notify_pergunta_sem_resposta(string $nickname, int $total): void {
    wpp_send(
        "❓ *Perguntas sem resposta!*\n" .
        "Conta: {$nickname}\n" .
        "Total: {$total} pergunta" . ($total > 1 ? 's' : '') . " aguardando.\n" .
        "Responda para não perder vendas."
    );
}

function wpp_notify_conta_pagar(string $descricao, float $valor, string $vencimento): void {
    wpp_send(
        "💸 *Conta a pagar vencendo!*\n" .
        "Descrição: {$descricao}\n" .
        "Valor: R$ " . number_format($valor, 2, ',', '.') . "\n" .
        "Vencimento: {$vencimento}\n" .
        "Acesse Financeiro → Contas a Pagar no SAM."
    );
}

function wpp_notify_reputacao_queda(string $nickname, string $nivel): void {
    wpp_send(
        "📉 *Queda de reputação detectada!*\n" .
        "Conta: {$nickname}\n" .
        "Nível atual: {$nivel}\n" .
        "Verifique reclamações e avaliações no SAM."
    );
}

function wpp_notify_anuncio_pausado(string $nickname, string $title, string $motivo): void {
    wpp_send(
        "🚫 *Anúncio pausado pelo ML!*\n" .
        "Conta: {$nickname}\n" .
        "Produto: {$title}\n" .
        "Motivo: {$motivo}\n" .
        "Corrija e reative em Anúncios → Saúde."
    );
}
