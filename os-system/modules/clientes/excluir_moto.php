<?php
require_once '../../config/config.php';
checkAuth();

if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['erro'] = 'ID da moto não informado!';
    header('Location: clientes.php');
    exit;
}

$id = $_GET['id'];

try {
    $query = "SELECT * FROM motos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    $moto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$moto) {
        $_SESSION['erro'] = 'Moto não encontrada!';
        header('Location: clientes.php');
        exit;
    }
    
    $query = "DELETE FROM motos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);
    
    $_SESSION['mensagem'] = 'Moto ' . $moto['modelo'] . ' (Placa: ' . $moto['placa'] . ') excluída com sucesso!';
    
} catch(PDOException $e) {
    $_SESSION['erro'] = 'Erro ao excluir moto: ' . $e->getMessage();
}

header('Location: clientes.php');
exit;
?>