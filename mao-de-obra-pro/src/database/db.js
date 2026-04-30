import Dexie from 'dexie';

export const db = new Dexie('MaoDeObraPro');

// Versão 9: store config com chave como primary key
db.version(9).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, desconto, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome, validade, dataVencimento',
  config: 'chave, valor',
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo',
  caixa: '++id, data, tipo, categoria, descricao, valor, orcamentoId'
});

// Upgrade para garantir dados corretos e limpeza
db.version(9).upgrade(async (trans) => {
  try {
    const configTable = trans.table('config');
    await configTable.clear();
    await configTable.bulkAdd([
      { chave: 'metaSalarial', valor: 5000 },
      { chave: 'horasTrabalhadas', valor: 160 },
      { chave: 'margemReserva', valor: 0.2 },
      { chave: 'taxaDeslocamento', valor: 50 },
      { chave: 'profissaoSelecionada', valor: 'eletricista' },
      { chave: 'setupConcluido', valor: 0 },
      { chave: 'comprou', valor: 0 }
    ]);
    console.log('Upgrade v9: config recriada');
  } catch (err) {
    console.error('Erro no upgrade:', err);
  }
});

db.on('populate', async () => {
  console.log('Populando banco...');
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
  await db.servicos.bulkAdd([
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Levantamento de alvenaria', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Pintura interna', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null }
  ]);
  await db.config.bulkAdd([
    { chave: 'metaSalarial', valor: 5000 },
    { chave: 'horasTrabalhadas', valor: 160 },
    { chave: 'margemReserva', valor: 0.2 },
    { chave: 'taxaDeslocamento', valor: 50 },
    { chave: 'profissaoSelecionada', valor: 'eletricista' },
    { chave: 'setupConcluido', valor: 0 },
    { chave: 'comprou', valor: 0 }
  ]);
});

export async function initDatabase() {
  try {
    await db.open();
    console.log('Banco aberto com sucesso');
    return db;
  } catch (error) {
    console.error('Erro ao abrir banco:', error);
    if (error.name === 'DataError' || error.name === 'VersionError') {
      console.warn('Tentando deletar banco e recriar...');
      await db.delete();
      await db.open();
      console.log('Banco recriado com sucesso');
      return db;
    }
    throw error;
  }
}

export default db;  // <-- garante o export default