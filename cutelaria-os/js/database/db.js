export const db = new Dexie(
  'cutelariaOS'
);

// ======================
// VERSION 1
// ======================

db.version(1).stores({

  materiais:
    '++id,nome,categoria,valor'

});

// ======================
// VERSION 2
// ======================

db.version(2).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone'

});

// ======================
// VERSION 3
// ======================

db.version(3).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome'

});

// ======================
// OPEN DATABASE
// ======================

db.open()
  .catch((error) => {

    console.error(
      'Erro IndexedDB:',
      error
    );

  });