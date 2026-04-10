<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$busca = $_GET['busca'] ?? '';

$query = "SELECT id, nome, preco_venda FROM produtos WHERE estoque_atual > 0 ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($produtos);
?>