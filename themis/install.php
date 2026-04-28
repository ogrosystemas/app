<?php
/**
 * Themis Enterprise — Instalador Web
 *
 * Este arquivo fica na RAIZ do site (public_html/install.php)
 * Acesse: https://themis.ogrosystemas.com.br/install.php
 *
 * Não requer SSH, Composer ou linha de comando.
 * Remove a si mesmo após a instalação bem-sucedida.
 */
declare(strict_types=1);

// Raiz = pasta onde este arquivo está (public_html/)
define('THEMIS_ROOT', __DIR__);
define('INSTALL_VERSION', '2.0.0');

session_start();

// ── Segurança: bloqueia se já instalado ──────────────────
$cfgFile = THEMIS_ROOT . '/_app/config/app.php';
if (file_exists($cfgFile)) {
    $cfg = require $cfgFile;
    $secret = $cfg['app']['secret'] ?? '';
    // Se secret não é placeholder, já foi instalado
    if (strlen($secret) >= 32 && !str_contains($secret, 'TROQUE') && !str_contains($secret, 'th3m1s_0gr0')) {
        http_response_code(404);
        die('Not Found');
    }
}

$step   = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
$errors = [];
$info   = [];

// ═══════════════════════════════════════════════════════════
// PROCESSAMENTO
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 2: Testa banco ──────────────────────────────────
    if ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = trim($_POST['db_pass'] ?? '');

        if (!$dbName || !$dbUser) {
            $errors[] = 'Nome do banco e usuário são obrigatórios.';
            $step = 2;
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                    $dbUser, $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
                );
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");
                // Testa uma query simples
                $pdo->query("SELECT 1");
                $_SESSION['install'] = compact('dbHost','dbPort','dbName','dbUser','dbPass');
                $step = 3;
                $info[] = 'Conexão estabelecida com sucesso.';
            } catch (\PDOException $e) {
                $errors[] = 'Falha na conexão: ' . $e->getMessage();
                $step = 2;
            }
        }
    }

    // STEP 3: Instala ──────────────────────────────────────
    elseif ($step === 3 && !empty($_SESSION['install'])) {
        $db = $_SESSION['install'];

        $adminNome   = trim($_POST['admin_nome']   ?? '');
        $adminEmail  = trim($_POST['admin_email']  ?? '');
        $adminPass   = trim($_POST['admin_pass']   ?? '');
        $adminPass2  = trim($_POST['admin_pass2']  ?? '');
        $escNome     = trim($_POST['escritorio_nome'] ?? '');
        $escCnpj     = trim($_POST['escritorio_cnpj'] ?? '');
        $appUrl      = rtrim(trim($_POST['app_url'] ?? ''), '/');

        // Validações
        if (!$adminNome || !$adminEmail || !$adminPass || !$escNome) {
            $errors[] = 'Preencha todos os campos obrigatórios.';
            $step = 3;
        } elseif ($adminPass !== $adminPass2) {
            $errors[] = 'As senhas não coincidem.';
            $step = 3;
        } elseif (strlen($adminPass) < 8) {
            $errors[] = 'A senha deve ter ao menos 8 caracteres.';
            $step = 3;
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
            $step = 3;
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$db['dbHost']};port={$db['dbPort']};dbname={$db['dbName']};charset=utf8mb4",
                    $db['dbUser'], $db['dbPass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // 1. Executa schema SQL via multi_query
                // multi_query executa o arquivo inteiro de uma vez, sem dividir por ;
                // Isso garante que SET FOREIGN_KEY_CHECKS=0, comentários e todas as
                // tabelas sejam criadas corretamente, independente da formatação do SQL
                $schemaFile = THEMIS_ROOT . '/_app/sql/schema.sql';
                if (!file_exists($schemaFile)) throw new \RuntimeException('Arquivo sql/schema.sql não encontrado.');
                $schema = file_get_contents($schemaFile);

                $mysqli = new \mysqli($db['dbHost'], $db['dbUser'], $db['dbPass'], $db['dbName'], (int)$db['dbPort']);
                if ($mysqli->connect_error) throw new \RuntimeException('Falha mysqli: ' . $mysqli->connect_error);
                $mysqli->set_charset('utf8mb4');

                // Executa tudo de uma vez — multi_query processa cada ; corretamente
                if ($mysqli->multi_query($schema) === false) {
                    throw new \RuntimeException('Erro ao executar schema: ' . $mysqli->error);
                }

                // Consome todos os result sets (obrigatório após multi_query)
                do {
                    if ($res = $mysqli->store_result()) $res->free();
                    if ($mysqli->errno && $mysqli->errno !== 1050 && $mysqli->errno !== 1060 && $mysqli->errno !== 1061) {
                        // 1050 = table already exists, 1060 = duplicate column, 1061 = duplicate key — ignora em re-instalação
                        $errMsg = $mysqli->error;
                        // Não interrompe por erros de "já existe"
                        if (!str_contains($errMsg, 'already exists') && !str_contains($errMsg, 'Duplicate')) {
                            // Registra mas continua (pode ser aviso não crítico)
                            error_log("[Themis Install] MySQL warning: {$errMsg}");
                        }
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());

                $mysqli->close();

                // 2. Seed de índices monetários (multi_query)
                $indicesFile = THEMIS_ROOT . '/_app/sql/seeds/indices.sql';
                if (file_exists($indicesFile)) {
                    $sqli2 = new \mysqli($db['dbHost'], $db['dbUser'], $db['dbPass'], $db['dbName'], (int)$db['dbPort']);
                    $sqli2->set_charset('utf8mb4');
                    $sqli2->multi_query(file_get_contents($indicesFile));
                    do { if ($r = $sqli2->store_result()) $r->free(); } while ($sqli2->more_results() && $sqli2->next_result());
                    $sqli2->close();
                }

                // 3. Cria tenant
                $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($escNome));
                $slug = trim(preg_replace('/-+/', '-', $slug), '-') ?: 'escritorio';
                $pdo->prepare("INSERT INTO tenants (slug, razao_social, cnpj, plano) VALUES (?,?,?,'professional')")
                    ->execute([$slug, $escNome, $escCnpj ?: null]);
                $tenantId = (int) $pdo->lastInsertId();

                // 4. Cria usuário admin
                $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("INSERT INTO users (tenant_id, nome, email, password_hash, perfil, ativo) VALUES (?,?,?,?,'admin',1)")
                    ->execute([$tenantId, $adminNome, $adminEmail, $hash]);
                $userId = (int) $pdo->lastInsertId();

                // 5. Vincula owner ao tenant
                $pdo->prepare("UPDATE tenants SET owner_id = ? WHERE id = ?")->execute([$userId, $tenantId]);

                // 6. Seed de templates (multi_query com tenant_id real)
                $tplFile = THEMIS_ROOT . '/_app/sql/seeds/templates.sql';
                if (file_exists($tplFile)) {
                    $tplSql = file_get_contents($tplFile);
                    // Substitui o tenant_id=1 placeholder pelo real
                    $tplSql = str_replace('(1, \'', "({$tenantId}, '", $tplSql);
                    $tplSql = str_replace(', 1, 1)', ", {$userId}, 1)", $tplSql);
                    $sqli3 = new \mysqli($db['dbHost'], $db['dbUser'], $db['dbPass'], $db['dbName'], (int)$db['dbPort']);
                    $sqli3->set_charset('utf8mb4');
                    $sqli3->multi_query($tplSql);
                    do { if ($r = $sqli3->store_result()) $r->free(); } while ($sqli3->more_results() && $sqli3->next_result());
                    $sqli3->close();
                }

                // 7. Gera secret seguro
                $secret = bin2hex(random_bytes(32));

                // 8. Grava config/app.php definitivo
                $storagePath = addslashes(THEMIS_ROOT . '/_storage');
                $tcpdfPath   = addslashes(THEMIS_ROOT . '/vendor/tcpdf');
                $configContent = <<<PHP
<?php
/**
 * Themis Enterprise — Configuração gerada pelo instalador
 * Instalado em: {$appUrl}
 */
declare(strict_types=1);

\$root = dirname(__DIR__, 2); // _app/config/ -> _app/ -> raiz

return [
    'app' => [
        'name'     => '{$escNome}',
        'url'      => '{$appUrl}',
        'secret'   => '{$secret}',
        'debug'    => false,
        'timezone' => 'America/Sao_Paulo',
        'env'      => 'production',
    ],
    'db' => [
        'host'    => '{$db['dbHost']}',
        'port'    => '{$db['dbPort']}',
        'name'    => '{$db['dbName']}',
        'user'    => '{$db['dbUser']}',
        'pass'    => '{$db['dbPass']}',
        'charset' => 'utf8mb4',
    ],
    'storage' => [
        'path'       => \$root . '/_storage',
        'max_mb'     => 50,
        'trash_days' => 30,
    ],
    'cors' => [
        'origins' => ['{$appUrl}'],
    ],
    'session'   => ['ttl' => 28800],
    'pdf'       => ['driver' => 'tcpdf', 'tcpdf_path' => \$root . '/vendor/tcpdf', 'margin_top' => 25, 'margin_right' => 15, 'margin_bottom' => 25, 'margin_left' => 20, 'font' => 'helvetica', 'font_size' => 11],
    'assinafy'  => ['token' => '', 'secret' => ''],
    'whatsapp'  => ['phone_id' => '', 'token' => '', 'verify_token' => ''],
    'datajud'   => ['api_key' => '', 'base_url' => 'https://api-publica.datajud.cnj.jus.br'],
    'mail'      => ['host' => '', 'port' => 587, 'encryption' => 'tls', 'user' => '', 'pass' => '', 'from_name' => '{$escNome}', 'from_addr' => ''],
    'despesas'  => ['valor_km_padrao' => 0.90],
];
PHP;
                file_put_contents(THEMIS_ROOT . '/_app/config/app.php', $configContent);

                // 9. Protege _storage com .htaccess
                if (!file_exists(THEMIS_ROOT . '/_storage/.htaccess')) {
                    file_put_contents(THEMIS_ROOT . '/_storage/.htaccess', "Order deny,allow\nDeny from all\n");
                }

                $_SESSION['install_done'] = [
                    'admin_email' => $adminEmail,
                    'app_url'     => $appUrl,
                    'tenant_id'   => $tenantId,
                ];
                unset($_SESSION['install']);
                $step = 4;

            } catch (\Throwable $e) {
                $errors[] = 'Erro na instalação: ' . $e->getMessage();
                $step = 3;
            }
        }
    }

    // STEP 4: Remove instalador e redireciona ─────────────
    elseif ($step === 4) {
        if (file_exists(__FILE__)) @unlink(__FILE__);
        $url = $_SESSION['install_done']['app_url'] ?? '';
        session_destroy();
        header("Location: {$url}/login");
        exit;
    }
}

// ═══════════════════════════════════════════════════════════
// VERIFICAÇÃO DE REQUISITOS
// ═══════════════════════════════════════════════════════════
function checkReqs(): array
{
    return [
        'PHP 8.3+'               => version_compare(PHP_VERSION, '8.3.0', '>='),
        'Extensão PDO MySQL'     => extension_loaded('pdo_mysql'),
        'Extensão MySQLi'        => extension_loaded('mysqli'),
        'Extensão cURL'          => extension_loaded('curl'),
        'Extensão mbstring'      => extension_loaded('mbstring'),
        'Extensão fileinfo'      => extension_loaded('fileinfo'),
        'Extensão openssl'       => extension_loaded('openssl'),
        'Extensão JSON'          => extension_loaded('json'),
        '_storage/ gravável'     => is_writable(THEMIS_ROOT . '/_storage'),
        '_app/config/ gravável'  => is_writable(THEMIS_ROOT . '/_app/config'),
        '_app/sql/schema.sql existe' => file_exists(THEMIS_ROOT . '/_app/sql/schema.sql'),
    ];
}

$reqs    = checkReqs();
$allPass = !in_array(false, $reqs, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Themis — Instalação v<?= INSTALL_VERSION ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f1117;color:#e8edf5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.wrap{width:100%;max-width:540px}
.card{background:#161b27;border:1px solid #2a3449;border-radius:16px;overflow:hidden}
.header{background:linear-gradient(135deg,#2563eb,#0d9488);padding:26px 30px}
.header-inner{display:flex;align-items:center;gap:14px}
.logo{width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:12px;display:grid;place-items:center;font-size:22px;flex-shrink:0}
.logo img{height:36px;width:auto}
.header h1{font-size:20px;font-weight:700;color:#fff}
.header p{font-size:12.5px;color:rgba(255,255,255,.75);margin-top:3px}
.steps{display:flex;background:#1e2535}
.step{flex:1;padding:11px 6px;text-align:center;font-size:11px;font-weight:600;color:#4f5b72;border-bottom:3px solid transparent;transition:all .2s}
.step.active{color:#3b82f6;border-bottom-color:#3b82f6}
.step.done{color:#10b981;border-bottom-color:#10b981}
.body{padding:26px 30px}
.slabel{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#4f5b72;margin:18px 0 12px;padding-bottom:6px;border-bottom:1px solid #1e2840}
.fg{display:flex;flex-direction:column;gap:5px;margin-bottom:14px}
label{font-size:11px;font-weight:600;color:#8b95a9;text-transform:uppercase;letter-spacing:.05em}
input,select{background:#1e2535;border:1px solid #2a3449;border-radius:8px;padding:10px 13px;font-size:13.5px;color:#e8edf5;font-family:inherit;outline:none;transition:border-color .18s,box-shadow .18s;width:100%}
input:focus,select:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;border-radius:9px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;border:none;transition:all .18s;margin-top:6px}
.btn-primary{background:#3b82f6;color:#fff;box-shadow:0 2px 12px rgba(59,130,246,.35)}
.btn-primary:hover{background:#2563eb;transform:translateY(-1px)}
.btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none}
.req-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #1e2840;font-size:13px}
.req-item:last-child{border-bottom:none}
.chk{width:20px;height:20px;border-radius:50%;display:grid;place-items:center;font-size:11px;flex-shrink:0}
.ok{background:rgba(16,185,129,.15);color:#10b981}
.fail{background:rgba(244,63,94,.15);color:#f43f5e}
.alert{padding:11px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;border:1px solid;display:flex;align-items:flex-start;gap:8px}
.aerr{background:rgba(244,63,94,.08);border-color:rgba(244,63,94,.25);color:#fb7185}
.aok{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.25);color:#34d399}
.note{font-size:12px;color:#4f5b72;margin-top:6px;line-height:1.5}
.success-icon{font-size:52px;text-align:center;margin-bottom:14px}
.cred{background:#1e2535;border:1px solid #2a3449;border-radius:9px;padding:14px 16px;margin:10px 0}
.cred p{font-size:11.5px;color:#8b95a9;margin-bottom:3px}
.cred strong{font-size:14px;color:#3b82f6;font-family:'Courier New',monospace}
.warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:12px 14px;font-size:12.5px;color:#fbbf24;margin-top:14px;line-height:1.5}
</style>
</head>
<body>
<div class="wrap">
<div class="card">

  <div class="header">
    <div class="header-inner">
      <div class="logo">
        <img src="/assets/img/themis_logo.png" alt="Themis"
             onerror="this.style.display='none';this.parentElement.textContent='⚖'">
      </div>
      <div>
        <h1>Themis Enterprise</h1>
        <p>Instalador Web v<?= INSTALL_VERSION ?> &nbsp;·&nbsp; themis.ogrosystemas.com.br</p>
      </div>
    </div>
  </div>

  <div class="steps">
    <?php foreach (['Requisitos','Banco de Dados','Configuração','Concluído'] as $i => $label):
        $n   = $i + 1;
        $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
    ?>
    <div class="step <?= $cls ?>"><?= $n < $step ? '✓' : $n ?> <?= $label ?></div>
    <?php endforeach; ?>
  </div>

  <div class="body">

    <?php foreach ($errors as $e): ?>
    <div class="alert aerr">⚠ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
    <?php foreach ($info as $m): ?>
    <div class="alert aok">✓ <?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 4 && !empty($_SESSION['install_done'])): ?>
    <!-- ── STEP 4: CONCLUÍDO ── -->
    <div class="success-icon">🎉</div>
    <h2 style="text-align:center;font-size:19px;margin-bottom:6px">Instalação concluída!</h2>
    <p style="text-align:center;font-size:13px;color:#8b95a9;margin-bottom:18px">Themis Enterprise configurado com sucesso.</p>
    <div class="cred"><p>Endereço do sistema</p><strong><?= htmlspecialchars($_SESSION['install_done']['app_url']) ?></strong></div>
    <div class="cred"><p>E-mail do administrador</p><strong><?= htmlspecialchars($_SESSION['install_done']['admin_email']) ?></strong></div>
    <div class="warn">🔐 Este instalador será <strong>removido automaticamente</strong> ao clicar em Entrar.<br>Verifique que <code>install.php</code> foi deletado após o primeiro acesso.</div>
    <form method="post" style="margin-top:16px">
      <input type="hidden" name="step" value="4">
      <button type="submit" class="btn btn-primary">🚀 Entrar no Themis</button>
    </form>

    <?php elseif ($step === 1): ?>
    <!-- ── STEP 1: REQUISITOS ── -->
    <div class="slabel">Verificação do ambiente PHP</div>
    <?php foreach ($reqs as $label => $ok): ?>
    <div class="req-item">
      <div class="chk <?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✓' : '✗' ?></div>
      <span style="flex:1"><?= htmlspecialchars($label) ?></span>
      <span style="font-size:11.5px;font-weight:600;color:<?= $ok ? '#34d399' : '#fb7185' ?>"><?= $ok ? 'OK' : 'FALHOU' ?></span>
    </div>
    <?php endforeach; ?>

    <?php if (!$allPass): ?>
    <div class="alert aerr" style="margin-top:14px">
      Corrija os itens acima. No cPanel, vá em <strong>Selecionar Versão do PHP</strong> e ative as extensões necessárias.
    </div>
    <?php else: ?>
    <form method="post">
      <input type="hidden" name="step" value="2">
      <button type="submit" class="btn btn-primary" style="margin-top:16px">Próximo: Banco de Dados →</button>
    </form>
    <?php endif; ?>

    <?php elseif ($step === 2): ?>
    <!-- ── STEP 2: BANCO ── -->
    <div class="slabel">Credenciais do banco de dados MySQL</div>
    <form method="post">
      <input type="hidden" name="step" value="2">
      <div class="g2">
        <div class="fg"><label>Host</label><input name="db_host" value="localhost" required></div>
        <div class="fg"><label>Porta</label><input name="db_port" value="3306" required></div>
      </div>
      <div class="fg"><label>Nome do banco</label><input name="db_name" value="themis" required placeholder="themis"></div>
      <div class="g2">
        <div class="fg"><label>Usuário</label><input name="db_user" value="themis" required></div>
        <div class="fg"><label>Senha</label><input type="password" name="db_pass" value="Themis147369#" required></div>
      </div>
      <p class="note">O banco será criado automaticamente caso não exista. O usuário precisa ter permissão CREATE.</p>
      <button type="submit" class="btn btn-primary">Testar conexão e continuar →</button>
    </form>

    <?php elseif ($step === 3): ?>
    <!-- ── STEP 3: CONFIGURAÇÃO ── -->
    <form method="post" onsubmit="return validar(this)">
      <input type="hidden" name="step" value="3">
      <div class="fg"><label>URL do sistema</label>
        <input name="app_url" value="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'themis.ogrosystemas.com.br') ?>" required>
      </div>
      <div class="slabel">Escritório / Banca</div>
      <div class="fg"><label>Nome completo</label><input name="escritorio_nome" placeholder="Melo &amp; Associados Advocacia" required></div>
      <div class="fg"><label>CNPJ (opcional)</label><input name="escritorio_cnpj" placeholder="00.000.000/0001-00"></div>
      <div class="slabel">Administrador do sistema</div>
      <div class="fg"><label>Nome</label><input name="admin_nome" placeholder="Dr. Daniel Melo" required></div>
      <div class="fg"><label>E-mail</label><input type="email" name="admin_email" required placeholder="admin@escritorio.com.br"></div>
      <div class="g2">
        <div class="fg"><label>Senha</label><input type="password" name="admin_pass" minlength="8" required></div>
        <div class="fg"><label>Confirmar</label><input type="password" name="admin_pass2" minlength="8" required></div>
      </div>
      <button type="submit" class="btn btn-primary">⚙ Instalar Themis →</button>
    </form>
    <script>
    function validar(f){
      if(f.admin_pass.value!==f.admin_pass2.value){alert('As senhas não coincidem.');return false;}
      if(f.admin_pass.value.length<8){alert('Senha mínima de 8 caracteres.');return false;}
      if(!f.escritorio_nome.value.trim()){alert('Informe o nome do escritório.');return false;}
      f.querySelector('button[type=submit]').disabled=true;
      f.querySelector('button[type=submit]').textContent='Instalando…';
      return true;
    }
    </script>
    <?php endif; ?>

  </div><!-- /body -->
</div><!-- /card -->
<p style="text-align:center;font-size:11px;color:#2a3449;margin-top:12px">Themis Enterprise v<?= INSTALL_VERSION ?> · PHP <?= PHP_VERSION ?></p>
</div>
</body>
</html>
