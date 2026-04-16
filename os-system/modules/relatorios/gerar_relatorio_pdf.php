<?php
ob_start();
require_once '../../config/config.php';
checkAuth();
require_once '../../tcpdf/tcpdf.php';
require_once '../../config/pdf_style.php';

$tipo       = $_GET['tipo']       ?? 'vendas';
$data_inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ini'] ?? '') ? $_GET['data_ini'] : date('Y-m-01');
$data_fim    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'] ?? '') ? $_GET['data_fim'] : date('Y-m-t');

// ── Dados ─────────────────────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT COALESCE(SUM(total),0) as total, COUNT(*) as qtd FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada'");
$stmt->execute([$data_inicio, $data_fim]);
$resumo_vendas = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) as total FROM ordens_servico WHERE DATE(data_abertura) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_os = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE DATE(data_criacao) BETWEEN ? AND ?");
$stmt->execute([$data_inicio, $data_fim]);
$total_orc = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Vendas por dia
$stmt = $db->prepare("SELECT DATE(data_venda) as data, COUNT(*) as qtd, SUM(total) as total FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada' GROUP BY DATE(data_venda) ORDER BY data");
$stmt->execute([$data_inicio, $data_fim]);
$vendas_dia = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top produtos
$stmt = $db->prepare("SELECT p.nome, SUM(vi.quantidade) as qtd, SUM(vi.total) as total FROM venda_itens vi JOIN produtos p ON vi.produto_id=p.id JOIN vendas v ON vi.venda_id=v.id WHERE DATE(v.data_venda) BETWEEN ? AND ? GROUP BY p.id ORDER BY total DESC LIMIT 10");
$stmt->execute([$data_inicio, $data_fim]);
$produtos_top = $stmt->fetchAll(PDO::FETCH_ASSOC);

// OS por status
$stmt = $db->prepare("SELECT status, COUNT(*) as qtd FROM ordens_servico WHERE DATE(data_abertura) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$data_inicio, $data_fim]);
$os_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Config
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) { $cfg = include $cfgFile; }
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);

$statusLabels = ['aberta'=>'Aberta','em_andamento'=>'Em Andamento','aguardando_pecas'=>'Aguard. Peças','finalizada'=>'Finalizada','cancelada'=>'Cancelada'];

// ── PDF ───────────────────────────────────────────────────────────────────────
ob_end_clean();
$pdf = pdfCreate('Relatório Gerencial');
$pdf->AddPage();

// Cabeçalho
pdfHeader(
    $pdf, $cfg,
    'Relatório Gerencial',
    date('d/m/Y', strtotime($data_inicio)) . ' — ' . date('d/m/Y', strtotime($data_fim)),
    'Emitido em: ' . date('d/m/Y H:i'),
    ''
);

// ── CARDS RESUMO ──────────────────────────────────────────────────────────────
pdfSectionTitle($pdf, 'Resumo do Período');
$y = $pdf->GetY();
$cards = [
    ['Total de Vendas',    'R$ ' . number_format($resumo_vendas['total'], 2, ',', '.'), $resumo_vendas['qtd'] . ' transações'],
    ['Ordens de Serviço',  (string)$total_os,   'no período'],
    ['Orçamentos',         (string)$total_orc,  'emitidos'],
];
$cw = 58;
foreach ($cards as $i => $card) {
    $cx = PDF_MARGIN_LEFT + $i * ($cw + 3);
    pdfFill($pdf, PDF_C_ROW_ALT);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetLineWidth(0.25);
    $pdf->RoundedRect($cx, $y, $cw, 18, 1.5, '1111', 'DF');
    $pdf->SetXY($cx + 2, $y + 2);
    $pdf->SetFont('dejavusans', '', 7);
    pdfColor($pdf, PDF_C_MUTED);
    $pdf->Cell($cw-4, 4, $card[0], 0, 1, 'C');
    $pdf->SetX($cx + 2);
    $pdf->SetFont('dejavusans', 'B', 11);
    pdfColor($pdf, PDF_C_DARK);
    $pdf->Cell($cw-4, 7, $card[1], 0, 1, 'C');
    $pdf->SetX($cx + 2);
    $pdf->SetFont('dejavusans', '', 6.5);
    pdfColor($pdf, PDF_C_MUTED);
    $pdf->Cell($cw-4, 4, $card[2], 0, 1, 'C');
}
$pdf->SetLineWidth(0.2);
$pdf->SetY($y + 22);

// ── VENDAS POR DIA ────────────────────────────────────────────────────────────
if (!empty($vendas_dia)) {
    $pdf->Ln(2);
    pdfSectionTitle($pdf, 'Vendas por Dia');
    pdfTableHeader($pdf, [
        ['label'=>'Data',       'w'=>60, 'align'=>'C'],
        ['label'=>'Qtd Vendas', 'w'=>60, 'align'=>'C'],
        ['label'=>'Valor Total','w'=>62, 'align'=>'R'],
    ]);
    foreach ($vendas_dia as $i => $v) {
        pdfTableRow($pdf, [
            ['val'=> date('d/m/Y', strtotime($v['data'])),               'w'=>60, 'align'=>'C'],
            ['val'=> (string)$v['qtd'],                                   'w'=>60, 'align'=>'C'],
            ['val'=> 'R$ ' . number_format($v['total'], 2, ',', '.'),    'w'=>62, 'align'=>'R'],
        ], $i);
    }
    // Total
    pdfTableTotal($pdf, 'TOTAL DO PERÍODO', 'R$ ' . number_format($resumo_vendas['total'], 2, ',', '.'), 120, 62);
    $pdf->Ln(4);
}

// ── OS POR STATUS ─────────────────────────────────────────────────────────────
if (!empty($os_status)) {
    pdfSectionTitle($pdf, 'Ordens de Serviço por Status');
    pdfTableHeader($pdf, [
        ['label'=>'Status',     'w'=>120, 'align'=>'L'],
        ['label'=>'Quantidade', 'w'=>62,  'align'=>'C'],
    ]);
    foreach ($os_status as $i => $s) {
        pdfTableRow($pdf, [
            ['val'=> $statusLabels[$s['status']] ?? $s['status'], 'w'=>120, 'align'=>'L'],
            ['val'=> (string)$s['qtd'],                            'w'=>62,  'align'=>'C'],
        ], $i);
    }
    $pdf->Ln(4);
}

// ── PRODUTOS MAIS VENDIDOS ────────────────────────────────────────────────────
if (!empty($produtos_top)) {
    pdfSectionTitle($pdf, 'Top 10 Produtos Mais Vendidos');
    pdfTableHeader($pdf, [
        ['label'=>'#',          'w'=>10,  'align'=>'C'],
        ['label'=>'Produto',    'w'=>100, 'align'=>'L'],
        ['label'=>'Qtd',        'w'=>36,  'align'=>'C'],
        ['label'=>'Receita',    'w'=>36,  'align'=>'R'],
    ]);
    foreach ($produtos_top as $i => $p) {
        pdfTableRow($pdf, [
            ['val'=> ($i+1) . 'º',                                    'w'=>10,  'align'=>'C'],
            ['val'=> $p['nome'],                                       'w'=>100, 'align'=>'L'],
            ['val'=> number_format($p['qtd'], 0, ',', '.'),            'w'=>36,  'align'=>'C'],
            ['val'=> 'R$ ' . number_format($p['total'], 2, ',', '.'), 'w'=>36,  'align'=>'R'],
        ], $i);
    }
}

// Rodapé
pdfRodape($pdf, $cfg['nome_oficina']);

$pdf->Output('Relatorio_' . date('Ymd') . '.pdf', 'D');
