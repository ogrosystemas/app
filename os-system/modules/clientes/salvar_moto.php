<?php
require_once '../../config/database.php';
require_once '../auth/auth.php';

session_start();
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if(!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $placa = strtoupper($_POST['placa']);
    $modelo = $_POST['modelo'];
    $marca = $_POST['marca'];
    $ano = $_POST['ano'];
    $cor = $_POST['cor'];
    $chassi = $_POST['chassi'];
    
    $query = "INSERT INTO motos (cliente_id, placa, modelo, marca, ano, cor, chassi) 
              VALUES (:cliente_id, :placa, :modelo, :marca, :ano, :cor, :chassi)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':cliente_id' => $cliente_id,
        ':placa' => $placa,
        ':modelo' => $modelo,
        ':marca' => $marca,
        ':ano' => $ano,
        ':cor' => $cor,
        ':chassi' => $chassi
    ]);
    
    header('Location: clientes.php');
    exit;
}
?>