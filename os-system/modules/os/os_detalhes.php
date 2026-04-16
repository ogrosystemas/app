<?php
require_once '../../config/config.php';
checkAuth();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: os.php');
    exit;
}

$id = $_GET['id'];

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
    header('Location: os.php');
    exit;
}

// Buscar serviços da OS
$servicos = $db->query("SELECT os.*, s.nome as servico_nome, s.descricao, u.nome as mecanico 
                        FROM os_servicos os 
                        JOIN servicos s ON os.servico_id = s.id 
                        LEFT JOIN usuarios u ON os.mecanico_id = u.id 
                        WHERE os.os_id = $id")->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos da OS
$produtos = $db->query("SELECT op.*, p.nome as produto_nome 
                        FROM os_produtos op 
                        JOIN produtos p ON op.produto_id = p.id 
                        WHERE op.os_id = $id")->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_servicos = array_sum(array_column($servicos, 'valor_unitario'));
$total_produtos = array_sum(array_column($produtos, 'valor_unitario'));
$total_geral = $total_servicos + $total_produtos;

// Buscar histórico de status
$historico = $db->prepare("SELECT * FROM os_status_log WHERE os_id = ? ORDER BY created_at DESC"); $historico->execute([$id]); $historico = $historico->fetchAll(PDO::FETCH_ASSOC);

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
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Ordem de Serviço #</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-wrench"></i> Ordem de Serviço #<?php echo $os['numero_os']; ?></h2>
            <div>
                <span class="badge bg-<?php echo $status_badge[$os['status']]; ?> status-badge">
                    <i class="ph-bold ph-tag"></i> <?php echo $status_lista[$os['status']]; ?>
                </span>
                <button class="btn btn-danger ms-2 no-print" onclick="gerarPDF()">
                    <i class="ph-bold ph-file-pdf"></i> Gerar PDF
                </button>
                
                <a href="os.php" class="btn btn-primary ms-2 no-print">
                    <i class="ph-bold ph-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <div class="row">
            <!-- Coluna Esquerda -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="ph-bold ph-user"></i> Informações do Cliente</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nome:</strong> <?php echo $os['cliente_nome']; ?></p>
                        <p><strong>Telefone:</strong> <?php echo $os['telefone'] ?: '-'; ?></p>
                        <p><strong>Email:</strong> <?php echo $os['email'] ?: '-'; ?></p>
                        <p><strong>Endereço:</strong> <?php echo $os['endereco'] ?: '-'; ?></p>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="ph-bold ph-motorcycle"></i> Informações da Moto</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Modelo:</strong> <?php echo $os['moto_modelo']; ?></p>
                        <p><strong>Placa:</strong> <?php echo $os['placa']; ?></p>
                        <p><strong>Marca:</strong> <?php echo $os['marca'] ?: '-'; ?></p>
                        <p><strong>Ano:</strong> <?php echo $os['ano'] ?: '-'; ?></p>
                        <p><strong>Cor:</strong> <?php echo $os['cor'] ?: '-'; ?></p>
                        <p><strong>Chassi:</strong> <?php echo $os['chassi'] ?: '-'; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Coluna Direita -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="ph-bold ph-calendar"></i> Datas</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Abertura:</strong> <?php echo date('d/m/Y H:i', strtotime($os['data_abertura'])); ?></p>
                        <p><strong>Previsão:</strong> <?php echo $os['data_previsao'] ? date('d/m/Y', strtotime($os['data_previsao'])) : '-'; ?></p>
                        <p><strong>Finalização:</strong> <?php echo $os['data_finalizacao'] ? date('d/m/Y H:i', strtotime($os['data_finalizacao'])) : '-'; ?></p>
                        <p><strong>Mecânico:</strong> <?php echo $os['mecanico_nome'] ?: '-'; ?></p>
                    </div>
                </div>
                
                <?php if($os['status'] != 'finalizada' && $os['status'] != 'cancelada'): ?>
                <div class="card mb-4 no-print">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="ph-bold ph-arrows-clockwise"></i> Atualizar Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="atualizar_status.php">
                            <input type="hidden" name="os_id" value="<?php echo $id; ?>">
                            <div class="mb-3">
                                <label>Novo Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="aberta" <?php echo $os['status'] == 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                                    <option value="em_andamento" <?php echo $os['status'] == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                    <option value="aguardando_pecas" <?php echo $os['status'] == 'aguardando_pecas' ? 'selected' : ''; ?>>Aguardando Peças</option>
                                    <option value="finalizada">Finalizada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Observação</label>
                                <textarea name="observacao" class="form-control" rows="2" placeholder="Descreva a alteração..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">Atualizar Status</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Serviços -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="ph-bold ph-toolbox"></i> Serviços Realizados</h5>
            </div>
            <div class="card-body">
                <?php if(count($servicos) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr><th>Serviço</th><th>Descrição</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th>Mecânico</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($servicos as $s): ?>
                        <tr>
                            <td><?php echo $s['servico_nome']; ?></td>
                            <td><?php echo $s['descricao'] ?: '-'; ?></td>
                            <td><?php echo $s['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($s['valor_unitario'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($s['valor_unitario'] * $s['quantidade'], 2, ',', '.'); ?></td>
                            <td><?php echo $s['mecanico'] ?: '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-active"><td colspan="4"><strong>Total Serviços</strong></td><td colspan="2"><strong>R$ <?php echo number_format($total_servicos, 2, ',', '.'); ?></strong></td></tr></tfoot>
                </table>
                <?php else: ?>
                <p class="text-muted">Nenhum serviço cadastrado.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Produtos -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="ph-bold ph-package"></i> Produtos Utilizados</h5>
            </div>
            <div class="card-body">
                <?php if(count($produtos) > 0): ?>
                <table class="table table-hover">
                    <thead><tr><th>Produto</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach($produtos as $p): ?>
                        <tr>
                            <td><?php echo $p['produto_nome']; ?></td>
                            <td><?php echo $p['quantidade']; ?></td>
                            <td>R$ <?php echo number_format($p['valor_unitario'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($p['valor_unitario'] * $p['quantidade'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active"><td colspan="3"><strong>Total Produtos</strong></td><td><strong>R$ <?php echo number_format($total_produtos, 2, ',', '.'); ?></strong></td></tr>
                        <tr class="table-primary"><td colspan="3"><strong>TOTAL GERAL</strong></td><td><strong>R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></strong></td></tr>
                    </tfoot>
                </table>
                <?php else: ?>
                <p class="text-muted">Nenhum produto utilizado.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Observações -->
        <?php if($os['observacoes']): ?>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="ph-bold ph-chat-text"></i> Observações</h5>
            </div>
            <div class="card-body">
                <p><?php echo nl2br($os['observacoes']); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Histórico -->
        <?php if(count($historico) > 0): ?>
        <div class="card mb-4 no-print">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="ph-bold ph-clock-counter-clockwise"></i> Histórico de Status</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Data</th><th>Status Anterior</th><th>Status Novo</th><th>Observação</th></tr></thead>
                    <tbody>
                        <?php foreach($historico as $h): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></td>
                            <td><?php echo $status_lista[$h['status_anterior']] ?? $h['status_anterior']; ?></td>
                            <td><?php echo $status_lista[$h['status_novo']] ?? $h['status_novo']; ?></td>
                            <td><?php echo $h['observacao'] ?: '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
</main>

<script>
        function gerarPDF() {
            window.open('gerar_os_pdf.php?id=<?php echo $id; ?>', '_blank');
        }
    </script>
    

<?php include '../../includes/footer.php'; ?>
