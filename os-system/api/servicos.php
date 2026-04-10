<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$query = "SELECT id, nome, valor FROM servicos WHERE ativo = 1 ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($servicos);
?>