import jsPDF from 'jspdf';
import 'jspdf-autotable';

export const gerarOrcamentoPdf = (orcamento, cliente, perfil) => {
  const doc = new jsPDF();
  const cinzaEscuro = "#334155";
  const azulPro = "#2563eb";

  // CABEÇALHO
  doc.setFontSize(22);
  doc.setTextColor(azulPro);
  doc.text("ORÇAMENTO PROFISSIONAL", 105, 20, { align: "center" });

  // DADOS DO PRESTADOR
  doc.setFontSize(10);
  doc.setTextColor(cinzaEscuro);
  doc.text(`Prestador: ${perfil.nomeEmpresa || 'Profissional Autônomo'}`, 14, 40);
  doc.text(`Data: ${new Date(orcamento.data).toLocaleDateString()}`, 14, 45);

  // DADOS DO CLIENTE
  doc.setDrawColor(200);
  doc.line(14, 50, 196, 50);
  doc.setFontSize(12);
  doc.text("CLIENTE:", 14, 60);
  doc.setFontSize(10);
  doc.text(`Nome: ${cliente.nome}`, 14, 66);
  doc.text(`Endereço: ${cliente.endereco || 'Não informado'}`, 14, 72);

  // TABELA DE SERVIÇOS
  const tableData = orcamento.itens.map(item => [
    item.nome,
    `${item.tempoAjustado} min`,
    `R$ ${item.valorFinal.toFixed(2)}`
  ]);

  doc.autoTable({
    startY: 80,
    head: [['Serviço', 'Tempo Est.', 'Subtotal']],
    body: tableData,
    headStyles: { fillStyle: azulPro },
    theme: 'striped'
  });

  // TOTAIS
  const finalY = doc.lastAutoTable.finalY + 10;
  doc.setFontSize(12);
  doc.text(`Taxa de Deslocamento: R$ ${Number(orcamento.taxaDeslocamento).toFixed(2)}`, 140, finalY, { align: "right" });
  doc.setFontSize(14);
  doc.setTextColor(azulPro);
  doc.text(`VALOR TOTAL: R$ ${orcamento.total.toFixed(2)}`, 140, finalY + 8, { align: "right" });

  // FOTOS (EVIDÊNCIAS)
  if (orcamento.fotos && orcamento.fotos.length > 0) {
    doc.addPage();
    doc.setTextColor(cinzaEscuro);
    doc.text("EVIDÊNCIAS DO LOCAL / REGISTRO FOTOGRÁFICO", 105, 20, { align: "center" });

    let xPos = 14;
    let yPos = 30;
    orcamento.fotos.forEach((foto, index) => {
      doc.addImage(foto, 'JPEG', xPos, yPos, 80, 60);
      xPos += 90;
      if (index % 2 !== 0) {
        xPos = 14;
        yPos += 70;
      }
    });
  }

  // TERMOS E GARANTIA
  const pageCount = doc.internal.getNumberOfPages();
  doc.setPage(pageCount);
  doc.setFontSize(8);
  doc.setTextColor(150);
  doc.text("Garantia de 90 dias conforme CDC. Orçamento válido por 7 dias.", 105, 285, { align: "center" });

  return doc;
};