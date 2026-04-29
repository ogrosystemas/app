import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(2).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria',
  orcamentos: '++id, clienteId, data, total, status, itens, fotos, taxaDeslocamento, subtotal',
  config: 'id, chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo'
});

// Seed initial data for professions
db.on('populate', async () => {
  // Default services
  await db.servicos.bulkAdd([
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica' },
    { nome: 'Troca de resistência', tempoPadrao: 45, categoria: 'Hidráulica' },
    { nome: 'Desentupimento', tempoPadrao: 60, categoria: 'Hidráulica' },
    { nome: 'Instalação de chuveiro', tempoPadrao: 40, categoria: 'Elétrica/Hidráulica' },
    { nome: 'Manutenção geral', tempoPadrao: 120, categoria: 'Geral' },
    { nome: 'Pintura de parede', tempoPadrao: 90, categoria: 'Pintura' },
    { nome: 'Reparo de encanamento', tempoPadrao: 50, categoria: 'Hidráulica' },
    { nome: 'Instalação de ventilador', tempoPadrao: 35, categoria: 'Elétrica' }
  ]);

  // Professions data
  await db.profissoes.bulkAdd([
    {
      slug: 'eletricista',
      nome: 'Eletricista',
      icone: 'Zap',
      riscoBase: 1.2,
      custoFerramental: 300,
      descricao: 'Requer EPIs e normas técnicas (NR10)',
      ativo: true
    },
    {
      slug: 'encanador',
      nome: 'Encanador',
      icone: 'Wrench',
      riscoBase: 1.1,
      custoFerramental: 200,
      descricao: 'Foco em tempo de estanqueidade e reparo',
      ativo: true
    },
    {
      slug: 'tecnico-ac',
      nome: 'Técnico de AC',
      icone: 'Wind',
      riscoBase: 1.2,
      custoFerramental: 500,
      descricao: 'Uso de bombas de vácuo e manifolds',
      ativo: true
    },
    {
      slug: 'pintor-pedreiro',
      nome: 'Pintor/Pedreiro',
      icone: 'Paintbrush',
      riscoBase: 1.0,
      custoFerramental: 150,
      descricao: 'Foco em volume de m² e acabamento',
      ativo: true
    },
    {
      slug: 'marteleteiro',
      nome: 'Marteleteiro',
      icone: 'Hammer',
      riscoBase: 1.4,
      custoFerramental: 800,
      descricao: 'Alto desgaste físico e de equipamento',
      ativo: true
    }
  ]);

  // Default config
  await db.config.bulkAdd([
    { id: 1, chave: 'metaSalarial', valor: 5000 },
    { id: 2, chave: 'horasTrabalhadas', valor: 160 },
    { id: 3, chave: 'margemReserva', valor: 0.2 },
    { id: 4, chave: 'taxaDeslocamento', valor: 50 },
    { id: 5, chave: 'profissaoSelecionada', valor: 'eletricista' },
    { id: 6, chave: 'primeiroAcesso', valor: true },
    { id: 7, chave: 'adicionalPericulosidade', valor: 0.15 },
    { id: 8, chave: 'custoManutencaoFerramenta', valor: 300 }
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