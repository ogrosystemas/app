<?php
// admin/event_media.php — Gerenciar mídias de um evento
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
require_once __DIR__ . '/../includes/layout.php';

$db      = db();
$eventId = (int)($_GET['event_id'] ?? 0);

if (!$eventId) redirect(BASE_URL . '/admin/events.php');

$evStmt = $db->prepare('SELECT * FROM events WHERE id=?');
$evStmt->execute([$eventId]);
$event  = $evStmt->fetch();
if (!$event) redirect(BASE_URL . '/admin/events.php');

pageOpen('Mídias — ' . $event['title'], 'events', 'Mídias do Evento');
?>

<div class="page-header">
  <div class="page-header-row">
    <div>
      <h2>📷 <?= e($event['title']) ?></h2>
      <p>Fotos e vídeos enviados pelos membros · <?= formatDate($event['event_date']) ?></p>
    </div>
    <div class="page-header-actions">
      <a href="<?= BASE_URL ?>/admin/events.php" class="btn btn-ghost">← Voltar</a>
    </div>
  </div>
</div>

<!-- Upload admin -->
<div class="card mb-6">
  <div class="card-header">
    <span class="card-title">Adicionar Mídia</span>
  </div>
  <div class="upload-zone" id="upload-zone" onclick="document.getElementById('file-input').click()">
    <input type="file" id="file-input" accept="image/*,video/mp4,video/quicktime,video/webm" multiple>
    <div class="upload-zone-icon">📁</div>
    <div class="upload-zone-text">Clique ou arraste fotos e vídeos aqui</div>
    <div class="upload-zone-hint">JPG, PNG, WebP, GIF, MP4, MOV · Máximo <?= UPLOAD_MAX_MB ?>MB por arquivo</div>
    <div class="upload-progress" id="upload-progress">
      <div id="upload-status" style="font-size:.82rem;color:var(--text-muted);margin-bottom:4px">Enviando...</div>
      <div class="upload-progress-bar"><div class="upload-progress-fill" id="progress-fill"></div></div>
    </div>
  </div>
</div>

<!-- Galeria -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Galeria</span>
    <span class="text-muted" id="media-count" style="font-size:.82rem"></span>
  </div>
  <div class="media-grid" id="media-grid">
    <div style="padding:40px;text-align:center;color:var(--text-muted);grid-column:1/-1">
      Carregando...
    </div>
  </div>
</div>

<!-- Lightbox -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:300;align-items:center;justify-content:center;flex-direction:column">
  <button onclick="closeLightbox()" style="position:absolute;top:16px;right:20px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;z-index:1">✕</button>
  <div id="lb-content" style="max-width:90vw;max-height:85vh;display:flex;align-items:center;justify-content:center"></div>
  <div id="lb-caption" style="color:#ccc;margin-top:12px;font-size:.875rem;text-align:center;max-width:600px;padding:0 20px"></div>
</div>

<script>
const EVENT_ID = <?= $eventId ?>;
const API_UPLOAD = '<?= BASE_URL ?>/api/upload.php';
const API_MEDIA  = '<?= BASE_URL ?>/api/media.php';

// ── Carrega galeria ───────────────────────────────────────────
async function loadMedia() {
  const res = await fetch(`${API_MEDIA}?event_id=${EVENT_ID}`);
  const items = await res.json();
  const grid = document.getElementById('media-grid');
  document.getElementById('media-count').textContent = items.length + ' arquivo(s)';

  if (!items.length) {
    grid.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);grid-column:1/-1">Nenhuma mídia enviada ainda.</div>';
    return;
  }

  grid.innerHTML = items.map(item => `
    <div class="media-item" data-id="${item.id}" data-type="${item.type}" data-url="${item.url}" data-caption="${item.caption||''}">
      ${item.type === 'photo'
        ? `<img src="${item.thumb_url || item.url}" alt="${item.caption||''}" loading="lazy">`
        : `<video src="${item.url}" muted preload="metadata"></video>`
      }
      <div class="media-overlay" onclick="openLightbox('${item.url}','${item.type}','${(item.caption||'').replace(/'/g,"\\'")}')">
        ${item.type === 'video' ? '<span style="font-size:2rem;color:#fff">▶</span>' : ''}
      </div>
      <span class="media-badge">${item.type === 'video' ? '🎥' : '📷'}</span>
      <button class="media-delete" onclick="deleteMedia(${item.id},event)" title="Excluir">✕</button>
      ${item.caption ? `<div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.7);color:#fff;font-size:.7rem;padding:4px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.caption}</div>` : ''}
    </div>
  `).join('');
}

// ── Upload ────────────────────────────────────────────────────
const zone  = document.getElementById('upload-zone');
const input = document.getElementById('file-input');

zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.classList.remove('drag-over');
  uploadFiles([...e.dataTransfer.files]);
});
input.addEventListener('change', () => uploadFiles([...input.files]));

async function uploadFiles(files) {
  if (!files.length) return;
  const progress = document.getElementById('upload-progress');
  const fill     = document.getElementById('progress-fill');
  const status   = document.getElementById('upload-status');
  progress.style.display = 'block';

  for (let i = 0; i < files.length; i++) {
    const f = files[i];
    status.textContent = `Enviando ${i+1}/${files.length}: ${f.name}`;
    fill.style.width = ((i / files.length) * 100) + '%';

    const fd = new FormData();
    fd.append('file', f);
    fd.append('event_id', EVENT_ID);

    const res  = await fetch(API_UPLOAD, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) alert('Erro: ' + data.error);
  }

  fill.style.width = '100%';
  status.textContent = 'Concluído!';
  setTimeout(() => { progress.style.display = 'none'; fill.style.width = '0'; }, 1500);
  input.value = '';
  loadMedia();
}

// ── Deletar mídia ─────────────────────────────────────────────
async function deleteMedia(id, evt) {
  evt.stopPropagation();
  if (!confirm('Excluir esta mídia?')) return;
  await fetch(API_MEDIA, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({id, _method:'DELETE'})
  });
  loadMedia();
}

// ── Lightbox ──────────────────────────────────────────────────
function openLightbox(url, type, caption) {
  const lb = document.getElementById('lightbox');
  const content = document.getElementById('lb-content');
  content.innerHTML = type === 'video'
    ? `<video src="${url}" controls autoplay style="max-width:90vw;max-height:80vh;border-radius:8px"></video>`
    : `<img src="${url}" style="max-width:90vw;max-height:80vh;border-radius:8px;object-fit:contain">`;
  document.getElementById('lb-caption').textContent = caption;
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightbox').style.display = 'none';
  document.getElementById('lb-content').innerHTML = '';
  document.body.style.overflow = '';
}

document.getElementById('lightbox').addEventListener('click', e => {
  if (e.target === document.getElementById('lightbox')) closeLightbox();
});

loadMedia();
</script>

<?php pageClose(); ?>
