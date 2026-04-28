<?php
/**
 * api/promocoes.php
 * GET  ?action=listar_produtos     — produtos ativos com promoções atuais
 * GET  ?action=verificar&item_id=  — promoções ativas de um item
 * POST action=aplicar              — aplica PRICE_DISCOUNT em lote
 * POST action=remover              — remove promoção de item(s)
 * POST action=criar_campanha       — cria SELLER_CAMPAIGN em lote
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

if (!$acctId) {
    echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML selecionada']); exit;
}

// Buscar conta e token
$acct = db_one(
    "SELECT id, meli_user_id, access_token_enc FROM meli_accounts
     WHERE id=? AND tenant_id=? AND is_active=1",
    [$acctId, $tenantId]
);
if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada']); exit; }

try {
    $token = crypto_decrypt_token($acct['access_token_enc']);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>'Token inválido — reconecte a conta ML']); exit;
}

// ── Helper ML ─────────────────────────────────────────────
function ml_get(string $path, string $token): array {
    $r = curl_ml("https://api.mercadolibre.com{$path}", [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
    ]);
    return ['code'=>$r['code'], 'data'=>json_decode($r['body'], true) ?: []];
}

function ml_post(string $path, array $payload, string $token): array {
    $r = curl_ml("https://api.mercadolibre.com{$path}", [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json",
        ],
    ]);
    return ['code'=>$r['code'], 'data'=>json_decode($r['body'], true) ?: []];
}

function ml_delete(string $path, string $token): array {
    $r = curl_ml("https://api.mercadolibre.com{$path}", [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}"],
    ]);
    return ['code'=>$r['code'], 'data'=>json_decode($r['body'], true) ?: []];
}

// ── GET: Listar produtos com status de promoção ───────────
if ($action === 'listar_produtos') {
    $produtos = db_all(
        "SELECT id, meli_item_id, title, price, cost_price, ml_fee_percent,
                stock_quantity, ml_status, thumbnail
         FROM products
         WHERE tenant_id=? AND meli_account_id=? AND ml_status='active'
           AND meli_item_id IS NOT NULL AND meli_item_id != ''
         ORDER BY title ASC",
        [$tenantId, $acctId]
    );

    // Buscar promoções ativas em lote (até 20 por vez para não sobrecarregar)
    $result = [];
    foreach (array_chunk($produtos, 20) as $chunk) {
        foreach ($chunk as $p) {
            $promo = ml_get(
                "/seller-promotions/items/{$p['meli_item_id']}?app_version=v2",
                $token
            );

            $promocoes = [];
            if ($promo['code'] === 200 && !empty($promo['data'])) {
                foreach ($promo['data'] as $pr) {
                    if (in_array($pr['status'] ?? '', ['started','candidate','pending'])) {
                        $promocoes[] = [
                            'offer_id'   => $pr['offer_id']       ?? '',
                            'type'       => $pr['promotion_type'] ?? '',
                            'price'      => $pr['price']          ?? 0,
                            'original'   => $pr['original_price'] ?? $p['price'],
                            'discount'   => $pr['discount_percentage'] ?? 0,
                            'status'     => $pr['status']         ?? '',
                        ];
                    }
                }
            }

            $result[] = array_merge($p, ['promocoes' => $promocoes]);
        }
    }

    echo json_encode(['ok'=>true,'produtos'=>$result,'total'=>count($result)]);
    exit;
}

// ── GET: Verificar promoções de um item ───────────────────
if ($action === 'verificar') {
    $itemId = trim($_GET['item_id'] ?? '');
    if (!$itemId) { echo json_encode(['ok'=>false,'error'=>'item_id obrigatório']); exit; }

    $r = ml_get("/seller-promotions/items/{$itemId}?app_version=v2", $token);
    echo json_encode(['ok'=>true,'promocoes'=>$r['data'],'http'=>$r['code']]);
    exit;
}

// ── POST: Aplicar PRICE_DISCOUNT em lote ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'aplicar') {
    $ids       = json_decode($_POST['ids']      ?? '[]', true) ?: [];
    $descPct   = (float)($_POST['desconto_pct'] ?? 0);
    $tipo      = $_POST['tipo'] ?? 'percentual'; // percentual | fixo

    if (empty($ids))    { echo json_encode(['ok'=>false,'error'=>'Selecione ao menos um produto']); exit; }
    if ($descPct <= 0)  { echo json_encode(['ok'=>false,'error'=>'Desconto deve ser maior que zero']); exit; }
    if ($descPct > 80)  { echo json_encode(['ok'=>false,'error'=>'ML limita desconto em 80%']); exit; }

    // Buscar produtos selecionados
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $produtos = db_all(
        "SELECT id, meli_item_id, title, price FROM products
         WHERE tenant_id=? AND meli_account_id=? AND id IN ({$placeholders})",
        array_merge([$tenantId, $acctId], $ids)
    );

    $resultados = [];
    foreach ($produtos as $p) {
        if (!$p['meli_item_id']) continue;

        // Calcular preço promocional
        if ($tipo === 'percentual') {
            $dealPrice = round($p['price'] * (1 - $descPct/100), 2);
        } else {
            $dealPrice = round((float)($_POST['valor_fixo'] ?? 0), 2);
        }

        // ML exige mínimo de 5% de desconto real
        $descontoReal = (($p['price'] - $dealPrice) / $p['price']) * 100;
        if ($descontoReal < 5) {
            $resultados[] = [
                'item_id' => $p['meli_item_id'],
                'title'   => $p['title'],
                'ok'      => false,
                'error'   => 'Desconto mínimo é 5% (preço muito próximo do original)',
            ];
            continue;
        }

        // Aplicar PRICE_DISCOUNT
        $r = ml_post(
            "/seller-promotions/items/{$p['meli_item_id']}?app_version=v2",
            [
                'deal_price'     => $dealPrice,
                'promotion_type' => 'PRICE_DISCOUNT',
            ],
            $token
        );

        $ok = in_array($r['code'], [200, 201]);
        $resultados[] = [
            'item_id'    => $p['meli_item_id'],
            'title'      => $p['title'],
            'preco_orig' => $p['price'],
            'preco_promo'=> $dealPrice,
            'desconto'   => round($descontoReal, 1),
            'ok'         => $ok,
            'error'      => $ok ? null : ($r['data']['message'] ?? 'Erro HTTP '.$r['code']),
        ];

        // Pausa para não sobrecarregar a API
        usleep(300000); // 300ms
    }

    $okCount  = count(array_filter($resultados, fn($r) => $r['ok']));
    $errCount = count($resultados) - $okCount;

    echo json_encode([
        'ok'         => $okCount > 0,
        'resultados' => $resultados,
        'ok_count'   => $okCount,
        'err_count'  => $errCount,
    ]);
    exit;
}

// ── POST: Remover promoção ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'remover') {
    $ids  = json_decode($_POST['ids'] ?? '[]', true) ?: [];
    $tipo = $_POST['tipo'] ?? 'PRICE_DISCOUNT'; // tipo de promoção a remover

    if (empty($ids)) { echo json_encode(['ok'=>false,'error'=>'Selecione ao menos um produto']); exit; }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $produtos = db_all(
        "SELECT meli_item_id, title FROM products
         WHERE tenant_id=? AND meli_account_id=? AND id IN ({$placeholders})",
        array_merge([$tenantId, $acctId], $ids)
    );

    $resultados = [];
    foreach ($produtos as $p) {
        if (!$p['meli_item_id']) continue;

        $r = ml_delete(
            "/seller-promotions/items/{$p['meli_item_id']}?app_version=v2&promotion_type={$tipo}",
            $token
        );

        $ok = in_array($r['code'], [200, 204]);
        $resultados[] = [
            'item_id' => $p['meli_item_id'],
            'title'   => $p['title'],
            'ok'      => $ok,
            'error'   => $ok ? null : ($r['data']['message'] ?? 'Erro HTTP '.$r['code']),
        ];
        usleep(200000);
    }

    $okCount = count(array_filter($resultados, fn($r) => $r['ok']));
    echo json_encode([
        'ok'         => $okCount > 0,
        'resultados' => $resultados,
        'ok_count'   => $okCount,
        'err_count'  => count($resultados) - $okCount,
    ]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
