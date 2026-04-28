<?php
/**
 * api/pdf_estoque.php
 * Relatorio de estoque em PDF via TCPDF.
 * GET: status=all|critico|zerado|ok  search=  order=
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo '<h2>Sessao expirada. Feche esta aba e recarregue o sistema.</h2>';
    exit;
}

require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';

$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? 'all';
$orderBy = $_GET['order']  ?? 'title';

$orderMap = [
    'title'    => 'title ASC',
    'qty_asc'  => 'stock_quantity ASC',
    'qty_desc' => 'stock_quantity DESC',
    'value'    => '(cost_price * stock_quantity) DESC',
    'critico'  => '(stock_quantity - stock_min) ASC',
];
$orderSql = $orderMap[$orderBy] ?? 'title ASC';

$where  = "WHERE tenant_id=?{$acctSql} AND ml_status != 'CLOSED'";
$params = array_merge([$tenantId], (array)$acctP);

if ($search !== '') {
    $where   .= " AND (title LIKE ? OR sku LIKE ? OR meli_item_id LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$allProducts = db_all(
    "SELECT id, meli_item_id, sku, title, price, cost_price,
            stock_quantity, stock_min, ml_status
     FROM products {$where} ORDER BY {$orderSql}",
    $params
);

$products = array_values(array_filter($allProducts, function($p) use ($status) {
    $qty = (int)$p['stock_quantity'];
    $min = (int)$p['stock_min'];
    return match($status) {
        'critico' => $qty > 0 && $qty <= $min,
        'zerado'  => $qty <= 0,
        'ok'      => $qty > $min,
        default   => true,
    };
}));

$total      = count($allProducts);
$criticos   = count(array_filter($allProducts, fn($p) => $p['stock_quantity'] > 0 && $p['stock_quantity'] <= $p['stock_min']));
$zerados    = count(array_filter($allProducts, fn($p) => $p['stock_quantity'] <= 0));
$valorCusto = array_sum(array_map(fn($p) => (float)$p['cost_price'] * max(0,(int)$p['stock_quantity']), $allProducts));
$valorVenda = array_sum(array_map(fn($p) => (float)$p['price']      * max(0,(int)$p['stock_quantity']), $allProducts));

$acctNick = $acctId
    ? (db_one("SELECT nickname FROM meli_accounts WHERE id=?", [$acctId])['nickname'] ?? '')
    : '';

function brl(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}

// Cores
define('CP2',  [30,  80, 162]);
define('CDK2', [20,  20,  30]);
define('CGN2', [22, 101,  52]);
define('CRD2', [153, 27,  27]);
define('CAM2', [180, 83,   9]);
define('CGR2', [100,100,100]);
define('CLG2', [244,246,250]);
define('CBD2', [210,215,225]);

$filename = 'Estoque_' . date('Y-m-d_Hi') . ($acctNick ? "_$acctNick" : '') . '.pdf';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor($user['name']);
$pdf->SetTitle('Relatorio de Estoque');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(12.7, 12.7, 12.7);
$pdf->SetAutoPageBreak(true, 28); // 12.7mm margem + 15mm rodapé + folga
$pdf->AddPage();

$pageW = $pdf->getPageWidth() - 25.4; // 12.7 * 2

// Altura limite para quebra manual de página (antes do rodapé)
$pageBreakY = $pdf->getPageHeight() - 28;

// ─────────────────────────────────────────────────────────
// CABECALHO
// ─────────────────────────────────────────────────────────
$pdf->SetFillColor(CP2[0],CP2[1],CP2[2]);
$pdf->Rect(0, 0, $pdf->getPageWidth(), 15, 'F');

$pdf->SetTextColor(255,255,255);
$pdf->SetFont('helvetica','B',13);
$pdf->SetXY(12, 4);
$pdf->Cell($pageW * 0.5, 7, APP_NAME, 0, 0, 'L');

$pdf->SetFont('helvetica','',8);
$pdf->SetXY($pageW * 0.4 + 12, 4);
$pdf->Cell($pageW * 0.6, 7, 'RELATORIO DE ESTOQUE  |  ' . date('d/m/Y H:i'), 0, 0, 'R');

$pdf->SetDrawColor(255,230,0);
$pdf->SetLineWidth(0.8);
$pdf->Line(0, 15, $pdf->getPageWidth(), 15);

// Info
$pdf->SetY(19);
$pdf->SetTextColor(CDK2[0],CDK2[1],CDK2[2]);
$pdf->SetFont('helvetica','B',13);
$pdf->SetX(12);
$pdf->Cell($pageW * 0.6, 6, 'Gestao de Estoque', 0, 0, 'L');

$pdf->SetFont('helvetica','',7.5);
$pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
$pdf->SetXY($pageW * 0.45 + 12, 19);
$meta = 'Gerado: ' . date('d/m/Y') . ' as ' . date('H:i') . '  |  Usuario: ' . $user['name'];
if ($acctNick) $meta .= '  |  Conta: ' . $acctNick;
$pdf->Cell($pageW * 0.55, 5, $meta, 0, 0, 'R');

$filterLabels = ['all'=>'Todos','ok'=>'OK','critico'=>'Critico','zerado'=>'Zerado'];
$pdf->SetXY($pageW * 0.45 + 12, 24);
$pdf->Cell($pageW * 0.55, 5, 'Filtro: ' . ($filterLabels[$status] ?? 'Todos') . '  |  ' . count($products) . ' produtos exibidos de ' . $total . ' total', 0, 0, 'R');

$pdf->SetDrawColor(CBD2[0],CBD2[1],CBD2[2]);
$pdf->SetLineWidth(0.3);
$pdf->Line(12, 32, 12 + $pageW, 32);
$pdf->SetY(35);

// ─────────────────────────────────────────────────────────
// KPIS
// ─────────────────────────────────────────────────────────
$kpiDefs = [
    ['Total produtos',   $total,      CDK2,  'ativos no ML'],
    ['Estoque critico',  $criticos,   $criticos>0?CAM2:CGN2, 'abaixo do minimo'],
    ['Sem estoque',      $zerados,    $zerados>0?CRD2:CGN2,  'zerados'],
    ['Valor (custo)',     $valorCusto, CGN2,  'estoque atual'],
    ['Pot. de venda',    $valorVenda, CP2,   'pelo preco ML'],
];
$kW = $pageW / 5;
$kY = $pdf->GetY();

foreach ($kpiDefs as $i => [$lbl, $val, $col, $sub]) {
    $x = 12 + $i * $kW;
    $pdf->SetFillColor(CLG2[0],CLG2[1],CLG2[2]);
    $pdf->SetDrawColor(CBD2[0],CBD2[1],CBD2[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->RoundedRect($x, $kY, $kW - 1.5, 20, 2, '1111', 'FD');
    $pdf->SetFillColor($col[0],$col[1],$col[2]);
    $pdf->SetLineWidth(1.5);
    $pdf->SetDrawColor($col[0],$col[1],$col[2]);
    $pdf->Line($x + 3, $kY, $x + $kW - 4.5, $kY);
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',6.5);
    $pdf->SetXY($x + 2, $kY + 2.5);
    $pdf->Cell($kW - 4, 3.5, $lbl, 0, 0, 'L');
    $pdf->SetTextColor($col[0],$col[1],$col[2]);
    $pdf->SetFont('helvetica','B', is_float($val)?9:11);
    $pdf->SetXY($x + 2, $kY + 7);
    $pdf->Cell($kW - 4, 6, is_float($val) ? brl($val) : (string)$val, 0, 0, 'L');
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',6);
    $pdf->SetXY($x + 2, $kY + 13.5);
    $pdf->Cell($kW - 4, 3.5, $sub, 0, 0, 'L');
}
$pdf->SetY($kY + 24);

// ─────────────────────────────────────────────────────────
// TABELA DE PRODUTOS
// ─────────────────────────────────────────────────────────
$cols = [51, 24, 15, 13, 15, 22, 22, 22]; // total 184mm — A4 retrato (184.6mm útil)
$hdrs = ['Produto', 'SKU / MLB', 'Qtd atual', 'Minimo', 'Status', 'Custo unit.', 'Preco ML', 'Valor total'];
$algs = ['L','L','C','C','C','R','R','R'];

$printTableHeader = function() use ($pdf, $cols, $hdrs, $algs) {
    $pdf->SetFillColor(CP2[0],CP2[1],CP2[2]);
    $pdf->SetDrawColor(CP2[0],CP2[1],CP2[2]);
    $pdf->SetFont('helvetica','B',7.5);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetX(12);
    foreach ($cols as $ci => $cw) {
        $pdf->Cell($cw, 6.5, $hdrs[$ci], 0, 0, $algs[$ci], true);
    }
    $pdf->Ln();
};

$printTableHeader();

$odd = true;
$totalValorFiltrado = 0;

foreach ($products as $p) {
    if ($pdf->GetY() > $pageBreakY) {
        $pdf->AddPage();
        $printTableHeader();
    }

    $qty   = (int)$p['stock_quantity'];
    $min   = (int)$p['stock_min'];
    $custo = (float)$p['cost_price'];
    $preco = (float)$p['price'];
    $valor = $custo * max(0, $qty);
    $totalValorFiltrado += $valor;

    if ($qty <= 0)        { $stCol=CRD2; $stLbl='Zerado';  $qCol=CRD2; }
    elseif ($qty <= $min) { $stCol=CAM2; $stLbl='Critico'; $qCol=CAM2; }
    else                  { $stCol=CGN2; $stLbl='OK';      $qCol=CGN2; }

    $bg = $odd ? [255,255,255] : [249,250,253];
    $pdf->SetFillColor($bg[0],$bg[1],$bg[2]);
    $pdf->SetDrawColor(CBD2[0],CBD2[1],CBD2[2]);
    $pdf->SetLineWidth(0.2);
    $pdf->SetX(12);

    // Produto
    $pdf->SetTextColor(CDK2[0],CDK2[1],CDK2[2]);
    $pdf->SetFont('helvetica','B',7.5);
    $pdf->Cell($cols[0], 6, mb_substr($p['title'], 0, 38), 0, 0, 'L', true);

    // SKU/MLB
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',6.5);
    $sku = mb_substr($p['sku'], 0, 14) . "\n" . mb_substr($p['meli_item_id'], 0, 10);
    $pdf->MultiCell($cols[1], 3, $sku, 0, 'L', true, 0);

    // Qtd
    $pdf->SetTextColor($qCol[0],$qCol[1],$qCol[2]);
    $pdf->SetFont('helvetica','B',10);
    $pdf->Cell($cols[2], 6, (string)$qty, 0, 0, 'C', true);

    // Min
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',8);
    $pdf->Cell($cols[3], 6, (string)$min, 0, 0, 'C', true);

    // Status badge
    $pdf->SetTextColor($stCol[0],$stCol[1],$stCol[2]);
    $pdf->SetFont('helvetica','B',7.5);
    $pdf->Cell($cols[4], 6, $stLbl, 0, 0, 'C', true);

    // Custo
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',7.5);
    $pdf->Cell($cols[5], 6, brl($custo), 0, 0, 'R', true);

    // Preco ML
    $pdf->Cell($cols[6], 6, brl($preco), 0, 0, 'R', true);

    // Valor total
    $pdf->SetTextColor(CDK2[0],CDK2[1],CDK2[2]);
    $pdf->SetFont('helvetica','B',8);
    $pdf->Cell($cols[7], 6, brl($valor), 0, 1, 'R', true);

    $odd = !$odd;
}

// Linha de total
$sumW = array_sum(array_slice($cols, 0, 7));
$pdf->SetFillColor(CLG2[0],CLG2[1],CLG2[2]);
$pdf->SetDrawColor(CBD2[0],CBD2[1],CBD2[2]);
$pdf->SetLineWidth(0.4);
$pdf->SetX(12);
$pdf->SetFont('helvetica','B',8);
$pdf->SetTextColor(CDK2[0],CDK2[1],CDK2[2]);
$pdf->Cell($sumW, 7, 'Total dos ' . count($products) . ' produto(s) exibidos', 'T', 0, 'R', true);
$pdf->SetTextColor(CGN2[0],CGN2[1],CGN2[2]);
$pdf->Cell($cols[7], 7, brl($totalValorFiltrado), 'T', 1, 'R', true);

// ─────────────────────────────────────────────────────────
// RODAPE
// ─────────────────────────────────────────────────────────
$totalPages = $pdf->getNumPages();
for ($pg = 1; $pg <= $totalPages; $pg++) {
    $pdf->setPage($pg);
    $pdf->SetAutoPageBreak(false); // impede criar nova página ao desenhar rodapé
    $fY = $pdf->getPageHeight() - 12.7;
    $pdf->SetDrawColor(CBD2[0],CBD2[1],CBD2[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12.7, $fY - 6, 12.7 + $pageW, $fY - 6);
    $pdf->SetTextColor(CGR2[0],CGR2[1],CGR2[2]);
    $pdf->SetFont('helvetica','',6.5);
    $pdf->SetXY(12.7, $fY - 5);
    $pdf->Cell($pageW * 0.7, 5, APP_NAME . ' - Relatorio de Estoque gerado automaticamente', 0, 0, 'L');
    $pdf->Cell($pageW * 0.3, 5, "Pagina $pg de $totalPages  |  " . date('d/m/Y H:i'), 0, 0, 'R');
}

$pdf->Output($filename, 'D');
