<?php
class DB {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (!self::$pdo) {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    public static function q(string $sql, array $p = []): PDOStatement {
        $s = self::get()->prepare($sql);
        $s->execute($p);
        return $s;
    }

    public static function row(string $sql, array $p = []): ?array {
        return self::q($sql, $p)->fetch() ?: null;
    }

    public static function all(string $sql, array $p = []): array {
        return self::q($sql, $p)->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        $cols = implode(',', array_keys($data));
        $ph   = implode(',', array_fill(0, count($data), '?'));
        self::q("INSERT INTO `$table` ($cols) VALUES ($ph)", array_values($data));
        return (int) self::get()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $wp = []): int {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
        return self::q("UPDATE `$table` SET $set WHERE $where", [...array_values($data), ...$wp])->rowCount();
    }

    public static function count(string $table, string $where = '1', array $p = []): int {
        return (int)(self::row("SELECT COUNT(*) as n FROM `$table` WHERE $where", $p)['n'] ?? 0);
    }

    public static function cfg(string $key, string $default = ''): string {
        $r = self::row("SELECT valor FROM configuracoes WHERE chave=?", [$key]);
        return $r['valor'] ?? $default;
    }

    public static function setCfg(string $key, string $value): void {
        self::q("INSERT INTO configuracoes (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?", [$key,$value,$value]);
    }

    public static function nextNum(string $key, string $prefix = ''): string {
        $pdo  = self::get();
        $inTx = $pdo->inTransaction();
        // Only open our own transaction if none is already active
        if (!$inTx) $pdo->beginTransaction();
        try {
            $r = self::row("SELECT valor FROM configuracoes WHERE chave=? FOR UPDATE", [$key]);
            $n = (int)($r['valor'] ?? 1);
            self::q("UPDATE configuracoes SET valor=? WHERE chave=?", [$n + 1, $key]);
            if (!$inTx) $pdo->commit();
            return $prefix . str_pad($n, 5, '0', STR_PAD_LEFT);
        } catch (\Throwable $e) {
            if (!$inTx) $pdo->rollBack();
            throw $e;
        }
    }

    public static function begin(): void {
        if (!self::get()->inTransaction()) self::get()->beginTransaction();
    }
    public static function commit(): void {
        if (self::get()->inTransaction()) self::get()->commit();
    }
    public static function rollback(): void {
        if (self::get()->inTransaction()) self::get()->rollBack();
    }
}
