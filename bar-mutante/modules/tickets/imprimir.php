<?php
/**
 * modules/tickets/imprimir.php
 * Impressão de tickets com layout configurável
 * URL: imprimir.php?venda_id=X  OR  imprimir.php?demo=1
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/DB.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/Auth.php';
Auth::requireLogin();

$demo    = isset($_GET['demo']);
$vendaId = (int)($_GET['venda_id'] ?? 0);

if (!$demo && !$vendaId) die('venda_id obrigatório');

// Carregar configurações de layout
$lCfg = array_column(
    DB::all("SELECT chave,valor FROM configuracoes WHERE chave LIKE 'ticket_%'"),
    'valor','chave'
);
$largura   = (int)($lCfg['ticket_largura_mm'] ?? 76);
$colunas   = (int)($lCfg['ticket_colunas']    ?? 1);
$borda     = $lCfg['ticket_borda_estilo']       ?? 'simples';
$rodape    = $lCfg['ticket_rodape']             ?? 'Obrigado pela preferência!';
$mostEst   = ($lCfg['ticket_mostrar_estabelecimento'] ?? '1') === '1';
$mostLogo  = ($lCfg['ticket_logo'] ?? '1') === '1';
$logoUrl   = '';
if ($mostLogo) {
    $logoFile = DB::cfg('logo_login','');
    if ($logoFile) $logoUrl = UPLOAD_URL . 'logos/' . $logoFile;
}
$mostData  = ($lCfg['ticket_mostrar_data'] ?? '1') === '1';

$nome_est = DB::cfg('nome_estabelecimento', 'Bar System Pro');

// Dados a imprimir
if ($demo) {
    $venda   = ['numero'=>'DEMO-001','data_venda'=>date('Y-m-d H:i:s'),'operador'=>'Demonstração'];
    $tickets = [
        ['codigo'=>'TKT-A1B2C3','produto_nome'=>'Chopp 300ml','status'=>'pendente'],
        ['codigo'=>'TKT-D4E5F6','produto_nome'=>'Heineken Lata','status'=>'pendente'],
        ['codigo'=>'TKT-G7H8J9','produto_nome'=>'Caipirinha','status'=>'pendente'],
        ['codigo'=>'TKT-K0L1M2','produto_nome'=>'Combo Cervejeiro','status'=>'pendente'],
    ];
} else {
    $venda   = DB::row("SELECT v.*, cx.operador FROM vendas v LEFT JOIN caixas cx ON v.caixa_id=cx.id WHERE v.id=?", [$vendaId]);
    if (!$venda) die('Venda não encontrada');
    $tickets = DB::all("SELECT * FROM tickets WHERE venda_id=? AND status!='cancelado' ORDER BY produto_nome,id", [$vendaId]);
    if (empty($tickets)) die('Nenhum ticket para imprimir');
}

$data     = date('d/m/Y H:i', strtotime($venda['data_venda']));

// Border CSS
$bordaCSS = match($borda) {
    'dupla'     => 'border:3px double #333',
    'grossa'    => 'border:3px solid #000',
    'tracejada' => 'border:2px dashed #666',
    'nenhuma'   => 'border:none;box-shadow:0 1px 3px rgba(0,0,0,.2)',
    default     => 'border:1.5px solid #ccc',
};

$colWidth = match($colunas) {
    2 => '48%', 3 => '31%', 4 => '23%', default => '100%'
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Tickets — Venda #<?= h($venda['numero']) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#f0f0f0; font-family:'Courier New',Courier,monospace; }

/* Botões de ação (não imprime) */
.action-bar {
  position:fixed; top:0; left:0; right:0;
  background:#1e2330; padding:10px 16px;
  display:flex; gap:10px; align-items:center;
  z-index:100; box-shadow:0 2px 8px rgba(0,0,0,.3);
}
.btn-print { background:#f59e0b; color:#000; border:none; padding:8px 20px; border-radius:8px; font-weight:700; cursor:pointer; font-size:14px; }
.btn-close-bar { background:transparent; color:#8892a4; border:1px solid #2d3447; padding:8px 14px; border-radius:8px; cursor:pointer; font-size:13px; text-decoration:none; }
.info-bar { color:#8892a4; font-size:12px; margin-left:auto; }

/* Área de impressão */
.print-area {
  margin-top:55px;
  padding:10mm;
  display:flex;
  flex-wrap:wrap;
  gap:3mm;
  background:#f0f0f0;
}

/* Ticket individual */
.ticket {
  width: <?= $largura ?>mm;
  max-width: <?= $colWidth ?>;
  background:#fff;
  padding:4mm 5mm;
  <?= $bordaCSS ?>;
  border-radius:3px;
  font-family:'Courier New',Courier,monospace;
  font-size:9pt;
  color:#000;
  page-break-inside:avoid;
}
.t-est   { text-align:center; font-weight:700; font-size:10pt; margin-bottom:2mm; }
.t-linha { border-top:1px dashed #aaa; margin:2.5mm 0; }
.t-prod  { text-align:center; font-weight:700; font-size:11pt; line-height:1.3; padding:1mm 0; }
.t-info  { font-size:7pt; color:#555; line-height:1.6; }
.t-rodape{ text-align:center; font-size:7pt; color:#888; margin-top:2mm; line-height:1.5; }

/* Impressão */
@media print {
  body    { background:#fff; }
  .action-bar { display:none; }
  .print-area { margin-top:0; padding:0; background:#fff; }
  .ticket { break-inside:avoid; }
  @page   { margin:5mm; }
}
</style>
</head>
<body>

<!-- Barra de ação -->
<div class="action-bar">
  <button class="btn-print" onclick="window.print()">
    🖨️ Imprimir <?= count($tickets) ?> ticket(s)
  </button>
  <?php if (!$demo): ?>
  <a href="<?= BASE_URL ?>modules/tickets/index.php" class="btn-close-bar">← Voltar</a>
  <?php endif; ?>
  <span class="info-bar">
    Venda #<?= h($venda['numero']) ?> · <?= $data ?> · <?= count($tickets) ?> ticket(s)
    <?php if ($demo): ?> · <strong style="color:#f59e0b">DEMONSTRAÇÃO</strong><?php endif; ?>
  </span>
  <a href="<?= BASE_URL ?>modules/tickets/layout.php" class="btn-close-bar" title="Configurar layout">⚙️ Layout</a>
</div>

<!-- Tickets -->
<div class="print-area">
  <?php foreach ($tickets as $t): ?>
  <div class="ticket">
    <?php if ($mostLogo && $logoUrl): ?><div style="text-align:center;margin-bottom:2mm"><img src="<?= h($logoUrl) ?>" style="max-height:16mm;max-width:<?= $largura-6 ?>mm;object-fit:contain"></div><?php endif; ?>
    <?php if ($mostEst): ?><div class="t-est"><?= h($nome_est) ?></div><?php endif; ?>
    <div class="t-linha"></div>
    <div class="t-prod"><?= h($t['produto_nome']) ?></div>
    <div class="t-linha"></div>
    <div class="t-linha"></div>
    <div class="t-info">
      <?php if ($mostVenda): ?><div>Venda: #<?= h($venda['numero']) ?></div><?php endif; ?>
      <?php if ($mostData):  ?><div>Data: <?= $data ?></div><?php endif; ?>
      <?php if ($mostOper):  ?><div>Operador: <?= h($venda['operador']??'-') ?></div><?php endif; ?>
    </div>
    <?php if ($instrucao): ?>
    <div class="t-linha"></div>
    <div class="t-rodape"><?= h($instrucao) ?></div>
    <?php endif; ?>
    <?php if ($rodape): ?>
    <div class="t-rodape"><?= h($rodape) ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<script>
let _printAttempts = 0;
const _maxAttempts = 10;

function tentarImprimir() {
  _printAttempts++;
  try {
    window.print();
  } catch(e) {
    console.warn('Erro ao imprimir:', e);
    mostrarErroImpressora();
  }
}

function mostrarErroImpressora() {
  const bar = document.querySelector('.action-bar');
  if (!bar) return;
  const aviso = document.createElement('div');
  aviso.id = 'avisoImpressora';
  aviso.style.cssText = 'background:#ef4444;color:#fff;padding:8px 16px;border-radius:6px;font-weight:700;font-size:13px;display:flex;align-items:center;gap:8px';
  aviso.innerHTML = `⚠️ Verifique a impressora (papel, conexão)
    <button onclick="aguardarERetentar()" style="background:rgba(255,255,255,.25);border:none;color:#fff;padding:4px 10px;border-radius:4px;cursor:pointer;font-weight:700">
      🔄 Tentar novamente
    </button>`;
  const existente = document.getElementById('avisoImpressora');
  if (existente) existente.replaceWith(aviso);
  else bar.appendChild(aviso);
}

function aguardarERetentar() {
  const aviso = document.getElementById('avisoImpressora');
  if (aviso) aviso.innerHTML = '⏳ Aguardando impressora... <span id="countDown">10</span>s';
  let s = 10;
  const iv = setInterval(() => {
    s--;
    const el = document.getElementById('countDown');
    if (el) el.textContent = s;
    if (s <= 0) {
      clearInterval(iv);
      tentarImprimir();
    }
  }, 1000);
}

// Detectar cancelamento de impressão (papel acabou, etc.)
window.addEventListener('afterprint', () => {
  // Verificar se realmente imprimiu (heurística: se foi muito rápido, provavelmente falhou)
  // Mostrar opção de reimprimir
  const bar = document.querySelector('.action-bar');
  if (bar && !document.getElementById('reimprimirBtn')) {
    const btn = document.createElement('button');
    btn.id = 'reimprimirBtn';
    btn.style.cssText = 'background:rgba(245,158,11,.2);border:1px solid #f59e0b;color:#f59e0b;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px';
    btn.innerHTML = '🔄 Reimprimir (papel acabou?)';
    btn.onclick = () => { btn.remove(); tentarImprimir(); };
    bar.appendChild(btn);
  }
});

<?php if (!$demo): ?>
// Auto-print ao carregar
window.addEventListener('load', () => setTimeout(tentarImprimir, 600));
<?php else: ?>
// Demo: não auto-imprimir
<?php endif; ?>
</script>
</body>
</html>
