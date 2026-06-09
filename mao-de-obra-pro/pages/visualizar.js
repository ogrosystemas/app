// ============================================================
// pages/visualizar.js — Ver orçamento + status + pagamentos + PDF
// ============================================================

import { render, toast } from '../js/app.js';
import { getById, getAll, add, remove, put } from '../js/db.js';
import { moeda, dataLocal, tempo, DIFICULDADE } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

// Fluxo de status permitido (só avança, não volta)
const FLUXO = ['pendente', 'aprovado', 'em andamento', 'finalizado'];

const BADGE = {
  'pendente':     'warning',
  'aprovado':     'success',
  'em andamento': 'primary',
  'finalizado':   'info',
  'arquivado':    'secondary',
  'recusado':     'danger',
  'cancelado':    'secondary',
};

export default async function visualizarPage({ id }) {
  if (!id) { navigate('/'); return; }

  const orc     = await getById('orcamentos', parseInt(id));
  if (!orc) { toast('Orçamento não encontrado.', 'danger'); navigate('/'); return; }

  const cliente   = await getById('clientes', orc.clienteId);
  const fotos     = await getAll('fotos',      'orcamentoId', IDBKeyRange.only(parseInt(orc.id)));
  const pagamentos = await getAll('pagamentos', 'orcamentoId', IDBKeyRange.only(parseInt(orc.id)));

  window._fotosVisualizacao = fotos;

  const totalPago    = pagamentos.reduce((s, p) => s + p.valor, 0);
  const totalRestante = Math.max(0, (orc.total || 0) - totalPago);
  const idxAtual     = FLUXO.indexOf(orc.status);
  const proximoStatus = idxAtual >= 0 && idxAtual < FLUXO.length - 1
    ? FLUXO[idxAtual + 1] : null;

  render(`
    <div class="page-content pb-5">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-link p-0 text-decoration-none" onclick="history.back()">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <span class="badge bg-${BADGE[orc.status] || 'secondary'} fs-6 text-capitalize">${orc.status}</span>
      </div>

      <!-- Cabeçalho -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
          <div>
            <div class="fw-bold fs-5">Orçamento #${orc.id}</div>
            <div class="small opacity-75">${orc.profissaoNome || '—'}</div>
          </div>
          <div class="text-end small opacity-75">
            <div>${dataLocal(orc.data)}</div>
            <div>Válido até ${dataLocal(orc.dataVencimento)}</div>
          </div>
        </div>
        <div class="card-body">

          <!-- Cliente -->
          <div class="mb-3 pb-2 border-bottom">
            <div class="text-muted small text-uppercase mb-1">Cliente</div>
            <div class="fw-semibold">${cliente?.nome || 'Cliente removido'}</div>
            ${cliente?.whatsapp ? `<div class="small text-muted"><i class="bi bi-whatsapp me-1 text-success"></i>${cliente.whatsapp}</div>` : ''}
            ${cliente?.endereco ? `<div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>${cliente.endereco}</div>` : ''}
          </div>

          <!-- Serviços -->
          <div class="mb-3 pb-2 border-bottom">
            <div class="text-muted small text-uppercase mb-2">Serviços</div>
            ${(orc.itens || []).map(item => `
              <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                <div class="me-2 flex-grow-1">
                  <div class="fw-semibold">${item.nome}</div>
                  <div class="small text-muted">
                    ${item.usaPrecoFixo
                      ? 'Preço fixo'
                      : `${tempo(item.tempoAjustado)} · ${DIFICULDADE[item.dificuldade]?.label || item.dificuldade}`
                    } · Qtd: ${item.quantidade}
                  </div>
                </div>
                <div class="fw-semibold text-primary text-nowrap">${moeda(item.precoTotal)}</div>
              </div>
            `).join('')}
          </div>

          <!-- Totais -->
          <div>
            <div class="d-flex justify-content-between text-muted mb-1">
              <span>Subtotal</span><span>${moeda(orc.subtotal)}</span>
            </div>
            <div class="d-flex justify-content-between text-muted mb-1">
              <span>Deslocamento</span><span>${moeda(orc.taxaDeslocamento)}</span>
            </div>
            ${orc.desconto?.valor > 0 ? `
              <div class="d-flex justify-content-between text-danger mb-1">
                <span>Desconto</span>
                <span>− ${orc.desconto.tipo === 'percentual'
                  ? orc.desconto.valor + '%' : moeda(orc.desconto.valor)}</span>
              </div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top">
              <span>Total</span><span class="text-primary">${moeda(orc.total)}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagamentos -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-semibold">Pagamentos</div>
            ${totalRestante > 0.01 ? `
              <button class="btn btn-sm btn-outline-success" onclick="abrirModalPagamento()">
                <i class="bi bi-plus-lg me-1"></i>Registrar
              </button>` : `
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-check-circle me-1"></i>Quitado
              </span>`}
          </div>

          ${pagamentos.length === 0 ? `
            <div class="text-muted small text-center py-2">Nenhum pagamento registrado.</div>
          ` : `
            ${pagamentos.map(p => `
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                  <div class="small fw-semibold">${p.descricao || 'Pagamento'}</div>
                  <div class="small text-muted">${dataLocal(p.data)}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-bold text-success">${moeda(p.valor)}</span>
                  <button class="btn btn-sm btn-link text-danger p-0" onclick="excluirPagamento(${p.id})">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            `).join('')}
          `}

          <!-- Resumo financeiro -->
          <div class="mt-3 pt-2 border-top">
            <div class="d-flex justify-content-between text-muted small mb-1">
              <span>Total do orçamento</span><span>${moeda(orc.total)}</span>
            </div>
            <div class="d-flex justify-content-between text-success small mb-1">
              <span>Recebido</span><span>${moeda(totalPago)}</span>
            </div>
            <div class="d-flex justify-content-between fw-bold ${totalRestante > 0 ? 'text-danger' : 'text-success'}">
              <span>${totalRestante > 0 ? 'A receber' : 'Quitado'}</span>
              <span>${moeda(totalRestante)}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Fotos -->
      ${fotos.length > 0 ? `
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="fw-semibold mb-2">Fotos (${fotos.length})</div>
            <div class="row g-2">
              ${fotos.map((f, i) => `
                <div class="col-4">
                  <img src="${f.blob}" class="img-fluid rounded"
                    style="height:80px;width:100%;object-fit:cover;cursor:pointer"
                    onclick="ampliarFoto(${i})">
                </div>`).join('')}
            </div>
          </div>
        </div>
      ` : ''}

      <!-- Ações -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="fw-semibold mb-3">Ações</div>
          <div class="d-grid gap-2">

            <!-- Avançar status -->
            ${proximoStatus ? `
              <button class="btn btn-primary" onclick="avancarStatus()">
                <i class="bi bi-arrow-right-circle me-2"></i>
                Avançar para <strong>${proximoStatus}</strong>
              </button>
            ` : ''}

            <!-- Recusar / Cancelar (só se não finalizado) -->
            ${!['finalizado','recusado','cancelado'].includes(orc.status) ? `
              <div class="d-flex gap-2">
                <button class="btn btn-outline-danger flex-fill" onclick="mudarStatus('recusado')">
                  <i class="bi bi-x-circle me-1"></i>Recusar
                </button>
                <button class="btn btn-outline-secondary flex-fill" onclick="mudarStatus('cancelado')">
                  <i class="bi bi-slash-circle me-1"></i>Cancelar
                </button>
              </div>
            ` : ''}

            ${orc.status === 'arquivado' ? `
              <button class="btn btn-outline-primary" onclick="desarquivar()">
                <i class="bi bi-archive me-2"></i>Desarquivar
              </button>` : ''}

            ${cliente?.whatsapp ? `
              <button class="btn btn-success" onclick="enviarWhatsApp()">
                <i class="bi bi-whatsapp me-2"></i>Enviar pelo WhatsApp
              </button>` : ''}

            <button class="btn btn-outline-primary" id="btn-pdf" onclick="gerarPDF()">
              <i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pagamento -->
    <div class="modal fade" id="modal-pagamento" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Pagamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Valor (R$) *</label>
              <input type="number" class="form-control form-control-lg" id="pag-valor"
                step="0.01" min="0.01" placeholder="0,00"
                value="${totalRestante > 0 ? totalRestante.toFixed(2) : ''}">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Descrição</label>
              <input type="text" class="form-control" id="pag-descricao"
                placeholder="Ex: Entrada, Saldo, Pagamento total...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Data</label>
              <input type="date" class="form-control" id="pag-data"
                value="${new Date().toISOString().slice(0,10)}">
            </div>
            ${totalRestante > 0 ? `
              <div class="alert alert-info small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Saldo restante: <strong>${moeda(totalRestante)}</strong>
              </div>
            ` : `
              <div class="alert alert-success small mb-0">
                <i class="bi bi-check-circle me-1"></i>
                Orçamento já quitado!
              </div>
            `}
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-success" onclick="salvarPagamento()">
              <i class="bi bi-check-lg me-1"></i>Salvar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox fotos -->
    <div class="modal fade" id="modal-foto" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark">
          <div class="modal-body p-0">
            <img id="foto-ampliada" src="" class="img-fluid w-100">
          </div>
          <div class="modal-footer border-0 justify-content-center">
            <button class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>
  `);

  // ── Handlers ──────────────────────────────────────────────

  window.ampliarFoto = (idx) => {
    const foto = (window._fotosVisualizacao || [])[idx];
    if (!foto) return;
    document.getElementById('foto-ampliada').src = foto.blob;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-foto')).show();
  };

  window.avancarStatus = async () => {
    if (!proximoStatus) return;
    orc.status = proximoStatus;
    await put('orcamentos', orc);
    toast(`Status: ${proximoStatus}`, 'success');
    visualizarPage({ id });
  };

  window.mudarStatus = async (status) => {
    if (!confirm(`Marcar como "${status}"?`)) return;
    orc.status = status;
    await put('orcamentos', orc);
    toast(`Orçamento ${status}.`);
    visualizarPage({ id });
  };

  window.desarquivar = async () => {
    orc.status = 'finalizado';
    await put('orcamentos', orc);
    toast('Orçamento reativado!');
    visualizarPage({ id });
  };

  window.abrirModalPagamento = () => {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-pagamento')).show();
  };

  window.salvarPagamento = async () => {
    const valor = parseFloat(document.getElementById('pag-valor')?.value);
    if (!valor || valor <= 0) { toast('Informe um valor válido.', 'warning'); return; }

    const descricao = document.getElementById('pag-descricao')?.value.trim() || 'Pagamento';
    const data      = document.getElementById('pag-data')?.value || new Date().toISOString().slice(0,10);

    await add('pagamentos', {
      orcamentoId: orc.id,
      valor,
      descricao,
      data: new Date(data + 'T12:00:00').toISOString(),
    });

    // Se quitado, avança para finalizado automaticamente (se estiver em andamento)
    const novoPago = totalPago + valor;
    if (novoPago >= orc.total && orc.status === 'em andamento') {
      orc.status = 'finalizado';
      await put('orcamentos', orc);
      toast('Pagamento registrado! Orçamento finalizado.', 'success');
    } else {
      toast('Pagamento registrado!', 'success');
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-pagamento')).hide();
    visualizarPage({ id });
  };

  window.excluirPagamento = async (pagId) => {
    if (!confirm('Excluir este pagamento?')) return;
    await remove('pagamentos', pagId);
    toast('Pagamento removido.', 'danger');
    visualizarPage({ id });
  };

  window.enviarWhatsApp = () => {
    const num = (cliente?.whatsapp || '').replace(/\D/g, '');
    if (!num) return;

    const linhas = (orc.itens || [])
      .map(i => `✓ ${i.nome} ×${i.quantidade} — ${moeda(i.precoTotal)}`)
      .join('\n');

    const partes = [
      `*ORÇAMENTO #${orc.id} — MÃO DE OBRA PRO*`,
      '',
      `*Cliente:* ${cliente?.nome || ''}`,
      `*Profissão:* ${orc.profissaoNome || ''}`,
      `*Data:* ${dataLocal(orc.data)}`,
      `*Válido até:* ${dataLocal(orc.dataVencimento)}`,
      '',
      '*SERVIÇOS:*',
      linhas,
      '',
      `*Deslocamento:* ${moeda(orc.taxaDeslocamento)}`,
    ];

    if (orc.desconto?.valor > 0) {
      partes.push(`*Desconto:* ${orc.desconto.tipo === 'percentual'
        ? orc.desconto.valor + '%' : moeda(orc.desconto.valor)}`);
    }

    partes.push(`*TOTAL: ${moeda(orc.total)}*`);
    if (totalPago > 0) {
      partes.push('');
      partes.push(`*Recebido:* ${moeda(totalPago)}`);
      partes.push(`*A receber:* ${moeda(totalRestante)}`);
    }

    window.open(`https://wa.me/55${num}?text=${encodeURIComponent(partes.join('\n'))}`, '_blank');
  };

  window.gerarPDF = async () => {
    const btn = document.getElementById('btn-pdf');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando...'; }

    try {
      if (!window.jspdf) {
        await carregarScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ unit: 'mm', format: 'a4' });
      const PW  = 210;
      const M   = 15;
      const CW  = PW - M * 2;
      let y     = 0;

      const nl = (h = 6) => { y += h; };
      const sf = (size, style, color) => {
        doc.setFontSize(size);
        doc.setFont('helvetica', style || 'normal');
        doc.setTextColor(...(color || [40,40,40]));
      };
      const hline = () => {
        doc.setDrawColor(220, 220, 220);
        doc.line(M, y, PW - M, y);
        nl(4);
      };

      // Header
      doc.setFillColor(37, 99, 235);
      doc.rect(0, 0, PW, 32, 'F');
      sf(18, 'bold', [255,255,255]);
      doc.text('MÃO DE OBRA PRO', M, 13);
      sf(9, 'normal', [200,220,255]);
      doc.text(`Orçamento #${orc.id}  ·  ${orc.profissaoNome || ''}`, M, 21);
      doc.text(`${dataLocal(orc.data)}  ·  Válido até ${dataLocal(orc.dataVencimento)}`, PW - M, 21, { align: 'right' });
      y = 42;

      // Cliente
      sf(7, 'bold', [120,120,120]); doc.text('CLIENTE', M, y); nl(5);
      sf(12, 'bold', [20,20,20]);  doc.text(cliente?.nome || 'Cliente removido', M, y); nl(5);
      if (cliente?.whatsapp) { sf(9, 'normal', [80,80,80]);   doc.text(cliente.whatsapp, M, y); nl(4); }
      if (cliente?.endereco) { sf(9, 'normal', [120,120,120]); doc.text(cliente.endereco, M, y); nl(4); }
      nl(4); hline();

      // Serviços
      sf(7, 'bold', [120,120,120]); doc.text('SERVIÇOS', M, y); nl(6);
      for (const item of (orc.itens || [])) {
        if (y > 255) { doc.addPage(); y = 20; }
        sf(10, 'bold', [20,20,20]);   doc.text(item.nome, M, y);
        sf(10, 'bold', [37,99,235]);  doc.text(moeda(item.precoTotal), PW - M, y, { align: 'right' });
        nl(5);
        const det = item.usaPrecoFixo
          ? `Qtd: ${item.quantidade}  ·  Preço fixo`
          : `Qtd: ${item.quantidade}  ·  ${tempo(item.tempoAjustado)}  ·  ${DIFICULDADE[item.dificuldade]?.label || ''}`;
        sf(8, 'normal', [130,130,130]); doc.text(det, M, y); nl(7);
      }
      hline();

      // Totais
      const rowLR = (l, r, cl, cr) => {
        sf(10, 'normal', cl || [100,100,100]); doc.text(String(l), M, y);
        sf(10, 'bold',   cr || [40,40,40]);   doc.text(String(r), PW - M, y, { align: 'right' });
        nl(6);
      };
      rowLR('Subtotal',     moeda(orc.subtotal));
      rowLR('Deslocamento', moeda(orc.taxaDeslocamento));
      if (orc.desconto?.valor > 0) {
        const dv = orc.desconto.tipo === 'percentual'
          ? orc.desconto.valor + '%' : moeda(orc.desconto.valor);
        rowLR('Desconto', '- ' + dv, [200,50,50], [200,50,50]);
      }
      nl(2);
      doc.setFillColor(37, 99, 235);
      doc.roundedRect(M, y - 3, CW, 11, 2, 2, 'F');
      sf(12, 'bold', [255,255,255]);
      doc.text('TOTAL', M + 4, y + 4);
      doc.text(moeda(orc.total), PW - M - 4, y + 4, { align: 'right' });
      nl(18);

      // Pagamentos no PDF
      if (pagamentos.length > 0) {
        if (y > 220) { doc.addPage(); y = 20; }
        sf(7, 'bold', [120,120,120]); doc.text('PAGAMENTOS', M, y); nl(6);
        for (const p of pagamentos) {
          sf(9, 'normal', [40,40,40]);  doc.text(p.descricao || 'Pagamento', M, y);
          sf(9, 'normal', [80,80,80]);  doc.text(dataLocal(p.data), M + 60, y);
          sf(9, 'bold',   [22,163,74]); doc.text(moeda(p.valor), PW - M, y, { align: 'right' });
          nl(6);
        }
        hline();
        rowLR('Recebido',  moeda(totalPago),     [22,163,74],  [22,163,74]);
        if (totalRestante > 0) rowLR('A receber', moeda(totalRestante), [220,50,50], [220,50,50]);
        else { sf(9, 'bold', [22,163,74]); doc.text('QUITADO', PW - M, y, { align: 'right' }); nl(6); }
        nl(4);
      }

      // Fotos
      if (fotos.length > 0) {
        if (y > 200) { doc.addPage(); y = 20; }
        sf(7, 'bold', [120,120,120]); doc.text('FOTOS DO SERVIÇO', M, y); nl(6);
        const fW = 55; const gap = 5;
        let fx = M; let rowH = 0;
        for (const foto of fotos) {
          try {
            const c     = await corrigirOrientacao(foto.blob);
            const ratio = c.w / c.h;
            const fH    = Math.round(fW / ratio);
            if (fx + fW > PW - M)  { fx = M; y += rowH + gap; rowH = 0; }
            if (y + fH > 270)      { doc.addPage(); y = 20; fx = M; rowH = 0; }
            doc.addImage(c.data, 'JPEG', fx, y, fW, fH);
            if (fH > rowH) rowH = fH;
            fx += fW + gap;
          } catch (e) { console.warn('foto ignorada:', e); }
        }
        y += rowH + 10;
      }

      sf(7, 'normal', [180,180,180]);
      doc.text('Gerado por Mão de Obra PRO', PW / 2, 287, { align: 'center' });
      doc.save(`orcamento-${orc.id}-${(cliente?.nome || 'cliente').replace(/\s+/g, '-')}.pdf`);
      toast('PDF gerado!');

    } catch (err) {
      console.error('PDF error:', err);
      toast('Erro ao gerar PDF.', 'danger');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF'; }
    }
  };
}

// ── Utilitários de módulo ─────────────────────────────────────

function carregarScript(src) {
  return new Promise((res, rej) => {
    if (document.querySelector(`script[src="${src}"]`)) { res(); return; }
    const s = document.createElement('script');
    s.src = src; s.onload = res; s.onerror = rej;
    document.head.appendChild(s);
  });
}

function corrigirOrientacao(dataUrl) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      let orientation = 1;
      try {
        const bin = atob(dataUrl.split(',')[1].substring(0, 680));
        for (let i = 0; i < bin.length - 12; i++) {
          if (bin.charCodeAt(i) === 0xFF && bin.charCodeAt(i+1) === 0xE1) {
            const le  = bin.charCodeAt(i+10) === 0x49;
            const r2  = (o) => le
              ? bin.charCodeAt(o) | (bin.charCodeAt(o+1) << 8)
              : (bin.charCodeAt(o) << 8) | bin.charCodeAt(o+1);
            const base   = i + 10;
            const ifdOff = base + (le
              ? bin.charCodeAt(base+4) | (bin.charCodeAt(base+5)<<8)
              : (bin.charCodeAt(base+4)<<8) | bin.charCodeAt(base+5));
            const entries = r2(ifdOff);
            for (let e = 0; e < entries && e < 20; e++) {
              const off = ifdOff + 2 + e * 12;
              if (r2(off) === 0x0112) { orientation = r2(off + 8); break; }
            }
            break;
          }
        }
      } catch (_) {}

      const w = img.naturalWidth; const h = img.naturalHeight;
      const canvas = document.createElement('canvas');
      const ctx    = canvas.getContext('2d');
      if (orientation >= 5) { canvas.width = h; canvas.height = w; }
      else                  { canvas.width = w; canvas.height = h; }
      ctx.save();
      switch (orientation) {
        case 2: ctx.transform(-1,0,0, 1,w,0); break;
        case 3: ctx.transform(-1,0,0,-1,w,h); break;
        case 4: ctx.transform( 1,0,0,-1,0,h); break;
        case 5: ctx.transform( 0,1,1, 0,0,0); break;
        case 6: ctx.transform( 0,1,-1,0,h,0); break;
        case 7: ctx.transform( 0,-1,-1,0,h,w); break;
        case 8: ctx.transform( 0,-1,1, 0,0,w); break;
        default: break;
      }
      ctx.drawImage(img, 0, 0);
      ctx.restore();
      resolve({ data: canvas.toDataURL('image/jpeg', 0.85), w: canvas.width, h: canvas.height });
    };
    img.onerror = () => resolve({ data: dataUrl, w: 100, h: 100 });
    img.src = dataUrl;
  });
}
