<?php
declare(strict_types=1);
// ============================================================
// AuthMiddleware — valida JWT e injeta user na request
// ============================================================
final class AuthMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $db   = DB::getInstance();
        $auth = new Auth($db);
        $user = $auth->guard();        // lança 401/403 se inválido
        $request->user = $user;
        $db->setTenant((int) $user['tenant_id']);
        return $next();
    }
}

// ============================================================
// AuthClienteMiddleware — apenas perfil=cliente
// ============================================================
final class AuthClienteMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $db   = DB::getInstance();
        $auth = new Auth($db);
        $user = $auth->guard('cliente');
        $request->user = $user;
        $db->setTenant((int) $user['tenant_id']);
        return $next();
    }
}

// ============================================================
// SocioOnlyMiddleware — silo financeiro
// Garante que o owner_id do request = user logado (ou admin)
// ============================================================
final class SocioOnlyMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $user = $request->user;
        if (!$user) throw new \RuntimeException('Não autenticado.', 401);
        if (!in_array($user['perfil'], ['socio', 'admin'], true)) {
            throw new \RuntimeException('Acesso restrito a sócios.', 403);
        }
        return $next();
    }
}

// ============================================================
// FinanceiroSiloMiddleware
// Injeta owner_id automaticamente em queries financeiras;
// sócios vêem apenas seus próprios dados, admin vê tudo
// ============================================================
final class FinanceiroSiloMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        $user = $request->user;
        if (!$user) throw new \RuntimeException('Não autenticado.', 401);

        $perfil = $user['perfil'];
        if (!in_array($perfil, ['socio', 'admin', 'financeiro'], true)) {
            throw new \RuntimeException('Sem acesso ao módulo financeiro.', 403);
        }

        // Sócio só vê o próprio silo
        if ($perfil === 'socio') {
            // Força owner_id no body/query para uso nos controllers
            $_GET['_owner_id']  = $user['owner_id'] ?? $user['id'];
            $_POST['_owner_id'] = $user['owner_id'] ?? $user['id'];
        }

        return $next();
    }
}

// ============================================================
// AdminOnlyMiddleware
// ============================================================
final class AdminOnlyMiddleware
{
    public function handle(Request $request, callable $next): mixed
    {
        if (($request->user['perfil'] ?? '') !== 'admin') {
            throw new \RuntimeException('Acesso restrito a administradores.', 403);
        }
        return $next();
    }
}

// ============================================================
// RateLimitMiddleware — baseado em IP + rota
// ============================================================
final class RateLimitMiddleware
{
    private int $maxRequests;
    private int $window;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->window      = $windowSeconds;
    }

    public function handle(Request $request, callable $next): mixed
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'rl_' . md5($ip . $request->uri);
        $file = sys_get_temp_dir() . "/{$key}.rl";

        $data = file_exists($file) ? (array)(json_decode(file_get_contents($file), true) ?? []) : [];
        $now  = time();

        // Limpa janela expirada
        $data = array_filter($data, fn($t) => $t > $now - $this->window);

        if (count($data) >= $this->maxRequests) {
            header('Retry-After: ' . $this->window);
            throw new \RuntimeException('Muitas requisições. Tente novamente em instantes.', 429);
        }

        $data[] = $now;
        file_put_contents($file, json_encode(array_values($data)));

        return $next();
    }
}
