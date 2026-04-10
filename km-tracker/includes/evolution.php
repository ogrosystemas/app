<?php
/**
 * includes/evolution.php
 * Helper para Evolution API (WhatsApp)
 */

class Evolution {

    private string $url;
    private string $key;
    private string $instance;

    public function __construct() {
        $db  = db();
        $cfg = $db->query("SELECT * FROM evolution_config LIMIT 1")->fetch();
        if (!$cfg) throw new RuntimeException('Evolution API não configurada.');
        $this->url      = rtrim($cfg['evolution_url'], '/');
        $this->key      = $cfg['evolution_key'];
        $this->instance = $cfg['instance_name'];
    }

    // ── Status da instância ───────────────────────────────
    public function status(): array {
        return $this->request('GET', "/instance/connectionState/{$this->instance}");
    }

    // ── QR Code para conectar ─────────────────────────────
    public function qrCode(): array {
        return $this->request('GET', "/instance/connect/{$this->instance}");
    }

    // ── Enviar mensagem de texto ──────────────────────────
    public function enviarTexto(string $numero, string $mensagem): array {
        $numero = self::limparNumero($numero);
        return $this->request('POST', "/message/sendText/{$this->instance}", [
            'number' => $numero,
            'text'   => $mensagem,
            'delay'  => 500,
        ]);
    }

    // ── Disparar notificação para lista de usuários ───────
    public function dispararNotificacao(int $notifId): array {
        $db = db();

        $stmt = $db->prepare("SELECT * FROM notificacoes WHERE id = ?");
        $stmt->execute([$notifId]);
        $notif = $stmt->fetch();

        if (!$notif || $notif['status'] !== 'pendente') {
            return ['success' => false, 'message' => 'Notificação não encontrada ou já processada.'];
        }

        $usuarios = $this->buscarDestinatarios($notif);

        if (empty($usuarios)) {
            $db->prepare("UPDATE notificacoes SET status='erro', erro_detalhe=? WHERE id=?")
               ->execute(['Nenhum destinatário com WhatsApp cadastrado.', $notifId]);
            return ['success' => false, 'message' => 'Nenhum destinatário com WhatsApp cadastrado.'];
        }

        $db->prepare("UPDATE notificacoes SET status='enviando', total_destinatarios=?, enviado_em=NOW() WHERE id=?")
           ->execute([count($usuarios), $notifId]);

        $enviados = 0;
        $erros    = 0;

        foreach ($usuarios as $u) {
            if (empty($u['whatsapp'])) continue;
            try {
                $res = $this->enviarTexto($u['whatsapp'], $notif['mensagem']);
                $ok  = isset($res['key']['id']) || (isset($res['status']) && $res['status'] !== 'error');
                $db->prepare("INSERT INTO notificacoes_log (notificacao_id, user_id, whatsapp, status, erro_msg) VALUES (?,?,?,?,?)")
                   ->execute([$notifId, $u['id'] ?? null, $u['whatsapp'], $ok ? 'enviado' : 'erro', $ok ? null : json_encode($res)]);
                $ok ? $enviados++ : $erros++;
                usleep(600000); // 600ms entre mensagens
            } catch (Throwable $e) {
                $erros++;
                $db->prepare("INSERT INTO notificacoes_log (notificacao_id, user_id, whatsapp, status, erro_msg) VALUES (?,?,?,?,?)")
                   ->execute([$notifId, $u['id'] ?? null, $u['whatsapp'], 'erro', $e->getMessage()]);
            }
        }

        $status = $erros === 0 ? 'enviado' : ($enviados > 0 ? 'enviado' : 'erro');
        $db->prepare("UPDATE notificacoes SET status=?, total_enviados=?, total_erros=? WHERE id=?")
           ->execute([$status, $enviados, $erros, $notifId]);

        return ['success' => true, 'enviados' => $enviados, 'erros' => $erros];
    }

    // ── Buscar destinatários ──────────────────────────────
    private function buscarDestinatarios(array $notif): array {
        $db = db();
        switch ($notif['destinatario']) {
            case 'todos':
                return $db->query("SELECT id, name, whatsapp FROM users WHERE active=1 AND whatsapp IS NOT NULL AND whatsapp != ''")->fetchAll();
            case 'admins':
                return $db->query("SELECT id, name, whatsapp FROM users WHERE active=1 AND role='admin' AND whatsapp IS NOT NULL AND whatsapp != ''")->fetchAll();
            case 'usuarios':
                return $db->query("SELECT id, name, whatsapp FROM users WHERE active=1 AND role='user' AND whatsapp IS NOT NULL AND whatsapp != ''")->fetchAll();
            case 'individual':
                $stmt = $db->prepare("SELECT id, name, whatsapp FROM users WHERE id=? AND whatsapp IS NOT NULL AND whatsapp != ''");
                $stmt->execute([$notif['user_id']]);
                return $stmt->fetchAll();
            default:
                return [];
        }
    }

    // ── Limpar número ─────────────────────────────────────
    public static function limparNumero(string $numero): string {
        $n = preg_replace('/\D/', '', $numero);
        if (strlen($n) <= 11) $n = '55' . $n;
        return $n;
    }

    // ── Request HTTP ──────────────────────────────────────
    private function request(string $method, string $path, array $body = []): array {
        $ch = curl_init($this->url . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'apikey: ' . $this->key,
            ],
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);
        if ($method === 'POST' && !empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($res, true) ?? [];
        $data['_http_code'] = $code;
        return $data;
    }

    // ── Helper: criar notificação e disparar ──────────────
    private static function criarEDisparar(string $tipo, string $titulo, string $msg, string $dest, ?int $userId, ?int $eventId): void {
        $db = db();
        $adminId = (int)($db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn() ?: 1);
        $db->prepare("INSERT INTO notificacoes (tipo, titulo, mensagem, destinatario, user_id, event_id, status, criado_por) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$tipo, $titulo, $msg, $dest, $userId, $eventId, 'pendente', $adminId]);
        $notifId = (int)$db->lastInsertId();
        if ($notifId) {
            $evo = new self();
            $evo->dispararNotificacao($notifId);
        }
    }

    // ── Notificação: novo evento ──────────────────────────
    public static function notificarNovoEvento(array $evento): void {
        try {
            $db  = db();
            $cfg = $db->query("SELECT notif_novo_evento FROM evolution_config LIMIT 1")->fetch();
            if (!$cfg || !$cfg['notif_novo_evento']) return;

            $data = date('d/m/Y', strtotime($evento['event_date']));
            $msg  = "🏍️ *Novo Evento: {$evento['title']}*\n\n"
                  . "📅 Data: {$data}\n"
                  . "📍 Local: " . ($evento['location'] ?? 'A definir') . "\n"
                  . "🛣️ KM: " . ($evento['km_awarded'] ?? '0') . " km\n\n"
                  . "Acesse o sistema para confirmar sua presença! 👊";

            self::criarEDisparar('novo_evento', "Novo Evento: {$evento['title']}", $msg, 'todos', null, $evento['id'] ?? null);
        } catch (Throwable $e) {
            error_log('Evolution::notificarNovoEvento: ' . $e->getMessage());
        }
    }

    // ── Notificação: presença confirmada ──────────────────
    public static function notificarPresencaConfirmada(int $userId, array $evento): void {
        try {
            $db  = db();
            $cfg = $db->query("SELECT notif_presenca_confirmada FROM evolution_config LIMIT 1")->fetch();
            if (!$cfg || !$cfg['notif_presenca_confirmada']) return;

            $stmt = $db->prepare("SELECT name, whatsapp FROM users WHERE id=?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user || empty($user['whatsapp'])) return;

            $data = date('d/m/Y', strtotime($evento['event_date']));
            $msg  = "✅ *Presença Confirmada!*\n\n"
                  . "Olá, {$user['name']}!\n"
                  . "Sua presença no evento *{$evento['title']}* foi confirmada.\n\n"
                  . "📅 {$data}\n"
                  . "📍 " . ($evento['location'] ?? '') . "\n\n"
                  . "Bora rodar! 🏍️";

            self::criarEDisparar('presenca_confirmada', "Presença confirmada: {$user['name']}", $msg, 'individual', $userId, $evento['id'] ?? null);
        } catch (Throwable $e) {
            error_log('Evolution::notificarPresencaConfirmada: ' . $e->getMessage());
        }
    }
}
