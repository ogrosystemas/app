<?php
/**
 * api/caixa.php — Operações de Caixa
 * Sempre retorna JSON. Nunca deixa output não-JSON escapar.
 */

// 1) Buffer tudo desde o primeiro byte — captura warnings, notices, erros
ob_start();

// 2) Suprimir display de erros PHP no output (eles ficam no log, não na resposta)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// 3) Função de saída JSON centralizada
function respJson(array $data, int $code = 200): never {
    // Descartar qualquer output acidental (warnings, notices)
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 4) Capturar exceções não tratadas
set_exception_handler(function (\Throwable $e) {
    respJson([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ], 500);
});

// 5) Capturar erros PHP fatais
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        respJson([
            'success' => false,
            'message' => 'Erro fatal PHP: ' . $err['message'],
            'file'    => basename($err['file']),
            'line'    => $err['line'],
        ], 500);
    }
});

// 6) Carregar dependências
try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/DB.php';
    require_once __DIR__ . '/../includes/helpers.php';
    require_once __DIR__ . '/../includes/Auth.php';
} catch (\Throwable $e) {
    respJson(['success' => false, 'message' => 'Erro ao carregar sistema: ' . $e->getMessage()], 500);
}

Auth::requireLogin();

// CSRF check para requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        respJson(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página.'], 403);
    }
}

// 7) Ler input (FormData ou JSON body)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input  = [];

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw && isset($raw[0]) && $raw[0] === '{') {
        $decoded = json_decode($raw, true);
        $input   = is_array($decoded) ? $decoded : [];
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$action = trim($input['action'] ?? $_GET['action'] ?? '');

// 8) Processar ação
switch ($action) {

    case 'abrir':
        $op    = trim($input['operador'] ?? '');
        $saldo = parseMoeda($input['saldo_inicial'] ?? '0');

        if (!$op) {
            respJson(['success' => false, 'message' => 'Informe o nome do operador.']);
        }

        $existe = DB::row("SELECT id FROM caixas WHERE status='aberto' LIMIT 1");
        if ($existe) {
            respJson(['success' => false, 'message' => 'Já existe um caixa aberto (#' . $existe['id'] . ').']);
        }

        $_SESSION['operador'] = $op;

        $id = DB::insert('caixas', [
            'operador'      => $op,
            'data_abertura' => date('Y-m-d H:i:s'),
            'saldo_inicial' => $saldo,
            'status'        => 'aberto',
        ]);

        respJson(['success' => true, 'caixa_id' => $id, 'message' => 'Caixa aberto!']);

    case 'fechar':
        $cid     = (int) ($input['caixa_id'] ?? 0);
        $saldo_c = (float) ($input['saldo_contado'] ?? 0);
        $obs     = trim($input['observacoes'] ?? '');

        $caixa = DB::row("SELECT * FROM caixas WHERE id = ? AND status = 'aberto'", [$cid]);
        if (!$caixa) {
            respJson(['success' => false, 'message' => 'Caixa não encontrado ou já fechado.']);
        }

        $esperado = (float)$caixa['saldo_inicial']
                  + (float)$caixa['total_vendas']
                  + (float)$caixa['total_suprimentos']
                  - (float)$caixa['total_sangrias'];

        DB::update('caixas', [
            'data_fechamento'       => date('Y-m-d H:i:s'),
            'saldo_final_informado' => $saldo_c,
            'diferenca'             => round($saldo_c - $esperado, 2),
            'status'                => 'fechado',
            'observacoes'           => $obs,
        ], 'id = ?', [$cid]);

        respJson(['success' => true, 'message' => 'Caixa fechado com sucesso.']);

    case 'resumo':
        $cid   = (int) ($input['id'] ?? 0);
        $caixa = DB::row("SELECT * FROM caixas WHERE id = ?", [$cid]);
        if (!$caixa) {
            respJson(['success' => false, 'message' => 'Caixa não encontrado.']);
        }

        $caixa['saldo_esperado'] = (float)$caixa['saldo_inicial']
                                 + (float)$caixa['total_vendas']
                                 + (float)$caixa['total_suprimentos']
                                 - (float)$caixa['total_sangrias'];

        respJson(['success' => true, 'caixa' => $caixa]);

    case 'sangria':
    case 'suprimento':
        $cid    = (int) ($input['caixa_id'] ?? 0);
        $tipo   = in_array($input['tipo'] ?? '', ['sangria', 'suprimento'])
                ? $input['tipo']
                : $action;
        $valor  = parseMoeda($input['valor'] ?? '0');
        $motivo = trim($input['motivo'] ?? '');

        if ($valor <= 0) {
            respJson(['success' => false, 'message' => 'Valor deve ser maior que zero.']);
        }

        if (!$cid) {
            $row = DB::row("SELECT id FROM caixas WHERE status='aberto' ORDER BY id DESC LIMIT 1");
            $cid = (int) ($row['id'] ?? 0);
        }
        if (!$cid) {
            respJson(['success' => false, 'message' => 'Nenhum caixa aberto encontrado.']);
        }

        DB::insert('caixa_movimentos', [
            'caixa_id' => $cid,
            'tipo'     => $tipo,
            'valor'    => $valor,
            'motivo'   => $motivo,
            'operador' => $_SESSION['operador'] ?? 'sistema',
        ]);

        $col = ($tipo === 'sangria') ? 'total_sangrias' : 'total_suprimentos';
        DB::q("UPDATE caixas SET {$col} = {$col} + ? WHERE id = ?", [$valor, $cid]);

        respJson([
            'success' => true,
            'message' => ucfirst($tipo) . ' de R$ ' . number_format($valor, 2, ',', '.') . ' registrada.',
        ]);

    default:
        respJson([
            'success' => false,
            'message' => 'Ação inválida: "' . htmlspecialchars($action) . '".',
            'dica'    => 'Ações válidas: abrir, fechar, resumo, sangria, suprimento',
        ], 400);
}


