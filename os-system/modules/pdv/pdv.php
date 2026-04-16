<?php
require_once '../../config/config.php';
checkAuth(['admin', 'gerente', 'caixa', 'vendedor']);

$query = "SELECT * FROM caixa WHERE status = 'aberto' ORDER BY id DESC LIMIT 1";
$caixa_atual = $db->query($query)->fetch(PDO::FETCH_ASSOC);

if (!$caixa_atual && $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: abrir_caixa.php'); exit;
}

// ── Processar venda (AJAX POST) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venda'])) {
    header('Content-Type: application/json');
    $cliente_id      = $_POST['cliente_id'] ?: null;
    $forma_pagamento = $_POST['forma_pagamento'];
    $subtotal        = (float)str_replace(',', '.', $_POST['subtotal']);
    $desconto        = (float)str_replace(',', '.', $_POST['desconto']);
    $total           = (float)str_replace(',', '.', $_POST['total']);
    $itens           = json_decode($_POST['itens'], true);

    if (empty($itens)) {
        echo json_encode(['success' => false, 'error' => 'Nenhum item no carrinho.']); exit;
    }

    $db->beginTransaction();
    try {
        $numero_venda = 'VD' . date('ymdHis'); // 2+12 = 14 chars, fits VARCHAR(20)
        $stmt = $db->prepare("INSERT INTO vendas (numero_venda,cliente_id,subtotal,desconto,total,forma_pagamento,caixa_id,created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$numero_venda, $cliente_id, $subtotal, $desconto, $total, $forma_pagamento, $caixa_atual['id'], $_SESSION['usuario_id']]);
        $venda_id = $db->lastInsertId();

        foreach ($itens as $item) {
            $db->prepare("INSERT INTO venda_itens (venda_id,produto_id,quantidade,valor_unitario,total) VALUES (?,?,?,?,?)")
               ->execute([$venda_id, $item['id'], $item['quantidade'], $item['preco'], round($item['quantidade'] * $item['preco'], 2)]);
            $db->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?")
               ->execute([$item['quantidade'], $item['id']]);
            $db->prepare("INSERT INTO movimentacoes_estoque (produto_id,tipo,quantidade,motivo,documento,created_by) VALUES (?,'saida',?,'Venda PDV',?,?)")
               ->execute([$item['id'], $item['quantidade'], $numero_venda, $_SESSION['usuario_id']]);
        }

        $db->prepare("INSERT INTO caixa_movimentacoes (caixa_id,tipo,valor,descricao,venda_id) VALUES (?,'venda',?,?,?)")
           ->execute([$caixa_atual['id'], $total, "Venda: $numero_venda", $venda_id]);
        $db->prepare("UPDATE caixa SET total_vendas = total_vendas + ? WHERE id = ?")
           ->execute([$total, $caixa_atual['id']]);
        $db->commit();

        $estoques = [];
        foreach ($itens as $item) {
            $r = $db->prepare("SELECT estoque_atual FROM produtos WHERE id = ?");
            $r->execute([$item['id']]);
            $estoques[(string)$item['id']] = (float)($r->fetchColumn() ?? 0);
        }
        echo json_encode(['success' => true, 'estoques' => $estoques, 'numero_venda' => $numero_venda]);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ── Sangria / Suprimento ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mov_caixa'])) {
    header('Content-Type: application/json');
    csrfVerify();
    $tipo  = $_POST['tipo'];
    $valor = (float)str_replace(',', '.', $_POST['valor']);
    $desc  = trim($_POST['descricao'] ?? '');
    if (!in_array($tipo, ['sangria','suprimento']) || $valor <= 0) {
        echo json_encode(['success'=>false,'error'=>'Dados inválidos']); exit;
    }
    $db->prepare("INSERT INTO caixa_movimentacoes (caixa_id,tipo,valor,descricao) VALUES (?,?,?,?)")
       ->execute([$caixa_atual['id'], $tipo, $valor, $desc]);
    $col = $tipo === 'sangria' ? 'total_sangrias' : 'total_suprimentos';
    $db->prepare("UPDATE caixa SET $col = $col + ? WHERE id = ?")->execute([$valor, $caixa_atual['id']]);
    echo json_encode(['success'=>true]); exit;
}

// ── Dados ────────────────────────────────────────────────────
// Buscar TODOS os produtos ativos (para busca global)
// Tenta buscar com categorias (requer patch v2); fallback sem categorias
try {
    $todos_produtos = $db->query(
        "SELECT p.id, p.nome, p.codigo_barras, p.preco_venda, p.estoque_atual,
                COALESCE(p.categoria_id, 0) as categoria_id,
                COALESCE(p.exibir_pdv, 1) as exibir_pdv,
                COALESCE(c.nome,'') as categoria_nome,
                COALESCE(c.cor,'#94a3b8') as categoria_cor
         FROM produtos p
         LEFT JOIN categorias_produtos c ON c.id = p.categoria_id
         WHERE p.ativo = 1
         ORDER BY p.nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tabela categorias_produtos ou coluna exibir_pdv inexistente — fallback
    $todos_produtos = $db->query(
        "SELECT id, nome, codigo_barras, preco_venda, estoque_atual,
                0 as categoria_id, 1 as exibir_pdv,
                '' as categoria_nome, '#94a3b8' as categoria_cor
         FROM produtos
         ORDER BY nome"
    )->fetchAll(PDO::FETCH_ASSOC);
}

// Produtos exibidos no PDV (com estoque)
$produtos_pdv = array_filter($todos_produtos, fn($p) => (int)$p['exibir_pdv'] !== 0 && $p['estoque_atual'] > 0);

// Categorias que possuem produtos no PDV
try {
    $categorias = $db->query(
        "SELECT DISTINCT c.id, c.nome, c.cor
         FROM categorias_produtos c
         INNER JOIN produtos p ON p.categoria_id = c.id
         WHERE c.ativo = 1
         ORDER BY c.nome"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
}

$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$saldo    = $caixa_atual ? ($caixa_atual['saldo_inicial'] + $caixa_atual['total_vendas']) : 0;

// MP config
$mp_cfg    = [];
$_mpFile   = __DIR__ . '/../../config/mercadopago.php';
if (file_exists($_mpFile)) { $mp_cfg = include $_mpFile; }
$mp_token  = $mp_cfg['mp_access_token'] ?? '';
$mp_ativo  = !empty($mp_token);
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar" style="padding:10px 24px">
  <div class="topbar-title">PDV <span style="color:var(--accent)">·</span> Ponto de Venda</div>
  <div class="topbar-actions">
    <span style="font-size:.8rem;color:var(--text-muted)">Caixa #<?= $caixa_atual['id'] ?></span>
    <span style="background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.25);border-radius:8px;padding:4px 12px;font-size:.82rem;font-weight:700">
      R$ <?= number_format($saldo,2,',','.') ?>
    </span>
    <button onclick="abrirMovCaixa('sangria')" style="background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.2);border-radius:7px;padding:5px 12px;font-size:.8rem;font-weight:600;cursor:pointer">
      <i class="ph-bold ph-arrow-down"></i> Sangria
    </button>
    <button onclick="abrirMovCaixa('suprimento')" style="background:rgba(34,197,94,.12);color:#22c55e;border:1px solid rgba(34,197,94,.2);border-radius:7px;padding:5px 12px;font-size:.8rem;font-weight:600;cursor:pointer">
      <i class="ph-bold ph-arrow-up"></i> Suprimento
    </button>
    <a href="fechar_caixa.php" style="background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.2);border-radius:7px;padding:6px 14px;font-size:.82rem;font-weight:600;text-decoration:none">
      <i class="ph-bold ph-lock"></i> Fechar Caixa
    </a>
  </div>
</header>

<main class="os-content" style="padding:16px 20px;height:calc(100vh - 112px);overflow:hidden">
<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;height:100%">

  <!-- ══ COLUNA PRODUTOS ══════════════════════════════════════ -->
  <div style="display:flex;flex-direction:column;gap:10px;overflow:hidden;min-height:0">

    <!-- Busca + Filtro categoria -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:12px 16px;flex-shrink:0">
      <div style="display:flex;gap:10px;margin-bottom:10px">
        <div style="position:relative;flex:1">
          <i class="ph-bold ph-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:1rem;pointer-events:none"></i>
          <input type="text" id="buscaProduto"
                 style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:10px 14px 10px 38px;color:var(--text);font-family:var(--font-body);font-size:.9rem;outline:none;transition:border-color .15s"
                 placeholder="Buscar por nome ou código de barras (busca em todos os produtos)..."
                 autocomplete="off" spellcheck="false"
                 onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
        </div>
        <button onclick="limparBusca()"
                style="background:var(--bg-card2);border:1px solid var(--border);border-radius:8px;padding:0 14px;color:var(--text-muted);cursor:pointer;font-size:.82rem;white-space:nowrap"
                title="Limpar busca">
          <i class="ph-bold ph-x"></i>
        </button>
      </div>

      <!-- Filtros de categoria -->
      <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
        <span style="font-size:.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-right:2px">Categoria:</span>
        <button class="btn-cat active" data-cat="0"
                style="background:rgba(245,158,11,.2);color:var(--accent);border:1px solid rgba(245,158,11,.4);border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s"
                onclick="filtrarCategoria(0, this)">
          Todas
        </button>
        <?php foreach ($categorias as $cat): ?>
        <button class="btn-cat" data-cat="<?= $cat['id'] ?>"
                style="background:var(--bg-card2);color:var(--text-muted);border:1px solid var(--border);border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s"
                onclick="filtrarCategoria(<?= $cat['id'] ?>, this)">
          <?= htmlspecialchars($cat['nome']) ?>
        </button>
        <?php endforeach; ?>
        <button class="btn-cat" data-cat="-1"
                style="background:var(--bg-card2);color:var(--text-muted);border:1px solid var(--border);border-radius:20px;padding:3px 12px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s"
                onclick="filtrarCategoria(-1, this)">
          Sem categoria
        </button>
      </div>
    </div>

    <!-- Grid de produtos -->
    <div style="flex:1;overflow-y:auto;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:14px;min-height:0" id="gridWrapper">
      <div id="listaProdutos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px">

        <?php foreach ($produtos_pdv as $p): ?>
        <div class="produto-card-pdv"
             data-id="<?= $p['id'] ?>"
             data-nome="<?= strtolower(htmlspecialchars($p['nome'])) ?>"
             data-cod="<?= htmlspecialchars($p['codigo_barras'] ?? '') ?>"
             data-cat="<?= (int)$p['categoria_id'] ?>"
             data-preco="<?= (float)$p['preco_venda'] ?>"
             data-estoque="<?= (float)$p['estoque_atual'] ?>"
             style="background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:12px;cursor:pointer;transition:all .18s;user-select:none"
             onclick="adicionarAoCarrinho(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nome'], ENT_QUOTES)) ?>', <?= (float)$p['preco_venda'] ?>)"
             onmouseenter="this.style.borderColor='var(--accent)';this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 16px rgba(245,158,11,.15)'"
             onmouseleave="this.style.borderColor='var(--border)';this.style.transform='';this.style.boxShadow=''">
          <?php if ($p['categoria_nome']): ?>
          <div style="font-size:.65rem;font-weight:700;color:<?= htmlspecialchars($p['categoria_cor']) ?>;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">
            <?= htmlspecialchars($p['categoria_nome']) ?>
          </div>
          <?php endif; ?>
          <div style="font-weight:600;font-size:.86rem;color:var(--text);margin-bottom:4px;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= htmlspecialchars($p['nome']) ?></div>
          <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:8px">
            <?= $p['codigo_barras'] ? htmlspecialchars($p['codigo_barras']) : '—' ?> ·
            <span class="prod-estoque-info" data-id="<?= $p['id'] ?>">Est: <?= number_format($p['estoque_atual'],0) ?></span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between">
            <span style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:800;color:var(--accent)">
              R$ <?= number_format($p['preco_venda'],2,',','.') ?>
            </span>
            <span style="background:var(--accent);color:#000;border-radius:6px;padding:3px 9px;font-size:.72rem;font-weight:700;pointer-events:none">
              + Adicionar
            </span>
          </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($produtos_pdv)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">
          <i class="ph-bold ph-package" style="font-size:2rem;display:block;margin-bottom:8px"></i>
          Nenhum produto disponível para venda no PDV.
        </div>
        <?php endif; ?>
      </div>

      <!-- Resultado de busca global -->
      <div id="resultadoBusca" style="display:none;margin-top:12px">
        <div style="font-size:.75rem;font-weight:700;color:var(--accent);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;padding:6px 0;border-top:1px solid var(--border)">
          <i class="ph-bold ph-magnifying-glass"></i> Resultado da busca global (todos os produtos):
        </div>
        <div id="listaBusca" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px"></div>
      </div>

      <div id="semResultados" style="display:none;text-align:center;padding:32px;color:var(--text-muted)">
        <i class="ph-bold ph-magnifying-glass" style="font-size:1.8rem;display:block;margin-bottom:8px"></i>
        Nenhum produto encontrado para "<span id="termoBusca"></span>"
      </div>
    </div>
  </div>

  <!-- ══ COLUNA CARRINHO ══════════════════════════════════════ -->
  <div style="display:flex;flex-direction:column;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;height:100%;min-height:0">

    <!-- Header carrinho -->
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
      <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;color:var(--text)">
        <i class="ph-bold ph-shopping-cart" style="color:var(--accent)"></i> Pedido
      </span>
      <span id="badgeItens" style="background:var(--accent);color:#000;border-radius:20px;padding:2px 10px;font-size:.73rem;font-weight:700">0 itens</span>
    </div>

    <!-- Itens -->
    <div id="carrinho" style="flex:1;overflow-y:auto;padding:10px 14px;min-height:0">
      <p style="color:var(--text-muted);text-align:center;padding:24px 0;font-size:.875rem">Carrinho vazio</p>
    </div>

    <!-- Footer carrinho -->
    <div style="padding:14px 18px;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:10px;flex-shrink:0;background:var(--bg-card)">

      <!-- Cliente -->
      <select id="cliente_id" style="background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:7px 10px;color:var(--text);font-family:var(--font-body);font-size:.83rem;width:100%;outline:none">
        <option value="">👤 Consumidor Final</option>
        <?php foreach ($clientes as $c): ?>
        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Desconto -->
      <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:.78rem;color:var(--text-muted);white-space:nowrap">Desconto R$</span>
        <input type="text" id="desconto" value="0,00"
               style="flex:1;background:var(--bg-input);border:1px solid var(--border);border-radius:8px;padding:6px 10px;color:var(--text);font-family:var(--font-body);font-size:.85rem;outline:none;text-align:right"
               oninput="calcularTotal()">
      </div>

      <!-- Totais -->
      <div style="background:var(--bg-card2);border-radius:10px;padding:10px 14px">
        <div style="display:flex;justify-content:space-between;font-size:.78rem;color:var(--text-muted);margin-bottom:3px">
          <span>Subtotal</span><span>R$ <span id="subtotal">0,00</span></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;color:var(--accent)">
          <span>TOTAL</span><span>R$ <span id="total">0,00</span></span>
        </div>
      </div>

      <!-- Forma de pagamento -->
      <div style="font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em">Pagamento</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px">

        <button onclick="selecionarPagamento('dinheiro')" id="btn-dinheiro"
                style="background:rgba(34,197,94,.15);color:#22c55e;border:2px solid #22c55e;border-radius:8px;padding:9px 6px;font-family:var(--font-body);font-weight:700;font-size:.8rem;cursor:pointer;transition:all .15s;line-height:1.2">
          <i class="ph-bold ph-money"></i> Dinheiro
        </button>

        <button onclick="selecionarPagamento('pix')" id="btn-pix"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:9px 6px;font-family:var(--font-body);font-weight:700;font-size:.8rem;cursor:pointer;transition:all .15s;line-height:1.2"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-qr-code"></i> PIX<?php if (!$mp_ativo): ?><br><span style="font-size:.6rem;opacity:.6;font-weight:500">MP não config.</span><?php endif; ?>
        </button>

        <button onclick="selecionarPagamento('cartao_credito')" id="btn-cartao_credito"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:9px 6px;font-family:var(--font-body);font-weight:700;font-size:.8rem;cursor:pointer;transition:all .15s;line-height:1.2"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-credit-card"></i> Crédito<?php if (!$mp_ativo): ?><br><span style="font-size:.6rem;opacity:.6;font-weight:500">MP não config.</span><?php endif; ?>
        </button>

        <button onclick="selecionarPagamento('cartao_debito')" id="btn-cartao_debito"
                style="background:var(--bg-card2);color:var(--text-muted);border:2px solid transparent;border-radius:8px;padding:9px 6px;font-family:var(--font-body);font-weight:700;font-size:.8rem;cursor:pointer;transition:all .15s;line-height:1.2"
                <?= !$mp_ativo ? 'title="Configure o Mercado Pago em Admin → Mercado Pago"' : '' ?>>
          <i class="ph-bold ph-credit-card-back"></i> Débito<?php if (!$mp_ativo): ?><br><span style="font-size:.6rem;opacity:.6;font-weight:500">MP não config.</span><?php endif; ?>
        </button>

      </div>

      <!-- Troco (dinheiro) -->
      <div id="trocoSection" style="display:flex;gap:8px">
        <div style="flex:1">
          <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:3px">Valor recebido</div>
          <input type="number" id="valorRecebido" step="0.01" min="0"
                 style="width:100%;background:var(--bg-input);border:1px solid var(--border);border-radius:7px;padding:6px 8px;color:var(--text);font-family:var(--font-body);font-size:.85rem;outline:none"
                 oninput="calcularTroco()" placeholder="0,00">
        </div>
        <div style="flex:1">
          <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:3px">Troco</div>
          <div id="trocoDisplay" style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:7px;padding:6px 8px;font-weight:700;color:#22c55e;font-family:'Syne',sans-serif;font-size:.9rem">
            R$ 0,00
          </div>
        </div>
      </div>

      <input type="hidden" id="forma_pagamento" value="dinheiro">
      <input type="hidden" id="mp_ativo" value="<?= $mp_ativo ? '1' : '0' ?>">

      <!-- Finalizar -->
      <button id="btnFinalizar" onclick="finalizarVenda()"
              style="background:var(--accent);color:#000;border:none;border-radius:10px;padding:13px;font-family:'Syne',sans-serif;font-weight:800;font-size:1rem;cursor:pointer;width:100%;transition:all .2s;margin-top:2px"
              onmouseenter="this.style.background='var(--accent-dark)'"
              onmouseleave="this.style.background='var(--accent)'">
        <i class="ph-bold ph-check-circle"></i> Finalizar Venda
      </button>
    </div>
  </div>

</div>
</main>

<!-- Modal Sangria/Suprimento -->
<div id="modalMovCaixa" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:400px;max-width:95vw">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
      <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin:0" id="modalMovTitulo">Sangria</h5>
      <button onclick="fecharModalMov()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem"><i class="ph-bold ph-x"></i></button>
    </div>
    <div style="padding:20px 24px">
      <div class="os-form-group" style="margin-bottom:14px">
        <label class="os-label">Valor (R$)</label>
        <input type="number" id="movValor" class="os-input" step="0.01" min="0.01" required>
      </div>
      <div class="os-form-group" style="margin-bottom:16px">
        <label class="os-label">Descrição</label>
        <input type="text" id="movDescricao" class="os-input" placeholder="Ex: Pagamento fornecedor">
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button class="btn-os btn-os-ghost" onclick="fecharModalMov()">Cancelar</button>
        <button class="btn-os btn-os-primary" onclick="confirmarMovCaixa()"><i class="ph-bold ph-check"></i> Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script>
// ═══════════════════════════════════════════════════════
// PDV — JavaScript (carregado após DOM completo)
// ═══════════════════════════════════════════════════════
(function() {
'use strict';

// ── Estado ───────────────────────────────────────────────────
var carrinho       = [];
var formaPagamento = 'dinheiro';
var catAtual       = 0;   // 0 = todas, -1 = sem categoria, N = id da categoria
var modosBusca     = false; // true quando há busca textual ativa

// Todos os produtos (PHP → JS)
var TODOS_PRODUTOS = <?= json_encode(array_values($todos_produtos), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
var MP_ATIVO = document.getElementById('mp_ativo').value === '1';

// ── Busca ────────────────────────────────────────────────────
var buscaTimer;
document.getElementById('buscaProduto').addEventListener('input', function() {
    clearTimeout(buscaTimer);
    buscaTimer = setTimeout(function() { aplicarFiltros(); }, 200);
});

function limparBusca() {
    document.getElementById('buscaProduto').value = '';
    aplicarFiltros();
}
window.limparBusca = limparBusca;

// ── Filtro categoria ─────────────────────────────────────────
function filtrarCategoria(catId, btn) {
    catAtual = catId;
    // Atualiza visual dos botões
    document.querySelectorAll('.btn-cat').forEach(function(b) {
        b.style.background = 'var(--bg-card2)';
        b.style.color      = 'var(--text-muted)';
        b.style.borderColor = 'var(--border)';
    });
    btn.style.background   = 'rgba(245,158,11,.2)';
    btn.style.color        = 'var(--accent)';
    btn.style.borderColor  = 'rgba(245,158,11,.4)';
    aplicarFiltros();
}
window.filtrarCategoria = filtrarCategoria;

// ── Aplicar filtros combinados ────────────────────────────────
function aplicarFiltros() {
    var q = (document.getElementById('buscaProduto').value || '').toLowerCase().trim();
    var cards = document.querySelectorAll('#listaProdutos .produto-card-pdv');
    var resultadoDiv = document.getElementById('resultadoBusca');
    var semResultadosDiv = document.getElementById('semResultados');
    var listaBusca = document.getElementById('listaBusca');

    if (!q) {
        // SEM BUSCA: filtrar apenas por categoria nos cards do PDV
        resultadoDiv.style.display = 'none';
        semResultadosDiv.style.display = 'none';
        var algumVisivel = false;

        cards.forEach(function(card) {
            var cat = parseInt(card.dataset.cat) || 0;
            var visivel = (catAtual === 0) ||
                          (catAtual === -1 && !cat) ||
                          (catAtual > 0 && cat === catAtual);
            card.style.display = visivel ? '' : 'none';
            if (visivel) algumVisivel = true;
        });

        if (!algumVisivel && cards.length > 0) {
            semResultadosDiv.style.display = 'block';
            document.getElementById('termoBusca').textContent = 'categoria selecionada';
        }
        return;
    }

    // COM BUSCA: procurar em TODOS os produtos (ignora categoria e exibir_pdv)
    cards.forEach(function(card) { card.style.display = 'none'; }); // oculta grid normal
    listaBusca.innerHTML = '';
    document.getElementById('termoBusca').textContent = q;

    var encontrados = TODOS_PRODUTOS.filter(function(p) {
        return p.nome.toLowerCase().includes(q) ||
               (p.codigo_barras && p.codigo_barras.toLowerCase().includes(q));
    });

    if (encontrados.length === 0) {
        resultadoDiv.style.display = 'none';
        semResultadosDiv.style.display = 'block';
        return;
    }

    semResultadosDiv.style.display = 'none';
    resultadoDiv.style.display = 'block';

    encontrados.forEach(function(p) {
        var semEstoque = p.estoque_atual <= 0;
        var card = document.createElement('div');
        card.style.cssText = 'background:var(--bg-card2);border:1px solid var(--border);border-radius:10px;padding:12px;' +
            (semEstoque ? 'opacity:.45;' : 'cursor:pointer;') +
            'transition:all .18s;user-select:none';
        card.innerHTML =
            (p.categoria_nome ? '<div style="font-size:.65rem;font-weight:700;color:' + (p.categoria_cor||'#94a3b8') + ';text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">' + escHtml(p.categoria_nome) + '</div>' : '') +
            '<div style="font-weight:600;font-size:.86rem;color:var(--text);margin-bottom:4px;line-height:1.3">' + escHtml(p.nome) + '</div>' +
            '<div style="font-size:.7rem;color:var(--text-muted);margin-bottom:8px">' + (p.codigo_barras ? escHtml(p.codigo_barras) : '—') + ' · Est: ' + p.estoque_atual + '</div>' +
            '<div style="display:flex;align-items:center;justify-content:space-between">' +
              '<span style="font-family:\'Syne\',sans-serif;font-size:1rem;font-weight:800;color:var(--accent)">R$ ' + parseFloat(p.preco_venda).toFixed(2).replace('.',',') + '</span>' +
              (semEstoque
                ? '<span style="background:#ef4444;color:#fff;border-radius:6px;padding:3px 9px;font-size:.72rem;font-weight:700">Sem estoque</span>'
                : '<span style="background:var(--accent);color:#000;border-radius:6px;padding:3px 9px;font-size:.72rem;font-weight:700;pointer-events:none">+ Adicionar</span>') +
            '</div>';

        if (!semEstoque) {
            card.addEventListener('click', function() {
                adicionarAoCarrinho(p.id, p.nome, parseFloat(p.preco_venda));
            });
            card.addEventListener('mouseenter', function() {
                this.style.borderColor = 'var(--accent)';
                this.style.transform = 'translateY(-2px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.borderColor = 'var(--border)';
                this.style.transform = '';
            });
        }
        listaBusca.appendChild(card);
    });
}

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ── Carrinho ─────────────────────────────────────────────────
function adicionarAoCarrinho(id, nome, preco) {
    var exist = carrinho.find(function(i) { return i.id === id; });
    if (exist) {
        exist.quantidade++;
        exist.total = exist.quantidade * exist.preco;
    } else {
        carrinho.push({ id: id, nome: nome, preco: parseFloat(preco), quantidade: 1, total: parseFloat(preco) });
    }
    atualizarCarrinho();
    // Flash de feedback no badge
    var badge = document.getElementById('badgeItens');
    badge.style.transform = 'scale(1.35)';
    setTimeout(function() { badge.style.transform = ''; }, 200);
}
window.adicionarAoCarrinho = adicionarAoCarrinho;

function removerDoCarrinho(index) {
    carrinho.splice(index, 1);
    atualizarCarrinho();
}
window.removerDoCarrinho = removerDoCarrinho;

function atualizarQuantidade(index, qtd) {
    if (qtd < 1) { removerDoCarrinho(index); return; }
    carrinho[index].quantidade = qtd;
    carrinho[index].total = qtd * carrinho[index].preco;
    atualizarCarrinho();
}
window.atualizarQuantidade = atualizarQuantidade;

function atualizarCarrinho() {
    var el    = document.getElementById('carrinho');
    var badge = document.getElementById('badgeItens');
    var total_itens = carrinho.reduce(function(s,i){ return s + i.quantidade; }, 0);
    badge.textContent = total_itens + (total_itens === 1 ? ' item' : ' itens');
    badge.style.transition = 'transform .15s';

    if (carrinho.length === 0) {
        el.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:24px 0;font-size:.875rem">Carrinho vazio</p>';
        document.getElementById('subtotal').textContent = '0,00';
        document.getElementById('total').textContent    = '0,00';
        return;
    }

    var html     = '';
    var subtotal = 0;
    carrinho.forEach(function(item, index) {
        subtotal += item.total;
        html += '<div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--border-light)">' +
          '<div style="flex:1;min-width:0">' +
            '<div style="font-size:.81rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escHtml(item.nome) + '</div>' +
            '<div style="font-size:.73rem;color:var(--text-muted)">R$ ' + item.preco.toFixed(2).replace('.',',') + ' × ' + item.quantidade + '</div>' +
          '</div>' +
          '<div style="display:flex;align-items:center;gap:3px;flex-shrink:0">' +
            '<button onclick="atualizarQuantidade(' + index + ',' + (item.quantidade-1) + ')" style="width:24px;height:24px;background:var(--bg-card2);border:1px solid var(--border);border-radius:5px;cursor:pointer;color:var(--text);font-size:.9rem;display:flex;align-items:center;justify-content:center">−</button>' +
            '<span style="font-weight:700;font-size:.85rem;color:var(--text);min-width:18px;text-align:center">' + item.quantidade + '</span>' +
            '<button onclick="atualizarQuantidade(' + index + ',' + (item.quantidade+1) + ')" style="width:24px;height:24px;background:var(--bg-card2);border:1px solid var(--border);border-radius:5px;cursor:pointer;color:var(--text);font-size:.9rem;display:flex;align-items:center;justify-content:center">+</button>' +
            '<button onclick="removerDoCarrinho(' + index + ')" style="width:24px;height:24px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:5px;cursor:pointer;color:#ef4444;font-size:.8rem;display:flex;align-items:center;justify-content:center">✕</button>' +
            '<span style="font-weight:700;font-size:.85rem;color:var(--accent);min-width:54px;text-align:right">R$ ' + item.total.toFixed(2).replace('.',',') + '</span>' +
          '</div>' +
        '</div>';
    });
    el.innerHTML = html;

    document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.',',');
    calcularTotal(subtotal);
}

function calcularTotal(subtotalVal) {
    var sub  = (subtotalVal !== undefined) ? subtotalVal :
               parseFloat((document.getElementById('subtotal').textContent||'0').replace(',','.')) || 0;
    var desc = parseFloat((document.getElementById('desconto').value||'0').replace(/\./g,'').replace(',','.')) || 0;
    var tot  = Math.max(0, sub - desc);
    document.getElementById('total').textContent = tot.toFixed(2).replace('.',',');
    calcularTroco();
}
window.calcularTotal = calcularTotal;

function calcularTroco() {
    if (formaPagamento !== 'dinheiro') return;
    var total    = parseFloat((document.getElementById('total').textContent||'0').replace(',','.')) || 0;
    var recebido = parseFloat(document.getElementById('valorRecebido').value||0) || 0;
    var troco    = Math.max(0, recebido - total);
    document.getElementById('trocoDisplay').textContent = 'R$ ' + troco.toFixed(2).replace('.',',');
    document.getElementById('trocoDisplay').style.color = troco > 0 ? '#22c55e' : 'var(--text-muted)';
}
window.calcularTroco = calcularTroco;

// ── Forma de pagamento ────────────────────────────────────────
var CORES = {
    dinheiro:       ['rgba(34,197,94,.15)',  '#22c55e', '2px solid #22c55e'],
    pix:            ['rgba(56,189,248,.15)', '#38bdf8', '2px solid #38bdf8'],
    cartao_credito: ['rgba(168,85,247,.15)', '#a855f7', '2px solid #a855f7'],
    cartao_debito:  ['rgba(245,158,11,.15)', '#f59e0b', '2px solid #f59e0b'],
};

function selecionarPagamento(forma) {
    formaPagamento = forma;
    document.getElementById('forma_pagamento').value = forma;

    Object.keys(CORES).forEach(function(b) {
        var el = document.getElementById('btn-' + b);
        if (!el) return;
        if (b === forma) {
            el.style.background   = CORES[b][0];
            el.style.color        = CORES[b][1];
            el.style.border       = CORES[b][2];
        } else {
            el.style.background   = 'var(--bg-card2)';
            el.style.color        = 'var(--text-muted)';
            el.style.border       = '2px solid transparent';
        }
    });

    var sec = document.getElementById('trocoSection');
    if (sec) sec.style.display = (forma === 'dinheiro') ? 'flex' : 'none';
}
window.selecionarPagamento = selecionarPagamento;

// ── Finalizar venda ──────────────────────────────────────────
function finalizarVenda() {
    if (carrinho.length === 0) {
        Swal.fire({ title:'Carrinho vazio!', text:'Adicione produtos antes de finalizar.', icon:'warning', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
        return;
    }

    var total = parseFloat((document.getElementById('total').textContent||'0').replace(',','.')) || 0;
    var forma = formaPagamento;
    var formasLabel = { dinheiro:'Dinheiro', pix:'PIX', cartao_credito:'Crédito', cartao_debito:'Débito' };

    if (forma !== 'dinheiro' && !MP_ATIVO) {
        Swal.fire({ title:'Mercado Pago não configurado', html:'Configure em <strong>Admin → Mercado Pago</strong> para usar ' + (formasLabel[forma]||forma) + '.', icon:'warning', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
        return;
    }

    Swal.fire({
        title: 'Confirmar Venda',
        html: '<div style="font-size:1.6rem;font-weight:800;color:#f59e0b;margin:8px 0">R$ ' + total.toFixed(2).replace('.',',') + '</div>' +
              '<div style="color:var(--text-muted);font-size:.875rem">Pagamento: ' + (formasLabel[forma]||forma) + '</div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor:  '#64748b',
        confirmButtonText:  'Sim, finalizar!',
        cancelButtonText:   'Cancelar',
        background: 'var(--bg-card)',
        color: 'var(--text)',
    }).then(function(result) {
        if (!result.isConfirmed) return;

        var btn = document.getElementById('btnFinalizar');
        btn.disabled = true;
        btn.textContent = 'Processando...';

        var subtotal = parseFloat((document.getElementById('subtotal').textContent||'0').replace(',','.')) || 0;
        var desconto = parseFloat((document.getElementById('desconto').value||'0').replace(/\./g,'').replace(',','.')) || 0;

        var form = new FormData();
        form.append('finalizar_venda',   '1');
        form.append('cliente_id',        document.getElementById('cliente_id').value);
        form.append('forma_pagamento',   forma);
        form.append('subtotal',          subtotal.toFixed(2));
        form.append('desconto',          desconto.toFixed(2));
        form.append('total',             total.toFixed(2));
        form.append('itens',             JSON.stringify(carrinho.map(function(i){ return { id:i.id, nome:i.nome, preco:i.preco, quantidade:i.quantidade }; })));

        fetch(window.location.href, { method:'POST', body:form })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Atualizar estoques nos cards
                if (data.estoques) {
                    Object.keys(data.estoques).forEach(function(pid) {
                        var novoEst = data.estoques[pid];
                        document.querySelectorAll('.prod-estoque-info[data-id="'+pid+'"]').forEach(function(el) {
                            el.textContent = 'Est: ' + novoEst;
                        });
                        if (novoEst <= 0) {
                            document.querySelectorAll('.produto-card-pdv[data-id="'+pid+'"]').forEach(function(c) {
                                c.style.display = 'none';
                            });
                        }
                    });
                }
                carrinho = [];
                document.getElementById('desconto').value = '0,00';
                document.getElementById('valorRecebido').value = '';
                document.getElementById('trocoDisplay').textContent = 'R$ 0,00';
                document.getElementById('cliente_id').value = '';
                atualizarCarrinho();
                Swal.fire({
                    title: '✓ Venda Finalizada!',
                    html: '<div style="font-family:\'Syne\',sans-serif;font-size:1.4rem;font-weight:800;color:#f59e0b">' + (data.numero_venda||'') + '</div>' +
                          '<div style="margin-top:6px;color:var(--text-muted)">R$ ' + total.toFixed(2).replace('.',',') + ' — ' + (formasLabel[forma]||forma) + '</div>',
                    icon: 'success',
                    confirmButtonColor: '#f59e0b',
                    background: 'var(--bg-card)',
                    color: 'var(--text)',
                    timer: 3000,
                    timerProgressBar: true,
                });
            } else {
                Swal.fire({ title:'Erro!', text: data.error || 'Erro ao finalizar venda.', icon:'error', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
            }
        })
        .catch(function(e) {
            Swal.fire({ title:'Erro de conexão', text:'Não foi possível processar a venda.', icon:'error', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph-bold ph-check-circle"></i> Finalizar Venda';
        });
    });
}
window.finalizarVenda = finalizarVenda;

// ── Movimentações de caixa (sangria/suprimento) ──────────────
var movTipoAtual = 'sangria';

function abrirMovCaixa(tipo) {
    movTipoAtual = tipo;
    document.getElementById('modalMovTitulo').textContent = tipo === 'sangria' ? 'Sangria de Caixa' : 'Suprimento de Caixa';
    document.getElementById('movValor').value = '';
    document.getElementById('movDescricao').value = '';
    document.getElementById('modalMovCaixa').style.display = 'flex';
}
window.abrirMovCaixa = abrirMovCaixa;

function fecharModalMov() { document.getElementById('modalMovCaixa').style.display = 'none'; }
window.fecharModalMov = fecharModalMov;

function confirmarMovCaixa() {
    var valor = parseFloat(document.getElementById('movValor').value) || 0;
    var desc  = document.getElementById('movDescricao').value.trim();
    if (valor <= 0) { Swal.fire({ title:'Valor inválido', icon:'warning', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' }); return; }

    var form = new FormData();
    form.append('mov_caixa',  '1');
    form.append('tipo',       movTipoAtual);
    form.append('valor',      valor.toFixed(2));
    form.append('descricao',  desc);
    form.append('_csrf',      '<?= csrfToken() ?>');

    fetch(window.location.href, { method:'POST', body:form })
    .then(function(r){ return r.json(); })
    .then(function(d){
        fecharModalMov();
        if(d.success) {
            Swal.fire({ title: movTipoAtual === 'sangria' ? 'Sangria registrada!' : 'Suprimento registrado!', icon:'success', timer:1500, showConfirmButton:false, background:'var(--bg-card)', color:'var(--text)' });
        } else {
            Swal.fire({ title:'Erro', text:d.error||'', icon:'error', confirmButtonColor:'#f59e0b', background:'var(--bg-card)', color:'var(--text)' });
        }
    });
}
window.confirmarMovCaixa = confirmarMovCaixa;

// ── Init ─────────────────────────────────────────────────────
selecionarPagamento('dinheiro');

})(); // IIFE — escopo isolado
</script>

<?php include '../../includes/footer.php'; ?>
