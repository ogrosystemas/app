<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, nome, cpf_cnpj, telefone, email FROM clientes ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();

$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($clientes);
?>