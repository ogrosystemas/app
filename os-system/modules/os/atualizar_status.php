<?php
require_once '../../config/config.php';
checkAuth();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $os_id = $_POST['os_id'];
    $novo_status = $_POST['status'];
    $observacao = $_POST['observacao'];
    
    // Buscar status atual
    $stmt_s = $db->prepare("SELECT status FROM ordens_servico WHERE id = ?"); $stmt_s->execute([$os_id]); $os = $stmt_s->fetch(PDO::FETCH_ASSOC);
    $status_anterior = $os['status'];
    
    $db->beginTransaction();
    try {
        // Registrar no log
        $stmt = $db->prepare("INSERT INTO os_status_log (os_id, status_anterior, status_novo, observacao, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$os_id, $status_anterior, $novo_status, $observacao, $_SESSION['usuario_id']]);
        
        // Atualizar OS
        $data_finalizacao = $novo_status == 'finalizada' ? 'NOW()' : 'NULL';
        $stmt = $db->prepare("UPDATE ordens_servico SET status = ?, data_finalizacao = IF(? = 'finalizada', NOW(), NULL) WHERE id = ?");
        $stmt->execute([$novo_status, $novo_status, $os_id]);
        
        $db->commit();
        $_SESSION['mensagem'] = 'Status atualizado com sucesso!';
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['erro'] = 'Erro ao atualizar status: ' . $e->getMessage();
    }
    
    header("Location: os_detalhes.php?id=$os_id");
    exit;
}
?>