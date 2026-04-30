import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

db.version(16).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, desconto, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome, validade, dataVencimento',
  config: 'chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo',
  caixa: '++id, data, tipo, categoria, descricao, valor, orcamentoId'
});

// Upgrade para garantir que a configuração inicial exista
db.version(16).upgrade(async (trans) => {
  const configTable = trans.table('config');
  const setupExists = await configTable.get('setupConcluido');
  if (!setupExists) {
    await configTable.add({ chave: 'setupConcluido', valor: 0 });
  }
  const metaExists = await configTable.get('metaSalarial');
  if (!metaExists) {
    await configTable.bulkAdd([
      { chave: 'metaSalarial', valor: 5000 },
      { chave: 'horasTrabalhadas', valor: 160 },
      { chave: 'margemReserva', valor: 0.2 },
      { chave: 'taxaDeslocamento', valor: 50 },
      { chave: 'profissaoSelecionada', valor: 'eletricista' }
    ]);
  }
});

// Populate apenas se banco vazio (primeira execução)
db.on('populate', async () => {
  const profissoesIds = {};
  const profissoesData = [
    { slug: 'eletricista', nome: 'Eletricista', icone: 'Zap', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)', ativo: 1 },
    { slug: 'encanador', nome: 'Encanador', icone: 'Wrench', riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em tempo de estanqueidade e reparo', ativo: 1 },
    { slug: 'tecnico-ac', nome: 'Técnico de AC', icone: 'Wind', riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds', ativo: 1 },
    { slug: 'pedreiro', nome: 'Pedreiro', icone: 'Hammer', riscoBase: 1.4, custoFerramental: 800, descricao: 'Alvenaria estrutural, fundações, lajes, demolição pesada', ativo: 1 },
    { slug: 'pintor', nome: 'Pintor', icone: 'Paintbrush', riscoBase: 1.0, custoFerramental: 150, descricao: 'Preparação de superfícies, pintura interna/externa, texturas', ativo: 1 }
  ];
  for (const prof of profissoesData) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  const servicosData = [
    // ELETRICISTA
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Instalação de ventilador', tempoPadrao: 40, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Manutenção de rede elétrica', tempoPadrao: 120, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de quadro de disjuntores', tempoPadrao: 180, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de iluminação LED', tempoPadrao: 50, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Aterramento elétrico', tempoPadrao: 90, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    // ENCANADOR
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Troca de registro', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Reparo de vazamento', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Instalação de torneira', tempoPadrao: 30, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 80.00 },
    { nome: 'Troca de caixa acoplada', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Desentupimento de vaso sanitário', tempoPadrao: 45, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 100.00 },
    { nome: 'Instalação de chuveiro a gás', tempoPadrao: 90, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    { nome: 'Manutenção de caixa d\'água', tempoPadrao: 120, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: null },
    // TÉCNICO AC
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Limpeza de ar condicionado', tempoPadrao: 90, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 200.00 },
    { nome: 'Recarga de gás refrigerante', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 300.00 },
    { nome: 'Manutenção preventiva AC', tempoPadrao: 120, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Instalação de ar condicionado janela', tempoPadrao: 120, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    { nome: 'Diagnóstico de falhas AC', tempoPadrao: 60, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: null },
    // PEDREIRO (completo)
    { nome: 'Levantamento de alvenaria', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Assentamento de tijolos/blocos', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Concretagem de laje', tempoPadrao: 300, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Contrapiso', tempoPadrao: 120, categoria: 'Piso', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Chapisco', tempoPadrao: 60, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Reboco (massa grossa)', tempoPadrao: 90, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Demolição de parede', tempoPadrao: 180, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 500.00 },
    { nome: 'Quebra de concreto armado', tempoPadrao: 300, categoria: 'Demolição', profissaoId: profissoesIds.pedreiro, precoFixo: 800.00 },
    { nome: 'Remoção de entulho', tempoPadrao: 120, categoria: 'Limpeza', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Abertura de valas para fundação', tempoPadrao: 300, categoria: 'Fundação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Perfuração para sondagem', tempoPadrao: 300, categoria: 'Perfuração', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Escavação manual', tempoPadrao: 480, categoria: 'Escavação', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Vergas e contra-vergas', tempoPadrao: 90, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Cinta de amarração', tempoPadrao: 180, categoria: 'Concreto', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Laje pré-moldada', tempoPadrao: 240, categoria: 'Laje', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Assentamento de cerâmica', tempoPadrao: 60, categoria: 'Revestimento', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    // PINTOR
    { nome: 'Lixamento de parede', tempoPadrao: 60, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Aplicação de massa corrida', tempoPadrao: 90, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Selador de parede', tempoPadrao: 45, categoria: 'Preparação', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura interna (látex/acrílica)', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura externa (látex/acrílica)', tempoPadrao: 180, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Pintura esmalte (madeira/metal)', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura grafiato', tempoPadrao: 90, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Textura rolo texturizado', tempoPadrao: 60, categoria: 'Textura', profissaoId: profissoesIds.pintor, precoFixo: null },
    { nome: 'Aplicação de verniz em madeira', tempoPadrao: 90, categoria: 'Acabamento', profissaoId: profissoesIds.pintor, precoFixo: null }
  ];
  for (const s of servicosData) {
    await db.servicos.add(s);
  }

  await db.config.bulkAdd([
    { chave: 'metaSalarial', valor: 5000 },
    { chave: 'horasTrabalhadas', valor: 160 },
    { chave: 'margemReserva', valor: 0.2 },
    { chave: 'taxaDeslocamento', valor: 50 },
    { chave: 'profissaoSelecionada', valor: 'eletricista' },
    { chave: 'setupConcluido', valor: 0 }
  ]);
});

export async function initDatabase() {
  try {
    await db.open();
    console.log('Database opened successfully');
    return db;
  } catch (error) {
    console.error('Failed to open database:', error);
    throw error;
  }
}

export default db;