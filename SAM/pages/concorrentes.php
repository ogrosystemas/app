<?php
/**
 * pages/concorrentes.php
 * Análise de concorrentes no Mercado Livre
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_once dirname(__DIR__) . '/crypto.php';

auth_module('access_anuncios');

$user     = auth_user();
$tenantId = $user['tenant_id'];
$acctId   = $_SESSION['active_meli_account_id'] ?? null;

// Buscar token da conta ativa
$token = null;
if ($acctId) {
    $acct = db_one("SELECT access_token_enc, meli_user_id FROM meli_accounts WHERE id=? AND tenant_id=?", [$acctId, $tenantId]);
    if ($acct) {
        try {
            $token = crypto_decrypt_token($acct['access_token_enc']);
        } catch(Throwable $e) {
            $token = null;
        }
    }
}

// Meus produtos para cruzamento
$meusProdutos = db_all(
    "SELECT meli_item_id, title, price, cost_price, ml_fee_percent, ml_status
     FROM products WHERE tenant_id=? AND meli_account_id=? AND ml_status='ACTIVE' AND price>0",
    [$tenantId, $acctId ?? '']
);

// Vendedores monitorados (carregados após garantir que tabela existe — veja abaixo)
$monitorados = [];

// Criar tabela se não existir
try {
    db_query("CREATE TABLE IF NOT EXISTS competitor_monitors (
        id          VARCHAR(36)  NOT NULL,
        tenant_id   VARCHAR(36)  NOT NULL,
        nickname    VARCHAR(100) NOT NULL,
        meli_user_id VARCHAR(30) NULL,
        categoria   VARCHAR(100) NULL,
        nota        TEXT         NULL,
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Throwable $e) {}

// Carrega monitorados após garantir que tabela existe
$monitorados = db_all(
    "SELECT * FROM competitor_monitors WHERE tenant_id=? ORDER BY created_at DESC",
    [$tenantId]
);

$title = 'Concorrentes';
include __DIR__ . '/layout.php';
?>

<div style="padding:20px">

  <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
    <div>
      <h1 style="font-size:16px;font-weight:500;color:#E8E8E6;margin-bottom:3px">Análise de Concorrentes</h1>
      <p style="font-size:11px;color:#5E5E5A">Monitore preços, vendas e estratégias dos seus concorrentes no ML em tempo real</p>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start">

    <!-- ── Painel esquerdo ── -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Busca por palavra-chave -->
      <div class="card" style="overflow:hidden">
        <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:6px">
          <i data-lucide="search" style="width:13px;height:13px;color:#3483FA"></i>
          <span style="font-size:12px;font-weight:500;color:#E8E8E6">Buscar no ML</span>
        </div>
        <div style="padding:14px">
          <input type="text" id="busca-kw" placeholder='Ex: "cabo usb-c 2m"'
            onkeydown="if(event.key==='Enter') buscarConcorrentes()"
            style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box;margin-bottom:10px">
          <div style="margin-bottom:10px">
            <label style="display:block;font-size:10px;color:#5E5E5A;margin-bottom:4px">Ordenar por</label>
            <select id="busca-sort" class="input" style="font-size:11px">
              <option value="relevance">Relevância</option>
              <option value="price_asc">Menor preço</option>
              <option value="price_desc">Maior preço</option>
            </select>
          </div>
          <div style="margin-bottom:12px">
            <label style="display:block;font-size:10px;color:#5E5E5A;margin-bottom:4px">Resultados</label>
            <select id="busca-limit" class="input" style="font-size:11px">
              <option value="10">Top 10</option>
              <option value="20" selected>Top 20</option>
              <option value="50">Top 50</option>
            </select>
          </div>
          <button onclick="buscarConcorrentes()" class="btn-primary" style="width:100%;font-size:12px;justify-content:center">
            <i data-lucide="search" style="width:12px;height:12px"></i> Buscar concorrentes
          </button>
        </div>
      </div>

      <!-- Busca por vendedor -->
      <div class="card" style="overflow:hidden">
        <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:6px">
          <i data-lucide="store" style="width:13px;height:13px;color:#f59e0b"></i>
          <span style="font-size:12px;font-weight:500;color:#E8E8E6">Analisar vendedor</span>
        </div>
        <div style="padding:14px">
          <input type="text" id="busca-seller" placeholder="Nickname do vendedor"
            onkeydown="if(event.key==='Enter') analisarVendedor()"
            style="width:100%;padding:8px 12px;background:#252528;border:0.5px solid #2E2E33;border-radius:8px;color:#E8E8E6;font-size:12px;outline:none;box-sizing:border-box;margin-bottom:8px">
          <button onclick="analisarVendedor()" class="btn-secondary" style="width:100%;font-size:12px;justify-content:center">
            <i data-lucide="user-search" style="width:12px;height:12px"></i> Ver perfil do vendedor
          </button>
        </div>
      </div>

      <!-- Vendedores monitorados -->
      <div class="card" style="overflow:hidden">
        <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;align-items:center;gap:6px">
            <i data-lucide="eye" style="width:13px;height:13px;color:#22c55e"></i>
            <span style="font-size:12px;font-weight:500;color:#E8E8E6">Monitorados</span>
          </div>
          <span style="font-size:10px;color:#5E5E5A"><?= count($monitorados) ?></span>
        </div>
        <div style="max-height:240px;overflow-y:auto">
          <?php if (empty($monitorados)): ?>
          <div style="padding:16px;text-align:center;font-size:11px;color:#5E5E5A">
            Nenhum vendedor monitorado.<br>Clique em "Monitorar" ao analisar um vendedor.
          </div>
          <?php else: ?>
          <?php foreach ($monitorados as $m): ?>
          <div style="padding:10px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:8px;cursor:pointer;transition:background .12s"
            onmouseover="this.style.background='#1E1E21'" onmouseout="this.style.background=''"
            onclick="document.getElementById('busca-seller').value='<?= htmlspecialchars($m['nickname']) ?>';analisarVendedor()">
            <div style="width:28px;height:28px;border-radius:50%;background:rgba(34,197,94,.15);color:#22c55e;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">
              <?= strtoupper(mb_substr($m['nickname'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:11px;font-weight:500;color:#E8E8E6;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($m['nickname']) ?></div>
              <?php if ($m['categoria']): ?>
              <div style="font-size:9px;color:#5E5E5A"><?= htmlspecialchars($m['categoria']) ?></div>
              <?php endif; ?>
            </div>
            <button onclick="event.stopPropagation();removerMonitor('<?= $m['id'] ?>')"
              style="background:none;border:none;color:#3E3E45;cursor:pointer;padding:2px;transition:color .15s"
              onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#3E3E45'">
              <i data-lucide="x" style="width:12px;height:12px"></i>
            </button>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Meus produtos para cruzamento -->
      <?php if (!empty($meusProdutos)): ?>
      <div class="card" style="overflow:hidden">
        <div style="padding:12px 16px;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:6px">
          <i data-lucide="package" style="width:13px;height:13px;color:#a855f7"></i>
          <span style="font-size:12px;font-weight:500;color:#E8E8E6">Cruzar com meu produto</span>
        </div>
        <div style="padding:10px">
          <select id="meu-produto" onchange="cruzarProduto()" class="input" style="font-size:11px">
            <option value="">Selecione um produto seu</option>
            <?php foreach ($meusProdutos as $mp): ?>
            <option value="<?= $mp['meli_item_id'] ?>" data-price="<?= $mp['price'] ?>" data-cost="<?= $mp['cost_price'] ?>" data-fee="<?= $mp['ml_fee_percent'] ?>">
              <?= htmlspecialchars(mb_substr($mp['title'],0,45)) ?> — R$<?= number_format($mp['price'],2,',','.') ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- ── Painel direito: resultados ── -->
    <div>

      <!-- Estado inicial -->
      <div id="painel-inicial" style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:64px;text-align:center">
        <i data-lucide="radar" style="width:36px;height:36px;color:#2E2E33;margin:0 auto 16px;display:block"></i>
        <div style="font-size:14px;font-weight:500;color:#E8E8E6;margin-bottom:6px">Pronto para monitorar a concorrência</div>
        <p style="font-size:12px;color:#5E5E5A;max-width:340px;margin:0 auto">Busque por palavra-chave para ver os top produtos do ML ou insira o nickname de um vendedor para analisar a loja dele.</p>
      </div>

      <!-- Loading -->
      <div id="painel-loading" style="display:none;background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:64px;text-align:center;color:#5E5E5A;font-size:13px">
        <i data-lucide="loader-2" style="width:24px;height:24px;animation:spin 1s linear infinite;margin:0 auto 12px;display:block;color:#3483FA"></i>
        Consultando a API do Mercado Livre...
      </div>

      <!-- Resultado busca por keyword -->
      <div id="painel-busca" style="display:none"></div>

      <!-- Resultado análise de vendedor -->
      <div id="painel-vendedor" style="display:none"></div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  lucide.createIcons();
});

let meuProdutoSel = null;

// ── Busca por palavra-chave ───────────────────────────────
async function buscarConcorrentes() {
  const q     = document.getElementById('busca-kw').value.trim();
  const sort  = document.getElementById('busca-sort').value;
  const limit = document.getElementById('busca-limit').value;
  if (!q) { toast('Digite uma palavra-chave', 'error'); return; }

  mostrarLoading();
  const r = await fetch(`/api/concorrentes.php?action=buscar&q=${encodeURIComponent(q)}&sort=${sort}&limit=${limit}`);
  const d = await r.json();
  esconderLoading();

  if (!d.ok) { toast(d.error || 'Erro ao buscar', 'error'); mostrarInicial(); return; }

  renderBusca(d, q);
}

function renderBusca(d, q) {
  const items  = d.items || [];
  const meuEl  = document.getElementById('meu-produto');
  const meuOpt = meuEl?.options[meuEl.selectedIndex];
  const meuPreco= meuOpt?.dataset?.price ? parseFloat(meuOpt.dataset.price) : null;

  // Calcular estatísticas do mercado
  const precos   = items.map(i => parseFloat(i.price)).filter(p=>p>0);
  const menorP   = precos.length ? Math.min(...precos) : 0;
  const maiorP   = precos.length ? Math.max(...precos) : 0;
  const mediaP   = precos.length ? precos.reduce((a,b)=>a+b,0)/precos.length : 0;
  const totalVendas = items.reduce((a,i)=>a+(i.sold_quantity||0),0);
  const lider    = items[0] || null;

  let html = `
    <!-- Header dos resultados -->
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;padding:16px;margin-bottom:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <div>
          <div style="font-size:13px;font-weight:600;color:#E8E8E6">"${q}"</div>
          <div style="font-size:11px;color:#5E5E5A">${items.length} anúncios analisados · ${totalVendas.toLocaleString('pt-BR')} vendas totais</div>
        </div>
        ${meuPreco ? `
        <div style="background:${meuPreco<=menorP?'rgba(239,68,68,.1)':meuPreco<=mediaP?'rgba(245,158,11,.1)':'rgba(34,197,94,.1)'};border:0.5px solid ${meuPreco<=menorP?'#ef4444':meuPreco<=mediaP?'#f59e0b':'#22c55e'};border-radius:8px;padding:8px 14px;text-align:center">
          <div style="font-size:9px;color:#5E5E5A;margin-bottom:2px">Meu preço vs mercado</div>
          <div style="font-size:15px;font-weight:700;color:${meuPreco<=menorP?'#ef4444':meuPreco<=mediaP?'#f59e0b':'#22c55e'}">
            ${meuPreco<=menorP?'🔴 Mais caro que todos':meuPreco<=mediaP?'⚠️ Acima da média':'✅ Competitivo'}
          </div>
        </div>` : ''}
      </div>

      <!-- KPIs de mercado -->
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
        ${[
          ['Menor preço','R$ '+menorP.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}),'#22c55e','trending-down'],
          ['Preço médio','R$ '+mediaP.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}),'#3483FA','minus'],
          ['Maior preço','R$ '+maiorP.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}),'#f59e0b','trending-up'],
          ['Total de vendas',totalVendas.toLocaleString('pt-BR'),'#a855f7','shopping-bag'],
        ].map(([l,v,c,ic])=>`
        <div style="background:#252528;border-radius:8px;padding:10px;text-align:center">
          <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px;display:flex;align-items:center;justify-content:center;gap:3px">
            <i data-lucide="${ic}" style="width:9px;height:9px;color:${c}"></i>${l}
          </div>
          <div style="font-size:14px;font-weight:700;color:${c}">${v}</div>
        </div>`).join('')}
      </div>

      ${meuPreco && lider ? `
      <!-- Sugestão de preço com IA -->
      <div style="margin-top:12px;padding:10px 14px;background:rgba(255,230,0,.06);border:0.5px solid rgba(255,230,0,.2);border-radius:8px;font-size:11px;color:#9A9A96;line-height:1.6">
        <strong style="color:#FFE600">💡 Estratégia sugerida:</strong>
        ${meuPreco > menorP
          ? `Líder de mercado vende a <strong style="color:#E8E8E6">R$ ${parseFloat(lider.price).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</strong>.
             Seu preço está ${((meuPreco-lider.price)/lider.price*100).toFixed(1)}% acima.
             Para competir, considere reduzir para <strong style="color:#22c55e">R$ ${(lider.price*1.02).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</strong> (2% acima do líder).`
          : 'Seu preço está competitivo! Foque na qualidade do anúncio (fotos, título, descrição) para ganhar posição.'
        }
      </div>` : ''}
    </div>

    <!-- Lista de anúncios -->
    <div style="display:flex;flex-direction:column;gap:8px">`;

  items.forEach((item, idx) => {
    const pos = idx + 1;
    const isLider = idx === 0;
    const precoItem = parseFloat(item.price);
    const diffMeu = meuPreco ? ((precoItem - meuPreco) / meuPreco * 100) : null;
    const vendas = item.sold_quantity || 0;
    const reputacao = item.seller?.seller_reputation?.level_id || 'unknown';
    const repColors = {
      '5_green':   ['MercadoLíder Platinum','#22c55e'],
      '4_light_green':['MercadoLíder Gold','#f59e0b'],
      '3_yellow':  ['MercadoLíder','#f59e0b'],
      '2_orange':  ['Padrão','#f97316'],
      '1_red':     ['Abaixo','#ef4444'],
      'unknown':   ['—','#5E5E5A'],
    };
    const [repLabel, repColor] = repColors[reputacao] || ['—','#5E5E5A'];
    const thumb = item.thumbnail || '';
    const frete = item.shipping?.free_shipping ? '🟢 Frete grátis' : '🔴 Sem frete grátis';
    const tipoAnuncio = item.listing_type_id === 'gold_special' ? 'Premium' : (item.listing_type_id === 'free' ? 'Clássico' : item.listing_type_id);

    // Score competitivo simplificado
    const scoreVendas = Math.min(40, Math.round((vendas / Math.max(items[0].sold_quantity||1,1)) * 40));
    const scorePreco  = precoItem <= mediaP ? 30 : Math.max(0, 30 - Math.round((precoItem-mediaP)/mediaP*30));
    const scoreFrete  = item.shipping?.free_shipping ? 20 : 0;
    const scoreRep    = reputacao.includes('green') ? 10 : (reputacao.includes('yellow') ? 6 : 2);
    const score = scoreVendas + scorePreco + scoreFrete + scoreRep;

    html += `
    <div style="background:#1A1A1C;border:0.5px solid ${isLider?'#FFE600':'#2E2E33'};border-radius:10px;padding:14px;transition:border-color .15s"
      onmouseover="this.style.borderColor='${isLider?'#FFE600':'#3483FA}'" onmouseout="this.style.borderColor='${isLider?'#FFE600':'#2E2E33'}'">
      <div style="display:flex;gap:12px;align-items:flex-start">

        <!-- Posição + thumb -->
        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-shrink:0">
          <div style="width:28px;height:28px;border-radius:50%;background:${isLider?'#FFE600':'#252528'};color:${isLider?'#1A1A1A':'#5E5E5A'};display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">#${pos}</div>
          ${thumb ? `<img src="${thumb}" style="width:52px;height:52px;border-radius:6px;object-fit:cover;background:#252528" onerror="this.style.display='none'">` : ''}
        </div>

        <!-- Info principal -->
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px">
            <div>
              <a href="${item.permalink}" target="_blank" style="font-size:12px;font-weight:600;color:#E8E8E6;text-decoration:none;line-height:1.4;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px"
                onmouseover="this.style.color='#3483FA'" onmouseout="this.style.color='#E8E8E6'">
                ${item.title}
              </a>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap">
                <span style="font-size:10px;color:${repColor};font-weight:500">${repLabel}</span>
                <span style="font-size:10px;color:#5E5E5A">·</span>
                <span style="font-size:10px;color:#5E5E5A">${tipoAnuncio}</span>
                <span style="font-size:10px;color:#5E5E5A">·</span>
                <span style="font-size:10px;color:${item.shipping?.free_shipping?'#22c55e':'#ef4444'}">${frete}</span>
                ${isLider ? '<span style="font-size:9px;padding:1px 6px;border-radius:5px;background:rgba(255,230,0,.15);color:#FFE600;font-weight:700">👑 LÍDER</span>' : ''}
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-size:20px;font-weight:700;color:#E8E8E6">R$ ${precoItem.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
              ${diffMeu !== null ? `<div style="font-size:10px;color:${diffMeu>0?'#22c55e':diffMeu<0?'#ef4444':'#5E5E5A'};font-weight:600">
                ${diffMeu>0?'+':''}${diffMeu.toFixed(1)}% vs meu preço
              </div>` : ''}
            </div>
          </div>

          <!-- Métricas + score -->
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#5E5E5A">
              <i data-lucide="shopping-bag" style="width:11px;height:11px;color:#3483FA"></i>
              <strong style="color:#3483FA">${vendas.toLocaleString('pt-BR')}</strong> vendas
            </div>
            ${item.reviews?.rating_average ? `
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#5E5E5A">
              <i data-lucide="star" style="width:11px;height:11px;color:#f59e0b"></i>
              <strong style="color:#f59e0b">${parseFloat(item.reviews.rating_average).toFixed(1)}</strong>
              (${item.reviews.total || 0})
            </div>` : ''}
            ${item.available_quantity ? `
            <div style="font-size:11px;color:#5E5E5A">
              <strong style="color:#9A9A96">${item.available_quantity}</strong> em estoque
            </div>` : ''}
            <div style="margin-left:auto;display:flex;align-items:center;gap:6px">
              <!-- Score competitivo -->
              <div style="display:flex;align-items:center;gap:4px">
                <div style="font-size:9px;color:#5E5E5A">Score</div>
                <div style="width:42px;height:6px;background:#2E2E33;border-radius:3px;overflow:hidden">
                  <div style="width:${score}%;height:100%;background:${score>=70?'#22c55e':score>=40?'#f59e0b':'#ef4444'};border-radius:3px"></div>
                </div>
                <div style="font-size:10px;font-weight:700;color:${score>=70?'#22c55e':score>=40?'#f59e0b':'#ef4444'}">${score}</div>
              </div>
              <a href="${item.permalink}" target="_blank"
                style="padding:4px 10px;background:rgba(52,131,250,.1);border:0.5px solid rgba(52,131,250,.3);color:#3483FA;border-radius:6px;font-size:10px;text-decoration:none;white-space:nowrap">
                Ver anúncio ↗
              </a>
              <button onclick="monitorarVendedor('${item.seller?.id}','${(item.seller?.nickname||'').replace(/'/g,"\\'")}','${q.replace(/'/g,"\\'")}')"
                style="padding:4px 10px;background:transparent;border:0.5px solid #2E2E33;color:#5E5E5A;border-radius:6px;font-size:10px;cursor:pointer;white-space:nowrap;transition:all .15s"
                onmouseover="this.style.borderColor='#22c55e';this.style.color='#22c55e'" onmouseout="this.style.borderColor='#2E2E33';this.style.color='#5E5E5A'">
                + Monitorar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>`;
  });

  html += `</div>`;

  document.getElementById('painel-inicial').style.display  = 'none';
  document.getElementById('painel-vendedor').style.display = 'none';
  const el = document.getElementById('painel-busca');
  el.style.display = 'block';
  el.innerHTML = html;
  lucide.createIcons();
}

// ── Análise de vendedor por nickname ─────────────────────
async function analisarVendedor() {
  const nickname = document.getElementById('busca-seller').value.trim();
  if (!nickname) { toast('Digite o nickname do vendedor', 'error'); return; }

  mostrarLoading();
  const r = await fetch(`/api/concorrentes.php?action=vendedor&nickname=${encodeURIComponent(nickname)}`);
  const d = await r.json();
  esconderLoading();

  if (!d.ok) { toast(d.error || 'Vendedor não encontrado', 'error'); mostrarInicial(); return; }

  renderVendedor(d);
}

function renderVendedor(d) {
  const v = d.vendor;
  const rep = v.seller_reputation || {};
  const trans = rep.transactions || {};
  const level = rep.level_id || 'unknown';
  const repColors = {
    '5_green':       ['MercadoLíder Platinum','#22c55e'],
    '4_light_green': ['MercadoLíder Gold','#f59e0b'],
    '3_yellow':      ['MercadoLíder','#f59e0b'],
    '2_orange':      ['Padrão','#f97316'],
    '1_red':         ['Abaixo do padrão','#ef4444'],
  };
  const [repLabel, repColor] = repColors[level] || ['Sem reputação','#5E5E5A'];

  const items = d.items || [];
  const totalVendas = items.reduce((a,i)=>a+(i.sold_quantity||0),0);
  const totalReceita = items.reduce((a,i)=>a+(i.sold_quantity||0)*(parseFloat(i.price)||0),0);
  const precos = items.map(i=>parseFloat(i.price)).filter(p=>p>0);
  const ticketMedio = precos.length ? precos.reduce((a,b)=>a+b,0)/precos.length : 0;

  let html = `
    <div style="background:#1A1A1C;border:0.5px solid #2E2E33;border-radius:12px;overflow:hidden">

      <!-- Header do vendedor -->
      <div style="padding:16px 20px;background:#1E1E21;border-bottom:0.5px solid #2E2E33;display:flex;align-items:center;gap:14px">
        <div style="width:48px;height:48px;border-radius:50%;background:rgba(52,131,250,.15);color:#3483FA;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0">
          ${(v.nickname||'?')[0].toUpperCase()}
        </div>
        <div style="flex:1">
          <div style="font-size:15px;font-weight:700;color:#E8E8E6">${v.nickname}</div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:3px;flex-wrap:wrap">
            <span style="font-size:10px;padding:2px 8px;border-radius:6px;background:${repColor}20;color:${repColor};font-weight:600">${repLabel}</span>
            ${v.registration_date ? `<span style="font-size:10px;color:#5E5E5A">Desde ${new Date(v.registration_date).getFullYear()}</span>` : ''}
            <a href="https://www.mercadolivre.com.br/perfil/${v.nickname}" target="_blank"
              style="font-size:10px;color:#3483FA;text-decoration:none">Ver no ML ↗</a>
          </div>
        </div>
        <button onclick="monitorarVendedor('${v.id}','${(v.nickname||'').replace(/'/g,"\\'")}','')"
          style="padding:7px 14px;background:rgba(34,197,94,.1);border:0.5px solid #22c55e;color:#22c55e;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap">
          + Monitorar
        </button>
      </div>

      <!-- KPIs do vendedor -->
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:0;border-bottom:0.5px solid #2E2E33">
        ${[
          ['Anúncios ativos', items.length, '#3483FA'],
          ['Total de vendas', totalVendas.toLocaleString('pt-BR'), '#22c55e'],
          ['Receita estimada', 'R$ '+totalReceita.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}), '#a855f7'],
          ['Ticket médio', 'R$ '+ticketMedio.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}), '#f59e0b'],
          ['Avaliações', trans.total?.toLocaleString('pt-BR')||'—', '#f97316'],
          ['Reputação', `${((1-(rep.metrics?.claims?.rate||0))*100).toFixed(0)}% ok`, repColor],
        ].map(([l,v,c])=>`
        <div style="padding:12px 16px;border-right:0.5px solid #2E2E33;text-align:center">
          <div style="font-size:9px;color:#5E5E5A;margin-bottom:3px">${l}</div>
          <div style="font-size:15px;font-weight:700;color:${c}">${v}</div>
        </div>`).join('')}
      </div>

      <!-- Anúncios do vendedor -->
      <div style="padding:14px 16px">
        <div style="font-size:11px;font-weight:600;color:#5E5E5A;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px">
          Top anúncios (${items.length})
        </div>
        <div style="display:flex;flex-direction:column;gap:6px">
          ${items.slice(0,15).map((item,idx)=>`
          <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:#252528;border-radius:8px;transition:background .12s"
            onmouseover="this.style.background='#2E2E35'" onmouseout="this.style.background='#252528'">
            <div style="font-size:10px;color:#5E5E5A;width:18px;text-align:center;flex-shrink:0">#${idx+1}</div>
            <div style="flex:1;min-width:0">
              <a href="${item.permalink}" target="_blank"
                style="font-size:11px;color:#E8E8E6;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block"
                onmouseover="this.style.color='#3483FA'" onmouseout="this.style.color='#E8E8E6'">
                ${item.title}
              </a>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-size:12px;font-weight:700;color:#E8E8E6">R$ ${parseFloat(item.price).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
              <div style="font-size:9px;color:#3483FA">${(item.sold_quantity||0).toLocaleString('pt-BR')} vendas</div>
            </div>
          </div>`).join('')}
        </div>
      </div>
    </div>`;

  document.getElementById('painel-inicial').style.display = 'none';
  document.getElementById('painel-busca').style.display   = 'none';
  const el = document.getElementById('painel-vendedor');
  el.style.display = 'block';
  el.innerHTML = html;
  lucide.createIcons();
}

// ── Monitorar vendedor ────────────────────────────────────
async function monitorarVendedor(sellerId, nickname, categoria) {
  if (!nickname) return;
  const fd = new FormData();
  fd.append('action',    'monitorar');
  fd.append('seller_id', sellerId);
  fd.append('nickname',  nickname);
  fd.append('categoria', categoria);
  const r = await fetch('/api/concorrentes.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) {
    toast(`✅ ${nickname} adicionado ao monitoramento`, 'success');
    setTimeout(() => location.reload(), 1000);
  } else {
    toast(d.error || 'Erro', 'error');
  }
}

async function removerMonitor(id) {
  const fd = new FormData();
  fd.append('action', 'remover');
  fd.append('id', id);
  const r = await fetch('/api/concorrentes.php', {method:'POST', body:fd});
  const d = await r.json();
  if (d.ok) location.reload();
}

function cruzarProduto() {
  const sel = document.getElementById('meu-produto');
  meuProdutoSel = sel.value ? sel.options[sel.selectedIndex] : null;
}

function mostrarLoading() {
  document.getElementById('painel-inicial').style.display  = 'none';
  document.getElementById('painel-busca').style.display    = 'none';
  document.getElementById('painel-vendedor').style.display = 'none';
  document.getElementById('painel-loading').style.display  = 'block';
}
function esconderLoading() {
  document.getElementById('painel-loading').style.display = 'none';
}
function mostrarInicial() {
  document.getElementById('painel-inicial').style.display = 'block';
}
</script>
<style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

<?php include __DIR__ . '/layout_end.php'; ?>
