<?php
/**
 * api/run_price_rules.php
 * Executa as regras de corrida de preços
 * Pode ser chamado via cron ou incluído por corrida_precos.php
 */

if (!function_exists('run_price_rules')) {

function run_price_rules(string $tenantId = '', string $acctId = ''): array {
    $updated = 0; $errors = [];

    // Busca regras ativas
    $where  = "pr.is_active=1";
    $params = [];
    if ($tenantId) { $where .= " AND pr.tenant_id=?"; $params[] = $tenantId; }
    if ($acctId)   { $where .= " AND pr.meli_account_id=?"; $params[] = $acctId; }

    $rules = db_all(
        "SELECT pr.*, p.meli_item_id, p.price as current_price, p.catalog_product_id,
                ma.access_token_enc, ma.meli_user_id
         FROM price_rules pr
         JOIN products p ON p.id = pr.product_id
         JOIN meli_accounts ma ON ma.id = pr.meli_account_id AND ma.is_active=1
         WHERE {$where}",
        $params
    );

    foreach ($rules as $rule) {
        try {
            $token   = crypto_decrypt_token($rule['access_token_enc']);
            $itemId  = $rule['meli_item_id'];
            $catId   = $rule['catalog_product_id'];

            if (!$itemId) continue;

            // Busca preço do concorrente via catálogo ML
            $competitorPrice = null;

            if ($catId) {
                // Produto de catálogo — busca ranking de preços
                $res = curl_ml(
                    "https://api.mercadolibre.com/products/{$catId}/items?limit=5",
                    [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 10]
                );
                if ($res['code'] === 200) {
                    $data   = json_decode($res['body'], true);
                    $prices = [];
                    foreach ($data['results'] ?? [] as $item) {
                        if ($item['id'] !== $itemId && isset($item['price'])) {
                            $prices[] = (float)$item['price'];
                        }
                    }
                    if (!empty($prices)) $competitorPrice = min($prices);
                }
            }

            // Fallback: busca por categoria/título
            if (!$competitorPrice) {
                $prodInfo = db_one("SELECT title, category_id FROM products WHERE meli_item_id=? AND tenant_id=?",
                    [$itemId, $rule['tenant_id']]);
                if ($prodInfo && $prodInfo['category_id']) {
                    $res = curl_ml(
                        "https://api.mercadolibre.com/sites/MLB/search?category={$prodInfo['category_id']}&limit=5&sort=price_asc",
                        [CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"], CURLOPT_TIMEOUT => 10]
                    );
                    if ($res['code'] === 200) {
                        $data = json_decode($res['body'], true);
                        foreach ($data['results'] ?? [] as $item) {
                            if ($item['id'] !== $itemId && isset($item['price'])) {
                                $competitorPrice = (float)$item['price'];
                                break;
                            }
                        }
                    }
                }
            }

            if (!$competitorPrice) continue;

            // Calcula novo preço baseado na regra
            $newPrice = match($rule['rule_type']) {
                'beat_lowest'     => $competitorPrice - 0.01,
                'match_lowest'    => $competitorPrice,
                'beat_by_value'   => $competitorPrice - (float)$rule['value'],
                'beat_by_percent' => $competitorPrice * (1 - (float)$rule['value'] / 100),
                default           => $competitorPrice,
            };

            // Aplica limites
            if ($rule['min_price'] > 0) $newPrice = max($newPrice, (float)$rule['min_price']);
            if ($rule['max_price'] > 0) $newPrice = min($newPrice, (float)$rule['max_price']);
            $newPrice = round($newPrice, 2);

            // Não atualiza se o preço não mudou significativamente (< R$ 0.01)
            if (abs($newPrice - (float)$rule['current_price']) < 0.01) continue;

            // Atualiza preço no ML
            $res = curl_ml("https://api.mercadolibre.com/items/{$itemId}", [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS    => json_encode(['price' => $newPrice]),
                CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
                CURLOPT_TIMEOUT       => 10,
            ]);

            if ($res['code'] === 200) {
                db_update('products', ['price' => $newPrice], 'meli_item_id=? AND tenant_id=?',
                    [$itemId, $rule['tenant_id']]);
                db_update('price_rules',
                    ['last_run' => date('Y-m-d H:i:s'), 'last_price_set' => $newPrice],
                    'id=?', [$rule['id']]);
                audit_log('PRICE_RULE_APPLIED', 'price_rules', $rule['id'],
                    ['price' => $rule['current_price']],
                    ['price' => $newPrice, 'competitor' => $competitorPrice]);
                $updated++;
            } else {
                $errors[] = "Item {$itemId}: HTTP {$res['code']}";
            }

            usleep(500000); // 500ms entre atualizações
        } catch (Throwable $e) {
            $errors[] = "Regra {$rule['id']}: " . $e->getMessage();
        }
    }

    return ['updated' => $updated, 'errors' => $errors];
}

} // end function_exists

// Execução via CLI (cron)
if (php_sapi_name() === 'cli') {
    require_once dirname(__DIR__) . '/config.php';
    require_once dirname(__DIR__) . '/db.php';
    require_once dirname(__DIR__) . '/crypto.php';

    echo '[' . date('Y-m-d H:i:s') . '] Iniciando corrida de preços...' . PHP_EOL;
    $result = run_price_rules();
    echo '[' . date('Y-m-d H:i:s') . "] Concluído: {$result['updated']} atualizado(s), " . count($result['errors']) . " erro(s)" . PHP_EOL;
    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $e) echo "  ERRO: {$e}" . PHP_EOL;
    }
}
