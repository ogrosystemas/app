<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_admin');

$user     = auth_user();
$tenantId = $user['tenant_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Create new user
    if (($_POST['action'] ?? '') === 'create_user') {
        $name  = trim($_POST['name']  ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role'] ?? 'OPERATOR';
        if (!$name || !$email || !$pass) { echo json_encode(['ok'=>false,'error'=>'Preencha nome, e-mail e senha']); exit; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['ok'=>false,'error'=>'E-mail inválido']); exit; }
        if (db_exists('users', 'email=?', [$email])) { echo json_encode(['ok'=>false,'error'=>'E-mail já cadastrado']); exit; }
        $newId = db_insert('users', [
            'tenant_id'     => $tenantId,
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($pass, PASSWORD_BCRYPT),
            'role'          => $role,
            'is_active'     => 1,
            'can_access_sac'      => (int)($_POST['can_access_sac'] ?? 1),
            'can_access_anuncios' => (int)($_POST['can_access_anuncios'] ?? 1),
            'can_access_financeiro' => (int)($_POST['can_access_financeiro'] ?? 0),
            'can_access_logistica'  => (int)($_POST['can_access_logistica'] ?? 1),
            'can_access_admin'      => (int)($_POST['can_access_admin'] ?? 0),
        ]);
        audit_log('CREATE_USER', 'users', $newId, null, ['name'=>$name,'email'=>$email,'role'=>$role]);
        echo json_encode(['ok'=>true,'id'=>$newId]);
        exit;
    }

    $id   = $_POST['id'] ?? '';
    $target = db_one("SELECT * FROM users WHERE id=? AND tenant_id=?", [$id, $tenantId]);
    if (!$target) { echo json_encode(['ok'=>false]); exit; }

    if ($id === $user['id'] && isset($_POST['can_access_admin']) && !$_POST['can_access_admin']) {
        echo json_encode(['ok'=>false,'error'=>'Você não pode remover seu próprio acesso admin']); exit;
    }

    $fields = ['role','is_active','can_access_sac','can_access_anuncios','can_access_financeiro','can_access_logistica','can_access_admin'];
    $data = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) $data[$f] = is_numeric($_POST[$f]) ? (int)$_POST[$f] : $_POST[$f];
    }
    if ($data) db_update('users', $data, 'id=? AND tenant_id=?', [$id, $tenantId]);
    audit_log('UPDATE_USER_PERMISSIONS', 'users', $_POST['id']??'');
    echo json_encode(['ok' => true]);
    exit;
}

$users = db_all("SELECT * FROM users WHERE tenant_id=? ORDER BY name", [$tenantId]);

$title = 'Permissões';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6">Controle de acesso RBAC</h1>
      <p style="font-size:12px;color:#5E5E5A;margin-top:2px">Gerencie permissões por módulo e usuário</p>
    </div>
    <button onclick="openNewUser()" class="btn-primary" style="padding:8px 16px;font-size:13px;display:flex;align-items:center;gap:6px">
      <i data-lucide="user-plus" style="width:14px;height:14px"></i> Novo usuário
    </button>
  </div>

  <div class="card" style="overflow:hidden;display:grid;grid-template-columns:200px 1fr">

    <!-- Lista usuários -->
    <div style="border-right:0.5px solid #2E2E33">
      <div style="padding:10px 14px;border-bottom:0.5px solid #2E2E33">
        <span style="font-size:10px;font-weight:500;color:#5E5E5A;text-transform:uppercase;letter-spacing:.6px">Usuários</span>
      </div>
      <?php foreach ($users as $u):
        $roleColor = match($u['role']) { 'ADMIN'=>'#3483FA', 'MANAGER'=>'#f59e0b', default=>'#5E5E5A' };
        $roleLabel = match($u['role']) { 'ADMIN'=>'Admin', 'MANAGER'=>'Gestor', default=>'Operador' };
      ?>
      <button onclick="selectUser(<?= htmlspecialchars(json_encode($u),ENT_QUOTES) ?>)"
        id="user-btn-<?= $u['id'] ?>"
        style="width:100%;text-align:left;padding:10px 14px;border:none;background:transparent;border-bottom:0.5px solid #2E2E33;cursor:pointer;transition:background .12s;border-left:2px solid transparent">
        <div style="font-size:12px;font-weight:500;color:#E8E8E6;margin-bottom:3px">
          <?= htmlspecialchars(implode(' ', array_slice(explode(' ', $u['name']), 0, 2))) ?>
        </div>
        <div style="display:flex;align-items:center;gap:5px">
          <span style="font-size:9px;padding:1px 6px;border-radius:10px;background:<?= $roleColor ?>22;color:<?= $roleColor ?>"><?= $roleLabel ?></span>
          <?php if (!$u['is_active']): ?><span class="badge badge-red" style="font-size:8px;padding:1px 4px">Inativo</span><?php endif; ?>
        </div>
      </button>
      <?php endforeach; ?>
    </div>

    <!-- Painel de permissões -->
    <div id="perm-panel" style="padding:20px">
      <div style="text-align:center;padding:40px;color:#5E5E5A;font-size:12px">
        <i data-lucide="mouse-pointer-click" style="width:28px;height:28px;margin:0 auto 8px;display:block;opacity:.4"></i>
        Selecione um usuário
      </div>
    </div>
  </div>
</div>

<script>
lucide.createIcons();
let currentUserId = null;

const modules = [
  { key:'can_access_sac',        label:'SAC',        desc:'Inbox e mediações' },
  { key:'can_access_anuncios',   label:'Anúncios',   desc:'Preços e estoque' },
  { key:'can_access_financeiro', label:'Financeiro', desc:'Fluxo e DRE' },
  { key:'can_access_logistica',  label:'Logística',  desc:'Expedição ZPL/PDF' },
  { key:'can_access_admin',      label:'Admin',      desc:'RBAC e config' },
];

function selectUser(u) {
  currentUserId = u.id;
  document.querySelectorAll('[id^="user-btn-"]').forEach(el => {
    el.style.background = 'transparent';
    el.style.borderLeftColor = 'transparent';
    el.style.color = '#E8E8E6';
  });
  const btn = document.getElementById('user-btn-' + u.id);
  if (btn) { btn.style.background = 'rgba(52,131,250,.08)'; btn.style.borderLeftColor = '#3483FA'; }

  const panel = document.getElementById('perm-panel');
  const initials = (u.name||'').split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase();

  panel.innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
      <div style="width:36px;height:36px;border-radius:50%;background:#3483FA;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:500">${initials}</div>
      <div>
        <div style="font-size:14px;font-weight:500;color:#E8E8E6">${u.name}</div>
        <div style="font-size:12px;color:#5E5E5A">${u.email}</div>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:#9A9A96">${u.is_active?'Ativo':'Inativo'}</span>
        <label class="toggle"><input type="checkbox" ${u.is_active?'checked':''} onchange="update('${u.id}','is_active',this.checked?1:0)"><span class="toggle-slider"></span></label>
      </div>
    </div>

    <div style="margin-bottom:16px">
      <div style="font-size:11px;color:#9A9A96;margin-bottom:8px">Perfil</div>
      <div style="display:flex;gap:6px">
        ${['ADMIN','MANAGER','OPERATOR'].map(r => `
          <button onclick="update('${u.id}','role','${r}')" id="role-${u.id}-${r}"
            style="padding:5px 12px;border-radius:7px;font-size:11px;cursor:pointer;transition:all .15s;${u.role===r?'background:rgba(52,131,250,.12);border:0.5px solid #3483FA;color:#3483FA':'background:#252528;border:0.5px solid #2E2E33;color:#5E5E5A'}">
            ${r==='ADMIN'?'Admin':r==='MANAGER'?'Gestor':'Operador'}
          </button>`).join('')}
      </div>
    </div>

    <div>
      <div style="font-size:11px;color:#9A9A96;margin-bottom:8px">Módulos</div>
      <div style="display:flex;flex-direction:column;gap:6px">
        ${modules.map(m => `
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:8px;transition:background .12s;${u[m.key]?'background:rgba(52,131,250,.06);border:0.5px solid rgba(52,131,250,.2)':'background:#252528;border:0.5px solid #2E2E33'}" id="mod-${u.id}-${m.key}">
            <div>
              <div style="font-size:13px;font-weight:500;color:#E8E8E6">${m.label}</div>
              <div style="font-size:11px;color:#5E5E5A">${m.desc}</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="font-size:11px;color:${u[m.key]?'#3483FA':'#5E5E5A'}">${u[m.key]?'Acesso':'Sem acesso'}</span>
              <label class="toggle"><input type="checkbox" ${u[m.key]?'checked':''} onchange="update('${u.id}','${m.key}',this.checked?1:0,this)"><span class="toggle-slider"></span></label>
            </div>
          </div>`).join('')}
      </div>
    </div>
  `;
  lucide.createIcons();
}

async function update(userId, field, value, el) {
  const fd = new FormData();
  fd.append('id', userId);
  fd.append(field, value);
  const r = await fetch('/pages/admin.php',{method:'POST',body:fd});
  const d = await r.json();
  if (d.ok) toast('Permissão atualizada','success');
  else { toast(d.error||'Erro','error'); if(el) el.checked = !el.checked; }
}
</script>

<!-- Modal novo usuário -->
<div id="new-user-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);align-items:center;justify-content:center;z-index:1000;padding:16px">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;padding:24px;width:100%;max-width:440px">
    <h3 style="font-size:15px;font-weight:500;color:#E8E8E6;margin-bottom:20px;display:flex;align-items:center;gap:8px">
      <i data-lucide="user-plus" style="width:15px;height:15px;color:#3483FA"></i> Novo usuário
    </h3>
    <div style="display:grid;gap:12px">
      <div><label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:5px">Nome completo *</label>
        <input type="text" id="nu-name" class="input" placeholder="João Silva"></div>
      <div><label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:5px">E-mail *</label>
        <input type="email" id="nu-email" class="input" placeholder="joao@empresa.com.br"></div>
      <div><label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:5px">Senha *</label>
        <input type="password" id="nu-pass" class="input" placeholder="Mínimo 8 caracteres"></div>
      <div><label style="display:block;font-size:12px;color:#9A9A96;margin-bottom:5px">Perfil</label>
        <select id="nu-role" class="input">
          <option value="OPERATOR">Operador</option>
          <option value="MANAGER">Gestor</option>
          <option value="ADMIN">Admin</option>
        </select></div>
      <div style="background:#252528;border-radius:8px;padding:12px">
        <div style="font-size:11px;color:#5E5E5A;margin-bottom:8px">Módulos com acesso</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
          <?php foreach(['sac'=>'SAC','anuncios'=>'Anúncios','financeiro'=>'Financeiro','logistica'=>'Logística','admin'=>'Admin'] as $k=>$v): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#9A9A96;cursor:pointer">
            <input type="checkbox" name="nu_perm_<?= $k ?>" id="nu-<?= $k ?>"
              <?= in_array($k,['sac','anuncios','logistica'])?'checked':'' ?>
              style="accent-color:#3483FA"> <?= $v ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:8px;margin-top:20px">
      <button onclick="saveNewUser()" class="btn-primary" style="flex:1" id="btn-save-user">Criar usuário</button>
      <button onclick="document.getElementById('new-user-modal').style.display='none'" class="btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<script>
function openNewUser() {
  ['nu-name','nu-email','nu-pass'].forEach(id => document.getElementById(id).value='');
  document.getElementById('nu-role').value='OPERATOR';
  document.getElementById('new-user-modal').style.display='flex';
  lucide.createIcons();
}

async function saveNewUser() {
  const name  = document.getElementById('nu-name').value.trim();
  const email = document.getElementById('nu-email').value.trim();
  const pass  = document.getElementById('nu-pass').value;
  const role  = document.getElementById('nu-role').value;
  if (!name||!email||!pass) { toast('Preencha nome, e-mail e senha','error'); return; }
  if (pass.length < 6) { toast('Senha muito curta (mínimo 6 caracteres)','error'); return; }

  const fd = new FormData();
  fd.append('action','create_user');
  fd.append('name', name); fd.append('email', email);
  fd.append('password', pass); fd.append('role', role);
  ['sac','anuncios','financeiro','logistica','admin'].forEach(k => {
    fd.append('can_access_'+k, document.getElementById('nu-'+k).checked ? '1' : '0');
  });

  const btn = document.getElementById('btn-save-user');
  btn.disabled=true; btn.textContent='Criando...';
  const r = await fetch('/pages/admin.php',{method:'POST',body:fd});
  const d = await r.json();
  btn.disabled=false; btn.textContent='Criar usuário';

  if (d.ok) {
    toast('Usuário criado com sucesso!','success');
    document.getElementById('new-user-modal').style.display='none';
    setTimeout(()=>location.reload(),500);
  } else {
    toast(d.error||'Erro ao criar usuário','error');
  }
}
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
