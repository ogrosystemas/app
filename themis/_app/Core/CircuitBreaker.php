<?php
declare(strict_types=1);

/**
 * Themis Enterprise — CircuitBreaker
 *
 * Para cada API externa, monitora falhas e abre o circuito após 3 falhas.
 * Estado "OPEN" dura 60s. Fila de retry em _storage/queue/retry_{api}.json
 */
final class CircuitBreaker
{
    private const MAX_FAILURES  = 3;
    private const OPEN_DURATION = 60;   // segundos
    private const QUEUE_MAX     = 100;  // itens máximos na fila

    private string $stateDir;
    private string $queueDir;

    public function __construct()
    {
        $base = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage');
        $this->stateDir = $base . '/circuit';
        $this->queueDir = $base . '/queue';
        foreach ([$this->stateDir, $this->queueDir] as $d) {
            if (!is_dir($d)) mkdir($d, 0750, true);
        }
    }

    // ── Estados ───────────────────────────────────────────────────────────────
    public function isOpen(string $api): bool
    {
        $state = $this->readState($api);
        if ($state['status'] !== 'open') return false;

        $openedAt = $state['opened_at'] ?? 0;
        if ((time() - $openedAt) >= self::OPEN_DURATION) {
            // Half-open: tenta fechar
            $state['status'] = 'half-open';
            $this->writeState($api, $state);
            return false;
        }
        return true;
    }

    public function getStatus(string $api): string
    {
        return $this->readState($api)['status'] ?? 'closed';
    }

    public function getFailures(string $api): int
    {
        return (int)($this->readState($api)['failures'] ?? 0);
    }

    public function getRemainingOpen(string $api): int
    {
        $state = $this->readState($api);
        if ($state['status'] !== 'open') return 0;
        return max(0, self::OPEN_DURATION - (time() - ($state['opened_at'] ?? 0)));
    }

    // ── Registro de sucesso/falha ─────────────────────────────────────────────
    public function recordSuccess(string $api): void
    {
        $this->writeState($api, [
            'status'    => 'closed',
            'failures'  => 0,
            'last_ok'   => time(),
        ]);
    }

    public function recordFailure(string $api, string $error = ''): void
    {
        $state     = $this->readState($api);
        $failures  = (int)($state['failures'] ?? 0) + 1;
        $newState  = [
            'status'     => $failures >= self::MAX_FAILURES ? 'open' : 'closed',
            'failures'   => $failures,
            'last_error' => $error,
            'last_fail'  => time(),
        ];
        if ($newState['status'] === 'open') {
            $newState['opened_at'] = time();
        }
        $this->writeState($api, $newState);

        // Loga no audit
        error_log(sprintf('[CircuitBreaker] %s: falha %d/%d%s',
            $api, $failures, self::MAX_FAILURES,
            $newState['status'] === 'open' ? ' — CIRCUITO ABERTO' : ''
        ));
    }

    public function forceClose(string $api): void
    {
        $this->writeState($api, ['status' => 'closed', 'failures' => 0]);
    }

    // ── Wrapper para chamadas externas ────────────────────────────────────────
    /**
     * Executa $callable protegido pelo circuit breaker.
     * Se aberto, enfileira para retry e lança exceção.
     *
     * @param string   $api      Nome da API (datajud, evolution, assinafy, smtp)
     * @param callable $callable Função que faz a chamada externa
     * @param array    $retryPayload Dados para enfileirar se falhar
     */
    public function call(string $api, callable $callable, array $retryPayload = []): mixed
    {
        if ($this->isOpen($api)) {
            if ($retryPayload) $this->enqueue($api, $retryPayload);
            throw new \RuntimeException("API {$api} indisponível (circuito aberto). Tentativa enfileirada.", 503);
        }

        try {
            $result = $callable();
            $this->recordSuccess($api);
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($api, $e->getMessage());
            if ($retryPayload) $this->enqueue($api, $retryPayload);
            throw $e;
        }
    }

    // ── Fila de retry ─────────────────────────────────────────────────────────
    public function enqueue(string $api, array $payload): void
    {
        $file  = $this->queueDir . '/retry_' . preg_replace('/[^a-z0-9_]/', '', $api) . '.json';
        $queue = $this->readQueue($file);
        if (count($queue) >= self::QUEUE_MAX) array_shift($queue); // descarta mais antigo
        $queue[] = array_merge($payload, [
            '_queued_at'  => time(),
            '_attempts'   => 0,
            '_api'        => $api,
        ]);
        file_put_contents($file, json_encode($queue), LOCK_EX);
    }

    public function dequeue(string $api, int $limit = 10): array
    {
        $file  = $this->queueDir . '/retry_' . preg_replace('/[^a-z0-9_]/', '', $api) . '.json';
        $queue = $this->readQueue($file);
        if (!$queue) return [];
        $batch = array_splice($queue, 0, $limit);
        file_put_contents($file, json_encode($queue), LOCK_EX);
        return $batch;
    }

    public function queueSize(string $api): int
    {
        $file = $this->queueDir . '/retry_' . preg_replace('/[^a-z0-9_]/', '', $api) . '.json';
        return count($this->readQueue($file));
    }

    public function allStatus(): array
    {
        $result = [];
        foreach (['datajud', 'evolution', 'assinafy', 'smtp'] as $api) {
            $state = $this->readState($api);
            $result[$api] = [
                'status'     => $state['status']     ?? 'closed',
                'failures'   => $state['failures']   ?? 0,
                'last_error' => $state['last_error']  ?? null,
                'last_ok'    => $state['last_ok']     ?? null,
                'queue_size' => $this->queueSize($api),
                'remaining'  => $this->getRemainingOpen($api),
            ];
        }
        return $result;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function readState(string $api): array
    {
        $file = $this->stateDir . '/' . preg_replace('/[^a-z0-9_]/', '', $api) . '.json';
        if (!file_exists($file)) return ['status' => 'closed', 'failures' => 0];
        $d = json_decode(file_get_contents($file), true);
        return is_array($d) ? $d : ['status' => 'closed', 'failures' => 0];
    }

    private function writeState(string $api, array $state): void
    {
        $file = $this->stateDir . '/' . preg_replace('/[^a-z0-9_]/', '', $api) . '.json';
        file_put_contents($file, json_encode($state), LOCK_EX);
    }

    private function readQueue(string $file): array
    {
        if (!file_exists($file)) return [];
        $d = json_decode(file_get_contents($file), true);
        return is_array($d) ? $d : [];
    }
}
