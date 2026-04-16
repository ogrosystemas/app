<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

use TCPDF;

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);
$auth->checkLogin();

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Buscar dados para gráficos
$query = "SELECT 
            DATE(v.data_venda) as data,
            COUNT(DISTINCT v.id) as total_vendas,
            SUM(v.valor_total) as valor_total,
            SUM(CASE WHEN v.forma_pagamento = 'dinheiro' THEN v.valor_total ELSE 0 END) as dinheiro,
            SUM(CASE WHEN v.forma_pagamento = 'cartao_credito' THEN v.valor_total ELSE 0 END) as cartao_credito,
            SUM(CASE WHEN v.forma_pagamento = 'cartao_debito' THEN v.valor_total ELSE 0 END) as cartao_debito,
            SUM(CASE WHEN v.forma_pagamento = 'pix' THEN v.valor_total ELSE 0 END) as pix,
            SUM(CASE WHEN v.forma_pagamento = 'boleto' THEN v.valor_total ELSE 0 END) as boleto
          FROM vendas v
          WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
          AND v.status = 'finalizada'
          GROUP BY DATE(v.data_venda)
          ORDER BY v.data_venda ASC";

$stmt = $conn->prepare($query);
$stmt->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais gerais
$total_geral = 0;
$total_vendas_count = 0;
foreach($vendas as $venda) {
    $total_geral += $venda['valor_total'];
    $total_vendas_count += $venda['total_vendas'];
}

class MYPDF extends TCPDF {
    public function Header() {
        $this->SetY(10);
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 10, 'Relatório de Vendas - Oficina de Motos', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Período: ' . date('d/m/Y', strtotime($GLOBALS['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($GLOBALS['data_fim'])), 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . ' | Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Oficina de Motos');
$pdf->SetAuthor('Sistema de Gestão');
$pdf->SetTitle('Relatório de Vendas');
$pdf->SetSubject('Relatório de Vendas');

$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);

$pdf->AddPage();

// Resumo
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Resumo Geral', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(80, 8, 'Período:', 0, 0);
$pdf->Cell(0, 8, date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim)), 0, 1);
$pdf->Cell(80, 8, 'Total de Vendas:', 0, 0);
$pdf->Cell(0, 8, $total_vendas_count, 0, 1);
$pdf->Cell(80, 8, 'Valor Total:', 0, 0);
$pdf->Cell(0, 8, 'R$ ' . number_format($total_geral, 2, ',', '.'), 0, 1);
$pdf->Cell(80, 8, 'Média por Venda:', 0, 0);
$pdf->Cell(0, 8, 'R$ ' . number_format($total_vendas_count > 0 ? $total_geral / $total_vendas_count : 0, 2, ',', '.'), 0, 1);
$pdf->Ln(10);

// Gráfico de barras
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Vendas por Dia', 0, 1, 'L');

$dados_dia = [];
foreach($vendas as $venda) {
    $dados_dia[$venda['data']] = $venda['valor_total'];
}

if(count($dados_dia) > 0) {
    $max_valor = max($dados_dia);
    $largura_barra = (250 / count($dados_dia)) - 2;
    $pos_x = 20;
    $pos_y = $pdf->GetY();
    
    foreach($dados_dia as $data => $valor) {
        $altura_barra = ($valor / $max_valor) * 60;
        $pdf->SetFillColor(13, 110, 253);
        $pdf->Rect($pos_x, $pos_y + 70 - $altura_barra, $largura_barra, $altura_barra, 'F');
        $pdf->SetXY($pos_x, $pos_y + 75);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($largura_barra, 5, date('d/m', strtotime($data)), 0, 0, 'C');
        $pdf->SetXY($pos_x, $pos_y + 80);
        $pdf->Cell($largura_barra, 5, 'R$ ' . number_format($valor, 0, ',', '.'), 0, 0, 'C');
        $pos_x += $largura_barra + 2;
    }
    
    $pdf->SetY($pos_y + 90);
}

// Gráfico de pizza (formas de pagamento)
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Formas de Pagamento', 0, 1, 'L');

$formas = [
    'dinheiro' => 0,
    'cartao_credito' => 0,
    'cartao_debito' => 0,
    'pix' => 0,
    'boleto' => 0
];

foreach($vendas as $venda) {
    $formas['dinheiro'] += $venda['dinheiro'];
    $formas['cartao_credito'] += $venda['cartao_credito'];
    $formas['cartao_debito'] += $venda['cartao_debito'];
    $formas['pix'] += $venda['pix'];
    $formas['boleto'] += $venda['boleto'];
}

$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(60, 8, 'Forma de Pagamento', 1, 0, 'C', 1);
$pdf->Cell(60, 8, 'Valor Total', 1, 0, 'C', 1);
$pdf->Cell(60, 8, 'Percentual', 1, 1, 'C', 1);

foreach($formas as $forma => $valor) {
    if($valor > 0) {
        $percentual = ($valor / $total_geral) * 100;
        $pdf->Cell(60, 7, ucfirst(str_replace('_', ' ', $forma)), 1, 0, 'L');
        $pdf->Cell(60, 7, 'R$ ' . number_format($valor, 2, ',', '.'), 1, 0, 'R');
        $pdf->Cell(60, 7, number_format($percentual, 1) . '%', 1, 1, 'R');
    }
}

// Produtos mais vendidos
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Produtos Mais Vendidos', 0, 1, 'L');

$query = "SELECT p.nome, SUM(vi.quantidade) as total_vendido, SUM(vi.subtotal) as valor_total
          FROM venda_itens vi
          JOIN produtos p ON vi.produto_id = p.id
          JOIN vendas v ON vi.venda_id = v.id
          WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
          GROUP BY p.id
          ORDER BY total_vendido DESC
          LIMIT 15";

$stmt = $conn->prepare($query);
$stmt->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(100, 7, 'Produto', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Quantidade', 1, 0, 'C', 1);
$pdf->Cell(70, 7, 'Valor Total', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 9);
$fill = 0;
foreach($produtos as $produto) {
    $pdf->Cell(100, 6, $produto['nome'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 6, $produto['total_vendido'], 1, 0, 'C', $fill);
    $pdf->Cell(70, 6, 'R$ ' . number_format($produto['valor_total'], 2, ',', '.'), 1, 1, 'R', $fill);
    $fill = !$fill;
}

// Serviços mais realizados
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Serviços Mais Realizados', 0, 1, 'L');

$query = "SELECT s.nome, COUNT(os.id) as total_servicos, SUM(os_itens.subtotal) as valor_total
          FROM os_itens
          JOIN servicos s ON os_itens.item_id = s.id
          JOIN ordens_servico os ON os_itens.os_id = os.id
          WHERE os_itens.tipo = 'servico'
          AND DATE(os.data_abertura) BETWEEN :inicio AND :fim
          GROUP BY s.id
          ORDER BY total_servicos DESC
          LIMIT 15";

$stmt = $conn->prepare($query);
$stmt->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(100, 7, 'Serviço', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Quantidade', 1, 0, 'C', 1);
$pdf->Cell(70, 7, 'Valor Total', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 9);
$fill = 0;
foreach($servicos as $servico) {
    $pdf->Cell(100, 6, $servico['nome'], 1, 0, 'L', $fill);
    $pdf->Cell(50, 6, $servico['total_servicos'], 1, 0, 'C', $fill);
    $pdf->Cell(70, 6, 'R$ ' . number_format($servico['valor_total'], 2, ',', '.'), 1, 1, 'R', $fill);
    $fill = !$fill;
}

// Tabela detalhada de vendas
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Detalhamento das Vendas', 0, 1, 'L');

$query = "SELECT v.numero_venda, v.data_venda, c.nome as cliente_nome, v.valor_total, v.forma_pagamento
          FROM vendas v
          LEFT JOIN clientes c ON v.cliente_id = c.id
          WHERE DATE(v.data_venda) BETWEEN :inicio AND :fim
          AND v.status = 'finalizada'
          ORDER BY v.data_venda DESC";

$stmt = $conn->prepare($query);
$stmt->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
$vendas_detalhadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(40, 7, 'Número Venda', 1, 0, 'C', 1);
$pdf->Cell(40, 7, 'Data', 1, 0, 'C', 1);
$pdf->Cell(80, 7, 'Cliente', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Forma Pagamento', 1, 0, 'C', 1);
$pdf->Cell(50, 7, 'Valor', 1, 1, 'C', 1);

$pdf->SetFont('helvetica', '', 8);
$fill = 0;
foreach($vendas_detalhadas as $venda) {
    $pdf->Cell(40, 6, $venda['numero_venda'], 1, 0, 'C', $fill);
    $pdf->Cell(40, 6, date('d/m/Y H:i', strtotime($venda['data_venda'])), 1, 0, 'C', $fill);
    $pdf->Cell(80, 6, $venda['cliente_nome'] ?? 'Não identificado', 1, 0, 'L', $fill);
    $pdf->Cell(50, 6, strtoupper(str_replace('_', ' ', $venda['forma_pagamento'])), 1, 0, 'C', $fill);
    $pdf->Cell(50, 6, 'R$ ' . number_format($venda['valor_total'], 2, ',', '.'), 1, 1, 'R', $fill);
    $fill = !$fill;
}

$pdf->Output('relatorio_vendas_' . date('Y-m-d') . '.pdf', 'I');
?>