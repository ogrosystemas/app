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
    "SELECT lt.*, os.numero_os, os.data_abertura, os.observacoes as os_obs, os.status as os_status,
            c.nome as cliente_nome, c.cpf_cnpj, c.telefone, c.email, c.endereco,
            m.modelo as moto_modelo, m.marca, m.placa, m.ano, m.cor, m.chassi, m.km_atual,
            u.nome as tecnico_nome
     FROM laudos_tecnicos lt
     JOIN ordens_servico os ON lt.os_id = os.id
     JOIN clientes c        ON os.cliente_id = c.id
     JOIN motos m           ON os.moto_id    = m.id
     LEFT JOIN usuarios u   ON lt.created_by = u.id
     WHERE lt.id = ?"
);
$stmt->execute([$id]);
$laudo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$laudo) die('Laudo não encontrado');

$stmt2 = $db->prepare("SELECT * FROM laudo_secoes WHERE laudo_id = ? ORDER BY secao, ordem");
$stmt2->execute([$id]);
$secoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Agrupar seções
$secaoItens = [];
foreach ($secoes as $s) {
    $secaoItens[$s['secao']][] = $s;
}

// Config do sistema
$cfg = [];
$cfgFile = __DIR__ . '/../../config/sistema.php';
if (file_exists($cfgFile)) { $cfg = include $cfgFile; }
$cfg = array_merge(['nome_sistema'=>'OS-System','nome_oficina'=>'Oficina','telefone'=>'','email'=>'','cnpj'=>'','endereco'=>'','logo_path'=>''], $cfg);

$secaoNomes = [
    1 => 'Motor / Lubrificação',
    2 => 'Arrefecimento',
    3 => 'Alimentação',
    4 => 'Transmissão',
    5 => 'Freios',
    6 => 'Rodas / Vedações',
    7 => 'Suspensão / Direção',
    8 => 'Comandos',
    9 => 'Serviços Complementares',
];

$resultadoLabel = [
    'ok'            => 'OK',
    'atencao'       => 'Atenção',
    'critico'       => 'Crítico',
    'substituido'   => 'Substituído',
    'nao_aplicavel' => 'N/A',
];
$resultadoColor = [
    'ok'            => [34,  197, 94],
    'atencao'       => [245, 158, 11],
    'critico'       => [239, 68,  68],
    'substituido'   => [99,  102, 241],
    'nao_aplicavel' => [148, 163, 184],
];

$statusVeiculoLabel = [
    'apta'             => 'APTA PARA USO',
    'em_revisao'       => 'EM REVISÃO',
    'aguardando_pecas' => 'AGUARDANDO PEÇAS',
    'inapta'           => 'INAPTA',
];
$statusVeiculoColor = [
    'apta'             => [34,  197, 94],
    'em_revisao'       => [245, 158, 11],
    'aguardando_pecas' => [249, 115, 22],
    'inapta'           => [239, 68,  68],
];

// ── PDF ───────────────────────────────────────────────────────────────────────
ob_end_clean();
$pdf = pdfCreate('Relatório Técnico — ' . $laudo['numero_os']);
$pdf->AddPage();

// Cabeçalho
pdfHeader(
    $pdf, $cfg,
    'Relatório Técnico',
    $laudo['numero_os'],
    'OS aberta em: ' . date('d/m/Y', strtotime($laudo['data_abertura'])),
    'Laudo emitido em: ' . date('d/m/Y H:i', strtotime($laudo['created_at']))
);

// ── IDENTIFICAÇÃO ─────────────────────────────────────────────────────────────
pdfSectionTitle($pdf, 'Identificação');

pdfInfoBoxes(
    $pdf,
    'Cliente',
    [
        ['label' => '', 'value' => $laudo['cliente_nome']],
        ['label' => 'CPF/CNPJ', 'value' => $laudo['cpf_cnpj'] ?: '—'],
        ['label' => 'Telefone',  'value' => $laudo['telefone'] ?: '—'],
        ['label' => 'E-mail',    'value' => $laudo['email']    ?: '—'],
    ],
    'Moto',
    [
        ['label' => '', 'value' => ($laudo['marca'] ? $laudo['marca'] . ' ' : '') . $laudo['moto_modelo']],
        ['label' => 'Placa',     'value' => $laudo['placa']   ?: '—'],
        ['label' => 'Ano',       'value' => $laudo['ano']     ?: '—'],
        ['label' => 'Chassi',    'value' => $laudo['chassi']  ?: '—'],
        ['label' => 'KM Laudo',  'value' => $laudo['km_revisao'] ? number_format($laudo['km_revisao'], 0, ',', '.') . ' km' : '—'],
    ]
);

// ── MANUTENÇÃO ────────────────────────────────────────────────────────────────
$pdf->Ln(2);
pdfSectionTitle($pdf, 'Manutenção');

$pdf->SetFont('dejavusans', '', 8.5);
pdfColor($pdf, PDF_C_MUTED);
$pdf->Cell(30, 5, 'Tipo:', 0, 0);
pdfColor($pdf, PDF_C_DARK);
$pdf->SetFont('dejavusans', 'B', 8.5);
$pdf->Cell(60, 5, ucfirst($laudo['tipo_manutencao']), 0, 0);
pdfColor($pdf, PDF_C_MUTED);
$pdf->SetFont('dejavusans', '', 8.5);
$pdf->Cell(25, 5, 'Objetivo:', 0, 0);
pdfColor($pdf, PDF_C_DARK);
$pdf->MultiCell(0, 5, $laudo['objetivo'] ?: '—', 0, 'L');
$pdf->Ln(3);

// ── SEÇÕES DE INSPEÇÃO ────────────────────────────────────────────────────────
pdfSectionTitle($pdf, 'Inspeção do Veículo');

foreach ($secaoNomes as $num => $nomeSecao) {
    $itens = $secaoItens[$num] ?? [];
    if (empty($itens)) continue;

    // Verificar quebra de página
    if ($pdf->GetY() > 240) { $pdf->AddPage(); }

    // Título da seção
    $pdf->Ln(1);
    $pdf->SetFont('dejavusans', 'B', 8);
    pdfColor($pdf, PDF_C_DARK);
    pdfFill($pdf, [240, 242, 247]);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetLineWidth(0.2);
    $pdf->Cell(0, 6, ' ' . $num . '. ' . strtoupper($nomeSecao), 1, 1, 'L', true);
    $pdf->SetLineWidth(0.2);

    // Header da tabela de itens
    pdfFill($pdf, PDF_C_TH_BG);
    pdfColor($pdf, PDF_C_TH_TEXT);
    $pdf->SetFont('dejavusans', 'B', 7);
    $pdf->Cell(90, 5, 'Item Inspecionado', 1, 0, 'L', true);
    $pdf->Cell(30, 5, 'Resultado', 1, 0, 'C', true);
    $pdf->Cell(62, 5, 'Observação', 1, 1, 'L', true);

    foreach ($itens as $i => $it) {
        $alt = ($i % 2 === 0);
        if ($alt) pdfFill($pdf, PDF_C_ROW_ALT);
        else       pdfFill($pdf, PDF_C_WHITE);
        pdfColor($pdf, PDF_C_BLACK);
        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->Cell(90, 5, $it['item'], 1, 0, 'L', true);

        // Resultado colorido
        $res = $it['resultado'];
        $cor = $resultadoColor[$res] ?? [148, 163, 184];
        $pdf->SetFont('dejavusans', 'B', 7);
        $pdf->SetTextColor($cor[0], $cor[1], $cor[2]);
        $pdf->Cell(30, 5, $resultadoLabel[$res] ?? $res, 1, 0, 'C', true);
        pdfColor($pdf, PDF_C_BLACK);
        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->Cell(62, 5, $it['observacao'] ?: '—', 1, 1, 'L', true);
    }
    $pdf->Ln(1);
}

// ── FINALIZAÇÃO ───────────────────────────────────────────────────────────────
if ($pdf->GetY() > 220) { $pdf->AddPage(); }
$pdf->Ln(3);
pdfSectionTitle($pdf, 'Conclusão Técnica');

$pdf->SetFont('dejavusans', '', 8.5);
pdfColor($pdf, PDF_C_DARK);
pdfFill($pdf, PDF_C_ROW_ALT);
pdfDraw($pdf, PDF_C_BORDER);
$pdf->SetLineWidth(0.25);
$pdf->MultiCell(0, 5, $laudo['conclusao_tecnica'] ?: '—', 1, 'L', true);
$pdf->SetLineWidth(0.2);
$pdf->Ln(4);

// Status do veículo — badge grande
$sv  = $laudo['status_veiculo'];
$svC = $statusVeiculoColor[$sv] ?? [148, 163, 184];
$svL = $statusVeiculoLabel[$sv] ?? strtoupper($sv);

$pdf->SetFont('dejavusans', 'B', 10);
pdfColor($pdf, PDF_C_MUTED);
$pdf->Cell(40, 7, 'Status do Veículo:', 0, 0, 'L');
$pdf->SetTextColor($svC[0], $svC[1], $svC[2]);
$pdf->Cell(0, 7, '● ' . $svL, 0, 1, 'L');
$pdf->Ln(2);

// Técnico responsável
$pdf->SetFont('dejavusans', '', 8);
pdfColor($pdf, PDF_C_MUTED);
$pdf->Cell(40, 5, 'Técnico Responsável:', 0, 0);
pdfColor($pdf, PDF_C_DARK);
$pdf->Cell(0, 5, $laudo['tecnico_nome'] ?: '—', 0, 1);

// Assinaturas
pdfAssinaturas($pdf, 'Assinatura do Técnico', 'Assinatura do Cliente / Responsável');

// Rodapé
pdfRodape($pdf, $cfg['nome_oficina']);

$pdf->Output('Laudo-' . $laudo['numero_os'] . '.pdf', 'I');
