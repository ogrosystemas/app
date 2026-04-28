<?php
// db.php — Conexão PDO com suporte a upsert e idempotência

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_one(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}

function db_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * INSERT simples — gera UUID automaticamente.
 * Lança exceção se já existir (use db_upsert para idempotência).
 */
function db_insert(string $table, array $data): string {
    db_assert_table($table);
    if (empty($data['id'])) {
        $data = ['id' => db_uuid()] + $data;
    }
    $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
    $vals = implode(', ', array_fill(0, count($data), '?'));
    db_query("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
    return $data['id'];
}

/**
 * INSERT ... ON DUPLICATE KEY UPDATE
 * Idempotente: insere ou atualiza se a chave única já existir.
 * $insert: campos a inserir (inclui a chave única)
 * $update: campos a atualizar em caso de duplicata (null = atualiza todos exceto id)
 */
function db_upsert(string $table, array $insert, ?array $update = null): string {
    db_assert_table($table);
    if (empty($insert['id'])) {
        $insert = ['id' => db_uuid()] + $insert;
    }
    $cols   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($insert)));
    $vals   = implode(', ', array_fill(0, count($insert), '?'));
    $update = $update ?? array_filter(
        array_keys($insert),
        fn($k) => $k !== 'id'
    );
    $sets   = implode(', ', array_map(fn($k) => "`{$k}`=VALUES(`{$k}`)", $update));
    db_query(
        "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals}) ON DUPLICATE KEY UPDATE {$sets}",
        array_values($insert)
    );
    return $insert['id'];
}

/**
 * INSERT IGNORE — silenciosamente ignora duplicatas.
 * Use quando não quiser atualizar em caso de duplicata.
 */
function db_insert_ignore(string $table, array $data): ?string {
    db_assert_table($table);
    if (empty($data['id'])) {
        $data = ['id' => db_uuid()] + $data;
    }
    $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
    $vals = implode(', ', array_fill(0, count($data), '?'));
    $stmt = db_query("INSERT IGNORE INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
    return $stmt->rowCount() > 0 ? $data['id'] : null;
}

function db_update(string $table, array $data, string $where, array $wp = []): int {
    db_assert_table($table);
    $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
    $stmt = db_query("UPDATE `{$table}` SET {$sets} WHERE {$where}", [...array_values($data), ...$wp]);
    return $stmt->rowCount();
}

function db_delete(string $table, string $where, array $wp = []): int {
    db_assert_table($table);
    $stmt = db_query("DELETE FROM `{$table}` WHERE {$where}", $wp);
    return $stmt->rowCount();
}

function db_exists(string $table, string $where, array $wp = []): bool {
    db_assert_table($table);
    $row = db_one("SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1", $wp);
    return $row !== null;
}

function db_uuid(): string {
    $d    = random_bytes(16);
    $d[6] = chr(ord($d[6]) & 0x0f | 0x40);
    $d[8] = chr(ord($d[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

/**
 * Transação segura com rollback automático.
 * Uso: db_transaction(function() { db_insert(...); db_update(...); });
 */
function db_transaction(callable $fn): mixed {
    db()->beginTransaction();
    try {
        $result = $fn();
        db()->commit();
        return $result;
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}
