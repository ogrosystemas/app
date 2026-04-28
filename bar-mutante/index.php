<?php
ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/DB.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/MercadoPago.php';

Auth::requireLogin();

// ── Configurações visuais ────────────────────────────────────────────────────
try {
    $cfgRows = DB::all("SELECT chave,valor FROM configuracoes WHERE chave IN
        ('nome_estabelecimento','logo_pdv','logo_login','tema','cor_primaria','cor_secundaria','mp_device_id')");
    $cfg = array_column($cfgRows, 'valor', 'chave');
} catch (\Throwable $e) { $cfg = []; }

$estabelecimento   = $cfg['nome_estabelecimento'] ?? 'Bar System Pro';
$logo_pdv_url      = !empty($cfg['logo_pdv'])    ? UPLOAD_URL . 'logos/' . $cfg['logo_pdv']    : '';
$logo_login_url    = !empty($cfg['logo_login'])   ? UPLOAD_URL . 'logos/' . $cfg['logo_login']  : '';
$cor_primaria      = $cfg['cor_primaria']          ?? '#f59e0b';
$cor_secundaria    = $cfg['cor_secundaria']         ?? '#d97706';
$smart2_terminal   = $cfg['mp_device_id']           ?? '';
$ps_tipo_pag       = $cfg['mp_tipo_pagamento']       ?? 'CREDIT_DEBIT_CARD';
$formas_permitidas = Auth::formasPermitidas();

// ── Caixa & stats ────────────────────────────────────────────────────────────
try {
    $caixa     = caixaAberto();
    $alertas   = alertasEstoque();
    $terminais = DB::all("SELECT * FROM mp_terminais WHERE ativo=1 ORDER BY nome");
    $stats     = $caixa ? [
        'vendas_n'   => DB::count('vendas', "caixa_id=? AND status='pago'", [$caixa['id']]),
        'vendas_val' => DB::row("SELECT COALESCE(SUM(total),0) as t FROM vendas WHERE caixa_id=? AND status='pago'", [$caixa['id']])['t'],
    ] : ['vendas_n' => 0, 'vendas_val' => 0];
} catch (\Throwable $e) {
    $caixa = null; $alertas = []; $terminais = []; $stats = ['vendas_n' => 0, 'vendas_val' => 0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-tema="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PDV — <?= h($estabelecimento) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>assets/css/pdv.css" rel="stylesheet">
<style>
:root {
    --amber: <?= h($cor_primaria) ?>;
    --amber-dark: <?= h($cor_secundaria) ?>;
    --amber-light: color-mix(in srgb, <?= h($cor_primaria) ?> 60%, white);
}
</style>
</head>
<body class="pdv-body">

<?php if (empty($caixa)): ?>
<!-- ===== TELA DE ABERTURA DE CAIXA ===== -->
<div class="abertura-overlay d-flex align-items-center justify-content-center min-vh-100">
  <div class="abertura-card">
    <div class="text-center mb-4">
      <?php if ($logo_login_url): ?>
        <img src="<?= h($logo_login_url) ?>" alt="Logo" style="max-height:90px;max-width:240px;object-fit:contain;margin-bottom:.75rem">
      <?php else: ?>
        <div class="abertura-icon"><i class="ph-bold ph-beer-bottle"></i></div>
      <?php endif; ?>
      <h2 class="fw-bold mt-2 mb-1" style="font-family:'Syne',sans-serif"><?= h($estabelecimento) ?></h2>
      <p class="text-muted">Abra o caixa para iniciar as vendas</p>
    </div>
    <form id="formAbrirCaixa" method="POST">
      <input type="hidden" name="action" value="abrir">
      <div id="errAbertura" class="alert alert-danger d-none py-2 small mb-3"></div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Nome do Operador</label>
        <input type="text" name="operador" class="form-control form-control-lg" required placeholder="Seu nome" autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Fundo de Caixa (R$)</label>
        <div class="input-group input-group-lg">
          <span class="input-group-text">R$</span>
          <input type="text" name="saldo_inicial" class="form-control money-input" value="0,00" required>
        </div>
      </div>
      <button type="submit" id="btnAbrirCaixa" class="btn btn-amber btn-lg w-100 fw-bold">
        <i class="ph-bold ph-lock-open me-2"></i>Abrir Caixa
      </button>
      <?php if (Auth::isAdmin()): ?>
      <a href="<?= BASE_URL ?>modules/relatorios/index.php" class="btn btn-outline-secondary w-100 mt-2">
        <i class="ph-bold ph-chart-bar me-1"></i>Ver Relatórios
      </a>
      <?php endif; ?>
    </form>
  </div>
</div>
<script>
(function() {
  var f = document.getElementById('formAbrirCaixa');
  if (!f) return;
  f.addEventListener('submit', async function(e) {
    e.preventDefault();
    var btn = document.getElementById('btnAbrirCaixa');
    var err = document.getElementById('errAbertura');
    btn.disabled = true;
    btn.innerHTML = 'Abrindo...';
    err.classList.add('d-none');
    try {
      var fd = new FormData(f);
      var r  = await fetch('<?= BASE_URL ?>api/caixa.php', { method: 'POST', headers: { 'X-CSRF-Token': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '') }, body: fd });
      var d  = await r.json();
      if (d.success) {
        window.location.reload();
      } else {
        err.textContent = d.message || 'Erro ao abrir caixa.';
        err.classList.remove('d-none');
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-bold ph-lock-open me-2"></i>Abrir Caixa';
      }
    } catch(ex) {
      err.textContent = 'Erro de conexao: ' + ex.message;
      err.classList.remove('d-none');
      btn.disabled = false;
      btn.innerHTML = '<i class="ph-bold ph-lock-open me-2"></i>Abrir Caixa';
    }
  });
})();
</script>

<?php else: ?>
<!-- ===== PDV PRINCIPAL ===== -->

<!-- Top Bar -->
<div class="pdv-topbar">

  <!-- Esquerda: apenas logo -->
  <div class="topbar-left">
    <div class="brand-logo">
      <?php if ($logo_pdv_url): ?>
        <img src="<?= h($logo_pdv_url) ?>" alt="Logo" style="height:38px;max-width:140px;object-fit:contain;border-radius:6px">
      <?php else: ?>
        <i class="ph-bold ph-beer-bottle" style="font-size:1.5rem"></i>
      <?php endif; ?>
    </div>
    <div style="line-height:1.2">
      <div class="fw-bold" style="font-family:'Syne',sans-serif;font-size:.95rem"><?= h($estabelecimento) ?></div>
      <div class="text-xs text-muted"><?= h(Auth::nome()) ?> · Caixa #<?= $caixa['id'] ?></div>
    </div>
  </div>

  <!-- Direita: vendas + total + botões -->
  <div class="topbar-right">
    <!-- Stats -->
    <div class="caixa-stat">
      <span class="stat-label">Vendas</span>
      <span class="stat-value" id="topVendas"><?= $stats['vendas_n'] ?></span>
    </div>
    <div class="caixa-stat">
      <span class="stat-label">Total</span>
      <span class="stat-value amber" id="topTotal"><?= moeda($stats['vendas_val']) ?></span>
    </div>

    <div class="topbar-divider"></div>

    <!-- Alertas estoque -->
    <?php if (!empty($alertas)): ?>
    <button class="topbar-btn alerta-btn" data-bs-toggle="modal" data-bs-target="#modalAlertas" title="Alertas de estoque">
      <i class="ph-bold ph-warning"></i>
      <span class="badge-count"><?= count($alertas) ?></span>
    </button>
    <?php endif; ?>

    <!-- Ações -->
    <button class="topbar-btn" onclick="abrirSangria()" title="Sangria / Suprimento">
      <i class="ph-bold ph-arrow-square-up"></i>
    </button>
    <button class="topbar-btn danger-btn" onclick="confirmarFecharCaixa()" title="Fechar Caixa">
      <i class="ph-bold ph-lock"></i>
    </button>

    <?php if (Auth::isAdmin()): ?>
    <div class="topbar-divider"></div>
    <button class="topbar-btn" onclick="window.location.href='<?= BASE_URL ?>modules/relatorios/index.php'" title="Relatórios">
      <i class="ph-bold ph-chart-bar"></i>
    </button>
    <button class="topbar-btn" onclick="window.location.href='<?= BASE_URL ?>modules/produtos/lista.php'" title="Produtos / Estoque">
      <i class="ph-bold ph-package"></i>
    </button>
    <button class="topbar-btn" onclick="window.location.href='<?= BASE_URL ?>modules/configuracoes/index.php'" title="Configurações">
      <i class="ph-bold ph-gear"></i>
    </button>
    <?php endif; ?>

    <div class="topbar-divider"></div>
    <a href="<?= BASE_URL ?>logout.php" class="topbar-btn" title="Sair" onclick="return confirm('Deseja sair do sistema?')">
      <i class="ph-bold ph-sign-out"></i>
    </a>
  </div>
</div>

<!-- Main PDV Layout -->
<div class="pdv-main">

  <!-- Grade de Produtos -->
  <div class="pdv-produtos-wrap">
    <div class="pdv-produtos" id="gridProdutos">
      <?php
      $produtos = DB::all("SELECT p.*, c.nome as cat_nome, c.cor as cat_cor, c.icone as cat_icone
          FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id
          WHERE p.ativo = 1 AND p.disponivel_pdv = 1
          ORDER BY p.destaque DESC, p.ordem_pdv, p.nome");

      foreach ($produtos as $prod):
          $tipos_com_estoque = ['unidade','chopp_lata','garrafa','chopp_barril','dose'];
          $controla_est  = in_array($prod['tipo'], $tipos_com_estoque);
          $sem_estoque   = $controla_est && (
              $prod['estoque_atual'] <= 0 ||
              ($prod['estoque_minimo'] > 0 && $prod['estoque_atual'] <= $prod['estoque_minimo'])
          );
          $estoque_baixo = false;
          $img_src       = $prod['imagem'] ? UPLOAD_URL . 'produtos/' . $prod['imagem'] : '';
          $icone_est = match($prod['tipo']) {
              'chopp_barril' => 'beer-bottle',
              'chopp_lata'   => 'wine',
              'garrafa'      => 'wine',
              'dose'         => 'tumbler',
              'drink'        => 'martini',
              'combo'        => 'fork-knife',
              default        => 'squares-four',
          };
          $un_est  = $prod['unidade_estoque'] ?? 'un';
          $qt_est  = $prod['estoque_atual'];
          $cls_est = $sem_estoque ? 'esgotado' : ($estoque_baixo ? 'baixo' : '');
      ?>
      <div class="produto-card <?= $sem_estoque ? 'esgotado' : '' ?> <?= $prod['destaque'] ? 'destaque' : '' ?>"
           data-id="<?= $prod['id'] ?>"
           data-nome="<?= h($prod['nome']) ?>"
           data-preco="<?= $prod['preco_venda'] ?>"
           data-tipo="<?= h($prod['tipo']) ?>"
           data-estoque="<?= $prod['estoque_atual'] ?>"
           data-minimo="<?= (int)$prod['estoque_minimo'] ?>"
           data-unidade="<?= h($prod['unidade_estoque'] ?? 'un') ?>"
           data-cat="<?= $prod['categoria_id'] ?>"
           onclick="<?= $sem_estoque ? 'alertaEsgotado()' : 'addCarrinho('.$prod['id'].')' ?>">

        <?php if ($prod['destaque']): ?>
        <div class="prod-badge destaque-badge"><i class="ph-bold ph-star"></i></div>
        <?php endif; ?>
        <?php if ($sem_estoque): ?>
        <div class="prod-badge esgotado-badge"><?= $prod['estoque_atual'] <= 0 ? 'Esgotado' : 'Estoque Mínimo' ?></div>
        <?php endif; ?>

        <div class="prod-img-wrap">
          <?php if ($img_src): ?>
            <img src="<?= h($img_src) ?>" alt="<?= h($prod['nome']) ?>" class="prod-img" loading="lazy">
          <?php else: ?>
            <div class="prod-img-placeholder" style="--cat-cor:<?= h($prod['cat_cor'] ?? '#f59e0b') ?>">
              <i class="ph-bold ph-<?= h($prod['cat_icone'] ?? 'beer-bottle') ?>" style="font-size:50px"></i>
            </div>
          <?php endif; ?>
        </div>

        <div class="prod-info">
          <div class="prod-nome"><?= h($prod['nome']) ?></div>
          <div class="prod-cat" style="color:<?= h($prod['cat_cor'] ?? '#f59e0b') ?>"><?= h($prod['cat_nome'] ?? '') ?></div>
          <div class="prod-preco"><?= moeda($prod['preco_venda']) ?></div>
          <div class="prod-estoque <?= $cls_est ?>">
            <i class="ph-bold ph-<?= $icone_est ?>" style="margin-right:3px"></i><?= number_format($qt_est, 0) ?> <?= h($un_est) ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (empty($produtos)): ?>
      <div class="no-produtos">
        <i class="ph-bold ph-package-open"></i>
        <p>Nenhum produto cadastrado.<br><a href="modules/produtos/form.php">Cadastrar produto</a></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Carrinho / Checkout -->
  <div class="pdv-carrinho">
    <!-- Header carrinho -->
    <div class="carrinho-header">
      <div class="d-flex justify-content-between align-items-center">
        <span class="fw-bold" style="font-family:'Syne',sans-serif">
          <i class="ph-bold ph-shopping-cart me-2 text-amber"></i>Pedido
        </span>
        <div class="d-flex gap-2 align-items-center">
          <span class="badge bg-amber text-dark" id="badgeItens">0</span>
          <button class="btn-icon-sm danger" onclick="limparCarrinho()" title="Limpar">
            <i class="ph-bold ph-trash"></i>
          </button>
        </div>
      </div>
      <div class="mt-2">
        <input type="text" id="mesaInput" class="form-control form-control-sm" placeholder="Mesa / Identificação (opcional)">
      </div>
    </div>

    <!-- Lista de itens -->
    <div class="carrinho-itens" id="carrinhoItens">
      <div class="carrinho-empty" id="carrinhoEmpty">
        <i class="ph-bold ph-shopping-cart"></i>
        <p>Selecione produtos<br>para iniciar o pedido</p>
      </div>
    </div>

    <!-- Totais -->
    <div class="carrinho-totais">
      <!-- Totais ocultos para cálculo (exibidos no trocoSection) -->
      <span id="totSubtotal" style="display:none">R$ 0,00</span>
      <input type="text" id="descontoInput" style="display:none" value="0,00" class="money-input">
      <div class="total-line">
        <span>TOTAL</span>
        <span id="totTotal" class="text-amber">R$ 0,00</span>
      </div>

      <!-- Troco (só aparece quando pagamento = dinheiro) -->
      <div id="trocoSection" style="display:none;margin-top:10px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px 14px">
        <div style="font-size:.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">
          <i class="ph-bold ph-money me-1" style="color:#22c55e"></i>Pagamento em Dinheiro
        </div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
          <label style="font-size:.8rem;color:var(--text-muted);white-space:nowrap">Valor recebido</label>
          <div class="input-group input-group-sm" style="flex:1">
            <span class="input-group-text py-0">R$</span>
            <input type="number" id="valorRecebido" class="form-control py-0 text-end"
                   placeholder="0,00" step="0.01" min="0" oninput="calcTroco()"
                   style="font-size:1.1rem;font-weight:700">
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:.85rem;font-weight:700;color:var(--text-muted)">Troco</span>
          <span id="trocoDisplay" style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:#22c55e">R$ 0,00</span>
        </div>
      </div>
    </div>

    <!-- Pagamento -->
    <div class="pagamento-section">
      <div class="pag-label">Forma de Pagamento</div>
      <div class="pag-grid">
        <?php if (Auth::podeDinheiro()): ?>
        <button class="pag-btn active" data-forma="dinheiro">
          <i class="ph-bold ph-money"></i><span>Dinheiro</span>
        </button>
        <?php endif; ?>
        <button class="pag-btn mp-btn" data-forma="mercadopago" id="btnMP">
          <i class="ph-bold ph-device-mobile"></i><span>Maquininha</span>
        </button>
        <?php if (Auth::isAdmin()): ?>
        <button class="pag-btn" data-forma="cortesia">
          <i class="ph-bold ph-gift"></i><span>Cortesia</span>
        </button>
        <?php endif; ?>
      </div>

      <!-- Sub-opções Mercado Pago -->
      <div id="mpSubOpcoes" class="d-none mt-2">
        <div class="pag-label" style="font-size:.72rem;margin-bottom:.35rem;color:var(--text-muted)">Modalidade na maquininha:</div>
        <div class="pag-grid" style="gap:.35rem">
          
          <button class="pag-sub-btn active" data-mp-tipo="CREDIT_CARD">
            <i class="ph-bold ph-credit-card" style="margin-right:4px"></i>Crédito
          </button>
          <button class="pag-sub-btn" data-mp-tipo="DEBIT_CARD">
            <i class="ph-bold ph-credit-card" style="margin-right:4px"></i>Débito
          </button>
          <button class="pag-sub-btn" data-mp-tipo="PIX">
            <i class="ph-bold ph-currency-circle-dollar me-1"></i>PIX
          </button>
        </div>
      </div>

      <!-- Terminal MP -->
      <div id="psTerminalWrap" class="ps-terminal-wrap d-none">
        <label class="text-xs text-muted mb-1">Terminal Mercado Pago</label>
        <select id="psTerminal" class="form-select form-select-sm">
          <option value="">— Selecionar terminal —</option>
          <?php foreach ($terminais as $t): ?>
          <option value="<?= h($t['device_id']) ?>"><?= h($t['nome']) ?> (<?= h($t['modelo'] ?? '') ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Botão finalizar -->
    <div class="carrinho-footer">
      <button class="btn-finalizar" id="btnFinalizar" onclick="finalizarVenda()" disabled>
        <i class="ph-bold ph-check-circle me-2"></i>Finalizar Venda
      </button>
    </div>
  </div>
</div>

<!-- ===== MODAIS ===== -->

<!-- Modal Alertas Estoque -->
<div class="modal fade" id="modalAlertas" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content dark-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title text-warning"><i class="ph-bold ph-warning me-2"></i>Alertas de Estoque</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php foreach ($alertas as $al): ?>
        <div class="alerta-item">
          <div>
            <div class="fw-semibold"><?= h($al['nome']) ?></div>
            <div class="text-muted text-xs"><?= h($al['tipo']) ?> | Mín: <?= number_format($al['estoque_minimo'], 0) ?> <?= h($al['unidade_estoque']) ?></div>
          </div>
          <div class="text-end">
            <div class="text-warning fw-bold"><?= number_format($al['estoque_atual'], 2, ',', '.') ?></div>
            <div class="text-xs text-muted"><?= h($al['unidade_estoque']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer border-0">
        <a href="modules/estoque/index.php" class="btn btn-outline-warning btn-sm">Ver Estoque Completo</a>
      </div>
    </div>
  </div>
</div>

<!-- Modal Sangria / Suprimento -->
<div class="modal fade" id="modalSangria" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content dark-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="titSangria">Sangria / Suprimento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="formSangria">
        <input type="hidden" name="action" value="sangria">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-danger flex-fill tipo-btn active-tipo" data-tipo="sangria" onclick="setTipo('sangria')">
                <i class="ph-bold ph-arrow-up me-1"></i>Sangria (Retirada)
              </button>
              <button type="button" class="btn btn-outline-success flex-fill tipo-btn" data-tipo="suprimento" onclick="setTipo('suprimento')">
                <i class="ph-bold ph-arrow-down me-1"></i>Suprimento (Entrada)
              </button>
            </div>
            <input type="hidden" id="tipoMovimento" name="tipo" value="sangria">
          </div>
          <div class="mb-3">
            <label class="form-label">Valor *</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor" class="form-control form-control-lg money-input" required placeholder="0,00">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Motivo</label>
            <input type="text" name="motivo" class="form-control" placeholder="Descreva o motivo">
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-amber fw-bold px-4">Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Mercado Pago Processando -->
<div class="modal fade" id="modalMercadoPago" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content dark-modal text-center">
      <div class="modal-body py-5">
        <div class="ps-loader" id="psLoader">
          <div class="ps-spinner"></div>
          <div class="ps-loader-title">Aguardando Pagamento</div>
          <div class="ps-loader-sub" id="psLoaderSub">Realize o pagamento na maquininha</div>
          <div class="ps-valor" id="psValorDisplay"></div>
        </div>
        <div id="psResultado" class="d-none">
          <div id="psIconResult" class="ps-result-icon"></div>
          <div id="psMsgResult" class="ps-result-msg"></div>
          <div id="psRetryBtns" class="mt-2 d-flex flex-wrap gap-1 justify-content-center"></div>
        </div>
        <button class="btn btn-outline-secondary btn-sm mt-3" id="btnCancelarMP" onclick="cancelarMercadoPago()">
          <i class="ph-bold ph-x" style="margin-right:4px"></i>Cancelar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Fechar Caixa -->
<div class="modal fade" id="modalFecharCaixa" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content dark-modal">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="ph-bold ph-lock me-2 text-amber"></i>Fechar Caixa</h5>
      </div>
      <div class="modal-body">
        <div class="row g-3 mb-3" id="resumoCaixa"></div>
        <div class="mb-3">
          <label class="form-label">Saldo Contado em Caixa (R$)</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text">R$</span>
            <input type="text" id="saldoContado" class="form-control money-input" placeholder="0,00" oninput="calcDiferenca()">
          </div>
        </div>
        <div id="difInfo" class="d-none alert"></div>
        <div class="mb-3">
          <label class="form-label">Observações</label>
          <textarea id="obsCaixa" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger fw-bold px-4" onclick="confirmarFechamento()">
          <i class="ph-bold ph-lock me-2"></i>Confirmar Fechamento
        </button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?><!-- fim caixa aberto -->

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- QZ Tray para impressão automática sem diálogo -->
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<script>
// ── Dados injetados do PHP (ANTES do pdv.js) ──────────────────────
const CAIXA_ID     = <?= $caixa ? (int)$caixa['id'] : 'null' ?>;
const BASE_URL     = '<?= BASE_URL ?>';
const TOTAL_ATUAL  = <?= (float)($stats['vendas_val'] ?? 0) ?>;
const N_ATUAL      = <?= (int)($stats['vendas_n'] ?? 0) ?>;
const FORMAS_OK    = <?= json_encode($formas_permitidas) ?>;
const SMART2_ID    = '<?= h($smart2_terminal) ?>';
const PS_TIPO_PAG  = '<?= h($ps_tipo_pag) ?>';
const USER_PERFIL  = '<?= h(Auth::perfil()) ?>';
const PRINTER_NAME = '<?= h(DB::cfg('impressora_nome', '')) ?>';
const PRINTER_IP   = '<?= h(DB::cfg('impressora_ip', '')) ?>';
const CSRF_TOKEN   = '<?= csrfToken() ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/pdv.js"></script>
</body>
</html>
