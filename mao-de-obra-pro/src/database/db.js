import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(5).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome, validade, dataVencimento',
  config: 'id, chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo',
  caixa: '++id, data, tipo, categoria, descricao, valor, orcamentoId'
});

// Populate initial data
db.on('populate', async () => {
  const profissoesIds = {};

  const profissoesData = [
    { slug: 'eletricista', nome: 'Eletricista', icone: 'Zap', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)', ativo: true },
    { slug: 'encanador', nome: 'Encanador', icone: 'Wrench', riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em tempo de estanqueidade e reparo', ativo: true },
    { slug: 'tecnico-ac', nome: 'Técnico de AC', icone: 'Wind', riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds', ativo: true },
    { slug: 'pedreiro', nome: 'Pedreiro', icone: 'Hammer', riscoBase: 1.4, custoFerramental: 800, descricao: 'Alvenaria estrutural, fundações, lajes, demolição pesada', ativo: true },
    { slug: 'pintor', nome: 'Pintor', icone: 'Paintbrush', riscoBase: 1.0, custoFerramental: 150, descricao: 'Preparação de superfícies, pintura interna e externa, texturas', ativo: true }
  ];

  for (const prof of profissoesData) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  await db.servicos.bulkAdd([
    // ========== ELETRICISTA ==========
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Instalação de ventilador', tempoPadrao: 40, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Manutenção de rede elétrica', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de quadro de disjuntores', tempoPadrao: 180, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de iluminação LED', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Aterramento elétrico', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de sensor de presença', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de interruptor three-way', tempoPadrao: 50, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },

    // ========== ENCANADOR ==========
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Troca de registro', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Reparo de vazamento', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Instalação de torneira', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 80.00 },
    { nome: 'Troca de caixa acoplada', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Desentupimento de vaso sanitário', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 100.00 },
    { nome: 'Instalação de chuveiro a gás', tempoPadrao: 120, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Manutenção de caixa d\'água', tempoPadrao: 120, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Instalação de bomba d\'água', tempoPadrao: 150, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Limpeza de caixa d\'água', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },

    // ========== TÉCNICO DE AR CONDICIONADO ==========
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 240, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Limpeza de ar condicionado', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 200.00 },
    { nome: 'Recarga de gás refrigerante', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 300.00 },
    { nome: 'Manutenção preventiva AC', tempoPadrao: 120, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Instalação de ar condicionado janela', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Diagnóstico de falhas AC', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Instalação de sistema multi-split', tempoPadrao: 360, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Desinfecção de dutos', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },

    // ========== PEDREIRO (inclui serviços pesados e marteleteiro) ==========
    // Fundações e estruturas
    { nome: 'Abertura de valas para fundação', tempoPadrao: 300, categoria: 'Fundação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Concretagem de vigas baldrames', tempoPadrao: 240, categoria: 'Concreto', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Confecção de formas para concreto', tempoPadrao: 180, categoria: 'Madeira', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Lançamento de concreto', tempoPadrao: 120, categoria: 'Concreto', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Sapatas de concreto', tempoPadrao: 240, categoria: 'Fundação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Estacas escavadas', tempoPadrao: 360, categoria: 'Fundação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Radier (laje de fundação)', tempoPadrao: 480, categoria: 'Fundação', profissaoId: profissoesIds.pedreiro, precoFixo: null },

    // Alvenaria
    { nome: 'Levantamento de alvenaria', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Assentamento de tijolos/blocos', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Eleveção de paredes', tempoPadrao: 120, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Vergas e contra-vergas', tempoPadrao: 90, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Cinta de amarração', tempoPadrao: 180, categoria: 'Concreto', profissaoId: profissoesIds.pedreiro, precoFixo: null },

    // Lajes e pisos
    { nome: 'Concretagem de laje', tempoPadrao: 300, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Contrapiso', tempoPadrao: 120, categoria: 'Piso', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Regularização de piso', tempoPadrao: 90, categoria: 'Piso', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Assentamento de cerâmica', tempoPadrao: 60, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Laje pré-moldada', tempoPadrao: 240, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Laje nervurada', tempoPadrao: 480, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },

    // Revestimento bruto
    { nome: 'Chapisco', tempoPadrao: 60, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Emboço (massa grossa)', tempoPadrao: 90, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Reboco (massa fina)', tempoPadrao: 90, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },

    // Demolição e serviços pesados (Marteleteiro)
    { nome: 'Demolição de parede simples', tempoPadrao: 180, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 500.00 },
    { nome: 'Demolição de parede estrutural', tempoPadrao: 360, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Demolição de laje', tempoPadrao: 480, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Quebra de concreto armado', tempoPadrao: 300, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 800.00 },
    { nome: 'Demolição de viga/pilar', tempoPadrao: 360, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Remoção de estrutura metálica', tempoPadrao: 240, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Quebra de piso industrial', tempoPadrao: 240, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Perfuração para sondagem', tempoPadrao: 300, categoria: 'Perfuração', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Escavação manual profunda', tempoPadrao: 480, categoria: 'Escavação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Remoção de entulho', tempoPadrao: 120, categoria: 'Limpeza', profissaoId: profissoesIds.pedreiro, precoFixo: null },

    // ========== PINTOR ==========
    { nome: 'Lixamento de parede', tempoPadrao: 60, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Aplicação de massa corrida', tempoPadrao: 90, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Lixamento de massa', tempoPadrao: 60, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Selador de parede', tempoPadrao: 45, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura interna (látex/ acrílica)', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura externa (látex acrílica)', tempoPadrao: 180, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura esmalte (madeira/metal)', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura epóxi (pisos)', tempoPadrao: 180, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura grafiato', tempoPadrao: 90, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura rolo texturizado', tempoPadrao: 60, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura projetada', tempoPadrao: 120, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Aplicação de verniz em madeira', tempoPadrao: 90, categoria: 'Acabamento', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura de grades e portões', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura de forro de gesso', tempoPadrao: 90, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null }
  ]);

  await db.config.bulkAdd([
    { id: 1, chave: 'metaSalarial', valor: 5000 },
    { id: 2, chave: 'horasTrabalhadas', valor: 160 },
    { id: 3, chave: 'margemReserva', valor: 0.2 },
    { id: 4, chave: 'taxaDeslocamento', valor: 50 },
    { id: 5, chave: 'profissaoSelecionada', valor: 'eletricista' },
    { id: 6, chave: 'primeiroAcesso', valor: true },
    { id: 7, chave: 'adicionalPericulosidade', valor: 0.15 },
    { id: 8, chave: 'custoManutencaoFerramenta', valor: 300 },
    { id: 9, chave: 'validadePadrao', valor: 30 }
  ]);
});

db.version(5).upgrade(async (trans) => {
  // Migração: remover profissão marteleteiro antiga e unificar serviços
  const marteleteiroVelho = await trans.table('profissoes').where('slug').equals('marteleteiro').first();
  const pedreiroNovo = await trans.table('profissoes').where('slug').equals('pedreiro').first();

  if (marteleteiroVelho && pedreiroNovo) {
    // Transferir serviços do marteleteiro para pedreiro
    await trans.table('servicos')
      .where('profissaoId')
      .equals(marteleteiroVelho.id)
      .modify({ profissaoId: pedreiroNovo.id });

    // Marcar marteleteiro como inativo
    await trans.table('profissoes').update(marteleteiroVelho.id, { ativo: false });
  }

  // Remover profissão pintor-pedreiro antiga
  const pintorPedreiroVelho = await trans.table('profissoes').where('slug').equals('pintor-pedreiro').first();
  if (pintorPedreiroVelho) {
    await trans.table('profissoes').update(pintorPedreiroVelho.id, { ativo: false });
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