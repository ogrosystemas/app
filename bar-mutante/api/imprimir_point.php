<?php
/**
 * api/imprimir_point.php
 * Envia tickets para impressão na Point Smart 2 via API do Mercado Pago
 *
 * POST { venda_id: 123 }
 * Busca todos os tickets da venda e imprime um a um na maquininha.
 */
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/Auth.php';
Auth::requireLogin();

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$vendaId = (int)($input['venda_id'] ?? $_GET['venda_id'] ?? 0);

if (!$vendaId) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'venda_id obrigatório']);
    exit;
}

// ── Credenciais MP ────────────────────────────────────────────────────────────
$mpToken  = DB::cfg('mp_access_token', '');
$deviceId = DB::cfg('mp_device_id', '');

if (!$mpToken || !$deviceId) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Mercado Pago não configurado. Acesse Configurações → Mercado Pago.']);
    exit;
}

// ── Configurações do layout ───────────────────────────────────────────────────
$lCfg     = array_column(DB::all("SELECT chave,valor FROM configuracoes WHERE chave LIKE 'ticket_%'"), 'valor', 'chave');
$nomeEst  = DB::cfg('nome_estabelecimento', 'Bar System Pro');
$rodape   = $lCfg['ticket_rodape']   ?? 'Obrigado pela preferência!';
$mostEst  = ($lCfg['ticket_mostrar_estabelecimento'] ?? '1') === '1';
$mostData = ($lCfg['ticket_mostrar_data'] ?? '1') === '1';
$mostLogo = ($lCfg['ticket_logo'] ?? '1') === '1';
$borda    = $lCfg['ticket_borda_estilo'] ?? 'simples';

// Logo em base64 (se configurada)
$logoB64 = '';
if ($mostLogo) {
    $logoFile = DB::cfg('logo_login', '');
    if ($logoFile) {
        $logoPath = UPLOAD_PATH . 'logos/' . $logoFile;
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
        }
    }
}

// ── Buscar tickets da venda ───────────────────────────────────────────────────
$tickets = DB::all(
    "SELECT t.*, v.data_venda FROM tickets t
     JOIN vendas v ON t.venda_id = v.id
     WHERE t.venda_id = ? AND t.status != 'cancelado'
     ORDER BY t.produto_nome, t.id",
    [$vendaId]
);

if (empty($tickets)) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Nenhum ticket encontrado para esta venda.']);
    exit;
}

// ── Montar conteúdo de impressão (ESC/POS via tags MP) ────────────────────────
function montarTicket(array $ticket, string $nomeEst, string $rodape,
                      bool $mostEst, bool $mostData, string $borda,
                      string $logoB64, string $data): string
{
    $sep = str_repeat(match($borda) {
        'dupla'     => '=',
        'tracejada' => '-',
        'grossa'    => '*',
        default     => '-',
    }, 32);

    $linhas = [];

    // Logo (imagem base64)
    if ($logoB64) {
        $linhas[] = "<image>{$logoB64}</image>";
    }

    // Nome do estabelecimento
    if ($mostEst) {
        $linhas[] = "<align>center</align><bold>true</bold><text>{$nomeEst}\n</text><bold>false</bold>";
    }

    $linhas[] = "<text>{$sep}\n</text>";

    // Produto
    $produto = mb_strtoupper($ticket['produto_nome'], 'UTF-8');
    $linhas[] = "<align>center</align><bold>true</bold><text-size>2</text-size><text>{$produto}\n</text><text-size>1</text-size><bold>false</bold>";

    $linhas[] = "<text>{$sep}\n</text>";

    // Data
    if ($mostData && !empty($ticket['data_venda'])) {
        $dt = date('d/m/Y H:i', strtotime($ticket['data_venda']));
        $linhas[] = "<align>center</align><text>{$dt}\n</text>";
    }

    // Rodapé
    if ($rodape) {
        $linhas[] = "<text>{$sep}\n</text>";
        $linhas[] = "<align>center</align><text>{$rodape}\n</text>";
    }

    $linhas[] = "<text>\n</text>"; // linha final antes do corte

    return implode('', $linhas);
}

// ── Enviar cada ticket para a fila de impressão da Point ─────────────────────
$impressos = 0;
$erros     = [];

foreach ($tickets as $ticket) {
    $conteudo = montarTicket(
        $ticket, $nomeEst, $rodape,
        $mostEst, $mostData, $borda,
        $logoB64, $ticket['data_venda'] ?? ''
    );

    $payload = [
        'type'   => 'print',
        'print'  => [
            'type'    => 'custom',
            'content' => $conteudo,
        ],
    ];

    $ch = curl_init("https://api.mercadopago.com/point/integration-api/devices/{$deviceId}/actions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer {$mpToken}",
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . uniqid('tkt_', true),
        ],
    ]);

    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (in_array($code, [200, 201])) {
        $impressos++;
    } else {
        $data_mp = json_decode($res, true);
        $erros[] = $ticket['produto_nome'] . ': ' . ($data_mp['message'] ?? "HTTP {$code}");
    }

    // Pequena pausa entre tickets para não sobrecarregar a fila
    if (count($tickets) > 1) usleep(300000); // 300ms
}

ob_end_clean();

if ($impressos === count($tickets)) {
    echo json_encode([
        'success'   => true,
        'impressos' => $impressos,
        'message'   => "{$impressos} ticket(s) enviado(s) para impressão na maquininha.",
    ]);
} elseif ($impressos > 0) {
    echo json_encode([
        'success'   => true,
        'impressos' => $impressos,
        'erros'     => $erros,
        'message'   => "{$impressos} de " . count($tickets) . " ticket(s) impresso(s). Erros: " . implode('; ', $erros),
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Falha ao enviar tickets: ' . implode('; ', $erros),
    ]);
}
