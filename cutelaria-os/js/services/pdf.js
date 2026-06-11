import { db } from '../database/db.js';

export async function gerarPDF(composicaoId) {

  const composicao =
    await db.composicoes.get(
      Number(composicaoId)
    );

  if (!composicao) {

    alert('Composição não encontrada');

    return;

  }

  const {
    jsPDF
  } = window.jspdf;

  const doc =
    new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4'
    });

  // FUNDO

  doc.setFillColor(2, 6, 23);

  doc.rect(
    0,
    0,
    210,
    297,
    'F'
  );

  // HEADER

  doc.setFillColor(
    249,
    115,
    22
  );

  doc.rect(
    0,
    0,
    210,
    35,
    'F'
  );

  // TITULO

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.setFont(
    'helvetica',
    'bold'
  );

  doc.setFontSize(28);

  doc.text(
    'CUTELARIA OS',
    15,
    20
  );

  doc.setFontSize(11);

  doc.text(
    'Orçamento Técnico Premium',
    15,
    28
  );

  // CARD PRINCIPAL

  doc.setFillColor(
    15,
    23,
    42
  );

  doc.roundedRect(
    12,
    45,
    186,
    78,
    6,
    6,
    'F'
  );

  // NOME

  doc.setFontSize(22);

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.text(
    composicao.nome ||
    'Faca Personalizada',
    20,
    60
  );

  doc.setFontSize(11);

  doc.setTextColor(
    148,
    163,
    184
  );

  doc.text(
    'Ficha técnica da produção',
    20,
    68
  );

  // DETALHES

  let y = 82;

  const linhas = [

    [
      'Tipo de faca',
      composicao.tipoFaca || '-'
    ],

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
      composicao.espessura || '-'
    ],

    [
      'Comprimento',
      composicao.comprimento || '-'
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
      'Cabo',
      composicao.tipoCabo || '-'
    ]

  ];

  linhas.forEach(item => {

    doc.setTextColor(
      148,
      163,
      184
    );

    doc.setFontSize(10);

    doc.text(
      item[0],
      20,
      y
    );

    doc.setTextColor(
      255,
      255,
      255
    );

    doc.setFontSize(12);

    doc.text(
      String(item[1]),
      100,
      y
    );

    y += 8;

  });

  // FINANCEIRO

  doc.setFillColor(
    15,
    23,
    42
  );

  doc.roundedRect(
    12,
    132,
    186,
    52,
    6,
    6,
    'F'
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.setFontSize(18);

  doc.text(
    'Resumo Financeiro',
    20,
    147
  );

  // CUSTO

  doc.setTextColor(
    148,
    163,
    184
  );

  doc.setFontSize(11);

  doc.text(
    'Custo operacional',
    20,
    162
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.text(
    `R$ ${(composicao.custoTotal || 0).toFixed(2)}`,
    145,
    162
  );

  // MARGEM

  doc.setTextColor(
    148,
    163,
    184
  );

  doc.text(
    'Margem aplicada',
    20,
    172
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.text(
    `${composicao.margemLucro || 0}%`,
    145,
    172
  );

  // TOTAL

  doc.setFillColor(
    249,
    115,
    22
  );

  doc.roundedRect(
    20,
    194,
    170,
    36,
    6,
    6,
    'F'
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.setFontSize(14);

  doc.text(
    'VALOR FINAL',
    72,
    207
  );

  doc.setFontSize(28);

  doc.text(
    `R$ ${(composicao.valorFinal || 0).toFixed(2)}`,
    50,
    222
  );

  // OBSERVAÇÕES

  doc.setFillColor(
    15,
    23,
    42
  );

  doc.roundedRect(
    12,
    238,
    186,
    32,
    6,
    6,
    'F'
  );

  doc.setTextColor(
    148,
    163,
    184
  );

  doc.setFontSize(10);

  doc.text(
    'Observações',
    20,
    251
  );

  doc.setTextColor(
    255,
    255,
    255
  );

  doc.setFontSize(11);

  doc.text(
    composicao.observacoes ||
    'Produção artesanal premium.',
    20,
    261
  );

  // FOOTER

  doc.setTextColor(
    100,
    116,
    139
  );

  doc.setFontSize(9);

  doc.text(
    'Gerado automaticamente pelo Cutelaria OS',
    55,
    285
  );

  // EXPORTAR

  doc.save(
    `orcamento-${composicao.nome}.pdf`
  );

}