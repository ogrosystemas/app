<?php
// REMOVER APÓS DIAGNÓSTICO
define('THEMIS_ROOT', __DIR__);

$errors = [];
$ok = [];

$dirs = [
    '_app/Core',
    '_app/Services', 
    '_app/Controllers',
    '_app/Middleware',
];

foreach ($dirs as $dir) {
    $files = glob(THEMIS_ROOT . "/{$dir}/*.php") ?: [];
    foreach ($files as $f) {
        $rel = str_replace(THEMIS_ROOT . '/', '', $f);
        $out = shell_exec("php -l " . escapeshellarg($f) . " 2>&1");
        if (str_contains($out, 'No syntax errors')) {
            $ok[] = $rel;
        } else {
            $errors[$rel] = trim($out);
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'errors' => $errors,
    'ok_count' => count($ok),
    'error_count' => count($errors),
    'php_version' => PHP_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
