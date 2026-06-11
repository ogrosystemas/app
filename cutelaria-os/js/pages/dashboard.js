import { db, fmtBRL, fmtDate, TIPO_FINANCEIRO, STATUS_PRODUCAO, STATUS_PEDIDO } from '../database/db.js';

export async function dashboardPage() {

  const [producao, pedidos, financeiro, materiais, settings] = await Promise.all([
    db.producao  ? db.producao.toArray()   : [],
    db.pedidos   ? db.pedidos.toArray()    : [],
    db.financeiro? db.financeiro.toArray() : [],
    db.materiais ? db.materiais.toArray()  : [],
    db.settings  ? db.settings.toCollection().first() : null
  ]);

  const nomeOficina = settings?.oficinaNome || 'Minha Oficina';

  // KPIs
  const producaoAtiva  = producao.filter(i => i.status !== STATUS_PRODUCAO.FINALIZADA).length;
  const pedidosAbertos = pedidos.filter(i => i.status === STATUS_PEDIDO.ABERTO || i.status === STATUS_PEDIDO.EM_PRODUCAO).length;
  const receitas = financeiro.filter(i => i.tipo === TIPO_FINANCEIRO.RECEITA).reduce((s,i) => s + Number(i.valor||0), 0);
  const despesas = financeiro.filter(i => i.tipo === TIPO_FINANCEIRO.DESPESA).reduce((s,i) => s + Number(i.valor||0), 0);
  const saldo = receitas - despesas;

  // Estoque crítico
  const criticos = materiais.filter(i => Number(i.estoqueAtual||0) <= Number(i.estoqueMinimo||0) && Number(i.estoqueMinimo||0) > 0);

  // Recentes
  const producaoRecente = [...producao].sort((a,b) => b.id - a.id).slice(0, 3);
  const pedidosRecentes = [...pedidos].sort((a,b) => b.id - a.id).slice(0, 3);

  // Pedidos com prazo próximo (próximos 7 dias)
  const hoje = new Date();
  const em7dias = new Date(hoje.getTime() + 7*24*60*60*1000);
  const prazoProximo = pedidos.filter(p => {
    if (!p.prazo || p.status === STATUS_PEDIDO.CONCLUIDO) return false;
    const d = new Date(p.prazo + 'T00:00:00');
    return d <= em7dias;
  }).sort((a,b) => new Date(a.prazo) - new Date(b.prazo));

  const saldoColor = saldo >= 0 ? '#34d399' : '#f87171';

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
    <section class="pb-4">

      <!-- SAUDAÇÃO -->
      <div style="margin-bottom:28px">
        <p style="color:var(--muted);font-size:14px;margin-bottom:4px">${nomeOficina}</p>
        <h1 style="font-size:clamp(26px,6vw,40px);font-weight:900;line-height:1.1">Dashboard</h1>
      </div>

      <!-- ALERTAS -->
      ${criticos.length ? `
        <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:12px">
          <i data-lucide="alert-triangle" style="width:20px;height:20px;color:#f87171;flex-shrink:0"></i>
          <div>
            <div style="font-weight:700;font-size:14px;color:#f87171">${criticos.length} material${criticos.length>1?'is':''} crítico${criticos.length>1?'s':''}</div>
            <div style="font-size:12px;color:var(--muted)">${criticos.slice(0,3).map(c=>c.nome).join(', ')}${criticos.length>3?' +mais...':''}</div>
          </div>
          <a href="#materiais" style="margin-left:auto;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;flex-shrink:0">Ver →</a>
        </div>
      ` : ''}

      ${prazoProximo.length ? `
        <div style="background:rgba(249,115,22,.08);border:1px solid rgba(249,115,22,.25);border-radius:var(--radius-md);padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:12px">
          <i data-lucide="clock" style="width:20px;height:20px;color:#fb923c;flex-shrink:0"></i>
          <div>
            <div style="font-weight:700;font-size:14px;color:#fb923c">${prazoProximo.length} pedido${prazoProximo.length>1?'s':''} com prazo próximo</div>
            <div style="font-size:12px;color:var(--muted)">${prazoProximo[0].nome} — prazo: ${fmtDate(prazoProximo[0].prazo + 'T00:00:00')}</div>
          </div>
          <a href="#pedidos" style="margin-left:auto;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600;flex-shrink:0">Ver →</a>
        </div>
      ` : ''}

      <!-- KPIs -->
      <div class="grid-2" style="gap:12px;margin-bottom:12px">
        <div class="kpi-card">
          <div class="kpi-card__label"><i data-lucide="hammer" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Produção ativa</div>
          <div class="kpi-card__value" style="color:var(--accent)">${producaoAtiva}</div>
          <div class="kpi-card__sub">${producao.length} total</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label"><i data-lucide="shopping-bag" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Pedidos abertos</div>
          <div class="kpi-card__value" style="color:#c084fc">${pedidosAbertos}</div>
          <div class="kpi-card__sub">${pedidos.length} total</div>
        </div>
      </div>
      <div class="grid-2" style="gap:12px;margin-bottom:28px">
        <div class="kpi-card">
          <div class="kpi-card__label"><i data-lucide="trending-up" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Receitas</div>
          <div class="kpi-card__value" style="font-size:18px;color:#34d399">${fmtBRL(receitas)}</div>
          <div class="kpi-card__sub">Saldo: <span style="color:${saldoColor};font-weight:700">${fmtBRL(saldo)}</span></div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label"><i data-lucide="package" style="width:14px;height:14px;vertical-align:-2px;margin-right:5px"></i>Materiais</div>
          <div class="kpi-card__value">${materiais.length}</div>
          <div class="kpi-card__sub">${criticos.length > 0 ? `<span style="color:#f87171">${criticos.length} crítico${criticos.length>1?'s':''}</span>` : 'estoque ok'}</div>
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
            <div class="icon-box icon-box-orange">
              <i data-lucide="flame"></i>
            </div>
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
            <div class="icon-box icon-box-purple">
              <i data-lucide="shopping-bag"></i>
            </div>
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
