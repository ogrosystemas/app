<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_financeiro');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId    = $_SESSION['active_meli_account_id'] ?? null;
$acctSql   = $acctId ? " AND meli_account_id=?"    : "";   // sem alias — queries simples
$acctSqlFe = $acctId ? " AND fe.meli_account_id=?" : "";   // com alias fe — queries com JOIN
$acctP     = $acctId ? [$acctId] : [];
$month    = $_GET['month'] ?? date('Y-m');
$tab      = $_GET['tab']   ?? 'lancamentos';

// ── POST handlers ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_entry') {
        $id = $_POST['id'] ?? '';
        $data = [
            'tenant_id'   => $tenantId,
            'entry_date'  => $_POST['entry_date'] ?: date('Y-m-d'),
            'due_date'    => $_POST['due_date']   ?: null,
            'paid_date'   => $_POST['paid_date']  ?: null,
            'description' => trim($_POST['description'] ?? ''),
            'amount'      => (float)str_replace(',', '.', preg_replace('/\.(?=.*,)/', '', $_POST['amount'] ?? '0')),
            'direction'   => $_POST['direction']  ?? 'DEBIT',
            'status'      => $_POST['status']     ?? 'PAID',
            'account_id'  => $_POST['account_id'] ?: null,
            'coa_id'      => $_POST['coa_id']     ?: null,
            'category'    => $_POST['category']   ?? 'OPERATIONAL',
            'dre_category'=> $_POST['dre_category'] ?: null,
            'is_recurring'=> (int)($_POST['is_recurring'] ?? 0),
            'recurrence_type' => $_POST['recurrence_type'] ?: null,
            'recurrence_end'  => $_POST['recurrence_end']  ?: null,
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
            'created_by'  => $user['id'],
        ];
        if (!$data['description'] || $data['amount'] <= 0) {
            echo json_encode(['ok'=>false,'error'=>'Descrição e valor são obrigatórios']);
            exit;
        }
        if ($id) {
            db_update('financial_entries', $data, 'id=? AND tenant_id=?', [$id, $tenantId]);
            $newId = $id;
        } else {
            $newId = db_insert('financial_entries', $data);
            // Gera recorrências futuras
            if ($data['is_recurring'] && $data['recurrence_type'] && $data['recurrence_end']) {
                $current = new DateTime($data['entry_date']);
                $end     = new DateTime($data['recurrence_end']);
                while (true) {
                    match($data['recurrence_type']) {
                        'MONTHLY' => $current->modify('+1 month'),
                        'WEEKLY'  => $current->modify('+1 week'),
                        'YEARLY'  => $current->modify('+1 year'),
                    };
                    if ($current > $end) break;
                    $rec = $data;
                    $rec['entry_date']      = $current->format('Y-m-d');
                    $rec['parent_entry_id'] = $newId;
                    $rec['is_recurring']    = 0;
                    db_insert('financial_entries', $rec);
                }
            }
        }
        audit_log($id ? 'UPDATE_ENTRY' : 'CREATE_ENTRY', 'financial_entries', $newId, null, ['description'=>$data['description'],'amount'=>$data['amount'],'direction'=>$data['direction']]);
        echo json_encode(['ok'=>true,'id'=>$newId]);
        exit;
    }

    if ($action === 'delete_entry') {
        audit_log('DELETE_ENTRY', 'financial_entries', $_POST['id']);
        db_delete('financial_entries', 'id=? AND tenant_id=?', [$_POST['id'], $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'pay_entry') {
        db_update('financial_entries', [
            'status'    => 'PAID',
            'paid_date' => date('Y-m-d'),
        ], 'id=? AND tenant_id=?', [$_POST['id'], $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_account') {
        $id = $_POST['id'] ?? '';
        // meli_account_id: usa o enviado pelo form, ou cai na conta ativa da sessão
        $acctIdForBank = $_POST['meli_account_id'] ?? $acctId ?? null;
        $data = [
            'tenant_id'       => $tenantId,
            'meli_account_id' => $acctIdForBank,
            'name'            => trim($_POST['name']       ?? ''),
            'type'            => $_POST['type']            ?? 'CORRENTE',
            'bank_name'       => trim($_POST['bank_name']  ?? '') ?: null,
            'agency'          => trim($_POST['agency']     ?? '') ?: null,
            'account_num'     => trim($_POST['account_num']?? '') ?: null,
            'balance'         => (float)str_replace(['.',',' ],['','.'], $_POST['balance'] ?? '0'),
        ];
        if ($id) { db_update('bank_accounts', $data, 'id=? AND tenant_id=?', [$id, $tenantId]); echo json_encode(['ok'=>true]); }
        else { $newId = db_insert('bank_accounts', $data); echo json_encode(['ok'=>true,'id'=>$newId]); }
        exit;
    }
}

// ── Dados do mês ─────────────────────────────────────────
$monthLabel = date_ptbr('F \d\e Y', strtotime($month.'-01'));

// KPIs financeiros — filtrados pela conta selecionada
$kpis = db_one(
    "SELECT
        COALESCE(SUM(CASE WHEN direction='CREDIT' AND status = 'PAID' THEN amount ELSE 0 END),0) as receitas,
        COALESCE(SUM(CASE WHEN direction='DEBIT'  AND status = 'PAID' THEN amount ELSE 0 END),0) as despesas,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='CREDIT' THEN amount ELSE 0 END),0) as a_receber,
        COALESCE(SUM(CASE WHEN status='PENDING' AND direction='DEBIT'  THEN amount ELSE 0 END),0) as a_pagar
     FROM financial_entries
     WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?{$acctSql}",
    array_merge([$tenantId, $month], (array)$acctP)
);

// Transactions: filtra pela conta selecionada (ou todas ativas se nenhuma)
$txAcctSql  = $acctId ? " AND meli_account_id=?" : " AND 1=0";
$txAcctP    = $acctId ? [$acctId] : [];

// Soma vendas ML do mês — conta selecionada
$vendasML = db_one(
    "SELECT COALESCE(SUM(amount),0) as total FROM transactions
     WHERE tenant_id=? AND direction='CREDIT' AND DATE_FORMAT(reference_date,'%Y-%m')=?{$txAcctSql}",
    array_merge([$tenantId, $month], $txAcctP)
);

$receitas = (float)$kpis['receitas'] + (float)$vendasML['total'];
$despesas = (float)$kpis['despesas'];
$saldo    = $receitas - $despesas;
$aReceber = (float)$kpis['a_receber'];
$aPagar   = (float)$kpis['a_pagar'];

// Lançamentos do mês — conta selecionada
$entries = db_all(
    "SELECT fe.*, b.name as bank_name, c.name as coa_name, c.code as coa_code
     FROM financial_entries fe
     LEFT JOIN bank_accounts b         ON b.id = fe.account_id
     LEFT JOIN chart_of_accounts c     ON c.id = fe.coa_id
     WHERE fe.tenant_id=? AND DATE_FORMAT(fe.entry_date,'%Y-%m')=?{$acctSqlFe}
     ORDER BY fe.entry_date DESC, fe.created_at DESC
     LIMIT 200",
    array_merge([$tenantId, $month], (array)$acctP)
);

// Contas a pagar/receber vencidas — conta selecionada
$overdue = db_all(
    "SELECT * FROM financial_entries
     WHERE tenant_id=? AND status='PENDING' AND due_date < CURDATE(){$acctSql}
     ORDER BY due_date ASC LIMIT 20",
    array_merge([$tenantId], (array)$acctP)
);

// Contas bancárias — filtradas pela conta ML ativa
$bankAccounts = db_all(
    "SELECT * FROM bank_accounts WHERE tenant_id=? AND is_active=1{$acctSql} ORDER BY name",
    array_merge([$tenantId], (array)$acctP)
);
$totalBancos = array_sum(array_column($bankAccounts, 'balance'));

// Plano de contas
$coa = db_all("SELECT * FROM chart_of_accounts WHERE tenant_id=? AND is_active=1 ORDER BY code", [$tenantId]);

// DRE — transactions e financial_entries da conta selecionada
$dreData = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM transactions
     WHERE tenant_id=? AND DATE_FORMAT(reference_date,'%Y-%m')=? AND dre_category IS NOT NULL{$txAcctSql}
     GROUP BY dre_category",
    array_merge([$tenantId, $month], $txAcctP)
);
$dreML = array_column($dreData, 'total', 'dre_category');

$dreEntriesData = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM financial_entries
     WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?
       AND dre_category IS NOT NULL AND status = 'PAID'{$acctSql}
     GROUP BY dre_category",
    array_merge([$tenantId, $month], (array)$acctP)
);
$dreManual = array_column($dreEntriesData, 'total', 'dre_category');

// Merge DRE
$dreMap = [];
foreach (array_merge($dreML, $dreManual) as $k => $v) {
    $dreMap[$k] = ($dreMap[$k] ?? 0) + (float)$v;
}
$dreMap['RECEITA_LIQUIDA'] = ($dreMap['RECEITA_BRUTA'] ?? 0) - abs($dreMap['DEDUCOES'] ?? 0);
$dreMap['LUCRO_BRUTO']     = $dreMap['RECEITA_LIQUIDA'] - abs($dreMap['CMV'] ?? 0);
$dreMap['EBITDA']          = $dreMap['LUCRO_BRUTO'] - abs($dreMap['DESPESAS_OPERACIONAIS'] ?? 0) - abs($dreMap['DESPESAS_FINANCEIRAS'] ?? 0);
$dreMap['LUCRO_LIQUIDO']   = $dreMap['EBITDA'] + abs($dreMap['OUTRAS_RECEITAS'] ?? 0) - abs($dreMap['OUTRAS_DESPESAS'] ?? 0);
$dreReceitas = $dreMap['RECEITA_BRUTA'] ?? 0;
$dreMargem   = $dreReceitas > 0 ? round($dreMap['LUCRO_LIQUIDO'] / $dreReceitas * 100, 1) : 0;

// Fluxo diário — conta selecionada
$dailyFlow = db_all(
    "SELECT day, SUM(credits) as credits, SUM(debits) as debits FROM (
       SELECT DATE(entry_date) as day,
              SUM(CASE WHEN direction='CREDIT' THEN amount ELSE 0 END) as credits,
              SUM(CASE WHEN direction='DEBIT'  THEN amount ELSE 0 END) as debits
       FROM financial_entries
       WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=? AND status = 'PAID'{$acctSql}
       GROUP BY DATE(entry_date)
       UNION ALL
       SELECT DATE(reference_date) as day,
              SUM(CASE WHEN direction='CREDIT' AND type='SALE' THEN amount ELSE 0 END) as credits,
              0 as debits
       FROM transactions
       WHERE tenant_id=? AND type='SALE' AND DATE_FORMAT(reference_date,'%Y-%m')=?{$txAcctSql}
       GROUP BY DATE(reference_date)
     ) t GROUP BY day ORDER BY day",
    array_merge([$tenantId, $month], (array)$acctP, [$tenantId, $month], $txAcctP)
);

// Por categoria (despesas) — conta selecionada
$byCategory = db_all(
    "SELECT COALESCE(c.name, fe.dre_category, 'Outros') as category, SUM(fe.amount) as total
     FROM financial_entries fe
     LEFT JOIN chart_of_accounts c ON c.id = fe.coa_id
     WHERE fe.tenant_id=? AND DATE_FORMAT(fe.entry_date,'%Y-%m')=?
       AND fe.direction='DEBIT' AND fe.status = 'PAID'{$acctSqlFe}
     GROUP BY c.id, fe.dre_category ORDER BY total DESC LIMIT 8",
    array_merge([$tenantId, $month], (array)$acctP)
);

$margem = $receitas > 0 ? round(($saldo / $receitas) * 100, 1) : 0;

// Contas ML do tenant para o select do modal de nova conta bancária
$meliContasSelect = db_all(
    "SELECT id, nickname FROM meli_accounts WHERE tenant_id=? AND is_active=1 ORDER BY nickname",
    [$tenantId]
);

$title = 'Financeiro';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px" id="fin-main">

  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Financeiro — <?= $monthLabel ?></h1>
      <p style="font-size:12px;color:#5E5E5A;margin-top:2px">Gestão financeira completa da empresa</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <form method="GET" style="display:flex;align-items:center;gap:8px">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <input type="month" name="month" value="<?= $month ?>"
          style="padding:7px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;cursor:pointer">
        <button type="submit" class="btn-secondary" style="padding:7px 12px;font-size:12px">Ver</button>
      </form>
      <button onclick="openEntry('DEBIT')" style="padding:7px 12px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;border-radius:8px;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i data-lucide="minus-circle" style="width:12px;height:12px"></i> Despesa
      </button>
      <button onclick="openEntry('CREDIT')" style="padding:7px 12px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:8px;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:5px">
        <i data-lucide="plus-circle" style="width:12px;height:12px"></i> Receita
      </button>
      <div style="display:flex;gap:6px">
        <button onclick="window.open('/api/pdf_financeiro.php?month=<?= $month ?>&tipo=extrato','_blank')" class="btn-secondary" style="padding:7px 12px;font-size:12px" title="Extrato de lançamentos">
          <i data-lucide="file-text" style="width:12px;height:12px"></i> Extrato
        </button>
        <button onclick="window.open('/api/pdf_financeiro.php?month=<?= $month ?>&tipo=dre','_blank')" class="btn-secondary" style="padding:7px 12px;font-size:12px" title="Apenas DRE">
          <i data-lucide="bar-chart-2" style="width:12px;height:12px"></i> DRE
        </button>
        <button onclick="window.open('/api/pdf_financeiro.php?month=<?= $month ?>&tipo=completo','_blank')" class="btn-secondary" style="padding:7px 12px;font-size:12px;background:rgba(52,131,250,.1);border-color:#3483FA;color:#3483FA" title="Relatório completo">
          <i data-lucide="file-down" style="width:12px;height:12px"></i> PDF
        </button>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid" style="display:grid;gap:12px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:12px;padding:14px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:5px">Receitas pagas</div>
      <div id="fin-credits" style="font-size:18px;font-weight:600;color:#22c55e">R$ <?= number_format($receitas,2,',','.') ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">incl. vendas ML</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:12px;padding:14px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:5px">Despesas pagas</div>
      <div id="fin-debits" style="font-size:18px;font-weight:600;color:#ef4444">R$ <?= number_format($despesas,2,',','.') ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">lançamentos manuais</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid <?= $saldo>=0?'#22c55e':'#ef4444' ?>;border-radius:12px;padding:14px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:5px">Saldo do período</div>
      <div id="fin-saldo" style="font-size:18px;font-weight:600;color:<?= $saldo>=0?'#22c55e':'#ef4444' ?>"><?= $saldo>=0?'+':'' ?>R$ <?= number_format($saldo,2,',','.') ?></div>
      <div id="fin-margem" style="font-size:10px;color:#5E5E5A;margin-top:2px">margem <?= number_format($margem,1,',','.') ?>%</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:12px;padding:14px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:5px">A receber</div>
      <div style="font-size:18px;font-weight:600;color:#3483FA">R$ <?= number_format($aReceber,2,',','.') ?></div>
      <div style="font-size:10px;color:#5E5E5A;margin-top:2px">pendente de recebimento</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid <?= $aPagar>0?'#f59e0b':'#22c55e' ?>;border-radius:12px;padding:14px">
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:5px">A pagar</div>
      <div style="font-size:18px;font-weight:600;color:<?= $aPagar>0?'#f59e0b':'#22c55e' ?>">R$ <?= number_format($aPagar,2,',','.') ?></div>
      <div style="font-size:10px;color:<?= count($overdue)>0?'#ef4444':'#5E5E5A' ?>;margin-top:2px"><?= count($overdue) ?> vencida(s)</div>
    </div>
  </div>

  <!-- Alertas de vencimento -->
  <?php if (!empty($overdue)): ?>
  <div style="background:rgba(239,68,68,.08);border:0.5px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i data-lucide="alert-triangle" style="width:14px;height:14px;color:#ef4444;flex-shrink:0"></i>
    <span style="font-size:12px;color:#E8E8E6">
      <strong style="color:#ef4444"><?= count($overdue) ?> conta(s) vencida(s):</strong>
      <?= implode(', ', array_map(fn($o) => htmlspecialchars(mb_substr($o['description'],0,25)).' (R$ '.number_format($o['amount'],2,',','.').')', array_slice($overdue, 0, 3))) ?>
      <?= count($overdue) > 3 ? '...' : '' ?>
    </span>
  </div>
  <?php endif; ?>

  <!-- Gráficos sempre visíveis -->
  <div class="fin-charts-grid" style="display:grid;gap:16px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:20px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <div>
          <div style="font-size:13px;font-weight:500;color:#E8E8E6">Fluxo de caixa — <?= $monthLabel ?></div>
          <div style="font-size:11px;color:#5E5E5A;margin-top:2px">Entradas vs saídas diárias</div>
        </div>
        <div style="display:flex;gap:12px;font-size:11px">
          <span style="color:#22c55e;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block"></span>Entradas</span>
          <span style="color:#ef4444;display:flex;align-items:center;gap:4px"><span style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block"></span>Saídas</span>
        </div>
      </div>
      <canvas id="flowChart" style="height:160px!important;max-height:160px"></canvas>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:20px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Despesas por categoria</div>
      <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Distribuição das saídas</div>
      <canvas id="catChart" style="height:140px!important;max-height:140px"></canvas>
      <div id="cat-legend" style="margin-top:10px;display:flex;flex-direction:column;gap:4px"></div>
    </div>
  </div>

  <!-- Gráfico por aba -->
  <div id="tab-charts" style="margin-bottom:20px"></div>

  <!-- Saldo das contas bancárias -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="font-size:13px;font-weight:500;color:#E8E8E6;display:flex;align-items:center;gap:6px">
        <i data-lucide="landmark" style="width:13px;height:13px;color:#3483FA"></i>
        Contas e caixas
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:#5E5E5A">Total:</span>
        <span style="font-size:16px;font-weight:600;color:#22c55e">R$ <?= number_format($totalBancos,2,',','.') ?></span>
        <button onclick="openAccountModal()" class="btn-secondary" style="padding:4px 10px;font-size:11px">
          <i data-lucide="plus" style="width:10px;height:10px"></i> Nova conta
        </button>
      </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap" class="bank-cards">
      <?php
      $typeColors = ['CORRENTE'=>'#3483FA','POUPANCA'=>'#22c55e','CAIXA'=>'#f59e0b','CARTAO_CREDITO'=>'#ef4444','INVESTIMENTO'=>'#a855f7'];
      $typeIcons  = ['CORRENTE'=>'building-2','POUPANCA'=>'piggy-bank','CAIXA'=>'wallet','CARTAO_CREDITO'=>'credit-card','INVESTIMENTO'=>'trending-up'];
      $temMultiploLojas = count($meliContasSelect) > 1;
      foreach ($bankAccounts as $ba):
        $color = $typeColors[$ba['type']] ?? '#5E5E5A';
        $icon  = $typeIcons[$ba['type']]  ?? 'wallet';
        // Encontra o nickname da loja associada
        $lojaNome = '';
        foreach ($meliContasSelect as $mc) {
            if ($mc['id'] === ($ba['meli_account_id'] ?? '')) { $lojaNome = $mc['nickname']; break; }
        }
      ?>
      <div style="flex:1;min-width:160px;background:#252528;border:0.5px solid #2E2E33;border-left:3px solid <?= $color ?>;border-radius:9px;padding:12px">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
          <i data-lucide="<?= $icon ?>" style="width:12px;height:12px;color:<?= $color ?>"></i>
          <span style="font-size:11px;color:#9A9A96;flex:1"><?= htmlspecialchars($ba['name']) ?></span>
        </div>
        <div style="font-size:16px;font-weight:600;color:<?= (float)$ba['balance']>=0?'#E8E8E6':'#ef4444' ?>">
          R$ <?= number_format((float)$ba['balance'],2,',','.') ?>
        </div>
        <?php if ($ba['bank_name']): ?>
        <div style="font-size:10px;color:#5E5E5A;margin-top:2px"><?= htmlspecialchars($ba['bank_name']) ?></div>
        <?php endif; ?>
        <?php if ($lojaNome): ?>
        <div style="font-size:9px;color:#3483FA;margin-top:4px;display:flex;align-items:center;gap:3px">
          <span style="width:5px;height:5px;border-radius:50%;background:#22c55e;display:inline-block"></span>
          <?= htmlspecialchars($lojaNome) ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:4px;padding:4px;background:#252528;border-radius:10px;width:fit-content;margin-bottom:20px">
    <?php
    $tabs = ['lancamentos'=>'Lançamentos','dre'=>'DRE','contas_pagar'=>'Contas a Pagar','contas_receber'=>'A Receber'];
    foreach ($tabs as $tk => $tl):
    ?>
    <a href="?month=<?= $month ?>&tab=<?= $tk ?>" id="tab-<?= $tk ?>"
      style="padding:6px 14px;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer;text-decoration:none;<?= $tab===$tk?'background:#1A1A1C;border:0.5px solid #2E2E33;color:#E8E8E6':'color:#5E5E5A' ?>">
      <?= htmlspecialchars($tl) ?>
      <?php if ($tk==='contas_pagar' && $aPagar > 0): ?>
      <span style="background:#f59e0b;color:#1A1A1A;font-size:9px;padding:1px 5px;border-radius:8px;margin-left:3px"><?= count(array_filter($entries,fn($e)=>$e['direction']==='DEBIT'&&$e['status']==='PENDING')) ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Tab: Lançamentos -->
  <?php if ($tab === 'lancamentos'): ?>
  <div class="card" style="overflow:hidden">
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table>
        <thead><tr>
          <th>Data</th><th>Descrição</th><th>Categoria</th><th>Conta</th><th>Status</th><th style="text-align:right">Valor</th><th>Ações</th>
        </tr></thead>
        <tbody id="fin-tbody">
          <?php if (empty($entries)): ?>
          <tr><td colspan="7" style="text-align:center;color:#5E5E5A;padding:24px">Nenhum lançamento neste mês. Clique em + Receita ou + Despesa.</td></tr>
          <?php else: foreach ($entries as $e):
            $isCredit = $e['direction'] === 'CREDIT';
            $statColors = ['PAID'=>'badge-green','PENDING'=>'badge-amber','OVERDUE'=>'badge-red','CANCELLED'=>'badge-blue'];
            $statLabels = ['PAID'=>'Pago','PENDING'=>'Pendente','OVERDUE'=>'Vencido','CANCELLED'=>'Cancelado'];
          ?>
          <tr>
            <td style="white-space:nowrap;color:#5E5E5A"><?= date('d/m',strtotime($e['entry_date'])) ?></td>
            <td>
              <div style="font-size:12px;font-weight:500"><?= htmlspecialchars(mb_substr($e['description'],0,45)) ?></div>
              <?php if ($e['notes']): ?><div style="font-size:10px;color:#5E5E5A"><?= htmlspecialchars(mb_substr($e['notes'],0,40)) ?></div><?php endif; ?>
              <?php if ($e['is_recurring']): ?><span style="font-size:9px;color:#a855f7;margin-top:2px;display:block">↻ Recorrente</span><?php endif; ?>
            </td>
            <td style="font-size:11px;color:#5E5E5A"><?= $e['coa_code'] ? $e['coa_code'].' — '.htmlspecialchars(mb_substr($e['coa_name']??'',0,25)) : '—' ?></td>
            <td style="font-size:11px;color:#5E5E5A"><?= htmlspecialchars($e['bank_name'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $statColors[$e['status']] ?? 'badge-blue' ?>"><?= $statLabels[$e['status']] ?? $e['status'] ?></span>
              <?php if ($e['due_date'] && $e['status']==='PENDING'): ?>
              <div style="font-size:9px;color:<?= strtotime($e['due_date'])<time()?'#ef4444':'#5E5E5A' ?>">vence <?= date('d/m',strtotime($e['due_date'])) ?></div>
              <?php endif; ?>
            </td>
            <td style="text-align:right;font-weight:500;color:<?= $isCredit?'#22c55e':'#ef4444' ?>;white-space:nowrap">
              <?= $isCredit?'+':'-' ?> R$ <?= number_format((float)$e['amount'],2,',','.') ?>
            </td>
            <td style="white-space:nowrap">
              <div style="display:flex;gap:5px;flex-wrap:wrap;min-width:130px">
                <?php if ($e['status']==='PENDING'): ?>
                <button onclick="payEntry('<?= $e['id'] ?>')" style="padding:6px 10px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:6px;cursor:pointer;font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px">
                  <i data-lucide="check" style="width:11px;height:11px"></i> <?= $isCredit ? 'Receber' : 'Pagar' ?>
                </button>
                <?php endif; ?>
                <button onclick='editEntry(<?= json_encode($e) ?>)' class="btn-secondary" style="padding:6px 10px;font-size:11px;display:flex;align-items:center;gap:4px">
                  <i data-lucide="pencil" style="width:11px;height:11px"></i> Editar
                </button>
                <button onclick="deleteEntry('<?= $e['id'] ?>')" style="padding:6px 10px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;border-radius:6px;cursor:pointer;font-size:11px;display:flex;align-items:center;gap:4px">
                  <i data-lucide="trash-2" style="width:11px;height:11px"></i> Excluir
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div id="fin-tbody-pager" style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-top:0.5px solid #2E2E33"></div>
  </div>

  <!-- Tab: DRE -->
  <?php elseif ($tab === 'dre'): ?>
  <div class="card" style="overflow:hidden;margin-bottom:16px">
    <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
      <span style="font-size:13px;font-weight:500;color:#E8E8E6">DRE — <?= $monthLabel ?></span>
      <span style="font-size:11px;color:#5E5E5A">Regime de competência · Consolidado ML + Empresa</span>
    </div>
    <?php
    $dreRows = [
      ['RECEITA_BRUTA',         '(+) Receita Bruta',            false, 0, '#22c55e'],
      ['DEDUCOES',              '(-) Deduções (Taxas ML, Dev.)', false, 1, '#ef4444'],
      ['RECEITA_LIQUIDA',       '(=) Receita Líquida',          true,  0, '#E8E8E6'],
      ['CMV',                   '(-) Custo dos Produtos (CMV)',  false, 1, '#ef4444'],
      ['LUCRO_BRUTO',           '(=) Lucro Bruto',              true,  0, '#E8E8E6'],
      ['DESPESAS_OPERACIONAIS', '(-) Despesas Operacionais',    false, 1, '#ef4444'],
      ['DESPESAS_FINANCEIRAS',  '(-) Despesas Financeiras',     false, 1, '#ef4444'],
      ['EBITDA',                '(=) EBITDA',                   true,  0, '#3483FA'],
      ['OUTRAS_RECEITAS',       '(+) Outras Receitas',          false, 1, '#22c55e'],
      ['OUTRAS_DESPESAS',       '(-) Outras Despesas',          false, 1, '#ef4444'],
      ['LUCRO_LIQUIDO',         '(=) Lucro Líquido',            true,  0, '#22c55e'],
    ];
    $runTotal = 0;
    foreach ($dreRows as [$key, $label, $bold, $indent, $defColor]):
      $val = (float)($dreMap[$key] ?? 0);
      $color = $val > 0 ? '#22c55e' : ($val < 0 ? '#ef4444' : '#5E5E5A');
      if ($bold) $color = $defColor;
      $pct = $dreReceitas > 0 && $bold ? abs($val/$dreReceitas*100) : null;
    ?>
    <div style="display:flex;align-items:center;padding:10px 18px;padding-left:<?= 18+$indent*20 ?>px;border-bottom:0.5px solid #2E2E33;<?= $bold?'background:#252528':'' ?>">
      <span style="flex:1;font-size:<?= $bold?'13px':'12px' ?>;<?= $bold?'font-weight:500;color:#E8E8E6':'color:#9A9A96' ?>">
        <?= $indent>0?'<span style="color:#2E2E33;margin-right:6px">┗</span>':'' ?><?= $label ?>
      </span>
      <?php if ($pct !== null): ?>
      <span style="font-size:10px;color:#5E5E5A;margin-right:12px"><?= number_format($pct,1,',','.') ?>% da receita</span>
      <?php endif; ?>
      <span style="font-size:<?= $bold?'14px':'12px' ?>;<?= $bold?'font-weight:600':'font-weight:400' ?>;color:<?= $color ?>;min-width:120px;text-align:right">
        <?= $val == 0 ? '—' : (($val>=0?'':'-').' R$ '.number_format(abs($val),2,',','.')) ?>
      </span>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;align-items:center;padding:14px 18px;background:rgba(52,131,250,.08)">
      <span style="flex:1;font-size:14px;font-weight:600;color:#3483FA">Margem Líquida</span>
      <span style="font-size:18px;font-weight:700;color:<?= $dreMargem>=0?'#22c55e':'#ef4444' ?>"><?= number_format($dreMargem,1,',','.') ?>%</span>
    </div>
  </div>

  <!-- Gráfico DRE (renderizado via JS) -->
  <div id="dre-chart-container" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:20px">
    <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Visão gráfica do DRE</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Principais indicadores em R$</div>
    <canvas id="dreChart" style="height:150px!important;max-height:150px"></canvas>
  </div>

  <!-- Tab: Contas a Pagar -->
  <?php elseif ($tab === 'contas_pagar'): ?>
  <?php
  $toPay = db_all("SELECT fe.*, b.name as bank_name, c.name as coa_name FROM financial_entries fe LEFT JOIN bank_accounts b ON b.id=fe.account_id LEFT JOIN chart_of_accounts c ON c.id=fe.coa_id WHERE fe.tenant_id=? AND fe.direction='DEBIT' AND fe.status IN ('PENDING','OVERDUE'){$acctSqlFe} ORDER BY fe.due_date ASC", array_merge([$tenantId], (array)$acctP));
  ?>
  <div class="card" style="overflow:hidden">
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table>
        <thead><tr><th>Vencimento</th><th>Descrição</th><th>Categoria</th><th>Conta</th><th style="text-align:right">Valor</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($toPay)): ?>
          <tr><td colspan="6" style="text-align:center;color:#22c55e;padding:24px">✓ Nenhuma conta a pagar pendente!</td></tr>
          <?php else: foreach ($toPay as $e):
            $vencida = $e['due_date'] && strtotime($e['due_date']) < time();
          ?>
          <tr style="<?= $vencida?'background:rgba(239,68,68,.03)':'' ?>">
            <td style="white-space:nowrap;color:<?= $vencida?'#ef4444':'#5E5E5A' ?>;font-weight:<?= $vencida?'600':'400' ?>">
              <?= $e['due_date'] ? date('d/m/Y',strtotime($e['due_date'])) : '—' ?>
              <?php if ($vencida): ?><div style="font-size:9px">VENCIDA</div><?php endif; ?>
            </td>
            <td style="font-size:12px"><?= htmlspecialchars($e['description']) ?></td>
            <td style="font-size:11px;color:#5E5E5A"><?= htmlspecialchars($e['coa_name'] ?? '—') ?></td>
            <td style="font-size:11px;color:#5E5E5A"><?= htmlspecialchars($e['bank_name'] ?? '—') ?></td>
            <td style="text-align:right;color:#ef4444;font-weight:500">R$ <?= number_format((float)$e['amount'],2,',','.') ?></td>
            <td>
              <div style="display:flex;gap:4px">
                <button onclick="payEntry('<?= $e['id'] ?>')" style="padding:5px 10px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:6px;cursor:pointer;font-size:11px">✓ Pagar</button>
                <button onclick='editEntry(<?= json_encode($e) ?>)' class="btn-secondary" style="padding:5px 7px">
                  <i data-lucide="pencil" style="width:11px;height:11px"></i>
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab: Contas a Receber -->
  <?php elseif ($tab === 'contas_receber'): ?>
  <?php
  $toReceive = db_all("SELECT fe.*, b.name as bank_name FROM financial_entries fe LEFT JOIN bank_accounts b ON b.id=fe.account_id WHERE fe.tenant_id=? AND fe.direction='CREDIT' AND fe.status IN ('PENDING','OVERDUE'){$acctSqlFe} ORDER BY fe.due_date ASC", array_merge([$tenantId], (array)$acctP));
  ?>
  <div class="card" style="overflow:hidden">
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
      <table>
        <thead><tr><th>Vencimento</th><th>Descrição</th><th>Conta</th><th style="text-align:right">Valor</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($toReceive)): ?>
          <tr><td colspan="5" style="text-align:center;color:#22c55e;padding:24px">✓ Nenhum recebimento pendente!</td></tr>
          <?php else: foreach ($toReceive as $e): ?>
          <tr>
            <td style="white-space:nowrap;color:#5E5E5A"><?= $e['due_date'] ? date('d/m/Y',strtotime($e['due_date'])) : '—' ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($e['description']) ?></td>
            <td style="font-size:11px;color:#5E5E5A"><?= htmlspecialchars($e['bank_name'] ?? '—') ?></td>
            <td style="text-align:right;color:#22c55e;font-weight:500">R$ <?= number_format((float)$e['amount'],2,',','.') ?></td>
            <td>
              <button onclick="payEntry('<?= $e['id'] ?>')" style="padding:5px 10px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:6px;cursor:pointer;font-size:11px">✓ Receber</button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal lançamento -->
<div id="entry-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:flex-start;justify-content:center;z-index:50;padding:16px;overflow-y:auto">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:540px;margin:20px auto">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <div id="entry-indicator" style="width:10px;height:10px;border-radius:50%;background:#22c55e"></div>
      <h2 id="entry-modal-title" style="font-size:15px;font-weight:500;color:#E8E8E6">Novo lançamento</h2>
    </div>
    <input type="hidden" id="e-id">
    <input type="hidden" id="e-direction" value="DEBIT">

    <div style="display:grid;gap:14px">
      <!-- Linha 1: Valor e Direção -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Valor (R$) *</label>
          <input type="text" id="e-amount" class="input" placeholder="0,00" oninput="formatCurrency(this)">
        </div>
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Status</label>
          <select id="e-status" class="input" onchange="toggleDates()">
            <option value="PAID">Pago/Recebido</option>
            <option value="PENDING">Pendente</option>
            <option value="CANCELLED">Cancelado</option>
          </select>
        </div>
      </div>

      <!-- Descrição -->
      <div>
        <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Descrição *</label>
        <input type="text" id="e-description" class="input" placeholder="Ex: Aluguel dezembro, Salário João, Venda atacado...">
      </div>

      <!-- Datas -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Data</label>
          <input type="date" id="e-entry-date" class="input" value="<?= date('Y-m-d') ?>">
        </div>
        <div id="e-due-wrap">
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Vencimento</label>
          <input type="date" id="e-due-date" class="input">
        </div>
        <div id="e-paid-wrap">
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Data pgto.</label>
          <input type="date" id="e-paid-date" class="input" value="<?= date('Y-m-d') ?>">
        </div>
      </div>

      <!-- Categoria e Conta -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Categoria (Plano de contas)</label>
          <select id="e-coa" class="input" onchange="updateDreCategory()">
            <option value="">— Selecione —</option>
            <?php foreach ($coa as $c): ?>
            <option value="<?= $c['id'] ?>" data-dre="<?= $c['dre_line'] ?>" data-type="<?= $c['type'] ?>">
              <?= htmlspecialchars($c['code'].' — '.$c['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Conta bancária</label>
          <select id="e-account" class="input">
            <option value="">— Selecione —</option>
            <?php foreach ($bankAccounts as $ba): ?>
            <option value="<?= $ba['id'] ?>"><?= htmlspecialchars($ba['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Recorrência -->
      <div style="padding:12px;background:#252528;border-radius:8px;border:0.5px solid #2E2E33">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;color:#9A9A96;margin-bottom:0">
          <input type="checkbox" id="e-recurring" onchange="toggleRecurrence()" style="accent-color:#a855f7">
          <span>Lançamento recorrente <span style="color:#a855f7">(gera automaticamente)</span></span>
        </label>
        <div id="recurrence-fields" style="display:none;margin-top:10px;display:none;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Frequência</label>
            <select id="e-recurrence-type" class="input" style="font-size:12px">
              <option value="MONTHLY">Mensal</option>
              <option value="WEEKLY">Semanal</option>
              <option value="YEARLY">Anual</option>
            </select>
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Repetir até</label>
            <input type="date" id="e-recurrence-end" class="input" style="font-size:12px">
          </div>
        </div>
      </div>

      <!-- Observações -->
      <div>
        <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Observações</label>
        <textarea id="e-notes" class="input" rows="2" placeholder="Nº NF, referência, observações..."></textarea>
      </div>

      <input type="hidden" id="e-dre-category">
    </div>

    <div style="display:flex;gap:8px;margin-top:20px">
      <button onclick="saveEntry()" class="btn-primary" style="flex:1" id="btn-save-entry">Salvar lançamento</button>
      <button onclick="closeEntryModal()" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal conta bancária -->
<div id="account-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:50;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:420px">
    <h2 style="font-size:15px;font-weight:500;color:#E8E8E6;margin-bottom:20px">Nova conta/caixa</h2>
    <div style="display:grid;gap:12px">

      <!-- Loja ML — campo principal -->
      <div>
        <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">
          Loja ML <span style="color:#ef4444">*</span>
        </label>
        <select id="ac-meli-acct" class="input">
          <?php foreach ($meliContasSelect as $mc): ?>
          <option value="<?= htmlspecialchars($mc['id']) ?>"
            <?= $mc['id'] === $acctId ? 'selected' : '' ?>>
            <?= htmlspecialchars($mc['nickname']) ?>
          </option>
          <?php endforeach; ?>
          <?php if (empty($meliContasSelect)): ?>
          <option value="">Nenhuma loja conectada</option>
          <?php endif; ?>
        </select>
        <div style="font-size:10px;color:#5E5E5A;margin-top:4px">Esta conta bancária pertence a esta loja</div>
      </div>

      <div>
        <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Nome da conta *</label>
        <input type="text" id="ac-name" class="input" placeholder="Ex: Conta BB, Caixa Loja">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Tipo</label>
          <select id="ac-type" class="input">
            <option value="CORRENTE">Conta Corrente</option>
            <option value="POUPANCA">Poupança</option>
            <option value="CAIXA">Caixa Físico</option>
            <option value="CARTAO_CREDITO">Cartão Crédito</option>
            <option value="INVESTIMENTO">Investimento</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Saldo inicial</label>
          <input type="text" id="ac-balance" class="input" placeholder="0,00" value="0" oninput="formatCurrency(this)">
        </div>
      </div>

      <div>
        <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">Banco</label>
        <input type="text" id="ac-bank" class="input" placeholder="Ex: Banco do Brasil, Nubank">
      </div>

    </div>
    <div style="display:flex;gap:8px;margin-top:20px">
      <button onclick="saveAccount()" class="btn-primary" style="flex:1">Salvar conta</button>
      <button onclick="document.getElementById('account-modal').style.display='none'" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<!-- Área de impressão -->


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.PAGE_DATA_API = '/api/financeiro_data.php?month=<?= $month ?>';

const tooltipCfg = {backgroundColor:'#252528',borderColor:'#2E2E33',borderWidth:1,titleColor:'#E8E8E6',bodyColor:'#9A9A96'};

// ── Dados PHP para JS ────────────────────────────────────
const flowData   = <?= json_encode(array_values($dailyFlow)) ?>;
const catData    = <?= json_encode(array_values($byCategory)) ?>;
const catColors  = ['#ef4444','#f59e0b','#3483FA','#a855f7','#22c55e','#06b6d4','#f97316','#84cc16'];
const dreValues  = <?= json_encode([
  ['Rec. Bruta',  (float)($dreMap['RECEITA_BRUTA']  ?? 0)],
  ['Lucro Bruto', (float)($dreMap['LUCRO_BRUTO']    ?? 0)],
  ['EBITDA',      (float)($dreMap['EBITDA']         ?? 0)],
  ['Luc. Líq.',   (float)($dreMap['LUCRO_LIQUIDO']  ?? 0)],
]) ?>;
const currentTab = '<?= $tab ?>';
const aPagarVal   = <?= (float)$aPagar ?>;
const aReceberVal = <?= (float)$aReceber ?>;
const overdueCount= <?= count($overdue) ?>;
const receitas    = <?= (float)$receitas ?>;
const despesas    = <?= (float)$despesas ?>;

document.addEventListener('DOMContentLoaded', () => {

// ── Gráfico fluxo (sempre visível) ──────────────────────
if (flowData.length) {
  registerChart('fin-flow', new Chart(document.getElementById('flowChart'), {
    type:'bar',
    data:{
      labels: flowData.map(d=>new Date(d.day+'T00:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'})),
      datasets:[
        {label:'Entradas',data:flowData.map(d=>parseFloat(d.credits)),backgroundColor:'rgba(34,197,94,0.7)',borderColor:'#22c55e',borderWidth:1,borderRadius:4},
        {label:'Saídas',  data:flowData.map(d=>parseFloat(d.debits)), backgroundColor:'rgba(239,68,68,0.7)', borderColor:'#ef4444',borderWidth:1,borderRadius:4}
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{...tooltipCfg,callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}},scales:{x:{grid:{color:'#2E2E3355'},ticks:{color:'#5E5E5A'}},y:{grid:{color:'#2E2E3355'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}}}
  }));
}

// ── Gráfico categorias (sempre visível) ─────────────────
if (catData.length) {
  registerChart('fin-cat', new Chart(document.getElementById('catChart'), {
    type:'doughnut',
    data:{labels:catData.map(d=>d.category||'Outros'),datasets:[{data:catData.map(d=>parseFloat(d.total)),backgroundColor:catColors.slice(0,catData.length),borderColor:'#1A1A1C',borderWidth:3}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',plugins:{legend:{display:false},tooltip:tooltipCfg}}
  }));
  const legend = document.getElementById('cat-legend');
  if (legend) catData.forEach((d,i)=>{
    legend.innerHTML+=`<div style="display:flex;align-items:center;justify-content:space-between;font-size:10px"><div style="display:flex;align-items:center;gap:5px"><div style="width:7px;height:7px;border-radius:50%;background:${catColors[i]}"></div><span style="color:#9A9A96">${d.category||'Outros'}</span></div><span style="color:#E8E8E6">R$ ${parseFloat(d.total).toFixed(2).replace('.',',')}</span></div>`;
  });
}

// ── Gráficos por aba ─────────────────────────────────────
function renderTabCharts(tab) {
  const container = document.getElementById('tab-charts');
  if (!container) return;

  // Destrói gráficos de aba anteriores
  ['tab-chart-1','tab-chart-2'].forEach(id => {
    if (Charts[id]) { Charts[id].destroy(); delete Charts[id]; }
  });
  container.innerHTML = '';

  if (tab === 'lancamentos') {
    // Gráfico: Receita vs Despesa do mês (barras)
    container.innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Receitas vs Despesas</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Comparativo do período</div>
          <canvas id="tc1" style="height:130px!important;max-height:130px"></canvas>
        </div>
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Evolução acumulada</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Saldo acumulado no mês</div>
          <canvas id="tc2" style="height:130px!important;max-height:130px"></canvas>
        </div>
      </div>`;

    // Receita vs Despesa
    registerChart('tab-chart-1', new Chart(document.getElementById('tc1'), {
      type: 'bar',
      data: {
        labels: ['Receitas', 'Despesas', 'Saldo'],
        datasets: [{
          data: [receitas, despesas, receitas - despesas],
          backgroundColor: ['rgba(34,197,94,.7)', 'rgba(239,68,68,.7)', receitas-despesas >= 0 ? 'rgba(52,131,250,.7)' : 'rgba(245,158,11,.7)'],
          borderColor:     ['#22c55e', '#ef4444', receitas-despesas >= 0 ? '#3483FA' : '#f59e0b'],
          borderWidth: 1, borderRadius: 6
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{...tooltipCfg, callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}}, scales:{x:{grid:{display:false},ticks:{color:'#9A9A96'}}, y:{grid:{color:'#2E2E3355'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}} }
    }));

    // Saldo acumulado (linha)
    const cumLabels = [], cumVals = [];
    let acc = 0;
    flowData.forEach(d => {
      acc += parseFloat(d.credits) - parseFloat(d.debits);
      cumLabels.push(new Date(d.day+'T00:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}));
      cumVals.push(parseFloat(acc.toFixed(2)));
    });
    registerChart('tab-chart-2', new Chart(document.getElementById('tc2'), {
      type: 'line',
      data: {
        labels: cumLabels,
        datasets: [{
          data: cumVals,
          borderColor: '#3483FA',
          backgroundColor: ctx => {
            const g = ctx.chart.ctx.createLinearGradient(0,0,0,150);
            g.addColorStop(0,'rgba(52,131,250,.25)'); g.addColorStop(1,'rgba(52,131,250,0)'); return g;
          },
          borderWidth: 2, fill: true, tension: 0.4,
          pointBackgroundColor: '#3483FA', pointBorderColor: '#1A1A1C', pointRadius: 3
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{...tooltipCfg, callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}}, scales:{x:{grid:{color:'#2E2E3322'},ticks:{color:'#5E5E5A'}}, y:{grid:{color:'#2E2E3322'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}} }
    }));

  } else if (tab === 'dre') {
    // DRE: barras principais + gauge de margem
    container.innerHTML = `
      <div class="fin-charts-grid" style="display:grid;gap:16px">
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Cascata do resultado</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Receita → Lucro Líquido</div>
          <canvas id="tc1" style="height:150px!important;max-height:150px"></canvas>
        </div>
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px;display:flex;flex-direction:column;align-items:center;justify-content:center">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px;align-self:flex-start">Margem líquida</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:20px;align-self:flex-start">Lucro / Receita bruta</div>
          <canvas id="tc2" style="height:110px!important;max-height:110px;max-width:110px"></canvas>
          <div id="margem-label" style="font-size:22px;font-weight:700;margin-top:12px;color:${receitas > 0 && (receitas-despesas)/receitas*100 >= 0 ? '#22c55e' : '#ef4444'}">${receitas > 0 ? ((receitas-despesas)/receitas*100).toFixed(1).replace('.',',') : '0,0'}%</div>
        </div>
      </div>`;

    // Cascata DRE
    registerChart('tab-chart-1', new Chart(document.getElementById('tc1'), {
      type: 'bar',
      data: {
        labels: dreValues.map(d=>d[0]),
        datasets: [{
          data: dreValues.map(d=>d[1]),
          backgroundColor: dreValues.map(d=>d[1]>=0?'rgba(52,131,250,.7)':'rgba(239,68,68,.7)'),
          borderColor:     dreValues.map(d=>d[1]>=0?'#3483FA':'#ef4444'),
          borderWidth: 1, borderRadius: 6
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{...tooltipCfg, callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}}, scales:{x:{grid:{display:false},ticks:{color:'#9A9A96'}}, y:{grid:{color:'#2E2E3322'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}} }
    }));

    // Gauge de margem (donut semi)
    const margem = receitas > 0 ? Math.max(0, Math.min(100, (receitas-despesas)/receitas*100)) : 0;
    const margemColor = margem >= 20 ? '#22c55e' : margem >= 10 ? '#f59e0b' : '#ef4444';
    registerChart('tab-chart-2', new Chart(document.getElementById('tc2'), {
      type: 'doughnut',
      data: {
        datasets: [{
          data: [margem, 100-margem],
          backgroundColor: [margemColor, '#2E2E33'],
          borderWidth: 0
        }]
      },
      options: { responsive:true, maintainAspectRatio:true, cutout:'75%', rotation:-90, circumference:180, plugins:{legend:{display:false}, tooltip:{enabled:false}} }
    }));

  } else if (tab === 'contas_pagar') {
    container.innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Situação dos pagamentos</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Pendente vs vencido</div>
          <canvas id="tc1" style="height:130px!important;max-height:130px"></canvas>
        </div>
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px;display:flex;flex-direction:column;justify-content:center;gap:12px">
          <div>
            <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Total a pagar</div>
            <div style="font-size:24px;font-weight:700;color:#f59e0b">R$ ${aPagarVal.toFixed(2).replace('.',',')}</div>
          </div>
          <div style="height:0.5px;background:#2E2E33"></div>
          <div>
            <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Contas vencidas</div>
            <div style="font-size:22px;font-weight:700;color:${overdueCount>0?'#ef4444':'#22c55e'}">${overdueCount} conta(s)</div>
          </div>
        </div>
      </div>`;

    registerChart('tab-chart-1', new Chart(document.getElementById('tc1'), {
      type: 'doughnut',
      data: {
        labels: ['Em dia', 'Vencidas'],
        datasets: [{
          data: [Math.max(0, aPagarVal - overdueCount*100), overdueCount > 0 ? overdueCount*100 : 0.01],
          backgroundColor: ['#f59e0b', '#ef4444'],
          borderColor: '#1A1A1C', borderWidth: 3
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, cutout:'65%', plugins:{legend:{display:false}, tooltip:tooltipCfg} }
    }));

  } else if (tab === 'contas_receber') {
    container.innerHTML = `
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px">
          <div style="font-size:13px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Recebimentos vs Despesas</div>
          <div style="font-size:11px;color:#5E5E5A;margin-bottom:12px">Perspectiva de caixa</div>
          <canvas id="tc1" style="height:130px!important;max-height:130px"></canvas>
        </div>
        <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:18px;display:flex;flex-direction:column;justify-content:center;gap:12px">
          <div>
            <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Total a receber</div>
            <div style="font-size:24px;font-weight:700;color:#3483FA">R$ ${aReceberVal.toFixed(2).replace('.',',')}</div>
          </div>
          <div style="height:0.5px;background:#2E2E33"></div>
          <div>
            <div style="font-size:11px;color:#5E5E5A;margin-bottom:4px">Saldo projetado</div>
            <div style="font-size:22px;font-weight:700;color:${aReceberVal-aPagarVal>=0?'#22c55e':'#ef4444'}">R$ ${(aReceberVal-aPagarVal).toFixed(2).replace('.',',')}</div>
          </div>
        </div>
      </div>`;

    registerChart('tab-chart-1', new Chart(document.getElementById('tc1'), {
      type: 'bar',
      data: {
        labels: ['A Receber', 'A Pagar', 'Saldo Proj.'],
        datasets: [{
          data: [aReceberVal, aPagarVal, aReceberVal - aPagarVal],
          backgroundColor: ['rgba(52,131,250,.7)', 'rgba(245,158,11,.7)', aReceberVal-aPagarVal>=0?'rgba(34,197,94,.7)':'rgba(239,68,68,.7)'],
          borderColor:     ['#3483FA', '#f59e0b', aReceberVal-aPagarVal>=0?'#22c55e':'#ef4444'],
          borderWidth: 1, borderRadius: 6
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{...tooltipCfg, callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}}, scales:{x:{grid:{display:false},ticks:{color:'#9A9A96'}}, y:{grid:{color:'#2E2E3322'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}} }
    }));
  }
}

// Renderiza gráficos da aba atual
renderTabCharts(currentTab);

// DRE chart (dentro da aba DRE)
if (currentTab === 'dre' && document.getElementById('dreChart')) {
  registerChart('fin-dre', new Chart(document.getElementById('dreChart'), {
    type:'bar',
    data:{labels:dreValues.map(d=>d[0]),datasets:[{data:dreValues.map(d=>d[1]),backgroundColor:dreValues.map(d=>d[1]>=0?'rgba(52,131,250,0.7)':'rgba(239,68,68,0.7)'),borderColor:dreValues.map(d=>d[1]>=0?'#3483FA':'#ef4444'),borderWidth:1,borderRadius:6}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{...tooltipCfg,callbacks:{label:ctx=>` R$ ${ctx.parsed.y.toFixed(2).replace('.',',')}`}}},scales:{x:{grid:{display:false},ticks:{color:'#9A9A96'}},y:{grid:{color:'#2E2E3355'},ticks:{color:'#5E5E5A',callback:v=>'R$'+v}}}}
  }));
}

// ── Helpers ──────────────────────────────────────────────
function formatCurrency(el) {
  // Permite digitar livremente: 50 = R$ 50,00 | 50,50 = R$ 50,50
  let raw = el.value.replace(/[^0-9,]/g, '');
  // Garante no máximo uma vírgula
  const parts = raw.split(',');
  if (parts.length > 2) raw = parts[0] + ',' + parts.slice(1).join('');
  // Limita casas decimais a 2
  if (parts.length === 2 && parts[1].length > 2) raw = parts[0] + ',' + parts[1].substring(0, 2);
  el.value = raw;
}

}); // fim DOMContentLoaded charts

function toggleDates() {
  const status = document.getElementById('e-status').value;
  document.getElementById('e-paid-wrap').style.display = status==='PAID' ? 'block' : 'none';
  document.getElementById('e-due-wrap').style.display  = status==='PENDING' ? 'block' : 'none';
}

function toggleRecurrence() {
  const on = document.getElementById('e-recurring').checked;
  document.getElementById('recurrence-fields').style.display = on ? 'grid' : 'none';
}

function updateDreCategory() {
  const sel = document.getElementById('e-coa');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('e-dre-category').value = opt?.dataset.dre || '';
}

// ── Modal lançamento ─────────────────────────────────────
function openEntry(direction) {
  document.getElementById('e-id').value = '';
  document.getElementById('e-direction').value = direction;
  document.getElementById('e-amount').value = '';
  document.getElementById('e-description').value = '';
  document.getElementById('e-entry-date').value = '<?= date('Y-m-d') ?>';
  document.getElementById('e-due-date').value = '';
  document.getElementById('e-paid-date').value = '<?= date('Y-m-d') ?>';
  document.getElementById('e-status').value = 'PAID';
  document.getElementById('e-coa').value = '';
  document.getElementById('e-account').value = '';
  document.getElementById('e-notes').value = '';
  document.getElementById('e-recurring').checked = false;
  document.getElementById('recurrence-fields').style.display = 'none';
  document.getElementById('e-dre-category').value = '';

  const isCredit = direction === 'CREDIT';
  document.getElementById('entry-indicator').style.background = isCredit ? '#22c55e' : '#ef4444';
  document.getElementById('entry-modal-title').textContent = isCredit ? 'Nova receita' : 'Nova despesa';
  document.getElementById('entry-modal').style.display = 'flex';
  toggleDates();
  lucide.createIcons();
}

function editEntry(e) {
  document.getElementById('e-id').value          = e.id;
  document.getElementById('e-direction').value   = e.direction;
  // Exibe valor existente no formato brasileiro sem transformações
  const amtNum = parseFloat(e.amount);
  document.getElementById('e-amount').value = amtNum.toFixed(2).replace('.', ',');
  document.getElementById('e-description').value = e.description;
  document.getElementById('e-entry-date').value  = e.entry_date;
  document.getElementById('e-due-date').value    = e.due_date || '';
  document.getElementById('e-paid-date').value   = e.paid_date || '<?= date('Y-m-d') ?>';
  document.getElementById('e-status').value      = e.status;
  document.getElementById('e-coa').value         = e.coa_id || '';
  document.getElementById('e-account').value     = e.account_id || '';
  document.getElementById('e-notes').value       = e.notes || '';
  document.getElementById('e-dre-category').value= e.dre_category || '';

  const isCredit = e.direction === 'CREDIT';
  document.getElementById('entry-indicator').style.background = isCredit ? '#22c55e' : '#ef4444';
  document.getElementById('entry-modal-title').textContent = 'Editar lançamento';
  document.getElementById('entry-modal').style.display = 'flex';
  toggleDates();
  lucide.createIcons();
}

function closeEntryModal() { document.getElementById('entry-modal').style.display = 'none'; }

async function saveEntry() {
  // Parse: remove pontos de milhar, troca vírgula decimal por ponto
  const amountRaw = document.getElementById('e-amount').value.trim();
  const amount = amountRaw.replace(/\./g,'').replace(',','.');
  const desc   = document.getElementById('e-description').value.trim();
  if (!desc || !amount || parseFloat(amount)<=0) { toast('Preencha descrição e valor','error'); return; }

  const fd = new FormData();
  fd.append('action',         'save_entry');
  fd.append('id',             document.getElementById('e-id').value);
  fd.append('direction',      document.getElementById('e-direction').value);
  fd.append('amount',         amount);
  fd.append('description',    desc);
  fd.append('entry_date',     document.getElementById('e-entry-date').value);
  fd.append('due_date',       document.getElementById('e-due-date').value);
  fd.append('paid_date',      document.getElementById('e-paid-date').value);
  fd.append('status',         document.getElementById('e-status').value);
  fd.append('coa_id',         document.getElementById('e-coa').value);
  fd.append('account_id',     document.getElementById('e-account').value);
  fd.append('notes',          document.getElementById('e-notes').value);
  fd.append('dre_category',   document.getElementById('e-dre-category').value);
  fd.append('is_recurring',   document.getElementById('e-recurring').checked ? '1' : '0');
  fd.append('recurrence_type',document.getElementById('e-recurrence-type')?.value || '');
  fd.append('recurrence_end', document.getElementById('e-recurrence-end')?.value || '');

  const btn = document.getElementById('btn-save-entry');
  btn.disabled = true; btn.textContent = 'Salvando...';
  const r = await fetch('/pages/financeiro.php',{method:'POST',body:fd});
  const d = await r.json();
  btn.disabled = false; btn.textContent = 'Salvar lançamento';

  if (d.ok) { toast('Lançamento salvo!','success'); closeEntryModal(); refreshCharts(); setTimeout(()=>location.reload(),500); }
  else toast(d.error,'error');
}

async function payEntry(id) {
  const fd = new FormData(); fd.append('action','pay_entry'); fd.append('id',id);
  const r = await fetch('/pages/financeiro.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Marcado como pago!','success'); refreshCharts(); setTimeout(()=>location.reload(),500); }
}

async function deleteEntry(id) {
  if (!await dialog({title:'Excluir lançamento',message:'Esta ação não pode ser desfeita.',confirmText:'Excluir',danger:true})) return;
  const fd = new FormData(); fd.append('action','delete_entry'); fd.append('id',id);
  const r = await fetch('/pages/financeiro.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Removido','success'); setTimeout(()=>location.reload(),300); }
}

function openAccountModal() { document.getElementById('account-modal').style.display='flex'; }

async function saveAccount() {
  const name = document.getElementById('ac-name').value.trim();
  if (!name) { toast('Nome obrigatório','error'); return; }
  const meliAcct = document.getElementById('ac-meli-acct')?.value || '';
  if (!meliAcct) { toast('Selecione a loja ML','error'); return; }
  const balance = document.getElementById('ac-balance').value.replace(/\./g,'').replace(',','.');
  const fd = new FormData();
  fd.append('action',           'save_account');
  fd.append('name',             name);
  fd.append('type',             document.getElementById('ac-type').value);
  fd.append('bank_name',        document.getElementById('ac-bank').value);
  fd.append('balance',          balance);
  fd.append('meli_account_id',  meliAcct);
  const r = await fetch('/pages/financeiro.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) { toast('Conta criada!','success'); document.getElementById('account-modal').style.display='none'; setTimeout(()=>location.reload(),300); }
}



// ── Paginação ─────────────────────────────────────────────
function initPag(tbodyId, pagerId, perPage=10) {
  const t = document.getElementById(tbodyId); if(!t) return;
  const rows = Array.from(t.querySelectorAll('tr')).filter(r=>r.cells&&r.cells.length>1);
  if(rows.length<=perPage) return;
  let page=1; const total=rows.length, pages=Math.ceil(total/perPage);
  function render() {
    rows.forEach((r,i)=>r.style.display=(i>=(page-1)*perPage&&i<page*perPage)?'':'none');
    const p=document.getElementById(pagerId); if(!p) return;
    const s=(page-1)*perPage+1, e=Math.min(page*perPage,total);
    let h=`<span style="font-size:12px;color:#5E5E5A">${s}–${e} de ${total} lançamentos</span><div style="display:flex;gap:4px">`;
    h+=`<button onclick="finPg('${tbodyId}','${pagerId}',${page-1})" ${page<=1?'disabled':''} style="padding:5px 10px;border-radius:6px;border:0.5px solid #2E2E33;background:${page<=1?'transparent':'#252528'};color:${page<=1?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:12px">←</button>`;
    for(let i=Math.max(1,page-2);i<=Math.min(pages,page+2);i++) h+=`<button onclick="finPg('${tbodyId}','${pagerId}',${i})" style="padding:5px 9px;border-radius:6px;border:0.5px solid ${i===page?'#3483FA':'#2E2E33'};background:${i===page?'#3483FA':'transparent'};color:${i===page?'#fff':'#9A9A96'};cursor:pointer;font-size:12px;min-width:30px">${i}</button>`;
    h+=`<button onclick="finPg('${tbodyId}','${pagerId}',${page+1})" ${page>=pages?'disabled':''} style="padding:5px 10px;border-radius:6px;border:0.5px solid #2E2E33;background:${page>=pages?'transparent':'#252528'};color:${page>=pages?'#3E3E45':'#E8E8E6'};cursor:pointer;font-size:12px">→</button></div>`;
    p.innerHTML=h;
  }
  window['_finPage_'+tbodyId]=()=>page;
  window.finPg=(tid,pid,p)=>{if(tid===tbodyId&&p>=1&&p<=pages){page=p;render();}};
  render();
}
document.addEventListener('DOMContentLoaded',()=>{
  initPag('fin-tbody','fin-tbody-pager',10);
});

window.onChartsData = function(d) {
  if (!d.kpis) return;
  const fmt = v => 'R$ '+Math.abs(parseFloat(v)).toFixed(2).replace('.',',');
  if (document.getElementById('fin-credits')) document.getElementById('fin-credits').textContent = fmt(d.kpis.credits);
  if (document.getElementById('fin-debits'))  document.getElementById('fin-debits').textContent  = fmt(d.kpis.debits);
  if (document.getElementById('fin-saldo'))   document.getElementById('fin-saldo').textContent   = (d.kpis.balance>=0?'+':'')+'R$ '+Math.abs(d.kpis.balance).toFixed(2).replace('.',',');
  if (d.daily_flow && Charts['fin-flow']) {
    updateChartData('fin-flow', d.daily_flow.map(r=>new Date(r.day+'T00:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'})), [d.daily_flow.map(r=>parseFloat(r.credits)), d.daily_flow.map(r=>parseFloat(r.debits))]);
  }
};
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
