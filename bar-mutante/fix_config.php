<?php
/**
 * fix_config.php — Correção rápida de credenciais do banco
 * Use apenas UMA VEZ e APAGUE depois!
 */

// Segurança básica - token de acesso
$token = $_GET['token'] ?? '';
if ($token !== 'fixar2025') {
    die('<p style="color:red;font-family:sans-serif;padding:2rem">Acesso negado. Use: ?token=fixar2025</p>');
}

$msg = '';
$configPath = __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['db_host'] ?? 'localhost');
    $user   = trim($_POST['db_user'] ?? '');
    $pass   = $_POST['db_pass'] ?? '';
    $dbname = trim($_POST['db_name'] ?? '');

    if (!$user || !$dbname) {
        $msg = '<p style="color:red">Usuário e nome do banco são obrigatórios.</p>';
    } else {
        // Testar conexão antes de salvar
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $msg = '<p style="color:green;font-weight:bold">✓ Conexão testada com sucesso!</p>';
        } catch (Exception $e) {
            $msg = '<p style="color:red">✗ Erro na conexão: ' . htmlspecialchars($e->getMessage()) . '</p>';
            $host = $user = $pass = $dbname = '';
        }

        if ($user && $dbname && str_contains($msg, 'sucesso')) {
            // Ler config atual e substituir apenas as credenciais
            $cfg = file_get_contents($configPath);
            $cfg = preg_replace("/define\('DB_HOST',\s*'[^']*'\);/", "define('DB_HOST',    '$host');", $cfg);
            $cfg = preg_replace("/define\('DB_USER',\s*'[^']*'\)[^;]*;/", "define('DB_USER',    '$user');", $cfg);
            $cfg = preg_replace("/define\('DB_PASS',\s*'[^']*'\)[^;]*;/", "define('DB_PASS',    '$pass');", $cfg);
            $cfg = preg_replace("/define\('DB_NAME',\s*'[^']*'\)[^;]*;/", "define('DB_NAME',    '$dbname');", $cfg);
            file_put_contents($configPath, $cfg);
            $msg .= '<p style="color:green;font-weight:bold">✓ config.php atualizado!</p>';
            $msg .= '<p><strong>Apague este arquivo agora:</strong> <code>fix_config.php</code></p>';
            $msg .= '<p><a href="index.php" style="color:#f59e0b">→ Acessar o sistema</a></p>';
        }
    }
}

// Ler valores atuais
$cfgAtual = [];
if (file_exists($configPath)) {
    preg_match("/define\('DB_HOST',\s*'([^']*)'\)/", file_get_contents($configPath), $m);
    $cfgAtual['host'] = $m[1] ?? 'localhost';
    preg_match("/define\('DB_USER',\s*'([^']*)'\)/", file_get_contents($configPath), $m);
    $cfgAtual['user'] = $m[1] ?? '';
    preg_match("/define\('DB_NAME',\s*'([^']*)'\)/", file_get_contents($configPath), $m);
    $cfgAtual['name'] = $m[1] ?? '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Fix Config — Bar System Pro</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#0d0f14;color:#f0f2f7;min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:#1e2330;border:1px solid #2d3447;border-radius:16px;padding:2rem;max-width:480px;width:100%}
h2{color:#f59e0b;margin-top:0}
label{display:block;font-size:.8rem;color:#8892a4;margin-bottom:.3rem;text-transform:uppercase}
input{width:100%;background:#252b38;border:1px solid #2d3447;color:#f0f2f7;padding:.65rem .875rem;border-radius:8px;font-size:.95rem;margin-bottom:1rem;box-sizing:border-box}
input:focus{outline:none;border-color:#f59e0b}
button{width:100%;background:linear-gradient(135deg,#f59e0b,#d97706);color:#000;font-weight:700;border:none;padding:.875rem;border-radius:10px;font-size:1rem;cursor:pointer}
.warn{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);color:#f59e0b;padding:.75rem 1rem;border-radius:8px;font-size:.82rem;margin-bottom:1.5rem}
code{background:#252b38;padding:2px 6px;border-radius:4px;font-size:.85rem}
</style>
</head>
<body>
<div class="box">
  <h2>🔧 Corrigir Credenciais do Banco</h2>
  <div class="warn">⚠ Use este arquivo apenas uma vez e <strong>apague-o</strong> do servidor depois!</div>
  <?= $msg ?>
  <form method="POST" action="?token=fixar2025">
    <label>Host MySQL</label>
    <input type="text" name="db_host" value="<?= htmlspecialchars($cfgAtual['host'] ?? 'localhost') ?>">
    <label>Usuário MySQL</label>
    <input type="text" name="db_user" value="<?= htmlspecialchars($cfgAtual['user'] ?? '') ?>" placeholder="Ex: barm_barmutante" required>
    <label>Senha MySQL</label>
    <input type="password" name="db_pass" placeholder="Senha do usuário">
    <label>Nome do Banco</label>
    <input type="text" name="db_name" value="<?= htmlspecialchars($cfgAtual['name'] ?? '') ?>" placeholder="Ex: barm_barmutante" required>
    <button type="submit">Testar e Salvar</button>
  </form>
</div>
</body>
</html>
