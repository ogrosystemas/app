import Dexie from 'https://unpkg.com/dexie@3/dist/dexie.mjs';

// ========================================
// DATABASE
// ========================================

export const db =
  new Dexie(
    'CutelariaDB'
  );

// ========================================
// VERSION 1
// ========================================

db.version(1).stores({

  producao:
    '++id,nome,status,progresso,createdAt'

});

// ========================================
// VERSION 2
// ========================================

db.version(2).stores({

  producao:
    '++id,nome,status,progresso,createdAt',

  materiais:
    '++id,nome,categoria,createdAt'

});

// ========================================
// VERSION 3
// ========================================

db.version(3).stores({

  producao:
    '++id,nome,status,progresso,createdAt',

  materiais:
    '++id,nome,categoria,createdAt',

  financeiro:
    '++id,tipo,categoria,createdAt'

});

// ========================================
// VERSION 4
// ========================================

db.version(4).stores({

  producao:
    '++id,nome,status,progresso,createdAt',

  materiais:
    '++id,nome,categoria,createdAt',

  financeiro:
    '++id,tipo,categoria,createdAt',

  pedidos:
    '++id,nome,cliente,status,createdAt'

});

// ========================================
// VERSION 5
// ========================================

db.version(5).stores({

  producao:
    '++id,nome,status,progresso,createdAt',

  materiais:
    '++id,nome,categoria,createdAt',

  financeiro:
    '++id,tipo,categoria,createdAt',

  pedidos:
    '++id,nome,cliente,status,createdAt',

  settings:
    '++id,createdAt'

});

// ========================================
// OPEN
// ========================================

db.open()
  .catch(error => {

    console.error(
      'Erro IndexedDB:',
      error
    );

  });