<?php
declare(strict_types=1);

/**
 * Themis Enterprise — LicenseManager
 * AES-256-GCM encryption · Trial 15 dias · Validação por request
 *
 * Formato da licença (plaintext antes de cifrar):
 *   JSON: {"licensee":"Nome","email":"x@y.com","issued_at":"2025-01-01",
 *          "expires_at":"2026-01-01","max_users":10,"features":["all"],
 *          "domain":"themis.ogrosystemas.com.br"}
 *
 * Formato do token (base64url):
 *   iv(12) + tag(16) + ciphertext  → base64url
 */
final class LicenseManager
{
    // ── Chave mestra de 32 bytes (SHA-256 do secret da app + salt fixo) ──────
    private const SALT = 'ThemisLicense2025EnterpriseSalt!!';

    // Caminho do arquivo de estado da licença
    private string $stateFile;

    // Cache em memória (evita re-validar no mesmo request)
    private static ?array $cached = null;
    private static bool $valid = false;

    public function __construct()
    {
        $this->stateFile = THEMIS_ROOT . '/_storage/license.dat';
    }

    // ── Ponto de entrada chamado pelo Bootstrap ───────────────────────────────
    public static function check(): void
    {
        if (self::$cached !== null) return;

        $inst = new self();
        $result = $inst->validate();
        self::$cached = $result;
        self::$valid  = $result['valid'];

        if (!self::$valid) {
            // Armazena motivo para a tela de licença exibir
            $_SESSION['license_error'] = $result['reason'] ?? 'Licença inválida.';
        }
    }

    public static function isValid(): bool
    {
        return self::$valid;
    }

    public static function info(): array
    {
        return self::$cached ?? [];
    }

    // ── Validação principal ───────────────────────────────────────────────────
    public function validate(): array
    {
        $state = $this->loadState();

        // Modo trial
        if (empty($state['token'])) {
            return $this->handleTrial($state);
        }

        // Valida token
        return $this->validateToken($state['token']);
    }

    private function handleTrial(array $state): array
    {
        if (empty($state['trial_started'])) {
            // Primeira execução — inicia trial
            $state['trial_started'] = date('Y-m-d');
            $this->saveState($state);
        }

        $started  = new \DateTime($state['trial_started']);
        $now      = new \DateTime();
        $elapsed  = (int) $now->diff($started)->days;
        $remaining = max(0, 15 - $elapsed);

        if ($remaining <= 0) {
            return [
                'valid'  => false,
                'mode'   => 'trial_expired',
                'reason' => 'Período trial de 15 dias expirado. Insira uma licença válida.',
            ];
        }

        return [
            'valid'       => true,
            'mode'        => 'trial',
            'days_left'   => $remaining,
            'licensee'    => 'Trial',
            'expires_at'  => (clone $started)->modify('+15 days')->format('Y-m-d'),
            'max_users'   => 3,
            'features'    => ['all'],
        ];
    }

    private function validateToken(string $token): array
    {
        $key = $this->deriveKey();
        $payload = $this->decrypt($token, $key);

        if ($payload === null) {
            return [
                'valid'  => false,
                'mode'   => 'invalid',
                'reason' => 'Token de licença corrompido ou adulterado.',
            ];
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return ['valid' => false, 'mode' => 'invalid', 'reason' => 'Formato de licença inválido.'];
        }

        // Verifica expiração
        if (!empty($data['expires_at']) && $data['expires_at'] !== 'lifetime') {
            $exp = new \DateTime($data['expires_at']);
            if ($exp < new \DateTime()) {
                return [
                    'valid'  => false,
                    'mode'   => 'expired',
                    'reason' => 'Licença expirada em ' . (new \DateTime($data['expires_at']))->format('d/m/Y') . '.',
                ];
            }
        }

        // Verifica domínio (opcional — se definido na licença)
        if (!empty($data['domain'])) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $host = preg_replace('/^www\./', '', $host);
            $licenseDomain = preg_replace('/^www\./', '', $data['domain']);
            if ($host !== $licenseDomain && $licenseDomain !== '*') {
                return [
                    'valid'  => false,
                    'mode'   => 'domain_mismatch',
                    'reason' => "Licença emitida para domínio '{$data['domain']}', não para '{$host}'.",
                ];
            }
        }

        $exp = $data['expires_at'] ?? 'lifetime';
        $daysLeft = $exp === 'lifetime' ? 99999 : (int) (new \DateTime())->diff(new \DateTime($exp))->days;

        return [
            'valid'       => true,
            'mode'        => 'licensed',
            'licensee'    => $data['licensee']   ?? 'Desconhecido',
            'email'       => $data['email']       ?? '',
            'issued_at'   => $data['issued_at']   ?? '',
            'expires_at'  => $exp,
            'max_users'   => (int)($data['max_users'] ?? 999),
            'features'    => $data['features']    ?? ['all'],
            'domain'      => $data['domain']      ?? '*',
            'days_left'   => $daysLeft,
        ];
    }

    // ── Instalação de nova licença (chamado pelo SettingsController) ──────────
    public function install(string $token): array
    {
        $token = trim($token);
        $result = $this->validateToken($token);

        if (!$result['valid']) {
            return $result;
        }

        $state = $this->loadState();
        $state['token'] = $token;
        $this->saveState($state);

        self::$cached = $result;
        self::$valid  = true;

        return $result;
    }

    public function revoke(): void
    {
        $state = $this->loadState();
        unset($state['token']);
        $this->saveState($state);
        self::$cached = null;
        self::$valid  = false;
    }

    // ── Criptografia AES-256-GCM ──────────────────────────────────────────────
    private function deriveKey(): string
    {
        $secret = Bootstrap::cfg('app.secret', 'themis_default');
        return hash('sha256', $secret . self::SALT, true); // 32 bytes raw
    }

    /**
     * Gera token cifrado:
     *   iv(12 bytes) + tag(16 bytes) + ciphertext → base64url
     */
    public function encrypt(string $plaintext): string
    {
        $key = $this->deriveKey();
        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        return rtrim(strtr(base64_encode($iv . $tag . $ct), '+/', '-_'), '=');
    }

    public function decrypt(string $token, string $key): ?string
    {
        $raw = base64_decode(strtr($token, '-_', '+/') . str_repeat('=', (4 - strlen($token) % 4) % 4));
        if ($raw === false || strlen($raw) < 29) return null;

        $iv  = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct  = substr($raw, 28);

        $plain = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }

    // ── Estado persistido em _storage/license.dat ────────────────────────────
    private function loadState(): array
    {
        if (!file_exists($this->stateFile)) return [];
        $raw = file_get_contents($this->stateFile);
        if (!$raw) return [];
        $decoded = json_decode(base64_decode($raw), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveState(array $state): void
    {
        $dir = dirname($this->stateFile);
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        file_put_contents($this->stateFile, base64_encode(json_encode($state)), LOCK_EX);
    }
}
