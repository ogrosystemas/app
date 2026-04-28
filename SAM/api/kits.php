<?php
/**
 * api/kits.php
 * GET  ?action=list              — lista kits do tenant
 * GET  ?action=get&id=           — detalhe de um kit com itens
 * POST action=save               — cria/edita kit
 * POST action=delete             — remove kit
 * POST action=publicar           — publica kit como anúncio no ML
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
$action   = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Garante tabelas existem
try {
    db_query("CREATE TABLE IF NOT EXISTS kits (
        id              VARCHAR(36)   NOT NULL,
        tenant_id       VARCHAR(36)   NOT NULL,
        meli_account_id VARCHAR(36)   NULL,
        title           VARCHAR(255)  NOT NULL,
        description     TEXT          NULL,
        price           DECIMAL(12,2) NOT NULL DEFAULT 0,
        cost_price      DECIMAL(12,2) NOT NULL DEFAULT 0,
        discount_pct    DECIMAL(5,2)  NOT NULL DEFAULT 0,
        ml_fee_percent  DECIMAL(5,2)  NOT NULL DEFAULT 14,
        meli_item_id    VARCHAR(30)   NULL,
        ml_status       VARCHAR(20)   NULL DEFAULT 'draft',
        sku             VARCHAR(80)   NULL,
        status          ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
        created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS kit_items (
        id          VARCHAR(36) NOT NULL,
        kit_id      VARCHAR(36) NOT NULL,
        product_id  VARCHAR(36) NOT NULL,
        quantity    INT         NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        KEY idx_kit (kit_id),
        KEY idx_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Throwable $e) {}

function uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
}

// ── Calcular estoque disponível do kit ───────────────────
function calcKitStock(string $kitId): int {
    $items = db_all(
        "SELECT ki.quantity, p.stock_quantity
         FROM kit_items ki
         JOIN products p ON p.id = ki.product_id
         WHERE ki.kit_id = ?",
        [$kitId]
    );
    if (empty($items)) return 0;
    $min = PHP_INT_MAX;
    foreach ($items as $i) {
        $possible = $i['quantity'] > 0 ? floor((int)$i['stock_quantity'] / (int)$i['quantity']) : 0;
        $min = min($min, $possible);
    }
    return $min === PHP_INT_MAX ? 0 : (int)$min;
}

// ── GET: listar ──────────────────────────────────────────
if ($action === 'list') {
    $kits = db_all(
        "SELECT k.*,
                (SELECT COUNT(*) FROM kit_items WHERE kit_id = k.id) as total_itens
         FROM kits k
         WHERE k.tenant_id = ?
         ORDER BY k.created_at DESC",
        [$tenantId]
    );

    foreach ($kits as &$k) {
        $k['estoque_disponivel'] = calcKitStock($k['id']);
        // Calcular margem
        $preco = (float)$k['price'];
        $custo = (float)$k['cost_price'];
        $fee   = $preco * ((float)$k['ml_fee_percent'] / 100);
        $lucro = $preco - $custo - $fee;
        $k['margem_pct'] = $preco > 0 ? round($lucro / $preco * 100, 1) : 0;
        $k['lucro']      = round($lucro, 2);
    }

    echo json_encode(['ok'=>true,'kits'=>$kits]);
    exit;
}

// ── GET: detalhe ─────────────────────────────────────────
if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) { echo json_encode(['ok'=>false,'error'=>'id obrigatório']); exit; }

    $kit = db_one("SELECT * FROM kits WHERE id=? AND tenant_id=?", [$id, $tenantId]);
    if (!$kit) { echo json_encode(['ok'=>false,'error'=>'Kit não encontrado']); exit; }

    $itens = db_all(
        "SELECT ki.id, ki.quantity, p.id as product_id, p.title, p.price,
                p.cost_price, p.stock_quantity, p.meli_item_id, p.ml_status, p.sku
         FROM kit_items ki
         JOIN products p ON p.id = ki.product_id
         WHERE ki.kit_id = ?",
        [$id]
    );

    $kit['itens']             = $itens;
    $kit['estoque_disponivel'] = calcKitStock($id);

    echo json_encode(['ok'=>true,'kit'=>$kit]);
    exit;
}

// ── POST: salvar ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $id          = trim($_POST['id'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountPct = (float)($_POST['discount_pct'] ?? 0);
    $feePct      = (float)($_POST['ml_fee_percent'] ?? 14);
    $sku         = trim($_POST['sku'] ?? '');
    $itensRaw    = json_decode($_POST['itens'] ?? '[]', true);

    if (!$title)         { echo json_encode(['ok'=>false,'error'=>'Título obrigatório']); exit; }
    if (empty($itensRaw)) { echo json_encode(['ok'=>false,'error'=>'Adicione pelo menos 1 produto']); exit; }

    // Buscar produtos e calcular preço/custo automaticamente
    $totalPreco = 0;
    $totalCusto = 0;
    $itensValidos = [];

    foreach ($itensRaw as $item) {
        $pid = $item['product_id'] ?? '';
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $prod = db_one("SELECT id,title,price,cost_price FROM products WHERE id=? AND tenant_id=?", [$pid, $tenantId]);
        if (!$prod) continue;
        $totalPreco += (float)$prod['price'] * $qty;
        $totalCusto += (float)$prod['cost_price'] * $qty;
        $itensValidos[] = ['product_id'=>$pid,'quantity'=>$qty];
    }

    if (empty($itensValidos)) { echo json_encode(['ok'=>false,'error'=>'Nenhum produto válido']); exit; }

    // Preço com desconto
    $precoFinal = isset($_POST['price']) && (float)$_POST['price'] > 0
        ? (float)$_POST['price']
        : round($totalPreco * (1 - $discountPct/100), 2);

    if ($id) {
        // Editar
        db_query(
            "UPDATE kits SET title=?,description=?,price=?,cost_price=?,discount_pct=?,
             ml_fee_percent=?,sku=?,updated_at=NOW()
             WHERE id=? AND tenant_id=?",
            [$title,$description,$precoFinal,$totalCusto,$discountPct,$feePct,$sku,$id,$tenantId]
        );
        db_query("DELETE FROM kit_items WHERE kit_id=?", [$id]);
    } else {
        // Criar
        $id = uuid();
        db_query(
            "INSERT INTO kits (id,tenant_id,meli_account_id,title,description,price,cost_price,discount_pct,ml_fee_percent,sku)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$id,$tenantId,$acctId,$title,$description,$precoFinal,$totalCusto,$discountPct,$feePct,$sku]
        );
    }

    foreach ($itensValidos as $item) {
        db_query(
            "INSERT INTO kit_items (id,kit_id,product_id,quantity) VALUES (?,?,?,?)",
            [uuid(), $id, $item['product_id'], $item['quantity']]
        );
    }

    echo json_encode(['ok'=>true,'id'=>$id,'price'=>$precoFinal,'cost'=>$totalCusto]);
    exit;
}

// ── POST: deletar ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { echo json_encode(['ok'=>false,'error'=>'id obrigatório']); exit; }
    db_query("DELETE FROM kit_items WHERE kit_id=?", [$id]);
    db_query("DELETE FROM kits WHERE id=? AND tenant_id=?", [$id, $tenantId]);
    echo json_encode(['ok'=>true]);
    exit;
}

// ── POST: publicar no ML ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'publicar') {
    $id      = trim($_POST['id'] ?? '');
    $catId   = trim($_POST['category_id'] ?? '');
    $pausar  = ($_POST['pausar'] ?? '0') === '1';

    if (!$id || !$catId) { echo json_encode(['ok'=>false,'error'=>'Kit e categoria obrigatórios']); exit; }
    if (!$acctId)         { echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML selecionada']); exit; }

    $kit = db_one("SELECT * FROM kits WHERE id=? AND tenant_id=?", [$id, $tenantId]);
    if (!$kit) { echo json_encode(['ok'=>false,'error'=>'Kit não encontrado']); exit; }

    $acct = db_one("SELECT access_token_enc FROM meli_accounts WHERE id=? AND tenant_id=?", [$acctId, $tenantId]);
    if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta ML não encontrada']); exit; }
    $token = (function($enc){ try { return crypto_decrypt_token($enc); } catch(\Throwable $e) { return null; } })($acct['access_token_enc']);

    // Buscar fotos do primeiro produto do kit
    $firstProduct = db_one(
        "SELECT p.meli_item_id FROM kit_items ki
         JOIN products p ON p.id = ki.product_id
         WHERE ki.kit_id = ? LIMIT 1",
        [$id]
    );

    $pictures = [];
    if ($firstProduct && $firstProduct['meli_item_id']) {
        $ch = curl_init("https://api.mercadolibre.com/items/{$firstProduct['meli_item_id']}?attributes=pictures");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>["Authorization: Bearer {$token}"],CURLOPT_TIMEOUT=>8,CURLOPT_SSL_VERIFYPEER=>false]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $pictures = array_map(fn($p) => ['source'=>$p['url']??$p['secure_url']??''],
            array_slice($res['pictures'] ?? [], 0, 6));
    }

    $payload = [
        'title'             => $kit['title'],
        'category_id'       => $catId,
        'price'             => (float)$kit['price'],
        'currency_id'       => 'BRL',
        'available_quantity'=> calcKitStock($id) ?: 1,
        'buying_mode'       => 'buy_it_now',
        'listing_type_id'   => 'gold_special',
        'condition'         => 'new',
        'description'       => ['plain_text' => $kit['description'] ?: $kit['title']],
        'pictures'          => $pictures,
        'status'            => $pausar ? 'paused' : 'active',
    ];

    $ch = curl_init('https://api.mercadolibre.com/items');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($body, true);

    if (($code === 200 || $code === 201) && !empty($data['id'])) {
        db_query("UPDATE kits SET meli_item_id=?,ml_status=? WHERE id=?",
            [$data['id'], $pausar?'paused':'active', $id]);
        echo json_encode(['ok'=>true,'meli_item_id'=>$data['id']]);
    } else {
        $err = $data['message'] ?? ($data['error'] ?? "Erro ML HTTP {$code}");
        echo json_encode(['ok'=>false,'error'=>$err]);
    }
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
