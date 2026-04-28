<?php
/**
 * Themis — cron/import_indices.php
 * Importa SELIC (BCB) e IPCA-E (IBGE) do mês anterior
 * Agendar: 0 3 2 * * php /var/www/themis/cron/import_indices.php >> /var/log/themis_indices.log 2>&1
 */
declare(strict_types=1);
define('THEMIS_ROOT', dirname(__DIR__, 2));
require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$db  = DB::getInstance();
$eng = new CalculationEngine($db);

// Mês anterior
$ano = (int) date('Y', strtotime('first day of last month'));
$mes = (int) date('n', strtotime('first day of last month'));

$ok_selic  = $eng->importarSelic($ano, $mes);
$ok_ipca   = $eng->importarIpcaE($ano, $mes);
$ok_inpc   = $eng->importarInpc($ano, $mes);
$ok_igpm   = $eng->importarIgpm($ano, $mes);

echo sprintf(
    "[%s] Índices %02d/%04d — SELIC: %s | IPCA-E: %s | INPC: %s | IGP-M: %s\n",
    date('Y-m-d H:i:s'),
    $mes, $ano,
    $ok_selic ? 'OK' : 'FALHOU',
    $ok_ipca  ? 'OK' : 'FALHOU',
    $ok_inpc  ? 'OK' : 'FALHOU',
    $ok_igpm  ? 'OK' : 'FALHOU'
);
