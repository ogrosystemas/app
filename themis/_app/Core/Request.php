<?php
declare(strict_types=1);
final class Request
{
    public readonly array  $params;
    public readonly array  $query;
    public readonly array  $body;
    public readonly array  $files;
    public readonly string $method;
    public readonly string $uri;
    public ?array $user = null;

    public function __construct(array $routeParams = [])
    {
        $this->params = $routeParams;
        $this->query  = $_GET;
        $this->files  = $_FILES;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri    = $_SERVER['REQUEST_URI']    ?? '/';
        $raw = (string) file_get_contents('php://input');
        $ct  = $_SERVER['CONTENT_TYPE'] ?? '';
        $this->body = str_contains($ct, 'application/json')
            ? (array)(json_decode($raw, true) ?? [])
            : $_POST;
    }

    public function param(string $k, mixed $d = null): mixed  { return $this->params[$k] ?? $d; }
    public function input(string $k, mixed $d = null): mixed  { return $this->body[$k]   ?? $this->query[$k] ?? $d; }
    public function q(string $k, mixed $d = null): mixed      { return $this->query[$k]  ?? $d; }
    public function int(string $k): int                        { return (int)$this->input($k, 0); }
    public function str(string $k, string $d = ''): string    { return (string)$this->input($k, $d); }
    public function bool(string $k): bool                     { return filter_var($this->input($k), FILTER_VALIDATE_BOOLEAN); }
    public function all(): array                              { return array_merge($this->query, $this->body); }

    public function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $val = $this->body[$field] ?? $this->query[$field] ?? null;
            foreach (explode('|', $rule) as $r) {
                if ($r === 'required'  && ($val === null || $val === ''))  $errors[$field][] = "'{$field}' obrigatório.";
                if ($r === 'email'     && !filter_var($val, FILTER_VALIDATE_EMAIL)) $errors[$field][] = "E-mail inválido.";
                if ($r === 'numeric'   && !is_numeric($val))               $errors[$field][] = "'{$field}' deve ser numérico.";
                if (str_starts_with($r,'min:') && mb_strlen((string)$val) < (int)substr($r,4)) $errors[$field][] = "'{$field}' muito curto.";
                if (str_starts_with($r,'max:') && mb_strlen((string)$val) > (int)substr($r,4)) $errors[$field][] = "'{$field}' muito longo.";
            }
        }
        if ($errors) throw new \InvalidArgumentException(json_encode(['validation' => $errors]), 422);
        return $this->all();
    }
}
