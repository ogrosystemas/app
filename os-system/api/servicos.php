<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$stmt = $db->prepare("SELECT id, nome, valor AS preco, tempo_estimado, garantia_dias FROM servicos WHERE ativo = 1 ORDER BY nome");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
