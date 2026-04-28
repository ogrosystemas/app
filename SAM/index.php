<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/crypto.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Rotas públicas — sem verificação de licença
$public = ['/pages/login.php', '/pages/errors/bloqueado.php', '/api/auth.php'];
$isPublic = array_reduce($public, fn($c, $p) => $c || str_starts_with($uri, $p), false);

if (!$isPublic) {
    license_check();
}

// Redireciona raiz para dashboard
if ($uri === '/') {
    header('Location: /pages/dashboard.php');
    exit;
}

// Rotas amigáveis ML OAuth
if ($uri === '/meli/connect') {
    require __DIR__ . '/api/meli_connect.php';
    exit;
}
if ($uri === '/meli/callback') {
    require __DIR__ . '/api/meli_callback.php';
    exit;
}

// 404
http_response_code(404);
include __DIR__ . '/pages/errors/404.php';
