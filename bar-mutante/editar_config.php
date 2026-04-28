<?php
/**
 * editar_config.php
 * Edita config/config.php diretamente.
 * APAGUE após usar!
 */
$token = $_GET['t'] ?? '';
if ($token !== 'fix25') {
    http_response_code(403);
    die('Acesso negado. Adicione ?t=fix25 na URL.');
}

$configPath = __DIR__ . '/config/config.php';
$msg = ''; $ok = false;

// Ler valores atuais do config.php
function lerCfg(string $path, string $chave): string {
    $c = file_get_contents($path);
    preg_match("/define\(['\"]" . preg_quote($chave) . "['\"],\s*['\"]([^'\"]*)['\"].*?\);/", $c, $m);
    return $m[1] ?? '';
}

$host   = lerCfg($configPath, 'DB_HOST');
$user   = lerCfg($configPath, 'DB_USER');
$pass   = lerCfg($configPath, 'DB_PASS');
$dbname = lerCfg($configPath, 'DB_NAME');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_host  = trim($_POST['h'] ?? 'localhost');
    $novo_user  = trim($_POST['u'] ?? '');
    $novo_pass  = $_POST['p'] ?? '';
    $novo_db    = trim($_POST['d'] ?? '');

    if (!$novo_user || !$novo_db) {
        $msg = 'Usuário e banco são obrigatórios.';
    } else {
        // Testar conexão
        // Usar senha atual do config se campo ficou vazio
        $senha_real = $novo_pass !== '' ? $novo_pass : $pass;
        try {
            $pdo = new PDO(
                "mysql:host=$novo_host;dbname=$novo_db;charset=utf8mb4",
                $novo_user, $senha_real,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            // Salvar no config.php
            $cfg = file_get_contents($configPath);
            $cfg = preg_replace(
                "/define\(['\"]DB_HOST['\"],\s*'[^']*'[^;]*\);/",
                "define('DB_HOST',    '$novo_host');", $cfg
            );
            $cfg = preg_replace(
                "/define\(['\"]DB_USER['\"],\s*'[^']*'[^;]*\);/",
                "define('DB_USER',    '$novo_user');", $cfg
            );
            $cfg = preg_replace(
                "/define\(['\"]DB_PASS['\"],\s*'[^']*'[^;]*\);/",
                "define('DB_PASS',    '$novo_pass');", $cfg
            );
            $cfg = preg_replace(
                "/define\(['\"]DB_NAME['\"],\s*'[^']*'[^;]*\);/",
                "define('DB_NAME',    '$novo_db');", $cfg
            );
            file_put_contents($configPath, $cfg);
            $ok  = true;
            $msg = 'Salvo! Testando login no sistema...';
            // Atualizar vars para exibir
            $host=$novo_host; $user=$novo_user; $pass=$novo_pass; $dbname=$novo_db;
        } catch (\Exception $e) {
            $msg = 'Erro BD: ' . $e->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar Banco — Bar System Pro</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0d0f14;color:#f0f2f7;font-family:'Segoe UI',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
.card{background:#1e2330;border:1px solid #2d3447;border-radius:16px;padding:2rem;width:100%;max-width:460px}
h2{color:#f59e0b;margin-bottom:1.5rem;font-size:1.25rem;display:flex;align-items:center;gap:.5rem}
label{display:block;font-size:.72rem;font-weight:600;color:#8892a4;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.35rem}
input{width:100%;background:#252b38;border:1.5px solid #2d3447;color:#f0f2f7;padding:.7rem .875rem;border-radius:8px;font-size:.95rem;margin-bottom:1rem;transition:border-color .15s}
input:focus{outline:none;border-color:#f59e0b}
.btn{width:100%;background:linear-gradient(135deg,#f59e0b,#d97706);color:#000;font-weight:800;border:none;padding:.9rem;border-radius:10px;font-size:1rem;cursor:pointer;margin-top:.5rem}
.btn:hover{filter:brightness(1.08)}
.ok{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#86efac;padding:.875rem;border-radius:10px;margin-bottom:1rem;font-weight:600}
.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5;padding:.875rem;border-radius:10px;margin-bottom:1rem;font-size:.88rem;word-break:break-all}
.dica{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);color:#f59e0b;padding:.75rem 1rem;border-radius:8px;font-size:.78rem;margin-bottom:1.5rem;line-height:1.5}
.links{margin-top:1.25rem;display:flex;gap:.75rem;font-size:.82rem}
.links a{color:#f59e0b;text-decoration:none}
.links a:hover{text-decoration:underline}
code{background:#252b38;padding:2px 6px;border-radius:4px;font-size:.82rem}
.atual{font-size:.72rem;color:#8892a4;margin-top:-.75rem;margin-bottom:.75rem}
</style>
</head>
<body>
<div class="card">
  <h2>🔧 Configurar Banco de Dados</h2>

  <?php if ($ok): ?>
  <div class="ok">✓ Credenciais salvas e conexão OK!</div>
  <div class="links">
    <a href="login.php">→ Ir para o Login</a>
    <a href="javascript:void(0)" onclick="if(confirm('Apagar este arquivo agora?'))fetch('editar_config.php?t=fix25&apagar=1').then(()=>window.location='login.php')">🗑 Apagar este arquivo</a>
  </div>
  <?php elseif ($msg): ?>
  <div class="err">⚠ <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="dica">
    <strong>No CyberPanel:</strong> Databases → seu banco → anote usuário, senha e nome.<br>
    Normalmente o formato é: usuário = <code>prefixo_nome</code>, banco = <code>prefixo_nome</code>
  </div>

  <form method="POST" action="?t=fix25">
    <label>Host MySQL</label>
    <input type="text" name="h" value="<?= htmlspecialchars($host ?: 'localhost') ?>">

    <label>Usuário MySQL</label>
    <?php if ($user): ?><div class="atual">Atual: <code><?= htmlspecialchars($user) ?></code></div><?php endif; ?>
    <input type="text" name="u" value="<?= htmlspecialchars($user) ?>" placeholder="Ex: barm_barmutante" required autofocus>

    <label>Senha MySQL</label>
    <input type="password" name="p" placeholder="Senha do usuário MySQL">
    <small style="color:#8892a4;font-size:.72rem;display:block;margin-top:-.75rem;margin-bottom:.875rem">
      Deixe em branco para manter a senha atual<?= $pass ? ' (já configurada)' : '' ?>
    </small>

    <label>Nome do Banco</label>
    <?php if ($dbname): ?><div class="atual">Atual: <code><?= htmlspecialchars($dbname) ?></code></div><?php endif; ?>
    <input type="text" name="d" value="<?= htmlspecialchars($dbname) ?>" placeholder="Ex: barm_barmutante" required>

    <button class="btn" type="submit">Testar Conexão e Salvar</button>
  </form>

  <div class="links" style="margin-top:1.5rem;font-size:.72rem;color:#8892a4">
    ⚠ Apague <code>editar_config.php</code> do servidor após configurar!
  </div>
</div>

<?php
// Auto-apagar se solicitado
if (($_GET['apagar'] ?? '') === '1') {
    @unlink(__FILE__);
    echo '<script>window.location="login.php"</script>';
}
?>
</body>
</html>
