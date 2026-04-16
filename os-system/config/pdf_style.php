<?php
/**
 * pdf_style.php — Design System compartilhado para todos os PDFs
 *
 * Paleta: preto/cinza — sem amarelo/laranja nas tabelas.
 * Bordas finas e elegantes (0.2 pt).
 * Logo dinâmica da configuração do sistema.
 * Rodapé fixo via SetAutoPageBreak com margem reservada.
 */

if (!defined('PDF_STYLE_LOADED')) {
    define('PDF_STYLE_LOADED', true);
}

// ── Constantes de cor (R, G, B) ───────────────────────────────────────────────
define('PDF_C_BLACK',       [15,  17,  23]);   // #0f1117 — texto principal
define('PDF_C_DARK',        [30,  35,  53]);   // #1e2335 — título bold
define('PDF_C_MUTED',       [100, 116, 139]);  // #64748b — legendas
define('PDF_C_LIGHT_MUTED', [148, 163, 184]);  // #94a3b2 — rodapé
define('PDF_C_BORDER',      [203, 213, 225]);  // #cbd5e1 — bordas de células
define('PDF_C_ROW_ALT',     [248, 250, 252]);  // #f8fafc — linha alternada
define('PDF_C_WHITE',       [255, 255, 255]);  // branco
define('PDF_C_TH_BG',       [30,  41,  59]);   // #1e293b — header da tabela
define('PDF_C_TH_TEXT',     [241, 245, 249]);  // #f1f5f9 — texto header

// Linha divisória padrão
define('PDF_C_RULE',        [203, 213, 225]);  // #cbd5e1

// Total row
define('PDF_C_TOTAL_BG',    [30,  41,  59]);   // #1e293b
define('PDF_C_TOTAL_TEXT',  [255, 255, 255]);  // branco

// Badge de título do documento (canto superior direito)
define('PDF_C_BADGE_BG',    [15,  17,  23]);   // #0f1117
define('PDF_C_BADGE_TEXT',  [255, 255, 255]);  // branco

// Margens
define('PDF_MARGIN_LEFT',   14);
define('PDF_MARGIN_RIGHT',  14);
define('PDF_MARGIN_TOP',    14);
define('PDF_FOOTER_H',      10); // altura reservada para rodapé

/**
 * Aplica SetTextColor a partir de uma constante de cor.
 */
function pdfColor(TCPDF $pdf, array $c): void {
    $pdf->SetTextColor($c[0], $c[1], $c[2]);
}

/**
 * Aplica SetFillColor a partir de uma constante.
 */
function pdfFill(TCPDF $pdf, array $c): void {
    $pdf->SetFillColor($c[0], $c[1], $c[2]);
}

/**
 * Aplica SetDrawColor a partir de uma constante.
 */
function pdfDraw(TCPDF $pdf, array $c): void {
    $pdf->SetDrawColor($c[0], $c[1], $c[2]);
}

/**
 * Cria e configura a instância TCPDF padrão.
 */
function pdfCreate(string $title): TCPDF {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('OS-System');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    // Reserva espaço para rodapé (PDF_FOOTER_H + 4mm de segurança)
    $pdf->SetAutoPageBreak(true, PDF_FOOTER_H + 4);
    $pdf->SetFont('dejavusans', '', 8.5);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetLineWidth(0.2);
    return $pdf;
}

/**
 * Cabeçalho padrão: logo + dados da empresa + badge do documento.
 * Retorna a posição Y após o cabeçalho.
 */
function pdfHeader(TCPDF $pdf, array $cfg, string $docLabel, string $docNumber, string $docSub1 = '', string $docSub2 = ''): float {
    $logoPath = !empty($cfg['logo_path'])
        ? rtrim(__DIR__, '/') . '/../' . ltrim($cfg['logo_path'], '/')
        : '';

    $hasLogo = $logoPath && file_exists($logoPath);

    // Logo
    if ($hasLogo) {
        $pdf->Image($logoPath, PDF_MARGIN_LEFT, 10, 38, 18, '', '', '', true, 300, '', false, false, 0, 'LT');
        $xText = PDF_MARGIN_LEFT + 41;
    } else {
        $xText = PDF_MARGIN_LEFT;
    }

    // Nome da oficina
    $pdf->SetXY($xText, 10);
    $pdf->SetFont('dejavusans', 'B', 13);
    pdfColor($pdf, PDF_C_DARK);
    $pdf->Cell(0, 6, $cfg['nome_oficina'] ?? 'Oficina', 0, 1, 'L');

    // Dados da empresa
    $pdf->SetFont('dejavusans', '', 7.5);
    pdfColor($pdf, PDF_C_MUTED);
    $infos = [];
    if (!empty($cfg['cnpj']))     $infos[] = 'CNPJ: ' . $cfg['cnpj'];
    if (!empty($cfg['telefone'])) $infos[] = 'Tel: ' . $cfg['telefone'];
    if (!empty($cfg['email']))    $infos[] = $cfg['email'];
    if (!empty($cfg['endereco'])) $infos[] = $cfg['endereco'];
    foreach ($infos as $info) {
        $pdf->SetX($xText);
        $pdf->Cell(0, 3.8, $info, 0, 1, 'L');
    }

    // Badge do documento (canto direito)
    $bx = 138; $by = 10; $bw = 58;
    pdfFill($pdf, PDF_C_BADGE_BG);
    pdfDraw($pdf, PDF_C_BADGE_BG);
    $pdf->SetLineWidth(0);
    $pdf->Rect($bx, $by, $bw, 7, 'F');
    $pdf->SetXY($bx, $by);
    $pdf->SetFont('dejavusans', 'B', 8);
    pdfColor($pdf, PDF_C_BADGE_TEXT);
    $pdf->Cell($bw, 7, strtoupper($docLabel), 0, 1, 'C');

    // Número
    $pdf->SetXY($bx, $by + 7);
    $pdf->SetFont('dejavusans', 'B', 12);
    pdfColor($pdf, PDF_C_DARK);
    $pdf->Cell($bw, 7, $docNumber, 0, 1, 'C');

    // Sub-linhas
    $pdf->SetFont('dejavusans', '', 7);
    pdfColor($pdf, PDF_C_MUTED);
    if ($docSub1) {
        $pdf->SetXY($bx, $by + 14);
        $pdf->Cell($bw, 4, $docSub1, 0, 1, 'C');
    }
    if ($docSub2) {
        $pdf->SetXY($bx, $by + 18);
        $pdf->Cell($bw, 4, $docSub2, 0, 1, 'C');
    }

    // Linha divisória
    $pdf->SetLineWidth(0.3);
    pdfDraw($pdf, PDF_C_BORDER);
    $yRule = max($pdf->GetY(), 33);
    $pdf->SetY($yRule + 2);
    $pdf->Line(PDF_MARGIN_LEFT, $pdf->GetY(), 196, $pdf->GetY());
    $pdf->SetLineWidth(0.2);
    $pdf->Ln(4);

    return $pdf->GetY();
}

/**
 * Cabeçalho de seção (label de grupo como "SERVIÇOS", "CLIENTES"…).
 */
function pdfSectionTitle(TCPDF $pdf, string $label): void {
    $pdf->SetFont('dejavusans', 'B', 7);
    pdfColor($pdf, PDF_C_MUTED);
    $pdf->Cell(0, 4, strtoupper($label), 0, 1, 'L');
    $pdf->SetLineWidth(0.15);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->Line(PDF_MARGIN_LEFT, $pdf->GetY(), 196, $pdf->GetY());
    $pdf->SetLineWidth(0.2);
    $pdf->Ln(3);
}

/**
 * Linha de tabela: header dark.
 * $cols = [['label'=>'', 'w'=>0, 'align'=>'L'], ...]
 */
function pdfTableHeader(TCPDF $pdf, array $cols): void {
    $pdf->SetLineWidth(0.2);
    pdfDraw($pdf, PDF_C_BORDER);
    pdfFill($pdf, PDF_C_TH_BG);
    pdfColor($pdf, PDF_C_TH_TEXT);
    $pdf->SetFont('dejavusans', 'B', 7.5);
    foreach ($cols as $col) {
        $pdf->Cell($col['w'], 5.5, $col['label'], 1, 0, $col['align'] ?? 'L', true);
    }
    $pdf->Ln();
}

/**
 * Linha de dados alternada.
 */
function pdfTableRow(TCPDF $pdf, array $cols, int $rowIndex): void {
    $alt = ($rowIndex % 2 === 0);
    if ($alt) pdfFill($pdf, PDF_C_ROW_ALT);
    else       pdfFill($pdf, PDF_C_WHITE);
    pdfColor($pdf, PDF_C_BLACK);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetFont('dejavusans', '', 8);
    foreach ($cols as $col) {
        $pdf->Cell($col['w'], 5, $col['val'], 1, 0, $col['align'] ?? 'L', true);
    }
    $pdf->Ln();
}

/**
 * Linha de total (fundo escuro, texto branco).
 */
function pdfTableTotal(TCPDF $pdf, string $label, string $valor, float $labelW, float $valW): void {
    $pdf->SetLineWidth(0);
    pdfFill($pdf, PDF_C_TOTAL_BG);
    pdfColor($pdf, PDF_C_TOTAL_TEXT);
    $pdf->SetFont('dejavusans', 'B', 8.5);
    $x = PDF_MARGIN_LEFT + (182 - $labelW - $valW);
    $pdf->SetX($x);
    $pdf->Cell($labelW, 6.5, $label, 0, 0, 'R', true);
    $pdf->Cell($valW,   6.5, $valor,  0, 1, 'R', true);
    $pdf->SetLineWidth(0.2);
    pdfDraw($pdf, PDF_C_BORDER);
}

/**
 * Caixa de info (cliente / moto) com duas colunas lado a lado.
 * $left / $right = array de ['label'=>'', 'value'=>'']
 */
function pdfInfoBoxes(TCPDF $pdf, string $titleL, array $left, string $titleR, array $right): void {
    // Títulos
    $pdf->SetFont('dejavusans', 'B', 7);
    pdfColor($pdf, PDF_C_MUTED);
    $pdf->Cell(91, 4, strtoupper($titleL), 0, 0, 'L');
    $pdf->Cell(91, 4, strtoupper($titleR), 0, 1, 'L');

    $y  = $pdf->GetY();
    $hL = max(count($left),  1) * 4 + 6;
    $hR = max(count($right), 1) * 4 + 6;
    $h  = max($hL, $hR);

    // Caixas com borda fina
    $pdf->SetLineWidth(0.25);
    pdfDraw($pdf, PDF_C_BORDER);
    pdfFill($pdf, PDF_C_ROW_ALT);
    $pdf->RoundedRect(PDF_MARGIN_LEFT, $y, 89, $h, 1.5, '1111', 'DF');
    $pdf->RoundedRect(105, $y, 91, $h, 1.5, '1111', 'DF');
    $pdf->SetLineWidth(0.2);

    // Coluna esquerda
    $pdf->SetXY(PDF_MARGIN_LEFT + 2, $y + 2);
    $pdf->SetFont('dejavusans', 'B', 8);
    pdfColor($pdf, PDF_C_DARK);
    // Primeiro item = nome (destaque)
    if (!empty($left)) {
        $first = array_shift($left);
        $pdf->Cell(85, 4.5, $first['value'], 0, 1);
        $pdf->SetFont('dejavusans', '', 7.5);
        foreach ($left as $row) {
            $pdf->SetX(PDF_MARGIN_LEFT + 2);
            pdfColor($pdf, PDF_C_MUTED);
            $pdf->Cell(20, 4, $row['label'] . ':', 0, 0);
            pdfColor($pdf, PDF_C_DARK);
            $pdf->Cell(65, 4, $row['value'] ?: '—', 0, 1);
        }
    }

    // Coluna direita
    $pdf->SetXY(107, $y + 2);
    $pdf->SetFont('dejavusans', 'B', 8);
    pdfColor($pdf, PDF_C_DARK);
    if (!empty($right)) {
        $first = array_shift($right);
        $pdf->Cell(83, 4.5, $first['value'], 0, 1);
        $pdf->SetFont('dejavusans', '', 7.5);
        foreach ($right as $row) {
            $pdf->SetX(107);
            pdfColor($pdf, PDF_C_MUTED);
            $pdf->Cell(20, 4, $row['label'] . ':', 0, 0);
            pdfColor($pdf, PDF_C_DARK);
            $pdf->Cell(65, 4, $row['value'] ?: '—', 0, 1);
        }
    }

    $pdf->SetY($y + $h + 3);
}

/**
 * Observações.
 */
function pdfObservacoes(TCPDF $pdf, string $texto): void {
    if (!trim($texto)) return;
    pdfSectionTitle($pdf, 'Observações');
    $pdf->SetFont('dejavusans', '', 8);
    pdfColor($pdf, PDF_C_DARK);
    pdfFill($pdf, PDF_C_ROW_ALT);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetLineWidth(0.25);
    $pdf->MultiCell(0, 4.5, $texto, 1, 'L', true);
    $pdf->SetLineWidth(0.2);
    $pdf->Ln(3);
}

/**
 * Bloco de assinaturas.
 */
function pdfAssinaturas(TCPDF $pdf, string $labelLeft, string $labelRight): void {
    $pdf->Ln(10);
    pdfDraw($pdf, PDF_C_BORDER);
    $pdf->SetLineWidth(0.3);
    $y = $pdf->GetY();
    $pdf->Line(PDF_MARGIN_LEFT, $y, 94, $y);
    $pdf->Line(102, $y, 196, $y);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('dejavusans', '', 7);
    pdfColor($pdf, PDF_C_MUTED);
    $pdf->SetX(PDF_MARGIN_LEFT);
    $pdf->Cell(80, 4, $labelLeft,  0, 0, 'C');
    $pdf->SetX(102);
    $pdf->Cell(94, 4, $labelRight, 0, 1, 'C');
}

/**
 * Imprime rodapé em todas as páginas.
 * Deve ser chamado APÓS o Output… não. Chamar antes de Output.
 */
function pdfRodape(TCPDF $pdf, string $nomeOficina): void {
    $n = $pdf->getNumPages();
    for ($i = 1; $i <= $n; $i++) {
        $pdf->setPage($i);
        // Posiciona a 10mm da borda inferior da página A4 (297mm)
        $pdf->SetY(297 - 12);
        $pdf->SetLineWidth(0.2);
        pdfDraw($pdf, PDF_C_BORDER);
        $pdf->Line(PDF_MARGIN_LEFT, $pdf->GetY(), 196, $pdf->GetY());
        $pdf->Ln(1.5);
        $pdf->SetFont('dejavusans', '', 6.5);
        pdfColor($pdf, PDF_C_LIGHT_MUTED);
        $pdf->Cell(0, 4,
            $nomeOficina . ' · OS-System · Pág. ' . $i . '/' . $n . ' · ' . date('d/m/Y H:i'),
            0, 0, 'C'
        );
    }
}
