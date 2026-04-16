<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente']);

$tipo      = $_GET['tipo']       ?? 'vendas';
$data_ini  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_ini'] ?? '') ? $_GET['data_ini'] : date('Y-m-01');
$data_fim  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'] ?? '') ? $_GET['data_fim'] : date('Y-m-t');

$dados = [];
$total_geral = 0;

switch ($tipo) {
    case 'vendas':
        $stmt = $db->prepare("SELECT DATE(data_venda) as data, COUNT(*) as qtd_vendas, SUM(total) as total, SUM(desconto) as descontos, GROUP_CONCAT(DISTINCT forma_pagamento) as formas FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ? AND status='finalizada' GROUP BY DATE(data_venda) ORDER BY data DESC");
        $stmt->execute([$data_ini, $data_fim]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_geral = array_sum(array_column($dados, 'total'));
        break;

    case 'produtos':
        $stmt = $db->prepare("SELECT p.nome, p.unidade, SUM(vi.quantidade) as qtd_vendida, SUM(vi.total) as receita FROM venda_itens vi JOIN produtos p ON vi.produto_id=p.id JOIN vendas v ON vi.venda_id=v.id WHERE DATE(v.data_venda) BETWEEN ? AND ? AND v.status='finalizada' GROUP BY p.id ORDER BY receita DESC LIMIT 30");
        $stmt->execute([$data_ini, $data_fim]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_geral = array_sum(array_column($dados, 'receita'));
        break;

    case 'os':
        $stmt = $db->prepare("SELECT os.status, COUNT(*) as qtd, COALESCE(SUM(os.total_geral),0) as total FROM ordens_servico os WHERE DATE(os.data_abertura) BETWEEN ? AND ? GROUP BY os.status");
        $stmt->execute([$data_ini, $data_fim]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_geral = array_sum(array_column($dados, 'total'));
        break;

    case 'clientes':
        $stmt = $db->prepare("SELECT c.nome, COUNT(DISTINCT v.id) as qtd_compras, SUM(v.total) as total_gasto FROM clientes c JOIN vendas v ON v.cliente_id=c.id WHERE DATE(v.data_venda) BETWEEN ? AND ? AND v.status='finalizada' GROUP BY c.id ORDER BY total_gasto DESC LIMIT 20");
        $stmt->execute([$data_ini, $data_fim]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_geral = array_sum(array_column($dados, 'total_gasto'));
        break;

    case 'caixa':
        $stmt = $db->prepare("SELECT cx.*, u.nome as operador FROM caixas cx LEFT JOIN usuarios u ON u.id=cx.usuario_abertura WHERE DATE(cx.data_abertura) BETWEEN ? AND ? ORDER BY cx.data_abertura DESC");
        // Fallback: table may be named 'caixa' not 'caixas'
        try { $stmt->execute([$data_ini, $data_fim]); $dados = $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) {
            $stmt = $db->prepare("SELECT cx.*, u.nome as operador FROM caixa cx LEFT JOIN usuarios u ON u.id=cx.usuario_abertura WHERE DATE(cx.data_abertura) BETWEEN ? AND ? ORDER BY cx.data_abertura DESC");
            $stmt->execute([$data_ini, $data_fim]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        break;
}

$formas_label = ['dinheiro'=>'Dinheiro','pix'=>'PIX','cartao_credito'=>'Crédito','cartao_debito'=>'Débito','boleto'=>'Boleto','mix'=>'Misto'];
$status_os_label = ['aberta'=>'Aberta','em_andamento'=>'Em Andamento','aguardando_pecas'=>'Aguard. Peças','finalizada'=>'Finalizada','cancelada'=>'Cancelada'];
$status_os_badge = ['aberta'=>'os-badge-blue','em_andamento'=>'os-badge-yellow','aguardando_pecas'=>'os-badge-yellow','finalizada'=>'os-badge-green','cancelada'=>'os-badge-red'];
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Relatórios</div>
  <div class="topbar-actions">
    <a href="gerar_relatorio_pdf.php?tipo=<?= urlencode($tipo) ?>&data_ini=<?= $data_ini ?>&data_fim=<?= $data_fim ?>"
       target="_blank" class="btn-os btn-os-ghost">
      <i class="ph-bold ph-file-pdf"></i> PDF
    </a>
  </div>
</header>

<main class="os-content">

<!-- Filtros -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div class="os-form-group">
        <label class="os-label">Tipo de Relatório</label>
        <select name="tipo" class="os-select" style="width:200px">
          <option value="vendas"   <?= $tipo==='vendas'  ?'selected':''?>>Vendas por Dia</option>
          <option value="produtos" <?= $tipo==='produtos'?'selected':''?>>Produtos Mais Vendidos</option>
          <option value="os"       <?= $tipo==='os'      ?'selected':''?>>Ordens de Serviço</option>
          <option value="clientes" <?= $tipo==='clientes'?'selected':''?>>Melhores Clientes</option>
          <option value="caixa"    <?= $tipo==='caixa'   ?'selected':''?>>Histórico de Caixa</option>
        </select>
      </div>
      <div class="os-form-group">
        <label class="os-label">Data Inicial</label>
        <input type="date" name="data_ini" class="os-input" value="<?= $data_ini ?>">
      </div>
      <div class="os-form-group">
        <label class="os-label">Data Final</label>
        <input type="date" name="data_fim" class="os-input" value="<?= $data_fim ?>">
      </div>
      <button type="submit" class="btn-os btn-os-primary" style="align-self:flex-end">
        <i class="ph-bold ph-chart-bar"></i> Gerar
      </button>
    </form>
  </div>
</div>

<!-- Resultado -->
<div class="os-card">
  <div class="os-card-header" style="justify-content:space-between">
    <div class="os-card-title">
      <i class="ph-bold ph-chart-bar"></i>
      <?= ['vendas'=>'Vendas por Dia','produtos'=>'Top 30 Produtos','os'=>'Ordens de Serviço','clientes'=>'Melhores Clientes','caixa'=>'Histórico de Caixa'][$tipo] ?>
    </div>
    <span style="font-size:.8rem;color:var(--text-muted)"><?= date('d/m/Y', strtotime($data_ini)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></span>
  </div>
  <div class="os-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="os-table">

        <?php if ($tipo === 'vendas'): ?>
        <thead><tr><th>Data</th><th style="text-align:center">Vendas</th><th style="text-align:right">Descontos</th><th style="text-align:right">Total</th><th>Formas</th></tr></thead>
        <tbody>
          <?php if (empty($dados)): ?><tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:32px">Sem dados no período.</td></tr><?php endif; ?>
          <?php foreach ($dados as $r): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($r['data'])) ?></td>
            <td style="text-align:center"><span class="os-badge os-badge-blue"><?= $r['qtd_vendas'] ?></span></td>
            <td style="text-align:right;color:#ef4444">R$ <?= number_format($r['descontos']??0,2,',','.') ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($r['total'],2,',','.') ?></td>
            <td style="font-size:.78rem;color:var(--text-muted)"><?= htmlspecialchars($r['formas']??'') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!empty($dados)): ?>
          <tr style="background:var(--bg-card2)">
            <td colspan="3" style="text-align:right;font-weight:700">TOTAL:</td>
            <td style="text-align:right;font-weight:800;font-size:1.1rem;color:var(--accent)">R$ <?= number_format($total_geral,2,',','.') ?></td>
            <td></td>
          </tr>
          <?php endif; ?>
        </tbody>

        <?php elseif ($tipo === 'produtos'): ?>
        <thead><tr><th>#</th><th>Produto</th><th style="text-align:center">Qtd. Vendida</th><th style="text-align:right">Receita</th></tr></thead>
        <tbody>
          <?php if (empty($dados)): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px">Sem dados.</td></tr><?php endif; ?>
          <?php foreach ($dados as $i => $r): ?>
          <tr>
            <td style="color:var(--text-muted);font-size:.82rem"><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($r['nome']) ?></strong></td>
            <td style="text-align:center"><?= number_format($r['qtd_vendida'],2) ?> <?= htmlspecialchars($r['unidade']) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($r['receita'],2,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <?php elseif ($tipo === 'os'): ?>
        <thead><tr><th>Status</th><th style="text-align:center">Quantidade</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php if (empty($dados)): ?><tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px">Sem dados.</td></tr><?php endif; ?>
          <?php foreach ($dados as $r): ?>
          <tr>
            <td><span class="os-badge <?= $status_os_badge[$r['status']]??'os-badge-gray' ?>"><?= $status_os_label[$r['status']]??$r['status'] ?></span></td>
            <td style="text-align:center;font-weight:700"><?= $r['qtd'] ?></td>
            <td style="text-align:right;font-weight:700">R$ <?= number_format($r['total'],2,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <?php elseif ($tipo === 'clientes'): ?>
        <thead><tr><th>Cliente</th><th style="text-align:center">Compras</th><th style="text-align:right">Total Gasto</th></tr></thead>
        <tbody>
          <?php if (empty($dados)): ?><tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:32px">Sem dados.</td></tr><?php endif; ?>
          <?php foreach ($dados as $r): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['nome']) ?></strong></td>
            <td style="text-align:center"><?= $r['qtd_compras'] ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent)">R$ <?= number_format($r['total_gasto'],2,',','.') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>

        <?php elseif ($tipo === 'caixa'): ?>
        <thead><tr><th>Abertura</th><th>Fechamento</th><th>Operador</th><th style="text-align:right">Saldo Ini.</th><th style="text-align:right">Vendas</th><th style="text-align:right">Sangrias</th><th style="text-align:right">Saldo Final</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($dados)): ?><tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:32px">Sem dados.</td></tr><?php endif; ?>
          <?php foreach ($dados as $cx):
            $sf = ($cx['saldo_final'] ?? $cx['saldo_inicial'] + $cx['total_vendas'] + $cx['total_suprimentos'] - $cx['total_sangrias']);
          ?>
          <tr>
            <td style="font-size:.78rem"><?= date('d/m/Y H:i', strtotime($cx['data_abertura'])) ?></td>
            <td style="font-size:.78rem"><?= $cx['data_fechamento']?date('d/m/Y H:i',strtotime($cx['data_fechamento'])):'-' ?></td>
            <td><?= htmlspecialchars($cx['operador']??'') ?></td>
            <td style="text-align:right">R$ <?= number_format($cx['saldo_inicial'],2,',','.') ?></td>
            <td style="text-align:right;color:var(--success);font-weight:600">R$ <?= number_format($cx['total_vendas'],2,',','.') ?></td>
            <td style="text-align:right;color:#ef4444">R$ <?= number_format($cx['total_sangrias'],2,',','.') ?></td>
            <td style="text-align:right;font-weight:700">R$ <?= number_format($sf,2,',','.') ?></td>
            <td><span class="os-badge <?= $cx['status']==='aberto'?'os-badge-green':'os-badge-gray' ?>"><?= ucfirst($cx['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <?php endif; ?>

      </table>
    </div>
  </div>
</div>

</main>
<?php include '../../includes/footer.php'; ?>
