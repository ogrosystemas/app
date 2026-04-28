<?php
/**
 * api/concorrentes.php
 * GET  ?action=buscar&q=&sort=&limit=   — busca anúncios por keyword
 * GET  ?action=vendedor&nickname=       — analisa um vendedor pelo nickname
 * POST action=monitorar                 — salva vendedor para monitoramento
 * POST action=remover                   — remove vendedor do monitoramento
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
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$action   = $_GET['action'] ?? $_POST['action'] ?? '';

// Token da conta ativa (necessário para endpoints autenticados)
$token = null;
if ($acctId) {
    $acct = db_one("SELECT access_token_enc FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId]);
    if ($acct) $token = crypto_decrypt_token($acct['access_token_enc']);
}

function mlCall(string $url, ?string $token = null): array {
    $headers = ['Accept: application/json'];
    if ($token) $headers[] = "Authorization: Bearer {$token}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'SAM-ERP/1.0',
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true) ?: []];
}

// ── GET: Buscar por palavra-chave ────────────────────────
if ($action === 'buscar') {
    $q     = trim($_GET['q']     ?? '');
    $sort  = in_array($_GET['sort']??'', ['relevance','price_asc','price_desc']) ? $_GET['sort'] : 'relevance';
    $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));

    if (!$q) { echo json_encode(['ok'=>false,'error'=>'Palavra-chave obrigatória']); exit; }

    // Parâmetros de ordenação para a API ML
    $sortMap = [
        'relevance'  => '',
        'price_asc'  => '&sort=price_asc',
        'price_desc' => '&sort=price_desc',
    ];
    $sortParam = $sortMap[$sort] ?? '';

    // Busca principal — API pública, sem necessidade de token
    $searchUrl = "https://api.mercadolibre.com/sites/MLB/search?q=" . urlencode($q) . "&limit={$limit}{$sortParam}";
    $res = mlCall($searchUrl, $token);

    if ($res['code'] !== 200) {
        echo json_encode(['ok'=>false,'error'=>'Erro na API ML: HTTP '.$res['code']]); exit;
    }

    $results = $res['data']['results'] ?? [];
    if (empty($results)) {
        echo json_encode(['ok'=>true,'items'=>[],'total'=>0]); exit;
    }

    // Enriquecer com dados de vendas e seller
    $items = [];
    foreach ($results as $item) {
        $items[] = [
            'id'               => $item['id'],
            'title'            => $item['title'] ?? '',
            'price'            => $item['price'] ?? 0,
            'currency_id'      => $item['currency_id'] ?? 'BRL',
            'sold_quantity'    => $item['sold_quantity'] ?? 0,
            'available_quantity'=> $item['available_quantity'] ?? 0,
            'listing_type_id'  => $item['listing_type_id'] ?? '',
            'condition'        => $item['condition'] ?? 'new',
            'thumbnail'        => $item['thumbnail'] ?? '',
            'permalink'        => $item['permalink'] ?? '#',
            'shipping'         => $item['shipping'] ?? [],
            'seller'           => [
                'id'       => $item['seller']['id'] ?? null,
                'nickname' => $item['seller']['nickname'] ?? '',
                'seller_reputation' => $item['seller']['seller_reputation'] ?? [],
            ],
            'reviews'          => $item['reviews'] ?? [],
            'attributes'       => array_slice($item['attributes'] ?? [], 0, 5),
            'catalog_product_id'=> $item['catalog_product_id'] ?? null,
        ];
    }

    // Estatísticas agregadas
    $total = $res['data']['paging']['total'] ?? count($items);

    echo json_encode([
        'ok'    => true,
        'items' => $items,
        'total' => $total,
        'query' => $q,
    ]);
    exit;
}

// ── GET: Analisar vendedor por nickname ──────────────────
if ($action === 'vendedor') {
    $nickname = trim($_GET['nickname'] ?? '');
    if (!$nickname) { echo json_encode(['ok'=>false,'error'=>'nickname obrigatório']); exit; }

    // Busca vendedor pelo nickname — API pública
    $siteRes = mlCall("https://api.mercadolibre.com/sites/MLB/search?nickname=" . urlencode($nickname) . "&limit=1", $token);
    $sellerId = null;

    // Tenta extrair seller_id dos resultados
    if (!empty($siteRes['data']['results'][0]['seller']['id'])) {
        $sellerId = $siteRes['data']['results'][0]['seller']['id'];
    } else {
        // Tenta direto pelo endpoint de usuários
        $userRes = mlCall("https://api.mercadolibre.com/users/search?nickname=" . urlencode($nickname));
        if (!empty($userRes['data']['results'][0]['id'])) {
            $sellerId = $userRes['data']['results'][0]['id'];
        }
    }

    if (!$sellerId) {
        // Última tentativa: busca com nome exato
        $sr2 = mlCall("https://api.mercadolibre.com/sites/MLB/search?q=" . urlencode($nickname) . "&limit=5");
        foreach ($sr2['data']['results'] ?? [] as $r) {
            if (strtolower($r['seller']['nickname'] ?? '') === strtolower($nickname)) {
                $sellerId = $r['seller']['id'];
                break;
            }
        }
    }

    if (!$sellerId) {
        echo json_encode(['ok'=>false,'error'=>'Vendedor não encontrado. Verifique o nickname exato.']); exit;
    }

    // Busca perfil do vendedor
    $vendorRes = mlCall("https://api.mercadolibre.com/users/{$sellerId}");
    if ($vendorRes['code'] !== 200) {
        echo json_encode(['ok'=>false,'error'=>'Erro ao buscar perfil do vendedor']); exit;
    }
    $vendor = $vendorRes['data'];

    // Busca anúncios ativos do vendedor
    $itemsRes = mlCall("https://api.mercadolibre.com/sites/MLB/search?seller_id={$sellerId}&status=active&limit=50&sort=sold_quantity_desc", $token);
    $rawItems = $itemsRes['data']['results'] ?? [];

    $items = array_map(fn($i) => [
        'id'             => $i['id'],
        'title'          => $i['title'] ?? '',
        'price'          => $i['price'] ?? 0,
        'sold_quantity'  => $i['sold_quantity'] ?? 0,
        'available_quantity'=> $i['available_quantity'] ?? 0,
        'thumbnail'      => $i['thumbnail'] ?? '',
        'permalink'      => $i['permalink'] ?? '#',
        'shipping'       => $i['shipping'] ?? [],
        'listing_type_id'=> $i['listing_type_id'] ?? '',
    ], $rawItems);

    echo json_encode([
        'ok'     => true,
        'vendor' => $vendor,
        'items'  => $items,
        'total'  => count($items),
    ]);
    exit;
}

// ── POST: Salvar monitoramento ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'monitorar') {
        $nickname  = trim($_POST['nickname']  ?? '');
        $sellerId  = trim($_POST['seller_id'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        if (!$nickname) { echo json_encode(['ok'=>false,'error'=>'nickname obrigatório']); exit; }

        // Verifica se já existe
        $existe = db_one("SELECT id FROM competitor_monitors WHERE tenant_id=? AND nickname=?", [$tenantId, $nickname]);
        if ($existe) {
            echo json_encode(['ok'=>true,'message'=>'Já monitorado']); exit;
        }

        db_query(
            "INSERT INTO competitor_monitors (id,tenant_id,nickname,meli_user_id,categoria) VALUES (UUID(),?,?,?,?)",
            [$tenantId, $nickname, $sellerId ?: null, $categoria ?: null]
        );
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'remover') {
        $id = trim($_POST['id'] ?? '');
        db_query("DELETE FROM competitor_monitors WHERE id=? AND tenant_id=?", [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
