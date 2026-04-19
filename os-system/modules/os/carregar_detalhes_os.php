<?php
require_once '../../config/config.php';
checkAuth();

$id = $_GET['id'] ?? 0;

// Buscar dados da OS
$query = "SELECT os.*, c.nome as cliente_nome, c.telefone, c.email, c.endereco, 
          m.modelo as moto_modelo, m.placa, m.marca, m.ano, m.cor, m.chassi,
          u.nome as mecanico_nome
          FROM ordens_servico os 
          JOIN clientes c ON os.cliente_id = c.id 
          JOIN motos m ON os.moto_id = m.id 
          LEFT JOIN usuarios u ON os.created_by = u.id 
          WHERE os.id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$os) {
    echo "<div class='alert alert-danger'>OS não encontrada</div>";
    exit;
}

// Buscar serviços
$servicos = $db->query("SELECT os.*, s.nome as servico_nome, u.nome as mecanico 
                        FROM os_servicos os 
                        JOIN servicos s ON os.servico_id = s.id 
                        LEFT JOIN usuarios u ON os.mecanico_id = u.id 
                        WHERE os.os_id = $id")->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos
$produtos = $db->query("SELECT op.*, p.nome as produto_nome 
                        FROM os_produtos op 
                        JOIN produtos p ON op.produto_id = p.id 
                        WHERE op.os_id = $id")->fetchAll(PDO::FETCH_ASSOC);

$total_servicos = 0;
foreach($servicos as $s) {
    $total_servicos += $s['valor_unitario'] * $s['quantidade'];
}
$total_produtos = 0;
foreach($produtos as $p) {
    $total_produtos += $p['valor_unitario'] * $p['quantidade'];
}
// Mão de obra
$valor_hora = 0;
$total_horas = 0;
try {
    $stmt_mao = $db->prepare("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1");
    $stmt_mao->execute();
    $mao = $stmt_mao->fetch(PDO::FETCH_ASSOC);
    $valor_hora = (float)($mao['valor_hora'] ?? 0);
} catch(Exception $e) {}
foreach ($servicos as $s) {
    try {
        $stmt_t = $db->prepare("SELECT tempo_estimado FROM servicos WHERE id = ?");
        $stmt_t->execute([$s['servico_id']]);
        $svc_t = $stmt_t->fetch(PDO::FETCH_ASSOC);
        $total_horas += (float)($svc_t['tempo_estimado'] ?? 0) * $s['quantidade'] / 60;
    } catch(Exception $e) {}
}
$total_mao_obra = round($total_horas * $valor_hora, 2);
$total_geral = $total_servicos + $total_produtos + $total_mao_obra;

$status_lista = [
    'aberta' => 'Aberta',
    'em_andamento' => 'Em Andamento',
    'aguardando_pecas' => 'Aguardando Peças',
    'finalizada' => 'Finalizada',
    'cancelada' => 'Cancelada'
];

$status_badge = [
    'aberta' => 'warning',
    'em_andamento' => 'info',
    'aguardando_pecas' => 'danger',
    'finalizada' => 'success',
    'cancelada' => 'secondary'
];
?>
<style>
  /* Theme-aware styles — reads CSS vars from parent document */
  .info-box-os {
    background: var(--bg-card2, #f8f9fa);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid var(--border, #dee2e6);
    color: var(--text, #1a2035);
  }
  .info-box-os h6 { color: var(--text, #1a2035); margin-bottom: 8px; }
  .info-box-os p  { color: var(--text, #1a2035); margin-bottom: 4px; font-size: .875rem; }
  .info-box-os strong { color: var(--text-muted, #64748b); font-weight: 600; }
  .status-badge-os { font-size: 0.875rem; padding: 5px 12px; }
  .table-os {
    font-size: 13px;
    width: 100%;
    border-collapse: collapse;
    color: var(--text, #1a2035);
  }
  .table-os th, .table-os td {
    border: 1px solid var(--border, #dee2e6);
    padding: 8px 10px;
  }
  .table-os th {
    background: var(--bg-card2, #f1f1f1);
    color: var(--text-muted, #64748b);
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .05em;
    text-transform: uppercase;
  }
  .table-os td { background: transparent; }
  .table-os tfoot td {
    background: var(--bg-card2, #f8f9fa);
    font-weight: 700;
    color: var(--text, #1a2035);
  }
  .table-os .total-row { background: rgba(245,158,11,.1) !important; }
  .text-end   { text-align: right; }
  .text-center{ text-align: center; }
  .fw-bold    { font-weight: bold; }
  .mb-2 { margin-bottom: 8px; }
  .mb-3 { margin-bottom: 15px; }
  .mt-3 { margin-top: 15px; }
  .section-title {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: .85rem;
    color: var(--accent, #f59e0b);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin: 16px 0 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid var(--border, #dee2e6);
  }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="info-box-os">
                <h6><i class="ph-bold ph-user"></i> <strong>Cliente</strong></h6>
                <p class="mb-1"><strong>Nome:</strong> <?php echo $os['cliente_nome']; ?></p>
                <p class="mb-1"><strong>Telefone:</strong> <?php echo $os['telefone'] ?: '-'; ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo $os['email'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Endereço:</strong> <?php echo $os['endereco'] ?: '-'; ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box-os">
                <h6><i class="ph-bold ph-motorcycle"></i> <strong>Moto</strong></h6>
                <p class="mb-1"><strong>Modelo:</strong> <?php echo $os['moto_modelo']; ?></p>
                <p class="mb-1"><strong>Placa:</strong> <?php echo $os['placa']; ?></p>
                <p class="mb-1"><strong>Marca:</strong> <?php echo $os['marca'] ?: '-'; ?></p>
                <p class="mb-1"><strong>Ano:</strong> <?php echo $os['ano'] ?: '-'; ?></p>
                <p class="mb-1"><strong>Cor:</strong> <?php echo $os['cor'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Chassi:</strong> <?php echo $os['chassi'] ?: '-'; ?></p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="info-box-os">
                <h6><i class="ph-bold ph-calendar"></i> <strong>Datas</strong></h6>
                <p class="mb-1"><strong>Abertura:</strong> <?php echo date('d/m/Y H:i', strtotime($os['data_abertura'])); ?></p>
                <p class="mb-1"><strong>Previsão:</strong> <?php echo $os['data_previsao'] ? date('d/m/Y', strtotime($os['data_previsao'])) : '-'; ?></p>
                <p class="mb-0"><strong>Finalização:</strong> <?php echo $os['data_finalizacao'] ? date('d/m/Y H:i', strtotime($os['data_finalizacao'])) : '-'; ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-box-os">
                <h6><i class="ph-bold ph-tag"></i> <strong>Status</strong></h6>
                <p><span class="badge bg-<?php echo $status_badge[$os['status']]; ?> status-badge-os"><?php echo $status_lista[$os['status']]; ?></span></p>
            </div>
        </div>
    </div>
    
    <?php if(count($servicos) > 0): ?>
    <div class="info-box-os">
        <h6><i class="ph-bold ph-toolbox"></i> <strong>Serviços Realizados</strong></h6>
        <table class="table-os">
            <thead>
                <tr><th>Serviço</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php foreach($servicos as $s): ?>
                <tr>
                    <td><?php echo $s['servico_nome']; ?></td>
                    <td class="text-center"><?php echo $s['quantidade']; ?></td>
                    <td class="text-end">R$ <?php echo number_format($s['valor_unitario'], 2, ',', '.'); ?></td>
                    <td class="text-end">R$ <?php echo number_format($s['valor_unitario'] * $s['quantidade'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold"><td colspan="3" class="text-end">Total Serviços</td><td class="text-end">R$ <?php echo number_format($total_servicos, 2, ',', '.'); ?></td></tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if(count($produtos) > 0): ?>
    <div class="info-box-os">
        <h6><i class="ph-bold ph-package"></i> <strong>Produtos Utilizados</strong></h6>
        <table class="table-os">
            <thead><tr><th>Produto</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach($produtos as $p): ?>
                <tr>
                    <td><?php echo $p['produto_nome']; ?></td>
                    <td class="text-center"><?php echo $p['quantidade']; ?></td>
                    <td class="text-end">R$ <?php echo number_format($p['valor_unitario'], 2, ',', '.'); ?></td>
                    <td class="text-end">R$ <?php echo number_format($p['valor_unitario'] * $p['quantidade'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold"><td colspan="3" class="text-end">Total Produtos</td><td class="text-end">R$ <?php echo number_format($total_produtos, 2, ',', '.'); ?></td></tr>
                <tr class="fw-bold" class="total-row"><td colspan="3" class="text-end">TOTAL GERAL</td><td class="text-end">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></td></tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if($os['observacoes']): ?>
    <div class="info-box-os">
        <h6><i class="bi bi-chat-text"></i> <strong>Observações</strong></h6>
        <p><?php echo nl2br($os['observacoes']); ?></p>
    </div>
    <?php endif; ?>
</div>
