import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(1).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria',
  orcamentos: '++id, clienteId, data, total, status, itens, fotos',
  config: 'id, chave, valor'
});

// Seed initial data
db.on('populate', async () => {
  // Default services
  await db.servicos.bulkAdd([
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica' },
    { nome: 'Troca de resistência', tempoPadrao: 45, categoria: 'Hidráulica' },
    { nome: 'Desentupimento', tempoPadrao: 60, categoria: 'Hidráulica' },
    { nome: 'Instalação de chuveiro', tempoPadrao: 40, categoria: 'Elétrica/Hidráulica' },
    { nome: 'Manutenção geral', tempoPadrao: 120, categoria: 'Geral' }
  ]);

  // Default config
  await db.config.bulkAdd([
    { id: 1, chave: 'metaSalarial', valor: 5000 },
    { id: 2, chave: 'horasTrabalhadas', valor: 160 },
    { id: 3, chave: 'margemReserva', valor: 0.2 },
    { id: 4, chave: 'taxaDeslocamento', valor: 50 }
  ]);
});

// Initialize database
export async function initDatabase() {
  try {
    await db.open();
    const count = await db.config.count();
    if (count === 0) {
      await db.populate();
    }
    console.log('Database initialized successfully');
    return db;
  } catch (error) {
    console.error('Failed to initialize database:', error);
    throw error;
  }
}

export default db;