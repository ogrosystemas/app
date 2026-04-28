<?php
/**
 * api/relatorio_export.php
 * GET ?formato=excel|pdf&from=YYYY-MM-DD&to=YYYY-MM-DD&group=dia|produto|categoria
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();
$user = auth_user();
if (!$user) { http_response_code(401); echo 'Sessão expirada'; exit; }

$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND o.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

$formato  = in_array($_GET['formato']??'', ['excel','pdf']) ? $_GET['formato'] : 'excel';
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$groupBy  = in_array($_GET['group']??'', ['dia','produto','categoria']) ? $_GET['group'] : 'dia';

$pBase = array_merge([$tenantId], $acctP, [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);

// ── Totais ────────────────────────────────────────────────
$totais = db_one(
    "SELECT COUNT(DISTINCT o.id) as pedidos, SUM(o.total_amount) as receita,
            SUM(o.ml_fee_amount) as taxas, SUM(o.net_amount) as liquido
     FROM orders o
     WHERE o.tenant_id=?{$acctSql}
       AND o.payment_status IN ('approved','APPROVED')
       AND o.order_date BETWEEN ? AND ?",
    $pBase
);

// ── Dados por agrupamento ─────────────────────────────────
$rows = [];
$headers = [];

if ($groupBy === 'dia') {
    $headers = ['Data','Pedidos','Receita Bruta (R$)','Taxas ML (R$)','Receita Líquida (R$)','Ticket Médio (R$)'];
    $data = db_all(
        "SELECT DATE(o.order_date) as data, COUNT(DISTINCT o.id) as pedidos,
                SUM(o.total_amount) as receita, SUM(o.ml_fee_amount) as taxas, SUM(o.net_amount) as liquido
         FROM orders o
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY DATE(o.order_date) ORDER BY data ASC", $pBase
    );
    foreach ($data as $r) {
        $ticket = (int)$r['pedidos'] > 0 ? round((float)$r['receita']/(int)$r['pedidos'],2) : 0;
        $rows[] = [
            date('d/m/Y', strtotime($r['data'])),
            $r['pedidos'],
            number_format((float)$r['receita'],2,',','.'),
            number_format((float)$r['taxas'],2,',','.'),
            number_format((float)$r['liquido'],2,',','.'),
            number_format($ticket,2,',','.'),
        ];
    }
} elseif ($groupBy === 'produto') {
    $headers = ['Produto','SKU','Item ID ML','Pedidos','Unidades','Preço Médio (R$)','Receita (R$)','% do Total'];
    $data = db_all(
        "SELECT oi.title, oi.sku, oi.meli_item_id, COUNT(DISTINCT o.id) as pedidos,
                SUM(oi.quantity) as unidades, AVG(oi.unit_price) as preco_medio, SUM(oi.total_price) as receita
         FROM order_items oi JOIN orders o ON o.id=oi.order_id
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY oi.meli_item_id, oi.title, oi.sku
         ORDER BY receita DESC LIMIT 500", $pBase
    );
    $recTotal = (float)($totais['receita'] ?? 1);
    foreach ($data as $r) {
        $pct = $recTotal > 0 ? round((float)$r['receita']/$recTotal*100,1) : 0;
        $rows[] = [
            $r['title'], $r['sku']??'', $r['meli_item_id']??'',
            $r['pedidos'], $r['unidades'],
            number_format((float)$r['preco_medio'],2,',','.'),
            number_format((float)$r['receita'],2,',','.'),
            $pct.'%',
        ];
    }
} else { // categoria
    $headers = ['Categoria ML','Pedidos','Unidades','Receita (R$)','% do Total'];
    $data = db_all(
        "SELECT COALESCE(p.category_id,'Sem categoria') as categoria,
                COUNT(DISTINCT o.id) as pedidos, SUM(oi.quantity) as unidades, SUM(oi.total_price) as receita
         FROM order_items oi JOIN orders o ON o.id=oi.order_id
         LEFT JOIN products p ON p.meli_item_id=oi.meli_item_id AND p.tenant_id=o.tenant_id
         WHERE o.tenant_id=?{$acctSql}
           AND o.payment_status IN ('approved','APPROVED')
           AND o.order_date BETWEEN ? AND ?
         GROUP BY categoria ORDER BY receita DESC", $pBase
    );
    $recTotal = (float)($totais['receita'] ?? 1);
    foreach ($data as $r) {
        $pct = $recTotal > 0 ? round((float)$r['receita']/$recTotal*100,1) : 0;
        $rows[] = [$r['categoria'], $r['pedidos'], $r['unidades'], number_format((float)$r['receita'],2,',','.'), $pct.'%'];
    }
}

$titulo    = 'Relatório de Vendas — '.date('d/m/Y', strtotime($dateFrom)).' a '.date('d/m/Y', strtotime($dateTo));
$grupLabel = ['dia'=>'Por dia','produto'=>'Por produto','categoria'=>'Por categoria'][$groupBy];
$filename  = 'relatorio_vendas_'.$groupBy.'_'.str_replace('-','',$dateFrom).'_'.str_replace('-','',$dateTo);

// ═══════════════════════════════════════════
// EXCEL (formato HTML que o Excel abre)
// ═══════════════════════════════════════════
if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
    echo "<html xmlns:o='urn:schemas-microsoft-com:office:office'
               xmlns:x='urn:schemas-microsoft-com:office:excel'
               xmlns='http://www.w3.org/TR/REC-html40'>
    <head><meta charset='UTF-8'>
    <style>
      body { font-family: Arial; font-size: 11pt; }
      h1   { font-size: 14pt; font-weight: bold; color: #1a1a1a; }
      h2   { font-size: 11pt; color: #555; }
      table { border-collapse: collapse; width: 100%; margin-top: 10px; }
      th   { background: #1a4a8a; color: #fff; padding: 8px 10px; font-weight: bold; text-align: left; border: 1px solid #ccc; }
      td   { padding: 6px 10px; border: 1px solid #ddd; vertical-align: top; }
      tr:nth-child(even) td { background: #f5f8ff; }
      .total { font-weight: bold; background: #e8f0fe !important; }
      .num   { text-align: right; mso-number-format: '\#\,\#\#0\.00'; }
    </style>
    </head><body>";

    echo "<h1>".htmlspecialchars($titulo)."</h1>";
    echo "<h2>Agrupamento: {$grupLabel} | Gerado em: ".date('d/m/Y H:i')."</h2>";

    // Resumo
    echo "<table style='width:auto;margin-bottom:20px'>
        <tr><th>Total de Pedidos</th><td>".number_format((int)($totais['pedidos']??0),0,',','.')."</td></tr>
        <tr><th>Receita Bruta</th><td class='num'>R$ ".number_format((float)($totais['receita']??0),2,',','.')."</td></tr>
        <tr><th>Taxas ML</th><td class='num'>R$ ".number_format((float)($totais['taxas']??0),2,',','.')."</td></tr>
        <tr><th>Receita Líquida</th><td class='num'>R$ ".number_format((float)($totais['liquido']??0),2,',','.')."</td></tr>
    </table>";

    // Dados
    echo "<table><thead><tr>";
    foreach ($headers as $h) echo "<th>".htmlspecialchars($h)."</th>";
    echo "</tr></thead><tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $cell) echo "<td>".htmlspecialchars((string)$cell)."</td>";
        echo "</tr>";
    }
    echo "</tbody></table></body></html>";
    exit;
}

// ═══════════════════════════════════════════
// PDF via TCPDF
// ═══════════════════════════════════════════
if ($formato === 'pdf') {
    $tcpdf_path = __DIR__ . '/../lib/tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        // Fallback: HTML simples
        header('Content-Type: text/html; charset=UTF-8');
        echo '<p>TCPDF não encontrado. Use a exportação Excel.</p>';
        exit;
    }

    require_once $tcpdf_path;

    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('SAM ERP');
    $pdf->SetAuthor('SAM');
    $pdf->SetTitle($titulo);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Título
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(26, 74, 138);
    $pdf->Cell(0, 8, $titulo, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(85, 85, 85);
    $pdf->Cell(0, 5, "Agrupamento: {$grupLabel} | Gerado: ".date('d/m/Y H:i'), 0, 1, 'L');
    $pdf->Ln(3);

    // Resumo em linha
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(26, 74, 138);
    $pdf->SetTextColor(255,255,255);
    foreach (['Pedidos','Receita Bruta','Taxas ML','Receita Líquida'] as $k) {
        $pdf->Cell(60, 7, $k, 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(30,30,30);
    $pdf->SetFillColor(240,246,255);
    $pdf->Cell(60, 7, number_format((int)($totais['pedidos']??0),0,',','.'), 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'R$ '.number_format((float)($totais['receita']??0),2,',','.'), 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'R$ '.number_format((float)($totais['taxas']??0),2,',','.'), 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'R$ '.number_format((float)($totais['liquido']??0),2,',','.'), 1, 1, 'C', true);
    $pdf->Ln(4);

    // Cabeçalho tabela
    $colWidths = [];
    $nCols = count($headers);
    $pageW = $pdf->getPageWidth() - 24;
    $colWidths = array_fill(0, $nCols, round($pageW / $nCols));

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(26, 74, 138);
    $pdf->SetTextColor(255,255,255);
    foreach ($headers as $i => $h) {
        $pdf->Cell($colWidths[$i], 6, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Linhas
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(30,30,30);
    $fill = false;
    foreach ($rows as $row) {
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 246 : 255, 255);
        foreach ($row as $i => $cell) {
            $pdf->Cell($colWidths[$i], 5, (string)$cell, 1, 0, 'L', $fill);
        }
        $pdf->Ln();
        $fill = !$fill;
    }

    $pdf->Output($filename.'.pdf', 'D');
    exit;
}
