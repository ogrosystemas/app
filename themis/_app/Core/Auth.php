<?php
declare(strict_types=1);
final class Auth
{
    private string $secret;
    private int $ttl;

    public function __construct(private DB $db)
    {
        $this->secret = $_ENV['APP_SECRET'] ?? 'changeme';
        $this->ttl    = (int) ($_ENV['SESSION_TTL'] ?? 28800);
    }

    public function login(string $email, string $password, ?string $totp = null): array
    {
        $user = $this->db->first(
            "SELECT u.*, t.slug AS tenant_slug, t.razao_social AS tenant_nome
             FROM users u JOIN tenants t ON t.id = u.tenant_id
             WHERE u.email = ? AND u.ativo = 1 AND u.deleted_at IS NULL",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->audit(null, null, 'login_falhou', 'auth', null, ['email' => $email]);
            throw new \RuntimeException('Credenciais inválidas.', 401);
        }

        if ((int) $user['dois_fatores'] === 1) {
            if (!$this->verifyTOTP((string) $user['totp_secret'], $totp)) {
                throw new \RuntimeException('Código 2FA inválido.', 401);
            }
        }

        $token = $this->generateJWT($user);
        $this->createSession($user, $token);
        $this->db->update('users', ['ultimo_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
        $this->audit((int) $user['tenant_id'], (int) $user['id'], 'login', 'auth', null);

        return ['token' => $token, 'user' => $this->sanitize($user), 'expires_in' => $this->ttl];
    }

    public function validateToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new \RuntimeException('Token malformado.', 401);
        [$header, $payload, $sig] = $parts;
        if (!hash_equals($this->sign("{$header}.{$payload}"), $sig)) {
            throw new \RuntimeException('Assinatura inválida.', 401);
        }
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (!$data || ($data['exp'] ?? 0) < time()) {
            throw new \RuntimeException('Token expirado.', 401);
        }
        return $data;
    }

    /** Middleware guard — retorna usuário autenticado */
    public function guard(string $perfis = ''): array
    {
        $bearer = $this->extractBearer();
        $payload = $this->validateToken($bearer);
        $user = $this->db->first(
            "SELECT * FROM users WHERE id = ? AND tenant_id = ? AND ativo = 1 AND deleted_at IS NULL",
            [$payload['sub'], $payload['tid']]
        );
        if (!$user) throw new \RuntimeException('Usuário não encontrado ou inativo.', 403);
        if ($perfis) {
            $allowed = array_map('trim', explode(',', $perfis));
            if (!in_array($user['perfil'], $allowed, true)) {
                throw new \RuntimeException('Permissão insuficiente.', 403);
            }
        }
        $this->db->setTenant((int) $user['tenant_id']);
        return $user;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function logout(string $token): void
    {
        $this->db->run("DELETE FROM sessions WHERE payload = ?", [$token]);
    }


    public function gerarPortalJwt(array $payload): string
    {
        $h = $this->b64e(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + 86400 * 7; // 7 dias
        $payload['iss'] = 'themis_portal';
        $p = $this->b64e(json_encode($payload));
        return "{$h}.{$p}." . $this->sign("{$h}.{$p}");
    }

    private function generateJWT(array $user): string
    {
        $h = $this->b64e(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $p = $this->b64e(json_encode([
            'sub'    => $user['id'],
            'tid'    => $user['tenant_id'],
            'owner'  => $user['owner_id'],
            'perfil' => $user['perfil'],
            'iat'    => time(),
            'exp'    => time() + $this->ttl,
        ]));
        return "{$h}.{$p}." . $this->sign("{$h}.{$p}");
    }

    private function sign(string $data): string
    {
        return $this->b64e(hash_hmac('sha256', $data, $this->secret, true));
    }

    private function b64e(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function createSession(array $user, string $token): void
    {
        // Limpa sessões expiradas do usuário
        $this->db->run("DELETE FROM sessions WHERE user_id = ? AND expires_at < NOW()", [$user['id']]);
        $this->db->insert('sessions', [
            'id'         => bin2hex(random_bytes(32)),
            'user_id'    => $user['id'],
            'tenant_id'  => $user['tenant_id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
            'payload'    => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + $this->ttl),
        ]);
    }

    private function verifyTOTP(string $secret, ?string $code): bool
    {
        if (!$code || strlen($code) !== 6) return false;
        $key  = $this->base32Decode($secret);
        $time = (int) floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            $msg    = pack('N*', 0) . pack('N*', $time + $i);
            $hash   = hash_hmac('sha1', $msg, $key, true);
            $offset = ord($hash[19]) & 0x0f;
            $otp    = (
                ((ord($hash[$offset])   & 0x7f) << 24) |
                ((ord($hash[$offset+1]) & 0xff) << 16) |
                ((ord($hash[$offset+2]) & 0xff) << 8)  |
                 (ord($hash[$offset+3]) & 0xff)
            ) % 1_000_000;
            if (str_pad((string) $otp, 6, '0', STR_PAD_LEFT) === $code) return true;
        }
        return false;
    }

    private function base32Decode(string $input): string
    {
        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $input = strtoupper($input);
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;
        foreach (str_split($input) as $char) {
            if (!isset($map[$char])) continue;
            $buffer = ($buffer << 5) | $map[$char];
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $output   .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }
        return $output;
    }

    private function extractBearer(): string
    {
        // 1. Header Authorization: Bearer <token>
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($h, 'Bearer ')) return substr($h, 7);
        // 2. Query string ?token= (para downloads via <a href>)
        if (!empty($_GET['token'])) return $_GET['token'];
        // 3. Cookie
        if (!empty($_COOKIE['themis_token'])) return $_COOKIE['themis_token'];
        throw new \RuntimeException('Token não fornecido.', 401);
    }

    private function sanitize(array $user): array
    {
        unset($user['password_hash'], $user['totp_secret']);
        return $user;
    }

    private function audit(?int $tid, ?int $uid, string $acao, string $modulo, ?int $eid, array $extra = []): void
    {
        try {
            $this->db->run(
                "INSERT INTO audit_logs (tenant_id,user_id,acao,modulo,entidade_id,dados_depois,ip_address,url) VALUES (?,?,?,?,?,?,?,?)",
                [$tid, $uid, $acao, $modulo, $eid, json_encode($extra) ?: null, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['REQUEST_URI'] ?? null]
            );
        } catch (\Throwable) {}
    }
}
