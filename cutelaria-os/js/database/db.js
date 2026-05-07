export const db = new Dexie(
  'CutelariaOS'
);

db.version(4).stores({

  materiais:
    `
      ++id,
      nome,
      categoria,
      valor,
      unidade,

      estoqueAtual,
      estoqueMinimo,

      createdAt
    `,

  equipamentos:
    '++id,nome,valorCompra,vidaUtil,horasMes,custoHora',

  composicoes:
    `
      ++id,
      nome,
      categoria,

      tipoFaca,
      tipoAco,
      hrc,
      espessura,
      comprimento,
      peso,
      acabamento,
      desbaste,
      tipoCabo,
      possuiBainha,
      observacoes,

      fotoCapa,

      custoMateriais,
      custoEtapas,
      custoTotal,
      margemLucro,
      valorFinal,
      createdAt
    `,

  composicaoItens:
    `
      ++id,
      composicaoId,
      materialId,
      nome,
      quantidade,
      valorUnitario,
      subtotal
    `,

  etapas:
    `
      ++id,
      composicaoId,
      nome,
      horas,
      valorHora,
      custoEquipamentos,
      custoEnergia,
      custoAbrasivos,
      custoTotal
    `,

  fotos:
    `
      ++id,
      composicaoId,
      imagem,
      createdAt
    `,

  financeiro:
    `
      ++id,
      tipo,
      categoria,
      descricao,
      valor,
      vencimento,
      status,
      createdAt
    `

});