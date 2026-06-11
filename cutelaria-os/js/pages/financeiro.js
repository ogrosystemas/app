import { db, fmtBRL, fmtDate, TIPO_FINANCEIRO } from '../database/db.js';
import { showToast } from '../components/toast.js';
import { navigate  } from '../core/router.js';

export async function financeiroPage() {

  const registros = await db.financeiro.orderBy('createdAt').reverse().toArray();

  const receitas = registros.filter(i => i.tipo === TIPO_FINANCEIRO.RECEITA).reduce((s,i) => s + Number(i.valor||0), 0);
  const despesas = registros.filter(i => i.tipo === TIPO_FINANCEIRO.DESPESA).reduce((s,i) => s + Number(i.valor||0), 0);
  const saldo    = receitas - despesas;
  const saldoPos = saldo >= 0;

  const badgeClass = (tipo) => tipo === TIPO_FINANCEIRO.RECEITA ? 'badge-green' : 'badge-red';
  const signal     = (tipo) => tipo === TIPO_FINANCEIRO.RECEITA ? '+' : '-';

  return `
    <section class="pb-4">

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

  await db.financeiro.add({ tipo, descricao, categoria, valor, vencimento, status:'pendente', createdAt: new Date().toISOString() });

  showToast({ message: tipo === TIPO_FINANCEIRO.RECEITA ? 'Receita lançada!' : 'Despesa lançada!' });
  setTimeout(() => navigate('financeiro'), 400);
});
