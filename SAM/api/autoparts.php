<?php
/**
 * api/autoparts.php
 * GET  ?action=list&q=&page=        — lista peças cadastradas
 * GET  ?action=get&product_id=      — detalhe + compatibilidades
 * GET  ?action=brands&tipo=carros   — marcas via BrasilAPI
 * GET  ?action=models&brand=        — modelos de uma marca via BrasilAPI
 * GET  ?action=years&brand=&model=  — anos de um modelo via BrasilAPI
 * GET  ?action=search_vehicle&q=    — busca peças compatíveis com veículo
 * POST action=save                  — salva/edita ficha da peça
 * POST action=save_compat           — adiciona compatibilidade
 * POST action=del_compat&id=        — remove compatibilidade
 * POST action=delete&product_id=    — remove ficha da peça
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_readonly();
auth_require();
session_write_close();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$action   = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Garantir tabelas
try {
    db_query("CREATE TABLE IF NOT EXISTS autoparts (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        product_id VARCHAR(36) NOT NULL, oem_code VARCHAR(80) NULL,
        part_number VARCHAR(80) NULL, brand VARCHAR(80) NULL,
        position VARCHAR(30) NULL, side VARCHAR(20) NULL,
        condition_part ENUM('novo','remontado','original_usado') NOT NULL DEFAULT 'novo',
        notes TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uk_product (tenant_id, product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS autoparts_compatibility (
        id VARCHAR(36) NOT NULL, autopart_id VARCHAR(36) NOT NULL,
        tenant_id VARCHAR(36) NOT NULL, brand VARCHAR(60) NOT NULL,
        model VARCHAR(100) NOT NULL, year_from SMALLINT NOT NULL,
        year_to SMALLINT NOT NULL, engine VARCHAR(60) NULL, fipe_code VARCHAR(20) NULL,
        PRIMARY KEY (id), KEY idx_autopart (autopart_id), KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Throwable $e) {}

function uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
}

// ── Dados FIPE locais (marcas estáticas) ─────────────────
function fipe_marcas(string $tipo): array {
    $file = dirname(__DIR__) . '/assets/fipe_marcas.json';
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return $data[$tipo] ?? [];
}

// ── Modelos via arquivo local ─────────────────────────────
function fipe_modelos(string $tipo, string $brandCode): array {
    $file = dirname(__DIR__) . '/assets/fipe_marcas.json';
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);

    // Buscar nome da marca pelo código dentro do tipo correto
    $marcas    = $data[$tipo] ?? [];
    $brandName = '';
    foreach ($marcas as $m) {
        if ((string)$m['code'] === (string)$brandCode) {
            $brandName = $m['name'];
            break;
        }
    }
    if (!$brandName) return [];

    // Buscar modelos separados por tipo
    $modelos = $data['modelos'][$tipo][$brandName] ?? [];
    return array_map(fn($m) => ['code' => $m, 'name' => $m], $modelos);
}
// ── GET: Marcas (arquivo local) ───────────────────────────
if ($action === 'brands') {
    $tipo   = in_array($_GET['tipo']??'', ['carros','motos','caminhoes']) ? $_GET['tipo'] : 'carros';
    $brands = fipe_marcas($tipo);
    if (!empty($brands)) {
        echo json_encode(['ok'=>true,'brands'=>$brands]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Arquivo de marcas não encontrado no servidor']);
    }
    exit;
}

// ── GET: Modelos via FIPE ─────────────────────────────────
if ($action === 'models') {
    $tipo  = in_array($_GET['tipo']??'', ['carros','motos','caminhoes']) ? $_GET['tipo'] : 'carros';
    $brand = $_GET['brand'] ?? '';
    if (!$brand) { echo json_encode(['ok'=>false,'error'=>'brand obrigatório']); exit; }

    $models = fipe_modelos($tipo, $brand);
    if (!empty($models)) {
        echo json_encode(['ok'=>true,'models'=>$models]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Não foi possível buscar modelos. Tente novamente.']);
    }
    exit;
}



// ── GET: Lista peças cadastradas ──────────────────────────
if ($action === 'list') {
    $q    = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $lim  = 20;
    $off  = ($page-1) * $lim;

    $where  = ['p.tenant_id=?'];
    $params = [$tenantId];

    if ($q) {
        $where[]  = "(p.title LIKE ? OR ap.oem_code LIKE ? OR ap.part_number LIKE ?)";
        $like     = "%{$q}%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $whereStr = implode(' AND ', $where);
    $total    = (int)(db_one(
        "SELECT COUNT(*) as c FROM products p
         LEFT JOIN autoparts ap ON ap.product_id = p.id AND ap.tenant_id = p.tenant_id
         WHERE {$whereStr}", $params)['c'] ?? 0);

    $rows = db_all(
        "SELECT p.id, p.title, p.price, p.cost_price, p.meli_item_id, p.ml_status,
                p.stock_quantity, p.sku,
                ap.id as ap_id, ap.oem_code, ap.part_number, ap.brand as ap_brand,
                ap.position, ap.side, ap.condition_part,
                (SELECT COUNT(*) FROM autoparts_compatibility ac WHERE ac.autopart_id = ap.id) as total_compat
         FROM products p
         LEFT JOIN autoparts ap ON ap.product_id = p.id AND ap.tenant_id = p.tenant_id
         WHERE {$whereStr}
         ORDER BY p.title ASC
         LIMIT {$lim} OFFSET {$off}",
        $params
    );

    echo json_encode(['ok'=>true,'parts'=>$rows,'total'=>$total,'pages'=>ceil($total/$lim)]);
    exit;
}

// ── GET: Detalhe de uma peça ──────────────────────────────
if ($action === 'get') {
    $productId = $_GET['product_id'] ?? '';
    if (!$productId) { echo json_encode(['ok'=>false,'error'=>'product_id obrigatório']); exit; }

    $product = db_one("SELECT id,title,price,cost_price,meli_item_id,ml_status,sku,stock_quantity
                       FROM products WHERE id=? AND tenant_id=?", [$productId, $tenantId]);
    if (!$product) { echo json_encode(['ok'=>false,'error'=>'Produto não encontrado']); exit; }

    $ap = db_one("SELECT * FROM autoparts WHERE product_id=? AND tenant_id=?", [$productId, $tenantId]);

    $compat = [];
    if ($ap) {
        $compat = db_all("SELECT * FROM autoparts_compatibility WHERE autopart_id=? ORDER BY brand,model,year_from",
            [$ap['id']]);
    }

    echo json_encode(['ok'=>true,'product'=>$product,'autopart'=>$ap,'compatibility'=>$compat]);
    exit;
}

// ── GET: Busca por veículo compatível ─────────────────────
if ($action === 'search_vehicle') {
    $brand = trim($_GET['brand'] ?? '');
    $model = trim($_GET['model'] ?? '');
    $year  = (int)($_GET['year'] ?? 0);

    if (!$brand) { echo json_encode(['ok'=>false,'error'=>'Informe a marca']); exit; }

    $where  = ["ac.tenant_id=?", "ac.brand LIKE ?"];
    $params = [$tenantId, "%{$brand}%"];

    if ($model) { $where[] = "ac.model LIKE ?"; $params[] = "%{$model}%"; }
    if ($year)  { $where[] = "ac.year_from <= ? AND ac.year_to >= ?"; $params[] = $year; $params[] = $year; }

    $rows = db_all(
        "SELECT p.id, p.title, p.price, p.meli_item_id, p.ml_status, p.stock_quantity,
                ap.oem_code, ap.part_number, ap.brand as ap_brand, ap.position, ap.side,
                ac.brand as v_brand, ac.model as v_model, ac.year_from, ac.year_to, ac.engine
         FROM autoparts_compatibility ac
         JOIN autoparts ap ON ap.id = ac.autopart_id
         JOIN products p ON p.id = ap.product_id
         WHERE " . implode(' AND ', $where) . "
         ORDER BY p.title ASC LIMIT 50",
        $params
    );

    echo json_encode(['ok'=>true,'parts'=>$rows,'total'=>count($rows)]);
    exit;
}

// ── POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Método inválido']); exit;
}

// Save ficha da peça
if ($action === 'save') {
    $productId   = trim($_POST['product_id'] ?? '');
    $oemCode     = trim($_POST['oem_code']    ?? '');
    $partNumber  = trim($_POST['part_number'] ?? '');
    $brand       = trim($_POST['brand']       ?? '');
    $position    = trim($_POST['position']    ?? '');
    $side        = trim($_POST['side']        ?? '');
    $condition   = in_array($_POST['condition_part']??'', ['novo','remontado','original_usado'])
                   ? $_POST['condition_part'] : 'novo';
    $notes       = trim($_POST['notes']       ?? '');

    if (!$productId) { echo json_encode(['ok'=>false,'error'=>'product_id obrigatório']); exit; }

    $existing = db_one("SELECT id FROM autoparts WHERE product_id=? AND tenant_id=?", [$productId, $tenantId]);

    if ($existing) {
        db_query("UPDATE autoparts SET oem_code=?,part_number=?,brand=?,position=?,side=?,condition_part=?,notes=?,updated_at=NOW()
                  WHERE id=? AND tenant_id=?",
            [$oemCode,$partNumber,$brand,$position,$side,$condition,$notes,$existing['id'],$tenantId]);
        echo json_encode(['ok'=>true,'id'=>$existing['id']]);
    } else {
        $id = uuid();
        db_query("INSERT INTO autoparts (id,tenant_id,product_id,oem_code,part_number,brand,position,side,condition_part,notes)
                  VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$id,$tenantId,$productId,$oemCode,$partNumber,$brand,$position,$side,$condition,$notes]);
        echo json_encode(['ok'=>true,'id'=>$id]);
    }
    exit;
}

// Save compatibilidade
if ($action === 'save_compat') {
    $autopartId = trim($_POST['autopart_id'] ?? '');
    $brand      = trim($_POST['brand']       ?? '');
    $model      = trim($_POST['model']       ?? '');
    $yearFrom   = (int)($_POST['year_from']  ?? 0);
    $yearTo     = (int)($_POST['year_to']    ?? 0);
    $engine     = trim($_POST['engine']      ?? '');
    $fipeCode   = trim($_POST['fipe_code']   ?? '');

    if (!$autopartId || !$brand || !$model || !$yearFrom || !$yearTo) {
        echo json_encode(['ok'=>false,'error'=>'Campos obrigatórios: marca, modelo, ano de/até']); exit;
    }
    if ($yearTo < $yearFrom) { echo json_encode(['ok'=>false,'error'=>'Ano final menor que inicial']); exit; }

    // Verifica se autopart pertence ao tenant
    $ap = db_one("SELECT id FROM autoparts WHERE id=? AND tenant_id=?", [$autopartId, $tenantId]);
    if (!$ap) { echo json_encode(['ok'=>false,'error'=>'Ficha não encontrada']); exit; }

    $id = uuid();
    db_query("INSERT INTO autoparts_compatibility (id,autopart_id,tenant_id,brand,model,year_from,year_to,engine,fipe_code)
              VALUES (?,?,?,?,?,?,?,?,?)",
        [$id,$autopartId,$tenantId,$brand,$model,$yearFrom,$yearTo,$engine,$fipeCode]);

    echo json_encode(['ok'=>true,'id'=>$id]);
    exit;
}

// Remover compatibilidade
if ($action === 'del_compat') {
    $id = trim($_POST['id'] ?? '');
    db_query("DELETE FROM autoparts_compatibility WHERE id=? AND tenant_id=?", [$id, $tenantId]);
    echo json_encode(['ok'=>true]);
    exit;
}

// Remover ficha
if ($action === 'delete') {
    $productId = trim($_POST['product_id'] ?? '');
    $ap = db_one("SELECT id FROM autoparts WHERE product_id=? AND tenant_id=?", [$productId, $tenantId]);
    if ($ap) {
        db_query("DELETE FROM autoparts_compatibility WHERE autopart_id=?", [$ap['id']]);
        db_query("DELETE FROM autoparts WHERE id=?", [$ap['id']]);
    }
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
