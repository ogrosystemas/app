const DATABASE_NAME =
  'cutelariaOS';

const DATABASE_VERSION =
  10;

export const db = new Dexie(
  DATABASE_NAME
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
// VERSION 4
// ======================

db.version(4).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total'

});

// ======================
// VERSION 5
// ======================

db.version(5).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status'

});

// ======================
// VERSION 6
// ======================

db.version(6).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status',

  logs:
    '++id,tipo,data'

});

// ======================
// VERSION 7
// ======================

db.version(7).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status',

  logs:
    '++id,tipo,data',

  estoque:
    '++id,nome,quantidade'

});

// ======================
// VERSION 8
// ======================

db.version(8).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status',

  logs:
    '++id,tipo,data',

  estoque:
    '++id,nome,quantidade',

  equipamentos:
    '++id,nome,valor'

});

// ======================
// VERSION 9
// ======================

db.version(9).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status',

  logs:
    '++id,tipo,data',

  estoque:
    '++id,nome,quantidade',

  equipamentos:
    '++id,nome,valor',

  backups:
    '++id,data'

});

// ======================
// VERSION 10
// ======================

db.version(DATABASE_VERSION).stores({

  materiais:
    '++id,nome,categoria,valor',

  composicoes:
    '++id,nome,tipoAco,valorFinal',

  financeiro:
    '++id,tipo,valor,data',

  clientes:
    '++id,nome,telefone',

  settings:
    '++id,oficinaNome',

  orcamentos:
    '++id,nome,cliente,total',

  producao:
    '++id,nome,status',

  logs:
    '++id,tipo,data',

  estoque:
    '++id,nome,quantidade',

  equipamentos:
    '++id,nome,valor',

  backups:
    '++id,data'

});

// ======================
// OPEN DATABASE
// ======================

async function initializeDatabase() {

  try {

    await db.open();

    console.log(
      'IndexedDB conectado'
    );

  } catch (error) {

    console.error(
      'Erro IndexedDB:',
      error
    );

    // RESET AUTOMATICO

    if (
      error.name === 'VersionError'
      ||
      error.name === 'DatabaseClosedError'
    ) {

      console.warn(
        'Resetando banco local...'
      );

      await Dexie.delete(
        DATABASE_NAME
      );

      window.location.reload();

    }

  }

}

initializeDatabase();