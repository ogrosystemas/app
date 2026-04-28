<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

set_exception_handler(function(Throwable $e) {
    if (!headers_sent()) {
        http_response_code((int)$e->getCode() ?: 500);
        header('Content-Type: application/json; charset=utf-8');
    }
    $http = in_array((int)$e->getCode(), [400,401,403,404,409,422,429]) ? (int)$e->getCode() : 500;
    http_response_code($http);
    echo json_encode([
        'error'   => true,
        'message' => $http >= 500 ? 'Erro interno.' : $e->getMessage(),
        'debug'   => basename(dirname($e->getFile())) . '/' . basename($e->getFile()) . ':' . $e->getLine(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

set_error_handler(function(int $no, string $str, string $file, int $line): bool {
    throw new ErrorException($str, 0, $no, $file, $line);
});

define('THEMIS_ROOT', __DIR__);
define('THEMIS_START', microtime(true));

// Injeta rota
$route = '/' . ltrim((string)($_GET['r'] ?? ''), '/');
$_SERVER['REQUEST_URI'] = '/api' . $route;

// Carrega todas as classes
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
