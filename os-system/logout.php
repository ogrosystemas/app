<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para o login (caminho relativo)
header('Location: login.php');
exit;
?>