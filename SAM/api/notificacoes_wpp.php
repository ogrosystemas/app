<?php
/**
 * api/notificacoes_wpp.php
 * Roda a cada hora via cron — verifica eventos críticos e notifica via WhatsApp
 *
 * Crontab sugerido:
 * 0 * * * * /usr/local/lsws/lsphp83/bin/php8.3 /home/www/lupa/api/notificacoes_wpp.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../crypto.php';
require_once __DIR__ . '/../whatsapp.php';

// Só roda se WPP estiver configurado
if (!EVOLUTION_KEY || !EVOLUTION_INSTANCE || !EVOLUTION_OWNER) {
    exit(0);
}

$agora = date('Y-m-d H:i:s');
echo "[{$agora}] Verificando eventos críticos...\n";

// ── 1. Contas a pagar vencendo hoje ou vencidas ───────────
$contasPagar = db_all(
    "SELECT t.description, t.amount, t.due_date
     FROM transactions t
     WHERE t.type = 'EXPENSE'
       AND t.status = 'PENDING'
       AND t.due_date <= CURDATE()
       AND (t.notified_wpp IS NULL OR t.notified_wpp < DATE_SUB(NOW(), INTERVAL 24 HOUR))
     ORDER BY t.due_date ASC
     LIMIT 5"
);

foreach ($contasPagar as $c) {
    $venc = date('d/m/Y', strtotime($c['due_date']));
    wpp_notify_conta_pagar($c['description'], (float)$c['amount'], $venc);
    // Marcar como notificado
    try {
        db_query("UPDATE transactions SET notified_wpp=NOW() WHERE description=? AND due_date=?",
            [$c['description'], $c['due_date']]);
    } catch(Throwable $e) {}
    echo "  → Conta a pagar notificada: {$c['description']}\n";
    sleep(1);
}

// ── 2. Perguntas sem resposta há mais de 2h ───────────────
$tenants = db_all("SELECT DISTINCT tenant_id FROM meli_accounts WHERE is_active=1");
foreach ($tenants as $t) {
    $tenantId = $t['tenant_id'];

    $perguntas = db_all(
        "SELECT COUNT(*) as total FROM questions
         WHERE tenant_id=? AND status='UNANSWERED'
           AND created_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)",
        [$tenantId]
    );
    $totalPerguntas = (int)($perguntas[0]['total'] ?? 0);

    if ($totalPerguntas > 0) {
        // Verificar se já notificou nas últimas 4h
        $ultimaNotif = db_one(
            "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='wpp_last_perguntas_notif'",
            [$tenantId]
        );
        $ultima = $ultimaNotif ? strtotime($ultimaNotif['value']) : 0;

        if (time() - $ultima > 14400) { // 4 horas
            $acct = db_one("SELECT nickname FROM meli_accounts WHERE tenant_id=? AND is_active=1 LIMIT 1", [$tenantId]);
            $nick = $acct['nickname'] ?? 'sua conta';
            wpp_notify_pergunta_sem_resposta($nick, $totalPerguntas);
            tenant_setting_set($tenantId, 'wpp_last_perguntas_notif', date('Y-m-d H:i:s'));
            echo "  → Perguntas sem resposta notificadas: {$totalPerguntas} ({$nick})\n";
            sleep(1);
        }
    }
}

// ── 3. Estoque crítico (≤ 3 unidades, com anúncio ativo) ─
$produtosCriticos = db_all(
    "SELECT p.title, p.stock_quantity, p.tenant_id, p.meli_item_id,
            COALESCE(ts.value, '') as ultima_notif
     FROM products p
     LEFT JOIN tenant_settings ts ON ts.tenant_id = p.tenant_id
         AND ts.`key` = CONCAT('wpp_stock_notif_', p.id)
     WHERE p.stock_quantity <= 3
       AND p.stock_quantity > 0
       AND p.ml_status = 'active'
       AND (ts.value IS NULL OR ts.updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))
     LIMIT 10"
);

foreach ($produtosCriticos as $p) {
    wpp_notify_estoque_critico($p['title'], (int)$p['stock_quantity'], 3);
    try {
        tenant_setting_set($p['tenant_id'], 'wpp_stock_notif_' . md5($p['meli_item_id']), date('Y-m-d H:i:s'));
    } catch(Throwable $e) {}
    echo "  → Estoque crítico notificado: {$p['title']} ({$p['stock_quantity']} un.)\n";
    sleep(1);
}

// ── 4. Reclamações abertas via API ML ─────────────────────
$contas = db_all("SELECT * FROM meli_accounts WHERE is_active=1");
foreach ($contas as $acct) {
    try {
        $token = crypto_decrypt_token($acct['access_token_enc']);
    } catch(Throwable $e) { continue; }

    $tenantId = $acct['tenant_id'];

    // Buscar reclamações abertas
    $ch = curl_init("https://api.mercadolibre.com/post-purchase/v1/claims/search?seller_id={$acct['meli_user_id']}&status=opened&limit=5");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) continue;

    $claims = json_decode($body, true)['data'] ?? [];
    foreach ($claims as $claim) {
        $claimId = $claim['id'] ?? '';
        $orderId = $claim['resource_id'] ?? '';
        $motivo  = $claim['reason_id'] ?? 'não especificado';

        // Verificar se já notificou esta reclamação
        $key = 'wpp_claim_notif_' . $claimId;
        $jaNotificou = db_one(
            "SELECT id FROM tenant_settings WHERE tenant_id=? AND `key`=?",
            [$tenantId, $key]
        );
        if ($jaNotificou) continue;

        wpp_notify_reclamacao($acct['nickname'], $orderId, $motivo);
        tenant_setting_set($tenantId, $key, date('Y-m-d H:i:s'));
        echo "  → Reclamação notificada: {$claimId} (pedido {$orderId})\n";
        sleep(1);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Concluído.\n";
