<?php
/**
 * api/clonagem.php
 * GET  ?action=listar&conta_id=  — lista anúncios ativos da conta
 * POST action=clonar             — clona item de uma conta para outra
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../crypto.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$action   = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Helper: busca token da conta ─────────────────────────
function getToken(string $tenantId, string $contaId): ?string {
    $acct = db_one(
        "SELECT access_token_enc FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1",
        [$contaId, $tenantId]
    );
    if (!$acct) return null;
    return crypto_decrypt_token($acct['access_token_enc']);
}

// ── Helper: chamada ML ────────────────────────────────────
function mlGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code, 'data'=>json_decode($body, true) ?: []];
}

function mlPost(string $url, array $payload, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code'=>$code, 'data'=>json_decode($body, true) ?: []];
}

// ── GET: listar anúncios da conta ────────────────────────
if ($action === 'listar') {
    $contaId = $_GET['conta_id'] ?? '';
    if (!$contaId) { echo json_encode(['ok'=>false,'error'=>'conta_id obrigatório']); exit; }

    $token = getToken($tenantId, $contaId);
    if (!$token) { echo json_encode(['ok'=>false,'error'=>'Conta não encontrada']); exit; }

    // Busca user ID da conta
    $acct = db_one("SELECT meli_user_id FROM meli_accounts WHERE id=? AND tenant_id=?", [$contaId, $tenantId]);
    $userId = $acct['meli_user_id'] ?? '';

    // Busca anúncios ativos (max 50 por chamada)
    $res = mlGet(
        "https://api.mercadolibre.com/users/{$userId}/items/search?status=active&limit=50",
        $token
    );

    if ($res['code'] !== 200) {
        echo json_encode(['ok'=>false,'error'=>'Erro ao buscar anúncios: HTTP '.$res['code']]); exit;
    }

    $itemIds = $res['data']['results'] ?? [];
    if (empty($itemIds)) {
        echo json_encode(['ok'=>true,'anuncios'=>[]]); exit;
    }

    // Busca detalhes em lote (max 20 por vez)
    $anuncios = [];
    foreach (array_chunk($itemIds, 20) as $chunk) {
        $ids = implode(',', $chunk);
        $det = mlGet("https://api.mercadolibre.com/items?ids={$ids}&attributes=id,title,price,available_quantity,thumbnail,status,listing_type_id,category_id,seller_custom_field", $token);
        if ($det['code'] === 200) {
            foreach ($det['data'] as $item) {
                if (!empty($item['body']['id'])) {
                    $b = $item['body'];
                    $anuncios[] = [
                        'id'                 => $b['id'],
                        'title'              => $b['title'] ?? '',
                        'price'              => $b['price'] ?? 0,
                        'available_quantity' => $b['available_quantity'] ?? 0,
                        'thumbnail'          => $b['thumbnail'] ?? '',
                        'status'             => $b['status'] ?? 'active',
                        'listing_type_id'    => $b['listing_type_id'] ?? 'gold_special',
                        'category_id'        => $b['category_id'] ?? '',
                        'sku'                => $b['seller_custom_field'] ?? '',
                    ];
                }
            }
        }
    }

    echo json_encode(['ok'=>true,'anuncios'=>$anuncios,'total'=>count($anuncios)]);
    exit;
}

// ── POST: clonar anúncio ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'clonar') {
    $itemId       = trim($_POST['item_id']       ?? '');
    $contaOrigem  = trim($_POST['conta_origem']  ?? '');
    $contaDestino = trim($_POST['conta_destino'] ?? '');
    $manterPreco  = ($_POST['manter_preco']      ?? '1') === '1';
    $manterEstoque= ($_POST['manter_estoque']    ?? '1') === '1';
    $pausar       = ($_POST['pausar']            ?? '0') === '1';

    if (!$itemId || !$contaOrigem || !$contaDestino) {
        echo json_encode(['ok'=>false,'error'=>'Parâmetros incompletos']); exit;
    }
    if ($contaOrigem === $contaDestino) {
        echo json_encode(['ok'=>false,'error'=>'Origem e destino iguais']); exit;
    }

    $tokenOrigem  = getToken($tenantId, $contaOrigem);
    $tokenDestino = getToken($tenantId, $contaDestino);
    if (!$tokenOrigem || !$tokenDestino) {
        echo json_encode(['ok'=>false,'error'=>'Token de conta não encontrado']); exit;
    }

    // 1. Buscar anúncio completo da origem
    $orig = mlGet("https://api.mercadolibre.com/items/{$itemId}", $tokenOrigem);
    if ($orig['code'] !== 200 || empty($orig['data']['id'])) {
        echo json_encode(['ok'=>false,'error'=>'Anúncio não encontrado na conta origem']); exit;
    }
    $item = $orig['data'];

    // 2. Buscar descrição
    $descRes = mlGet("https://api.mercadolibre.com/items/{$itemId}/description", $tokenOrigem);
    $descricao = $descRes['data']['plain_text'] ?? '';

    // 3. Buscar fotos (reusa os IDs de picture — ML permite reusar entre contas do mesmo app)
    $pictures = array_map(fn($p) => ['source' => $p['url'] ?? $p['secure_url'] ?? ''],
        array_filter($item['pictures'] ?? [], fn($p) => !empty($p['url'] ?? $p['secure_url'] ?? '')));

    // 4. Montar payload para a conta destino
    $payload = [
        'title'            => $item['title'],
        'category_id'      => $item['category_id'],
        'price'            => $manterPreco ? $item['price'] : $item['price'],
        'currency_id'      => $item['currency_id'] ?? 'BRL',
        'available_quantity'=> $manterEstoque ? max(1, (int)($item['available_quantity'] ?? 1)) : 1,
        'buying_mode'      => $item['buying_mode'] ?? 'buy_it_now',
        'listing_type_id'  => $item['listing_type_id'] ?? 'gold_special',
        'condition'        => $item['condition'] ?? 'new',
        'pictures'         => array_slice($pictures, 0, 12),
        'sale_terms'       => array_values(array_filter(
            $item['sale_terms'] ?? [],
            fn($t) => in_array($t['id'] ?? '', ['WARRANTY_TYPE','WARRANTY_TIME','INVOICE_TYPE'])
        )),
    ];

    // Atributos obrigatórios (filtra os que têm valor)
    $attrs = array_values(array_filter(
        $item['attributes'] ?? [],
        fn($a) => !empty($a['value_name']) && !empty($a['id'])
    ));
    if (!empty($attrs)) {
        $payload['attributes'] = array_map(fn($a) => [
            'id'         => $a['id'],
            'value_name' => $a['value_name'],
        ], $attrs);
    }

    // Descrição
    if ($descricao) {
        $payload['description'] = ['plain_text' => $descricao];
    }

    // Iniciar pausado se solicitado
    if ($pausar) {
        $payload['status'] = 'paused';
    }

    // 5. Publicar na conta destino
    $novo = mlPost('https://api.mercadolibre.com/items', $payload, $tokenDestino);

    if (($novo['code'] === 200 || $novo['code'] === 201) && !empty($novo['data']['id'])) {
        $newItemId = $novo['data']['id'];

        // Salvar no banco local como produto da conta destino
        $acctDest = db_one("SELECT id FROM meli_accounts WHERE id=? AND tenant_id=?", [$contaDestino, $tenantId]);
        if ($acctDest) {
            try {
                db_query(
                    "INSERT IGNORE INTO products
                     (id, tenant_id, meli_account_id, meli_item_id, title, price, ml_status, created_at)
                     VALUES (UUID(), ?, ?, ?, ?, ?, 'ACTIVE', NOW())",
                    [$tenantId, $contaDestino, $newItemId, $item['title'], $item['price']]
                );
            } catch(Throwable $e) {}
        }

        audit_log('CLONE_ITEM', 'products', null, null, [
            'from' => $itemId,
            'to'   => $newItemId,
            'conta_origem'  => $contaOrigem,
            'conta_destino' => $contaDestino,
        ]);

        echo json_encode(['ok'=>true,'new_item_id'=>$newItemId]);
    } else {
        $errMsg = $novo['data']['message'] ?? ($novo['data']['error'] ?? 'Erro HTTP '.$novo['code']);
        // Causa mais comum
        if (str_contains($errMsg, 'category')) $errMsg = 'Categoria incompatível com a conta destino';
        if (str_contains($errMsg, 'picture'))   $errMsg = 'Erro ao transferir fotos';
        echo json_encode(['ok'=>false,'error'=>$errMsg]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
