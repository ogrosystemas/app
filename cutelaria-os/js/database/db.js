export const db = new Dexie(
  'CutelariaOS'
);

db.version(6).stores({

  clientes:
    `
      ++id,
      nome,
      telefone,
      email,
      instagram,
      cidade,
      observacoes,
      createdAt
    `,

  pedidos:
    `
      ++id,
      clienteId,
      composicaoId,

      titulo,

      status,

      valor,
      entrada,
      restante,

      prioridade,

      entregaPrevista,

      progresso,

      observacoes,

      createdAt
    `,

  timeline:
    `
      ++id,
      pedidoId,
      etapa,
      descricao,
      foto,
      concluido,
      createdAt
    `,

  materiais:
    `
      ++id,
      nome,
      categoria,
      valor,
      unidade,
      estoqueAtual,
      estoqueMinimo,
      createdAt
    `,

  equipamentos:
    `
      ++id,
      nome,
      valorCompra,
      vidaUtil,
      horasMes,
      custoHora
    `,

  composicoes:
    `
      ++id,
      nome,
      categoria,

      tipoFaca,
      tipoAco,
      hrc,
      espessura,
      comprimento,
      peso,
      acabamento,
      desbaste,
      tipoCabo,
      possuiBainha,
      observacoes,

      fotoCapa,

      custoMateriais,
      custoEtapas,
      custoTotal,
      margemLucro,
      valorFinal,
      createdAt
    `,

  composicaoItens:
    `
      ++id,
      composicaoId,
      materialId,
      nome,
      quantidade,
      valorUnitario,
      subtotal
    `,

  etapas:
    `
      ++id,
      composicaoId,
      nome,
      horas,
      valorHora,
      custoEquipamentos,
      custoEnergia,
      custoAbrasivos,
      custoTotal
    `,

  fotos:
    `
      ++id,
      composicaoId,
      imagem,
      createdAt
    `,

  financeiro:
    `
      ++id,
      tipo,
      categoria,
      descricao,
      valor,
      vencimento,
      status,
      createdAt
    `

});