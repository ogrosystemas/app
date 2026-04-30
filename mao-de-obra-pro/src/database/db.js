import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(6).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, desconto, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome, validade, dataVencimento',
  config: 'id, chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo',
  caixa: '++id, data, tipo, categoria, descricao, valor, orcamentoId'
});

// Populate initial data
db.on('populate', async () => {
  const profissoesIds = {};

  const profissoesData = [
    { slug: 'eletricista', nome: 'Eletricista', icone: 'Zap', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)', ativo: 1 },
    { slug: 'encanador', nome: 'Encanador', icone: 'Wrench', riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em tempo de estanqueidade e reparo', ativo: 1 },
    { slug: 'tecnico-ac', nome: 'Técnico de AC', icone: 'Wind', riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds', ativo: 1 },
    { slug: 'pedreiro', nome: 'Pedreiro', icone: 'Hammer', riscoBase: 1.4, custoFerramental: 800, descricao: 'Alvenaria estrutural, fundações, lajes, demolição pesada', ativo: 1 },
    { slug: 'pintor', nome: 'Pintor', icone: 'Paintbrush', riscoBase: 1.0, custoFerramental: 150, descricao: 'Preparação de superfícies, pintura interna e externa, texturas', ativo: 1 }
  ];

  for (const prof of profissoesData) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  await db.servicos.bulkAdd([
    // ELETRICISTA
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Manutenção de rede elétrica', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    // ENCANADOR
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Troca de registro', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Reparo de vazamento', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Instalação de torneira', tempoPadrao: 30, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 80.00 },
    // TÉCNICO AC
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Limpeza de ar condicionado', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 200.00 },
    { nome: 'Recarga de gás refrigerante', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 300.00 },
    // PEDREIRO
    { nome: 'Levantamento de alvenaria', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Concretagem de laje', tempoPadrao: 300, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Contrapiso', tempoPadrao: 120, categoria: 'Piso', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Reboco', tempoPadrao: 90, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Demolição de parede', tempoPadrao: 180, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 500.00 },
    { nome: 'Quebra de concreto armado', tempoPadrao: 300, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 800.00 },
    // PINTOR
    { nome: 'Lixamento de parede', tempoPadrao: 60, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Aplicação de massa corrida', tempoPadrao: 90, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura interna', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura externa', tempoPadrao: 180, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura grafiato', tempoPadrao: 90, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null }
  ]);

  await db.config.bulkAdd([
    { id: 1, chave: 'metaSalarial', valor: 5000 },
    { id: 2, chave: 'horasTrabalhadas', valor: 160 },
    { id: 3, chave: 'margemReserva', valor: 0.2 },
    { id: 4, chave: 'taxaDeslocamento', valor: 50 },
    { id: 5, chave: 'profissaoSelecionada', valor: 'eletricista' },
    { id: 6, chave: 'primeiroAcesso', valor: 0 },
    { id: 7, chave: 'adicionalPericulosidade', valor: 0.15 },
    { id: 8, chave: 'custoManutencaoFerramenta', valor: 300 },
    { id: 9, chave: 'validadePadrao', valor: 30 }
  ]);
});

db.version(6).upgrade(async (trans) => {
  // Adicionar campo desconto em orcamentos que não tem
  const orcamentosSemDesconto = await trans.table('orcamentos').where('desconto').equals(undefined).count();
  if (orcamentosSemDesconto > 0) {
    await trans.table('orcamentos').toCollection().modify({ desconto: 0 });
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