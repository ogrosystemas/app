<?php
ob_start();
require_once '../../config/config.php';
checkAuth();
require_once '../../tcpdf/tcpdf.php';

$data_inicio = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_GET['data_inicio'] ?? '') ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim    = preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $_GET['data_fim']    ?? '') ? $_GET['data_fim']    : date('Y-m-t');

// Data queries — all prepared statements
$stmt = $db->prepare("SELECT DATE(data_venda) as data, COUNT(*) as qtd, SUM(total) as total
                       FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada'
                       GROUP BY DATE(data_venda) ORDER BY data");
$stmt->execute([$data_inicio, $data_fim]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) as total FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada'");
$stmt->execute([$data_inicio, $data_fim]);
$total_vendas = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) as total FROM ordens_servico WHERE DATE(data_abertura) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_os = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE DATE(data_criacao) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_orc = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT p.nome, SUM(vi.quantidade) as qtd, SUM(vi.total) as total
                       FROM venda_itens vi JOIN produtos p ON vi.produto_id = p.id
                       JOIN vendas v ON vi.venda_id = v.id
                       WHERE DATE(v.data_venda) BETWEEN ? AND ?
                       GROUP BY p.id ORDER BY qtd DESC LIMIT 10");
$stmt->execute([$data_inicio, $data_fim]);
$produtos_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sistema config
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) $cfg = include $cfgFile;
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);
$logoPath = !empty($cfg['logo_path']) ? __DIR__ . '/../../' . ltrim($cfg['logo_path'], '/') : '';

// Build PDF
ob_end_clean();
$pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->SetCreator('OS-System');
$pdf->SetTitle('Relatório Gerencial');
$pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
$pdf->SetMargins(14,14,14); $pdf->SetAutoPageBreak(true,18);
$pdf->AddPage();
$pdf->SetFont('dejavusans','',9);

// ── CABEÇALHO ──────────────────────────────────────────────────────
if ($logoPath && file_exists($logoPath)) {
    $pdf->Image($logoPath,14,10,40,20,'','','',true,300,'',false,false,0,'LT');
    $pdf->SetXY(58,10);
} else {
    $pdf->SetXY(14,10);
}
$pdf->SetFont('dejavusans','B',14); $pdf->SetTextColor(30,35,53);
$pdf->Cell(0,7,$cfg['nome_oficina'],0,1,'L');
$pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
if ($cfg['cnpj'])     { $xc = $logoPath&&file_exists($logoPath)?58:14; $pdf->SetX($xc); $pdf->Cell(0,4,'CNPJ: '.$cfg['cnpj'],0,1); }
if ($cfg['telefone']) { $xc = $logoPath&&file_exists($logoPath)?58:14; $pdf->SetX($xc); $pdf->Cell(0,4,'Tel: '.$cfg['telefone'],0,1); }

$pdf->SetXY(140,10);
$pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0); $pdf->SetFont('dejavusans','B',10);
$pdf->Cell(56,8,'RELATÓRIO GERENCIAL',1,1,'C',true);
$pdf->SetXY(140,18); $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
$pdf->Cell(56,5,'Período:',0,1,'C');
$pdf->SetXY(140,23); $pdf->SetFont('dejavusans','B',9); $pdf->SetTextColor(30,35,53);
$pdf->Cell(56,5,date('d/m/Y',strtotime($data_inicio)).' a '.date('d/m/Y',strtotime($data_fim)),0,1,'C');
$pdf->SetXY(140,28); $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
$pdf->Cell(56,5,'Emitido: '.date('d/m/Y H:i'),0,1,'C');

$y = max($pdf->GetY(),36);
$pdf->SetY($y+2);
$pdf->SetDrawColor(245,158,11); $pdf->SetLineWidth(0.8);
$pdf->Line(14,$pdf->GetY(),196,$pdf->GetY());
$pdf->Ln(5);

// ── CARDS RESUMO ──────────────────────────────────────────────────
$pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
$pdf->Cell(0,5,'RESUMO DO PERÍODO',0,1);
$y = $pdf->GetY();
$cards = [
    ['Total de Vendas','R$ '.number_format($total_vendas['total'],2,',','.'),'#22c55e'],
    ['Ordens de Serviço',(string)$total_os['total'],'#38bdf8'],
    ['Orçamentos',(string)$total_orc['total'],'#f59e0b'],
];
$cardW = 58;
foreach ($cards as $i=>$card) {
    $x = 14 + $i*($cardW+2);
    $pdf->SetFillColor(248,250,252);
    $pdf->RoundedRect($x,$y,$cardW,16,2,'1111','DF');
    $pdf->SetXY($x+2,$y+2);
    $pdf->SetFont('dejavusans','',7); $pdf->SetTextColor(100,116,139);
    $pdf->Cell($cardW-4,4,$card[0],0,1,'C');
    $pdf->SetX($x+2);
    $pdf->SetFont('dejavusans','B',12);
    list($r,$g,$b) = sscanf($card[2],'#%02x%02x%02x');
    $pdf->SetTextColor($r,$g,$b);
    $pdf->Cell($cardW-4,8,$card[1],0,1,'C');
}
$pdf->SetY($y+20);

// ── VENDAS POR DIA (tabela) ───────────────────────────────────────
if (!empty($vendas)) {
    $pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(0,6,'VENDAS POR DIA',0,1);
    $pdf->SetFillColor(30,35,53); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(60,5,'Data',1,0,'C',true);
    $pdf->Cell(60,5,'Quantidade',1,0,'C',true);
    $pdf->Cell(62,5,'Valor Total',1,1,'C',true);
    $pdf->SetFont('dejavusans','',8);
    foreach ($vendas as $i=>$v) {
        $bg = $i%2===0;
        $pdf->SetFillColor($bg?248:255,$bg?250:255,$bg?252:255); $pdf->SetTextColor(30,35,53);
        $pdf->Cell(60,5,date('d/m/Y',strtotime($v['data'])),1,0,'C',$bg);
        $pdf->Cell(60,5,$v['qtd'],1,0,'C',$bg);
        $pdf->Cell(62,5,'R$ '.number_format($v['total'],2,',','.'),1,1,'R',$bg);
    }
    // Total
    $pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0); $pdf->SetFont('dejavusans','B',8);
    $pdf->Cell(120,5,'TOTAL DO PERÍODO',1,0,'R',true);
    $pdf->Cell(62,5,'R$ '.number_format($total_vendas['total'],2,',','.'),1,1,'R',true);
    $pdf->Ln(5);
}

// ── PRODUTOS MAIS VENDIDOS ────────────────────────────────────────
if (!empty($produtos_top)) {
    $pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(0,6,'PRODUTOS MAIS VENDIDOS',0,1);
    $pdf->SetFillColor(30,35,53); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(10,5,'#',1,0,'C',true);
    $pdf->Cell(100,5,'Produto',1,0,'L',true);
    $pdf->Cell(42,5,'Quantidade',1,0,'C',true);
    $pdf->Cell(30,5,'Valor Total',1,1,'R',true);
    $pdf->SetFont('dejavusans','',8);
    foreach ($produtos_top as $i=>$p) {
        $bg = $i%2===0;
        $pdf->SetFillColor($bg?248:255,$bg?250:255,$bg?252:255); $pdf->SetTextColor(30,35,53);
        $pdf->Cell(10,5,($i+1).'º',1,0,'C',$bg);
        $pdf->Cell(100,5,$p['nome'],1,0,'L',$bg);
        $pdf->Cell(42,5,$p['qtd'],1,0,'C',$bg);
        $pdf->Cell(30,5,'R$ '.number_format($p['total'],2,',','.'),1,1,'R',$bg);
    }
}

// Rodapé
$pageCount = $pdf->getNumPages();
for ($i=1;$i<=$pageCount;$i++) {
    $pdf->setPage($i);
    $pdf->SetY(-12); $pdf->SetFont('dejavusans','',7); $pdf->SetTextColor(150,150,150);
    $pdf->Cell(0,5,$cfg['nome_oficina'].' · OS-System · Pág.'.$i.'/'.$pageCount.' · '.date('d/m/Y H:i'),0,0,'C');
}
$pdf->Output('Relatorio_'.date('Ymd').'.pdf','D');
