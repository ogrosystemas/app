<?php
declare(strict_types=1);

/**
 * Themis Enterprise — SmtpService
 * Envio de e-mail via socket puro (sem PHPMailer, sem Composer).
 * Suporta TLS (STARTTLS), SSL, autenticação LOGIN e PLAIN.
 * Templates HTML embutidos.
 */
final class SmtpService
{
    private array $cfg;
    private CircuitBreaker $cb;

    public function __construct()
    {
        $this->cfg = Bootstrap::cfg('mail', []);
        $this->cb  = new CircuitBreaker();
    }

    // ── Envio principal ───────────────────────────────────────────────────────
    public function send(
        string $to,
        string $subject,
        string $bodyHtml,
        string $bodyText = '',
        array  $cc       = [],
        array  $attachments = []
    ): bool {
        if (empty($this->cfg['host'])) {
            error_log('[SmtpService] SMTP não configurado.');
            return false;
        }

        return $this->cb->call('smtp', function () use ($to, $subject, $bodyHtml, $bodyText, $cc, $attachments) {
            return $this->doSend($to, $subject, $bodyHtml, $bodyText, $cc, $attachments);
        }, [
            'action' => 'send_email',
            'to'     => $to,
            'subject'=> $subject,
        ]);
    }

    // ── Templates prontos ─────────────────────────────────────────────────────
    public function sendAlert(string $to, string $titulo, string $mensagem, string $ctaUrl = '', string $ctaLabel = 'Ver no Themis'): bool
    {
        $html = $this->tplAlert($titulo, $mensagem, $ctaUrl, $ctaLabel);
        return $this->send($to, $titulo, $html, strip_tags($mensagem));
    }

    public function sendWelcome(string $to, string $nome, string $loginUrl): bool
    {
        $html = $this->tplWelcome($nome, $loginUrl);
        return $this->send($to, 'Bem-vindo ao Themis Enterprise', $html);
    }

    public function sendPasswordReset(string $to, string $nome, string $resetUrl): bool
    {
        $html = $this->tplPasswordReset($nome, $resetUrl);
        return $this->send($to, 'Redefinição de senha — Themis', $html);
    }

    public function sendPrazoAlert(string $to, string $processo, int $dias, string $url): bool
    {
        $cor  = $dias <= 2 ? '#ef4444' : ($dias <= 7 ? '#f59e0b' : '#3b82f6');
        $html = $this->tplAlert(
            "⚠️ Prazo em {$dias} dia(s): {$processo}",
            "O processo <strong>{$processo}</strong> tem prazo fatal em <strong>{$dias} dia(s)</strong>. Verifique e tome as providências necessárias.",
            $url,
            'Ver Processo'
        );
        return $this->send($to, "Prazo em {$dias}d — {$processo}", $html);
    }

    // ── Implementação SMTP ────────────────────────────────────────────────────
    private function doSend(string $to, string $subject, string $bodyHtml, string $bodyText, array $cc, array $attachments): bool
    {
        $host       = $this->cfg['host'];
        $port       = (int)($this->cfg['port'] ?? 587);
        $user       = $this->cfg['user']       ?? '';
        $pass       = $this->cfg['pass']       ?? '';
        $fromAddr   = $this->cfg['from_addr']  ?? $user;
        $fromName   = $this->cfg['from_name']  ?? 'Themis Enterprise';
        $encryption = $this->cfg['encryption'] ?? 'tls';
        $timeout    = 15;

        // Conexão
        $prefix = $encryption === 'ssl' ? 'ssl://' : '';
        $sock   = @fsockopen($prefix . $host, $port, $errno, $errstr, $timeout);
        if (!$sock) throw new \RuntimeException("SMTP conexão falhou: {$errstr} ({$errno})");

        stream_set_timeout($sock, $timeout);
        $read = function() use ($sock): string { return fgets($sock, 512) ?: ''; };
        $send = function(string $cmd) use ($sock): void { fputs($sock, $cmd . "\r\n"); };

        $banner = $read(); // 220
        if (!str_starts_with($banner, '220')) throw new \RuntimeException("SMTP banner inesperado: {$banner}");

        $send("EHLO themis.local");
        $caps = '';
        while ($line = $read()) {
            $caps .= $line;
            if ($line[3] === ' ') break;
        }

        // STARTTLS
        if ($encryption === 'tls' && str_contains($caps, 'STARTTLS')) {
            $send("STARTTLS");
            $tlsResp = $read();
            if (!str_starts_with($tlsResp, '220')) throw new \RuntimeException("STARTTLS falhou: {$tlsResp}");
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send("EHLO themis.local");
            $caps = '';
            while ($line = $read()) {
                $caps .= $line;
                if ($line[3] === ' ') break;
            }
        }

        // Auth
        if ($user && $pass) {
            $send("AUTH LOGIN");
            $read(); // 334
            $send(base64_encode($user));
            $read(); // 334
            $send(base64_encode($pass));
            $authResp = $read();
            if (!str_starts_with($authResp, '235')) throw new \RuntimeException("SMTP autenticação falhou: {$authResp}");
        }

        $send("MAIL FROM:<{$fromAddr}>");
        $read();

        $recipients = array_merge([$to], $cc);
        foreach ($recipients as $rcpt) {
            $send("RCPT TO:<{$rcpt}>");
            $read();
        }

        $send("DATA");
        $read(); // 354

        // Monta mensagem MIME multipart
        $boundary = 'ThemisMsg_' . bin2hex(random_bytes(8));
        $msgId    = '<' . uniqid('themis') . '@themis>';
        $date     = date('r');
        $subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $fromEncoded    = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromAddr . '>';

        $headers = implode("\r\n", [
            "From: {$fromEncoded}",
            "To: {$to}",
            $cc ? "Cc: " . implode(', ', $cc) : null,
            "Subject: {$subjectEncoded}",
            "Date: {$date}",
            "Message-ID: {$msgId}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: ThemisEnterprise/2.0",
        ]);
        $headers = preg_replace('/\n/', "\r\n", implode("\n", array_filter(explode("\n", $headers))));

        $text = $bodyText ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
        $body = "--{$boundary}\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($text))
              . "--{$boundary}\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($bodyHtml))
              . "--{$boundary}--\r\n";

        fputs($sock, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
        $resp = $read();
        $send("QUIT");
        fclose($sock);

        if (!str_starts_with($resp, '250')) throw new \RuntimeException("SMTP DATA rejeitado: {$resp}");
        return true;
    }

    // ── Templates HTML ────────────────────────────────────────────────────────
    private function tplBase(string $content): string
    {
        $nome = Bootstrap::cfg('app.name', 'Themis Enterprise');
        $url  = Bootstrap::cfg('app.url',  '#');
        return <<<HTML
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
<style>body{margin:0;padding:0;background:#0f172a;font-family:'Segoe UI',Arial,sans-serif}
.wrap{max-width:600px;margin:0 auto;padding:32px 20px}
.card{background:#1e293b;border-radius:12px;overflow:hidden;border:1px solid #334155}
.header{background:linear-gradient(135deg,#0ea5e9,#0d9488);padding:24px 28px;text-align:center}
.header h1{color:#fff;font-size:20px;margin:0;font-weight:700}
.header small{color:rgba(255,255,255,.8);font-size:12px}
.body{padding:28px;color:#cbd5e1;font-size:14px;line-height:1.6}
.cta{display:inline-block;background:#0ea5e9;color:#fff!important;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;margin:16px 0}
.footer{text-align:center;padding:16px;font-size:12px;color:#475569}
a{color:#38bdf8}
</style></head>
<body><div class="wrap"><div class="card">
<div class="header"><h1>⚖ {$nome}</h1><small>Sistema Enterprise de Gestão Jurídica</small></div>
<div class="body">{$content}</div>
<div class="footer">© {$nome} · <a href="{$url}">{$url}</a></div>
</div></div></body></html>
HTML;
    }

    private function tplAlert(string $titulo, string $mensagem, string $ctaUrl, string $ctaLabel): string
    {
        $cta = $ctaUrl ? "<a href=\"{$ctaUrl}\" class=\"cta\">{$ctaLabel} →</a>" : '';
        return $this->tplBase("<h2 style='color:#f1f5f9;margin-top:0'>{$titulo}</h2><p>{$mensagem}</p>{$cta}");
    }

    private function tplWelcome(string $nome, string $url): string
    {
        $content = "<h2 style='color:#f1f5f9;margin-top:0'>Bem-vindo(a), {$nome}! 👋</h2>
<p>Sua conta no <strong>Themis Enterprise</strong> foi criada com sucesso.</p>
<p>Acesse o sistema usando suas credenciais:</p>
<a href='{$url}' class='cta'>Acessar Themis →</a>
<p style='color:#64748b;font-size:13px'>Se não reconhece este cadastro, ignore este e-mail.</p>";
        return $this->tplBase($content);
    }

    private function tplPasswordReset(string $nome, string $url): string
    {
        $content = "<h2 style='color:#f1f5f9;margin-top:0'>Redefinição de Senha</h2>
<p>Olá, <strong>{$nome}</strong>.</p>
<p>Recebemos uma solicitação para redefinir a senha da sua conta Themis. Clique no botão abaixo para continuar:</p>
<a href='{$url}' class='cta'>Redefinir Senha →</a>
<p style='color:#64748b;font-size:13px'>Este link expira em 1 hora. Se não solicitou, ignore este e-mail.</p>";
        return $this->tplBase($content);
    }
}
