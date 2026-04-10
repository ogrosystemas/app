<?php
/**
 * modules/tickets/layout.php — Configuração do layout dos tickets
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();
Auth::requireAdmin();

$_cor  = DB::cfg('cor_primaria',  '#f59e0b');
$_cor2 = DB::cfg('cor_secundaria','#d97706');
$msg_ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'ticket_largura_mm','ticket_mostrar_estabelecimento','ticket_logo',
        'ticket_mostrar_data','ticket_rodape',
        'ticket_colunas','ticket_borda_estilo','impressora_nome',
    ];
    foreach ($campos as $c) {
        if (isset($_POST[$c])) DB::setCfg($c, trim($_POST[$c]));
    }
    $msg_ok = 'Layout salvo.';
}

// Carregar config atual
$cfg = array_column(DB::all("SELECT chave,valor FROM configuracoes WHERE chave LIKE 'ticket_%'"), 'valor', 'chave');
$nome_est = DB::cfg('nome_estabelecimento', 'Bar System Pro');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Layout dos Tickets — Bar System Pro</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
<style>
:root{--amber:<?= $_cor ?>;--amber-dark:<?= $_cor2 ?>;}
.preview-wrap{background:#f5f5f5;padding:16px;border-radius:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start;justify-content:center;min-height:200px}
/* Ticket preview */
.tk-prev{background:#fff;font-family:'Courier New',monospace;font-size:9pt;color:#000;padding:5mm 4mm;border:1px solid #ddd;page-break-inside:avoid;transition:all .3s}
.tk-prev.borda-simples{border:1px solid #ccc}
.tk-prev.borda-dupla{border:3px double #333}
.tk-prev.borda-grossa{border:3px solid #000}
.tk-prev.borda-tracejada{border:2px dashed #666}
.tk-prev.borda-nenhuma{border:none;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.tk-prev .tp-est{text-align:center;font-weight:700;margin-bottom:3px}
.tk-prev .tp-linha{border-top:1px dashed #999;margin:3px 0}
.tk-prev .tp-prod{text-align:center;font-weight:700;line-height:1.3;margin:3px 0}
.tk-prev .tp-info{font-size:7pt;color:#555}
.tk-prev .tp-rodape{text-align:center;font-size:7pt;color:#888;margin-top:3px}
.range-val{font-weight:700;color:var(--amber);font-size:.95rem}
</style>
</head>
<body class="admin-body">
<?php include __DIR__.'/../../includes/nav.php'; ?>
<div class="admin-content">
<div class="page-header d-flex justify-content-between">
  <h4><i class="ph-bold ph-palette me-2"></i>Layout dos Tickets</h4>
  <a href="<?= BASE_URL ?>modules/tickets/index.php" class="btn btn-outline-secondary btn-sm">
    <i class="ph-bold ph-arrow-left me-1"></i>Voltar
  </a>
</div>

<?php if ($msg_ok): ?><div class="alert alert-success"><?= h($msg_ok) ?></div><?php endif; ?>

<div class="row g-3">
  <!-- Configurações -->
  <div class="col-lg-6">
    <form method="POST">
      <div class="admin-card mb-3">
        <div class="card-section-title"><i class="ph-bold ph-ruler me-2"></i>Dimensões e Layout</div>

        <div class="mb-3">
          <label class="form-label d-flex justify-content-between">
            Largura do ticket
            <span class="range-val" id="vLarg"><?= $cfg['ticket_largura_mm']??76 ?>mm</span>
          </label>
          <input type="range" name="ticket_largura_mm" class="form-range"
                 min="58" max="80" step="2"
                 value="<?= $cfg['ticket_largura_mm']??76 ?>"
                 oninput="document.getElementById('vLarg').textContent=this.value+'mm';atualizarPreview()">
          <div class="d-flex justify-content-between" style="font-size:.72rem;color:var(--text-muted)">
            <span>58mm (mini)</span><span>76mm (padrão)</span><span>80mm (largo)</span>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Colunas por página (impressão)</label>
          <select name="ticket_colunas" class="form-select" onchange="atualizarPreview()">
            <?php foreach ([1=>'1 coluna',2=>'2 colunas',3=>'3 colunas',4=>'4 colunas'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($cfg['ticket_colunas']??1)==$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Estilo da borda</label>
          <select name="ticket_borda_estilo" class="form-select" onchange="atualizarPreview()">
            <?php foreach (['simples'=>'Simples','dupla'=>'Dupla','grossa'=>'Grossa','tracejada'=>'Tracejada','nenhuma'=>'Sem borda'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($cfg['ticket_borda_estilo']??'simples')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label d-flex justify-content-between">
            Tamanho do código
            <span class="range-val" id="vCod"><?= $cfg['ticket_tamanho_codigo']??18 ?>pt</span>
          </label>
          <input type="range" name="ticket_tamanho_codigo" class="form-range"
                 min="12" max="28" step="1"
                 value="<?= $cfg['ticket_tamanho_codigo']??18 ?>"
                 oninput="document.getElementById('vCod').textContent=this.value+'pt';atualizarPreview()">
        </div>
      </div>

      <div class="admin-card mb-3">
        <div class="card-section-title"><i class="ph-bold ph-eye me-2"></i>Campos Visíveis</div>

        <?php
        $toggles = [
            'ticket_logo'                    => ['1', 'Logo do Estabelecimento'],
            'ticket_mostrar_estabelecimento' => ['1', 'Nome do Estabelecimento'],
            'ticket_mostrar_data'            => ['1', 'Data e Hora'],
        ];
        foreach ($toggles as $key => [$default, $label]):
            $val = $cfg[$key] ?? $default;
        ?>
        <div class="form-check form-switch mb-2">
          <input type="hidden" name="<?= $key ?>" value="0">
          <input type="checkbox" name="<?= $key ?>" class="form-check-input" value="1"
                 <?= $val==='1'?'checked':'' ?> onchange="atualizarPreview()">
          <label class="form-check-label"><?= $label ?></label>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="admin-card mb-3">
        <div class="card-section-title"><i class="ph-bold ph-text-t me-2"></i>Textos</div>
        <div class="mb-3">
          <label class="form-label">Mensagem de rodapé</label>
          <input type="text" name="ticket_rodape" class="form-control"
                 value="<?= h($cfg['ticket_rodape']??'Obrigado pela preferência!') ?>"
                 oninput="atualizarPreview()" maxlength="80"
                 placeholder="Ex: Obrigado pela preferência!">
        </div>
      </div>

      <button type="submit" class="btn btn-amber w-100 fw-bold btn-lg">
        <i class="ph-bold ph-check me-2"></i>Salvar Layout
      </button>
    </form>

    <div class="admin-card mt-3">
      <div class="card-section-title"><i class="ph-bold ph-printer me-2"></i>Destino da Impressão</div>
      <?php
        $mpToken  = DB::cfg('mp_access_token','');
        $mpDevice = DB::cfg('mp_device_id','');
        $temPoint = !empty($mpToken) && !empty($mpDevice);
      ?>
      <?php if ($temPoint): ?>
      <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem">
        <div style="font-weight:700;color:#22c55e;margin-bottom:4px"><i class="ph-bold ph-device-mobile"></i> Point Smart 2 configurada</div>
        <div style="color:var(--text-muted)">Tickets serão impressos automaticamente na maquininha após cada venda. Device: <code><?= h(substr($mpDevice, 0, 20)) ?>...</code></div>
      </div>
      <?php else: ?>
      <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem">
        <div style="font-weight:700;color:var(--amber);margin-bottom:4px"><i class="ph-bold ph-warning"></i> Point não configurada</div>
        <div style="color:var(--text-muted)">Configure em <a href="<?= BASE_URL ?>modules/configuracoes/index.php" style="color:var(--amber)">Configurações → Mercado Pago</a> para impressão automática. Usando impressora via rede abaixo.</div>
      </div>
      <?php endif; ?>
      <div class="card-section-title" style="margin-top:8px"><i class="ph-bold ph-wifi-high me-2"></i>Impressora via Rede (fallback)</div>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">IP da Impressora na Rede</label>
          <div class="input-group">
            <input type="text" name="impressora_ip" class="form-control font-mono"
                   value="<?= h(DB::cfg('impressora_ip','')) ?>"
                   placeholder="Ex: 192.168.1.100">
            <input type="number" name="impressora_porta" class="form-control font-mono"
                   style="max-width:90px"
                   value="<?= h(DB::cfg('impressora_porta','9100')) ?>"
                   placeholder="9100">
          </div>
          <small style="color:var(--text-muted)">
            Impressora conectada na rede Wi-Fi. Porta padrão ESC/POS = 9100.
          </small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Nome no QZ Tray <small style="color:var(--text-muted)">(alternativa ao IP)</small></label>
          <input type="text" name="impressora_nome" class="form-control font-mono"
                 value="<?= h(DB::cfg('impressora_nome','')) ?>"
                 placeholder="Ex: EPSON TM-T20III">
          <small style="color:var(--text-muted)">Nome exato como aparece no Windows/Android. Requer QZ Tray instalado.</small>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-outline-secondary flex-fill btn-sm">
            <i class="ph-bold ph-check me-1"></i>Salvar
          </button>
          <button type="button" class="btn btn-outline-secondary flex-fill btn-sm" onclick="testarImpressora()">
            <i class="ph-bold ph-plug me-1"></i>Testar Conexão
          </button>
        </div>
        <div id="testResult" style="margin-top:.5rem;font-size:.78rem"></div>
      </form>
    </div>
  </div>

  <!-- Preview ao vivo -->
  <div class="col-lg-6">
    <div class="admin-card" style="position:sticky;top:70px">
      <div class="card-section-title"><i class="ph-bold ph-eye me-2"></i>Preview ao Vivo</div>
      <div class="preview-wrap" id="previewWrap">
        <!-- Gerado por JS -->
      </div>
      <div class="mt-3 d-flex gap-2">
        <a href="<?= BASE_URL ?>modules/tickets/imprimir.php?demo=1" target="_blank"
           class="btn btn-outline-secondary btn-sm flex-fill">
          <i class="ph-bold ph-printer me-1"></i>Testar Impressão
        </a>
      </div>
    </div>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const NOME_EST = <?= json_encode($nome_est) ?>;

function lerCfg(){
  return {
    largura:    document.querySelector('[name=ticket_largura_mm]')?.value||76,
    colunas:    document.querySelector('[name=ticket_colunas]')?.value||1,
    borda:      document.querySelector('[name=ticket_borda_estilo]')?.value||'simples',
    mostrarLogo:document.querySelector('[name=ticket_logo]:checked'),
    mostrarEst: document.querySelector('[name=ticket_mostrar_estabelecimento]:checked'),
    mostrarData:document.querySelector('[name=ticket_mostrar_data]:checked'),
    rodape:     document.querySelector('[name=ticket_rodape]')?.value||'Obrigado pela preferência!',
  };
}

function gerarTicketHTML(cfg, produto, idx){
  const w = cfg.largura + 'mm';
  let html = `<div class="tk-prev borda-${cfg.borda}" style="width:${w};font-size:9pt">`;
  if(cfg.mostrarLogo) html += `<div class="tp-est" style="font-size:7pt;color:#888">[ Logo ]</div>`;
  if(cfg.mostrarEst)  html += `<div class="tp-est">${NOME_EST}</div>`;
  html += `<div class="tp-linha"></div>`;
  html += `<div class="tp-prod">${produto}</div>`;
  html += `<div class="tp-linha"></div>`;
  html += `<div class="tp-linha"></div>`;
  html += `<div class="tp-info">`;
  if(cfg.mostrarData) html += `<div>Data: ${new Date().toLocaleString('pt-BR')}</div>`;
  html += `</div>`;
  if(cfg.rodape){ html += `<div class="tp-linha"></div><div class="tp-rodape">${cfg.rodape}</div>`; }
  html += `</div>`;
  return html;
}

function atualizarPreview(){
  const cfg = lerCfg();
  const wrap = document.getElementById('previewWrap');
  const exemplos = [
    {produto:'Chopp 300ml', codigo:'TKT-A1B2C3'},
    {produto:'Heineken Lata', codigo:'TKT-D4E5F6'},
    {produto:'Caipirinha', codigo:'TKT-G7H8J9'},
  ];
  wrap.innerHTML = '';
  wrap.style.gridTemplateColumns = `repeat(${cfg.colunas}, 1fr)`;
  exemplos.forEach((ex,i)=>{
    wrap.insertAdjacentHTML('beforeend', gerarTicketHTML(cfg, ex.produto, i));
  });
}

// Inicializar preview
document.addEventListener('DOMContentLoaded', atualizarPreview);
async function testarImpressora() {
  var div = document.getElementById('testResult');
  if (div) div.innerHTML = '<span style="color:var(--text-muted)">Testando...</span>';
  try {
    var r = await fetch('<?= BASE_URL ?>api/imprimir.php?action=ping');
    var d = await r.json();
    if (div) div.innerHTML = d.success
      ? '<span style="color:#22c55e">✓ ' + d.message + '</span>'
      : '<span style="color:#ef4444">✗ ' + d.message + '</span>';
  } catch(e) {
    if (div) div.innerHTML = '<span style="color:#ef4444">✗ Erro: ' + e.message + '</span>';
  }
}
</script>
</body>
</html>
