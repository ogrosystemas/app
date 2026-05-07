export async function gerarPDF({
  composicao,
  itens = [],
  etapas = []
}) {

  const { jsPDF } = window.jspdf;

  const doc = new jsPDF();

  // HEADER

  doc.setFontSize(22);

  doc.text(
    'CUTELARIA OS',
    20,
    20
  );

  doc.setFontSize(12);

  doc.text(
    composicao.nome,
    20,
    32
  );

  doc.setDrawColor(240);

  doc.line(20, 38, 190, 38);

  // RESUMO

  let y = 50;

  doc.setFontSize(16);

  doc.text('Resumo Financeiro', 20, y);

  y += 12;

  doc.setFontSize(12);

  doc.text(
    `Custo materiais: R$ ${composicao.custoMateriais.toFixed(2)}`,
    20,
    y
  );

  y += 8;

  doc.text(
    `Custo etapas: R$ ${composicao.custoEtapas.toFixed(2)}`,
    20,
    y
  );

  y += 8;

  doc.text(
    `Custo total: R$ ${composicao.custoTotal.toFixed(2)}`,
    20,
    y
  );

  y += 8;

  doc.text(
    `Margem: ${composicao.margemLucro}%`,
    20,
    y
  );

  y += 10;

  doc.setFontSize(16);

  doc.text(
    `Valor final: R$ ${composicao.valorFinal.toFixed(2)}`,
    20,
    y
  );

  // MATERIAIS

  y += 20;

  doc.setFontSize(16);

  doc.text('Materiais', 20, y);

  y += 10;

  doc.setFontSize(11);

  itens.forEach(item => {

    doc.text(
      `${item.nome} | Qtd: ${item.quantidade} | R$ ${item.subtotal.toFixed(2)}`,
      20,
      y
    );

    y += 8;

  });

  // ETAPAS

  y += 10;

  doc.setFontSize(16);

  doc.text('Etapas', 20, y);

  y += 10;

  doc.setFontSize(11);

  etapas.forEach(etapa => {

    doc.text(
      `${etapa.nome} | ${etapa.horas}h | R$ ${etapa.custoTotal.toFixed(2)}`,
      20,
      y
    );

    y += 8;

  });

  // FOOTER

  y += 20;

  doc.setFontSize(10);

  doc.text(
    'Gerado pelo Cutelaria OS',
    20,
    y
  );

  doc.save(
    `${composicao.nome}.pdf`
  );

}