export const db = new Dexie('cutelariaOS');

db.version(3).stores({

  materiais: `
    ++id,
    nome,
    categoria,
    unidade,
    valor,
    estoque
  `,

  equipamentos: `
    ++id,
    nome,
    valorCompra,
    vidaUtil,
    custoHora
  `,

  composicoes: `
    ++id,
    nome,
    categoria,
    custoMateriais,
    custoMaoObra,
    custoEnergia,
    custoTotal,
    margemLucro,
    valorFinal,
    createdAt
  `,

  composicaoItens: `
    ++id,
    composicaoId,
    materialId,
    nome,
    quantidade,
    valorUnitario,
    subtotal
  `,

  etapas: `
    ++id,
    composicaoId,
    nome,
    horas,
    custo
  `

});