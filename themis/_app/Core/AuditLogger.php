<?php
declare(strict_types=1);

/**
 * Themis Enterprise — AuditLogger
 *
 * Logs imutáveis append-only com MAC (HMAC-SHA256) de integridade.
 * Cada linha: JSON + "\n"
 * Arquivo: _storage/logs/{tenant_id}/audit_{date}.log
 *
 * Formato de cada entrada:
 *   {"ts":1234567890,"user_id":1,"action":"update","module":"processos",
 *    "target_id":42,"ip":"1.2.3.4","detail":"...","mac":"hex"}
 *
 * O MAC é calculado sobre os campos sem o "mac", garantindo integridade.
 * Verificação: AuditLogger::verify($logFile) retorna array de linhas inválidas.
 */
final class AuditLogger
{
    private const LOG_DIR_TPL = '/{tenant_id}/audit_{date}.log';

    private static string $baseDir = '';

    public static function init(): void
    {
        self::$baseDir = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage') . '/logs';
    }

    // ── Registra uma entrada ──────────────────────────────────────────────────
    public static function log(
        string $action,
        string $module,
        int|string|null $targetId = null,
        string $detail = '',
        ?int $userId = null,
        ?int $tenantId = null
    ): void {
        if (!self::$baseDir) self::init();

        // Resolve contexto atual se não fornecido
        $tid = $tenantId ?? self::resolveTenant();
        $uid = $userId   ?? self::resolveUser();

        $entry = [
            'ts'        => time(),
            'dt'        => date('Y-m-d H:i:s'),
            'user_id'   => $uid,
            'action'    => $action,          // create|update|delete|read|login|logout|error
            'module'    => $module,
            'target_id' => $targetId,
            'ip'        => self::ip(),
            'detail'    => $detail,
        ];

        $entry['mac'] = self::mac($entry);

        $file = self::filePath((string)$tid, date('Y-m-d'));
        self::ensureDir(dirname($file));

        // Append-only — nunca sobrescreve
        file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    }

    // ── Também grava na tabela audit_logs do banco ────────────────────────────
    public static function logDB(
        string $action,
        string $module,
        int|string|null $targetId = null,
        string $detail = '',
        ?array $req = null
    ): void {
        try {
            $db = DB::getInstance();
            $db->insert('audit_logs', [
                'tenant_id'   => self::resolveTenant(),
                'user_id'     => self::resolveUser(),
                'acao'        => $action,
                'modulo'      => $module,
                'entidade_id' => $targetId,
                'ip_address'  => self::ip(),
                'url'         => $_SERVER['REQUEST_URI'] ?? null,
            ]);
        } catch (\Throwable) {
            // Falha silenciosa — log em arquivo já garantiu o registro
        }
        self::log($action, $module, $targetId, $detail);
    }

    // ── Verificação de integridade ────────────────────────────────────────────
    public static function verify(string $filePath): array
    {
        $invalid = [];
        if (!file_exists($filePath)) return $invalid;
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $i => $line) {
            $entry = json_decode($line, true);
            if (!is_array($entry)) { $invalid[] = $i + 1; continue; }
            $mac = $entry['mac'] ?? '';
            unset($entry['mac']);
            if (!hash_equals(self::mac($entry), $mac)) {
                $invalid[] = $i + 1;
            }
        }
        return $invalid;
    }

    // ── Leitura de logs (últimas N entradas de um tenant) ─────────────────────
    public static function read(int $tenantId, string $date, int $limit = 100): array
    {
        if (!self::$baseDir) self::init();
        $file = self::filePath((string)$tenantId, $date);
        if (!file_exists($file)) return [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -$limit);
        return array_map(fn($l) => json_decode($l, true), $lines);
    }

    public static function readRange(int $tenantId, string $from, string $to, int $limit = 500): array
    {
        if (!self::$baseDir) self::init();
        $entries = [];
        $cur = new \DateTime($from);
        $end = new \DateTime($to);
        while ($cur <= $end && count($entries) < $limit) {
            $entries = array_merge($entries, self::read($tenantId, $cur->format('Y-m-d'), $limit));
            $cur->modify('+1 day');
        }
        return array_slice($entries, -$limit);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private static function mac(array $entry): string
    {
        $secret = Bootstrap::cfg('app.secret', 'themis_audit');
        return hash_hmac('sha256', json_encode($entry, JSON_UNESCAPED_UNICODE), $secret);
    }

    private static function filePath(string $tenantId, string $date): string
    {
        return self::$baseDir . '/' . $tenantId . '/audit_' . $date . '.log';
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        // Protege com .htaccess
        $ht = $dir . '/../.htaccess';
        if (!file_exists($ht)) {
            file_put_contents($ht, "Order deny,allow\nDeny from all\n");
        }
    }

    private static function ip(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return explode(',', $_SERVER[$k])[0];
            }
        }
        return '0.0.0.0';
    }

    private static function resolveTenant(): int
    {
        try { return DB::getInstance()->getTenantId(); } catch (\Throwable) { return 0; }
    }

    private static function resolveUser(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }
}
