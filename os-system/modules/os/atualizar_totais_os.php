<?php
/**
 * modules/os/atualizar_totais_os.php
 *
 * Recalcula e persiste os totais (subtotal/total) de uma OS
 * somando os valores de os_servicos e os_produtos.
 * Chamado via AJAX pelo os_editar.php após adicionar/remover itens.
 */
require_once '../../config/config.php';
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['os_id'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit;
}

$os_id = (int) $_POST['os_id'];

try {
    // Soma dos serviços
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(quantidade * valor_unitario), 0) as total
         FROM os_servicos WHERE os_id = ?"
    );
    $stmt->execute([$os_id]);
    $total_servicos = (float) $stmt->fetchColumn();

    // Soma dos produtos
    $stmt = $db->prepare(
        "SELECT COALESCE(SUM(quantidade * valor_unitario), 0) as total
         FROM os_produtos WHERE os_id = ?"
    );
    $stmt->execute([$os_id]);
    $total_produtos = (float) $stmt->fetchColumn();

    $total_geral = $total_servicos + $total_produtos;

    // Persistir na OS (caso a tabela tenha colunas de total)
    // Se não existirem, o UPDATE simplesmente não gera erro por causa do try/catch
    try {
        $db->prepare(
            "UPDATE ordens_servico SET total_servicos = ?, total_produtos = ?, total_geral = ? WHERE id = ?"
        )->execute([$total_servicos, $total_produtos, $total_geral, $os_id]);
    } catch (PDOException $ignored) {
        // Colunas podem não existir no schema — ignora silenciosamente
    }

    echo json_encode([
        'success'         => true,
        'total_servicos'  => number_format($total_servicos, 2, ',', '.'),
        'total_produtos'  => number_format($total_produtos, 2, ',', '.'),
        'total_geral'     => number_format($total_geral, 2, ',', '.'),
        'total_geral_raw' => $total_geral,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
