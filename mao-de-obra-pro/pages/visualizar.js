// ============================================================
// pages/visualizar.js — Ver orçamento + PDF + WhatsApp
// ============================================================

import { render, toast } from '../js/app.js';
import { getById, getAll, put } from '../js/db.js';
import { moeda, dataLocal, tempo, DIFICULDADE } from '../js/calculadora.js';
import { navigate } from '../js/router.js';

export default async function visualizarPage({ id }) {
  if (!id) { navigate('/'); return; }

  const orc = await getById('orcamentos', parseInt(id));
  if (!orc) { toast('Orçamento não encontrado.', 'danger'); navigate('/'); return; }

  const cliente  = await getById('clientes', orc.clienteId);
  const fotos    = await getAll('fotos', 'orcamentoId', IDBKeyRange.only(orc.id));

  const badgeStatus = { pendente: 'warning', aprovado: 'success', recusado: 'danger', cancelado: 'secondary' };

  render(`
    <div class="page-content pb-5">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-link p-0 text-decoration-none" onclick="history.back()">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <span class="badge bg-${badgeStatus[orc.status] || 'secondary'} fs-6">${orc.status}</span>
      </div>

      <!-- Cabeçalho do orçamento -->
      <div class="card border-0 shadow-sm mb-3" id="pdf-content">
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
                    ${item.usaPrecoFixo ? 'Preço fixo' : `${tempo(item.tempoAjustado)} · ${DIFICULDADE[item.dificuldade]?.label || item.dificuldade}`}
                    · Qtd: ${item.quantidade}
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
                <span>− ${orc.desconto.tipo === 'percentual' ? orc.desconto.valor + '%' : moeda(orc.desconto.valor)}</span>
              </div>` : ''}
            <div class="d-flex justify-content-between fw-bold fs-5 pt-2 border-top">
              <span>Total</span><span class="text-primary">${moeda(orc.total)}</span>
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
              ${fotos.map(f => `
                <div class="col-4">
                  <img src="${f.blob}" class="img-fluid rounded" style="height:80px;width:100%;object-fit:cover"
                    onclick="ampliarFoto('${f.blob}')">
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

            <!-- Status -->
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
                <i class="bi bi-whatsapp me-2"></i>Enviar resumo pelo WhatsApp
              </button>
            ` : ''}

            <button class="btn btn-primary" onclick="gerarPDF()" id="btn-pdf">
              <i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF
            </button>

          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox foto -->
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

  window.ampliarFoto = (src) => {
    document.getElementById('foto-ampliada').src = src;
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

    const linhas = (orc.itens || []).map(i =>
      `✓ ${i.nome} ×${i.quantidade} — ${moeda(i.precoTotal)}`
    ).join('\n');

    const msg = [
      `*ORÇAMENTO #${orc.id} — MÃO DE OBRA PRO*`,
      ``,
      `*Cliente:* ${cliente?.nome || ''}`,
      `*Profissão:* ${orc.profissaoNome}`,
      `*Data:* ${dataLocal(orc.data)}`,
      `*Válido até:* ${dataLocal(orc.dataVencimento)}`,
      ``,
      `*SERVIÇOS:*`,
      linhas,
      ``,
      `*Deslocamento:* ${moeda(orc.taxaDeslocamento)}`,
      orc.desconto?.valor > 0 ? `*Desconto:* ${orc.desconto.tipo === 'percentual' ? orc.desconto.valor + '%' : moeda(orc.desconto.valor)}` : null,
      `*TOTAL: ${moeda(orc.total)}*`,
    ].filter(l => l !== null).join('\n');

    window.open(`https://wa.me/55${num}?text=${encodeURIComponent(msg)}`, '_blank');
  };

  window.gerarPDF = async () => {
    const btn = document.getElementById('btn-pdf');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando...'; }

    try {
      // Carrega jsPDF dinamicamente
      if (!window.jspdf) {
        await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ unit: 'mm', format: 'a4' });
      const W_PDF = 210;
      const MARGIN = 15;
      const CONTENT_W = W_PDF - MARGIN * 2;
      let y = 20;

      // Funções auxiliares
      const line = () => { doc.setDrawColor(220, 220, 220); doc.line(MARGIN, y, W_PDF - MARGIN, y); y += 4; };
      const text = (txt, x, size = 10, style = 'normal', color = [40,40,40]) => {
        doc.setFontSize(size); doc.setFont('helvetica', style); doc.setTextColor(...color);
        doc.text(String(txt || ''), x, y);
      };
      const nextLine = (h = 6) => { y += h; };

      // Header
      doc.setFillColor(37, 99, 235);
      doc.rect(0, 0, W_PDF, 30, 'F');
      doc.setFontSize(16); doc.setFont('helvetica', 'bold'); doc.setTextColor(255, 255, 255);
      doc.text('MÃO DE OBRA PRO', MARGIN, 13);
      doc.setFontSize(10); doc.setFont('helvetica', 'normal');
      doc.text(`Orçamento #${orc.id}  ·  ${orc.profissaoNome || ''}`, MARGIN, 21);
      doc.text(`${dataLocal(orc.data)}  ·  Válido até ${dataLocal(orc.dataVencimento)}`, W_PDF - MARGIN, 21, { align: 'right' });
      y = 40;

      // Cliente
      doc.setFontSize(8); doc.setFont('helvetica', 'bold'); doc.setTextColor(100, 100, 100);
      doc.text('CLIENTE', MARGIN, y); y += 5;
      text(cliente?.nome || 'Cliente removido', MARGIN, 12, 'bold');
      if (cliente?.whatsapp) { nextLine(5); text(cliente.whatsapp, MARGIN, 9, 'normal', [80,80,80]); }
      if (cliente?.endereco) { nextLine(4); text(cliente.endereco, MARGIN, 9, 'normal', [80,80,80]); }
      y += 8; line();

      // Serviços
      doc.setFontSize(8); doc.setFont('helvetica', 'bold'); doc.setTextColor(100, 100, 100);
      doc.text('SERVIÇOS', MARGIN, y); y += 6;

      for (const item of (orc.itens || [])) {
        if (y > 260) { doc.addPage(); y = 20; }
        text(item.nome, MARGIN, 10, 'semibold');
        text(moeda(item.precoTotal), W_PDF - MARGIN, 10, 'bold', [37,99,235]);
        doc.text(moeda(item.precoTotal), W_PDF - MARGIN, y, { align: 'right' });
        // reajusta para evitar texto duplo
        doc.setFontSize(10); doc.setFont('helvetica', 'normal'); doc.setTextColor(40,40,40);
        doc.text(item.nome, MARGIN, y);
        nextLine(4);
        const detalhe = `Qtd: ${item.quantidade}  ·  ${item.usaPrecoFixo ? 'Preço fixo' : tempo(item.tempoAjustado) + ' · ' + (DIFICULDADE[item.dificuldade]?.label || '')}`;
        doc.setFontSize(8); doc.setTextColor(120,120,120);
        doc.text(detalhe, MARGIN, y);
        nextLine(6);
      }

      y += 2; line();

      // Totais
      const rows = [
        ['Subtotal', moeda(orc.subtotal)],
        ['Deslocamento', moeda(orc.taxaDeslocamento)],
      ];
      if (orc.desconto?.valor > 0) {
        rows.push(['Desconto', `- ${orc.desconto.tipo === 'percentual' ? orc.desconto.valor + '%' : moeda(orc.desconto.valor)}`]);
      }

      for (const [label, val] of rows) {
        doc.setFontSize(10); doc.setFont('helvetica', 'normal'); doc.setTextColor(80,80,80);
        doc.text(label, MARGIN, y);
        doc.text(val, W_PDF - MARGIN, y, { align: 'right' });
        nextLine(6);
      }

      // Total final
      y += 2;
      doc.setFillColor(37, 99, 235);
      doc.roundedRect(MARGIN, y - 4, CONTENT_W, 10, 2, 2, 'F');
      doc.setFontSize(12); doc.setFont('helvetica', 'bold'); doc.setTextColor(255,255,255);
      doc.text('TOTAL', MARGIN + 4, y + 3);
      doc.text(moeda(orc.total), W_PDF - MARGIN - 4, y + 3, { align: 'right' });
      y += 16;

      // Fotos (se existirem)
      if (fotos.length > 0) {
        if (y > 200) { doc.addPage(); y = 20; }
        doc.setFontSize(8); doc.setFont('helvetica', 'bold'); doc.setTextColor(100,100,100);
        doc.text('FOTOS DO SERVIÇO', MARGIN, y); y += 6;
        let fx = MARGIN;
        for (const foto of fotos) {
          try {
            doc.addImage(foto.blob, 'JPEG', fx, y, 55, 42);
            fx += 60;
            if (fx > W_PDF - MARGIN - 55) { fx = MARGIN; y += 47; }
          } catch {}
        }
        y += 50;
      }

      // Rodapé
      doc.setFontSize(8); doc.setFont('helvetica', 'normal'); doc.setTextColor(150,150,150);
      doc.text('Gerado por Mão de Obra PRO', W_PDF / 2, 287, { align: 'center' });

      doc.save(`orcamento-${orc.id}-${(cliente?.nome || 'cliente').replace(/\s+/g,'-')}.pdf`);
      toast('PDF gerado!');
    } catch (err) {
      console.error('PDF error:', err);
      toast('Erro ao gerar PDF. Tente novamente.', 'danger');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-file-earmark-pdf me-2"></i>Gerar PDF'; }
    }
  };
}

function loadScript(src) {
  return new Promise((res, rej) => {
    const s = document.createElement('script');
    s.src = src; s.onload = res; s.onerror = rej;
    document.head.appendChild(s);
  });
}
