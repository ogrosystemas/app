<?php
/**
 * Themis — cron/retry_queue.php
 * Processa fila de retry de APIs (DataJud, Assinafy, WhatsApp)
 * Agendar: *\/5 * * * * php /var/www/themis/cron/retry_queue.php >> /var/log/themis_retry.log 2>&1
 */
declare(strict_types=1);
define('THEMIS_ROOT', dirname(__DIR__, 2));
require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$db     = DB::getInstance();
$wh     = new WebhookHandler($db);
$report = $wh->processRetryQueue();

echo sprintf(
    "[%s] Retry Queue: sucesso=%d | falhou=%d | esgotado=%d\n",
    date('Y-m-d H:i:s'),
    $report['sucesso'],
    $report['falhou'],
    $report['esgotado']
);
