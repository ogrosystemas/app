<?php
/**
 * db.php — Camada de banco de dados PDO
 * SAM v2.0
 */

// ── Whitelist de tabelas permitidas ──────────────────────
const DB_ALLOWED_TABLES = [
    'tenants', 'users', 'sessions', 'meli_accounts',
    'products', 'orders', 'order_items',
    'sac_messages', 'sac_conversations',
    'transactions', 'queue_jobs', 'audit_logs',
    'tenant_settings', 'chart_of_accounts',
    'bank_accounts', 'financial_entries', 'login_attempts',
    // Sprint 1
    'quick_replies', 'auto_messages', 'auto_messages_log',
    // Sprint 2
    'renovacoes_log',
    // Sprint 3
    'price_rules',
    // Outros
    'questions', 'licenses', 'usuarios', 'claim_notes', 'customers', 'kits', 'kit_items', 'autoparts', 'autoparts_compatibility', 'competitor_monitors',
];

function db_assert_table(string $table): void {
    if (!in_array($table, DB_ALLOWED_TABLES, true)) {
        error_log("[SECURITY] db_assert_table blocked table: {$table}");
        throw new InvalidArgumentException("Tabela não permitida: {$table}");
    }
}

// ── Conexão PDO singleton ─────────────────────────────────
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

// ── Query raw ─────────────────────────────────────────────
function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// ── Fetch one row ─────────────────────────────────────────
function db_one(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}

// ── Fetch all rows ────────────────────────────────────────
function db_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

// ── UUID v4 ───────────────────────────────────────────────
function db_uuid(): string {
    $b = random_bytes(16);
    $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
    $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

// ── INSERT (gera UUID automaticamente) ───────────────────
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

// ── UPDATE ────────────────────────────────────────────────
function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    db_assert_table($table);
    $set = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
    $stmt = db_query("UPDATE `{$table}` SET {$set} WHERE {$where}", [...array_values($data), ...$whereParams]);
    return $stmt->rowCount();
}

// ── DELETE ────────────────────────────────────────────────
function db_delete(string $table, string $where, array $params = []): int {
    db_assert_table($table);
    return db_query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
}

// ── UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) ──────────
function db_upsert(string $table, array $insert, ?array $update = null): string {
    db_assert_table($table);
    if (empty($insert['id'])) {
        $insert = ['id' => db_uuid()] + $insert;
    }
    $cols   = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($insert)));
    $vals   = implode(', ', array_fill(0, count($insert), '?'));
    $update = $update ?? array_filter(array_keys($insert), fn($k) => $k !== 'id');
    $upd    = implode(', ', array_map(fn($k) => "`{$k}` = VALUES(`{$k}`)", (array)$update));
    db_query("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals}) ON DUPLICATE KEY UPDATE {$upd}", array_values($insert));
    return $insert['id'];
}

// ── Tenant Settings (upsert por tenant_id + key) ─────────
function tenant_setting_set(string $tenantId, string $key, string $value): void {
    db_query(
        "INSERT INTO tenant_settings (id, tenant_id, `key`, value)
         VALUES (UUID(), ?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value)",
        [$tenantId, $key, $value]
    );
}

function tenant_setting_get(string $tenantId, string $key, string $default = ''): string {
    $row = db_one(
        "SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`=?",
        [$tenantId, $key]
    );
    return $row['value'] ?? $default;
}


function db_insert_ignore(string $table, array $data): string {
    db_assert_table($table);
    if (empty($data['id'])) {
        $data = ['id' => db_uuid()] + $data;
    }
    $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
    $vals = implode(', ', array_fill(0, count($data), '?'));
    db_query("INSERT IGNORE INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
    return $data['id'];
}

// ── EXISTS ────────────────────────────────────────────────
function db_exists(string $table, string $where, array $params = []): bool {
    db_assert_table($table);
    $row = db_query("SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1", $params)->fetch();
    return (bool)$row;
}

// ── TRANSACTION ───────────────────────────────────────────
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
