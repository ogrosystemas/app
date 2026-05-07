export const db = new Dexie('cutelariaOS');

db.version(1).stores({

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
    valorFinal
  `

});