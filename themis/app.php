<?php
declare(strict_types=1);
define('THEMIS_ROOT', __DIR__);
$_cfg    = file_exists(THEMIS_ROOT . '/_app/config/app.php') ? require THEMIS_ROOT . '/_app/config/app.php' : [];
$_appUrl = rtrim($_cfg['app']['url'] ?? '', '/');
$_nome   = htmlspecialchars($_cfg['app']['name'] ?? 'Themis Enterprise');
$_km     = (float)($_cfg['despesas']['valor_km_padrao'] ?? 0.90);
?><!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $_nome ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.5/tinymce.min.js" referrerpolicy="origin"></script>
<style>
:root{--bg:#0f1117;--sf:#161b27;--el:#1e2535;--hv:#242d40;--br:#2a3449;--bs:#1e2840;--t1:#e8edf5;--t2:#8b95a9;--t3:#4f5b72;--blue:#3b82f6;--bd:#1e3a5f;--bg2:rgba(59,130,246,.15);--teal:#14b8a6;--amber:#f59e0b;--rose:#f43f5e;--emerald:#10b981;--violet:#8b5cf6;--fm:'DM Sans',sans-serif;--mo:'JetBrains Mono',monospace}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:var(--fm);background:var(--bg);color:var(--t1);-webkit-font-smoothing:antialiased;overflow:hidden}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--br);border-radius:99px}
.shell{display:grid;grid-template-columns:260px 1fr;grid-template-rows:60px 1fr;height:100vh}
.sidebar{grid-row:1/-1;background:var(--sf);border-right:1px solid var(--br);display:flex;flex-direction:column;overflow-y:auto;scrollbar-width:none}
.sidebar::-webkit-scrollbar{display:none}
.slogo{padding:20px 16px;border-bottom:1px solid var(--bs);display:flex;align-items:center;gap:12px;flex-shrink:0}
.slogo img{height:32px;width:auto}
.smark{width:38px;height:38px;background:linear-gradient(135deg,var(--blue),var(--teal));border-radius:10px;display:none;place-items:center;font-size:16px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 4px 16px rgba(59,130,246,.3)}
.stxt strong{font-size:13px;font-weight:700;display:block}.stxt small{font-size:10.5px;color:var(--t3)}
.ssec{padding:14px 12px 4px}.sslbl{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--t3);padding:0 6px;margin-bottom:5px}
.nav{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;cursor:pointer;text-decoration:none;color:var(--t2);font-size:13px;font-weight:500;transition:all .18s;position:relative;border:none;background:none;width:100%;font-family:var(--fm);user-select:none}
.nav:hover{color:var(--t1);background:var(--el)}
.nav.on{color:var(--blue);background:var(--bd)}
.nav.on::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--blue);border-radius:0 3px 3px 0}
.ni{font-size:15px;flex-shrink:0;width:22px;text-align:center}
.nbg{margin-left:auto;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:99px;min-width:18px;text-align:center}
.nbr{background:var(--rose)}.nbb{background:var(--blue)}.nba{background:var(--amber);color:#000}
.sfoot{margin-top:auto;padding:14px 12px;border-top:1px solid var(--bs);flex-shrink:0}
.suser{display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;background:var(--el)}
.sav{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--violet),var(--blue));display:grid;place-items:center;font-size:13px;font-weight:700;color:#fff;flex-shrink:0}
.ssair{margin-left:auto;font-size:11px;cursor:pointer;padding:4px 8px;border-radius:6px;border:1px solid var(--br);background:none;color:var(--t2);font-family:var(--fm);transition:all .15s}
.ssair:hover{color:var(--rose);border-color:rgba(244,63,94,.3)}
.topbar{background:var(--sf);border-bottom:1px solid var(--br);padding:0 28px;display:flex;align-items:center;gap:14px;z-index:100}
.ttl{font-size:16px;font-weight:600}.tbc{font-size:12px;color:var(--t3);margin-left:4px}
.tsearch{margin-left:auto;display:flex;align-items:center;gap:8px;background:var(--el);border:1px solid var(--br);border-radius:8px;padding:7px 14px;min-width:260px;transition:border-color .18s}
.tsearch:focus-within{border-color:var(--blue);box-shadow:0 0 0 3px var(--bg2)}
.tsearch input{background:none;border:none;outline:none;font-size:13px;color:var(--t1);font-family:var(--fm);width:100%}
.tsearch input::placeholder{color:var(--t3)}
.tact{display:flex;align-items:center;gap:8px}
.ibtn{width:36px;height:36px;background:var(--el);border:1px solid var(--br);border-radius:8px;display:grid;place-items:center;cursor:pointer;color:var(--t2);font-size:16px;transition:all .18s;position:relative}
.ibtn:hover{color:var(--t1);border-color:var(--blue)}
.ndot{position:absolute;top:5px;right:5px;width:7px;height:7px;background:var(--rose);border-radius:50%;border:1.5px solid var(--sf);display:none}
.np{position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--sf);border:1px solid var(--br);border-radius:12px;box-shadow:0 16px 48px rgba(0,0,0,.5);z-index:500;display:none}
.np.on{display:block}
.nr{display:flex;gap:10px;padding:12px 16px;border-bottom:1px solid var(--bs);cursor:pointer;transition:background .15s}
.nr:hover{background:var(--el)}.nr:last-child{border-bottom:none}
.views{overflow-y:auto;padding:26px 28px}
.view{display:none;animation:fup .3s ease both}.view.on{display:block}
@keyframes fup{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.kgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.kpi{background:var(--sf);border:1px solid var(--br);border-radius:14px;padding:20px 22px;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;cursor:default}
.kpi:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(0,0,0,.3)}
.kpi::before{content:'';position:absolute;inset:0;opacity:0}
.kpi.kbl::before{background:radial-gradient(circle at 90% 10%,var(--bg2),transparent 60%);opacity:1}
.kpi.ktl::before{background:radial-gradient(circle at 90% 10%,rgba(20,184,166,.12),transparent 60%);opacity:1}
.kpi.kam::before{background:radial-gradient(circle at 90% 10%,rgba(245,158,11,.1),transparent 60%);opacity:1}
.kpi.krs::before{background:radial-gradient(circle at 90% 10%,rgba(244,63,94,.1),transparent 60%);opacity:1}
.kico{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:18px;margin-bottom:14px;position:relative}
.kico.bl{background:rgba(59,130,246,.15);color:var(--blue)}.kico.tl{background:rgba(20,184,166,.15);color:var(--teal)}
.kico.am{background:rgba(245,158,11,.15);color:var(--amber)}.kico.rs{background:rgba(244,63,94,.15);color:var(--rose)}
.kico.vi{background:rgba(139,92,246,.15);color:var(--violet)}
.kval{font-size:28px;font-weight:700;letter-spacing:-.04em;line-height:1;margin-bottom:4px;position:relative}
.klbl{font-size:12.5px;color:var(--t2);font-weight:500;position:relative}
.kchg{margin-top:10px;display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;position:relative}
.up{color:var(--emerald)}.dn{color:var(--rose)}.kchg span{color:var(--t3);font-weight:400}
.card{background:var(--sf);border:1px solid var(--br);border-radius:14px;overflow:hidden;margin-bottom:20px}
.ch{padding:16px 22px;border-bottom:1px solid var(--bs);display:flex;align-items:center;justify-content:space-between;gap:12px}
.cht{font-size:14px;font-weight:600;color:var(--t1);display:flex;align-items:center;gap:8px}
.chico{width:28px;height:28px;border-radius:7px;display:grid;place-items:center;font-size:13px;flex-shrink:0}
.cb{padding:20px 22px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.g21{display:grid;grid-template-columns:2fr 1fr;gap:20px}
.g12{display:grid;grid-template-columns:1fr 2fr;gap:20px}
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead tr{border-bottom:1px solid var(--br)}
th{padding:11px 16px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);text-align:left;white-space:nowrap}
td{padding:13px 16px;font-size:13px;color:var(--t2);border-bottom:1px solid var(--bs);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02);color:var(--t1)}
.tdp{color:var(--t1)!important;font-weight:500}.tdm{font-family:var(--mo);font-size:12px;color:var(--teal)!important}
.tds{font-size:11.5px;color:var(--t3)!important;margin-top:2px;display:block}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;white-space:nowrap}
.badge::before{content:'●';font-size:7px}
.bb{background:rgba(59,130,246,.12);color:#60a5fa}.bg{background:rgba(16,185,129,.12);color:#34d399}
.ba{background:rgba(245,158,11,.12);color:#fbbf24}.br{background:rgba(244,63,94,.12);color:#fb7185}
.bv{background:rgba(139,92,246,.12);color:#a78bfa}.bt{background:rgba(20,184,166,.12);color:#2dd4bf}
.bgr{background:rgba(107,114,128,.15);color:#9ca3af}
.cd{font-family:var(--mo);font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px}
.cdr{background:rgba(244,63,94,.15);color:var(--rose)}.cda{background:rgba(245,158,11,.15);color:var(--amber)}
.cdg{background:rgba(16,185,129,.1);color:var(--emerald)}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;font-size:13.5px;font-weight:600;font-family:var(--fm);cursor:pointer;border:none;transition:all .18s;white-space:nowrap;user-select:none}
.bp{background:var(--blue);color:#fff;box-shadow:0 2px 12px rgba(59,130,246,.35)}.bp:hover{background:#2563eb;transform:translateY(-1px)}
.bg2{background:var(--el);color:var(--t2);border:1px solid var(--br)}.bg2:hover{color:var(--t1)}
.bd{background:rgba(244,63,94,.12);color:var(--rose);border:1px solid rgba(244,63,94,.25)}.bd:hover{background:rgba(244,63,94,.2)}
.bs2{background:rgba(16,185,129,.12);color:var(--emerald);border:1px solid rgba(16,185,129,.25)}.bs2:hover{background:rgba(16,185,129,.22)}
.bsm{padding:6px 12px;font-size:12px}.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.mover{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.65);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .25s}
.mover.on{opacity:1;pointer-events:all}
.modal{background:var(--sf);border:1px solid var(--br);border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;transform:translateY(20px) scale(.97);transition:transform .25s ease}
.mover.on .modal{transform:none}.mlg{max-width:720px}
.mh{padding:20px 24px;border-bottom:1px solid var(--bs);display:flex;align-items:center;justify-content:space-between}
.mht{font-size:15px;font-weight:700}
.mx{width:28px;height:28px;border-radius:6px;background:var(--el);border:1px solid var(--br);cursor:pointer;display:grid;place-items:center;font-size:14px;color:var(--t2);transition:all .15s}
.mx:hover{color:var(--t1);background:var(--hv)}
.mb{padding:22px;display:flex;flex-direction:column;gap:14px}.mf{padding:14px 22px;border-top:1px solid var(--bs);display:flex;gap:10px;justify-content:flex-end}
.fg{display:flex;flex-direction:column;gap:6px}.fl{font-size:12px;font-weight:600;color:var(--t2);text-transform:uppercase;letter-spacing:.04em}
.fi{background:var(--el);border:1px solid var(--br);border-radius:8px;padding:9px 13px;font-size:13px;color:var(--t1);font-family:var(--fm);outline:none;transition:border-color .18s,box-shadow .18s;width:100%}
.fi:focus{border-color:var(--blue);box-shadow:0 0 0 3px var(--bg2)}.fi::placeholder{color:var(--t3)}
.fsel{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b95a9' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer}.fsel option{background:var(--el)}
.ftxt{resize:vertical;min-height:80px}
.tabs{display:flex;gap:2px;background:var(--el);border-radius:10px;padding:3px}
.tab{padding:7px 16px;border-radius:7px;font-size:13px;font-weight:500;font-family:var(--fm);cursor:pointer;background:none;border:none;color:var(--t2);transition:all .18s}
.tab.on{background:var(--sf);color:var(--t1);box-shadow:0 1px 4px rgba(0,0,0,.3)}.tab:hover:not(.on){color:var(--t1)}
.prog{height:6px;background:var(--el);border-radius:99px;overflow:hidden}.progf{height:100%;border-radius:99px;transition:width .6s ease}
.tli{display:flex;gap:14px;padding:12px 0;position:relative}
.tli:not(:last-child)::after{content:'';position:absolute;left:18px;top:42px;bottom:0;width:1px;background:var(--bs)}
.tld{width:36px;height:36px;border-radius:50%;background:var(--el);border:2px solid var(--br);display:grid;place-items:center;font-size:14px;flex-shrink:0}
.tlc{flex:1}.tlt{font-size:13px;font-weight:600;color:var(--t1);margin-bottom:2px}.tlm{font-size:11.5px;color:var(--t3)}
.agi{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bs)}
.agi:last-child{border-bottom:none}
.agt{font-family:var(--mo);font-size:12px;color:var(--t3);width:38px;flex-shrink:0}
.agb{width:3px;height:36px;border-radius:99px;flex-shrink:0}
.agc{flex:1}.agtit{font-size:13px;font-weight:600;color:var(--t1)}.agsub{font-size:11.5px;color:var(--t3);margin-top:1px}
.prii{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bs)}.prii:last-child{border-bottom:none}
.prd{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.prc{background:var(--rose);box-shadow:0 0 6px var(--rose)}.pra{background:var(--amber)}.prm{background:var(--blue)}.prb{background:var(--t3)}
.chki{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--bs)}.chki:last-child{border-bottom:none}
.chkb{width:18px;height:18px;border-radius:5px;border:2px solid var(--br);flex-shrink:0;cursor:pointer;display:grid;place-items:center;transition:all .15s;margin-top:1px}
.chkb:hover{border-color:var(--blue)}.chkb.on{background:var(--blue);border-color:var(--blue);color:#fff}.chkb.on::after{content:'✓';font-size:11px;font-weight:700}
.chkbd{flex:1}.chkt{font-size:13px;font-weight:600;color:var(--t1)}.chkd{font-size:11.5px;color:var(--t3);margin-top:2px}
.abanner{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:8px;font-size:13px;border:1px solid;margin-bottom:10px}
.abw{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.2);color:#fbbf24}
.abd{background:rgba(244,63,94,.08);border-color:rgba(244,63,94,.2);color:#fb7185}
.abs{background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);color:#34d399}
.abb{background:rgba(59,130,246,.08);border-color:rgba(59,130,246,.2);color:#60a5fa}
.abmsg{flex:1}.abmsg strong{display:block;font-weight:600;margin-bottom:1px}.abmsg small{opacity:.75;font-size:12px}
.pag{display:flex;align-items:center;gap:8px;padding:14px 22px;border-top:1px solid var(--bs);font-size:12.5px;color:var(--t3)}
.pagb{padding:5px 10px;border-radius:6px;background:var(--el);border:1px solid var(--br);color:var(--t2);cursor:pointer;font-family:var(--fm);font-size:12px;transition:all .15s}
.pagb:hover{color:var(--t1)}.pagb:disabled{opacity:.4;cursor:not-allowed}.pagi{margin:0 auto}
.cmem td,.cmem th{padding:7px 12px;font-family:var(--mo);font-size:12px}.numr{text-align:right}.pos{color:var(--emerald)!important}
.spinw{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px;gap:12px;color:var(--t3)}
.spin{width:28px;height:28px;border:3px solid var(--br);border-top-color:var(--blue);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.emst{display:flex;flex-direction:column;align-items:center;padding:48px;gap:10px;color:var(--t3);text-align:center}.ei{font-size:36px;opacity:.4}
.toast{position:fixed;bottom:24px;right:24px;padding:13px 18px;border-radius:10px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 32px rgba(0,0,0,.4);z-index:9999;transform:translateY(80px);opacity:0;transition:all .3s ease;max-width:380px;border:1px solid;pointer-events:none}
.toast.on{transform:none;opacity:1}
.tok{background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.3);color:var(--emerald)}
.terr{background:rgba(244,63,94,.12);border-color:rgba(244,63,94,.3);color:var(--rose)}
@media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{display:none}.kgrid{grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<div class="shell">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="slogo">
    <img src="/assets/img/themis_logo.png" alt="" onerror="this.style.display='none';document.querySelector('.smark').style.display='grid'">
    <div class="smark">⚖</div>
    <div class="stxt"><strong id="sNome">Themis</strong><small>Enterprise v2.0</small></div>
  </div>
  <div class="ssec"><div class="sslbl">Principal</div>
    <button class="nav on" onclick="go('dashboard',this)"><span class="ni">⬡</span> Dashboard BI</button>
    <button class="nav" onclick="go('processos',this)"><span class="ni">⚖</span> Processos <span class="nbg nbb" id="nb-proc">–</span></button>
    <button class="nav" onclick="go('workflow',this)"><span class="ni">🔄</span> Workflow Kanban</button>
    <button class="nav" onclick="go('agenda',this)"><span class="ni">📅</span> Agenda <span class="nbg nba" id="nb-agenda">–</span></button>
    <button class="nav" onclick="go('tarefas',this)"><span class="ni">✓</span> Tarefas <span class="nbg nbb" id="nb-tarefas">–</span></button>
  </div>
  <div class="ssec"><div class="sslbl">Jurídico</div>
    <button class="nav" onclick="go('pericias',this)"><span class="ni">🔬</span> Perícias</button>
    <button class="nav" onclick="go('calculos',this)"><span class="ni">📐</span> Cálculos SELIC/IPCA</button>
    <button class="nav" onclick="go('ibutg',this)"><span class="ni">🌡</span> IBUTG / Insalubridade</button>
    <button class="nav" onclick="go('documentos',this)"><span class="ni">📄</span> GED / Documentos</button>
    <button class="nav" onclick="go('fabrica',this)"><span class="ni">🏭</span> Fábrica de Docs</button>
    <button class="nav" onclick="go('lixeira',this)"><span class="ni">🗑</span> Lixeira GED</button>
  </div>
  <div class="ssec"><div class="sslbl">Financeiro</div>
    <button class="nav" onclick="go('financeiro',this)"><span class="ni">💰</span> Honorários</button>
    <button class="nav" onclick="go('despesas',this)"><span class="ni">🧾</span> Despesas Campo</button>
  </div>
  <div class="ssec"><div class="sslbl">CRM & Relações</div>
    <button class="nav" onclick="go('clientes',this)"><span class="ni">🧑‍💼</span> Clientes</button>
    <button class="nav" onclick="go('stakeholders',this)"><span class="ni">👥</span> Stakeholders</button>
    <button class="nav" onclick="go('crm',this)"><span class="ni">🔔</span> Alertas CRM <span class="nbg nbr" id="nb-crm">–</span></button>
  </div>
  <div class="ssec"><div class="sslbl">Sistema</div>
    <button class="nav" onclick="go('radar',this)"><span class="ni">🛰</span> DataJud Radar</button>
    <button class="nav" onclick="go('circuit',this)"><span class="ni">🔌</span> Status das APIs</button>
    <button class="nav" onclick="go('licenca',this)"><span class="ni">🎫</span> Licença</button>
    <button class="nav" onclick="go('audit',this)"><span class="ni">📜</span> Audit Log</button>
    <a class="nav" href="<?= $_appUrl ?>/settings"><span class="ni">⚙</span> Configurações</a>
  </div>
  <div class="sfoot">
    <div class="suser">
      <div class="sav" id="sAv">?</div>
      <div><div style="font-size:13px;font-weight:600;color:var(--t1)" id="sUser">…</div><div style="font-size:11px;color:var(--t3)" id="sPerfil"></div></div>
      <button class="ssair" onclick="logout()">Sair</button>
    </div>
  </div>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div><div class="ttl" id="tTtl">Dashboard BI</div><div class="tbc" id="tBc"><?= $_nome ?></div></div>
  <div class="tsearch"><span style="color:var(--t3)">🔍</span><input type="text" placeholder="Buscar processo, cliente, CNJ…" id="gSearch" oninput="gBusca(this.value)"></div>
  <div class="tact">
    <div class="tabs" style="padding:2px">
      <button class="tab on" style="padding:5px 12px;font-size:12px" onclick="setPeriodo('hoje',this)">Hoje</button>
      <button class="tab" style="padding:5px 12px;font-size:12px" onclick="setPeriodo('mes',this)">Mês</button>
      <button class="tab" style="padding:5px 12px;font-size:12px" onclick="setPeriodo('ano',this)">Ano</button>
    </div>
    <div style="position:relative">
      <div class="ibtn" onclick="togNotif()">🔔<div class="ndot" id="ndot"></div></div>
      <div class="np" id="notifPanel">
        <div class="ch" style="padding:12px 16px"><span class="cht" style="font-size:13px">🔔 Notificações</span><button class="bg2 btn bsm" onclick="markRead()">Ler todas</button></div>
        <div id="notifList"><div class="spinw" style="padding:20px"><div class="spin"></div></div></div>
      </div>
    </div>
    <div class="ibtn" onclick="abrirM('processoM')" title="Novo processo">➕</div>
  </div>
</header>

<!-- VIEWS -->
<div class="views" id="vwrap">

<!-- ═══ DASHBOARD ═══ -->
<div class="view on" id="view-dashboard">
  <div id="alertas" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px"></div>
  <div class="kgrid">
    <div class="kpi kbl"><div class="kico bl">⚖</div><div class="kval" id="kProc">–</div><div class="klbl">Processos Ativos</div><div class="kchg up" id="kProcC"></div></div>
    <div class="kpi ktl"><div class="kico tl">💰</div><div class="kval" id="kRec">–</div><div class="klbl">Receita Prevista Mês</div><div class="kchg up" id="kRecC"></div></div>
    <div class="kpi kam"><div class="kico am">⏰</div><div class="kval" id="kPrz">–</div><div class="klbl">Prazos Críticos (7d)</div><div class="kchg dn" id="kPrzC"></div></div>
    <div class="kpi krs"><div class="kico rs">📊</div><div class="kval" id="kEx">–</div><div class="klbl">Taxa de Êxito</div><div class="kchg up" id="kExC"></div></div>
  </div>
  <div class="g21">
    <div class="card">
      <div class="ch"><div class="cht"><div class="chico" style="background:rgba(59,130,246,.15);color:var(--blue)">📈</div>Rentabilidade por Tipo</div>
        <div class="tabs" style="padding:2px"><button class="tab on" style="padding:5px 10px;font-size:11px">6M</button><button class="tab" style="padding:5px 10px;font-size:11px">12M</button></div>
      </div>
      <div class="cb"><div style="height:220px"><canvas id="cRent"></canvas></div></div>
    </div>
    <div class="card">
      <div class="ch"><div class="cht"><div class="chico" style="background:rgba(139,92,246,.15);color:var(--violet)">🥧</div>Status Processual</div></div>
      <div class="cb">
        <div style="height:180px"><canvas id="cStatus"></canvas></div>
        <div id="stLeg" style="display:flex;flex-direction:column;gap:6px;margin-top:12px"></div>
      </div>
    </div>
  </div>
  <div class="g2">
    <div class="card">
      <div class="ch"><div class="cht">⚖ Processos com Prazos Urgentes</div><button class="bg2 btn bsm" onclick="go('processos',null)">Ver todos →</button></div>
      <div class="tw" id="dProc"><div class="spinw"><div class="spin"></div></div></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:20px">
      <div class="card">
        <div class="ch"><div class="cht">📅 Agenda de Hoje</div><button class="bg2 btn bsm" onclick="abrirM('eventoM')">+ Evento</button></div>
        <div class="cb" id="dAgenda"><div class="spinw" style="padding:20px"><div class="spin"></div></div></div>
      </div>
      <div class="card">
        <div class="ch"><div class="cht">👥 CRM — Alertas</div><span class="badge ba" id="crmBadge">–</span></div>
        <div class="cb" id="dCRM"><div class="spinw" style="padding:20px"><div class="spin"></div></div></div>
      </div>
    </div>
  </div>
  <div class="g12">
    <div class="card">
      <div class="ch"><div class="cht"><div class="chico" style="background:rgba(244,63,94,.15);color:var(--rose)">⚡</div>Checklist — Laudo Adverso</div><span class="badge br" id="parBadge">–</span></div>
      <div class="cb" id="dCheck"></div>
    </div>
    <div class="card">
      <div class="ch"><div class="cht">💰 BI Financeiro — Honorários</div></div>
      <div class="cb">
        <div style="height:200px"><canvas id="cHon"></canvas></div>
        <div class="g3" style="margin-top:18px" id="honSum"></div>
        <div style="margin-top:18px" id="honSoc"></div>
      </div>
    </div>
  </div>
  <div class="g2">
    <div class="card">
      <div class="ch"><div class="cht">🛰 DataJud — Movimentações</div><span class="badge bg" id="djSync">–</span></div>
      <div class="cb" id="dDatajud"><div class="spinw" style="padding:20px"><div class="spin"></div></div></div>
    </div>
    <div class="card">
      <div class="ch"><div class="cht">🧾 Despesas Campo — Pendentes</div><button class="bp btn bsm" onclick="abrirM('despesaM')">+ Registrar</button></div>
      <div class="tw" id="dDesp"><div class="spinw"><div class="spin"></div></div></div>
      <div class="pag" id="dDespTotal"></div>
    </div>
  </div>
</div>

<!-- ═══ PROCESSOS ═══ -->
<div class="view" id="view-processos">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div><div style="font-size:20px;font-weight:700">⚖ Processos</div><div style="font-size:13px;color:var(--t2);margin-top:3px">Gestão processual completa</div></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <select class="fi fsel" id="fSt" style="width:auto;padding:7px 30px 7px 10px;font-size:12.5px" onchange="loadProc()">
        <option value="">Todos os status</option><option value="ativo">Ativo</option><option value="aguardando_decisao">Aguardando Decisão</option>
        <option value="recurso">Recurso</option><option value="execucao">Execução</option><option value="proposta">Proposta</option>
        <option value="encerrado">Encerrado</option><option value="arquivado">Arquivado</option>
      </select>
      <select class="fi fsel" id="fPo" style="width:auto;padding:7px 30px 7px 10px;font-size:12.5px" onchange="loadProc()">
        <option value="">Polo</option><option value="ativo">Ativo</option><option value="passivo">Passivo</option>
      </select>
      <button class="bp btn" onclick="abrirM('processoM')">➕ Novo Processo</button>
    </div>
  </div>
  <div class="card">
    <div class="tw" id="tProc"><div class="spinw"><div class="spin"></div></div></div>
    <div class="pag" id="pagProc"></div>
  </div>
</div>

<!-- ═══ AGENDA ═══ -->
<div class="view" id="view-agenda">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">📅 Agenda</div>
    <button class="bp btn" onclick="abrirM('eventoM')">➕ Novo Evento</button>
  </div>

  <!-- Mini calendário -->
  <div class="card" style="margin-bottom:20px">
    <div class="ch">
      <div class="cht">📅 Calendário</div>
      <div style="display:flex;align-items:center;gap:8px">
        <button class="bg2 btn bsm" onclick="calNav(-1)">‹</button>
        <span id="calLabel" style="font-size:13px;font-weight:600;min-width:120px;text-align:center"></span>
        <button class="bg2 btn bsm" onclick="calNav(1)">›</button>
      </div>
    </div>
    <div class="cb" style="padding:12px 16px">
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:8px">
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Dom</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Seg</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Ter</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Qua</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Qui</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Sex</div>
        <div style="text-align:center;font-size:10.5px;font-weight:700;color:var(--t3);padding:4px">Sáb</div>
      </div>
      <div id="calGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px"></div>
    </div>
  </div>
  <div class="card"><div class="tw" id="tAgenda"><div class="spinw"><div class="spin"></div></div></div></div>
</div>

<!-- ═══ TAREFAS ═══ -->
<div class="view" id="view-tarefas">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">✓ Tarefas</div>
  </div>
  <div class="card"><div class="tw" id="tTarefas"><div class="spinw"><div class="spin"></div></div></div></div>
</div>

<!-- ═══ PERÍCIAS ═══ -->
<div class="view" id="view-pericias">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">🔬 Perícias</div>
    <button class="bp btn" onclick="abrirM('periciaM')">➕ Nova Perícia</button>
  </div>
  <div class="card"><div class="tw" id="tPericias"><div class="spinw"><div class="spin"></div></div></div></div>
  <div class="card" id="cardParecer" style="display:none">
    <div class="ch">
      <div class="cht"><div class="chico" style="background:rgba(244,63,94,.15);color:var(--rose)">⚡</div>Análise de Laudo Adverso</div>
      <div style="display:flex;gap:8px"><span class="badge br">Em análise</span><button class="bp btn bsm" onclick="abrirM('parecerM')">📋 Gerar Parecer</button></div>
    </div>
    <div class="cb" id="chkParecer"></div>
  </div>
</div>

<!-- ═══ CÁLCULOS ═══ -->
<div class="view" id="view-calculos">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">📐 Cálculos SELIC / IPCA-E</div>
    <button class="bg2 btn bsm" onclick="atualizarIndices(this)" title="Buscar índices atualizados nas APIs do BCB e IBGE">🔄 Atualizar Índices</button>
  </div>
  <div class="g2" style="align-items:flex-start">
    <div class="card">
      <div class="ch"><div class="cht">⚙ Parâmetros</div></div>
      <div class="cb" style="display:flex;flex-direction:column;gap:14px">
        <div class="fg"><label class="fl">Valor Principal (R$)</label><input class="fi" type="number" id="cVal" placeholder="10000.00" step="0.01"></div>
        <div class="fg"><label class="fl">Data de Início</label><input class="fi" type="date" id="cIni"></div>
        <div class="fg"><label class="fl">Data de Fim</label><input class="fi" type="date" id="cFim"></div>
        <div class="fg"><label class="fl">Índice de Correção</label>
          <select class="fi fsel" id="cIdx" onchange="calcular()"><option value="SELIC">SELIC (Lei 14.905/2024)</option><option value="IPCA-E">IPCA-E</option><option value="INPC">INPC</option><option value="IGP-M">IGP-M</option></select>
        </div>
        <div class="fg"><label class="fl">Juros de Mora</label>
          <select class="fi fsel" id="cJur"><option value="simples">Simples (1% a.m.)</option><option value="compostos">Compostos</option><option value="pro_rata">Pro Rata Die</option><option value="nenhum">Sem juros</option></select>
        </div>
        <div class="fg"><label class="fl">Processo (opcional)</label><input class="fi" type="text" id="cProcN" placeholder="Número interno"></div>
        <button class="bp btn" style="width:100%;justify-content:center" onclick="calcular()">🧮 Calcular Atualização</button>
        <button class="bg2 btn bsm" style="width:100%;justify-content:center" onclick="exportCalc()">📥 Exportar CSV</button>
      </div>
    </div>
    <div>
      <div class="card" id="calcRes" style="display:none">
        <div class="ch"><div class="cht">📊 Resultado</div><span class="badge bg" id="calcBadge"></span></div>
        <div class="cb">
          <div class="g3" style="margin-bottom:16px" id="calcKpis"></div>
          <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Memória de Cálculo</div>
          <div class="tw"><table class="cmem"><thead><tr><th>Competência</th><th class="numr">Saldo</th><th class="numr">Índice%</th><th class="numr">Correção</th><th class="numr">Juros</th><th class="numr">Total</th></tr></thead><tbody id="calcMem"></tbody></table></div>
        </div>
      </div>
      <div class="card">
        <div class="ch"><div class="cht">🗂 Histórico de Cálculos</div></div>
        <div class="tw" id="histCalc"><div class="spinw"><div class="spin"></div></div></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ GED ═══ -->
<div class="view" id="view-documentos">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">📄 GED — Documentos</div>
    <button class="bp btn" onclick="document.getElementById('fUpload').click()">📎 Upload</button>
    <input type="file" id="fUpload" style="display:none" multiple onchange="uploadDoc(this)">
  </div>
  <div class="card">
    <div class="ch">
      <div class="tabs">
        <button class="tab on" onclick="filtDoc('todos',this)">Todos</button>
        <button class="tab" onclick="filtDoc('peticao',this)">Petições</button>
        <button class="tab" onclick="filtDoc('laudo',this)">Laudos</button>
        <button class="tab" onclick="filtDoc('contrato',this)">Contratos</button>
        <button class="tab" onclick="filtDoc('outros',this)">Outros</button>
      </div>
    </div>
    <div class="tw" id="tDocs"><div class="spinw"><div class="spin"></div></div></div>
    <div class="pag" id="pagDocs"></div>
  </div>
</div>

<!-- ═══ FÁBRICA DE DOCUMENTOS ═══ -->
<div class="view" id="view-fabrica">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div><div style="font-size:20px;font-weight:700">🏭 Fábrica de Documentos</div><div style="font-size:13px;color:var(--t2);margin-top:3px">Crie, edite e gere documentos jurídicos com tags dinâmicas</div></div>
    <button class="bp btn" onclick="novoTemplate()">➕ Novo Template</button>
  </div>

  <!-- Layout: lista esquerda + editor direita -->
  <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:flex-start">

    <!-- Lista de templates -->
    <div>
      <div class="card" style="margin-bottom:0">
        <div class="ch" style="padding:12px 16px">
          <div class="cht" style="font-size:13px">📚 Templates</div>
          <div class="tabs" style="padding:2px">
            <button class="tab on" style="padding:4px 8px;font-size:11px" onclick="filtTpl('todos',this)">Todos</button>
            <button class="tab" style="padding:4px 8px;font-size:11px" onclick="filtTpl('peticao',this)">Petições</button>
            <button class="tab" style="padding:4px 8px;font-size:11px" onclick="filtTpl('laudo',this)">Laudos</button>
            <button class="tab" style="padding:4px 8px;font-size:11px" onclick="filtTpl('contrato',this)">Contratos</button>
          </div>
        </div>
        <div id="tplLista" style="max-height:calc(100vh - 240px);overflow-y:auto">
          <div class="spinw"><div class="spin"></div></div>
        </div>
      </div>
    </div>

    <!-- Editor -->
    <div id="editorArea">
      <!-- Estado inicial: instrução -->
      <div class="card" id="editorVazio" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:400px;color:var(--t3)">
        <div style="font-size:48px;opacity:.3">📝</div>
        <p style="font-size:14px;margin-top:12px">Selecione um template para editar</p>
        <p style="font-size:12px;margin-top:6px">ou clique em <strong style="color:var(--blue)">➕ Novo Template</strong></p>
      </div>

      <!-- Editor ativo -->
      <div id="editorAtivo" style="display:none">
        <!-- Meta do template -->
        <div class="card" style="margin-bottom:16px">
          <div class="cb" style="padding:16px 20px">
            <div class="g2" style="gap:12px;margin-bottom:12px">
              <div class="fg"><label class="fl">Nome do Template *</label><input class="fi" id="tplNome" placeholder="Ex: Petição Inicial Trabalhista"></div>
              <div class="fg"><label class="fl">Tipo</label>
                <select class="fi fsel" id="tplTipo">
                  <option value="peticao">Petição</option><option value="laudo">Laudo</option>
                  <option value="parecer">Parecer</option><option value="contrato">Contrato</option>
                  <option value="notificacao">Notificação</option><option value="relatorio">Relatório</option>
                  <option value="outro">Outro</option>
                </select>
              </div>
            </div>
            <div class="g2" style="gap:12px">
              <div class="fg"><label class="fl">Subtipo / Descrição</label><input class="fi" id="tplSub" placeholder="Ex: NR-15 Calor"></div>
              <div style="display:flex;align-items:center;gap:16px;padding-top:20px">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--t2);cursor:pointer">
                  <input type="checkbox" id="tplTimbrado" checked style="width:16px;height:16px;accent-color:var(--blue)">
                  Papel timbrado
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--t2);cursor:pointer">
                  <input type="checkbox" id="tplAtivo" checked style="width:16px;height:16px;accent-color:var(--blue)">
                  Ativo
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Tags dinâmicas -->
        <div class="card" style="margin-bottom:16px">
          <div class="ch" style="padding:12px 20px">
            <div class="cht" style="font-size:13px">🏷 Tags Dinâmicas — clique para inserir no editor</div>
            <button class="bg2 btn bsm" onclick="togTags()" id="togTagsBtn">Ver todas</button>
          </div>
          <div class="cb" style="padding:12px 20px" id="tagsArea">
            <div style="margin-bottom:10px">
              <div style="font-size:10.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Processo</div>
              <div style="display:flex;flex-wrap:wrap;gap:6px" id="tagsProcesso"></div>
            </div>
            <div style="margin-bottom:10px">
              <div style="font-size:10.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Cliente</div>
              <div style="display:flex;flex-wrap:wrap;gap:6px" id="tagsCliente"></div>
            </div>
            <div>
              <div style="font-size:10.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px">Sistema</div>
              <div style="display:flex;flex-wrap:wrap;gap:6px" id="tagsSistema"></div>
            </div>
          </div>
        </div>

        <!-- TinyMCE -->
        <div class="card" style="margin-bottom:16px">
          <div class="ch" style="padding:12px 20px">
            <div class="cht" style="font-size:13px">✍ Conteúdo do Template</div>
            <div style="display:flex;gap:8px">
              <button class="bg2 btn bsm" onclick="salvarTemplate(false)">💾 Salvar</button>
              <button class="bp btn bsm" onclick="salvarTemplate(true)">💾 Salvar e Fechar</button>
            </div>
          </div>
          <div class="cb" style="padding:16px 20px">
            <textarea id="tinyEditor" style="width:100%;min-height:500px"></textarea>
          </div>
        </div>

        <!-- Gerar documento -->
        <div class="card">
          <div class="ch" style="padding:12px 20px"><div class="cht" style="font-size:13px">🚀 Gerar Documento</div></div>
          <div class="cb" style="padding:16px 20px">
            <div class="g2" style="gap:12px;margin-bottom:12px">
              <div class="fg">
                <label class="fl">Processo (opcional)</label>
                <select class="fi fsel" id="gerarProc"><option value="">Nenhum — preencher manualmente</option></select>
              </div>
              <div class="fg">
                <label class="fl">Destino</label>
                <select class="fi fsel" id="gerarDest">
                  <option value="ged">Salvar no GED</option>
                  <option value="pdf_download">Download PDF</option>
                  <option value="assinatura">Enviar para Assinafy</option>
                </select>
              </div>
            </div>
            <div id="gerarVarsArea" style="display:none;margin-bottom:12px">
              <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Preencher variáveis manualmente</div>
              <div id="gerarVars" style="display:flex;flex-direction:column;gap:8px"></div>
            </div>
            <button class="bp btn" style="width:100%;justify-content:center" onclick="gerarDocumento()">📄 Gerar Documento</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ WORKFLOW KANBAN ═══ -->
<div class="view" id="view-workflow">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div><div style="font-size:20px;font-weight:700">🔄 Workflow — Kanban de Processos</div><div style="font-size:13px;color:var(--t2);margin-top:3px">Arraste ou clique para avançar o status do processo</div></div>
    <button class="bp btn" onclick="loadWorkflow()">⟳ Atualizar</button>
  </div>
  <!-- Kanban board com scroll horizontal -->
  <div style="overflow-x:auto;padding-bottom:12px">
    <div id="kanbanBoard" style="display:flex;gap:14px;min-width:max-content;align-items:flex-start">
      <div class="spinw" style="width:300px"><div class="spin"></div></div>
    </div>
  </div>
  <!-- Legenda de transições -->
  <div class="card" style="margin-top:20px">
    <div class="ch" style="padding:12px 20px"><div class="cht" style="font-size:13px">📋 Transições Automáticas</div></div>
    <div class="cb" style="padding:14px 20px">
      <div style="display:flex;flex-wrap:wrap;gap:8px;font-size:12.5px;color:var(--t2)">
        <span>Proposta → <span class="badge bb">Ativo</span> gera tarefas de protocolo</span>
        <span style="color:var(--t3)">·</span>
        <span>Ativo → <span class="badge ba">Aguardando Decisão</span> gera monitoramento DJE</span>
        <span style="color:var(--t3)">·</span>
        <span>Ativo → <span class="badge bt">Execução</span> gera cálculo de liquidação</span>
        <span style="color:var(--t3)">·</span>
        <span>Aguardando → <span class="badge bv">Recurso</span> gera minuta</span>
      </div>
    </div>
  </div>
</div>

<!-- ═══ IBUTG / INSALUBRIDADE ═══ -->
<div class="view" id="view-ibutg">
  <div style="font-size:20px;font-weight:700;margin-bottom:18px">🌡 IBUTG / Insalubridade NR-15</div>
  <div class="g2" style="align-items:flex-start">
    <!-- Calculadora -->
    <div>
      <div class="card" style="margin-bottom:18px">
        <div class="ch"><div class="cht">🌡 Cálculo IBUTG (Calor — NR-15 Anexo 3)</div></div>
        <div class="cb" style="display:flex;flex-direction:column;gap:14px">
          <div style="font-size:12px;color:var(--t3);padding:10px;background:var(--el);border-radius:8px;line-height:1.6">
            <strong style="color:var(--t2)">Fórmula:</strong><br>
            • Ambiente interno / sem carga solar: <code style="color:var(--teal)">IBUTG = 0,7·TBU + 0,3·TG</code><br>
            • Ambiente externo / com carga solar: <code style="color:var(--teal)">IBUTG = 0,7·TBU + 0,1·TBS + 0,2·TG</code>
          </div>
          <div class="fg">
            <label class="fl">Ambiente</label>
            <select class="fi fsel" id="ibtAmb" onchange="updIbutg()">
              <option value="interno">Interno / Sem carga solar</option>
              <option value="externo">Externo / Com carga solar</option>
            </select>
          </div>
          <div class="g3">
            <div class="fg">
              <label class="fl">TBU — Temp. Bulbo Úmido (°C)</label>
              <input class="fi" type="number" id="ibtTbu" placeholder="28.5" step="0.1" oninput="calcIbutg()">
            </div>
            <div class="fg">
              <label class="fl">TBS — Temp. Bulbo Seco (°C)</label>
              <input class="fi" type="number" id="ibtTbs" placeholder="32.0" step="0.1" oninput="calcIbutg()" id2="externo">
            </div>
            <div class="fg">
              <label class="fl">TG — Temp. Globo Negro (°C)</label>
              <input class="fi" type="number" id="ibtTg" placeholder="38.0" step="0.1" oninput="calcIbutg()">
            </div>
          </div>
          <div class="fg">
            <label class="fl">Tipo de Atividade</label>
            <select class="fi fsel" id="ibtAtiv" onchange="calcIbutg()">
              <option value="leve">Leve (sentado/em pé, trabalho manual leve) — Limite: 30°C</option>
              <option value="moderada">Moderada (caminhada, trabalho manual moderado) — Limite: 26,7°C</option>
              <option value="pesada">Pesada (trabalho intenso, carregamento) — Limite: 25°C</option>
            </select>
          </div>
          <div class="fg">
            <label class="fl">Regime de Trabalho</label>
            <select class="fi fsel" id="ibtRegime" onchange="calcIbutg()">
              <option value="continuo">Contínuo (45min trabalho / 15min descanso)</option>
              <option value="75_25">75% trabalho / 25% descanso</option>
              <option value="50_50">50% trabalho / 50% descanso</option>
              <option value="25_75">25% trabalho / 75% descanso</option>
            </select>
          </div>
          <!-- Resultado IBUTG -->
          <div id="ibtResultado" style="display:none;padding:16px;background:var(--el);border-radius:10px;border:1px solid var(--br)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
              <span style="font-size:13px;color:var(--t2)">IBUTG Calculado</span>
              <span style="font-size:28px;font-weight:700;font-family:var(--mo)" id="ibtValor">–</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px">
              <span style="color:var(--t2)">Limite NR-15</span>
              <span id="ibtLimite" style="font-weight:600"></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:12px">
              <span style="color:var(--t2)">Resultado</span>
              <span id="ibtVeredicto" style="font-size:14px;font-weight:700"></span>
            </div>
            <div class="prog"><div class="progf" id="ibtBar" style="height:8px"></div></div>
            <div id="ibtAdic" style="margin-top:12px;font-size:12.5px;color:var(--t3)"></div>
          </div>
        </div>
      </div>

      <!-- Adicional de Insalubridade -->
      <div class="card">
        <div class="ch"><div class="cht">💰 Adicional de Insalubridade (CLT Art. 192)</div></div>
        <div class="cb" style="display:flex;flex-direction:column;gap:14px">
          <div class="g2">
            <div class="fg">
              <label class="fl">Grau</label>
              <select class="fi fsel" id="insGrau" onchange="calcInsalubridade()">
                <option value="10">Mínimo — 10% do salário mínimo</option>
                <option value="20">Médio — 20% do salário mínimo</option>
                <option value="40">Máximo — 40% do salário mínimo</option>
              </select>
            </div>
            <div class="fg">
              <label class="fl">Salário Mínimo Base (R$)</label>
              <input class="fi" type="number" id="insSalMin" placeholder="1412.00" step="0.01" value="1412.00" oninput="calcInsalubridade()">
            </div>
          </div>
          <div class="fg">
            <label class="fl">Meses trabalhados em condição insalubre</label>
            <input class="fi" type="number" id="insMeses" placeholder="24" min="1" oninput="calcInsalubridade()">
          </div>
          <div id="insResultado" style="display:none;padding:16px;background:var(--el);border-radius:10px;border:1px solid var(--br)">
            <div class="g3">
              <div style="text-align:center">
                <div style="font-size:22px;font-weight:700;color:var(--blue)" id="insValMensal">–</div>
                <div style="font-size:11px;color:var(--t3);margin-top:3px">Adicional/mês</div>
              </div>
              <div style="text-align:center">
                <div style="font-size:22px;font-weight:700;color:var(--amber)" id="insValTotal">–</div>
                <div style="font-size:11px;color:var(--t3);margin-top:3px">Total período</div>
              </div>
              <div style="text-align:center">
                <div style="font-size:22px;font-weight:700;color:var(--emerald)" id="insValCorr">–</div>
                <div style="font-size:11px;color:var(--t3);margin-top:3px">Corrigido (SELIC)</div>
              </div>
            </div>
          </div>
          <button class="bp btn" style="width:100%;justify-content:center" onclick="calcInsalubridade()">🧮 Calcular Adicional</button>
          <button class="bg2 btn bsm" style="width:100%;justify-content:center" onclick="exportIbutg()">📥 Exportar Laudo CSV</button>
        </div>
      </div>
    </div>

    <!-- Tabela NR-15 + Histórico -->
    <div>
      <div class="card" style="margin-bottom:18px">
        <div class="ch"><div class="cht">📋 Tabela NR-15 — Limites de Tolerância (Calor)</div></div>
        <div class="cb" style="padding:0">
          <table>
            <thead><tr><th>Tipo de Atividade</th><th>Regime Contínuo</th><th>75%/25%</th><th>50%/50%</th><th>25%/75%</th></tr></thead>
            <tbody>
              <tr><td class="tdp">Leve</td><td><span class="badge cdg">30,0°C</span></td><td><span class="badge cdg">30,6°C</span></td><td><span class="badge cdg">31,4°C</span></td><td><span class="badge bg">32,2°C</span></td></tr>
              <tr><td class="tdp">Moderada</td><td><span class="badge cda">26,7°C</span></td><td><span class="badge cda">28,0°C</span></td><td><span class="badge cda">29,4°C</span></td><td><span class="badge ba">31,1°C</span></td></tr>
              <tr><td class="tdp">Pesada</td><td><span class="badge cdr">25,0°C</span></td><td><span class="badge cdr">25,9°C</span></td><td><span class="badge cdr">27,9°C</span></td><td><span class="badge br">30,0°C</span></td></tr>
            </tbody>
          </table>
          <div style="padding:12px 16px;font-size:12px;color:var(--t3)">Fonte: NR-15 Anexo 3 — Portaria MTb 3.214/78 atualizada</div>
        </div>
      </div>
      <div class="card">
        <div class="ch"><div class="cht">📊 Histórico de Cálculos IBUTG</div><button class="bg2 btn bsm" onclick="loadHistIbutg()">⟳</button></div>
        <div class="tw" id="histIbutg"><div class="spinw"><div class="spin"></div></div></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ LIXEIRA GED ═══ -->
<div class="view" id="view-lixeira">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div>
      <div style="font-size:20px;font-weight:700">🗑 Lixeira — GED</div>
      <div style="font-size:13px;color:var(--t2);margin-top:3px">Documentos excluídos são purgados automaticamente após 30 dias</div>
    </div>
    <button class="bp btn" onclick="loadLixeira()">⟳ Atualizar</button>
  </div>
  <div class="abanner abw" style="margin-bottom:18px">
    <span style="font-size:18px">⚠️</span>
    <div class="abmsg"><strong>Purga automática</strong><small>Documentos na lixeira há mais de 30 dias são removidos permanentemente pelo cron noturno.</small></div>
  </div>
  <div class="card">
    <div class="tw" id="tLixeira"><div class="spinw"><div class="spin"></div></div></div>
  </div>
</div>

<!-- ═══ CIRCUIT BREAKER ═══ -->
<div class="view" id="view-circuit">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div><div style="font-size:20px;font-weight:700">🔌 Status das APIs — Circuit Breaker</div><div style="font-size:13px;color:var(--t2);margin-top:3px">Monitora falhas de APIs externas e fila de retentativas</div></div>
    <button class="bp btn" onclick="loadCircuit()">⟳ Atualizar</button>
  </div>
  <div id="circuitGrid" class="g2"><div class="spinw" style="grid-column:span 2"><div class="spin"></div></div></div>
  <div class="card" style="margin-top:20px">
    <div class="ch"><div class="cht">⚙ Ações</div></div>
    <div class="cb" style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="bs2 btn" onclick="processQueue()">▶ Processar Fila de Retry</button>
    </div>
  </div>
</div>

<!-- ═══ LICENÇA ═══ -->
<div class="view" id="view-licenca">
  <div style="font-size:20px;font-weight:700;margin-bottom:18px">🎫 Licença do Sistema</div>
  <div class="g2" style="align-items:flex-start">
    <div class="card" id="licStatusCard">
      <div class="ch"><div class="cht">📊 Status Atual</div></div>
      <div class="cb" id="licInfo"><div class="spinw"><div class="spin"></div></div></div>
    </div>
    <div class="card">
      <div class="ch"><div class="cht">🔑 Instalar / Atualizar Licença</div></div>
      <div class="cb" style="display:flex;flex-direction:column;gap:14px">
        <div style="font-size:13px;color:var(--t2);line-height:1.6">Cole o token de licença recebido do emissor. O token é cifrado com AES-256-GCM e validado localmente.</div>
        <div class="fg"><label class="fl">Token de Licença</label>
          <textarea class="fi ftxt" id="licToken" rows="5" placeholder="Cole aqui o token de licença…" style="font-family:var(--mo);font-size:12px"></textarea>
        </div>
        <button class="bp btn" onclick="instalarLic()">✅ Ativar Licença</button>
        <div class="sep" style="height:1px;background:var(--bs)"></div>
        <button class="bd btn" onclick="revogarLic()">🗑 Revogar Licença (volta para trial)</button>
        <div style="font-size:12px;color:var(--t3)">
          O keygen para gerar tokens está no arquivo <code>keygen_themis.html</code> — abra-o localmente no navegador.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ AUDIT LOG ═══ -->
<div class="view" id="view-audit">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div><div style="font-size:20px;font-weight:700">📜 Audit Log</div><div style="font-size:13px;color:var(--t2);margin-top:3px">Trilha de auditoria imutável com verificação de integridade (HMAC-SHA256)</div></div>
    <div style="display:flex;gap:8px">
      <input class="fi" type="date" id="auditDate" style="width:160px" onchange="loadAudit()">
      <button class="bs2 btn" onclick="verificarAudit()">🔍 Verificar Integridade</button>
    </div>
  </div>
  <div class="card">
    <div class="ch">
      <div class="tabs">
        <button class="tab on" onclick="filtAudit('',this)">Todos</button>
        <button class="tab" onclick="filtAudit('processos',this)">Processos</button>
        <button class="tab" onclick="filtAudit('financeiro',this)">Financeiro</button>
        <button class="tab" onclick="filtAudit('configuracoes',this)">Config</button>
        <button class="tab" onclick="filtAudit('license',this)">Licença</button>
      </div>
      <span class="badge bg2" id="auditInteg" style="display:none">✅ Íntegro</span>
    </div>
    <div class="tw" id="tAudit"><div class="spinw"><div class="spin"></div></div></div>
    <div class="pag" id="pagAudit"></div>
  </div>
</div>

<!-- ═══ FINANCEIRO ═══ -->
<div class="view" id="view-financeiro">
  <div style="font-size:20px;font-weight:700;margin-bottom:18px">💰 Honorários & Financeiro</div>
  <div class="kgrid" id="finKpis" style="grid-template-columns:repeat(3,1fr)"><div class="spinw" style="grid-column:span 3"><div class="spin"></div></div></div>
  <div class="g2">
    <div class="card">
      <div class="ch"><div class="cht">📊 Previstos vs Recebidos</div></div>
      <div class="cb"><div style="height:260px"><canvas id="cFinHon"></canvas></div></div>
    </div>
    <div class="card">
      <div class="ch"><div class="cht">💳 Receitas Recentes</div><button class="bp btn bsm" onclick="abrirM('receitaM')">+ Lançar</button></div>
      <div class="tw" id="tReceitas"><div class="spinw"><div class="spin"></div></div></div>
    </div>
  </div>
  <div class="card">
    <div class="ch"><div class="cht">🏦 Alvarás Monitorados</div></div>
    <div class="tw" id="tAlvaras"><div class="spinw"><div class="spin"></div></div></div>
  </div>
</div>

<!-- ═══ DESPESAS ═══ -->
<div class="view" id="view-despesas">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">🧾 Despesas de Campo</div>
    <button class="bp btn" onclick="abrirM('despesaM')">+ Registrar Despesa</button>
  </div>
  <div class="card">
    <div class="tw" id="tDesp"><div class="spinw"><div class="spin"></div></div></div>
    <div class="pag" id="pagDesp"></div>
  </div>
</div>

<!-- ═══ CLIENTES ═══ -->
<div class="view" id="view-clientes">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">🧑‍💼 Clientes</div>
    <div style="display:flex;gap:8px">
      <input class="fi" id="cliSearch" placeholder="Buscar nome, CPF, e-mail…" oninput="loadClientes()" style="width:220px">
      <button class="bp btn" onclick="abrirModalCliente(0)">+ Novo Cliente</button>
    </div>
  </div>
  <div class="card"><div class="tw" id="cliTabela"><div class="spinw"><div class="spin"></div></div></div></div>
</div>

<!-- ═══ STAKEHOLDERS ═══ -->
<div class="view" id="view-stakeholders">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
    <div style="font-size:20px;font-weight:700">👥 Stakeholders</div>
    <div style="display:flex;gap:8px">
      <select class="fi fsel" id="fTipo" style="width:auto;padding:7px 28px 7px 10px;font-size:12.5px" onchange="loadStake()">
        <option value="">Todos</option><option value="cliente">Clientes</option><option value="advogado_adversario">Adv. Adversários</option>
        <option value="perito">Peritos</option><option value="testemunha">Testemunhas</option>
      </select>
      <button class="bp btn" onclick="abrirM('stakeM')">+ Novo</button>
    </div>
  </div>
  <div class="card">
    <div class="tw" id="tStake"><div class="spinw"><div class="spin"></div></div></div>
    <div class="pag" id="pagStake"></div>
  </div>
</div>

<!-- ═══ CRM ═══ -->
<div class="view" id="view-crm">
  <div style="font-size:20px;font-weight:700;margin-bottom:18px">🔔 CRM — Alertas de Engajamento</div>
  <div class="g2">
    <div class="card"><div class="ch"><div class="cht">⚠ Clientes sem contato (+30 dias)</div></div><div id="crmSC"><div class="spinw"><div class="spin"></div></div></div></div>
    <div class="card"><div class="ch"><div class="cht">🎂 Aniversariantes (próximos 7 dias)</div></div><div id="crmAniv"><div class="spinw"><div class="spin"></div></div></div></div>
  </div>
  <div class="card">
    <div class="ch"><div class="cht">📋 Interações Recentes</div><button class="bp btn bsm" onclick="abrirM('interM')">+ Registrar</button></div>
    <div class="tw" id="tInter"><div class="spinw"><div class="spin"></div></div></div>
  </div>
</div>

<!-- ═══ RADAR ═══ -->
<div class="view" id="view-radar">
  <div style="font-size:20px;font-weight:700;margin-bottom:18px">🛰 DataJud Radar</div>
  <div class="g2">
    <div class="card">
      <div class="ch"><div class="cht">⏱ Processos Parados (+60 dias)</div><button class="bp btn bsm" onclick="syncDJ()">⟳ Sincronizar</button></div>
      <div class="tw" id="tRadar"><div class="spinw"><div class="spin"></div></div></div>
    </div>
    <div class="card"><div class="ch"><div class="cht">🏦 Alvarás Expedidos</div></div><div class="tw" id="tAlvR"><div class="spinw"><div class="spin"></div></div></div></div>
  </div>
  <div class="card"><div class="ch"><div class="cht">📡 Movimentações DataJud</div></div><div class="tw" id="tMov"><div class="spinw"><div class="spin"></div></div></div></div>
</div>

</div><!-- /views -->
</div><!-- /shell -->

<!-- ═══ MODAIS ═══ -->

<!-- Processo -->
<div class="mover" id="processoM">
  <div class="modal mlg">
    <div class="mh"><div class="mht">⚖ Novo Processo</div><div class="mx" onclick="fecharM('processoM')">✕</div></div>
    <div class="mb">
      <div class="g2">
        <div class="fg"><label class="fl">Número CNJ</label><input class="fi" id="pCnj" placeholder="0000000-00.0000.0.00.0000"></div>
        <div class="fg"><label class="fl">Número Interno</label><input class="fi" id="pInt" placeholder="TH-2025-001"></div>
      </div>
      <div class="fg"><label class="fl">Título *</label><input class="fi" id="pTit" placeholder="Ex: Ação de Indenização por Danos Morais"></div>
      <div class="g2">
        <div class="fg"><label class="fl">Tipo de Ação</label>
          <select class="fi fsel" id="pTipo"><option value="trabalhista">Trabalhista</option><option value="civel">Cível</option><option value="previdenciario">Previdenciário</option><option value="criminal">Criminal</option><option value="tributario">Tributário</option><option value="pericia">Pericial</option></select>
        </div>
        <div class="fg"><label class="fl">Polo</label>
          <select class="fi fsel" id="pPolo"><option value="ativo">Polo Ativo</option><option value="passivo">Polo Passivo</option></select>
        </div>
      </div>
      <div class="g2">
        <div class="fg"><label class="fl">Tribunal / Vara</label><input class="fi" id="pTrib" placeholder="TJSP / 3ª Vara Cível"></div>
        <div class="fg"><label class="fl">Comarca</label><input class="fi" id="pCom" placeholder="São Paulo"></div>
      </div>
      <div class="g2">
        <div class="fg"><label class="fl">Valor da Causa (R$)</label><input class="fi" type="number" id="pVal" placeholder="0.00" step="0.01"></div>
        <div class="fg"><label class="fl">Prazo Fatal</label><input class="fi" type="date" id="pPrz"></div>
      </div>
      <div class="fg"><label class="fl">Parte Contrária</label><input class="fi" id="pContr" placeholder="Nome ou razão social da parte contrária"></div>
      <div class="fg"><label class="fl">Observações</label><textarea class="fi ftxt" id="pObs" placeholder="Contexto, notas relevantes…"></textarea></div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('processoM')">Cancelar</button><button class="bp btn" onclick="salvarProcesso()">✓ Criar Processo</button></div>
  </div>
</div>

<!-- Despesa -->
<div class="mover" id="despesaM">
  <div class="modal">
    <div class="mh"><div class="mht">🧾 Registrar Despesa de Campo</div><div class="mx" onclick="fecharM('despesaM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Categoria</label>
        <select class="fi fsel" id="dCat" onchange="togKm()">
          <option value="km">Quilometragem (Km)</option><option value="alimentacao">Alimentação</option>
          <option value="hospedagem">Hospedagem</option><option value="pedagio">Pedágio</option>
          <option value="transporte">Transporte</option><option value="outros">Outros</option>
        </select>
      </div>
      <div class="fg" id="kmField">
        <label class="fl">Quilometragem</label>
        <input class="fi" type="number" id="dKm" placeholder="143" oninput="calcKm()" min="0">
        <span style="font-size:11.5px;color:var(--t3)">Taxa: R$ <?= number_format($_km, 2, ',', '.') ?>/km · Valor calculado automaticamente</span>
      </div>
      <div class="fg"><label class="fl">Valor (R$)</label><input class="fi" type="number" id="dVal" placeholder="0.00" step="0.01"></div>
      <div class="fg"><label class="fl">Data</label><input class="fi" type="date" id="dDt"></div>
      <div class="fg"><label class="fl">Processo (opcional)</label><select class="fi fsel" id="dProc"><option value="">Nenhum</option></select></div>
      <div class="fg"><label class="fl">Descrição</label><input class="fi" id="dDesc" placeholder="Detalhes da despesa…"></div>
      <div class="fg"><label class="fl">Comprovante (opcional)</label><input class="fi" type="file" id="dComp" accept="image/*,.pdf" style="padding:6px"></div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('despesaM')">Cancelar</button><button class="bp btn" onclick="salvarDesp()">✓ Registrar</button></div>
  </div>
</div>

<!-- Parecer Divergente -->
<div class="mover" id="parecerM">
  <div class="modal">
    <div class="mh"><div class="mht">📋 Gerar Parecer Técnico Divergente</div><div class="mx" onclick="fecharM('parecerM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Processo</label><select class="fi fsel" id="parProc"><option value="">Selecione o processo</option></select></div>
      <div class="fg"><label class="fl">Perito Oficial</label><input class="fi" id="parPer" placeholder="Dr. Nome do Perito Oficial"></div>
      <div class="fg"><label class="fl">Principais Divergências</label><textarea class="fi ftxt" id="parDiv" rows="4" placeholder="Descreva os pontos de divergência técnica…"></textarea></div>
      <div class="fg"><label class="fl">Conclusão Divergente</label><textarea class="fi ftxt" id="parConc" rows="3" placeholder="Valor divergente, metodologia proposta…"></textarea></div>
      <div class="fg"><label class="fl">Destino do Documento</label>
        <select class="fi fsel" id="parDest"><option value="ged">Salvar no GED</option><option value="assinatura">Enviar para assinatura (Assinafy)</option></select>
      </div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('parecerM')">Cancelar</button><button class="bp btn" onclick="gerarParecer()">📄 Gerar Parecer</button></div>
  </div>
</div>

<!-- Interação CRM -->
<div class="mover" id="interM">
  <div class="modal">
    <div class="mh"><div class="mht">💬 Registrar Interação</div><div class="mx" onclick="fecharM('interM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Stakeholder</label><select class="fi fsel" id="iSt"><option value="">Selecione</option></select></div>
      <div class="g2">
        <div class="fg"><label class="fl">Canal</label>
          <select class="fi fsel" id="iCan"><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option><option value="telefone">Telefone</option><option value="presencial">Presencial</option><option value="outro">Outro</option></select>
        </div>
        <div class="fg"><label class="fl">Sentimento</label>
          <select class="fi fsel" id="iSen"><option value="positivo">✅ Positivo</option><option value="neutro">➡️ Neutro</option><option value="negativo">❌ Negativo</option></select>
        </div>
      </div>
      <div class="fg"><label class="fl">Assunto *</label><input class="fi" id="iAss" placeholder="Ex: Atualização sobre prazo recursal"></div>
      <div class="fg"><label class="fl">Descrição</label><textarea class="fi ftxt" id="iDesc" placeholder="Resumo da interação…"></textarea></div>
      <div class="g2">
        <div class="fg"><label class="fl">Data/Hora</label><input class="fi" type="datetime-local" id="iDt"></div>
        <div class="fg"><label class="fl">Próximo Follow-up</label><input class="fi" type="date" id="iFup"></div>
      </div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('interM')">Cancelar</button><button class="bp btn" onclick="salvarInter()">✓ Salvar</button></div>
  </div>
</div>

<!-- Evento Agenda -->
<div class="mover" id="eventoM">
  <div class="modal">
    <div class="mh"><div class="mht">📅 Novo Evento na Agenda</div><div class="mx" onclick="fecharM('eventoM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Título *</label><input class="fi" id="evTit" placeholder="Ex: Audiência — Proc. 0012345"></div>
      <div class="fg"><label class="fl">Tipo</label>
        <select class="fi fsel" id="evTipo"><option value="audiencia">Audiência</option><option value="pericia">Perícia</option><option value="reuniao">Reunião</option><option value="prazo">Prazo</option><option value="followup">Follow-up CRM</option><option value="outro">Outro</option></select>
      </div>
      <div class="g2">
        <div class="fg"><label class="fl">Início *</label><input class="fi" type="datetime-local" id="evIni"></div>
        <div class="fg"><label class="fl">Fim</label><input class="fi" type="datetime-local" id="evFim"></div>
      </div>
      <div class="fg"><label class="fl">Local</label><input class="fi" id="evLoc" placeholder="Sala, vara, endereço…"></div>
      <div class="fg"><label class="fl">Processo (opcional)</label><select class="fi fsel" id="evProc"><option value="">Nenhum</option></select></div>
      <div class="fg"><label class="fl">Descrição</label><textarea class="fi ftxt" id="evDesc" rows="2"></textarea></div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('eventoM')">Cancelar</button><button class="bp btn" onclick="salvarEvento()">✓ Salvar</button></div>
  </div>
</div>

<!-- Perícia -->
<div class="mover" id="periciaM">
  <div class="modal">
    <div class="mh"><div class="mht">🔬 Nova Perícia</div><div class="mx" onclick="fecharM('periciaM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Processo *</label><select class="fi fsel" id="perProc"><option value="">Selecione o processo</option></select></div>
      <div class="g2">
        <div class="fg"><label class="fl">Tipo</label>
          <select class="fi fsel" id="perTipo"><option value="nr15_calor">NR-15 Calor (IBUTG)</option><option value="nr15_ruido">NR-15 Ruído</option><option value="nr15_quimicos">NR-15 Agentes Químicos</option><option value="contabil">Contábil</option><option value="engenharia">Engenharia</option><option value="medica">Médica</option><option value="outra">Outra</option></select>
        </div>
        <div class="fg"><label class="fl">Data da Perícia *</label><input class="fi" type="date" id="perDt"></div>
      </div>
      <div class="fg"><label class="fl">Local</label><input class="fi" id="perLoc" placeholder="Endereço da perícia"></div>
      <div class="fg"><label class="fl">Perito Adversário (se houver)</label><input class="fi" id="perAdv" placeholder="Dr. Nome do perito da parte contrária"></div>
      <div class="fg"><label class="fl">Observações</label><textarea class="fi ftxt" id="perObs" rows="2"></textarea></div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('periciaM')">Cancelar</button><button class="bp btn" onclick="salvarPericia()">✓ Criar</button></div>
  </div>
</div>

<!-- Stakeholder -->
<div class="mover" id="stakeM">
  <div class="modal">
    <div class="mh"><div class="mht">👤 Novo Stakeholder</div><div class="mx" onclick="fecharM('stakeM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Nome Completo / Razão Social *</label><input class="fi" id="skNome"></div>
      <div class="g2">
        <div class="fg"><label class="fl">Tipo</label>
          <select class="fi fsel" id="skTipo"><option value="cliente">Cliente</option><option value="advogado_adversario">Adv. Adversário</option><option value="perito">Perito</option><option value="juiz">Juiz/Desembargador</option><option value="testemunha">Testemunha</option><option value="outro">Outro</option></select>
        </div>
        <div class="fg"><label class="fl">CPF / CNPJ</label><input class="fi" id="skDoc" placeholder="000.000.000-00"></div>
      </div>
      <div class="g2">
        <div class="fg"><label class="fl">E-mail</label><input class="fi" type="email" id="skEm"></div>
        <div class="fg"><label class="fl">WhatsApp</label><input class="fi" id="skWa" placeholder="5511999999999"></div>
      </div>
      <div class="fg"><label class="fl">Data de Nascimento</label><input class="fi" type="date" id="skNasc"></div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('stakeM')">Cancelar</button><button class="bp btn" onclick="salvarStake()">✓ Salvar</button></div>
  </div>
</div>

<!-- Receita -->
<div class="mover" id="receitaM">
  <div class="modal">
    <div class="mh"><div class="mht">💰 Lançar Receita / Honorário</div><div class="mx" onclick="fecharM('receitaM')">✕</div></div>
    <div class="mb">
      <div class="fg"><label class="fl">Processo</label><select class="fi fsel" id="rProc"><option value="">Nenhum</option></select></div>
      <div class="fg"><label class="fl">Descrição *</label><input class="fi" id="rDesc" placeholder="Ex: Honorários de êxito — Proc. 001"></div>
      <div class="g2">
        <div class="fg"><label class="fl">Valor (R$) *</label><input class="fi" type="number" id="rVal" placeholder="0.00" step="0.01"></div>
        <div class="fg"><label class="fl">Vencimento *</label><input class="fi" type="date" id="rVenc"></div>
      </div>
      <div class="fg"><label class="fl">Status</label>
        <select class="fi fsel" id="rSt"><option value="previsto">Previsto</option><option value="recebido">Recebido</option><option value="atrasado">Atrasado</option></select>
      </div>
    </div>
    <div class="mf"><button class="bg2 btn" onclick="fecharM('receitaM')">Cancelar</button><button class="bp btn" onclick="salvarReceita()">✓ Lançar</button></div>
  </div>
</div>

<!-- Toast -->

<!-- Token Portal -->
<div class="mover" id="tokenM">
  <div class="modal">
    <div class="mh"><div class="mht">🔑 Gerar Token de Acesso ao Portal</div><div class="mx" onclick="fecharM('tokenM')">✕</div></div>
    <div class="mb">
      <div style="padding:12px 16px;background:var(--el);border-radius:8px;font-size:13px;color:var(--t2);line-height:1.6;margin-bottom:4px">
        O cliente receberá um token único para acessar o portal usando <strong style="color:var(--t1)">CPF + Token</strong>. Envie o token por e-mail ou WhatsApp.
      </div>
      <div class="fg"><label class="fl">Cliente</label><input class="fi" id="tkNome" readonly style="color:var(--teal)"></div>
      <div class="fg">
        <label class="fl">Validade do Token</label>
        <select class="fi fsel" id="tkDias">
          <option value="7">7 dias</option>
          <option value="30" selected>30 dias</option>
          <option value="90">90 dias</option>
          <option value="180">180 dias</option>
          <option value="365">1 ano</option>
        </select>
      </div>
      <!-- Resultado -->
      <div id="tkResultado" style="display:none;padding:16px;background:var(--el);border-radius:10px;border:1px solid var(--teal)">
        <div style="font-size:11px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">Token Gerado — Envie ao cliente</div>
        <div id="tkToken" style="font-family:var(--mo);font-size:13px;color:var(--teal);word-break:break-all;cursor:pointer;padding:10px;background:var(--bg);border-radius:6px;border:1px solid var(--br)" onclick="copiarToken()" title="Clique para copiar"></div>
        <div style="font-size:11.5px;color:var(--t3);margin-top:8px">📋 Clique no token para copiar · CPF do cliente deve ser informado junto</div>
        <div style="display:flex;gap:8px;margin-top:12px">
          <button class="bs2 btn bsm" onclick="copiarToken()">📋 Copiar Token</button>
          <button class="bg2 btn bsm" onclick="copiarMensagem()">💬 Copiar Mensagem Completa</button>
        </div>
      </div>
    </div>
    <div class="mf">
      <button class="bg2 btn" onclick="fecharM('tokenM')">Fechar</button>
      <button class="bp btn" id="btnGerarToken" onclick="gerarToken()">🔑 Gerar Token</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"><span id="toastMsg"></span></div>
<script>
const AB='<?= $_appUrl ?>',KMR=<?= $_km ?>;
let TK=localStorage.getItem('themis_token');
let currentUser=JSON.parse(localStorage.getItem('themis_user')||'{"id":1}');
let cRent,cStatus,cHon,cFinHon,calcData=null;
let pProc=1,pStake=1,pDesp=1,pDoc=1;
let docFilt='todos',periodo='hoje';

if(!TK){window.location.href=AB+'/login';}

/* ── API ── */
async function api(m,p,b,form){
  const h={'Authorization':'Bearer '+TK};
  if(!form)h['Content-Type']='application/json';
  const o={method:m,headers:h};
  if(b&&!form)o.body=JSON.stringify(b);
  if(b&&form)o.body=b;
  let r=await fetch(AB+'/api'+p,o).catch(()=>null);
  if(!r||(!r.ok&&r.status===404))r=await fetch(AB+'/api.php?r='+p,o).catch(()=>({ok:false,json:()=>({})}));
  try{return await r.json();}catch(e){return{};}
}

/* ── Helpers ── */
const R=v=>'R$ '+Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
const fD=s=>s?new Date(s.includes('T')?s:s+'T12:00').toLocaleDateString('pt-BR'):'—';
const fDT=s=>s?new Date(s).toLocaleString('pt-BR',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}):'—';
const sp=()=>'<div class="spinw"><div class="spin"></div></div>';
const em=(t='Nenhum registro encontrado.',i='📭')=>`<div class="emst"><div class="ei">${i}</div><p>${t}</p></div>`;

function cd(s){
  if(!s)return'—';
  const d=Math.ceil((new Date(s.includes('T')?s:s+'T12:00')-Date.now())/86400000);
  return d<=0?'<span class="cd cdr">Vencido</span>':d<=3?`<span class="cd cdr">${d}d</span>`:d<=14?`<span class="cd cda">${d}d</span>`:`<span class="cd cdg">${d}d</span>`;
}
function bSt(s){const m={ativo:'bb',encerrado:'bg',recurso:'ba',execucao:'bb',arquivado:'bgr',suspenso:'ba',aguardando_decisao:'ba',proposta:'bv',pericia:'bt'};return`<span class="badge ${m[s]||'bgr'}">${s||'—'}</span>`;}
function bD(s){const m={pendente:'ba',aprovado:'bg',rejeitado:'br',reembolsado:'bt'};return`<span class="badge ${m[s]||'bgr'}">${s||'—'}</span>`;}

/* ── Toast ── */
function toast(msg,ok=true){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=msg;t.className='toast on '+(ok?'tok':'terr');clearTimeout(t._t);t._t=setTimeout(()=>t.classList.remove('on'),4000);}

/* ── Nav ── */
function go(v,btn){
  document.querySelectorAll('.view').forEach(x=>x.classList.remove('on'));
  document.querySelectorAll('.nav').forEach(x=>x.classList.remove('on'));
  const el=document.getElementById('view-'+v);if(el)el.classList.add('on');
  if(btn)btn.classList.add('on');
  const T={dashboard:'Dashboard BI',processos:'Processos',agenda:'Agenda',tarefas:'Tarefas',pericias:'Perícias',calculos:'Cálculos SELIC/IPCA',documentos:'GED / Documentos',fabrica:'Fábrica de Documentos',workflow:'Workflow — Kanban de Processos',ibutg:'IBUTG / Insalubridade NR-15',lixeira:'Lixeira — GED (30 dias)',financeiro:'Honorários',despesas:'Despesas Campo',clientes:'Clientes',stakeholders:'Stakeholders',crm:'CRM — Alertas',radar:'DataJud Radar',circuit:'Status das APIs — Circuit Breaker',licenca:'Licença do Sistema',audit:'Audit Log — Trilha de Auditoria'};
  document.getElementById('tTtl').textContent=T[v]||v;
  document.getElementById('vwrap').scrollTop=0;
  localStorage.setItem('themis_view',v);
  lv(v);
}
function lv(v){switch(v){case'dashboard':loadDash();break;case'processos':loadProc();break;case'agenda':loadAgenda();break;case'tarefas':loadTarefas();break;case'pericias':loadPericias();break;case'calculos':loadHistCalc();break;case'documentos':loadDocs();break;case'fabrica':loadFabrica();break;case'workflow':loadWorkflow();break;case'ibutg':initIbutg();break;case'lixeira':loadLixeira();break;case'financeiro':loadFin();break;case'despesas':loadDesp();break;case'clientes':loadClientes();break;case'stakeholders':loadStake();break;case'crm':loadCRM();break;case'radar':loadRadar();break;case'circuit':loadCircuit();break;case'licenca':loadLicenca();break;case'audit':loadAudit();break;}}
function setPeriodo(p,btn){periodo=p;btn.closest('.tabs').querySelectorAll('.tab').forEach(b=>b.classList.remove('on'));btn.classList.add('on');if(document.getElementById('view-dashboard').classList.contains('on'))loadDash();}

/* ── DASHBOARD ── */
async function loadDash(){
  const[kp,pr,ag,cr,dj,de,no]=await Promise.all([
    api('GET','/financeiro/kpis'),api('GET','/processos?per_page=5&urgentes=1'),
    api('GET','/agenda/hoje'),api('GET','/stakeholders/dashboard/crm'),
    api('GET','/datajud/movimentos?limit=4'),api('GET','/despesas?per_page=5&status=pendente'),
    api('GET','/notificacoes?limit=10'),
  ]);
  const k=kp.data||{},c=cr.data||{};
  // KPIs
  document.getElementById('kProc').textContent=k.processos_ativos??'–';
  document.getElementById('kRec').textContent=k.honorarios_mes?R(k.honorarios_mes):'–';
  document.getElementById('kPrz').textContent=k.prazos_criticos??'–';
  document.getElementById('kEx').textContent=k.taxa_exito?k.taxa_exito+'%':'–';
  document.getElementById('nb-proc').textContent=k.processos_ativos||'–';
  document.getElementById('nb-crm').textContent=c.alertas_pendentes||'–';
  document.getElementById('nb-agenda').textContent=(ag.data||[]).length||'–';
  // Alertas
  const al=document.getElementById('alertas');al.innerHTML='';
  (no.data||[]).filter(n=>n.tipo==='urgente').slice(0,2).forEach(n=>{
    al.insertAdjacentHTML('beforeend',`<div class="abanner abd"><span style="font-size:20px">⚠️</span><div class="abmsg"><strong>${n.titulo}</strong><small>${n.mensagem}</small></div></div>`);
  });
  if((k.alvaras_expedidos||0)>0)al.insertAdjacentHTML('afterbegin',`<div class="abanner abw"><span style="font-size:20px">🏦</span><div class="abmsg"><strong>${k.alvaras_expedidos} alvará(s) expedido(s) — verifique o módulo Radar</strong><small>DataJud detectou expedição recente</small></div></div>`);
  // Charts
  bldRent(k);bldStatus(k);bldHon(k);
  // Processos urgentes
  const ps=pr.data||[];
  document.getElementById('dProc').innerHTML=ps.length?`<table><thead><tr><th>Processo</th><th>Cliente</th><th>Status</th><th>Prazo Fatal</th><th></th></tr></thead><tbody>${ps.map(p=>`<tr><td><div class="tdp">${(p.titulo||'').substring(0,35)}</div><div class="tdm">${p.numero_interno||''}</div></td><td>${p.cliente_nome||'—'}</td><td>${bSt(p.status)}</td><td>${cd(p.prazo_fatal)}</td><td><button class="bg2 btn bsm">→</button></td></tr>`).join('')}</tbody></table>`:em('Nenhum prazo urgente ✓','✅');
  // Agenda
  const ag2=ag.data||[];
  const cor={audiencia:'var(--blue)',pericia:'var(--teal)',reuniao:'var(--violet)',prazo:'var(--rose)',followup:'var(--amber)',outro:'var(--t3)'};
  document.getElementById('dAgenda').innerHTML=ag2.length?ag2.map(e=>`<div class="agi"><div class="agt">${fDT(e.inicio).split(' ')[1]||'–'}</div><div class="agb" style="background:${cor[e.tipo]||'var(--t3)'}"></div><div class="agc"><div class="agtit">${e.titulo}</div><div class="agsub">${e.local||''}</div></div><span class="badge bb">${e.tipo}</span></div>`).join(''):'<div style="padding:16px;text-align:center;color:var(--t3);font-size:13px">Nenhum evento hoje</div>';
  // CRM alertas
  document.getElementById('crmBadge').textContent=(c.alertas_pendentes||0)+' alertas';
  const sc=c.sem_contato_30dias_lista||[],an=c.aniversariantes_7d||[];
  let ch='';
  sc.slice(0,3).forEach(cl=>{ch+=`<div class="prii"><div class="prd prc"></div><div style="flex:1"><div style="font-size:13px;font-weight:600;color:var(--t1)">${cl.nome}</div><div style="font-size:11.5px;color:var(--t3)">Sem contato há ${cl.dias_sem_contato} dias</div></div><button class="bg2 btn bsm" onclick="intRap(${cl.id},'${(cl.nome||'').replace(/'/g,"\\'")}')">Contatar</button></div>`;});
  an.forEach(a=>{ch+=`<div class="prii"><div class="prd pra"></div><div style="flex:1"><div style="font-size:13px;font-weight:600;color:var(--t1)">${a.nome}</div><div style="font-size:11.5px;color:var(--t3)">🎂 Aniversário em ${a.dias} dias</div></div></div>`;});
  if(!ch)ch='<div style="padding:12px;font-size:13px;color:var(--t3)">Nenhum alerta pendente ✓</div>';
  document.getElementById('dCRM').innerHTML=ch;
  // Checklist parecer
  const chks=[{t:'Metodologia (SELIC vs IPCA-E)',d:'Índice pós 11/2017 × Tese 810/STF',s:'Crítica',c:'br'},{t:'IBUTG — Aferição NR-15',d:'Calibração e horário de medição',s:'Alta',c:'ba'},{t:'Pro Rata Die vs Juros simples',d:'Verificar juros compostos indevidos',s:'Alta',c:'ba'},{t:'Premissas fáticas — Jornada',d:'Confirmar horas com documentação',s:'Média',c:'bb'}];
  let ck=0;
  const chtml=chks.map((x,i)=>{const on=i%2===0;if(on)ck++;return`<div class="chki"><div class="chkb ${on?'on':''}" onclick="this.classList.toggle('on');updP()"></div><div class="chkbd"><div class="chkt">${x.t}</div><div class="chkd">${x.d}</div></div><span class="badge ${x.c}">${x.s}</span></div>`;}).join('');
  const pct=Math.round(ck/chks.length*100);
  document.getElementById('dCheck').innerHTML=chtml+`<div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--bs)"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><span style="font-size:12px;color:var(--t3)">Progresso da análise</span><span style="font-size:12px;font-weight:700;color:var(--teal)" id="chkPct">${pct}%</span></div><div class="prog"><div class="progf" id="chkBar" style="width:${pct}%;background:linear-gradient(90deg,var(--blue),var(--teal))"></div></div><button class="bp btn" style="width:100%;justify-content:center;margin-top:14px" onclick="abrirM('parecerM')">📋 Gerar Parecer Técnico Divergente</button></div>`;
  document.getElementById('parBadge').textContent=pct+'% analisado';
  // Honorários BI
  const h=k.honorarios_summary||{};
  document.getElementById('honSum').innerHTML=[{v:h.previsto||k.honorarios_mes,l:'Previsto',c:'var(--teal)'},{v:h.recebido||k.honorarios_recebido,l:'Recebido',c:'var(--emerald)'},{v:h.pendente||k.honorarios_inadimplente,l:'Pendente',c:'var(--amber)'}].map(x=>`<div style="text-align:center;padding:12px;background:var(--el);border-radius:10px"><div style="font-size:17px;font-weight:700;color:${x.c}">${x.v?R(x.v):'–'}</div><div style="font-size:11px;color:var(--t3);margin-top:3px">${x.l}</div></div>`).join('');
  const soc=k.socios||[];
  document.getElementById('honSoc').innerHTML=soc.length?`<div style="font-size:12px;color:var(--t3);margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:.06em">Performance por Sócio</div>`+soc.map(s=>`<div style="margin-bottom:10px"><div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px"><span style="color:var(--t1);font-weight:500">${s.nome}</span><span style="color:var(--t2)">${R(s.honorarios)} <span style="color:var(--emerald)">${s.pct||0}%</span></span></div><div class="prog"><div class="progf" style="width:${s.pct||0}%;background:var(--blue)"></div></div></div>`).join(''):'';
  // DataJud
  const mv=dj.data||[];
  document.getElementById('dDatajud').innerHTML=mv.length?mv.map(m=>`<div class="tli"><div class="tld" style="border-color:var(--blue);background:rgba(59,130,246,.1);color:var(--blue)">${m.tipo==='alvara'?'🏦':m.tipo==='sentenca'?'⚖':'📨'}</div><div class="tlc"><div class="tlt">${m.descricao||m.tipo}</div><div class="tlm">${fDT(m.data_movimento)} · ${m.numero_processo||''}</div></div></div>`).join(''):'<div style="padding:12px;font-size:13px;color:var(--t3)">Sem movimentações</div>';
  document.getElementById('djSync').textContent='Sincronizado';
  // Despesas pendentes
  const dp=de.data||[];
  const tot=dp.reduce((a,x)=>a+parseFloat(x.valor||0),0);
  document.getElementById('dDesp').innerHTML=dp.length?`<table><thead><tr><th>Profissional</th><th>Categoria</th><th>Valor</th><th>Status</th><th></th></tr></thead><tbody>${dp.map(d=>`<tr><td class="tdp">${d.usuario_nome||'—'}</td><td><span class="badge bb">${d.categoria}</span></td><td class="tdm">${R(d.valor)}</td><td>${bD(d.status)}</td><td><button class="bs2 btn bsm" onclick="aprovarD(${d.id})">Aprovar</button></td></tr>`).join('')}</tbody></table>`:em('Nenhuma despesa pendente','✅');
  document.getElementById('dDespTotal').innerHTML=dp.length?`<span>Total pendente:</span><span class="tdm" style="margin-left:auto;font-size:14px;font-weight:700;color:var(--amber)">${R(tot)}</span>`:'';
  loadNotifs(no.data||[]);
}
function updP(){const a=document.querySelectorAll('.chkb').length,d=document.querySelectorAll('.chkb.on').length,p=Math.round(d/a*100);const b=document.getElementById('chkBar'),t=document.getElementById('chkPct');if(b)b.style.width=p+'%';if(t)t.textContent=p+'%';}

/* ── CHARTS ── */
Chart.defaults.color='#8b95a9';Chart.defaults.borderColor='#2a3449';Chart.defaults.font.family="'DM Sans',sans-serif";
function bldRent(k){const ctx=document.getElementById('cRent');if(!ctx)return;if(cRent)cRent.destroy();const ml=k.meses_labels||['Jan','Fev','Mar','Abr','Mai','Jun'];cRent=new Chart(ctx,{type:'bar',data:{labels:ml,datasets:[{label:'Advocacia',data:k.rent_advocacia||[42,38,55,61,58,72],backgroundColor:'rgba(59,130,246,.7)',borderRadius:6},{label:'Perícia',data:k.rent_pericia||[18,22,19,28,31,35],backgroundColor:'rgba(20,184,166,.7)',borderRadius:6},{label:'Consultoria',data:k.rent_consult||[8,12,9,11,15,18],backgroundColor:'rgba(139,92,246,.6)',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:10,font:{size:11}}}},scales:{x:{grid:{color:'#1e2840'},ticks:{font:{size:11}}},y:{grid:{color:'#1e2840'},ticks:{font:{size:11},callback:v=>'R$'+(v/1000).toFixed(0)+'k'}}}}});}
function bldStatus(k){const ctx=document.getElementById('cStatus');if(!ctx)return;if(cStatus)cStatus.destroy();const at=k.processos_ativos||68,re=k.processos_recurso||31,ex=k.processos_execucao||28,tot=at+re+ex||1;cStatus=new Chart(ctx,{type:'doughnut',data:{labels:['Ativos','Aguard. Decisão','Execução'],datasets:[{data:[at,re,ex],backgroundColor:['#3b82f6','#f59e0b','#10b981'],borderWidth:0,hoverOffset:6}]},options:{responsive:true,maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});document.getElementById('stLeg').innerHTML=[{c:'#3b82f6',l:'Ativos',v:at},{c:'#f59e0b',l:'Aguard. Decisão',v:re},{c:'#10b981',l:'Execução',v:ex}].map(x=>`<div style="display:flex;justify-content:space-between;font-size:12px;align-items:center"><span style="display:flex;align-items:center;gap:6px;color:var(--t2)"><span style="width:10px;height:10px;border-radius:2px;background:${x.c};display:inline-block"></span>${x.l}</span><span style="color:var(--t1);font-weight:600">${x.v} (${Math.round(x.v/tot*100)}%)</span></div>`).join('');}
function bldHon(k){const ctx=document.getElementById('cHon');if(!ctx)return;if(cHon)cHon.destroy();const ml=k.meses_labels||['Jan','Fev','Mar','Abr','Mai','Jun','Jul'];cHon=new Chart(ctx,{type:'line',data:{labels:ml,datasets:[{label:'Previsto',data:k.hon_previsto||[48,52,49,61,58,65,58],borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.06)',tension:.4,fill:true,borderWidth:2,pointRadius:3},{label:'Recebido',data:k.hon_recebido||[42,45,44,53,50,55,44],borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.04)',tension:.4,fill:true,borderWidth:2,pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{boxWidth:10,font:{size:11}}}},scales:{x:{grid:{color:'#1e2840'},ticks:{font:{size:11}}},y:{grid:{color:'#1e2840'},ticks:{font:{size:11},callback:v=>'R$'+(v/1000).toFixed(0)+'k'}}}}});}

/* ── PROCESSOS ── */
async function loadProc(){
  const st=document.getElementById('fSt')?.value||'',po=document.getElementById('fPo')?.value||'';
  let url=`/processos?per_page=20&page=${pProc}`;if(st)url+='&status='+st;if(po)url+='&polo='+po;
  const d=await api('GET',url);const ps=d.data||[];
  document.getElementById('tProc').innerHTML=ps.length?`<table><thead><tr><th>Número</th><th>Título</th><th>Cliente</th><th>Responsável</th><th>Status</th><th>Valor</th><th>Prazo Fatal</th></tr></thead><tbody>${ps.map(p=>`<tr><td><div class="tdm">${p.numero_interno||''}</div><div class="tds">${(p.numero_cnj||'').substring(0,22)}</div></td><td class="tdp">${(p.titulo||'').substring(0,40)}</td><td>${p.cliente_nome||'—'}</td><td>${p.responsavel_nome||'—'}</td><td>${bSt(p.status)}</td><td class="tdm">${p.valor_causa?R(p.valor_causa):'—'}</td><td>${cd(p.prazo_fatal)}</td></tr>`).join('')}</tbody></table>`:em();
  document.getElementById('pagProc').innerHTML=`<button class="pagb" onclick="pProc--;loadProc()" ${pProc<=1?'disabled':''}>←</button><span class="pagi">${ps.length} de ${d.total||0}</span><button class="pagb" onclick="pProc++;loadProc()" ${ps.length<20?'disabled':''}>→</button>`;
}
async function salvarProcesso(){
  const b={numero_cnj:document.getElementById('pCnj').value,numero_interno:document.getElementById('pInt').value,titulo:document.getElementById('pTit').value,tipo:document.getElementById('pTipo').value,polo:document.getElementById('pPolo').value,tribunal:document.getElementById('pTrib').value,comarca:document.getElementById('pCom').value,valor_causa:parseFloat(document.getElementById('pVal').value)||null,prazo_fatal:document.getElementById('pPrz').value||null,parte_contraria:document.getElementById('pContr').value,observacoes:document.getElementById('pObs').value};
  if(!b.titulo){toast('Informe o título do processo.',false);return;}
  const r=await api('POST','/processos',b);
  if(r.success||r.data){fecharM('processoM');toast('✅ Processo criado!');loadProc();}else toast('❌ '+(r.message||'Erro.'),false);
}

/* ── AGENDA ── */
async function loadAgenda(){
  // Carregar mês atual no calendário
  renderCal();
  carregarCalMes();
  // Carregar lista de eventos
  const mesStr=calAno+'-'+String(calMes+1).padStart(2,'0');
  const ini=mesStr+'-01',fim=mesStr+'-31';
  const d=await api('GET','/agenda?inicio='+ini+'&fim='+fim+'&per_page=60');
  const ev=d.data||[];
  const cor={audiencia:'var(--blue)',pericia:'var(--teal)',reuniao:'var(--violet)',prazo:'var(--rose)',followup:'var(--amber)',outro:'var(--t3)'};
  document.getElementById('tAgenda').innerHTML=ev.length?`<table><thead><tr><th>Data/Hora</th><th>Título</th><th>Tipo</th><th>Local</th><th>Processo</th><th></th></tr></thead><tbody>${ev.map(e=>`<tr><td class="tdm">${fDT(e.inicio)}</td><td class="tdp">${e.titulo}</td><td><span class="badge" style="background:${cor[e.tipo]||'var(--t3)'}22;color:${cor[e.tipo]||'var(--t3)'}">${e.tipo}</span></td><td>${e.local||'—'}</td><td class="tdm">${e.processo_numero||'—'}</td><td><button class="bd btn bsm" onclick="deletarEvento(${e.id})">✕</button></td></tr>`).join('')}</tbody></table>`:em('Nenhum evento neste mês','📅');
}
async function deletarEvento(id){if(!confirm('Excluir evento?'))return;await api('DELETE','/agenda/'+id);toast('✅ Evento excluído!');loadAgenda();}
async function salvarEvento(){
  const b={titulo:document.getElementById('evTit').value,tipo:document.getElementById('evTipo').value,inicio:document.getElementById('evIni').value,fim:document.getElementById('evFim').value||null,local:document.getElementById('evLoc').value||null,processo_id:document.getElementById('evProc').value||null,descricao:document.getElementById('evDesc').value||null,user_ids:[currentUser.id||1]};
  if(!b.titulo||!b.inicio){toast('Informe título e data de início.',false);return;}
  const r=await api('POST','/agenda',b);
  if(r.success||r.data){fecharM('eventoM');toast('✅ Evento criado!');loadAgenda();carregarCalMes();}else toast('❌ '+(r.message||'Erro ao criar evento.'),false);
}

/* ── TAREFAS ── */
async function loadTarefas(){
  const d=await api('GET','/processos/tarefas?per_page=30');const ts=d.data||[];
  document.getElementById('tTarefas').innerHTML=ts.length?`<table><thead><tr><th>Tarefa</th><th>Processo</th><th>Responsável</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>${ts.map(t=>`<tr><td class="tdp">${t.titulo}</td><td class="tdm">${t.numero_interno||'—'}</td><td>${t.responsavel_nome||'—'}</td><td>${cd(t.data_vencimento)}</td><td>${bSt(t.status||'pendente')}</td></tr>`).join('')}</tbody></table>`:em('Nenhuma tarefa encontrada','✓');
}

/* ── PERÍCIAS ── */
async function loadPericias(){
  const d=await api('GET','/pericias?per_page=20');const ps=d.data||[];
  document.getElementById('tPericias').innerHTML=ps.length?`<table><thead><tr><th>Processo</th><th>Tipo</th><th>Data</th><th>Local</th><th>Status</th><th></th></tr></thead><tbody>${ps.map(p=>`<tr><td class="tdm">${p.numero_interno||'—'}</td><td>${p.tipo}</td><td>${fD(p.data_pericia)}</td><td>${(p.local_realizacao||'').substring(0,30)||'—'}</td><td>${bSt(p.status||'agendada')}</td><td><button class="bg2 btn bsm" onclick="verPar(${p.id})">📋 Parecer</button></td></tr>`).join('')}</tbody></table>`:em('Nenhuma perícia','🔬');
}
function verPar(id){document.getElementById('cardParecer').style.display='';document.getElementById('parProc').value=id;document.getElementById('cardParecer').scrollIntoView({behavior:'smooth'});}
async function salvarPericia(){
  const b={processo_id:document.getElementById('perProc').value,tipo:document.getElementById('perTipo').value,data_pericia:document.getElementById('perDt').value,local_realizacao:document.getElementById('perLoc').value,observacoes:document.getElementById('perObs').value};
  if(!b.processo_id||!b.data_pericia){toast('Selecione processo e data.',false);return;}
  const r=await api('POST','/pericias',b);
  if(r.success||r.data){fecharM('periciaM');toast('✅ Perícia criada!');loadPericias();}else toast('❌ '+(r.message||'Erro.'),false);
}
async function gerarParecer(){
  const b={processo_id:document.getElementById('parProc').value,perito_oficial:document.getElementById('parPer').value,divergencias:document.getElementById('parDiv').value,conclusao:document.getElementById('parConc').value,destino:document.getElementById('parDest').value};
  if(!b.processo_id){toast('Selecione o processo.',false);return;}
  const r=await api('POST','/pericias/parecer-divergente',b);
  fecharM('parecerM');
  if(r.success||r.data){
    toast('✅ Parecer gerado e salvo no GED!');
    setTimeout(()=>go('documentos',null), 1200);
  } else toast('❌ '+(r.message||'Erro ao gerar parecer.'),false);
}

/* ── CÁLCULOS ── */
async function loadHistCalc(){
  const d=await api('GET','/calculos?per_page=10');const cs=d.data||[];
  document.getElementById('histCalc').innerHTML=cs.length?`<table><thead><tr><th>Processo</th><th>Índice</th><th>Valor Base</th><th>Total Atualizado</th><th>Data</th></tr></thead><tbody>${cs.map(c=>`<tr><td class="tdm">${c.numero_interno||'—'}</td><td><span class="badge bb">${c.indice_correcao||c.indice||'—'}</span></td><td>${R(c.valor_base)}</td><td class="pos" style="font-family:var(--mo);font-size:12px">${R(c.valor_total)}</td><td>${fD(c.created_at)}</td></tr>`).join('')}</tbody></table>`:em('Nenhum cálculo registrado','📐');
}
async function atualizarIndices(btn) {
  btn.disabled = true;
  btn.textContent = '⏳ Buscando índices…';
  toast('⏳ Atualizando índices — pode levar alguns segundos…');
  try {
    const r = await api('POST', '/calculos/atualizar-indices');
    if (r.success || r.data) {
      const d = r.data || {};
      const msg = Object.entries(d).map(([k,v]) => k+': '+v).join(' | ');
      toast('✅ ' + msg);
    } else {
      toast('❌ ' + (r.message || 'Erro ao atualizar.'), false);
    }
  } catch(e) {
    toast('❌ Erro de conexão.', false);
  }
  btn.disabled = false;
  btn.textContent = '🔄 Atualizar Índices';
}

async function calcular(){
  const val=parseFloat(document.getElementById('cVal').value),ini=document.getElementById('cIni').value,fim=document.getElementById('cFim').value,idx=document.getElementById('cIdx').value,jur=document.getElementById('cJur').value;
  if(!val||!ini||!fim) return; // silencioso quando chamado pelo onchange sem dados
  const r=await api('POST','/calculos/calcular',{valor_base:val,data_base:ini,data_calculo:fim,indice:idx,metodo_juros:jur,taxa_juros:1.0});
  if(!r.data&&!r.success){toast('❌ '+(r.message||'Erro no cálculo.'),false);return;}
  const res=r.data||{};calcData=res;
  document.getElementById('calcRes').style.display='';
  document.getElementById('calcBadge').textContent=idx+' · '+fD(ini)+' → '+fD(fim);
  document.getElementById('calcKpis').innerHTML=[
    {l:'Valor Original',  v:R(res.valor_base      ||res.valor_original),     c:'var(--t2)'},
    {l:'Correção',        v:R(res.valor_correcao   ||res.correcao_monetaria), c:'var(--blue)'},
    {l:'Juros de Mora',   v:R(res.valor_juros      ||res.juros_mora),         c:'var(--amber)'},
    {l:'Total Atualizado',v:R(res.valor_total       ||res.valor_corrigido),   c:'var(--emerald)'},
  ].map(x=>`<div style="text-align:center;padding:12px;background:var(--el);border-radius:10px"><div style="font-size:16px;font-weight:700;color:${x.c}">${x.v}</div><div style="font-size:11px;color:var(--t3);margin-top:2px">${x.l}</div></div>`).join('');
  const mem=res.memoria_calculo||res.memoria||[];
  document.getElementById('calcMem').innerHTML=mem.length?mem.map(m=>`<tr>
    <td>${m.competencia||m.mes||fD(m.data)||'—'}</td>
    <td class="numr">${R(m.saldo||m.saldo_inicial||0)}</td>
    <td class="numr">${((m.indice_pct||m.fator_mes||0)*100).toFixed(4)}%</td>
    <td class="numr pos">${R(m.correcao||m.correcao_parcial||0)}</td>
    <td class="numr">${R(m.juros||m.juros_mes||0)}</td>
    <td class="numr pos">${R(m.total_periodo||m.total_mes||0)}</td>
  </tr>`).join(''):'<tr><td colspan="6" style="text-align:center;color:var(--t3);padding:16px">Cálculo realizado — sem memória detalhada disponível</td></tr>';
  toast('✅ Cálculo realizado!');loadHistCalc();
}
function exportCalc(){
  if(!calcData){toast('Realize um cálculo primeiro.',false);return;}
  const mem=calcData.memoria_calculo||calcData.memoria||[];
  const rows=[['Competência','Saldo','Índice %','Correção','Juros','Total'].join(';')];
  mem.forEach(m=>rows.push([
    m.competencia||m.mes||'',
    m.saldo||m.saldo_inicial||0,
    ((m.indice_pct||m.fator_mes||0)*100).toFixed(4),
    m.correcao||m.correcao_parcial||0,
    m.juros||m.juros_mes||0,
    m.total_periodo||m.total_mes||0
  ].join(';')));
  const totCorr = calcData.valor_correcao||calcData.correcao_monetaria||0;
  const totJur  = calcData.valor_juros||calcData.juros_mora||0;
  const totTot  = calcData.valor_total||calcData.valor_corrigido||0;
  rows.push(['','','TOTAL',totCorr,totJur,totTot].join(';'));
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob(['\uFEFF'+rows.join('\n')],{type:'text/csv;charset=utf-8'}));a.download=`calculo_${document.getElementById('cIdx').value}_${document.getElementById('cFim').value}.csv`;a.click();toast('✅ CSV exportado!');
}

/* ── GED ── */

/* ── CLIENTES ── */
var _cliEditId = null;

async function loadClientes() {
  var q = document.getElementById('cliSearch') ? document.getElementById('cliSearch').value : '';
  var r = await api('GET', '/clientes?q=' + encodeURIComponent(q) + '&per_page=50');
  var clientes = r.data || [];
  var el = document.getElementById('cliTabela');
  if (!el) return;
  if (!clientes.length) {
    el.innerHTML = '<div style="padding:48px;text-align:center;color:var(--t3)">Nenhum cliente cadastrado.</div>';
    return;
  }
  var rows = clientes.map(function(c) {
    return '<tr><td class="tdp">' + (c.nome||'—') + '</td><td>' + (c.cpf_cnpj||'—') + '</td><td>' + (c.email||'—') + '</td><td>' + (c.telefone||'—') + '</td><td style="display:flex;gap:6px"><button class="bs2 btn bsm" onclick="editarCliente(' + c.id + ')">✏️</button><button class="btn bsm" style="background:#e11d48;color:#fff;border:none" onclick="deletarCliente(' + c.id + ')">🗑</button></td></tr>';
  }).join('');
  el.innerHTML = '<table><thead><tr><th>Nome</th><th>CPF/CNPJ</th><th>E-mail</th><th>Telefone</th><th></th></tr></thead><tbody>' + rows + '</tbody></table>';
}

function abrirModalCliente(id) {
  _cliEditId = id || null;
  var m = document.getElementById('modalCliente');
  if (!m) {
    m = document.createElement('div');
    m.id = 'modalCliente';
    m.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;display:flex;align-items:center;justify-content:center';
    var html = '<div style="background:#1e2433;border:1px solid #3a4060;border-radius:12px;padding:28px 32px;width:500px;max-width:95vw">';
    html += '<h3 style="margin:0 0 20px;color:#e2e8f0" id="cliTitulo">Novo Cliente</h3>';
    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">';
    html += '<div style="grid-column:1/-1"><label style="color:#94a3b8;font-size:.82rem">Nome *</label><input id="cliNome" class="fi" style="width:100%;margin-top:4px"></div>';
    html += '<div><label style="color:#94a3b8;font-size:.82rem">CPF/CNPJ</label><input id="cliCpf" class="fi" style="width:100%;margin-top:4px"></div>';
    html += '<div><label style="color:#94a3b8;font-size:.82rem">E-mail</label><input id="cliEmail" class="fi" style="width:100%;margin-top:4px"></div>';
    html += '<div><label style="color:#94a3b8;font-size:.82rem">Telefone</label><input id="cliTel" class="fi" style="width:100%;margin-top:4px"></div>';
    html += '<div><label style="color:#94a3b8;font-size:.82rem">WhatsApp</label><input id="cliWhats" class="fi" style="width:100%;margin-top:4px"></div>';
    html += '<div style="grid-column:1/-1"><label style="color:#94a3b8;font-size:.82rem">Observações</label><textarea id="cliNotas" class="fi" rows="2" style="width:100%;margin-top:4px"></textarea></div>';
    html += '</div>';
    html += '<div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px">';
    html += '<button onclick="fecharModalCliente()" style="background:#1e2433;border:1px solid #3a4060;color:#94a3b8;padding:8px 16px;border-radius:8px;cursor:pointer">Cancelar</button>';
    html += '<button onclick="salvarCliente()" style="background:#2dd4bf;border:none;color:#0f1623;padding:8px 20px;border-radius:8px;cursor:pointer;font-weight:700">Salvar</button>';
    html += '</div></div>';
    m.innerHTML = html;
    document.body.appendChild(m);
  }
  document.getElementById('cliTitulo').textContent = id ? 'Editar Cliente' : 'Novo Cliente';
  ['cliNome','cliCpf','cliEmail','cliTel','cliWhats','cliNotas'].forEach(function(fid){ var el=document.getElementById(fid); if(el) el.value=''; });
  m.style.display = 'flex';
}

function fecharModalCliente() {
  var m = document.getElementById('modalCliente');
  if (m) m.style.display = 'none';
}

async function editarCliente(id) {
  var r = await api('GET', '/clientes/' + id);
  var c = r.data;
  if (!c) return;
  _cliEditId = id;
  abrirModalCliente(id);
  setTimeout(function() {
    document.getElementById('cliNome').value = c.nome||'';
    document.getElementById('cliCpf').value = c.cpf_cnpj||'';
    document.getElementById('cliEmail').value = c.email||'';
    document.getElementById('cliTel').value = c.telefone||'';
    document.getElementById('cliWhats').value = c.whatsapp||'';
    document.getElementById('cliNotas').value = c.notas||'';
  }, 50);
}

async function salvarCliente() {
  var nome = document.getElementById('cliNome').value.trim();
  if (!nome) { toast('Nome obrigatorio.', false); return; }
  var payload = { nome:nome, cpf_cnpj:document.getElementById('cliCpf').value, email:document.getElementById('cliEmail').value, telefone:document.getElementById('cliTel').value, whatsapp:document.getElementById('cliWhats').value, notas:document.getElementById('cliNotas').value };
  var r = _cliEditId ? await api('PUT', '/clientes/' + _cliEditId, payload) : await api('POST', '/clientes', payload);
  if (r.success || r.data) { fecharModalCliente(); toast('Cliente salvo!'); loadClientes(); }
  else toast('Erro: ' + (r.message||'falha'), false);
}

async function deletarCliente(id) {
  if (!confirm('Remover este cliente?')) return;
  var r = await api('DELETE', '/clientes/' + id);
  if (r.success) { toast('Cliente removido.'); loadClientes(); }
  else toast('Erro ao remover.', false);
}


async function loadDocs(){
  let url=`/documentos?per_page=20&page=${pDoc}`;if(docFilt!=='todos')url+='&categoria='+docFilt;
  const d=await api('GET',url);const docs=d.data||[];
  document.getElementById('tDocs').innerHTML=docs.length?`<table><thead><tr><th>Nome</th><th>Categoria</th><th>Processo</th><th>Tamanho</th><th>Data</th><th></th></tr></thead><tbody>${docs.map(f=>`<tr>
    <td class="tdp">📎 ${f.nome_original||f.nome}</td>
    <td><span class="badge bb">${f.categoria||'outros'}</span></td>
    <td class="tdm">${f.numero_interno||'—'}</td>
    <td style="font-size:12px;color:var(--t3)">${f.tamanho_bytes?(f.tamanho_bytes/1024).toFixed(0)+'KB':'—'}</td>
    <td>${fD(f.created_at)}</td>
    <td style="display:flex;gap:4px">
      <button class="bg2 btn bsm" onclick="dlDoc(${f.id})" title="Download">⬇</button>
      <button class="bg2 btn bsm" onclick="exportarDocPdf(${f.id})">📄 PDF</button>
      <button class="bd btn bsm" onclick="moverLixeira(${f.id})" title="Mover para lixeira">🗑</button>
    </td>
  </tr>`).join('')}</tbody></table>`:em('Nenhum documento','📄');
  document.getElementById('pagDocs').innerHTML=`<span class="pagi">${docs.length} documentos</span>`;
}
function filtDoc(cat,btn){docFilt=cat;pDoc=1;document.querySelectorAll('#view-documentos .tab').forEach(b=>b.classList.remove('on'));btn.classList.add('on');loadDocs();}
async function uploadDoc(inp){
  for(const f of inp.files){
    const fd=new FormData();
    fd.append('arquivo',f);
    fd.append('categoria','outros');
    // Sem processo_id obrigatório — backend aceita 0
    const r=await api('POST','/documentos',fd,true);
    toast(r.success||r.data?'✅ '+f.name+' enviado!':'❌ Erro: '+(r.message||'falha no upload'),!!(r.success||r.data));
  }
  loadDocs();
}

/* ── FINANCEIRO ── */
async function loadFin(){
  const[da,re,al]=await Promise.all([api('GET','/financeiro/dashboard'),api('GET','/financeiro/receitas?per_page=15'),api('GET','/financeiro/alvaras?per_page=10')]);
  const d=da.data||{};
  document.getElementById('finKpis').innerHTML=[{i:'📈',v:R(d.recebido_mes),l:'Recebido no Mês',c:'var(--emerald)'},{i:'📋',v:R(d.previsto_mes),l:'Previsto no Mês',c:'var(--blue)'},{i:'⚠️',v:R(d.inadimplente),l:'Inadimplente',c:'var(--rose)'}].map(x=>`<div class="kpi" style="padding:18px"><div class="kico bl" style="font-size:20px;width:40px;height:40px;margin-bottom:12px">${x.i}</div><div class="kval" style="font-size:22px;color:${x.c}">${x.v}</div><div class="klbl">${x.l}</div></div>`).join('');
  const rs=re.data||[];
  document.getElementById('tReceitas').innerHTML=rs.length?`<table><thead><tr><th>Processo</th><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>${rs.map(r=>`<tr><td class="tdm">${r.numero_interno||'—'}</td><td>${r.descricao||'—'}</td><td class="tdm">${R(r.valor_previsto)}</td><td>${fD(r.data_prevista)}</td><td><span class="badge ${r.status==='recebido'?'bg':r.status==='atrasado'?'br':'ba'}">${r.status}</span></td></tr>`).join('')}</tbody></table>`:em('Nenhuma receita','💰');
  const av=al.data||[];
  document.getElementById('tAlvaras').innerHTML=av.length?`<table><thead><tr><th>Processo</th><th>Valor</th><th>Status</th><th>Data Expedição</th></tr></thead><tbody>${av.map(a=>`<tr><td class="tdm">${a.numero_interno||'—'}</td><td class="tdm">${R(a.valor_alvara)}</td><td><span class="badge ${a.status==='levantado'?'bg':a.status==='expedido'?'ba':'bb'}">${a.status}</span></td><td>${fD(a.data_expedicao)}</td></tr>`).join('')}</tbody></table>`:em('Nenhum alvará','🏦');
  const ctx=document.getElementById('cFinHon');
  if(ctx){if(cFinHon)cFinHon.destroy();const ml=d.meses_labels||['Jan','Fev','Mar','Abr','Mai','Jun'];cFinHon=new Chart(ctx,{type:'line',data:{labels:ml,datasets:[{label:'Previsto',data:d.hon_previsto||[],borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.06)',tension:.4,fill:true,borderWidth:2},{label:'Recebido',data:d.hon_recebido||[],borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.04)',tension:.4,fill:true,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{x:{grid:{color:'#1e2840'}},y:{grid:{color:'#1e2840'},ticks:{callback:v=>'R$'+(v/1000).toFixed(0)+'k'}}}}});}
}
async function salvarReceita(){
  const b={processo_id:document.getElementById('rProc').value||null,descricao:document.getElementById('rDesc').value,valor_previsto:parseFloat(document.getElementById('rVal').value),tipo:'honorario',data_prevista:document.getElementById('rVenc').value,status:document.getElementById('rSt').value};
  if(!b.descricao||!b.valor_previsto||isNaN(b.valor_previsto)||!b.data_prevista){toast('Preencha os campos obrigatórios.',false);return;}
  const r=await api('POST','/financeiro/receitas',b);
  if(r.success||r.data){fecharM('receitaM');toast('✅ Receita lançada!');loadFin();}else toast('❌ '+(r.message||'Erro.'),false);
}

/* ── DESPESAS ── */
async function loadDesp(){
  const d=await api('GET',`/despesas?per_page=20&page=${pDesp}`);const ds=d.data||[];
  document.getElementById('tDesp').innerHTML=ds.length?`<table><thead><tr><th>Profissional</th><th>Categoria</th><th>Descrição</th><th>Valor</th><th>Data</th><th>Status</th><th></th></tr></thead><tbody>${ds.map(d=>`<tr><td class="tdp">${d.usuario_nome||'—'}</td><td><span class="badge bb">${d.categoria}</span></td><td>${(d.descricao||'').substring(0,30)}</td><td class="tdm">${R(d.valor)}</td><td>${fD(d.data_despesa)}</td><td>${bD(d.status)}</td><td>${d.status==='pendente'?`<button class="bs2 btn bsm" onclick="aprovarD(${d.id})">Aprovar</button>`:''}</td></tr>`).join('')}</tbody></table>`:em('Nenhuma despesa','🧾');
  document.getElementById('pagDesp').innerHTML=`<button class="pagb" onclick="pDesp--;loadDesp()" ${pDesp<=1?'disabled':''}>←</button><span class="pagi">${d.total||ds.length} total</span><button class="pagb" onclick="pDesp++;loadDesp()" ${ds.length<20?'disabled':''}>→</button>`;
}
function togKm(){const c=document.getElementById('dCat').value;document.getElementById('kmField').style.display=c==='km'?'':'none';}
function calcKm(){const km=parseFloat(document.getElementById('dKm').value)||0;document.getElementById('dVal').value=(km*KMR).toFixed(2);}
async function salvarDesp(){
  const cat=document.getElementById('dCat').value;
  const desc=document.getElementById('dDesc').value||cat;
  const val=parseFloat(document.getElementById('dVal').value);
  const dt=document.getElementById('dDt').value;
  if(!val||!dt){toast('Preencha valor e data.',false);return;}
  const b={categoria:cat,quilometragem:parseFloat(document.getElementById('dKm').value)||null,valor:val,data_despesa:dt,processo_id:document.getElementById('dProc').value||null,descricao:desc};
  const r=await api('POST','/despesas',b);
  if(r.success||r.data){
    // Upload comprovante no GED geral (sem processo_id fixo)
    const comp=document.getElementById('dComp');
    if(comp&&comp.files&&comp.files[0]){
      const fd=new FormData();
      fd.append('arquivo',comp.files[0]);
      fd.append('nome','Comprovante — '+desc);
      fd.append('categoria','recibo');
      if(b.processo_id) fd.append('processo_id', b.processo_id);
      // Upload direto sem fallback (FormData não funciona via api.php)
      try {
        await fetch(AB+'/api/documentos',{method:'POST',headers:{'Authorization':'Bearer '+TK},body:fd});
      } catch(e) { console.warn('Upload comprovante falhou:',e); }
    }
    // Limpar modal
    ['dDesc','dVal','dKm'].forEach(function(id){var el=document.getElementById(id);if(el)el.value='';});
    var dc=document.getElementById('dComp');if(dc)dc.value='';
    var dp=document.getElementById('dProc');if(dp)dp.value='';
    var dd=document.getElementById('dDt');if(dd)dd.value='';
    fecharM('despesaM');toast('✅ Despesa registrada!');loadDesp();
  } else toast('❌ '+(r.message||'Erro.'),false);
}
async function aprovarD(id){const r=await api('PATCH','/despesas/'+id+'/aprovar',{status:'aprovado'});if(r.success||r.data){toast('✅ Aprovada!');loadDesp();if(document.getElementById('view-dashboard').classList.contains('on'))loadDash();}else toast('❌ '+(r.message||'Erro ao aprovar.'),false);}

/* ── STAKEHOLDERS ── */
async function loadStake(){
  const tp=document.getElementById('fTipo')?.value||'';let url=`/stakeholders?per_page=20&page=${pStake}`;if(tp)url+='&tipo='+tp;
  const d=await api('GET',url);const ss=d.data||[];
  document.getElementById('tStake').innerHTML=ss.length?`<table><thead><tr><th>Nome</th><th>Tipo</th><th>E-mail</th><th>WhatsApp</th><th>Score</th><th>Último Contato</th><th></th></tr></thead><tbody>${ss.map(s=>`<tr><td class="tdp">${s.nome}</td><td><span class="badge bb">${s.tipo}</span></td><td>${s.email||'—'}</td><td class="tdm">${s.whatsapp||'—'}</td><td style="color:${s.score_engajamento>60?'var(--emerald)':s.score_engajamento>30?'var(--amber)':'var(--rose)'};font-weight:600">${s.score_engajamento||0}/100</td><td>${s.ultimo_contato?fD(s.ultimo_contato):'Nunca'}</td><td><button class="bg2 btn bsm" onclick="intRap(${s.id},'${(s.nome||'').replace(/'/g,"\\'")}')">💬</button>${s.tipo==='cliente'||s.tipo==='contraparte'?`<button class="bs2 btn bsm" style="margin-left:4px" onclick="abrirGerarToken(${s.id},'${(s.nome||'').replace(/'/g,"\\'")}')">🔑</button>`:''}</td></tr>`).join('')}</tbody></table>`:em('Nenhum stakeholder','👥');
  document.getElementById('pagStake').innerHTML=`<button class="pagb" onclick="pStake--;loadStake()" ${pStake<=1?'disabled':''}>←</button><span class="pagi">${d.total||ss.length} total</span><button class="pagb" onclick="pStake++;loadStake()" ${ss.length<20?'disabled':''}>→</button>`;
}
async function salvarStake(){
  const b={nome:document.getElementById('skNome').value,tipo:document.getElementById('skTipo').value,cpf_cnpj:document.getElementById('skDoc').value,email:document.getElementById('skEm').value,whatsapp:document.getElementById('skWa').value,data_nascimento:document.getElementById('skNasc').value||null};
  if(!b.nome){toast('Informe o nome.',false);return;}
  const r=await api('POST','/stakeholders',b);
  if(r.success||r.data){fecharM('stakeM');toast('✅ Salvo!');loadStake();}else toast('❌ '+(r.message||'Erro.'),false);
}

/* ── CRM ── */
async function loadCRM(){
  const[cr,it]=await Promise.all([api('GET','/stakeholders/dashboard/crm'),api('GET','/crm/interacoes?per_page=15')]);
  const c=cr.data||{};
  const sc=c.sem_contato_30dias_lista||[];
  document.getElementById('crmSC').innerHTML=sc.length?sc.map(cl=>`<div class="prii" style="padding:12px 16px"><div class="prd prc"></div><div style="flex:1"><div style="font-size:13px;font-weight:600;color:var(--t1)">${cl.nome}</div><div style="font-size:11.5px;color:var(--t3)">${cl.dias_sem_contato} dias · Score: ${cl.score_engajamento}/100</div></div><button class="bg2 btn bsm" onclick="intRap(${cl.id},'${(cl.nome||'').replace(/'/g,"\\'")}')">Contatar</button></div>`).join(''):'<div style="padding:16px;text-align:center;color:var(--t3)">Nenhum cliente em atraso ✓</div>';
  const an=c.aniversariantes_7d||[];
  document.getElementById('crmAniv').innerHTML=an.length?an.map(a=>`<div class="prii" style="padding:12px 16px"><div class="prd pra"></div><div style="flex:1"><div style="font-size:13px;font-weight:600;color:var(--t1)">${a.nome}</div><div style="font-size:11.5px;color:var(--t3)">🎂 ${a.dias} dias</div></div></div>`).join(''):'<div style="padding:16px;text-align:center;color:var(--t3)">Nenhum aniversariante ✓</div>';
  const its=it.data||[];
  document.getElementById('tInter').innerHTML=its.length?`<table><thead><tr><th>Stakeholder</th><th>Canal</th><th>Assunto</th><th>Sentimento</th><th>Data</th></tr></thead><tbody>${its.map(i=>`<tr><td class="tdp">${i.stakeholder_nome||'—'}</td><td><span class="badge bb">${i.canal}</span></td><td>${(i.assunto||'').substring(0,40)}</td><td><span class="badge ${i.sentimento==='positivo'?'bg':i.sentimento==='negativo'?'br':'ba'}">${i.sentimento}</span></td><td>${fDT(i.data_interacao)}</td></tr>`).join('')}</tbody></table>`:em('Nenhuma interação','💬');
}
function intRap(id,nome){document.getElementById('iSt').value=id;document.getElementById('iDt').value=new Date().toISOString().slice(0,16);abrirM('interM');}
async function salvarInter(){
  const b={stakeholder_id:document.getElementById('iSt').value,canal:document.getElementById('iCan').value,sentimento:document.getElementById('iSen').value,assunto:document.getElementById('iAss').value,descricao:document.getElementById('iDesc').value,data_interacao:document.getElementById('iDt').value,proximo_contato:document.getElementById('iFup').value||null};
  if(!b.stakeholder_id||!b.assunto){toast('Selecione stakeholder e informe assunto.',false);return;}
  const r=await api('POST','/crm/interacoes',b);
  if(r.success||r.data){fecharM('interM');toast('✅ Interação registrada!');}else toast('❌ '+(r.message||'Erro.'),false);
}

/* ── RADAR ── */
async function loadRadar(){
  const[pa,al,mv]=await Promise.all([api('GET','/radar/parados'),api('GET','/radar/alvaras'),api('GET','/datajud/movimentos?limit=15')]);
  const ps=pa.data||[];
  document.getElementById('tRadar').innerHTML=ps.length?`<table><thead><tr><th>Número</th><th>Título</th><th>Dias Parado</th><th>Tribunal</th></tr></thead><tbody>${ps.map(p=>`<tr><td class="tdm">${p.numero_interno}</td><td class="tdp">${(p.titulo||'').substring(0,45)}</td><td><span class="cd ${p.dias_parado>90?'cdr':'cda'}">${p.dias_parado} dias</span></td><td>${p.tribunal||'—'}</td></tr>`).join('')}</tbody></table>`:'<div style="padding:24px;text-align:center;color:var(--t3)">✓ Nenhum processo parado há mais de 60 dias</div>';
  const av=al.data||[];
  document.getElementById('tAlvR').innerHTML=av.length?`<table><thead><tr><th>Processo</th><th>Valor</th><th>Data</th><th>Status</th></tr></thead><tbody>${av.map(a=>`<tr><td class="tdm">${a.numero_interno||'—'}</td><td class="tdm">${R(a.valor_alvara)}</td><td>${fD(a.data_expedicao)}</td><td><span class="badge ${a.status==='levantado'?'bg':'ba'}">${a.status}</span></td></tr>`).join('')}</tbody></table>`:'<div style="padding:24px;text-align:center;color:var(--t3)">Nenhum alvará</div>';
  const ms=mv.data||[];
  document.getElementById('tMov').innerHTML=ms.length?`<table><thead><tr><th>Data</th><th>Processo</th><th>Movimentação</th><th>Código</th></tr></thead><tbody>${ms.map(m=>`<tr><td class="tdm">${fDT(m.data_movimento)}</td><td class="tdm">${m.numero_processo||'—'}</td><td class="tdp">${(m.descricao||'').substring(0,60)}</td><td><span class="badge bb">${m.codigo_movimento||'—'}</span></td></tr>`).join('')}</tbody></table>`:em('Nenhuma movimentação','🛰');
}
async function syncDJ(){const b=event.currentTarget;b.disabled=true;b.textContent='⟳ Sincronizando…';await api('POST','/radar/monitorar-todos');b.disabled=false;b.textContent='⟳ Sincronizar';toast('✅ Sincronização iniciada!');setTimeout(loadRadar,2000);}

/* ── NOTIFICAÇÕES ── */
function loadNotifs(ns){
  const cm={urgente:'var(--rose)',info:'var(--blue)',alerta:'var(--amber)',sucesso:'var(--emerald)'};
  document.getElementById('notifList').innerHTML=ns.length?ns.map(n=>`<div class="nr"><div style="width:8px;height:8px;border-radius:50%;background:${cm[n.tipo]||'var(--blue)'};flex-shrink:0;margin-top:5px"></div><div><div style="font-size:13px;font-weight:600;color:var(--t1)">${n.titulo}</div><div style="font-size:12px;color:var(--t3);margin-top:2px">${n.mensagem}</div></div></div>`).join(''):'<div style="padding:16px;text-align:center;color:var(--t3);font-size:13px">Sem notificações</div>';
  document.getElementById('ndot').style.display=ns.some(n=>!n.lida)?'':'none';
}
function togNotif(){document.getElementById('notifPanel').classList.toggle('on');}
function markRead(){api('POST','/notificacoes/marcar-todas-lidas');document.getElementById('ndot').style.display='none';}
document.addEventListener('click',e=>{const p=document.getElementById('notifPanel');if(p.classList.contains('on')&&!e.target.closest('#notifPanel')&&!e.target.closest('.ibtn'))p.classList.remove('on');});

/* ── MODAIS ── */
function abrirM(id){
  document.getElementById(id)?.classList.add('on');
  if(['despesaM','parecerM','periciaM','eventoM','receitaM'].includes(id))preProcs();
  if(id==='interM')preStakes();
  if(id==='receitaM'){
    setTimeout(function(){
      ['rDesc','rVal','rVenc'].forEach(function(fid){var el=document.getElementById(fid);if(el)el.value='';});
      var st=document.getElementById('rSt');if(st)st.value='previsto';
      var rp=document.getElementById('rProc');if(rp)rp.value='';
    },50);
  }
}
function fecharM(id){document.getElementById(id)?.classList.remove('on');}
async function preProcs(){
  const d=await api('GET','/processos?per_page=100&status=ativo');const ps=d.data||[];
  const opts='<option value="">Nenhum</option>'+ps.map(p=>`<option value="${p.id}">${p.numero_interno} — ${(p.titulo||'').substring(0,30)}</option>`).join('');
  ['dProc','parProc','perProc','evProc','rProc'].forEach(id=>{const el=document.getElementById(id);if(el)el.innerHTML=opts;});
}
async function preStakes(){
  const d=await api('GET','/stakeholders?tipo=cliente&per_page=100');const ss=d.data||[];
  const el=document.getElementById('iSt');if(el)el.innerHTML='<option value="">Selecione</option>'+ss.map(s=>`<option value="${s.id}">${s.nome}</option>`).join('');
}
document.querySelectorAll('.mover').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('on');}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.mover.on').forEach(m=>m.classList.remove('on'));});

/* ── BUSCA ── */
let bTmr;
function gBusca(q){clearTimeout(bTmr);if(!q||q.length<2)return;bTmr=setTimeout(()=>{if(document.getElementById('view-processos').classList.contains('on'))loadProc();},400);}


/* ── FÁBRICA DE DOCUMENTOS ── */
let tinyInst = null, tplAtualId = null, tplFilt = 'todos', tagsExibidas = false;

const TAGS = {
  processo: [
    {tag:'{{processo_numero}}',   label:'Nº CNJ'},
    {tag:'{{processo_interno}}',  label:'Nº Interno'},
    {tag:'{{processo_titulo}}',   label:'Título'},
    {tag:'{{processo_vara}}',     label:'Vara'},
    {tag:'{{processo_comarca}}',  label:'Comarca'},
    {tag:'{{processo_tribunal}}', label:'Tribunal'},
    {tag:'{{valor_causa}}',       label:'Valor da Causa'},
    {tag:'{{parte_contraria}}',   label:'Parte Contrária'},
    {tag:'{{polo}}',              label:'Polo'},
  ],
  cliente: [
    {tag:'{{cliente_nome}}',      label:'Nome do Cliente'},
    {tag:'{{cliente_doc}}',       label:'CPF/CNPJ'},
    {tag:'{{cliente_endereco}}',  label:'Endereço'},
  ],
  sistema: [
    {tag:'{{advogado_nome}}',     label:'Advogado Responsável'},
    {tag:'{{advogado_oab}}',      label:'OAB'},
    {tag:'{{data_hoje}}',         label:'Data por Extenso'},
    {tag:'{{data_hoje_fmt}}',     label:'Data dd/mm/aaaa'},
    {tag:'{{ano_atual}}',         label:'Ano Atual'},
    {tag:'{{app_nome}}',          label:'Nome do Escritório'},
    {tag:'{{#if variavel}}...{{/if}}', label:'Bloco Condicional'},
  ]
};

function renderTags() {
  ['processo','cliente','sistema'].forEach(grupo => {
    const el = document.getElementById('tags' + grupo.charAt(0).toUpperCase() + grupo.slice(1));
    if (!el) return;
    el.innerHTML = TAGS[grupo].map(t =>
      `<button class="bg2 btn" style="padding:4px 10px;font-size:11.5px;font-family:var(--mo);letter-spacing:0" onclick="inserirTag('${t.tag.replace(/'/g,"\\'")}')">
        ${t.tag.includes('{{')?t.tag.substring(0,20):t.tag} <span style="font-family:var(--fm);color:var(--t3);font-size:10.5px">${t.label}</span>
      </button>`
    ).join('');
  });
}

function inserirTag(tag) {
  if (tinyInst) {
    tinyInst.insertContent(tag);
    tinyInst.focus();
  }
}

function togTags() {
  tagsExibidas = !tagsExibidas;
  document.getElementById('tagsArea').style.display = tagsExibidas ? 'none' : '';
  document.getElementById('togTagsBtn').textContent = tagsExibidas ? 'Mostrar' : 'Ver todas';
}

function filtTpl(tipo, btn) {
  tplFilt = tipo;
  document.querySelectorAll('#view-fabrica .tab').forEach(b => b.classList.remove('on'));
  btn.classList.add('on');
  loadFabrica();
}

async function loadFabrica() {
  const d = await api('GET', '/templates');
  const ts = (d.data || []).filter(t => tplFilt === 'todos' || t.tipo === tplFilt);
  const tipos = {peticao:'bb',laudo:'bt',parecer:'br',contrato:'bv',notificacao:'ba',relatorio:'bg',outro:'bgr'};
  document.getElementById('tplLista').innerHTML = ts.length
    ? ts.map(t => `<div class="nr" style="flex-direction:column;align-items:flex-start;gap:4px;cursor:pointer;padding:12px 16px" onclick="abrirTemplate(${t.id})">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%">
          <div style="font-size:13px;font-weight:600;color:var(--t1)">${t.nome}</div>
          <span class="badge ${tipos[t.tipo]||'bgr'}">${t.tipo}</span>
        </div>
        <div style="font-size:11.5px;color:var(--t3)">${t.subtipo||''} · Usado ${t.uso_count||0}x</div>
      </div>`).join('')
    : '<div style="padding:24px;text-align:center;color:var(--t3);font-size:13px">Nenhum template</div>';
  // Pré-popula select de processo no gerador
  const p = await api('GET', '/processos?per_page=100&status=ativo');
  const ps = p.data || [];
  const gp = document.getElementById('gerarProc');
  if (gp) gp.innerHTML = '<option value="">Nenhum — preencher manualmente</option>' + ps.map(x => `<option value="${x.id}">${x.numero_interno} — ${(x.titulo||'').substring(0,30)}</option>`).join('');
}

async function abrirTemplate(id) {
  tplAtualId = id;
  document.getElementById('editorVazio').style.display = 'none';
  document.getElementById('editorAtivo').style.display = '';
  // Destaca na lista
  document.querySelectorAll('#tplLista .nr').forEach(r => r.style.background = '');
  const rows = document.querySelectorAll('#tplLista .nr');
  // Carrega dados
  const d = await api('GET', '/templates/' + id);
  const t = d.data || {};
  document.getElementById('tplNome').value = t.nome || '';
  document.getElementById('tplTipo').value = t.tipo || 'peticao';
  document.getElementById('tplSub').value  = t.subtipo || '';
  document.getElementById('tplTimbrado').checked = !!t.papel_timbrado;
  document.getElementById('tplAtivo').checked = !!t.ativo;
  renderTags();
  initTiny(t.conteudo_html || '');
  // Detecta variáveis no conteúdo para o formulário de geração
  detectarVars(t.conteudo_html || '', t.variaveis_json);
}

function novoTemplate() {
  tplAtualId = null;
  document.getElementById('editorVazio').style.display = 'none';
  document.getElementById('editorAtivo').style.display = '';
  document.getElementById('tplNome').value = '';
  document.getElementById('tplTipo').value = 'peticao';
  document.getElementById('tplSub').value = '';
  document.getElementById('tplTimbrado').checked = true;
  document.getElementById('tplAtivo').checked = true;
  renderTags();
  initTiny('<p>Insira o conteúdo do template aqui. Use as tags dinâmicas acima para campos automáticos.</p>');
}

function initTiny(conteudo) {
  if (tinyInst) { tinyInst.destroy(); tinyInst = null; }
  if (typeof tinymce === 'undefined') {
    // TinyMCE não carregou (sem API key) — fallback para textarea normal
    const ta = document.getElementById('tinyEditor');
    ta.style.display = '';
    ta.value = conteudo;
    ta.style.background = 'var(--el)';
    ta.style.border = '1px solid var(--br)';
    ta.style.borderRadius = '8px';
    ta.style.padding = '12px';
    ta.style.color = 'var(--t1)';
    ta.style.fontFamily = 'var(--fm)';
    ta.style.fontSize = '13px';
    return;
  }
  tinymce.init({
    selector: '#tinyEditor',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
    skin: 'oxide-dark',
    content_css: 'dark',
    language: 'pt_BR',
    height: 520,
    menubar: true,
    branding: false,
    promotion: false,
    setup: function(editor) {
      tinyInst = editor;
      editor.on('init', function() {
        editor.setContent(conteudo);
      });
    }
  });
}

function getConteudo() {
  if (tinyInst) return tinyInst.getContent();
  return document.getElementById('tinyEditor').value;
}

async function salvarTemplate(fechar) {
  const nome = document.getElementById('tplNome').value.trim();
  if (!nome) { toast('Informe o nome do template.', false); return; }
  const body = {
    nome,
    tipo:          document.getElementById('tplTipo').value,
    subtipo:       document.getElementById('tplSub').value,
    papel_timbrado:document.getElementById('tplTimbrado').checked ? 1 : 0,
    ativo:         document.getElementById('tplAtivo').checked ? 1 : 0,
    conteudo_html: getConteudo(),
  };
  let r;
  if (tplAtualId) {
    r = await api('PUT', '/templates/' + tplAtualId, body);
  } else {
    r = await api('POST', '/templates', body);
    if (r.data?.id) tplAtualId = r.data.id;
  }
  if (r.success || r.data) {
    toast('✅ Template salvo!');
    loadFabrica();
    if (fechar) {
      document.getElementById('editorAtivo').style.display = 'none';
      document.getElementById('editorVazio').style.display = '';
      tplAtualId = null;
    }
  } else {
    toast('❌ ' + (r.message || 'Erro ao salvar.'), false);
  }
}

function detectarVars(html, varJson) {
  const varsDef = varJson ? (typeof varJson === 'string' ? JSON.parse(varJson) : varJson) : {};
  // Extrai {{variavel}} do HTML excluindo as que são preenchidas automaticamente
  const autoVars = new Set(['processo_numero','processo_interno','processo_titulo','processo_vara','processo_comarca','processo_tribunal','valor_causa','parte_contraria','polo','cliente_nome','cliente_doc','cliente_endereco','advogado_nome','advogado_oab','data_hoje','data_hoje_fmt','ano_atual','app_nome']);
  const found = [...html.matchAll(/\{\{(\w+)\}\}/g)].map(m => m[1]).filter(v => !autoVars.has(v));
  const uniq = [...new Set(found)];
  const area = document.getElementById('gerarVarsArea');
  const cont = document.getElementById('gerarVars');
  if (uniq.length) {
    area.style.display = '';
    cont.innerHTML = uniq.map(v => `<div class="fg"><label class="fl">${v.replace(/_/g,' ')}</label><input class="fi" id="gvar_${v}" placeholder="Valor para {{${v}}}" value="${varsDef[v]||''}"></div>`).join('');
  } else {
    area.style.display = 'none';
  }
}

async function gerarDocumento() {
  if (!tplAtualId) { toast('Selecione um template primeiro.', false); return; }
  // Salvar template silenciosamente ANTES de gerar (sem toast, sem loadFabrica)
  const conteudo = getConteudo();
  if (conteudo) {
    await api('PUT', '/templates/' + tplAtualId, {
      nome: document.getElementById('tplNome').value,
      tipo: document.getElementById('tplTipo').value,
      conteudo_html: conteudo,
      papel_timbrado: document.getElementById('tplTimbrado').checked ? 1 : 0,
      ativo: document.getElementById('tplAtivo').checked ? 1 : 0,
    });
  }
  const procId = parseInt(document.getElementById('gerarProc').value) || null;
  const dest   = document.getElementById('gerarDest').value;
  const vars = {};
  document.querySelectorAll('[id^="gvar_"]').forEach(el => {
    const k = el.id.replace('gvar_', '');
    if (el.value) vars[k] = el.value;
  });
  const btn = event?.currentTarget || document.querySelector('[onclick="gerarDocumento()"]');
  if(btn){btn.disabled = true; btn.textContent = '⏳ Gerando…';}
  try {
    const r = await api('POST', '/templates/' + tplAtualId + '/gerar', {
      processo_id: procId,
      destino: dest,
      variaveis: vars,
    });
    if (r.success || r.data) {
      const d = r.data || {};
      const docId = d.documento_id;
      // Mostrar resultado com link direto
      if (docId) {
        const dlUrl = AB + '/api/ged/download/' + docId + '?token=' + TK;
        const msg = dest === 'pdf_download'
          ? '✅ PDF gerado! <a href="' + dlUrl + '" style="color:var(--teal);text-decoration:underline" target="_blank">Clique aqui para baixar</a>'
          : '✅ Documento salvo no GED!';
        // Mostrar toast com link clicável
        const t = document.getElementById('toast');
        if(t){ t.innerHTML = msg; t.className = 'toast on'; setTimeout(()=>t.classList.remove('on'),6000); }
        // Download automático se destino for pdf_download
        if (dest === 'pdf_download') {
          setTimeout(() => {
            const a = document.createElement('a');
            a.href = dlUrl;
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
          }, 300);
        }
      } else {
        toast(r.message || '✅ Documento gerado!');
      }
      loadFabrica();
      pDoc=1; docFilt='todos'; loadDocs(); // recarregar GED na pág 1 sem filtro
    } else {
      toast('❌ ' + (r.message || 'Erro ao gerar documento.'), false);
    }
  } catch(e) {
    toast('❌ Erro: ' + (e.message || 'falha na geração'), false);
  } finally {
    if(btn){btn.disabled = false; btn.textContent = '📄 Gerar Documento';}
  }
}


/* ── WORKFLOW KANBAN ── */
const WF_COLS = [
  {id:'proposta',         label:'Proposta',          cor:'var(--violet)'},
  {id:'ativo',            label:'Ativo',             cor:'var(--blue)'},
  {id:'aguardando_decisao',label:'Aguard. Decisão',  cor:'var(--amber)'},
  {id:'recurso',          label:'Recurso',           cor:'var(--rose)'},
  {id:'execucao',         label:'Execução',          cor:'var(--teal)'},
  {id:'encerrado',        label:'Encerrado',         cor:'var(--emerald)'},
  {id:'arquivado',        label:'Arquivado',         cor:'var(--t3)'},
];

async function loadWorkflow() {
  const d = await api('GET', '/workflow/kanban');
  const data = d.data || {};
  const board = document.getElementById('kanbanBoard');
  if (!board) return;
  board.innerHTML = WF_COLS.map(col => {
    const cards = data[col.id] || [];
    return `<div style="width:260px;flex-shrink:0">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding:0 2px">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:10px;height:10px;border-radius:50%;background:${col.cor}"></div>
          <span style="font-size:13px;font-weight:700;color:var(--t1)">${col.label}</span>
        </div>
        <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:99px;background:${col.cor}22;color:${col.cor}">${cards.length}</span>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px;min-height:80px">
        ${cards.length ? cards.map(p => wfCard(p, col)).join('') : `<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px;background:var(--el);border-radius:10px;border:1px dashed var(--br)">Vazio</div>`}
      </div>
    </div>`;
  }).join('');
}

function wfCard(p, col) {
  const dias = p.prazo_fatal ? Math.ceil((new Date(p.prazo_fatal+'T12:00') - Date.now()) / 86400000) : null;
  const prazoHtml = dias !== null ? `<div style="margin-top:6px">${cd(p.prazo_fatal)}</div>` : '';
  const nextStatus = {proposta:'ativo',ativo:'aguardando_decisao',aguardando_decisao:'execucao',recurso:'execucao',execucao:'encerrado'};
  const next = nextStatus[col.id];
  return `<div style="background:var(--sf);border:1px solid var(--br);border-left:3px solid ${col.cor};border-radius:10px;padding:12px;cursor:pointer;transition:transform .15s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
    <div style="font-size:12.5px;font-weight:600;color:var(--t1);margin-bottom:4px">${(p.titulo||'').substring(0,32)}</div>
    <div style="font-family:var(--mo);font-size:11px;color:var(--teal)">${p.numero_interno||''}</div>
    <div style="font-size:11.5px;color:var(--t3);margin-top:3px">${p.cliente_nome||'—'}</div>
    ${prazoHtml}
    ${next ? `<div style="margin-top:8px;display:flex;gap:6px">
      <button class="bs2 btn bsm" style="font-size:11px;padding:4px 8px;flex:1" onclick="avancarStatus(${p.id},'${next}')">→ ${WF_COLS.find(c=>c.id===next)?.label||next}</button>
    </div>` : ''}
  </div>`;
}

async function avancarStatus(id, novoStatus) {
  const r = await api('POST', `/processos/${id}/status`, {status: novoStatus});
  if (r.success || r.data) {
    toast(`✅ Status atualizado → ${novoStatus}`);
    loadWorkflow();
  } else {
    toast('❌ ' + (r.message || 'Erro ao atualizar status.'), false);
  }
}

/* ── IBUTG / INSALUBRIDADE ── */
// Tabela de limites NR-15 [atividade][regime]
const NR15 = {
  leve:     { continuo: 30.0, '75_25': 30.6, '50_50': 31.4, '25_75': 32.2 },
  moderada: { continuo: 26.7, '75_25': 28.0, '50_50': 29.4, '25_75': 31.1 },
  pesada:   { continuo: 25.0, '75_25': 25.9, '50_50': 27.9, '25_75': 30.0 },
};
let ibutgHist = [];

function initIbutg() {
  loadHistIbutg();
}

function updIbutg() {
  const amb = document.getElementById('ibtAmb').value;
  const tbsRow = document.getElementById('ibtTbs')?.closest('.fg');
  if (tbsRow) tbsRow.style.opacity = amb === 'interno' ? '0.4' : '1';
  calcIbutg();
}

function calcIbutg() {
  const amb    = document.getElementById('ibtAmb')?.value || 'interno';
  const tbu    = parseFloat(document.getElementById('ibtTbu')?.value) || 0;
  const tbs    = parseFloat(document.getElementById('ibtTbs')?.value) || 0;
  const tg     = parseFloat(document.getElementById('ibtTg')?.value) || 0;
  const ativ   = document.getElementById('ibtAtiv')?.value || 'leve';
  const regime = document.getElementById('ibtRegime')?.value || 'continuo';

  if (!tbu && !tg) return;

  let ibutg;
  if (amb === 'interno') {
    ibutg = 0.7 * tbu + 0.3 * tg;
  } else {
    ibutg = 0.7 * tbu + 0.1 * tbs + 0.2 * tg;
  }
  ibutg = Math.round(ibutg * 10) / 10;

  const limite = NR15[ativ][regime];
  const insalubre = ibutg > limite;
  const pct = Math.min(100, Math.round((ibutg / (limite * 1.3)) * 100));

  const el = document.getElementById('ibtResultado');
  if (!el) return;
  el.style.display = '';
  document.getElementById('ibtValor').textContent = ibutg.toFixed(1) + '°C';
  document.getElementById('ibtValor').style.color = insalubre ? 'var(--rose)' : 'var(--emerald)';
  document.getElementById('ibtLimite').textContent = limite.toFixed(1) + '°C';
  document.getElementById('ibtLimite').style.color = 'var(--t2)';
  document.getElementById('ibtVeredicto').textContent = insalubre ? '🔴 INSALUBRE — Acima do Limite' : '✅ DENTRO do Limite';
  document.getElementById('ibtVeredicto').style.color = insalubre ? 'var(--rose)' : 'var(--emerald)';
  const bar = document.getElementById('ibtBar');
  bar.style.width = pct + '%';
  bar.style.background = insalubre ? 'var(--rose)' : 'var(--emerald)';
  const grau = ibutg > limite + 4 ? 'máximo (40%)' : ibutg > limite + 2 ? 'médio (20%)' : 'mínimo (10%)';
  document.getElementById('ibtAdic').innerHTML = insalubre
    ? `Grau sugerido: <strong style="color:var(--amber)">${grau}</strong> · Diferença do limite: <strong style="color:var(--rose)">+${(ibutg-limite).toFixed(1)}°C</strong>`
    : `Margem de segurança: <strong style="color:var(--emerald)">${(limite-ibutg).toFixed(1)}°C abaixo do limite</strong>`;

  // Salva no histórico local
  ibutgHist.unshift({ts: new Date().toLocaleTimeString('pt-BR'), ibutg, limite, ativ, regime, insalubre});
  renderHistIbutg();
}

function calcInsalubridade() {
  const grau   = parseInt(document.getElementById('insGrau')?.value) || 10;
  const salMin = parseFloat(document.getElementById('insSalMin')?.value) || 1412;
  const meses  = parseInt(document.getElementById('insMeses')?.value) || 0;
  const mensal = salMin * grau / 100;
  const total  = mensal * meses;
  const corr   = total * 1.12; // estimativa SELIC acumulada
  const el = document.getElementById('insResultado');
  if (!el) return;
  el.style.display = '';
  document.getElementById('insValMensal').textContent = 'R$ ' + mensal.toLocaleString('pt-BR',{minimumFractionDigits:2});
  document.getElementById('insValTotal').textContent  = meses ? 'R$ ' + total.toLocaleString('pt-BR',{minimumFractionDigits:2}) : '—';
  document.getElementById('insValCorr').textContent   = meses ? 'R$ ' + corr.toLocaleString('pt-BR',{minimumFractionDigits:2}) : '—';
}

function loadHistIbutg() {
  renderHistIbutg();
}

function renderHistIbutg() {
  const el = document.getElementById('histIbutg');
  if (!el) return;
  if (!ibutgHist.length) {
    el.innerHTML = '<div style="padding:24px;text-align:center;color:var(--t3);font-size:13px">Nenhum cálculo nesta sessão</div>';
    return;
  }
  el.innerHTML = `<table><thead><tr><th>Hora</th><th>IBUTG</th><th>Limite</th><th>Atividade</th><th>Resultado</th></tr></thead><tbody>
    ${ibutgHist.slice(0,10).map(h=>`<tr>
      <td class="tdm">${h.ts}</td>
      <td style="font-weight:700;color:${h.insalubre?'var(--rose)':'var(--emerald)'}">${h.ibutg.toFixed(1)}°C</td>
      <td>${h.limite.toFixed(1)}°C</td>
      <td>${h.ativ} · ${h.regime}</td>
      <td><span class="badge ${h.insalubre?'br':'bg'}">${h.insalubre?'Insalubre':'Dentro'}</span></td>
    </tr>`).join('')}
  </tbody></table>`;
}

function exportIbutg() {
  const tbu  = document.getElementById('ibtTbu')?.value || '–';
  const tg   = document.getElementById('ibtTg')?.value || '–';
  const tbs  = document.getElementById('ibtTbs')?.value || '–';
  const amb  = document.getElementById('ibtAmb')?.value || '–';
  const ativ = document.getElementById('ibtAtiv')?.value || '–';
  const reg  = document.getElementById('ibtRegime')?.value || '–';
  const val  = document.getElementById('ibtValor')?.textContent || '–';
  const lim  = document.getElementById('ibtLimite')?.textContent || '–';
  const res  = document.getElementById('ibtVeredicto')?.textContent || '–';
  const rows = [
    ['Parâmetro','Valor'],
    ['Data/Hora', new Date().toLocaleString('pt-BR')],
    ['Ambiente', amb],
    ['TBU (°C)', tbu],['TBS (°C)', tbs],['TG (°C)', tg],
    ['Atividade', ativ],['Regime', reg],
    ['IBUTG calculado', val],['Limite NR-15', lim],
    ['Resultado', res],
  ].map(r => r.join(';')).join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob(['\uFEFF'+rows], {type:'text/csv;charset=utf-8'}));
  a.download = 'ibutg_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
  toast('✅ CSV exportado!');
}

/* ── LIXEIRA GED ── */
async function loadLixeira() {
  const d = await api('GET', '/documentos/lixeira');
  const docs = d.data || [];
  const el = document.getElementById('tLixeira');
  if (!el) return;
  el.innerHTML = docs.length
    ? `<table><thead><tr><th>Documento</th><th>Processo</th><th>Excluído em</th><th>Purga em</th><th>Dias restantes</th><th></th></tr></thead><tbody>
      ${docs.map(f => `<tr>
        <td class="tdp">📎 ${f.nome_original||f.nome||'—'}</td>
        <td class="tdm">${f.numero_interno||'—'}</td>
        <td>${f.deleted_at ? new Date(f.deleted_at).toLocaleDateString('pt-BR') : '—'}</td>
        <td style="color:var(--rose)">${f.purge_at ? new Date(f.purge_at).toLocaleDateString('pt-BR') : '—'}</td>
        <td><span class="cd ${f.dias_restantes <= 5 ? 'cdr' : f.dias_restantes <= 10 ? 'cda' : 'cdg'}">${f.dias_restantes}d</span></td>
        <td>
          <button class="bs2 btn bsm" onclick="restaurarDoc(${f.id})" title="Restaurar">↩ Restaurar</button>
        </td>
      </tr>`).join('')}
    </tbody></table>`
    : '<div style="padding:48px;text-align:center;color:var(--t3)"><div style="font-size:36px;opacity:.3;margin-bottom:10px">🗑</div><p>Lixeira vazia</p></div>';
}

async function moverLixeira(id) {
  if (!confirm('Mover para a lixeira? O documento pode ser restaurado em até 30 dias.')) return;
  const r = await api('DELETE', '/documentos/' + id);
  if (r.success || r.data || r.message) {
    toast('🗑 Movido para a lixeira!');
    loadDocs();
  } else {
    toast('❌ ' + (r.message || 'Erro ao mover.'), false);
  }
}

async function restaurarDoc(id) {
  const r = await api('POST', `/documentos/${id}/restore`);
  if (r.success || r.data) {
    toast('✅ Documento restaurado para o GED!');
    loadLixeira();
  } else {
    toast('❌ ' + (r.message || 'Erro ao restaurar.'), false);
  }
}


/* ── CIRCUIT BREAKER ── */
async function loadCircuit() {
  const d = await api('GET', '/circuit/status');
  const s = d.data || {};
  const apis = ['datajud','evolution','assinafy','smtp'];
  const labels = {datajud:'🛰 DataJud/CNJ',evolution:'💬 Evolution (WhatsApp)',assinafy:'✍ Assinafy',smtp:'📧 SMTP'};
  const el = document.getElementById('circuitGrid');
  if (!el) return;
  el.innerHTML = apis.map(api => {
    const x = s[api] || {};
    const st = x.status || 'closed';
    const cor = st === 'open' ? 'var(--rose)' : st === 'half-open' ? 'var(--amber)' : 'var(--emerald)';
    const icon = st === 'open' ? '🔴' : st === 'half-open' ? '🟡' : '🟢';
    const rem = x.remaining ? `<div style="font-size:11.5px;color:var(--rose)">Reabre em ${x.remaining}s</div>` : '';
    return `<div class="card">
      <div class="ch" style="padding:14px 18px">
        <div class="cht">${labels[api]||api}</div>
        <span class="badge" style="background:${cor}22;color:${cor}">${icon} ${st.toUpperCase()}</span>
      </div>
      <div class="cb" style="padding:14px 18px;display:flex;flex-direction:column;gap:8px">
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:var(--t2)">Falhas consecutivas</span>
          <span style="font-weight:700;color:${x.failures>0?'var(--rose)':'var(--emerald)'}">${x.failures||0} / 3</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:var(--t2)">Fila de retry</span>
          <span style="font-weight:700;color:${x.queue_size>0?'var(--amber)':'var(--t3)'}">${x.queue_size||0} itens</span>
        </div>
        ${x.last_error ? `<div style="font-size:11.5px;color:var(--rose);word-break:break-word">Último erro: ${x.last_error.substring(0,80)}</div>` : ''}
        ${rem}
        <div style="display:flex;gap:8px;margin-top:4px">
          ${st!=='closed'?`<button class="bs2 btn bsm" onclick="resetCircuit('${api}')">⟳ Fechar Circuito</button>`:''}
          ${x.queue_size>0?`<button class="bg2 btn bsm" onclick="flushQueue('${api}')">🗑 Limpar Fila</button>`:''}
        </div>
      </div>
    </div>`;
  }).join('');
}

async function resetCircuit(apiName) {
  const r = await api_call('POST', '/circuit/' + apiName + '/reset');
  toast(r.success ? '✅ Circuito fechado!' : '❌ Erro.', !!r.success);
  loadCircuit();
}
async function flushQueue(apiName) {
  const r = await api_call('POST', '/circuit/' + apiName + '/flush');
  toast(r.success ? '✅ Fila limpa!' : '❌ Erro.', !!r.success);
  loadCircuit();
}
async function processQueue() {
  const r = await api_call('POST', '/circuit/process-queue');
  toast(r.success ? `✅ ${r.data?.processed||0} itens processados.` : '❌ Erro.', !!r.success);
  loadCircuit();
}

// Wrapper para não conflitar com função api() global
async function api_call(m, p, b) { return api(m, p, b); }

/* ── LICENÇA ── */
async function loadLicenca() {
  const d = await api('GET', '/license');
  const x = d.data || {};
  const el = document.getElementById('licInfo');
  if (!el) return;
  const cor = x.valid ? (x.mode === 'trial' ? 'var(--amber)' : 'var(--emerald)') : 'var(--rose)';
  const badge = x.valid
    ? (x.mode === 'trial' ? '⏳ TRIAL' : '✅ LICENCIADO')
    : '❌ INVÁLIDA / EXPIRADA';
  el.innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
      <div style="font-size:32px">${x.mode==='trial'?'⏳':x.mode==='licensed'?'🎫':'❌'}</div>
      <div><div style="font-size:16px;font-weight:700;color:${cor}">${badge}</div>
           <div style="font-size:12.5px;color:var(--t2);margin-top:2px">${x.mode==='trial'?'Período de avaliação':'Licença comercial'}</div></div>
    </div>
    ${x.mode==='trial'?`<div class="abanner abw" style="margin-bottom:12px"><span>⚠️</span><div class="abmsg"><strong>${x.days_left} dias restantes no trial</strong><small>Instale uma licença para continuar após o vencimento.</small></div></div>`:''}
    <div style="display:flex;flex-direction:column;gap:6px;font-size:13px">
      ${x.licensee?`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bs)"><span style="color:var(--t2)">Licenciado</span><strong>${x.licensee}</strong></div>`:''}
      ${x.email?`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bs)"><span style="color:var(--t2)">E-mail</span><strong>${x.email}</strong></div>`:''}
      ${x.domain?`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bs)"><span style="color:var(--t2)">Domínio</span><strong style="font-family:var(--mo)">${x.domain}</strong></div>`:''}
      <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bs)">
        <span style="color:var(--t2)">Expira em</span>
        <strong style="color:${x.expires_at==='lifetime'?'var(--emerald)':'var(--t1)'}">${x.expires_at==='lifetime'?'♾ Vitalícia':(x.expires_at?new Date(x.expires_at).toLocaleDateString('pt-BR'):'—')}</strong>
      </div>
      ${x.max_users?`<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bs)"><span style="color:var(--t2)">Máx. usuários</span><strong>${x.max_users}</strong></div>`:''}
      ${x.features?`<div style="display:flex;justify-content:space-between;padding:8px 0"><span style="color:var(--t2)">Módulos</span><strong style="font-size:12px">${x.features.join(', ')}</strong></div>`:''}
    </div>`;
}

async function instalarLic() {
  const token = document.getElementById('licToken').value.trim();
  if (!token) { toast('Cole o token de licença primeiro.', false); return; }
  const r = await api('POST', '/license/install', { token });
  if (r.success || r.data) { toast('✅ Licença ativada com sucesso!'); loadLicenca(); }
  else toast('❌ ' + (r.message || 'Token inválido.'), false);
}

async function revogarLic() {
  if (!confirm('Revogar a licença? O sistema voltará para o modo trial.')) return;
  const r = await api('POST', '/license/revoke');
  if (r.success) { toast('✅ Licença revogada. Modo trial ativado.'); loadLicenca(); }
  else toast('❌ Erro ao revogar.', false);
}

/* ── AUDIT LOG ── */
let auditMod = '', auditPg = 1;

async function loadAudit() {
  const date = document.getElementById('auditDate')?.value || new Date().toISOString().slice(0,10);
  let url = `/audit?page=${auditPg}`;
  if (auditMod) url += '&modulo=' + auditMod;
  const d = await api('GET', url);
  const logs = d.data || [];
  const icons = {create:'➕',update:'✏️',delete:'🗑',read:'👁',login:'🔑',logout:'🚪',install:'🎫',revoke:'⛔',error:'⚠️'};
  document.getElementById('tAudit').innerHTML = logs.length
    ? `<table><thead><tr><th>Data/Hora</th><th>Usuário</th><th>Ação</th><th>Módulo</th><th>ID</th><th>IP</th></tr></thead><tbody>
      ${logs.map(l=>`<tr>
        <td class="tdm">${fDT(l.created_at)}</td>
        <td>${l.usuario_nome||'Sistema'}</td>
        <td><span class="badge ${l.acao==='delete'?'br':l.acao==='create'?'bg':l.acao==='login'?'bb':'bgr'}">${icons[l.acao]||'•'} ${l.acao}</span></td>
        <td>${l.modulo||'—'}</td>
        <td class="tdm">${l.entidade_id||'—'}</td>
        <td style="font-size:11.5px;color:var(--t3)">${l.ip_address||'—'}</td>
      </tr>`).join('')}
      </tbody></table>`
    : '<div style="padding:32px;text-align:center;color:var(--t3)">Nenhum registro de auditoria para esta data.</div>';
  document.getElementById('pagAudit').innerHTML = `<button class="pagb" onclick="auditPg--;loadAudit()" ${auditPg<=1?'disabled':''}>←</button><span class="pagi">Pág. ${auditPg}</span><button class="pagb" onclick="auditPg++;loadAudit()" ${logs.length<20?'disabled':''}>→</button>`;
}

function filtAudit(mod, btn) {
  auditMod = mod; auditPg = 1;
  document.querySelectorAll('#view-audit .tab').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  loadAudit();
}

async function verificarAudit() {
  const date = document.getElementById('auditDate')?.value || new Date().toISOString().slice(0,10);
  const r = await api('GET', '/audit/verify?date=' + date);
  const x = r.data || {};
  const el = document.getElementById('auditInteg');
  if (!el) return;
  el.style.display = '';
  if (!x.file_exists) { el.className='badge bgr'; el.textContent='⚠️ Sem log para esta data'; return; }
  if (x.integrity === 'ok') { el.className='badge bg'; el.textContent='✅ Íntegro'; }
  else { el.className='badge br'; el.textContent=`❌ ${x.invalid_lines?.length} linha(s) comprometida(s)`; }
  toast(x.integrity === 'ok' ? '✅ Log íntegro!' : `❌ Integridade comprometida: ${x.invalid_lines?.length} linhas inválidas`, x.integrity === 'ok');
}

// Inicializa data de audit para hoje
(function(){ const el = document.getElementById('auditDate'); if(el) el.value = new Date().toISOString().slice(0,10); })();



function dlDoc(id) {
  const a = document.createElement('a');
  a.href = AB + '/api/ged/download/' + id + '?token=' + TK;
  a.target = '_blank';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

async function exportarDocPdf(id) {
  const a = document.createElement('a');
  a.href = AB + '/api/ged/download/' + id + '?token=' + TK;
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  setTimeout(() => document.body.removeChild(a), 1000);
  toast('✅ Download iniciado!');
}


/* ── CALENDÁRIO MINI ── */
// Cores por tipo de evento (mesmas do sistema)
const EV_CORES = {
  audiencia:'#3b82f6', pericia:'#14b8a6', reuniao:'#8b5cf6',
  prazo:'#f43f5e', followup:'#f59e0b', outro:'#4f5b72'
};
let calAno = new Date().getFullYear();
let calMes = new Date().getMonth(); // 0-based
let calEventos = {};

function calNav(dir) {
  calMes += dir;
  if (calMes > 11) { calMes = 0; calAno++; }
  if (calMes < 0)  { calMes = 11; calAno--; }
  renderCal();
  carregarCalMes();
}

async function carregarCalMes() {
  const mesStr = calAno + '-' + String(calMes+1).padStart(2,'0');
  const d = await api('GET', '/agenda/mes?mes=' + mesStr);
  const evs = d.data || [];
  calEventos = {};
  evs.forEach(e => {
    const dia = (e.inicio||'').slice(8,10);
    if (!calEventos[dia]) calEventos[dia] = [];
    calEventos[dia].push(e);
  });
  renderCal();
}

function renderCal() {
  const meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
  const lbl = document.getElementById('calLabel');
  if (lbl) lbl.textContent = meses[calMes] + ' ' + calAno;
  const grid = document.getElementById('calGrid');
  if (!grid) return;

  const hoje = new Date();
  const primeiroDia = new Date(calAno, calMes, 1).getDay(); // 0=dom
  const diasNoMes   = new Date(calAno, calMes+1, 0).getDate();

  let html = '';
  // Células vazias antes do primeiro dia
  for (let i = 0; i < primeiroDia; i++) {
    html += '<div></div>';
  }
  for (let d = 1; d <= diasNoMes; d++) {
    const dStr = String(d).padStart(2,'0');
    const evsDia = calEventos[dStr] || [];
    const isHoje = hoje.getFullYear()===calAno && hoje.getMonth()===calMes && hoje.getDate()===d;

    // Pega as cores dos tipos de eventos do dia
    const dots = evsDia.slice(0,3).map(e =>
      `<span style="width:5px;height:5px;border-radius:50%;background:${EV_CORES[e.tipo]||'var(--blue)'};display:inline-block;flex-shrink:0"></span>`
    ).join('');

    html += `<div onclick="calDiaClick(${d})" style="cursor:pointer;padding:4px;border-radius:7px;text-align:center;position:relative;
      background:${isHoje?'var(--blue)':evsDia.length?'var(--el)':'transparent'};
      color:${isHoje?'#fff':'var(--t1)'};
      border:${isHoje?'2px solid var(--blue)':'1px solid transparent'};
      transition:background .15s"
      onmouseover="this.style.background='${isHoje?'var(--blue)':'var(--hv)'}'"
      onmouseout="this.style.background='${isHoje?'var(--blue)':evsDia.length?'var(--el)':'transparent'}'">
      <div style="font-size:12.5px;font-weight:${isHoje?700:evsDia.length?600:400}">${d}</div>
      ${dots?`<div style="display:flex;justify-content:center;gap:2px;margin-top:2px">${dots}</div>`:''}
    </div>`;
  }
  grid.innerHTML = html;
}

function calDiaClick(dia) {
  const dStr = String(dia).padStart(2,'0');
  const evsDia = calEventos[dStr] || [];
  if (evsDia.length === 0) {
    // Pré-preenche o modal com a data clicada
    const dt = calAno + '-' + String(calMes+1).padStart(2,'0') + '-' + dStr + 'T09:00';
    const el = document.getElementById('evIni');
    if (el) el.value = dt;
    abrirM('eventoM');
  } else {
    // Mostra toast com os eventos do dia
    const lista = evsDia.map(e => e.titulo).join(', ');
    toast('📅 ' + dStr + ': ' + lista);
  }
}


function previewTemplate() {
  const html = getConteudo();
  if (!html) { toast('Escreva o conteúdo primeiro.', false); return; }
  const win = window.open('', '_blank', 'width=900,height=700');
  win.document.write(`<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <title>Preview — Themis</title>
    <style>body{font-family:'Times New Roman',serif;max-width:800px;margin:40px auto;padding:20px;line-height:1.6;color:#000;background:#fff}</style>
    </head><body>${html}</body></html>`);
  win.document.close();
}


/* ── PORTAL TOKENS ── */
let tkStakeId = null, tkTokenGerado = null, tkNomeCliente = '';

function abrirGerarToken(id, nome) {
  tkStakeId = id;
  tkNomeCliente = nome;
  tkTokenGerado = null;
  document.getElementById('tkNome').value = nome;
  document.getElementById('tkResultado').style.display = 'none';
  document.getElementById('btnGerarToken').style.display = '';
  abrirM('tokenM');
}

async function gerarToken() {
  if (!tkStakeId) return;
  const dias = parseInt(document.getElementById('tkDias').value);
  const btn = document.getElementById('btnGerarToken');
  btn.disabled = true; btn.textContent = '⏳ Gerando…';
  const r = await api('POST', '/portal-tokens', { stakeholder_id: tkStakeId, validade_dias: dias });
  btn.disabled = false; btn.textContent = '🔑 Gerar Novo Token';
  if (r.success || r.data) {
    tkTokenGerado = r.data?.token || r.token;
    const expAt = r.data?.expires_at || '';
    document.getElementById('tkToken').textContent = tkTokenGerado;
    document.getElementById('tkResultado').style.display = '';
    toast('✅ Token gerado! Envie ao cliente.');
  } else {
    toast('❌ ' + (r.message || 'Erro ao gerar token.'), false);
  }
}

function copiarToken() {
  if (!tkTokenGerado) return;
  navigator.clipboard.writeText(tkTokenGerado).then(() => toast('✅ Token copiado!')).catch(() => {
    const el = document.getElementById('tkToken');
    const range = document.createRange(); range.selectNodeContents(el);
    window.getSelection().removeAllRanges(); window.getSelection().addRange(range);
    toast('Selecione e copie manualmente (Ctrl+C)');
  });
}

function copiarMensagem() {
  if (!tkTokenGerado) return;
  const msg = `Olá, ${tkNomeCliente}!\n\n` +
    `Seu acesso ao Portal do Cliente foi configurado.\n\n` +
    `🔗 Acesse: ${AB}/portal\n` +
    `📋 CPF: (seu CPF com os dígitos)\n` +
    `🔑 Token: ${tkTokenGerado}\n\n` +
    `Com esse token você pode acompanhar seus processos, documentos e prazos.\n` +
    `Em caso de dúvidas, entre em contato conosco.`;
  navigator.clipboard.writeText(msg).then(() => toast('✅ Mensagem copiada!')).catch(() => toast('Copie manualmente', false));
}

/* ── LOGOUT ── */
function logout(){localStorage.removeItem('themis_token');localStorage.removeItem('themis_user');window.location.href=AB+'/login';}

/* ── INIT ── */
(async function(){
  let u=JSON.parse(localStorage.getItem('themis_user')||'{}');
  if(!u.nome){const r=await api('GET','/auth/me');if(r.data){localStorage.setItem('themis_user',JSON.stringify(r.data));u=r.data;currentUser=u;}}
  document.getElementById('sUser').textContent=u.nome||'Usuário';
  document.getElementById('sPerfil').textContent=u.perfil||'';
  document.getElementById('sAv').textContent=(u.nome||'U').charAt(0).toUpperCase();
  if(u.nome)document.getElementById('sNome').textContent=document.getElementById('tBc').textContent;
  document.getElementById('dDt').value=new Date().toISOString().slice(0,10);
  document.getElementById('iDt').value=new Date().toISOString().slice(0,16);
  await preStakes();
  // Restaurar última view ou verificar hash da URL
  var hash = window.location.hash.replace('#','');
  var savedView = localStorage.getItem('themis_view');
  var viewToLoad = hash || savedView || 'dashboard';
  if(hash) history.replaceState(null, '', window.location.pathname);
  go(viewToLoad, null);
})();
</script>
</body>
</html>
