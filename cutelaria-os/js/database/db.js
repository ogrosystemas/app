export const db = new Dexie('cutelariaOS');

db.version(2).stores({

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
    valorCompra
  `,

  composicoes: `
    ++id,
    nome,
    categoria,
    custoMateriais,
    custoMaoObra,
    custoTotal,
    margemLucro,
    valorFinal,
    createdAt
  `,

  composicaoItens: `
    ++id,
    composicaoId,
    materialId,
    quantidade,
    subtotal
  `

});