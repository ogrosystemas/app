import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(4).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome',
  config: 'id, chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo'
});

// Populate initial data
db.on('populate', async () => {
  const profissoesIds = {};

  const profissoesData = [
    { slug: 'eletricista', nome: 'Eletricista', icone: 'Zap', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)', ativo: true },
    { slug: 'encanador', nome: 'Encanador', icone: 'Wrench', riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em tempo de estanqueidade e reparo', ativo: true },
    { slug: 'tecnico-ac', nome: 'Técnico de AC', icone: 'Wind', riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds', ativo: true },
    { slug: 'pintor-pedreiro', nome: 'Pintor/Pedreiro', icone: 'Paintbrush', riscoBase: 1.0, custoFerramental: 150, descricao: 'Foco em volume de m² e acabamento', ativo: true },
    { slug: 'marteleteiro', nome: 'Marteleteiro', icone: 'Hammer', riscoBase: 1.4, custoFerramental: 800, descricao: 'Alto desgaste físico e de equipamento', ativo: true }
  ];

  for (const prof of profissoesData) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  // Serviços por profissão com preço fixo opcional
  await db.servicos.bulkAdd([
    // Eletricista
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Instalação de ventilador', tempoPadrao: 40, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Manutenção de rede elétrica', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    // Encanador
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Troca de registro', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Reparo de vazamento', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Instalação de torneira', tempoPadrao: 30, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 80.00 },
    // Técnico AC
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Limpeza de ar condicionado', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 200.00 },
    { nome: 'Recarga de gás refrigerante', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 300.00 },
    // Pintor/Pedreiro
    { nome: 'Pintura de parede (m²)', tempoPadrao: 20, categoria: 'Pintura', profissaoId: profissoesIds['pintor-pedreiro'], precoFixo: 25.00 },
    { nome: 'Reboco de parede (m²)', tempoPadrao: 30, categoria: 'Construção', profissaoId: profissoesIds['pintor-pedreiro'], precoFixo: 40.00 },
    { nome: 'Assentamento de azulejo (m²)', tempoPadrao: 45, categoria: 'Construção', profissaoId: profissoesIds['pintor-pedreiro'], precoFixo: 60.00 },
    // Marteleteiro
    { nome: 'Demolição de parede', tempoPadrao: 180, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro, precoFixo: 500.00 },
    { nome: 'Quebra de piso/concreto', tempoPadrao: 120, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro, precoFixo: 400.00 },
    { nome: 'Remoção de estrutura', tempoPadrao: 240, categoria: 'Demolição', profissaoId: profissoesIds.marteleteiro, precoFixo: 800.00 }
  ]);

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

// Migration for existing users
db.version(4).upgrade(async (trans) => {
  const servicosSemPreco = await trans.table('servicos').where('precoFixo').equals(undefined).count();
  if (servicosSemPreco > 0) {
    await trans.table('servicos').toCollection().modify({ precoFixo: null });
  }
});

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