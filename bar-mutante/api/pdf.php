<?php
/**
 * api/pdf.php
 * Endpoint para geração de PDFs com TCPDF
 * GET ?tipo=financeiro&mes=4&ano=2025
 * GET ?tipo=estoque
 * GET ?tipo=caixa&id=12
 */
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/PDF.php';

// Carregar TCPDF — tenta vários caminhos possíveis
$tcpdfPaths = [
    __DIR__ . '/../vendor/tcpdf/tcpdf.php',
    __DIR__ . '/../vendor/tcpdf/TCPDF.php',
    __DIR__ . '/../vendor/tcpdf/src/tcpdf.php',
    __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
    __DIR__ . '/../tcpdf/tcpdf.php',
    __DIR__ . '/../vendor/autoload.php',
];
foreach ($tcpdfPaths as $_p) {
    if (file_exists($_p) && !class_exists('TCPDF')) {
        require_once $_p;
        break;
    }
}
unset($tcpdfPaths, $_p);

set_exception_handler(function (\Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo '<pre style="background:#1e2330;color:#ef4444;padding:1rem;font-family:monospace">Erro ao gerar PDF: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine() . '</pre>';
    exit;
});

$tipo      = $_GET['tipo'] ?? '';
$mes       = (int) ($_GET['mes'] ?? date('m'));
$ano       = (int) ($_GET['ano'] ?? date('Y'));
$caixa_id  = (int) ($_GET['id']  ?? 0);

$estabelecimento = DB::cfg('nome_estabelecimento', 'Bar System Pro');
$meses_nome = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

switch ($tipo) {

    // ── Relatório Financeiro ─────────────────────────────────────────────
    case 'financeiro':
        $ini = sprintf('%04d-%02d-01 00:00:00', $ano, $mes);
        $fim = date('Y-m-t 23:59:59', mktime(0, 0, 0, $mes, 1, $ano));

        $fat_total_row = DB::row("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as n FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ?", [$ini, $fim]);
        $fat_total  = (float) $fat_total_row['t'];
        $n_vendas   = (int)   $fat_total_row['n'];
        $ticket     = $n_vendas > 0 ? $fat_total / $n_vendas : 0;

        $fat_forma  = DB::all("SELECT forma_pagamento, COUNT(*) as n, SUM(total) as total FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ? GROUP BY forma_pagamento ORDER BY total DESC", [$ini, $fim]);
        $fat_dia    = DB::all("SELECT DATE(data_venda) as dia, SUM(total) as total, COUNT(*) as n FROM vendas WHERE status='pago' AND data_venda BETWEEN ? AND ? GROUP BY DATE(data_venda) ORDER BY dia", [$ini, $fim]);
        $top_prods  = DB::all("SELECT vi.descricao, SUM(vi.quantidade) as qtd, SUM(vi.total) as total FROM venda_itens vi JOIN vendas v ON vi.venda_id=v.id WHERE v.status='pago' AND v.data_venda BETWEEN ? AND ? GROUP BY vi.produto_id ORDER BY total DESC LIMIT 15", [$ini, $fim]);

        PDF::relatorioFinanceiro([
            'estabelecimento' => $estabelecimento,
            'titulo'          => $meses_nome[$mes] . '/' . $ano,
            'fat_total'       => $fat_total,
            'n_vendas'        => $n_vendas,
            'ticket_medio'    => $ticket,
            'fat_forma'       => $fat_forma,
            'fat_dia'         => $fat_dia,
            'top_prods'       => $top_prods,
        ]);
        break;

    // ── Relatório de Estoque ─────────────────────────────────────────────
    case 'estoque':
        $produtos   = DB::all("SELECT p.*,c.nome as cat_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id=c.id WHERE p.ativo=1 ORDER BY c.nome,p.nome");
        $alertas    = alertasEstoque();
        $val_est    = (float) (DB::row("SELECT COALESCE(SUM(estoque_atual*preco_custo),0) as t FROM produtos WHERE ativo=1")['t'] ?? 0);
        $sem_est    = DB::count('produtos', "ativo=1 AND estoque_atual<=0 AND tipo NOT IN ('chopp_barril','dose','drink','combo')");

        PDF::relatorioEstoque([
            'estabelecimento' => $estabelecimento,
            'titulo'          => 'Posição em ' . date('d/m/Y'),
            'produtos'        => $produtos,
            'alertas'         => $alertas,
            'valor_estoque'   => $val_est,
            'sem_estoque'     => $sem_est,
        ]);
        break;

    // ── Fechamento de Caixa ──────────────────────────────────────────────
    case 'caixa':
        if (!$caixa_id) {
            die('<p style="color:red;font-family:sans-serif;padding:2rem">ID do caixa não informado. Use ?tipo=caixa&id=N</p>');
        }

        $caixa = DB::row("SELECT * FROM caixas WHERE id = ?", [$caixa_id]);
        if (!$caixa) {
            die('<p style="color:red;font-family:sans-serif;padding:2rem">Caixa #' . $caixa_id . ' não encontrado.</p>');
        }

        $movimentos  = DB::all("SELECT * FROM caixa_movimentos WHERE caixa_id = ? ORDER BY created_at", [$caixa_id]);
        $vendas      = DB::all("SELECT * FROM vendas WHERE caixa_id = ? AND status='pago' ORDER BY data_venda", [$caixa_id]);
        $fat_forma   = DB::all("SELECT forma_pagamento, COUNT(*) as n, SUM(total) as total FROM vendas WHERE caixa_id = ? AND status='pago' GROUP BY forma_pagamento ORDER BY total DESC", [$caixa_id]);

        PDF::fechamentoCaixa([
            'estabelecimento' => $estabelecimento,
            'caixa'           => $caixa,
            'movimentos'      => $movimentos,
            'vendas'          => $vendas,
            'fat_forma'       => $fat_forma,
        ]);
        break;

    default:
        while (ob_get_level()) ob_end_clean();
        http_response_code(400);
        echo '<p style="color:red;font-family:sans-serif;padding:2rem">Tipo de relatório inválido. Use: <code>?tipo=financeiro</code>, <code>?tipo=estoque</code> ou <code>?tipo=caixa&id=N</code></p>';
        exit;
}
