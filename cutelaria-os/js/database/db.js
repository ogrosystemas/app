export const db = new Dexie(
  'cutelariaOS'
);

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

db.open();