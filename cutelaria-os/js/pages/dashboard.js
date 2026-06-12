import { db, fmtBRL, fmtDate, TIPO_FINANCEIRO, STATUS_PRODUCAO, STATUS_PEDIDO } from '../database/db.js';

export async function dashboardPage() {

  const [producao, pedidos, financeiro, materiais, settings] = await Promise.all([
    db.producao   ? db.producao.toArray()   : [],
    db.pedidos    ? db.pedidos.toArray()    : [],
    db.financeiro ? db.financeiro.toArray() : [],
    db.materiais  ? db.materiais.toArray()  : [],
    db.settings   ? db.settings.toCollection().first() : null
  ]);

  const nomeOficina = settings?.oficinaNome || 'Minha Oficina';

  // ── KPIs ───────────────────────────────────────────────
  const producaoAtiva  = producao.filter(i => i.status !== STATUS_PRODUCAO.FINALIZADA).length;
  const pedidosAbertos = pedidos.filter(i => i.status === STATUS_PEDIDO.ABERTO || i.status === STATUS_PEDIDO.EM_PRODUCAO).length;
  const criticos       = materiais.filter(i => Number(i.estoqueAtual||0) <= Number(i.estoqueMinimo||0) && Number(i.estoqueMinimo||0) > 0);

  // ── Financeiro: mês atual vs total ─────────────────────
  const hoje     = new Date();
  const anoAtual = hoje.getFullYear();
  const mesAtual = hoje.getMonth();
  const nomeMes  = hoje.toLocaleDateString('pt-BR', { month: 'long' });

  const doMes   = financeiro.filter(i => {
    if (!i.createdAt) return false;
    const d = new Date(i.createdAt);
    return d.getFullYear() === anoAtual && d.getMonth() === mesAtual;
  });
  const total   = financeiro;

  function somarTipo(lista, tipo) {
    return lista.filter(i => i.tipo === tipo).reduce((s,i) => s + Number(i.valor||0), 0);
  }

  const receitasMes   = somarTipo(doMes,  TIPO_FINANCEIRO.RECEITA);
  const despesasMes   = somarTipo(doMes,  TIPO_FINANCEIRO.DESPESA);
  const saldoMes      = receitasMes - despesasMes;

  const receitasTotal = somarTipo(total, TIPO_FINANCEIRO.RECEITA);
  const despesasTotal = somarTipo(total, TIPO_FINANCEIRO.DESPESA);
  const saldoTotal    = receitasTotal - despesasTotal;

  // Estado do toggle — persiste na sessão via dataset no DOM
  const mostrarTotal = sessionStorage.getItem('dash_fin_total') === '1';
  const receitas     = mostrarTotal ? receitasTotal : receitasMes;
  const despesas     = mostrarTotal ? despesasTotal : despesasMes;
  const saldo        = mostrarTotal ? saldoTotal    : saldoMes;
  const saldoColor   = saldo >= 0 ? '#34d399' : '#f87171';
  const periodoLabel = mostrarTotal ? 'Total histórico' : `${nomeMes.charAt(0).toUpperCase() + nomeMes.slice(1)} ${anoAtual}`;

  // ── Alertas ────────────────────────────────────────────
  const em7dias      = new Date(hoje.getTime() + 7*24*60*60*1000);
  const prazoProximo = pedidos.filter(p => {
    if (!p.prazo || p.status === STATUS_PEDIDO.CONCLUIDO) return false;
    return new Date(p.prazo + 'T00:00:00') <= em7dias;
  }).sort((a,b) => new Date(a.prazo) - new Date(b.prazo));

  // ── Recentes ───────────────────────────────────────────
  const producaoRecente = [...producao].sort((a,b) => b.id - a.id).slice(0, 3);
  const pedidosRecentes = [...pedidos].sort((a,b) => b.id - a.id).slice(0, 3);

  const statusBadge = (status) => {
    const map = {
      [STATUS_PRODUCAO.INICIADA]:   'badge-gray',
      [STATUS_PRODUCAO.FORJAMENTO]: 'badge-orange',
      [STATUS_PRODUCAO.TEMPERA]:    'badge-blue',
      [STATUS_PRODUCAO.ACABAMENTO]: 'badge-purple',
      [STATUS_PRODUCAO.FINALIZADA]: 'badge-green',
      [STATUS_PEDIDO.ABERTO]:       'badge-orange',
      [STATUS_PEDIDO.EM_PRODUCAO]:  'badge-blue',
      [STATUS_PEDIDO.CONCLUIDO]:    'badge-green',
      [STATUS_PEDIDO.CANCELADO]:    'badge-red',
    };
    return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
  };

  return `
    <section>

      <div style="margin-bottom:28px">
        <p style="color:var(--muted);font-size:14px;margin-bottom:4px">${nomeOficina}</p>
        <h1 style="font-size:clamp(26px,6vw,40px);font-weight:900;line-height:1.1">Dashboard</h1>
      </div>

      <!-- ALERTAS -->
      ${criticos.length ? `
        <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px">
          <i class="ph ph-warning" style="width:18px;height:18px;color:#f87171;flex-shrink:0"></i>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:14px;color:#f87171">${criticos.length} material${criticos.length>1?'is':''} crítico${criticos.length>1?'s':''}</div>
            <div style="font-size:12px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${criticos.slice(0,3).map(c=>c.nome).join(', ')}</div>
          </div>
          <a href="#materiais" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;flex-shrink:0">Ver →</a>
        </div>
      ` : ''}

      ${prazoProximo.length ? `
        <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px">
          <i class="ph ph-clock" style="width:18px;height:18px;color:#fb923c;flex-shrink:0"></i>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:14px;color:#fb923c">${prazoProximo.length} pedido${prazoProximo.length>1?'s':''} com prazo próximo</div>
            <div style="font-size:12px;color:var(--muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${prazoProximo[0].nome} — prazo: ${fmtDate(prazoProximo[0].prazo + 'T00:00:00')}</div>
          </div>
          <a href="#pedidos" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;flex-shrink:0">Ver →</a>
        </div>
      ` : ''}

      <div style="margin-bottom:20px"></div>

      <!-- KPIs OPERACIONAIS -->
      <div class="grid-2" style="gap:12px;margin-bottom:12px">
        <div class="kpi-card">
          <div class="kpi-card__label"><i class="ph ph-hammer" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px"></i>Produção ativa</div>
          <div class="kpi-card__value" style="color:var(--accent)">${producaoAtiva}</div>
          <div class="kpi-card__sub">${producao.length} total</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label"><i class="ph ph-shopping-bag" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px"></i>Pedidos abertos</div>
          <div class="kpi-card__value" style="color:#c084fc">${pedidosAbertos}</div>
          <div class="kpi-card__sub">${pedidos.length} total</div>
        </div>
      </div>

      <!-- KPI FINANCEIRO COM TOGGLE DE PERÍODO -->
      <div class="kpi-card" style="margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div class="kpi-card__label" style="margin-bottom:0">
            <i class="ph ph-trend-up" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px"></i>
            Receitas — <span style="color:var(--accent)">${periodoLabel}</span>
          </div>
          <button id="toggleFinPeriod" style="
            background:rgba(255,255,255,.06);border:1px solid var(--border);
            border-radius:10px;padding:4px 10px;font-size:11px;font-weight:600;
            color:var(--muted);cursor:pointer;white-space:nowrap
          ">${mostrarTotal ? 'Ver mês atual' : 'Ver total'}</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
          <div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:4px">Receitas</div>
            <div style="font-size:clamp(14px,3.5vw,20px);font-weight:900;color:#34d399;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${fmtBRL(receitas)}</div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:4px">Despesas</div>
            <div style="font-size:clamp(14px,3.5vw,20px);font-weight:900;color:#f87171;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${fmtBRL(despesas)}</div>
          </div>
          <div>
            <div style="font-size:11px;color:var(--muted);margin-bottom:4px">Saldo</div>
            <div style="font-size:clamp(14px,3.5vw,20px);font-weight:900;color:${saldoColor};white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${fmtBRL(saldo)}</div>
          </div>
        </div>
      </div>

      <!-- PRODUÇÃO RECENTE -->
      <div class="card" style="margin-bottom:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-size:17px;font-weight:800">Produção recente</h2>
          <a href="#producao" style="font-size:13px;color:var(--accent);text-decoration:none;font-weight:600">Ver tudo →</a>
        </div>
        ${producaoRecente.length ? producaoRecente.map(item => `
          <div class="list-item">
            <div class="icon-box icon-box-orange"><i class="ph ph-flame"></i></div>
            <div class="list-item__info">
              <div class="list-item__title">${item.nome || 'Faca artesanal'}</div>
              <div class="list-item__sub">${fmtDate(item.createdAt)}</div>
            </div>
            <div class="list-item__right">
              ${statusBadge(item.status)}
              <div style="font-size:12px;color:var(--accent);font-weight:700;margin-top:4px">${item.progresso||0}%</div>
            </div>
          </div>
        `).join('') : `<p style="color:var(--muted);text-align:center;padding:24px 0;font-size:14px">Nenhuma produção iniciada.</p>`}
      </div>

      <!-- PEDIDOS RECENTES -->
      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-size:17px;font-weight:800">Pedidos recentes</h2>
          <a href="#pedidos" style="font-size:13px;color:var(--accent);text-decoration:none;font-weight:600">Ver tudo →</a>
        </div>
        ${pedidosRecentes.length ? pedidosRecentes.map(item => `
          <div class="list-item">
            <div class="icon-box icon-box-purple"><i class="ph ph-shopping-bag"></i></div>
            <div class="list-item__info">
              <div class="list-item__title">${item.nome || 'Pedido'}</div>
              <div class="list-item__sub">${item.cliente || 'Cliente não informado'}</div>
            </div>
            <div class="list-item__right">
              ${statusBadge(item.status)}
              <div style="font-size:13px;font-weight:700;margin-top:4px">${fmtBRL(item.valor)}</div>
            </div>
          </div>
        `).join('') : `<p style="color:var(--muted);text-align:center;padding:24px 0;font-size:14px">Nenhum pedido cadastrado.</p>`}
      </div>

    </section>
  `;
}

// Toggle mês atual / total histórico
window.addEventListener('click', (e) => {
  if (e.target.id !== 'toggleFinPeriod') return;
  const atual = sessionStorage.getItem('dash_fin_total') === '1';
  sessionStorage.setItem('dash_fin_total', atual ? '0' : '1');
  // Re-renderiza só o dashboard
  import('./dashboard.js').then(m => {
    m.dashboardPage().then(html => {
      const content = document.getElementById('pageContent');
      if (content) {
        content.innerHTML = html;
      }
    });
  });
});
