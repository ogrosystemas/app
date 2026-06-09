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
      if (!window.jspdf) {
        await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');
      }

      const { jsPDF } = window.jspdf;
      const doc      = new jsPDF({ unit: 'mm', format: 'a4' });
      const PW       = 210;   // largura página
      const M        = 15;    // margem
      const CW       = PW - M * 2; // largura conteúdo
      let y          = 0;

      // ── helpers ──────────────────────────────────────────
      const nl = (h = 6) => { y += h; };

      const setFont = (size, style = 'normal', color = [40,40,40]) => {
        doc.setFontSize(size);
        doc.setFont('helvetica', style);
        doc.setTextColor(...color);
      };

      const rowLR = (left, right, size = 10, colorL = [80,80,80], colorR = [40,40,40]) => {
        setFont(size, 'normal', colorL);
        doc.text(String(left), M, y);
        setFont(size, 'bold', colorR);
        doc.text(String(right), PW - M, y, { align: 'right' });
        nl(6);
      };

      const hline = (color = [220,220,220]) => {
        doc.setDrawColor(...color);
        doc.line(M, y, PW - M, y);
        nl(4);
      };

      // ── Header ───────────────────────────────────────────
      doc.setFillColor(37, 99, 235);
      doc.rect(0, 0, PW, 32, 'F');

      setFont(18, 'bold', [255,255,255]);
      doc.text('MÃO DE OBRA PRO', M, 13);

      setFont(9, 'normal', [200,220,255]);
      doc.text(`Orçamento #${orc.id}  ·  ${orc.profissaoNome || ''}`, M, 21);
      doc.text(`${dataLocal(orc.data)}  ·  Válido até ${dataLocal(orc.dataVencimento)}`, PW - M, 21, { align: 'right' });

      y = 42;

      // ── Cliente ──────────────────────────────────────────
      setFont(7, 'bold', [120,120,120]);
      doc.text('CLIENTE', M, y); nl(5);

      setFont(12, 'bold', [20,20,20]);
      doc.text(cliente?.nome || 'Cliente removido', M, y); nl(5);

      if (cliente?.whatsapp) {
        setFont(9, 'normal', [80,80,80]);
        doc.text(cliente.whatsapp, M, y); nl(4);
      }
      if (cliente?.endereco) {
        setFont(9, 'normal', [120,120,120]);
        doc.text(cliente.endereco, M, y); nl(4);
      }

      nl(4); hline();

      // ── Serviços ─────────────────────────────────────────
      setFont(7, 'bold', [120,120,120]);
      doc.text('SERVIÇOS', M, y); nl(6);

      for (const item of (orc.itens || [])) {
        if (y > 255) { doc.addPage(); y = 20; }

        // Nome à esquerda, preço à direita — numa única chamada cada
        setFont(10, 'bold', [20,20,20]);
        doc.text(item.nome, M, y);
        setFont(10, 'bold', [37,99,235]);
        doc.text(moeda(item.precoTotal), PW - M, y, { align: 'right' });
        nl(5);

        // Detalhe
        const detalhe = item.usaPrecoFixo
          ? `Qtd: ${item.quantidade}  ·  Preço fixo`
          : `Qtd: ${item.quantidade}  ·  ${tempo(item.tempoAjustado)}  ·  ${DIFICULDADE[item.dificuldade]?.label || item.dificuldade}`;
        setFont(8, 'normal', [130,130,130]);
        doc.text(detalhe, M, y);
        nl(7);
      }

      hline();

      // ── Totais ───────────────────────────────────────────
      rowLR('Subtotal', moeda(orc.subtotal), 10, [100,100,100], [40,40,40]);
      rowLR('Deslocamento', moeda(orc.taxaDeslocamento), 10, [100,100,100], [40,40,40]);

      if (orc.desconto?.valor > 0) {
        const dVal = orc.desconto.tipo === 'percentual'
          ? orc.desconto.valor + '%'
          : moeda(orc.desconto.valor);
        rowLR('Desconto', '- ' + dVal, 10, [200,50,50], [200,50,50]);
      }

      nl(2);
      doc.setFillColor(37, 99, 235);
      doc.roundedRect(M, y - 3, CW, 11, 2, 2, 'F');
      setFont(12, 'bold', [255,255,255]);
      doc.text('TOTAL', M + 4, y + 4);
      doc.text(moeda(orc.total), PW - M - 4, y + 4, { align: 'right' });
      nl(18);

      // ── Fotos ────────────────────────────────────────────
      if (fotos.length > 0) {
        if (y > 200) { doc.addPage(); y = 20; }

        setFont(7, 'bold', [120,120,120]);
        doc.text('FOTOS DO SERVIÇO', M, y); nl(6);

        const fotoW = 55;
        const fotoH = 70; // proporção retrato
        const gap   = 5;
        let fx = M;

        for (const foto of fotos) {
          try {
            // Corrige orientação EXIF desenhando num canvas antes
            const corrected = await corrigirOrientacaoFoto(foto.blob);
            const imgData   = corrected.dataUrl;
            const imgW      = corrected.width;
            const imgH      = corrected.height;

            // Mantém proporção real da imagem
            const ratio   = imgW / imgH;
            const drawH   = fotoW / ratio;

            if (fx + fotoW > PW - M) {
              fx = M;
              y += drawH + gap;
            }
            if (y + drawH > 270) { doc.addPage(); y = 20; fx = M; }

            doc.addImage(imgData, 'JPEG', fx, y, fotoW, drawH);
            fx += fotoW + gap;
          } catch (e) {
            console.warn('Erro ao adicionar foto:', e);
          }
        }
        y += fotoH + 10;
      }

      // ── Rodapé ───────────────────────────────────────────
      setFont(7, 'normal', [180,180,180]);
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


