<?php
/**
 * api/imprimir.php — Proxy de impressão silenciosa
 *
 * Envia ESC/POS via TCP socket direto para impressora na rede.
 * Chamado pelo PDV via fetch() — sem abrir janela alguma.
 *
 * POST { venda_id: X }  → busca tickets, gera ESC/POS, envia para impressora
 * GET  ?action=ping      → testa conexão com a impressora
 */
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::requireLogin();

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? 'imprimir';

// Configurações da impressora
$printerIP   = DB::cfg('impressora_ip',   '');
$printerPort = (int)(DB::cfg('impressora_porta', '9100') ?: 9100);
$printerNome = DB::cfg('impressora_nome', '');

// ── Ping / teste de conexão ────────────────────────────────────────────────
if ($action === 'ping') {
    if (!$printerIP) {
        jsonRes(['success' => false, 'message' => 'IP da impressora não configurado.']);
    }
    $sock = @fsockopen($printerIP, $printerPort, $errno, $errstr, 3);
    if ($sock) {
        fclose($sock);
        jsonRes(['success' => true, 'message' => "Impressora $printerIP:$printerPort OK"]);
    }
    jsonRes(['success' => false, 'message' => "Não foi possível conectar: $errstr ($errno)"]);
}

// ── Imprimir tickets ───────────────────────────────────────────────────────
$vendaId = (int)($input['venda_id'] ?? $_GET['venda_id'] ?? 0);
if (!$vendaId) jsonRes(['success' => false, 'message' => 'venda_id obrigatório']);

// Buscar tickets da venda
$tickets = DB::all(
    "SELECT * FROM tickets WHERE venda_id = ? AND status != 'cancelado' ORDER BY produto_nome, id",
    [$vendaId]
);
if (empty($tickets)) jsonRes(['success' => false, 'message' => 'Nenhum ticket para imprimir.']);

$venda    = DB::row(
    "SELECT v.*, cx.operador FROM vendas v LEFT JOIN caixas cx ON v.caixa_id = cx.id WHERE v.id = ?",
    [$vendaId]
);
$nomeEst  = DB::cfg('nome_estabelecimento', 'Bar System');
$instrucao= DB::cfg('ticket_instrucao',     'Apresente este ticket ao balcão');
$rodape   = DB::cfg('ticket_rodape',        '');
$mostEst  = DB::cfg('ticket_mostrar_estabelecimento', '1') === '1';
$mostData = DB::cfg('ticket_mostrar_data',             '1') === '1';
$mostVenda= DB::cfg('ticket_mostrar_numero_venda',     '1') === '1';
$data_hr  = date('d/m/Y H:i', strtotime($venda['data_venda'] ?? 'now'));

// ── Gerar bytes ESC/POS ────────────────────────────────────────────────────
$ESC = chr(27); $GS = chr(29);
$INIT    = $ESC . '@';            // Reset
$CENTER  = $ESC . 'a' . chr(1);  // Centro
$LEFT    = $ESC . 'a' . chr(0);  // Esquerda
$BOLD_ON = $ESC . 'E' . chr(1);
$BOLD_OF = $ESC . 'E' . chr(0);
$BIG_ON  = $GS  . '!' . chr(17); // 2× altura e largura
$BIG_OF  = $GS  . '!' . chr(0);
$CUT     = $GS  . 'V' . chr(65) . chr(3); // Corte parcial
$LINE    = str_repeat('-', 32) . "\n";
$DLINE   = str_repeat('=', 32) . "\n";

$raw = $INIT;

foreach ($tickets as $t) {
    $raw .= $DLINE;
    if ($mostEst) {
        $raw .= $CENTER . $BOLD_ON . mb_strtoupper(mb_substr($nomeEst, 0, 32)) . "\n" . $BOLD_OF;
    }
    $raw .= $LINE;
    // Nome do produto em destaque
    $raw .= $CENTER . $BIG_ON . $BOLD_ON;
    $raw .= mb_strtoupper(mb_substr($t['produto_nome'], 0, 16)) . "\n";
    $raw .= $BIG_OF . $BOLD_OF;
    $raw .= $LINE;
    // Código do ticket — fonte grande
    $raw .= $CENTER . $BIG_ON . $BOLD_ON . $t['codigo'] . "\n" . $BIG_OF . $BOLD_OF;
    $raw .= $LINE;
    // Informações
    $raw .= $LEFT;
    if ($mostVenda) $raw .= "Venda: #" . $venda['numero'] . "\n";
    if ($mostData)  $raw .= "Data:  " . $data_hr . "\n";
    $raw .= $LINE;
    if ($instrucao) $raw .= $CENTER . wordwrap($instrucao, 32, "\n", true) . "\n";
    if ($rodape)    $raw .= $CENTER . wordwrap($rodape,    32, "\n", true) . "\n";
    $raw .= "\n\n";
    $raw .= $CUT;
}

// ── Tentar enviar para impressora via TCP ──────────────────────────────────
$viaTCP = false;
if ($printerIP && $printerPort) {
    $sock = @fsockopen($printerIP, $printerPort, $errno, $errstr, 4);
    if ($sock) {
        $bytes = strlen($raw);
        $sent  = fwrite($sock, $raw);
        fclose($sock);
        if ($sent >= $bytes) {
            $viaTCP = true;
        }
    }
}

// ── Retornar resultado + ESC/POS base64 para QZ Tray ──────────────────────
jsonRes([
    'success'       => true,
    'via_tcp'       => $viaTCP,
    'escpos'        => base64_encode($raw),
    'total_tickets' => count($tickets),
    'message'       => $viaTCP
        ? count($tickets) . ' ticket(s) enviado(s) para impressora.'
        : count($tickets) . ' ticket(s) prontos (use QZ Tray para imprimir).',
]);
