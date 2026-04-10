<?php
ob_start();
require_once '../../config/config.php';
checkAuth();
require_once '../../tcpdf/tcpdf.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('ID inválido');

$stmt = $db->prepare("SELECT o.*, c.nome as cliente_nome, c.cpf_cnpj, c.telefone, c.email, c.endereco,
                       m.modelo as moto_modelo, m.placa, m.marca, m.ano, m.cor, m.chassi
                       FROM orcamentos o
                       JOIN clientes c ON o.cliente_id = c.id
                       JOIN motos m ON o.moto_id = m.id
                       WHERE o.id = ?");
$stmt->execute([$id]);
$orcamento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orcamento) die('Orçamento não encontrado');

$stmt = $db->prepare("SELECT oi.*,
    CASE WHEN oi.tipo = 'servico' THEN s.nome ELSE p.nome END as nome_item,
    CASE WHEN oi.tipo = 'servico' THEN s.tempo_estimado ELSE 0 END as tempo_estimado
    FROM orcamento_itens oi
    LEFT JOIN servicos s ON oi.tipo = 'servico' AND oi.item_id = s.id
    LEFT JOIN produtos p ON oi.tipo = 'produto' AND oi.item_id = p.id
    WHERE oi.orcamento_id = ?");
$stmt->execute([$id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mão de obra
$valor_hora = 0; $total_horas = 0;
try {
    $mao = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao['valor_hora'] ?? 0);
} catch(Exception $e) {}

$total_itens = 0;
foreach ($itens as $item) {
    $total_itens += $item['quantidade'] * $item['valor_unitario'];
    if ($item['tipo'] === 'servico') {
        $total_horas += (float)($item['tempo_estimado'] ?? 0) * $item['quantidade'] / 60;
    }
}
$total_mao_obra = round($total_horas * $valor_hora, 2);
$total_geral = $total_itens + $total_mao_obra;

// Sistema config
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) $cfg = include $cfgFile;
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);
$logoPath = !empty($cfg['logo_path']) ? __DIR__ . '/../../' . ltrim($cfg['logo_path'], '/') : '';

$status_labels = ['ativo'=>'Ativo','aprovado'=>'Aprovado','rejeitado'=>'Rejeitado','convertido'=>'Convertido em OS'];

ob_end_clean();
$pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
$pdf->SetCreator('OS-System');
$pdf->SetTitle('Orçamento '.$orcamento['numero_orcamento']);
$pdf->setPrintHeader(false); $pdf->setPrintFooter(false);
$pdf->SetMargins(14,14,14); $pdf->SetAutoPageBreak(true,18);
$pdf->AddPage();
$pdf->SetFont('dejavusans','',9);

// ── CABEÇALHO ────────────────────────────────────────────────────────────────
if ($logoPath && file_exists($logoPath)) {
    $pdf->Image($logoPath,14,10,40,20,'','','',true,300,'',false,false,0,'LT');
    $pdf->SetXY(58,10);
} else {
    $pdf->SetXY(14,10);
}
$pdf->SetFont('dejavusans','B',14); $pdf->SetTextColor(30,35,53);
$pdf->Cell(0,7,$cfg['nome_oficina'],0,1,'L');
$pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
if ($cfg['cnpj'])     { $pdf->SetX(58); $pdf->Cell(0,4,'CNPJ: '.$cfg['cnpj'],0,1); }
if ($cfg['telefone']) { $pdf->SetX(58); $pdf->Cell(0,4,'Tel: '.$cfg['telefone'].($cfg['email']?' · '.$cfg['email']:''),0,1); }
if ($cfg['endereco']) { $pdf->SetX(58); $pdf->Cell(0,4,$cfg['endereco'],0,1); }

$pdf->SetXY(140,10);
$pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0); $pdf->SetFont('dejavusans','B',10);
$pdf->Cell(56,8,'ORÇAMENTO',1,1,'C',true);
$pdf->SetXY(140,18); $pdf->SetFont('dejavusans','B',13); $pdf->SetTextColor(30,35,53);
$pdf->Cell(56,8,$orcamento['numero_orcamento'],0,1,'C');
$pdf->SetXY(140,26); $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
$pdf->Cell(56,5,'Criado: '.date('d/m/Y',strtotime($orcamento['data_criacao'])),0,1,'C');
$pdf->SetXY(140,31); $pdf->Cell(56,5,'Válido até: '.date('d/m/Y',strtotime($orcamento['data_validade'])),0,1,'C');

$y = max($pdf->GetY(),36);
$pdf->SetY($y+2);
$pdf->SetDrawColor(245,158,11); $pdf->SetLineWidth(0.8);
$pdf->Line(14,$pdf->GetY(),196,$pdf->GetY());
$pdf->Ln(4);

// ── STATUS ────────────────────────────────────────────────────────────────────
$pdf->SetFont('dejavusans','B',8); $pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0);
$pdf->Cell(40,6,'Status: '.($status_labels[$orcamento['status']]??$orcamento['status']),1,0,'C',true);
$pdf->SetFillColor(248,250,252); $pdf->SetTextColor(100,116,139);
$pdf->Cell(60,6,'Gerado em: '.date('d/m/Y H:i'),1,1,'C',true);
$pdf->Ln(4);

// ── CLIENTE E MOTO ─────────────────────────────────────────────────────────
$pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
$pdf->Cell(91,5,'CLIENTE',0,0); $pdf->Cell(91,5,'MOTO',0,1);
$y = $pdf->GetY();
$pdf->SetFillColor(248,250,252);
$pdf->RoundedRect(14,$y,89,26,2,'1111','DF');
$pdf->RoundedRect(105,$y,91,26,2,'1111','DF');
$pdf->SetXY(16,$y+2);
$pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(30,35,53); $pdf->Cell(85,5,$orcamento['cliente_nome'],0,1);
$pdf->SetX(16); $pdf->SetFont('dejavusans','',7.5);
foreach(['CPF/CNPJ'=>$orcamento['cpf_cnpj'],'Tel'=>$orcamento['telefone'],'Email'=>$orcamento['email']] as $l=>$v) {
    if ($v) { $pdf->SetX(16); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,$l.':',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$v,0,1); }
}
$pdf->SetXY(107,$y+2);
$pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(30,35,53);
$pdf->Cell(85,5,$orcamento['moto_modelo'].' '.($orcamento['ano']?:''),0,1);
$pdf->SetX(107); $pdf->SetFont('dejavusans','',7.5);
foreach(['Marca'=>$orcamento['marca'],'Placa'=>$orcamento['placa'],'Cor'=>$orcamento['cor']] as $l=>$v) {
    $pdf->SetX(107); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,$l.':',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$v?:'-',0,1);
}
$pdf->SetY($y+30);

// ── ITENS ────────────────────────────────────────────────────────────────────
$pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
$pdf->Cell(0,5,'ITENS DO ORÇAMENTO',0,1);
$pdf->SetFillColor(30,35,53); $pdf->SetTextColor(245,158,11);
$pdf->Cell(82,5,'Item',1,0,'L',true); $pdf->Cell(18,5,'Tipo',1,0,'C',true);
$pdf->Cell(15,5,'Qtd',1,0,'C',true); $pdf->Cell(32,5,'Valor Unit.',1,0,'R',true); $pdf->Cell(35,5,'Total',1,1,'R',true);
$pdf->SetFont('dejavusans','',8);
foreach ($itens as $i=>$item) {
    $bg = $i%2===0;
    $pdf->SetFillColor($bg?248:255,$bg?250:255,$bg?252:255); $pdf->SetTextColor(30,35,53);
    $pdf->Cell(82,5,$item['nome_item']?:'-',1,0,'L',$bg);
    $tipo_label = $item['tipo']==='servico'?'Serviço':'Produto';
    $pdf->Cell(18,5,$tipo_label,1,0,'C',$bg);
    $pdf->Cell(15,5,$item['quantidade'],1,0,'C',$bg);
    $pdf->Cell(32,5,'R$ '.number_format($item['valor_unitario'],2,',','.'),1,0,'R',$bg);
    $pdf->Cell(35,5,'R$ '.number_format($item['quantidade']*$item['valor_unitario'],2,',','.'),1,1,'R',$bg);
}

// ── TOTAIS ────────────────────────────────────────────────────────────────────
$pdf->Ln(2);
$pdf->SetX(110); $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
$pdf->Cell(52,5,'Subtotal Peças/Serviços',0,0,'R');
$pdf->Cell(30,5,'R$ '.number_format($total_itens,2,',','.'),0,1,'R');
if ($total_mao_obra > 0) {
    $pdf->SetX(110);
    $pdf->Cell(52,5,'Mão de Obra ('.number_format($total_horas,1).'h × R$ '.number_format($valor_hora,2,',','.').'\/h)',0,0,'R');
    $pdf->Cell(30,5,'R$ '.number_format($total_mao_obra,2,',','.'),0,1,'R');
}
$pdf->SetX(110);
$pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0); $pdf->SetFont('dejavusans','B',10);
$pdf->Cell(52,7,'TOTAL GERAL',1,0,'R',true); $pdf->Cell(30,7,'R$ '.number_format($total_geral,2,',','.'),1,1,'R',true);
$pdf->Ln(4);

// Observações
if (!empty($orcamento['observacoes'])) {
    $pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11); $pdf->Cell(0,5,'OBSERVAÇÕES',0,1);
    $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(30,35,53);
    $pdf->SetFillColor(248,250,252); $pdf->MultiCell(0,5,$orcamento['observacoes'],1,'L',true);
    $pdf->Ln(3);
}

// Assinatura
$pdf->Ln(8);
$pdf->SetDrawColor(180,180,180);
$pdf->Line(14,$pdf->GetY(),96,$pdf->GetY()); $pdf->SetX(105); $pdf->Line(105,$pdf->GetY(),196,$pdf->GetY());
$pdf->SetFont('dejavusans','',7); $pdf->SetTextColor(100,116,139);
$pdf->SetX(14); $pdf->Cell(82,4,'Assinatura / Aprovação',0,0,'C');
$pdf->SetX(105); $pdf->Cell(91,4,'Assinatura do Cliente',0,1,'C');

// Rodapé
$pageCount = $pdf->getNumPages();
for ($i=1;$i<=$pageCount;$i++) {
    $pdf->setPage($i);
    $pdf->SetY(-12); $pdf->SetFont('dejavusans','',7); $pdf->SetTextColor(150,150,150);
    $pdf->Cell(0,5,$cfg['nome_oficina'].' · OS-System · Pág.'.$i.'/'.$pageCount.' · '.date('d/m/Y H:i'),0,0,'C');
}
$pdf->Output('Orcamento_'.$orcamento['numero_orcamento'].'.pdf','D');
