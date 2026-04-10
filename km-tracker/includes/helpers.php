<?php
// includes/helpers.php - Funções auxiliares

/**
 * Escape para HTML (alias de htmlspecialchars)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata data para exibição (YYYY-MM-DD para DD/MM/YYYY)
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return '—';
    }
    return date($format, $timestamp);
}

/**
 * Formata quilometragem para exibição
 */
function formatKm($km) {
    if (empty($km) || $km == 0) {
        return '0 km';
    }
    return number_format((float)$km, 0, ',', '.') . ' km';
}

/**
 * Retorna flash message da sessão
 */
function getFlash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

/**
 * Define flash message na sessão
 */
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Alias para setFlash (compatibilidade)
 */
function flash($type, $message) {
    setFlash($type, $message);
}

/**
 * Retorna nome do mês em português
 */
function getMonthName($month) {
    $months = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $months[(int)$month] ?? '';
}

/**
 * Gera slug a partir de string
 */
function createSlug($string) {
    $string = preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($string)));
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Valida CPF
 */
function validateCpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

/**
 * Formata CPF para exibição
 */
function formatCpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

/**
 * Trunca texto em um determinado número de caracteres
 */
function truncate($text, $length = 100, $suffix = '…') {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Retorna a URL base
 */
function baseUrl($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Verifica se é uma requisição AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Redireciona para uma URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Calcula paginação
 */
function paginate($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'perPage' => $perPage,
        'current' => $currentPage,
        'pages' => $totalPages,
        'offset' => $offset,
        'hasPrev' => $currentPage > 1,
        'hasNext' => $currentPage < $totalPages,
        'prevPage' => $currentPage - 1,
        'nextPage' => $currentPage + 1
    ];
}

/**
 * Gera token CSRF (se não existir no auth.php)
 */
if (!function_exists('csrfToken')) {
    function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verifica CSRF (se não existir no auth.php)
 */
if (!function_exists('verifyCsrf')) {
    function verifyCsrf() {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Erro de validação CSRF');
        }
    }
}
// ============================================================
// FUNÇÕES PARA AS SEXTAS (Eventos obrigatórios)
// ============================================================

/**
 * Gera todas as datas de Sexta-feira do ano
 */
function getSextasDoAno($year) {
    $sextas = [];
    $data = new DateTime("first friday of January $year");
    
    while ($data->format('Y') == $year) {
        $sextas[] = $data->format('Y-m-d');
        $data->modify('+7 days');
    }
    
    return $sextas;
}

/**
 * Busca a PRÓXIMA Sexta-feira (apenas uma)
 */
function getProximaSexta($db, $userId, $year) {
    $todasSextas = getSextasDoAno($year);
    $hoje = date('Y-m-d');
    
    // Buscar a próxima Sexta (a mais próxima, incluindo hoje se for sexta)
    $proximaData = null;
    foreach ($todasSextas as $data) {
        if ($data >= $hoje) {
            $proximaData = $data;
            break;
        }
    }
    
    if (!$proximaData) {
        return null;
    }
    
    // Buscar confirmação existente
    $stmt = $db->prepare("SELECT data_sexta, status FROM sextas_confirmacoes WHERE user_id = ? AND data_sexta = ?");
    $stmt->execute([$userId, $proximaData]);
    $confirmacao = $stmt->fetch();
    
    return [
        'data' => $proximaData,
        'ja_confirmou' => !empty($confirmacao),
        'status' => $confirmacao['status'] ?? null
    ];
}

/**
 * Confirma uma Sexta para um usuário
 */
function confirmarSexta($db, $userId, $dataSexta) {
    // Verificar se já existe
    $stmt = $db->prepare("SELECT id FROM sextas_confirmacoes WHERE data_sexta = ? AND user_id = ?");
    $stmt->execute([$dataSexta, $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $db->prepare("UPDATE sextas_confirmacoes SET status = 'confirmado', confirmed_at = NOW() WHERE id = ?");
        return $stmt->execute([$existing['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO sextas_confirmacoes (data_sexta, user_id, status, confirmed_at) VALUES (?, ?, 'confirmado', NOW())");
        return $stmt->execute([$dataSexta, $userId]);
    }
}

/**
 * Conta total de confirmações de Sextas no ano
 */
function countSextasConfirmadas($db, $year) {
    $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) FROM sextas_confirmacoes WHERE YEAR(data_sexta) = ?");
    $stmt->execute([$year]);
    return (int)$stmt->fetchColumn();
}

/**
 * Verifica se um usuário já confirmou uma Sexta específica
 */
function verificarConfirmacaoSexta($db, $userId, $dataSexta) {
    $stmt = $db->prepare("SELECT id, status FROM sextas_confirmacoes WHERE data_sexta = ? AND user_id = ?");
    $stmt->execute([$dataSexta, $userId]);
    return $stmt->fetch();
}

/**
 * Cancela confirmação de uma Sexta
 */
function cancelarConfirmacaoSexta($db, $userId, $dataSexta) {
    $stmt = $db->prepare("DELETE FROM sextas_confirmacoes WHERE data_sexta = ? AND user_id = ?");
    return $stmt->execute([$dataSexta, $userId]);
}

/**
 * Retorna o total de Sextas no ano
 */
function totalSextasNoAno($year) {
    return count(getSextasDoAno($year));
}

/**
 * Retorna o percentual de confirmações de um usuário nas Sextas
 */
function percentualConfirmacaoSextas($db, $userId, $year) {
    $totalSextas = totalSextasNoAno($year);
    if ($totalSextas == 0) return 0;
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM sextas_confirmacoes WHERE user_id = ? AND YEAR(data_sexta) = ?");
    $stmt->execute([$userId, $year]);
    $confirmadas = (int)$stmt->fetchColumn();
    
    return round(($confirmadas / $totalSextas) * 100);
}