<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_admin');

$user     = auth_user();
$tenantId = $user['tenant_id'];

function tenant_get(string $tenantId, string $key): string {
    return tenant_setting_get($tenantId, $key);
}
function tenant_set(string $tenantId, string $key, string $value): void {
    tenant_setting_set($tenantId, $key, $value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_config') {
        audit_log('UPDATE_ML_CONFIG', 'tenant_settings', null);
        $appId  = trim($_POST['meli_app_id'] ?? '');
        $secret = trim($_POST['meli_secret'] ?? '');
        if (!$appId || !$secret) { echo json_encode(['ok'=>false,'error'=>'Preencha App ID e Secret']); exit; }
        tenant_set($tenantId, 'meli_app_id',        $appId);
        tenant_set($tenantId, 'meli_client_secret', $secret);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_gemini') {
        $key = trim($_POST['gemini_key'] ?? '');
        tenant_set($tenantId, 'gemini_api_key', $key); // legado
        tenant_set($tenantId, 'ai_key_gemini',  $key); // novo padrão
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_groq') {
        $key = trim($_POST['groq_key'] ?? '');
        tenant_set($tenantId, 'groq_api_key', $key); // legado
        tenant_set($tenantId, 'ai_key_groq',  $key); // novo padrão
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'save_wpp_config') {
        tenant_set($tenantId, 'wpp_key',      trim($_POST['key']      ?? ''));
        tenant_set($tenantId, 'wpp_instance', trim($_POST['instance'] ?? ''));
        tenant_set($tenantId, 'wpp_owner',    trim($_POST['owner']    ?? ''));
        echo json_encode(['ok'=>true]); exit;
    }

    if ($action === 'test_wpp') {
        $key      = tenant_get($tenantId, 'wpp_key');
        $instance = tenant_get($tenantId, 'wpp_instance');
        $owner    = tenant_get($tenantId, 'wpp_owner');
        if (!$key || !$instance || !$owner) {
            echo json_encode(['ok'=>false,'error'=>'Configure e salve as credenciais primeiro']); exit;
        }
        // Sobrescrever constantes temporariamente via função direta
        $url     = EVOLUTION_URL;
        $payload = json_encode([
            'number' => $owner,
            'text'   => "✅ *SAM ERP* conectado!\n\nNotificações do sistema estão ativas. Você receberá alertas de pedidos, estoque, reclamações e muito mais.",
            'options' => ['delay'=>500,'presence'=>'composing'],
        ]);
        $ch = curl_init(rtrim($url,'/')."/message/sendText/{$instance}");
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$payload,CURLOPT_TIMEOUT=>8,
            CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','apikey: '.$key],
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo json_encode(['ok'=>$code>=200&&$code<300,'code'=>$code,'body'=>$body]); exit;
    }

    if ($action === 'save_bot_config') {
        tenant_set($tenantId, 'ai_bot_questions',    $_POST['ai_bot_questions']    === '1' ? '1' : '0');
        tenant_set($tenantId, 'ai_bot_instructions', trim($_POST['ai_bot_instructions'] ?? ''));
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'disconnect') {
        audit_log('DISCONNECT_ML_ACCOUNT', 'meli_accounts', $_POST['account_id']??'');
        $accountId = $_POST['account_id'] ?? '';
        db_update('meli_accounts', ['is_active'=>0], 'id=? AND tenant_id=?', [$accountId, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }
}

// Modelo SaaS — credenciais globais do config.php, não por tenant
$savedAppId  = defined('MELI_APP_ID')       ? MELI_APP_ID       : '';
$savedSecret = defined('MELI_CLIENT_SECRET') ? MELI_CLIENT_SECRET : '';

// Multi-IA — carrega todas as chaves e provedor ativo
require_once dirname(__DIR__) . '/ai.php';
$aiConfig   = ai_get_config($tenantId);
$aiProvider = $aiConfig['provider'] ?? 'groq';

// Provedores por uso
$aiUseProviders = [];
foreach (array_keys(AI_USES) as $use) {
    $aiUseProviders[$use] = ai_get_provider_for_use($tenantId, $use);
}

$accounts    = db_all("SELECT * FROM meli_accounts WHERE tenant_id=? ORDER BY created_at DESC", [$tenantId]);
$isConfigured = !empty($savedAppId) && !empty($savedSecret);
$redirectUri  = APP_URL . '/meli/callback.php';
$webhookUrl   = APP_URL . '/api/webhooks/meli';

$title = 'Integração ML';
include __DIR__ . '/layout.php';

// Toasts via GET params do OAuth callback
$successParam = $_GET['success'] ?? '';
$errorParam   = $_GET['error']   ?? '';
$nickname     = htmlspecialchars($_GET['nickname'] ?? '');
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  <?php if ($successParam === 'connected'): ?>
  toast('✅ Conta <?= $nickname ?> conectada com sucesso!', 'success');
  <?php elseif ($successParam === 'connected_no_webhook'): ?>
  toast('✅ Conta <?= $nickname ?> conectada! Webhook ainda não detectado — confirme no painel ML em Integrações → Notificações.', 'warning');
  <?php elseif ($errorParam): ?>
  <?php $errosTraduzidos = [
    'invalid_state'        => 'Sessão inválida ou expirada. Tente novamente.',
    'expired_state'        => 'O tempo de autorização expirou. Tente novamente.',
    'no_code'              => 'Autorização cancelada ou negada pelo ML.',
    'no_credentials'       => 'App ID ou Client Secret não configurados.',
    'token_exchange_failed'=> 'Falha ao obter token do ML. Verifique as credenciais do app.',
    'user_fetch_failed'    => 'Conta conectada mas não foi possível buscar os dados do usuário ML.',
    'access_denied'        => 'Acesso negado. O usuário não autorizou o app.',
  ]; ?>
  toast('❌ <?= htmlspecialchars($errosTraduzidos[$errorParam] ?? 'Erro ao conectar: '.$errorParam) ?>', 'error');
  <?php endif; ?>
});
</script>

<div style="padding:24px">

  <div style="margin-bottom:24px">
    <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Integração Mercado Livre</h1>
    <p style="font-size:12px;color:#5E5E5A;margin-top:2px">Configure credenciais e gerencie contas conectadas</p>
  </div>

  <!-- Status KPIs — modelo SaaS: sem credenciais, só contas e status -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid <?= count($accounts)>0?'#22c55e':'#f59e0b' ?>;border-radius:12px;padding:14px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <i data-lucide="store" style="width:13px;height:13px;color:<?= count($accounts)>0?'#22c55e':'#f59e0b' ?>"></i>
        <span style="font-size:12px;color:#E8E8E6;font-weight:500">Contas conectadas</span>
      </div>
      <div style="font-size:24px;font-weight:700;color:<?= count($accounts)>0?'#22c55e':'#f59e0b' ?>"><?= count($accounts) ?></div>
      <div style="font-size:11px;color:#5E5E5A;margin-top:3px"><?= count(array_filter($accounts,fn($a)=>$a['is_active'])) ?> ativas</div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:12px;padding:14px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <i data-lucide="zap" style="width:13px;height:13px;color:#3483FA"></i>
        <span style="font-size:12px;color:#E8E8E6;font-weight:500">Webhook</span>
      </div>
      <div style="font-size:14px;font-weight:700;color:#3483FA">✓ Ativo</div>
      <div style="font-size:11px;color:#5E5E5A;margin-top:3px">Pedidos em tempo real</div>
    </div>

    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:12px;padding:14px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
        <i data-lucide="shield-check" style="width:13px;height:13px;color:#22c55e"></i>
        <span style="font-size:12px;color:#E8E8E6;font-weight:500">Permissões ativas</span>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:4px">
        <?php foreach ([['read','#3483FA'],['write','#3483FA'],['offline_access','#22c55e'],['orders','#f59e0b'],['messages','#f59e0b'],['payments','#22c55e'],['shipping_label','#a855f7'],['items','#f59e0b']] as [$s,$c]): ?>
        <span style="font-size:9px;padding:2px 7px;border-radius:20px;background:<?= $c ?>15;border:0.5px solid <?= $c ?>40;color:<?= $c ?>;font-family:monospace"><?= $s ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="config-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    <!-- Esquerda: IA + Robô -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <!-- IA — Multi-Provedor -->
      <div class="card" style="overflow:hidden">
        <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px">
            <i data-lucide="sparkles" style="width:14px;height:14px;color:#FFE600"></i>
            <span style="font-size:13px;font-weight:500;color:#E8E8E6">IA — Provedor de Linguagem</span>
          </div>
          <?php $activeP = AI_PROVIDERS[$aiProvider] ?? AI_PROVIDERS['groq']; ?>
          <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;background:<?= $activeP['color'] ?>20;color:<?= $activeP['color'] ?>">
            ATIVO: <?= $activeP['name'] ?>
          </span>
        </div>
        <div style="padding:18px">

          <!-- Seletor de provedor -->
          <div style="margin-bottom:16px">
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:8px">Provedor padrão <span style="color:#5E5E5A">(usado quando não configurado por seção)</span></label>
            <div style="display:flex;flex-direction:column;gap:6px" id="ai-provider-list">
              <?php foreach (AI_PROVIDERS as $pid => $p): ?>
              <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#252528;border:0.5px solid <?= $aiProvider===$pid?$p['color']:'#2E2E33' ?>;border-radius:8px;cursor:pointer;transition:border .15s" id="ai-label-<?= $pid ?>">
                <input type="radio" name="ai_provider" value="<?= $pid ?>" <?= $aiProvider===$pid?'checked':'' ?> onchange="selectProvider('<?= $pid ?>')" style="accent-color:<?= $p['color'] ?>">
                <div style="flex:1">
                  <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-size:12px;font-weight:500;color:#E8E8E6"><?= $p['name'] ?></span>
                    <span style="font-size:8px;padding:1px 6px;border-radius:6px;background:<?= $p['color'] ?>20;color:<?= $p['color'] ?>;font-weight:600"><?= $p['badge'] ?></span>
                  </div>
                  <div style="font-size:10px;color:#5E5E5A;margin-top:2px"><?= $p['desc'] ?></div>
                </div>
                <span id="ai-status-<?= $pid ?>" style="font-size:9px;color:#5E5E5A">
                  <?= !empty($aiConfig[$pid]) ? '● Configurado' : '○ Sem chave' ?>
                </span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- IA por seção -->
          <div style="margin-bottom:16px;padding:12px;background:#0F0F10;border-radius:8px;border:0.5px solid #2E2E33">
            <div style="font-size:11px;color:#9A9A96;margin-bottom:10px;display:flex;align-items:center;gap:5px">
              <i data-lucide="layers" style="width:11px;height:11px"></i>
              IA por seção — configure um provedor diferente para cada uso
            </div>
            <?php foreach (AI_USES as $use => $useInfo):
              $currentUseProvider = $aiUseProviders[$use];
              $currentP = AI_PROVIDERS[$currentUseProvider] ?? AI_PROVIDERS['groq'];
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;padding:8px 10px;background:#1A1A1C;border-radius:6px">
              <div style="display:flex;align-items:center;gap:6px">
                <i data-lucide="<?= $useInfo['icon'] ?>" style="width:11px;height:11px;color:#5E5E5A"></i>
                <span style="font-size:11px;color:#E8E8E6"><?= $useInfo['label'] ?></span>
              </div>
              <select onchange="saveUseProvider('<?= $use ?>', this.value)"
                style="padding:4px 8px;background:#252528;border:0.5px solid #2E2E33;border-radius:6px;color:#E8E8E6;font-size:11px;cursor:pointer">
                <?php foreach (AI_PROVIDERS as $pid => $p): ?>
                <option value="<?= $pid ?>" <?= $currentUseProvider===$pid?'selected':'' ?>>
                  <?= $p['name'] ?> <?= !empty($aiConfig[$pid])?'✓':'' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Campo de API Key dinâmico -->
          <?php foreach (AI_PROVIDERS as $pid => $p): ?>
          <div id="ai-key-block-<?= $pid ?>" style="<?= $aiProvider!==$pid?'display:none':''; ?>margin-bottom:12px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
              <label style="font-size:11px;color:#9A9A96">API Key — <?= $p['name'] ?></label>
              <a href="<?= $p['link'] ?>" target="_blank" style="font-size:10px;color:<?= $p['color'] ?>">Obter chave →</a>
            </div>
            <div style="display:flex;gap:8px">
              <div style="position:relative;flex:1">
                <input type="password" id="inp-ai-<?= $pid ?>"
                  value="<?= htmlspecialchars($aiConfig[$pid] ?? '') ?>"
                  placeholder="<?= $p['key_hint'] ?>"
                  style="width:100%;padding:9px 36px 9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;font-family:monospace;box-sizing:border-box">
                <button onclick="togglePass('inp-ai-<?= $pid ?>','eye-ai-<?= $pid ?>')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5E5E5A;cursor:pointer">
                  <i id="eye-ai-<?= $pid ?>" data-lucide="eye" style="width:13px;height:13px"></i>
                </button>
              </div>
              <button onclick="testAndSaveKey('<?= $pid ?>')" id="btn-test-<?= $pid ?>"
                style="padding:9px 14px;background:<?= $p['color'] ?>20;border:0.5px solid <?= $p['color'] ?>;color:<?= $p['color'] ?>;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px">
                <i data-lucide="zap" style="width:11px;height:11px"></i> Testar e salvar
              </button>
            </div>
            <div id="ai-test-result-<?= $pid ?>" style="margin-top:6px;font-size:10px;display:none"></div>
          </div>
          <?php endforeach; ?>

        </div>
      </div>





      <!-- WhatsApp — Notificações -->
      <?php
      $wppKey      = tenant_get($tenantId, 'wpp_key');
      $wppInstance = tenant_get($tenantId, 'wpp_instance');
      $wppOwner    = tenant_get($tenantId, 'wpp_owner');
      $wppAtivo    = $wppKey && $wppInstance && $wppOwner;
      $wppWebhook  = APP_URL . '/api/webhooks/evolution.php';
      ?>
      <div class="card" style="overflow:hidden">
        <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px">
            <div style="width:28px;height:28px;border-radius:7px;background:rgba(34,197,94,.1);display:flex;align-items:center;justify-content:center">
              <i data-lucide="message-circle" style="width:14px;height:14px;color:#22c55e"></i>
            </div>
            <span style="font-size:13px;font-weight:500;color:#E8E8E6">WhatsApp — Notificações</span>
          </div>
          <span style="font-size:10px;padding:3px 8px;border-radius:20px;background:<?= $wppAtivo ? 'rgba(34,197,94,.15)' : 'rgba(90,90,90,.15)' ?>;color:<?= $wppAtivo ? '#22c55e' : '#5E5E5A' ?>;font-weight:600">
            <?= $wppAtivo ? '● ATIVO' : '○ Não configurado' ?>
          </span>
        </div>
        <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px">
          <p style="font-size:11px;color:#9A9A96;margin:0;line-height:1.6">
            Receba notificações no WhatsApp para: novos pedidos, reclamações, estoque crítico, perguntas sem resposta e conta ML desconectada.
          </p>

          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">API Key (Evolution)</label>
            <input type="text" id="wpp-key" value="<?= htmlspecialchars($wppKey) ?>" class="input"
              placeholder="Ex: seu-api-key-evolution">
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Nome da instância</label>
            <input type="text" id="wpp-instance" value="<?= htmlspecialchars($wppInstance) ?>" class="input"
              placeholder="Ex: sam-notificacoes">
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">
              Número do proprietário
              <span style="color:#5E5E5A">— com DDI e DDD, sem espaços</span>
            </label>
            <input type="text" id="wpp-owner" value="<?= htmlspecialchars($wppOwner) ?>" class="input"
              placeholder="Ex: 5511999999999">
          </div>

          <!-- Webhook URL para configurar na Evolution -->
          <div style="background:#252528;border-radius:8px;padding:12px;display:flex;flex-direction:column;gap:8px">
            <div style="font-size:10px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:5px">
              <i data-lucide="webhook" style="width:11px;height:11px;color:#3483FA"></i>
              Webhook URL — configure no gerenciador da Evolution
            </div>
            <div style="display:flex;align-items:center;gap:6px">
              <code id="wpp-webhook-url" style="flex:1;padding:7px 10px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:6px;font-size:11px;color:#3483FA;word-break:break-all;font-family:monospace">
                <?= $wppWebhook ?>
              </code>
              <button onclick="copyText('<?= $wppWebhook ?>')"
                style="padding:7px 10px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:6px;cursor:pointer;color:#9A9A96;flex-shrink:0;transition:all .15s"
                onmouseover="this.style.borderColor='#3483FA';this.style.color='#3483FA'"
                onmouseout="this.style.borderColor='#2E2E33';this.style.color='#9A9A96'"
                title="Copiar URL">
                <i data-lucide="copy" style="width:13px;height:13px"></i>
              </button>
            </div>
            <div style="font-size:10px;color:#5E5E5A;line-height:1.5">
              No painel da Evolution API → Instâncias → sua instância → Webhook → cole esta URL e ative os eventos: <strong style="color:#9A9A96">MESSAGES_UPSERT</strong>
            </div>
          </div>

          <div style="display:flex;gap:8px">
            <button onclick="saveWppConfig()" class="btn-primary" style="flex:1;font-size:12px">
              <i data-lucide="save" style="width:12px;height:12px"></i> Salvar
            </button>
            <button onclick="testarWpp(event)" class="btn-secondary" style="font-size:12px">
              <i data-lucide="send" style="width:12px;height:12px"></i> Testar
            </button>
          </div>

          <div style="background:#252528;border-radius:8px;padding:10px 12px;font-size:10px;color:#5E5E5A;line-height:1.8">
            <strong style="color:#E8E8E6">Notificações automáticas:</strong><br>
            🛒 Novo pedido aprovado &nbsp;·&nbsp; ⚠️ Reclamação aberta<br>
            📦 Estoque ≤ 3 unidades &nbsp;·&nbsp; ❓ Perguntas sem resposta (2h)<br>
            🔴 Conta ML desconectada &nbsp;·&nbsp; 💸 Conta a pagar vencida
          </div>
        </div>
      </div>

    </div><!-- fim coluna esquerda -->

    <!-- Direita: Contas ML conectadas -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Robô IA — Perguntas Pré-venda -->
      <?php
      $botEnabled      = tenant_get($tenantId, 'ai_bot_questions') === '1';
      $botInstructions = tenant_get($tenantId, 'ai_bot_instructions');
      ?>
      <div class="card" style="overflow:hidden">
        <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px">
            <i data-lucide="bot" style="width:14px;height:14px;color:#22c55e"></i>
            <span style="font-size:13px;font-weight:500;color:#E8E8E6">Robô IA — Perguntas Pré-venda</span>
          </div>
          <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;background:<?= $botEnabled ? 'rgba(34,197,94,.15)' : 'rgba(94,94,90,.15)' ?>;color:<?= $botEnabled ? '#22c55e' : '#5E5E5A' ?>">
            <?= $botEnabled ? 'ATIVO' : 'INATIVO' ?>
          </span>
        </div>
        <div style="padding:18px">
          <div style="margin-bottom:14px;padding:10px 12px;background:rgba(34,197,94,.06);border:0.5px solid rgba(34,197,94,.2);border-radius:8px;font-size:11px;color:#9A9A96;line-height:1.6">
            Quando ativado, o robô responde automaticamente todas as perguntas pré-venda usando IA.
            As respostas são baseadas nos dados do produto e histórico de perguntas anteriores.
            Requer um provedor de IA configurado acima (Groq, Gemini, OpenAI, Claude ou Mistral).
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding:12px;background:#252528;border-radius:8px">
            <div>
              <div style="font-size:12px;font-weight:500;color:#E8E8E6">Resposta automática</div>
              <div style="font-size:10px;color:#5E5E5A;margin-top:2px">Responde perguntas sem intervenção humana</div>
            </div>
            <label style="position:relative;display:inline-block;width:40px;height:22px;cursor:pointer">
              <input type="checkbox" id="bot-toggle" <?= $botEnabled ? 'checked' : '' ?> onchange="toggleBot(this)" style="opacity:0;width:0;height:0">
              <span style="position:absolute;inset:0;background:<?= $botEnabled ? '#22c55e' : '#3E3E45' ?>;border-radius:11px;transition:.3s" id="bot-slider"></span>
              <span style="position:absolute;left:<?= $botEnabled ? '20px' : '2px' ?>;top:2px;width:18px;height:18px;background:#fff;border-radius:50%;transition:.3s" id="bot-knob"></span>
            </label>
          </div>
          <div style="margin-bottom:12px">
            <label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:6px">
              Instruções para o robô <span style="color:#5E5E5A">(opcional)</span>
            </label>
            <textarea id="bot-instructions" placeholder="Ex: Sempre mencione frete grátis acima de R$200."
              style="width:100%;height:80px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;line-height:1.5;box-sizing:border-box"><?= htmlspecialchars($botInstructions) ?></textarea>
          </div>
          <button onclick="saveBotConfig()" class="btn-primary">
            <i data-lucide="save" style="width:13px;height:13px"></i> Salvar configurações do robô
          </button>
        </div>
      </div>


      <!-- Contas conectadas -->
      <div class="card" style="overflow:hidden">
        <div style="padding:14px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:8px">
            <i data-lucide="user-check" style="width:14px;height:14px;color:#22c55e"></i>
            <span style="font-size:13px;font-weight:500;color:#E8E8E6">Contas ML conectadas</span>
          </div>
          <button onclick="abrirModalConectar()" style="display:flex;align-items:center;gap:5px;padding:6px 12px;background:#FFE600;color:#1A1A1A;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:none">
            <i data-lucide="plus" style="width:11px;height:11px"></i> Conectar conta
          </button>
        </div>
        <?php if (empty($accounts)): ?>
        <div style="padding:28px;text-align:center">
          <i data-lucide="store" style="width:28px;height:28px;color:#2E2E33;margin:0 auto 10px;display:block"></i>
          <p style="font-size:13px;color:#5E5E5A;margin-bottom:12px">Nenhuma conta conectada</p>
          <button onclick="abrirModalConectar()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#FFE600;color:#1A1A1A;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none">
            <i data-lucide="link" style="width:13px;height:13px"></i> Conectar agora
          </a>
        </div>
        <?php else: foreach ($accounts as $acc):
          $expTs      = strtotime($acc['token_expires_at'] ?? '2000-01-01');
          $active     = (bool)$acc['is_active'];
          $tokenDead  = $expTs <= time();
          $tokenWarn  = $expTs > time() && $expTs < time() + 3600;
          $tokenOk    = $active && !$tokenDead && !$tokenWarn;
          $isRevogado = !$active && !empty($acc['refresh_token_enc'])
                        && !in_array($acc['refresh_token_enc'], ['demo_refresh','']);
          // Determina estado visual
          if ($tokenOk)       { $stColor='#22c55e'; $stBg='rgba(34,197,94,.15)';  $stLabel='✓ Conectada'; $stIcon='check-circle'; }
          elseif ($isRevogado){ $stColor='#ef4444'; $stBg='rgba(239,68,68,.15)';  $stLabel='✗ Desconectada — reconecte'; $stIcon='wifi-off'; }
          elseif ($tokenDead) { $stColor='#f59e0b'; $stBg='rgba(245,158,11,.15)'; $stLabel='⚠ Token expirado'; $stIcon='alert-triangle'; }
          elseif ($tokenWarn) { $stColor='#f59e0b'; $stBg='rgba(245,158,11,.15)'; $stLabel='⚠ Expira em breve'; $stIcon='alert-triangle'; }
          else                { $stColor='#5E5E5A'; $stBg='rgba(94,94,90,.15)';   $stLabel='Inativa'; $stIcon='minus-circle'; }
        ?>
        <div style="padding:12px 18px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:12px;<?= $isRevogado ? 'background:rgba(239,68,68,.03)' : ($tokenDead?'background:rgba(245,158,11,.02)':'') ?>">
          <div style="width:36px;height:36px;border-radius:50%;background:<?= $tokenOk?'rgba(52,131,250,.15)':'rgba(94,94,90,.15)' ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:<?= $tokenOk?'#3483FA':'#5E5E5A' ?>;flex-shrink:0;position:relative">
            <?= strtoupper(mb_substr($acc['nickname'],0,2)) ?>
            <?php if ($isRevogado || $tokenDead): ?>
            <span style="position:absolute;bottom:-2px;right:-2px;width:12px;height:12px;border-radius:50%;background:<?= $stColor ?>;border:2px solid #1A1A1C;display:flex;align-items:center;justify-content:center">
              <i data-lucide="<?= $stIcon ?>" style="width:7px;height:7px;color:#fff"></i>
            </span>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:500;color:#E8E8E6"><?= htmlspecialchars($acc['nickname']) ?></div>
            <div style="font-size:11px;color:#5E5E5A;margin-top:1px"><?= htmlspecialchars($acc['email'] ?? 'ID: '.$acc['meli_user_id']) ?></div>
            <div style="display:flex;gap:5px;margin-top:5px;flex-wrap:wrap">
              <span style="font-size:9px;padding:1px 7px;border-radius:10px;background:<?= $stBg ?>;color:<?= $stColor ?>"><?= $stLabel ?></span>
              <?php if ($acc['reputation_level']): ?>
              <span style="font-size:9px;padding:1px 7px;border-radius:10px;background:rgba(255,230,0,.1);color:#FFE600"><?= ucfirst($acc['reputation_level']) ?></span>
              <?php endif; ?>
              <?php if ($tokenOk): ?>
              <span style="font-size:9px;padding:1px 7px;border-radius:10px;background:rgba(94,94,90,.1);color:#5E5E5A">
                Expira <?= date('d/m H:i', $expTs) ?>
              </span>
              <?php endif; ?>
            </div>
            <?php if ($isRevogado): ?>
            <div style="font-size:10px;color:#ef4444;margin-top:6px;line-height:1.4">
              O acesso foi revogado pelo Mercado Livre. Clique em "Reconectar" para restaurar a integração.
            </div>
            <?php elseif ($tokenDead && $active): ?>
            <div style="font-size:10px;color:#f59e0b;margin-top:6px;line-height:1.4">
              Token expirado. O sistema tentará renovar automaticamente via cron. Se persistir, reconecte.
            </div>
            <?php endif; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
            <?php if ($isRevogado || $tokenDead || !$active): ?>
            <a href="/api/meli_connect.php" style="padding:6px 12px;background:rgba(52,131,250,.1);border:0.5px solid #3483FA;color:#3483FA;border-radius:7px;font-size:11px;text-decoration:none;display:flex;align-items:center;gap:4px;font-weight:600">
              <i data-lucide="refresh-cw" style="width:11px;height:11px"></i> Reconectar
            </a>
            <?php endif; ?>
            <?php if ($active && $tokenDead): ?>
            <a href="/api/meli_refresh_token.php?force=1&secret=<?= MASTER_SECRET ?>" target="_blank"
              style="padding:5px 10px;background:rgba(245,158,11,.1);border:0.5px solid #f59e0b;color:#f59e0b;border-radius:7px;font-size:10px;text-decoration:none;display:flex;align-items:center;gap:3px">
              <i data-lucide="zap" style="width:10px;height:10px"></i> Forçar refresh
            </a>
            <?php endif; ?>
            <?php if ($active && $tokenOk): ?>
            <button onclick="disconnectAccount('<?= $acc['id'] ?>')" style="padding:5px 10px;background:rgba(239,68,68,.1);border:0.5px solid #ef4444;color:#ef4444;border-radius:7px;font-size:11px;cursor:pointer;display:flex;align-items:center;gap:3px">
              <i data-lucide="unlink" style="width:11px;height:11px"></i> Desconectar
            </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
      <!-- Licença do sistema -->
      <!-- ── Card de Licença ─────────────────────────────────── -->
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:14px;overflow:hidden">

          <!-- Header -->
          <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:10px">
            <i data-lucide="key" style="width:16px;height:16px;color:#FFE600;flex-shrink:0"></i>
            <div style="flex:1;min-width:0">
              <div style="font-size:14px;font-weight:600;color:#E8E8E6">Licença do sistema</div>
              <div style="font-size:11px;color:#5E5E5A;margin-top:1px">Ativação e validade do SAM</div>
            </div>
            <!-- Badge de status -->
            <?php
            $licStatus  = $user['license_status'] ?? 'TRIAL';
            if (!empty($user['license_expiry'])) {
                $licExpiry = strtotime($user['license_expiry']);
            } elseif (!empty($user['trial_started'])) {
                $licExpiry = strtotime($user['trial_started']) + (15 * 86400);
            } else {
                $licExpiry = strtotime($user['created_at'] ?? 'now') + (15 * 86400);
            }
            $licDays    = max(0, (int)ceil(($licExpiry - time()) / 86400));
            $badgeColor = match($licStatus) { 'ACTIVE'=>'#22c55e', 'TRIAL'=>'#3483FA', 'EXPIRED'=>'#ef4444', default=>'#f59e0b' };
            $badgeLabel = match($licStatus) { 'ACTIVE'=>'Ativa', 'TRIAL'=>'Trial', 'EXPIRED'=>'Expirada', default=>'Bloqueada' };
            ?>
            <span style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;background:<?= $badgeColor ?>20;border:0.5px solid <?= $badgeColor ?>;color:<?= $badgeColor ?>">
              <?= $badgeLabel ?>
            </span>
          </div>

          <!-- Status atual -->
          <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div style="background:#252528;border-radius:10px;padding:14px">
                <div style="font-size:10px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Status</div>
                <div style="font-size:16px;font-weight:600;color:<?= $badgeColor ?>"><?= $badgeLabel ?></div>
                <?php if ($licStatus === 'TRIAL'): ?>
                <div style="font-size:11px;color:#5E5E5A;margin-top:3px">Trial de 15 dias</div>
                <?php endif; ?>
              </div>
              <div style="background:#252528;border-radius:10px;padding:14px">
                <div style="font-size:10px;color:#5E5E5A;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">
                  <?= $licStatus === 'TRIAL' ? 'Trial expira em' : 'Licença válida até' ?>
                </div>
                <div style="font-size:16px;font-weight:600;color:<?= $licDays <= 3 ? '#ef4444' : ($licDays <= 7 ? '#f59e0b' : '#E8E8E6') ?>">
                  <?= $licDays ?> dia<?= $licDays !== 1 ? 's' : '' ?>
                </div>
                <div style="font-size:11px;color:#5E5E5A;margin-top:3px"><?= date('d/m/Y', $licExpiry) ?></div>
              </div>
            </div>

            <?php if ($licStatus === 'TRIAL'): ?>
            <!-- Barra de progresso do trial -->
            <?php
            $trialStart   = strtotime($user['trial_started'] ?? date('Y-m-d', strtotime('-1 day')));
            $trialTotal   = 15 * 86400;
            $trialElapsed = time() - $trialStart;
            $trialPct     = min(100, max(0, round($trialElapsed / $trialTotal * 100)));
            $barColor     = $trialPct >= 80 ? '#ef4444' : ($trialPct >= 60 ? '#f59e0b' : '#3483FA');
            ?>
            <div style="margin-top:14px">
              <div style="display:flex;justify-content:space-between;font-size:11px;color:#5E5E5A;margin-bottom:6px">
                <span>Progresso do trial</span>
                <span><?= $trialPct ?>% utilizado</span>
              </div>
              <div style="height:6px;background:#2E2E33;border-radius:3px;overflow:hidden">
                <div style="height:100%;width:<?= $trialPct ?>%;background:<?= $barColor ?>;border-radius:3px;transition:width .3s"></div>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Campo de ativação -->
          <?php if ($licStatus !== 'ACTIVE'): ?>
          <div style="padding:16px 20px">
            <div style="font-size:12px;color:#9A9A96;margin-bottom:10px;display:flex;align-items:center;gap:6px">
              <i data-lucide="lock" style="width:12px;height:12px;color:#FFE600"></i>
              Insira a chave de ativação fornecida pela Ogro Systemas
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <div style="position:relative;flex:1;min-width:200px">
                <input type="text" id="license-key-input"
                  placeholder="XXXX-XXXX-XXXX-XXXX-XXXX"
                  style="width:100%;padding:10px 36px 10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:13px;outline:none;font-family:monospace;box-sizing:border-box;letter-spacing:1px"
                  oninput="formatLicenseKey(this)">
                <button type="button" onclick="pasteLicense()" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5E5E5A;cursor:pointer" title="Colar">
                  <i data-lucide="clipboard" style="width:14px;height:14px"></i>
                </button>
              </div>
              <button onclick="activateLicense()" id="btn-activate"
                style="padding:10px 20px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;min-height:42px">
                <i data-lucide="key" style="width:14px;height:14px"></i>
                Ativar licença
              </button>
            </div>
            <div id="license-msg" style="margin-top:8px;font-size:12px;display:none"></div>

            <!-- Contato para adquirir licença -->
            <div style="margin-top:12px;padding:10px 14px;background:#252528;border-radius:8px;font-size:11px;color:#5E5E5A;display:flex;align-items:center;gap:8px">
              <i data-lucide="info" style="width:12px;height:12px;flex-shrink:0"></i>
              Não tem uma chave? Entre em contato:
              <a href="mailto:contato@ogrosystemas.com.br" style="color:#3483FA;text-decoration:none;font-weight:500">contato@ogrosystemas.com.br</a>
            </div>
          </div>
          <?php else: ?>
          <!-- Licença ativa -->
          <div style="padding:16px 20px;display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:50%;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <i data-lucide="check" style="width:16px;height:16px;color:#22c55e"></i>
            </div>
            <div>
              <div style="font-size:13px;font-weight:500;color:#E8E8E6">Licença ativa</div>
              <div style="font-size:11px;color:#5E5E5A">Válida até <?= date('d/m/Y', $licExpiry) ?> · <?= $licDays ?> dias restantes</div>
            </div>
          </div>
          <?php endif; ?>
        </div>

    </div>


  </div>
</div>

<script>
function formatLicenseKey(el) {
  // Formata como XXXX-XXXX-XXXX-XXXX
  let v = el.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  let formatted = v.match(/.{1,4}/g)?.join('-') || v;
  el.value = formatted.substring(0, 39); // max 4x8 + 3 hífens
}

async function pasteLicense() {
  try {
    const text = await navigator.clipboard.readText();
    const el = document.getElementById('license-key-input');
    el.value = text.trim();
    formatLicenseKey(el);
  } catch(e) {
    toast('Permita o acesso à área de transferência', 'warning');
  }
}

async function activateLicense() {
  const key = document.getElementById('license-key-input').value.trim();
  const msg = document.getElementById('license-msg');
  const btn = document.getElementById('btn-activate');

  if (!key || key.length < 10) {
    msg.textContent = '✗ Insira uma chave válida';
    msg.style.cssText = 'margin-top:8px;font-size:12px;display:block;color:#ef4444';
    return;
  }

  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:14px;height:14px;animation:spin 1s linear infinite"></i> Validando...';
  lucide.createIcons();

  const fd = new FormData();
  fd.append('license_key', key);

  try {
    const r = await fetch('/api/activate_license.php', {method:'POST', body:fd});
    const d = await r.json();

    if (d.ok) {
      msg.innerHTML = `✓ Licença ativada! Plano <strong>${d.plan}</strong> · Válida por <strong>${d.days} dias</strong> (até ${d.expiry})`;
      msg.style.cssText = 'margin-top:8px;font-size:12px;display:block;color:#22c55e';
      toast('Licença ativada com sucesso!', 'success');
      setTimeout(() => location.reload(), 2000);
    } else {
      msg.textContent = '✗ ' + d.error;
      msg.style.cssText = 'margin-top:8px;font-size:12px;display:block;color:#ef4444';
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="key" style="width:14px;height:14px"></i> Ativar licença';
      lucide.createIcons();
    }
  } catch(e) {
    msg.textContent = '✗ Erro de conexão. Tente novamente.';
    msg.style.cssText = 'margin-top:8px;font-size:12px;display:block;color:#ef4444';
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="key" style="width:14px;height:14px"></i> Ativar licença';
    lucide.createIcons();
  }
}
</script>

<!-- Modal: instrução para conectar conta ML -->
<div id="modal-conectar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:center;justify-content:center;z-index:1000;padding:16px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:460px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6)">
    <div style="padding:20px 24px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:10px">
      <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,230,0,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i data-lucide="alert-circle" style="width:18px;height:18px;color:#FFE600"></i>
      </div>
      <div>
        <div style="font-size:14px;font-weight:600;color:#E8E8E6">Antes de conectar</div>
        <div style="font-size:11px;color:#5E5E5A">Leia as instruções abaixo</div>
      </div>
      <button onclick="fecharModalConectar()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>

    <div style="padding:20px 24px">
      <p style="font-size:13px;color:#9A9A96;margin-bottom:16px;line-height:1.7">
        O SAM conecta contas do Mercado Livre via <strong style="color:#E8E8E6">autorização OAuth</strong> — não armazenamos sua senha. Ao clicar em continuar, você será redirecionado para o site do ML.
      </p>

      <div style="background:#252528;border-radius:10px;padding:14px 16px;margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;color:#FFE600;margin-bottom:10px;display:flex;align-items:center;gap:5px">
          <i data-lucide="list-ordered" style="width:12px;height:12px"></i> Siga os passos:
        </div>
        <?php foreach ([
          ['1', 'Abra o Mercado Livre em outra aba e <strong>faça login na conta que deseja conectar</strong>'],
          ['2', 'Volte aqui e clique em <strong>Continuar para o ML</strong>'],
          ['3', 'O ML exibirá uma tela pedindo autorização para o SAM — clique em <strong>Permitir</strong>'],
          ['4', 'Você será redirecionado de volta ao SAM automaticamente'],
        ] as [$n, $txt]): ?>
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px">
          <div style="width:20px;height:20px;border-radius:50%;background:#FFE600;color:#1A1A1A;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0;margin-top:1px"><?= $n ?></div>
          <div style="font-size:12px;color:#9A9A96;line-height:1.5"><?= $txt ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="background:rgba(239,68,68,.06);border:0.5px solid rgba(239,68,68,.2);border-radius:8px;padding:10px 12px;margin-bottom:16px;font-size:11px;color:#ef4444;line-height:1.5">
        ⚠ Certifique-se de estar logado na conta ML correta antes de continuar. Se conectar a conta errada, desconecte e repita o processo.
      </div>

      <div style="display:flex;gap:8px;justify-content:flex-end">
        <button onclick="fecharModalConectar()" class="btn-secondary" style="font-size:12px">Cancelar</button>
        <button onclick="irParaML()" style="display:flex;align-items:center;gap:6px;padding:9px 18px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer">
          <i data-lucide="external-link" style="width:13px;height:13px"></i> Continuar para o ML
        </button>
      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function abrirModalConectar() {
  document.getElementById('modal-conectar').style.display = 'flex';
  lucide.createIcons();
}
function fecharModalConectar() {
  document.getElementById('modal-conectar').style.display = 'none';
}
function irParaML() {
  window.location.href = '/meli/connect.php';
}

// ── WhatsApp config ───────────────────────────────────────
async function saveWppConfig() {
  const fd = new FormData();
  fd.append('action',   'save_wpp_config');
  fd.append('key',      document.getElementById('wpp-key').value.trim());
  fd.append('instance', document.getElementById('wpp-instance').value.trim());
  fd.append('owner',    document.getElementById('wpp-owner').value.trim());
  const r = await fetch('/pages/config_ml.php', {method:'POST', body:fd});
  const d = await r.json();
  d.ok ? toast('Configurações WhatsApp salvas!', 'success') : toast(d.error || 'Erro', 'error');
}

async function testarWpp(e) {
  const fd = new FormData();
  fd.append('action', 'test_wpp');
  const btn = e?.currentTarget || e?.target;
  if (btn) { btn.disabled = true; btn.textContent = 'Enviando...'; }
  const r = await fetch('/pages/config_ml.php', {method:'POST', body:fd});
  const d = await r.json();
  if (btn) { btn.disabled = false; btn.innerHTML = '<i data-lucide="send" style="width:12px;height:12px"></i> Testar'; lucide.createIcons(); }
  d.ok ? toast('✅ Mensagem de teste enviada!', 'success') : toast('❌ Falha: código ' + (d.code||'?'), 'error');
}

// ── Salvar use provider ──────────────────────────────────
async function saveUseProvider(use, provider) {
  const fd = new FormData();
  fd.append('action',   'save_use_provider');
  fd.append('use',      use);
  fd.append('provider', provider);
  const r = await fetch('/api/ai_config.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) toast(`IA da seção "${use}" alterada para ${provider}`, 'success');
  else toast('Erro ao salvar', 'error');
}

// ── Multi-IA — seletor e chaves ───────────────────────────
function selectProvider(provider) {
  // Atualiza bordas dos labels
  <?php foreach (array_keys(AI_PROVIDERS) as $pid): ?>
  document.getElementById('ai-label-<?= $pid ?>').style.borderColor = provider === '<?= $pid ?>' ? '<?= AI_PROVIDERS[$pid]['color'] ?>' : '#2E2E33';
  document.getElementById('ai-key-block-<?= $pid ?>').style.display = provider === '<?= $pid ?>' ? 'block' : 'none';
  <?php endforeach; ?>

  // Salva provedor ativo
  const fd = new FormData();
  fd.append('action',   'save_provider');
  fd.append('provider', provider);
  fetch('/api/ai_config.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => { if (d.ok) toast('Provedor ' + provider + ' selecionado!', 'success'); });
}

async function testAndSaveKey(provider) {
  const key = document.getElementById('inp-ai-' + provider)?.value.trim();
  if (!key) { toast('Cole a API key', 'error'); return; }

  const btn    = document.getElementById('btn-test-' + provider);
  const result = document.getElementById('ai-test-result-' + provider);
  const origHtml = btn.innerHTML;

  btn.disabled  = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:11px;height:11px;animation:spin 1s linear infinite"></i> Testando...';
  lucide.createIcons();

  try {
    const r = await fetch(`/api/ai_config.php?action=test&provider=${provider}&key=${encodeURIComponent(key)}`);
    const d = await r.json();

    result.style.display = 'block';
    if (d.ok) {
      result.innerHTML = `<span style="color:#22c55e">✓ Conectado em ${d.latency_ms}ms · Resposta: "${d.response}"</span>`;
      // Salva a chave
      const fd = new FormData();
      fd.append('action',   'save_key');
      fd.append('provider', provider);
      fd.append('key',      key);
      await fetch('/api/ai_config.php', {method:'POST', body:fd});
      // Atualiza status
      const statusEl = document.getElementById('ai-status-' + provider);
      if (statusEl) statusEl.textContent = '● Configurado';
      statusEl.style.color = '#22c55e';
      toast('Chave ' + provider + ' salva e testada!', 'success');
    } else {
      result.innerHTML = `<span style="color:#ef4444">✗ ${d.error || 'Falha na conexão'} (${d.latency_ms}ms)</span>`;
      toast('Chave inválida — verifique', 'error');
    }
  } catch(e) {
    result.innerHTML = '<span style="color:#ef4444">✗ Erro de conexão</span>';
    toast('Erro ao testar', 'error');
  }

  btn.disabled  = false;
  btn.innerHTML = origHtml;
  lucide.createIcons();
}

// ── Toggle robô ───────────────────────────────────────────
async function toggleBot(checkbox) {
  const enabled = checkbox.checked;
  const slider  = document.getElementById('bot-slider');
  const knob    = document.getElementById('bot-knob');
  const badge   = document.getElementById('bot-status-badge');
  if (slider) { slider.style.background = enabled ? '#22c55e' : '#3E3E45'; }
  if (knob)   { knob.style.left = enabled ? '20px' : '2px'; }
  const fd = new FormData();
  fd.append('action',              'save_bot_config');
  fd.append('ai_bot_questions',    enabled ? '1' : '0');
  fd.append('ai_bot_instructions', document.getElementById('bot-instructions')?.value || '');
  const r = await fetch('/pages/config_ml.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    if (badge) {
      badge.textContent      = enabled ? 'ATIVO' : 'INATIVO';
      badge.style.background = enabled ? 'rgba(34,197,94,.15)' : 'rgba(94,94,90,.15)';
      badge.style.color      = enabled ? '#22c55e' : '#5E5E5A';
    }
    toast(enabled ? 'Robô IA ativado!' : 'Robô IA desativado', enabled ? 'success' : 'info');
  } else {
    checkbox.checked = !enabled;
    if (slider) slider.style.background = !enabled ? '#22c55e' : '#3E3E45';
    if (knob)   knob.style.left = !enabled ? '20px' : '2px';
    toast('Erro ao salvar', 'error');
  }
}

// ── Salvar config do robô ─────────────────────────────────
async function saveBotConfig() {
  const enabled = document.getElementById('bot-toggle')?.checked;
  const fd = new FormData();
  fd.append('action',              'save_bot_config');
  fd.append('ai_bot_questions',    enabled ? '1' : '0');
  fd.append('ai_bot_instructions', document.getElementById('bot-instructions')?.value || '');
  const r = await fetch('/pages/config_ml.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    const badge = document.getElementById('bot-status-badge');
    if (badge) {
      badge.textContent      = enabled ? 'ATIVO' : 'INATIVO';
      badge.style.background = enabled ? 'rgba(34,197,94,.15)' : 'rgba(94,94,90,.15)';
      badge.style.color      = enabled ? '#22c55e' : '#5E5E5A';
    }
    toast('Configurações do robô salvas!', 'success');
  } else {
    toast('Erro ao salvar', 'error');
  }
}

// ── Desconectar conta ML ──────────────────────────────────
async function disconnectAccount(id) {
  if (!await dialog({title:'Desconectar Conta',message:'Deseja desconectar esta conta do Mercado Livre?',confirmText:'Desconectar',danger:true})) return;
  const fd = new FormData();
  fd.append('action',     'disconnect');
  fd.append('account_id', id);
  const r = await fetch('/pages/config_ml.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Conta desconectada', 'info'); setTimeout(() => location.reload(), 1000); }
  else       { toast('Erro ao desconectar', 'error'); }
}

// ── Copiar para clipboard ─────────────────────────────────
function copyToClipboard(id) {
  const el = document.getElementById(id);
  if (!el) return;
  navigator.clipboard.writeText(el.textContent.trim())
    .then(() => toast('Copiado!', 'success'))
    .catch(() => toast('Erro ao copiar', 'error'));
}

// ── Mostrar/ocultar senha ─────────────────────────────────
function togglePass(inputId, iconId) {
  const inp  = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (!inp) return;
  if (inp.type === 'password') {
    inp.type = 'text';
    if (icon) icon.setAttribute('data-lucide', 'eye-off');
  } else {
    inp.type = 'password';
    if (icon) icon.setAttribute('data-lucide', 'eye');
  }
  lucide.createIcons();
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
    </div><!-- fim coluna direita -->

  </div><!-- fim grid -->

