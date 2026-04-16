<?php
header('Content-Type: application/json');
require_once '../config/config.php';

$busca = trim($_GET['busca'] ?? $_GET['q'] ?? '');
$id    = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($c) {
        $stmt2 = $db->prepare("SELECT * FROM motos WHERE cliente_id = ?");
        $stmt2->execute([$id]);
        $c['motos'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($c ?: null);
    exit;
}

if (strlen($busca) >= 2) {
    $stmt = $db->prepare("SELECT id, nome, cpf_cnpj, telefone, celular, email FROM clientes WHERE nome LIKE ? OR cpf_cnpj LIKE ? OR telefone LIKE ? OR celular LIKE ? ORDER BY nome LIMIT 15");
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $db->prepare("SELECT id, nome, cpf_cnpj, telefone, celular, email FROM clientes ORDER BY nome LIMIT 50");
    $stmt->execute();
}
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
