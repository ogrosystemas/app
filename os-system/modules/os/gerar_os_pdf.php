<?php
ob_start();
require_once '../../config/config.php';
checkAuth();
require_once '../../tcpdf/tcpdf.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('ID inválido');

// Buscar OS com prepared statement
$stmt = $db->prepare("SELECT os.*, c.nome as cliente_nome, c.cpf_cnpj, c.telefone, c.email, c.endereco,
                       m.modelo as moto_modelo, m.placa, m.marca, m.ano, m.cor, m.chassi,
                       u.nome as mecanico_nome
                       FROM ordens_servico os
                       JOIN clientes c ON os.cliente_id = c.id
                       JOIN motos m ON os.moto_id = m.id
                       LEFT JOIN usuarios u ON os.created_by = u.id
                       WHERE os.id = ?");
$stmt->execute([$id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$os) die('OS não encontrada');

$stmt = $db->prepare("SELECT os_s.*, s.nome as servico_nome, s.tempo_estimado FROM os_servicos os_s
                       JOIN servicos s ON os_s.servico_id = s.id WHERE os_s.os_id = ?");
$stmt->execute([$id]);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT op.*, p.nome as produto_nome FROM os_produtos op
                       JOIN produtos p ON op.produto_id = p.id WHERE op.os_id = ?");
$stmt->execute([$id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mão de obra
$valor_hora = 0;
try {
    $mao_row = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao_row['valor_hora'] ?? 0);
} catch(Exception $e) {}

$total_servicos = 0;
$total_horas = 0;
foreach ($servicos as $s) {
    $total_servicos += $s['quantidade'] * $s['valor_unitario'];
    $total_horas += (float)($s['tempo_estimado'] ?? 0) * $s['quantidade'] / 60;
}
$total_produtos = 0;
foreach ($produtos as $p) {
    $total_produtos += $p['quantidade'] * $p['valor_unitario'];
}
$total_mao_obra = round($total_horas * $valor_hora, 2);
$total_geral = $total_servicos + $total_produtos + $total_mao_obra;

// Config do sistema
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) { $cfg = include $cfgFile; }
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);

$logoPath = !empty($cfg['logo_path']) ? __DIR__ . '/../../' . ltrim($cfg['logo_path'], '/') : '';

// Status labels
$status_labels = ['aberta'=>'Aberta','em_andamento'=>'Em Andamento','aguardando_pecas'=>'Aguardando Peças','finalizada'=>'Finalizada','cancelada'=>'Cancelada'];

// Create PDF
ob_end_clean();
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('OS-System');
$pdf->SetTitle('Ordem de Serviço ' . $os['numero_os']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(14, 14, 14);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 9);

$ac = '#f59e0b'; // accent color

// ── CABEÇALHO ────────────────────────────────────────────────────────────────
$pdf->SetDrawColor(245, 158, 11);
$pdf->SetLineWidth(0.8);

if ($logoPath && file_exists($logoPath)) {
    $pdf->Image($logoPath, 14, 10, 40, 20, '', '', '', true, 300, '', false, false, 0, 'LT');
    $pdf->SetXY(58, 10);
} else {
    $pdf->SetXY(14, 10);
}

$pdf->SetFont('dejavusans', 'B', 14);
$pdf->SetTextColor(30, 35, 53);
$pdf->Cell(0, 7, $cfg['nome_oficina'], 0, 1, $logoPath && file_exists($logoPath) ? 'L' : 'L');
$pdf->SetFont('dejavusans', '', 8);
$pdf->SetTextColor(100, 116, 139);
if ($cfg['cnpj'])     $pdf->Cell(0, 4, 'CNPJ: ' . $cfg['cnpj'], 0, 1);
if ($cfg['telefone']) $pdf->Cell(0, 4, 'Tel: ' . $cfg['telefone'] . ($cfg['email'] ? ' · ' . $cfg['email'] : ''), 0, 1);
if ($cfg['endereco']) $pdf->Cell(0, 4, $cfg['endereco'], 0, 1);

// Badge OS no canto superior direito
$pdf->SetXY(140, 10);
$pdf->SetFillColor(245, 158, 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(56, 8, 'ORDEM DE SERVIÇO', 1, 1, 'C', true);
$pdf->SetXY(140, 18);
$pdf->SetFont('dejavusans', 'B', 13);
$pdf->SetTextColor(30, 35, 53);
$pdf->Cell(56, 8, $os['numero_os'], 0, 1, 'C');
$pdf->SetXY(140, 26);
$pdf->SetFont('dejavusans', '', 8);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(56, 5, date('d/m/Y H:i', strtotime($os['data_abertura'])), 0, 1, 'C');

// Linha divisória
$y = max($pdf->GetY(), 36);
$pdf->SetY($y + 2);
$pdf->Line(14, $pdf->GetY(), 196, $pdf->GetY());
$pdf->Ln(4);

// ── CLIENTE E MOTO ─────────────────────────────────────────────────────────
$pdf->SetFont('dejavusans', 'B', 8);
$pdf->SetTextColor(245, 158, 11);
$pdf->Cell(91, 5, 'CLIENTE', 0, 0);
$pdf->Cell(91, 5, 'MOTO', 0, 1);
$pdf->SetTextColor(30, 35, 53);
$pdf->SetFont('dejavusans', '', 8);

$y = $pdf->GetY();
$pdf->SetFillColor(248, 250, 252);
$pdf->RoundedRect(14, $y, 89, 28, 2, '1111', 'DF');
$pdf->RoundedRect(105, $y, 91, 28, 2, '1111', 'DF');

$pdf->SetXY(16, $y+2);
$pdf->SetFont('dejavusans', 'B', 8); $pdf->Cell(85, 5, $os['cliente_nome'], 0, 1);
$pdf->SetX(16); $pdf->SetFont('dejavusans', '', 7.5); $pdf->SetTextColor(100,116,139);
$pdf->Cell(20, 4, 'CPF/CNPJ:', 0, 0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65, 4, $os['cpf_cnpj'] ?: '-', 0, 1);
$pdf->SetX(16); $pdf->SetTextColor(100,116,139); $pdf->Cell(20, 4, 'Tel:', 0, 0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65, 4, $os['telefone'] ?: '-', 0, 1);
$pdf->SetX(16); $pdf->SetTextColor(100,116,139); $pdf->Cell(20, 4, 'Email:', 0, 0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65, 4, $os['email'] ?: '-', 0, 1);
$pdf->SetX(16); $pdf->SetTextColor(100,116,139); $pdf->Cell(20, 4, 'End:', 0, 0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65, 4, $os['endereco'] ?: '-', 0, 1);

$pdf->SetXY(107, $y+2);
$pdf->SetFont('dejavusans', 'B', 8); $pdf->SetTextColor(30,35,53); $pdf->Cell(85, 5, $os['moto_modelo'] . ' ' . ($os['ano'] ?: ''), 0, 1);
$pdf->SetX(107); $pdf->SetFont('dejavusans', '', 7.5); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,'Marca:',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$os['marca']?:'-',0,1);
$pdf->SetX(107); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,'Placa:',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$os['placa'],0,1);
$pdf->SetX(107); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,'Cor:',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$os['cor']?:'-',0,1);
$pdf->SetX(107); $pdf->SetTextColor(100,116,139); $pdf->Cell(20,4,'Chassi:',0,0); $pdf->SetTextColor(30,35,53); $pdf->Cell(65,4,$os['chassi']?:'-',0,1);

$pdf->SetY($y + 32);

// Status e previsão
$pdf->SetFont('dejavusans', '', 8);
$pdf->SetFillColor(245, 158, 11);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(40, 5, 'Status: ' . ($status_labels[$os['status']] ?? $os['status']), 1, 0, 'C', true);
$pdf->SetFillColor(248,250,252);
$pdf->SetTextColor(100,116,139);
$pdf->Cell(40, 5, 'Previsão: ' . ($os['data_previsao'] ? date('d/m/Y', strtotime($os['data_previsao'])) : '-'), 1, 0, 'C', true);
$pdf->SetTextColor(100,116,139);
$pdf->Cell(50, 5, 'Mecânico: ' . ($os['mecanico_nome'] ?? '-'), 1, 1, 'C', true);
$pdf->Ln(4);

// ── SERVIÇOS ────────────────────────────────────────────────────────────────
if (!empty($servicos)) {
    $pdf->SetFont('dejavusans', 'B', 8); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(0, 5, 'SERVIÇOS', 0, 1);
    $pdf->SetFillColor(30,35,53); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(80,5,'Serviço',1,0,'L',true); $pdf->Cell(20,5,'Qtd',1,0,'C',true);
    $pdf->Cell(30,5,'Tempo',1,0,'C',true); $pdf->Cell(30,5,'Valor Unit.',1,0,'R',true); $pdf->Cell(22,5,'Total',1,1,'R',true);
    $pdf->SetFillColor(248,250,252); $pdf->SetTextColor(30,35,53); $pdf->SetFont('dejavusans','',8);
    foreach ($servicos as $i => $s) {
        $bg = $i%2===0;
        $mins = $s['tempo_estimado'] ? $s['tempo_estimado'].' min' : '-';
        $pdf->SetFillColor($bg?248:255, $bg?250:255, $bg?252:255);
        $pdf->Cell(80,5,$s['servico_nome'],1,0,'L',$bg); $pdf->Cell(20,5,$s['quantidade'],1,0,'C',$bg);
        $pdf->Cell(30,5,$mins,1,0,'C',$bg);
        $pdf->Cell(30,5,'R$ '.number_format($s['valor_unitario'],2,',','.'),1,0,'R',$bg);
        $pdf->Cell(22,5,'R$ '.number_format($s['quantidade']*$s['valor_unitario'],2,',','.'),1,1,'R',$bg);
    }
    $pdf->Ln(3);
}

// ── PRODUTOS ────────────────────────────────────────────────────────────────
if (!empty($produtos)) {
    $pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(0,5,'PEÇAS / PRODUTOS',0,1);
    $pdf->SetFillColor(30,35,53); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(100,5,'Produto',1,0,'L',true); $pdf->Cell(20,5,'Qtd',1,0,'C',true);
    $pdf->Cell(32,5,'Valor Unit.',1,0,'R',true); $pdf->Cell(30,5,'Total',1,1,'R',true);
    $pdf->SetFont('dejavusans','',8);
    foreach ($produtos as $i => $p) {
        $bg = $i%2===0;
        $pdf->SetFillColor($bg?248:255,$bg?250:255,$bg?252:255);
        $pdf->SetTextColor(30,35,53);
        $pdf->Cell(100,5,$p['produto_nome'],1,0,'L',$bg); $pdf->Cell(20,5,$p['quantidade'],1,0,'C',$bg);
        $pdf->Cell(32,5,'R$ '.number_format($p['valor_unitario'],2,',','.'),1,0,'R',$bg);
        $pdf->Cell(30,5,'R$ '.number_format($p['quantidade']*$p['valor_unitario'],2,',','.'),1,1,'R',$bg);
    }
    $pdf->Ln(3);
}

// ── TOTAIS ───────────────────────────────────────────────────────────────────
$pdf->SetX(100);
$pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(100,116,139);
$pdf->Cell(60,5,'Subtotal Serviços',0,0,'R'); $pdf->Cell(32,5,'R$ '.number_format($total_servicos,2,',','.'),0,1,'R');
$pdf->SetX(100);
$pdf->Cell(60,5,'Subtotal Produtos',0,0,'R'); $pdf->Cell(32,5,'R$ '.number_format($total_produtos,2,',','.'),0,1,'R');
if ($total_mao_obra > 0) {
    $pdf->SetX(100);
    $hLabel = number_format($total_horas,1).'h × R$ '.number_format($valor_hora,2,',','.').'\/h';
    $pdf->Cell(60,5,'Mão de Obra ('.$hLabel.')',0,0,'R');
    $pdf->Cell(32,5,'R$ '.number_format($total_mao_obra,2,',','.'),0,1,'R');
}
$pdf->SetX(100);
$pdf->SetFillColor(245,158,11); $pdf->SetTextColor(0,0,0); $pdf->SetFont('dejavusans','B',10);
$pdf->Cell(60,7,'TOTAL GERAL',1,0,'R',true);
$pdf->Cell(32,7,'R$ '.number_format($total_geral,2,',','.'),1,1,'R',true);
$pdf->Ln(4);

// Observações
if (!empty($os['observacoes'])) {
    $pdf->SetFont('dejavusans','B',8); $pdf->SetTextColor(245,158,11);
    $pdf->Cell(0,5,'OBSERVAÇÕES',0,1);
    $pdf->SetFont('dejavusans','',8); $pdf->SetTextColor(30,35,53);
    $pdf->SetFillColor(248,250,252);
    $pdf->MultiCell(0,5,$os['observacoes'],1,'L',true);
    $pdf->Ln(3);
}

// Assinatura
$pdf->Ln(8);
$pdf->SetDrawColor(180,180,180);
$pdf->Line(14,$pdf->GetY(),96,$pdf->GetY());
$pdf->SetX(100);
$pdf->Line(100,$pdf->GetY(),196,$pdf->GetY());
$pdf->SetFont('dejavusans','',7); $pdf->SetTextColor(100,116,139);
$pdf->SetX(14); $pdf->Cell(82,4,'Assinatura do Responsável / Mecânico',0,0,'C');
$pdf->SetX(100); $pdf->Cell(96,4,'Assinatura do Cliente',0,1,'C');

// Rodapé
$pageCount = $pdf->getNumPages();
for ($i=1; $i<=$pageCount; $i++) {
    $pdf->setPage($i);
    $pdf->SetY(-12);
    $pdf->SetFont('dejavusans','',7);
    $pdf->SetTextColor(150,150,150);
    $pdf->Cell(0,5,$cfg['nome_oficina'].' · OS-System · Pág.'.$i.'/'.$pageCount.' · '.date('d/m/Y H:i'),0,0,'C');
}

$pdf->Output('OS_'.$os['numero_os'].'.pdf','D');
