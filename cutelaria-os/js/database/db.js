const db = new Dexie(
  'cutelaria_os'
);

// ========================================
// DATABASE VERSION
// ========================================

db.version(7).stores({

  configuracoes: `
    ++id,
    empresa,
    telefone,
    cidade
  `,

  clientes: `
    ++id,
    nome,
    telefone,
    createdAt
  `,

  composicoes: `
    ++id,
    nome,
    custo,
    createdAt
  `,

  producao: `
    ++id,
    nome,
    status,
    progresso,
    createdAt
  `,

  financeiro: `
    ++id,
    tipo,
    descricao,
    valor,
    createdAt
  `,

  pedidos: `
    ++id,
    nome,
    cliente,
    valor,
    status,
    prazo,
    createdAt
  `

});

// ========================================
// OPEN
// ========================================

db.open()
  .then(() => {

    console.log(
      'Banco carregado.'
    );

  })
  .catch((error) => {

    console.error(
      'Erro IndexedDB:',
      error
    );

  });

export {
  db
};