<?php
require_once '../../config/config.php';
require_once '../../tcpdf/tcpdf.php';
checkAuth(['admin', 'gerente', 'caixa', 'vendedor']);

$query = "SELECT * FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1";
$caixa_atual = $db->query($query)->fetch(PDO::FETCH_ASSOC);

if (!$caixa_atual && $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: abrir_caixa.php'); exit;
}

// ── Processar venda ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_venda'])) {
    $cliente_id      = $_POST['cliente_id'] ?: null;
    $forma_pagamento = $_POST['forma_pagamento'];
    $subtotal        = str_replace(',', '.', $_POST['subtotal']);
    $desconto        = str_replace(',', '.', $_POST['desconto']);
    $total           = str_replace(',', '.', $_POST['total']);
    $itens           = json_decode($_POST['itens'], true);

    $db->beginTransaction();
    try {
        $numero_venda = 'VENDA-' . date('YmdHis');
        $stmt = $db->prepare("INSERT INTO vendas (numero_venda,cliente_id,subtotal,desconto,total,forma_pagamento,caixa_id,created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$numero_venda,$cliente_id,$subtotal,$desconto,$total,$forma_pagamento,$caixa_atual['id'],$_SESSION['usuario_id']]);
        $venda_id = $db->lastInsertId();

        foreach ($itens as $item) {
            $db->prepare("INSERT INTO venda_itens (venda_id,produto_id,quantidade,valor_unitario,total) VALUES (?,?,?,?,?)")
               ->execute([$venda_id,$item['id'],$item['quantidade'],$item['preco'],$item['quantidade']*$item['preco']]);
            $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?")
               ->execute([$item['quantidade'],$item['id']]);
            $db->prepare("INSERT INTO movimentacoes_estoque (produto_id,tipo,quantidade,motivo,documento,created_by) VALUES (?,'saida',?,'Venda PDV',?,?)")
               ->execute([$item['id'],$item['quantidade'],$numero_venda,$_SESSION['usuario_id']]);
        }

        $db->prepare("INSERT INTO caixa_movimentacoes (caixa_id,tipo,valor,descricao,venda_id) VALUES (?,'venda',?,?,?)")
           ->execute([$caixa_atual['id'],$total,"Venda: $numero_venda",$venda_id]);
        $db->prepare("UPDATE caixa SET total_vendas = total_vendas + ? WHERE id = ?")
           ->execute([$total,$caixa_atual['id']]);

        $db->commit();

        // Buscar novos estoques dos produtos vendidos para atualizar os cards no front
        $estoques = [];
        foreach ($itens as $item) {
            $stmt_est = $db->prepare("SELECT estoque_atual FROM produtos WHERE id = ?");
            $stmt_est->execute([$item['id']]);
            $row = $stmt_est->fetch(PDO::FETCH_ASSOC);
            $estoques[(string)$item['id']] = (float)($row['estoque_atual'] ?? 0);
        }

        echo json_encode(['success' => true, 'estoques' => $estoques, 'numero_venda' => $numero_venda]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

$produtos  = $db->query("SELECT id,nome,codigo_barras,preco_venda,estoque_atual FROM produtos WHERE estoque_atual > 0 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$clientes  = $db->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$saldo     = $caixa_atual ? $caixa_atual['saldo_inicial'] + $caixa_atual['total_vendas'] : 0;
// Mercado Pago
$mp_cfg    = [];
$_mpFile   = __DIR__ . '/../../config/mercadopago.php';
if (file_exists($_mpFile)) { $mp_cfg = include $_mpFile; }
$mp_token  = $mp_cfg['mp_access_token'] ?? MP_TOKEN ?? '';
$mp_device = $mp_cfg['mp_device_id']    ?? MP_DEVICE ?? '';
$mp_ativo  = !empty($mp_token);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">PDV <span style="color:var(--accent)">·</span> Ponto de Venda</div>
  <div class="topbar-actions">
    <span style="font-size:.82rem;color:var(--text-muted)">Caixa #<?= $caixa_atual['id'] ?></span>
    <span style="background:rgba(34,197,94,.15);color:#22c55e;border-radius:6px;padding:4px 10px;font-size:.82rem;font-weight:600">
      Saldo: R$ <?= number_format($saldo,2,',','.') ?>
    </span>
    <a href="fechar_caixa.php" style="background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.2);border-radius:6px;padding:6px 12px;font-size:.82rem;font-weight:600;text-decoration:none">
      <i class="ph-bold ph-lock"></i> Fechar Caixa
    </a>
  </div>
</header>

<main class="os-content" style="padding:20px 24px">
<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;height:calc(100vh - 120px)">

  <!-- ── Coluna Produtos ── -->
  <div style="display:flex;flex-direction:column;gap:16px;overflow:hidden">

    <!-- Busca -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:14px 18px">
      <div style="display:flex;gap:10px">
        <div style="position:relative;flex:1">
          <i class="ph-bold ph-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1rem"></i>
          <input type="text" id="buscaProduto"
                 style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:10px 14px 10px 38px;color:var(--text);font-family:var(--font-body);font-size:.9rem;outline:none"
                 placeholder="Buscar por nome ou código de barras..."
                 autocomplete="off">
        </div>
        <button onclick="buscarProdutos()"
                style="background:var(--accent);color:#000;border:none;border-radius:8px;padding:0 18px;font-weight:700;cursor:pointer;font-family:var(--font-body);font-size:.85rem;white-space:nowrap">
          <i class="ph-bold ph-magnifying-glass"></i> Buscar
        </button>
      </div>
    </div>

    <!-- Grid produtos -->
    <div style="flex:1;overflow-y:auto;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px">
      <div id="listaProdutos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        <?php foreach ($produtos as $p): ?>
        <div onclick="adicionarAoCarrinho(<?= $p['id'] ?>,'<?= addslashes($p['nome']) ?>',<?= $p['preco_venda'] ?>)"
             style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:14px;cursor:pointer;transition:all .2s"
             onmouseover="this.style.borderColor='var(--accent)';this.style.transform='translateY(-2px)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.transform=''"
             class="produto-card-pdv" data-nome="<?= strtolower($p['nome']) ?>" data-cod="<?= $p['codigo_barras'] ?>" data-id="<?= $p['id'] ?>">
          <div style="font-weight:600;font-size:.88rem;color:var(--text);margin-bottom:4px;line-height:1.3"><?= htmlspecialchars($p['nome']) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:8px">
            <?= $p['codigo_barras'] ? 'Cód: '.$p['codigo_barras'] : '' ?> · <span class="prod-estoque-info">Estoque: <?= $p['estoque_atual'] ?></span>
          </div>
          <div style="font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:800;color:var(--accent)">
            R$ <?= number_format($p['preco_venda'],2,',','.') ?>
          </div>
          <div style="margin-top:8px;text-align:right">
            <span style="background:var(--accent);color:#000;border-radius:6px;padding:3px 10px;font-size:.75rem;font-weight:700">+ Adicionar</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── Coluna Carrinho ── -->
  <div style="display:flex;flex-direction:column;gap:0;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden">

    <!-- Header carrinho -->
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--text)">
        <i class="ph-bold ph-shopping-cart" style="color:var(--accent)"></i> Pedido
      </span>
      <span id="badgeItens" style="background:var(--accent);color:#000;border-radius:20px;padding:2px 10px;font-size:.75rem;font-weight:700">0 itens</span>
    </div>

    <!-- Itens -->
    <div id="carrinho" style="flex:1;overflow-y:auto;padding:12px 16px;min-height:0">
      <p style="color:var(--text-muted);text-align:center;padding:20px 0;font-size:.875rem">Carrinho vazio</p>
    </div>

    <!-- Rodapé carrinho -->
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:12px">
      <!-- Cliente -->
      <select id="cliente_id" style="background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:8px 12px;color:var(--text);font-family:var(--font-body);font-size:.85rem;width:100%;outline:none">
        <option value="">👤 Consumidor Final</option>
        <?php foreach ($clientes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Desconto -->
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:.8rem;color:var(--text-muted);white-space:nowrap">Desconto R$</span>
        <input type="text" id="desconto" value="0,00"
               style="flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:var(--font-body);font-size:.85rem;outline:none;text-align:right"
               oninput="calcularTotal()">
      </div>

      <!-- Totais -->
      <div style="background:var(--bg-card2);border-radius:8px;padding:12px 16px">
        <div style="display:flex;justify-content:space-between;font-size:.8rem;color:var(--text-muted);margin-bottom:4px">
          <span>Subtotal</span><span>R$ <span id="subtotal">0,00</span></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:800;color:var(--accent)">
          <span>TOTAL</span><span>R$ <span id="total">0,00</span></span>
        </div>
      </div>

      <!-- Forma de pagamento -->
      <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">Forma de Pagamento</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">

        <!-- Dinheiro — sempre disponível -->
        <button onclick="selecionarPagamento('dinheiro')" id="btn-dinheiro"
                style="background:rgba(34,197,94,.15);color:#22c55e;border:2px solid #22c55e;border-radius:8px;padding:10px;font-family:var(--font-body);font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s">
          <i class="ph-bold ph-money"></i> Dinheiro
        </button>

        <!-- PIX — via terminal Point ou QR no PDV -->
        <button onclick="selecionarPagamento('pix')" id="btn-pix"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:10px;font-family:var(--font-body);font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-qr-code"></i> PIX
          <?php if (!$mp_ativo): ?>
          <div style="font-size:.6rem;opacity:.6;font-weight:500">MP não configurado</div>
          <?php endif; ?>
        </button>

        <!-- Crédito — via terminal Point -->
        <button onclick="selecionarPagamento('cartao_credito')" id="btn-cartao_credito"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:10px;font-family:var(--font-body);font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-credit-card"></i> Crédito
          <?php if (!$mp_ativo): ?>
          <div style="font-size:.6rem;opacity:.6;font-weight:500">MP não configurado</div>
          <?php endif; ?>
        </button>

        <!-- Débito — via terminal Point -->
        <button onclick="selecionarPagamento('cartao_debito')" id="btn-cartao_debito"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:10px;font-family:var(--font-body);font-weight:700;font-size:.82rem;cursor:pointer;transition:all .2s"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-credit-card"></i> Débito
          <?php if (!$mp_ativo): ?>
          <div style="font-size:.6rem;opacity:.6;font-weight:500">MP não configurado</div>
          <?php endif; ?>
        </button>

      </div>
      <input type="hidden" id="forma_pagamento" value="dinheiro">
      <!-- MP configurado? expor para JS -->
      <input type="hidden" id="mp_ativo" value="<?= $mp_ativo ? '1' : '0' ?>">

      <!-- Botão finalizar -->
      <button onclick="finalizarVenda()"
              style="background:var(--accent);color:#000;border:none;border-radius:10px;padding:14px;font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;cursor:pointer;width:100%;transition:all .2s"
              onmouseover="this.style.background='var(--accent-dark)'"
              onmouseout="this.style.background='var(--accent)'">
        <i class="ph-bold ph-check-circle"></i> Finalizar Venda
      </button>
    </div>
  </div>
</div>
</main>

<script>
var carrinho = [];
var formaPagamento = 'dinheiro';
var todosOsProdutos = <?= json_encode($produtos) ?>;

// ── Busca de produtos (local, sem AJAX) ──────────────────
document.getElementById('buscaProduto').addEventListener('input', function() {
  var q = this.value.toLowerCase().trim();
  var cards = document.querySelectorAll('.produto-card-pdv');
  cards.forEach(function(card) {
    var nome = card.getAttribute('data-nome') || '';
    var cod  = card.getAttribute('data-cod')  || '';
    card.style.display = (!q || nome.includes(q) || cod.includes(q)) ? '' : 'none';
  });
});

function buscarProdutos() {
  var q = document.getElementById('buscaProduto').value.toLowerCase().trim();
  var cards = document.querySelectorAll('.produto-card-pdv');
  cards.forEach(function(card) {
    var nome = card.getAttribute('data-nome') || '';
    var cod  = card.getAttribute('data-cod')  || '';
    card.style.display = (!q || nome.includes(q) || cod.includes(q)) ? '' : 'none';
  });
}

// ── Forma de pagamento ───────────────────────────────────
var mpAtivo = document.getElementById('mp_ativo')?.value === '1';

function selecionarPagamento(forma) {
  formaPagamento = forma;
  document.getElementById('forma_pagamento').value = forma;

  var cores = {
    'dinheiro':      ['rgba(34,197,94,.15)',  '#22c55e', '2px solid #22c55e'],
    'pix':           ['rgba(56,189,248,.15)', '#38bdf8', '2px solid #38bdf8'],
    'cartao_credito':['rgba(168,85,247,.15)', '#a855f7', '2px solid #a855f7'],
    'cartao_debito': ['rgba(245,158,11,.15)', '#f59e0b', '2px solid #f59e0b'],
  };

  ['dinheiro','pix','cartao_credito','cartao_debito'].forEach(function(b) {
    var el = document.getElementById('btn-'+b);
    if (!el) return;
    if (b === forma) {
      el.style.background = cores[b][0];
      el.style.color      = cores[b][1];
      el.style.border     = cores[b][2];
    } else {
      el.style.background = 'var(--bg-card2)';
      el.style.color      = 'var(--text-muted)';
      el.style.border     = '2px solid transparent';
    }
  });

  // Mostrar/ocultar seção de troco
  var sec = document.getElementById('trocoSection');
  if (sec) {
    sec.style.display = (forma === 'dinheiro') ? '' : 'none';
    if (forma === 'dinheiro') {
      var vr = document.getElementById('valorRecebido');
      if (vr) { vr.value = ''; }
      var td = document.getElementById('trocoDisplay');
      if (td) td.textContent = 'R$ 0,00';
    }
  }
}

// ── Carrinho ─────────────────────────────────────────────
function adicionarAoCarrinho(id, nome, preco) {
  var exist = carrinho.find(function(i){ return i.id === id; });
  if (exist) {
    exist.quantidade++;
    exist.total = exist.quantidade * exist.preco;
  } else {
    carrinho.push({ id:id, nome:nome, preco:parseFloat(preco), quantidade:1, total:parseFloat(preco) });
  }
  atualizarCarrinho();
}

function removerDoCarrinho(index) {
  carrinho.splice(index, 1);
  atualizarCarrinho();
}

function atualizarQuantidade(index, qtd) {
  if (qtd < 1) { removerDoCarrinho(index); return; }
  carrinho[index].quantidade = qtd;
  carrinho[index].total = qtd * carrinho[index].preco;
  atualizarCarrinho();
}

function atualizarCarrinho() {
  var el = document.getElementById('carrinho');
  var badge = document.getElementById('badgeItens');
  var total_itens = carrinho.reduce(function(s,i){ return s+i.quantidade; }, 0);
  badge.textContent = total_itens + (total_itens===1?' item':' itens');

  if (carrinho.length === 0) {
    el.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:20px 0;font-size:.875rem">Carrinho vazio</p>';
    document.getElementById('subtotal').textContent = '0,00';
    document.getElementById('total').textContent = '0,00';
    return;
  }

  var html = '';
  var subtotal = 0;
  carrinho.forEach(function(item, index) {
    subtotal += item.total;
    html += '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light)">' +
      '<div style="flex:1;min-width:0">' +
        '<div style="font-size:.82rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + item.nome + '</div>' +
        '<div style="font-size:.75rem;color:var(--text-muted)">R$ ' + item.preco.toFixed(2).replace('.',',') + ' × ' + item.quantidade + '</div>' +
      '</div>' +
      '<div style="display:flex;align-items:center;gap:4px;flex-shrink:0">' +
        '<button onclick="atualizarQuantidade('+index+','+(item.quantidade-1)+')" style="width:26px;height:26px;background:var(--bg-card2);border:1px solid var(--border);border-radius:6px;cursor:pointer;color:var(--text);font-size:.9rem">−</button>' +
        '<span style="font-weight:700;font-size:.85rem;color:var(--text);min-width:20px;text-align:center">' + item.quantidade + '</span>' +
        '<button onclick="atualizarQuantidade('+index+','+(item.quantidade+1)+')" style="width:26px;height:26px;background:var(--bg-card2);border:1px solid var(--border);border-radius:6px;cursor:pointer;color:var(--text);font-size:.9rem">+</button>' +
        '<button onclick="removerDoCarrinho('+index+')" style="width:26px;height:26px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:6px;cursor:pointer;color:#ef4444;font-size:.8rem">✕</button>' +
      '</div>' +
    '</div>';
  });
  el.innerHTML = html;

  document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.',',');
  calcularTotal(subtotal);
}

function calcularTotal(subtotalVal) {
  var sub = subtotalVal !== undefined ? subtotalVal :
    parseFloat((document.getElementById('subtotal').textContent || '0').replace(',','.')) || 0;
  var desc = parseFloat((document.getElementById('desconto').value || '0').replace(/\./g,'').replace(',','.')) || 0;
  var total = Math.max(0, sub - desc);
  document.getElementById('total').textContent = total.toFixed(2).replace('.',',');
}

// ── Finalizar venda ──────────────────────────────────────
function finalizarVenda() {
  if (carrinho.length === 0) {
    Swal.fire({ title:'Carrinho vazio!', text:'Adicione produtos antes de finalizar.', icon:'warning', confirmButtonColor:'#f59e0b' });
    return;
  }

  var total = document.getElementById('total').textContent;
  var forma = formaPagamento;
  var formasLabel = { dinheiro:'Dinheiro', pix:'PIX', cartao_credito:'Crédito (maquininha)', cartao_debito:'Débito (maquininha)' };

  // PIX ou Cartão sem MP configurado — avisar e não continuar
  if (forma !== 'dinheiro' && !mpAtivo) {
    Swal.fire({
      title: 'Mercado Pago não configurado',
      html: 'Para aceitar <strong>' + (formasLabel[forma]||forma) + '</strong>, configure o Mercado Pago em<br><strong>Admin → Mercado Pago</strong>.',
      icon: 'warning',
      confirmButtonColor: '#f59e0b',
      background: 'var(--bg-card)',
      color: 'var(--text)',
    });
    return;
  }

  Swal.fire({
    title: 'Confirmar Venda',
    html: '<div style="font-size:1.5rem;font-weight:800;color:#f59e0b;margin:8px 0">R$ ' + total + '</div>' +
          '<div style="color:#888;font-size:.875rem">Pagamento: ' + (formasLabel[forma]||forma) + '</div>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#f59e0b',
    confirmButtonText: 'Confirmar',
    cancelButtonText: 'Cancelar',
    background: 'var(--bg-card)',
    color: 'var(--text)',
  }).then(function(result) {
    if (!result.isConfirmed) return;

    if (forma === 'dinheiro') {
      // Dinheiro — registra direto
      processarVenda(null);

    } else if (forma === 'pix') {
      // PIX — aciona MP e aguarda webhook
      acionarPagamentoMP('pix', null);

    } else if (forma === 'cartao_credito') {
      // Crédito via terminal Point
      acionarPagamentoMP('point', 'credit_card');

    } else if (forma === 'cartao_debito') {
      // Débito via terminal Point
      acionarPagamentoMP('point', 'debit_card');
    }
  });
}

// ── Acionar MP (Point ou PIX) ─────────────────────────────
function acionarPagamentoMP(tipo, tipoCartao) {
  var totalStr = document.getElementById('total').textContent.replace(',','.');
  var valor    = parseFloat(totalStr) || 0;
  var nomeVenda = 'PDV-' + Date.now();

  // Primeiro salvar a venda no sistema (pendente)
  processarVenda(nomeVenda, function(vendaNumero) {
    // Depois acionar o terminal/PIX
    var payload = { tipo: tipo, valor: valor, external_reference: vendaNumero || nomeVenda };
    if (tipoCartao) payload.tipo_cartao = tipoCartao;

    // Mostrar loading
    Swal.fire({
      title: tipo === 'pix' ? 'Gerando PIX...' : 'Acionando terminal...',
      text: 'Aguarde...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: function() { Swal.showLoading(); },
      background: 'var(--bg-card)',
      color: 'var(--text)',
    });

    fetch('../../api/mp_payment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (!res.success) {
        Swal.fire({ title:'Erro no Mercado Pago', text: res.message, icon:'error',
          confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
        return;
      }

      if (res.tipo === 'pix_qr') {
        // Sem terminal — exibir QR Code na tela do PDV
        exibirQRCode(res.qr_code, res.qr_code_base64, valor);
      } else if (res.tipo === 'point_pix' || res.tipo === 'point_card') {
        // Terminal acionado — aguardar cliente pagar
        Swal.fire({
          title: res.tipo === 'point_pix' ? '📱 QR Code na maquininha!' : '💳 Terminal pronto!',
          html: '<div style="font-size:.9rem;color:var(--text-muted)">' + res.message + '</div>' +
                '<div style="margin-top:12px;font-size:.8rem;color:var(--text-muted)">A venda será confirmada automaticamente após o pagamento.</div>',
          icon: 'success',
          confirmButtonColor: '#f59e0b',
          confirmButtonText: 'OK',
          background: 'var(--bg-card)',
          color: 'var(--text)',
        });
      }
    })
    .catch(function() {
      Swal.fire({ title:'Erro de conexão', text:'Não foi possível contatar o Mercado Pago.', icon:'error',
        confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
    });
  });
}

// ── Exibir QR Code PIX (sem terminal) ────────────────────
function exibirQRCode(qrCode, qrB64, valor) {
  var imgHtml = qrB64
    ? '<img src="data:image/png;base64,' + qrB64 + '" style="width:180px;height:180px;margin:12px auto;display:block;border-radius:8px">'
    : '';
  var copyBtn = qrCode
    ? '<button onclick="navigator.clipboard.writeText('' + qrCode.replace(/'/g,"\'") + '').then(function(){Swal.showValidationMessage('Código copiado!')})" style="margin-top:8px;background:var(--accent);color:#000;border:none;border-radius:6px;padding:6px 14px;font-weight:700;cursor:pointer;font-size:.82rem">Copiar código PIX</button>'
    : '';

  Swal.fire({
    title: '📱 PIX — R$ ' + valor.toFixed(2).replace('.',','),
    html: imgHtml +
          '<div style="font-size:.8rem;color:var(--text-muted);margin-top:4px">Escaneie o QR Code com o app do banco</div>' +
          copyBtn,
    showCancelButton: false,
    confirmButtonColor: '#f59e0b',
    confirmButtonText: 'Pagamento realizado',
    background: 'var(--bg-card)',
    color: 'var(--text)',
  });
}

// ── Salvar venda no sistema ───────────────────────────────
function processarVenda(numeroExterno, callback) {
  var dados = {
    finalizar_venda: true,
    cliente_id:      document.getElementById('cliente_id').value,
    forma_pagamento: formaPagamento,
    subtotal:        document.getElementById('subtotal').textContent.replace(',','.'),
    desconto:        (document.getElementById('desconto').value||'0').replace(/\./g,'').replace(',','.'),
    total:           document.getElementById('total').textContent.replace(',','.'),
    itens:           JSON.stringify(carrinho),
  };

  var formData = new FormData();
  Object.keys(dados).forEach(function(k) { formData.append(k, dados[k]); });

  fetch('pdv.php', { method:'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) {
        atualizarEstoqueCards(res.estoques || {});
        carrinho = [];
        atualizarCarrinho();
        document.getElementById('desconto').value = '0,00';
        document.getElementById('buscaProduto').value = '';
        buscarProdutos();

        if (typeof callback === 'function') {
          // Chamado por PIX/cartão — deixa o callback tratar o feedback
          callback(res.numero_venda || '');
        } else {
          // Dinheiro — feedback direto
          Swal.fire({
            title: 'Venda Finalizada!',
            icon: 'success',
            confirmButtonColor: '#f59e0b',
            background: 'var(--bg-card)',
            color: 'var(--text)',
          });
        }
      } else {
        Swal.fire('Erro!', res.error || 'Erro ao finalizar venda.', 'error');
      }
    })
    .catch(function() {
      Swal.fire('Erro!', 'Erro na requisição.', 'error');
    });
}

// ── Atualizar estoque nos cards após venda ───────────────
function atualizarEstoqueCards(estoques) {
  // estoques = { produto_id: novo_estoque_atual, ... }
  document.querySelectorAll('.produto-card-pdv').forEach(function(card) {
    var pid = card.getAttribute('data-id');
    if (!pid || !(pid in estoques)) return;

    var novoEstoque = parseFloat(estoques[pid]);

    // Atualizar texto de estoque no card
    var infoDiv = card.querySelector('.prod-estoque-info');
    if (infoDiv) {
      infoDiv.textContent = 'Estoque: ' + novoEstoque;
    }

    // Se estoque zerou, esconder o card (produto sem estoque)
    if (novoEstoque <= 0) {
      card.style.display = 'none';
    }
  });
}

// Init
selecionarPagamento('dinheiro');
</script>

<?php include '../../includes/footer.php'; ?>
