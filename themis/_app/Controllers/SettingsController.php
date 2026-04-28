<?php
declare(strict_types=1);

final class SettingsController
{
    private string $cfgFile;

    public function __construct()
    {
        $this->cfgFile = THEMIS_ROOT . '/_app/config/app.php';
    }

    // GET /api/settings — lê do banco (tenant) + arquivo (configs estáticas)
    public function index(Request $req): Response
    {
        $db     = DB::getInstance();
        $tid    = $db->getTenantId();
        $tenant = $db->first("SELECT * FROM tenants WHERE id = ?", [$tid]);
        $cfg    = require $this->cfgFile;

        if (!$tenant) throw new \RuntimeException('Tenant não encontrado.', 404);

        return Response::success([
            'escritorio' => [
                'nome'     => $tenant['razao_social']          ?? $cfg['app']['name'] ?? '',
                'url'      => $cfg['app']['url']               ?? '',
                'timezone' => $tenant['timezone']              ?? $cfg['app']['timezone'] ?? 'America/Sao_Paulo',
            ],
            'assinafy' => [
                'token'      => $this->mask($tenant['assinafy_key']       ?? ''),
                'account_id' => $tenant['assinafy_account_id']            ?? '',
                'secret'     => $this->mask($cfg['assinafy']['secret']     ?? ''),
                'ativo'      => !empty($tenant['assinafy_key']),
            ],
            'whatsapp' => [
                'provider'     => $tenant['whatsapp_provider'] ?? $cfg['whatsapp']['provider']  ?? 'evolution',
                'base_url'     => $tenant['whatsapp_base_url'] ?? $cfg['whatsapp']['base_url']  ?? '',
                'instance'     => $tenant['whatsapp_instance'] ?? $cfg['whatsapp']['instance']  ?? '',
                'api_key'      => $this->mask($tenant['whatsapp_api_key'] ?? $cfg['whatsapp']['api_key'] ?? ''),
                'token'        => $this->mask($tenant['whatsapp_token']   ?? $cfg['whatsapp']['token']   ?? ''),
                'verify_token' => $cfg['whatsapp']['verify_token'] ?? '',
                'ativo'        => !empty($tenant['whatsapp_api_key']) || !empty($tenant['whatsapp_token']),
            ],
            'datajud' => [
                'api_key'  => $this->mask($tenant['datajud_key'] ?? $cfg['datajud']['api_key'] ?? ''),
                'base_url' => $cfg['datajud']['base_url'] ?? 'https://api-publica.datajud.cnj.jus.br',
                'ativo'    => !empty($tenant['datajud_key']),
            ],
            'mail' => [
                'host'       => $tenant['smtp_host']       ?? $cfg['mail']['host']       ?? '',
                'port'       => (int)($tenant['smtp_port'] ?? $cfg['mail']['port']       ?? 587),
                'encryption' => $tenant['smtp_encryption'] ?? $cfg['mail']['encryption'] ?? 'tls',
                'user'       => $tenant['smtp_user']       ?? $cfg['mail']['user']       ?? '',
                'pass'       => $this->mask($tenant['smtp_pass'] ?? $cfg['mail']['pass'] ?? ''),
                'from_name'  => $tenant['smtp_from_name']  ?? $cfg['mail']['from_name']  ?? '',
                'from_addr'  => $tenant['smtp_from_addr']  ?? $cfg['mail']['from_addr']  ?? '',
                'ativo'      => !empty($tenant['smtp_host']),
            ],
            'despesas' => [
                'valor_km_padrao' => (float)($tenant['valor_km'] ?? $cfg['despesas']['valor_km_padrao'] ?? 0.90),
            ],
            'db' => [
                'host' => $cfg['db']['host'] ?? 'localhost',
                'name' => $cfg['db']['name'] ?? '',
                'user' => $cfg['db']['user'] ?? '',
            ],
        ]);
    }

    // POST /api/settings — salva TUDO no banco do tenant
    public function save(Request $req): Response
    {
        $db  = DB::getInstance();
        $tid = $db->getTenantId();
        $b   = $req->body;
        $upd = []; // colunas a atualizar no tenant

        // ── Escritório ───────────────────────────────────────────────
        if (isset($b['escritorio'])) {
            $e = $b['escritorio'];
            if (!empty($e['nome']))     $upd['razao_social'] = trim($e['nome']);
            if (!empty($e['timezone'])) $upd['timezone']     = trim($e['timezone']);
        }

        // ── Assinafy ────────────────────────────────────────────────
        if (isset($b['assinafy'])) {
            $a = $b['assinafy'];
            if (!empty($a['token'])     && !$this->isMasked($a['token']))  $upd['assinafy_key']        = trim($a['token']);
            if (isset($a['account_id']))                                          $upd['assinafy_account_id'] = trim($a['account_id']);
        }

        // ── WhatsApp ─────────────────────────────────────────────────
        if (isset($b['whatsapp'])) {
            $w = $b['whatsapp'];
            if (isset($w['provider']))                                    $upd['whatsapp_provider'] = trim($w['provider']);
            if (!empty($w['base_url']))                                   $upd['whatsapp_base_url'] = rtrim(trim($w['base_url']), '/');
            if (!empty($w['instance']))                                   $upd['whatsapp_instance'] = trim($w['instance']);
            if (!empty($w['api_key']) && !$this->isMasked($w['api_key'])) $upd['whatsapp_api_key']  = trim($w['api_key']);
            if (!empty($w['token'])   && !$this->isMasked($w['token']))   $upd['whatsapp_token']    = trim($w['token']);
        }

        // ── DataJud ──────────────────────────────────────────────────
        if (isset($b['datajud'])) {
            $d = $b['datajud'];
            if (!empty($d['api_key']) && !$this->isMasked($d['api_key'])) $upd['datajud_key'] = trim($d['api_key']);
        }

        // ── SMTP ─────────────────────────────────────────────────────
        if (isset($b['mail'])) {
            $m = $b['mail'];
            if (isset($m['host']))        $upd['smtp_host']       = trim($m['host']);
            if (isset($m['port']))        $upd['smtp_port']       = (int)$m['port'];
            if (isset($m['encryption']))  $upd['smtp_encryption'] = trim($m['encryption']);
            if (isset($m['user']))        $upd['smtp_user']       = trim($m['user']);
            if (!empty($m['pass']) && !$this->isMasked($m['pass'])) $upd['smtp_pass'] = trim($m['pass']);
            if (isset($m['from_name']))   $upd['smtp_from_name']  = trim($m['from_name']);
            if (isset($m['from_addr']))   $upd['smtp_from_addr']  = trim($m['from_addr']);
        }

        // ── Despesas ─────────────────────────────────────────────────
        if (isset($b['despesas']['valor_km_padrao'])) {
            $upd['valor_km'] = (float)$b['despesas']['valor_km_padrao'];
        }

        // Salvar no banco
        if (!empty($upd)) {
            $db->update('tenants', $upd, ['id' => $tid]);
        }

        // Audit
        AuditLogger::log('update', 'configuracoes', null, json_encode(array_keys($upd)));

        return Response::success(null, 'Configurações salvas com sucesso!');
    }

    // POST /api/settings/test-mail
    public function testMail(Request $req): Response
    {
        $db     = DB::getInstance();
        $tenant = $db->first("SELECT * FROM tenants WHERE id = ?", [$db->getTenantId()]);
        $cfg    = require $this->cfgFile;

        $mail = [
            'host'       => $tenant['smtp_host']      ?? $cfg['mail']['host']      ?? '',
            'port'       => (int)($tenant['smtp_port'] ?? $cfg['mail']['port']     ?? 587),
            'encryption' => $tenant['smtp_encryption'] ?? $cfg['mail']['encryption'] ?? 'tls',
            'user'       => $tenant['smtp_user']       ?? $cfg['mail']['user']      ?? '',
            'pass'       => $tenant['smtp_pass']       ?? $cfg['mail']['pass']      ?? '',
            'from_name'  => $tenant['smtp_from_name']  ?? $cfg['mail']['from_name'] ?? 'Themis',
            'from_addr'  => $tenant['smtp_from_addr']  ?? $cfg['mail']['from_addr'] ?? '',
        ];

        if (empty($mail['host'])) throw new \RuntimeException('Configure o servidor SMTP primeiro.', 400);

        $to = $req->str('to') ?: ($mail['user'] ?? '');
        if (!$to) throw new \RuntimeException('Informe o e-mail de destino.', 400);

        $ok = $this->sendMail($mail, $to, 'Teste Themis — ' . date('d/m/Y H:i'),
            "Este é um e-mail de teste enviado pelo Themis Enterprise.\n\nSe recebeu, o SMTP está configurado corretamente.");

        if (!$ok) throw new \RuntimeException('Falha ao enviar. Verifique as configurações SMTP.', 500);
        return Response::success(null, "E-mail enviado para {$to}.");
    }

    // POST /api/settings/test-whatsapp
    public function testWhatsapp(Request $req): Response
    {
        $db     = DB::getInstance();
        $tenant = $db->first("SELECT * FROM tenants WHERE id = ?", [$db->getTenantId()]);
        $cfg    = require $this->cfgFile;

        $w = [
            'provider'  => $tenant['whatsapp_provider'] ?? $cfg['whatsapp']['provider']  ?? 'evolution',
            'base_url'  => $tenant['whatsapp_base_url'] ?? $cfg['whatsapp']['base_url']  ?? '',
            'instance'  => $tenant['whatsapp_instance'] ?? $cfg['whatsapp']['instance']  ?? '',
            'api_key'   => $tenant['whatsapp_api_key']  ?? $cfg['whatsapp']['api_key']   ?? '',
            'token'     => $tenant['whatsapp_token']    ?? $cfg['whatsapp']['token']     ?? '',
            'phone_id'  => $cfg['whatsapp']['phone_id'] ?? '',
        ];

        $tel = $req->str('telefone');
        if (!$tel) throw new \RuntimeException('Informe o número de telefone.', 400);

        if ($w['provider'] === 'evolution') {
            if (empty($w['base_url']) || empty($w['instance']) || empty($w['api_key']))
                throw new \RuntimeException('Configure a Evolution API primeiro.', 400);
            $ok = $this->sendEvolution($w, $tel, '✅ Teste Themis Enterprise — WhatsApp OK!');
        } else {
            if (empty($w['token']) || empty($w['phone_id']))
                throw new \RuntimeException('Configure a API Meta WhatsApp primeiro.', 400);
            $ok = $this->sendMetaWhatsapp($w, $tel, '✅ Teste Themis Enterprise — WhatsApp OK!');
        }

        if (!$ok) throw new \RuntimeException('Falha ao enviar. Verifique as configurações.', 500);
        return Response::success(null, "Mensagem enviada para {$tel}.");
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function sendMail(array $cfg, string $to, string $subject, string $body): bool
    {
        $host = $cfg['host']; $port = (int)($cfg['port'] ?? 587);
        $user = $cfg['user'] ?? ''; $pass = $cfg['pass'] ?? '';
        $fromName = $cfg['from_name'] ?? 'Themis'; $fromAddr = $cfg['from_addr'] ?? $user;
        $enc  = $cfg['encryption'] ?? 'tls';
        $prefix = $enc === 'ssl' ? 'ssl://' : '';
        $sock = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
        if (!$sock) return false;
        $rd = fn() => fgets($sock, 512);
        $sn = fn($c) => fputs($sock, $c . "\r\n");
        $rd();
        $sn("EHLO themis");
        while ($l = $rd()) { if ($l[3] === ' ') break; }
        if ($enc === 'tls') {
            $sn("STARTTLS"); $rd();
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $sn("EHLO themis");
            while ($l = $rd()) { if ($l[3] === ' ') break; }
        }
        $sn("AUTH LOGIN"); $rd(); $sn(base64_encode($user)); $rd(); $sn(base64_encode($pass));
        $r = $rd(); if (!str_starts_with($r, '235')) { fclose($sock); return false; }
        $sn("MAIL FROM:<{$fromAddr}>"); $rd();
        $sn("RCPT TO:<{$to}>"); $rd();
        $sn("DATA"); $rd();
        $h = "From: {$fromName} <{$fromAddr}>\r\nTo: {$to}\r\nSubject: {$subject}\r\nDate: " . date('r') . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=utf-8\r\n";
        $sn($h . "\r\n" . $body . "\r\n.");
        $r2 = $rd(); $sn("QUIT"); fclose($sock);
        return str_starts_with($r2, '250');
    }

    private function sendEvolution(array $cfg, string $tel, string $msg): bool
    {
        $url  = rtrim($cfg['base_url'], '/') . '/message/sendText/' . $cfg['instance'];
        $body = json_encode(['number' => preg_replace('/\D/', '', $tel), 'text' => $msg]);
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','apikey: '.$cfg['api_key']],
            CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private function sendMetaWhatsapp(array $cfg, string $tel, string $msg): bool
    {
        $url  = "https://graph.facebook.com/v19.0/{$cfg['phone_id']}/messages";
        $body = json_encode(['messaging_product'=>'whatsapp','to'=>preg_replace('/\D/','',$tel),'type'=>'text','text'=>['body'=>$msg]]);
        $ch   = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$cfg['token']],
            CURLOPT_TIMEOUT=>15]);
        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    private function mask(string $val): string
    {
        if (strlen($val) <= 4) return $val ? '••••' : '';
        return str_repeat('•', min(strlen($val) - 4, 20)) . substr($val, -4);
    }

    private function isMasked(string $val): bool
    {
        return str_contains($val, '•');
    }
}
