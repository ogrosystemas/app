<?php
/**
 * api/financeiro_data.php
 * Fix: transactions não tem meli_account_id populado.
 *      Todo filtro de conta usa financial_entries.
 *      DRE encadeado: RECEITA_LIQUIDA → LUCRO_BRUTO → EBITDA → LUCRO_LIQUIDO
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();
auth_require();
license_check();

header('Content-Type: application/json');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// Filtra por conta ML selecionada — agora que financial_entries tem meli_account_id
$acctId  = $_SESSION['active_meli_account_id'] ?? null;
$acctSql = $acctId ? " AND meli_account_id=?" : "";
$acctP   = $acctId ? [$acctId] : [];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── Helpers ────────────────────────────────────────────────────

function dre_period(string $tenantId, string $inicio, string $fim, string $acctSql = '', array $acctP = []): array {
    $rows = db_all(
        "SELECT dre_category, direction, SUM(amount) AS total
         FROM financial_entries
         WHERE tenant_id = ?
           AND entry_date BETWEEN ? AND ?
           AND status = 'PAID'{$acctSql}
         GROUP BY dre_category, direction",
        array_merge([$tenantId, $inicio, $fim], $acctP)
    );

    $map = [];
    foreach ($rows as $r) {
        $cat = $r['dre_category'] ?? 'OUTROS';
        $dir = $r['direction'];
        $map[$cat][$dir] = (float)$r['total'];
    }

    // Receita Bruta = todas entradas IN
    $receitaBruta = 0;
    $cmv          = 0;
    $despOp       = 0;
    $despAdmin    = 0;
    $despFin      = 0;
    $outros       = 0;

    foreach ($map as $cat => $dirs) {
        $in  = $dirs['IN']  ?? 0;
        $out = $dirs['OUT'] ?? 0;
        switch ($cat) {
            case 'RECEITA_VENDA':
            case 'RECEITA_FRETE':
            case 'RECEITA_OUTROS':
                $receitaBruta += $in;
                break;
            case 'CMV':
            case 'TAXA_ML':
            case 'FRETE_CUSTO':
                $cmv += $out;
                break;
            case 'MARKETING':
            case 'OPERACIONAL':
                $despOp += $out;
                break;
            case 'ADMINISTRATIVO':
            case 'PESSOAL':
                $despAdmin += $out;
                break;
            case 'FINANCEIRO':
            case 'JUROS':
            case 'IOF':
                $despFin += $out;
                break;
            default:
                if ($out > 0) $outros += $out;
                if ($in  > 0) $receitaBruta += $in;
        }
    }

    $deducoes      = $map['DEDUCOES']['OUT'] ?? 0;
    $receitaLiq    = $receitaBruta - $deducoes;
    $lucroBruto    = $receitaLiq - $cmv;
    $ebitda        = $lucroBruto - $despOp - $despAdmin;
    $lucroLiq      = $ebitda - $despFin - $outros;

    return [
        'receita_bruta'  => round($receitaBruta, 2),
        'deducoes'       => round($deducoes, 2),
        'receita_liq'    => round($receitaLiq, 2),
        'cmv'            => round($cmv, 2),
        'lucro_bruto'    => round($lucroBruto, 2),
        'desp_op'        => round($despOp, 2),
        'desp_admin'     => round($despAdmin, 2),
        'ebitda'         => round($ebitda, 2),
        'desp_fin'       => round($despFin, 2),
        'outros'         => round($outros, 2),
        'lucro_liq'      => round($lucroLiq, 2),
        'margem_bruta'   => $receitaLiq > 0 ? round(($lucroBruto / $receitaLiq) * 100, 1) : 0,
        'margem_ebitda'  => $receitaLiq > 0 ? round(($ebitda      / $receitaLiq) * 100, 1) : 0,
        'margem_liq'     => $receitaLiq > 0 ? round(($lucroLiq    / $receitaLiq) * 100, 1) : 0,
    ];
}

// ─── GET actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // DRE
    if ($action === 'dre') {
        $mes  = $_GET['mes']  ?? date('Y-m');
        $inicio = $mes . '-01';
        $fim    = date('Y-m-t', strtotime($inicio));

        $dre = dre_period($tenantId, $inicio, $fim, $acctSql, $acctP);
        echo json_encode(['success'=>true, 'dre'=>$dre, 'periodo'=>['inicio'=>$inicio,'fim'=>$fim]]);
        exit;
    }

    // Gráfico mensal (últimos 6 meses)
    if ($action === 'chart_monthly') {
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts     = strtotime("-{$i} months");
            $label  = date('M/y', $ts);
            $inicio = date('Y-m-01', $ts);
            $fim    = date('Y-m-t', $ts);
            $d      = dre_period($tenantId, $inicio, $fim, $acctSql, $acctP);
            $meses[] = [
                'label'       => $label,
                'receita'     => $d['receita_liq'],
                'lucro_bruto' => $d['lucro_bruto'],
                'lucro_liq'   => $d['lucro_liq'],
            ];
        }
        echo json_encode(['success'=>true, 'data'=>$meses]);
        exit;
    }

    // Listagem de lançamentos
    if ($action === 'entries') {
        $direction = $_GET['direction'] ?? 'all';
        $status    = $_GET['status']    ?? 'all';
        $search    = trim($_GET['search'] ?? '');
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $limit     = 30;
        $offset    = ($page - 1) * $limit;

        $where  = "WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($direction !== 'all') {
            $where   .= " AND direction = ?";
            $params[] = $direction;
        }
        if ($status !== 'all') {
            $where   .= " AND status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $where   .= " AND description LIKE ?";
            $params[] = "%{$search}%";
        }

        $rows = db_all(
            "SELECT fe.*, ba.name AS bank_name
             FROM financial_entries fe
             LEFT JOIN bank_accounts ba ON ba.id = fe.account_id
             {$where}
             ORDER BY entry_date DESC, created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        $total = db_one(
            "SELECT COUNT(*) AS cnt FROM financial_entries {$where}",
            $params
        );

        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'total'   => (int)($total['cnt'] ?? 0),
            'page'    => $page,
            'pages'   => ceil(($total['cnt'] ?? 0) / $limit),
        ]);
        exit;
    }

    // KPIs resumo rápido
    if ($action === 'kpis') {
        $mesAtual  = date('Y-m');
        $inicio    = $mesAtual . '-01';
        $fim       = date('Y-m-t', strtotime($inicio));

        $dre = dre_period($tenantId, $inicio, $fim, $acctSql, $acctP);

        // A receber (pendentes)
        $aReceber = db_one(
            "SELECT SUM(amount) AS total FROM financial_entries
             WHERE tenant_id=? AND direction='CREDIT' AND status='PENDING' AND due_date >= CURDATE(){$acctSql}",
            array_merge([$tenantId], (array)$acctP)
        );
        // A pagar (pendentes)
        $aPagar = db_one(
            "SELECT SUM(amount) AS total FROM financial_entries
             WHERE tenant_id=? AND direction='DEBIT' AND status='PENDING' AND due_date >= CURDATE(){$acctSql}",
            array_merge([$tenantId], (array)$acctP)
        );
        // Vencidos
        $vencidos = db_one(
            "SELECT SUM(amount) AS total FROM financial_entries
             WHERE tenant_id=? AND status='PENDING' AND due_date < CURDATE(){$acctSql}",
            array_merge([$tenantId], (array)$acctP)
        );

        echo json_encode([
            'success'       => true,
            'receita_mes'   => $dre['receita_liq'],
            'lucro_liq_mes' => $dre['lucro_liq'],
            'margem_liq'    => $dre['margem_liq'],
            'a_receber'     => (float)($aReceber['total'] ?? 0),
            'a_pagar'       => (float)($aPagar['total']  ?? 0),
            'vencidos'      => (float)($vencidos['total'] ?? 0),
        ]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Ação desconhecida']);
    exit;
}

// ─── POST actions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? $action;

    // Criar lançamento
    if ($action === 'create_entry') {
        $required = ['direction','amount','description','entry_date','status','dre_category'];
        foreach ($required as $f) {
            if (empty($body[$f])) {
                echo json_encode(['success'=>false,'error'=>"Campo obrigatório: {$f}"]); exit;
            }
        }

        $direction   = $body['direction']   === 'IN' ? 'IN' : 'OUT';
        $amount      = abs((float)$body['amount']);
        $isRecurring = !empty($body['is_recurring']) ? 1 : 0;
        $recType     = $body['recurrence_type'] ?? null;
        $recEnd      = $body['recurrence_end']  ?? null;

        $data = [
            'tenant_id'       => $tenantId,
            'direction'       => $direction,
            'amount'          => $amount,
            'description'     => substr(trim($body['description']), 0, 255),
            'entry_date'      => $body['entry_date'],
            'due_date'        => $body['due_date']  ?? $body['entry_date'],
            'paid_date'       => $body['paid_date'] ?? null,
            'status'          => in_array($body['status'], ['PENDING','PAID','CANCELLED','OVERDUE'])
                                    ? $body['status'] : 'PENDING',
            'dre_category'    => $body['dre_category'],
            'is_recurring'    => $isRecurring,
            'recurrence_type' => $isRecurring ? $recType : null,
            'recurrence_end'  => $isRecurring ? $recEnd  : null,
            'account_id'      => $body['account_id'] ?? null,
            'coa_id'          => $body['coa_id']     ?? null,
            'notes'           => $body['notes']       ?? null,
        ];

        $newId = db_insert('financial_entries', $data);
        audit_log('FIN_CREATE', 'financial_entries', $newId, null, $data);

        // Gerar recorrências futuras se necessário
        if ($isRecurring && $recType && $recEnd) {
            _generate_recurrences($tenantId, $data, $recType, $recEnd);
        }

        echo json_encode(['success'=>true, 'id'=>$newId]);
        exit;
    }

    // Atualizar lançamento
    if ($action === 'update_entry') {
        $entryId = $body['entry_id'] ?? '';
        if (!$entryId) { echo json_encode(['success'=>false,'error'=>'entry_id obrigatório']); exit; }

        $before = db_one("SELECT * FROM financial_entries WHERE id=? AND tenant_id=?", [$entryId, $tenantId]);
        if (!$before) { echo json_encode(['success'=>false,'error'=>'Não encontrado']); exit; }

        $allowed = ['amount','description','entry_date','due_date','paid_date','status','dre_category','notes','account_id'];
        $upd = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) $upd[$f] = $body[$f];
        }
        if (empty($upd)) { echo json_encode(['success'=>false,'error'=>'Nada a atualizar']); exit; }

        db_update('financial_entries', $upd, 'id=? AND tenant_id=?', [$entryId, $tenantId]);
        audit_log('FIN_UPDATE', 'financial_entries', $entryId, $before, $upd);
        echo json_encode(['success'=>true]);
        exit;
    }

    // Deletar lançamento
    if ($action === 'delete_entry') {
        $entryId = $body['entry_id'] ?? '';
        if (!$entryId) { echo json_encode(['success'=>false,'error'=>'entry_id obrigatório']); exit; }

        $before = db_one("SELECT * FROM financial_entries WHERE id=? AND tenant_id=?", [$entryId, $tenantId]);
        if (!$before) { echo json_encode(['success'=>false,'error'=>'Não encontrado']); exit; }

        db_delete('financial_entries', 'id=? AND tenant_id=?', [$entryId, $tenantId]);
        audit_log('FIN_DELETE', 'financial_entries', $entryId, $before, null);
        echo json_encode(['success'=>true]);
        exit;
    }

    echo json_encode(['success'=>false,'error'=>'Ação desconhecida']);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Método não suportado']);

// ─── Helper: gerar recorrências ─────────────────────────────────
function _generate_recurrences(string $tenantId, array $base, string $type, string $endDate): void {
    $current = $base['entry_date'];
    $end     = $endDate;
    $count   = 0;
    $maxIter = 60; // segurança

    while ($count < $maxIter) {
        switch ($type) {
            case 'MONTHLY':  $next = date('Y-m-d', strtotime($current . ' +1 month')); break;
            case 'WEEKLY':   $next = date('Y-m-d', strtotime($current . ' +1 week'));  break;
            case 'YEARLY':   $next = date('Y-m-d', strtotime($current . ' +1 year')); break;
            default: return;
        }
        if ($next > $end) break;

        $rec = $base;
        $rec['entry_date']   = $next;
        $rec['due_date']     = $next;
        $rec['paid_date']    = null;
        $rec['status']       = 'PENDING';
        $rec['is_recurring'] = 1;
        unset($rec['id']);

        db_insert('financial_entries', $rec);
        $current = $next;
        $count++;
    }
}
