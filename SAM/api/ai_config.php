<?php
/**
 * api/ai_config.php
 * GET  ?action=test&provider=groq&key=gsk_...  — testa um provedor
 * POST action=save_provider  — salva provedor ativo + chaves
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../ai.php';

session_start_readonly();
auth_require();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// ── GET: testar provedor ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $provider = $_GET['provider'] ?? '';
    $key      = $_GET['key']      ?? '';

    if (!$provider || !$key) {
        echo json_encode(['ok'=>false,'error'=>'provider e key obrigatórios']);
        exit;
    }

    $result = ai_test_provider($provider, $key);
    echo json_encode($result);
    exit;
}

// ── POST: salvar configurações ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'analisar_saude') {
        $productId = $_POST['product_id'] ?? '';
        $title     = trim($_POST['title'] ?? '');
        $health    = (int)($_POST['health'] ?? 0);

        if (!$productId || !$title) {
            echo json_encode(['ok'=>false,'error'=>'Parâmetros inválidos']);
            exit;
        }

        require_once dirname(__DIR__) . '/ai.php';

        $product = db_one("SELECT * FROM products WHERE id=? AND tenant_id=?", [$productId, $tenantId]);
        $attrs   = json_decode($product['ml_attributes'] ?? '[]', true) ?: [];
        $attrStr = implode(', ', array_map(fn($a) => "{$a['name']}: {$a['value_name']}", array_slice($attrs, 0, 8)));

        $prompt = "Você é um especialista em otimização de anúncios do Mercado Livre Brasil.

Analise este anúncio com saúde de {$health}% e forneça sugestões práticas e específicas para melhorar a qualidade.

DADOS DO ANÚNCIO:
- Título: {$title}
- Preço: R$ " . number_format((float)($product['price']??0), 2, ',', '.') . "
- Condição: " . ($product['item_condition'] === 'used' ? 'Usado' : 'Novo') . "
- Atributos: " . ($attrStr ?: 'Não informado') . "
- Estoque: " . ($product['stock_quantity'] ?? 'N/A') . "

INSTRUÇÕES:
- Liste de 3 a 5 melhorias específicas e acionáveis
- Foque nos critérios do ML: título (60 chars, palavras-chave), fotos (mínimo 5), descrição, atributos obrigatórios, preço competitivo
- Seja direto e objetivo
- Use bullets (•) para cada sugestão
- Não use markdown com asteriscos";

        $result = ai_generate_for($tenantId, 'sac', $prompt, 600);

        if ($result['text']) {
            echo json_encode(['ok'=>true, 'analysis'=>$result['text']]);
        } else {
            echo json_encode(['ok'=>false, 'error'=>'Configure um provedor de IA em Integração ML.']);
        }
        exit;
    }

    if ($action === 'save_use_provider') {
        $use      = $_POST['use']      ?? '';
        $provider = $_POST['provider'] ?? '';
        if (!$use || !isset(AI_PROVIDERS[$provider])) {
            echo json_encode(['ok'=>false,'error'=>'Parâmetros inválidos']);
            exit;
        }
        tenant_setting_set($tenantId, "ai_provider_{$use}", $provider);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_provider') {
        $provider = $_POST['provider'] ?? '';
        if (!$provider || !isset(AI_PROVIDERS[$provider])) {
            echo json_encode(['ok'=>false,'error'=>'Provedor inválido']);
            exit;
        }
        tenant_setting_set($tenantId, 'ai_provider', $provider);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_key') {
        $provider = $_POST['provider'] ?? '';
        $key      = trim($_POST['key'] ?? '');
        if (!$provider || !isset(AI_PROVIDERS[$provider])) {
            echo json_encode(['ok'=>false,'error'=>'Provedor inválido']);
            exit;
        }
        tenant_setting_set($tenantId, 'ai_key_'.$provider, $key);
        if ($provider === 'gemini') tenant_setting_set($tenantId, 'gemini_api_key', $key);
        if ($provider === 'groq')   tenant_setting_set($tenantId, 'groq_api_key',   $key);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

echo json_encode(['ok'=>false,'error'=>'Ação inválida']);
