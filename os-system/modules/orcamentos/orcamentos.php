<?php
require_once '../../config/config.php';
checkAuth();

function gerarNumeroOrcamento($db) {
    $max = $db->query("SELECT MAX(CAST(SUBSTRING(numero_orcamento, 5) AS UNSIGNED)) as max_num FROM orcamentos")->fetch(PDO::FETCH_ASSOC);
    $numero = ($max['max_num'] ?? 0) + 1;
    return 'ORC-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
}

// Processar novo orçamento
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_orcamento'])) {
    $numero_orcamento = gerarNumeroOrcamento($db);
    $cliente_id = $_POST['cliente_id'];
    $moto_id = $_POST['moto_id'];
    $data_validade = $_POST['data_validade'];
    $observacoes = $_POST['observacoes'];
    $itens = json_decode($_POST['itens_json'], true);
    
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("INSERT INTO orcamentos (numero_orcamento, cliente_id, moto_id, data_validade, observacoes, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
        $stmt->execute([$numero_orcamento, $cliente_id, $moto_id, $data_validade, $observacoes]);
        $orcamento_id = $db->lastInsertId();
        
        foreach($itens as $item) {
            $stmt = $db->prepare("INSERT INTO orcamento_itens (orcamento_id, tipo, item_id, quantidade, valor_unitario) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orcamento_id, $item['tipo'], $item['id'], $item['quantidade'], str_replace(',', '.', str_replace('.', '', $item['valor']))]);
        }
        
        $db->commit();
        $_SESSION['mensagem'] = "Orçamento $numero_orcamento criado com sucesso!";
        header('Location: orcamentos.php');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['erro'] = 'Erro ao salvar orçamento: ' . $e->getMessage();
        header('Location: orcamentos.php');
        exit;
    }
}

// Converter orçamento em OS
if(isset($_GET['converter'])) {
    $id = $_GET['converter'];
    
    try {
        $stmt_orc = $db->prepare("SELECT * FROM orcamentos WHERE id = ?"); $stmt_orc->execute([$id]); $orcamento = $stmt_orc->fetch(PDO::FETCH_ASSOC);
        
        if($orcamento) {
            $max_os = $db->query("SELECT MAX(CAST(SUBSTRING(numero_os, 4) AS UNSIGNED)) as max_num FROM ordens_servico")->fetch(PDO::FETCH_ASSOC);
            $num_os = ($max_os['max_num'] ?? 0) + 1;
            $numero_os = 'OS-' . str_pad($num_os, 5, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO ordens_servico (numero_os, cliente_id, moto_id, status, observacoes, created_by) VALUES (?, ?, ?, 'aberta', ?, ?)");
            $stmt->execute([$numero_os, $orcamento['cliente_id'], $orcamento['moto_id'], $orcamento['observacoes'], $_SESSION['usuario_id']]);
            $os_id = $db->lastInsertId();
            
            $stmt_itens = $db->prepare("SELECT * FROM orcamento_itens WHERE orcamento_id = ?"); $stmt_itens->execute([$id]); $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($itens as $item) {
                if($item['tipo'] == 'servico') {
                    $stmt = $db->prepare("INSERT INTO os_servicos (os_id, servico_id, quantidade, valor_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$os_id, $item['item_id'], $item['quantidade'], $item['valor_unitario']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO os_produtos (os_id, produto_id, quantidade, valor_unitario) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$os_id, $item['item_id'], $item['quantidade'], $item['valor_unitario']]);
                    $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?")->execute([$item['quantidade'], $item['item_id']]);
                }
            }
            
            $db->prepare("UPDATE orcamentos SET status = 'convertido', convertido_os_id = ? WHERE id = ?")->execute([$os_id, $id]);
            $_SESSION['mensagem'] = "Orçamento convertido em OS: $numero_os";
        }
    } catch(Exception $e) {
        $_SESSION['erro'] = 'Erro ao converter orçamento: ' . $e->getMessage();
    }
    
    header('Location: orcamentos.php');
    exit;
}

// Excluir orçamento
if(isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $db->prepare("DELETE FROM orcamentos WHERE id = ?")->execute([$id]);
    $db->prepare("DELETE FROM orcamento_itens WHERE orcamento_id = ?")->execute([$id]);
    $_SESSION['mensagem'] = 'Orçamento excluído com sucesso!';
    header('Location: orcamentos.php');
    exit;
}

// Buscar orçamentos
$orcamentos = $db->query("SELECT o.*, c.nome as cliente_nome, m.modelo as moto_modelo, m.placa FROM orcamentos o JOIN clientes c ON o.cliente_id = c.id JOIN motos m ON o.moto_id = m.id ORDER BY o.data_criacao DESC")->fetchAll(PDO::FETCH_ASSOC);
$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$servicos = $db->query("SELECT id, nome, valor, tempo_estimado FROM servicos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $db->query("SELECT id, nome, preco_venda, estoque_atual FROM produtos WHERE estoque_atual > 0 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Orçamentos</div>
  <div class="topbar-actions"><a href="form_orcamento.php" class="btn-os btn-os-primary"><i class="ph-bold ph-plus-circle"></i> Novo Orçamento</a></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="ph-bold ph-file-text"></i> Orçamentos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOrcamento" onclick="limparOrcamento()">
                <i class="ph-bold ph-plus-circle"></i> Novo Orçamento
            </button>
        </div>
        
        <?php if($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        <?php if($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nº Orçamento</th>
                                <th>Cliente</th>
                                <th>Moto</th>
                                <th>Data</th>
                                <th>Validade</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orcamentos as $orc): 
                                $stmt_io = $db->prepare("SELECT SUM(quantidade * valor_unitario) as total FROM orcamento_itens WHERE orcamento_id = ?"); $stmt_io->execute([$orc['id']]); $itens_orc = $stmt_io->fetch(PDO::FETCH_ASSOC);
                                $total = $itens_orc['total'] ?? 0;
                                $status_badge = ['ativo'=>'success', 'aprovado'=>'info', 'rejeitado'=>'danger', 'convertido'=>'primary'];
                                $status_text = ['ativo'=>'Ativo', 'aprovado'=>'Aprovado', 'rejeitado'=>'Rejeitado', 'convertido'=>'Convertido'];
                            ?>
                            <tr>
                                <td><strong><?php echo $orc['numero_orcamento']; ?></strong></td>
                                <td><?php echo $orc['cliente_nome']; ?></td>
                                <td><?php echo $orc['moto_modelo'] . ' - ' . $orc['placa']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($orc['data_criacao'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($orc['data_validade'])); ?></td>
                                <td>R$ <?php echo number_format($total, 2, ',', '.'); ?></td>
                                <td><span class="badge bg-<?php echo $status_badge[$orc['status']]; ?>"><?php echo $status_text[$orc['status']]; ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="verDetalhes(<?php echo $orc['id']; ?>)">
                                        <i class="ph-bold ph-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="gerarPDF(<?php echo $orc['id']; ?>)">
                                        <i class="ph-bold ph-file-pdf"></i> PDF
                                    </button>
                                    <?php if($orc['status'] == 'ativo'): ?>
                                    <button class="btn btn-sm btn-success" onclick="confirmarConversao(<?php echo $orc['id']; ?>)">
                                        <i class="ph-bold ph-arrows-clockwise"></i> Converter
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusaoOrcamento(<?php echo $orc['id']; ?>, '<?php echo addslashes($orc['numero_orcamento']); ?>')">
                                        <i class="ph-bold ph-trash"></i> Excluir
                                    </button>
                                    <?php endif; ?>
                                 </tr>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </div>
                </div>
            </div>
</main>

</div>
    
    <!-- Modal Novo Orçamento (mantenha o mesmo) -->
    <div class="modal fade" id="modalOrcamento" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Orçamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formOrcamento">
                    <input type="hidden" name="salvar_orcamento" value="1">
                    <input type="hidden" name="itens_json" id="itens_json">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Cliente *</label>
                                <select name="cliente_id" id="cliente_id" class="form-select" required>
                                    <option value="">Selecione</option>
                                    <?php foreach($clientes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo $c['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Moto *</label>
                                <select name="moto_id" id="moto_id" class="form-select" required>
                                    <option value="">Selecione um cliente primeiro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Data Validade *</label>
                                <input type="date" name="data_validade" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Observações</label>
                                <textarea name="observacoes" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary">Itens do Orçamento</h6>
                                    <button type="button" class="btn btn-sm btn-success" onclick="adicionarItem()">
                                        <i class="ph-bold ph-plus-circle"></i> Adicionar Item
                                    </button>
                                </div>
                                <div id="lista_itens" class="mb-3"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Orçamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Detalhes -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Orçamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalhesConteudo"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-danger" onclick="gerarPDFAtual()"><i class="ph-bold ph-file-pdf"></i> Gerar PDF</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Item -->
    <div class="modal fade" id="modalAdicionarItem" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Tipo de Item</label>
                        <select id="tipo_item" class="form-select">
                            <option value="servico">Serviço</option>
                            <option value="produto">Produto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Item</label>
                        <select id="item_id" class="form-select">
                            <option value="">Selecione</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Quantidade</label>
                        <input type="number" id="quantidade_item" class="form-control" value="1" min="1">
                    </div>
                    <div class="mb-3">
                        <label>Valor Unitário (R$)</label>
                        <input type="text" id="valor_item" class="form-control">
                        <small class="text-muted" id="valor_hint">Preço preenchido automaticamente ou digite manualmente</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarAdicionarItem()">Adicionar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let itensOrcamento = [];
        let servicos = <?php echo json_encode($servicos); ?>;
        let produtos = <?php echo json_encode($produtos); ?>;
        // Mão de obra
        let valorHoraMaoObra = 0;
        <?php
        try {
            $mao_orc = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            echo 'valorHoraMaoObra = ' . (float)($mao_orc['valor_hora'] ?? 0) . ';';
        } catch(Exception $e) { echo 'valorHoraMaoObra = 0;'; }
        ?>
        let currentOrcamentoId = null;
        
        $(document).ready(function() {
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
                }
            });
            
            $('#tipo_item').change(function() { carregarItens(); });
            $('#item_id').change(function() { preencherValor(); });
            carregarItens();
        });
        
        function carregarItens() {
            const tipo = $('#tipo_item').val();
            let options = '<option value="">Selecione</option>';
            
            if(tipo === 'servico') {
                servicos.forEach(item => {
                    options += `<option value="${item.id}" data-valor="0" data-tempo="${item.tempo_estimado||0}">${item.nome} (${item.tempo_estimado||0} min)</option>`;
                });
            } else {
                produtos.forEach(item => {
                    options += `<option value="${item.id}" data-valor="${item.preco_venda}">${item.nome} - R$ ${parseFloat(item.preco_venda).toFixed(2)}</option>`;
                });
            }
            $('#item_id').html(options);
            preencherValor();
        }
        
        function preencherValor() {
            const selected = $('#item_id').find(':selected');
            const tipo  = $('#tipo_item').val();
            const valor = selected.data('valor');
            const tempo = selected.data('tempo') || 0;
            if (tipo === 'servico') {
                // Serviços: valor é calculado via mão de obra, campo fica 0
                $('#valor_item').val('0,00');
                $('#valor_item').prop('readonly', true);
                $('#valor_item').css('backgroundColor', '');
                $('#valor_hint').html('<i class="ph-bold ph-info" style="color:#f59e0b"></i> Valor calculado pelo sistema via Mão de Obra (' + tempo + ' min)');
            } else {
                $('#valor_item').prop('readonly', false);
                if (valor && valor > 0) {
                    $('#valor_item').val(parseFloat(valor).toFixed(2).replace('.', ','));
                    $('#valor_item').css('backgroundColor', '');
                } else {
                    $('#valor_item').val('');
                }
                $('#valor_hint').text('Preço preenchido automaticamente ou digite manualmente');
            }
        }
        
        function adicionarItem() {
            $('#tipo_item').val('servico');
            carregarItens();
            $('#quantidade_item').val('1');
            $('#valor_item').val('');
            $('#modalAdicionarItem').modal('show');
        }
        
        function confirmarAdicionarItem() {
            const tipo = $('#tipo_item').val();
            const item_id = $('#item_id').val();
            const item_nome = $('#item_id option:selected').text().split(' - ')[0];
            const quantidade = parseInt($('#quantidade_item').val());
            let valor = $('#valor_item').val();
            
            if(!item_id) { alert('Selecione um item!'); return; }
            if(!quantidade || quantidade < 1) { alert('Quantidade inválida!'); return; }
            if(!valor || valor === '') { alert('Informe o valor unitário!'); return; }
            
            valor = parseFloat(valor.replace(/\./g, '').replace(',', '.'));
            
            itensOrcamento.push({
                tipo: tipo, id: item_id, nome: item_nome,
                quantidade: quantidade, valor: valor, total: quantidade * valor
            });
            
            atualizarListaItens();
            $('#modalAdicionarItem').modal('hide');
        }
        
        function removerItem(index) {
            itensOrcamento.splice(index, 1);
            atualizarListaItens();
        }
        
        function atualizarListaItens() {
            let html = '';
            let totalGeral = 0;
            
            if(itensOrcamento.length === 0) {
                html = '<p class="text-muted text-center">Nenhum item adicionado</p>';
            } else {
                html = '<table class="table table-bordered"><thead class="table-light"><tr><th>Item</th><th>Tipo</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th></th></tr></thead><tbody>';
                
                itensOrcamento.forEach((item, index) => {
                    totalGeral += item.total;
                    html += `<tr>
                        <td>${item.nome}</td>
                        <td><span class="badge ${item.tipo === 'servico' ? 'bg-info' : 'bg-success'}">${item.tipo === 'servico' ? 'Serviço' : 'Produto'}</span></td>
                        <td class="text-center">${item.quantidade}</td>
                        <td class="text-end">R$ ${item.valor.toFixed(2).replace('.', ',')}</td>
                        <td class="text-end">R$ ${item.total.toFixed(2).replace('.', ',')}</td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${index})"><i class="ph-bold ph-trash"></i></button></td>
                    </tr>`;
                });
                
                // Calcular mão de obra
                let totalHoras = 0;
                itensOrcamento.forEach(item => {
                    if (item.tipo === 'servico') {
                        let svc = servicos.find(s => s.id == item.id);
                        if (svc && svc.tempo_estimado) totalHoras += (parseFloat(svc.tempo_estimado) * item.quantidade / 60);
                    }
                });
                let totalMaoObra = Math.round(totalHoras * valorHoraMaoObra * 100) / 100;
                let totalComMao  = totalGeral + totalMaoObra;

                if (totalMaoObra > 0) {
                    html += `<tr><td colspan="4" class="text-end text-muted small">Subtotal Peças/Serviços</td><td class="text-end">R$ ${totalGeral.toFixed(2).replace('.', ',')}</td><td></td></tr>`;
                    html += `<tr><td colspan="4" class="text-end text-muted small">Mão de Obra (${totalHoras.toFixed(1)}h × R$ ${valorHoraMaoObra.toFixed(2).replace('.',',')}\/h)</td><td class="text-end">R$ ${totalMaoObra.toFixed(2).replace('.', ',')}</td><td></td></tr>`;
                }
                html += `<tr class="table-primary"><td colspan="4" class="text-end"><strong>TOTAL GERAL</strong></td><td class="text-end"><strong>R$ ${totalComMao.toFixed(2).replace('.', ',')}</strong></td><td></td></tr>`;
                html += '</tbody></table>';
            }
            
            $('#lista_itens').html(html);
            $('#itens_json').val(JSON.stringify(itensOrcamento));
        }
        
        function limparOrcamento() {
            itensOrcamento = [];
            atualizarListaItens();
            $('#cliente_id').val('');
            $('#moto_id').html('<option value="">Selecione um cliente primeiro</option>');
            $('input[name="data_validade"]').val('');
            $('textarea[name="observacoes"]').val('');
        }
        
        function verDetalhes(id) {
            currentOrcamentoId = id;
            $.ajax({
                url: 'detalhes_orcamento.php',
                method: 'GET',
                data: { id: id },
                success: function(data) {
                    $('#detalhesConteudo').html(data);
                    $('#modalDetalhes').modal('show');
                }
            });
        }
        
        function gerarPDF(id) {
            window.open('gerar_pdf_tcpdf.php?id=' + id, '_blank');
        }
        
        function gerarPDFAtual() {
            if(currentOrcamentoId) {
                window.open('gerar_pdf_tcpdf.php?id=' + currentOrcamentoId, '_blank');
            }
        }
        
        function confirmarConversao(id) {
            Swal.fire({
                title: 'Converter Orçamento?',
                text: "Este orçamento será convertido em Ordem de Serviço!",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, converter!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?converter=' + id;
                }
            });
        }
        
        function confirmarExclusaoOrcamento(id, numero) {
            Swal.fire({
                title: 'Tem certeza?',
                text: `Deseja realmente excluir o orçamento ${numero}? Esta ação não pode ser desfeita!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?excluir=' + id;
                }
            });
        }
    </script>

<?php include '../../includes/footer.php'; ?>
