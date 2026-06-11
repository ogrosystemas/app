import Dexie from 'https://unpkg.com/dexie@3/dist/dexie.mjs';

// ============================================
// ENUMS — fonte única da verdade para tipos
// ============================================

export const TIPO_FINANCEIRO = {
  RECEITA: 'receita',
  DESPESA: 'despesa'
};

export const STATUS_PRODUCAO = {
  INICIADA:    'Iniciada',
  FORJAMENTO:  'Forjamento',
  TEMPERA:     'Têmpera',
  ACABAMENTO:  'Acabamento',
  FINALIZADA:  'Finalizada'
};

export const STATUS_PEDIDO = {
  ABERTO:      'Aberto',
  EM_PRODUCAO: 'Em produção',
  CONCLUIDO:   'Concluído',
  CANCELADO:   'Cancelado'
};

// ============================================
// DATABASE
// ============================================

export const db = new Dexie('CutelariaDB');

// Versões históricas — necessárias para migração de usuários existentes
db.version(1).stores({ producao: '++id,nome,status,progresso,createdAt' });
db.version(2).stores({ producao: '++id,nome,status,progresso,createdAt', materiais: '++id,nome,categoria,createdAt' });
db.version(3).stores({ producao: '++id,nome,status,progresso,createdAt', materiais: '++id,nome,categoria,createdAt', financeiro: '++id,tipo,categoria,createdAt' });
db.version(4).stores({ producao: '++id,nome,status,progresso,createdAt', materiais: '++id,nome,categoria,createdAt', financeiro: '++id,tipo,categoria,createdAt', pedidos: '++id,nome,cliente,status,createdAt' });
db.version(5).stores({ producao: '++id,nome,status,progresso,createdAt', materiais: '++id,nome,categoria,createdAt', financeiro: '++id,tipo,categoria,createdAt', pedidos: '++id,nome,cliente,status,createdAt', settings: '++id,createdAt' });

// Versão atual — schema consolidado
db.version(6).stores({
  producao:  '++id,nome,status,progresso,pedidoId,createdAt',
  materiais: '++id,nome,categoria,createdAt',
  financeiro:'++id,tipo,categoria,pedidoId,createdAt',
  pedidos:   '++id,nome,cliente,status,createdAt',
  settings:  '++id,createdAt'
});

db.open().catch(err => console.error('Erro IndexedDB:', err));

// ============================================
// HELPERS
// ============================================

export function fmtBRL(value) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
}

export function fmtDate(iso) {
  if (!iso) return '--';
  return new Date(iso).toLocaleDateString('pt-BR');
}

export function fmtDatetime(iso) {
  if (!iso) return '--';
  return new Date(iso).toLocaleString('pt-BR');
}
