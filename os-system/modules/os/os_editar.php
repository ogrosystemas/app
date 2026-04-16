<?php
require_once '../../config/config.php';
checkAuth();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: os.php');
    exit;
}

$id = $_GET['id'];

// Buscar OS
$stmt_os = $db->prepare("SELECT * FROM ordens_servico WHERE id = ?"); $stmt_os->execute([$id]); $os = $stmt_os->fetch(PDO::FETCH_ASSOC);

if(!$os) {
    header('Location: os.php');
    exit;
}

// Processar remoção via POST (AJAX)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remover_item'])) {
    $tipo = $_POST['tipo'];
    $item_id = $_POST['item_id'];
    
    if($tipo == 'servico') {
        $db->prepare("DELETE FROM os_servicos WHERE id = ?")->execute([$item_id]);
    } elseif($tipo == 'produto') {
        $db->prepare("DELETE FROM os_produtos WHERE id = ?")->execute([$item_id]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// Processar adição de serviço
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar_servico'])) {
    $servico_id = $_POST['servico_id'];
    $quantidade = (int)$_POST['quantidade'];
    $mecanico_id = !empty($_POST['mecanico_id']) ? $_POST['mecanico_id'] : null;
    
    // Buscar tempo do serviço
    $svc_row = $db->prepare("SELECT tempo_estimado FROM servicos WHERE id = ?");
    $svc_row->execute([$servico_id]);
    $svc_data = $svc_row->fetch(PDO::FETCH_ASSOC);
    $tempo_min = (float)($svc_data['tempo_estimado'] ?? 0);
    
    // Buscar valor da hora
    $mao_row = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $vh = (float)($mao_row['valor_hora'] ?? 0);
    
    // CALCULO CORRETO: (tempo em minutos / 60) * valor_hora * quantidade
    $valor = ($tempo_min / 60) * $vh * $quantidade;
    $valor = round($valor, 2);
    
    if($servico_id && $quantidade > 0) {
        $stmt = $db->prepare("INSERT INTO os_servicos (os_id, servico_id, quantidade, valor_unitario, mecanico_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $servico_id, $quantidade, $valor, $mecanico_id]);
        $_SESSION['mensagem'] = 'Serviço adicionado com sucesso!';
    } else {
        $_SESSION['erro'] = 'Selecione um serviço e informe a quantidade.';
    }
    
    header("Location: os_editar.php?id=$id");
    exit;
}

// Processar adição de produto
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar_produto'])) {
    $produto_id = $_POST['produto_id'];
    $quantidade = $_POST['quantidade'];
    
    $valor_bruto = trim($_POST['valor']);
    $valor_bruto = str_replace('.', '', $valor_bruto);
    $valor_bruto = str_replace(',', '.', $valor_bruto);
    $valor = (float)$valor_bruto;
    
    if($produto_id && $quantidade > 0 && $valor > 0) {
        $stmt = $db->prepare("INSERT INTO os_produtos (os_id, produto_id, quantidade, valor_unitario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $produto_id, $quantidade, $valor]);
        
        // Dar baixa no estoque
        $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?")->execute([$quantidade, $produto_id]);
        $_SESSION['mensagem'] = 'Produto adicionado com sucesso!';
    } else {
        $_SESSION['erro'] = 'Preencha todos os campos do produto corretamente!';
    }
    
    header("Location: os_editar.php?id=$id");
    exit;
}

// Buscar serviços disponíveis
$servicos = $db->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos disponíveis
$produtos = $db->query("SELECT * FROM produtos WHERE estoque_atual > 0 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Buscar serviços já adicionados
$stmt_osv = $db->prepare("SELECT os.*, s.nome as servico_nome FROM os_servicos os JOIN servicos s ON os.servico_id = s.id WHERE os.os_id = ?"); $stmt_osv->execute([$id]); $os_servicos = $stmt_osv->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos já adicionados
$stmt_osp = $db->prepare("SELECT op.*, p.nome as produto_nome FROM os_produtos op JOIN produtos p ON op.produto_id = p.id WHERE op.os_id = ?"); $stmt_osp->execute([$id]); $os_produtos = $stmt_osp->fetchAll(PDO::FETCH_ASSOC);

// Buscar mecânicos
$mecanicos = $db->query("SELECT id, nome FROM usuarios WHERE perfil = 'mecanico' AND ativo = 1")->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_servicos = 0;
foreach($os_servicos as $s) {
    $total_servicos += $s['valor_unitario'] * $s['quantidade'];
}
$total_produtos = 0;
foreach($os_produtos as $p) {
    $total_produtos += $p['valor_unitario'] * $p['quantidade'];
}
$total_geral = $total_servicos + $total_produtos;

// Buscar valor da hora apenas para exibição
$valor_hora_exibicao = 0;
try {
    $mao_exibicao = $db->query("SELECT valor_hora FROM mao_de_obra ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $valor_hora_exibicao = (float)($mao_exibicao['valor_hora'] ?? 0);
} catch(Exception $e) {}

$mensagem = $_SESSION['mensagem'] ?? null;
$erro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Editar OS <span style="color:var(--accent)">#<?php echo htmlspecialchars($os['numero_os']); ?></span></div>
  <div class="topbar-actions"><a href="os_detalhes.php?id=<?php echo $id; ?>" class="btn-os btn-os-ghost"><i class="ph-bold ph-eye"></i> Ver Detalhes</a></div>
</header>

<main class="os-content">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="ph-bold ph-pencil-simple"></i> Editar OS #<?php echo $os['numero_os']; ?></h2>
    <a href="os_detalhes.php?id=<?php echo $id; ?>" class="btn btn-secondary">
        <i class="ph-bold ph-eye"></i> Ver Detalhes
    </a>
</div>

<?php if($mensagem): ?>
    <div class="alert alert-success"><?php echo $mensagem; ?></div>
<?php endif; ?>
<?php if($erro): ?>
    <div class="alert alert-danger"><?php echo $erro; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Adicionar Serviço -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="ph-bold ph-toolbox"></i> Adicionar Serviço</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formServico">
                    <input type="hidden" name="adicionar_servico" value="1">
                    <div class="mb-3">
                        <label>Serviço *</label>
                        <select name="servico_id" id="servico_id" class="form-select" required>
                            <option value="">Selecione um serviço</option>
                            <?php foreach($servicos as $s): ?>
                            <option value="<?php echo $s['id']; ?>" data-tempo="<?php echo $s['tempo_estimado']; ?>"><?php echo $s['nome']; ?> (<?php echo $s['tempo_estimado']; ?> min)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Quantidade *</label>
                        <input type="number" name="quantidade" id="quantidade_servico" class="form-control" value="1" min="1" required>
                    </div>
                    <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:12px 14px;font-size:.82rem;color:var(--text-muted);margin-bottom:12px">
                        <i class="ph-bold ph-info" style="color:#f59e0b"></i>
                        Valor calculado via <strong style="color:var(--accent)">Mão de Obra por Hora</strong>
                        (R$ <?php echo number_format($valor_hora_exibicao,2,',','.'); ?>/h × tempo estimado × qtd):<br>
                        <strong id="valorServicoDisplay" style="font-size:1rem;color:var(--accent);font-family:'Syne',sans-serif">R$ 0,00</strong>
                    </div>
                    <div class="mb-3">
                        <label>Mecânico Responsável</label>
                        <select name="mecanico_id" class="form-select">
                            <option value="">Selecione</option>
                            <?php foreach($mecanicos as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo $m['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Adicionar Serviço</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Adicionar Produto -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="ph-bold ph-package"></i> Adicionar Produto</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formProduto">
                    <input type="hidden" name="adicionar_produto" value="1">
                    <div class="mb-3">
                        <label>Produto *</label>
                        <select name="produto_id" id="produto_id" class="form-select" required>
                            <option value="">Selecione um produto</option>
                            <?php foreach($produtos as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-valor="<?php echo $p['preco_venda']; ?>" data-estoque="<?php echo $p['estoque_atual']; ?>"><?php echo $p['nome']; ?> - Estoque: <?php echo $p['estoque_atual']; ?> - R$ <?php echo number_format($p['preco_venda'], 2, ',', '.'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Quantidade *</label>
                        <input type="number" name="quantidade" id="quantidade_produto" class="form-control" value="1" min="1" required>
                        <small id="estoque_alerta" class="text-danger" style="display: none;"></small>
                    </div>
                    <div class="mb-3">
                        <label>Valor Unitário (R$) *</label>
                        <input type="text" name="valor" id="valor_produto" class="form-control" required>
                        <small class="text-muted">Preço preenchido automaticamente - Você pode alterar manualmente</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Adicionar Produto</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Serviços -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="ph-bold ph-list-check"></i> Serviços Adicionados</h5>
    </div>
    <div class="card-body">
        <?php if(count($os_servicos) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="tabelaServicos">
                <thead>
                    <tr>
                        <th>Serviço</th>
                        <th>Quantidade</th>
                        <th>Valor Unit.</th>
                        <th>Total</th>
                        <th>Mecânico</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="listaServicos">
                    <?php foreach($os_servicos as $s): ?>
                    <tr id="servico-<?php echo $s['id']; ?>">
                        <td><?php echo $s['servico_nome']; ?> <?php if($s['servico_id'] == 0): ?><span class="badge bg-warning">Personalizado</span><?php endif; ?></td>
                        <td><?php echo $s['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($s['valor_unitario'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($s['valor_unitario'] * $s['quantidade'], 2, ',', '.'); ?></td>
                        <td><?php echo $s['mecanico_id'] ?: '-'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger btn-remover" data-tipo="servico" data-id="<?php echo $s['id']; ?>">
                                <i class="ph-bold ph-trash"></i> Remover
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Nenhum serviço adicionado.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Lista de Produtos -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="ph-bold ph-list-check"></i> Produtos Adicionados</h5>
    </div>
    <div class="card-body">
        <?php if(count($os_produtos) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover" id="tabelaProdutos">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Unit.</th>
                        <th>Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="listaProdutos">
                    <?php foreach($os_produtos as $p): ?>
                    <tr id="produto-<?php echo $p['id']; ?>">
                        <td><?php echo $p['produto_nome']; ?> <?php if($p['produto_id'] == 0): ?><span class="badge bg-warning">Personalizado</span><?php endif; ?></td>
                        <td><?php echo $p['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($p['valor_unitario'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($p['valor_unitario'] * $p['quantidade'], 2, ',', '.'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger btn-remover" data-tipo="produto" data-id="<?php echo $p['id']; ?>">
                                <i class="ph-bold ph-trash"></i> Remover
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Nenhum produto utilizado.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Totais - Layout Original -->
<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="ph-bold ph-calculator"></i> Totais da OS</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h6>Total Serviços</h6>
                <h4 class="text-info">R$ <?php echo number_format($total_servicos, 2, ',', '.'); ?></h4>
            </div>
            <div class="col-md-4">
                <h6>Total Produtos</h6>
                <h4 class="text-success">R$ <?php echo number_format($total_produtos, 2, ',', '.'); ?></h4>
            </div>
            <div class="col-md-4">
                <h6>Total Geral</h6>
                <h3 class="text-primary">R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
</div>
</main>

<script>
$(document).ready(function() {
    function formatarMoeda(valor) {
        return parseFloat(valor).toFixed(2).replace('.', ',');
    }
    
    var VALOR_HORA = parseFloat(<?php echo json_encode((float)$valor_hora_exibicao); ?>);

    function calcularValorServico() {
        var opt = $('#servico_id').find(':selected');
        var tempo = parseFloat(opt.data('tempo')) || 0;
        var qtd = parseFloat($('#quantidade_servico').val()) || 1;
        
        if (tempo <= 0) {
            $('#valorServicoDisplay').text('R$ 0,00');
            return;
        }
        
        var horas = tempo / 60;
        var valor = horas * VALOR_HORA * qtd;
        valor = Math.round(valor * 100) / 100;
        
        $('#valorServicoDisplay').text('R$ ' + valor.toFixed(2).replace('.', ','));
    }

    $('#servico_id').change(calcularValorServico);
    $('#quantidade_servico').on('input change', calcularValorServico);
    
    $('#produto_id').change(function() {
        var valor = $(this).find(':selected').data('valor');
        var estoque = $(this).find(':selected').data('estoque');
        
        if(valor && valor > 0) {
            $('#valor_produto').val(formatarMoeda(valor));
        } else {
            $('#valor_produto').val('');
        }
        
        var qtd = $('#quantidade_produto').val();
        if(estoque && parseInt(qtd) > parseInt(estoque)) {
            $('#estoque_alerta').text('⚠️ Estoque disponível: ' + estoque + ' unidades').show();
        } else {
            $('#estoque_alerta').hide();
        }
    });
    
    $('#quantidade_produto').on('keyup change', function() {
        var estoque = $('#produto_id').find(':selected').data('estoque');
        var qtd = $(this).val();
        if(estoque && parseInt(qtd) > parseInt(estoque)) {
            $('#estoque_alerta').text('⚠️ Estoque disponível: ' + estoque + ' unidades').show();
        } else {
            $('#estoque_alerta').hide();
        }
    });
    
    $('.btn-remover').click(function(e) {
        e.preventDefault();
        
        var botao = $(this);
        var tipo = botao.data('tipo');
        var itemId = botao.data('id');
        var linha = botao.closest('tr');
        
        Swal.fire({
            title: 'Tem certeza?',
            text: "Este " + (tipo === 'servico' ? 'serviço' : 'produto') + " será removido da OS!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, remover!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                linha.addClass('removendo');
                
                $.ajax({
                    url: 'os_editar.php?id=<?php echo $id; ?>',
                    method: 'POST',
                    data: {
                        remover_item: 1,
                        tipo: tipo,
                        item_id: itemId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            linha.fadeOut(300, function() {
                                $(this).remove();
                                location.reload();
                            });
                        } else {
                            Swal.fire('Erro!', 'Erro ao remover item', 'error');
                            linha.removeClass('removendo');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro!', 'Erro na comunicação com o servidor', 'error');
                        linha.removeClass('removendo');
                    }
                });
            }
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>