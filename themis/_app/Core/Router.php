<?php
declare(strict_types=1);
// ============================================================
// Router
// ============================================================
final class Router
{
    private array $routes = [];
    private string $prefix = '';
    private array $groupMw = [];

    public function get(string $p, array|callable $h, array $mw = []): self    { return $this->add('GET',    $p, $h, $mw); }
    public function post(string $p, array|callable $h, array $mw = []): self   { return $this->add('POST',   $p, $h, $mw); }
    public function put(string $p, array|callable $h, array $mw = []): self    { return $this->add('PUT',    $p, $h, $mw); }
    public function patch(string $p, array|callable $h, array $mw = []): self  { return $this->add('PATCH',  $p, $h, $mw); }
    public function delete(string $p, array|callable $h, array $mw = []): self { return $this->add('DELETE', $p, $h, $mw); }

    private function add(string $method, string $path, array|callable $handler, array $mw): self
    {
        $full = $this->prefix . $path;
        $this->routes[] = [
            'method'  => $method,
            'path'    => $full,
            'handler' => $handler,
            'mw'      => array_merge($this->groupMw, $mw),
            'regex'   => '#^' . preg_replace('/\{([a-zA-Z_]\w*)\}/', '(?P<$1>[^/]+)', $full) . '$#',
        ];
        return $this;
    }

    public function group(string $prefix, callable $fn, array $mw = []): void
    {
        [$prevP, $prevM]    = [$this->prefix, $this->groupMw];
        $this->prefix       = $prevP . $prefix;
        $this->groupMw      = array_merge($prevM, $mw);
        $fn($this);
        [$this->prefix, $this->groupMw] = [$prevP, $prevM];
    }

    public function dispatch(): never
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = rawurldecode(strtok($_SERVER['REQUEST_URI'], '?'));

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) continue;
            if (!preg_match($route['regex'], $uri, $m)) continue;

            $params  = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $request = new Request($params);
            $next    = fn() => $this->callHandler($route['handler'], $request);

            foreach (array_reverse($route['mw']) as $mwClass) {
                $nextCopy = $next;
                $mwInst   = is_string($mwClass) ? new $mwClass() : $mwClass;
                $next     = fn() => $mwInst->handle($request, $nextCopy);
            }

            $this->send($next());
            exit;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Rota não encontrada.', 'code' => 404], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function callHandler(array|callable $handler, Request $request): mixed
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            return (new $class())->$method($request);
        }
        return $handler($request);
    }

    private function send(mixed $response): void
    {
        if ($response instanceof Response) { $response->send(); return; }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
