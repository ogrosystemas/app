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

// Tipo de material — define o grupo e como é medido
export const TIPO_MATERIAL = {
  ACO:        'aco',
  CABO:       'cabo',
  CONSUMIVEL: 'consumivel'
};

// Subtipo de consumível — define medida automaticamente
export const SUBTIPO_CONSUMIVEL = {
  GENERICO:   'generico',   // unidade genérica
  PINO:       'pino',       // comprimento em mm
  ESPACADOR:  'espacador',  // unidades
};

// Tipo de medida — derivado do tipo / subtipo de material
export const TIPO_MEDIDA = {
  // Aço
  COMPRIMENTO: 'comprimento',  // cm
  PESO:        'peso',         // kg
  // Cabo
  TALA:        'tala',         // pares
  BLOCO:       'bloco',        // unidades
  // Consumível genérico
  UNIDADE:     'unidade',      // un
  // Consumível — pino
  PINO_MM:     'pino_mm',      // mm
  // Consumível — espaçador (reutiliza unidade, mas semântica distinta)
  ESPACADOR:   'espacador',    // un
};

// Retorna a unidade de exibição e o passo do +/- para cada tipo de medida
export const MEDIDA_CONFIG = {
  [TIPO_MEDIDA.COMPRIMENTO]: { unidade: 'cm',  passo: 1   },
  [TIPO_MEDIDA.PESO]:        { unidade: 'kg',  passo: 0.1 },
  [TIPO_MEDIDA.TALA]:        { unidade: 'par', passo: 1   },
  [TIPO_MEDIDA.BLOCO]:       { unidade: 'un',  passo: 1   },
  [TIPO_MEDIDA.UNIDADE]:     { unidade: 'un',  passo: 1   },
  [TIPO_MEDIDA.PINO_MM]:     { unidade: 'mm',  passo: 1   },
  [TIPO_MEDIDA.ESPACADOR]:   { unidade: 'un',  passo: 1   },
};

// Mapa subtipo → tipoMedida automático
export const SUBTIPO_MEDIDA = {
  [SUBTIPO_CONSUMIVEL.GENERICO]:  TIPO_MEDIDA.UNIDADE,
  [SUBTIPO_CONSUMIVEL.PINO]:      TIPO_MEDIDA.PINO_MM,
  [SUBTIPO_CONSUMIVEL.ESPACADOR]: TIPO_MEDIDA.ESPACADOR,
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
db.version(6).stores({
  producao:  '++id,nome,status,progresso,pedidoId,createdAt',
  materiais: '++id,nome,categoria,createdAt',
  financeiro:'++id,tipo,categoria,pedidoId,createdAt',
  pedidos:   '++id,nome,cliente,status,createdAt',
  settings:  '++id,createdAt'
});

// v7 — adiciona tipoMaterial e tipoMedida nos índices de materiais
//      adiciona materiaisPrevistos (JSON) na produção
db.version(7).stores({
  producao:  '++id,nome,status,progresso,pedidoId,createdAt',
  materiais: '++id,nome,tipoMaterial,tipoMedida,createdAt',
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

// Retorna unidade de exibição de um material
export function unidadeMaterial(mat) {
  if (!mat) return 'un';
  const cfg = MEDIDA_CONFIG[mat.tipoMedida];
  return cfg ? cfg.unidade : (mat.unidade || 'un');
}

// Retorna passo do ajuste rápido de um material
export function passoMaterial(mat) {
  if (!mat) return 1;
  const cfg = MEDIDA_CONFIG[mat.tipoMedida];
  return cfg ? cfg.passo : 1;
}
