const DB_NAME = 'AutoCareDB';
const DB_VERSION = 17;
let db = null;

// ==================== FUNÇÃO DE PERSISTÊNCIA ====================
// Esta é a chave para o banco de dados NUNCA MAIS ser apagado.
async function solicitarPersistencia() {
    if (navigator.storage && navigator.storage.persist) {
        try {
            // Verifica se o armazenamento já é persistente
            const jaPersistente = await navigator.storage.persisted();
            if (jaPersistente) {
                console.log('✅ Armazenamento já é persistente. Seus dados estão seguros!');
                return true;
            }
            
            // Solicita ao navegador que torne o armazenamento persistente
            const foiConcedido = await navigator.storage.persist();
            if (foiConcedido) {
                console.log('🔒 Persistência concedida! O banco de dados não será mais apagado automaticamente.');
                return true;
            } else {
                console.warn('⚠️ Persistência negada. Os dados podem ser apagados se o espaço acabar.');
                return false;
            }
        } catch (e) {
            console.error('Erro ao solicitar persistência:', e);
            return false;
        }
    } else {
        console.warn('⚠️ API de persistência não suportada. Faça backups regulares.');
    }
    return false;
}

// ==================== ABERTURA DO BANCO ====================
async function abrirDB() {
    // Garante a persistência ANTES de abrir o banco. Esta é a chamada que faltava.
    await solicitarPersistencia();

    if (db) return db;

    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = (event) => {
            console.error('❌ Erro ao abrir banco:', event.target.error);
            reject(event.target.error);
        };

        request.onsuccess = (event) => {
            db = event.target.result;
            
            // Previne o fechamento acidental da conexão e reabre se necessário
            db.onclose = () => {
                console.log('🔄 Conexão com o banco fechada. Tentando reabrir...');
                db = null;
            };
            
            console.log('✅ Banco de dados aberto com sucesso!');
            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            console.log('🔄 Criando/atualizando estrutura do banco de dados...');
            
            // Tabela: Clientes
            if (!db.objectStoreNames.contains('clientes')) {
                const clienteStore = db.createObjectStore('clientes', { keyPath: 'id', autoIncrement: true });
                clienteStore.createIndex('telefone', 'telefone', { unique: false });
                clienteStore.createIndex('nome', 'nome', { unique: false });
            }
            
            // Tabela: Veículos
            if (!db.objectStoreNames.contains('veiculos')) {
                const veiculoStore = db.createObjectStore('veiculos', { keyPath: 'id', autoIncrement: true });
                veiculoStore.createIndex('clienteId', 'clienteId', { unique: false });
                veiculoStore.createIndex('tipo', 'tipo', { unique: false });
            }
            
            // Tabela: Serviços (OS e Orçamentos)
            if (!db.objectStoreNames.contains('servicos')) {
                const servicoStore = db.createObjectStore('servicos', { keyPath: 'id', autoIncrement: true });
                servicoStore.createIndex('status', 'status', { unique: false });
                servicoStore.createIndex('clienteId', 'clienteId', { unique: false });
                servicoStore.createIndex('veiculoId', 'veiculoId', { unique: false });
                servicoStore.createIndex('dataCriacao', 'dataCriacao', { unique: false });
            }
            
            // Tabela: Caixa
            if (!db.objectStoreNames.contains('caixa')) {
                const caixaStore = db.createObjectStore('caixa', { keyPath: 'id', autoIncrement: true });
                caixaStore.createIndex('data', 'data', { unique: false });
                caixaStore.createIndex('tipo', 'tipo', { unique: false });
            }
            
            // Tabela: Lembretes
            if (!db.objectStoreNames.contains('lembretes')) {
                const lembreteStore = db.createObjectStore('lembretes', { keyPath: 'id', autoIncrement: true });
                lembreteStore.createIndex('status', 'status', { unique: false });
                lembreteStore.createIndex('osId', 'osId', { unique: false });
                lembreteStore.createIndex('clienteId', 'clienteId', { unique: false });
                lembreteStore.createIndex('dataHora', 'dataHora', { unique: false });
            }
        };
    });
}

// ==================== FUNÇÕES CRUD ====================
async function salvar(storeName, dados) {
    await abrirDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const request = store.add(dados);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function listar(storeName) {
    await abrirDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

async function buscarPorId(storeName, id) {
    await abrirDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readonly');
        const store = tx.objectStore(storeName);
        const request = store.get(Number(id));
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function atualizar(storeName, dados) {
    await abrirDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const request = store.put(dados);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function deletar(storeName, id) {
    await abrirDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);
        const request = store.delete(Number(id));
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

async function listarVeiculosPorCliente(clienteId) {
    const veiculos = await listar('veiculos');
    return veiculos.filter(v => v.clienteId === clienteId);
}
