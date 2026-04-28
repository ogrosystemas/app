<?php
/**
 * api/metricas_ads.php
 * GET ?period=7|14|30 — busca métricas de anúncios patrocinados
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$period   = in_array((int)($_GET['period']??7), [7,14,30]) ? (int)$_GET['period'] : 7;

if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa']); exit; }

$acct = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId]);
if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta não encontrada']); exit; }

$token  = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);
$userId = $acct['meli_user_id'] ?? '';

if (!$userId) { echo json_encode(['ok'=>false,'error'=>'ID do usuário ML não encontrado']); exit; }

$dateFrom = date('Y-m-d', strtotime("-{$period} days"));
$dateTo   = date('Y-m-d');

// Busca campanhas de ADS
$campResult = curl_ml(
    "https://api.mercadolibre.com/advertising/v1/campaigns?user_id={$userId}&status=ACTIVE&limit=50",
    [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 15]
);

if ($campResult['code'] !== 200) {
    // Tenta endpoint alternativo
    $campResult = curl_ml(
        "https://api.mercadolibre.com/advertiser/{$userId}/campaign?date_from={$dateFrom}&date_to={$dateTo}",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 15]
    );
}

if ($campResult['code'] !== 200) {
    echo json_encode(['ok'=>false,'error'=>'ADS não disponível para esta conta. Verifique se há campanhas ativas no ML.']);
    exit;
}

$campaigns = json_decode($campResult['body'], true) ?? [];
if (isset($campaigns['results'])) $campaigns = $campaigns['results'];
if (empty($campaigns)) {
    echo json_encode(['ok'=>true,'totals'=>null,'daily'=>[],'items'=>[],'msg'=>'Nenhuma campanha ADS ativa']);
    exit;
}

// Busca métricas das campanhas
$totalImpressions = 0;
$totalClicks      = 0;
$totalConversions = 0;
$totalSpend       = 0.0;
$totalRevenue     = 0.0;
$dailyData        = [];
$itemsData        = [];

foreach (array_slice($campaigns, 0, 5) as $camp) {
    $campId = $camp['id'] ?? $camp['campaign_id'] ?? null;
    if (!$campId) continue;

    $metricsResult = curl_ml(
        "https://api.mercadolibre.com/advertising/v1/campaigns/{$campId}/metrics?date_from={$dateFrom}&date_to={$dateTo}&period=daily",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 15]
    );

    if ($metricsResult['code'] !== 200) continue;
    $metrics = json_decode($metricsResult['body'], true) ?? [];

    foreach ($metrics['daily'] ?? $metrics['results'] ?? [] as $day) {
        $date = substr($day['date'] ?? '', 0, 10);
        if (!$date) continue;
        $fmtDate = $date ? date('d/m', strtotime($date)) : $date;

        if (!isset($dailyData[$fmtDate])) {
            $dailyData[$fmtDate] = ['date'=>$fmtDate,'impressions'=>0,'clicks'=>0,'conversions'=>0,'spend'=>0];
        }
        $dailyData[$fmtDate]['impressions']  += (int)($day['impressions'] ?? 0);
        $dailyData[$fmtDate]['clicks']        += (int)($day['clicks'] ?? 0);
        $dailyData[$fmtDate]['conversions']   += (int)($day['conversions'] ?? 0);
        $dailyData[$fmtDate]['spend']         += (float)($day['spend'] ?? $day['cost'] ?? 0);

        $totalImpressions += (int)($day['impressions'] ?? 0);
        $totalClicks      += (int)($day['clicks'] ?? 0);
        $totalConversions += (int)($day['conversions'] ?? 0);
        $totalSpend       += (float)($day['spend'] ?? $day['cost'] ?? 0);
        $totalRevenue     += (float)($day['revenue'] ?? 0);
    }

    // Métricas por item/anúncio
    $itemsResult = curl_ml(
        "https://api.mercadolibre.com/advertising/v1/campaigns/{$campId}/metrics?date_from={$dateFrom}&date_to={$dateTo}&dimension=item",
        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 15]
    );
    if ($itemsResult['code'] === 200) {
        $itemsMetrics = json_decode($itemsResult['body'], true) ?? [];
        foreach ($itemsMetrics['results'] ?? [] as $item) {
            $itemId = $item['item_id'] ?? $item['id'] ?? '';
            if (!$itemId) continue;

            // Tenta buscar título do banco
            $prod = db_one("SELECT title FROM products WHERE meli_item_id=? AND tenant_id=?", [$itemId, $tenantId]);

            $clicks      = (int)($item['clicks'] ?? 0);
            $impressions = (int)($item['impressions'] ?? 0);
            $spend       = (float)($item['spend'] ?? $item['cost'] ?? 0);
            $revenue     = (float)($item['revenue'] ?? 0);
            $conversions = (int)($item['conversions'] ?? 0);

            $itemsData[] = [
                'item_id'     => $itemId,
                'title'       => $prod['title'] ?? null,
                'impressions' => $impressions,
                'clicks'      => $clicks,
                'ctr'         => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0,
                'conversions' => $conversions,
                'spend'       => $spend,
                'revenue'     => $revenue,
                'acos'        => $revenue > 0 ? round($spend / $revenue * 100, 2) : 0,
            ];
        }
    }

    usleep(300000);
}

// Ordena itens por cliques
usort($itemsData, fn($a,$b) => $b['clicks'] - $a['clicks']);

$ctr  = $totalImpressions > 0 ? round($totalClicks / $totalImpressions * 100, 2) : 0;
$acos = $totalRevenue > 0 ? round($totalSpend / $totalRevenue * 100, 2) : 0;

echo json_encode([
    'ok'     => true,
    'totals' => [
        'impressions' => $totalImpressions,
        'clicks'      => $totalClicks,
        'ctr'         => $ctr,
        'conversions' => $totalConversions,
        'spend'       => round($totalSpend, 2),
        'revenue'     => round($totalRevenue, 2),
        'acos'        => $acos,
    ],
    'daily'  => array_values($dailyData),
    'items'  => array_slice($itemsData, 0, 50),
]);
