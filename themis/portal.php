<?php
declare(strict_types=1);
$_cfg    = file_exists(__DIR__ . '/_app/config/app.php') ? require __DIR__ . '/_app/config/app.php' : [];
$_appUrl = rtrim($_cfg['app']['url'] ?? '', '/');
$_nome   = htmlspecialchars($_cfg['app']['name'] ?? 'Themis Enterprise');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Portal do Cliente — <?= $_nome ?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<style>
:root{--bg:#0f1117;--sf:#161b27;--el:#1e2535;--br:#2a3449;--bs:#1e2840;--t1:#e8edf5;--t2:#8b95a9;--t3:#4f5b72;--blue:#3b82f6;--teal:#14b8a6;--amber:#f59e0b;--rose:#f43f5e;--emerald:#10b981;--violet:#8b5cf6;--fm:'DM Sans',sans-serif;--mo:'JetBrains Mono',monospace}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{min-height:100%;font-family:var(--fm);background:var(--bg);color:var(--t1);-webkit-font-smoothing:antialiased}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-thumb{background:var(--br);border-radius:99px}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,.08),transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(20,184,166,.06),transparent 60%)}
.login-card{width:100%;max-width:440px;background:var(--sf);border:1px solid var(--br);border-radius:18px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.5)}
.lc-header{padding:36px 36px 28px;text-align:center;border-bottom:1px solid var(--bs)}
.lc-logo{font-size:36px;margin-bottom:12px}
.lc-title{font-size:22px;font-weight:700;letter-spacing:-.02em;margin-bottom:4px}
.lc-sub{font-size:13.5px;color:var(--t2)}
.lc-body{padding:32px 36px}
.fg{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.fl{font-size:11.5px;font-weight:600;color:var(--t2);text-transform:uppercase;letter-spacing:.06em}
.fi{background:var(--el);border:1px solid var(--br);border-radius:10px;padding:12px 16px;font-size:14px;color:var(--t1);font-family:var(--fm);outline:none;width:100%;transition:border-color .18s,box-shadow .18s}
.fi:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.fi::placeholder{color:var(--t3)}
.fi-mono{font-family:var(--mo);letter-spacing:.06em;font-size:13px}
.btn-login{width:100%;padding:14px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;font-family:var(--fm);cursor:pointer;transition:all .2s;box-shadow:0 4px 16px rgba(59,130,246,.35);margin-top:8px;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-login:hover{background:#2563eb;transform:translateY(-1px)}
.btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none}
.err{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:#fb7185;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none}
.err.on{display:block}
.lc-footer{padding:16px 36px;text-align:center;font-size:12px;color:var(--t3);border-top:1px solid var(--bs)}
.helper{font-size:12px;color:var(--t3);margin-top:5px;line-height:1.5}
.spin{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:sp .7s linear infinite}
.spin2{width:28px;height:28px;border:3px solid var(--br);border-top-color:var(--blue);border-radius:50%;animation:sp .7s linear infinite}
@keyframes sp{to{transform:rotate(360deg)}}
.portal{display:none;min-height:100vh;flex-direction:column}
.portal.on{display:flex}
.pbar{background:var(--sf);border-bottom:1px solid var(--br);padding:0 28px;height:60px;display:flex;align-items:center;gap:16px}
.pbar-logo{font-size:18px;font-weight:800;letter-spacing:-.02em;display:flex;align-items:center;gap:10px}
.pbar-user{margin-left:auto;display:flex;align-items:center;gap:12px;font-size:13px;color:var(--t2)}
.pbar-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:grid;place-items:center;font-size:13px;font-weight:700;color:#fff}
.psair{padding:6px 12px;border:1px solid var(--br);border-radius:7px;background:none;color:var(--t2);cursor:pointer;font-family:var(--fm);font-size:12px;transition:all .15s}
.psair:hover{color:var(--rose);border-color:rgba(244,63,94,.3)}
.ptabs{background:var(--sf);border-bottom:1px solid var(--br);padding:0 28px;display:flex;gap:0}
.ptab{padding:14px 20px;font-size:13.5px;font-weight:500;color:var(--t2);border-bottom:2px solid transparent;cursor:pointer;transition:all .18s;border-top:none;border-left:none;border-right:none;background:none;font-family:var(--fm)}
.ptab:hover{color:var(--t1)}.ptab.on{color:var(--blue);border-bottom-color:var(--blue)}
.pcont{flex:1;padding:28px;max-width:1100px;width:100%;margin:0 auto}
.pview{display:none}.pview.on{display:block;animation:fup .3s ease both}
@keyframes fup{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.kgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
.kcard{background:var(--sf);border:1px solid var(--br);border-radius:14px;padding:22px 24px}
.kico{font-size:24px;margin-bottom:12px}.kval{font-size:26px;font-weight:700;letter-spacing:-.04em}.klbl{font-size:12.5px;color:var(--t2);margin-top:4px}
.card{background:var(--sf);border:1px solid var(--br);border-radius:14px;overflow:hidden;margin-bottom:20px}
.ch{padding:16px 22px;border-bottom:1px solid var(--bs);display:flex;align-items:center;justify-content:space-between}
.cht{font-size:14px;font-weight:600}
.cb{padding:20px 22px}
table{width:100%;border-collapse:collapse}
th{padding:10px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);text-align:left;border-bottom:1px solid var(--br)}
td{padding:13px 16px;font-size:13px;color:var(--t2);border-bottom:1px solid var(--bs);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02);color:var(--t1)}
.tdp{color:var(--t1)!important;font-weight:500}.tdm{font-family:var(--mo);font-size:12px;color:var(--teal)!important}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600}
.badge::before{content:'●';font-size:7px}
.bb{background:rgba(59,130,246,.12);color:#60a5fa}.bg{background:rgba(16,185,129,.12);color:#34d399}
.ba{background:rgba(245,158,11,.12);color:#fbbf24}.bv{background:rgba(139,92,246,.12);color:#a78bfa}
.bgr{background:rgba(107,114,128,.15);color:#9ca3af}
.cd{font-family:var(--mo);font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px}
.cdr{background:rgba(244,63,94,.15);color:var(--rose)}.cda{background:rgba(245,158,11,.15);color:var(--amber)}.cdg{background:rgba(16,185,129,.1);color:var(--emerald)}
.spinw{display:flex;align-items:center;justify-content:center;padding:48px;gap:12px;color:var(--t3)}
.msg-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid var(--bs)}.msg-item:last-child{border-bottom:none}
.msg-av{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--teal));display:grid;place-items:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.msg-av.cli{background:linear-gradient(135deg,var(--violet),var(--rose))}
.msg-author{font-size:12.5px;font-weight:600;color:var(--t1)}.msg-time{font-size:11px;color:var(--t3);margin-left:8px}
.msg-text{font-size:13px;color:var(--t2);margin-top:4px;line-height:1.5}
.msg-input-wrap{display:flex;gap:10px;margin-top:16px;padding-top:16px;border-top:1px solid var(--bs)}
.msg-input{flex:1;background:var(--el);border:1px solid var(--br);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--t1);font-family:var(--fm);outline:none;resize:none;min-height:42px;transition:border-color .18s}
.msg-input:focus{border-color:var(--blue)}
.msg-send{padding:10px 18px;background:var(--blue);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:var(--fm)}
.msg-send:hover{background:#2563eb}
@media(max-width:680px){.kgrid{grid-template-columns:1fr 1fr}.ptab{padding:12px 12px;font-size:12px}}
</style>
</head>
<body>

<!-- LOGIN -->
<div class="login-wrap" id="loginScreen">
  <div class="login-card">
    <div class="lc-header">
      <div class="lc-logo">⚖</div>
      <div class="lc-title"><?= $_nome ?></div>
      <div class="lc-sub">Portal do Cliente — Acesso exclusivo</div>
    </div>
    <div class="lc-body">
      <div class="err" id="loginErr">❌ <span id="loginErrMsg"></span></div>
      <div class="fg">
        <label class="fl">Seu CPF</label>
        <input class="fi" type="text" id="cpfInput" placeholder="000.000.000-00"
               maxlength="14" oninput="maskCpf(this)" autocomplete="off">
        <span class="helper">Digite apenas os números do seu CPF</span>
      </div>
      <div class="fg">
        <label class="fl">Token de Acesso</label>
        <input class="fi fi-mono" type="text" id="tokenInput"
               placeholder="Token enviado pelo seu advogado"
               autocomplete="one-time-code">
        <span class="helper">Verifique seu e-mail ou WhatsApp para o token de acesso enviado pelo escritório</span>
      </div>
      <button class="btn-login" id="btnLogin" onclick="doLogin()">
        <span id="btnLoginTxt">Entrar no Portal</span>
      </button>
    </div>
    <div class="lc-footer">Acesso seguro e exclusivo · <?= $_nome ?></div>
  </div>
</div>

<!-- PORTAL -->
<div class="portal" id="portalScreen">
  <nav class="pbar">
    <div class="pbar-logo"><span>⚖</span><?= $_nome ?></div>
    <div class="pbar-user">
      <div class="pbar-av" id="pAv">?</div>
      <div>
        <div style="font-size:13px;font-weight:600;color:var(--t1)" id="pNome">…</div>
        <div style="font-size:11px;color:var(--t3)">Portal do Cliente</div>
      </div>
      <button class="psair" onclick="sair()">Sair</button>
    </div>
  </nav>
  <nav class="ptabs">
    <button class="ptab on" onclick="tab('home',this)">🏠 Início</button>
    <button class="ptab" onclick="tab('processos',this)">⚖ Processos</button>
    <button class="ptab" onclick="tab('documentos',this)">📄 Documentos</button>
    <button class="ptab" onclick="tab('prazos',this)">📅 Prazos</button>
    <button class="ptab" onclick="tab('mensagens',this)">💬 Mensagens</button>
  </nav>
  <div class="pcont">
    <div class="pview on" id="pview-home">
      <div style="font-size:22px;font-weight:700;margin-bottom:20px" id="bemVindo">Bem‑vindo(a)!</div>
      <div class="kgrid" id="homeKpis"><div class="spinw" style="grid-column:span 3"><div class="spin2"></div></div></div>
      <div class="card"><div class="ch"><div class="cht">⚖ Meus Processos</div></div><div id="homeProc"><div class="spinw"><div class="spin2"></div></div></div></div>
    </div>
    <div class="pview" id="pview-processos">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">⚖ Meus Processos</div>
      <div class="card"><div id="tProc"><div class="spinw"><div class="spin2"></div></div></div></div>
    </div>
    <div class="pview" id="pview-documentos">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">📄 Meus Documentos</div>
      <div class="card"><div id="tDocs"><div class="spinw"><div class="spin2"></div></div></div></div>
    </div>
    <div class="pview" id="pview-prazos">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">📅 Prazos</div>
      <div class="card"><div id="tPrazos"><div class="spinw"><div class="spin2"></div></div></div></div>
    </div>
    <div class="pview" id="pview-mensagens">
      <div style="font-size:20px;font-weight:700;margin-bottom:18px">💬 Mensagens com o Escritório</div>
      <div class="card"><div class="cb">
        <div id="msgList" style="max-height:400px;overflow-y:auto"><div class="spinw"><div class="spin2"></div></div></div>
        <div class="msg-input-wrap">
          <textarea class="msg-input" id="msgTxt" placeholder="Escreva sua mensagem…" rows="1"
                    oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
          <button class="msg-send" onclick="enviarMsg()">Enviar →</button>
        </div>
      </div></div>
    </div>
  </div>
</div>

<script>
const AB='<?= $_appUrl ?>';
let PTOK=localStorage.getItem('portal_token');
let pUser=JSON.parse(localStorage.getItem('portal_user')||'{}');

function maskCpf(el){let v=el.value.replace(/\D/g,'').substring(0,11);v=v.replace(/(\d{3})(\d)/,'$1.$2');v=v.replace(/(\d{3})(\d)/,'$1.$2');v=v.replace(/(\d{3})(\d{1,2})$/,'$1-$2');el.value=v;}

async function doLogin(){
  const cpf=document.getElementById('cpfInput').value;
  const token=document.getElementById('tokenInput').value.trim();
  const errEl=document.getElementById('loginErr');
  const btn=document.getElementById('btnLogin');
  const txt=document.getElementById('btnLoginTxt');
  errEl.classList.remove('on');
  if(!cpf||!token){errEl.classList.add('on');document.getElementById('loginErrMsg').textContent='Preencha o CPF e o token de acesso.';return;}
  btn.disabled=true;txt.innerHTML='<div class="spin"></div> Verificando…';
  try{
    const r=await fetch(AB+'/api/portal/auth/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({cpf:cpf.replace(/\D/g,''),token})});
    const d=await r.json();
    if(!r.ok||!d.data?.token){
      errEl.classList.add('on');
      document.getElementById('loginErrMsg').textContent=d.message||'CPF ou token inválido. Verifique os dados e tente novamente.';
      btn.disabled=false;txt.textContent='Entrar no Portal';return;
    }
    PTOK=d.data.token;pUser={nome:d.data.nome};
    localStorage.setItem('portal_token',PTOK);localStorage.setItem('portal_user',JSON.stringify(pUser));
    mostrarPortal();
  }catch(e){
    errEl.classList.add('on');document.getElementById('loginErrMsg').textContent='Erro de conexão. Tente novamente.';
    btn.disabled=false;txt.textContent='Entrar no Portal';
  }
}

document.addEventListener('keydown',e=>{if(e.key==='Enter'&&!document.getElementById('portalScreen').classList.contains('on'))doLogin();});

function mostrarPortal(){
  document.getElementById('loginScreen').style.display='none';
  document.getElementById('portalScreen').classList.add('on');
  const nome=pUser.nome||'Cliente';
  document.getElementById('pNome').textContent=nome;
  document.getElementById('pAv').textContent=nome.charAt(0).toUpperCase();
  document.getElementById('bemVindo').textContent='Bem\u2011vindo(a), '+nome.split(' ')[0]+'!';
  carregarHome();
}

function sair(){localStorage.removeItem('portal_token');localStorage.removeItem('portal_user');window.location.reload();}

async function papi(path,opts={}){
  opts.headers=Object.assign({'Authorization':'Bearer '+PTOK,'Content-Type':'application/json'},opts.headers||{});
  try{const r=await fetch(AB+'/api/portal'+path,opts);if(r.status===401){sair();return{};}return await r.json();}catch{return{};}
}

function tab(v,btn){
  document.querySelectorAll('.pview').forEach(x=>x.classList.remove('on'));
  document.querySelectorAll('.ptab').forEach(x=>x.classList.remove('on'));
  document.getElementById('pview-'+v)?.classList.add('on');btn.classList.add('on');
  if(v==='processos')carregarProcessos();
  if(v==='documentos')carregarDocumentos();
  if(v==='prazos')carregarPrazos();
  if(v==='mensagens')carregarMensagens();
}

const fD=s=>s?new Date(s.includes('T')?s:s+'T12:00').toLocaleDateString('pt-BR'):'—';
const fDT=s=>s?new Date(s).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}):'—';
function cdP(s){if(!s)return'—';const d=Math.ceil((new Date(s.includes('T')?s:s+'T12:00')-Date.now())/86400000);return d<=0?'<span class="cd cdr">Vencido</span>':d<=7?`<span class="cd cdr">${d}d</span>`:d<=30?`<span class="cd cda">${d}d</span>`:`<span class="cd cdg">${d}d</span>`;}
function bSt(s){const m={ativo:'bb',encerrado:'bg',recurso:'ba',execucao:'bb',arquivado:'bgr',aguardando_decisao:'ba'};return`<span class="badge ${m[s]||'bgr'}">${s||'—'}</span>`;}
function em(t,i='📭'){return`<div style="text-align:center;padding:40px;color:var(--t3)"><div style="font-size:32px;opacity:.4;margin-bottom:8px">${i}</div><p style="font-size:13px">${t}</p></div>`;}

async function carregarHome(){
  const[proc,prazos]=await Promise.all([papi('/processos'),papi('/prazos')]);
  const ps=proc.data||[],prz=prazos.data||[];
  const urg=prz.filter(p=>{if(!p.data_prazo&&!p.prazo_fatal)return false;const d=Math.ceil((new Date((p.data_prazo||p.prazo_fatal)+'T12:00')-Date.now())/86400000);return d<=30&&d>=0;}).length;
  document.getElementById('homeKpis').innerHTML=[
    {i:'⚖',v:ps.length,l:'Processos Ativos'},
    {i:'📅',v:urg,l:'Prazos próximos (30d)'},
    {i:'📋',v:prz.length,l:'Total de prazos'},
  ].map(x=>`<div class="kcard"><div class="kico">${x.i}</div><div class="kval">${x.v}</div><div class="klbl">${x.l}</div></div>`).join('');
  document.getElementById('homeProc').innerHTML=ps.length
    ?`<table><thead><tr><th>Processo</th><th>Status</th><th>Responsável</th><th>Prazo Fatal</th></tr></thead><tbody>${ps.slice(0,5).map(p=>`<tr><td><div class="tdp">${(p.titulo||'').substring(0,40)}</div><div class="tdm">${p.numero_interno||''}</div></td><td>${bSt(p.status)}</td><td>${p.responsavel_nome||'—'}</td><td>${cdP(p.prazo_fatal)}</td></tr>`).join('')}</tbody></table>`
    :em('Nenhum processo encontrado.','⚖');
}

async function carregarProcessos(){
  const d=await papi('/processos');const ps=d.data||[];
  document.getElementById('tProc').innerHTML=ps.length
    ?`<table><thead><tr><th>Número</th><th>Título</th><th>Tipo</th><th>Tribunal</th><th>Status</th><th>Prazo Fatal</th></tr></thead><tbody>${ps.map(p=>`<tr><td class="tdm">${p.numero_interno||'—'}</td><td class="tdp">${(p.titulo||'').substring(0,45)}</td><td>${p.tipo||'—'}</td><td>${p.tribunal||p.vara||'—'}</td><td>${bSt(p.status)}</td><td>${cdP(p.prazo_fatal)}</td></tr>`).join('')}</tbody></table>`
    :em('Nenhum processo encontrado.','⚖');
}

async function carregarDocumentos(){
  const d=await papi('/documentos');const docs=d.data||[];
  document.getElementById('tDocs').innerHTML=docs.length
    ?`<table><thead><tr><th>Documento</th><th>Categoria</th><th>Processo</th><th>Data</th><th></th></tr></thead><tbody>${docs.map(f=>`<tr><td class="tdp">📎 ${f.nome_original||f.nome||'—'}</td><td><span class="badge bb">${f.categoria||'—'}</span></td><td class="tdm">${f.numero_interno||'—'}</td><td>${fD(f.created_at)}</td><td><a href="${AB}/api/ged/download/${f.id}?token=${PTOK}" target="_blank" style="color:var(--blue);font-size:13px;font-weight:600;text-decoration:none">⬇ Baixar</a></td></tr>`).join('')}</tbody></table>`
    :em('Nenhum documento disponível.','📄');
}

async function carregarPrazos(){
  const d=await papi('/prazos');const ps=d.data||[];
  document.getElementById('tPrazos').innerHTML=ps.length
    ?`<table><thead><tr><th>Descrição</th><th>Processo</th><th>Data</th><th>Urgência</th></tr></thead><tbody>${ps.map(p=>`<tr><td class="tdp">${p.descricao||p.titulo||'—'}</td><td class="tdm">${p.numero_interno||'—'}</td><td>${fD(p.data_prazo||p.prazo_fatal)}</td><td>${cdP(p.data_prazo||p.prazo_fatal)}</td></tr>`).join('')}</tbody></table>`
    :em('Nenhum prazo registrado.','📅');
}

async function carregarMensagens(){
  const d=await papi('/mensagens');const msgs=d.data||[];
  const ul=document.getElementById('msgList');
  ul.innerHTML=msgs.length
    ?msgs.map(m=>`<div class="msg-item"><div class="msg-av ${m.origem==='cliente'?'cli':''}">${(m.autor||'?').charAt(0)}</div><div><span class="msg-author">${m.autor||'—'}</span><span class="msg-time">${fDT(m.created_at)}</span><div class="msg-text">${m.mensagem||''}</div></div></div>`).join('')
    :'<div style="padding:20px;text-align:center;color:var(--t3);font-size:13px">Nenhuma mensagem ainda.</div>';
  ul.scrollTop=ul.scrollHeight;
}

async function enviarMsg(){
  const txt=document.getElementById('msgTxt').value.trim();
  if(!txt)return;
  document.getElementById('msgTxt').value='';
  document.getElementById('msgTxt').style.height='auto';
  await papi('/mensagens',{method:'POST',body:JSON.stringify({mensagem:txt})});
  carregarMensagens();
}

// Auto-login se já tiver token
if(PTOK&&pUser.nome)mostrarPortal();
</script>
</body>
</html>
