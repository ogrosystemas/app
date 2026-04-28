<?php
/**
 * diagnostico.php — Bar System Pro
 * Acesse: https://barmutante.ogrosystemas.com.br/diagnostico.php
 * APAGUE APÓS DIAGNOSTICAR!
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0'>";
echo "=== DIAGNÓSTICO BAR SYSTEM PRO ===\n\n";

// 1. PHP
echo "PHP: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "OS: " . PHP_OS . "\n\n";

// 2. SERVER vars relevantes
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
echo "__FILE__: " . __FILE__ . "\n";
echo "__DIR__: " . __DIR__ . "\n\n";

// 3. Calcular BASE_URL como o config.php faria
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$project_root = str_replace('\\', '/', dirname(__DIR__ . '/../'));
// Simular config.php: __DIR__ é a raiz do projeto (onde diagnostico.php está)
$proj = str_replace('\\', '/', __DIR__);
$doc  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
echo "Proj root: $proj\n";
echo "Doc root:  $doc\n";
if ($doc && strpos($proj, $doc) === 0) {
    $web = substr($proj, strlen($doc));
    $web = '/' . ltrim($web, '/');
    if (substr($web, -1) !== '/') $web .= '/';
} else {
    $web = '/';
}
$base_url = $scheme . '://' . $host . $web;
echo "BASE_URL calculada: $base_url\n\n";

// 4. Extensões PHP necessárias
$exts = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo', 'session', 'curl'];
echo "=== EXTENSÕES PHP ===\n";
foreach ($exts as $ext) {
    echo ($ext . ': ' . (extension_loaded($ext) ? '✓ OK' : '✗ FALTANDO') . "\n");
}
echo "\n";

// 5. Arquivos principais
echo "=== ARQUIVOS ===\n";
$files = [
    'config/config.php',
    'includes/DB.php',
    'includes/helpers.php',
    'includes/Auth.php',
    'includes/nav.php',
    'index.php',
    'login.php',
    'assets/css/pdv.css',
    'assets/js/pdv.js',
    'assets/uploads/produtos/',
    'assets/uploads/logos/',
];
foreach ($files as $f) {
    $full = __DIR__ . '/' . $f;
    $exists = file_exists($full);
    $writable = is_writable($full);
    echo $f . ': ' . ($exists ? '✓ existe' : '✗ NÃO EXISTE');
    if ($exists && is_dir($full)) echo ' (dir, ' . ($writable ? 'gravável' : 'NÃO GRAVÁVEL') . ')';
    echo "\n";
}
echo "\n";

// 6. Carregar config
echo "=== CONFIG ===\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "config.php: ✓ carregou\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "BASE_URL: " . BASE_URL . "\n";
    echo "BASE_PATH: " . BASE_PATH . "\n";
    echo "UPLOAD_PATH: " . UPLOAD_PATH . "\n";
} catch (Throwable $e) {
    echo "config.php: ✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
echo "\n";

// 7. Conexão banco
echo "=== BANCO DE DADOS ===\n";
try {
    require_once __DIR__ . '/includes/DB.php';
    $pdo = DB::get();
    echo "Conexão: ✓ OK\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas (" . count($tables) . "): " . implode(', ', $tables) . "\n";
    
    // Check usuarios table
    if (in_array('usuarios', $tables)) {
        $users = $pdo->query("SELECT login, perfil, ativo FROM usuarios")->fetchAll();
        echo "\nUsuários:\n";
        foreach ($users as $u) {
            echo "  - {$u['login']} ({$u['perfil']}) " . ($u['ativo'] ? 'ativo' : 'inativo') . "\n";
        }
    }
} catch (Throwable $e) {
    echo "Banco: ✗ ERRO: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Carregar Auth
echo "=== AUTH ===\n";
try {
    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/includes/Auth.php';
    echo "Auth.php: ✓ carregou\n";
    echo "Sessão ativa: " . (session_status() === PHP_SESSION_ACTIVE ? 'sim' : 'não') . "\n";
    echo "User logado: " . (Auth::logado() ? Auth::nome() . ' (' . Auth::perfil() . ')' : 'não') . "\n";
} catch (Throwable $e) {
    echo "Auth.php: ✗ ERRO: " . $e->getMessage() . "\n";
    echo "Em: " . basename($e->getFile()) . ':' . $e->getLine() . "\n";
}
echo "\n";

// 9. Testar URL da API
echo "=== REDIRECT TEST ===\n";
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$projRoot= rtrim(str_replace('\\', '/', __DIR__), '/');
$base    = ($docRoot && strpos($projRoot, $docRoot) === 0)
           ? rtrim(substr($projRoot, strlen($docRoot)), '/') . '/'
           : '/';
$loginUrl = $scheme . '://' . $host . $base . 'login.php';
$indexUrl = $scheme . '://' . $host . $base . 'index.php';
echo "Redirect para login.php: $loginUrl\n";
echo "Redirect para index.php: $indexUrl\n";
echo "(Estes URLs devem estar CORRETOS antes de testar o sistema)\n\n";

echo "=== URL DA API ===\n";
$url_caixa = BASE_URL . 'api/caixa.php';
echo "URL api/caixa.php: $url_caixa\n";
echo "(Verifique se essa URL está correta para o seu servidor)\n\n";

echo "=== PERMISSÕES E CORREÇÕES ===\n";
$dirs = [
    __DIR__.'/assets/uploads/',
    __DIR__.'/assets/uploads/produtos/',
    __DIR__.'/assets/uploads/logos/',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
        echo "Criado: $dir\n";
    }
    $result = @chmod($dir, 0775);
    $perms = substr(sprintf('%o', fileperms($dir)), -4);
    echo ($dir . ': perms=' . $perms . ' gravável=' . (is_writable($dir) ? 'SIM' : 'NÃO') . "\n");
}
echo "\n";

echo "=== FIM DO DIAGNÓSTICO ===\n";
echo "\nACESSE: {$base_url}login.php\n";
echo "</pre>";
echo "<p style='font-family:sans-serif;background:#ff0;padding:10px;color:red'><strong>⚠ APAGUE diagnostico.php após diagnosticar!</strong></p>";
