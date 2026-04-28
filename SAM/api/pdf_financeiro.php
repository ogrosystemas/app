<?php
/**
 * api/pdf_financeiro.php
 * Relatorio financeiro em PDF via TCPDF.
 * GET: month=YYYY-MM  tipo=dre|extrato|completo
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
$acctSqlFe= $acctId ? " AND fe.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$tipo  = in_array($_GET['tipo'] ?? '', ['dre','extrato','completo']) ? $_GET['tipo'] : 'completo';

$inicio = $month . '-01';
$fim    = date('Y-m-t', strtotime($inicio));

$meses    = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Marco','04'=>'Abril','05'=>'Maio','06'=>'Junho',
             '07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
[, $mm]   = explode('-', $month);
$mesLabel = ($meses[$mm] ?? $mm) . ' de ' . substr($month, 0, 4);

$acctNick = $acctId
    ? (db_one("SELECT nickname FROM meli_accounts WHERE id=?", [$acctId])['nickname'] ?? '')
    : '';

$txAcctSql = $acctId ? " AND meli_account_id=?" : " AND 1=0";
$txAcctP   = $acctId ? [$acctId] : [];

// ── Dados ────────────────────────────────────────────────
$kpisRow = db_one(
    "SELECT
        COALESCE(SUM(CASE WHEN direction='CREDIT' AND status='PAID' THEN amount ELSE 0 END),0) as receitas,
        COALESCE(SUM(CASE WHEN direction='DEBIT'  AND status='PAID' THEN amount ELSE 0 END),0) as despesas,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='CREDIT' THEN amount ELSE 0 END),0) as a_receber,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='DEBIT'  THEN amount ELSE 0 END),0) as a_pagar
     FROM financial_entries WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?{$acctSql}",
    array_merge([$tenantId, $month], (array)$acctP)
);
$vendasML = db_one(
    "SELECT COALESCE(SUM(amount),0) as total FROM transactions
     WHERE tenant_id=? AND direction='CREDIT' AND DATE_FORMAT(reference_date,'%Y-%m')=?{$txAcctSql}",
    array_merge([$tenantId, $month], $txAcctP)
);
$receitas = (float)$kpisRow['receitas'] + (float)$vendasML['total'];
$despesas = (float)$kpisRow['despesas'];
$saldo    = $receitas - $despesas;
$aReceber = (float)$kpisRow['a_receber'];
$aPagar   = (float)$kpisRow['a_pagar'];
$margem   = $receitas > 0 ? round($saldo / $receitas * 100, 1) : 0;

$dreRaw = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM transactions WHERE tenant_id=? AND DATE_FORMAT(reference_date,'%Y-%m')=?
     AND dre_category IS NOT NULL{$txAcctSql} GROUP BY dre_category",
    array_merge([$tenantId, $month], $txAcctP)
);
$dreEntriesRaw = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM financial_entries WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?
     AND dre_category IS NOT NULL AND status='PAID'{$acctSql} GROUP BY dre_category",
    array_merge([$tenantId, $month], (array)$acctP)
);
$dreMap = [];
foreach (array_merge($dreRaw, $dreEntriesRaw) as $r) {
    $dreMap[$r['dre_category']] = ($dreMap[$r['dre_category']] ?? 0) + (float)$r['total'];
}
$dreMap['RECEITA_LIQUIDA'] = ($dreMap['RECEITA_BRUTA'] ?? $receitas) - abs($dreMap['DEDUCOES'] ?? 0);
$dreMap['LUCRO_BRUTO']     = $dreMap['RECEITA_LIQUIDA'] - abs($dreMap['CMV'] ?? 0);
$dreMap['EBITDA']          = $dreMap['LUCRO_BRUTO'] - abs($dreMap['DESPESAS_OPERACIONAIS'] ?? 0) - abs($dreMap['DESPESAS_FINANCEIRAS'] ?? 0);
$dreMap['LUCRO_LIQUIDO']   = $dreMap['EBITDA'] + abs($dreMap['OUTRAS_RECEITAS'] ?? 0) - abs($dreMap['OUTRAS_DESPESAS'] ?? 0);
$margemLiq = ($dreMap['RECEITA_LIQUIDA'] ?? 0) > 0
    ? round($dreMap['LUCRO_LIQUIDO'] / $dreMap['RECEITA_LIQUIDA'] * 100, 1) : 0;

$bankAccounts = db_all(
    "SELECT * FROM bank_accounts WHERE tenant_id=? AND is_active=1{$acctSql} ORDER BY name",
    array_merge([$tenantId], (array)$acctP)
);
$totalBancos = array_sum(array_column($bankAccounts, 'balance'));

$entries = db_all(
    "SELECT fe.entry_date, fe.description, fe.direction, fe.amount, fe.status,
            fe.dre_category, b.name as bank_name, c.name as coa_name
     FROM financial_entries fe
     LEFT JOIN bank_accounts b     ON b.id = fe.account_id
     LEFT JOIN chart_of_accounts c ON c.id = fe.coa_id
     WHERE fe.tenant_id=? AND DATE_FORMAT(fe.entry_date,'%Y-%m')=?{$acctSqlFe}
     ORDER BY fe.entry_date ASC, fe.created_at ASC",
    array_merge([$tenantId, $month], (array)$acctP)
);

// ── Helpers ──────────────────────────────────────────────
function brl(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
function stlbl(string $s): string {
    return match($s) {
        'PAID'      => 'Pago',
        'PENDING'   => 'Pendente',
        'OVERDUE'   => 'Vencido',
        'CANCELLED' => 'Cancelado',
        default     => $s
    };
}

// ── Cores ─────────────────────────────────────────────────
define('CP',  [30,  80, 162]);   // azul primario
define('CDK', [20,  20,  30]);   // dark
define('CGN', [22, 101,  52]);   // verde
define('CRD', [153, 27,  27]);   // vermelho
define('CAM', [180, 83,   9]);   // amber
define('CGR', [100,100,100]);    // cinza
define('CLG', [244,246,250]);    // fundo claro
define('CBD', [210,215,225]);    // borda

// ── TCPDF ─────────────────────────────────────────────────
$tipoLabel = match($tipo) { 'dre'=>'DRE', 'extrato'=>'Extrato', default=>'Completo' };
$filename  = "Financeiro_{$tipoLabel}_{$month}" . ($acctNick ? "_{$acctNick}" : '') . '.pdf';

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(APP_NAME);
$pdf->SetAuthor($user['name']);
$pdf->SetTitle("Relatorio Financeiro - $mesLabel");
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(12.7, 12.7, 12.7);
$pdf->SetAutoPageBreak(true, 28);
$pdf->AddPage();

$pageW = $pdf->getPageWidth() - 25.4;

// Limite Y para quebra manual de página (antes do rodapé)
$pageBreakY = $pdf->getPageHeight() - 28;

// ─────────────────────────────────────────────────────────
// CABECALHO
// ─────────────────────────────────────────────────────────
// Faixa azul topo
$pdf->SetFillColor(CP[0],CP[1],CP[2]);
$pdf->Rect(0, 0, $pdf->getPageWidth(), 16, 'F');

$pdf->SetTextColor(255,255,255);
$pdf->SetFont('helvetica','B',13);
$pdf->SetXY(12.7, 4);
$pdf->Cell($pageW * 0.55, 8, APP_NAME, 0, 0, 'L');

$pdf->SetFont('helvetica','',8);
$pdf->SetXY($pageW * 0.45 + 12.7, 4);
$pdf->Cell($pageW * 0.55, 8, 'RELATORIO FINANCEIRO  |  ' . strtoupper($tipoLabel), 0, 0, 'R');

// Linha dourada decorativa
$pdf->SetDrawColor(255,230,0);
$pdf->SetLineWidth(0.8);
$pdf->Line(0, 16, $pdf->getPageWidth(), 16);

// Info principal — mesmo padrão do estoque
$pdf->SetY(20);
$pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
$pdf->SetFont('helvetica','B',14);
$pdf->SetX(12.7);
$pdf->Cell($pageW * 0.5, 7, "Financeiro - $mesLabel", 0, 0, 'L');

// Meta direita — linha 1: gerado + usuario + conta (igual ao estoque)
$pdf->SetFont('helvetica','',8);
$pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
$meta1 = "Gerado: " . date('d/m/Y') . " as " . date('H:i') . "  |  Usuario: " . $user['name'];
if ($acctNick) $meta1 .= "  |  Conta ML: $acctNick";
$pdf->SetXY(12.7, 20);
$pdf->Cell($pageW, 5, $meta1, 0, 1, 'R');

// Meta direita — linha 2: periodo + filtro
$meta2 = "Periodo: " . date('d/m/Y', strtotime($inicio)) . " a " . date('d/m/Y', strtotime($fim)) . "  |  " . strtoupper($tipoLabel);
$pdf->SetX(12.7);
$pdf->Cell($pageW, 5, $meta2, 0, 1, 'R');

$pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
$pdf->SetLineWidth(0.3);
$pdf->Line(12.7, $pdf->GetY() + 1, 12.7 + $pageW, $pdf->GetY() + 1);
$pdf->SetY($pdf->GetY() + 4);
$pdf->SetY(41);

// ─────────────────────────────────────────────────────────
// KPIS
// ─────────────────────────────────────────────────────────
$kpiDefs = [
    ['Receitas pagas',   $receitas, CGN,   'incl. vendas ML'],
    ['Despesas pagas',   $despesas, CRD,   'lancamentos manuais'],
    ['Saldo do periodo', $saldo,    $saldo>=0?CGN:CRD, 'margem '.number_format($margem,1,',','.').'%'],
    ['A receber',        $aReceber, CP,    'pendente recebimento'],
    ['A pagar',          $aPagar,   CAM,   'contas em aberto'],
];

$kW = $pageW / 5;
$kY = $pdf->GetY();

foreach ($kpiDefs as $i => [$lbl, $val, $col, $sub]) {
    $x = 14 + $i * $kW;
    // Card fundo
    $pdf->SetFillColor(CLG[0],CLG[1],CLG[2]);
    $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->RoundedRect($x, $kY, $kW - 1.5, 22, 2.5, '1111', 'FD');
    // Barra cor no topo
    $pdf->SetFillColor($col[0],$col[1],$col[2]);
    $pdf->SetDrawColor($col[0],$col[1],$col[2]);
    $pdf->SetLineWidth(1.5);
    $pdf->Line($x + 3, $kY, $x + $kW - 4.5, $kY);
    // Label
    $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
    $pdf->SetFont('helvetica','',6.5);
    $pdf->SetXY($x + 2, $kY + 2.5);
    $pdf->Cell($kW - 4, 3.5, $lbl, 0, 0, 'L');
    // Valor
    $pdf->SetTextColor($col[0],$col[1],$col[2]);
    $pdf->SetFont('helvetica','B',9.5);
    $pdf->SetXY($x + 2, $kY + 7);
    $pdf->Cell($kW - 4, 6, brl($val), 0, 0, 'L');
    // Sub
    $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
    $pdf->SetFont('helvetica','',6);
    $pdf->SetXY($x + 2, $kY + 14);
    $pdf->Cell($kW - 4, 3.5, $sub, 0, 0, 'L');
}
$pdf->SetY($kY + 26);

// ─────────────────────────────────────────────────────────
// CONTAS BANCARIAS
// ─────────────────────────────────────────────────────────
if (!empty($bankAccounts)) {
    $pdf->SetFont('helvetica','B',7.5);
    $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
    $pdf->SetX(12.7);
    $pdf->Cell($pageW, 5, 'Contas e Caixas  |  Total: ' . brl($totalBancos), 0, 1, 'L');
    $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12.7, $pdf->GetY(), 12.7 + $pageW, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 2);

    $bCount = min(count($bankAccounts), 4);
    $bW     = $pageW / $bCount;
    $bY     = $pdf->GetY();
    $tColors = ['CORRENTE'=>CP,'POUPANCA'=>CGN,'CAIXA'=>CAM,'CARTAO_CREDITO'=>CRD,'INVESTIMENTO'=>[124,58,237]];

    foreach (array_slice($bankAccounts, 0, 4) as $i => $b) {
        $bx  = 14 + $i * $bW;
        $col = $tColors[$b['type']] ?? CGR;
        $bal = (float)$b['balance'];
        $pdf->SetFillColor(CLG[0],CLG[1],CLG[2]);
        $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
        $pdf->RoundedRect($bx, $bY, $bW - 1.5, 17, 2, '1111', 'FD');
        // Borda esquerda
        $pdf->SetFillColor($col[0],$col[1],$col[2]);
        $pdf->SetLineWidth(2);
        $pdf->SetDrawColor($col[0],$col[1],$col[2]);
        $pdf->Line($bx, $bY + 2, $bx, $bY + 15);
        // Nome
        $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
        $pdf->SetFont('helvetica','',6.5);
        $pdf->SetXY($bx + 5, $bY + 2);
        $pdf->Cell($bW - 7, 3.5, mb_substr($b['name'], 0, 24), 0, 0, 'L');
        // Saldo
        $pdf->SetTextColor($bal >= 0 ? CDK[0] : CRD[0], $bal >= 0 ? CDK[1] : CRD[1], $bal >= 0 ? CDK[2] : CRD[2]);
        $pdf->SetFont('helvetica','B',10);
        $pdf->SetXY($bx + 5, $bY + 6.5);
        $pdf->Cell($bW - 7, 5, brl($bal), 0, 0, 'L');
        // Banco
        $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
        $pdf->SetFont('helvetica','',6);
        $pdf->SetXY($bx + 5, $bY + 12.5);
        $pdf->Cell($bW - 7, 3, $b['bank_name'] ?? '', 0, 0, 'L');
    }
    $pdf->SetY($bY + 21);
}

// ─────────────────────────────────────────────────────────
// DRE
// ─────────────────────────────────────────────────────────
if ($tipo === 'dre' || $tipo === 'completo') {
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
    $pdf->SetX(12.7);
    $pdf->Cell($pageW, 5, 'DRE - Demonstracao do Resultado do Exercicio', 0, 1, 'L');
    $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12.7, $pdf->GetY(), 12.7 + $pageW, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 2);

    $cL = $pageW * 0.72;
    $cR = $pageW * 0.28;

    $dreRows = [
        ['group',     'RECEITA'],
        ['item',      'Receita Bruta',           $dreMap['RECEITA_BRUTA']  ?? $receitas, true],
        ['item',      '(-) Deducoes',            -abs($dreMap['DEDUCOES']  ?? 0),        false],
        ['total',     'Receita Liquida',          $dreMap['RECEITA_LIQUIDA']?? $receitas],
        ['group',     'CUSTOS'],
        ['item',      '(-) CMV / Taxa ML / Frete',-abs($dreMap['CMV']      ?? 0),        false],
        ['total',     'Lucro Bruto',              $dreMap['LUCRO_BRUTO']   ?? 0],
        ['group',     'DESPESAS'],
        ['item',      '(-) Desp. Operacionais',  -abs($dreMap['DESPESAS_OPERACIONAIS'] ?? 0), false],
        ['item',      '(-) Desp. Financeiras',   -abs($dreMap['DESPESAS_FINANCEIRAS']  ?? 0), false],
        ['total',     'EBITDA',                   $dreMap['EBITDA']        ?? 0],
        ['group',     'RESULTADO FINAL'],
        ['item',      '(+) Outras Receitas',      abs($dreMap['OUTRAS_RECEITAS'] ?? 0),  true],
        ['item',      '(-) Outras Despesas',     -abs($dreMap['OUTRAS_DESPESAS'] ?? 0),  false],
        ['highlight', 'LUCRO LIQUIDO',             $dreMap['LUCRO_LIQUIDO'] ?? 0],
        ['margin',    'Margem liquida: ' . number_format($margemLiq, 1, ',', '.') . '%'],
    ];

    foreach ($dreRows as $row) {
        if ($pdf->GetY() > $pageBreakY) $pdf->AddPage();
        $type = $row[0];

        if ($type === 'group') {
            $pdf->SetFillColor(CLG[0],CLG[1],CLG[2]);
            $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
            $pdf->SetX(12.7);
            $pdf->SetFont('helvetica','B',7);
            $pdf->SetTextColor(CP[0],CP[1],CP[2]);
            $pdf->Cell($pageW, 5, '  ' . $row[1], 'B', 1, 'L', true);

        } elseif ($type === 'item') {
            [, $label, $valor, $pos] = $row;
            if ($valor == 0) continue;
            $pdf->SetX(18);
            $pdf->SetFont('helvetica','',8);
            $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
            $pdf->Cell($cL - 4, 5.5, $label, 0, 0, 'L');
            $col = $pos === true ? CGN : ($pos === false ? CRD : CDK);
            $pdf->SetTextColor($col[0],$col[1],$col[2]);
            $pdf->Cell($cR, 5.5, brl($valor), 0, 1, 'R');

        } elseif ($type === 'total') {
            [, $label, $valor] = $row;
            $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(12.7, $pdf->GetY(), 12.7 + $pageW, $pdf->GetY());
            $pdf->SetX(12.7);
            $pdf->SetFont('helvetica','B',8.5);
            $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
            $pdf->Cell($cL, 6, $label, 0, 0, 'L');
            $col = $valor >= 0 ? CGN : CRD;
            $pdf->SetTextColor($col[0],$col[1],$col[2]);
            $pdf->Cell($cR, 6, brl($valor), 0, 1, 'R');

        } elseif ($type === 'highlight') {
            [, $label, $valor] = $row;
            $pdf->SetFillColor(235,245,255);
            $pdf->SetDrawColor(CP[0],CP[1],CP[2]);
            $pdf->SetLineWidth(0.6);
            $pdf->Rect(12.7, $pdf->GetY(), $pageW, 9, 'FD');
            $pdf->SetX(16);
            $pdf->SetFont('helvetica','B',10);
            $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
            $pdf->Cell($cL - 2, 9, $label, 0, 0, 'L');
            $col = $valor >= 0 ? CGN : CRD;
            $pdf->SetTextColor($col[0],$col[1],$col[2]);
            $pdf->Cell($cR - 2, 9, brl($valor), 0, 1, 'R');

        } elseif ($type === 'margin') {
            $pdf->SetFont('helvetica','I',7.5);
            $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
            $pdf->SetX(12.7);
            $pdf->Cell($pageW, 5, $row[1], 0, 1, 'R');
        }
    }
    $pdf->SetY($pdf->GetY() + 5);
}

// ─────────────────────────────────────────────────────────
// EXTRATO
// ─────────────────────────────────────────────────────────
if ($tipo === 'extrato' || $tipo === 'completo') {
    if ($tipo === 'completo') $pdf->AddPage();

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
    $pdf->SetX(12.7);
    $pdf->Cell($pageW, 5, "Extrato de Lancamentos - $mesLabel", 0, 1, 'L');
    $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12.7, $pdf->GetY(), 12.7 + $pageW, $pdf->GetY());
    $pdf->SetY($pdf->GetY() + 3);

    if (!empty($entries)) {
        $totalC = array_sum(array_map(fn($e)=>$e['direction']==='CREDIT'?(float)$e['amount']:0, $entries));
        $totalD = array_sum(array_map(fn($e)=>$e['direction']==='DEBIT' ?(float)$e['amount']:0, $entries));
        $saldoE = $totalC - $totalD;

        // Box totais
        $tY = $pdf->GetY();
        $pdf->SetFillColor(CLG[0],CLG[1],CLG[2]);
        $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
        $pdf->SetLineWidth(0.3);
        $pdf->RoundedRect(12.7, $tY, $pageW, 14, 2, '1111', 'FD');
        $tW = $pageW / 4;
        foreach ([
            ['Total creditos',  $totalC, CGN],
            ['Total debitos',   $totalD, CRD],
            ['Saldo',           $saldoE, $saldoE>=0?CGN:CRD],
            ['Lancamentos',     count($entries), CDK],
        ] as $i => [$lbl, $val, $col]) {
            $tx = 14 + $i * $tW;
            $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
            $pdf->SetFont('helvetica','',6.5);
            $pdf->SetXY($tx + 3, $tY + 2);
            $pdf->Cell($tW - 4, 4, $lbl, 0, 0, 'L');
            $pdf->SetTextColor($col[0],$col[1],$col[2]);
            $pdf->SetFont('helvetica','B',9);
            $pdf->SetXY($tx + 3, $tY + 6.5);
            $pdf->Cell($tW - 4, 5, is_float($val) ? brl($val) : (string)$val, 0, 0, 'L');
        }
        $pdf->SetY($tY + 17);

        // Colunas da tabela
        $cols = [14, 58, 38, 30, 22, 24]; // larguras
        $hdrs = ['Data', 'Descricao', 'Categoria', 'Conta', 'Status', 'Valor'];
        $algs = ['C','L','L','L','C','R'];

        $printHeader = function() use ($pdf, $cols, $hdrs, $algs, $pageW) {
            $pdf->SetFillColor(CP[0],CP[1],CP[2]);
            $pdf->SetDrawColor(CP[0],CP[1],CP[2]);
            $pdf->SetFont('helvetica','B',7.5);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetX(12.7);
            foreach ($cols as $ci => $cw) {
                $pdf->Cell($cw, 6.5, $hdrs[$ci], 0, 0, $algs[$ci], true);
            }
            $pdf->Ln();
        };
        $printHeader();

        $odd = true;
        foreach ($entries as $e) {
            if ($pdf->GetY() > $pageBreakY) {
                $pdf->AddPage();
                $printHeader();
            }
            $isCredit = $e['direction'] === 'CREDIT';
            $bg = $odd ? [255,255,255] : [249,250,253];
            $pdf->SetFillColor($bg[0],$bg[1],$bg[2]);
            $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
            $pdf->SetLineWidth(0.2);
            $pdf->SetX(12.7);

            $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
            $pdf->SetFont('helvetica','',7.5);
            $pdf->Cell($cols[0], 5.5, date('d/m', strtotime($e['entry_date'])), 0, 0, 'C', true);

            $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
            $pdf->Cell($cols[1], 5.5, mb_substr($e['description'],0,40), 0, 0, 'L', true);

            $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
            $pdf->SetFont('helvetica','',7);
            $pdf->Cell($cols[2], 5.5, mb_substr($e['coa_name'] ?? ($e['dre_category'] ?? '-'), 0, 24), 0, 0, 'L', true);
            $pdf->Cell($cols[3], 5.5, mb_substr($e['bank_name'] ?? '-', 0, 18), 0, 0, 'L', true);

            $stCol = match($e['status']) {
                'PAID'    => CGN, 'PENDING' => CAM,
                'OVERDUE' => CRD, default   => CGR,
            };
            $pdf->SetTextColor($stCol[0],$stCol[1],$stCol[2]);
            $pdf->SetFont('helvetica','B',7);
            $pdf->Cell($cols[4], 5.5, stlbl($e['status']), 0, 0, 'C', true);

            $col = $isCredit ? CGN : CRD;
            $pdf->SetTextColor($col[0],$col[1],$col[2]);
            $pdf->SetFont('helvetica','B',7.5);
            $pdf->Cell($cols[5], 5.5, ($isCredit?'+':'-').' '.brl((float)$e['amount']), 0, 1, 'R', true);
            $odd = !$odd;
        }

        // Total final
        $sumW = array_sum(array_slice($cols, 0, 5));
        $pdf->SetFillColor(CLG[0],CLG[1],CLG[2]);
        $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
        $pdf->SetLineWidth(0.4);
        $pdf->SetX(12.7);
        $pdf->SetFont('helvetica','B',8);
        $pdf->SetTextColor(CDK[0],CDK[1],CDK[2]);
        $pdf->Cell($sumW, 6.5, 'Total de ' . count($entries) . ' lancamento(s)', 'T', 0, 'R', true);
        $col = $saldoE >= 0 ? CGN : CRD;
        $pdf->SetTextColor($col[0],$col[1],$col[2]);
        $pdf->Cell($cols[5], 6.5, brl($saldoE), 'T', 1, 'R', true);

    } else {
        $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
        $pdf->SetFont('helvetica','I',9);
        $pdf->SetX(12.7);
        $pdf->Cell($pageW, 10, 'Nenhum lancamento no periodo.', 0, 1, 'C');
    }
}

// ─────────────────────────────────────────────────────────
// RODAPE EM TODAS AS PAGINAS
// ─────────────────────────────────────────────────────────
$totalPages = $pdf->getNumPages();
for ($p = 1; $p <= $totalPages; $p++) {
    $pdf->setPage($p);
    $pdf->SetAutoPageBreak(false); // impede criar nova página ao desenhar rodapé
    $fY = $pdf->getPageHeight() - 12.7;
    $pdf->SetDrawColor(CBD[0],CBD[1],CBD[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12.7, $fY - 6, 12.7 + $pageW, $fY - 6);
    $pdf->SetTextColor(CGR[0],CGR[1],CGR[2]);
    $pdf->SetFont('helvetica','',6.5);
    $pdf->SetXY(12.7, $fY - 5);
    $pdf->Cell($pageW * 0.7, 5, APP_NAME . ' - Relatorio Financeiro gerado automaticamente', 0, 0, 'L');
    $pdf->Cell($pageW * 0.3, 5, "Pagina $p de $totalPages  |  " . date('d/m/Y H:i'), 0, 0, 'R');
}

$pdf->Output($filename, 'D');
