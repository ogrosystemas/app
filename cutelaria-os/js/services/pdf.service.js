export async function gerarPDF({
  composicao,
  itens = [],
  etapas = []
}) {

  const { jsPDF } = window.jspdf;

  const doc = new jsPDF();

  let y = 20;

  // HEADER

  doc.setFontSize(24);

  doc.text(
    'CUTELARIA OS',
    20,
    y
  );

  y += 8;

  doc.setFontSize(12);

  doc.text(
    'Ficha técnica e orçamento',
    20,
    y
  );

  y += 10;

  doc.setDrawColor(220);

  doc.line(20, y, 190, y);

  // IDENTIFICAÇÃO

  y += 12;

  doc.setFontSize(18);

  doc.text(
    composicao.nome,
    20,
    y
  );

  y += 10;

  doc.setFontSize(11);

  doc.text(
    `Tipo: ${composicao.tipoFaca || '-'}`,
    20,
    y
  );

  y += 7;

  doc.text(
    `Data: ${new Date(
      composicao.createdAt
    ).toLocaleDateString()}`,
    20,
    y
  );

  // FICHA TÉCNICA

  y += 15;

  doc.setFontSize(16);

  doc.text(
    'Ficha Técnica',
    20,
    y
  );

  y += 10;

  doc.setFontSize(11);

  const ficha = [

    [
      'Tipo de aço',
      composicao.tipoAco || '-'
    ],

    [
      'HRC',
      composicao.hrc || '-'
    ],

    [
      'Espessura',
      `${composicao.espessura || '-'} mm`
    ],

    [
      'Comprimento',
      `${composicao.comprimento || '-'} cm`
    ],

    [
      'Peso',
      `${composicao.peso || '-'} g`
    ],

    [
      'Acabamento',
      composicao.acabamento || '-'
    ],

    [
      'Desbaste',
      composicao.desbaste || '-'
    ],

    [
      'Tipo de cabo',
      composicao.tipoCabo || '-'
    ],

    [
      'Bainha',
      composicao.possuiBainha
        ? 'Sim'
        : 'Não'
    ]

  ];

  ficha.forEach(item => {

    doc.text(
      `${item[0]}: ${item[1]}`,
      20,
      y
    );

    y += 7;

  });

  // OBSERVAÇÕES

  if (composicao.observacoes) {

    y += 8;

    doc.setFontSize(16);

    doc.text(
      'Observações',
      20,
      y
    );

    y += 10;

    doc.setFontSize(11);

    const observacoes =
      doc.splitTextToSize(
        composicao.observacoes,
        170
      );

    doc.text(
      observacoes,
      20,
      y
    );

    y +=
      observacoes.length * 7;

  }

  // FINANCEIRO

  y += 15;

  doc.setFontSize(16);

  doc.text(
    'Resumo Financeiro',
    20,
    y
  );

  y += 10;

  doc.setFontSize(11);

  const lucro =
    composicao.valorFinal -
    composicao.custoTotal;

  const margem =
    (
      (lucro /
        composicao.valorFinal) *
      100
    ).toFixed(1);

  const financeiro = [

    [
      'Materiais',
      composicao.custoMateriais
    ],

    [
      'Etapas',
      composicao.custoEtapas
    ],

    [
      'Custo total',
      composicao.custoTotal
    ],

    [
      'Lucro',
      lucro
    ],

    [
      'Margem',
      `${margem}%`
    ],

    [
      'Valor final',
      composicao.valorFinal
    ]

  ];

  financeiro.forEach(item => {

    const valor =
      typeof item[1] === 'number'
        ? `R$ ${item[1].toFixed(2)}`
        : item[1];

    doc.text(
      `${item[0]}: ${valor}`,
      20,
      y
    );

    y += 7;

  });

  // MATERIAIS

  y += 15;

  doc.setFontSize(16);

  doc.text(
    'Materiais Utilizados',
    20,
    y
  );

  y += 10;

  doc.setFontSize(11);

  itens.forEach(item => {

    doc.text(

      `${item.nome} | Qtd: ${item.quantidade} | R$ ${item.subtotal.toFixed(2)}`,

      20,
      y
    );

    y += 7;

  });

  // ETAPAS

  y += 12;

  doc.setFontSize(16);

  doc.text(
    'Etapas da Produção',
    20,
    y
  );

  y += 10;

  doc.setFontSize(11);

  etapas.forEach(etapa => {

    doc.text(

      `${etapa.nome} | ${etapa.horas}h | R$ ${etapa.custoTotal.toFixed(2)}`,

      20,
      y
    );

    y += 7;

  });

  // RODAPÉ

  y += 20;

  doc.setDrawColor(220);

  doc.line(20, y, 190, y);

  y += 10;

  doc.setFontSize(10);

  doc.text(
    'Gerado pelo Cutelaria OS',
    20,
    y
  );

  y += 6;

  doc.text(
    'Sistema profissional de gestão para cuteleiros',
    20,
    y
  );

  // SAVE

  doc.save(
    `${composicao.nome}.pdf`
  );

}