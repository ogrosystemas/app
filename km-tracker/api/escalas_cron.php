<?php
/**
 * api/escalas_cron.php
 * Disparo automático das escalas semanais
 * Dispara na quarta-feira, mostrando apenas a escala da SEXTA-FEIRA
 *
 * Crontab (todos executam na quarta-feira às 20h):
 * 0 20 * * 3 curl -s "https://kmtracker.ogrosystemas.com.br/api/escalas_cron.php?token=mutantes_km_secret_key_2024&tipo=limpeza" > /dev/null 2>&1
 * 0 20 * * 3 curl -s "https://kmtracker.ogrosystemas.com.br/api/escalas_cron.php?token=mutantes_km_secret_key_2024&tipo=churrasco" > /dev/null 2>&1
 * 0 20 * * 3 curl -s "https://kmtracker.ogrosystemas.com.br/api/escalas_cron.php?token=mutantes_km_secret_key_2024&tipo=bar" > /dev/null 2>&1
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_GET['token'] ?? '';
if ($token !== APP_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$tipo = $_GET['tipo'] ?? '';
$db   = db();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/evolution.php';
    $evo = new Evolution();

    $tipoConfig = $db->prepare("SELECT * FROM escalas_tipos WHERE nome=? AND ativo=1");
    $tipoConfig->execute([$tipo]);
    $config = $tipoConfig->fetch();

    if (!$config || !$config['grupo_whatsapp_id']) {
        echo json_encode(['error' => "Tipo '{$tipo}' não configurado"]);
        exit;
    }

    $grpStmt = $db->prepare("SELECT * FROM whatsapp_grupos WHERE id=? AND ativo=1");
    $grpStmt->execute([$config['grupo_whatsapp_id']]);
    $grupo = $grpStmt->fetch();

    if (!$grupo) {
        echo json_encode(['error' => 'Grupo não encontrado']);
        exit;
    }

    // Data da SEXTA-FEIRA da semana atual
    $sextaFeira = date('Y-m-d', strtotime('friday this week'));
    $sextaFormatado = date('d/m/Y', strtotime($sextaFeira));
    $semanaInicio = date('Y-m-d', strtotime('monday this week'));

    $msg = '';

    if ($tipo === 'bar') {
        $stmt = $db->prepare("SELECT eb.*, u1.name as nome1, u2.name as nome2 FROM escala_bar eb JOIN users u1 ON u1.id=eb.user1_id JOIN users u2 ON u2.id=eb.user2_id WHERE eb.semana_inicio=? AND eb.enviado=0");
        $stmt->execute([$semanaInicio]);
        $escala = $stmt->fetch();

        if (!$escala) {
            echo json_encode(['skip' => 'Sem escala do bar para esta semana ou já enviada']);
            exit;
        }

        $msg = "🍺 *Escala do Bar - Sexta-feira {$sextaFormatado}*\n\n";
        $msg .= "👤 {$escala['nome1']}\n👤 {$escala['nome2']}\n";
        if ($escala['observacao']) $msg .= "\n📝 {$escala['observacao']}";
        $msg .= "\n\nContamos com vocês! 🤘";

        $db->prepare("UPDATE escala_bar SET enviado=1, enviado_em=NOW() WHERE id=?")->execute([$escala['id']]);

    } elseif ($tipo === 'churrasco') {
        // Usa ordem_envio para o rodízio
        $grupos = $db->query("SELECT * FROM churrasco_grupos WHERE ativo=1 ORDER BY ordem_envio ASC")->fetchAll();

        if (empty($grupos)) {
            echo json_encode(['skip' => 'Sem grupos de churrasco']);
            exit;
        }

        $ultimaEc = $db->query("SELECT grupo_id FROM escala_churrasco ORDER BY id DESC LIMIT 1")->fetchColumn();

        $proximoIdx = 0;
        if ($ultimaEc) {
            foreach ($grupos as $i => $g) {
                if ($g['id'] == $ultimaEc) {
                    $proximoIdx = ($i + 1) % count($grupos);
                    break;
                }
            }
        }

        $grupoCh = $grupos[$proximoIdx];
        $stmtM = $db->prepare("SELECT u.name FROM churrasco_grupo_membros cgm JOIN users u ON u.id=cgm.user_id WHERE cgm.grupo_id=? ORDER BY u.name");
        $stmtM->execute([$grupoCh['id']]);
        $membros = $stmtM->fetchAll(PDO::FETCH_COLUMN);

        if (empty($membros)) {
            echo json_encode(['skip' => 'Grupo sem membros']);
            exit;
        }

        $msg = "🔥 *Escala do Churrasco - Sexta-feira {$sextaFormatado}*\n";
        $msg .= "👥 Grupo: {$grupoCh['nome']}\n\n";
        foreach ($membros as $nome) $msg .= "👤 {$nome}\n";
        $msg .= "\nPreparar o churrasco! 🥩🤘";

        $check = $db->prepare("SELECT id FROM escala_churrasco WHERE semana_inicio = ?");
        $check->execute([$semanaInicio]);

        if (!$check->fetch()) {
            $db->prepare("INSERT INTO escala_churrasco (semana_inicio, grupo_id, enviado, enviado_em) VALUES (?,?,1,NOW())")
               ->execute([$semanaInicio, $grupoCh['id']]);
        }

    } elseif ($tipo === 'limpeza') {
        // Buscar configurações da enquete no banco
        $configEnquete = $db->query("SELECT enquete_pergunta, enquete_opcoes FROM escalas_tipos WHERE nome='limpeza' LIMIT 1")->fetch();

        $pergunta = $configEnquete['enquete_pergunta'] ?? "🧹 *Quarta - dia de limpeza da sede!*\n\nVocê consegue ajudar?";
        $opcoes = [];

        if ($configEnquete['enquete_opcoes']) {
            $opcoes = json_decode($configEnquete['enquete_opcoes'], true);
        }

        if (empty($opcoes)) {
            $opcoes = ["✅ Estou lá!", "❌ Não vou conseguir", "⚠️ Vou atrasar"];
        }

        $res = $evo->enviarEnquete($grupo['group_id'], $pergunta, $opcoes);
        $ok = isset($res['key']['id']) || isset($res['messageTimestamp']);
        echo json_encode(['success' => $ok, 'tipo' => $tipo, 'data_sexta' => $sextaFormatado, 'response' => $res]);
        exit;
    }

    if ($msg) {
        $res = $evo->enviarGrupo($grupo['group_id'], $msg);
        $ok  = isset($res['key']['id']) || isset($res['messageTimestamp']);
        echo json_encode(['success' => $ok, 'tipo' => $tipo, 'data_sexta' => $sextaFormatado, 'response' => $res]);
    }

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}