<?php
declare(strict_types=1);
final class Bootstrap
{
    private static bool  $booted = false;
    private static array $cfg    = [];

    public static function boot(): void
    {
        if (self::$booted) return;

        // Config fica em _app/config/app.php
        $cfgFile = THEMIS_ROOT . '/_app/config/app.php';
        if (!file_exists($cfgFile)) {
            http_response_code(503);
            die('Themis não instalado. Acesse <a href="/install.php">/install.php</a> para configurar.');
        }
        self::$cfg = require $cfgFile;

        // Publica como $_ENV para compatibilidade com Core/Services
        $envMap = [
            'APP_NAME'              => 'app.name',
            'APP_URL'               => 'app.url',
            'APP_SECRET'            => 'app.secret',
            'APP_DEBUG'             => 'app.debug',
            'DB_HOST'               => 'db.host',
            'DB_PORT'               => 'db.port',
            'DB_NAME'               => 'db.name',
            'DB_USER'               => 'db.user',
            'DB_PASS'               => 'db.pass',
            'STORAGE_PATH'          => 'storage.path',
            'MAX_UPLOAD_MB'         => 'storage.max_mb',
            'TRASH_DAYS'            => 'storage.trash_days',
            'SESSION_TTL'           => 'session.ttl',
            'ASSINAFY_TOKEN'        => 'assinafy.token',
            'ASSINAFY_SECRET'       => 'assinafy.secret',
            'WHATSAPP_PHONE_ID'     => 'whatsapp.phone_id',
            'WHATSAPP_TOKEN'        => 'whatsapp.token',
            'WHATSAPP_VERIFY_TOKEN' => 'whatsapp.verify_token',
            'DATAJUD_API_KEY'       => 'datajud.api_key',
            'DATAJUD_BASE_URL'      => 'datajud.base_url',
        ];
        foreach ($envMap as $envKey => $cfgKey) {
            $val = self::cfg($cfgKey, '');
            if (is_bool($val)) $val = $val ? 'true' : 'false';
            $_ENV[$envKey] = (string) $val;
        }
        $_ENV['CORS_ORIGINS'] = implode(',', self::cfg('cors.origins', []));

        date_default_timezone_set(self::cfg('app.timezone', 'America/Sao_Paulo'));
        mb_internal_encoding('UTF-8');
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('X-XSS-Protection: 1; mode=block');
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com fonts.gstatic.com; font-src 'self' fonts.gstatic.com data:; img-src 'self' data: blob:;");
        }

        // CORS
        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = self::cfg('cors.origins', []);
        if ($origin && in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204); exit;
        }

        // Inicializa AuditLogger
        AuditLogger::init();

        // Verifica licença (não bloqueia — apenas registra estado)
        LicenseManager::check();

        self::$booted = true;
    }

    /** Acessa config por dot-notation: Bootstrap::cfg('db.host') */
    public static function cfg(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $node  = self::$cfg;
        foreach ($parts as $p) {
            if (!is_array($node) || !array_key_exists($p, $node)) return $default;
            $node = $node[$p];
        }
        return $node;
    }

    public static function handleException(\Throwable $e): never
    {
        $code = (int) $e->getCode();
        $http = in_array($code, [400, 401, 403, 404, 409, 422, 429], true) ? $code : 500;
        error_log(sprintf('[Themis] %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        http_response_code($http);
        header('Content-Type: application/json; charset=utf-8');
        $out = ['error' => true, 'message' => $http >= 500 ? 'Erro interno do servidor.' : $e->getMessage(), 'code' => $http];
        if (self::cfg('app.debug')) {
            $out['debug'] = ['class' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()];
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function handleError(int $no, string $str, string $file, int $line): bool
    {
        throw new \ErrorException($str, $no, $no, $file, $line);
    }
}
