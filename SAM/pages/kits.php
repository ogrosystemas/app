<?php
/**
 * pages/kits.php
 * Kits e Composições — agrupa produtos em conjuntos para vender no ML
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

// Produtos disponíveis para montar kits
$produtos = db_all(
    "SELECT id, title, price, cost_price, stock_quantity, meli_item_id, sku, ml_status
     FROM products
     WHERE tenant_id=? AND price>0
     ORDER BY title ASC",
    [$tenantId]
);

$title = 'Kits e Composições';
include __DIR__ . '/layout.php';
?>

<div style="padding:24px">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Kits e Composições</h1>
      <p style="font-size:11px;color:#5E5E5A">Monte conjuntos de produtos, calcule margem real e publique diretamente no ML</p>
    </div>
    <button onclick="abrirModal()" class="btn-primary" style="font-size:12px">
      <i data-lucide="plus" style="width:13px;height:13px"></i> Novo kit
    </button>
  </div>

  <!-- KPIs -->
  <div id="kits-kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px"></div>

  <!-- Lista de kits -->
  <div id="kits-loading" style="text-align:center;padding:48px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;color:#5E5E5A;font-size:13px">
    <i data-lucide="loader-2" style="width:20px;height:20px;animation:spin 1s linear infinite;margin:0 auto 8px;display:block"></i>
    Carregando kits...
  </div>
  <div id="kits-content" style="display:none"></div>
</div>

<!-- ═══════════════════════════════════════════
     Modal: Criar/Editar Kit
═══════════════════════════════════════════ -->
<div id="modal-kit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:flex-start;justify-content:center;z-index:500;padding:20px;overflow-y:auto;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:16px;width:100%;max-width:900px;margin:0 auto;box-shadow:0 24px 80px rgba(0,0,0,.6);overflow:hidden">

    <!-- Header -->
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px;background:#1E1E21;position:sticky;top:0;z-index:10">
      <i data-lucide="package-plus" style="width:16px;height:16px;color:#a855f7"></i>
      <span id="modal-kit-title" style="font-size:14px;font-weight:600;color:#E8E8E6">Novo kit</span>
      <button onclick="fecharModal()" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>

    <input type="hidden" id="kit-id">

    <div class="kit-modal-grid" style="display:grid;grid-template-columns:1fr 1fr;min-height:500px">

      <!-- Esquerda: dados do kit -->
      <div style="padding:20px;border-right:0.5px solid #2E2E33;display:flex;flex-direction:column;gap:14px">

        <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;gap:5px">
          <i data-lucide="info" style="width:11px;height:11px"></i> Dados do kit
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Nome do kit *</label>
          <input type="text" id="kit-title" class="input" placeholder="Ex: Kit Escritório Premium">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">SKU do kit</label>
            <input type="text" id="kit-sku" class="input" placeholder="KIT-001">
          </div>
          <div>
            <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Desconto sobre componentes (%)</label>
            <input type="number" id="kit-discount" class="input" value="0" min="0" max="90" step="0.5" oninput="recalcularKit()">
          </div>
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Taxa ML (%)</label>
          <input type="number" id="kit-fee" class="input" value="14" min="0" max="30" step="0.1" oninput="recalcularKit()">
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Preço de venda (R$) <span style="color:#5E5E5A">— deixe 0 para calcular automaticamente</span></label>
          <input type="number" id="kit-price" class="input" step="0.01" min="0" placeholder="0,00" oninput="recalcularKit()">
        </div>

        <div>
          <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Descrição</label>
          <textarea id="kit-desc" class="input" style="height:80px;resize:none" placeholder="Descreva o que está incluído no kit..."></textarea>
        </div>

        <!-- Preview de lucratividade -->
        <div id="kit-preview" style="display:none;background:#252528;border-radius:10px;padding:14px">
          <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;display:flex;align-items:center;gap:5px">
            <i data-lucide="trending-up" style="width:11px;height:11px"></i> Preview de lucratividade
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <div style="text-align:center">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Preço de venda</div>
              <div id="prev-preco" style="font-size:18px;font-weight:700;color:#E8E8E6">—</div>
            </div>
            <div style="text-align:center">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Custo total</div>
              <div id="prev-custo" style="font-size:18px;font-weight:700;color:#9A9A96">—</div>
            </div>
            <div style="text-align:center">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Lucro líquido</div>
              <div id="prev-lucro" style="font-size:18px;font-weight:700">—</div>
            </div>
            <div style="text-align:center">
              <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Margem</div>
              <div id="prev-margem" style="font-size:18px;font-weight:700">—</div>
            </div>
          </div>
          <div style="margin-top:10px;padding-top:10px;border-top:0.5px solid #2E2E33">
            <div style="font-size:9px;color:#5E5E5A;margin-bottom:4px">Estoque disponível do kit</div>
            <div id="prev-estoque" style="font-size:15px;font-weight:600;color:#3483FA">—</div>
            <div style="font-size:9px;color:#5E5E5A;margin-top:2px">limitado pelo componente com menos unidades</div>
          </div>
        </div>

      </div>

      <!-- Direita: componentes -->
      <div style="padding:20px;display:flex;flex-direction:column;gap:14px">

        <div style="font-size:10px;font-weight:700;color:#5E5E5A;text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;gap:5px">
          <i data-lucide="layers" style="width:11px;height:11px"></i> Componentes do kit
        </div>

        <!-- Busca produto -->
        <div style="position:relative">
          <i data-lucide="search" style="width:13px;height:13px;color:#5E5E5A;position:absolute;left:10px;top:50%;transform:translateY(-50%)"></i>
          <input type="text" id="busca-produto" placeholder="Buscar produto por nome ou SKU..."
            oninput="filtrarProdutos(this.value)"
            style="width:100%;padding:8px 12px 8px 32px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box">
        </div>

        <!-- Lista de produtos para adicionar -->
        <div id="lista-produtos" style="max-height:200px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;border:0.5px solid #2E2E33;border-radius:8px;padding:6px;background:#0F0F10">
          <?php foreach ($produtos as $p): ?>
          <div class="prod-item" data-id="<?= $p['id'] ?>"
            data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>"
            data-sku="<?= htmlspecialchars(strtolower($p['sku']??'')) ?>"
            data-price="<?= $p['price'] ?>"
            data-cost="<?= $p['cost_price'] ?>"
            data-stock="<?= $p['stock_quantity'] ?>"
            data-fulltitle="<?= htmlspecialchars($p['title']) ?>"
            onclick="adicionarComponente(this)"
            style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;cursor:pointer;transition:background .12s"
            onmouseover="this.style.background='#252528'" onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0">
              <div style="font-size:11px;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['title']) ?></div>
              <div style="font-size:10px;color:#5E5E5A;display:flex;gap:8px;margin-top:1px">
                <span>R$ <?= number_format($p['price'],2,',','.') ?></span>
                <span><?= $p['stock_quantity'] ?> em estoque</span>
                <?php if ($p['sku']): ?><span style="font-family:monospace"><?= htmlspecialchars($p['sku']) ?></span><?php endif; ?>
              </div>
            </div>
            <i data-lucide="plus-circle" style="width:14px;height:14px;color:#3483FA;flex-shrink:0"></i>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Componentes adicionados -->
        <div>
          <div style="font-size:10px;color:#5E5E5A;margin-bottom:6px;display:flex;align-items:center;justify-content:space-between">
            <span>Componentes adicionados</span>
            <span id="comp-count" style="color:#3483FA">0 produto(s)</span>
          </div>
          <div id="kit-componentes" style="display:flex;flex-direction:column;gap:6px;min-height:60px">
            <div id="comp-empty" style="text-align:center;padding:20px;color:#5E5E5A;font-size:11px;border:1.5px dashed #2E2E33;border-radius:8px">
              Clique nos produtos acima para adicionar
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <div style="padding:14px 20px;border-top:0.5px solid #2E2E33;display:flex;gap:8px;justify-content:flex-end;background:#1E1E21">
      <button onclick="fecharModal()" class="btn-secondary" style="font-size:12px">Cancelar</button>
      <button onclick="salvarKit()" class="btn-primary" style="font-size:12px">
        <i data-lucide="save" style="width:12px;height:12px"></i> Salvar kit
      </button>
    </div>
  </div>
</div>

<!-- Modal: Publicar no ML -->
<div id="modal-publicar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);align-items:center;justify-content:center;z-index:600;padding:16px;backdrop-filter:blur(3px)">
  <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:14px;width:100%;max-width:440px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.6)">
    <div style="padding:16px 20px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px">
      <i data-lucide="send" style="width:15px;height:15px;color:#FFE600"></i>
      <span style="font-size:14px;font-weight:600;color:#E8E8E6">Publicar kit no ML</span>
      <button onclick="document.getElementById('modal-publicar').style.display='none'" style="margin-left:auto;background:none;border:none;color:#5E5E5A;cursor:pointer;font-size:20px">✕</button>
    </div>
    <div style="padding:20px;display:flex;flex-direction:column;gap:12px">
      <input type="hidden" id="pub-kit-id">
      <div>
        <label style="display:block;font-size:11px;color:#9A9A96;margin-bottom:5px">Categoria ML * <span style="color:#5E5E5A">(busque pelo nome)</span></label>
        <div style="display:flex;gap:6px">
          <input type="text" id="pub-cat-search" placeholder="Buscar categoria..."
            style="flex:1;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:7px;color:#E8E8E6;font-size:12px;outline:none"
            onkeydown="if(event.key==='Enter'){buscarCatPub();event.preventDefault()}">
          <button onclick="buscarCatPub()" style="padding:8px 14px;background:#3483FA;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer">Buscar</button>
        </div>
        <div id="pub-cat-results" style="display:none;margin-top:6px;max-height:140px;overflow-y:auto;border:0.5px solid #2E2E33;border-radius:8px;background:#1A1A1C"></div>
        <input type="hidden" id="pub-cat-id">
        <div id="pub-cat-sel" style="display:none;margin-top:6px;font-size:11px;color:#3483FA;padding:5px 8px;background:rgba(52,131,250,.1);border-radius:6px"></div>
      </div>
      <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12px;color:#9A9A96">
        <input type="checkbox" id="pub-pausar" style="accent-color:#3483FA">
        Publicar pausado (ativar manualmente depois)
      </label>
      <button onclick="publicarKit()" style="padding:10px;background:#FFE600;color:#1A1A1A;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px">
        <i data-lucide="send" style="width:14px;height:14px"></i> Publicar no Mercado Livre
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
  carregarKits();
});

let kitComponentes = []; // [{product_id, quantity, title, price, cost, stock}]
let kitEditId = null;

// ── Carregar kits ────────────────────────────────────────
async function carregarKits() {
  const r = await fetch('/api/kits.php?action=list');
  const d = await r.json();

  document.getElementById('kits-loading').style.display = 'none';
  const content = document.getElementById('kits-content');
  content.style.display = 'block';

  if (!d.ok || !d.kits?.length) {
    content.innerHTML = `
      <div style="text-align:center;padding:64px;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px">
        <i data-lucide="package-plus" style="width:36px;height:36px;color:#2E2E33;margin:0 auto 14px;display:block"></i>
        <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:6px">Nenhum kit criado ainda</div>
        <p style="font-size:12px;color:#5E5E5A;margin-bottom:16px">Monte conjuntos de produtos e venda como um kit no ML</p>
        <button onclick="abrirModal()" class="btn-primary" style="font-size:12px">
          <i data-lucide="plus" style="width:12px;height:12px"></i> Criar primeiro kit
        </button>
      </div>`;
    lucide.createIcons();
    return;
  }

  // KPIs
  const totalKits = d.kits.length;
  const totalAtivos = d.kits.filter(k => k.status==='ativo').length;
  const publicados  = d.kits.filter(k => k.meli_item_id).length;
  const semEstoque  = d.kits.filter(k => k.estoque_disponivel <= 0).length;

  document.getElementById('kits-kpis').innerHTML = [
    ['Kits criados',   totalKits,    '#3483FA', 'package'],
    ['Ativos',         totalAtivos,  '#22c55e', 'check-circle'],
    ['Publicados ML',  publicados,   '#FFE600', 'send'],
    ['Sem estoque',    semEstoque,   semEstoque>0?'#ef4444':'#22c55e', 'alert-triangle'],
  ].map(([l,v,c,ic]) => `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-top:3px solid ${c};border-radius:10px;padding:12px 14px">
      <div style="font-size:10px;color:#5E5E5A;margin-bottom:4px;display:flex;align-items:center;gap:4px">
        <i data-lucide="${ic}" style="width:10px;height:10px;color:${c}"></i>${l}
      </div>
      <div style="font-size:22px;font-weight:700;color:${c}">${v}</div>
    </div>`).join('');

  // Grid de kits
  content.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px">${
    d.kits.map(k => {
      const estoqueColor = k.estoque_disponivel > 5 ? '#22c55e' : k.estoque_disponivel > 0 ? '#f59e0b' : '#ef4444';
      const margemColor  = k.margem_pct >= 20 ? '#22c55e' : k.margem_pct >= 10 ? '#f59e0b' : '#ef4444';
      const mlBadge = k.meli_item_id
        ? `<span style="font-size:9px;padding:1px 6px;border-radius:5px;background:rgba(255,230,0,.15);color:#FFE600;font-weight:600">● ML ${k.ml_status==='active'?'Ativo':'Pausado'}</span>`
        : `<span style="font-size:9px;padding:1px 6px;border-radius:5px;background:#252528;color:#5E5E5A">Não publicado</span>`;

      return `
      <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden;transition:box-shadow .15s"
        onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,.3)'" onmouseout="this.style.boxShadow=''">

        <!-- Header do card -->
        <div style="padding:14px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:flex-start;gap:10px">
          <div style="width:36px;height:36px;border-radius:8px;background:rgba(168,85,247,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i data-lucide="package" style="width:16px;height:16px;color:#a855f7"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${k.title}</div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:3px;flex-wrap:wrap">
              ${mlBadge}
              ${k.sku ? `<span style="font-size:9px;color:#5E5E5A;font-family:monospace">${k.sku}</span>` : ''}
              <span style="font-size:9px;color:#5E5E5A">${k.total_itens} produto${k.total_itens!=1?'s':''}</span>
            </div>
          </div>
        </div>

        <!-- Métricas -->
        <div style="padding:12px 16px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;border-bottom:0.5px solid #2E2E33">
          <div style="text-align:center">
            <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Preço kit</div>
            <div style="font-size:14px;font-weight:700;color:#E8E8E6">R$ ${parseFloat(k.price).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
          </div>
          <div style="text-align:center">
            <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Margem</div>
            <div style="font-size:14px;font-weight:700;color:${margemColor}">${k.margem_pct}%</div>
          </div>
          <div style="text-align:center">
            <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Estoque</div>
            <div style="font-size:14px;font-weight:700;color:${estoqueColor}">${k.estoque_disponivel} kits</div>
          </div>
        </div>

        <!-- Ações -->
        <div style="padding:10px 16px;display:flex;gap:6px">
          <button onclick="editarKit('${k.id}')"
            style="flex:1;padding:7px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .15s"
            onmouseover="this.style.background='rgba(52,131,250,.2)'" onmouseout="this.style.background='rgba(52,131,250,.1)'">
            <i data-lucide="pencil" style="width:11px;height:11px"></i> Editar
          </button>
          <button onclick="abrirPublicar('${k.id}')"
            style="flex:1;padding:7px;background:rgba(255,230,0,.08);border:0.5px solid rgba(255,230,0,.3);color:#FFE600;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .15s"
            onmouseover="this.style.background='rgba(255,230,0,.15)'" onmouseout="this.style.background='rgba(255,230,0,.08)'">
            <i data-lucide="send" style="width:11px;height:11px"></i> ${k.meli_item_id?'Re-publicar':'Publicar ML'}
          </button>
          <button onclick="excluirKit('${k.id}','${k.title.replace(/'/g,"\\'")}')"
            style="padding:7px 10px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:7px;font-size:11px;cursor:pointer;transition:all .15s"
            onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
            <i data-lucide="trash-2" style="width:11px;height:11px"></i>
          </button>
        </div>
      </div>`;
    }).join('')
  }</div>`;

  lucide.createIcons();
}

// ── Modal criar/editar ───────────────────────────────────
function abrirModal() {
  kitEditId = null;
  kitComponentes = [];
  document.getElementById('kit-id').value     = '';
  document.getElementById('kit-title').value  = '';
  document.getElementById('kit-sku').value    = '';
  document.getElementById('kit-desc').value   = '';
  document.getElementById('kit-discount').value= '0';
  document.getElementById('kit-fee').value    = '14';
  document.getElementById('kit-price').value  = '';
  document.getElementById('modal-kit-title').textContent = 'Novo kit';
  renderComponentes();
  document.getElementById('modal-kit').style.display = 'flex';
  lucide.createIcons();
}

function fecharModal() {
  document.getElementById('modal-kit').style.display = 'none';
}

async function editarKit(id) {
  const r = await fetch(`/api/kits.php?action=get&id=${id}`);
  const d = await r.json();
  if (!d.ok) { toast(d.error || 'Erro', 'error'); return; }

  const k = d.kit;
  kitEditId = id;
  kitComponentes = k.itens.map(i => ({
    product_id: i.product_id,
    quantity:   i.quantity,
    title:      i.title,
    price:      parseFloat(i.price),
    cost:       parseFloat(i.cost_price),
    stock:      parseInt(i.stock_quantity),
  }));

  document.getElementById('kit-id').value      = id;
  document.getElementById('kit-title').value   = k.title;
  document.getElementById('kit-sku').value     = k.sku || '';
  document.getElementById('kit-desc').value    = k.description || '';
  document.getElementById('kit-discount').value= k.discount_pct;
  document.getElementById('kit-fee').value     = k.ml_fee_percent;
  document.getElementById('kit-price').value   = k.price;
  document.getElementById('modal-kit-title').textContent = 'Editar kit';

  renderComponentes();
  recalcularKit();
  document.getElementById('modal-kit').style.display = 'flex';
  lucide.createIcons();
}

// ── Componentes ──────────────────────────────────────────
function adicionarComponente(el) {
  const pid   = el.dataset.id;
  const title = el.dataset.fulltitle;
  const price = parseFloat(el.dataset.price);
  const cost  = parseFloat(el.dataset.cost);
  const stock = parseInt(el.dataset.stock);

  if (kitComponentes.find(c => c.product_id === pid)) {
    toast('Produto já adicionado', 'info'); return;
  }
  kitComponentes.push({product_id:pid, quantity:1, title, price, cost, stock});
  renderComponentes();
  recalcularKit();
}

function removerComponente(pid) {
  kitComponentes = kitComponentes.filter(c => c.product_id !== pid);
  renderComponentes();
  recalcularKit();
}

function alterarQtd(pid, qtd) {
  const c = kitComponentes.find(c => c.product_id === pid);
  if (c) { c.quantity = Math.max(1, parseInt(qtd)||1); recalcularKit(); }
}

function renderComponentes() {
  const el = document.getElementById('kit-componentes');
  const empty = document.getElementById('comp-empty');
  document.getElementById('comp-count').textContent = `${kitComponentes.length} produto(s)`;

  if (!kitComponentes.length) {
    el.innerHTML = '';
    el.appendChild(empty);
    empty.style.display = 'block';
    document.getElementById('kit-preview').style.display = 'none';
    return;
  }

  empty.style.display = 'none';
  el.innerHTML = kitComponentes.map(c => `
    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:#252528;border-radius:8px;border:0.5px solid #2E2E33">
      <div style="flex:1;min-width:0">
        <div style="font-size:11px;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${c.title}</div>
        <div style="font-size:10px;color:#5E5E5A">R$ ${c.price.toLocaleString('pt-BR',{minimumFractionDigits:2})} · ${c.stock} em estoque</div>
      </div>
      <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <button onclick="alterarQtd('${c.product_id}',${c.quantity-1})" style="width:22px;height:22px;border-radius:5px;background:#1A1A1C;border:0.5px solid #2E2E33;color:#E8E8E6;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">−</button>
        <input type="number" value="${c.quantity}" min="1" onchange="alterarQtd('${c.product_id}',this.value)"
          style="width:36px;text-align:center;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:5px;color:#E8E8E6;font-size:11px;padding:3px">
        <button onclick="alterarQtd('${c.product_id}',${c.quantity+1})" style="width:22px;height:22px;border-radius:5px;background:#1A1A1C;border:0.5px solid #2E2E33;color:#E8E8E6;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">+</button>
      </div>
      <button onclick="removerComponente('${c.product_id}')" style="background:none;border:none;color:#5E5E5A;cursor:pointer;padding:2px"
        onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#5E5E5A'">
        <i data-lucide="x" style="width:13px;height:13px"></i>
      </button>
    </div>`).join('');

  lucide.createIcons();
}

function recalcularKit() {
  if (!kitComponentes.length) return;

  const discount = parseFloat(document.getElementById('kit-discount').value)||0;
  const fee      = parseFloat(document.getElementById('kit-fee').value)||14;
  const precoMan = parseFloat(document.getElementById('kit-price').value)||0;

  const totalPreco = kitComponentes.reduce((s,c) => s + c.price * c.quantity, 0);
  const totalCusto = kitComponentes.reduce((s,c) => s + c.cost  * c.quantity, 0);
  const preco      = precoMan > 0 ? precoMan : totalPreco * (1 - discount/100);
  const comissao   = preco * (fee/100);
  const lucro      = preco - totalCusto - comissao;
  const margem     = preco > 0 ? (lucro/preco)*100 : 0;

  // Estoque mínimo dos componentes
  const estoque = Math.min(...kitComponentes.map(c => Math.floor(c.stock / c.quantity)));

  const fmt = v => 'R$ ' + v.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
  const margemColor = margem >= 20 ? '#22c55e' : margem >= 10 ? '#f59e0b' : '#ef4444';

  document.getElementById('kit-preview').style.display = 'block';
  document.getElementById('prev-preco').textContent  = fmt(preco);
  document.getElementById('prev-custo').textContent  = fmt(totalCusto);
  document.getElementById('prev-lucro').textContent  = fmt(lucro);
  document.getElementById('prev-lucro').style.color  = lucro >= 0 ? '#22c55e' : '#ef4444';
  document.getElementById('prev-margem').textContent = margem.toFixed(1) + '%';
  document.getElementById('prev-margem').style.color = margemColor;
  document.getElementById('prev-estoque').textContent= `${Math.max(0,estoque)} kits disponíveis`;
  document.getElementById('prev-estoque').style.color= estoque > 5 ? '#22c55e' : estoque > 0 ? '#f59e0b' : '#ef4444';
}

function filtrarProdutos(q) {
  const termo = q.toLowerCase().trim();
  document.querySelectorAll('.prod-item').forEach(el => {
    el.style.display = (!termo || el.dataset.title.includes(termo) || el.dataset.sku.includes(termo)) ? '' : 'none';
  });
}

// ── Salvar ───────────────────────────────────────────────
async function salvarKit() {
  const title = document.getElementById('kit-title').value.trim();
  if (!title)               { toast('Nome do kit obrigatório', 'error'); return; }
  if (!kitComponentes.length){ toast('Adicione pelo menos 1 produto', 'error'); return; }

  const fd = new FormData();
  fd.append('action',       'save');
  fd.append('id',           document.getElementById('kit-id').value);
  fd.append('title',        title);
  fd.append('sku',          document.getElementById('kit-sku').value);
  fd.append('description',  document.getElementById('kit-desc').value);
  fd.append('discount_pct', document.getElementById('kit-discount').value);
  fd.append('ml_fee_percent',document.getElementById('kit-fee').value);
  fd.append('price',        document.getElementById('kit-price').value);
  fd.append('itens',        JSON.stringify(kitComponentes.map(c=>({product_id:c.product_id,quantity:c.quantity}))));

  const r = await fetch('/api/kits.php', {method:'POST', body:fd});
  const d = await r.json();

  if (d.ok) {
    toast('Kit salvo!', 'success');
    fecharModal();
    document.getElementById('kits-loading').style.display = 'block';
    document.getElementById('kits-content').style.display = 'none';
    carregarKits();
  } else {
    toast(d.error || 'Erro ao salvar', 'error');
  }
}

// ── Excluir ──────────────────────────────────────────────
async function excluirKit(id, nome) {
  const ok = await dialog({title:'Excluir kit', message:`Excluir "${nome}"? Esta ação não pode ser desfeita.`, confirmText:'Excluir', danger:true});
  if (!ok) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  const r = await fetch('/api/kits.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) { toast('Kit removido', 'success'); carregarKits(); }
  else toast(d.error || 'Erro', 'error');
}

// ── Publicar no ML ────────────────────────────────────────
function abrirPublicar(id) {
  document.getElementById('pub-kit-id').value = id;
  document.getElementById('pub-cat-id').value = '';
  document.getElementById('pub-cat-search').value = '';
  document.getElementById('pub-cat-results').style.display = 'none';
  document.getElementById('pub-cat-sel').style.display = 'none';
  document.getElementById('modal-publicar').style.display = 'flex';
  lucide.createIcons();
}

async function buscarCatPub() {
  const q = document.getElementById('pub-cat-search').value.trim();
  if (!q) return;
  const r = await fetch(`/api/anuncios_data.php?action=search_category&q=${encodeURIComponent(q)}`);
  const d = await r.json();
  const res = document.getElementById('pub-cat-results');
  if (!d.ok || !d.categories?.length) {
    res.style.display = 'block';
    res.innerHTML = '<div style="padding:10px;font-size:11px;color:#5E5E5A">Nenhuma categoria encontrada</div>';
    return;
  }
  res.style.display = 'block';
  res.innerHTML = d.categories.slice(0,8).map(c => `
    <div onclick="selecionarCatPub('${c.id}','${c.name.replace(/'/g,"\\'")}')"
      style="padding:8px 12px;font-size:12px;color:#E8E8E6;cursor:pointer;transition:background .12s"
      onmouseover="this.style.background='#252528'" onmouseout="this.style.background=''">
      ${c.name}
    </div>`).join('');
}

function selecionarCatPub(id, nome) {
  document.getElementById('pub-cat-id').value = id;
  const sel = document.getElementById('pub-cat-sel');
  sel.textContent = nome;
  sel.style.display = 'block';
  document.getElementById('pub-cat-results').style.display = 'none';
}

async function publicarKit() {
  const kitId = document.getElementById('pub-kit-id').value;
  const catId = document.getElementById('pub-cat-id').value;
  const pausar = document.getElementById('pub-pausar').checked;
  if (!catId) { toast('Selecione uma categoria', 'error'); return; }
  const fd = new FormData();
  fd.append('action', 'publicar');
  fd.append('id', kitId);
  fd.append('category_id', catId);
  fd.append('pausar', pausar ? '1' : '0');
  const r = await fetch('/api/kits.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast(`✅ Kit publicado! ID: ${d.meli_item_id}`, 'success');
    document.getElementById('modal-publicar').style.display = 'none';
    carregarKits();
  } else {
    toast(d.error || 'Erro ao publicar', 'error');
  }
}

document.getElementById('modal-kit').addEventListener('click', function(e) { if(e.target===this) fecharModal(); });
document.getElementById('modal-publicar').addEventListener('click', function(e) { if(e.target===this) this.style.display='none'; });
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
