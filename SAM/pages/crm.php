<?php
/**
 * pages/crm.php
 * CRM de Compradores — histórico, tags, notas, status
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_crm');

$user     = auth_user();
$tenantId = $user['tenant_id'];

$title = 'CRM';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">CRM de Compradores</h1>
      <p style="font-size:11px;color:#5E5E5A">Histórico, perfil e relacionamento com seus compradores</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <div style="position:relative">
        <i data-lucide="search" style="width:13px;height:13px;color:#5E5E5A;position:absolute;left:10px;top:50%;transform:translateY(-50%)"></i>
        <input type="text" id="crm-search" placeholder="Buscar comprador..."
          oninput="debounceSearch(this.value)"
          style="padding:8px 12px 8px 32px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;width:220px">
      </div>
      <div style="display:flex;gap:4px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;padding:3px">
        <?php foreach ([''=>'Todos','vip'=>'VIP','bloqueado'=>'Bloqueados'] as $s=>$l): ?>
        <button onclick="setStatusFilter('<?= $s ?>')" id="sf-<?= $s ?: 'all' ?>"
          style="padding:5px 10px;border:none;border-radius:6px;font-size:11px;font-weight:500;cursor:pointer;transition:all .15s;background:<?= $s===''?'#3483FA':'transparent' ?>;color:<?= $s===''?'#fff':'#5E5E5A' ?>">
          <?= $l ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- KPIs rápidos -->
  <div id="crm-kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:20px">
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #3483FA;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Total de compradores</div>
      <div id="kpi-total" style="font-size:22px;font-weight:700;color:#E8E8E6">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #FFE600;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">VIP</div>
      <div id="kpi-vip" style="font-size:22px;font-weight:700;color:#FFE600">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #22c55e;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Recorrentes (2+ pedidos)</div>
      <div id="kpi-recorrentes" style="font-size:22px;font-weight:700;color:#22c55e">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #ef4444;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Com reclamações</div>
      <div id="kpi-reclamacoes" style="font-size:22px;font-weight:700;color:#ef4444">—</div>
    </div>
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid #a855f7;border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px">Ticket médio geral</div>
      <div id="kpi-ticket" style="font-size:22px;font-weight:700;color:#a855f7">—</div>
    </div>
  </div>

  <!-- Lista -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <div id="crm-loading" style="text-align:center;padding:48px;color:#5E5E5A;font-size:13px">
      <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
      Carregando compradores...
    </div>
    <div id="crm-table" style="display:none;overflow-x:auto"></div>
    <div id="crm-pagination" style="padding:10px 16px;border-top:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between;display:none">
      <span id="crm-page-info" style="font-size:11px;color:#5E5E5A"></span>
      <div style="display:flex;gap:6px" id="crm-page-btns"></div>
    </div>
  </div>
</div>

<!-- Modal perfil do comprador -->
<div id="crm-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:flex-start;justify-content:center;z-index:500;padding:20px;overflow-y:auto;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:780px;margin:0 auto;box-shadow:0 24px 80px rgba(0,0,0,.6)">

    <!-- Header modal -->
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:10px;position:sticky;top:0;background:#1A1A1C;border-radius:16px 16px 0 0;z-index:10">
      <div id="cm-avatar" style="width:36px;height:36px;border-radius:50%;background:rgba(52,131,250,.2);color:#3483FA;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0"></div>
      <div>
        <div id="cm-name" style="font-size:14px;font-weight:600;color:#E8E8E6"></div>
        <div id="cm-sub" style="font-size:11px;color:#5E5E5A"></div>
      </div>
      <div id="cm-status-badge" style="margin-left:8px"></div>
      <button onclick="closeCrmModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>

    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:20px">

      <!-- Coluna esquerda: métricas + histórico -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Métricas -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" id="cm-metrics"></div>

        <!-- Histórico de pedidos -->
        <div>
          <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Histórico de pedidos</div>
          <div id="cm-orders" style="display:flex;flex-direction:column;gap:6px;max-height:300px;overflow-y:auto"></div>
        </div>

        <!-- Perguntas feitas -->
        <div id="cm-questions-wrap" style="display:none">
          <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Perguntas feitas</div>
          <div id="cm-questions" style="display:flex;flex-direction:column;gap:6px;max-height:180px;overflow-y:auto"></div>
        </div>
      </div>

      <!-- Coluna direita: CRM -->
      <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Status -->
        <div>
          <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Status do comprador</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
            <?php foreach ([
              'ativo'    => ['Ativo',     '#22c55e'],
              'vip'      => ['VIP',       '#FFE600'],
              'inativo'  => ['Inativo',   '#5E5E5A'],
              'bloqueado'=> ['Bloqueado', '#ef4444'],
            ] as $s => [$l, $c]): ?>
            <button onclick="setStatus('<?= $s ?>')" id="cms-<?= $s ?>"
              style="padding:8px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;border:0.5px solid <?= $c ?>;background:transparent;color:<?= $c ?>">
              <?= $l ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Tags -->
        <div>
          <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Tags</div>
          <div id="cm-tags-display" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px"></div>
          <div style="display:flex;gap:6px">
            <input type="text" id="cm-tag-input" placeholder="Nova tag... (Enter para adicionar)"
              onkeydown="if(event.key==='Enter'){addTag();event.preventDefault()}"
              style="flex:1;padding:7px 10px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:12px;outline:none">
            <button onclick="addTag()" style="padding:7px 12px;background:#3483FA;border:none;color:#fff;border-radius:7px;font-size:11px;cursor:pointer">+ Tag</button>
          </div>
          <!-- Tags sugeridas -->
          <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:8px">
            <?php foreach (['VIP','Recorrente','Problemático','Fiel','Atacado','Revendedor','Cuidado'] as $tag): ?>
            <button onclick="addTagDirect('<?= $tag ?>')"
              style="padding:3px 8px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;cursor:pointer;transition:all .15s"
              onmouseover="this.style.borderColor='#3483FA';this.style.color='#3483FA'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
              + <?= $tag ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Notas internas -->
        <div>
          <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Notas internas</div>
          <textarea id="cm-notes" placeholder="Observações sobre este comprador..."
            style="width:100%;height:100px;padding:9px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;resize:none;box-sizing:border-box;line-height:1.5"></textarea>
          <button onclick="saveNote()" style="margin-top:6px;padding:7px 14px;background:rgba(245,158,11,.1);border:0.5px solid #f59e0b;color:#f59e0b;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">
            <i data-lucide="save" style="width:11px;height:11px"></i> Salvar nota
          </button>
        </div>

        <!-- Info de contato -->
        <div id="cm-contact" style="background:#252528;border-radius:10px;padding:12px;font-size:11px;color:#9A9A96;line-height:1.8"></div>

      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

let currentPage     = 1;
let currentStatus   = '';
let currentSearch   = '';
let currentNickname = '';
let currentTags     = [];
let searchTimer     = null;

const statusColors = {
  ativo:     ['#22c55e', 'Ativo'],
  vip:       ['#FFE600', '⭐ VIP'],
  inativo:   ['#5E5E5A', 'Inativo'],
  bloqueado: ['#ef4444', '🚫 Bloqueado'],
};

// ── Carregamento ──────────────────────────────────────────
async function loadCRM(page=1) {
  currentPage = page;
  document.getElementById('crm-loading').style.display = 'block';
  document.getElementById('crm-table').style.display   = 'none';

  const params = new URLSearchParams({
    action: 'list', page, q: currentSearch, status: currentStatus
  });
  const r = await fetch(`/api/crm.php?${params}`);
  const d = await r.json();

  document.getElementById('crm-loading').style.display = 'none';
  document.getElementById('crm-table').style.display   = 'block';

  if (!d.ok || !d.customers?.length) {
    document.getElementById('crm-table').innerHTML =
      `<div style="text-align:center;padding:48px;color:#5E5E5A;font-size:12px">
        <i data-lucide="users" style="width:28px;height:28px;margin:0 auto 10px;display:block"></i>
        Nenhum comprador encontrado
      </div>`;
    lucide.createIcons();
    return;
  }

  // Atualizar KPIs na primeira página
  if (page === 1 && !currentSearch && !currentStatus) {
    updateKPIs(d.customers, d.total);
  }

  let html = `<table style="width:100%;border-collapse:collapse;font-size:12px">
    <thead>
      <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
        <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Comprador</th>
        <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Status</th>
        <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Pedidos</th>
        <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Total gasto</th>
        <th style="padding:10px 14px;text-align:right;color:#5E5E5A;font-weight:500">Ticket médio</th>
        <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Tags</th>
        <th style="padding:10px 14px;text-align:left;color:#5E5E5A;font-weight:500">Último pedido</th>
        <th style="padding:10px 14px;text-align:center;color:#5E5E5A;font-weight:500">Ações</th>
      </tr>
    </thead><tbody>`;

  for (const c of d.customers) {
    const [sColor, sLabel] = statusColors[c.status] || ['#5E5E5A', c.status];
    const initial  = (c.nickname||'?')[0].toUpperCase();
    const tagsHtml = (c.tags||[]).slice(0,3).map(t =>
      `<span style="font-size:9px;padding:1px 6px;border-radius:5px;background:rgba(52,131,250,.15);color:#3483FA">${t}</span>`
    ).join(' ');
    const lastOrder = c.last_order_at
      ? new Date(c.last_order_at).toLocaleDateString('pt-BR')
      : '—';
    const spent = 'R$ ' + parseFloat(c.total_spent||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
    const ticket = 'R$ ' + parseFloat(c.avg_ticket||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});

    html += `<tr style="border-bottom:0.5px solid #2E2E33;transition:background .12s"
      onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
      <td style="padding:10px 14px">
        <div style="display:flex;align-items:center;gap:9px">
          <div style="width:30px;height:30px;border-radius:50%;background:${sColor}20;color:${sColor};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">${initial}</div>
          <div>
            <div style="font-weight:500;color:#E8E8E6">${c.nickname}</div>
            <div style="font-size:10px;color:#5E5E5A">${c.first_name||''} ${c.last_name||''}</div>
          </div>
          ${c.has_complaints ? '<span title="Tem reclamações" style="font-size:10px">⚠</span>' : ''}
        </div>
      </td>
      <td style="padding:10px 14px;text-align:center">
        <span style="font-size:9px;font-weight:600;padding:2px 8px;border-radius:8px;background:${sColor}18;color:${sColor}">${sLabel}</span>
      </td>
      <td style="padding:10px 14px;text-align:right;color:#3483FA;font-weight:600">${c.total_orders}</td>
      <td style="padding:10px 14px;text-align:right;color:#22c55e;font-weight:600">${spent}</td>
      <td style="padding:10px 14px;text-align:right;color:#f59e0b">${ticket}</td>
      <td style="padding:10px 14px">${tagsHtml || '<span style="color:#3E3E45;font-size:10px">—</span>'}</td>
      <td style="padding:10px 14px;color:#5E5E5A;font-size:11px">${lastOrder}</td>
      <td style="padding:10px 14px;text-align:center">
        <button onclick="openProfile('${c.nickname}')"
          style="padding:5px 12px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;font-size:10px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .15s"
          onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
          <i data-lucide="user" style="width:10px;height:10px"></i> Perfil
        </button>
      </td>
    </tr>`;
  }

  html += `</tbody></table>`;
  document.getElementById('crm-table').innerHTML = html;

  // Paginação
  const pgEl = document.getElementById('crm-pagination');
  const pgInfo = document.getElementById('crm-page-info');
  const pgBtns = document.getElementById('crm-page-btns');
  pgEl.style.display = 'flex';
  pgInfo.textContent = `${d.total} comprador${d.total!==1?'es':''} · Página ${page} de ${d.pages}`;
  pgBtns.innerHTML = '';
  for (let i=1; i<=Math.min(d.pages,10); i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.onclick = () => loadCRM(i);
    btn.style.cssText = `padding:4px 9px;border-radius:6px;font-size:11px;cursor:pointer;border:0.5px solid ${i===page?'#3483FA':'#2E2E33'};background:${i===page?'rgba(52,131,250,.15)':'transparent'};color:${i===page?'#3483FA':'#5E5E5A'}`;
    pgBtns.appendChild(btn);
  }

  lucide.createIcons();
}

function updateKPIs(customers, total) {
  document.getElementById('kpi-total').textContent = total.toLocaleString('pt-BR');

  const vip        = customers.filter(c => c.status==='vip').length;
  const recorrentes = customers.filter(c => c.total_orders >= 2).length;
  const comRec     = customers.filter(c => c.has_complaints).length;
  const tickets    = customers.map(c => parseFloat(c.avg_ticket||0)).filter(v => v>0);
  const avgTicket  = tickets.length ? tickets.reduce((a,b)=>a+b,0)/tickets.length : 0;

  document.getElementById('kpi-vip').textContent        = vip;
  document.getElementById('kpi-recorrentes').textContent = recorrentes;
  document.getElementById('kpi-reclamacoes').textContent = comRec;
  document.getElementById('kpi-ticket').textContent     = 'R$ '+avgTicket.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
}

function debounceSearch(val) {
  clearTimeout(searchTimer);
  currentSearch = val;
  searchTimer   = setTimeout(() => loadCRM(1), 400);
}

function setStatusFilter(status) {
  currentStatus = status;
  ['all','vip','bloqueado'].forEach(s => {
    const btn = document.getElementById('sf-'+s);
    if (!btn) return;
    const active = (s === (status||'all'));
    btn.style.background = active ? '#3483FA' : 'transparent';
    btn.style.color      = active ? '#fff'    : '#5E5E5A';
  });
  loadCRM(1);
}

// ── Modal de perfil ───────────────────────────────────────
async function openProfile(nickname) {
  currentNickname = nickname;
  document.getElementById('crm-modal').style.display = 'flex';
  document.getElementById('cm-avatar').textContent   = nickname[0].toUpperCase();
  document.getElementById('cm-name').textContent     = nickname;
  document.getElementById('cm-sub').textContent      = 'Carregando...';
  document.getElementById('cm-metrics').innerHTML    = '';
  document.getElementById('cm-orders').innerHTML     = '<div style="text-align:center;padding:16px;color:#5E5E5A;font-size:11px">Carregando...</div>';
  document.getElementById('cm-notes').value          = '';
  document.getElementById('cm-tags-display').innerHTML = '';

  const r = await fetch(`/api/crm.php?action=get&nickname=${encodeURIComponent(nickname)}`);
  const d = await r.json();
  if (!d.ok) { toast(d.error||'Erro ao carregar perfil', 'error'); closeCrmModal(); return; }

  const c = d.customer;
  currentTags = c.tags || [];

  // Header
  const [sColor, sLabel] = statusColors[c.status] || ['#5E5E5A', c.status];
  document.getElementById('cm-sub').textContent = `${c.city||''}${c.state?' — '+c.state:''}`;
  document.getElementById('cm-status-badge').innerHTML =
    `<span style="font-size:10px;padding:2px 8px;border-radius:6px;background:${sColor}18;color:${sColor};font-weight:600">${sLabel}</span>`;

  // Marcar botão de status ativo
  Object.keys(statusColors).forEach(s => {
    const btn = document.getElementById('cms-'+s);
    if (!btn) return;
    const [sc] = statusColors[s];
    btn.style.background = s===c.status ? sc+'25' : 'transparent';
    btn.style.fontWeight = s===c.status ? '700' : '600';
  });

  // Métricas
  const metricas = [
    ['Pedidos',    c.total_orders, '#3483FA'],
    ['Total gasto','R$ '+parseFloat(c.total_spent||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}),'#22c55e'],
    ['Ticket médio','R$ '+parseFloat(c.avg_ticket||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}),'#f59e0b'],
    ['Reclamações', c.complaint_count||0, c.complaint_count>0?'#ef4444':'#22c55e'],
  ];
  document.getElementById('cm-metrics').innerHTML = metricas.map(([l,v,c]) => `
    <div style="background:#252528;border-radius:8px;padding:10px 12px;text-align:center">
      <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px">${l}</div>
      <div style="font-size:16px;font-weight:700;color:${c}">${v}</div>
    </div>`).join('');

  // Pedidos
  const orders = d.orders || [];
  if (orders.length) {
    document.getElementById('cm-orders').innerHTML = orders.map(o => {
      const paid = ['approved','APPROVED'].includes(o.payment_status);
      const date = o.order_date ? new Date(o.order_date).toLocaleDateString('pt-BR') : '—';
      return `<div style="background:#252528;border-radius:8px;padding:10px 12px;border-left:3px solid ${paid?'#22c55e':'#5E5E5A'}">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
          <span style="font-size:10px;font-family:monospace;color:#3483FA">#${o.meli_order_id}</span>
          <span style="font-size:10px;font-weight:700;color:#22c55e">R$ ${parseFloat(o.total_amount||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
        </div>
        <div style="font-size:10px;color:#9A9A96;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${o.items||'—'}</div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:3px">${date} ${o.has_mediacao?'<span style="color:#ef4444">⚠ Reclamação</span>':''}</div>
      </div>`;
    }).join('');
  } else {
    document.getElementById('cm-orders').innerHTML = '<div style="text-align:center;padding:16px;color:#5E5E5A;font-size:11px">Sem pedidos registrados</div>';
  }

  // Perguntas
  const questions = d.questions || [];
  if (questions.length) {
    document.getElementById('cm-questions-wrap').style.display = 'block';
    document.getElementById('cm-questions').innerHTML = questions.map(q => `
      <div style="background:#252528;border-radius:8px;padding:9px 12px">
        <div style="font-size:10px;color:#9A9A96;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${q.question_text||'—'}</div>
        <div style="font-size:9px;color:#5E5E5A;margin-top:2px">${q.product_title||''}</div>
      </div>`).join('');
  }

  // Tags
  renderTags();

  // Notas
  document.getElementById('cm-notes').value = c.notes || '';

  // Contato
  document.getElementById('cm-contact').innerHTML = [
    c.email    ? `<div>📧 ${c.email}</div>` : '',
    c.phone    ? `<div>📱 ${c.phone}</div>` : '',
    c.city     ? `<div>📍 ${c.city}${c.state?', '+c.state:''}</div>` : '',
    c.meli_user_id ? `<div style="font-family:monospace;font-size:10px;color:#5E5E5A">ID ML: ${c.meli_user_id}</div>` : '',
  ].filter(Boolean).join('') || '<div style="color:#5E5E5A">Sem dados de contato</div>';

  lucide.createIcons();
}

function closeCrmModal() {
  document.getElementById('crm-modal').style.display = 'none';
  currentNickname = '';
  loadCRM(currentPage);
}

function renderTags() {
  document.getElementById('cm-tags-display').innerHTML = currentTags.map(t => `
    <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;padding:3px 8px;border-radius:6px;background:rgba(52,131,250,.15);color:#3483FA">
      ${t}
      <button onclick="removeTag('${t}')" style="background:none;border:none;color:#3483FA;cursor:pointer;padding:0;font-size:12px;line-height:1">×</button>
    </span>`).join('');
}

function addTag() {
  const val = document.getElementById('cm-tag-input').value.trim();
  if (!val || currentTags.includes(val)) { document.getElementById('cm-tag-input').value=''; return; }
  currentTags.push(val);
  document.getElementById('cm-tag-input').value = '';
  renderTags();
  saveTags();
}

function addTagDirect(tag) {
  if (currentTags.includes(tag)) return;
  currentTags.push(tag);
  renderTags();
  saveTags();
}

function removeTag(tag) {
  currentTags = currentTags.filter(t => t !== tag);
  renderTags();
  saveTags();
}

async function saveTags() {
  const fd = new FormData();
  fd.append('action',   'save_tags');
  fd.append('nickname', currentNickname);
  fd.append('tags',     JSON.stringify(currentTags));
  await fetch('/api/crm.php', {method:'POST', body:fd});
}

async function saveNote() {
  const fd = new FormData();
  fd.append('action',   'save_note');
  fd.append('nickname', currentNickname);
  fd.append('note',     document.getElementById('cm-notes').value);
  const r = await fetch('/api/crm.php', {method:'POST', body:fd});
  const d = await r.json();
  d.ok ? toast('Nota salva!', 'success') : toast('Erro', 'error');
}

async function setStatus(status) {
  const fd = new FormData();
  fd.append('action',   'set_status');
  fd.append('nickname', currentNickname);
  fd.append('status',   status);
  const r = await fetch('/api/crm.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    const [sColor, sLabel] = statusColors[status];
    document.getElementById('cm-status-badge').innerHTML =
      `<span style="font-size:10px;padding:2px 8px;border-radius:6px;background:${sColor}18;color:${sColor};font-weight:600">${sLabel}</span>`;
    Object.keys(statusColors).forEach(s => {
      const btn = document.getElementById('cms-'+s);
      if (!btn) return;
      const [sc] = statusColors[s];
      btn.style.background = s===status ? sc+'25' : 'transparent';
      btn.style.fontWeight = s===status ? '700' : '600';
    });
    toast(`Status: ${sLabel}`, 'success');
  }
}

document.getElementById('crm-modal').addEventListener('click', function(e) {
  if (e.target === this) closeCrmModal();
});

// Carregar ao abrir
loadCRM();

// Auto-abrir perfil se vier de link externo
const autoOpen = new URLSearchParams(location.search).get('open') || sessionStorage.getItem('crm_open');
if (autoOpen) {
  sessionStorage.removeItem('crm_open');
  setTimeout(() => openProfile(autoOpen), 600);
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
