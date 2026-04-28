<?php
/**
 * includes/settings.php
 * Carrega as configurações do sistema do banco de dados.
 * Chamado pelo bootstrap.php
 */

function loadSettings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $db = db();
        $rows = $db->query("SELECT chave, valor FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cache = $rows ?: [];
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}

function setting(string $key, string $default = ''): string {
    $s = loadSettings();
    return $s[$key] ?? $default;
}

function saveSetting(string $key, string $value): void {
    $db = db();
    $db->prepare("INSERT INTO system_settings (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?")
       ->execute([$key, $value, $value]);
}
