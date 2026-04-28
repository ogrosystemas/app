<?php
/**
 * api/publish_item.php
 * Publica ou atualiza um anúncio no Mercado Livre.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/crypto.php';
require_once dirname(__DIR__) . '/auth.php';

session_start_secure();
auth_require();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$user      = auth_user();
$tenantId  = $user['tenant_id'];
$accountId = $_SESSION['active_meli_account_id'] ?? null;

if (!$accountId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa.']);
    exit;
}

$account = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$accountId, $tenantId]);

    // Descriptografa token ML se necessário
    if (TOKEN_KEY) {
        try { $account['access_token_enc'] = crypto_decrypt_token($account['access_token_enc']); }
        catch (Throwable $e) { /* não criptografado ainda */ }
    }

    if (!$account) {
    echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada.']);
    exit;
}

$productId = $_POST['product_id'] ?? '';
$product   = db_one("SELECT * FROM products WHERE id=? AND tenant_id=?", [$productId, $tenantId]);
if (!$product) {
    echo json_encode(['ok'=>false,'error'=>'Produto não encontrado.']);
    exit;
}

// Valida campos obrigatórios para publicação no ML
$errors = [];
if (empty($product['title']))       $errors[] = 'Título obrigatório';
if (empty($product['price']))       $errors[] = 'Preço obrigatório';
if (empty($product['category_id'])) $errors[] = 'Categoria do ML obrigatória — busque e selecione uma categoria no modal de edição';
if (!empty($errors)) {
    echo json_encode(['ok'=>false,'error'=>implode('. ', $errors)]);
    exit;
}

// Monta pictures
$pictureIds = json_decode($product['picture_ids'] ?? '[]', true) ?: [];
$pictures   = array_map(fn($id) => ['id' => $id], $pictureIds);

// Monta atributos
$attributes = json_decode($product['ml_attributes'] ?? '[]', true) ?: [];

// Garante ITEM_CONDITION
$hasCondition = array_filter($attributes, fn($a) => $a['id'] === 'ITEM_CONDITION');
if (!$hasCondition) {
    $attributes[] = [
        'id'         => 'ITEM_CONDITION',
        'value_name' => $product['item_condition'] === 'used' ? 'Usado' : 'Novo',
    ];
}

// Monta payload base
$payload = [
    'title'              => $product['title'],
    'category_id'        => $product['category_id'] ?? 'MLB1648',
    'price'              => (float)$product['price'],
    'currency_id'        => 'BRL',
    'available_quantity' => (int)$product['stock_quantity'],
    'buying_mode'        => 'buy_it_now',
    'listing_type_id'    => $product['listing_type_id'] ?? 'gold_special',
    'item_condition'          => $product['item_condition'] ?? 'new',
    'pictures'           => $pictures,
    'attributes'         => $attributes,
    'shipping'           => ['mode' => 'me2', 'free_shipping' => false],
];

// Adiciona descrição se tiver
if (!empty($product['description'])) {
    $payload['description'] = ['plain_text' => $product['description']];
}

// Catalog listing se tiver catalog_product_id
if (!empty($product['catalog_product_id'])) {
    $payload['catalog_product_id'] = $product['catalog_product_id'];
    $payload['catalog_listing']    = true;
}

$isUpdate = !empty($product['meli_item_id']);

if ($isUpdate) {
    // PUT — atualiza item existente
    $url    = "https://api.mercadolibre.com/items/{$product['meli_item_id']}";
    $method = 'PUT';
    // No update não envia category_id, buying_mode, listing_type_id
    unset($payload['category_id'], $payload['buying_mode'], $payload['listing_type_id']);
} else {
    // POST — cria novo item
    $url    = 'https://api.mercadolibre.com/items';
    $method = 'POST';
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $account['access_token_enc'],
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['ok'=>false,'error'=>'Erro de conexão: '.$err]);
    exit;
}

$data = json_decode($res, true);

if (!in_array($httpCode, [200, 201])) {
    // Formata erros do ML de forma legível
    $causes = $data['cause'] ?? [];
    $msgs   = array_map(fn($c) => $c['message'] ?? $c['code'] ?? '', $causes);
    $error  = !empty($msgs) ? implode('; ', $msgs) : ($data['message'] ?? 'Erro ao publicar no ML');
    echo json_encode(['ok'=>false,'error'=>$error,'raw'=>$data]);
    exit;
}

// Atualiza produto com meli_item_id
$meliItemId = $data['id'] ?? $product['meli_item_id'];
db_update('products', [
    'meli_item_id' => $meliItemId,
    'ml_status'    => strtoupper($data['status'] ?? 'ACTIVE'),
    'ml_permalink' => $data['permalink'] ?? null,
], 'id=?', [$productId]);

echo json_encode([
    'ok'          => true,
    'meli_item_id'=> $meliItemId,
    'status'      => $data['status'] ?? 'active',
    'permalink'   => $data['permalink'] ?? '',
    'action'      => $isUpdate ? 'updated' : 'created',
]);
