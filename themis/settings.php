<?php
declare(strict_types=1);
define('THEMIS_ROOT', __DIR__);
$_cfg    = file_exists(THEMIS_ROOT . '/_app/config/app.php') ? require THEMIS_ROOT . '/_app/config/app.php' : [];
$_appUrl = rtrim($_cfg['app']['url'] ?? '', '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Themis — Configurações</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="icon" href="/assets/img/themis_logo.png" type="image/png">
<style>
:root{
  --bg:#0f1117;--sf:#161b27;--el:#1e2535;--hv:#242d40;
  --br:#2a3449;--bs:#1e2840;
  --t1:#e8edf5;--t2:#8b95a9;--t3:#4f5b72;
  --blue:#3b82f6;--blue-d:#1e3a5f;--blue-g:rgba(59,130,246,.15);
  --teal:#14b8a6;--amber:#f59e0b;--rose:#f43f5e;
  --emerald:#10b981;--violet:#8b5cf6;
  --font:'DM Sans',sans-serif;--mono:'JetBrains Mono',monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:var(--font);background:var(--bg);color:var(--t1);-webkit-font-smoothing:antialiased}

/* Layout */
.shell{display:grid;grid-template-columns:240px 1fr;min-height:100vh}

/* Sidebar */
.sidebar{background:var(--sf);border-right:1px solid var(--br);padding:20px 12px;display:flex;flex-direction:column;gap:3px;position:sticky;top:0;height:100vh;overflow-y:auto}
.logo{display:flex;align-items:center;gap:10px;padding:8px 10px;margin-bottom:16px}
.logo img{height:34px;width:auto}
.logo-mark{width:36px;height:36px;background:linear-gradient(135deg,var(--blue),var(--teal));border-radius:9px;display:grid;place-items:center;font-size:16px;font-weight:700;color:#fff;flex-shrink:0}
.logo-txt strong{font-size:13px;font-weight:700;display:block}
.logo-txt small{font-size:10.5px;color:var(--t3)}
.nav{display:flex;align-items:center;gap:9px;padding:9px 10px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--t2);text-decoration:none;transition:all .15s;border:none;background:none;width:100%;text-align:left}
.nav:hover{color:var(--t1);background:var(--el)}
.nav.active{color:var(--blue);background:var(--blue-d)}
.nav-icon{font-size:15px;width:20px;text-align:center;flex-shrink:0}
.nav-section{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--t3);padding:14px 10px 5px;margin-top:4px}
.user-row{margin-top:auto;padding:10px;background:var(--el);border-radius:8px;display:flex;align-items:center;gap:9px;font-size:12.5px}
.av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:grid;place-items:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.btn-sair{margin-left:auto;font-size:11px;color:var(--t3);cursor:pointer;padding:4px 8px;border-radius:6px;border:1px solid var(--br);background:transparent;color:var(--t2);font-family:var(--font);transition:all .15s}
.btn-sair:hover{color:var(--rose);border-color:rgba(244,63,94,.3)}

/* Main */
.main{padding:28px 32px;max-width:900px}
.page-header{margin-bottom:28px}
.page-header h1{font-size:21px;font-weight:700;letter-spacing:-.02em}
.page-header p{font-size:13px;color:var(--t2);margin-top:4px}

/* Sections */
.section{margin-bottom:20px}
.section-card{background:var(--sf);border:1px solid var(--br);border-radius:12px;overflow:hidden}
.section-head{padding:16px 22px;border-bottom:1px solid var(--bs);display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;transition:background .15s}
.section-head:hover{background:var(--el)}
.section-title{display:flex;align-items:center;gap:10px;font-size:14px;font-weight:700}
.section-icon{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;font-size:15px;flex-shrink:0}
.si-blue{background:rgba(59,130,246,.12);color:var(--blue)}
.si-teal{background:rgba(20,184,166,.12);color:var(--teal)}
.si-green{background:rgba(16,185,129,.12);color:var(--emerald)}
.si-amber{background:rgba(245,158,11,.12);color:var(--amber)}
.si-violet{background:rgba(139,92,246,.12);color:var(--violet)}
.si-rose{background:rgba(244,63,94,.12);color:var(--rose)}
.status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.dot-green{background:var(--emerald);box-shadow:0 0 6px var(--emerald)}
.dot-gray{background:var(--t3)}
.chevron{font-size:12px;color:var(--t3);transition:transform .2s}
.chevron.open{transform:rotate(180deg)}
.section-body{padding:22px;display:none;flex-direction:column;gap:16px}
.section-body.open{display:flex}

/* Form */
.fg{display:flex;flex-direction:column;gap:6px}
.flabel{font-size:11.5px;font-weight:600;color:var(--t2);text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px}
.flabel small{font-size:10px;color:var(--t3);text-transform:none;font-weight:400;letter-spacing:0}
.finput{background:var(--el);border:1px solid var(--br);border-radius:8px;padding:10px 14px;font-size:13.5px;color:var(--t1);font-family:var(--font);outline:none;transition:border-color .18s,box-shadow .18s;width:100%}
.finput:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-g)}
.finput::placeholder{color:var(--t3)}
.fselect{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b95a9' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;background-color:var(--el);cursor:pointer;padding-right:32px}
.fselect option{background:var(--el)}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.hint{font-size:11.5px;color:var(--t3);line-height:1.5;margin-top:-4px}
.hint a{color:var(--blue);text-decoration:none}
.hint a:hover{text-decoration:underline}

/* Botões */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;font-size:13.5px;font-weight:600;font-family:var(--font);cursor:pointer;border:none;transition:all .18s;white-space:nowrap}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 2px 10px rgba(59,130,246,.3)}
.btn-primary:hover{background:#2563eb;transform:translateY(-1px)}
.btn-ghost{background:var(--el);color:var(--t2);border:1px solid var(--br)}
.btn-ghost:hover{color:var(--t1)}
.btn-test{background:rgba(16,185,129,.1);color:var(--emerald);border:1px solid rgba(16,185,129,.25)}
.btn-test:hover{background:rgba(16,185,129,.2)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.btn-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding-top:4px;border-top:1px solid var(--bs);margin-top:4px}

/* Toggle */
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0}
.toggle-lbl{font-size:13px;font-weight:500;color:var(--t2)}
.toggle{position:relative;width:42px;height:24px;cursor:pointer;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.tslider{position:absolute;inset:0;background:var(--el);border:1px solid var(--br);border-radius:999px;transition:.2s}
.tslider::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:var(--t3);border-radius:50%;transition:.2s}
.toggle input:checked+.tslider{background:var(--blue);border-color:var(--blue)}
.toggle input:checked+.tslider::before{transform:translateX(18px);background:#fff}

/* Alerts */
.toast{position:fixed;bottom:24px;right:24px;padding:13px 18px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(0,0,0,.4);z-index:9999;transform:translateY(80px);opacity:0;transition:all .3s cubic-bezier(.4,0,.2,1);max-width:380px;border:1px solid}
.toast.show{transform:none;opacity:1}
.toast-ok{background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.3);color:var(--emerald)}
.toast-err{background:rgba(244,63,94,.12);border-color:rgba(244,63,94,.3);color:var(--rose)}

/* Separador interno */
.inner-sep{height:1px;background:var(--bs);margin:4px 0}

/* Provider tabs */
.provider-tabs{display:flex;gap:2px;background:var(--el);border-radius:9px;padding:3px;margin-bottom:4px}
.ptab{flex:1;padding:7px;border-radius:7px;font-size:12.5px;font-weight:600;font-family:var(--font);cursor:pointer;background:none;border:none;color:var(--t2);transition:all .18s;text-align:center}
.ptab.active{background:var(--sf);color:var(--t1);box-shadow:0 1px 4px rgba(0,0,0,.3)}
.provider-panel{display:none}
.provider-panel.active{display:contents}

@media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{display:none}.main{padding:20px}}
</style>
</head>
<body>
<div class="shell">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo">
    <img src="/assets/img/themis_logo.png" alt="Themis"
         onerror="this.style.display='none';document.querySelector('.logo-mark').style.display='grid'">
    <div class="logo-mark" style="display:none">⚖</div>
    <div class="logo-txt"><strong>Themis</strong><small>Enterprise</small></div>
  </div>
  <div class="nav-section">Principal</div>
  <a class="nav" href="/app">          <span class="nav-icon">⬡</span> Dashboard</a>
  <a class="nav" href="/app#processos">          <span class="nav-icon">⚖</span> Processos</a>
  <a class="nav" href="/app">          <span class="nav-icon">👥</span> Clientes</a>
  <div class="nav-section">Sistema</div>
  <a class="nav active" href="/settings"><span class="nav-icon">⚙</span> Configurações</a>
  <div style="margin-top:auto">
    <div class="user-row">
      <div class="av" id="sidebarAv">?</div>
      <div>
        <div style="font-weight:600;color:var(--t1);font-size:13px" id="sidebarNome">Carregando…</div>
        <div style="font-size:10.5px;color:var(--t3)" id="sidebarPerfil"></div>
      </div>
      <button class="btn-sair" onclick="logout()">Sair</button>
    </div>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="page-header">
    <h1>⚙ Configurações do Sistema</h1>
    <p>Gerencie integrações, dados do escritório e preferências do Themis Enterprise</p>
  </div>

  <!-- ── ESCRITÓRIO ── -->
  <div class="section">
    <div class="section-card">
      <div class="section-head" onclick="toggle(this)">
        <div class="section-title">
          <div class="section-icon si-blue">🏛</div>
          Escritório / Advogado
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span class="chevron">▼</span>
        </div>
      </div>
      <div class="section-body open" id="sec-escritorio">
        <div class="fg">
          <label class="flabel">Nome do Escritório ou Advogado</label>
          <input class="finput" id="app_name" placeholder="Ex: Melo & Associados Advocacia">
        </div>
        <div class="fg">
          <label class="flabel">URL do Sistema <small>(usado nos documentos e e-mails)</small></label>
          <input class="finput" id="app_url" placeholder="https://themis.ogrosystemas.com.br">
        </div>
        <div class="g2">
          <div class="fg">
            <label class="flabel">Fuso Horário</label>
            <select class="finput fselect" id="app_timezone">
              <option value="America/Sao_Paulo">America/Sao_Paulo (BRT -3)</option>
              <option value="America/Manaus">America/Manaus (AMT -4)</option>
              <option value="America/Belem">America/Belem (BRT -3)</option>
              <option value="America/Fortaleza">America/Fortaleza (BRT -3)</option>
              <option value="America/Recife">America/Recife (BRT -3)</option>
              <option value="America/Bahia">America/Bahia (BRT -3)</option>
              <option value="America/Porto_Velho">America/Porto_Velho (AMT -4)</option>
              <option value="America/Rio_Branco">America/Rio_Branco (ACT -5)</option>
              <option value="America/Noronha">America/Noronha (FNT -2)</option>
            </select>
          </div>
          <div class="fg">
            <label class="flabel">Valor por Km (R$) <small>para reembolso de despesas</small></label>
            <input class="finput" type="number" step="0.01" id="valor_km" placeholder="0.90">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-primary" onclick="salvar('escritorio')">💾 Salvar Escritório</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── ASSINAFY ── -->
  <div class="section">
    <div class="section-card">
      <div class="section-head" onclick="toggle(this)">
        <div class="section-title">
          <div class="section-icon si-violet">✍</div>
          Assinafy — Assinatura Digital
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="status-dot dot-gray" id="dot-assinafy"></div>
          <span class="chevron">▼</span>
        </div>
      </div>
      <div class="section-body" id="sec-assinafy">
        <p class="hint">Obtenha suas credenciais em <a href="https://app.assinafy.com.br" target="_blank">app.assinafy.com.br</a> → My Account → API</p>
        <div class="form-group">
          <label class="flabel">Workspace Account ID <span style="color:var(--rose)">*</span></label>
          <input class="finput" id="assinafy_account_id" placeholder="Ex: 615601fab04c0a31" autocomplete="off">
          <small style="color:var(--t3)">Encontre em: My Account → Workspaces (aba)</small>
        </div>
        <div class="fg">
          <label class="flabel">API Key <span style="color:var(--rose)">*</span></label>
          <input class="finput" id="assinafy_token" placeholder="Gerada em My Account → API → Generate API Key" autocomplete="off">
        </div>
        <div class="fg">
          <label class="flabel">Webhook Secret <small>(para validar callbacks)</small></label>
          <input class="finput" id="assinafy_secret" placeholder="••••••••" autocomplete="off">
        </div>
        <div class="fg">
          <label class="flabel">URL do Webhook <small>(cole no painel Assinafy)</small></label>
          <input class="finput" id="assinafy_webhook" readonly style="cursor:pointer;color:var(--teal)" onclick="copyField(this)">
          <span class="hint">Clique para copiar · O Themis recebe status de assinatura automaticamente</span>
        </div>
        <div class="btn-row">
          <button class="btn btn-primary" onclick="salvar('assinafy')">💾 Salvar Assinafy</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── WHATSAPP ── -->
  <div class="section">
    <div class="section-card">
      <div class="section-head" onclick="toggle(this)">
        <div class="section-title">
          <div class="section-icon si-green">💬</div>
          WhatsApp
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="status-dot dot-gray" id="dot-whatsapp"></div>
          <span class="chevron">▼</span>
        </div>
      </div>
      <div class="section-body" id="sec-whatsapp">
        <div class="provider-tabs">
          <button class="ptab active" onclick="switchProvider('evolution', this)">Evolution API</button>
          <button class="ptab" onclick="switchProvider('meta', this)">Meta (Official)</button>
        </div>

        <!-- Evolution API -->
        <div class="provider-panel active" id="prov-evolution">
          <p class="hint" style="display:block">Evolution API é auto-hospedada. <a href="https://doc.evolution-api.com" target="_blank">Ver documentação →</a></p>
          <div class="fg">
            <label class="flabel">URL Base da Evolution API</label>
            <input class="finput" id="wh_base_url" placeholder="https://evolution.seuservidor.com">
          </div>
          <div class="g2">
            <div class="fg">
              <label class="flabel">Nome da Instância</label>
              <input class="finput" id="wh_instance" placeholder="themis">
            </div>
            <div class="fg">
              <label class="flabel">API Key (Global)</label>
              <input class="finput" id="wh_api_key" placeholder="••••••••" autocomplete="off">
            </div>
          </div>
        </div>

        <!-- Meta API -->
        <div class="provider-panel" id="prov-meta">
          <p class="hint" style="display:block">API Oficial do WhatsApp Business (Meta). <a href="https://developers.facebook.com/docs/whatsapp" target="_blank">Ver documentação →</a></p>
          <div class="fg">
            <label class="flabel">Phone Number ID</label>
            <input class="finput" id="wh_phone_id" placeholder="123456789012345">
          </div>
          <div class="fg">
            <label class="flabel">Access Token</label>
            <input class="finput" id="wh_token" placeholder="EAAb••••••••" autocomplete="off">
          </div>
          <div class="fg">
            <label class="flabel">Verify Token <small>(webhook)</small></label>
            <input class="finput" id="wh_verify_token" placeholder="themis_verify_2025">
          </div>
          <div class="fg">
            <label class="flabel">URL do Webhook <small>(cole no Meta Developers)</small></label>
            <input class="finput" id="wh_webhook_url" readonly style="cursor:pointer;color:var(--teal)" onclick="copyField(this)">
          </div>
        </div>

        <div class="btn-row">
          <button class="btn btn-primary" onclick="salvar('whatsapp')">💾 Salvar WhatsApp</button>
          <div style="display:flex;gap:8px;align-items:center;margin-left:auto">
            <input class="finput" id="wh_test_tel" placeholder="5511999999999" style="width:180px">
            <button class="btn btn-test" onclick="testarWhatsapp()">📱 Testar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── DATAJUD / CNJ ── -->
  <div class="section">
    <div class="section-card">
      <div class="section-head" onclick="toggle(this)">
        <div class="section-title">
          <div class="section-icon si-amber">🛰</div>
          DataJud / CNJ — Radar Processual
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="status-dot dot-gray" id="dot-datajud"></div>
          <span class="chevron">▼</span>
        </div>
      </div>
      <div class="section-body" id="sec-datajud">
        <p class="hint">Obtenha sua chave em <a href="https://datajud-wiki.cnj.jus.br/api-publica/acesso" target="_blank">datajud-wiki.cnj.jus.br</a> → Credenciais de Acesso</p>
        <div class="fg">
          <label class="flabel">API Key DataJud</label>
          <input class="finput" id="datajud_api_key" placeholder="APIKey ••••••••" autocomplete="off">
          <span class="hint">A API pública do CNJ é gratuita. A chave é enviada por e-mail após cadastro.</span>
        </div>
        <div class="fg">
          <label class="flabel">URL Base <small>(não altere salvo se o CNJ mudar)</small></label>
          <input class="finput" id="datajud_base_url" placeholder="https://api-publica.datajud.cnj.jus.br">
        </div>
        <div class="btn-row">
          <button class="btn btn-primary" onclick="salvar('datajud')">💾 Salvar DataJud</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── E-MAIL ── -->
  <div class="section">
    <div class="section-card">
      <div class="section-head" onclick="toggle(this)">
        <div class="section-title">
          <div class="section-icon si-teal">📧</div>
          E-mail SMTP
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="status-dot dot-gray" id="dot-mail"></div>
          <span class="chevron">▼</span>
        </div>
      </div>
      <div class="section-body" id="sec-mail">
        <div class="g2">
          <div class="fg">
            <label class="flabel">Servidor SMTP</label>
            <input class="finput" id="mail_host" placeholder="smtp.gmail.com">
          </div>
          <div class="g2" style="gap:10px">
            <div class="fg">
              <label class="flabel">Porta</label>
              <input class="finput" type="number" id="mail_port" placeholder="587">
            </div>
            <div class="fg">
              <label class="flabel">Segurança</label>
              <select class="finput fselect" id="mail_encryption">
                <option value="tls">TLS (587)</option>
                <option value="ssl">SSL (465)</option>
                <option value="none">Nenhuma (25)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="g2">
          <div class="fg">
            <label class="flabel">Usuário (e-mail)</label>
            <input class="finput" id="mail_user" placeholder="noreply@seudominio.com.br" autocomplete="off">
          </div>
          <div class="fg">
            <label class="flabel">Senha</label>
            <input class="finput" type="password" id="mail_pass" placeholder="••••••••" autocomplete="new-password">
          </div>
        </div>
        <div class="inner-sep"></div>
        <div class="g2">
          <div class="fg">
            <label class="flabel">Nome do Remetente</label>
            <input class="finput" id="mail_from_name" placeholder="Melo & Associados">
          </div>
          <div class="fg">
            <label class="flabel">E-mail Remetente</label>
            <input class="finput" id="mail_from_addr" placeholder="noreply@seudominio.com.br">
          </div>
        </div>
        <div class="btn-row">
          <button class="btn btn-primary" onclick="salvar('mail')">💾 Salvar E-mail</button>
          <div style="display:flex;gap:8px;align-items:center;margin-left:auto">
            <input class="finput" id="mail_test_to" placeholder="seu@email.com" style="width:210px">
            <button class="btn btn-test" onclick="testarEmail()">📧 Enviar Teste</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</main>
</div>

<!-- Toast -->
<div class="toast" id="toast"><span id="toastIcon"></span><span id="toastMsg"></span></div>

<script>
const TOKEN    = localStorage.getItem('themis_token');
const API_BASE = '<?= $_appUrl ?>';
let   cfg      = {};

if (!TOKEN) window.location.href = API_BASE + '/login';

// ── Carrega dados ───────────────────────────────────────────
async function load() {
  try {
    const r = await api('GET', '/settings');
    if (!r.success) throw new Error(r.message);
    cfg = r.data;
    fill();
  } catch(e) {
    toast('Erro ao carregar configurações: ' + e.message, false);
  }
}

function fill() {
  // Escritório
  set('app_name',     cfg.escritorio?.nome     || '');
  set('app_url',      cfg.escritorio?.url      || '');
  set('app_timezone', cfg.escritorio?.timezone || 'America/Sao_Paulo');
  set('valor_km',     cfg.despesas?.valor_km_padrao || '0.90');

  // Assinafy
  set('assinafy_account_id', cfg.assinafy?.account_id || '');
  set('assinafy_token',   cfg.assinafy?.token  || '');
  set('assinafy_secret',  cfg.assinafy?.secret || '');
  set('assinafy_webhook', API_BASE + '/api/webhooks/assinafy');
  setDot('assinafy', cfg.assinafy?.ativo);

  // WhatsApp
  const wh = cfg.whatsapp || {};
  const prov = wh.provider || 'evolution';
  switchProvider(prov, document.querySelector(`.ptab:${prov === 'evolution' ? 'first-child' : 'last-child'}`));
  set('wh_base_url',    wh.base_url    || '');
  set('wh_instance',    wh.instance    || '');
  set('wh_api_key',     wh.api_key     || '');
  set('wh_phone_id',    wh.phone_id    || '');
  set('wh_token',       wh.token       || '');
  set('wh_verify_token',wh.verify_token|| '');
  set('wh_webhook_url', API_BASE + '/api/webhooks/whatsapp');
  setDot('whatsapp', wh.ativo);

  // DataJud
  set('datajud_api_key',  cfg.datajud?.api_key  || '');
  set('datajud_base_url', cfg.datajud?.base_url || 'https://api-publica.datajud.cnj.jus.br');
  setDot('datajud', cfg.datajud?.ativo);

  // Mail
  const m = cfg.mail || {};
  set('mail_host',       m.host      || '');
  set('mail_port',       m.port      || 587);
  set('mail_encryption', m.encryption|| 'tls');
  set('mail_user',       m.user      || '');
  set('mail_pass',       m.pass      || '');
  set('mail_from_name',  m.from_name || '');
  set('mail_from_addr',  m.from_addr || '');
  setDot('mail', m.ativo);

  // Usuário na sidebar
  const user = JSON.parse(localStorage.getItem('themis_user') || '{}');
  document.getElementById('sidebarNome').textContent  = user.nome    || 'Admin';
  document.getElementById('sidebarPerfil').textContent = user.perfil  || '';
  document.getElementById('sidebarAv').textContent    = (user.nome||'A').charAt(0).toUpperCase();
}

// ── Salvar seção ────────────────────────────────────────────
async function salvar(sec) {
  const payload = {};
  
  if (sec === 'escritorio') {
    payload.escritorio = {
      nome:     get('app_name'),
      url:      get('app_url'),
      timezone: get('app_timezone'),
    };
    payload.despesas = { valor_km_padrao: parseFloat(get('valor_km')) || 0.90 };
  }
  else if (sec === 'assinafy') {
    payload.assinafy = {
      account_id: get('assinafy_account_id'),
      token:  get('assinafy_token'),
      secret: get('assinafy_secret'),
    };
  }
  else if (sec === 'whatsapp') {
    const prov = document.querySelector('.ptab.active')?.dataset?.provider || 'evolution';
    payload.whatsapp = {
      provider:     prov,
      base_url:     get('wh_base_url'),
      instance:     get('wh_instance'),
      api_key:      get('wh_api_key'),
      phone_id:     get('wh_phone_id'),
      token:        get('wh_token'),
      verify_token: get('wh_verify_token'),
    };
  }
  else if (sec === 'datajud') {
    payload.datajud = {
      api_key:  get('datajud_api_key'),
      base_url: get('datajud_base_url'),
    };
  }
  else if (sec === 'mail') {
    payload.mail = {
      host:       get('mail_host'),
      port:       parseInt(get('mail_port')) || 587,
      encryption: get('mail_encryption'),
      user:       get('mail_user'),
      pass:       get('mail_pass'),
      from_name:  get('mail_from_name'),
      from_addr:  get('mail_from_addr'),
    };
  }

  const btn = event.currentTarget;
  btn.disabled = true;
  btn.textContent = 'Salvando…';

  try {
    const r = await api('POST', '/settings', payload);
    if (!r.success) throw new Error(r.message);
    toast('✅ ' + (r.message || 'Salvo com sucesso!'), true);
    await load(); // Recarrega para atualizar dots de status
  } catch(e) {
    toast('❌ ' + e.message, false);
  } finally {
    btn.disabled = false;
    btn.textContent = {
      escritorio: '💾 Salvar Escritório',
      assinafy:   '💾 Salvar Assinafy',
      whatsapp:   '💾 Salvar WhatsApp',
      datajud:    '💾 Salvar DataJud',
      mail:       '💾 Salvar E-mail',
    }[sec] || '💾 Salvar';
  }
}

// ── Testes ──────────────────────────────────────────────────
async function testarEmail() {
  const to = get('mail_test_to').trim();
  if (!to) { toast('Informe o e-mail de destino.', false); return; }
  try {
    const r = await api('POST', '/settings/test-mail', { to });
    toast(r.success ? '✅ ' + r.message : '❌ ' + r.message, r.success);
  } catch(e) { toast('❌ ' + e.message, false); }
}

async function testarWhatsapp() {
  const tel = get('wh_test_tel').trim();
  if (!tel) { toast('Informe o número com DDD+DDI (ex: 5511999999999).', false); return; }
  try {
    const r = await api('POST', '/settings/test-whatsapp', { telefone: tel });
    toast(r.success ? '✅ ' + r.message : '❌ ' + r.message, r.success);
  } catch(e) { toast('❌ ' + e.message, false); }
}

// ── UI helpers ───────────────────────────────────────────────
function toggle(head) {
  const body = head.nextElementSibling;
  const chev = head.querySelector('.chevron');
  body.classList.toggle('open');
  chev.classList.toggle('open');
}

function switchProvider(prov, btn) {
  document.querySelectorAll('.ptab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.provider-panel').forEach(p => p.classList.remove('active'));
  if (btn) { btn.classList.add('active'); btn.dataset.provider = prov; }
  const panel = document.getElementById('prov-' + prov);
  if (panel) panel.classList.add('active');
}

function setDot(key, ativo) {
  const d = document.getElementById('dot-' + key);
  if (!d) return;
  d.className = 'status-dot ' + (ativo ? 'dot-green' : 'dot-gray');
  if (ativo) d.style.boxShadow = '0 0 6px var(--emerald)';
  else d.style.boxShadow = '';
}

function set(id, val) {
  const el = document.getElementById(id);
  if (el) el.value = val;
}
function get(id) {
  const el = document.getElementById(id);
  return el ? el.value : '';
}

function copyField(el) {
  navigator.clipboard.writeText(el.value).then(() => toast('URL copiada!', true));
}

function toast(msg, ok) {
  const t  = document.getElementById('toast');
  const ic = document.getElementById('toastIcon');
  const ms = document.getElementById('toastMsg');
  ms.textContent = msg;
  t.className = 'toast show ' + (ok ? 'toast-ok' : 'toast-err');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), 4000);
}

function logout() {
  localStorage.removeItem('themis_token');
  localStorage.removeItem('themis_user');
  window.location.href = API_BASE + '/login';
}

// ── API call ────────────────────────────────────────────────
async function api(method, path, body) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN },
  };
  if (body) opts.body = JSON.stringify(body);
  // Tenta rewrite, fallback para api.php
  let r = await fetch(API_BASE + '/api' + path, opts);
  if (!r.ok && r.status === 404) {
    r = await fetch(API_BASE + '/api.php?r=' + path, { ...opts });
  }
  return r.json();
}

// Init
load();
</script>
</body>
</html>
