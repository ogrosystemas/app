import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

// Versão 3: Adiciona profissaoId nos serviços
db.version(3).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId',
  orcamentos: '++id, clienteId, data, total, status, itens, fotos, taxaDeslocamento, subtotal',
  config: 'id, chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo'
});

// Populate initial data
db.on('populate', async () => {
  // First, add professions
  const profissoesIds = {};

  const profissoesData = [
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
  ];

  // Add professions and store their IDs
  for (const prof of profissoesData) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  // Services for Eletricista
  await db.servicos.bulkAdd([
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Instalação de ventilador', tempoPadrao: 40, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Manutenção de rede elétrica', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Instalação de quadro de disjuntores', tempoPadrao: 180, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Instalação de iluminação LED', tempoPadrao: 50, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista },
    { nome: 'Aterramento elétrico', tempoPadrao: 90, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista }
  ]);

  // Services for Encanador
  await db.servicos.bulkAdd([
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Troca de registro', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Reparo de vazamento', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Instalação de torneira', tempoPadrao: 30, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Troca de caixa acoplada', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Desentupimento de vaso sanitário', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Instalação de chuveiro a gás', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador },
    { nome: 'Manutenção de caixa d\'água', tempoPadrao: 120, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador }
  ]);

  // Services for Técnico de AC
  await db.servicos.bulkAdd([
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] },
    { nome: 'Limpeza de ar condicionado', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] },
    { nome: 'Recarga de gás refrigerante', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] },
    { nome: 'Manutenção preventiva AC', tempoPadrao: 120, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] },
    { nome: 'Instalação de ar condicionado janela', tempoPadrao: 120, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] },
    { nome: 'Diagnóstico de falhas AC', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'] }
  ]);

  // Services for Pintor/Pedreiro
  await db.servicos.bulkAdd([
    { nome: 'Pintura de parede (m²)', tempoPadrao: 20, categoria: 'Pintura', profissaoId: profissoesIds['pintor-pedreiro'] },
    { nome: 'Reboco de parede (m²)', tempoPadrao: 30, categoria: 'Construção', profissaoId: profissoesIds['pintor-pedreiro'] },
    { nome: 'Assentamento de azulejo (m²)', tempoPadrao: 45, categoria: 'Construção', profissaoId: profissoesIds['pintor-pedreiro'] },
    { nome: 'Peq. reparos estruturais', tempoPadrao: 120, categoria: 'Construção', profissaoId: profissoesIds['pintor-pedreiro'] },
    { nome: 'Acabamento em gesso', tempoPadrao: 60, categoria: 'Acabamento', profissaoId: profissoesIds['pintor-pedreiro'] },
    { nome: 'Texturização de parede', tempoPadrao: 40, categoria: 'Pintura', profissaoId: profissoesIds['pintor-pedreiro'] }
  ]);

  // Services for Marteleteiro
  await db.servicos.bulkAdd([
    { nome: 'Demolição de parede', tempoPadrao: 180, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro },
    { nome: 'Quebra de piso/concreto', tempoPadrao: 120, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro },
    { nome: 'Remoção de estrutura', tempoPadrao: 240, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro },
    { nome: 'Perfuração para sondagem', tempoPadrao: 300, categoria: 'Perfuração', profissaoId: profissoesIds.marteleteiro },
    { nome: 'Escavação manual', tempoPadrao: 240, categoria: 'Escavação', profissaoId: profissoesIds.marteleteiro }
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