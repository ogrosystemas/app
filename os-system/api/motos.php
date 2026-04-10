<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$cliente_id = $_GET['cliente_id'] ?? 0;

$query = "SELECT id, modelo, placa, marca, ano FROM motos WHERE cliente_id = :cliente_id";
$stmt = $db->prepare($query);
$stmt->execute([':cliente_id' => $cliente_id]);
$motos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($motos);
?>