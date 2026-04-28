<?php
// Função local para não depender do helpers.php
function esc(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$step = (int)($_POST['step'] ?? 0);
$error = ''; $success = '';

if ($step === 1) {
    $host   = trim($_POST['db_host']??'localhost');
    $user   = trim($_POST['db_user']??'root');
    $pass   = $_POST['db_pass']??'';
    $dbname = trim($_POST['db_name']??'bar_system');
    $base_url = ''; // não mais necessário - auto-detectado em config.php

    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        $sql = file_get_contents(__DIR__ . '/banco.sql');
        $sql = preg_replace('/^CREATE DATABASE.*?;[\r\n]*/m','',$sql);
        $sql = preg_replace('/^USE.*?;[\r\n]*/m','',$sql);
        // Split SQL on semicolons that are NOT inside quotes
        $statements = [];
        $current = '';
        $inSingleQuote = false;
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            if ($char === "'" && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inSingleQuote = !$inSingleQuote;
            }
            if ($char === ';' && !$inSingleQuote) {
                $stmt = trim($current);
                if ($stmt !== '') $statements[] = $stmt;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        if (trim($current) !== '') $statements[] = trim($current);
        foreach ($statements as $s) {
            if (!empty($s)) $pdo->exec($s);
        }
        // Gerar config.php robusto (filesystem-based BASE_URL, imune ao LiteSpeed)
        $cfg = "<?php\n"
             . "define('DB_HOST',    '$host');\n"
             . "define('DB_USER',    '$user');\n"
             . "define('DB_PASS',    '$pass');\n"
             . "define('DB_NAME',    '$dbname');\n"
             . "define('DB_CHARSET', 'utf8mb4');\n\n"
             . "// BASE_URL: usa filesystem (imune ao SCRIPT_NAME incorreto do LiteSpeed)\n"
             . "if (!defined('BASE_URL')) {\n"
             . "    \$_scheme  = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';\n"
             . "    if (!empty(\$_SERVER['HTTP_X_FORWARDED_PROTO'])) \$_scheme = \$_SERVER['HTTP_X_FORWARDED_PROTO'];\n"
             . "    \$_host    = \$_SERVER['HTTP_HOST'] ?? 'localhost';\n"
             . "    \$_docRoot = rtrim(str_replace('\\\\\\\\', '/', \$_SERVER['DOCUMENT_ROOT'] ?? ''), '/');\n"
             . "    \$_projRoot= rtrim(str_replace('\\\\\\\\', '/', dirname(__DIR__)), '/');\n"
             . "    \$_webBase = (\$_docRoot && strpos(\$_projRoot, \$_docRoot) === 0)\n"
             . "                ? rtrim(substr(\$_projRoot, strlen(\$_docRoot)), '/') . '/'\n"
             . "                : '/';\n"
             . "    define('BASE_URL', \$_scheme . '://' . \$_host . \$_webBase);\n"
             . "}\n\n"
             . "define('SISTEMA_NOME',   'Bar System Pro');\n"
             . "define('SISTEMA_VERSAO', '1.0.0');\n"
             . "define('BASE_PATH',  dirname(__DIR__) . '/');\n"
             . "define('UPLOAD_PATH', BASE_PATH . 'assets/uploads/');\n"
             . "define('UPLOAD_URL',  BASE_URL  . 'assets/uploads/');\n"
             . "define('MP_API_URL', 'https://api.mercadopago.com');\n"
             . "date_default_timezone_set('America/Sao_Paulo');\n"
             . "if (session_status() === PHP_SESSION_NONE) {\n"
             . "    session_set_cookie_params(['lifetime'=>86400*7,'path'=>'/','secure'=>(!empty(\$_SERVER['HTTPS'])&&\$_SERVER['HTTPS']!=='off'),'httponly'=>true,'samesite'=>'Lax']);\n"
             . "    session_start();\n"
             . "}\n";
        file_put_contents(dirname(__DIR__).'/config/config.php', $cfg);

        // Criar e garantir permissão das pastas de upload
        $uploadDirs = [
            dirname(__DIR__).'/assets/uploads/',
            dirname(__DIR__).'/assets/uploads/produtos/',
            dirname(__DIR__).'/assets/uploads/logos/',
        ];
        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            @chmod($dir, 0775);
        }

        // Inserir usuários padrão com bcrypt
        $users = [
            ['Administrador', 'admin',   'admin123',  'admin',       null],
            ['Caixa Bar',     'caixabar','user123',   'caixa_bar',   '["dinheiro","mercadopago"]'],
            ['Caixa Totem',   'totem',   'user123',   'caixa_totem', '["mercadopago"]'],
        ];
        $stmtU = $pdo->prepare("INSERT IGNORE INTO usuarios (nome,login,senha,perfil,formas_pagamento) VALUES (?,?,?,?,?)");
        foreach ($users as $u) {
            $stmtU->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_BCRYPT),$u[3],$u[4]]);
        }

        // Configurações padrão
        $pdo->exec("INSERT IGNORE INTO configuracoes (chave,valor,descricao) VALUES
            ('tema','dark','Tema do sistema'),
            ('cor_primaria','#f59e0b','Cor primária'),
            ('cor_secundaria','#d97706','Cor secundária'),
            ('logo_login','','Logo da tela de login'),
            ('logo_pdv','','Logo do PDV'),
            ('mp_device_id','','Device ID do terminal Mercado Pago Point'),
            ('mp_access_token','','Access Token Mercado Pago'),
            ('mp_webhook_secret','','Webhook Secret Mercado Pago')");

        $success = 'Instalação concluída! <a href="../index.php">Acessar o sistema</a>';
        $step = 2;
    } catch (\Exception $e) {
        $error = 'Erro: '.$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalação — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:radial-gradient(ellipse at 30% 50%,#1a1300,#0d0f14);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;}
.card{background:#1e2330;border:1px solid #2d3447;border-radius:18px;max-width:520px;width:100%;overflow:hidden;}
.card-header-custom{background:linear-gradient(135deg,#f59e0b,#d97706);padding:2rem;text-align:center;}
.card-header-custom .ico{font-size:2.5rem;margin-bottom:.5rem;}
.card-body-custom{padding:2rem;}
.form-control,.form-select{background:#252b38;border:1px solid #2d3447;color:#f0f2f7;border-radius:8px;}
.form-control:focus,.form-select:focus{background:#252b38;border-color:#f59e0b;box-shadow:0 0 0 .15rem rgba(245,158,11,.2);color:#f0f2f7;}
.form-label{font-size:.8rem;font-weight:600;color:#8892a4;}
.btn-install{background:linear-gradient(135deg,#f59e0b,#d97706);color:#000;font-weight:800;border:none;border-radius:10px;width:100%;padding:.9rem;font-size:1rem;}
.btn-install:hover{background:#fcd34d;color:#000;}
.alert-ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#86efac;border-radius:10px;padding:1rem;}
.alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5;border-radius:10px;padding:1rem;}
a{color:#f59e0b;}
</style>
</head>
<body>
<div class="card">
  <div class="card-header-custom">
    <div class="ico">🍺</div>
    <h3 style="font-family:'Syne',sans-serif;font-weight:800;color:#000;margin:0">Bar System Pro</h3>
    <p style="color:rgba(0,0,0,.6);margin:.3rem 0 0;font-size:.9rem">Instalação do Sistema</p>
  </div>
  <div class="card-body-custom">
    <?php if ($error): ?><div class="alert-err mb-3"><?= esc($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
    <div class="alert-ok text-center">
      <div style="font-size:2rem;margin-bottom:.5rem">✅</div>
      <strong>Instalação concluída!</strong><br>
      <?= $success ?><br>
      <small style="color:#8892a4">⚠️ Apague a pasta <code>install/</code> por segurança.</small>
    </div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="step" value="1">
      <h6 style="color:#8892a4;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem">Banco de Dados MySQL</h6>
      <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:.6rem .875rem;font-size:.75rem;color:#f59e0b;margin-bottom:.875rem">
        <strong>CyberPanel:</strong> Databases → Create Database → anote o usuário e senha criados.
        Não use <code style="background:rgba(0,0,0,.3);padding:1px 5px;border-radius:3px">root</code> — crie um usuário dedicado para o banco.
      </div>
      <div class="row g-3 mb-3">
        <div class="col-6"><label class="form-label">Host</label><input type="text" name="db_host" class="form-control" value="localhost" required></div>
        <div class="col-6"><label class="form-label">Nome do Banco</label><input type="text" name="db_name" class="form-control" value="bar_system" required></div>
        <div class="col-6"><label class="form-label">Usuário MySQL</label><input type="text" name="db_user" class="form-control" value="" placeholder="Ex: barmutante_user" required></div>
        <div class="col-6"><label class="form-label">Senha MySQL</label><input type="password" name="db_pass" class="form-control" placeholder="Senha do usuário MySQL" required></div>
      </div>
      <div class="mb-4" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:.75rem;font-size:.78rem;color:#f59e0b">
        <strong>✓ URL detectada automaticamente</strong> — o sistema usa a URL real do servidor, sem necessidade de configuração manual.
      </div>
      <button type="submit" class="btn-install">🚀 Instalar Bar System Pro</button>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
