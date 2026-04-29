import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraProDB');

// Definindo as tabelas e índices para buscas rápidas
db.version(1).stores({
  configuracoes: 'id', // id fixo 'perfil' para salvar pro-labore e custos
  clientes: '++id, &nome, whatsapp', // &nome garante que não duplicamos nomes
  servicos: '++id, nome, categoria',
  orcamentos: '++id, clienteId, data, status, total', // status: 'rascunho', 'enviado', 'pago'
  caixa: '++id, data, mes, ano' // histórico financeiro consolidado
});

export default db;