<?php
declare(strict_types=1);
use PDO;
use PDOException;
use PDOStatement;

final class DB
{
    private static ?self $instance = null;
    private PDO $pdo;
    private ?int $tenantId = null;
    private array $queryLog = [];
    private int $queryCount = 0;

    private function __construct()
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'themis'
        );
        try {
            $this->pdo = new PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASS'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='-03:00', sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE'",
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Falha na conexão com o banco de dados.', 500, $e);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setTenant(int $id): static  { $this->tenantId = $id; return $this; }
    public function getTenantId(): ?int          { return $this->tenantId; }

    public function run(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new \RuntimeException("DB Error [{$e->getCode()}]: {$e->getMessage()}", 500, $e);
        }
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $this->queryLog[] = ['sql' => $sql, 'time_ms' => round((microtime(true) - $start) * 1000, 2)];
        }
        $this->queryCount++;
        return $stmt;
    }

    public function all(string $sql, array $p = []): array         { return $this->run($sql, $p)->fetchAll(); }
    public function first(string $sql, array $p = []): array|false { return $this->run($sql, $p)->fetch(); }
    public function scalar(string $sql, array $p = []): mixed      { return $this->run($sql, $p)->fetchColumn(); }

    public function insert(string $table, array $data): int|string
    {
        if ($this->tenantId && !array_key_exists('tenant_id', $data)) {
            $data['tenant_id'] = $this->tenantId;
        }
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $this->run("INSERT INTO `{$table}` ({$cols}) VALUES ({$vals})", array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $cond = implode(' AND ', array_map(fn($k) => "`{$k}` = ?", array_keys($where)));
        return $this->run(
            "UPDATE `{$table}` SET {$set} WHERE {$cond}",
            [...array_values($data), ...array_values($where)]
        )->rowCount();
    }

    public function softDelete(string $table, int $id): void
    {
        $where = ['id' => $id];
        if ($this->tenantId) $where['tenant_id'] = $this->tenantId;
        $this->update($table, ['deleted_at' => date('Y-m-d H:i:s')], $where);
    }

    public function paginate(string $sql, array $params = [], int $page = 1, int $perPage = 25): array
    {
        $total  = (int) $this->scalar("SELECT COUNT(*) FROM ({$sql}) AS _c", $params);
        $offset = ($page - 1) * $perPage;
        $items  = $this->all("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params);
        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / max($perPage, 1)),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function transaction(callable $fn): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $fn($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }
    public function getQueryLog(): array   { return $this->queryLog; }
    public function getQueryCount(): int   { return $this->queryCount; }
}
