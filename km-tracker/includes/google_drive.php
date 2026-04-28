<?php
/**
 * includes/google_drive.php
 * Integração com Google Drive via OAuth2
 */

class GoogleDrive {

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $rootFolderId;
    private string $tokenFile;

    public function __construct() {
        $this->clientId     = setting('google_drive_client_id',     '258020236528-j8afcao6p6o497bmgb89uvfsl43qv3hb.apps.googleusercontent.com');
        $this->clientSecret = setting('google_drive_client_secret', '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');
        $this->rootFolderId = setting('google_drive_folder_id',     '1uTI4Een7fhN3nE9ZIjyF3ga6PYsRSxBJ');
        $this->redirectUri  = BASE_URL . '/admin/google_auth.php';
        $this->tokenFile    = __DIR__ . '/google_token.json';
    }

    // ── URL de autorização ────────────────────────────────────────────────────
    public function getAuthUrl(): string {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/drive',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    // ── Trocar code por tokens ────────────────────────────────────────────────
    public function exchangeCode(string $code): array {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'grant_type'    => 'authorization_code',
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT    => 15,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!empty($data['access_token'])) {
            $data['expires_at'] = time() + ($data['expires_in'] ?? 3600);
            file_put_contents($this->tokenFile, json_encode($data));
        }
        return $data;
    }

    // ── Obter access token (refresh se necessário) ────────────────────────────
    public function getAccessToken(): string {
        if (!file_exists($this->tokenFile)) {
            throw new RuntimeException('Google Drive não autorizado. Acesse Admin → Google Drive para autorizar.');
        }
        $token = json_decode(file_get_contents($this->tokenFile), true);
        if (time() >= ($token['expires_at'] ?? 0) - 60) {
            if (empty($token['refresh_token'])) {
                throw new RuntimeException('Token expirado. Reautorize em Admin → Google Drive.');
            }
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'refresh_token' => $token['refresh_token'],
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type'    => 'refresh_token',
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_TIMEOUT    => 15,
            ]);
            $res  = curl_exec($ch);
            curl_close($ch);
            $new = json_decode($res, true);
            if (!empty($new['access_token'])) {
                $token['access_token'] = $new['access_token'];
                $token['expires_at']   = time() + ($new['expires_in'] ?? 3600);
                file_put_contents($this->tokenFile, json_encode($token));
            }
        }
        return $token['access_token'];
    }

    // ── Verificar se está autorizado ──────────────────────────────────────────
    public function isAuthorized(): bool {
        return file_exists($this->tokenFile);
    }

    // ── Criar pasta se não existir ────────────────────────────────────────────
    public function criarPasta(string $nome, string $parentId): string {
        $token = $this->getAccessToken();
        $nomeSafe = str_replace("'", "\\'", $nome);
        $query = urlencode("name='{$nomeSafe}' and '{$parentId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false");
        $ch = curl_init("https://www.googleapis.com/drive/v3/files?q={$query}&fields=files(id,name)");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (!empty($data['files'][0]['id'])) return $data['files'][0]['id'];

        $ch = curl_init('https://www.googleapis.com/drive/v3/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['name' => $nome, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$parentId]]),
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (empty($data['id'])) throw new RuntimeException('Failed to create folder: ' . $res);
        return $data['id'];
    }

    // ── Upload de arquivo ─────────────────────────────────────────────────────
    public function uploadArquivo(string $filePath, string $fileName, string $folderId, string $mimeType = 'image/jpeg'): array {
        $token    = $this->getAccessToken();
        $metadata = json_encode(['name' => $fileName, 'parents' => [$folderId]]);
        $content  = file_get_contents($filePath);
        $boundary = '-------314159265358979323846';
        $body  = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$metadata}\r\n";
        $body .= "--{$boundary}\r\nContent-Type: {$mimeType}\r\n\r\n{$content}\r\n--{$boundary}--";

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", "Content-Type: multipart/related; boundary=\"{$boundary}\""],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $res  = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        if (empty($data['id'])) throw new RuntimeException('Upload failed: ' . $res);
        $this->tornarPublico($data['id']);
        return $data;
    }

    private function tornarPublico(string $fileId): void {
        $token = $this->getAccessToken();
        $ch = curl_init("https://www.googleapis.com/drive/v3/files/{$fileId}/permissions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['role' => 'reader', 'type' => 'anyone']),
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function uploadFotoEvento(string $filePath, string $fileName, string $ano, string $nomeEvento): array {
        $pastaEventos = $this->criarPasta('Eventos', $this->rootFolderId);
        $pastaAno     = $this->criarPasta($ano, $pastaEventos);
        $pastaEvento  = $this->criarPasta($nomeEvento, $pastaAno);
        return $this->uploadArquivo($filePath, $fileName, $pastaEvento);
    }

    public function uploadFotoSexta(string $filePath, string $fileName, string $ano, string $mes): array {
        $pastaSextas = $this->criarPasta('Sextas', $this->rootFolderId);
        $pastaAno    = $this->criarPasta($ano, $pastaSextas);
        $pastaMes    = $this->criarPasta($mes, $pastaAno);
        return $this->uploadArquivo($filePath, $fileName, $pastaMes);
    }

    public static function thumbnailUrl(string $fileId, int $size = 400): string {
        // URL de thumbnail público do Drive — funciona com arquivos compartilhados publicamente
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w{$size}";
    }

    public static function viewUrl(string $fileId): string {
        // URL de visualização direta da imagem (não a página do Drive)
        return "https://drive.google.com/thumbnail?id={$fileId}&sz=w1200";
    }
}
