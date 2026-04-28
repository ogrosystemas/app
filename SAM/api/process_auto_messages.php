<?php
/**
 * api/process_auto_messages.php
 * Processa e envia mensagens automáticas pendentes.
 * Chamado pelo worker.php a cada minuto.
 */

function process_auto_messages(array $account): void {
    $tenantId = $account['tenant_id'];
    $acctId   = $account['id'];

    // Busca mensagens ativas
    $messages = db_all(
        "SELECT * FROM auto_messages WHERE tenant_id=? AND is_active=1",
        [$tenantId]
    );
    if (empty($messages)) return;

    // Para cada evento, busca pedidos recentes que precisam de mensagem
    foreach ($messages as $msg) {
        $trigger     = $msg['trigger_event'];
        $delayHours  = (int)$msg['delay_hours'];
        $msgId       = $msg['id'];

        // Define qual campo e status de orders corresponde ao trigger
        $whereStatus = match($trigger) {
            'payment_confirmed' => "payment_status IN ('APPROVED','approved') AND ship_status NOT IN ('delivered','DELIVERED')",
            'order_shipped'     => "ship_status IN ('SHIPPED','shipped','handling','HANDLING')",
            'order_delivered'   => "ship_status IN ('DELIVERED','delivered')",
            'feedback_received' => "feedback_rating IS NOT NULL",
            default             => null,
        };
        if (!$whereStatus) continue;

        $dateField = match($trigger) {
            'payment_confirmed' => 'paid_at',
            'order_shipped'     => 'shipped_at',
            'order_delivered'   => 'delivered_at',
            'feedback_received' => 'updated_at',
            default             => 'updated_at',
        };

        // Pedidos que atingiram o evento há pelo menos $delayHours horas
        // e ainda não receberam essa mensagem
        $orders = db_all(
            "SELECT o.id, o.meli_order_id, o.buyer_nickname, o.buyer_meli_id,
                    o.{$dateField} as event_date,
                    oi.title as product_title,
                    o.meli_shipment_id, o.shipping_deadline
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.tenant_id=? AND o.meli_account_id=?
               AND {$whereStatus}
               AND o.{$dateField} IS NOT NULL
               AND o.{$dateField} >= DATE_SUB(NOW(), INTERVAL 7 DAY)
               AND TIMESTAMPDIFF(HOUR, o.{$dateField}, NOW()) >= ?
               AND o.id NOT IN (
                   SELECT order_id FROM auto_messages_log
                   WHERE auto_message_id=? AND status='SENT'
               )
             GROUP BY o.id
             LIMIT 20",
            [$tenantId, $acctId, $delayHours, $msgId]
        );

        foreach ($orders as $order) {
            // Substitui variáveis
            $text = $msg['message_body'];
            $text = str_replace('{{comprador}}',     $order['buyer_nickname'] ?? 'comprador', $text);
            $text = str_replace('{{produto}}',       $order['product_title']  ?? 'seu produto', $text);
            $text = str_replace('{{numero_pedido}}', $order['meli_order_id']  ?? '', $text);
            $text = str_replace('{{data_entrega}}',  $order['shipping_deadline'] ? date('d/m/Y', strtotime($order['shipping_deadline'])) : 'em breve', $text);
            $text = str_replace('{{link_rastreio}}', $order['meli_shipment_id'] ? "https://www.mercadolivre.com.br/tracking/{$order['meli_shipment_id']}" : '', $text);

            // Envia mensagem via ML API
            $buyerId = $order['buyer_meli_id'] ?? '';
            if (!$buyerId) {
                log_auto_msg_skipped($tenantId, $msgId, $order['id'], $order['meli_order_id'], $order['buyer_nickname'], 'buyer_meli_id vazio');
                continue;
            }

            $token  = crypto_decrypt_token($account['access_token_enc']);
            $result = curl_ml("https://api.mercadolibre.com/messages/action_guide/packs/{$order['meli_order_id']}/sellers/{$account['meli_user_id']}", [
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => json_encode(['text' => $text, 'type' => 'buyer']),
                CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
                CURLOPT_TIMEOUT    => 15,
            ]);

            $logId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            if ($result['code'] >= 200 && $result['code'] < 300) {
                db_insert_ignore('auto_messages_log', [
                    'id'              => $logId,
                    'tenant_id'       => $tenantId,
                    'auto_message_id' => $msgId,
                    'order_id'        => $order['id'],
                    'meli_order_id'   => $order['meli_order_id'],
                    'buyer_nickname'  => $order['buyer_nickname'],
                    'status'          => 'SENT',
                    'sent_at'         => date('Y-m-d H:i:s'),
                ]);
                db_query("UPDATE auto_messages SET sent_count=sent_count+1 WHERE id=?", [$msgId]);
                log_worker("AUTO_MSG: enviada '{$msg['name']}' para {$order['buyer_nickname']} (pedido {$order['meli_order_id']})");
            } else {
                $err = json_decode($result['body'], true)['message'] ?? "HTTP {$result['code']}";
                log_auto_msg_skipped($tenantId, $msgId, $order['id'], $order['meli_order_id'], $order['buyer_nickname'], $err);
                log_worker("AUTO_MSG: falha '{$msg['name']}' para {$order['buyer_nickname']}: {$err}");
            }
            sleep(1); // não sobrecarregar ML
        }
    }
}

function log_auto_msg_skipped(string $tenantId, string $msgId, string $orderId, ?string $meliOrderId, ?string $buyer, string $error): void {
    try {
        $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
        db_insert_ignore('auto_messages_log', [
            'id'              => $id,
            'tenant_id'       => $tenantId,
            'auto_message_id' => $msgId,
            'order_id'        => $orderId,
            'meli_order_id'   => $meliOrderId,
            'buyer_nickname'  => $buyer,
            'status'          => 'SKIPPED',
            'error_message'   => $error,
        ]);
    } catch (Throwable $e) {}
}
