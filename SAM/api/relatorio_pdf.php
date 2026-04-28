<?php
/**
 * api/relatorio_pdf.php
 * Gera relatório financeiro em PDF limpo.
 *
 * Estratégia: gera HTML completo e auto-imprimível (sem sidebar, sem header,
 * sem botões) e envia com Content-Type text/html + script de auto-print.
 * O browser abre a janela de impressão com "Salvar como PDF".
 *
 * Parâmetros GET:
 *   month  = YYYY-MM  (padrão: mês atual)
 *   tipo   = dre | extrato | completo (padrão: completo)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();
auth_require();
license_check();

$user     = auth_user();
$tenantId = $user['tenant_id'];

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$tipo  = in_array($_GET['tipo'] ?? '', ['dre','extrato','completo']) ? $_GET['tipo'] : 'completo';

// ── Conta ativa ──────────────────────────────────────────
$acctId = $_SESSION['active_meli_account_id'] ?? null;

// ── Contas ML ativas (para filtrar transactions) ─────────
$activeAcctIds    = db_all("SELECT id, nickname FROM meli_accounts WHERE tenant_id=? AND is_active=1", [$tenantId]);
$activeAcctIdList = array_column($activeAcctIds, 'id');
$activeAcctIn     = count($activeAcctIdList)
    ? implode(',', array_fill(0, count($activeAcctIdList), '?'))
    : "'__none__'";
$txFilter = count($activeAcctIdList) ? " AND meli_account_id IN ({$activeAcctIn})" : " AND 1=0";

$monthLabel = date_ptbr('F \d\e Y', strtotime($month . '-01'));
$inicio     = $month . '-01';
$fim        = date('Y-m-t', strtotime($inicio));

// ── KPIs ─────────────────────────────────────────────────
$kpis = db_one(
    "SELECT
        COALESCE(SUM(CASE WHEN direction='CREDIT' AND status = 'PAID' THEN amount ELSE 0 END),0) as receitas,
        COALESCE(SUM(CASE WHEN direction='DEBIT'  AND status = 'PAID' THEN amount ELSE 0 END),0) as despesas,
        COALESCE(SUM(CASE WHEN status='PENDING'   AND direction='CREDIT' THEN amount ELSE 0 END),0) as a_receber,
        COALESCE(SUM(CASE WHEN status='PENDING'   AND direction='DEBIT'  THEN amount ELSE 0 END),0) as a_pagar
     FROM financial_entries WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?",
    [$tenantId, $month]
);

$vendasML = db_one(
    "SELECT COALESCE(SUM(amount),0) as total FROM transactions
     WHERE tenant_id=? AND direction='CREDIT' AND DATE_FORMAT(reference_date,'%Y-%m')=?{$txFilter}",
    array_merge([$tenantId, $month], $activeAcctIdList)
);

$receitas = (float)$kpis['receitas'] + (float)$vendasML['total'];
$despesas = (float)$kpis['despesas'];
$saldo    = $receitas - $despesas;
$aReceber = (float)$kpis['a_receber'];
$aPagar   = (float)$kpis['a_pagar'];
$margem   = $receitas > 0 ? round($saldo / $receitas * 100, 1) : 0;

// ── Lançamentos do mês ───────────────────────────────────
$entries = db_all(
    "SELECT fe.entry_date, fe.description, fe.direction, fe.amount, fe.status,
            fe.dre_category, b.name as bank_name, c.name as coa_name
     FROM financial_entries fe
     LEFT JOIN bank_accounts b      ON b.id = fe.account_id
     LEFT JOIN chart_of_accounts c  ON c.id = fe.coa_id
     WHERE fe.tenant_id=? AND DATE_FORMAT(fe.entry_date,'%Y-%m')=?
     ORDER BY fe.entry_date ASC, fe.created_at ASC",
    [$tenantId, $month]
);

// ── DRE encadeada ────────────────────────────────────────
$dreRaw = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM transactions WHERE tenant_id=? AND DATE_FORMAT(reference_date,'%Y-%m')=?
     AND dre_category IS NOT NULL{$txFilter}
     GROUP BY dre_category",
    array_merge([$tenantId, $month], $activeAcctIdList)
);
$dreEntriesRaw = db_all(
    "SELECT dre_category, SUM(CASE WHEN direction='CREDIT' THEN amount ELSE -amount END) as total
     FROM financial_entries WHERE tenant_id=? AND DATE_FORMAT(entry_date,'%Y-%m')=?
     AND dre_category IS NOT NULL AND status = 'PAID'
     GROUP BY dre_category",
    [$tenantId, $month]
);
$dreMap = [];
foreach (array_merge($dreRaw, $dreEntriesRaw) as $r) {
    $dreMap[$r['dre_category']] = ($dreMap[$r['dre_category']] ?? 0) + (float)$r['total'];
}
$dreMap['RECEITA_LIQUIDA'] = ($dreMap['RECEITA_BRUTA'] ?? $receitas) - abs($dreMap['DEDUCOES'] ?? 0);
$dreMap['LUCRO_BRUTO']     = $dreMap['RECEITA_LIQUIDA'] - abs($dreMap['CMV'] ?? 0);
$dreMap['EBITDA']          = $dreMap['LUCRO_BRUTO'] - abs($dreMap['DESPESAS_OPERACIONAIS'] ?? 0) - abs($dreMap['DESPESAS_FINANCEIRAS'] ?? 0);
$dreMap['LUCRO_LIQUIDO']   = $dreMap['EBITDA'] + abs($dreMap['OUTRAS_RECEITAS'] ?? 0) - abs($dreMap['OUTRAS_DESPESAS'] ?? 0);
$margemLiq = ($dreMap['RECEITA_LIQUIDA'] ?? 0) > 0
    ? round($dreMap['LUCRO_LIQUIDO'] / $dreMap['RECEITA_LIQUIDA'] * 100, 1) : 0;

// ── Conta bancária ativo ─────────────────────────────────
$bankAccounts = db_all("SELECT * FROM bank_accounts WHERE tenant_id=? AND is_active=1 ORDER BY name", [$tenantId]);
$totalBancos  = array_sum(array_column($bankAccounts, 'balance'));

// ── Helpers ──────────────────────────────────────────────
function brl(float $v): string {
    return 'R$ ' . number_format(abs($v), 2, ',', '.');
}
function color_val(float $v): string {
    return $v >= 0 ? '#166534' : '#991b1b';
}
function sign(float $v): string {
    return $v >= 0 ? '+' : '-';
}
function status_label(string $s): string {
    return match($s) {
        'PAID' => 'Pago',
        'PENDING'         => 'Pendente',
        'CANCELLED'       => 'Cancelado',
        'OVERDUE'         => 'Vencido',
        default           => $s,
    };
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Relatório Financeiro — <?= htmlspecialchars($monthLabel) ?></title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a1a;
    background: #fff;
    padding: 0;
  }

  /* ── Layout de página ─────────────────────────────── */
  .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 14mm 14mm 12mm; }

  /* ── Cabeçalho ────────────────────────────────────── */
  .report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #1a1a1a;
    padding-bottom: 10px;
    margin-bottom: 16px;
  }
  .report-header h1 { font-size: 18px; font-weight: 700; letter-spacing: -.3px; }
  .report-header .sub { font-size: 11px; color: #555; margin-top: 2px; }
  .report-header .meta { text-align: right; font-size: 10px; color: #777; line-height: 1.6; }

  /* ── KPIs ─────────────────────────────────────────── */
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
    margin-bottom: 16px;
  }
  .kpi-box {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 9px 10px;
  }
  .kpi-box .label { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
  .kpi-box .value { font-size: 14px; font-weight: 700; }
  .kpi-box .sub   { font-size: 9px; color: #aaa; margin-top: 2px; }

  /* ── Seções ───────────────────────────────────────── */
  .section-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #555;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 5px;
    margin: 16px 0 10px;
  }

  /* ── DRE ──────────────────────────────────────────── */
  .dre-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
  .dre-table td { padding: 5px 8px; font-size: 11px; }
  .dre-table .dre-group { background: #f5f5f5; font-weight: 600; font-size: 10px; color: #444; text-transform: uppercase; letter-spacing: .4px; }
  .dre-table .dre-row td:last-child { text-align: right; font-weight: 500; }
  .dre-table .dre-total { border-top: 1.5px solid #1a1a1a; font-weight: 700; }
  .dre-table .dre-total td:last-child { text-align: right; }
  .dre-table .dre-sub { color: #555; padding-left: 20px; }
  .dre-table .dre-deduct { color: #991b1b; }
  .dre-table .dre-positive { color: #166534; }
  .dre-table .dre-negative { color: #991b1b; }

  /* ── Tabela de lançamentos ────────────────────────── */
  .entries-table { width: 100%; border-collapse: collapse; font-size: 10px; }
  .entries-table thead tr { background: #f0f0f0; }
  .entries-table th {
    padding: 6px 8px;
    text-align: left;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    color: #666;
    border-bottom: 1px solid #ddd;
  }
  .entries-table td { padding: 5px 8px; border-bottom: 1px solid #efefef; vertical-align: top; }
  .entries-table tr:nth-child(even) td { background: #fafafa; }
  .entries-table .valor { text-align: right; font-weight: 600; }
  .entries-table .val-credit { color: #166534; }
  .entries-table .val-debit  { color: #991b1b; }
  .status-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 3px;
    font-size: 9px;
    font-weight: 600;
  }
  .badge-paid     { background: #dcfce7; color: #166534; }
  .badge-pending  { background: #fef9c3; color: #713f12; }
  .badge-overdue  { background: #fee2e2; color: #991b1b; }
  .badge-cancelled{ background: #f3f4f6; color: #6b7280; }

  /* ── Contas bancárias ─────────────────────────────── */
  .bank-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; }
  .bank-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 9px 10px; }
  .bank-box .bname { font-size: 10px; color: #888; margin-bottom: 3px; }
  .bank-box .bval  { font-size: 14px; font-weight: 700; color: #1a1a1a; }

  /* ── Rodapé ───────────────────────────────────────── */
  .report-footer {
    margin-top: 20px;
    border-top: 1px solid #ddd;
    padding-top: 8px;
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: #aaa;
  }

  /* ── Totais do extrato ────────────────────────────── */
  .totals-row { display: flex; gap: 16px; margin-top: 8px; padding: 8px 10px; background: #f5f5f5; border-radius: 6px; }
  .totals-row .t-item { flex: 1; }
  .totals-row .t-label { font-size: 9px; color: #888; }
  .totals-row .t-val { font-size: 13px; font-weight: 700; margin-top: 2px; }

  /* ── @media print ─────────────────────────────────── */
  @media print {
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { padding: 0; background: #fff; }
    .page { padding: 10mm 12mm; width: 100%; min-height: auto; }
    .no-print { display: none !important; }
    .page-break { page-break-before: always; }
    @page { size: A4 portrait; margin: 8mm; }
  }

  /* ── Toolbar (não imprime) ────────────────────────── */
  .toolbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    background: #1a1a1a;
    color: #fff;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 999;
    font-size: 12px;
    gap: 12px;
  }
  .toolbar button {
    padding: 7px 18px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
  }
  .btn-print { background: #3483FA; color: #fff; }
  .btn-close { background: #2E2E33; color: #E8E8E6; }
  .toolbar-spacer { height: 44px; }
</style>
</head>
<body>

<!-- Toolbar (só na tela, não imprime) -->
<div class="toolbar no-print">
  <div>
    <strong>Ogro ERP-WMS</strong>
    <span style="color:#9A9A96;margin-left:12px">Relatório Financeiro — <?= htmlspecialchars($monthLabel) ?></span>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn-print" onclick="window.print()">🖨️ Salvar como PDF</button>
    <button class="btn-close" onclick="window.close()">✕ Fechar</button>
  </div>
</div>
<div class="toolbar-spacer no-print"></div>

<!-- ══ PÁGINA DO RELATÓRIO ══════════════════════════════ -->
<div class="page">

  <!-- Cabeçalho -->
  <div class="report-header">
    <div>
      <h1><?= APP_NAME ?></h1>
      <div class="sub">Relatório Financeiro — <?= htmlspecialchars($monthLabel) ?></div>
      <div class="sub" style="margin-top:2px;color:#888">
        Conta: <?= htmlspecialchars($activeAcctIds[0]['nickname'] ?? 'Todas as contas') ?>
      </div>
    </div>
    <div class="meta">
      Gerado em <?= date('d/m/Y') ?> às <?= date('H:i') ?><br>
      Período: <?= date('d/m/Y', strtotime($inicio)) ?> a <?= date('d/m/Y', strtotime($fim)) ?><br>
      Por: <?= htmlspecialchars($user['name']) ?>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-row">
    <div class="kpi-box">
      <div class="label">Receitas pagas</div>
      <div class="value" style="color:#166534"><?= brl($receitas) ?></div>
      <div class="sub">incl. vendas ML</div>
    </div>
    <div class="kpi-box">
      <div class="label">Despesas pagas</div>
      <div class="value" style="color:#991b1b"><?= brl($despesas) ?></div>
      <div class="sub">lançamentos manuais</div>
    </div>
    <div class="kpi-box">
      <div class="label">Saldo do período</div>
      <div class="value" style="color:<?= color_val($saldo) ?>"><?= sign($saldo) ?> <?= brl($saldo) ?></div>
      <div class="sub">margem <?= number_format($margem, 1, ',', '.') ?>%</div>
    </div>
    <div class="kpi-box">
      <div class="label">A receber</div>
      <div class="value" style="color:#1d4ed8"><?= brl($aReceber) ?></div>
      <div class="sub">pendente</div>
    </div>
    <div class="kpi-box">
      <div class="label">A pagar</div>
      <div class="value" style="color:#b45309"><?= brl($aPagar) ?></div>
      <div class="sub">pendente</div>
    </div>
  </div>

  <?php if (!empty($bankAccounts)): ?>
  <!-- Contas bancárias -->
  <div class="section-title">🏦 Contas e Caixas</div>
  <div class="bank-grid">
    <?php foreach ($bankAccounts as $b): ?>
    <div class="bank-box">
      <div class="bname"><?= htmlspecialchars($b['name']) ?></div>
      <div class="bval"><?= brl((float)$b['balance']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="text-align:right;font-size:10px;color:#555;margin-bottom:4px">
    Total em caixa: <strong><?= brl($totalBancos) ?></strong>
  </div>
  <?php endif; ?>

  <?php if ($tipo === 'dre' || $tipo === 'completo'): ?>
  <!-- DRE -->
  <div class="section-title">📊 DRE — Demonstração do Resultado</div>
  <table class="dre-table">
    <tr class="dre-group"><td colspan="2">Receita</td></tr>
    <tr class="dre-row">
      <td class="dre-sub">Receita Bruta</td>
      <td class="dre-positive"><?= brl($dreMap['RECEITA_BRUTA'] ?? $receitas) ?></td>
    </tr>
    <?php if (($dreMap['DEDUCOES'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(-) Deduções / Devoluções</td>
      <td class="dre-deduct">(<?= brl($dreMap['DEDUCOES'] ?? 0) ?>)</td>
    </tr>
    <?php endif; ?>
    <tr class="dre-total">
      <td>Receita Líquida</td>
      <td style="color:<?= color_val($dreMap['RECEITA_LIQUIDA'] ?? 0) ?>"><?= brl($dreMap['RECEITA_LIQUIDA'] ?? $receitas) ?></td>
    </tr>

    <tr class="dre-group"><td colspan="2">Custos</td></tr>
    <?php if (($dreMap['CMV'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(-) CMV / Taxa ML / Frete</td>
      <td class="dre-deduct">(<?= brl(abs($dreMap['CMV'] ?? 0)) ?>)</td>
    </tr>
    <?php endif; ?>
    <tr class="dre-total">
      <td>Lucro Bruto</td>
      <td style="color:<?= color_val($dreMap['LUCRO_BRUTO'] ?? 0) ?>"><?= brl($dreMap['LUCRO_BRUTO'] ?? 0) ?></td>
    </tr>

    <tr class="dre-group"><td colspan="2">Despesas Operacionais</td></tr>
    <?php if (($dreMap['DESPESAS_OPERACIONAIS'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(-) Despesas Operacionais</td>
      <td class="dre-deduct">(<?= brl(abs($dreMap['DESPESAS_OPERACIONAIS'] ?? 0)) ?>)</td>
    </tr>
    <?php endif; ?>
    <?php if (($dreMap['DESPESAS_FINANCEIRAS'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(-) Despesas Financeiras</td>
      <td class="dre-deduct">(<?= brl(abs($dreMap['DESPESAS_FINANCEIRAS'] ?? 0)) ?>)</td>
    </tr>
    <?php endif; ?>
    <tr class="dre-total">
      <td>EBITDA</td>
      <td style="color:<?= color_val($dreMap['EBITDA'] ?? 0) ?>"><?= brl($dreMap['EBITDA'] ?? 0) ?></td>
    </tr>

    <tr class="dre-group"><td colspan="2">Resultado</td></tr>
    <?php if (($dreMap['OUTRAS_RECEITAS'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(+) Outras Receitas</td>
      <td class="dre-positive"><?= brl($dreMap['OUTRAS_RECEITAS'] ?? 0) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (($dreMap['OUTRAS_DESPESAS'] ?? 0) != 0): ?>
    <tr class="dre-row">
      <td class="dre-sub">(-) Outras Despesas</td>
      <td class="dre-deduct">(<?= brl(abs($dreMap['OUTRAS_DESPESAS'] ?? 0)) ?>)</td>
    </tr>
    <?php endif; ?>
    <tr class="dre-total" style="background:#f0f0f0">
      <td style="font-size:12px">Lucro Líquido</td>
      <td style="color:<?= color_val($dreMap['LUCRO_LIQUIDO'] ?? 0) ?>;font-size:13px"><?= brl($dreMap['LUCRO_LIQUIDO'] ?? 0) ?></td>
    </tr>
    <tr>
      <td colspan="2" style="text-align:right;font-size:10px;color:#888;padding:4px 8px">
        Margem líquida: <strong><?= number_format($margemLiq, 1, ',', '.') ?>%</strong>
      </td>
    </tr>
  </table>
  <?php endif; ?>

  <?php if ($tipo === 'extrato' || $tipo === 'completo'): ?>
  <!-- Extrato de lançamentos -->
  <?php if ($tipo === 'completo' && !empty($entries)): ?>
  <div class="page-break"></div>
  <?php endif; ?>

  <div class="section-title">📋 Extrato de Lançamentos — <?= htmlspecialchars($monthLabel) ?></div>

  <?php if (empty($entries)): ?>
  <p style="color:#888;font-size:11px;padding:12px 0">Nenhum lançamento no período.</p>
  <?php else: ?>

  <!-- Totais do extrato -->
  <div class="totals-row" style="margin-bottom:10px">
    <?php
    $totalCreditos = array_sum(array_map(fn($e) => $e['direction']==='CREDIT'?(float)$e['amount']:0, $entries));
    $totalDebitos  = array_sum(array_map(fn($e) => $e['direction']==='DEBIT' ?(float)$e['amount']:0, $entries));
    ?>
    <div class="t-item"><div class="t-label">Total de créditos</div><div class="t-val" style="color:#166534"><?= brl($totalCreditos) ?></div></div>
    <div class="t-item"><div class="t-label">Total de débitos</div><div class="t-val" style="color:#991b1b"><?= brl($totalDebitos) ?></div></div>
    <div class="t-item"><div class="t-label">Saldo do extrato</div><div class="t-val" style="color:<?= color_val($totalCreditos-$totalDebitos) ?>"><?= brl($totalCreditos-$totalDebitos) ?></div></div>
    <div class="t-item"><div class="t-label">Lançamentos</div><div class="t-val"><?= count($entries) ?></div></div>
  </div>

  <table class="entries-table">
    <thead>
      <tr>
        <th>Data</th>
        <th>Descrição</th>
        <th>Categoria</th>
        <th>Conta</th>
        <th>Status</th>
        <th style="text-align:right">Valor</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $e):
        $badgeClass = match($e['status']) {
            'PAID' => 'badge-paid',
            'PENDING'         => 'badge-pending',
            'OVERDUE'         => 'badge-overdue',
            default           => 'badge-cancelled',
        };
      ?>
      <tr>
        <td style="white-space:nowrap;color:#555"><?= date('d/m', strtotime($e['entry_date'])) ?></td>
        <td><?= htmlspecialchars(mb_substr($e['description'], 0, 45)) ?></td>
        <td style="color:#777"><?= htmlspecialchars($e['coa_name'] ?? ($e['dre_category'] ?? '—')) ?></td>
        <td style="color:#777"><?= htmlspecialchars($e['bank_name'] ?? '—') ?></td>
        <td><span class="status-badge <?= $badgeClass ?>"><?= status_label($e['status']) ?></span></td>
        <td class="valor <?= $e['direction']==='CREDIT' ? 'val-credit' : 'val-debit' ?>">
          <?= $e['direction']==='CREDIT' ? '+' : '-' ?> <?= brl((float)$e['amount']) ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Rodapé -->
  <div class="report-footer">
    <span><?= APP_NAME ?> — Relatório gerado automaticamente</span>
    <span>Período: <?= date('d/m/Y', strtotime($inicio)) ?> a <?= date('d/m/Y', strtotime($fim)) ?> · <?= date('d/m/Y H:i') ?></span>
  </div>

</div><!-- /page -->

<script>
// Auto-foca a janela para print estar pronto
window.addEventListener('load', function() {
  // Pequeno delay para garantir renderização
  setTimeout(() => window.focus(), 200);
});
</script>
</body>
</html>
