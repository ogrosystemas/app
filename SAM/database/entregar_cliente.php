#!/usr/bin/env php
<?php
/**
 * database/entregar_cliente.php
 * Zera o banco e prepara o sistema para o cliente com senha aleatória segura.
 *
 * USO:
 *   /usr/local/lsws/lsphp83/bin/php8.3 /home/www/lupa/database/entregar_cliente.php 2>/dev/null
 *
 * O script pergunta os dados do cliente e faz tudo automaticamente:
 *   1. Zera todas as tabelas
 *   2. Cria tenant + usuário admin com senha aleatória
 *   3. Insere plano de contas padrão
 *   4. Exibe credenciais de acesso para repassar ao cliente
 */

// Só roda via CLI
if (PHP_SAPI !== 'cli') { exit("Rode via terminal.\n"); }

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';

// ── Helpers ──────────────────────────────────────────────────
function ask(string $prompt, string $default = ''): string {
    echo $prompt . ($default ? " [{$default}]" : '') . ': ';
    $line = trim(fgets(STDIN));
    return $line !== '' ? $line : $default;
}

function gerar_senha(): string {
    $chars    = 'abcdefghjkmnpqrstuvwxyz'; // sem i, l, o (confusos)
    $nums     = '23456789';               // sem 0, 1 (confusos)
    $specials = '@#$!';

    $senha = '';
    // Padrão: Cc00@cc00 — fácil de digitar, segura
    $senha .= strtoupper($chars[random_int(0, strlen($chars)-1)]);
    $senha .= $chars[random_int(0, strlen($chars)-1)];
    $senha .= $nums[random_int(0, strlen($nums)-1)];
    $senha .= $nums[random_int(0, strlen($nums)-1)];
    $senha .= $specials[random_int(0, strlen($specials)-1)];
    $senha .= strtoupper($chars[random_int(0, strlen($chars)-1)]);
    $senha .= $chars[random_int(0, strlen($chars)-1)];
    $senha .= $nums[random_int(0, strlen($nums)-1)];
    $senha .= $nums[random_int(0, strlen($nums)-1)];

    return $senha;
}

// ── Coleta dados ─────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║   SAM — Entrega ao Cliente          ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";
echo "⚠  ATENÇÃO: Este script APAGA TODOS OS DADOS do banco.\n";
echo "   Certifique-se de ter feito backup antes de continuar.\n\n";

$confirma = ask('Digite CONFIRMAR para continuar');
if ($confirma !== 'CONFIRMAR') {
    echo "Cancelado.\n";
    exit(0);
}

echo "\n── Dados do cliente ──────────────────────────\n";
$empresa = ask('Nome da empresa', 'Minha Empresa');
$email   = ask('E-mail do admin');
$nome    = ask('Nome do administrador', 'Administrador');
$plano   = ask('Plano (TRIAL/PRO/ENTERPRISE)', 'PRO');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "E-mail inválido. Cancelado.\n";
    exit(1);
}

$senha = gerar_senha();
$hash  = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

echo "\n── Confirmação ────────────────────────────────\n";
echo "Empresa : {$empresa}\n";
echo "E-mail  : {$email}\n";
echo "Admin   : {$nome}\n";
echo "Plano   : {$plano}\n";
echo "Senha   : {$senha}  ← anote agora!\n\n";

$ok = ask('Confirma? (s/n)', 'n');
if (strtolower($ok) !== 's') {
    echo "Cancelado.\n";
    exit(0);
}

// ── Executa ──────────────────────────────────────────────────
echo "\nZerando banco de dados...\n";

$tabelas = [
    'audit_logs','login_attempts','queue_jobs','sac_conversations',
    'sac_messages','order_items','orders','transactions',
    'financial_entries','bank_accounts','products','meli_accounts',
    'chart_of_accounts','tenant_settings','sessions','users','tenants',
];

db_query('SET foreign_key_checks = 0');
foreach ($tabelas as $t) {
    db_query("TRUNCATE TABLE `{$t}`");
    echo "  ✓ {$t}\n";
}
db_query('SET foreign_key_checks = 1');

// Tenant
$tenantId = db_uuid();
db_insert('tenants', [
    'id'             => $tenantId,
    'name'           => $empresa,
    'plan'           => strtoupper($plano),
    'is_active'      => 1,
    'license_status' => 'TRIAL',
    'trial_started'  => date('Y-m-d H:i:s'),
    'license_expiry' => date('Y-m-d H:i:s', strtotime('+15 days')),
]);
echo "\n✓ Tenant criado: {$empresa}\n";

// Usuário admin
$userId = db_uuid();
db_insert('users', [
    'id'                     => $userId,
    'tenant_id'              => $tenantId,
    'name'                   => $nome,
    'email'                  => strtolower(trim($email)),
    'password_hash'          => $hash,
    'role'                   => 'ADMIN',
    'is_active'              => 1,
    'can_access_sac'         => 1,
    'can_access_anuncios'    => 1,
    'can_access_financeiro'  => 1,
    'can_access_logistica'   => 1,
    'can_access_admin'       => 1,
]);
echo "✓ Admin criado: {$email}\n";

// Plano de contas
$coa = [
    ['3.1.1','Vendas Marketplace ML','RECEITA','OPERACIONAL','RECEITA_BRUTA'],
    ['3.1.2','Vendas Loja Própria','RECEITA','OPERACIONAL','RECEITA_BRUTA'],
    ['3.9.1','Taxas Marketplace ML','DESPESA','DEDUCAO','DEDUCOES'],
    ['3.9.2','Devoluções','DESPESA','DEDUCAO','DEDUCOES'],
    ['4.1.1','Custo de Mercadorias','CUSTO','CMV','CMV'],
    ['4.2.1','Fretes de Envio','CUSTO','LOGISTICA','CMV'],
    ['4.2.2','Embalagens','CUSTO','LOGISTICA','CMV'],
    ['5.1.1','Salários e Pró-labore','DESPESA','PESSOAL','DESPESAS_OPERACIONAIS'],
    ['5.1.2','Encargos Trabalhistas','DESPESA','PESSOAL','DESPESAS_OPERACIONAIS'],
    ['5.2.1','Aluguel','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.2.2','Energia Elétrica','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.2.3','Internet e Telefone','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.2.4','Água e Saneamento','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.2.5','Material de Escritório','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.2.6','Contabilidade','DESPESA','ADMINISTRATIVA','DESPESAS_OPERACIONAIS'],
    ['5.3.1','Anúncios Patrocinados ML','DESPESA','MARKETING','DESPESAS_OPERACIONAIS'],
    ['5.3.2','Google Ads / Meta Ads','DESPESA','MARKETING','DESPESAS_OPERACIONAIS'],
    ['5.4.1','SAM / Sistemas','DESPESA','TECNOLOGIA','DESPESAS_OPERACIONAIS'],
    ['5.4.2','Hospedagem e Domínio','DESPESA','TECNOLOGIA','DESPESAS_OPERACIONAIS'],
    ['5.5.1','Tarifas Bancárias','DESPESA','FINANCEIRA','DESPESAS_FINANCEIRAS'],
    ['5.5.2','Juros e Multas','DESPESA','FINANCEIRA','DESPESAS_FINANCEIRAS'],
    ['5.6.1','Simples Nacional / DAS','DESPESA','FISCAL','DESPESAS_OPERACIONAIS'],
    ['5.9.1','Despesas Diversas','DESPESA','OUTRAS','OUTRAS_DESPESAS'],
];
foreach ($coa as [$code, $name, $type, $subtype, $dre]) {
    db_insert('chart_of_accounts', [
        'tenant_id' => $tenantId,
        'code'      => $code,
        'name'      => $name,
        'type'      => $type,
        'subtype'   => $subtype,
        'dre_line'  => $dre,
        'is_active' => 1,
    ]);
}
echo "✓ Plano de contas inserido\n";

// ── Credenciais ML — copia do config.php para o tenant ────────
// O cliente usa o mesmo App ML do sistema (modelo SaaS)
$mlAppId     = defined('MELI_APP_ID')        ? MELI_APP_ID        : '';
$mlSecret    = defined('MELI_CLIENT_SECRET') ? MELI_CLIENT_SECRET : '';

if ($mlAppId && $mlSecret) {
    db_upsert('tenant_settings',
        ['tenant_id' => $tenantId, 'key' => 'meli_app_id',        'value' => $mlAppId],
        ['value']
    );
    db_upsert('tenant_settings',
        ['tenant_id' => $tenantId, 'key' => 'meli_client_secret',  'value' => $mlSecret],
        ['value']
    );
    echo "✓ Credenciais ML configuradas (App ID: {$mlAppId})\n";
} else {
    echo "⚠ MELI_APP_ID ou MELI_CLIENT_SECRET não definidos no config.php — configure manualmente!\n";
}

// ── Resultado ─────────────────────────────────────────────────
echo "\n╔══════════════════════════════════════════════╗\n";
echo "║   ✓ Sistema pronto para o cliente!           ║\n";
echo "╠══════════════════════════════════════════════╣\n";
echo "║                                              ║\n";
printf("║  URL    : %-35s║\n", APP_URL);
printf("║  Login  : %-35s║\n", $email);
printf("║  Senha  : %-35s║\n", $senha);
echo "║                                              ║\n";
echo "║  ⚠ Repasse estas credenciais ao cliente      ║\n";
echo "║    e oriente a trocar a senha no 1º login.   ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// Salva um arquivo de log com as credenciais (para referência)
$logFile = dirname(__DIR__) . '/storage/logs/entrega_' . date('Ymd_His') . '.txt';
file_put_contents($logFile,
    "Entrega realizada em: " . date('d/m/Y H:i:s') . "\n" .
    "Empresa : {$empresa}\n" .
    "URL     : " . APP_URL . "\n" .
    "Login   : {$email}\n" .
    "Senha   : {$senha}\n" .
    "Tenant  : {$tenantId}\n"
);
echo "Log salvo em: {$logFile}\n\n";
