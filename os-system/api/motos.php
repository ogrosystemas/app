<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$cliente_id = (int)($_GET['cliente_id'] ?? 0);
if (!$cliente_id) { echo json_encode([]); exit; }

$stmt = $db->prepare("SELECT id, modelo, placa, marca, ano, cor, chassi, cilindrada, km_atual, combustivel FROM motos WHERE cliente_id = ? ORDER BY marca, modelo");
$stmt->execute([$cliente_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
