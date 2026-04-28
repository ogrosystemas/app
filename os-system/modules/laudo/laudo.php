<?php
require_once '../../config/config.php';
checkAuth();

$mensagem     = $_SESSION['mensagem']      ?? null;
$mensagemErro = $_SESSION['mensagem_erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['mensagem_erro']);

$laudos     = [];
$erroTabela = false;

try {
    $laudos = $db->query(
        "SELECT lt.*, os.numero_os, os.status as os_status,
                c.nome as cliente_nome,
                m.modelo as moto_modelo, m.placa,
                u.nome as tecnico_nome
         FROM laudos_tecnicos lt
         JOIN ordens_servico os ON lt.os_id = os.id
         JOIN clientes c        ON os.cliente_id = c.id
         JOIN motos m           ON os.moto_id    = m.id
         LEFT JOIN usuarios u   ON lt.created_by = u.id
         ORDER BY lt.created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $laudos     = [];
    $erroTabela = true;
}

$statusVeiculoLabel = [
    'apta'             => 'Apta para uso',
    'em_revisao'       => 'Em revisão',
    'aguardando_pecas' => 'Aguardando peças',
    'inapta'           => 'Inapta',
];
$statusVeiculoColor = [
    'apta'             => '#22c55e',
    'em_revisao'       => '#f59e0b',
    'aguardando_pecas' => '#f97316',
    'inapta'           => '#ef4444',
];
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Relatórios Técnicos</div>
  <div class="topbar-actions">
    <a href="../os/os.php" class="btn-os btn-os-primary">
      <i class="ph-bold ph-plus-circle"></i> Novo Laudo (via OS)
    </a>
  </div>
</header>

<main class="os-content">

<?php if ($erroTabela || $mensagemErro): ?>
<div class="os-alert os-alert-danger" style="margin-bottom:20px">
  <i class="ph-bold ph-warning-circle"></i>
  <div>
    <strong>Tabela não encontrada.</strong>
    Execute o script <code>database_patch_v3.sql</code> no banco de dados do servidor para ativar este módulo.
  </div>
</div>
<?php endif; ?>

<?php if ($mensagem): ?>
<div class="os-alert os-alert-success" style="margin-bottom:20px">
  <i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?>
</div>
<?php endif; ?>

<div class="os-card">
  <div class="os-card-body" style="padding:0">
    <table class="os-table">
      <thead>
        <tr>
          <th>OS</th>
          <th>Cliente</th>
          <th>Moto / Placa</th>
          <th>Tipo Manutenção</th>
          <th>Status Veículo</th>
          <th>Técnico</th>
          <th>Data</th>
          <th style="text-align:center">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($laudos)): ?>
        <tr>
          <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px">
            <i class="ph-bold ph-clipboard-text" style="font-size:2rem;display:block;margin-bottom:8px"></i>
            Nenhum relatório técnico gerado ainda.<br>
            <small>Acesse uma Ordem de Serviço e clique em <strong>Relatório Técnico</strong>.</small>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($laudos as $l): ?>
        <tr>
          <td><strong><?= htmlspecialchars($l['numero_os']) ?></strong></td>
          <td><?= htmlspecialchars($l['cliente_nome']) ?></td>
          <td>
            <?= htmlspecialchars($l['moto_modelo']) ?>
            <span style="color:var(--text-muted);font-size:.8rem"><?= htmlspecialchars($l['placa']) ?></span>
          </td>
          <td>
            <span class="os-badge" style="background:rgba(99,102,241,.15);color:#818cf8;border:1px solid rgba(99,102,241,.25)">
              <?= $l['tipo_manutencao'] === 'preventiva' ? 'Preventiva' : 'Corretiva' ?>
            </span>
          </td>
          <td>
            <?php $sv = $l['status_veiculo']; ?>
            <span style="color:<?= $statusVeiculoColor[$sv] ?? '#94a3b8' ?>;font-weight:600;font-size:.82rem">
              <i class="ph-bold ph-circle" style="font-size:.5rem;vertical-align:middle"></i>
              <?= htmlspecialchars($statusVeiculoLabel[$sv] ?? $sv) ?>
            </span>
          </td>
          <td style="color:var(--text-muted);font-size:.82rem"><?= htmlspecialchars($l['tecnico_nome'] ?? '—') ?></td>
          <td style="color:var(--text-muted);font-size:.82rem"><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
          <td style="text-align:center">
            <div style="display:flex;gap:6px;justify-content:center">
              <a href="form_laudo.php?id=<?= $l['id'] ?>" class="btn-os btn-os-ghost" style="padding:5px 10px" title="Editar">
                <i class="ph-bold ph-pencil-simple"></i>
              </a>
              <a href="gerar_laudo_pdf.php?id=<?= $l['id'] ?>" target="_blank"
                 class="btn-os" style="padding:5px 10px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444" title="Gerar PDF">
                <i class="ph-bold ph-file-pdf"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
<?php include '../../includes/footer.php'; ?>
