<?php
/**
 * api/tickets.php — Sistema de Tickets/Fichas de Consumo
 *
 * Endpoints:
 * POST ?action=gerar   → gera tickets ao finalizar venda
 * POST ?action=usar    → marca ticket como utilizado
 * POST ?action=cancelar → cancela ticket
 * GET  ?action=listar  → lista tickets de uma venda
 * GET  ?action=buscar  → busca ticket pelo código
 */
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/DB.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/Auth.php';

Auth::requireLogin();

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {

    // ── Gerar tickets para uma venda ──────────────────────────────────────
    case 'gerar':
        $vendaId = (int)($input['venda_id'] ?? 0);
        if (!$vendaId) jsonRes(['success'=>false,'message'=>'venda_id obrigatório']);

        $venda = DB::row("SELECT v.*, c.nome as cx_nome FROM vendas v
                          LEFT JOIN caixas c ON v.caixa_id=c.id
                          WHERE v.id=?", [$vendaId]);
        if (!$venda) jsonRes(['success'=>false,'message'=>'Venda não encontrada']);

        $itens = DB::all("SELECT vi.*, p.tipo, p.composicao
                          FROM venda_itens vi
                          LEFT JOIN produtos p ON vi.produto_id=p.id
                          WHERE vi.venda_id=?", [$vendaId]);

        $tickets = [];
        foreach ($itens as $item) {
            // Quantas fichas gerar:
            // - produto normal: 1 ficha por unidade vendida
            // - combo: fichas para cada item do combo (composição)
            // - drink: 1 ficha por drink
            $qtd     = (int)$item['quantidade'];
            $tipo    = $item['tipo'] ?? 'unidade';
            $composicao = $item['composicao'] ? json_decode($item['composicao'], true) : null;

            if ($tipo === 'combo' && $composicao) {
                // Gerar fichas individuais por item do combo
                foreach ($composicao as $comp) {
                    $compProd = DB::row("SELECT nome FROM produtos WHERE id=?", [$comp['produto_id']]);
                    $compNome = $compProd['nome'] ?? $item['descricao'];
                    $compQtd  = (int)($comp['quantidade'] ?? 1) * $qtd;
                    for ($i = 0; $i < $compQtd; $i++) {
                        $codigo = gerarCodigoTicket();
                        DB::insert('tickets', [
                            'codigo'       => $codigo,
                            'venda_id'     => $vendaId,
                            'produto_id'   => $comp['produto_id'],
                            'produto_nome' => $compNome,
                            'status'       => 'pendente',
                        ]);
                        $tickets[] = [
                            'codigo'        => $codigo,
                            'produto_nome'  => $compNome,
                            'venda_numero'  => $venda['numero'],
                        ];
                    }
                }
            } else {
                // 1 ficha por unidade vendida
                for ($i = 0; $i < $qtd; $i++) {
                    $codigo = gerarCodigoTicket();
                    DB::insert('tickets', [
                        'codigo'       => $codigo,
                        'venda_id'     => $vendaId,
                        'produto_id'   => $item['produto_id'],
                        'produto_nome' => $item['descricao'],
                        'status'       => 'pendente',
                    ]);
                    $tickets[] = [
                        'codigo'        => $codigo,
                        'produto_nome'  => $item['descricao'],
                        'venda_numero'  => $venda['numero'],
                    ];
                }
            }
        }

        jsonRes(['success'=>true, 'tickets'=>$tickets, 'total'=>count($tickets)]);

    // ── Usar (resgatar) um ticket ─────────────────────────────────────────
    case 'usar':
        $codigo   = trim($input['codigo'] ?? strtoupper($_GET['codigo'] ?? ''));
        $operador = trim($input['operador'] ?? Auth::nome());

        if (!$codigo) jsonRes(['success'=>false,'message'=>'Código obrigatório']);

        $ticket = DB::row("SELECT * FROM tickets WHERE codigo=?", [$codigo]);
        if (!$ticket) jsonRes(['success'=>false,'message'=>'Ticket não encontrado: '.$codigo]);

        if ($ticket['status'] === 'utilizado') {
            jsonRes(['success'=>false,'message'=>'Ticket já utilizado em '.dataHoraBR($ticket['utilizado_em'])]);
        }
        if ($ticket['status'] === 'cancelado') {
            jsonRes(['success'=>false,'message'=>'Ticket cancelado']);
        }

        DB::update('tickets', [
            'status'            => 'utilizado',
            'utilizado_em'      => date('Y-m-d H:i:s'),
            'operador_utilizou' => $operador,
        ], 'id=?', [$ticket['id']]);

        jsonRes([
            'success'      => true,
            'message'      => '✅ Ticket validado!',
            'produto_nome' => $ticket['produto_nome'],
            'codigo'       => $codigo,
        ]);

    // ── Cancelar ticket ───────────────────────────────────────────────────
    case 'cancelar':
        $codigo = trim($input['codigo'] ?? '');
        if (!$codigo) jsonRes(['success'=>false,'message'=>'Código obrigatório']);

        $ticket = DB::row("SELECT * FROM tickets WHERE codigo=?", [$codigo]);
        if (!$ticket) jsonRes(['success'=>false,'message'=>'Ticket não encontrado']);
        if ($ticket['status'] === 'utilizado') jsonRes(['success'=>false,'message'=>'Ticket já utilizado, não pode cancelar']);

        DB::update('tickets', ['status'=>'cancelado'], 'id=?', [$ticket['id']]);
        jsonRes(['success'=>true,'message'=>'Ticket cancelado.']);

    // ── Listar tickets de uma venda ───────────────────────────────────────
    case 'listar':
        $vendaId = (int)($_GET['venda_id'] ?? $input['venda_id'] ?? 0);
        if (!$vendaId) jsonRes(['success'=>false,'message'=>'venda_id obrigatório']);

        $tickets = DB::all("SELECT * FROM tickets WHERE venda_id=? ORDER BY criado_em", [$vendaId]);
        $resumo  = [
            'total'     => count($tickets),
            'pendentes' => count(array_filter($tickets, fn($t)=>$t['status']==='pendente')),
            'utilizados'=> count(array_filter($tickets, fn($t)=>$t['status']==='utilizado')),
            'cancelados'=> count(array_filter($tickets, fn($t)=>$t['status']==='cancelado')),
        ];
        jsonRes(['success'=>true,'tickets'=>$tickets,'resumo'=>$resumo]);

    // ── Buscar ticket pelo código ─────────────────────────────────────────
    case 'buscar':
        $codigo = strtoupper(trim($_GET['codigo'] ?? $input['codigo'] ?? ''));
        if (!$codigo) jsonRes(['success'=>false,'message'=>'Código obrigatório']);

        $ticket = DB::row(
            "SELECT t.*, v.numero as venda_numero, v.data_venda
             FROM tickets t
             LEFT JOIN vendas v ON t.venda_id=v.id
             WHERE t.codigo=?",
            [$codigo]
        );

        if (!$ticket) jsonRes(['success'=>false,'message'=>'Ticket não encontrado']);
        jsonRes(['success'=>true,'ticket'=>$ticket]);

    // ── Gerar dados ESC/POS em base64 para QZ Tray ──────────────────────
    case 'escpos':
        $vendaId = (int)($_GET['venda_id'] ?? 0);
        if (!$vendaId) jsonRes(['success'=>false,'message'=>'venda_id obrigatório']);

        $tickets = DB::all("SELECT * FROM tickets WHERE venda_id=? AND status!='cancelado' ORDER BY produto_nome,id", [$vendaId]);
        if (empty($tickets)) jsonRes(['success'=>false,'message'=>'Nenhum ticket']);

        $venda   = DB::row("SELECT v.*, cx.operador FROM vendas v LEFT JOIN caixas cx ON v.caixa_id=cx.id WHERE v.id=?", [$vendaId]);
        $nomeEst = DB::cfg('nome_estabelecimento','Bar System Pro');
        $instrucao = DB::cfg('ticket_instrucao','Apresente este ticket ao balcão');
        $rodape    = DB::cfg('ticket_rodape','');
        $mostEst   = DB::cfg('ticket_mostrar_estabelecimento','1')==='1';
        $mostData  = DB::cfg('ticket_mostrar_data','1')==='1';
        $mostVenda = DB::cfg('ticket_mostrar_numero_venda','1')==='1';
        $data      = date('d/m/Y H:i', strtotime($venda['data_venda']));

        // Gerar ESC/POS bytes
        $ESC=chr(27); $GS=chr(29);
        $init      = $ESC.'@';                         // Initialize
        $centerOn  = $ESC.'a'.chr(1);                 // Center align
        $leftOn    = $ESC.'a'.chr(0);                 // Left align
        $boldOn    = $ESC.'E'.chr(1);                 // Bold on
        $boldOff   = $ESC.'E'.chr(0);                 // Bold off
        $bigOn     = $GS.'!'.chr(17);                 // Double size
        $bigOff    = $GS.'!'.chr(0);                  // Normal size
        $cut       = $GS.'V'.chr(65).chr(3);          // Partial cut
        $lf        = "
";
        $line      = str_repeat('-',32)."
";
        $dline     = str_repeat('=',32)."
";

        $raw = $init;
        foreach ($tickets as $t) {
            $raw .= $dline;
            if ($mostEst) { $raw .= $centerOn.$boldOn.mb_strtoupper($nomeEst)."
".$boldOff; }
            $raw .= $line;
            $raw .= $centerOn.$boldOn.$bigOn.mb_strtoupper($t['produto_nome'])."
".$bigOff.$boldOff;
            $raw .= $line;
            $raw .= $centerOn.$bigOn.$boldOn.$t['codigo']."
".$boldOff.$bigOff;
            $raw .= $line;
            $raw .= $leftOn;
            if ($mostVenda) { $raw .= 'Venda: #'.$venda['numero']."
"; }
            if ($mostData)  { $raw .= 'Data: '.$data."
"; }
            $raw .= $line;
            if ($instrucao) { $raw .= $centerOn.$instrucao."
"; }
            if ($rodape)    { $raw .= $centerOn.$rodape."
"; }
            $raw .= "

";
            $raw .= $cut;
        }

        jsonRes(['success'=>true, 'escpos'=>base64_encode($raw), 'total'=>count($tickets)]);

    default:
        jsonRes(['success'=>false,'message'=>'Ação inválida'], 400);
}

function gerarCodigoTicket(): string {
    // Formato: TKT-XXXXXX (6 caracteres alfanuméricos)
    $chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // sem I e O para evitar confusão
    do {
        $codigo = 'TKT-';
        for ($i = 0; $i < 6; $i++) {
            $codigo .= $chars[random_int(0, strlen($chars)-1)];
        }
    } while (DB::count('tickets', "codigo=?", [$codigo]) > 0);
    return $codigo;
}
