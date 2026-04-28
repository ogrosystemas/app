<?php
/**
 * api/pdf_etiqueta.php
 * Gera etiqueta(s) de envio em PDF via TCPDF.
 * Formato: 10x15cm (A6 landscape)
 *
 * GET params:
 *   order_id  = ID único do pedido
 *   ids       = IDs separados por vírgula para lote
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Inicia sessão sem redirecionar — PDF não pode fazer redirect
session_start_secure();

$user = auth_user();

// Se não estiver logado, mostra página de erro simples (não redirect)
if (!$user) {
    http_response_code(401);
    echo '<h2>Sessao expirada. Feche esta aba e recarregue o sistema.</h2>';
    exit;
}

$tenantId = $user['tenant_id'];

// Carrega TCPDF somente após validar sessão
require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

// Aceita order_id único ou ids[] para lote
$orderIds = [];
if (!empty($_GET['order_id'])) {
    $orderIds = [$_GET['order_id']];
} elseif (!empty($_GET['ids'])) {
    $orderIds = array_filter(explode(',', $_GET['ids']));
}

if (empty($orderIds)) {
    http_response_code(400);
    exit(json_encode(['ok'=>false,'error'=>'order_id obrigatorio']));
}

// Busca pedidos com itens
$placeholders = implode(',', array_fill(0, count($orderIds), '?'));
$orders = db_all(
    "SELECT o.*,
            ma.nickname as acct_nickname,
            GROUP_CONCAT(oi.title ORDER BY oi.id SEPARATOR ' | ') as items_titles,
            SUM(oi.quantity) as total_qty
     FROM orders o
     LEFT JOIN meli_accounts ma ON ma.id = o.meli_account_id
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.id IN ({$placeholders}) AND o.tenant_id=?
     GROUP BY o.id
     ORDER BY o.order_date ASC",
    array_merge($orderIds, [$tenantId])
);

if (empty($orders)) {
    http_response_code(404);
    exit(json_encode(['ok'=>false,'error'=>'Pedidos nao encontrados']));
}

// Busca dados do tenant para remetente
$tenant = db_one("SELECT name FROM tenants WHERE id=?", [$tenantId]);
$remetente = $tenant['name'] ?? APP_NAME;

// ── Cores ─────────────────────────────────────────────────
$C_DARK   = [20,  20,  30];
$C_BLUE   = [30,  80, 162];
$C_GRAY   = [100,100,100];
$C_LGRAY  = [244,246,250];
$C_BORDER = [200,205,215];
$C_WHITE  = [255,255,255];
$C_GREEN  = [22, 101, 52];
$C_AMBER  = [180, 83,  9];
$C_RED    = [153, 27, 27];

// ── TCPDF — formato 10x15cm (100x150mm) ──────────────────
$pdf = new TCPDF('L', 'mm', [100, 150], true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor($user['name']);
$pdf->SetTitle('Etiqueta de Envio');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

foreach ($orders as $idx => $o) {
    $pdf->AddPage();

    $W = 150; // largura (landscape)
    $H = 100; // altura

    // ── Faixa azul topo ──────────────────────────────────
    $pdf->SetFillColor($C_BLUE[0],$C_BLUE[1],$C_BLUE[2]);
    $pdf->Rect(0, 0, $W, 14, 'F');

    // Logo/nome do sistema
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('helvetica','B',10);
    $pdf->SetXY(4, 3);
    $pdf->Cell(80, 8, APP_NAME, 0, 0, 'L');

    // Número do pedido
    $pdf->SetFont('helvetica','B',11);
    $pdf->SetXY(80, 3);
    $pdf->Cell(66, 8, '#' . $o['meli_order_id'], 0, 0, 'R');

    // Linha dourada decorativa
    $pdf->SetDrawColor(255,230,0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(0, 14, $W, 14);

    // ── Corpo principal ──────────────────────────────────
    // Divide em 2 colunas: esquerda (destinatário) e direita (info pedido)
    $colL = 88;
    $colR = $W - $colL - 6;

    // ── COLUNA ESQUERDA: Destinatário ────────────────────
    $pdf->SetDrawColor($C_BORDER[0],$C_BORDER[1],$C_BORDER[2]);
    $pdf->SetLineWidth(0.3);

    // Título "DESTINATÁRIO"
    $pdf->SetFillColor($C_LGRAY[0],$C_LGRAY[1],$C_LGRAY[2]);
    $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
    $pdf->SetFont('helvetica','B',6.5);
    $pdf->SetXY(4, 16);
    $pdf->Cell($colL, 5, 'DESTINATARIO', 0, 1, 'L');

    // Nome do comprador
    $pdf->SetTextColor($C_DARK[0],$C_DARK[1],$C_DARK[2]);
    $pdf->SetFont('helvetica','B',12);
    $pdf->SetXY(4, 22);
    $pdf->Cell($colL, 7, mb_strtoupper(mb_substr($o['buyer_nickname'],0,28)), 0, 1, 'L');

    // Cidade / Estado
    $cidade  = $o['ship_city']  ?? '';
    $estado  = $o['ship_state'] ?? '';
    $destino = trim("$cidade" . ($estado ? " - $estado" : ''));
    if ($destino) {
        $pdf->SetFont('helvetica','',9);
        $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
        $pdf->SetXY(4, 30);
        $pdf->Cell($colL, 6, $destino, 0, 1, 'L');
    }

    // Linha separadora
    $pdf->SetDrawColor($C_BORDER[0],$C_BORDER[1],$C_BORDER[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(4, 38, $colL + 2, 38);

    // Remetente
    $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
    $pdf->SetFont('helvetica','B',6.5);
    $pdf->SetXY(4, 40);
    $pdf->Cell($colL, 4, 'REMETENTE', 0, 1, 'L');

    $pdf->SetTextColor($C_DARK[0],$C_DARK[1],$C_DARK[2]);
    $pdf->SetFont('helvetica','B',9);
    $pdf->SetXY(4, 45);
    $pdf->Cell($colL, 5, mb_substr($remetente, 0, 32), 0, 1, 'L');

    if ($o['acct_nickname']) {
        $pdf->SetFont('helvetica','',7.5);
        $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
        $pdf->SetXY(4, 51);
        $pdf->Cell($colL, 4, 'Conta ML: ' . $o['acct_nickname'], 0, 1, 'L');
    }

    // Linha separadora
    $pdf->Line(4, 57, $colL + 2, 57);

    // Produtos
    $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
    $pdf->SetFont('helvetica','B',6.5);
    $pdf->SetXY(4, 59);
    $pdf->Cell($colL, 4, 'CONTEUDO', 0, 1, 'L');

    $pdf->SetTextColor($C_DARK[0],$C_DARK[1],$C_DARK[2]);
    $pdf->SetFont('helvetica','',7.5);
    $pdf->SetXY(4, 64);
    $itens = mb_substr($o['items_titles'] ?? 'Produtos variados', 0, 90);
    $pdf->MultiCell($colL - 2, 4, $itens, 0, 'L', false, 1);

    // ── DIVISÓRIA VERTICAL ────────────────────────────────
    $pdf->SetDrawColor($C_BORDER[0],$C_BORDER[1],$C_BORDER[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line($colL + 4, 15, $colL + 4, $H - 6);

    // ── COLUNA DIREITA: Detalhes do pedido ───────────────
    $rx = $colL + 8; // x inicial col direita
    $rw = $colR - 4; // largura col direita

    // Status de envio
    $shipLabels = [
        'ready_to_ship' => ['Pronto p/ Envio', $C_BLUE],
        'READY_TO_SHIP' => ['Pronto p/ Envio', $C_BLUE],
        'shipped'       => ['Em Transito',     $C_GREEN],
        'SHIPPED'       => ['Em Transito',     $C_GREEN],
        'pending'       => ['Pendente',         $C_AMBER],
        'PENDING'       => ['Pendente',         $C_AMBER],
        'delivered'     => ['Entregue',         $C_GREEN],
        'DELIVERED'     => ['Entregue',         $C_GREEN],
    ];
    [$shipLabel, $shipColor] = $shipLabels[$o['ship_status']] ?? ['Desconhecido', $C_GRAY];

    $pdf->SetFillColor($shipColor[0],$shipColor[1],$shipColor[2]);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('helvetica','B',8);
    $pdf->RoundedRect($rx, 16, $rw, 8, 2, '1111', 'F');
    $pdf->SetXY($rx, 17.5);
    $pdf->Cell($rw, 5, $shipLabel, 0, 0, 'C');

    // Dados do pedido
    $fields = [
        ['Data do pedido',   date('d/m/Y', strtotime($o['order_date']))],
        ['Pagamento',        ucfirst(strtolower($o['payment_status'] ?? '-'))],
        ['Qtd. itens',       ($o['total_qty'] ?? 1) . ' item(s)'],
        ['Valor total',      'R$ ' . number_format((float)$o['total_amount'],2,',','.')],
    ];

    $fy = 28;
    foreach ($fields as [$label, $val]) {
        $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
        $pdf->SetFont('helvetica','',6.5);
        $pdf->SetXY($rx, $fy);
        $pdf->Cell($rw, 4, $label, 0, 1, 'L');

        $pdf->SetTextColor($C_DARK[0],$C_DARK[1],$C_DARK[2]);
        $pdf->SetFont('helvetica','B',8.5);
        $pdf->SetXY($rx, $fy + 4);
        $pdf->Cell($rw, 5, $val, 0, 1, 'L');

        $fy += 11;
    }

    // Mediação (se houver)
    if ($o['has_mediacao']) {
        $pdf->SetFillColor($C_RED[0],$C_RED[1],$C_RED[2]);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFont('helvetica','B',7);
        $pdf->SetXY($rx, 72);
        $pdf->Cell($rw, 6, '! MEDIACAO ATIVA', 0, 0, 'C', true);
    }

    // NF (se houver)
    if ($o['nf_number']) {
        $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
        $pdf->SetFont('helvetica','',6.5);
        $pdf->SetXY($rx, $o['has_mediacao'] ? 80 : 74);
        $pdf->Cell($rw, 4, 'NF: ' . $o['nf_number'], 0, 0, 'L');
    }

    // ── Rodapé da etiqueta ────────────────────────────────
    $pdf->SetFillColor($C_LGRAY[0],$C_LGRAY[1],$C_LGRAY[2]);
    $pdf->SetDrawColor($C_BORDER[0],$C_BORDER[1],$C_BORDER[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(0, $H - 7, $W, 7, 'FD');

    $pdf->SetTextColor($C_GRAY[0],$C_GRAY[1],$C_GRAY[2]);
    $pdf->SetFont('helvetica','',6);
    $pdf->SetXY(4, $H - 6);
    $pdf->Cell($W * 0.5, 5, 'Gerado em ' . date('d/m/Y H:i') . ' por ' . APP_NAME, 0, 0, 'L');
    $pdf->SetFont('helvetica','B',6);
    $pdf->Cell($W * 0.5 - 4, 5, 'Pedido #' . $o['meli_order_id'], 0, 0, 'R');

    // Borda geral
    $pdf->SetDrawColor($C_BORDER[0],$C_BORDER[1],$C_BORDER[2]);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(0.2, 0.2, $W - 0.4, $H - 0.4, 'D');

    // Marca como impresso no banco
    db_update('orders', ['pdf_printed'=>1], 'id=? AND tenant_id=?', [$o['id'], $tenantId]);
}

// Marca label_printed se pdf e zpl já impressos
foreach ($orders as $o) {
    $fresh = db_one("SELECT pdf_printed, zpl_printed FROM orders WHERE id=?", [$o['id']]);
    if ($fresh && $fresh['pdf_printed'] && $fresh['zpl_printed']) {
        db_update('orders', ['label_printed'=>1], 'id=?', [$o['id']]);
    }
}

$count    = count($orders);
$filename = $count === 1
    ? 'Etiqueta_' . $orders[0]['meli_order_id'] . '.pdf'
    : 'Etiquetas_Lote_' . date('Ymd_Hi') . '_' . $count . 'pcs.pdf';

$pdf->Output($filename, 'D');
