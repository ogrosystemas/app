<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

$filter  = in_array($_GET['filter'] ?? 'all', ['all','unanswered','answered']) ? $_GET['filter'] : 'all';
$acctSql = $acctId ? " AND meli_account_id=?" : "";
$acctP   = $acctId ? [$acctId] : [];

$botEnabled = false;
try {
    $row = db_one("SELECT value FROM tenant_settings WHERE tenant_id=? AND `key`='ai_bot_questions'", [$tenantId]);
    $botEnabled = ($row['value'] ?? '0') === '1';
} catch (Throwable $e) {}

$totalQ      = (int)(db_one("SELECT COUNT(*) as cnt FROM questions WHERE tenant_id=?{$acctSql}", array_merge([$tenantId], $acctP))['cnt'] ?? 0);
$unansweredQ = (int)(db_one("SELECT COUNT(*) as cnt FROM questions WHERE tenant_id=?{$acctSql} AND status='UNANSWERED'", array_merge([$tenantId], $acctP))['cnt'] ?? 0);
$answeredQ   = $totalQ - $unansweredQ;

$statusSql = $filter === 'unanswered' ? " AND status='UNANSWERED'" : ($filter === 'answered' ? " AND status='ANSWERED'" : '');

$questions = db_all(
    "SELECT * FROM questions WHERE tenant_id=?{$acctSql}{$statusSql} ORDER BY created_at DESC LIMIT 50",
    array_merge([$tenantId], $acctP)
);

$title = 'Perguntas Pré-venda';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Perguntas Pré-venda</h1>
      <p style="font-size:11px;color:#5E5E5A">Atualizadas automaticamente via webhook</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <div style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:<?= $botEnabled?'rgba(34,197,94,.1)':'rgba(94,94,90,.1)' ?>;border:0.5px solid <?= $botEnabled?'#22c55e':'#3E3E45' ?>;border-radius:8px">
        <i data-lucide="bot" style="width:12px;height:12px;color:<?= $botEnabled?'#22c55e':'#5E5E5A' ?>"></i>
        <span style="font-size:11px;font-weight:600;color:<?= $botEnabled?'#22c55e':'#5E5E5A' ?>">Robô <?= $botEnabled?'Ativo':'Inativo' ?></span>
      </div>
      <a href="/pages/config_ml.php" style="padding:7px 12px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:8px;font-size:11px;text-decoration:none;display:flex;align-items:center;gap:5px">
        <i data-lucide="settings" style="width:11px;height:11px"></i> Configurar robô
      </a>
    </div>
  </div>

  <!-- KPIs -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Total</div>
      <div style="font-size:20px;font-weight:700;color:#E8E8E6"><?= $totalQ ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid rgba(52,131,250,.3);border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Sem resposta</div>
      <div style="font-size:20px;font-weight:700;color:#3483FA"><?= $unansweredQ ?></div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid rgba(34,197,94,.3);border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Respondidas</div>
      <div style="font-size:20px;font-weight:700;color:#22c55e"><?= $answeredQ ?></div>
    </div>
  </div>

  <!-- Filtros -->
  <div style="display:flex;gap:6px;margin-bottom:16px">
    <?php foreach (['all'=>'Todas','unanswered'=>'Não respondidas','answered'=>'Respondidas'] as $k=>$label): ?>
    <a href="?filter=<?= $k ?>" style="padding:6px 14px;border-radius:8px;font-size:11px;font-weight:500;text-decoration:none;border:0.5px solid <?= $filter===$k?'#3483FA':'#2E2E33' ?>;background:<?= $filter===$k?'rgba(52,131,250,.1)':'transparent' ?>;color:<?= $filter===$k?'#3483FA':'#5E5E5A' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($questions)): ?>
  <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
    <i data-lucide="help-circle" style="width:32px;height:32px;color:#5E5E5A;margin:0 auto 12px;display:block"></i>
    <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:4px">Nenhuma pergunta</div>
    <div style="font-size:11px;color:#5E5E5A">As perguntas chegam automaticamente via webhook do ML</div>
  </div>
  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach ($questions as $q):
      $answered = $q['status'] === 'ANSWERED';
      $isRobot  = $answered && $q['answer_by_robot'];
      $date     = date('d/m H:i', strtotime($q['created_at']));
    ?>
    <div style="background:#1A1A1C;border:0.5px solid <?= $answered?'#2E2E33':'rgba(52,131,250,.25)' ?>;border-radius:12px;padding:16px">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px">
        <div style="flex:1;min-width:0">
          <?php if ($q['item_title']): ?>
          <div style="font-size:11px;font-weight:600;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px">
            <i data-lucide="tag" style="width:10px;height:10px;color:#3483FA;margin-right:3px"></i><?= htmlspecialchars($q['item_title']) ?>
          </div>
          <?php endif; ?>
          <div style="display:flex;align-items:center;gap:6px">
            <div style="width:18px;height:18px;border-radius:50%;background:#252528;display:flex;align-items:center;justify-content:center;font-size:8px;color:#9A9A96"><?= strtoupper(mb_substr($q['buyer_nickname']??'?',0,1)) ?></div>
            <span style="font-size:11px;color:#9A9A96"><?= htmlspecialchars($q['buyer_nickname']??'Comprador') ?></span>
            <span style="font-size:10px;color:#5E5E5A"><?= $date ?></span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
          <?php if ($isRobot): ?>
          <span style="font-size:9px;padding:2px 7px;border-radius:8px;background:rgba(34,197,94,.1);color:#22c55e;display:flex;align-items:center;gap:3px"><i data-lucide="bot" style="width:9px;height:9px"></i> Robô</span>
          <?php endif; ?>
          <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;<?= $answered?'background:rgba(34,197,94,.1);color:#22c55e':'background:rgba(52,131,250,.12);color:#3483FA' ?>">
            <?= $answered?'✓ Respondida':'Pendente' ?>
          </span>
        </div>
      </div>

      <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-left:3px solid <?= $answered?'#2E2E33':'#3483FA' ?>;border-radius:8px;padding:10px 12px;margin-bottom:<?= $answered?'10px':'12px' ?>">
        <div style="font-size:12px;color:#E8E8E6;line-height:1.5"><?= htmlspecialchars($q['question_text']) ?></div>
      </div>

      <?php if ($answered && $q['answer_text']): ?>
      <div style="background:#252528;border:0.5px solid #2E2E33;border-left:3px solid <?= $isRobot?'#22c55e':'#3483FA' ?>;border-radius:8px;padding:10px 12px;margin-bottom:12px">
        <div style="font-size:9px;color:<?= $isRobot?'#22c55e':'#3483FA' ?>;font-weight:600;margin-bottom:4px;display:flex;align-items:center;gap:4px">
          <?php if ($isRobot): ?><i data-lucide="bot" style="width:9px;height:9px"></i> ROBÔ<?php else: ?>SUA RESPOSTA<?php endif; ?>
          <?php if ($q['answered_at']): ?><span style="color:#5E5E5A;font-weight:400">· <?= date('d/m', strtotime($q['answered_at'])) ?></span><?php endif; ?>
        </div>
        <div style="font-size:11px;color:#9A9A96;line-height:1.5"><?= htmlspecialchars($q['answer_text']) ?></div>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if (!$answered): ?>
        <button onclick="openAnswerModal('<?= $q['meli_question_id'] ?>',`<?= htmlspecialchars(addslashes($q['question_text'])) ?>`, '<?= $q['id'] ?>')"
          style="padding:7px 14px;background:#3483FA;border:none;color:#fff;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">
          <i data-lucide="reply" style="width:11px;height:11px"></i> Responder
        </button>
        <?php endif; ?>
        <?php if ($q['meli_item_id']): ?>
        <a href="https://www.mercadolivre.com.br/p/<?= $q['meli_item_id'] ?>" target="_blank"
          style="padding:7px 12px;background:rgba(94,94,90,.1);border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:8px;font-size:11px;text-decoration:none;display:flex;align-items:center;gap:5px">
          <i data-lucide="external-link" style="width:11px;height:11px"></i> Ver anúncio
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="text-align:right;padding:8px 0;font-size:11px;color:#5E5E5A">
      <?= count($questions) ?> pergunta<?= count($questions)!==1?'s':'' ?> · <?= $unansweredQ ?> sem resposta
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
lucide.createIcons();

function openAnswerModal(questionId, questionText, localId) {
  const existing = document.getElementById('answer-modal');
  if (existing) existing.remove();
  const modal = document.createElement('div');
  modal.id = 'answer-modal';
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:1000;padding:16px';
  modal.innerHTML = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:500px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <i data-lucide="reply" style="width:14px;height:14px;color:#3483FA"></i>
        <span style="font-size:14px;font-weight:600;color:#E8E8E6">Responder pergunta</span>
      </div>
      <div style="background:#0F0F10;border:0.5px solid #2E2E33;border-left:3px solid #3483FA;border-radius:8px;padding:10px 12px;margin-bottom:14px">
        <div style="font-size:9px;color:#3483FA;font-weight:600;margin-bottom:4px">PERGUNTA</div>
        <div style="font-size:12px;color:#E8E8E6;line-height:1.5">${questionText}</div>
      </div>
      <textarea id="answer-text" placeholder="Escreva sua resposta pública..."
        style="width:100%;height:100px;padding:10px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;resize:vertical;outline:none;line-height:1.5;box-sizing:border-box"></textarea>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
        <button onclick="toggleQuickReplies()" style="font-size:10px;color:#3483FA;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px">
          <i data-lucide="book-open" style="width:10px;height:10px"></i> Respostas prontas
        </button>
        <span style="font-size:10px;color:#5E5E5A"><span id="answer-char">0</span>/2000</span>
      </div>
      <!-- Lista de respostas prontas -->
      <div id="quick-replies-panel" style="display:none;background:#0F0F10;border:0.5px solid #2E2E33;border-radius:8px;padding:8px;max-height:160px;overflow-y:auto;margin-top:8px">
        <div id="quick-replies-content" style="font-size:11px;color:#5E5E5A">Carregando...</div>
      </div>
      <div style="display:flex;gap:8px;margin-top:14px">
        <button onclick="loadPerguntasReplies()" id="qr-perguntas-btn"
          style="padding:7px 12px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:8px;font-size:11px;cursor:pointer;display:flex;align-items:center;gap:5px">
          <i data-lucide="message-square-reply" style="width:11px;height:11px"></i> Prontas
        </button>
        <button onclick="submitAnswer('${questionId}','${localId}')" id="answer-submit-btn" class="btn-primary" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px">
          <i data-lucide="send" style="width:12px;height:12px"></i> Publicar resposta
        </button>
        <button onclick="document.getElementById('answer-modal').remove()" class="btn-secondary">Cancelar</button>
      </div>
      <div id="qr-perguntas-list" style="margin-top:10px;display:none;max-height:200px;overflow-y:auto;display:none;flex-direction:column;gap:4px"></div>
    </div>`;
  document.body.appendChild(modal);
  lucide.createIcons();
  const ta = document.getElementById('answer-text');
  ta.addEventListener('input', () => document.getElementById('answer-char').textContent = ta.value.length);

  // Carregar respostas prontas
  loadQuickReplies();
  setTimeout(() => ta.focus(), 100);
}

async function loadQuickReplies() {
  const r = await fetch('/api/sprint1.php?action=list_replies');
  const d = await r.json();
  const el = document.getElementById('quick-replies-content');
  if (!d.replies?.length) {
    el.innerHTML = '<div style="text-align:center;padding:8px;color:#5E5E5A">Nenhuma resposta pronta cadastrada. <a href="/pages/ferramentas.php" style="color:#3483FA">Criar agora</a></div>';
    return;
  }
  el.innerHTML = d.replies.map(r => `
    <div onclick="useQuickReply('${r.id}', \`${r.body.replace(/`/g,"'")}\`)"
      style="padding:8px 10px;border-radius:6px;cursor:pointer;margin-bottom:4px;border:0.5px solid #2E2E33"
      onmouseover="this.style.background='#252528'" onmouseout="this.style.background='transparent'">
      <div style="font-size:11px;font-weight:500;color:#E8E8E6;margin-bottom:2px">${r.title}</div>
      <div style="font-size:10px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.body}</div>
    </div>`).join('');
}

function toggleQuickReplies() {
  const panel = document.getElementById('quick-replies-panel');
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

async function useQuickReply(id, body) {
  document.getElementById('answer-text').value = body;
  document.getElementById('answer-char').textContent = body.length;
  document.getElementById('quick-replies-panel').style.display = 'none';
  // Incrementa uso
  const fd = new FormData(); fd.append('action','use_reply'); fd.append('id',id);
  fetch('/api/sprint1.php', {method:'POST',body:fd});
}

async function loadPerguntasReplies() {
  const el = document.getElementById('qr-perguntas-list');
  el.style.display = 'flex';
  el.innerHTML = '<div style="color:#5E5E5A;font-size:11px;padding:8px">Carregando...</div>';
  const r = await fetch('/api/quick_replies.php?context=perguntas');
  const d = await r.json();
  if (!d.replies?.length) {
    el.innerHTML = '<div style="color:#5E5E5A;font-size:11px;padding:8px">Nenhuma resposta cadastrada. <a href="/pages/respostas.php" target="_blank" style="color:#3483FA">Cadastrar →</a></div>';
    return;
  }
  el.innerHTML = d.replies.map(r => `
    <div onclick="document.getElementById('answer-text').value=\`${r.body.replace(/`/g,"'")}\`; document.getElementById('qr-perguntas-list').style.display='none'"
      style="padding:8px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:6px;cursor:pointer">
      <div style="font-size:11px;font-weight:500;color:#E8E8E6">${r.title}</div>
      <div style="font-size:10px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${r.body}</div>
    </div>
  `).join('');
}

async function submitAnswer(questionId, localId) {
  const text = document.getElementById('answer-text').value.trim();
  if (!text) { toast('Escreva uma resposta', 'warning'); return; }
  const btn = document.getElementById('answer-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" style="width:12px;height:12px;animation:spin 1s linear infinite"></i> Publicando...';
  lucide.createIcons();
  const fd = new FormData();
  fd.append('question_id', questionId);
  fd.append('local_id',    localId);
  fd.append('answer',      text);
  const r = await fetch('/api/anuncios_questions.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast('Resposta publicada!', 'success');
    document.getElementById('answer-modal').remove();
    location.reload();
  } else {
    toast(d.error || 'Erro ao publicar', 'error');
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send" style="width:12px;height:12px"></i> Publicar resposta';
    lucide.createIcons();
  }
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>
<?php include __DIR__ . '/layout_end.php'; ?>
