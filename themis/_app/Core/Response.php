<?php
declare(strict_types=1);
final class Response
{
    public function __construct(
        private readonly mixed $data,
        private readonly int   $status  = 200,
        private readonly array $headers = []
    ) {}

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    { return new self($data, $status, $headers); }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): self
    { return new self(['success' => true, 'message' => $message, 'data' => $data], $status); }

    public static function error(string $message, int $status = 400, mixed $details = null): self
    { return new self(['error' => true, 'message' => $message, 'code' => $status, 'details' => $details], $status); }

    public static function paginated(array $paged, int $status = 200): self
    { return new self(array_merge(['success' => true], $paged), $status); }

    public static function created(mixed $data = null, string $message = 'Criado com sucesso.'): self
    { return new self(['success' => true, 'message' => $message, 'data' => $data], 201); }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($this->headers as $k => $v) header("{$k}: {$v}");
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
