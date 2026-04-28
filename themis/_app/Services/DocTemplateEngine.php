<?php
declare(strict_types=1);
/**
 * DocTemplateEngine — Fábrica de Documentos
 * PDF via TCPDF (sem Composer). Fallback: HTML puro.
 * Para usar TCPDF: arraste a pasta tcpdf/ para vendor/tcpdf/
 */
final class DocTemplateEngine
{
    public function __construct(private DB $db, private StorageManager $storage) {}

    // ── Renderiza template com variáveis dinâmicas ────────
    public function gerarPdfHtml(string $html, string $nomeArquivo, ?int $processoId, int $userId): array
    {
        $tcpdfMain = Bootstrap::cfg('pdf.tcpdf_main', THEMIS_ROOT . '/vendor/tcpdf/tcpdf.php');
        if (!file_exists($tcpdfMain)) throw new \RuntimeException('TCPDF não encontrado.', 500);

        $subdir  = 'processos/' . ($processoId ?: 0) . '/documentos';
        $fullDir = THEMIS_ROOT . '/_storage/' . $subdir;
        if (!is_dir($fullDir)) mkdir($fullDir, 0750, true);
        $hash = hash('sha1', $html . microtime());

        // Aplicar papel timbrado igual à Fábrica de Docs
        $html = $this->wrapTimbrado($html);
        $path = $this->gerarComTCPDF($html, $fullDir, $subdir, $hash, $tcpdfMain);

        // Salvar no banco de documentos
        $size = file_exists("{$fullDir}/{$hash}.pdf") ? filesize("{$fullDir}/{$hash}.pdf") : 0;
        $row = [
            'tenant_id'     => $this->db->getTenantId(),
            'user_id'       => $userId,
            'categoria'     => 'parecer',
            'nome_original' => $nomeArquivo,
            'nome_hash'     => $hash,
            'caminho'       => $path,
            'mime_type'     => 'application/pdf',
            'tamanho_bytes' => $size,
        ];
        if ($processoId) $row['processo_id'] = $processoId;
        $id = (int) $this->db->insert('documentos', $row);

        return ['id' => $id, 'path' => $path, 'nome' => $nomeArquivo];
    }

    public function render(int $templateId, array $vars, ?int $processoId = null, int $userId = 0): array
    {
        $tpl = $this->db->first("SELECT * FROM doc_templates WHERE id = ? AND ativo = 1", [$templateId]);
        if (!$tpl) throw new \RuntimeException('Template não encontrado.', 404);

        if ($processoId) $vars = array_merge($this->processoVars($processoId), $vars);

        // Variáveis de sistema
        $meses = ['','janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
        $vars['data_hoje']     = date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
        $vars['data_hoje_fmt'] = date('d/m/Y');
        $vars['hora_atual']    = date('H:i');
        $vars['ano_atual']     = date('Y');
        $vars['app_nome']      = Bootstrap::cfg('app.name', 'Themis');

        $html = $tpl['conteudo_html'];

        // {{#if variavel}}...{{/if}}
        $html = preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            fn($m) => !empty($vars[$m[1]]) ? $m[2] : '',
            $html
        );

        // {{#each lista}}...{{/each}}
        $html = preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($m) use ($vars) {
                $list = $vars[$m[1]] ?? [];
                if (!is_array($list)) return '';
                return implode('', array_map(function ($item) use ($m) {
                    $b = $m[2];
                    foreach ($item as $k => $v) {
                        $b = str_replace("{{this.{$k}}}", htmlspecialchars((string)$v), $b);
                    }
                    return $b;
                }, $list));
            },
            $html
        );

        // {{variavel}}
        foreach ($vars as $k => $v) {
            if (is_scalar($v)) {
                $html = str_replace("{{{$k}}}", htmlspecialchars((string)$v), $html);
            }
        }
        // Remove tags não preenchidas
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        if ((int)$tpl['papel_timbrado']) $html = $this->wrapTimbrado($html);

        $pdfPath = $this->gerarPDF($html, $processoId);

        $this->db->run("UPDATE doc_templates SET uso_count = uso_count + 1 WHERE id = ?", [$templateId]);

        // ── Salvar no GED (tabela documentos) ─────────────────────────
        $tid = $this->db->getTenantId();
        if (!$tid) {
            error_log('[DocTemplateEngine] getTenantId() retornou 0 — não é possível salvar no GED');
            throw new \RuntimeException('Sessão inválida. Faça login novamente.', 401);
        }
        // Transliterar acentos para ASCII antes de limpar caracteres especiais
        $nomeBase = $tpl['nome'];
        $nomeBase = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeBase) ?: $tpl['nome'];
        $nomeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nomeBase);
        $nomeBase = preg_replace('/_+/', '_', $nomeBase); // colapsar múltiplos _
        $nomeBase = trim($nomeBase, '_');
        $nomeArquivo = ($nomeBase ?: 'documento') . '_' . date('Ymd_His');
        $ext         = str_ends_with($pdfPath, '.pdf') ? 'pdf' : 'html';
        $mime        = $ext === 'pdf' ? 'application/pdf' : 'text/html';
        $fullPath    = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage') . '/' . $pdfPath;
        $size        = file_exists($fullPath) ? filesize($fullPath) : 0;
        $hash = false;
        if (file_exists($fullPath)) {
            $hash = sha1_file($fullPath);
        }
        if (!$hash || strlen($hash) !== 40) {
            $hash = substr(sha1($pdfPath . microtime() . $userId), 0, 40);
        }
        $categoria   = 'outros'; // ENUM da tabela documentos não tem 'documento'

        // Evitar duplicata
        // Dedup: processo_id pode ser null
        if ($processoId) {
            $gedExist = $this->db->first("SELECT id FROM documentos WHERE nome_hash=? AND processo_id=? AND deleted_at IS NULL", [$hash, $processoId]);
        } else {
            $gedExist = $this->db->first("SELECT id FROM documentos WHERE nome_hash=? AND processo_id IS NULL AND deleted_at IS NULL", [$hash]);
        }
        // Montar dados do documento para inserção
        $docData = [
            'tenant_id'      => $tid,
            'user_id'        => $userId,
            'categoria'      => $categoria,
            'nome_original'  => $nomeArquivo . '.' . $ext,
            'nome_hash'      => $hash,
            'caminho'        => $pdfPath,
            'mime_type'      => $mime,
            'tamanho_bytes'  => max(1, $size),
            'publico_cliente' => 0,
        ];
        if ($processoId) $docData['processo_id'] = $processoId;

        if ($gedExist) {
            $gedId = (int) $gedExist['id'];
        } else {
            try {
                $gedId = (int) $this->db->insert('documentos', $docData);
            } catch (\Throwable $e) {
                // Log do erro real para diagnóstico
                error_log('[DocTemplateEngine] ERRO ao salvar no GED: ' . $e->getMessage());
                error_log('[DocTemplateEngine] Dados: ' . json_encode($docData));
                throw new \RuntimeException('Erro ao salvar documento no GED: ' . $e->getMessage(), 500);
            }
        }

        // ── Salvar em doc_gerados ────────────────────────────────────
        $docGeradoRow = [
            'tenant_id'      => $this->db->getTenantId(),
            'template_id'    => $templateId,
            'user_id'        => $userId,
            'titulo'         => $tpl['nome'] . ' — ' . ($vars['cliente_nome'] ?? date('d/m/Y')),
            'documento_id'   => $gedId,
            'variaveis_json' => json_encode($vars),
        ];
        if ($processoId) $docGeradoRow['processo_id'] = $processoId;
        $docGeradoId = (int) $this->db->insert('doc_gerados', $docGeradoRow);

        return [
            'doc_gerado_id' => $docGeradoId,
            'documento_id'  => $gedId,
            'template'      => $tpl['nome'],
            'pdf_path'      => $pdfPath,
            'nome'          => $nomeArquivo . '.' . $ext,
            'success'       => true,
        ];
    }

    // ── Gera PDF via TCPDF ou fallback HTML ───────────────
    private function gerarPDF(string $htmlCompleto, ?int $processoId): string
    {
        $hash    = sha1($htmlCompleto . microtime(true));
        $subdir  = 'processos/' . ($processoId ?? '0') . '/documentos';
        $fullDir = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage') . '/' . $subdir;
        if (!is_dir($fullDir)) mkdir($fullDir, 0750, true);

        $driver    = Bootstrap::cfg('pdf.driver', 'html');
        $tcpdfPath = Bootstrap::cfg('pdf.tcpdf_path', THEMIS_ROOT . '/vendor/tcpdf');
        $tcpdfMain = $tcpdfPath . '/tcpdf.php';

        if ($driver === 'tcpdf' && file_exists($tcpdfMain)) {
            return $this->gerarComTCPDF($htmlCompleto, $fullDir, $subdir, $hash, $tcpdfMain);
        }

        // Fallback HTML
        $file = "{$fullDir}/{$hash}.html";
        file_put_contents($file, $htmlCompleto);
        return "{$subdir}/{$hash}.html";
    }

    private function gerarComTCPDF(string $html, string $fullDir, string $subdir, string $hash, string $tcpdfMain): string
    {
        $tcpdfDir = dirname($tcpdfMain) . '/';

        // Definir APENAS os K_PATH_* antes do require_once
        // para redirecionar caminhos que estariam fora do open_basedir.
        // NÃO definir constantes que o tcpdf_config.php já define
        // (K_BLANK_IMAGE, PDF_*, etc.) — causaria "already defined" fatal.
        $cacheDir = THEMIS_ROOT . '/_storage/temp/';
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0750, true);

        if (!defined('K_PATH_MAIN'))      define('K_PATH_MAIN',      $tcpdfDir);
        if (!defined('K_PATH_URL'))       define('K_PATH_URL',       '');
        if (!defined('K_PATH_FONTS'))     define('K_PATH_FONTS',     $tcpdfDir . 'fonts/');
        if (!defined('K_PATH_CACHE'))     define('K_PATH_CACHE',     $cacheDir);
        if (!defined('K_PATH_URL_CACHE')) define('K_PATH_URL_CACHE', '');
        if (!defined('K_PATH_IMAGES'))    define('K_PATH_IMAGES',    THEMIS_ROOT . '/assets/img/');

        require_once $tcpdfMain;

        $cfg  = Bootstrap::cfg('pdf', []);
        $nome = Bootstrap::cfg('app.name', 'Themis Enterprise');

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator($nome);
        $pdf->SetAuthor($nome);
        $pdf->SetTitle('Documento ' . $nome);
        $pdf->SetSubject('Documento Jurídico');
        $pdf->SetKeywords('Themis, Jurídico, Documento');

        // Sem header/footer padrão do TCPDF
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFooterFont(['helvetica', '', 6]);
        $pdf->setFooterData([0,0,0], [255,255,255]);
        $pdf->setFooterMargin(0);
        $pdf->SetFooterMargin(0);

        $ml = $cfg['margin_left']   ?? 20;
        $mt = $cfg['margin_top']    ?? 20;
        $mr = $cfg['margin_right']  ?? 15;
        $mb = $cfg['margin_bottom'] ?? 20;

        $pdf->SetMargins($ml, $mt, $mr);
        $pdf->SetAutoPageBreak(true, $mb);

        // Fonte padrão
        $font     = $cfg['font']      ?? 'helvetica';
        $fontSize = $cfg['font_size'] ?? 11;
        $pdf->SetFont($font, '', $fontSize);

        $pdf->AddPage();

        // Converter entidades HTML para UTF-8 real antes de renderizar
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // writeHTML: $ln=true, $fill=false, $reseth=true, $cell=false, $align=''
        $pdf->writeHTML($html, true, false, true, false, '');

        $file = "{$fullDir}/{$hash}.pdf";
        $pdf->Output($file, 'F');

        return "{$subdir}/{$hash}.pdf";
    }

    // ── Watermark via TCPDF reprocessando o PDF gerado ────
    // ── Papel timbrado — compatível com TCPDF ─────────────
    private function wrapTimbrado(string $content): string
    {
        $tenant   = $this->db->first("SELECT razao_social, cnpj FROM tenants WHERE id = ?", [$this->db->getTenantId()]);
        $nome     = $tenant['razao_social'] ?? Bootstrap::cfg('app.name', 'Escritorio');
        $nomeHtml = htmlspecialchars($nome, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $logoPath  = THEMIS_ROOT . '/assets/img/themis_logo.png';
        $logoTag   = '';
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
            $logoTag = '<img src="data:image/png;base64,' . $logoB64 . '" height="48" alt="' . $nomeHtml . '">';
        }

        // Marca d'água inserida diretamente via TCPDF Image() em gerarComTCPDF
        $wmTag = '';

        $css = '
body { font-family: helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #111; }
table { width: 100%; border-collapse: collapse; }
.th-logo { width: 35%; vertical-align: middle; }
.th-info { width: 65%; vertical-align: middle; text-align: right; font-size: 9pt; color: #444; line-height: 1.4; }
.th-nome { font-size: 13pt; font-weight: bold; color: #1e3a5f; }
.divider { border-top: 2px solid #1e3a5f; margin: 0 0 18pt 0; }
.content { font-size: 11pt; text-align: justify; }
h1, h2, h3 { color: #1e3a5f; }
p { margin: 0 0 8pt 0; }
';

        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>
' . $wmTag . '
<table><tr>
  <td class="th-logo">' . $logoTag . '</td>
  <td class="th-info">
    <div class="th-nome">' . $nomeHtml . '</div>
    Advocacia &amp; Per&iacute;cias Judiciais
  </td>
</tr></table>
<div class="divider"></div>
<div class="content">' . $content . '</div>
</body></html>';
    }


    // ── Variáveis automáticas do processo ─────────────────
    private function processoVars(int $id): array
    {
        $p = $this->db->first(
            "SELECT p.*, s.nome AS cliente_nome, s.cpf_cnpj AS cliente_doc,
             s.endereco_json AS cliente_end,
             u.nome AS adv_nome, u.oab_numero, u.oab_uf
             FROM processos p
             JOIN stakeholders s ON s.id = p.cliente_id
             JOIN users u ON u.id = p.responsavel_id
             WHERE p.id = ?",
            [$id]
        );
        if (!$p) return [];
        $end = json_decode($p['cliente_end'] ?? '{}', true) ?: [];
        return [
            'processo_numero'   => $p['numero_cnj'] ?? $p['numero_interno'],
            'processo_interno'  => $p['numero_interno'],
            'processo_titulo'   => $p['titulo'],
            'processo_vara'     => $p['vara']      ?? '',
            'processo_comarca'  => $p['comarca']   ?? '',
            'processo_tribunal' => $p['tribunal']  ?? '',
            'valor_causa'       => $p['valor_causa']
                                    ? 'R$ ' . number_format((float)$p['valor_causa'], 2, ',', '.')
                                    : '',
            'cliente_nome'      => $p['cliente_nome'],
            'cliente_doc'       => $p['cliente_doc'] ?? '',
            'cliente_endereco'  => implode(', ', array_filter([
                $end['logradouro'] ?? '',
                $end['numero']     ?? '',
                $end['cidade']     ?? '',
                $end['uf']         ?? '',
            ])),
            'advogado_nome'     => $p['adv_nome'],
            'advogado_oab'      => $p['oab_numero']
                                    ? "OAB/{$p['oab_uf']} {$p['oab_numero']}"
                                    : '',
            'parte_contraria'   => $p['parte_contraria'] ?? '',
            'polo'              => ucfirst($p['polo'] ?? ''),
        ];
    }

    // ── Envia para assinatura via Assinafy ────────────────
    /**
     * Envia documento para assinatura na Assinafy.
     * Fluxo: 1) Upload multipart → doc_id
     *         2) Criar signatários → signer_ids
     *         3) Criar assignment virtual → signing_urls
     */
    public function enviarAssinatura(int $documentoId, array $signatarios): array
    {
        $doc = $this->db->first("SELECT * FROM documentos WHERE id = ?", [$documentoId]);
        if (!$doc) throw new \RuntimeException('Documento não encontrado.', 404);

        $full = Bootstrap::cfg('storage.path', THEMIS_ROOT . '/_storage') . '/' . $doc['caminho'];
        if (!file_exists($full)) throw new \RuntimeException('Arquivo PDF não encontrado no storage.', 404);

        // Token e account_id do banco do tenant
        $tenant   = $this->db->first("SELECT assinafy_key, assinafy_account_id FROM tenants WHERE id = ?", [$this->db->getTenantId()]);
        $apiKey   = trim($tenant['assinafy_key'] ?? '');
        $accountId = trim($tenant['assinafy_account_id'] ?? '');

        if (empty($apiKey)) {
            throw new \RuntimeException('Token Assinafy não configurado. Acesse Configurações → Assinafy.', 400);
        }
        if (empty($accountId)) {
            throw new \RuntimeException('Workspace Account ID da Assinafy não configurado. Acesse Configurações → Assinafy.', 400);
        }
        if (empty($signatarios)) {
            throw new \RuntimeException('Informe pelo menos um signatário (nome e e-mail).', 400);
        }

        $base    = 'https://api.assinafy.com.br/v1';
        $headers = ["X-Api-Key: {$apiKey}", 'Accept: application/json'];

        // ── PASSO 1: Upload do PDF via CURLFile (multipart nativo) ──────────────
        // CURLFile deixa o cURL montar o multipart corretamente (evita body vazio / boundary errado)
        $fileSize = filesize($full);
        if ($fileSize === false || $fileSize === 0) {
            throw new \RuntimeException('PDF inválido ou vazio: ' . $full, 422);
        }
        $cfile = new \CURLFile($full, 'application/pdf', basename($full));

        $ch = curl_init("{$base}/accounts/{$accountId}/documents");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => ['file' => $cfile],
            CURLOPT_HTTPHEADER     => $headers, // sem Content-Type manual; cURL define o boundary
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        error_log("[Assinafy][P1] HTTP={$code} len=" . strlen((string)$resp) . " curlErr={$err}");
        error_log("[Assinafy][P1] body=" . substr((string)$resp, 0, 600));

        if ($err) throw new \RuntimeException("Assinafy upload erro cURL: {$err}", 503);
        if (trim((string)$resp) === '') {
            throw new \RuntimeException("Assinafy retornou resposta vazia no upload (HTTP {$code}). Verifique API Key e Account ID.", 502);
        }
        $uploadData = json_decode((string)$resp, true);
        if ($uploadData === null) {
            throw new \RuntimeException("Assinafy resposta não-JSON no upload: " . substr((string)$resp, 0, 300), 502);
        }
        if ($code >= 400) {
            $msg = $uploadData['message'] ?? "HTTP {$code}";
            throw new \RuntimeException("Assinafy upload falhou: {$msg}", 422);
        }
        // Resposta pode vir como {"id":...} ou {"data":{"id":...}}
        $assinafyDocId = $uploadData['data']['id'] ?? $uploadData['id'] ?? null;
        if (!$assinafyDocId) throw new \RuntimeException('Assinafy não retornou ID do documento. Resp: ' . substr((string)$resp, 0, 300), 502);// ── PASSO 2: Criar signatários ────────────────────────────────
        $signerIds = [];
        foreach ($signatarios as $s) {
            $nome  = $s['nome']  ?? $s['name']  ?? 'Signatário';
            $email = $s['email'] ?? '';
            if (empty($email)) continue; // email obrigatório

            $ch = curl_init("{$base}/accounts/{$accountId}/signers");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['full_name' => $nome, 'email' => $email]),
                CURLOPT_HTTPHEADER     => array_merge($headers, ['Content-Type: application/json']),
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $signerData = json_decode((string)$resp, true) ?? [];
            $signerId   = $signerData['data']['id'] ?? $signerData['id'] ?? null;
            error_log("[Assinafy][P2] HTTP={$code} signerId={$signerId} resp=" . substr((string)$resp, 0, 300));

            // Se 400 por e-mail duplicado, buscar o signatário existente
            if (!$signerId && $code === 400) {
                $search = curl_init("{$base}/accounts/{$accountId}/signers?search=" . urlencode($email));
                curl_setopt_array($search, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $sResp = curl_exec($search);
                curl_close($search);
                $sData = json_decode((string)$sResp, true) ?? [];
                $signers = $sData['data'] ?? [];
                foreach ($signers as $existing) {
                    if (strtolower($existing['email'] ?? '') === strtolower($email)) {
                        $signerId = $existing['id'];
                        error_log("[Assinafy][P2] Signatário existente encontrado: {$signerId}");
                        break;
                    }
                }
            }
            if ($signerId) {
                $signerIds[] = ['id' => $signerId];
            }
        }
        if (empty($signerIds)) throw new \RuntimeException('Nenhum signatário válido (e-mail obrigatório).', 422);

        // ── PASSO 3: Criar assignment virtual ─────────────────────────
        $appUrl  = Bootstrap::cfg('app.url', '');
        $payload = [
            'method'   => 'virtual',
            'signers'  => $signerIds,
            'message'  => 'Por favor, assine o documento jurídico em anexo.',
        ];

        $ch = curl_init("{$base}/documents/{$assinafyDocId}/assignments");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_merge($headers, ['Content-Type: application/json']),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("Assinafy assignment erro cURL: {$err}", 503);
        $assignData = json_decode((string)$resp, true) ?? [];
        if ($code >= 400) {
            $msg = $assignData['message'] ?? "HTTP {$code}";
            throw new \RuntimeException("Assinafy assignment falhou: {$msg}", 422);
        }

        // ── Atualizar registro no banco ───────────────────────────────
        $this->db->update('documentos', [
            'assinatura_status' => 'enviado',
            'assinafy_doc_id'   => $assinafyDocId,
        ], ['id' => $documentoId]);

        // Coletar links de assinatura (resposta pode vir em data ou raiz)
        $assignRoot  = $assignData['data'] ?? $assignData;
        $signingUrls = $assignRoot['signing_urls'] ?? [];
        $firstUrl    = !empty($signingUrls[0]['url']) ? $signingUrls[0]['url'] : null;

        error_log("[Assinafy] Documento {$documentoId} → assinafy_doc_id={$assinafyDocId} | signers=" . count($signerIds));

        return [
            'id'           => $assinafyDocId,
            'assignment_id'=> $assignRoot['id'] ?? null,
            'signing_urls' => $signingUrls,
            'url'          => $firstUrl,
            'signing_url'  => $firstUrl,
        ];
    }
}
