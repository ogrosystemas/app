<?php
/**
 * pages/mensagens_auto.php
 * Configuração de mensagens automáticas pós-venda
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_sac');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// Criar tabelas se não existirem
try {
    db_query("CREATE TABLE IF NOT EXISTS auto_messages (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        name VARCHAR(100) NOT NULL,
        trigger_event ENUM('payment_confirmed','order_shipped','order_delivered','feedback_received') NOT NULL,
        delay_hours INT NOT NULL DEFAULT 0, message_body TEXT NOT NULL,
        is_active TINYINT NOT NULL DEFAULT 1, sent_count INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db_query("CREATE TABLE IF NOT EXISTS auto_messages_log (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        auto_message_id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL,
        meli_order_id VARCHAR(30) NULL, buyer_nickname VARCHAR(100) NULL,
        status ENUM('SENT','FAILED','SKIPPED') NOT NULL DEFAULT 'SENT',
        error_message TEXT NULL, sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uk_msg_order (auto_message_id, order_id),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id      = trim($_POST['id'] ?? '');
        $name    = trim($_POST['name'] ?? '');
        $trigger = $_POST['trigger_event'] ?? '';
        $delay   = (int)($_POST['delay_hours'] ?? 0);
        $body    = trim($_POST['message_body'] ?? '');
        $active  = ($_POST['is_active'] ?? '1') === '1' ? 1 : 0;

        $validTriggers = ['payment_confirmed','order_shipped','order_delivered','feedback_received'];
        if (!$name || !$body || !in_array($trigger, $validTriggers)) {
            echo json_encode(['ok'=>false,'error'=>'Preencha todos os campos']); exit;
        }

        if ($id) {
            db_update('auto_messages', compact('name','trigger_event','delay_hours','message_body','is_active') + ['trigger_event'=>$trigger,'delay_hours'=>$delay,'message_body'=>$body,'is_active'=>$active], 'id=? AND tenant_id=?', [$id,$tenantId]);
        } else {
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            db_insert('auto_messages', ['id'=>$id,'tenant_id'=>$tenantId,'name'=>$name,'trigger_event'=>$trigger,'delay_hours'=>$delay,'message_body'=>$body,'is_active'=>$active]);
        }
        echo json_encode(['ok'=>true,'id'=>$id]); exit;
    }

    if ($action === 'toggle') {
        $id = trim($_POST['id'] ?? '');
        $am = db_one("SELECT is_active FROM auto_messages WHERE id=? AND tenant_id=?", [$id,$tenantId]);
        if ($am) {
            $new = $am['is_active'] ? 0 : 1;
            db_update('auto_messages', ['is_active'=>$new], 'id=? AND tenant_id=?', [$id,$tenantId]);
            echo json_encode(['ok'=>true,'is_active'=>$new]); exit;
        }
        echo json_encode(['ok'=>false]); exit;
    }

    if ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        if ($id) db_query("DELETE FROM auto_messages WHERE id=? AND tenant_id=?", [$id,$tenantId]);
        echo json_encode(['ok'=>true]); exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

$messages = db_all("SELECT am.*, (SELECT COUNT(*) FROM auto_messages_log aml WHERE aml.auto_message_id=am.id) as sent_total FROM auto_messages am WHERE am.tenant_id=? ORDER BY am.trigger_event, am.delay_hours", [$tenantId]);

$triggerLabels = [
    'payment_confirmed' => ['label'=>'Pagamento confirmado','icon'=>'credit-card','color'=>'#22c55e'],
    'order_shipped'     => ['label'=>'Pedido enviado',      'icon'=>'truck',       'color'=>'#3483FA'],
    'order_delivered'   => ['label'=>'Pedido entregue',     'icon'=>'package-check','color'=>'#a855f7'],
    'feedback_received' => ['label'=>'Avaliação recebida',  'icon'=>'star',        'color'=>'#f59e0b'],
];

$variables = [
    '{{comprador}}'      => 'Nome do comprador',
    '{{produto}}'        => 'Nome do produto',
    '{{numero_pedido}}'  => 'Número do pedido ML',
    '{{data_entrega}}'   => 'Prazo de entrega estimado',
    '{{link_rastreio}}'  => 'Link de rastreamento',
];

$title = 'Mensagens Automáticas';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Mensagens Automáticas</h1>
      <p style="font-size:11px;color:#5E5E5A">Envio automático de mensagens ao comprador em eventos do pedido</p>
    </div>
    <button onclick="openModal()" class="btn-primary">
      <i data-lucide="plus" style="width:13px;height:13px"></i> Nova mensagem
    </button>
  </div>

  <!-- Variáveis disponíveis -->
  <div style="background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:11px;color:#9A9A96;line-height:1.8">
    <strong style="color:#3483FA">Variáveis disponíveis no texto:</strong>
    <?php foreach ($variables as $var => $desc): ?>
    <code style="background:#252528;padding:1px 5px;border-radius:4px;color:#E8E8E6;font-size:10px;margin:0 4px"><?= $var ?></code><span style="color:#5E5E5A"><?= $desc ?></span>
    <?php endforeach; ?>
  </div>

  <!-- Lista por evento -->
  <?php if (empty($messages)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="send" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma mensagem configurada</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Configure mensagens automáticas para cada etapa do pedido</div>
    <button onclick="openModal()" class="btn-primary" style="font-size:12px">
      <i data-lucide="plus" style="width:12px;height:12px"></i> Criar primeira mensagem
    </button>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($messages as $m):
      $trig = $triggerLabels[$m['trigger_event']] ?? ['label'=>$m['trigger_event'],'icon'=>'zap','color'=>'#5E5E5A'];
      $delayLabel = $m['delay_hours'] == 0 ? 'Imediato' : "Após {$m['delay_hours']}h";
    ?>
    <div style="background:#1A1A1C;border:0.5px solid <?= $m['is_active'] ? '#2E2E33' : 'rgba(94,94,90,.3)' ?>;border-radius:12px;padding:14px 16px;opacity:<?= $m['is_active'] ? '1' : '0.6' ?>">
      <div style="display:flex;align-items:flex-start;gap:12px">
        <!-- Ícone evento -->
        <div style="width:36px;height:36px;border-radius:8px;background:<?= $trig['color'] ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">
          <i data-lucide="<?= $trig['icon'] ?>" style="width:16px;height:16px;color:<?= $trig['color'] ?>"></i>
        </div>
        <!-- Conteúdo -->
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span style="font-size:13px;font-weight:500;color:#E8E8E6"><?= htmlspecialchars($m['name']) ?></span>
            <span style="font-size:9px;padding:2px 7px;border-radius:6px;background:<?= $trig['color'] ?>15;color:<?= $trig['color'] ?>"><?= $trig['label'] ?></span>
            <span style="font-size:9px;color:#5E5E5A"><?= $delayLabel ?></span>
            <?php if ($m['sent_total'] > 0): ?>
            <span style="font-size:9px;color:#5E5E5A">enviada <?= $m['sent_total'] ?>×</span>
            <?php endif; ?>
          </div>
          <div style="font-size:11px;color:#5E5E5A;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= htmlspecialchars($m['message_body']) ?>
          </div>
        </div>
        <!-- Ações -->
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
          <!-- Toggle ativo/inativo -->
          <button onclick="toggleMessage('<?= $m['id'] ?>',this)"
            style="padding:4px 10px;border-radius:6px;font-size:10px;font-weight:600;cursor:pointer;border:0.5px solid <?= $m['is_active'] ? '#22c55e' : '#3E3E45' ?>;background:<?= $m['is_active'] ? 'rgba(34,197,94,.1)' : 'transparent' ?>;color:<?= $m['is_active'] ? '#22c55e' : '#5E5E5A' ?>">
            <?= $m['is_active'] ? 'Ativa' : 'Pausada' ?>
          </button>
          <button onclick="editMessage(<?= htmlspecialchars(json_encode($m)) ?>)"
            style="padding:6px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;cursor:pointer;display:flex">
            <i data-lucide="pencil" style="width:12px;height:12px"></i>
          </button>
          <button onclick="deleteMessage('<?= $m['id'] ?>','<?= htmlspecialchars(addslashes($m['name'])) ?>')"
            style="padding:6px;background:rgba(239,68,68,.1);border:0.5px solid rgba(239,68,68,.3);color:#ef4444;border-radius:6px;cursor:pointer;display:flex">
            <i data-lucide="trash-2" style="width:12px;height:12px"></i>
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div id="msg-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <i data-lucide="send" style="width:16px;height:16px;color:#3483FA"></i>
      <span id="msg-modal-title" style="font-size:15px;font-weight:600;color:#E8E8E6">Nova mensagem automática</span>
    </div>
    <input type="hidden" id="msg-id">

    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Nome da mensagem</label>
      <input type="text" id="msg-name" placeholder="Ex: Agradecimento pós-pagamento" class="input">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Evento gatilho</label>
        <select id="msg-trigger" style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px">
          <option value="payment_confirmed">Pagamento confirmado</option>
          <option value="order_shipped">Pedido enviado</option>
          <option value="order_delivered">Pedido entregue</option>
          <option value="feedback_received">Avaliação recebida</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Enviar após</label>
        <select id="msg-delay" style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px">
          <option value="0">Imediatamente</option>
          <option value="1">1 hora</option>
          <option value="2">2 horas</option>
          <option value="6">6 horas</option>
          <option value="12">12 horas</option>
          <option value="24">24 horas</option>
          <option value="48">48 horas</option>
        </select>
      </div>
    </div>

    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Texto da mensagem</label>
      <textarea id="msg-body" placeholder="Olá {{comprador}}! Seu pedido {{numero_pedido}} foi confirmado..."
        style="width:100%;height:130px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;line-height:1.5;box-sizing:border-box"></textarea>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px">
        <?php foreach (array_keys($variables) as $var): ?>
        <button onclick="insertVar('<?= $var ?>')" style="font-size:9px;padding:2px 7px;background:#252528;border:0.5px solid #2E2E33;border-radius:4px;color:#9A9A96;cursor:pointer"><?= $var ?></button>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:10px 12px;background:#252528;border-radius:8px">
      <input type="checkbox" id="msg-active" checked style="width:14px;height:14px;accent-color:#22c55e">
      <label for="msg-active" style="font-size:12px;color:#E8E8E6;cursor:pointer">Mensagem ativa (será enviada automaticamente)</label>
    </div>

    <div style="display:flex;gap:8px">
      <button onclick="saveMessage()" id="msg-save-btn" class="btn-primary" style="flex:1;justify-content:center">
        <i data-lucide="save" style="width:13px;height:13px"></i> Salvar mensagem
      </button>
      <button onclick="closeMsgModal()" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function openModal(data = null) {
  document.getElementById('msg-id').value      = data?.id            || '';
  document.getElementById('msg-name').value    = data?.name          || '';
  document.getElementById('msg-trigger').value = data?.trigger_event || 'payment_confirmed';
  document.getElementById('msg-delay').value   = data?.delay_hours   || '0';
  document.getElementById('msg-body').value    = data?.message_body  || '';
  document.getElementById('msg-active').checked = data ? !!parseInt(data.is_active) : true;
  document.getElementById('msg-modal-title').textContent = data ? 'Editar mensagem' : 'Nova mensagem automática';
  document.getElementById('msg-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('msg-name').focus(), 100);
}

function closeMsgModal() { document.getElementById('msg-modal').style.display = 'none'; }
function editMessage(data) { openModal(data); }

function insertVar(v) {
  const ta  = document.getElementById('msg-body');
  const pos = ta.selectionStart;
  ta.value  = ta.value.slice(0,pos) + v + ta.value.slice(ta.selectionEnd);
  ta.setSelectionRange(pos + v.length, pos + v.length);
  ta.focus();
}

async function saveMessage() {
  const name = document.getElementById('msg-name').value.trim();
  const body = document.getElementById('msg-body').value.trim();
  if (!name || !body) { toast('Preencha nome e texto', 'error'); return; }

  const btn = document.getElementById('msg-save-btn');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('action',        'save');
  fd.append('id',            document.getElementById('msg-id').value);
  fd.append('name',          name);
  fd.append('trigger_event', document.getElementById('msg-trigger').value);
  fd.append('delay_hours',   document.getElementById('msg-delay').value);
  fd.append('message_body',  body);
  fd.append('is_active',     document.getElementById('msg-active').checked ? '1' : '0');

  const r = await fetch('/pages/mensagens_auto.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Mensagem salva!', 'success'); closeMsgModal(); location.reload(); }
  else { toast(d.error || 'Erro ao salvar', 'error'); btn.disabled = false; }
}

async function toggleMessage(id, btn) {
  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('id', id);
  const r = await fetch('/pages/mensagens_auto.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    const active = d.is_active;
    btn.textContent = active ? 'Ativa' : 'Pausada';
    btn.style.borderColor = active ? '#22c55e' : '#3E3E45';
    btn.style.background  = active ? 'rgba(34,197,94,.1)' : 'transparent';
    btn.style.color       = active ? '#22c55e' : '#5E5E5A';
    const card = btn.closest('div[style*="border-radius:12px"]');
    if (card) { card.style.opacity = active ? '1' : '0.6'; card.style.borderColor = active ? '#2E2E33' : 'rgba(94,94,90,.3)'; }
    toast(active ? 'Mensagem ativada' : 'Mensagem pausada', active ? 'success' : 'info');
  }
}

async function deleteMessage(id, name) {
  if (!await dialog({title:'Excluir Mensagem',message:`Excluir <strong>${name}</strong>?`,confirmText:'Excluir',danger:true})) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  const r = await fetch('/pages/mensagens_auto.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Excluída!', 'info'); location.reload(); }
}

document.getElementById('msg-modal').addEventListener('click', function(e) {
  if (e.target === this) closeMsgModal();
});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
