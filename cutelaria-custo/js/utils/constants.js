// ============================================
// CUTELARIA CUSTO - CONSTANTS.JS
// Enums, configurações globais e tipos
// ============================================

const APP_CONFIG = {
    name: 'Cutelaria Custo',
    version: '1.0.0',
    dbName: 'CutelariaCustoDB',
    dbVersion: 1,
    cacheName: 'cutelaria-custo-v1'
};

// Tipos de materiais/insumos para cutelaria
const TIPOS_INSUMO = {
    aco: { label: 'Aço', badge: 'badge-aco', icon: '🔩' },
    'aco-carbono': { label: 'Aço Carbono', badge: 'badge-aco', icon: '🔩' },
    'aco-inox': { label: 'Aço Inoxidável', badge: 'badge-aco', icon: '🔩' },
    'aco-damasco': { label: 'Aço Damasco', badge: 'badge-aco', icon: '🔩' },
    cabo: { label: 'Cabo', badge: 'badge-cabo', icon: '🪵' },
    'cabo-madeira': { label: 'Madeira', badge: 'badge-cabo', icon: '🪵' },
    'cabo-micarta': { label: 'Micarta', badge: 'badge-cabo', icon: '🟫' },
    'cabo-g10': { label: 'G10', badge: 'badge-cabo', icon: '🟫' },
    'cabo-carbono': { label: 'Fibra de Carbono', badge: 'badge-cabo', icon: '⬛' },
    'cabo-osso': { label: 'Osso/Chifre', badge: 'badge-cabo', icon: '🦴' },
    lixa: { label: 'Lixa', badge: 'badge-lixa', icon: '📄' },
    carvao: { label: 'Carvão', badge: 'badge-carvao', icon: '⚫' },
    gas: { label: 'Gás', badge: 'badge-gas', icon: '🔥' },
    'gas-propan': { label: 'Gás Propano', badge: 'badge-gas', icon: '🔥' },
    'gas-natural': { label: 'Gás Natural', badge: 'badge-gas', icon: '🔥' },
    ferramenta: { label: 'Ferramenta', badge: 'badge-ferramenta', icon: '🔧' },
    'disco-corte': { label: 'Disco de Corte', badge: 'badge-ferramenta', icon: '⭕' },
    'disco-desbaste': { label: 'Disco de Desbaste', badge: 'badge-ferramenta', icon: '⭕' },
    cola: { label: 'Cola/Epóxi', badge: 'badge-outro', icon: '🧪' },
    oleo: { label: 'Óleo/Têmpera', badge: 'badge-outro', icon: '🛢️' },
    juta: { label: 'Juta/Cordão', badge: 'badge-outro', icon: '🧶' },
    'pino-passante': { label: 'Pino/Passante', badge: 'badge-outro', icon: '📌' },
    outro: { label: 'Outro', badge: 'badge-outro', icon: '📦' }
};

// Unidades de medida
const UNIDADES = [
    { value: 'kg', label: 'kg' },
    { value: 'g', label: 'g' },
    { value: 'un', label: 'unidade' },
    { value: 'm', label: 'metro' },
    { value: 'cm', label: 'cm' },
    { value: 'mm', label: 'mm' },
    { value: 'l', label: 'litro' },
    { value: 'ml', label: 'ml' },
    { value: 'pacote', label: 'pacote' },
    { value: 'rolo', label: 'rolo' },
    { value: 'folha', label: 'folha' },
    { value: 'par', label: 'par' }
];

// Tipos de equipamentos
const TIPOS_EQUIPAMENTO = {
    esmerilhadeira: { label: 'Esmerilhadeira', icon: '⚙️' },
    lixadeira: { label: 'Lixadeira', icon: '🔄' },
    esmeril: { label: 'Esmeril', icon: '⚙️' },
    forja: { label: 'Forja/Esqueleira', icon: '🔥' },
    furadeira: { label: 'Furadeira', icon: '🔩' },
    serra: { label: 'Serra', icon: '🪚' },
    forno: { label: 'Forno Elétrico', icon: '🌡️' },
    prensa: { label: 'Prensa', icon: '⚡' },
    torno: { label: 'Torno', icon: '🔧' },
    morsa: { label: 'Morsa/Bancada', icon: '🗜️' },
    outro: { label: 'Outro', icon: '🔧' }
};

// Moedas
const MOEDAS = {
    BRL: { label: 'Real Brasileiro', symbol: 'R$', locale: 'pt-BR' },
    USD: { label: 'Dólar Americano', symbol: '$', locale: 'en-US' },
    EUR: { label: 'Euro', symbol: '€', locale: 'de-DE' },
    ARS: { label: 'Peso Argentino', symbol: '$', locale: 'es-AR' },
    CLP: { label: 'Peso Chileno', symbol: '$', locale: 'es-CL' },
    COP: { label: 'Peso Colombiano', symbol: '$', locale: 'es-CO' }
};

// Configurações padrão
const DEFAULT_CONFIG = {
    horaTrabalho: 50.00,
    precoKwh: 0.75,
    moeda: 'BRL',
    perdaPadrao: 10,
    margemPadrao: 50
};

// Schema do IndexedDB
const DB_SCHEMA = {
    materiais: '++id, nome, tipo, dataCompra',
    insumos: '++id, nome, tipo, dataCompra',
    equipamentos: '++id, nome, tipo, dataAquisicao',
    modelos: '++id, nome',
    facas: '++id, nome, data, cliente',
    configuracoes: '++id'
};

// Páginas do app
const PAGES = {
    dashboard: { title: 'Dashboard', icon: '📊' },
    materiais: { title: 'Materiais', icon: '📦' },
    insumos: { title: 'Insumos', icon: '🧪' },
    equipamentos: { title: 'Equipamentos', icon: '🔧' },
    faca: { title: 'Nova Faca', icon: '➕' },
    historico: { title: 'Histórico', icon: '📜' },
    configuracoes: { title: 'Configurações', icon: '⚙️' }
};
