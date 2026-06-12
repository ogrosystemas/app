import { db, fmtBRL, fmtDate, TIPO_FINANCEIRO } from '../database/db.js';
import { showToast } from '../components/toast.js';
import { navigate  } from '../core/router.js';

// ============================================
// DADOS DOS ÚLTIMOS N MESES
// ============================================

function buildChartData(registros, meses = 6) {
  const hoje   = new Date();
  const labels = [];
  const rec    = [];
  const desp   = [];

  for (let i = meses - 1; i >= 0; i--) {
    const d    = new Date(hoje.getFullYear(), hoje.getMonth() - i, 1);
    const ano  = d.getFullYear();
    const mes  = d.getMonth();
    const label= d.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' })
                  .replace('.', '').replace(' de ', '/');

    const doMes = registros.filter(r => {
      if (!r.createdAt) return false;
      const rd = new Date(r.createdAt);
      return rd.getFullYear() === ano && rd.getMonth() === mes;
    });

    labels.push(label);
    rec.push( doMes.filter(r => r.tipo === TIPO_FINANCEIRO.RECEITA).reduce((s,r) => s + Number(r.valor||0), 0));
    desp.push(doMes.filter(r => r.tipo === TIPO_FINANCEIRO.DESPESA).reduce((s,r) => s + Number(r.valor||0), 0));
  }

  return { labels, rec, desp };
}

// ============================================
// RENDERIZA O GRÁFICO (chamado após HTML no DOM)
// ============================================

function renderChart(registros) {
  const canvas = document.getElementById('finChart');
  if (!canvas || typeof Chart === 'undefined') return;

  const { labels, rec, desp } = buildChartData(registros, 6);

  // Destrói instância anterior se existir (ao navegar de volta)
  if (canvas._chartInstance) {
    canvas._chartInstance.destroy();
  }

  const ctx = canvas.getContext('2d');

  canvas._chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Receitas',
          data: rec,
          backgroundColor: 'rgba(52,211,153,.75)',
          borderColor:     'rgba(52,211,153,1)',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: 'Despesas',
          data: desp,
          backgroundColor: 'rgba(248,113,113,.65)',
          borderColor:     'rgba(248,113,113,1)',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          labels: {
            color: '#94a3b8',
            font:  { size: 12, family: 'Inter, system-ui, sans-serif' },
            boxWidth: 12,
            boxHeight: 12,
            borderRadius: 4,
          },
        },
        tooltip: {
          backgroundColor: 'rgba(4,9,26,.95)',
          borderColor:     'rgba(255,255,255,.1)',
          borderWidth: 1,
          titleColor: '#f8fafc',
          bodyColor:  '#94a3b8',
          padding: 12,
          callbacks: {
            label: (ctx) => ` ${ctx.dataset.label}: ${fmtBRL(ctx.parsed.y)}`,
          },
        },
      },
      scales: {
        x: {
          grid:  { color: 'rgba(255,255,255,.04)' },
          ticks: { color: '#64748b', font: { size: 11 } },
        },
        y: {
          grid:  { color: 'rgba(255,255,255,.06)' },
          ticks: {
            color: '#64748b',
            font:  { size: 11 },
            callback: (v) => {
              if (v >= 1000) return 'R$' + (v/1000).toFixed(0) + 'k';
              return 'R$' + v;
            },
          },
          beginAtZero: true,
        },
      },
    },
  });
}

// ============================================
// PAGE
// ============================================

export async function financeiroPage() {

  const registros = await db.financeiro.orderBy('createdAt').reverse().toArray();

  const receitas = registros.filter(i => i.tipo === TIPO_FINANCEIRO.RECEITA).reduce((s,i) => s + Number(i.valor||0), 0);
  const despesas = registros.filter(i => i.tipo === TIPO_FINANCEIRO.DESPESA).reduce((s,i) => s + Number(i.valor||0), 0);
  const saldo    = receitas - despesas;
  const saldoPos = saldo >= 0;

  const badgeClass = (tipo) => tipo === TIPO_FINANCEIRO.RECEITA ? 'badge-green' : 'badge-red';
  const signal     = (tipo) => tipo === TIPO_FINANCEIRO.RECEITA ? '+' : '-';

  // Agendar renderização do gráfico após o HTML estar no DOM
  setTimeout(() => renderChart(registros), 50);

  return `
    <section>

      <div class="page-header">
        <div>
          <h1>Financeiro</h1>
          <p>Receitas e despesas</p>
        </div>
      </div>

      <!-- KPIs -->
      <div class="grid-3" style="margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">Receitas</div>
          <div class="kpi-card__value" style="color:#34d399;font-size:18px">${fmtBRL(receitas)}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Despesas</div>
          <div class="kpi-card__value" style="color:#f87171;font-size:18px">${fmtBRL(despesas)}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Saldo</div>
          <div class="kpi-card__value" style="color:${saldoPos?'#34d399':'#f87171'};font-size:18px">${fmtBRL(saldo)}</div>
        </div>
      </div>

      <!-- GRÁFICO -->
      <div class="card" style="margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-size:17px;font-weight:800">Últimos 6 meses</h2>
          <div style="display:flex;gap:12px">
            <span style="font-size:12px;color:#34d399;display:flex;align-items:center;gap:5px">
              <span style="width:10px;height:10px;background:#34d399;border-radius:3px;display:inline-block"></span>Receitas
            </span>
            <span style="font-size:12px;color:#f87171;display:flex;align-items:center;gap:5px">
              <span style="width:10px;height:10px;background:#f87171;border-radius:3px;display:inline-block"></span>Despesas
            </span>
          </div>
        </div>
        <div style="position:relative;height:220px">
          <canvas id="finChart"></canvas>
        </div>
        ${registros.length === 0 ? `
          <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:12px">
            Nenhum lançamento ainda — o gráfico aparece conforme você registra.
          </p>
        ` : ''}
      </div>

      <!-- NOVO LANÇAMENTO -->
      <div class="card" style="margin-bottom:20px">
        <h2 style="font-size:17px;font-weight:800;margin-bottom:18px">Novo lançamento</h2>
        <form id="financeiroForm">
          <label>Tipo</label>
          <select id="tipo">
            <option value="${TIPO_FINANCEIRO.RECEITA}">Receita</option>
            <option value="${TIPO_FINANCEIRO.DESPESA}">Despesa</option>
          </select>

          <label>Descrição</label>
          <input type="text" id="descricao" placeholder="Ex: Venda faca hunter" />

          <label>Categoria</label>
          <input type="text" id="categoria" placeholder="Ex: Vendas, Material, Energia..." />

          <label>Valor (R$)</label>
          <input type="number" step="0.01" min="0" id="valor" placeholder="0,00" />

          <label>Data de referência</label>
          <input type="date" id="vencimento" />

          <button class="primary-button" type="submit">Salvar lançamento</button>
        </form>
      </div>

      <!-- HISTÓRICO -->
      <div class="card">
        <h2 style="font-size:17px;font-weight:800;margin-bottom:16px">Histórico (${registros.length})</h2>
        ${registros.length ? registros.map(item => `
          <div class="list-item">
            <div class="list-item__info">
              <div class="list-item__title">${item.descricao || '—'}</div>
              <div class="list-item__sub">
                ${item.categoria || '—'} · ${fmtDate(item.createdAt)}
                ${item.vencimento ? ` · ref: ${item.vencimento}` : ''}
              </div>
            </div>
            <div class="list-item__right">
              <div style="font-weight:700;font-size:16px;color:${item.tipo===TIPO_FINANCEIRO.RECEITA?'#34d399':'#f87171'}">
                ${signal(item.tipo)}${fmtBRL(item.valor)}
              </div>
              <span class="badge ${badgeClass(item.tipo)}" style="margin-top:4px">${item.tipo}</span>
            </div>
          </div>
        `).join('') : `<p style="color:var(--muted);text-align:center;padding:24px 0;font-size:14px">Nenhum lançamento ainda.</p>`}
      </div>

    </section>
  `;
}

// ============================================
// SALVAR LANÇAMENTO
// ============================================

window.addEventListener('submit', async (e) => {
  if (e.target.id !== 'financeiroForm') return;
  e.preventDefault();

  const tipo      = document.getElementById('tipo').value;
  const descricao = document.getElementById('descricao').value.trim();
  const categoria = document.getElementById('categoria').value.trim();
  const valor     = parseFloat(document.getElementById('valor').value);
  const vencimento= document.getElementById('vencimento').value;

  if (!descricao) { showToast({ type:'error', message:'Informe a descrição.' }); return; }
  if (!valor || valor <= 0) { showToast({ type:'error', message:'Informe um valor válido.' }); return; }

  await db.financeiro.add({
    tipo, descricao, categoria, valor, vencimento,
    status: 'pendente',
    createdAt: new Date().toISOString()
  });

  showToast({ message: tipo === TIPO_FINANCEIRO.RECEITA ? 'Receita lançada!' : 'Despesa lançada!' });
  setTimeout(() => navigate('financeiro'), 400);
});
