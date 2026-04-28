<?php
/**
 * pages/usuarios.php
 * Cadastro e gestão de usuários operadores da loja
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_admin');

$user     = auth_user();
$tenantId = $user['tenant_id'];

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = $_POST['id'] ?? '';
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = in_array($_POST['role']??'', ['operator','manager','admin']) ? $_POST['role'] : 'operator';

        $perms = [
            'can_access_sac'        => isset($_POST['can_access_sac'])        ? 1 : 0,
            'can_access_crm'        => isset($_POST['can_access_crm'])        ? 1 : 0,
            'can_access_anuncios'   => isset($_POST['can_access_anuncios'])   ? 1 : 0,
            'can_access_financeiro' => isset($_POST['can_access_financeiro']) ? 1 : 0,
            'can_access_logistica'  => isset($_POST['can_access_logistica'])  ? 1 : 0,
            'can_access_admin'      => isset($_POST['can_access_admin'])      ? 1 : 0,
        ];

        if (!$name || !$email) {
            echo json_encode(['ok'=>false,'error'=>'Nome e e-mail são obrigatórios']); exit;
        }

        if ($id) {
            $existing = db_one("SELECT id FROM users WHERE id=? AND tenant_id=?", [$id, $tenantId]);
            if (!$existing) { echo json_encode(['ok'=>false,'error'=>'Usuário não encontrado']); exit; }

            $data = ['name'=>$name, 'email'=>$email, 'role'=>$role];
            // Adiciona permissões uma a uma, ignorando colunas que não existam
            foreach ($perms as $col => $val) {
                try {
                    db_update('users', [$col => $val], 'id=? AND tenant_id=?', [$id, $tenantId]);
                } catch(Throwable $e) {
                    // Coluna não existe no servidor — ignora
                }
            }
            if ($password) $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            db_update('users', $data, 'id=? AND tenant_id=?', [$id, $tenantId]);
            echo json_encode(['ok'=>true,'msg'=>'Usuário atualizado']);
        } else {
            // Criar
            if (!$password) { echo json_encode(['ok'=>false,'error'=>'Senha obrigatória para novo usuário']); exit; }

            $existing = db_one("SELECT id FROM users WHERE email=? AND tenant_id=?", [$email, $tenantId]);
            if ($existing) { echo json_encode(['ok'=>false,'error'=>'E-mail já cadastrado']); exit; }

            $newId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));

            db_insert('users', array_merge([
                'id'            => $newId,
                'tenant_id'     => $tenantId,
                'name'          => $name,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => $role,
                'is_active'     => 1,
            ], $perms));
            echo json_encode(['ok'=>true,'msg'=>'Usuário criado','id'=>$newId]);
        }
        exit;
    }

    if ($action === 'toggle_perm') {
        $id   = $_POST['id']   ?? '';
        $perm = $_POST['perm'] ?? '';
        $val  = (int)$_POST['val'];
        $allowed = ['can_access_sac','can_access_crm','can_access_anuncios',
                    'can_access_financeiro','can_access_logistica','can_access_admin'];
        if (!$id || !in_array($perm, $allowed)) {
            echo json_encode(['ok'=>false,'error'=>'Parâmetros inválidos']); exit;
        }
        if ($id === $user['id'] && $perm === 'can_access_admin' && !$val) {
            echo json_encode(['ok'=>false,'error'=>'Não é possível remover seu próprio acesso Admin']); exit;
        }
        db_update('users', [$perm => $val], 'id=? AND tenant_id=?', [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'toggle') {
        $id     = $_POST['id'] ?? '';
        $active = (int)$_POST['active'];
        // Não desativar o próprio usuário
        if ($id === $user['id']) { echo json_encode(['ok'=>false,'error'=>'Não é possível desativar sua própria conta']); exit; }
        db_update('users', ['is_active'=>$active], 'id=? AND tenant_id=?', [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id === $user['id']) { echo json_encode(['ok'=>false,'error'=>'Não é possível excluir sua própria conta']); exit; }
        db_query("DELETE FROM users WHERE id=? AND tenant_id=?", [$id, $tenantId]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($action === 'get') {
        $id = $_POST['id'] ?? '';
        $u  = db_one("SELECT id,name,email,role,is_active,can_access_sac,can_access_crm,can_access_anuncios,can_access_financeiro,can_access_logistica,can_access_admin FROM users WHERE id=? AND tenant_id=?", [$id, $tenantId]);
        echo json_encode(['ok'=>(bool)$u,'user'=>$u]);
        exit;
    }

    echo json_encode(['ok'=>false,'error'=>'Ação inválida']); exit;
}

// Lista usuários
$users = db_all(
    "SELECT id,name,email,role,is_active,can_access_sac,can_access_crm,can_access_anuncios,can_access_financeiro,can_access_logistica,can_access_admin,created_at
     FROM users WHERE tenant_id=? ORDER BY name ASC",
    [$tenantId]
);

$roleLabels = ['operator'=>'Operador','manager'=>'Gerente','admin'=>'Admin'];
$roleColors = ['operator'=>'#3483FA','manager'=>'#f59e0b','admin'=>'#a855f7'];

$title = 'Usuários';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Usuários</h1>
      <p style="font-size:11px;color:#5E5E5A">Gerencie os operadores da sua loja</p>
    </div>
    <button onclick="openModal()" class="btn-primary" style="font-size:12px">
      <i data-lucide="user-plus" style="width:13px;height:13px"></i> Novo usuário
    </button>
  </div>

  <!-- Lista -->
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">
    <?php if (empty($users)): ?>
    <div style="text-align:center;padding:64px;color:#5E5E5A">
      <i data-lucide="users" style="width:32px;height:32px;margin:0 auto 12px;display:block"></i>
      <div style="font-size:14px;color:#E8E8E6;margin-bottom:4px">Nenhum usuário cadastrado</div>
      <div style="font-size:11px;margin-bottom:16px">Crie o primeiro operador da loja</div>
      <button onclick="openModal()" class="btn-primary" style="font-size:12px">
        <i data-lucide="user-plus" style="width:12px;height:12px"></i> Criar usuário
      </button>
    </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:12px;table-layout:fixed">
      <thead>
        <tr style="border-bottom:0.5px solid #2E2E33;background:#151517">
          <th style="padding:10px 16px;text-align:left;color:#5E5E5A;font-weight:500;width:28%">Usuário</th>
          <th style="padding:10px 16px;text-align:left;color:#5E5E5A;font-weight:500;width:10%">Perfil</th>
          <th style="padding:10px 8px;text-align:center;color:#22c55e;font-weight:600;font-size:10px;width:8%">
            <i data-lucide="headphones" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>SAC
          </th>
          <th style="padding:10px 8px;text-align:center;color:#3483FA;font-weight:600;font-size:10px;width:7%">
            <i data-lucide="users" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>CRM
          </th>
          <th style="padding:10px 8px;text-align:center;color:#FFE600;font-weight:600;font-size:10px;width:8%">
            <i data-lucide="tag" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>Anúncios
          </th>
          <th style="padding:10px 8px;text-align:center;color:#a855f7;font-weight:600;font-size:10px;width:9%">
            <i data-lucide="wallet" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>Financeiro
          </th>
          <th style="padding:10px 8px;text-align:center;color:#f97316;font-weight:600;font-size:10px;width:9%">
            <i data-lucide="truck" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>Logística
          </th>
          <th style="padding:10px 8px;text-align:center;color:#9A9A96;font-weight:600;font-size:10px;width:7%">
            <i data-lucide="shield-check" style="width:11px;height:11px;display:block;margin:0 auto 2px"></i>Admin
          </th>
          <th style="padding:10px 8px;text-align:center;color:#5E5E5A;font-weight:500;font-size:10px;width:8%">Status</th>
          <th style="padding:10px 16px;text-align:center;color:#5E5E5A;font-weight:500;font-size:10px;width:13%">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $permCols = [
          'can_access_sac'        => '#22c55e',
          'can_access_crm'        => '#3483FA',
          'can_access_anuncios'   => '#FFE600',
          'can_access_financeiro' => '#a855f7',
          'can_access_logistica'  => '#f97316',
          'can_access_admin'      => '#9A9A96',
        ];
        foreach ($users as $u):
          $isMe    = $u['id'] === $user['id'];
          $rColor  = $roleColors[$u['role']] ?? '#5E5E5A';
          $rLabel  = $roleLabels[$u['role']] ?? $u['role'];
          $initial = mb_strtoupper(mb_substr($u['name'],0,1));
        ?>
        <tr style="border-bottom:0.5px solid #2E2E33;transition:background .12s" onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''">
          <td style="padding:12px 16px">
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:32px;height:32px;border-radius:50%;background:<?= $rColor ?>22;color:<?= $rColor ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0"><?= $initial ?></div>
              <div style="min-width:0">
                <div style="font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  <?= htmlspecialchars($u['name']) ?>
                  <?= $isMe ? '<span style="font-size:9px;color:#3483FA;margin-left:4px">(você)</span>' : '' ?>
                </div>
                <div style="font-size:10px;color:#5E5E5A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td style="padding:12px 16px">
            <span style="font-size:10px;padding:2px 8px;border-radius:6px;background:<?= $rColor ?>18;color:<?= $rColor ?>;font-weight:600;white-space:nowrap"><?= $rLabel ?></span>
          </td>
          <?php foreach ($permCols as $perm => $color): ?>
          <?php $val = (int)($u[$perm] ?? 0); ?>
          <td style="padding:12px 8px;text-align:center">
            <button onclick="togglePerm('<?= $u['id'] ?>','<?= $perm ?>',<?= $val ?>,this)"
              title="<?= $val ? 'Clique para revogar' : 'Clique para conceder' ?>"
              style="width:24px;height:24px;border-radius:50%;border:1.5px solid <?= $val?$color:'#2E2E33' ?>;background:<?= $val?$color.'20':'#1A1A1C' ?>;display:flex;align-items:center;justify-content:center;margin:0 auto;cursor:pointer;transition:all .2s;padding:0">
              <?php if ($val): ?>
              <i data-lucide="check" style="width:12px;height:12px;color:<?= $color ?>"></i>
              <?php else: ?>
              <i data-lucide="x" style="width:11px;height:11px;color:#3E3E45"></i>
              <?php endif; ?>
            </button>
          </td>
          <?php endforeach; ?>
          <td style="padding:12px 8px;text-align:center">
            <label style="position:relative;display:inline-block;width:36px;height:20px;cursor:pointer">
              <input type="checkbox" <?= $u['is_active']?'checked':'' ?> <?= $isMe?'disabled':'' ?>
                onchange="toggleUser('<?= $u['id'] ?>',this.checked)"
                style="opacity:0;width:0;height:0">
              <span style="position:absolute;inset:0;background:<?= $u['is_active']?'#22c55e':'#3E3E45' ?>;border-radius:10px;transition:.3s;opacity:<?= $isMe?.5:1 ?>"></span>
              <span style="position:absolute;left:<?= $u['is_active']?'18px':'2px' ?>;top:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:.3s"></span>
            </label>
          </td>
          <td style="padding:12px 16px;text-align:center">
            <div style="display:flex;align-items:center;justify-content:center;gap:6px">
              <button onclick="editUser('<?= $u['id'] ?>')"
                style="padding:5px 10px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;font-size:10px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .15s"
                onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
                <i data-lucide="pencil" style="width:10px;height:10px"></i> Editar
              </button>
              <?php if (!$isMe): ?>
              <button onclick="deleteUser('<?= $u['id'] ?>','<?= htmlspecialchars(addslashes($u['name'])) ?>')"
                style="padding:5px 8px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;cursor:pointer;display:inline-flex;align-items:center;transition:all .15s"
                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
                <i data-lucide="trash-2" style="width:10px;height:10px"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Modal -->
<div id="user-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);align-items:center;justify-content:center;z-index:1000;padding:16px;backdrop-filter:blur(2px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:520px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.5)">
    <div style="padding:20px 24px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
      <i data-lucide="user-plus" style="width:16px;height:16px;color:#3483FA"></i>
      <span id="modal-title" style="font-size:14px;font-weight:600;color:#E8E8E6">Novo Usuário</span>
      <button onclick="closeModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px;line-height:1">✕</button>
    </div>
    <div style="padding:20px 24px;max-height:70vh;overflow-y:auto">
      <input type="hidden" id="edit-id">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Nome completo *</label>
          <input type="text" id="u-name" class="input" placeholder="João Silva">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">E-mail *</label>
          <input type="email" id="u-email" class="input" placeholder="joao@loja.com">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Senha <span id="pwd-hint" style="color:#5E5E5A">(obrigatória)</span></label>
          <input type="password" id="u-password" class="input" placeholder="Mínimo 6 caracteres">
        </div>
        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Perfil</label>
          <select id="u-role" class="input">
            <option value="operator">Operador</option>
            <option value="manager">Gerente</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>

      <!-- Permissões -->
      <div style="background:#252528;border-radius:10px;padding:14px;margin-bottom:4px">
        <div style="font-size:11px;font-weight:500;color:#E8E8E6;margin-bottom:12px">Permissões de acesso</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <?php foreach ([
            'can_access_sac'        => ['SAC',       'headphones'],
            'can_access_crm'        => ['CRM',       'users'],
            'can_access_anuncios'   => ['Anúncios',  'tag'],
            'can_access_financeiro' => ['Financeiro','wallet'],
            'can_access_logistica'  => ['Logística', 'truck'],
            'can_access_admin'      => ['Admin',     'shield-check'],
          ] as $perm => [$label, $icon]): ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 8px;border-radius:6px;background:#1A1A1C">
            <input type="checkbox" id="<?= $perm ?>" name="<?= $perm ?>" style="accent-color:#3483FA;width:14px;height:14px">
            <i data-lucide="<?= $icon ?>" style="width:12px;height:12px;color:#5E5E5A"></i>
            <span style="font-size:12px;color:#9A9A96"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div style="padding:16px 24px;border-top:0.5px solid #2E2E33;display:flex;gap:8px;justify-content:flex-end">
      <button onclick="closeModal()" class="btn-secondary" style="font-size:12px">Cancelar</button>
      <button onclick="saveUser()" class="btn-primary" style="font-size:12px">
        <i data-lucide="save" style="width:12px;height:12px"></i> Salvar
      </button>
    </div>
  </div>
</div>

<script>
lucide.createIcons();

function openModal(data=null) {
  document.getElementById('edit-id').value       = data?.id || '';
  document.getElementById('u-name').value        = data?.name || '';
  document.getElementById('u-email').value       = data?.email || '';
  document.getElementById('u-password').value    = '';
  document.getElementById('u-role').value        = data?.role || 'operator';
  document.getElementById('modal-title').textContent = data ? 'Editar Usuário' : 'Novo Usuário';
  document.getElementById('pwd-hint').textContent    = data ? '(deixe em branco para não alterar)' : '(obrigatória)';

  const perms = ['can_access_sac','can_access_crm','can_access_anuncios','can_access_financeiro','can_access_logistica','can_access_admin'];
  perms.forEach(p => {
    document.getElementById(p).checked = data ? !!data[p] : (p !== 'can_access_admin' && p !== 'can_access_financeiro');
  });

  document.getElementById('user-modal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('user-modal').style.display = 'none';
}

async function editUser(id) {
  const fd = new FormData(); fd.append('action','get'); fd.append('id',id);
  const r = await fetch('/pages/usuarios.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) openModal(d.user);
  else toast('Erro ao carregar usuário', 'error');
}

async function saveUser() {
  const id    = document.getElementById('edit-id').value;
  const name  = document.getElementById('u-name').value.trim();
  const email = document.getElementById('u-email').value.trim();
  const pwd   = document.getElementById('u-password').value;
  const role  = document.getElementById('u-role').value;

  if (!name || !email) { toast('Nome e e-mail são obrigatórios', 'error'); return; }
  if (!id && !pwd) { toast('Senha obrigatória para novo usuário', 'error'); return; }

  const fd = new FormData();
  fd.append('action',   'save');
  fd.append('id',       id);
  fd.append('name',     name);
  fd.append('email',    email);
  fd.append('password', pwd);
  fd.append('role',     role);

  ['can_access_sac','can_access_crm','can_access_anuncios','can_access_financeiro','can_access_logistica','can_access_admin'].forEach(p => {
    if (document.getElementById(p)?.checked) fd.append(p, '1');
  });

  const r = await fetch('/pages/usuarios.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    toast(d.msg || 'Salvo!', 'success');
    closeModal();
    setTimeout(() => location.reload(), 1000);
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}

async function togglePerm(id, perm, currentVal, btn) {
  const newVal = currentVal ? 0 : 1;
  const fd = new FormData();
  fd.append('action', 'toggle_perm');
  fd.append('id',     id);
  fd.append('perm',   perm);
  fd.append('val',    newVal);

  const r = await fetch('/pages/usuarios.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    // Atualiza visual sem recarregar a página
    const permColors = {
      can_access_sac:'#22c55e', can_access_crm:'#3483FA',
      can_access_anuncios:'#FFE600', can_access_financeiro:'#a855f7',
      can_access_logistica:'#f97316', can_access_admin:'#9A9A96'
    };
    const color = permColors[perm] || '#22c55e';
    btn.setAttribute('onclick', `togglePerm('${id}','${perm}',${newVal},this)`);
    btn.title = newVal ? 'Clique para revogar' : 'Clique para conceder';
    btn.style.border    = `1.5px solid ${newVal ? color : '#2E2E33'}`;
    btn.style.background = newVal ? color+'20' : '#1A1A1C';
    btn.innerHTML = newVal
      ? `<i data-lucide="check" style="width:12px;height:12px;color:${color}"></i>`
      : `<i data-lucide="x" style="width:11px;height:11px;color:#3E3E45"></i>`;
    lucide.createIcons();
    toast(newVal ? 'Permissão concedida' : 'Permissão revogada', newVal ? 'success' : 'info');
  } else {
    toast(d.error || 'Erro ao alterar permissão', 'error');
  }
}

async function toggleUser(id, active) {
  const fd = new FormData();
  fd.append('action','toggle'); fd.append('id',id); fd.append('active',active?'1':'0');
  const r = await fetch('/pages/usuarios.php', {method:'POST', body:fd});
  const d = await r.json();
  if (!d.ok) { toast(d.error||'Erro', 'error'); location.reload(); }
  else toast(active ? 'Usuário ativado' : 'Usuário desativado', active ? 'success' : 'info');
}

async function deleteUser(id, name) {
  const ok = await dialog({title:'Excluir Usuário', message:`Excluir <strong>${name}</strong>? Esta ação não pode ser desfeita.`, confirmText:'Excluir', danger:true});
  if (!ok) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id',id);
  const r = await fetch('/pages/usuarios.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Usuário excluído', 'info'); location.reload(); }
  else toast(d.error||'Erro', 'error');
}

document.getElementById('user-modal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/layout_end.php'; ?>
