<?php
ob_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DB.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Auth.php';
Auth::logout();

// Garantir que a sessão está completamente limpa
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    // Iniciar nova sessão vazia para invalidar o cookie antigo
    session_start();
    session_regenerate_id(true);
    session_destroy();
}

// Redirect seguro — imune ao SCRIPT_NAME do LiteSpeed
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projRoot= rtrim(str_replace('\\', '/', __DIR__), '/');
$base    = ($docRoot && strpos($projRoot, $docRoot) === 0)
           ? rtrim(substr($projRoot, strlen($docRoot)), '/') . '/'
           : '/';
http_response_code(302);
header('Location: ' . $scheme . '://' . $host . $base . 'login.php');
exit;
