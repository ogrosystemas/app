// ============================================================
// pages/financeiro.js — Dashboard financeiro completo
// ============================================================

import { render } from '../js/app.js';
import { getAll } from '../js/db.js';
import { moeda, dataLocal } from '../js/calculadora.js';

export default async function financeiroPage() {
  const orcamentos  = await getAll('orcamentos', 'data');
  const pagamentos  = await getAll('pagamentos', 'data');
  const clientes    = await getAll('clientes');

  // Mapas para lookup rápido
  const clienteMap  = {};
  clientes.forEach(c => { clienteMap[c.id] = c.nome; });

  // Força orcamentoId como number para garantir lookup correto (IDB pode retornar string)
  const pagPorOrc = {};
  pagamentos.forEach(p => {
    const key = parseInt(p.orcamentoId);
    if (!pagPorOrc[key]) pagPorOrc[key] = [];
    pagPorOrc[key].push(p);
  });

  // ── Cálculos globais ──────────────────────────────────────
  // Pipeline: orçamentos ativos (para faturamento)
  const ativos       = orcamentos.filter(o => ['aprovado','em andamento','finalizado'].includes(o.status));
  const totalFaturado = ativos.reduce((s, o) => s + (o.total || 0), 0);
  const totalRecebido = pagamentos.reduce((s, p) => s + p.valor, 0);
  const totalAReceber = Math.max(0, totalFaturado - totalRecebido);

  // Inadimplentes: apenas orçamentos FINALIZADOS com saldo pendente
  // Usa tolerância de R$0,01 para evitar erro de ponto flutuante
  const inadimplentes = orcamentos.filter(o => {
    if (o.status !== 'finalizado') return false;
    const pago = (pagPorOrc[o.id] || []).reduce((s, p) => s + p.valor, 0);
    return (o.total - pago) > 0.01;
  });

  // ── Receita por mês (últimos 6 meses) ─────────────────────
  const hoje     = new Date();
  const meses    = [];
  for (let i = 5; i >= 0; i--) {
    const d = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
    meses.push({
      label: d.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }),
      ano:   d.getFullYear(),
      mes:   d.getMonth(),
      total: 0,
      recebido: 0,
    });
  }

  pagamentos.forEach(p => {
    const d = new Date(p.data);
    const m = meses.find(m => m.ano === d.getFullYear() && m.mes === d.getMonth());
    if (m) m.recebido += p.valor;
  });

  orcamentos.forEach(o => {
    if (!['aprovado','em andamento','finalizado'].includes(o.status)) return;
    const d = new Date(o.data);
    const m = meses.find(m => m.ano === d.getFullYear() && m.mes === d.getMonth());
    if (m) m.total += o.total || 0;
  });

  const maxVal  = Math.max(...meses.map(m => Math.max(m.total, m.recebido)), 1);

  // ── Receita por profissão ──────────────────────────────────
  const porProf = {};
  orcamentos.forEach(o => {
    if (!['aprovado','em andamento','finalizado'].includes(o.status)) return;
    const k = o.profissaoNome || 'Outros';
    if (!porProf[k]) porProf[k] = { total: 0, recebido: 0, qtd: 0 };
    porProf[k].total += o.total || 0;
    porProf[k].qtd++;
    const pago = (pagPorOrc[o.id] || []).reduce((s, p) => s + p.valor, 0);
    porProf[k].recebido += pago;
  });

  // ── Filtro de período ──────────────────────────────────────
  let filtroStatus = 'todos';

  render(`
    <div class="page-content pb-5">
      <h4 class="fw-bold mb-4">Financeiro</h4>

      <!-- Cards resumo -->
      <div class="row g-3 mb-4">
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Total Faturado</div>
              <div class="fw-bold fs-5 text-primary">${moeda(totalFaturado)}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Recebido</div>
              <div class="fw-bold fs-5 text-success">${moeda(totalRecebido)}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">A Receber</div>
              <div class="fw-bold fs-5 ${totalAReceber > 0 ? 'text-danger' : 'text-success'}">${moeda(totalAReceber)}</div>
            </div>
          </div>
        </div>
        <div class="col-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small mb-1">Inadimplentes</div>
              <div class="fw-bold fs-5 ${inadimplentes.length > 0 ? 'text-warning' : 'text-success'}">${inadimplentes.length}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de barras — receita mensal -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-3">Receita últimos 6 meses</div>
          <div class="d-flex align-items-end gap-2" style="height:100px">
            ${meses.map(m => {
              const hTotal    = m.total > 0    ? Math.round((m.total    / maxVal) * 90) : 2;
              const hRecebido = m.recebido > 0 ? Math.round((m.recebido / maxVal) * 90) : 0;
              return `
                <div class="flex-fill d-flex flex-column align-items-center gap-1" style="min-width:0">
                  <div class="w-100 d-flex align-items-end gap-1 justify-content-center" style="height:90px">
                    <div style="width:45%;height:${hTotal}px;background:#2563eb33;border-radius:3px 3px 0 0" title="Faturado: ${moeda(m.total)}"></div>
                    <div style="width:45%;height:${hRecebido}px;background:#16a34a;border-radius:3px 3px 0 0" title="Recebido: ${moeda(m.recebido)}"></div>
                  </div>
                  <div class="text-muted" style="font-size:0.6rem;white-space:nowrap">${m.label}</div>
                </div>`;
            }).join('')}
          </div>
          <div class="d-flex gap-3 mt-2">
            <div class="d-flex align-items-center gap-1"><div style="width:12px;height:12px;background:#2563eb33;border-radius:2px"></div><span class="small text-muted">Faturado</span></div>
            <div class="d-flex align-items-center gap-1"><div style="width:12px;height:12px;background:#16a34a;border-radius:2px"></div><span class="small text-muted">Recebido</span></div>
          </div>
        </div>
      </div>

      <!-- Por profissão -->
      ${Object.keys(porProf).length > 0 ? `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="fw-semibold mb-3">Por Profissão</div>
            ${Object.entries(porProf).sort((a,b) => b[1].total - a[1].total).map(([nome, d]) => {
              const pct = totalFaturado > 0 ? Math.round((d.total / totalFaturado) * 100) : 0;
              return `
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-semibold">${nome}</span>
                    <span class="small text-muted">${d.qtd} orç. · ${moeda(d.total)}</span>
                  </div>
                  <div class="progress" style="height:6px">
                    <div class="progress-bar bg-primary" style="width:${pct}%"></div>
                  </div>
                  <div class="d-flex justify-content-between mt-1">
                    <span class="small text-success">Recebido: ${moeda(d.recebido)}</span>
                    <span class="small text-muted">${pct}%</span>
                  </div>
                </div>`;
            }).join('')}
          </div>
        </div>
      ` : ''}

      <!-- Inadimplência -->
      ${inadimplentes.length > 0 ? `
        <div class="card border-danger border mb-3">
          <div class="card-body">
            <div class="fw-semibold text-danger mb-3">
              <i class="bi bi-exclamation-triangle me-2"></i>A Receber (${inadimplentes.length})
            </div>
            ${inadimplentes.map(o => {
              const pago      = (pagPorOrc[o.id] || []).reduce((s, p) => s + p.valor, 0);
              const restante  = o.total - pago;
              return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"
                  onclick="navigate('/visualizar', {id: ${o.id}})" style="cursor:pointer">
                  <div>
                    <div class="fw-semibold">${clienteMap[o.clienteId] || 'Cliente removido'}</div>
                    <div class="small text-muted">#${o.id} · ${dataLocal(o.data)} · <span class="text-capitalize">${o.status}</span></div>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-danger">${moeda(restante)}</div>
                    <div class="small text-muted">de ${moeda(o.total)}</div>
                  </div>
                </div>`;
            }).join('')}
          </div>
        </div>
      ` : `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body text-center py-4">
            <i class="bi bi-check-circle text-success display-6"></i>
            <p class="text-muted mt-2 mb-0">Nenhuma inadimplência!</p>
          </div>
        </div>
      `}

      <!-- Histórico de pagamentos -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-3">Últimos Pagamentos</div>
          ${pagamentos.length === 0 ? `
            <div class="text-muted small text-center py-3">Nenhum pagamento registrado.</div>
          ` : [...pagamentos].reverse().slice(0, 20).map(p => {
            const orc = orcamentos.find(o => o.id === p.orcamentoId);
            return `
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom"
                onclick="navigate('/visualizar', {id: ${p.orcamentoId}})" style="cursor:pointer">
                <div>
                  <div class="small fw-semibold">${p.descricao || 'Pagamento'}</div>
                  <div class="small text-muted">
                    ${clienteMap[orc?.clienteId] || '—'} · Orç. #${p.orcamentoId} · ${dataLocal(p.data)}
                  </div>
                </div>
                <span class="fw-bold text-success">${moeda(p.valor)}</span>
              </div>`;
          }).join('')}
        </div>
      </div>
    </div>
  `);
}
