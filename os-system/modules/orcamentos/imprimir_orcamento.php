<?php
require_once '../../config/config.php';

$id = $_GET['id'] ?? 0;

// Buscar orçamento
$orcamento = $db->query("SELECT o.*, c.nome as cliente_nome, c.telefone, c.email, c.endereco, m.modelo, m.placa, m.marca 
                         FROM orcamentos o 
                         JOIN clientes c ON o.cliente_id = c.id 
                         JOIN motos m ON o.moto_id = m.id 
                         WHERE o.id = ?")->fetch(PDO::FETCH_ASSOC);

if(!$orcamento) {
    echo "<p class='text-danger'>Orçamento não encontrado</p>";
    exit;
}

// Buscar itens
$itens = $db->query("SELECT oi.*, 
                     CASE WHEN oi.tipo = 'servico' THEN s.nome ELSE p.nome END as nome_item 
                     FROM orcamento_itens oi 
                     LEFT JOIN servicos s ON oi.tipo = 'servico' AND oi.item_id = s.id 
                     LEFT JOIN produtos p ON oi.tipo = 'produto' AND oi.item_id = p.id 
                     WHERE oi.orcamento_id = ?")->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach($itens as $item) {
    $total += $item['quantidade'] * $item['valor_unitario'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento <?php echo $orcamento['numero_orcamento']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: Arial, sans-serif; 
            background: white;
            padding: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .info-box { 
            border: 1px solid #dee2e6; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
            background: #f8f9fa;
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }
        .table th, .table td { 
            border: 1px solid #dee2e6; 
            padding: 10px; 
            text-align: left;
        }
        .table th { 
            background: #f8f9fa; 
        }
        .text-end { 
            text-align: right; 
        }
        .text-center { 
            text-align: center; 
        }
        .assinatura { 
            margin-top: 50px; 
        }
        .total-row {
            background: #d4edda;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                margin: 1.5cm;
                size: A4;
            }
            @page :header {
                display: none;
            }
            @page :footer {
                display: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>OS-System</h2>
            <h3>ORÇAMENTO Nº <?php echo $orcamento['numero_orcamento']; ?></h3>
            <p>Emissão: <?php echo date('d/m/Y H:i', strtotime($orcamento['data_criacao'])); ?></p>
        </div>
        
        <div class="row">
            <div style="width: 50%; float: left; padding-right: 10px;">
                <div class="info-box">
                    <h5>DADOS DO CLIENTE</h5>
                    <p><strong>Nome:</strong> <?php echo $orcamento['cliente_nome']; ?><br>
                    <strong>Telefone:</strong> <?php echo $orcamento['telefone'] ?: '-'; ?><br>
                    <strong>Email:</strong> <?php echo $orcamento['email'] ?: '-'; ?><br>
                    <strong>Endereço:</strong> <?php echo $orcamento['endereco'] ?: '-'; ?></p>
                </div>
            </div>
            <div style="width: 50%; float: left; padding-left: 10px;">
                <div class="info-box">
                    <h5>DADOS DA MOTO</h5>
                    <p><strong>Modelo:</strong> <?php echo $orcamento['modelo']; ?><br>
                    <strong>Placa:</strong> <?php echo $orcamento['placa']; ?><br>
                    <strong>Marca:</strong> <?php echo $orcamento['marca'] ?: '-'; ?></p>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div class="info-box">
            <h5>ITENS DO ORÇAMENTO</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Tipo</th>
                        <th style="text-align: center;">Qtd</th>
                        <th style="text-align: right;">Valor Unit.</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($itens as $item): ?>
                    <tr>
                        <td><?php echo $item['nome_item']; ?></td>
                        <td><?php echo $item['tipo'] == 'servico' ? 'Serviço' : 'Produto'; ?></td>
                        <td style="text-align: center;"><?php echo $item['quantidade']; ?></td>
                        <td style="text-align: right;">R$ <?php echo number_format($item['valor_unitario'], 2, ',', '.'); ?></td>
                        <td style="text-align: right;">R$ <?php echo number_format($item['quantidade'] * $item['valor_unitario'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;"><strong>TOTAL</strong></td>
                        <td style="text-align: right;"><strong>R$ <?php echo number_format($total, 2, ',', '.'); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if($orcamento['observacoes']): ?>
        <div class="info-box">
            <h5>OBSERVAÇÕES</h5>
            <p><?php echo nl2br($orcamento['observacoes']); ?></p>
        </div>
        <?php endif; ?>
        
        <div style="width: 50%; float: left;">
            <p><strong>Validade:</strong> <?php echo date('d/m/Y', strtotime($orcamento['data_validade'])); ?></p>
        </div>
        <div style="width: 50%; float: left; text-align: right;">
            <div class="assinatura">
                <p>_________________________<br>Assinatura do Cliente</p>
            </div>
        </div>
        <div style="clear: both;"></div>
        
        <div style="text-align: center; margin-top: 30px;">
            <p>Obrigado pela preferência!<br>Este orçamento é válido até a data de validade informada.</p>
        </div>
        
        <div style="text-align: center; margin-top: 20px;" class="no-print">
            <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">🖨️ Imprimir</button>
            <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Fechar</button>
        </div>
    </div>
    
    <script>
        // Remover URL e cabeçalho na impressão
        var style = document.createElement('style');
        style.innerHTML = `
            @media print {
                @page {
                    margin: 1.5cm;
                    size: A4;
                }
                @page :header {
                    display: none;
                }
                @page :footer {
                    display: none;
                }
                body {
                    margin: 0;
                    padding: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>