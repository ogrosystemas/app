<?php
/**
 * api/lucratividade_export.php
 * Exporta lucratividade por produto em Excel
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

session_start_secure();
$user = auth_user();
if (!$user) { http_response_code(401); exit; }

$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;
$acctSql  = $acctId ? " AND p.meli_account_id=?" : "";
$acctP    = $acctId ? [$acctId] : [];

function luc_exp_get(string $tenantId, string $key, mixed $default = 0): mixed {
    $r = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?", [$tenantId, $key]);
    return $r ? $r['value'] : $default;
}

$cfg = [
    'imposto_pct' => (float)luc_exp_get($tenantId, 'luc_imposto',      6.0),
    'embalagem'   => (float)luc_exp_get($tenantId, 'luc_embalagem',    2.5),
    'frete_pct'   => (float)luc_exp_get($tenantId, 'luc_frete_pct',    0),
    'ads_pct'     => (float)luc_exp_get($tenantId, 'luc_ads_pct',      0),
    'custo_op'    => (float)luc_exp_get($tenantId, 'luc_custo_op',     0),
    'margem_alvo' => (float)luc_exp_get($tenantId, 'luc_margem_alvo',  20),
];

$pedidosMes  = (int)(db_one("SELECT COUNT(*) as c FROM orders WHERE tenant_id=? AND payment_status IN ('approved','APPROVED') AND order_date >= DATE_FORMAT(NOW(),'%Y-%m-01')", [$tenantId])['c'] ?? 1);
$custoOpUnit = $pedidosMes > 0 ? $cfg['custo_op'] / max($pedidosMes, 1) : 0;

$produtos = db_all(
    "SELECT p.id, p.meli_item_id, p.title, p.price, p.cost_price, p.ml_fee_percent, p.ipi_valor, p.ml_status, p.listing_type_id,
            COALESCE(SUM(oi.quantity),0) as unidades_vendidas, COALESCE(SUM(oi.total_price),0) as receita_total,
            COALESCE(AVG(oi.unit_price),p.price) as preco_medio_venda
     FROM products p
     LEFT JOIN order_items oi ON oi.meli_item_id = p.meli_item_id
     LEFT JOIN orders o ON o.id = oi.order_id AND o.tenant_id = p.tenant_id AND o.payment_status IN ('approved','APPROVED')
     WHERE p.tenant_id=?{$acctSql} GROUP BY p.id ORDER BY receita_total DESC",
    array_merge([$tenantId], $acctP)
);

function calcLuc(array $p, array $cfg, float $custoOpUnit): array {
    $preco     = (float)$p['preco_medio_venda'] ?: (float)$p['price'];
    $custo     = (float)$p['cost_price'];
    $ipiValor  = (float)($p['ipi_valor'] ?? 0);
    $feePct    = (float)$p['ml_fee_percent'] ?: 14.0;
    $custoReal = $custo + $ipiValor;
    $taxaFixa  = $preco < 12.50 ? $preco * 0.5 : ($preco < 79.00 ? 6.75 : 0);
    $comissao  = $preco * ($feePct / 100);
    $frete     = $preco * ($cfg['frete_pct'] / 100);
    $ads       = $preco * ($cfg['ads_pct'] / 100);
    $imposto   = $preco * ($cfg['imposto_pct'] / 100);
    $total     = $custoReal + $comissao + $taxaFixa + $frete + $ads + $imposto + $cfg['embalagem'] + $custoOpUnit;
    $lucro     = $preco - $total;
    $margem    = $preco > 0 ? ($lucro / $preco) * 100 : 0;
    $divisor   = 1 - ($feePct/100) - ($cfg['frete_pct']/100) - ($cfg['ads_pct']/100) - ($cfg['imposto_pct']/100);
    $precoMin  = $divisor > 0 ? ($custoReal + $taxaFixa + $cfg['embalagem'] + $custoOpUnit) / $divisor : $preco;
    $precoIdeal = $divisor - $cfg['margem_alvo']/100 > 0
        ? ($custoReal + $taxaFixa + $cfg['embalagem'] + $custoOpUnit) / ($divisor - $cfg['margem_alvo']/100) : $preco;
    $status = $lucro < 0 ? 'Prejuízo' : ($margem < $cfg['margem_alvo']/2 ? 'Crítico' : ($margem < $cfg['margem_alvo'] ? 'Atenção' : 'OK'));
    return compact('preco','custo','ipiValor','custoReal','comissao','taxaFixa','frete','ads','imposto','total','lucro','margem','precoMin','precoIdeal','status');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="lucratividade_' . date('Y-m-d') . '.xls"');

echo "\xEF\xBB\xBF";
echo "<html><head><meta charset='UTF-8'><style>
  body{font-family:Arial;font-size:10pt}
  th{background:#1a4a8a;color:#fff;padding:6px 8px;font-weight:bold;text-align:center;border:1px solid #ccc}
  td{padding:5px 8px;border:1px solid #ddd;vertical-align:top}
  tr:nth-child(even) td{background:#f5f8ff}
  .ok{color:#15803d;font-weight:bold}.atencao{color:#b45309}.critico{color:#c2410c}.prejuizo{color:#dc2626;font-weight:bold}
</style></head><body>";

echo "<h2>Lucratividade por Produto — " . date('d/m/Y') . "</h2>";
echo "<p>Meta de margem: {$cfg['margem_alvo']}% | Imposto: {$cfg['imposto_pct']}% | Embalagem: R$ {$cfg['embalagem']}/un | Frete: {$cfg['frete_pct']}% | ADS: {$cfg['ads_pct']}%</p>";

echo "<table><thead><tr>
  <th>Produto</th><th>Item ID ML</th><th>Status ML</th><th>Preço Venda</th>
  <th>Custo (CMV)</th><th>Comissão ML</th><th>Taxa Fixa ML</th><th>Frete</th>
  <th>ADS</th><th>Impostos</th><th>Embalagem</th><th>Custo Op.</th>
  <th>Lucro/un (R$)</th><th>Margem (%)</th><th>Preço Mín.</th><th>Preço Ideal</th>
  <th>Unid. Vendidas</th><th>Receita Total</th><th>Lucro Total</th><th>Status</th>
</tr></thead><tbody>";

foreach ($produtos as $p) {
    $l = calcLuc($p, $cfg, $custoOpUnit);
    $cls = $l['status'] === 'OK' ? 'ok' : (str_contains($l['status'],'Atenção') ? 'atencao' : (str_contains($l['status'],'Crítico') ? 'critico' : 'prejuizo'));
    $fmt = fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
    $lucroTotal = round($l['lucro'] * (int)$p['unidades_vendidas'], 2);
    echo "<tr>
      <td>" . htmlspecialchars($p['title']) . "</td>
      <td style='font-family:monospace'>{$p['meli_item_id']}</td>
      <td>{$p['ml_status']}</td>
      <td>{$fmt($l['preco'])}</td>
      <td>{$fmt($l['custo'])}</td>
      <td>{$fmt($l['comissao'])}</td>
      <td>{$fmt($l['taxaFixa'])}</td>
      <td>{$fmt($l['frete'])}</td>
      <td>{$fmt($l['ads'])}</td>
      <td>{$fmt($l['imposto'])}</td>
      <td>{$fmt($cfg['embalagem'])}</td>
      <td>{$fmt($custoOpUnit)}</td>
      <td class='$cls'>{$fmt($l['lucro'])}</td>
      <td class='$cls'>" . number_format($l['margem'], 1, ',', '.') . "%</td>
      <td>{$fmt($l['precoMin'])}</td>
      <td>{$fmt($l['precoIdeal'])}</td>
      <td style='text-align:center'>{$p['unidades_vendidas']}</td>
      <td>{$fmt($p['receita_total'])}</td>
      <td class='$cls'>{$fmt($lucroTotal)}</td>
      <td class='$cls'>{$l['status']}</td>
    </tr>";
}

echo "</tbody></table></body></html>";
