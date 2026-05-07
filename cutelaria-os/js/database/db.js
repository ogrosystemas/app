export const db = new Dexie('cutelariaOS');

db.version(1).stores({
  materiais: '++id,nome,categoria',
  producoes: '++id,nome,data',
  equipamentos: '++id,nome',
});