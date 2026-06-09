// ============================================================
// pages/visualizar.js — Ver orçamento + PDF + WhatsApp
// ============================================================

import { render, toast } from '../js/app.js';
import { getById, getAll, put } from '../js/db.js';
import { moeda, dataLocal, tempo, DIFICULDADE } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

export default async function visualizarPage({ id }) {
  if (!id) { navigate('/'); return; }

  const orc     = await getById('orcamentos', parseInt(id));
  if (!orc) { toast('Orçamento não encontrado.', 'danger'); navigate('/'); return; }

  const cliente = await getById('clientes', orc.clienteId);
  const fotos   = await getAll('fotos', 'orcamentoId', IDBKeyRange.only(orc.id));

  // Mapeia fotos por índice para evitar blob no onclick
  // O blob fica em memória — onclick usa só o índice
  window._fotosVisualizacao = fotos;

  const badgeStatus = { pendente: 'warning', aprovado: 'success', recusado: 'danger', cancelado: 'secondary' };

  render(`
    <div class="page-content pb-5">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-link p-0 text-decoration-none" onclick="history.back()">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <span class="badge bg-${badgeStatus[orc.status] || 'secondary'} fs-6">${orc.status}</span>
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
                  ? orc.desconto.valor + '%'
                  : moeda(orc.desconto.valor)}</span>
              </div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top">
              <span>Total</span><span class="text-primary">${moeda(orc.total)}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Fotos — onclick usa índice, nunca o blob direto -->
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
            <div class="d-flex gap-2">
              <button class="btn btn-outline-success flex-fill" onclick="mudarStatus('aprovado')">
                <i class="bi bi-check-circle me-1"></i>Aprovado
              </button>
              <button class="btn btn-outline-danger flex-fill" onclick="mudarStatus('recusado')">
                <i class="bi bi-x-circle me-1"></i>Recusado
              </button>
            </div>
            ${cliente?.whatsapp ? `
              <button class="btn btn-success" onclick="enviarWhatsApp()">
                <i class="bi bi-whatsapp me-2"></i>Enviar pelo WhatsApp
              </button>` : ''}
            <button class="btn btn-primary" id="btn-pdf" onclick="gerarPDF()">
              <i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
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

  // ── Handlers ─────────────────────────────────────────────

  window.ampliarFoto = (idx) => {
    const foto = (window._fotosVisualizacao || [])[idx];
    if (!foto) return;
    document.getElementById('foto-ampliada').src = foto.blob;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-foto')).show();
  };

  window.mudarStatus = async (status) => {
    orc.status = status;
    await put('orcamentos', orc);
    toast(`Orçamento marcado como ${status}!`);
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
      const dv = orc.desconto.tipo === 'percentual'
        ? orc.desconto.valor + '%'
        : moeda(orc.desconto.valor);
      partes.push(`*Desconto:* ${dv}`);
    }

    partes.push(`*TOTAL: ${moeda(orc.total)}*`);

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
      const doc  = new jsPDF({ unit: 'mm', format: 'a4' });
      const PW   = 210;
      const M    = 15;
      const CW   = PW - M * 2;
      let y      = 0;

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
      sf(7, 'bold', [120,120,120]);
      doc.text('CLIENTE', M, y); nl(5);
      sf(12, 'bold', [20,20,20]);
      doc.text(cliente?.nome || 'Cliente removido', M, y); nl(5);
      if (cliente?.whatsapp) { sf(9, 'normal', [80,80,80]);  doc.text(cliente.whatsapp, M, y); nl(4); }
      if (cliente?.endereco) { sf(9, 'normal', [120,120,120]); doc.text(cliente.endereco, M, y); nl(4); }
      nl(4); hline();

      // Serviços
      sf(7, 'bold', [120,120,120]);
      doc.text('SERVIÇOS', M, y); nl(6);

      for (const item of (orc.itens || [])) {
        if (y > 255) { doc.addPage(); y = 20; }
        sf(10, 'bold', [20,20,20]);
        doc.text(item.nome, M, y);
        sf(10, 'bold', [37,99,235]);
        doc.text(moeda(item.precoTotal), PW - M, y, { align: 'right' });
        nl(5);
        const det = item.usaPrecoFixo
          ? `Qtd: ${item.quantidade}  ·  Preço fixo`
          : `Qtd: ${item.quantidade}  ·  ${tempo(item.tempoAjustado)}  ·  ${DIFICULDADE[item.dificuldade]?.label || ''}`;
        sf(8, 'normal', [130,130,130]);
        doc.text(det, M, y); nl(7);
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

      // Fotos
      if (fotos.length > 0) {
        if (y > 200) { doc.addPage(); y = 20; }
        sf(7, 'bold', [120,120,120]);
        doc.text('FOTOS DO SERVIÇO', M, y); nl(6);

        const fW  = 55;
        const gap = 5;
        let fx    = M;
        let rowH  = 0;

        for (const foto of fotos) {
          try {
            const corrected = await corrigirOrientacao(foto.blob);
            const ratio     = corrected.w / corrected.h;
            const fH        = Math.round(fW / ratio);

            if (fx + fW > PW - M) { fx = M; y += rowH + gap; rowH = 0; }
            if (y + fH > 270)     { doc.addPage(); y = 20; fx = M; rowH = 0; }

            doc.addImage(corrected.data, 'JPEG', fx, y, fW, fH);
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

// ── Utilitários de módulo ────────────────────────────────────

// Carrega script externo dinamicamente
function carregarScript(src) {
  return new Promise((res, rej) => {
    if (document.querySelector(`script[src="${src}"]`)) { res(); return; }
    const s = document.createElement('script');
    s.src = src; s.onload = res; s.onerror = rej;
    document.head.appendChild(s);
  });
}

// Corrige orientação EXIF via canvas
function corrigirOrientacao(dataUrl) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      let orientation = 1;
      try {
        // Lê só os primeiros 512 bytes para encontrar o tag EXIF 0x0112
        const bin = atob(dataUrl.split(',')[1].substring(0, 680));
        for (let i = 0; i < bin.length - 12; i++) {
          if (bin.charCodeAt(i) === 0xFF && bin.charCodeAt(i+1) === 0xE1) {
            const le = bin.charCodeAt(i+10) === 0x49;
            const r2 = (o) => le
              ? bin.charCodeAt(o) | (bin.charCodeAt(o+1) << 8)
              : (bin.charCodeAt(o) << 8) | bin.charCodeAt(o+1);
            const base    = i + 10;
            const ifdOff  = base + (le
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
      } catch (_) { /* sem EXIF, usa 1 */ }

      const w = img.naturalWidth;
      const h = img.naturalHeight;
      const canvas = document.createElement('canvas');
      const ctx    = canvas.getContext('2d');

      if (orientation >= 5) { canvas.width = h; canvas.height = w; }
      else                  { canvas.width = w; canvas.height = h; }

      ctx.save();
      switch (orientation) {
        case 2: ctx.transform(-1, 0, 0,  1, w, 0); break;
        case 3: ctx.transform(-1, 0, 0, -1, w, h); break;
        case 4: ctx.transform( 1, 0, 0, -1, 0, h); break;
        case 5: ctx.transform( 0, 1, 1,  0, 0, 0); break;
        case 6: ctx.transform( 0, 1,-1,  0, h, 0); break;
        case 7: ctx.transform( 0,-1,-1,  0, h, w); break;
        case 8: ctx.transform( 0,-1, 1,  0, 0, w); break;
        default: break;
      }
      ctx.drawImage(img, 0, 0);
      ctx.restore();

      resolve({
        data: canvas.toDataURL('image/jpeg', 0.85),
        w:    canvas.width,
        h:    canvas.height,
      });
    };
    img.onerror = () => resolve({ data: dataUrl, w: 100, h: 100 });
    img.src = dataUrl;
  });
}
