import { db, fmtBRL, fmtDate, STATUS_PRODUCAO, STATUS_PEDIDO, TIPO_MATERIAL, MEDIDA_CONFIG, unidadeMaterial } from '../database/db.js';
import { emptyState } from '../components/empty-state.js';
import { showToast  } from '../components/toast.js';
import { openModal, closeModal } from '../components/modal.js';
import { navigate } from '../core/router.js';

const STATUS_LIST = Object.values(STATUS_PRODUCAO);

const STATUS_PROGRESS = {
  [STATUS_PRODUCAO.INICIADA]:   5,
  [STATUS_PRODUCAO.FORJAMENTO]: 30,
  [STATUS_PRODUCAO.TEMPERA]:    60,
  [STATUS_PRODUCAO.ACABAMENTO]: 85,
  [STATUS_PRODUCAO.FINALIZADA]: 100,
};

const statusBadge = (status) => {
  const map = {
    [STATUS_PRODUCAO.INICIADA]:   'badge-gray',
    [STATUS_PRODUCAO.FORJAMENTO]: 'badge-orange',
    [STATUS_PRODUCAO.TEMPERA]:    'badge-blue',
    [STATUS_PRODUCAO.ACABAMENTO]: 'badge-purple',
    [STATUS_PRODUCAO.FINALIZADA]: 'badge-green',
  };
  return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
};

// ============================================
// PAGE
// ============================================

export async function producaoPage() {
  const [producao, pedidos, materiais] = await Promise.all([
    db.producao  ? db.producao.orderBy('id').reverse().toArray()  : [],
    db.pedidos   ? db.pedidos.toArray()   : [],
    db.materiais ? db.materiais.toArray() : [],
  ]);

  const pedidoMap  = Object.fromEntries(pedidos.map(p => [p.id, p]));
  const materialMap= Object.fromEntries(materiais.map(m => [m.id, m]));

  const ativas     = producao.filter(i => i.status !== STATUS_PRODUCAO.FINALIZADA).length;
  const finalizadas= producao.filter(i => i.status === STATUS_PRODUCAO.FINALIZADA).length;

  return `
    <section class="pb-4">
      <div class="page-header">
        <div>
          <h1>Produção</h1>
          <p>Controle da oficina</p>
        </div>
        <button id="newProductionButton" class="btn btn-primary btn-sm">+ Nova</button>
      </div>

      <div class="grid-2" style="gap:10px;margin-bottom:20px">
        <div class="kpi-card">
          <div class="kpi-card__label">Em andamento</div>
          <div class="kpi-card__value" style="color:var(--accent)">${ativas}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-card__label">Finalizadas</div>
          <div class="kpi-card__value" style="color:#34d399">${finalizadas}</div>
        </div>
      </div>

      ${!producao.length ? emptyState({
        icon: 'hammer',
        title: 'Nenhuma faca em produção',
        description: 'Inicie uma produção para acompanhar cada etapa da fabricação.'
      }) : `
        <div class="grid-stack">
          ${producao.map(item => {
            const pct    = item.progresso ?? STATUS_PROGRESS[item.status] ?? 0;
            const pedido = item.pedidoId ? pedidoMap[item.pedidoId] : null;
            const previstos = item.materiaisPrevistos || [];

            return `
              <div class="card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px">
                  <div style="flex:1;min-width:0">
                    <h2 style="font-size:18px;font-weight:800;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.nome || 'Faca artesanal'}</h2>
                    <p style="color:var(--muted);font-size:13px">${fmtDate(item.createdAt)}</p>
                  </div>
                  ${statusBadge(item.status)}
                </div>

                ${pedido ? `
                  <div style="display:flex;align-items:center;gap:8px;background:rgba(168,85,247,.08);border:1px solid rgba(168,85,247,.2);border-radius:10px;padding:8px 12px;margin-bottom:12px">
                    <i data-lucide="shopping-bag" style="width:14px;height:14px;color:#c084fc;flex-shrink:0"></i>
                    <span style="font-size:13px;color:#c084fc;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                      ${pedido.nome}${pedido.cliente ? ' — ' + pedido.cliente : ''}
                    </span>
                    ${pedido.prazo ? `<span style="font-size:11px;color:var(--muted);margin-left:auto;flex-shrink:0">prazo ${fmtDate(pedido.prazo+'T00:00:00')}</span>` : ''}
                  </div>
                ` : ''}

                <!-- MATERIAIS RESERVADOS -->
                ${previstos.length ? `
                  <div style="background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:12px;padding:10px 12px;margin-bottom:12px">
                    <div style="font-size:11px;color:var(--muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">Materiais reservados</div>
                    ${previstos.map(p => {
                      const mat = materialMap[p.materialId];
                      if (!mat) return '';
                      const unidade = unidadeMaterial(mat);
                      return `
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:4px 0;border-top:1px solid var(--border)">
                          <span style="font-weight:600">${mat.nome}</span>
                          <span style="color:var(--accent);font-weight:700">${p.qtdPrevista} ${unidade}</span>
                        </div>
                      `;
                    }).join('')}
                  </div>
                ` : ''}

                ${item.acoTipo || item.caboMaterial ? `
                  <div style="display:flex;gap:16px;margin-bottom:12px">
                    ${item.acoTipo          ? `<div><div style="font-size:11px;color:var(--muted)">Aço</div><div style="font-size:14px;font-weight:600">${item.acoTipo}</div></div>` : ''}
                    ${item.caboMaterial     ? `<div><div style="font-size:11px;color:var(--muted)">Cabo</div><div style="font-size:14px;font-weight:600">${item.caboMaterial}</div></div>` : ''}
                    ${item.comprimentoLamina? `<div><div style="font-size:11px;color:var(--muted)">Lâmina</div><div style="font-size:14px;font-weight:600">${item.comprimentoLamina}cm</div></div>` : ''}
                  </div>
                ` : ''}

                <div style="margin-bottom:14px">
                  <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px">
                    <span style="color:var(--muted)">Progresso</span>
                    <strong style="color:var(--accent)">${pct}%</strong>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-bar__fill" style="width:${pct}%"></div>
                  </div>
                </div>

                <!-- ETAPAS RÁPIDAS -->
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
                  ${STATUS_LIST.filter(s => s !== item.status).map(s => `
                    <button class="advance-status-btn btn btn-ghost btn-sm"
                      data-id="${item.id}" data-status="${s}"
                      style="font-size:11px;padding:5px 10px;${s === STATUS_PRODUCAO.FINALIZADA ? 'color:#34d399;border-color:rgba(52,211,153,.3)' : ''}">
                      ${s === STATUS_PRODUCAO.FINALIZADA ? '✓' : '→'} ${s}
                    </button>
                  `).join('')}
                </div>

                <div style="display:flex;gap:8px">
                  <button class="btn btn-ghost btn-sm edit-prod-btn" data-id="${item.id}">
                    <i data-lucide="pencil" style="width:14px;height:14px"></i> Editar
                  </button>
                  <button class="btn btn-danger btn-sm delete-prod-btn" data-id="${item.id}" style="margin-left:auto">
                    <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                  </button>
                </div>
              </div>
            `;
          }).join('')}
        </div>
      `}
    </section>
  `;
}

// ============================================
// MODAL DE CONSUMO — pré-preenchido com reservados
// ============================================

async function openConsumoModal(producaoId, producaoNome, materiaisPrevistos, onConfirm) {
  const todosMateriaisDB = db.materiais ? await db.materiais.toArray() : [];

  if (!todosMateriaisDB.length) {
    await onConfirm([]);
    return;
  }

  // Mapa dos previstos: materialId → qtdPrevista
  const previstoMap = Object.fromEntries(
    (materiaisPrevistos || []).map(p => [p.materialId, p.qtdPrevista])
  );

  // Materiais que tinham reserva aparecem primeiro, com quantidade pré-preenchida
  const comReserva = todosMateriaisDB.filter(m => previstoMap[m.id] !== undefined);
  const semReserva = todosMateriaisDB.filter(m => previstoMap[m.id] === undefined);
  const ordenados  = [...comReserva, ...semReserva];

  openModal({
    title: 'Confirmar baixa de estoque',
    size: 'md',
    content: `
      <div style="margin-bottom:16px">
        <p style="font-size:14px;color:var(--muted);line-height:1.6">
          Confirme ou ajuste os materiais usados em
          <strong style="color:var(--text)">${producaoNome}</strong>.
          Os valores pré-preenchidos são os que foram reservados.
        </p>
      </div>

      <form id="consumoForm" class="grid-stack">
        ${ordenados.map(m => {
          const unidade  = unidadeMaterial(m);
          const previsto = previstoMap[m.id] ?? '';
          const temReserva = previstoMap[m.id] !== undefined;

          return `
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
              <div style="flex:1;min-width:0">
                <div style="font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px">
                  ${m.nome}
                  ${temReserva ? `<span style="font-size:10px;background:rgba(249,115,22,.12);color:#fb923c;border-radius:6px;padding:1px 6px;font-weight:600">reservado</span>` : ''}
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px">
                  Estoque: <span style="color:var(--text);font-weight:600">${m.estoqueAtual||0} ${unidade}</span>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                <input
                  type="number"
                  class="consumo-input"
                  data-id="${m.id}"
                  data-atual="${m.estoqueAtual||0}"
                  data-unidade="${unidade}"
                  min="0"
                  step="${MEDIDA_CONFIG[m.tipoMedida]?.passo || 1}"
                  placeholder="0"
                  value="${previsto}"
                  style="width:80px;margin-bottom:0;text-align:right;padding:8px 10px;font-size:14px;${temReserva ? 'border-color:rgba(249,115,22,.4)' : ''}"
                />
                <span style="font-size:12px;color:var(--muted);width:32px">${unidade}</span>
              </div>
            </div>
          `;
        }).join('')}

        <div style="display:flex;gap:10px;margin-top:8px">
          <button type="button" id="consumoSkipBtn" class="btn btn-ghost" style="flex:1">
            Finalizar sem baixa
          </button>
          <button type="submit" class="btn btn-primary" style="flex:2">
            <i data-lucide="check" style="width:16px;height:16px"></i>
            Confirmar baixa
          </button>
        </div>
      </form>
    `
  });

  if (window.lucide) lucide.createIcons();

  document.getElementById('consumoSkipBtn').addEventListener('click', async () => {
    closeModal();
    await onConfirm([]);
  });

  document.getElementById('consumoForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const inputs  = document.querySelectorAll('.consumo-input');
    const consumos = [];

    for (const input of inputs) {
      const qtd   = parseFloat(input.value) || 0;
      if (qtd <= 0) continue;

      const id     = Number(input.dataset.id);
      const atual  = parseFloat(input.dataset.atual) || 0;
      const unidade= input.dataset.unidade;

      if (qtd > atual) {
        showToast({ type:'error', message:`Quantidade maior que estoque disponível de ${unidade}.` });
        return;
      }
      consumos.push({ id, qtd, novoEstoque: Math.max(0, Math.round((atual - qtd) * 100) / 100) });
    }

    closeModal();
    await onConfirm(consumos);
  });
}

// ============================================
// FINALIZAR PRODUÇÃO
// ============================================

async function finalizarProducao(id) {
  const item = await db.producao.get(id);
  if (!item) return;

  openConsumoModal(id, item.nome, item.materiaisPrevistos || [], async (consumos) => {
    for (const { id: matId, novoEstoque } of consumos) {
      await db.materiais.update(matId, { estoqueAtual: novoEstoque });
    }
    await db.producao.update(id, { status: STATUS_PRODUCAO.FINALIZADA, progresso: 100 });

    const msg = consumos.length > 0
      ? `Finalizada! Baixa em ${consumos.length} material${consumos.length > 1 ? 'is' : ''}.`
      : 'Produção finalizada!';

    showToast({ message: msg });
    setTimeout(() => navigate('producao'), 300);
  });
}

// ============================================
// MODAL CRIAR / EDITAR
// ============================================

async function openProducaoModal(existing = null) {
  const [pedidosAbertos, materiais] = await Promise.all([
    db.pedidos
      ? db.pedidos.filter(p => p.status === STATUS_PEDIDO.ABERTO || p.status === STATUS_PEDIDO.EM_PRODUCAO).toArray()
      : [],
    db.materiais ? db.materiais.toArray() : [],
  ]);

  const acos  = materiais.filter(m => m.tipoMaterial === TIPO_MATERIAL.ACO);
  const cabos = materiais.filter(m => m.tipoMaterial === TIPO_MATERIAL.CABO);

  // Reservas atuais: mapa materialId → qtdPrevista
  const reservaAtual = Object.fromEntries(
    (existing?.materiaisPrevistos || []).map(p => [p.materialId, p.qtdPrevista])
  );

  const pedidoOptions = [
    `<option value="">— Sem vínculo —</option>`,
    ...pedidosAbertos.map(p =>
      `<option value="${p.id}" ${existing?.pedidoId === p.id ? 'selected' : ''}>
        ${p.nome}${p.cliente ? ' — ' + p.cliente : ''} (${fmtBRL(p.valor)})
      </option>`
    )
  ].join('');

  const renderMaterialSelect = (lista, titulo, tipoIcon) => {
    if (!lista.length) return '';
    return `
      <div style="margin-bottom:14px">
        <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:5px;text-transform:uppercase;letter-spacing:.5px">
          <i data-lucide="${tipoIcon}" style="width:13px;height:13px"></i>${titulo}
        </div>
        ${lista.map(m => {
          const unidade  = unidadeMaterial(m);
          const reservado= reservaAtual[m.id] ?? '';
          const passo    = MEDIDA_CONFIG[m.tipoMedida]?.passo || 1;
          return `
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
              <div style="flex:1;min-width:0">
                <div style="font-size:14px;font-weight:600">${m.nome}</div>
                <div style="font-size:12px;color:var(--muted)">em estoque: ${m.estoqueAtual||0} ${unidade}</div>
              </div>
              <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                <input
                  type="number"
                  class="reserva-input"
                  data-material-id="${m.id}"
                  min="0"
                  step="${passo}"
                  placeholder="0"
                  value="${reservado}"
                  style="width:80px;margin-bottom:0;text-align:right;padding:8px 10px;font-size:14px"
                />
                <span style="font-size:12px;color:var(--muted);width:32px">${unidade}</span>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  };

  openModal({
    title: existing ? 'Editar Produção' : 'Nova Produção',
    size: 'md',
    content: `
      <form id="producaoForm" class="grid-stack">

        <!-- DADOS BÁSICOS -->
        <div>
          <label>Nome da peça *</label>
          <input type="text" id="prodNome" placeholder="Ex: Bowie artesanal" value="${existing?.nome||''}" />
        </div>
        <div>
          <label>Pedido relacionado</label>
          <select id="prodPedidoId">${pedidoOptions}</select>
        </div>
        <div class="grid-2" style="gap:12px">
          <div>
            <label>Comprimento lâmina (cm)</label>
            <input type="number" id="prodComp" placeholder="14" value="${existing?.comprimentoLamina||''}" />
          </div>
          <div>
            <label>Espessura (mm)</label>
            <input type="number" id="prodEsp" placeholder="4" value="${existing?.espessura||''}" />
          </div>
        </div>
        <div>
          <label>Status</label>
          <select id="prodStatus">
            ${STATUS_LIST.filter(s => s !== STATUS_PRODUCAO.FINALIZADA).map(s =>
              `<option value="${s}" ${(existing?.status||STATUS_PRODUCAO.INICIADA)===s?'selected':''}>${s}</option>`
            ).join('')}
          </select>
        </div>
        <div>
          <label>Observações</label>
          <textarea id="prodObs" placeholder="Detalhes...">${existing?.observacao||''}</textarea>
        </div>

        <!-- MATERIAIS A RESERVAR -->
        ${(acos.length || cabos.length) ? `
          <div style="border-top:1px solid var(--border);padding-top:18px">
            <div style="font-size:15px;font-weight:800;margin-bottom:4px">Materiais previstos</div>
            <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Informe a quantidade de cada material. Deixe em branco o que não for usar.</p>
            ${renderMaterialSelect(acos,  'Aço',  'hammer')}
            ${renderMaterialSelect(cabos, 'Cabo', 'grip-horizontal')}
          </div>
        ` : `
          <div style="border-top:1px solid var(--border);padding-top:14px">
            <p style="font-size:13px;color:var(--muted)">
              <i data-lucide="info" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px"></i>
              Cadastre materiais (Aço e Cabo) para reservar aqui.
            </p>
          </div>
        `}

        <button type="submit" class="primary-button">${existing ? 'Salvar' : 'Criar produção'}</button>
      </form>
    `
  });

  if (window.lucide) lucide.createIcons();

  document.getElementById('producaoForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('prodNome').value.trim();
    if (!nome) { showToast({ type:'error', message:'Informe o nome.' }); return; }

    // Coletar materiais reservados
    const materiaisPrevistos = [];
    document.querySelectorAll('.reserva-input').forEach(input => {
      const qtd = parseFloat(input.value) || 0;
      if (qtd > 0) {
        materiaisPrevistos.push({
          materialId:  Number(input.dataset.materialId),
          qtdPrevista: qtd
        });
      }
    });

    const status      = document.getElementById('prodStatus').value;
    const pedidoIdRaw = document.getElementById('prodPedidoId').value;

    const data = {
      nome,
      pedidoId:         pedidoIdRaw ? Number(pedidoIdRaw) : null,
      comprimentoLamina:Number(document.getElementById('prodComp').value)||null,
      espessura:        Number(document.getElementById('prodEsp').value)||null,
      status,
      progresso:        STATUS_PROGRESS[status] || 0,
      observacao:       document.getElementById('prodObs').value.trim(),
      materiaisPrevistos,
    };

    if (existing) {
      await db.producao.update(existing.id, data);
      showToast({ message: 'Produção atualizada.' });
    } else {
      data.createdAt = new Date().toISOString();
      await db.producao.add(data);
      if (data.pedidoId) {
        await db.pedidos.update(data.pedidoId, { status: STATUS_PEDIDO.EM_PRODUCAO });
      }
      showToast({ message: 'Produção iniciada!' });
    }
    closeModal();
    setTimeout(() => navigate('producao'), 300);
  });
}

// ============================================
// EVENTS
// ============================================

window.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-id]');
  const id  = btn ? Number(btn.dataset.id) : null;

  if (e.target.id === 'newProductionButton' || e.target.closest('#newProductionButton')) {
    openProducaoModal(); return;
  }

  if (e.target.closest('.edit-prod-btn') && id) {
    const item = await db.producao.get(id);
    if (item) openProducaoModal(item);
    return;
  }

  if (e.target.closest('.advance-status-btn') && id) {
    const newStatus = e.target.closest('.advance-status-btn').dataset.status;
    if (newStatus === STATUS_PRODUCAO.FINALIZADA) {
      finalizarProducao(id);
      return;
    }
    await db.producao.update(id, { status: newStatus, progresso: STATUS_PROGRESS[newStatus]||0 });
    showToast({ message: `Status: ${newStatus}` });
    setTimeout(() => navigate('producao'), 250);
    return;
  }

  if (e.target.closest('.delete-prod-btn') && id) {
    if (!confirm('Excluir esta produção?')) return;
    await db.producao.delete(id);
    showToast({ message: 'Produção excluída.' });
    setTimeout(() => navigate('producao'), 250);
    return;
  }
});
