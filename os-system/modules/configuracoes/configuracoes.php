<?php
require_once '../../config/config.php';
require_once '../../config/licenca.php';
checkAuth(['admin']);

// ── Licença ───────────────────────────────────────────────────────────────────
$licStatus    = licenca_status($db);
$licErro      = $_SESSION['licenca_erro'] ?? null;
unset($_SESSION['licenca_erro']);

$config_file = __DIR__ . '/../../config/sistema.php';
$cfg = [];
if (file_exists($config_file)) {
    $cfg = include $config_file;
    if (!is_array($cfg)) $cfg = [];
}

// Defaults
$cfg = array_merge([
    'nome_sistema'   => 'OS-System',
    'nome_oficina'   => 'Oficina de Motos',
    'telefone'       => '',
    'email'          => '',
    'endereco'       => '',
    'cnpj'           => '',
    'cor_primaria'   => '#f59e0b',
    'logo_path'      => '',
], $cfg);

$mensagem = $erro = '';

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $novo = [
        'nome_sistema'  => trim($_POST['nome_sistema']  ?? 'OS-System'),
        'nome_oficina'  => trim($_POST['nome_oficina']  ?? ''),
        'telefone'      => trim($_POST['telefone']      ?? ''),
        'email'         => trim($_POST['email']         ?? ''),
        'endereco'      => trim($_POST['endereco']      ?? ''),
        'cnpj'          => trim($_POST['cnpj']          ?? ''),
        'cor_primaria'  => trim($_POST['cor_primaria']  ?? '#f59e0b'),
        'logo_path'     => $cfg['logo_path'],
    ];

    // Upload logo
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext          = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowedExts  = ['jpg','jpeg','png','webp','svg'];
        $allowedMimes = ['image/jpeg','image/png','image/webp','image/svg+xml'];

        // Validate file size (max 5MB)
        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
            $erro = 'Arquivo muito grande. Máximo 5MB.';
        } elseif (!in_array($ext, $allowedExts)) {
            $erro = 'Extensão inválida. Use JPG, PNG, WebP ou SVG.';
        } else {
            // Validate real MIME type using finfo (ignores fake $_FILES['type'])
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($_FILES['logo']['tmp_name']);
            if (!in_array($realMime, $allowedMimes)) {
                $erro = 'Tipo de arquivo inválido. Apenas imagens são aceitas.';
            } else {
                $dest_dir = __DIR__ . '/../../assets/images/';
                if (!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);
                // Randomize filename to prevent overwrite attacks
                $filename = 'logo_sistema_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest_dir . $filename)) {
                    $novo['logo_path'] = 'assets/images/' . $filename;
                } else {
                    $erro = 'Falha ao salvar a logo. Verifique as permissões da pasta assets/images/.';
                }
            }
        }
    }

    if (!$erro) {
        $php_content  = "<?php\nreturn " . var_export($novo, true) . ";\n";
        if (file_put_contents($config_file, $php_content) !== false) {
            $cfg      = $novo;
            $mensagem = 'Configurações salvas com sucesso!';
        } else {
            $erro = 'Não foi possível salvar. Verifique as permissões da pasta config/.';
        }
    }
}

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Configurações <span style="color:var(--accent)">·</span> Sistema</div>
</header>

<main class="os-content">
<div style="max-width:820px;margin:0 auto">

<?php if ($mensagem): ?>
<div class="os-alert os-alert-success" style="margin-bottom:20px">
  <i class="ph-bold ph-check-circle"></i> <?= htmlspecialchars($mensagem) ?>
</div>
<?php endif; ?>
<?php if ($erro): ?>
<div class="os-alert os-alert-danger" style="margin-bottom:20px">
  <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="salvar" value="1">

  <!-- Grid: Identidade + Licença lado a lado -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;align-items:start">

  <!-- Identidade -->
  <div class="os-card">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-buildings"></i> Identidade do Sistema</div>
    </div>
    <div class="os-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
        <div class="os-form-group">
          <label class="os-label">Nome do Sistema</label>
          <input type="text" name="nome_sistema" class="os-input"
                 value="<?= htmlspecialchars($cfg['nome_sistema']) ?>"
                 placeholder="OS-System">
          <small style="color:var(--text-muted);font-size:.73rem;margin-top:4px;display:block">Aparece na navbar e no topo das páginas</small>
        </div>
        <div class="os-form-group">
          <label class="os-label">Nome da Oficina</label>
          <input type="text" name="nome_oficina" class="os-input"
                 value="<?= htmlspecialchars($cfg['nome_oficina']) ?>"
                 placeholder="Oficina de Motos XYZ">
        </div>
        <div class="os-form-group">
          <label class="os-label">CNPJ</label>
          <input type="text" name="cnpj" class="os-input"
                 value="<?= htmlspecialchars($cfg['cnpj']) ?>"
                 placeholder="00.000.000/0001-00">
        </div>
        <div class="os-form-group">
          <label class="os-label">Telefone</label>
          <input type="text" name="telefone" class="os-input"
                 value="<?= htmlspecialchars($cfg['telefone']) ?>"
                 placeholder="(00) 00000-0000">
        </div>
        <div class="os-form-group" style="grid-column:1/-1">
          <label class="os-label">E-mail</label>
          <input type="email" name="email" class="os-input"
                 value="<?= htmlspecialchars($cfg['email']) ?>"
                 placeholder="contato@suaoficina.com.br">
        </div>
        <div class="os-form-group" style="grid-column:1/-1">
          <label class="os-label">Endereço</label>
          <input type="text" name="endereco" class="os-input"
                 value="<?= htmlspecialchars($cfg['endereco']) ?>"
                 placeholder="Rua, número, bairro, cidade - estado">
        </div>
      </div>
    </div>
  </div>

  <!-- Licença (lado direito) -->
  <?php
  $licCorBg    = 'rgba(34,197,94,.08)';
  $licCorBorda = 'rgba(34,197,94,.25)';
  $licCorIcon  = '#22c55e';
  $licIcone    = 'ph-seal-check';
  $licTitulo   = 'Licença Ativa';

  if ($licStatus['status'] === 'trial') {
      if ($licStatus['dias_restantes'] <= 5) {
          $licCorBg    = 'rgba(239,68,68,.08)';
          $licCorBorda = 'rgba(239,68,68,.25)';
          $licCorIcon  = '#ef4444';
          $licIcone    = 'ph-warning-circle';
          $licTitulo   = 'Trial Expirando';
      } else {
          $licCorBg    = 'rgba(245,158,11,.08)';
          $licCorBorda = 'rgba(245,158,11,.25)';
          $licCorIcon  = '#f59e0b';
          $licIcone    = 'ph-clock-countdown';
          $licTitulo   = 'Período de Avaliação';
      }
  } elseif ($licStatus['status'] === 'expirado') {
      $licCorBg    = 'rgba(239,68,68,.08)';
      $licCorBorda = 'rgba(239,68,68,.3)';
      $licCorIcon  = '#ef4444';
      $licIcone    = 'ph-lock';
      $licTitulo   = 'Licença Expirada';
  }
  ?>
  <div class="os-card" style="border:1px solid <?= $licCorBorda ?>;background:<?= $licCorBg ?>">
    <div class="os-card-header" style="border-bottom:1px solid <?= $licCorBorda ?>">
      <div class="os-card-title" style="color:<?= $licCorIcon ?>">
        <i class="ph-bold <?= $licIcone ?>"></i> <?= $licTitulo ?>
      </div>
    </div>
    <div class="os-card-body">

      <!-- Status pills -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px">
        <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Status</div>
          <div style="font-weight:700;font-size:.95rem;color:<?= $licCorIcon ?>">
            <?php if ($licStatus['status'] === 'ativa'): ?>Ativa
            <?php elseif ($licStatus['status'] === 'trial'): ?>Trial
            <?php else: ?>Expirada<?php endif; ?>
          </div>
        </div>
        <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">
            <?= $licStatus['status'] === 'expirado' ? 'Expirou em' : 'Expira em' ?>
          </div>
          <div style="font-weight:700;font-size:.9rem;color:var(--text)">
            <?= $licStatus['expiracao']->format('d/m/Y') ?>
          </div>
        </div>
        <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;text-align:center">
          <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Dias Restantes</div>
          <div style="font-weight:700;font-size:1.3rem;color:<?= $licStatus['dias_restantes'] <= 5 ? '#ef4444' : $licCorIcon ?>">
            <?= $licStatus['dias_restantes'] ?>
          </div>
        </div>
      </div>

      <!-- Domínio -->
      <div style="margin-bottom:14px;padding:9px 12px;background:var(--bg-muted);border:1px solid var(--border);border-radius:8px;font-size:.78rem;color:var(--text-muted)">
        <i class="ph-bold ph-globe"></i> Domínio:
        <strong style="color:var(--accent);font-family:monospace;margin-left:4px"><?= htmlspecialchars($licStatus['dominio']) ?></strong>
        <br><span style="font-size:.72rem;opacity:.6;margin-left:16px">Use este domínio ao gerar a chave de licença</span>
      </div>

      <?php if ($licStatus['status'] === 'trial'): ?>
      <div style="margin-bottom:14px;padding:10px 12px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);border-radius:8px;font-size:.8rem;color:var(--text-muted)">
        <i class="ph-bold ph-info" style="color:#f59e0b"></i>
        Avaliação gratuita de <strong><?= LICENCA_TRIAL_DIAS ?> dias</strong>. Após o vencimento o acesso será bloqueado.
      </div>
      <?php endif; ?>

      <?php if ($licErro): ?>
      <div class="os-alert os-alert-danger" style="margin-bottom:12px;padding:10px 12px;font-size:.82rem">
        <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($licErro) ?>
      </div>
      <?php endif; ?>

      <!-- Ativação -->
      <form method="POST" action="ativar_licenca.php">
        <?= csrfField() ?>
        <input type="hidden" name="origem" value="config">
        <div class="os-form-group" style="margin-bottom:10px">
          <label class="os-label">Chave de Licença</label>
          <input type="text" name="chave_licenca" class="os-input"
                 placeholder="OSSYS-XXXXXXXX-XXXX-XXXX-XXXX-XXXX"
                 maxlength="34" autocomplete="off" spellcheck="false"
                 style="font-family:monospace;letter-spacing:.05em;text-transform:uppercase;font-size:.85rem">
        </div>
        <button type="submit" class="btn-os btn-os-primary" style="width:100%;justify-content:center;padding:10px">
          <i class="ph-bold ph-key"></i> Ativar Licença
        </button>
      </form>

    </div>
  </div>

  </div><!-- /grid identidade+licença -->

  <!-- Visual -->
  <div class="os-card" style="margin-bottom:20px">
    <div class="os-card-header">
      <div class="os-card-title"><i class="ph-bold ph-palette"></i> Identidade Visual</div>
    </div>
    <div class="os-card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

        <!-- Logo -->
        <div>
          <label class="os-label">Logo do Sistema</label>

          <div id="logoPreviewWrap" style="background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:16px;text-align:center;margin-bottom:12px;<?= $cfg['logo_path'] ? '' : 'display:none' ?>">
            <img id="logoPreviewImg" src="<?= $cfg['logo_path'] ? ($baseUrl.'/'.ltrim(htmlspecialchars($cfg['logo_path']),'/')) : '' ?>"
                 alt="Logo" style="max-height:80px;max-width:200px;object-fit:contain">
          </div>
          <div id="logoPlaceholderWrap" style="background:var(--bg-card2);border:2px dashed var(--border);border-radius:8px;padding:24px;text-align:center;margin-bottom:12px;color:var(--text-muted);<?= $cfg['logo_path'] ? 'display:none' : '' ?>">
            <i class="ph-bold ph-image" style="font-size:2rem;display:block;margin-bottom:6px"></i>
            <span style="font-size:.8rem">Selecione uma logo</span>
          </div>
          <input type="file" name="logo" id="logoInput" accept=".jpg,.jpeg,.png,.webp,.svg"
                 style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-size:.82rem">
          <small style="color:var(--text-muted);font-size:.73rem;margin-top:4px;display:block">JPG, PNG, WebP ou SVG · Max 5MB · Recomendado: 300×100px</small>
        </div>

        <!-- Cor -->
        <div>
          <label class="os-label">Cor Principal</label>
          <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px">
            <input type="color" name="cor_primaria" id="corPrimaria"
                   value="<?= htmlspecialchars($cfg['cor_primaria']) ?>"
                   style="width:56px;height:56px;border:2px solid var(--border);border-radius:10px;cursor:pointer;background:none;padding:2px">
            <div>
              <div id="corHex" style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($cfg['cor_primaria']) ?></div>
              <div style="font-size:.75rem;color:var(--text-muted)">Cor dos botões e destaques</div>
            </div>
          </div>

          <!-- Paletas rápidas -->
          <label class="os-label">Paletas Rápidas</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <?php
            $paletas = [
              '#f59e0b'=>'Âmbar','#3b82f6'=>'Azul','#22c55e'=>'Verde',
              '#ef4444'=>'Vermelho','#a855f7'=>'Roxo','#ec4899'=>'Rosa',
              '#06b6d4'=>'Ciano','#f97316'=>'Laranja','#64748b'=>'Cinza',
            ];
            foreach ($paletas as $hex => $nome):
            ?>
            <button type="button" onclick="setCor('<?= $hex ?>')" title="<?= $nome ?>"
                    style="width:32px;height:32px;background:<?= $hex ?>;border:2px solid <?= $hex === $cfg['cor_primaria'] ? '#fff' : 'transparent' ?>;border-radius:8px;cursor:pointer;transition:all .15s"
                    onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform=''">
            </button>
            <?php endforeach; ?>
          </div>

          <!-- Preview -->
          <div style="margin-top:16px;padding:14px;background:var(--bg-card2);border-radius:8px;border:1px solid var(--border)">
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px;font-weight:600">PREVIEW</div>
            <button type="button" id="btnPreview"
                    style="background:<?= htmlspecialchars($cfg['cor_primaria']) ?>;color:#000;border:none;border-radius:8px;padding:8px 18px;font-weight:700;font-family:'Syne',sans-serif;font-size:.85rem;cursor:default">
              Botão de Exemplo
            </button>
            <span id="badgePreview"
                  style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:rgba(0,0,0,.15);border-radius:20px;font-size:.75rem;font-weight:700;color:<?= htmlspecialchars($cfg['cor_primaria']) ?>;margin-left:10px">
              Badge
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Salvar -->
  <div style="display:flex;justify-content:flex-end;gap:12px">
    <button type="button" class="btn-os btn-os-ghost" onclick="restaurarFormulario()">
      <i class="ph-bold ph-arrow-counter-clockwise"></i> Restaurar
    </button>
    <button type="submit" class="btn-os btn-os-primary" style="padding:10px 28px;font-size:.95rem">
      <i class="ph-bold ph-floppy-disk"></i> Salvar Configurações
    </button>
  </div>

</form>
</div>

</main>

<script>
// ── Cor ─────────────────────────────────────────────────
function setCor(hex) {
  document.getElementById('corPrimaria').value = hex;
  atualizarCor(hex);
}
document.getElementById('corPrimaria').addEventListener('input', function() {
  atualizarCor(this.value);
});
function atualizarCor(hex) {
  document.getElementById('corHex').textContent = hex;
  document.getElementById('btnPreview').style.background = hex;
  document.getElementById('badgePreview').style.color = hex;
  // Atualizar border nos paleta buttons
  document.querySelectorAll('[data-paleta]').forEach(function(btn) {
    btn.style.borderColor = btn.getAttribute('data-paleta') === hex ? '#fff' : 'transparent';
  });
}

// ── Preview da logo ao selecionar arquivo ────────────────
document.getElementById('logoInput').addEventListener('change', function() {
  var file = this.files[0];
  if (!file) return;

  // Validar tipo
  var ext = file.name.split('.').pop().toLowerCase();
  if (!['jpg','jpeg','png','webp','svg'].includes(ext)) {
    Swal.fire({ title: 'Formato inválido', text: 'Use JPG, PNG, WebP ou SVG.', icon: 'warning', confirmButtonColor: '#f59e0b' });
    this.value = '';
    return;
  }

  var reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('logoPreviewImg').src = e.target.result;
    document.getElementById('logoPreviewWrap').style.display = '';
    document.getElementById('logoPlaceholderWrap').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

function restaurarFormulario() {
  Swal.fire({
    title: 'Restaurar valores?',
    text: 'Os campos voltarão aos valores salvos atualmente. Alterações não salvas serão perdidas.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    confirmButtonText: 'Sim, restaurar',
    cancelButtonText: 'Cancelar',
    background: 'var(--bg-card)',
    color: 'var(--text)',
  }).then(function(result) {
    if (!result.isConfirmed) return;

    // Restaurar campos de texto
    document.querySelector('[name="nome_sistema"]').value  = <?= json_encode($cfg['nome_sistema']) ?>;
    document.querySelector('[name="nome_oficina"]').value  = <?= json_encode($cfg['nome_oficina']) ?>;
    document.querySelector('[name="cnpj"]').value          = <?= json_encode($cfg['cnpj']) ?>;
    document.querySelector('[name="telefone"]').value      = <?= json_encode($cfg['telefone']) ?>;
    document.querySelector('[name="email"]').value         = <?= json_encode($cfg['email']) ?>;
    document.querySelector('[name="endereco"]').value      = <?= json_encode($cfg['endereco']) ?>;

    // Restaurar cor
    var corOriginal = <?= json_encode($cfg['cor_primaria']) ?>;
    document.getElementById('corPrimaria').value = corOriginal;
    atualizarCor(corOriginal);

    // Restaurar logo preview
    var logoOriginal = <?= json_encode(!empty($cfg['logo_path']) ? $cfg['logo_path'] : '') ?>;
    var wrap = document.getElementById('logoPreviewWrap');
    var placeholder = document.getElementById('logoPlaceholderWrap');
    var logoInput = document.getElementById('logoInput');
    if (logoInput) logoInput.value = '';

    if (logoOriginal) {
      document.getElementById('logoPreviewImg').src = '/' + logoOriginal.replace(/^\//, '');
      if (wrap) wrap.style.display = '';
      if (placeholder) placeholder.style.display = 'none';
    } else {
      if (wrap) wrap.style.display = 'none';
      if (placeholder) placeholder.style.display = '';
    }

    Swal.fire({
      title: 'Restaurado!',
      text: 'Os campos foram restaurados para os valores salvos.',
      icon: 'success',
      timer: 1800,
      showConfirmButton: false,
      background: 'var(--bg-card)',
      color: 'var(--text)',
    });
  });
}
</script>

<?php include '../../includes/footer.php'; ?>
