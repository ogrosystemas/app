<?php
require_once '../../config/config.php';
checkAuth();

// ── Resolver OS ───────────────────────────────────────────────────────────────
$os_id    = (int)($_GET['os_id'] ?? 0);
$laudo_id = (int)($_GET['id']    ?? 0);

$laudo  = null;
$secoes = [];

// Verificar se tabela existe
try {
    $db->query("SELECT 1 FROM laudos_tecnicos LIMIT 1");
} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = 'Execute o script database_patch_v3.sql no banco de dados para ativar o módulo de Relatório Técnico.';
    header('Location: laudo.php'); exit;
}

if ($laudo_id) {
    // Editar laudo existente
    $laudo = $db->prepare("SELECT * FROM laudos_tecnicos WHERE id = ?")->execute([$laudo_id]) ? null : null;
    $stmt  = $db->prepare("SELECT * FROM laudos_tecnicos WHERE id = ?");
    $stmt->execute([$laudo_id]);
    $laudo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($laudo) {
        $os_id = (int)$laudo['os_id'];
        $stmt2 = $db->prepare("SELECT * FROM laudo_secoes WHERE laudo_id = ? ORDER BY secao, ordem");
        $stmt2->execute([$laudo_id]);
        $secoes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!$os_id) { header('Location: laudo.php'); exit; }

// Buscar OS (somente não cancelada)
$stmtOS = $db->prepare(
    "SELECT os.*, c.nome as cliente_nome, c.telefone, c.cpf_cnpj,
            m.modelo as moto_modelo, m.marca, m.placa, m.ano, m.km_atual,
            u.nome as tecnico_nome
     FROM ordens_servico os
     JOIN clientes c  ON os.cliente_id = c.id
     JOIN motos m     ON os.moto_id    = m.id
     LEFT JOIN usuarios u ON os.created_by = u.id
     WHERE os.id = ? AND os.status != 'cancelada'"
);
$stmtOS->execute([$os_id]);
$os = $stmtOS->fetch(PDO::FETCH_ASSOC);
if (!$os) { header('Location: ../os/os.php'); exit; }

// ── Salvar ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_laudo'])) {
    csrfVerify();

    $dados = [
        'os_id'             => $os_id,
        'tipo_manutencao'   => $_POST['tipo_manutencao'] ?? 'corretiva',
        'objetivo'          => trim($_POST['objetivo'] ?? ''),
        'km_revisao'        => (int)($_POST['km_revisao'] ?? 0) ?: null,
        'conclusao_tecnica' => trim($_POST['conclusao_tecnica'] ?? ''),
        'status_veiculo'    => $_POST['status_veiculo'] ?? 'apta',
        'created_by'        => $_SESSION['usuario_id'],
    ];

    if ($laudo_id) {
        $db->prepare("UPDATE laudos_tecnicos SET tipo_manutencao=?, objetivo=?, km_revisao=?, conclusao_tecnica=?, status_veiculo=? WHERE id=?")
           ->execute([$dados['tipo_manutencao'], $dados['objetivo'], $dados['km_revisao'], $dados['conclusao_tecnica'], $dados['status_veiculo'], $laudo_id]);
        $db->prepare("DELETE FROM laudo_secoes WHERE laudo_id = ?")->execute([$laudo_id]);
        $lid = $laudo_id;
    } else {
        // Verificar se já existe laudo para esta OS
        $existente = $db->prepare("SELECT id FROM laudos_tecnicos WHERE os_id = ?");
        $existente->execute([$os_id]);
        $row = $existente->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $lid = $row['id'];
            $db->prepare("UPDATE laudos_tecnicos SET tipo_manutencao=?, objetivo=?, km_revisao=?, conclusao_tecnica=?, status_veiculo=? WHERE id=?")
               ->execute([$dados['tipo_manutencao'], $dados['objetivo'], $dados['km_revisao'], $dados['conclusao_tecnica'], $dados['status_veiculo'], $lid]);
            $db->prepare("DELETE FROM laudo_secoes WHERE laudo_id = ?")->execute([$lid]);
        } else {
            $db->prepare("INSERT INTO laudos_tecnicos (os_id, tipo_manutencao, objetivo, km_revisao, conclusao_tecnica, status_veiculo, created_by) VALUES (?,?,?,?,?,?,?)")
               ->execute(array_values($dados));
            $lid = (int)$db->lastInsertId();
        }
    }

    // Salvar itens das seções
    $itensSecao = $_POST['secao_itens'] ?? [];
    $stmtIns = $db->prepare("INSERT INTO laudo_secoes (laudo_id, secao, item, resultado, observacao, ordem) VALUES (?,?,?,?,?,?)");
    foreach ($itensSecao as $secNum => $itens) {
        foreach ($itens as $ordem => $item) {
            if (empty(trim($item['item'] ?? ''))) continue;
            $stmtIns->execute([
                $lid,
                (int)$secNum,
                trim($item['item']),
                $item['resultado'] ?? 'ok',
                trim($item['observacao'] ?? ''),
                (int)$ordem,
            ]);
        }
    }

    $_SESSION['mensagem'] = 'Relatório técnico salvo com sucesso!';
    header('Location: form_laudo.php?id=' . $lid);
    exit;
}

// ── Itens padrão por seção ────────────────────────────────────────────────────
$secaoNomes = [
    1 => 'Motor / Lubrificação',
    2 => 'Arrefecimento',
    3 => 'Alimentação',
    4 => 'Transmissão',
    5 => 'Freios',
    6 => 'Rodas / Vedações',
    7 => 'Suspensão / Direção',
    8 => 'Comandos',
    9 => 'Serviços Complementares',
];
$itensPadrao = [
    1 => ['Nível de óleo do motor','Qualidade do óleo','Filtro de óleo','Vedações externas','Respiro do motor','Corrente/tensor de distribuição'],
    2 => ['Nível de fluido de arrefecimento','Mangueiras e conexões','Tampas e radiador','Termostato'],
    3 => ['Filtro de ar','Carburador / Injeção','Bicos injetores','Duto de admissão','Tanque de combustível'],
    4 => ['Embreagem','Corrente de transmissão (tensão/desgaste)','Coroa e pinhão','Câmbio (funcionamento)','Cabo de embreagem'],
    5 => ['Fluido de freio dianteiro','Fluido de freio traseiro','Pastilhas/lonas dianteiras','Pastilhas/lonas traseiras','Discos de freio','Mangueiras de freio','ABS (se aplicável)'],
    6 => ['Pneu dianteiro (calibragem/desgaste)','Pneu traseiro (calibragem/desgaste)','Rolamentos de roda','Raios (se aplicável)','Vedadores de garfo'],
    7 => ['Garfo dianteiro (vazamentos)','Amortecedor traseiro','Rolamento de direção','Alinhamento de direção'],
    8 => ['Cabo do acelerador','Cabo do freio traseiro','Manetes e punhos','Espelhos','Buzina','Iluminação geral','Sinaleiros'],
    9 => ['Lavagem / higienização','Lubrificação de cabos','Aperto geral de parafusos','Regulagem de válvulas','Sincronização de carburadores'],
];

// Organizar seções existentes por número
$secoesSalvas = [];
foreach ($secoes as $s) {
    $secoesSalvas[$s['secao']][] = $s;
}

$mensagem = $_SESSION['mensagem'] ?? null;
unset($_SESSION['mensagem']);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Relatório Técnico — <?= htmlspecialchars($os['numero_os']) ?></div>
  <div class="topbar-actions">
    <a href="../os/os_detalhes.php?id=<?= $os_id ?>" class="btn-os btn-os-ghost">
      <i class="ph-bold ph-arrow-left"></i> Voltar para OS
    </a>
    <?php if ($laudo_id): ?>
    <a href="gerar_laudo_pdf.php?id=<?= $laudo_id ?>" target="_blank" class="btn-os" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ef4444">
      <i class="ph-bold ph-file-pdf"></i> Gerar PDF
    </a>
    <?php endif; ?>
  </div>
</header>

<main class="os-content">

<?php if ($mensagem): ?>
<div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="POST" id="formLaudo">
  <input type="hidden" name="salvar_laudo" value="1">
  <?= csrfField() ?>

  <!-- ── IDENTIFICAÇÃO ─────────────────────────────────────── -->
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-body">
      <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px;color:var(--accent);text-transform:uppercase;font-size:.78rem;letter-spacing:.06em">
        <i class="ph-bold ph-identification-card"></i> Identificação
      </h6>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
        <div class="os-form-group">
          <label class="os-label">Número da OS</label>
          <input type="text" class="os-input" value="<?= htmlspecialchars($os['numero_os']) ?>" readonly style="background:var(--bg-muted)">
        </div>
        <div class="os-form-group">
          <label class="os-label">Cliente</label>
          <input type="text" class="os-input" value="<?= htmlspecialchars($os['cliente_nome']) ?>" readonly style="background:var(--bg-muted)">
        </div>
        <div class="os-form-group">
          <label class="os-label">Telefone</label>
          <input type="text" class="os-input" value="<?= htmlspecialchars($os['telefone'] ?? '') ?>" readonly style="background:var(--bg-muted)">
        </div>
        <div class="os-form-group">
          <label class="os-label">Modelo da Moto</label>
          <input type="text" class="os-input" value="<?= htmlspecialchars(($os['marca']??'').' '.$os['moto_modelo']) ?>" readonly style="background:var(--bg-muted)">
        </div>
        <div class="os-form-group">
          <label class="os-label">Placa</label>
          <input type="text" class="os-input" value="<?= htmlspecialchars($os['placa']) ?>" readonly style="background:var(--bg-muted)">
        </div>
        <div class="os-form-group">
          <label class="os-label">KM no Laudo</label>
          <input type="number" name="km_revisao" class="os-input" min="0" placeholder="Ex: 15000"
                 value="<?= htmlspecialchars($laudo['km_revisao'] ?? $os['km_atual'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── MANUTENÇÃO ─────────────────────────────────────────── -->
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-body">
      <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px;color:var(--accent);text-transform:uppercase;font-size:.78rem;letter-spacing:.06em">
        <i class="ph-bold ph-wrench"></i> Manutenção
      </h6>
      <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px">
        <div class="os-form-group">
          <label class="os-label">Tipo de Manutenção *</label>
          <select name="tipo_manutencao" class="os-input" required>
            <option value="preventiva" <?= ($laudo['tipo_manutencao'] ?? '') === 'preventiva' ? 'selected' : '' ?>>Preventiva</option>
            <option value="corretiva"  <?= ($laudo['tipo_manutencao'] ?? 'corretiva') === 'corretiva'  ? 'selected' : '' ?>>Corretiva</option>
          </select>
        </div>
        <div class="os-form-group">
          <label class="os-label">Objetivo da Manutenção</label>
          <input type="text" name="objetivo" class="os-input" placeholder="Descreva o objetivo principal..."
                 value="<?= htmlspecialchars($laudo['objetivo'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── INSPEÇÃO — 9 SEÇÕES ─────────────────────────────────── -->
  <?php foreach ($secaoNomes as $num => $nomeSecao):
    $itensDaSecao = $secoesSalvas[$num] ?? [];
    $padrao = $itensPadrao[$num] ?? [];
  ?>
  <div class="os-card" style="margin-bottom:16px" id="card-secao-<?= $num ?>">
    <div class="os-card-body" style="padding-bottom:10px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <h6 style="font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);text-transform:uppercase;font-size:.78rem;letter-spacing:.06em;margin:0">
          <i class="ph-bold ph-list-checks"></i> <?= (int)$num ?>. <?= htmlspecialchars($nomeSecao) ?>
        </h6>
        <button type="button" class="btn-os btn-os-ghost" style="padding:4px 10px;font-size:.75rem"
                onclick="adicionarItem(<?= $num ?>)">
          <i class="ph-bold ph-plus"></i> Item
        </button>
      </div>

      <!-- Cabeçalho da tabela -->
      <div style="display:grid;grid-template-columns:3fr 140px 2fr 32px;gap:6px;padding:0 4px;margin-bottom:4px">
        <span style="font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Item Inspecionado</span>
        <span style="font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Resultado</span>
        <span style="font-size:.7rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Observação</span>
        <span></span>
      </div>

      <div class="secao-itens" id="secao-<?= $num ?>-itens">
        <?php
        // Mostrar itens salvos ou padrão vazio
        $linhas = !empty($itensDaSecao) ? $itensDaSecao : [];
        foreach ($linhas as $idx => $it):
        ?>
        <div class="secao-linha" style="display:grid;grid-template-columns:3fr 140px 2fr 32px;gap:6px;margin-bottom:6px">
          <input type="text" name="secao_itens[<?= $num ?>][<?= $idx ?>][item]"
                 class="os-input" style="padding:6px 10px;font-size:.83rem"
                 value="<?= htmlspecialchars($it['item']) ?>" placeholder="Descrição do item" required>
          <select name="secao_itens[<?= $num ?>][<?= $idx ?>][resultado]" class="os-input resultado-sel" style="padding:5px 8px;font-size:.8rem">
            <option value="ok"            <?= ($it['resultado']??'') === 'ok'            ? 'selected':'' ?>>✅ OK</option>
            <option value="atencao"       <?= ($it['resultado']??'') === 'atencao'       ? 'selected':'' ?>>⚠️ Atenção</option>
            <option value="critico"       <?= ($it['resultado']??'') === 'critico'       ? 'selected':'' ?>>🔴 Crítico</option>
            <option value="substituido"   <?= ($it['resultado']??'') === 'substituido'   ? 'selected':'' ?>>🔧 Substituído</option>
            <option value="nao_aplicavel" <?= ($it['resultado']??'') === 'nao_aplicavel' ? 'selected':'' ?>>— N/A</option>
          </select>
          <input type="text" name="secao_itens[<?= $num ?>][<?= $idx ?>][observacao]"
                 class="os-input" style="padding:6px 10px;font-size:.83rem"
                 value="<?= htmlspecialchars($it['observacao'] ?? '') ?>" placeholder="Observação (opcional)">
          <button type="button" onclick="removerLinha(this)" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center">
            <i class="ph-bold ph-trash" style="font-size:.85rem"></i>
          </button>
        </div>
        <?php endforeach; ?>

        <?php if (empty($linhas)): ?>
        <!-- Linha vazia inicial para cada seção -->
        <div class="secao-linha" style="display:grid;grid-template-columns:3fr 140px 2fr 32px;gap:6px;margin-bottom:6px">
          <input type="text" name="secao_itens[<?= $num ?>][0][item]"
                 class="os-input" style="padding:6px 10px;font-size:.83rem"
                 value="" placeholder="Descrição do item">
          <select name="secao_itens[<?= $num ?>][0][resultado]" class="os-input resultado-sel" style="padding:5px 8px;font-size:.8rem">
            <option value="ok">✅ OK</option>
            <option value="atencao">⚠️ Atenção</option>
            <option value="critico">🔴 Crítico</option>
            <option value="substituido">🔧 Substituído</option>
            <option value="nao_aplicavel">— N/A</option>
          </select>
          <input type="text" name="secao_itens[<?= $num ?>][0][observacao]"
                 class="os-input" style="padding:6px 10px;font-size:.83rem"
                 value="" placeholder="Observação (opcional)">
          <button type="button" onclick="removerLinha(this)" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center">
            <i class="ph-bold ph-trash" style="font-size:.85rem"></i>
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Itens padrão rápidos -->
      <?php if (!empty($padrao)): ?>
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border)">
        <span style="font-size:.7rem;color:var(--text-muted);margin-right:6px">Atalhos:</span>
        <?php foreach ($padrao as $p): ?>
        <button type="button" class="btn-os" style="padding:2px 8px;font-size:.7rem;margin:2px;background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text-muted)"
                onclick="adicionarItemRapido(<?= $num ?>, <?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
          + <?= htmlspecialchars($p) ?>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- ── FINALIZAÇÃO ────────────────────────────────────────── -->
  <div class="os-card" style="margin-bottom:24px">
    <div class="os-card-body">
      <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px;color:var(--accent);text-transform:uppercase;font-size:.78rem;letter-spacing:.06em">
        <i class="ph-bold ph-seal-check"></i> Finalização
      </h6>
      <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
        <div class="os-form-group">
          <label class="os-label">Conclusão Técnica *</label>
          <textarea name="conclusao_tecnica" class="os-input" rows="4" required
                    placeholder="Descreva a conclusão técnica do laudo..."><?= htmlspecialchars($laudo['conclusao_tecnica'] ?? '') ?></textarea>
        </div>
        <div class="os-form-group">
          <label class="os-label">Status do Veículo *</label>
          <select name="status_veiculo" class="os-input" required style="height:auto">
            <?php foreach ([
              'apta'             => '✅ Apta para uso',
              'em_revisao'       => '⚠️ Em revisão',
              'aguardando_pecas' => '🔧 Aguardando peças',
              'inapta'           => '🔴 Inapta',
            ] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($laudo['status_veiculo'] ?? 'apta') === $val ? 'selected' : '' ?>>
              <?= $label ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div style="margin-top:16px;padding:14px;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;font-size:.8rem;color:var(--text-muted)">
            <i class="ph-bold ph-info"></i>
            O status será impresso em destaque no PDF do laudo.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── AÇÕES ──────────────────────────────────────────────── -->
  <div style="display:flex;gap:12px;justify-content:flex-end;padding-bottom:32px">
    <a href="../os/os_detalhes.php?id=<?= $os_id ?>" class="btn-os btn-os-ghost">Cancelar</a>
    <button type="submit" class="btn-os btn-os-primary">
      <i class="ph-bold ph-floppy-disk"></i> Salvar Relatório
    </button>
    <?php if ($laudo_id): ?>
    <a href="gerar_laudo_pdf.php?id=<?= $laudo_id ?>" target="_blank" class="btn-os" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ef4444">
      <i class="ph-bold ph-file-pdf"></i> Gerar PDF
    </a>
    <?php endif; ?>
  </div>

</form>

</main>

<script>
// Controle de índice por seção
var secaoIdx = {};
<?php foreach ($secaoNomes as $num => $nome): ?>
secaoIdx[<?= $num ?>] = <?= max(count($secoesSalvas[$num] ?? [['x']]), 1) ?>;
<?php endforeach; ?>

function adicionarItem(secNum) {
    var container = document.getElementById('secao-' + secNum + '-itens');
    var idx = secaoIdx[secNum]++;
    var div = document.createElement('div');
    div.className = 'secao-linha';
    div.style = 'display:grid;grid-template-columns:3fr 140px 2fr 32px;gap:6px;margin-bottom:6px';
    div.innerHTML =
        '<input type="text" name="secao_itens[' + secNum + '][' + idx + '][item]" class="os-input" style="padding:6px 10px;font-size:.83rem" placeholder="Descrição do item">' +
        '<select name="secao_itens[' + secNum + '][' + idx + '][resultado]" class="os-input resultado-sel" style="padding:5px 8px;font-size:.8rem">' +
        '<option value="ok">✅ OK</option>' +
        '<option value="atencao">⚠️ Atenção</option>' +
        '<option value="critico">🔴 Crítico</option>' +
        '<option value="substituido">🔧 Substituído</option>' +
        '<option value="nao_aplicavel">— N/A</option>' +
        '</select>' +
        '<input type="text" name="secao_itens[' + secNum + '][' + idx + '][observacao]" class="os-input" style="padding:6px 10px;font-size:.83rem" placeholder="Observação (opcional)">' +
        '<button type="button" onclick="removerLinha(this)" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center">' +
        '<i class="ph-bold ph-trash" style="font-size:.85rem"></i></button>';
    container.appendChild(div);
    div.querySelector('input').focus();
}

function adicionarItemRapido(secNum, nome) {
    var container = document.getElementById('secao-' + secNum + '-itens');
    // Verificar se já existe item com esse nome
    var inputs = container.querySelectorAll('input[type=text]');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].name.includes('[item]') && inputs[i].value.trim() === nome) {
            inputs[i].style.outline = '2px solid var(--accent)';
            setTimeout(function(el){ el.style.outline=''; }, 1500, inputs[i]);
            return;
        }
    }
    // Verificar se a última linha está vazia para reutilizar
    var linhas = container.querySelectorAll('.secao-linha');
    var ultima = linhas[linhas.length - 1];
    if (ultima) {
        var inputItem = ultima.querySelector('input[type=text]');
        if (inputItem && inputItem.value.trim() === '') {
            inputItem.value = nome;
            return;
        }
    }
    adicionarItem(secNum);
    setTimeout(function() {
        var linhas2 = container.querySelectorAll('.secao-linha');
        var nova = linhas2[linhas2.length - 1];
        if (nova) nova.querySelector('input[type=text]').value = nome;
    }, 20);
}

function removerLinha(btn) {
    var linha = btn.closest('.secao-linha');
    var container = linha.parentElement;
    var linhas = container.querySelectorAll('.secao-linha');
    if (linhas.length <= 1) {
        // Limpar campos ao invés de remover
        linha.querySelectorAll('input[type=text]').forEach(function(i){ i.value = ''; });
        linha.querySelector('select').value = 'ok';
    } else {
        linha.remove();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
