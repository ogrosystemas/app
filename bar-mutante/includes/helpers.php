<?php
function h(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function moeda(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function moedaNum(float $v): string {
    return number_format($v, 2, ',', '.');
}

function parseMoeda(string $v): float {
    return (float) str_replace(['.', ','], ['', '.'], $v);
}

function dataBR(?string $d): string {
    if (!$d) return '-';
    return date('d/m/Y', strtotime($d));
}

function dataHoraBR(?string $d): string {
    if (!$d) return '-';
    return date('d/m/Y H:i', strtotime($d));
}

function flash(string $tipo = 'success'): string {
    $key = "flash_$tipo";
    if (!empty($_SESSION[$key])) {
        $m = $_SESSION[$key];
        unset($_SESSION[$key]);
        $cls = match($tipo) { 'error'=>'danger', default=>$tipo };
        return "<div class='alert alert-$cls alert-dismissible fade show py-2' role='alert'>$m<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
    return '';
}

function setFlash(string $tipo, string $msg): void {
    $_SESSION["flash_$tipo"] = $msg;
}

function redirect(string $url): never {
    http_response_code(302);
    header("Location: $url");
    exit;
}

function jsonRes(array $data, int $status = 200): never {
    // Descartar qualquer output acidental (warnings, PHP notices)
    while (ob_get_level()) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function caixaAberto(): ?array {
    return DB::row("SELECT * FROM caixas WHERE status='aberto' ORDER BY id DESC LIMIT 1");
}

function alertasEstoque(): array {
    return DB::all("SELECT id,nome,estoque_atual,estoque_minimo,tipo,unidade_estoque FROM produtos WHERE ativo=1 AND estoque_minimo>0 AND estoque_atual<=estoque_minimo ORDER BY (estoque_atual/NULLIF(estoque_minimo,0))");
}

// Cálculo de barril de chopp
function calcBarril(float $capacidade_ml, float $rendimento_pct = 85, float $ml_por_dose = 300): array {
    $ml_util    = $capacidade_ml * ($rendimento_pct / 100);
    $ml_perda   = $capacidade_ml - $ml_util;
    $doses      = floor($ml_util / $ml_por_dose);
    $litros_util= $ml_util / 1000;
    $litros_cap = $capacidade_ml / 1000;
    return compact('ml_util','ml_perda','doses','litros_util','litros_cap','rendimento_pct','ml_por_dose','capacidade_ml');
}

function uploadImagem(array $file, string $destDir): string|false {
    // Verificar erro de upload do PHP
    if ($file['error'] !== UPLOAD_ERR_OK) return false;

    // Usar finfo para detecção real do MIME (ignora $_FILES['type'] que pode ser falso)
    $finfo    = new \finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);

    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array(strtolower($mimeReal), $allowed)) return false;

    if ($file['size'] > 5 * 1024 * 1024) return false;

    // Extensão baseada no MIME real
    $extMap = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    $ext    = $extMap[strtolower($mimeReal)] ?? 'jpg';

    $name = uniqid('img_', true) . '.' . $ext;
    $dest = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;

    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0775, true)) return false;
    }

    return move_uploaded_file($file['tmp_name'], $dest) ? $name : false;
}

function movEstoque(int $produto_id, string $tipo, float $qty, float $custo = 0, string $motivo = '', string $ref = '', int $ref_id = 0, ?int $barril_id = null): void {
    $p = DB::row("SELECT estoque_atual, unidade_estoque FROM produtos WHERE id=?", [$produto_id]);
    if (!$p) return;
    $ant  = (float)$p['estoque_atual'];
    $novo = in_array($tipo, ['entrada','abertura_barril']) ? $ant + $qty : max(0, $ant - $qty);
    DB::update('produtos', ['estoque_atual' => $novo], 'id=?', [$produto_id]);
    DB::insert('estoque_movimentacoes', [
        'produto_id'       => $produto_id,
        'barril_id'        => $barril_id,
        'tipo'             => $tipo,
        'quantidade'       => $qty,
        'estoque_anterior' => $ant,
        'estoque_novo'     => $novo,
        'unidade'          => $p['unidade_estoque'],
        'custo_unitario'   => $custo,
        'motivo'           => $motivo,
        'referencia'       => $ref,
        'referencia_id'    => $ref_id,
        'operador'         => $_SESSION['operador'] ?? 'sistema',
    ]);
}


// ── CSRF Protection ───────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): void {
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token inválido. Recarregue a página e tente novamente.');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrfToken()) . '">';
}
