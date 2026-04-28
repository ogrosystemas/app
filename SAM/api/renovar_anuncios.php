<?php
/**
 * api/renovar_anuncios.php
 * Lista e renova anúncios com 120+ dias (fecha o antigo e recria idêntico).
 *
 * GET  ?action=listar   — retorna candidatos a renovação
 * POST action=renovar   — renova um anúncio específico (product_id)
 * POST action=renovar_todos — renova todos os candidatos
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$acctId    = $_SESSION['active_meli_account_id'] ?? null;
$action    = $_GET['action'] ?? $_POST['action'] ?? 'listar';

if (!$acctId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa']);
    exit;
}

$acct = db_one(
    "SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1",
    [$acctId, $tenantId]
);
if (!$acct) {
    echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada']);
    exit;
}

$token = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);

// ── Helper: fechar anúncio no ML ─────────────────────────
function fechar_anuncio(string $itemId, string $token): bool {
    $result = curl_ml("https://api.mercadolibre.com/items/{$itemId}", [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS    => json_encode(['status' => 'closed']),
        CURLOPT_HTTPHEADER    => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
        ],
    ]);
    return $result['code'] === 200;
}

// ── Helper: criar anúncio novo baseado no produto local ──
function criar_anuncio(array $product, string $token): array {
    $pictureIds = json_decode($product['picture_ids'] ?? '[]', true) ?: [];
    $pictures   = array_map(fn($id) => ['id' => $id], $pictureIds);
    $attributes = json_decode($product['ml_attributes'] ?? '[]', true) ?: [];

    // Garante ITEM_CONDITION
    if (!array_filter($attributes, fn($a) => $a['id'] === 'ITEM_CONDITION')) {
        $attributes[] = [
            'id'         => 'ITEM_CONDITION',
            'value_name' => $product['item_condition'] === 'used' ? 'Usado' : 'Novo',
        ];
    }

    $payload = [
        'title'              => $product['title'],
        'category_id'        => $product['category_id'],
        'price'              => (float)$product['price'],
        'currency_id'        => 'BRL',
        'available_quantity' => (int)$product['stock_quantity'],
        'buying_mode'        => 'buy_it_now',
        'listing_type_id'    => $product['listing_type_id'] ?? 'gold_special',
        'item_condition'     => $product['item_condition'] ?? 'new',
        'pictures'           => $pictures,
        'attributes'         => $attributes,
        'shipping'           => ['mode' => 'me2', 'free_shipping' => false],
    ];

    if (!empty($product['description'])) {
        $payload['description'] = ['plain_text' => $product['description']];
    }
    if (!empty($product['catalog_product_id'])) {
        $payload['catalog_product_id'] = $product['catalog_product_id'];
        $payload['catalog_listing']    = true;
    }

    $result = curl_ml('https://api.mercadolibre.com/items', [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $data = json_decode($result['body'], true);

    if (!in_array($result['code'], [200, 201]) || empty($data['id'])) {
        $causes = $data['cause'] ?? [];
        $msgs   = array_map(fn($c) => $c['message'] ?? $c['code'] ?? '', $causes);
        $error  = !empty($msgs) ? implode('; ', $msgs) : ($data['message'] ?? "HTTP {$result['code']}");
        return ['ok' => false, 'error' => $error];
    }

    return [
        'ok'           => true,
        'meli_item_id' => $data['id'],
        'status'       => $data['status'] ?? 'active',
        'permalink'    => $data['permalink'] ?? '',
    ];
}

// ── GET: listar candidatos ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $dias = (int)($_GET['dias'] ?? 120);
    $dias = max(30, min(365, $dias)); // entre 30 e 365
    $catId = trim($_GET['category_id'] ?? '');
    $catSql = $catId ? " AND category_id=?" : "";
    $catP   = $catId ? [$catId] : [];

    $candidates = db_all(
        "SELECT id, meli_item_id, title, price, stock_quantity,
                ml_status, ml_visits, ml_health, category_id, created_at,
                DATEDIFF(NOW(), created_at) as dias_ativo
         FROM products
         WHERE tenant_id=? AND meli_account_id=?
           AND meli_item_id IS NOT NULL
           AND ml_status IN ('ACTIVE','PAUSED')
           AND DATEDIFF(NOW(), created_at) >= ?
           {$catSql}
         ORDER BY dias_ativo DESC",
        array_merge([$tenantId, $acctId, $dias], $catP)
    );

    echo json_encode([
        'ok'         => true,
        'candidates' => $candidates,
        'total'      => count($candidates),
        'dias'       => $dias,
    ]);
    exit;
}

// ── POST: renovar um ou todos ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productIds = [];

    if ($action === 'renovar') {
        $pid = $_POST['product_id'] ?? '';
        if (!$pid) { echo json_encode(['ok'=>false,'error'=>'product_id obrigatório']); exit; }
        $productIds = [$pid];
    } elseif ($action === 'renovar_todos') {
        $dias = (int)($_POST['dias'] ?? 120);
        $rows = db_all(
            "SELECT id FROM products
             WHERE tenant_id=? AND meli_account_id=?
               AND meli_item_id IS NOT NULL
               AND ml_status IN ('ACTIVE','PAUSED')
               AND DATEDIFF(NOW(), created_at) >= ?",
            [$tenantId, $acctId, $dias]
        );
        $productIds = array_column($rows, 'id');
    }

    if (empty($productIds)) {
        echo json_encode(['ok'=>false,'error'=>'Nenhum produto para renovar']);
        exit;
    }

    $results = ['renovados'=>0, 'erros'=>[], 'detalhes'=>[]];

    foreach ($productIds as $pid) {
        $product = db_one(
            "SELECT * FROM products WHERE id=? AND tenant_id=? AND meli_account_id=?",
            [$pid, $tenantId, $acctId]
        );
        if (!$product) {
            $results['erros'][] = "Produto {$pid}: não encontrado";
            continue;
        }

        // Validações mínimas
        if (empty($product['category_id'])) {
            $results['erros'][] = "{$product['title']}: sem categoria — edite o produto primeiro";
            continue;
        }
        if (empty($product['price']) || $product['price'] <= 0) {
            $results['erros'][] = "{$product['title']}: preço inválido";
            continue;
        }

        $oldItemId = $product['meli_item_id'];

        // 1. Fechar anúncio antigo
        $fechado = fechar_anuncio($oldItemId, $token);
        if (!$fechado) {
            $results['erros'][] = "{$product['title']}: falha ao fechar {$oldItemId}";
            continue;
        }

        // 2. Criar anúncio novo
        $novo = criar_anuncio($product, $token);
        if (!$novo['ok']) {
            $results['erros'][] = "{$product['title']}: falha ao recriar — {$novo['error']}";
            // Tenta reabrir o antigo para não ficar sem anúncio
            curl_ml("https://api.mercadolibre.com/items/{$oldItemId}", [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS    => json_encode(['status' => 'active']),
                CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            ]);
            continue;
        }

        // 3. Atualizar banco com novo item_id
        db_update('products', [
            'meli_item_id' => $novo['meli_item_id'],
            'ml_status'    => 'ACTIVE',
            'ml_permalink' => $novo['permalink'],
            'created_at'   => date('Y-m-d H:i:s'), // reseta o contador de 120 dias
            'updated_at'   => date('Y-m-d H:i:s'),
        ], 'id=? AND tenant_id=?', [$pid, $tenantId]);

        audit_log('ANUNCIO_RENOVADO', 'products', $pid, ['meli_item_id'=>$oldItemId], ['meli_item_id'=>$novo['meli_item_id']]);

        $results['renovados']++;
        $results['detalhes'][] = [
            'title'       => $product['title'],
            'old_item_id' => $oldItemId,
            'new_item_id' => $novo['meli_item_id'],
            'permalink'   => $novo['permalink'],
        ];
    }

    echo json_encode(['ok' => true] + $results);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Método não suportado']);
