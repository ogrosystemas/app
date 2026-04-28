<?php
/**
 * Themis — cron/daily.php
 * Executa verificações diárias: processos parados, prazos, CRM, aniversários
 * Agendar: 0 7 * * * php /var/www/themis/cron/daily.php >> /var/log/themis_daily.log 2>&1
 */
declare(strict_types=1);
define('THEMIS_ROOT', dirname(__DIR__, 2));
require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$db = DB::getInstance();

// Busca todos os tenants ativos
$tenants = $db->all("SELECT id, razao_social FROM tenants WHERE deleted_at IS NULL");

$totalProcessados = 0;
foreach ($tenants as $tenant) {
    $db->setTenant((int) $tenant['id']);
    try {
        $engine = new WorkflowEngine($db);
        $report = $engine->runDailyChecks((int) $tenant['id']);
        $totalProcessados++;
        echo sprintf(
            "[%s] Tenant #%d (%s): parados=%d | prazos=%d | crm=%d | aniv=%d\n",
            date('Y-m-d H:i:s'),
            $tenant['id'],
            $tenant['razao_social'],
            $report['parados'],
            $report['prazos_urgentes'],
            $report['followup_crm'],
            $report['aniversarios']
        );
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO tenant #{$tenant['id']}: {$e->getMessage()}\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Daily concluído. Tenants processados: {$totalProcessados}\n";
