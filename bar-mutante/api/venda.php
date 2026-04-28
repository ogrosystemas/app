<?php
ob_start();
ini_set("display_errors","0");
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];

// CSRF check for state-changing requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['_csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
        jsonRes(['success' => false, 'message' => 'Token de segurança inválido. Recarregue a página.'], 403);
    }
}
$action = $input['action'] ?? '';

if ($action !== 'finalizar') jsonRes(['success'=>false,'message'=>'Ação inválida.'], 400);

$caixa_id = (int)($input['caixa_id'] ?? 0);
$itens    = $input['itens']    ?? [];
$subtotal = (float)($input['subtotal'] ?? 0);
$desconto = (float)($input['desconto'] ?? 0);
$total    = (float)($input['total']    ?? 0);
$forma    = $input['forma_pagamento'] ?? 'dinheiro';
$mesa     = trim($input['mesa'] ?? '');
$psData   = $input['mp_data'] ?? $input['mercadopago'] ?? null;

if (empty($itens))  jsonRes(['success'=>false,'message'=>'Nenhum item no pedido.']);
if ($total < 0)     jsonRes(['success'=>false,'message'=>'Total inválido.']);
if (!$caixa_id)     jsonRes(['success'=>false,'message'=>'Caixa não identificado.']);

$caixa = DB::row("SELECT * FROM caixas WHERE id=? AND status='aberto'", [$caixa_id]);
if (!$caixa) jsonRes(['success'=>false,'message'=>'Caixa fechado ou inválido.']);

DB::begin();
try {
    $prefixo = DB::cfg('prefix_venda','VND');
    $numero  = DB::nextNum('numero_venda', $prefixo);

    $venda_id = DB::insert('vendas',[
        'caixa_id'             => $caixa_id,
        'numero'               => $numero,
        'data_venda'           => date('Y-m-d H:i:s'),
        'subtotal'             => $subtotal,
        'desconto'             => $desconto,
        'total'                => $total,
        'forma_pagamento'      => $forma,
        'status'               => $forma === 'mercadopago' ? 'pago' : 'pago',
        'mp_order_id'  => $psData['charge_id'] ?? null,
        'mp_device_id'=> $psData['terminal_id'] ?? null,
        'mp_status'     => $psData['status'] ?? null,
        'mesa'                 => $mesa,
    ]);

    foreach ($itens as $item) {
        $prod_id = (int)($item['id'] ?? 0);
        $qty     = (float)($item['qty'] ?? 1);
        $preco   = (float)($item['preco'] ?? 0);
        $nome    = $item['nome'] ?? '';
        if (!$prod_id || $qty <= 0) continue;

        // Buscar tipo e composição do banco (mais confiável que o JS)
        $prod = DB::row("SELECT * FROM produtos WHERE id=?", [$prod_id]);
        if (!$prod) continue;
        $tipo = $prod['tipo'];

        DB::insert('venda_itens', [
            'venda_id'       => $venda_id,
            'produto_id'     => $prod_id,
            'descricao'      => $nome,
            'quantidade'     => $qty,
            'preco_unitario' => $preco,
            'total'          => $qty * $preco,
        ]);

        // ── Baixar estoque conforme tipo ──────────────────────────
        if (in_array($tipo, ['unidade', 'chopp_lata', 'garrafa'])) {
            movEstoque($prod_id, 'saida', $qty, $preco, "Venda $numero", 'venda', $venda_id);

        } elseif ($tipo === 'chopp_barril' || $tipo === 'dose') {
            movEstoque($prod_id, 'saida', $qty, $preco, "Venda $numero", 'venda', $venda_id);
            // Para chopp barril: registrar consumo em ml no barril ativo
            if ($tipo === 'chopp_barril' && $prod['ml_por_dose']) {
                $barril = DB::row("SELECT * FROM barris WHERE produto_id=? AND status='em_uso' ORDER BY id LIMIT 1", [$prod_id]);
                if ($barril) {
                    $novo_ml = $barril['ml_consumido'] + ($qty * $prod['ml_por_dose']);
                    DB::update('barris', ['ml_consumido' => $novo_ml], 'id=?', [$barril['id']]);
                    $saldo_ml = ($barril['capacidade_ml'] * $barril['rendimento_pct'] / 100) - $novo_ml;
                    if ($saldo_ml <= 0) DB::update('barris', ['status' => 'vazio'], 'id=?', [$barril['id']]);
                }
            }

        } elseif ($tipo === 'drink' || $tipo === 'combo') {
            // Baixar ingredientes da composição
            $comp = $prod['composicao'] ? json_decode($prod['composicao'], true) : [];
            foreach ($comp as $ing) {
                $ipid = (int)($ing['produto_id'] ?? 0);
                $iqty = (float)($ing['quantidade'] ?? 1) * $qty;
                if ($ipid > 0 && $iqty > 0) {
                    movEstoque($ipid, 'saida', $iqty, 0, "Venda $numero ($nome)", 'venda', $venda_id);
                }
            }
        }
    }

    // Atualizar total do caixa
    DB::q("UPDATE caixas SET total_vendas=total_vendas+? WHERE id=?", [$total, $caixa_id]);

    DB::commit();

    // Retornar novo resumo do caixa + estoques atualizados para cada produto vendido
    $novo_caixa = DB::row("SELECT total_vendas, (SELECT COUNT(*) FROM vendas WHERE caixa_id=? AND status='pago') as n FROM caixas WHERE id=?", [$caixa_id,$caixa_id]);

    // Buscar estoque atualizado de todos os produtos vendidos (para o PDV atualizar ao vivo)
    $prod_ids = array_unique(array_filter(array_map(fn($i)=>(int)($i['id']??0), $itens)));
    $estoques = [];
    foreach ($prod_ids as $pid) {
        $p = DB::row("SELECT id, estoque_atual, tipo, composicao FROM produtos WHERE id=?", [$pid]);
        if ($p) {
            $estoques[$pid] = (float)$p['estoque_atual'];
            // Para drinks/combos, retornar estoque dos ingredientes
            if (in_array($p['tipo'],['drink','combo']) && $p['composicao']) {
                $comp = json_decode($p['composicao'],true) ?? [];
                foreach ($comp as $ing) {
                    $ipid = (int)($ing['produto_id']??0);
                    if ($ipid && !isset($estoques[$ipid])) {
                        $ip = DB::row("SELECT estoque_atual FROM produtos WHERE id=?",[$ipid]);
                        if ($ip) $estoques[$ipid] = (float)$ip['estoque_atual'];
                    }
                }
            }
        }
    }

    // ── Gerar tickets de consumo ──────────────────────────────────────────
    $tickets_gerados = [];
    $itens_venda = DB::all(
        "SELECT vi.*, p.tipo, p.composicao FROM venda_itens vi
         LEFT JOIN produtos p ON vi.produto_id=p.id
         WHERE vi.venda_id=?",
        [$venda_id]
    );

    foreach ($itens_venda as $item) {
        $qtd         = (int)$item['quantidade'];
        $tipo        = $item['tipo'] ?? 'unidade';
        $composicao  = $item['composicao'] ? json_decode($item['composicao'], true) : null;

        if ($tipo === 'combo' && $composicao) {
            // Combo: gerar 1 ticket por item do combo × quantidade vendida
            foreach ($composicao as $comp) {
                $compProd = DB::row("SELECT nome FROM produtos WHERE id=?", [$comp['produto_id']]);
                $compNome = $compProd['nome'] ?? $item['descricao'];
                $compQtd  = (int)($comp['quantidade'] ?? 1) * $qtd;
                for ($i = 0; $i < $compQtd; $i++) {
                    $codigo = gerarCodigoTicket();
                    DB::insert('tickets', [
                        'codigo'       => $codigo,
                        'venda_id'     => $venda_id,
                        'produto_id'   => (int)$comp['produto_id'],
                        'produto_nome' => $compNome,
                        'status'       => 'pendente',
                    ]);
                    $tickets_gerados[] = ['codigo' => $codigo, 'produto' => $compNome];
                }
            }
        } else {
            // Produto simples: 1 ticket por unidade
            for ($i = 0; $i < $qtd; $i++) {
                $codigo = gerarCodigoTicket();
                DB::insert('tickets', [
                    'codigo'       => $codigo,
                    'venda_id'     => $venda_id,
                    'produto_id'   => (int)$item['produto_id'],
                    'produto_nome' => $item['descricao'],
                    'status'       => 'pendente',
                ]);
                $tickets_gerados[] = ['codigo' => $codigo, 'produto' => $item['descricao']];
            }
        }
    }

    // ── Impressão automática via Point Smart 2 ──────────────────────────────
    $point_resultado = null;
    $mpToken   = DB::cfg('mp_access_token', '');
    $deviceId  = DB::cfg('mp_device_id', '');
    $usarPoint = !empty($mpToken) && !empty($deviceId) && !empty($tickets_gerados);

    if ($usarPoint) {
        // Chamar imprimir_point.php internamente via include
        $point_resultado = imprimirTicketsPoint($venda_id, $mpToken, $deviceId);
    }

    jsonRes([
        'success'       => true,
        'venda_id'      => $venda_id,
        'numero'        => $numero,
        'total_caixa'   => (float)($novo_caixa['total_vendas']??0),
        'vendas_n'      => (int)($novo_caixa['n']??0),
        'estoques'      => $estoques,
        'tickets'       => $tickets_gerados,
        'total_tickets' => count($tickets_gerados),
        'print_url'     => BASE_URL . 'modules/tickets/imprimir.php?venda_id=' . $venda_id,
        'point_print'   => $point_resultado,
    ]);

} catch (\Throwable $e) {
    DB::rollback();
    jsonRes(['success'=>false,'message'=>'Erro interno: '.$e->getMessage()], 500);
}

// ── Impressão na Point Smart 2 ───────────────────────────────────────────────
function imprimirTicketsPoint(int $vendaId, string $mpToken, string $deviceId): array {
    $lCfg     = array_column(DB::all("SELECT chave,valor FROM configuracoes WHERE chave LIKE 'ticket_%'"), 'valor', 'chave');
    $nomeEst  = DB::cfg('nome_estabelecimento', 'Bar System Pro');
    $rodape   = $lCfg['ticket_rodape']   ?? 'Obrigado pela preferência!';
    $mostEst  = ($lCfg['ticket_mostrar_estabelecimento'] ?? '1') === '1';
    $mostData = ($lCfg['ticket_mostrar_data'] ?? '1') === '1';
    $mostLogo = ($lCfg['ticket_logo'] ?? '1') === '1';
    $borda    = $lCfg['ticket_borda_estilo'] ?? 'simples';

    // Logo base64
    $logoB64 = '';
    if ($mostLogo) {
        $logoFile = DB::cfg('logo_login', '');
        if ($logoFile) {
            $logoPath = UPLOAD_PATH . 'logos/' . $logoFile;
            if (file_exists($logoPath)) $logoB64 = base64_encode(file_get_contents($logoPath));
        }
    }

    $tickets = DB::all(
        "SELECT t.*, v.data_venda FROM tickets t
         JOIN vendas v ON t.venda_id = v.id
         WHERE t.venda_id = ? AND t.status != 'cancelado'
         ORDER BY t.produto_nome, t.id",
        [$vendaId]
    );

    if (empty($tickets)) return ['impressos'=>0,'total'=>0];

    $sep = str_repeat(match($borda) { 'dupla'=>'=','tracejada'=>'-','grossa'=>'*',default=>'-' }, 32);

    $impressos = 0;
    foreach ($tickets as $ticket) {
        $linhas = [];
        if ($logoB64) $linhas[] = "<image>{$logoB64}</image>";
        if ($mostEst) $linhas[] = "<align>center</align><bold>true</bold><text>{$nomeEst}
</text><bold>false</bold>";
        $linhas[] = "<text>{$sep}
</text>";
        $prod = mb_strtoupper($ticket['produto_nome'], 'UTF-8');
        $linhas[] = "<align>center</align><bold>true</bold><text-size>2</text-size><text>{$prod}
</text><text-size>1</text-size><bold>false</bold>";
        $linhas[] = "<text>{$sep}
</text>";
        if ($mostData && !empty($ticket['data_venda'])) {
            $linhas[] = "<align>center</align><text>" . date('d/m/Y H:i', strtotime($ticket['data_venda'])) . "
</text>";
        }
        if ($rodape) {
            $linhas[] = "<text>{$sep}
</text>";
            $linhas[] = "<align>center</align><text>{$rodape}
</text>";
        }
        $linhas[] = "<text>
</text>";

        $payload = ['type'=>'print','print'=>['type'=>'custom','content'=>implode('', $linhas)]];

        $ch = curl_init("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/actions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$mpToken}",
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid('tkt_', true),
            ],
        ]);
        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if (in_array($code, [200, 201])) $impressos++;
        if (count($tickets) > 1) usleep(250000); // 250ms entre tickets
    }

    return ['impressos' => $impressos, 'total' => count($tickets)];
}

function gerarCodigoTicket(): string {
    $chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    do {
        $c = 'TKT-';
        for ($i=0;$i<6;$i++) $c .= $chars[random_int(0,strlen($chars)-1)];
    } while (DB::count('tickets',"codigo=?",[$c]) > 0);
    return $c;
}
