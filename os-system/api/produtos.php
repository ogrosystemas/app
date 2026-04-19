<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$busca = trim($_GET['busca'] ?? $_GET['q'] ?? '');
$id    = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ? AND ativo = 1");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    exit;
}

if (strlen($busca) < 1) {
    // Retorna todos com estoque
    $stmt = $db->prepare("SELECT id, nome, codigo_barras, preco_venda, estoque_atual, unidade FROM produtos WHERE estoque_atual > 0 AND ativo = 1 ORDER BY nome LIMIT 100");
    $stmt->execute();
} else {
    $stmt = $db->prepare("SELECT id, nome, codigo_barras, preco_venda, estoque_atual, unidade FROM produtos WHERE ativo = 1 AND (codigo_barras = ? OR nome LIKE ? OR codigo_barras LIKE ?) ORDER BY CASE WHEN codigo_barras = ? THEN 0 ELSE 1 END, nome LIMIT 20");
    $stmt->execute([$busca, "%$busca%", "%$busca%", $busca]);
}
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
