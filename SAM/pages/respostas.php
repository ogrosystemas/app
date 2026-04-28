<?php
/**
 * pages/respostas.php
 * Banco de respostas prontas — cadastro e gestão
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_sac');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// Cria tabela se não existir
try {
    db_query("CREATE TABLE IF NOT EXISTS quick_replies (
        id VARCHAR(36) NOT NULL, tenant_id VARCHAR(36) NOT NULL,
        title VARCHAR(100) NOT NULL, body TEXT NOT NULL,
        context ENUM('sac','perguntas','ambos') NOT NULL DEFAULT 'ambos',
        tags VARCHAR(255) NULL, uses_count INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id    = trim($_POST['id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        $ctx   = in_array($_POST['context']??'ambos',['sac','perguntas','ambos']) ? $_POST['context'] : 'ambos';
        $tags  = trim($_POST['tags'] ?? '');

        if (!$title || !$body) { echo json_encode(['ok'=>false,'error'=>'Título e texto são obrigatórios']); exit; }

        if ($id) {
            db_update('quick_replies', ['title'=>$title,'body'=>$body,'context'=>$ctx,'tags'=>$tags], 'id=? AND tenant_id=?', [$id,$tenantId]);
        } else {
            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            db_insert('quick_replies', ['id'=>$id,'tenant_id'=>$tenantId,'title'=>$title,'body'=>$body,'context'=>$ctx,'tags'=>$tags]);
        }
        echo json_encode(['ok'=>true,'id'=>$id]);
        exit;
    }

    if ($action === 'delete') {
        $id = trim($_POST['id'] ?? '');
        if ($id) db_query("DELETE FROM quick_replies WHERE id=? AND tenant_id=?", [$id,$tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'use') {
        $id = trim($_POST['id'] ?? '');
        if ($id) db_query("UPDATE quick_replies SET uses_count=uses_count+1 WHERE id=? AND tenant_id=?", [$id,$tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

// Busca respostas
$search  = trim($_GET['q'] ?? '');
$context = $_GET['ctx'] ?? 'all';
$sql     = "SELECT * FROM quick_replies WHERE tenant_id=?";
$params  = [$tenantId];
if ($search) { $sql .= " AND (title LIKE ? OR body LIKE ? OR tags LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params,[$s,$s,$s]); }
if ($context !== 'all') { $sql .= " AND context IN (?,?)"; $params[] = $context; $params[] = 'ambos'; }
$sql .= " ORDER BY uses_count DESC, updated_at DESC LIMIT 100";
$replies = db_all($sql, $params);

$title = 'Respostas Prontas';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <!-- Header -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Banco de Respostas Prontas</h1>
      <p style="font-size:11px;color:#5E5E5A">Respostas rápidas para SAC e Perguntas Pré-venda</p>
    </div>
    <button onclick="openModal()" class="btn-primary">
      <i data-lucide="plus" style="width:13px;height:13px"></i> Nova resposta
    </button>
  </div>

  <!-- Filtros -->
  <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <input type="text" id="search-input" placeholder="Buscar por título, texto ou tags..."
      value="<?= htmlspecialchars($search) ?>"
      onkeydown="if(event.key==='Enter')applyFilter()"
      style="flex:1;min-width:200px;padding:8px 12px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none">
    <select id="ctx-filter" onchange="applyFilter()" style="padding:8px 10px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;cursor:pointer">
      <option value="all" <?= $context==='all'?'selected':'' ?>>Todos os contextos</option>
      <option value="sac" <?= $context==='sac'?'selected':'' ?>>SAC</option>
      <option value="perguntas" <?= $context==='perguntas'?'selected':'' ?>>Perguntas</option>
    </select>
    <button onclick="applyFilter()" class="btn-secondary" style="font-size:12px;padding:8px 14px">
      <i data-lucide="search" style="width:12px;height:12px"></i> Buscar
    </button>
  </div>

  <!-- KPIs rápidos -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px">
    <?php
    $total = count($replies);
    $sac   = count(array_filter($replies, fn($r) => in_array($r['context'],['sac','ambos'])));
    $pergs = count(array_filter($replies, fn($r) => in_array($r['context'],['perguntas','ambos'])));
    foreach ([['Total',  $total,'#E8E8E6','#2E2E33'],['Para SAC',$sac,'#3483FA','rgba(52,131,250,.3)'],['Para Perguntas',$pergs,'#22c55e','rgba(34,197,94,.3)']] as [$label,$val,$color,$border]):
    ?>
    <div style="background:#1A1A1C;border:0.5px solid <?= $border ?>;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:3px"><?= $label ?></div>
      <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Lista -->
  <?php if (empty($replies)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="message-square-plus" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma resposta cadastrada</div>
    <div style="font-size:11px;color:#5E5E5A;margin-bottom:16px">Cadastre respostas prontas para usar no SAC e Perguntas</div>
    <button onclick="openModal()" class="btn-primary" style="font-size:12px">
      <i data-lucide="plus" style="width:12px;height:12px"></i> Cadastrar primeira resposta
    </button>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($replies as $r):
      $ctxColor = $r['context']==='sac' ? '#3483FA' : ($r['context']==='perguntas' ? '#22c55e' : '#f59e0b');
      $ctxLabel = $r['context']==='sac' ? 'SAC' : ($r['context']==='perguntas' ? 'Perguntas' : 'SAC + Perguntas');
      $tags = array_filter(array_map('trim', explode(',', $r['tags'] ?? '')));
    ?>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:14px 16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px">
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <span style="font-size:13px;font-weight:500;color:#E8E8E6"><?= htmlspecialchars($r['title']) ?></span>
            <span style="font-size:9px;padding:2px 7px;border-radius:6px;background:<?= $ctxColor ?>20;color:<?= $ctxColor ?>;font-weight:600"><?= $ctxLabel ?></span>
            <?php if ($r['uses_count'] > 0): ?>
            <span style="font-size:9px;color:#5E5E5A">usado <?= $r['uses_count'] ?>×</span>
            <?php endif; ?>
          </div>
          <div style="font-size:12px;color:#9A9A96;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
            <?= htmlspecialchars($r['body']) ?>
          </div>
          <?php if (!empty($tags)): ?>
          <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:6px">
            <?php foreach ($tags as $tag): ?>
            <span style="font-size:9px;padding:2px 6px;background:#252528;border:0.5px solid #2E2E33;border-radius:4px;color:#5E5E5A">#<?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
          <button onclick="editReply(<?= htmlspecialchars(json_encode($r)) ?>)"
            style="padding:6px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;cursor:pointer;display:flex;align-items:center">
            <i data-lucide="pencil" style="width:12px;height:12px"></i>
          </button>
          <button onclick="deleteReply('<?= $r['id'] ?>','<?= htmlspecialchars(addslashes($r['title'])) ?>')"
            style="padding:6px;background:rgba(239,68,68,.1);border:0.5px solid rgba(239,68,68,.3);color:#ef4444;border-radius:6px;cursor:pointer;display:flex;align-items:center">
            <i data-lucide="trash-2" style="width:12px;height:12px"></i>
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal cadastro/edição -->
<div id="reply-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);display:none;align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
      <i data-lucide="message-square-plus" style="width:16px;height:16px;color:#3483FA"></i>
      <span id="modal-title-label" style="font-size:15px;font-weight:600;color:#E8E8E6">Nova resposta pronta</span>
    </div>
    <input type="hidden" id="edit-id">
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Título <span style="color:#ef4444">*</span></label>
      <input type="text" id="edit-title" placeholder="Ex: Prazo de entrega, Garantia do produto..."
        class="input" maxlength="100">
    </div>
    <div style="margin-bottom:12px">
      <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Texto da resposta <span style="color:#ef4444">*</span></label>
      <textarea id="edit-body" placeholder="Olá! O prazo de entrega é de 3 a 7 dias úteis após confirmação do pagamento..."
        style="width:100%;height:120px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;line-height:1.5;box-sizing:border-box"></textarea>
      <div style="text-align:right;font-size:10px;color:#5E5E5A;margin-top:2px"><span id="body-count">0</span> caracteres</div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Contexto de uso</label>
        <select id="edit-context" style="width:100%;padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px">
          <option value="ambos">SAC + Perguntas</option>
          <option value="sac">Só SAC</option>
          <option value="perguntas">Só Perguntas</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Tags (separadas por vírgula)</label>
        <input type="text" id="edit-tags" placeholder="entrega, prazo, garantia"
          style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="saveReply()" id="save-btn" class="btn-primary" style="flex:1;justify-content:center">
        <i data-lucide="save" style="width:13px;height:13px"></i> Salvar resposta
      </button>
      <button onclick="closeModal()" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function applyFilter() {
  const q   = document.getElementById('search-input').value;
  const ctx = document.getElementById('ctx-filter').value;
  window.location.href = `/pages/respostas.php?q=${encodeURIComponent(q)}&ctx=${ctx}`;
}

function openModal(data = null) {
  document.getElementById('edit-id').value    = data?.id    || '';
  document.getElementById('edit-title').value = data?.title || '';
  document.getElementById('edit-body').value  = data?.body  || '';
  document.getElementById('edit-context').value = data?.context || 'ambos';
  document.getElementById('edit-tags').value  = data?.tags  || '';
  document.getElementById('body-count').textContent = (data?.body||'').length;
  document.getElementById('modal-title-label').textContent = data ? 'Editar resposta' : 'Nova resposta pronta';
  document.getElementById('reply-modal').style.display = 'flex';
  setTimeout(() => document.getElementById('edit-title').focus(), 100);
}

function closeModal() {
  document.getElementById('reply-modal').style.display = 'none';
}

function editReply(data) { openModal(data); }

document.getElementById('edit-body').addEventListener('input', function() {
  document.getElementById('body-count').textContent = this.value.length;
});

async function saveReply() {
  const title = document.getElementById('edit-title').value.trim();
  const body  = document.getElementById('edit-body').value.trim();
  if (!title || !body) { toast('Preencha título e texto', 'error'); return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  const fd = new FormData();
  fd.append('action',  'save');
  fd.append('id',      document.getElementById('edit-id').value);
  fd.append('title',   title);
  fd.append('body',    body);
  fd.append('context', document.getElementById('edit-context').value);
  fd.append('tags',    document.getElementById('edit-tags').value);

  const r = await fetch('/pages/respostas.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast('Resposta salva!', 'success');
    closeModal();
    location.reload();
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
    btn.disabled = false;
  }
}

async function deleteReply(id, title) {
  if (!await dialog({title:'Excluir Resposta',message:`Excluir <strong>${title}</strong>?`,confirmText:'Excluir',danger:true})) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  const r = await fetch('/pages/respostas.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Excluída!', 'info'); location.reload(); }
  else toast('Erro ao excluir', 'error');
}

document.getElementById('reply-modal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
