<?php
/**
 * licenca.php — Sistema de Licenciamento OS-System
 *
 * Funcionamento:
 *  - Trial: 15 dias gratuitos a partir da primeira execução
 *  - Licença anual: chave gerada externamente, validada offline via HMAC-SHA256
 *  - Vinculada ao domínio no momento da ativação
 */

// ── Chave secreta compartilhada com o gerador de chaves ──────────────────────
// IMPORTANTE: altere este valor e mantenha o mesmo no gerador HTML
define('LICENCA_SECRET', 'OSSystem@2025#SecretKey!Moto$Workshop');
define('LICENCA_TRIAL_DIAS', 15);
define('LICENCA_PREFIXO', 'OSSYS');

// ── Funções utilitárias ───────────────────────────────────────────────────────

/**
 * Retorna o domínio atual (host sem www e sem porta).
 */
function licenca_dominio(): string {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host); // remove porta
    $host = strtolower(preg_replace('/^www\./', '', $host));
    return $host;
}

/**
 * Garante que o registro de instalação existe e retorna a data de instalação.
 */
function licenca_get_install(PDO $db): \DateTime {
    // Criar tabela se não existir (segurança extra)
    try {
        $row = $db->query("SELECT data_instalacao FROM sistema_install ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        // Tabela não existe ainda — retornar hoje (trial começa agora)
        return new \DateTime();
    }

    if (!$row) {
        // Primeira execução — registrar
        $token = bin2hex(random_bytes(32));
        try {
            $db->prepare("INSERT INTO sistema_install (install_token, data_instalacao) VALUES (?, NOW())")
               ->execute([$token]);
            $row = $db->query("SELECT data_instalacao FROM sistema_install ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return new \DateTime();
        }
    }

    return new \DateTime($row['data_instalacao']);
}

/**
 * Busca a licença ativa no banco.
 * Retorna array com dados ou null se não houver.
 */
function licenca_get_ativa(PDO $db): ?array {
    try {
        $row = $db->query(
            "SELECT * FROM licencas ORDER BY data_expiracao DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (\PDOException $e) {
        return null;
    }
}

/**
 * Gera o HMAC esperado para uma chave.
 * Formato da chave: OSSYS-XXXX-XXXX-XXXX-XXXX
 * Payload assinado: domínio|data_expiracao(Y-m-d)
 */
function licenca_hmac(string $dominio, string $data_expiracao_ymd): string {
    $payload = strtolower($dominio) . '|' . $data_expiracao_ymd;
    return strtoupper(substr(hash_hmac('sha256', $payload, LICENCA_SECRET), 0, 16));
}

/**
 * Valida uma chave de licença para o domínio atual.
 * Retorna ['valida' => bool, 'expiracao' => DateTime|null, 'erro' => string]
 */
function licenca_validar_chave(string $chave, string $dominio): array {
    $chave = strtoupper(trim($chave));

    // Formato: OSSYS-DDDDDDDD-HMACHMAC (domínio codificado + HMAC)
    // Estrutura real: OSSYS-{data_exp_hex}-{hmac_16chars}
    // Ex: OSSYS-20261231-AB12CD34EF56GH78

    // Remover prefixo
    if (strpos($chave, LICENCA_PREFIXO . '-') !== 0) {
        return ['valida' => false, 'expiracao' => null, 'erro' => 'Formato de chave inválido.'];
    }

    $partes = explode('-', $chave);
    // OSSYS - YYYYMMDD - XXXX - XXXX - XXXX - XXXX  (6 partes)
    if (count($partes) !== 6) {
        return ['valida' => false, 'expiracao' => null, 'erro' => 'Formato de chave inválido.'];
    }

    $data_str = $partes[1]; // YYYYMMDD
    $hmac_recebido = $partes[2] . $partes[3] . $partes[4] . $partes[5]; // 16 chars

    // Validar data
    if (!preg_match('/^\d{8}$/', $data_str)) {
        return ['valida' => false, 'expiracao' => null, 'erro' => 'Data de expiração inválida na chave.'];
    }
    $data_exp_ymd = substr($data_str, 0, 4) . '-' . substr($data_str, 4, 2) . '-' . substr($data_str, 6, 2);
    $expiracao = \DateTime::createFromFormat('Y-m-d', $data_exp_ymd);
    if (!$expiracao) {
        return ['valida' => false, 'expiracao' => null, 'erro' => 'Data de expiração inválida na chave.'];
    }

    // Calcular HMAC esperado
    $hmac_esperado = licenca_hmac($dominio, $data_exp_ymd);

    if (!hash_equals($hmac_esperado, $hmac_recebido)) {
        return ['valida' => false, 'expiracao' => null, 'erro' => 'Chave inválida ou não pertence a este domínio.'];
    }

    // Verificar se não está expirada
    $hoje = new \DateTime();
    $hoje->setTime(0, 0, 0);
    $expiracao->setTime(23, 59, 59);
    if ($hoje > $expiracao) {
        return ['valida' => false, 'expiracao' => $expiracao, 'erro' => 'Esta chave de licença já expirou.'];
    }

    return ['valida' => true, 'expiracao' => $expiracao, 'erro' => ''];
}

/**
 * Retorna o status completo da licença.
 *
 * Retorna array:
 *  'status'     => 'ativa' | 'trial' | 'expirado'
 *  'dias_restantes' => int
 *  'expiracao'  => DateTime
 *  'dominio'    => string
 *  'trial'      => bool
 */
function licenca_status(PDO $db): array {
    $dominio    = licenca_dominio();
    $instalacao = licenca_get_install($db);
    $licenca    = licenca_get_ativa($db);
    $hoje       = new \DateTime();
    $hoje->setTime(0, 0, 0);

    // Verificar licença ativa no banco
    if ($licenca) {
        $exp = new \DateTime($licenca['data_expiracao']);
        $exp->setTime(23, 59, 59);

        if ($hoje <= $exp) {
            $diff = $hoje->diff($exp);
            return [
                'status'         => 'ativa',
                'dias_restantes' => (int)$diff->days + 1,
                'expiracao'      => $exp,
                'dominio'        => $licenca['dominio'],
                'trial'          => false,
            ];
        }
    }

    // Calcular trial
    $fim_trial = clone $instalacao;
    $fim_trial->modify('+' . LICENCA_TRIAL_DIAS . ' days');
    $fim_trial->setTime(23, 59, 59);

    if ($hoje <= $fim_trial) {
        $diff = $hoje->diff($fim_trial);
        return [
            'status'         => 'trial',
            'dias_restantes' => (int)$diff->days + 1,
            'expiracao'      => $fim_trial,
            'dominio'        => $dominio,
            'trial'          => true,
        ];
    }

    // Expirado
    return [
        'status'         => 'expirado',
        'dias_restantes' => 0,
        'expiracao'      => $fim_trial,
        'dominio'        => $dominio,
        'trial'          => true,
    ];
}

/**
 * Verifica licença e redireciona para página de bloqueio se expirada.
 * Deve ser chamada após checkAuth().
 */
function licenca_check(PDO $db): void {
    // Não bloquear a própria página de configurações nem o login
    $atual = basename($_SERVER['PHP_SELF'] ?? '');
    if (in_array($atual, ['login.php', 'logout.php', 'configuracoes.php', 'licenca_bloqueio.php'])) {
        return;
    }

    $status = licenca_status($db);
    if ($status['status'] === 'expirado') {
        header('Location: ' . BASE_URL . '/licenca_bloqueio.php');
        exit;
    }
}

/**
 * Ativa uma chave de licença no banco.
 * Retorna ['ok' => bool, 'erro' => string]
 */
function licenca_ativar(PDO $db, string $chave): array {
    $dominio    = licenca_dominio();
    $resultado  = licenca_validar_chave($chave, $dominio);

    if (!$resultado['valida']) {
        return ['ok' => false, 'erro' => $resultado['erro']];
    }

    $chave_hash  = hash('sha256', strtoupper(trim($chave)));
    $expiracao   = $resultado['expiracao']->format('Y-m-d 23:59:59');

    try {
        // Verificar se chave já foi registrada
        $existe = $db->prepare("SELECT id FROM licencas WHERE chave_hash = ?");
        $existe->execute([$chave_hash]);
        if ($existe->fetch()) {
            // Atualizar domínio e datas
            $db->prepare("UPDATE licencas SET dominio=?, data_ativacao=NOW(), data_expiracao=? WHERE chave_hash=?")
               ->execute([$dominio, $expiracao, $chave_hash]);
        } else {
            $db->prepare("INSERT INTO licencas (chave_hash, dominio, data_ativacao, data_expiracao) VALUES (?,?,NOW(),?)")
               ->execute([$chave_hash, $dominio, $expiracao]);
        }
        return ['ok' => true, 'erro' => ''];
    } catch (\PDOException $e) {
        return ['ok' => false, 'erro' => 'Erro ao salvar licença: ' . $e->getMessage()];
    }
}
