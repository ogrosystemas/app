<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/MercadoPago.php';

Auth::requireLogin();

$tab = $_GET['tab'] ?? 'geral';

// Admin obrigatório para usuários — verificar ANTES de qualquer output
if ($tab === 'usuarios' && !Auth::isAdmin()) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Acesso negado.</p>');
}

$_cor  = DB::cfg('cor_primaria',  '#f59e0b');
$_cor2 = DB::cfg('cor_secundaria','#d97706');
$msg_ok = $msg_err = '';

// ── Todos os POST handlers ANTES do HTML ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar_usuario') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $res = Auth::salvar([
            'nome'             => trim($_POST['u_nome']   ?? ''),
            'login'            => trim($_POST['u_login']  ?? ''),
            'senha'            => $_POST['u_senha']       ?? '',
            'perfil'           => $_POST['u_perfil']      ?? 'caixa_bar',
            'formas_pagamento' => $_POST['u_formas']      ?? null,
            'ativo'            => (int)($_POST['u_ativo'] ?? 1),
        ], $uid);
        if ($res['ok']) {
            setFlash('success', $uid ? 'Usuário atualizado.' : 'Usuário criado.');
            redirect(BASE_URL . 'modules/configuracoes/index.php?tab=usuarios');
        } else {
            setFlash('error', $res['msg']);
            redirect(BASE_URL . 'modules/configuracoes/index.php?tab=usuarios' . ($uid ? "&edit_user=$uid" : ''));
        }
    }

    if ($action === 'salvar_geral') {
        $campos = ['nome_estabelecimento','cnpj','endereco','telefone','cor_primaria',
                   'taxa_servico','rendimento_barril_padrao','ml_dose_padrao','ml_dose_destilado_padrao'];
        foreach ($campos as $c) {
            if (isset($_POST[$c])) DB::setCfg($c, trim($_POST[$c]));
        }
        $msg_ok = 'Configurações salvas.';
    }

    if ($action === 'salvar_visual') {
        // Garantir que o diretório de logos existe e é gravável
        $logosDir = UPLOAD_PATH . 'logos' . DIRECTORY_SEPARATOR;
        if (!is_dir($logosDir)) @mkdir($logosDir, 0775, true);
        @chmod($logosDir, 0775);

        foreach (['logo_login','logo_pdv'] as $campo) {
            if (!empty($_FILES[$campo]['name']) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                $nome = uploadImagem($_FILES[$campo], $logosDir);
                if ($nome) DB::setCfg($campo, $nome);
                elseif ($_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
                    setFlash('error', "Erro ao salvar logo ($campo). Verifique as permissões do diretório.");
                    redirect(BASE_URL . 'modules/configuracoes/index.php?tab=visual');
                }
            }
        }
        foreach (['cor_primaria','cor_secundaria'] as $c) {
            if (isset($_POST[$c])) DB::setCfg($c, trim($_POST[$c]));
        }
        DB::setCfg('tema', 'dark');
        setFlash('success', 'Visual salvo com sucesso!');
        redirect(BASE_URL . 'modules/configuracoes/index.php?tab=visual');
    }

    if ($action === 'salvar_mp') {
        // Access Token: remover espaços/newlines
        if (isset($_POST['mp_access_token'])) {
            DB::setCfg('mp_access_token', preg_replace('/\s+/', '', $_POST['mp_access_token']));
        }
        foreach (['mp_device_id','mp_webhook_secret'] as $c) {
            if (isset($_POST[$c])) DB::setCfg($c, trim($_POST[$c]));
        }
        $msg_ok = 'Configurações Mercado Pago salvas.';
    }

    if ($action === 'testar_mp') {
        $mp     = new MercadoPago();
        $result = $mp->testarConexao();
        if ($result['ok']) {
            $linhas = ["✅ <strong>Conexão OK!</strong> " . htmlspecialchars($result['message'])];
            foreach ($result['terminais'] ?? [] as $t) {
                $linhas[] = "&nbsp;&nbsp;→ <code>{$t['id']}</code> | Modo: {$t['operating_mode']}";
            }
            if (empty($result['terminais'])) {
                $linhas[] = "ℹ️ Nenhum terminal encontrado. Vincule sua maquininha Point à conta Mercado Pago.";
            }
            $msg_ok = implode('<br>', $linhas);
        } else {
            $msg_err = "❌ " . htmlspecialchars($result['message'])
                     . ($result['http_code'] ? " (HTTP {$result['http_code']})" : "");
        }
    }

    if ($action === 'salvar_terminal') {
        $nome=trim($_POST['nome']??''); $tid=trim($_POST['terminal_id']??''); $mod=trim($_POST['modelo']??'');
        if ($nome&&$tid) {
            $exist=DB::row("SELECT id FROM mp_terminais WHERE device_id=?",[$tid]);
            if ($exist) DB::update('mp_terminais',['nome'=>$nome,'modelo'=>$mod],'id=?',[$exist['id']]);
            else        DB::insert('mp_terminais',['nome'=>$nome,'device_id'=>$tid,'modelo'=>$mod,'ativo'=>1]);
            $msg_ok='Terminal salvo.';
        } else $msg_err='Preencha nome e ID.';
    }

    if ($action === 'toggle_terminal') {
        DB::q("UPDATE mp_terminais SET ativo=1-ativo WHERE id=?",[(int)($_POST['tid']??0)]);
        $msg_ok='Terminal atualizado.';
    }

    if ($action === 'salvar_categorias') {
        foreach ($_POST['cats'] as $cid=>$d) {
            $cid=(int)$cid; if(!$cid) continue;
            DB::update('categorias',['nome'=>trim($d['nome']),'cor'=>$d['cor'],'icone'=>trim($d['icone']??'box'),'ordem'=>(int)$d['ordem']],'id=?',[$cid]);
        }
        $msg_ok='Categorias salvas.';
    }

    if ($action === 'nova_categoria') {
        $nome=trim($_POST['cat_nome']??'');
        if ($nome) { DB::insert('categorias',['nome'=>$nome,'cor'=>$_POST['cat_cor']??'#f59e0b','icone'=>trim($_POST['cat_icone']??'box'),'ordem'=>0,'ativo'=>1]); $msg_ok='Categoria criada.'; }
    }
}

// DELETE usuário (GET)
if (isset($_GET['del_user'])) {
    $uid=(int)$_GET['del_user'];
    if ($uid && $uid!==Auth::id()) DB::update('usuarios',['ativo'=>0],'id=?',[$uid]);
    redirect(BASE_URL.'modules/configuracoes/index.php?tab=usuarios');
}

$cfg        = array_column(DB::all("SELECT chave,valor FROM configuracoes"),'valor','chave');
$terminais  = DB::all("SELECT * FROM mp_terminais ORDER BY nome");
$categorias = DB::all("SELECT * FROM categorias ORDER BY ordem,nome");
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurações — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>:root{--amber:<?= $_cor ?>;--amber-dark:<?= $_cor2 ?>;}</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/nav.php'; ?>
<div class="admin-content">
<div class="page-header"><h4><i class="ph-bold ph-gear me-2"></i>Configurações</h4></div>

<?= flash('success') ?>
<?= flash('error') ?>
<?php if ($msg_ok):  ?><div class="alert alert-success" style="font-size:.82rem;line-height:1.6"><?= $msg_ok ?></div><?php endif; ?>
<?php if ($msg_err): ?><div class="alert alert-danger"  style="font-size:.82rem;line-height:1.6"><?= $msg_err ?></div><?php endif; ?>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php foreach (['geral'=>['gear','Geral'],'visual'=>['palette','Visual'],'mercadopago'=>['credit-card','Mercado Pago'],'categorias'=>['tag','Categorias'],'usuarios'=>['users-three','Usuários'],'ticket_layout'=>['ticket','Layout Tickets']] as $t=>[$ico,$lbl]): ?>
  <a href="?tab=<?= $t ?>" class="btn btn-sm <?= $tab===$t?'btn-amber':'btn-outline-secondary' ?>"><i class="ph-bold ph-<?= $ico ?> me-1"></i><?= $lbl ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'geral'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="card-section-title">Dados do Estabelecimento</div>
      <form method="POST">
        <input type="hidden" name="action" value="salvar_geral">
        <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="nome_estabelecimento" class="form-control" value="<?= h($cfg['nome_estabelecimento']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">CNPJ</label><input type="text" name="cnpj" class="form-control" value="<?= h($cfg['cnpj']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Endereço</label><input type="text" name="endereco" class="form-control" value="<?= h($cfg['endereco']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Telefone</label><input type="text" name="telefone" class="form-control" value="<?= h($cfg['telefone']??'') ?>"></div>
        <div class="mb-3"><label class="form-label">Cor Primária</label><div class="d-flex gap-2 align-items-center"><input type="color" name="cor_primaria" class="form-control form-control-color" value="<?= h($cfg['cor_primaria']??'#f59e0b') ?>" style="width:60px;height:38px"><span style="color:var(--text-muted);font-size:.8rem">Padrão: #f59e0b</span></div></div>
        <div class="mb-3"><label class="form-label">Taxa de Serviço (%)</label><div class="input-group"><input type="number" name="taxa_servico" class="form-control" value="<?= h($cfg['taxa_servico']??'0') ?>" min="0" max="100" step="0.5"><span class="input-group-text">%</span></div><small class="text-muted">0 = desativado</small></div>
        <button type="submit" class="btn btn-amber w-100 fw-bold">Salvar</button>
      </form>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="admin-card mb-3">
      <div class="card-section-title">🍺 Chopp (Barril)</div>
      <form method="POST">
        <input type="hidden" name="action" value="salvar_geral">
        <div class="mb-3"><label class="form-label">Rendimento padrão (%)</label><div class="input-group"><input type="number" name="rendimento_barril_padrao" class="form-control" value="<?= h($cfg['rendimento_barril_padrao']??'85') ?>" min="50" max="100" step="0.5"><span class="input-group-text">%</span></div></div>
        <div class="mb-3"><label class="form-label">ML por copo padrão</label><div class="input-group"><input type="number" name="ml_dose_padrao" class="form-control" value="<?= h($cfg['ml_dose_padrao']??'300') ?>" min="50" step="10"><span class="input-group-text">ml</span></div></div>
        <?php $calc=calcBarril(30000,(float)($cfg['rendimento_barril_padrao']??85),(float)($cfg['ml_dose_padrao']??300)); ?>
        <div class="barril-calc"><div class="calc-row"><span>Barril 30L:</span><span style="color:var(--amber);font-family:'Syne',sans-serif"><?= $calc['doses'] ?> copos</span></div></div>
        <button type="submit" class="btn btn-amber w-100 fw-bold mt-3">Salvar</button>
      </form>
    </div>
    <div class="admin-card">
      <div class="card-section-title">🥃 Destilado (Dose)</div>
      <form method="POST">
        <input type="hidden" name="action" value="salvar_geral">
        <div class="mb-3">
          <label class="form-label">ML por dose padrão</label>
          <select name="ml_dose_destilado_padrao" class="form-select">
            <?php foreach ([30=>'30ml',40=>'40ml',50=>'50ml (padrão)',60=>'60ml',100=>'100ml'] as $ml=>$lbl): ?>
            <option value="<?= $ml ?>" <?= ($cfg['ml_dose_destilado_padrao']??50)==$ml?'selected':'' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php $mlD=(int)($cfg['ml_dose_destilado_padrao']??50); ?>
        <div class="barril-calc">
          <div class="calc-row"><span>Garrafa 750ml:</span><span style="color:var(--amber)"><?= floor(750/$mlD) ?> doses</span></div>
          <div class="calc-row"><span>Garrafa 1L:</span><span style="color:var(--amber)"><?= floor(1000/$mlD) ?> doses</span></div>
        </div>
        <button type="submit" class="btn btn-amber w-100 fw-bold mt-3">Salvar</button>
      </form>
    </div>
  </div>
</div>

<?php elseif ($tab === 'visual'): ?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="salvar_visual">
<div class="row g-3">
  <div class="col-lg-6">
    <div class="admin-card mb-3">
      <div class="card-section-title"><i class="ph-bold ph-palette me-2"></i>Cores</div>
      <div class="row g-3">
        <div class="col-6"><label class="form-label">Cor Primária</label><input type="color" name="cor_primaria" class="form-control form-control-color w-100" value="<?= h($cfg['cor_primaria']??'#f59e0b') ?>" style="height:44px"></div>
        <div class="col-6"><label class="form-label">Cor Secundária</label><input type="color" name="cor_secundaria" class="form-control form-control-color w-100" value="<?= h($cfg['cor_secundaria']??'#d97706') ?>" style="height:44px"></div>
      </div>
    </div>
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-image me-2"></i>Logo Login</div>
      <?php if (!empty($cfg['logo_login'])): ?><img src="<?= UPLOAD_URL ?>logos/<?= h($cfg['logo_login']) ?>" style="max-height:80px;margin-bottom:.75rem;border-radius:8px"><?php endif; ?>
      <input type="file" name="logo_login" class="form-control" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-monitor me-2"></i>Logo PDV</div>
      <?php if (!empty($cfg['logo_pdv'])): ?><img src="<?= UPLOAD_URL ?>logos/<?= h($cfg['logo_pdv']) ?>" style="max-height:80px;margin-bottom:.75rem;border-radius:8px"><?php endif; ?>
      <input type="file" name="logo_pdv" class="form-control" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>
</div>
<div class="mt-3"><button type="submit" class="btn btn-amber fw-bold px-5"><i class="ph-bold ph-check me-2"></i>Salvar Visual</button></div>
</form>

<?php elseif ($tab === 'mercadopago'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="admin-card mb-3">
      <div class="card-section-title"><i class="ph-bold ph-credit-card me-2"></i>Credenciais Mercado Pago</div>

      <div style="background:rgba(0,158,227,.1);border:1px solid rgba(0,158,227,.3);border-radius:8px;padding:.875rem;margin-bottom:1rem;font-size:.78rem;line-height:1.8">
        <strong style="color:#009ee3">Como configurar o Mercado Pago Point:</strong><br>
        1. Acesse <a href="https://www.mercadopago.com.br/developers" target="_blank" style="color:#009ee3">mercadopago.com.br/developers</a><br>
        2. <strong>Suas Integrações → Criar Aplicação → Point</strong><br>
        3. Copie o <strong>Access Token</strong> de produção (começa com <code>APP_USR-</code>)<br>
        4. Vincule sua maquininha Point à conta via app Mercado Pago<br>
        5. Cole o Token abaixo, salve e clique em <strong>Testar Conexão</strong>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="salvar_mp">

        <div class="mb-3">
          <label class="form-label">Access Token</label>
          <textarea name="mp_access_token" class="form-control font-mono" rows="2"
                    spellcheck="false" autocomplete="off"
                    data-lpignore="true" data-1p-ignore="true"
                    placeholder="APP_USR-0000000000000000-000000-00000000000000000000000000000000-000000000"><?= h($cfg['mp_access_token']??'') ?></textarea>
          <small style="color:var(--text-muted);font-size:.72rem">
            Token de produção começa com <code>APP_USR-</code> · Token de teste começa com <code>TEST-</code>
          </small>
        </div>

        <div class="mb-3">
          <label class="form-label">Device ID do Terminal <span style="color:var(--text-muted);font-size:.72rem">(preenche automaticamente ao testar)</span></label>
          <input type="text" name="mp_device_id" class="form-control font-mono"
                 value="<?= h($cfg['mp_device_id']??'') ?>"
                 autocomplete="off" placeholder="Ex: INGENICO_MOVE2500__ING-ARG-12345678">
          <small style="color:var(--text-muted);font-size:.72rem">Obtido ao clicar em Testar Conexão abaixo</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Webhook Secret <span style="color:var(--text-muted);font-size:.72rem">(opcional)</span></label>
          <input type="text" name="mp_webhook_secret" class="form-control font-mono"
                 value="<?= h($cfg['mp_webhook_secret']??'') ?>"
                 autocomplete="off" readonly onfocus="this.removeAttribute('readonly')"
                 placeholder="Para validar assinatura dos webhooks">
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-amber flex-fill fw-bold">
            <i class="ph-bold ph-check me-1"></i>Salvar
          </button>
          <button type="submit" name="action" value="testar_mp" class="btn btn-outline-secondary flex-fill">
            <i class="ph-bold ph-plug me-1"></i>Testar Conexão
          </button>
        </div>
      </form>
    </div>

    <div class="admin-card">
      <div style="font-size:.75rem;color:var(--text-muted);font-weight:600;margin-bottom:.5rem">URL DO WEBHOOK</div>
      <div style="background:var(--bg-card2);font-family:monospace;font-size:.78rem;padding:.5rem .75rem;border-radius:8px;color:#009ee3;cursor:pointer;word-break:break-all"
           onclick="navigator.clipboard.writeText('<?= rtrim(BASE_URL,'/') ?>/webhook.php');this.style.color='#22c55e';setTimeout(()=>this.style.color='',2000)">
        <?= rtrim(BASE_URL,'/') ?>/webhook.php
      </div>
      <small style="color:var(--text-muted)">Cadastre em: Mercado Pago Developers → Sua aplicação → Webhooks → Tópico: <code>order</code></small>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="admin-card">
      <div class="card-section-title"><i class="ph-bold ph-device-mobile me-2"></i>Terminais Cadastrados</div>
      <form method="POST" class="mb-3 p-2 rounded" style="background:var(--bg-card2)">
        <input type="hidden" name="action" value="salvar_terminal">
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control form-control-sm" placeholder="Ex: Caixa Principal">
          </div>
          <div class="col-12">
            <label class="form-label">Device ID</label>
            <input type="text" name="terminal_id" class="form-control form-control-sm font-mono" placeholder="Ex: INGENICO_MOVE2500__ING-ARG-12345678">
          </div>
          <div class="col-6">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-control form-control-sm" placeholder="Point Pro 2">
          </div>
          <div class="col-6 d-flex align-items-end">
            <button type="submit" class="btn btn-amber btn-sm w-100"><i class="ph-bold ph-plus me-1"></i>Adicionar</button>
          </div>
        </div>
      </form>

      <?php foreach ($terminais as $t): ?>
      <div class="d-flex justify-content-between align-items-center p-2 rounded mb-1" style="background:var(--bg-card2)">
        <div>
          <div class="fw-semibold" style="font-size:.85rem"><?= h($t['nome']) ?></div>
          <div style="font-size:.68rem;color:var(--text-muted);font-family:monospace"><?= h($t['device_id']) ?><?= $t['modelo']?' | '.h($t['modelo']):'' ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge-<?= $t['ativo']?'success':'muted' ?>"><?= $t['ativo']?'Ativo':'Inativo' ?></span>
          <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="toggle_terminal">
            <input type="hidden" name="tid" value="<?= $t['id'] ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm py-0"><i class="ph-bold ph-power"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($terminais)): ?>
      <p class="text-center py-3" style="color:var(--text-muted);font-size:.82rem">
        Nenhum terminal. Clique em <strong>Testar Conexão</strong> para ver terminais disponíveis.
      </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($tab === 'ticket_layout'): ?>
<script>window.location.href='<?= BASE_URL ?>modules/tickets/layout.php';</script>
<?php elseif ($tab === 'usuarios'): ?>
<?php
$usuarios_lista = DB::all("SELECT id,nome,login,perfil,ativo,ultimo_acesso FROM usuarios ORDER BY perfil,nome");
$editU = isset($_GET['edit_user']) ? DB::row("SELECT * FROM usuarios WHERE id=?",[(int)$_GET['edit_user']]) : null;
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="admin-card">
      <div class="card-section-title">Usuários Cadastrados</div>
      <table class="admin-table">
        <thead><tr><th>Nome</th><th>Login</th><th>Perfil</th><th>Último Acesso</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($usuarios_lista as $u):
            $cor=match($u['perfil']){'admin'=>'danger','caixa_bar'=>'amber','caixa_totem'=>'success',default=>'muted'};
            $lbl=match($u['perfil']){'admin'=>'Admin','caixa_bar'=>'Caixa Bar','caixa_totem'=>'Totem',default=>$u['perfil']};
          ?><tr>
            <td class="fw-semibold"><?= h($u['nome']) ?></td>
            <td style="font-family:monospace;font-size:.8rem"><?= h($u['login']) ?></td>
            <td><span class="badge-<?= $cor ?>"><?= $lbl ?></span></td>
            <td style="font-size:.72rem;color:var(--text-muted)"><?= $u['ultimo_acesso']?dataHoraBR($u['ultimo_acesso']):'—' ?></td>
            <td><span class="badge-<?= $u['ativo']?'success':'muted' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
            <td>
              <a href="<?= BASE_URL ?>modules/configuracoes/index.php?tab=usuarios&edit_user=<?= $u['id'] ?>" class="btn btn-outline-secondary btn-sm py-0" title="Editar"><i class="ph-bold ph-pencil"></i></a>
              <?php if ($u['id']!==Auth::id()): ?>
              <a href="<?= BASE_URL ?>modules/configuracoes/index.php?tab=usuarios&del_user=<?= $u['id'] ?>" class="btn btn-outline-danger btn-sm py-0" onclick="return swalConfirm(event,'Desativar usuário?','Desativar',this.href)" title="Desativar"><i class="ph-bold ph-x"></i></a>
              <?php endif; ?>
            </td>
          </tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="admin-card">
      <div class="card-section-title"><?= $editU ? '✏️ Editar: '.h($editU['nome']) : '➕ Novo Usuário' ?></div>
      <form method="POST" action="<?= BASE_URL ?>modules/configuracoes/index.php?tab=usuarios">
        <input type="hidden" name="action"  value="salvar_usuario">
        <input type="hidden" name="user_id" value="<?= (int)($editU['id']??0) ?>">
        <div class="mb-2">
          <label class="form-label">Nome Completo</label>
          <input type="text" name="u_nome" class="form-control" required value="<?= h($editU['nome']??'') ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Login</label>
          <input type="text" name="u_login" class="form-control" required value="<?= h($editU['login']??'') ?>" autocomplete="off">
        </div>
        <div class="mb-2">
          <label class="form-label">
            Senha<?= $editU ? ' <small style="font-weight:400;color:var(--text-muted)">(em branco = manter)</small>' : ' *' ?>
          </label>
          <input type="password" name="u_senha" class="form-control" <?= $editU?'':'required' ?> autocomplete="new-password">
        </div>
        <div class="mb-2">
          <label class="form-label">Perfil</label>
          <select name="u_perfil" class="form-select" onchange="updateFormas(this.value)">
            <option value="admin"       <?= ($editU['perfil']??'')==='admin'      ?'selected':'' ?>>Admin</option>
            <option value="caixa_bar"   <?= ($editU['perfil']??'')==='caixa_bar'  ?'selected':'' ?>>Caixa Bar</option>
            <option value="caixa_totem" <?= ($editU['perfil']??'')==='caixa_totem'?'selected':'' ?>>Caixa Totem</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Formas de Pagamento</label>
          <?php
          $editFormas  = $editU && $editU['formas_pagamento'] ? json_decode($editU['formas_pagamento'],true) : [];
          $todasFormas = ['dinheiro'=>'Dinheiro','pix'=>'PIX','cartao_debito'=>'Débito','cartao_credito'=>'Crédito','mercadopago'=>'Mercado Pago','cortesia'=>'Cortesia'];
          ?>
          <div id="formasCheck">
          <?php foreach ($todasFormas as $fk=>$fl): ?>
          <div class="form-check form-check-inline">
            <input type="checkbox" name="u_formas[]" id="f_<?= $fk ?>" value="<?= $fk ?>" class="form-check-input"
                   <?= (empty($editFormas)||in_array($fk,$editFormas))?'checked':'' ?>>
            <label class="form-check-label" for="f_<?= $fk ?>" style="font-size:.8rem"><?= $fl ?></label>
          </div>
          <?php endforeach; ?>
          </div>
        </div>
        <div class="form-check form-switch mb-3">
          <input type="checkbox" name="u_ativo" class="form-check-input" value="1" <?= ($editU['ativo']??1)?'checked':'' ?>>
          <label class="form-check-label">Ativo</label>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-amber flex-fill fw-bold">
            <i class="ph-bold ph-check me-1"></i><?= $editU ? 'Salvar Alterações' : 'Criar Usuário' ?>
          </button>
          <?php if ($editU): ?>
          <a href="<?= BASE_URL ?>modules/configuracoes/index.php?tab=usuarios" class="btn btn-outline-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function updateFormas(perfil) {
  const perms={admin:['dinheiro','mercadopago','cortesia'],caixa_bar:['dinheiro','mercadopago'],caixa_totem:['mercadopago']};
  const ok=perms[perfil]||[];
  document.querySelectorAll('#formasCheck input').forEach(cb=>{cb.checked=ok.includes(cb.value);cb.disabled=perfil==='admin';});
}
</script>

<?php elseif ($tab === 'categorias'): ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="card-section-title">Categorias</div>
      <form method="POST">
        <input type="hidden" name="action" value="salvar_categorias">
        <table class="admin-table">
          <thead><tr><th>Nome</th><th>Cor</th><th>Ícone</th><th>Ordem</th></tr></thead>
          <tbody>
            <?php foreach ($categorias as $cat): ?>
            <tr>
              <td><input type="text" name="cats[<?=$cat['id']?>][nome]" class="form-control form-control-sm" value="<?=h($cat['nome'])?>"></td>
              <td><input type="color" name="cats[<?=$cat['id']?>][cor]" class="form-control form-control-color form-control-sm" value="<?=h($cat['cor']??'#f59e0b')?>" style="width:50px;height:34px"></td>
              <td><input type="text" name="cats[<?=$cat['id']?>][icone]" class="form-control form-control-sm font-mono" value="<?=h($cat['icone']??'')?>" placeholder="beer-mug-empty"></td>
              <td><input type="number" name="cats[<?=$cat['id']?>][ordem]" class="form-control form-control-sm" value="<?=$cat['ordem']?>" style="width:70px"></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <button type="submit" class="btn btn-amber mt-3 fw-bold">Salvar Categorias</button>
      </form>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <div class="card-section-title">Nova Categoria</div>
      <form method="POST">
        <input type="hidden" name="action" value="nova_categoria">
        <div class="mb-2"><label class="form-label">Nome</label><input type="text" name="cat_nome" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Cor</label><input type="color" name="cat_cor" class="form-control form-control-color" value="#f59e0b" style="height:38px"></div>
        <div class="mb-3"><label class="form-label">Ícone</label><input type="text" name="cat_icone" class="form-control font-mono" placeholder="beer-mug-empty"></div>
        <button type="submit" class="btn btn-amber w-100 fw-bold">Criar</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function swalConfirm(e,msg,btnTxt,href){
  e.preventDefault();
  Swal.fire({icon:'warning',title:msg,showCancelButton:true,confirmButtonText:btnTxt,
    cancelButtonText:'Cancelar',confirmButtonColor:'#ef4444',background:'#1e2330',color:'#f0f2f7'})
    .then(r=>{if(r.isConfirmed)window.location.href=href;});
}
</script>
</body>
</html>
