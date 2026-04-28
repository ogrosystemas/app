<?php
// Iniciar sessão antes de qualquer coisa
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/config.php';
require_once '../../config/licenca.php';

// Este endpoint aceita POST de usuário logado (configurações)
// E também da página de bloqueio (usuário pode não estar logado)
// Não chamamos checkAuth() aqui propositalmente

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/modules/configuracoes/configuracoes.php');
    exit;
}

csrfVerify();

$chave  = trim($_POST['chave_licenca'] ?? '');
$origem = $_POST['origem'] ?? 'bloqueio'; // 'config' ou 'bloqueio'

$destino_erro  = $origem === 'config'
    ? BASE_URL . '/modules/configuracoes/configuracoes.php'
    : BASE_URL . '/licenca_bloqueio.php';
$destino_ok    = BASE_URL . '/modules/configuracoes/configuracoes.php';

if (!$chave) {
    $_SESSION['licenca_erro'] = 'Informe a chave de licença.';
    header('Location: ' . $destino_erro);
    exit;
}

$resultado = licenca_ativar($db, $chave);

if ($resultado['ok']) {
    $_SESSION['mensagem'] = 'Licença ativada com sucesso! Sistema liberado.';
    header('Location: ' . $destino_ok);
} else {
    $_SESSION['licenca_erro'] = $resultado['erro'];
    header('Location: ' . $destino_erro);
}
exit;
