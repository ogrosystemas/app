import Dexie from 'dexie';

// Definindo o banco de dados
export const db = new Dexie('MaoDeObraPro');

// Versão 9 – schema definitivo
db.version(9).stores({
  clientes: '++id, nome, whatsapp, endereco',
  servicos: '++id, nome, tempoPadrao, categoria, profissaoId, precoFixo',
  orcamentos: '++id, clienteId, data, total, desconto, status, itens, fotos, taxaDeslocamento, subtotal, profissaoId, profissaoNome, validade, dataVencimento',
  config: 'chave, valor',  // chave é a chave primária
  profissoes: '++id, slug, nome, icone, riscoBase, custoFerramental, descricao, ativo',
  caixa: '++id, data, tipo, categoria, descricao, valor, orcamentoId'
});

// Popula apenas se o banco estiver vazio (primeira execução)
db.on('populate', async () => {
  console.log('Populando banco pela primeira vez...');

  // Profissões
  const profissoes = [
    { slug: 'eletricista', nome: 'Eletricista', icone: 'Zap', riscoBase: 1.2, custoFerramental: 300, descricao: 'Requer EPIs e normas técnicas (NR10)', ativo: 1 },
    { slug: 'encanador', nome: 'Encanador', icone: 'Wrench', riscoBase: 1.1, custoFerramental: 200, descricao: 'Foco em tempo de estanqueidade e reparo', ativo: 1 },
    { slug: 'tecnico-ac', nome: 'Técnico de AC', icone: 'Wind', riscoBase: 1.2, custoFerramental: 500, descricao: 'Uso de bombas de vácuo e manifolds', ativo: 1 },
    { slug: 'pedreiro', nome: 'Pedreiro', icone: 'Hammer', riscoBase: 1.4, custoFerramental: 800, descricao: 'Alvenaria estrutural, fundações, lajes, demolição pesada', ativo: 1 },
    { slug: 'pintor', nome: 'Pintor', icone: 'Paintbrush', riscoBase: 1.0, custoFerramental: 150, descricao: 'Preparação de superfícies, pintura interna/externa, texturas', ativo: 1 }
  ];
  const profissoesIds = {};
  for (const prof of profissoes) {
    const id = await db.profissoes.add(prof);
    profissoesIds[prof.slug] = id;
  }

  // Serviços
  await db.servicos.bulkAdd([
    { nome: 'Instalação de tomada', tempoPadrao: 30, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Troca de disjuntor', tempoPadrao: 45, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: null },
    { nome: 'Instalação de chuveiro elétrico', tempoPadrao: 60, categoria: 'Elétrica', profissaoId: profissoesIds.eletricista, precoFixo: 150.00 },
    { nome: 'Desentupimento de pia', tempoPadrao: 60, categoria: 'Hidráulica', profissaoId: profissoesIds.encanador, precoFixo: 120.00 },
    { nome: 'Instalação de ar condicionado split', tempoPadrao: 180, categoria: 'Climatização', profissaoId: profissoesIds['tecnico-ac'], precoFixo: 600.00 },
    { nome: 'Levantamento de alvenaria', tempoPadrao: 60, categoria: 'Alvenaria', profissaoId: profissoesIds.pedreiro, precoFixo: null },
    { nome: 'Pintura interna', tempoPadrao: 120, categoria: 'Pintura', profissaoId: profissoesIds.pintor, precoFixo: null }
  ]);

  // Configurações
  await db.config.bulkAdd([
    { chave: 'metaSalarial', valor: 5000 },
    { chave: 'horasTrabalhadas', valor: 160 },
    { chave: 'margemReserva', valor: 0.2 },
    { chave: 'taxaDeslocamento', valor: 50 },
    { chave: 'profissaoSelecionada', valor: 'eletricista' },
    { chave: 'setupConcluido', valor: 0 },
    { chave: 'primeiroAcesso', valor: 0 },
    { chave: 'adicionalPericulosidade', valor: 0.15 },
    { chave: 'custoManutencaoFerramenta', valor: 300 },
    { chave: 'validadePadrao', valor: 30 }
  ]);
});

// Função de inicialização com tratamento de erro e recriação do banco
export async function initDatabase() {
  try {
    await db.open();
    console.log('Banco aberto com sucesso');
    return db;
  } catch (error) {
    console.error('Erro ao abrir banco:', error);
    // Se for erro de versão ou upgrade, tenta deletar e recriar
    if (error.name === 'UpgradeError' || error.name === 'VersionError') {
      console.warn('Erro de versão, deletando banco e recriando...');
      await db.delete();
      await db.open();
      console.log('Banco recriado com sucesso');
      return db;
    }
    throw error;
  }
}