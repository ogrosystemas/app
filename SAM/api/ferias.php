<?php
/**
 * api/ferias.php
 * GET  ?action=status          — retorna status atual das férias
 * POST action=ativar|desativar — pausa/reativa todos os anúncios ativos
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

if (!$acctId) { echo json_encode(['ok'=>false,'error'=>'Nenhuma conta ML ativa']); exit; }

$acct = db_one("SELECT * FROM meli_accounts WHERE id=? AND tenant_id=? AND is_active=1", [$acctId, $tenantId]);
if (!$acct) { echo json_encode(['ok'=>false,'error'=>'Conta não encontrada']); exit; }

$token = crypto_decrypt_token($acct['access_token_enc']);

// ── GET: status ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $feriasAtiva = tenant_get_val($tenantId, $acctId, 'ferias_ativa') === '1';
    $pausadosEm  = tenant_get_val($tenantId, $acctId, 'ferias_pausado_em');
    $totalAtivos = (int)(db_one(
        "SELECT COUNT(*) as cnt FROM products WHERE tenant_id=? AND meli_account_id=? AND ml_status='ACTIVE'",
        [$tenantId, $acctId]
    )['cnt'] ?? 0);
    $totalPausados = (int)(db_one(
        "SELECT COUNT(*) as cnt FROM products WHERE tenant_id=? AND meli_account_id=? AND ml_status='PAUSED'",
        [$tenantId, $acctId]
    )['cnt'] ?? 0);

    echo json_encode([
        'ok'            => true,
        'ferias_ativa'  => $feriasAtiva,
        'pausado_em'    => $pausadosEm,
        'total_ativos'  => $totalAtivos,
        'total_pausados'=> $totalPausados,
    ]);
    exit;
}

// ── POST ─────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

function tenant_get_val(string $tenantId, string $acctId, string $key): string {
    $row = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?",
        [$tenantId, "{$key}_{$acctId}"]
    );
    return $row['value'] ?? '';
}
function tenant_set_val(string $tenantId, string $acctId, string $key, string $value): void {
    tenant_setting_set($tenantId, "{$key}_{$acctId}", $value);
}

if ($action === 'ativar') {
    // Busca todos os anúncios ativos
    $produtos = db_all(
        "SELECT id, meli_item_id, title FROM products
         WHERE tenant_id=? AND meli_account_id=? AND ml_status='ACTIVE' AND meli_item_id IS NOT NULL",
        [$tenantId, $acctId]
    );

    if (empty($produtos)) {
        echo json_encode(['ok'=>false,'error'=>'Nenhum anúncio ativo para pausar']);
        exit;
    }

    $pausados = 0; $erros = [];
    foreach ($produtos as $p) {
        $result = curl_ml("https://api.mercadolibre.com/items/{$p['meli_item_id']}", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS    => json_encode(['status' => 'paused']),
            CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            CURLOPT_TIMEOUT       => 10,
        ]);
        if ($result['code'] === 200) {
            db_update('products', ['ml_status'=>'PAUSED'], 'id=?', [$p['id']]);
            $pausados++;
        } else {
            $erros[] = $p['title'];
        }
        usleep(200000); // 200ms entre requisições
    }

    tenant_set_val($tenantId, $acctId, 'ferias_ativa', '1');
    tenant_set_val($tenantId, $acctId, 'ferias_pausado_em', date('Y-m-d H:i:s'));

    audit_log('FERIAS_ATIVADA', 'products', $acctId, null, ['pausados'=>$pausados]);
    echo json_encode(['ok'=>true, 'pausados'=>$pausados, 'erros'=>count($erros)]);
    exit;
}

if ($action === 'desativar') {
    $produtos = db_all(
        "SELECT id, meli_item_id, title FROM products
         WHERE tenant_id=? AND meli_account_id=? AND ml_status='PAUSED' AND meli_item_id IS NOT NULL",
        [$tenantId, $acctId]
    );

    if (empty($produtos)) {
        echo json_encode(['ok'=>false,'error'=>'Nenhum anúncio pausado para reativar']);
        exit;
    }

    $reativados = 0; $erros = [];
    foreach ($produtos as $p) {
        $result = curl_ml("https://api.mercadolibre.com/items/{$p['meli_item_id']}", [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS    => json_encode(['status' => 'active']),
            CURLOPT_HTTPHEADER    => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            CURLOPT_TIMEOUT       => 10,
        ]);
        if ($result['code'] === 200) {
            db_update('products', ['ml_status'=>'ACTIVE'], 'id=?', [$p['id']]);
            $reativados++;
        } else {
            $erros[] = $p['title'];
        }
        usleep(200000);
    }

    tenant_set_val($tenantId, $acctId, 'ferias_ativa', '0');

    audit_log('FERIAS_DESATIVADA', 'products', $acctId, null, ['reativados'=>$reativados]);
    echo json_encode(['ok'=>true, 'reativados'=>$reativados, 'erros'=>count($erros)]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
