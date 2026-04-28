<?php
declare(strict_types=1);

define('THEMIS_ROOT', __DIR__);
define('THEMIS_START', microtime(true));

// Se acessado como / ou /index.php diretamente (sem rota de API)
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = strtok($uri, '?');

// Raiz ou index.php puro → redireciona para login
if ($path === '/' || $path === '/index.php') {
    header('Location: ' . rtrim(
        (file_exists(THEMIS_ROOT . '/_app/config/app.php')
            ? (require THEMIS_ROOT . '/_app/config/app.php')['app']['url'] ?? ''
            : ''),
        '/'
    ) . '/login');
    exit;
}

// Carrega todas as classes automaticamente
foreach ([
    THEMIS_ROOT . '/_app/Core',
    THEMIS_ROOT . '/_app/Services',
    THEMIS_ROOT . '/_app/Controllers',
    THEMIS_ROOT . '/_app/Middleware',
] as $_dir) {
    foreach (glob($_dir . '/*.php') ?: [] as $_f) {
        require_once $_f;
    }
}

require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$router = require THEMIS_ROOT . '/_app/routes/api.php';
$router->dispatch();
