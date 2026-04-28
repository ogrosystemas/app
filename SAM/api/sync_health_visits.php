<?php
/**
 * api/sync_health_visits.php
 * Sincroniza saúde (health) e visitas de anúncios com o ML
 * Chamado via cron ou manualmente pela página de saúde
 *
 * GET ?limit=50  — sincroniza os N anúncios mais antigos
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

// Aceita chamada via cron (sem sessão) ou via browser (com sessão)
$isCron = php_sapi_name() === 'cli' || ($_GET['secret'] ?? '') === MASTER_SECRET;

if (!$isCron) {
    session_start_readonly();
    auth_require();
    header('Content-Type: application/json');
}

$tenantId = null;
$acctId   = null;

if ($isCron) {
    // Processa todas as contas de todos os tenants
    $accounts = db_all(
        "SELECT ma.*, t.id as tenant_id FROM meli_accounts ma
         JOIN tenants t ON t.id = ma.tenant_id
         WHERE ma.is_active=1 AND t.is_active=1",
        []
    );
} else {
    $user     = auth_user();
    $tenantId = $user['tenant_id'];
    $acctId   = $_SESSION['active_meli_account_id'] ?? null;
    $accounts = $acctId
        ? db_all("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId])
        : [];
}

$limit   = (int)($_GET['limit'] ?? 50);
$updated = 0;

foreach ($accounts as $acct) {
    try {
        $token = crypto_decrypt_token($acct['access_token_enc']);
        $tId   = $acct['tenant_id'];

        // Busca anúncios que precisam de atualização (os mais antigos primeiro)
        $produtos = db_all(
            "SELECT id, meli_item_id FROM products
             WHERE tenant_id=? AND meli_account_id=?
               AND meli_item_id IS NOT NULL
               AND ml_status IN ('ACTIVE','PAUSED')
             ORDER BY updated_at ASC LIMIT ?",
            [$tId, $acct['id'], $limit]
        );

        if (empty($produtos)) continue;

        // Busca em lotes de 20 (limite da API ML)
        $chunks = array_chunk($produtos, 20);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'meli_item_id'));

            $result = curl_ml(
                "https://api.mercadolibre.com/items?ids={$ids}&attributes=id,status,health,sold_quantity",
                [
                    CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
                    CURLOPT_TIMEOUT    => 15,
                ]
            );

            if ($result['code'] !== 200) continue;
            $items = json_decode($result['body'], true) ?? [];

            foreach ($items as $item) {
                if (empty($item['body']['id'])) continue;
                $meli_id = $item['body']['id'];
                $health  = isset($item['body']['health']) ? (int)round($item['body']['health'] * 100) : null;
                $status  = $item['body']['status'] ?? null;

                db_update('products',
                    array_filter(['ml_health'=>$health, 'ml_status'=>$status ? strtoupper($status) : null], fn($v) => $v !== null),
                    'meli_item_id=? AND tenant_id=?',
                    [$meli_id, $tId]
                );
                $updated++;
            }

            // Busca visitas separadamente
            foreach ($chunk as $p) {
                $vis = curl_ml(
                    "https://api.mercadolibre.com/items/{$p['meli_item_id']}/visits/time_window?last=30&unit=day&ending=today",
                    [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 8]
                );
                if ($vis['code'] === 200) {
                    $visData    = json_decode($vis['body'], true);
                    $totalVisits = array_sum(array_column($visData['results'] ?? [], 'total'));
                    db_update('products', ['ml_visits'=>$totalVisits, 'updated_at'=>date('Y-m-d H:i:s')],
                        'id=? AND tenant_id=?', [$p['id'], $tId]);
                }
                usleep(100000); // 100ms
            }

            usleep(300000); // 300ms entre chunks
        }
    } catch (Throwable $e) {
        if ($isCron) echo "[WARN] Conta {$acct['nickname']}: " . $e->getMessage() . "\n";
    }
}

if (!$isCron) {
    echo json_encode(['ok'=>true, 'updated'=>$updated]);
}
