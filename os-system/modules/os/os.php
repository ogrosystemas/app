<?php
require_once '../../config/config.php';
checkAuth();

function gerarNumeroOS($db) {
    $max = $db->query("SELECT MAX(CAST(SUBSTRING(numero_os, 4) AS UNSIGNED)) as max_num FROM ordens_servico")->fetch(PDO::FETCH_ASSOC);
    $numero = ($max['max_num'] ?? 0) + 1;
    return 'OS-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nova_os'])) {
    $numero_os = gerarNumeroOS($db);
    $stmt = $db->prepare("INSERT INTO ordens_servico (numero_os, cliente_id, moto_id, data_previsao, observacoes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$numero_os, $_POST['cliente_id'], $_POST['moto_id'], $_POST['data_previsao'], $_POST['observacoes'], $_SESSION['usuario_id']]);
    $_SESSION['mensagem'] = "OS $numero_os criada com sucesso!";
    header('Location: os.php');
    exit;
}

$os_list = $db->query("SELECT os.*, c.nome as cliente_nome, m.modelo as moto_modelo, m.placa FROM ordens_servico os JOIN clientes c ON os.cliente_id = c.id JOIN motos m ON os.moto_id = m.id ORDER BY os.data_abertura DESC")->fetchAll(PDO::FETCH_ASSOC);
$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$mensagem = $_SESSION['mensagem'] ?? null;
unset($_SESSION['mensagem']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Ordens de Serviço</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-wrench"></i> Ordens de Serviço</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaOS"><i class="ph-bold ph-plus-circle"></i> Nova OS</button>
        </div>
        
        <?php if($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nº OS</th>
                                <th>Cliente</th>
                                <th>Moto</th>
                                <th>Placa</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($os_list as $os): 
                                $status_badge = ['aberta'=>'warning', 'em_andamento'=>'info', 'aguardando_pecas'=>'danger', 'finalizada'=>'success', 'cancelada'=>'secondary'];
                                $status_text = ['aberta'=>'Aberta', 'em_andamento'=>'Em Andamento', 'aguardando_pecas'=>'Aguardando Peças', 'finalizada'=>'Finalizada', 'cancelada'=>'Cancelada'];
                            ?>
                            <tr>
                                <td><strong><?php echo $os['numero_os']; ?></strong></td>
                                <td><?php echo $os['cliente_nome']; ?></td>
                                <td><?php echo $os['moto_modelo']; ?></td>
                                <td><?php echo $os['placa']; ?></td>
                                <td><span class="badge bg-<?php echo $status_badge[$os['status']]; ?>"><?php echo $status_text[$os['status']]; ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($os['data_abertura'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verDetalhes(<?php echo $os['id']; ?>)"><i class="ph-bold ph-eye"></i></button>
                                    <a href="gerar_os_pdf.php?id=<?php echo $os['id']; ?>" target="_blank" class="btn btn-sm btn-danger" title="Gerar PDF"><i class="ph-bold ph-file-pdf"></i></a>
                                    <?php if($os['status'] != 'finalizada' && $os['status'] != 'cancelada'): ?>
                                    <a href="os_editar.php?id=<?php echo $os['id']; ?>" class="btn btn-sm btn-warning"><i class="ph-bold ph-pencil-simple"></i></a>
                                    <?php endif; ?>
                                 </tr ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </div>
                </div>
            </div>
</main>

</div>
    
    <!-- Modal Nova OS -->
    <div class="modal fade" id="modalNovaOS" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Ordem de Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="nova_os" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach($clientes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Moto</label>
                            <select name="moto_id" id="moto_id" class="form-select" required>
                                <option value="">Selecione um cliente primeiro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Data Previsão</label>
                            <input type="date" name="data_previsao" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Observações</label>
                            <textarea name="observacoes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar OS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Detalhes da OS -->
    <div class="modal fade" id="modalDetalhesOS" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOSTitle">Detalhes da OS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalOSContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando dados...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-danger" id="btnPDFOS"><i class="bi bi-file-pdf"></i> Gerar PDF</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentOSId = null;
        
        $('#cliente_id').change(function() {
            const cliente_id = $(this).val();
            if(cliente_id) {
                $.ajax({
                    url: '../../api/motos.php',
                    method: 'GET',
                    dataType: 'json',
                    data: { cliente_id: cliente_id },
                    success: function(data) {
                        let options = '<option value="">Selecione uma moto</option>';
                        data.forEach(moto => {
                            options += `<option value="${moto.id}">${moto.modelo} - ${moto.placa}</option>`;
                        });
                        $('#moto_id').html(options);
                    }
                });
            } else {
                $('#moto_id').html('<option value="">Selecione um cliente primeiro</option>');
            }
        });
        
        function verDetalhes(id) {
            currentOSId = id;
            $('#modalOSTitle').html('Carregando...');
            $('#modalOSContent').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Carregando dados...</p></div>');
            $('#modalDetalhesOS').modal('show');
            
            $.ajax({
                url: 'carregar_detalhes_os.php',
                method: 'GET',
                data: { id: id },
                success: function(data) {
                    $('#modalOSContent').html(data);
                    // Extrair o número da OS do conteúdo
                    $('#modalOSTitle').html('Ordem de Serviço #' + id);
                },
                error: function(xhr, status, error) {
                    $('#modalOSContent').html('<div class="alert alert-danger m-3">Erro ao carregar dados da OS: ' + error + '</div>');
                }
            });
        }
        
        $('#btnPDFOS').click(function() {
            if(currentOSId) {
                window.open('gerar_os_pdf.php?id=' + currentOSId, '_blank');
            }
        });
    </script>

<?php include '../../includes/footer.php'; ?>
