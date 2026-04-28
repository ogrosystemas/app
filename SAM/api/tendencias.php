<?php
/**
 * api/tendencias.php
 * GET ?action=trends&category_id=   — top 50 termos em alta do ML (semanais)
 * GET ?action=categorias             — lista de categorias MLB
 * GET ?action=analisar&q=&cat=       — analisa nicho: volume, preços, oportunidade
 * GET ?action=top_sellers&q=         — top vendedores de um nicho
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
$action   = $_GET['action'] ?? 'trends';

// Buscar token da conta ativa
$token = null;
if ($acctId) {
    $acct = db_one(
        "SELECT access_token_enc FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1",
        [$acctId, $tenantId]
    );
    if ($acct) {
        try { $token = crypto_decrypt_token($acct['access_token_enc']); }
        catch(Throwable $e) { $token = null; }
    }
}

if (!$token) {
    echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada ou token inválido']); exit;
}

// ── Helper ML ─────────────────────────────────────────────
function ml(string $path, string $token, array $extra = []): array {
    $r = curl_ml("https://api.mercadolibre.com{$path}", array_merge([
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
    ], $extra));
    return ['code'=>$r['code'], 'data'=>json_decode($r['body'], true) ?: []];
}

// ── Cache simples em banco ────────────────────────────────
function cache_get(string $key): ?array {
    $r = db_one("SELECT value, updated_at FROM tenant_settings WHERE tenant_id='__cache__' AND `key`=?", [$key]);
    if (!$r) return null;
    // Cache válido por 6 horas
    if (strtotime($r['updated_at']) < time() - 21600) return null;
    return json_decode($r['value'], true);
}

function cache_set(string $key, array $data): void {
    try {
        db_query("INSERT INTO tenant_settings (id, tenant_id, `key`, value)
                  VALUES (UUID(), '__cache__', ?, ?)
                  ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=NOW()",
            [$key, json_encode($data)]);
    } catch(Throwable $e) {}
}

// ── GET: Termos em alta ───────────────────────────────────
if ($action === 'trends') {
    $catId = trim($_GET['category_id'] ?? '');
    $cacheKey = 'trends_' . ($catId ?: 'all');

    $cached = cache_get($cacheKey);
    if ($cached) {
        echo json_encode(['ok'=>true,'trends'=>$cached,'cached'=>true]); exit;
    }

    $path = $catId ? "/trends/MLB/{$catId}" : "/trends/MLB";
    $r    = ml($path, $token);

    if ($r['code'] === 200 && is_array($r['data'])) {
        cache_set($cacheKey, $r['data']);
        echo json_encode(['ok'=>true,'trends'=>$r['data'],'cached'=>false]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Erro ao buscar tendências: HTTP '.$r['code']]);
    }
    exit;
}

// ── GET: Categorias MLB ───────────────────────────────────
if ($action === 'categorias') {
    $cached = cache_get('categorias_mlb');
    if ($cached) {
        echo json_encode(['ok'=>true,'categorias'=>$cached]); exit;
    }

    $r = ml('/sites/MLB/categories', $token);
    if ($r['code'] === 200 && !empty($r['data'])) {
        $cats = array_map(fn($c) => ['id'=>$c['id'],'name'=>$c['name']], $r['data']);
        cache_set('categorias_mlb', $cats);
        echo json_encode(['ok'=>true,'categorias'=>$cats]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Erro ao buscar categorias']);
    }
    exit;
}

// ── GET: Analisar nicho ───────────────────────────────────
if ($action === 'analisar') {
    $q   = trim($_GET['q']   ?? '');
    $cat = trim($_GET['cat'] ?? '');
    if (!$q) { echo json_encode(['ok'=>false,'error'=>'Informe um termo para analisar']); exit; }

    $cacheKey = 'nicho_' . md5($q . $cat);
    $cached   = cache_get($cacheKey);
    if ($cached) {
        echo json_encode(array_merge(['ok'=>true,'cached'=>true], $cached)); exit;
    }

    // Busca 1: resultados gerais com o termo
    $qParam  = urlencode($q);
    $catParam = $cat ? "&category={$cat}" : '';
    $r = ml("/sites/MLB/search?q={$qParam}{$catParam}&limit=50&sort=sold_quantity_desc", $token);

    if ($r['code'] !== 200 || empty($r['data']['results'])) {
        echo json_encode(['ok'=>false,'error'=>'Nenhum resultado encontrado para "'.$q.'"']); exit;
    }

    $results = $r['data']['results'];
    $total   = $r['data']['paging']['total'] ?? 0;

    // Calcular métricas
    $precos   = array_filter(array_column($results, 'price'), fn($p) => $p > 0);
    $precoMin = !empty($precos) ? min($precos) : 0;
    $precoMax = !empty($precos) ? max($precos) : 0;
    $precoMed = !empty($precos) ? array_sum($precos) / count($precos) : 0;

    // Vendedores únicos
    $vendedores = array_unique(array_filter(
        array_map(fn($i) => $i['seller']['id'] ?? null, $results)
    ));

    // Top produtos (top 10 por vendas)
    $topProdutos = array_slice(array_map(fn($i) => [
        'id'            => $i['id'],
        'title'         => $i['title'],
        'price'         => $i['price'] ?? 0,
        'sold_quantity' => $i['sold_quantity'] ?? 0,
        'thumbnail'     => $i['thumbnail'] ?? '',
        'permalink'     => $i['permalink'] ?? '',
        'free_shipping' => $i['shipping']['free_shipping'] ?? false,
        'seller_id'     => $i['seller']['id'] ?? null,
    ], $results), 0, 10);

    // Índice de oportunidade (50 = neutro, >70 = boa oportunidade, <30 = saturado)
    // Lógica: quanto menor a concorrência e maior a demanda implícita, melhor
    $oportunidade = 50;
    if ($total < 100)       $oportunidade += 30;
    elseif ($total < 500)   $oportunidade += 15;
    elseif ($total < 2000)  $oportunidade += 5;
    elseif ($total > 10000) $oportunidade -= 20;
    elseif ($total > 5000)  $oportunidade -= 10;

    // Vendedores únicos: menos = melhor oportunidade
    if (count($vendedores) < 5)  $oportunidade += 20;
    elseif (count($vendedores) < 15) $oportunidade += 10;
    elseif (count($vendedores) > 30) $oportunidade -= 10;

    $oportunidade = max(0, min(100, $oportunidade));
    $oportunidadeLabel = $oportunidade >= 70 ? 'Alta' : ($oportunidade >= 40 ? 'Média' : 'Baixa');
    $oportunidadeColor = $oportunidade >= 70 ? '#22c55e' : ($oportunidade >= 40 ? '#f59e0b' : '#ef4444');

    // Frete grátis: % de produtos com frete grátis
    $freteGratis = count(array_filter($results, fn($i) => $i['shipping']['free_shipping'] ?? false));
    $freePct     = count($results) > 0 ? round($freteGratis / count($results) * 100) : 0;

    // Categorias encontradas
    $categorias = array_count_values(array_filter(
        array_map(fn($i) => $i['category_id'] ?? null, $results)
    ));
    arsort($categorias);
    $topCategoria = array_key_first($categorias) ?? '';

    $data = [
        'query'               => $q,
        'total_anuncios'      => $total,
        'vendedores_unicos'   => count($vendedores),
        'preco_min'           => round($precoMin, 2),
        'preco_max'           => round($precoMax, 2),
        'preco_medio'         => round($precoMed, 2),
        'frete_gratis_pct'    => $freePct,
        'oportunidade'        => $oportunidade,
        'oportunidade_label'  => $oportunidadeLabel,
        'oportunidade_color'  => $oportunidadeColor,
        'top_categoria'       => $topCategoria,
        'top_produtos'        => $topProdutos,
    ];

    cache_set($cacheKey, $data);
    echo json_encode(array_merge(['ok'=>true,'cached'=>false], $data));
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
