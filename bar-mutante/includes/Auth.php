<?php
/**
 * includes/Auth.php
 * Sistema de autenticação e controle de acesso
 */
class Auth {

    /** Perfis e suas permissões */
    private static array $perfis = [
        'admin'        => ['label' => 'Administrador', 'cor' => '#ef4444'],
        'caixa_bar'    => ['label' => 'Caixa Bar',     'cor' => '#f59e0b'],
        'caixa_totem'  => ['label' => 'Caixa Totem',   'cor' => '#3b82f6'],
    ];

    /** Formas de pagamento padrão por perfil (se formas_pagamento for NULL no banco) */
    private static array $formasPadrao = [
        'admin'       => ['dinheiro','mercadopago','cortesia'],
        'caixa_bar'   => ['dinheiro','mercadopago'],
        'caixa_totem' => ['mercadopago'],
    ];

    // ── Login / Logout ────────────────────────────────────────────────

    public static function login(string $login, string $senha): array {
        $user = DB::row("SELECT * FROM usuarios WHERE login=? AND ativo=1", [trim($login)]);

        if (!$user || !password_verify($senha, $user['senha'])) {
            return ['ok' => false, 'msg' => 'Login ou senha incorretos.'];
        }

        // Registrar na sessão
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nome']   = $user['nome'];
        $_SESSION['user_login']  = $user['login'];
        $_SESSION['user_perfil'] = $user['perfil'];

        // Formas permitidas
        $formas = $user['formas_pagamento']
            ? json_decode($user['formas_pagamento'], true)
            : (self::$formasPadrao[$user['perfil']] ?? self::$formasPadrao['caixa_bar']);
        $_SESSION['user_formas'] = $formas;

        // Atualizar último acesso
        DB::update('usuarios', ['ultimo_acesso' => date('Y-m-d H:i:s')], 'id=?', [$user['id']]);

        return ['ok' => true, 'user' => $user];
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Verificações ──────────────────────────────────────────────────

    public static function logado(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void {
        if (!self::logado()) {
            // Construir URL de login diretamente dos server vars
            // evita problemas com BASE_URL no LiteSpeed
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            }
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // Calcular raiz do site via filesystem (imune ao SCRIPT_NAME do LiteSpeed)
            $docRoot  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
            $projRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
            $webBase  = ($docRoot && strpos($projRoot, $docRoot) === 0)
                ? rtrim(substr($projRoot, strlen($docRoot)), '/') . '/'
                : '/';
            $loginUrl = $scheme . '://' . $host . $webBase . 'login.php';
            http_response_code(302);
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    public static function isAdmin(): bool {
        return ($_SESSION['user_perfil'] ?? '') === 'admin';
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die('<p style="font-family:sans-serif;color:red;padding:2rem">Acesso negado — apenas administradores.</p>');
        }
    }

    // ── Getters ───────────────────────────────────────────────────────

    public static function id(): int       { return (int) ($_SESSION['user_id']     ?? 0); }
    public static function nome(): string  { return        $_SESSION['user_nome']   ?? ''; }
    public static function loginAtual(): string { return   $_SESSION['user_login']  ?? ''; }
    public static function perfil(): string{ return        $_SESSION['user_perfil'] ?? ''; }
    public static function labelPerfil(): string {
        return self::$perfis[self::perfil()]['label'] ?? self::perfil();
    }

    /** Formas de pagamento permitidas para o usuário logado */
    public static function formasPermitidas(): array {
        return $_SESSION['user_formas'] ?? self::$formasPadrao['caixa_bar'];
    }

    public static function podeUsarForma(string $forma): bool {
        return in_array($forma, self::formasPermitidas());
    }

    public static function podeDinheiro(): bool {
        return self::podeUsarForma('dinheiro');
    }

    // ── Admin: CRUD de usuários ───────────────────────────────────────

    public static function listar(): array {
        return DB::all("SELECT id,nome,login,perfil,formas_pagamento,ativo,ultimo_acesso FROM usuarios ORDER BY perfil,nome");
    }

    public static function salvar(array $data, int $id = 0): array {
        $login = trim($data['login'] ?? '');
        $nome  = trim($data['nome']  ?? '');
        $perfil= $data['perfil'] ?? 'caixa_bar';
        $senha = $data['senha'] ?? '';
        $formas= isset($data['formas_pagamento']) && is_array($data['formas_pagamento'])
                 ? json_encode(array_values($data['formas_pagamento']))
                 : null;
        $ativo = (int) ($data['ativo'] ?? 1);

        if (!$login || !$nome) return ['ok'=>false,'msg'=>'Login e nome são obrigatórios.'];

        // Verificar login duplicado
        $existe = DB::row("SELECT id FROM usuarios WHERE login=? AND id!=?", [$login, $id]);
        if ($existe) return ['ok'=>false,'msg'=>'Este login já está em uso.'];

        if ($id) {
            $upd = ['nome'=>$nome,'login'=>$login,'perfil'=>$perfil,'formas_pagamento'=>$formas,'ativo'=>$ativo];
            if ($senha) $upd['senha'] = password_hash($senha, PASSWORD_BCRYPT);
            DB::update('usuarios', $upd, 'id=?', [$id]);
        } else {
            if (!$senha) return ['ok'=>false,'msg'=>'Senha obrigatória para novo usuário.'];
            DB::insert('usuarios', [
                'nome'             => $nome,
                'login'            => $login,
                'senha'            => password_hash($senha, PASSWORD_BCRYPT),
                'perfil'           => $perfil,
                'formas_pagamento' => $formas,
                'ativo'            => $ativo,
            ]);
        }
        return ['ok'=>true];
    }
}
