<?php
/**
 * modules/orcamentos/form_orcamento.php
 * Formulário completo para criação e edição de orçamentos com itens.
 * Substituí a criação inline no orcamentos.php por uma página dedicada.
 */
require_once '../../config/config.php';
checkAuth();

$id = (int)($_GET['id'] ?? 0);
$orcamento = null;
$itens_existentes = [];

if ($id) {
    $stmt = $db->prepare(
        "SELECT o.*, c.nome as cliente_nome, m.modelo as moto_modelo, m.placa
         FROM orcamentos o
         JOIN clientes c ON c.id = o.cliente_id
         LEFT JOIN motos m ON m.id = o.moto_id
         WHERE o.id = ?"
    );
    $stmt->execute([$id]);
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$orcamento) { header('Location: orcamentos.php'); exit; }

    $stmt_it = $db->prepare(
        "SELECT oi.*, 
                CASE WHEN oi.tipo='servico' THEN s.nome ELSE p.nome END as item_nome,
                CASE WHEN oi.tipo='servico' THEN s.preco ELSE p.preco_venda END as preco_ref
         FROM orcamento_itens oi
         LEFT JOIN servicos s ON oi.tipo='servico' AND oi.item_id=s.id
         LEFT JOIN produtos p ON oi.tipo='produto' AND oi.item_id=p.id
         WHERE oi.orcamento_id = ?"
    );
    $stmt_it->execute([$id]);
    $itens_existentes = $stmt_it->fetchAll(PDO::FETCH_ASSOC);
}

$clientes  = $db->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$servicos  = $db->query("SELECT id,nome,preco FROM servicos WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$produtos  = $db->query("SELECT id,nome,preco_venda,estoque_atual FROM produtos WHERE estoque_atual>0 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<?php include '../../includes/sidebar.php'; ?>

<header class="os-topbar">
  <div class="topbar-title">
    <?= $id ? 'Editar Orçamento' : 'Novo Orçamento' ?>
    <span style="color:var(--accent)">·</span>
    <?= $orcamento ? htmlspecialchars($orcamento['numero_orcamento']) : '' ?>
  </div>
  <div class="topbar-actions">
    <a href="orcamentos.php" class="btn-os btn-os-ghost" style="font-size:.82rem">
      <i class="ph-bold ph-arrow-left"></i> Voltar
    </a>
  </div>
</header>

<main class="os-content">
<form id="formOrcamento" method="POST" action="orcamentos.php">
  <input type="hidden" name="salvar_orcamento" value="1">
  <?php if ($id): ?><input type="hidden" name="orcamento_id" value="<?= $id ?>"><?php endif; ?>
  <input type="hidden" name="itens_json" id="itensJson" value="[]">
  <?= csrfField() ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:20px">

    <!-- Coluna principal -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Dados -->
      <div class="os-card">
        <div class="os-card-header"><div class="os-card-title"><i class="ph-bold ph-file-text"></i> Dados do Orçamento</div></div>
        <div class="os-card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="os-form-group">
              <label class="os-label">Cliente *</label>
              <select name="cliente_id" id="selCliente" class="os-select" required onchange="carregarMotos(this.value)">
                <option value="">Selecione...</option>
                <?php foreach($clientes as $c): ?>
                <option value="<?=$c['id']?>" <?= ($orcamento['cliente_id']??0)==$c['id']?'selected':''?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="os-form-group">
              <label class="os-label">Moto</label>
              <select name="moto_id" id="selMoto" class="os-select">
                <option value="">Selecione o cliente primeiro...</option>
                <?php if($orcamento && $orcamento['moto_id']): ?>
                <option value="<?=$orcamento['moto_id']?>" selected><?= htmlspecialchars($orcamento['moto_modelo'].' — '.$orcamento['placa']) ?></option>
                <?php endif; ?>
              </select>
            </div>
            <div class="os-form-group">
              <label class="os-label">Validade</label>
              <input type="date" name="data_validade" class="os-input"
                     value="<?= htmlspecialchars($orcamento['data_validade'] ?? date('Y-m-d', strtotime('+15 days'))) ?>" required>
            </div>
            <div class="os-form-group" style="grid-column:1/-1">
              <label class="os-label">Observações</label>
              <textarea name="observacoes" class="os-input" rows="2"><?= htmlspecialchars($orcamento['observacoes'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Itens -->
      <div class="os-card">
        <div class="os-card-header" style="display:flex;align-items:center;justify-content:space-between">
          <div class="os-card-title"><i class="ph-bold ph-list-checks"></i> Itens do Orçamento</div>
          <div style="display:flex;gap:8px">
            <button type="button" class="btn-os btn-os-ghost" style="font-size:.8rem" onclick="abrirModalItem('servico')">
              <i class="ph-bold ph-toolbox"></i> + Serviço
            </button>
            <button type="button" class="btn-os btn-os-ghost" style="font-size:.8rem" onclick="abrirModalItem('produto')">
              <i class="ph-bold ph-package"></i> + Produto
            </button>
          </div>
        </div>
        <div class="os-card-body" style="padding:0">
          <table class="os-table" id="tabelaItens">
            <thead><tr><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Unit.</th><th>Total</th><th></th></tr></thead>
            <tbody id="tbodyItens">
              <tr id="trVazio"><td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px">Nenhum item adicionado.</td></tr>
            </tbody>
          </table>
        </div>
        <div style="padding:16px 20px;border-top:1px solid var(--border)">
          <div style="display:flex;justify-content:flex-end;gap:24px;font-family:'Syne',sans-serif">
            <div style="text-align:right">
              <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">SUBTOTAL</div>
              <div style="font-size:1rem;font-weight:700;color:var(--text)" id="dispSubtotal">R$ 0,00</div>
            </div>
            <div style="text-align:right">
              <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">TOTAL</div>
              <div style="font-size:1.4rem;font-weight:800;color:var(--accent)" id="dispTotal">R$ 0,00</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lateral -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="os-card">
        <div class="os-card-header"><div class="os-card-title">Salvar</div></div>
        <div class="os-card-body" style="display:flex;flex-direction:column;gap:10px">
          <button type="submit" class="btn-os btn-os-primary" style="width:100%;justify-content:center;padding:12px">
            <i class="ph-bold ph-floppy-disk"></i>
            <?= $id ? 'Salvar Alterações' : 'Criar Orçamento' ?>
          </button>
          <a href="orcamentos.php" class="btn-os btn-os-ghost" style="width:100%;justify-content:center">Cancelar</a>
        </div>
      </div>
    </div>
  </div>
</form>
</main>

<!-- Modal Item -->
<div id="modalItem" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:3000;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;width:480px;max-width:95vw;padding:24px">
    <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px" id="modalItemTitulo">Adicionar Item</h5>
    <div class="os-form-group mb-3">
      <label class="os-label" id="labelItemSel">Selecione</label>
      <select id="selItem" class="os-select" onchange="preencherItem()">
        <option value="">Selecione...</option>
      </select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="os-form-group">
        <label class="os-label">Quantidade</label>
        <input type="number" id="itemQtd" class="os-input" value="1" min="0.01" step="0.01" oninput="calcItemTotal()">
      </div>
      <div class="os-form-group">
        <label class="os-label">Valor Unitário (R$)</label>
        <input type="number" id="itemValor" class="os-input" value="0" step="0.01" oninput="calcItemTotal()">
      </div>
    </div>
    <div style="background:var(--bg-card2);border-radius:8px;padding:12px;margin:12px 0;display:flex;justify-content:space-between;align-items:center">
      <span style="color:var(--text-muted);font-size:.85rem">Total do item:</span>
      <span id="itemTotalDisp" style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:var(--accent)">R$ 0,00</span>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn-os btn-os-ghost" onclick="fecharModalItem()">Cancelar</button>
      <button class="btn-os btn-os-primary" onclick="confirmarItem()"><i class="ph-bold ph-check"></i> Adicionar</button>
    </div>
  </div>
</div>

<script>
const SERVICOS = <?= json_encode($servicos, JSON_HEX_TAG) ?>;
const PRODUTOS  = <?= json_encode($produtos, JSON_HEX_TAG) ?>;
let itens = <?= json_encode(array_map(fn($i) => [
    'tipo'     => $i['tipo'],
    'id'       => $i['item_id'],
    'nome'     => $i['item_nome'],
    'quantidade'=> (float)$i['quantidade'],
    'valor'    => (float)$i['valor_unitario'],
    'total'    => (float)$i['quantidade'] * (float)$i['valor_unitario'],
], $itens_existentes), JSON_HEX_TAG) ?>;

let tipoAtual = 'servico';

function abrirModalItem(tipo) {
    tipoAtual = tipo;
    const sel = document.getElementById('selItem');
    sel.innerHTML = '<option value="">Selecione...</option>';
    const lista = tipo === 'servico' ? SERVICOS : PRODUTOS;
    lista.forEach(i => {
        const opt = document.createElement('option');
        opt.value = i.id;
        opt.textContent = i.nome + ' — R$ ' + parseFloat(tipo==='servico'?i.preco:i.preco_venda).toFixed(2).replace('.',',');
        opt.dataset.preco = tipo === 'servico' ? i.preco : i.preco_venda;
        sel.appendChild(opt);
    });
    document.getElementById('modalItemTitulo').textContent = 'Adicionar ' + (tipo==='servico'?'Serviço':'Produto');
    document.getElementById('labelItemSel').textContent = tipo==='servico' ? 'Serviço' : 'Produto';
    document.getElementById('itemQtd').value = 1;
    document.getElementById('itemValor').value = '0.00';
    document.getElementById('itemTotalDisp').textContent = 'R$ 0,00';
    document.getElementById('modalItem').style.display = 'flex';
}
function fecharModalItem() { document.getElementById('modalItem').style.display = 'none'; }

function preencherItem() {
    const opt = document.getElementById('selItem').options[document.getElementById('selItem').selectedIndex];
    if (!opt.value) return;
    document.getElementById('itemValor').value = parseFloat(opt.dataset.preco||0).toFixed(2);
    calcItemTotal();
}

function calcItemTotal() {
    const qtd = parseFloat(document.getElementById('itemQtd').value)||0;
    const val = parseFloat(document.getElementById('itemValor').value)||0;
    const total = qtd * val;
    document.getElementById('itemTotalDisp').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
}

function confirmarItem() {
    const selEl = document.getElementById('selItem');
    const id = parseInt(selEl.value);
    const opt = selEl.options[selEl.selectedIndex];
    if (!id) {
        Swal.fire({ icon:'warning', title:'Atenção', text:'Selecione um item.', background:'var(--bg-card,#1c2333)', color:'var(--text,#f0f2f7)', confirmButtonColor:'#f59e0b' });
        return;
    }
    const qtd   = parseFloat(document.getElementById('itemQtd').value)||1;
    const valor = parseFloat(document.getElementById('itemValor').value)||0;
    const lista = tipoAtual === 'servico' ? SERVICOS : PRODUTOS;
    const item  = lista.find(i => i.id == id);
    itens.push({ tipo: tipoAtual, id, nome: item ? item.nome : opt.textContent, quantidade: qtd, valor, total: qtd*valor });
    fecharModalItem();
    renderItens();
}

function removerItem(idx) { itens.splice(idx, 1); renderItens(); }

function renderItens() {
    const tbody = document.getElementById('tbodyItens');
    const trVazio = document.getElementById('trVazio');
    if (itens.length === 0) { tbody.innerHTML = ''; tbody.appendChild(trVazio); }
    else {
        if (trVazio.parentNode) trVazio.remove();
        tbody.innerHTML = '';
        itens.forEach((item, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><span class="os-badge ${item.tipo==='servico'?'os-badge-blue':'os-badge-green'}">${item.tipo==='servico'?'Serviço':'Produto'}</span></td>
                <td>${htmlEsc(item.nome)}</td>
                <td>${item.quantidade}</td>
                <td>R$ ${item.valor.toFixed(2).replace('.',',')}</td>
                <td style="font-weight:700;color:var(--accent)">R$ ${item.total.toFixed(2).replace('.',',')}</td>
                <td><button type="button" onclick="removerItem(${i})" style="background:none;border:none;color:var(--danger);cursor:pointer"><i class="ph-bold ph-x-circle"></i></button></td>`;
            tbody.appendChild(tr);
        });
    }
    const subtotal = itens.reduce((s, i) => s + i.total, 0);
    document.getElementById('dispSubtotal').textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    document.getElementById('dispTotal').textContent    = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
    document.getElementById('itensJson').value = JSON.stringify(itens.map(i => ({
        tipo: i.tipo, id: i.id, quantidade: i.quantidade, valor: i.valor
    })));
}

function htmlEsc(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

// Carregar motos do cliente
async function carregarMotos(cid) {
    const sel = document.getElementById('selMoto');
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!cid) { sel.innerHTML = '<option value="">Selecione o cliente primeiro</option>'; return; }
    const r = await fetch(`<?= $baseUrl ?>/api/motos.php?cliente_id=${cid}`);
    const d = await r.json();
    sel.innerHTML = '<option value="">Sem moto específica</option>';
    (d || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.marca} ${m.modelo} — ${m.placa}`;
        sel.appendChild(opt);
    });
}

<?php if ($orcamento && $orcamento['cliente_id']): ?>
carregarMotos(<?= (int)$orcamento['cliente_id'] ?>);
<?php endif; ?>

renderItens();
</script>

<?php include '../../includes/footer.php'; ?>
