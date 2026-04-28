<?php
/**
 * Themis — cron/purge_trash.php
 * Purga definitiva de documentos na lixeira há mais de TRASH_DAYS dias
 * Agendar: 0 4 * * * php /var/www/themis/cron/purge_trash.php >> /var/log/themis_trash.log 2>&1
 */
declare(strict_types=1);
define('THEMIS_ROOT', dirname(__DIR__, 2));
require_once THEMIS_ROOT . '/_app/Bootstrap.php';
Bootstrap::boot();

$db      = DB::getInstance();
$storage = new StorageManager($db);
$count   = $storage->purgeTrash();

echo sprintf("[%s] Lixeira purgada: %d arquivo(s) removido(s)\n", date('Y-m-d H:i:s'), $count);

// Limpa sessões expiradas
$sessoes = $db->run("DELETE FROM sessions WHERE expires_at < NOW()")->rowCount();
echo sprintf("[%s] Sessões expiradas removidas: %d\n", date('Y-m-d H:i:s'), $sessoes);

// Limpa rate-limit files do temp
$rlFiles = glob(sys_get_temp_dir() . '/rl_*.rl') ?: [];
$removed = 0;
foreach ($rlFiles as $f) {
    if (filemtime($f) < time() - 3600) { unlink($f); $removed++; }
}
echo sprintf("[%s] Rate-limit temp files removidos: %d\n", date('Y-m-d H:i:s'), $removed);
