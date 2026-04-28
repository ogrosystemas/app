<?php
declare(strict_types=1);
final class StorageManager
{
    private string $base;
    private int $trashDays;

    public function __construct(private DB $db)
    {
        $this->base      = rtrim(Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage'), '/');
        $this->trashDays = (int) Bootstrap::cfg('storage.trash_days', 30);
    }

    /** Upload via $_FILES */
    public function upload(array $file, int $processoId, string $categoria, int $userId, array $extra = []): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException("Erro upload: código {$file['error']}", 400);
        $this->validateMime($file['tmp_name']);
        $this->validateSize($file['size']);
        // Normaliza categoria e diretório
        $categoria = $categoria ?: 'outros';
        $procDir   = $processoId > 0 ? $processoId : '0';
        $hash      = sha1_file($file['tmp_name']);
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'bin';
        $relative  = "docs/{$procDir}/{$categoria}/{$hash}.{$ext}";
        $full      = "{$this->base}/{$relative}";
        if (!is_dir(dirname($full))) mkdir(dirname($full), 0750, true);
        if (!file_exists($full)) {
            if (!move_uploaded_file($file['tmp_name'], $full)) {
                throw new \RuntimeException('Falha ao salvar arquivo no servidor.', 500);
            }
        }
        $mime = mime_content_type($full);
        return $this->persist($hash, $file['name'], $relative, $mime, filesize($full), $processoId, $userId, $categoria, $extra);
    }

    /** Upload base64 — mobile */
    public function uploadBase64(string $b64, string $originalName, int $processoId, string $categoria, int $userId, array $extra = []): array
    {
        $data      = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $b64));
        if (!$data) throw new \RuntimeException('Base64 inválido.', 400);
        $hash      = sha1($data);
        $ext       = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: 'bin';
        $categoria = $categoria ?: 'outros';
        $procDir   = $processoId > 0 ? $processoId : '0';
        $relative  = "docs/{$procDir}/{$categoria}/{$hash}.{$ext}";
        $full      = "{$this->base}/{$relative}";
        if (!is_dir(dirname($full))) mkdir(dirname($full), 0750, true);
        if (!file_exists($full)) file_put_contents($full, $data);
        $mime = mime_content_type($full);
        return $this->persist($hash, $originalName, $relative, $mime, strlen($data), $processoId, $userId, $categoria, $extra);
    }

    private function persist(string $hash, string $nomeOriginal, string $relative, string $mime, int $size, int $processoId, int $userId, string $categoria, array $extra): array
    {
        // Evita duplicata por hash no processo
        $where = $processoId > 0
            ? ["nome_hash = ? AND processo_id = ? AND deleted_at IS NULL", [$hash, $processoId]]
            : ["nome_hash = ? AND deleted_at IS NULL", [$hash]];
        $existing = $this->db->first("SELECT id, caminho FROM documentos WHERE {$where[0]}", $where[1]);
        if ($existing) return ['id' => $existing['id'], 'hash' => $hash, 'path' => $existing['caminho'], 'duplicate' => true];

        $row = [
            'tenant_id'       => $this->db->getTenantId(),
            'user_id'         => $userId,
            'categoria'       => $categoria,
            'nome_original'   => $nomeOriginal,
            'nome_hash'       => $hash,
            'caminho'         => $relative,
            'mime_type'       => $mime,
            'tamanho_bytes'   => $size,
            'publico_cliente' => (int) ($extra['publico_cliente'] ?? 0),
            'metadata_json'   => isset($extra['metadata']) ? json_encode($extra['metadata']) : null,
        ];
        if ($processoId > 0) $row['processo_id'] = $processoId;
        $id = (int) $this->db->insert('documentos', $row);
        return ['id' => $id, 'hash' => $hash, 'path' => $relative, 'mime' => $mime, 'size' => $size, 'nome_original' => $nomeOriginal];
    }

    /** URL temporária assinada (1h de validade) */
    public function signedUrl(int $docId, int $userId): string
    {
        $exp   = time() + 3600;
        $token = hash_hmac('sha256', "{$docId}:{$userId}:{$exp}", Bootstrap::cfg('app.secret', 'themis'));
        return "/api/ged/download/{$docId}?uid={$userId}&exp={$exp}&sig={$token}";
    }

    /** Valida URL assinada */
    public function validateSignedUrl(int $docId, int $userId, int $exp, string $sig): bool
    {
        if ($exp < time()) return false;
        return hash_equals(hash_hmac('sha256', "{$docId}:{$userId}:{$exp}", Bootstrap::cfg('app.secret', 'themis')), $sig);
    }

    /** Envia arquivo para o cliente via stream */
    public function stream(int $docId): never
    {
        $doc = $this->db->first("SELECT * FROM documentos WHERE id = ? AND deleted_at IS NULL", [$docId]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado.', 404);
        $full = "{$this->base}/{$doc['caminho']}";
        if (!file_exists($full)) throw new \RuntimeException('Arquivo não encontrado no storage.', 404);
        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . addslashes($doc['nome_original']) . '"');
        header('Content-Length: ' . filesize($full));
        header('Cache-Control: private, max-age=3600');
        readfile($full);
        exit;
    }

    public function trash(int $docId): void
    {
        $this->db->update('documentos', ['deleted_at' => date('Y-m-d H:i:s')], ['id' => $docId]);
    }

    public function restore(int $docId): void
    {
        $this->db->update('documentos', ['deleted_at' => null], ['id' => $docId]);
    }

    /** Purga lixeira — rodar via cron */
    public function purgeTrash(): int
    {
        $docs = $this->db->all(
            "SELECT id, caminho FROM documentos WHERE deleted_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$this->trashDays]
        );
        $count = 0;
        foreach ($docs as $doc) {
            $full = "{$this->base}/{$doc['caminho']}";
            if (file_exists($full)) unlink($full);
            $this->db->run("DELETE FROM documentos WHERE id = ?", [$doc['id']]);
            $count++;
        }
        return $count;
    }

    private function validateMime(string $tmpPath): void
    {
        $allowed = [
            'application/pdf','image/jpeg','image/png','image/webp','image/gif',
            'audio/mpeg','audio/mp4','video/mp4',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain','application/zip',
        ];
        $mime = mime_content_type($tmpPath);
        if (!in_array($mime, $allowed, true)) throw new \RuntimeException("Tipo de arquivo não permitido: {$mime}", 400);
    }

    private function validateSize(int $bytes): void
    {
        $max = (int) Bootstrap::cfg('storage.max_mb', 50) * 1024 * 1024;
        if ($bytes > $max) throw new \RuntimeException('Arquivo excede o limite máximo de upload.', 400);
    }
}
