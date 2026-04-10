<?php
require_once '../../config/config.php';
checkAuth(['admin']);

$mp_config_file = __DIR__ . '/../../config/mercadopago.php';

// Carregar configurações salvas
$mp_cfg = [];
if (file_exists($mp_config_file)) {
    $mp_cfg = include $mp_config_file;
    if (!is_array($mp_cfg)) $mp_cfg = [];
}
$mp_cfg = array_merge([
    'mp_access_token'  => '',
    'mp_device_id'     => '',
    'mp_webhook_secret'=> '',
], $mp_cfg);

// Prioridade: arquivo config > config.php defines
if (empty($mp_cfg['mp_access_token']) && defined('MP_ACCESS_TOKEN') && MP_ACCESS_TOKEN !== 'APP_USR-seu-token-aqui') {
    $mp_cfg['mp_access_token'] = MP_ACCESS_TOKEN;
}
if (empty($mp_cfg['mp_device_id']) && defined('MP_DEVICE_ID') && MP_DEVICE_ID !== 'PAX_A910__SMARTPOS-id-do-terminal') {
    $mp_cfg['mp_device_id'] = MP_DEVICE_ID;
}

$mensagem = $erro = '';

// ── Salvar configurações ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_config'])) {
    $novo = [
        'mp_access_token'  => trim($_POST['mp_access_token']   ?? ''),
        'mp_device_id'     => trim($_POST['mp_device_id']      ?? ''),
        'mp_webhook_secret'=> trim($_POST['mp_webhook_secret'] ?? ''),
    ];

    $php_content = "<?php\nreturn " . var_export($novo, true) . ";\n";

    if (file_put_contents($mp_config_file, $php_content) !== false) {
        $mp_cfg   = $novo;
        $mensagem = 'Configurações do Mercado Pago salvas com sucesso!';
    } else {
        $erro = 'Não foi possível salvar. Verifique as permissões da pasta config/.';
    }
}

$mp_token     = $mp_cfg['mp_access_token'];
$mp_device_id = $mp_cfg['mp_device_id'];

// ── Buscar terminais via API ──────────────────────────────────────────────────
$terminais = [];
if ($mp_token) {
    $ch = curl_init('https://api.mercadopago.com/point/integration-api/devices');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $mp_token"],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res && $code === 200) {
        $data      = json_decode($res, true);
        $terminais = $data['devices'] ?? [];
    }
}
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">Mercado Pago <span style="color:var(--accent)">·</span> Integração</div>
  <div class="topbar-actions"></div>
</header>

<main class="os-content">
<div style="max-width:760px;margin:0 auto">

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

<!-- Credenciais -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-key"></i> Credenciais da API</div>
    <?php if ($mp_token): ?>
    <span style="background:rgba(34,197,94,.15);color:#22c55e;border-radius:20px;padding:3px 12px;font-size:.75rem;font-weight:600">
      <i class="ph-bold ph-check-circle"></i> Configurado
    </span>
    <?php else: ?>
    <span style="background:rgba(239,68,68,.12);color:#ef4444;border-radius:20px;padding:3px 12px;font-size:.75rem;font-weight:600">
      <i class="ph-bold ph-warning-circle"></i> Não configurado
    </span>
    <?php endif; ?>
  </div>
  <div class="os-card-body">
    <form method="POST">
      <input type="hidden" name="salvar_config" value="1">
      <div class="os-form-group">
        <label class="os-label">Access Token (Produção)</label>
        <input type="text" name="mp_access_token" class="os-input"
               value="<?= htmlspecialchars($mp_token) ?>"
               placeholder="APP_USR-...">
        <small style="color:var(--text-muted);font-size:.75rem;margin-top:4px;display:block">
          Obtenha em: <a href="https://www.mercadopago.com.br/developers/panel" target="_blank" style="color:var(--accent)">mercadopago.com.br/developers/panel</a>
        </small>
      </div>
      <div class="os-form-group">
        <label class="os-label">Device ID do Terminal (Point)</label>
        <input type="text" name="mp_device_id" class="os-input"
               value="<?= htmlspecialchars($mp_device_id) ?>"
               placeholder="PAX_A910__SMARTPOS...">
        <small style="color:var(--text-muted);font-size:.75rem;margin-top:4px;display:block">
          Aparece na lista de terminais abaixo após conectar o Point ao Wi-Fi
        </small>
      </div>

      <div class="os-form-group">
        <label class="os-label">Webhook Secret (Chave de Assinatura)</label>
        <input type="text" name="mp_webhook_secret" class="os-input"
               value="<?= htmlspecialchars($mp_cfg['mp_webhook_secret'] ?? '') ?>"
               placeholder="cole aqui o secret gerado pelo MP">
        <small style="color:var(--text-muted);font-size:.75rem;margin-top:4px;display:block">
          Gerado automaticamente pelo MP ao configurar o webhook. Opcional — se não informado, a validação de assinatura é ignorada (não recomendado em produção).
        </small>
      </div>

      <!-- URL do Webhook -->
      <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-bottom:16px">
        <div style="font-size:.75rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">
          <i class="ph-bold ph-link"></i> URL do Webhook
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <code id="webhookUrl" style="flex:1;font-size:.82rem;color:var(--text);background:var(--bg-input);padding:8px 12px;border-radius:6px;border:1px solid var(--border);word-break:break-all">
            <?php
              $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
              $host  = $_SERVER['HTTP_HOST'] ?? 'seudominio.com.br';
              $base  = defined('BASE_URL') ? BASE_URL : '';
              echo htmlspecialchars("{$proto}://{$host}{$base}/api/webhook_mercadopago.php");
            ?>
          </code>
          <button type="button" onclick="copiarWebhook()"
                  class="btn-os btn-os-ghost btn-os-sm" style="white-space:nowrap">
            <i class="ph-bold ph-copy"></i> Copiar
          </button>
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:8px">
          Cole esta URL no painel do Mercado Pago em <strong style="color:var(--text)">Suas integrações → Webhooks → Configurar notificações</strong>.<br>
          Ative os eventos: <strong style="color:var(--text)">Pagamentos · Ordens · Point (terminal)</strong>.
        </div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" class="btn-os btn-os-primary">
          <i class="ph-bold ph-floppy-disk"></i> Salvar Configurações
        </button>
        <button type="button" onclick="testarConexao()" class="btn-os btn-os-ghost">
          <i class="ph-bold ph-plugs"></i> Testar Conexão
        </button>
      </div>
      <div id="testeResult" style="margin-top:12px"></div>
    </form>
  </div>
</div>

<!-- Terminais -->
<div class="os-card" style="margin-bottom:20px">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-device-mobile"></i> Terminais Disponíveis</div>
    <button onclick="location.reload()" class="btn-os btn-os-ghost btn-os-sm">
      <i class="ph-bold ph-arrows-clockwise"></i> Atualizar
    </button>
  </div>
  <div class="os-card-body">
    <?php if (empty($terminais)): ?>
    <div style="text-align:center;padding:30px;color:var(--text-muted)">
      <i class="ph-bold ph-device-mobile" style="font-size:2rem;margin-bottom:8px;display:block"></i>
      <?= $mp_token ? 'Nenhum terminal encontrado. Verifique se o terminal está ligado e conectado ao Wi-Fi.' : 'Configure o Access Token acima para listar os terminais.' ?>
    </div>
    <?php else: ?>
      <?php foreach ($terminais as $t): ?>
      <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:14px 18px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-weight:600;color:var(--text);font-size:.9rem"><?= htmlspecialchars($t['id'] ?? '') ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($t['device_name'] ?? 'Terminal Point') ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="background:rgba(34,197,94,.15);color:#22c55e;border-radius:20px;padding:3px 12px;font-size:.75rem;font-weight:600">
            <?= htmlspecialchars($t['operating_mode'] ?? 'Ativo') ?>
          </span>
          <button onclick="copiarDeviceId('<?= htmlspecialchars($t['id'] ?? '') ?>')"
                  class="btn-os btn-os-ghost btn-os-sm" title="Copiar Device ID">
            <i class="ph-bold ph-copy"></i>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Como usar -->
<div class="os-card">
  <div class="os-card-header">
    <div class="os-card-title"><i class="ph-bold ph-info"></i> Como Integrar</div>
  </div>
  <div class="os-card-body">
    <div style="display:flex;flex-direction:column;gap:14px">
      <?php foreach ([
        ['1', 'Obter Access Token', 'Acesse o painel de desenvolvedores e crie uma aplicação para obter o token de produção.'],
        ['2', 'Conectar Terminal Point', 'Ligue o terminal, conecte ao Wi-Fi e vincule ao seu painel MP. O Device ID aparecerá na lista acima.'],
        ['3', 'Copiar Device ID', 'Clique no ícone de copiar ao lado do terminal desejado e cole no campo Device ID acima.'],
        ['4', 'Salvar e usar no PDV', 'Salve as configurações. No PDV, selecione "Maquininha" e a cobrança será enviada automaticamente.'],
      ] as [$num, $titulo, $desc]): ?>
      <div style="display:flex;gap:12px;align-items:flex-start">
        <div style="width:26px;height:26px;background:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#000;flex-shrink:0;font-size:.8rem"><?= $num ?></div>
        <div>
          <div style="font-weight:600;color:var(--text);font-size:.875rem"><?= $titulo ?></div>
          <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</div>
</main>

<script>
function testarConexao() {
  var token = document.querySelector('[name="mp_access_token"]').value.trim();
  var div   = document.getElementById('testeResult');
  if (!token) {
    div.innerHTML = '<div class="os-alert os-alert-warning"><i class="ph-bold ph-warning"></i> Informe o Access Token primeiro.</div>';
    return;
  }
  div.innerHTML = '<span style="color:var(--text-muted);font-size:.85rem"><i class="ph-bold ph-spinner"></i> Testando...</span>';

  fetch('../../api/mercadopago_test.php?token=' + encodeURIComponent(token))
    .then(function(r) { return r.json(); })
    .then(function(d) {
      div.innerHTML = d.success
        ? '<div class="os-alert os-alert-success"><i class="ph-bold ph-check-circle"></i> ' + d.message + '</div>'
        : '<div class="os-alert os-alert-danger"><i class="ph-bold ph-x-circle"></i> ' + (d.message || 'Token inválido') + '</div>';
    })
    .catch(function() {
      div.innerHTML = '<div class="os-alert os-alert-danger"><i class="ph-bold ph-x-circle"></i> Erro de conexão.</div>';
    });
}

function copiarDeviceId(id) {
  navigator.clipboard.writeText(id).then(function() {
    Swal.fire({ title: 'Copiado!', text: id, icon: 'success', timer: 1800,
      showConfirmButton: false, background: 'var(--bg-card)', color: 'var(--text)' });
  });
}
</script>

<?php include '../../includes/footer.php'; ?>
