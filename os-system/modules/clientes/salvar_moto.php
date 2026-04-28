<?php
require_once '../../config/config.php';
checkAuth();
csrfVerify();

$cliente_id = (int)($_POST['cliente_id'] ?? 0);
if (!$cliente_id) { header('Location: clientes.php'); exit; }

$dados = [
    'cliente_id'  => $cliente_id,
    'placa'       => strtoupper(preg_replace('/[^A-Z0-9\-]/i', '', $_POST['placa'] ?? '')),
    'marca'       => trim($_POST['marca']  ?? ''),
    'modelo'      => trim($_POST['modelo'] ?? ''),
    'ano'         => (int)($_POST['ano'] ?? 0) ?: null,
    'cor'         => trim($_POST['cor']    ?? ''),
    'chassi'      => strtoupper(trim($_POST['chassi'] ?? '')),
    'cilindrada'  => trim($_POST['cilindrada'] ?? ''),
    'km_atual'    => (int)($_POST['km_atual'] ?? 0),
];

if (!$dados['placa'] || !$dados['modelo'] || !$dados['marca']) {
    $_SESSION['erro'] = 'Placa, marca e modelo são obrigatórios.';
    header("Location: clientes.php"); exit;
}

try {
    $cols = implode(', ', array_keys($dados));
    $vals = implode(', ', array_fill(0, count($dados), '?'));
    $db->prepare("INSERT INTO motos ($cols) VALUES ($vals)")->execute(array_values($dados));
    $_SESSION['mensagem'] = 'Moto cadastrada com sucesso!';
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao salvar moto: ' . $e->getMessage();
}

header('Location: clientes.php'); exit;
