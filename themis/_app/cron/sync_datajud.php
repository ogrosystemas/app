<?php
/**
 * Themis — cron/sync_datajud.php
 * Sincroniza todos os processos monitorados com o DataJud/CNJ
 * Agendar: 0 2 * * * php /var/www/themis/cron/sync_datajud.php >> /var/log/themis_datajud.log 2>&1
 */
declare(strict_types=1);
define('THEMIS_ROOT', dirname(__DIR__, 2));
require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$db      = DB::getInstance();
$tenants = $db->all("SELECT id, razao_social FROM tenants WHERE deleted_at IS NULL");

foreach ($tenants as $tenant) {
    $db->setTenant((int) $tenant['id']);
    try {
        $svc    = new DataJudService($db);
        $report = $svc->monitorarTodos((int) $tenant['id']);
        echo sprintf(
            "[%s] DataJud — Tenant #%d (%s): sync=%d | sem_retorno=%d | erros=%d\n",
            date('Y-m-d H:i:s'),
            $tenant['id'],
            $tenant['razao_social'],
            $report['sincronizados'],
            $report['sem_retorno'],
            $report['erros']
        );
    } catch (\Throwable $e) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO tenant #{$tenant['id']}: {$e->getMessage()}\n";
    }
}
