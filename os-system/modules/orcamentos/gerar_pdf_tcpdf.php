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
    "SELECT o.*, c.nome as cliente_nome, c.cpf_cnpj, c.telefone, c.email, c.endereco,
            m.modelo as moto_modelo, m.placa, m.marca, m.ano, m.cor, m.chassi
     FROM orcamentos o
     JOIN clientes c ON o.cliente_id = c.id
     JOIN motos m ON o.moto_id = m.id
     WHERE o.id = ?"
);
$stmt->execute([$id]);
$orc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$orc) die('Orçamento não encontrado');

$stmt = $db->prepare(
    "SELECT oi.*,
            CASE WHEN oi.tipo='servico' THEN s.nome ELSE p.nome END as nome_item,
            CASE WHEN oi.tipo='servico' THEN s.tempo_estimado ELSE 0 END as tempo_estimado
     FROM orcamento_itens oi
     LEFT JOIN servicos s ON oi.tipo='servico' AND oi.item_id=s.id
     LEFT JOIN produtos p ON oi.tipo='produto' AND oi.item_id=p.id
     WHERE oi.orcamento_id = ?"
);
$stmt->execute([$id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mão de obra
$valor_hora = 0; $total_horas = 0;
try {
    $mao = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao['valor_hora'] ?? 0);
} catch (Exception $e) {}

$total_itens = 0;
foreach ($itens as $item) {
    $total_itens += $item['quantidade'] * $item['valor_unitario'];
    if ($item['tipo'] === 'servico') {
        $total_horas += (float)($item['tempo_estimado'] ?? 0) * $item['quantidade'] / 60;
    }
}
$total_mao_obra = round($total_horas * $valor_hora, 2);
$total_geral    = $total_itens + $total_mao_obra;

// Config
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) { $cfg = include $cfgFile; }
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);

$statusLabels = ['ativo'=>'Ativo','aprovado'=>'Aprovado','rejeitado'=>'Rejeitado','convertido'=>'Convertido em OS'];

// ── PDF ───────────────────────────────────────────────────────────────────────
ob_end_clean();
$pdf = pdfCreate('Orçamento ' . $orc['numero_orcamento']);
$pdf->AddPage();

// Cabeçalho
pdfHeader(
    $pdf, $cfg,
    'Orçamento',
    $orc['numero_orcamento'],
    'Emitido: '   . date('d/m/Y', strtotime($orc['data_criacao'])),
    'Válido até: ' . date('d/m/Y', strtotime($orc['data_validade']))
);

// Status
$pdf->SetFont('dejavusans', '', 7.5);
pdfFill($pdf, PDF_C_ROW_ALT);
pdfDraw($pdf, PDF_C_BORDER);
$pdf->SetLineWidth(0.25);
pdfColor($pdf, PDF_C_MUTED);
$pdf->Cell(35, 5, 'Status:', 1, 0, 'L', true);
pdfColor($pdf, PDF_C_DARK);
$pdf->SetFont('dejavusans', 'B', 7.5);
$pdf->Cell(55, 5, $statusLabels[$orc['status']] ?? $orc['status'], 1, 1, 'L', true);
$pdf->SetLineWidth(0.2);
$pdf->Ln(4);

// Info cliente + moto
pdfInfoBoxes(
    $pdf,
    'Cliente',
    [
        ['label'=>'',         'value'=> $orc['cliente_nome']],
        ['label'=>'CPF/CNPJ', 'value'=> $orc['cpf_cnpj'] ?: ''],
        ['label'=>'Tel',      'value'=> $orc['telefone']  ?: ''],
        ['label'=>'Email',    'value'=> $orc['email']     ?: ''],
    ],
    'Moto',
    [
        ['label'=>'',      'value'=> trim(($orc['moto_modelo'] ?? '') . ' ' . ($orc['ano'] ?? ''))],
        ['label'=>'Marca', 'value'=> $orc['marca'] ?: ''],
        ['label'=>'Placa', 'value'=> $orc['placa'] ?: ''],
        ['label'=>'Cor',   'value'=> $orc['cor']   ?: ''],
    ]
);

// ── ITENS ─────────────────────────────────────────────────────────────────────
pdfSectionTitle($pdf, 'Itens do Orçamento');
pdfTableHeader($pdf, [
    ['label'=>'Descrição',   'w'=>90,  'align'=>'L'],
    ['label'=>'Tipo',        'w'=>22,  'align'=>'C'],
    ['label'=>'Qtd',         'w'=>15,  'align'=>'C'],
    ['label'=>'Valor Unit.', 'w'=>30,  'align'=>'R'],
    ['label'=>'Total',       'w'=>25,  'align'=>'R'],
]);
foreach ($itens as $i => $item) {
    pdfTableRow($pdf, [
        ['val'=> $item['nome_item'] ?: '—',                                              'w'=>90,  'align'=>'L'],
        ['val'=> $item['tipo'] === 'servico' ? 'Serviço' : 'Produto',                   'w'=>22,  'align'=>'C'],
        ['val'=> (string)$item['quantidade'],                                            'w'=>15,  'align'=>'C'],
        ['val'=> 'R$ ' . number_format($item['valor_unitario'],2,',','.'),              'w'=>30,  'align'=>'R'],
        ['val'=> 'R$ ' . number_format($item['quantidade']*$item['valor_unitario'],2,',','.'), 'w'=>25, 'align'=>'R'],
    ], $i);
}
$pdf->Ln(3);

// ── TOTAIS ────────────────────────────────────────────────────────────────────
$labelW = 80; $valW = 36;
$pdf->SetFont('dejavusans', '', 7.5);
pdfColor($pdf, PDF_C_MUTED);

$pdf->SetX(PDF_MARGIN_LEFT + (182 - $labelW - $valW));
$pdf->Cell($labelW, 4.5, 'Subtotal Peças / Serviços', 0, 0, 'R');
$pdf->Cell($valW,   4.5, 'R$ ' . number_format($total_itens, 2, ',', '.'), 0, 1, 'R');

if ($total_mao_obra > 0) {
    $pdf->SetX(PDF_MARGIN_LEFT + (182 - $labelW - $valW));
    $hLabel = number_format($total_horas,1) . 'h × R$ ' . number_format($valor_hora,2,',','.') . '/h';
    $pdf->Cell($labelW, 4.5, 'Mão de Obra (' . $hLabel . ')', 0, 0, 'R');
    $pdf->Cell($valW,   4.5, 'R$ ' . number_format($total_mao_obra, 2, ',', '.'), 0, 1, 'R');
}
$pdf->Ln(1);
pdfTableTotal($pdf, 'TOTAL GERAL', 'R$ ' . number_format($total_geral, 2, ',', '.'), $labelW, $valW);
$pdf->Ln(4);

// Observações
if (!empty($orc['observacoes'])) {
    pdfObservacoes($pdf, $orc['observacoes']);
}

// Assinaturas
pdfAssinaturas($pdf, 'Assinatura / Aprovação do Responsável', 'Assinatura do Cliente');

// Rodapé
pdfRodape($pdf, $cfg['nome_oficina']);

$pdf->Output('Orcamento_' . $orc['numero_orcamento'] . '.pdf', 'D');
