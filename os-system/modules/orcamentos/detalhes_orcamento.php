<?php
require_once '../../config/config.php';
checkAuth();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT o.*, c.nome as cliente_nome, c.telefone, c.email, c.endereco,
                       m.modelo, m.placa, m.marca, m.ano, m.cor
                       FROM orcamentos o
                       JOIN clientes c ON o.cliente_id = c.id
                       JOIN motos m ON o.moto_id = m.id
                       WHERE o.id = ?");
$stmt->execute([$id]);
$orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orcamento) { echo "<p class='text-danger'>Orçamento não encontrado</p>"; exit; }

$stmt = $db->prepare("SELECT oi.*,
                       CASE WHEN oi.tipo = 'servico' THEN s.nome ELSE p.nome END as nome_item
                       FROM orcamento_itens oi
                       LEFT JOIN servicos s ON oi.tipo = 'servico' AND oi.item_id = s.id
                       LEFT JOIN produtos p ON oi.tipo = 'produto' AND oi.item_id = p.id
                       WHERE oi.orcamento_id = ?");
$stmt->execute([$id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mão de obra
$valor_hora = 0;
try {
    $stmt_mao = $db->prepare("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1");
    $stmt_mao->execute();
    $mao = $stmt_mao->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao['valor_hora'] ?? 0);
} catch(Exception $e) {}

$total_itens = 0;
$total_horas = 0;
foreach ($itens as $item) {
    $total_itens += $item['quantidade'] * $item['valor_unitario'];
    if ($item['tipo'] === 'servico') {
        // Get tempo_estimado for this service
        try {
            $stmt_svc = $db->prepare("SELECT tempo_estimado FROM servicos WHERE id = ?");
            $stmt_svc->execute([$item['item_id']]);
            $svc = $stmt_svc->fetch(PDO::FETCH_ASSOC);
            $total_horas += (float)($svc['tempo_estimado'] ?? 0) * $item['quantidade'] / 60;
        } catch(Exception $e) {}
    }
}
$total_mao_obra = round($total_horas * $valor_hora, 2);
$total_geral = $total_itens + $total_mao_obra;

$status_badge = ['ativo'=>'warning','aprovado'=>'info','rejeitado'=>'danger','convertido'=>'primary'];
$status_text  = ['ativo'=>'Ativo','aprovado'=>'Aprovado','rejeitado'=>'Rejeitado','convertido'=>'Convertido'];
?>
<style>
.orc-box {
  background: var(--bg-card2, #f8fafc);
  border: 1px solid var(--border, #e2e8f4);
  border-radius: 8px;
  padding: 14px 16px;
  margin-bottom: 12px;
  color: var(--text, #1a2035);
}
.orc-box-label {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--accent, #f59e0b);
  margin-bottom: 8px;
}
.orc-box p { margin: 3px 0; font-size: .85rem; color: var(--text, #1a2035); }
.orc-box strong { color: var(--text-muted, #64748b); font-weight: 600; }
.orc-table { width: 100%; border-collapse: collapse; font-size: .83rem; color: var(--text, #1a2035); }
.orc-table th {
  background: var(--bg-card2, #f1f5f9);
  color: var(--text-muted, #64748b);
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  padding: 8px 10px;
  border-bottom: 1px solid var(--border, #e2e8f4);
  text-align: left;
}
.orc-table td {
  padding: 8px 10px;
  border-bottom: 1px solid var(--border-light, #edf2f7);
  color: var(--text, #1a2035);
}
.orc-table tfoot td {
  border-top: 2px solid var(--border, #e2e8f4);
  font-weight: 700;
  padding: 10px;
}
.badge-servico { background: rgba(56,189,248,.15); color: #38bdf8; padding: 2px 8px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
.badge-produto  { background: rgba(34,197,94,.15);  color: #22c55e; padding: 2px 8px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
.total-row { background: rgba(245,158,11,.08); }
.total-row td { color: var(--accent, #f59e0b) !important; font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; }
.section-title {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: .8rem;
  color: var(--accent, #f59e0b);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin: 14px 0 8px;
  padding-bottom: 4px;
  border-bottom: 1px solid var(--border, #e2e8f4);
}
</style>

<!-- Cabeçalho -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
  <div>
    <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;color:var(--text)"><?= htmlspecialchars($orcamento['numero_orcamento']) ?></div>
    <div style="font-size:.78rem;color:var(--text-muted)">Criado em <?= date('d/m/Y H:i', strtotime($orcamento['data_criacao'])) ?> · Válido até <?= date('d/m/Y', strtotime($orcamento['data_validade'])) ?></div>
  </div>
  <span class="badge bg-<?= $status_badge[$orcamento['status']] ?? 'secondary' ?> px-3 py-2"><?= $status_text[$orcamento['status']] ?? $orcamento['status'] ?></span>
</div>

<!-- Cliente e Moto -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
  <div class="orc-box">
    <div class="orc-box-label"><i class="ph-bold ph-user"></i> Cliente</div>
    <p><strong>Nome:</strong> <?= htmlspecialchars($orcamento['cliente_nome']) ?></p>
    <p><strong>Telefone:</strong> <?= htmlspecialchars($orcamento['telefone'] ?: '-') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($orcamento['email'] ?: '-') ?></p>
    <p><strong>Endereço:</strong> <?= htmlspecialchars($orcamento['endereco'] ?: '-') ?></p>
  </div>
  <div class="orc-box">
    <div class="orc-box-label"><i class="ph-bold ph-motorcycle"></i> Moto</div>
    <p><strong>Modelo:</strong> <?= htmlspecialchars($orcamento['modelo']) ?></p>
    <p><strong>Marca:</strong> <?= htmlspecialchars($orcamento['marca'] ?: '-') ?></p>
    <p><strong>Placa:</strong> <?= htmlspecialchars($orcamento['placa']) ?></p>
    <p><strong>Ano:</strong> <?= htmlspecialchars($orcamento['ano'] ?? '-') ?></p>
  </div>
</div>

<!-- Itens -->
<div class="section-title"><i class="ph-bold ph-list-bullets"></i> Itens do Orçamento</div>
<table class="orc-table">
  <thead>
    <tr>
      <th>Item</th>
      <th>Tipo</th>
      <th style="text-align:center">Qtd</th>
      <th style="text-align:right">Valor Unit.</th>
      <th style="text-align:right">Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($itens as $item): ?>
    <tr>
      <td><?= htmlspecialchars($item['nome_item'] ?? '-') ?></td>
      <td><?= $item['tipo'] === 'servico' ? '<span class="badge-servico">Serviço</span>' : '<span class="badge-produto">Produto</span>' ?></td>
      <td style="text-align:center"><?= $item['quantidade'] ?></td>
      <td style="text-align:right">R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
      <td style="text-align:right">R$ <?= number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <?php if ($total_mao_obra > 0): ?>
    <tr>
      <td colspan="4" style="text-align:right;color:var(--text-muted);font-size:.83rem">Subtotal Peças/Serviços</td>
      <td style="text-align:right">R$ <?= number_format($total_itens, 2, ',', '.') ?></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align:right;color:var(--text-muted);font-size:.83rem">
        Mão de Obra (<?= number_format($total_horas, 1) ?>h × R$ <?= number_format($valor_hora, 2, ',', '.') ?>/h)
      </td>
      <td style="text-align:right">R$ <?= number_format($total_mao_obra, 2, ',', '.') ?></td>
    </tr>
    <?php endif; ?>
    <tr class="total-row">
      <td colspan="4" style="text-align:right">TOTAL GERAL</td>
      <td style="text-align:right">R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
    </tr>
  </tfoot>
</table>

<?php if ($orcamento['observacoes']): ?>
<div class="section-title"><i class="ph-bold ph-note"></i> Observações</div>
<div class="orc-box" style="font-size:.85rem"><?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?></div>
<?php endif; ?>

<!-- Assinatura -->
<div style="display:flex;justify-content:space-between;margin-top:20px;padding-top:12px;border-top:1px solid var(--border)">
  <div style="font-size:.8rem;color:var(--text-muted)">
    <strong style="color:var(--text)">Validade:</strong> <?= date('d/m/Y', strtotime($orcamento['data_validade'])) ?>
  </div>
  <div style="font-size:.8rem;color:var(--text-muted);text-align:right">
    _________________________<br>Assinatura do Cliente
  </div>
</div>
