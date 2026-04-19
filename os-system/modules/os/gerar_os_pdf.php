<?php
ob_start();
require_once '../../config/config.php';
checkAuth();
require_once '../../tcpdf/tcpdf.php';
require_once '../../config/pdf_style.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('ID inválido');

// ── Dados ─────────────────────────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT os.*, c.nome as cliente_nome, c.cpf_cnpj, c.telefone, c.email, c.endereco,
            m.modelo as moto_modelo, m.placa, m.marca, m.ano, m.cor, m.chassi,
            u.nome as mecanico_nome
     FROM ordens_servico os
     JOIN clientes c ON os.cliente_id = c.id
     JOIN motos m ON os.moto_id = m.id
     LEFT JOIN usuarios u ON os.created_by = u.id
     WHERE os.id = ?"
);
$stmt->execute([$id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$os) die('OS não encontrada');

$stmt = $db->prepare(
    "SELECT os_s.*, s.nome as servico_nome, s.tempo_estimado
     FROM os_servicos os_s
     JOIN servicos s ON os_s.servico_id = s.id
     WHERE os_s.os_id = ?"
);
$stmt->execute([$id]);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare(
    "SELECT op.*, p.nome as produto_nome
     FROM os_produtos op
     JOIN produtos p ON op.produto_id = p.id
     WHERE op.os_id = ?"
);
$stmt->execute([$id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mão de obra
$valor_hora = 0;
try {
    $mao = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao['valor_hora'] ?? 0);
} catch (Exception $e) {}

$total_servicos = 0; $total_horas = 0;
foreach ($servicos as $s) {
    $total_servicos += $s['quantidade'] * $s['valor_unitario'];
    $total_horas    += (float)($s['tempo_estimado'] ?? 0) * $s['quantidade'] / 60;
}
$total_produtos  = 0;
foreach ($produtos as $p) { $total_produtos += $p['quantidade'] * $p['valor_unitario']; }
$total_mao_obra  = round($total_horas * $valor_hora, 2);
$total_geral     = $total_servicos + $total_produtos + $total_mao_obra;

// Config
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) { $cfg = include $cfgFile; }
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);

$statusLabels = ['aberta'=>'Aberta','em_andamento'=>'Em Andamento','aguardando_pecas'=>'Aguardando Peças','finalizada'=>'Finalizada','cancelada'=>'Cancelada'];
$status = $statusLabels[$os['status']] ?? $os['status'];

// ── PDF ───────────────────────────────────────────────────────────────────────
ob_end_clean();
$pdf = pdfCreate('Ordem de Serviço ' . $os['numero_os']);
$pdf->AddPage();

// Cabeçalho
pdfHeader(
    $pdf, $cfg,
    'Ordem de Serviço',
    $os['numero_os'],
    'Abertura: ' . date('d/m/Y H:i', strtotime($os['data_abertura'])),
    $os['data_previsao'] ? 'Previsão: ' . date('d/m/Y', strtotime($os['data_previsao'])) : ''
);

// Status + Mecânico
$pdf->SetFont('dejavusans', '', 7.5);
pdfFill($pdf, PDF_C_ROW_ALT);
pdfDraw($pdf, PDF_C_BORDER);
pdfColor($pdf, PDF_C_MUTED);
$pdf->SetLineWidth(0.25);
$pdf->Cell(50, 5, 'Status: ', 1, 0, 'L', true);
pdfColor($pdf, PDF_C_DARK);
$pdf->SetFont('dejavusans', 'B', 7.5);
$pdf->Cell(50, 5, $status, 1, 0, 'L', true);
$pdf->SetFont('dejavusans', '', 7.5);
pdfColor($pdf, PDF_C_MUTED);
$pdf->Cell(30, 5, 'Mecânico: ', 1, 0, 'L', true);
pdfColor($pdf, PDF_C_DARK);
$pdf->Cell(52, 5, $os['mecanico_nome'] ?: '—', 1, 1, 'L', true);
$pdf->SetLineWidth(0.2);
$pdf->Ln(4);

// Info cliente + moto
pdfInfoBoxes(
    $pdf,
    'Cliente',
    [
        ['label'=>'',        'value'=> $os['cliente_nome']],
        ['label'=>'CPF/CNPJ','value'=> $os['cpf_cnpj'] ?: ''],
        ['label'=>'Tel',     'value'=> $os['telefone']  ?: ''],
        ['label'=>'Email',   'value'=> $os['email']     ?: ''],
        ['label'=>'Endereço','value'=> $os['endereco']  ?: ''],
    ],
    'Moto',
    [
        ['label'=>'',      'value'=> trim(($os['moto_modelo'] ?? '') . ' ' . ($os['ano'] ?? ''))],
        ['label'=>'Marca', 'value'=> $os['marca']  ?: ''],
        ['label'=>'Placa', 'value'=> $os['placa']  ?: ''],
        ['label'=>'Cor',   'value'=> $os['cor']    ?: ''],
        ['label'=>'Chassi','value'=> $os['chassi'] ?: ''],
    ]
);

// Problema relatado / diagnóstico
if (!empty($os['observacoes'])) {
    pdfObservacoes($pdf, $os['observacoes']);
}

// ── SERVIÇOS ──────────────────────────────────────────────────────────────────
if (!empty($servicos)) {
    pdfSectionTitle($pdf, 'Serviços');
    pdfTableHeader($pdf, [
        ['label'=>'Descrição do Serviço', 'w'=>82, 'align'=>'L'],
        ['label'=>'Qtd',                  'w'=>18, 'align'=>'C'],
        ['label'=>'Tempo Est.',           'w'=>28, 'align'=>'C'],
        ['label'=>'Valor Unit.',          'w'=>30, 'align'=>'R'],
        ['label'=>'Total',                'w'=>24, 'align'=>'R'],
    ]);
    foreach ($servicos as $i => $s) {
        pdfTableRow($pdf, [
            ['val'=> $s['servico_nome'],                                          'w'=>82, 'align'=>'L'],
            ['val'=> (string)$s['quantidade'],                                    'w'=>18, 'align'=>'C'],
            ['val'=> $s['tempo_estimado'] ? $s['tempo_estimado'] . ' min' : '—', 'w'=>28, 'align'=>'C'],
            ['val'=> 'R$ ' . number_format($s['valor_unitario'],2,',','.'),       'w'=>30, 'align'=>'R'],
            ['val'=> 'R$ ' . number_format($s['quantidade']*$s['valor_unitario'],2,',','.'), 'w'=>24, 'align'=>'R'],
        ], $i);
    }
    $pdf->Ln(3);
}

// ── PEÇAS / PRODUTOS ──────────────────────────────────────────────────────────
if (!empty($produtos)) {
    pdfSectionTitle($pdf, 'Peças / Produtos');
    pdfTableHeader($pdf, [
        ['label'=>'Produto',     'w'=>102, 'align'=>'L'],
        ['label'=>'Qtd',         'w'=>18,  'align'=>'C'],
        ['label'=>'Valor Unit.', 'w'=>30,  'align'=>'R'],
        ['label'=>'Total',       'w'=>32,  'align'=>'R'],
    ]);
    foreach ($produtos as $i => $p) {
        pdfTableRow($pdf, [
            ['val'=> $p['produto_nome'],                                         'w'=>102, 'align'=>'L'],
            ['val'=> (string)$p['quantidade'],                                   'w'=>18,  'align'=>'C'],
            ['val'=> 'R$ ' . number_format($p['valor_unitario'],2,',','.'),      'w'=>30,  'align'=>'R'],
            ['val'=> 'R$ ' . number_format($p['quantidade']*$p['valor_unitario'],2,',','.'), 'w'=>32, 'align'=>'R'],
        ], $i);
    }
    $pdf->Ln(3);
}

// ── TOTAIS ────────────────────────────────────────────────────────────────────
$labelW = 80; $valW = 36;
// Sub-linhas em cinza
$pdf->SetFont('dejavusans', '', 7.5);
pdfColor($pdf, PDF_C_MUTED);
if ($total_servicos > 0) {
    $pdf->SetX(PDF_MARGIN_LEFT + (182 - $labelW - $valW));
    $pdf->Cell($labelW, 4.5, 'Subtotal Serviços', 0, 0, 'R');
    $pdf->Cell($valW,   4.5, 'R$ ' . number_format($total_servicos, 2, ',', '.'), 0, 1, 'R');
}
if ($total_produtos > 0) {
    $pdf->SetX(PDF_MARGIN_LEFT + (182 - $labelW - $valW));
    $pdf->Cell($labelW, 4.5, 'Subtotal Produtos', 0, 0, 'R');
    $pdf->Cell($valW,   4.5, 'R$ ' . number_format($total_produtos, 2, ',', '.'), 0, 1, 'R');
}
if ($total_mao_obra > 0) {
    $pdf->SetX(PDF_MARGIN_LEFT + (182 - $labelW - $valW));
    $hLabel = number_format($total_horas,1) . 'h × R$ ' . number_format($valor_hora,2,',','.') . '/h';
    $pdf->Cell($labelW, 4.5, 'Mão de Obra (' . $hLabel . ')', 0, 0, 'R');
    $pdf->Cell($valW,   4.5, 'R$ ' . number_format($total_mao_obra, 2, ',', '.'), 0, 1, 'R');
}
$pdf->Ln(1);
pdfTableTotal($pdf, 'TOTAL GERAL', 'R$ ' . number_format($total_geral, 2, ',', '.'), $labelW, $valW);
$pdf->Ln(4);

// Assinaturas
pdfAssinaturas($pdf, 'Assinatura do Responsável / Mecânico', 'Assinatura do Cliente');

// Rodapé em todas as páginas
pdfRodape($pdf, $cfg['nome_oficina']);

$pdf->Output('OS_' . $os['numero_os'] . '.pdf', 'D');
