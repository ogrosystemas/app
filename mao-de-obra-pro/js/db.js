// ============================================================
// db.js — IndexedDB wrapper (sem dependências externas)
// ============================================================

const DB_NAME = 'MaoDeObraPro';
const DB_VERSION = 1;

let _db = null;

export function openDB() {
  if (_db) return Promise.resolve(_db);

  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);

    req.onupgradeneeded = (e) => {
      const db = e.target.result;

      function store(name, keyPath, autoIncrement) {
        if (db.objectStoreNames.contains(name)) return null;
        return db.createObjectStore(name, { keyPath, autoIncrement });
      }

      const profissoes = store('profissoes', 'id', true);
      if (profissoes) {
        profissoes.createIndex('slug', 'slug', { unique: true });
      }

      const clientes = store('clientes', 'id', true);
      if (clientes) {
        clientes.createIndex('nome', 'nome', { unique: false });
      }

      const servicos = store('servicos', 'id', true);
      if (servicos) {
        servicos.createIndex('profissaoId', 'profissaoId', { unique: false });
        servicos.createIndex('categoria',   'categoria',   { unique: false });
      }

      const orcamentos = store('orcamentos', 'id', true);
      if (orcamentos) {
        orcamentos.createIndex('clienteId',  'clienteId',  { unique: false });
        orcamentos.createIndex('profissaoId','profissaoId',{ unique: false });
        orcamentos.createIndex('status',     'status',     { unique: false });
        orcamentos.createIndex('data',       'data',       { unique: false });
      }

      const fotos = store('fotos', 'id', true);
      if (fotos) {
        fotos.createIndex('orcamentoId', 'orcamentoId', { unique: false });
      }

      store('config', 'chave', false);

      const caixa = store('caixa', 'id', true);
      if (caixa) {
        caixa.createIndex('data',        'data',        { unique: false });
        caixa.createIndex('orcamentoId', 'orcamentoId', { unique: false });
      }
    };

    req.onsuccess = (e) => {
      _db = e.target.result;
      _db.onversionchange = () => { _db.close(); _db = null; };
      resolve(_db);
    };

    req.onerror = () => reject(req.error);
  });
}

// ── helpers ──────────────────────────────────────────────────

function tx(stores, mode = 'readonly') {
  return _db.transaction(stores, mode);
}

function promisify(req) {
  return new Promise((res, rej) => {
    req.onsuccess = () => res(req.result);
    req.onerror  = () => rej(req.error);
  });
}

// ── CRUD genérico ────────────────────────────────────────────

export async function getAll(store, indexName, query) {
  await openDB();
  const t  = tx(store);
  const os = t.objectStore(store);
  const source = indexName ? os.index(indexName) : os;
  return promisify(query !== undefined ? source.getAll(query) : source.getAll());
}

export async function getById(store, id) {
  await openDB();
  return promisify(tx(store).objectStore(store).get(id));
}

export async function add(store, data) {
  await openDB();
  return promisify(tx(store, 'readwrite').objectStore(store).add(data));
}

export async function put(store, data) {
  await openDB();
  return promisify(tx(store, 'readwrite').objectStore(store).put(data));
}

export async function remove(store, id) {
  await openDB();
  return promisify(tx(store, 'readwrite').objectStore(store).delete(id));
}

export async function count(store) {
  await openDB();
  return promisify(tx(store).objectStore(store).count());
}

// ── config ───────────────────────────────────────────────────

export async function getConfig(chave) {
  await openDB();
  const row = await promisify(tx('config').objectStore('config').get(chave));
  return row ? row.valor : null;
}

export async function setConfig(chave, valor) {
  await openDB();
  return promisify(tx('config', 'readwrite').objectStore('config').put({ chave, valor }));
}

export async function getAllConfig() {
  const rows = await getAll('config');
  const obj  = {};
  rows.forEach(r => { obj[r.chave] = r.valor; });
  return obj;
}

// ── seed inicial ─────────────────────────────────────────────

export async function seedIfEmpty() {
  await openDB();
  const n = await count('profissoes');
  if (n > 0) return;

  const profissoes = [
    { slug: 'eletricista',  nome: 'Eletricista',   icone: 'bi-lightning-charge-fill', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)',         ativo: 1 },
    { slug: 'encanador',    nome: 'Encanador',      icone: 'bi-droplet-fill',          riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em estanqueidade e reparo',                ativo: 1 },
    { slug: 'tecnico-ac',   nome: 'Técnico de AC',  icone: 'bi-wind',                  riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds',            ativo: 1 },
    { slug: 'pedreiro',     nome: 'Pedreiro',       icone: 'bi-bricks',                riscoBase: 1.4, custoFerramental: 800, descricao: 'Alvenaria estrutural, fundações, lajes',        ativo: 1 },
    { slug: 'pintor',       nome: 'Pintor',         icone: 'bi-brush-fill',            riscoBase: 1.0, custoFerramental: 150, descricao: 'Preparação de superfícies, pintura int/ext',    ativo: 1 },
    { slug: 'marceneiro',   nome: 'Marceneiro',     icone: 'bi-tools',                 riscoBase: 1.1, custoFerramental: 600, descricao: 'Móveis sob medida, portas, janelas',            ativo: 1 },
    { slug: 'serralheiro',  nome: 'Serralheiro',    icone: 'bi-gear-fill',             riscoBase: 1.2, custoFerramental: 700, descricao: 'Grades, portões, estruturas metálicas',         ativo: 1 },
  ];

  const ids = {};
  for (const p of profissoes) {
    ids[p.slug] = await add('profissoes', p);
  }

  const servicos = [
    // ELETRICISTA
    { nome: 'Instalação de tomada',              tempoPadrao: 30,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Troca de disjuntor',                tempoPadrao: 45,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico',   tempoPadrao: 60,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: 150.00 },
    { nome: 'Instalação de ventilador de teto',  tempoPadrao: 50,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Instalação de quadro de disjuntores', tempoPadrao: 180, categoria: 'Elétrica',   profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Instalação de iluminação LED',      tempoPadrao: 50,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Aterramento elétrico',              tempoPadrao: 90,  categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    { nome: 'Manutenção de rede elétrica',       tempoPadrao: 120, categoria: 'Elétrica',     profissaoId: ids.eletricista,  precoFixo: null },
    // ENCANADOR
    { nome: 'Desentupimento de pia',             tempoPadrao: 60,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: 120.00 },
    { nome: 'Troca de registro',                 tempoPadrao: 45,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: null },
    { nome: 'Reparo de vazamento',               tempoPadrao: 90,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: null },
    { nome: 'Instalação de torneira',            tempoPadrao: 30,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: 80.00 },
    { nome: 'Troca de caixa acoplada',           tempoPadrao: 60,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: null },
    { nome: 'Desentupimento de vaso',            tempoPadrao: 45,  categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: 100.00 },
    { nome: 'Instalação de caixa d\'água',       tempoPadrao: 120, categoria: 'Hidráulica',   profissaoId: ids.encanador,    precoFixo: null },
    // TÉCNICO AC
    { nome: 'Instalação de split',               tempoPadrao: 180, categoria: 'Climatização', profissaoId: ids['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Limpeza de ar condicionado',        tempoPadrao: 90,  categoria: 'Climatização', profissaoId: ids['tecnico-ac'], precoFixo: 200.00 },
    { nome: 'Recarga de gás refrigerante',       tempoPadrao: 60,  categoria: 'Climatização', profissaoId: ids['tecnico-ac'], precoFixo: 300.00 },
    { nome: 'Manutenção preventiva AC',          tempoPadrao: 120, categoria: 'Climatização', profissaoId: ids['tecnico-ac'], precoFixo: null },
    { nome: 'Diagnóstico de falhas AC',          tempoPadrao: 60,  categoria: 'Climatização', profissaoId: ids['tecnico-ac'], precoFixo: null },
    // PEDREIRO
    { nome: 'Levantamento de alvenaria',         tempoPadrao: 60,  categoria: 'Alvenaria',    profissaoId: ids.pedreiro,     precoFixo: null },
    { nome: 'Concretagem de laje',               tempoPadrao: 300, categoria: 'Laje',         profissaoId: ids.pedreiro,     precoFixo: null },
    { nome: 'Contrapiso',                        tempoPadrao: 120, categoria: 'Piso',         profissaoId: ids.pedreiro,     precoFixo: null },
    { nome: 'Reboco (massa grossa)',              tempoPadrao: 90,  categoria: 'Revestimento', profissaoId: ids.pedreiro,     precoFixo: null },
    { nome: 'Demolição de parede',               tempoPadrao: 180, categoria: 'Demolição',    profissaoId: ids.pedreiro,     precoFixo: 500.00 },
    { nome: 'Assentamento de cerâmica',          tempoPadrao: 60,  categoria: 'Revestimento', profissaoId: ids.pedreiro,     precoFixo: null },
    // PINTOR
    { nome: 'Lixamento de parede',               tempoPadrao: 60,  categoria: 'Preparação',   profissaoId: ids.pintor,       precoFixo: null },
    { nome: 'Aplicação de massa corrida',        tempoPadrao: 90,  categoria: 'Preparação',   profissaoId: ids.pintor,       precoFixo: null },
    { nome: 'Pintura interna (látex)',           tempoPadrao: 120, categoria: 'Pintura',      profissaoId: ids.pintor,       precoFixo: null },
    { nome: 'Pintura externa (acrílica)',        tempoPadrao: 180, categoria: 'Pintura',      profissaoId: ids.pintor,       precoFixo: null },
    { nome: 'Textura grafiato',                  tempoPadrao: 90,  categoria: 'Textura',      profissaoId: ids.pintor,       precoFixo: null },
    // MARCENEIRO
    { nome: 'Instalação de porta',               tempoPadrao: 90,  categoria: 'Portas',       profissaoId: ids.marceneiro,   precoFixo: null },
    { nome: 'Montagem de móvel planejado',       tempoPadrao: 240, categoria: 'Móveis',       profissaoId: ids.marceneiro,   precoFixo: null },
    { nome: 'Reparo em móvel',                   tempoPadrao: 60,  categoria: 'Reparos',      profissaoId: ids.marceneiro,   precoFixo: null },
    // SERRALHEIRO
    { nome: 'Instalação de grade',               tempoPadrao: 120, categoria: 'Grades',       profissaoId: ids.serralheiro,  precoFixo: null },
    { nome: 'Manutenção de portão',              tempoPadrao: 90,  categoria: 'Portões',      profissaoId: ids.serralheiro,  precoFixo: null },
    { nome: 'Soldagem estrutural',               tempoPadrao: 180, categoria: 'Estrutural',   profissaoId: ids.serralheiro,  precoFixo: null },
  ];

  for (const s of servicos) await add('servicos', s);

  await setConfig('metaSalarial',     5000);
  await setConfig('horasTrabalhadas', 160);
  await setConfig('margemReserva',    0.2);
  await setConfig('taxaDeslocamento', 50);
  await setConfig('validadePadrao',   30);
  await setConfig('setupConcluido',   0);
  await setConfig('profissoesAtivas', []);
}
