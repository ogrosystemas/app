import type { MutantesDB } from "./db";
import type { Membro, Pagamento } from "../types";

/**
 * Popula o banco com dados fictícios de teste, apenas se ele estiver vazio.
 * Cenário desenhado para cobrir os 3 casos visuais mais importantes da conferência:
 *
 * 1) "Foice"  -> em dia, pagou todos os meses desde o ingresso.
 * 2) "Sombra" -> pendente apenas no mês atual (ingressou há poucos meses).
 * 3) "Coice"  -> inadimplente acumulado (faltam vários meses, ingressou há mais tempo).
 */
export async function seedDatabase(db: MutantesDB): Promise<void> {
  await db.transaction("rw", db.config, db.membros, db.pagamentos, async () => {
    const configCount = await db.config.count();
    if (configCount === 0) {
      await db.config.add({
        id: 1,
        nomeClube: "Mutantes Moto Clube",
        valorMensalidade: 50.0,
        atualizadoEm: Date.now(),
      });
    }

    const membrosCount = await db.membros.count();
    if (membrosCount > 0) {
      // Banco já populado (uso real do app) — não sobrescreve dados do usuário.
      return;
    }

    const agora = Date.now();
    const hoje = new Date();
    const anoAtual = hoje.getFullYear();
    const mesAtual = hoje.getMonth() + 1; // 1-12

    const membrosSeed: Membro[] = [
      {
        nome: "Carlos Eduardo Ferreira",
        apelido: "Foice",
        dataIngresso: `${anoAtual}-01-10`,
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "Marcos Vinícius Lima",
        apelido: "Sombra",
        dataIngresso: dataMesesAtras(hoje, 3),
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "Roberto Carlos Souza",
        apelido: "Coice",
        dataIngresso: dataMesesAtras(hoje, 6),
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
    ];

    const idsInseridos = (await db.membros.bulkAdd(membrosSeed, { allKeys: true })) as number[];
    const idFoice = idsInseridos[0];
    const idSombra = idsInseridos[1];
    const idCoice = idsInseridos[2];

    if (idFoice === undefined || idSombra === undefined || idCoice === undefined) {
      throw new Error("Falha ao inserir membros fictícios: IDs gerados inválidos.");
    }

    const pagamentosSeed: Pagamento[] = [];

    // Foice: pagou TODOS os meses desde janeiro até o mês atual (inclusive) -> 100% em dia.
    for (const { mes, ano } of competenciasEntre(1, anoAtual, mesAtual, anoAtual)) {
      pagamentosSeed.push(criarPagamento(idFoice, mes, ano, agora));
    }

    // Sombra: ingressou há 3 meses, pagou tudo MENOS o mês atual -> pendente só no mês corrente.
    const competenciasSombra = competenciasDesde(hoje, 3).filter(
      (c) => !(c.mes === mesAtual && c.ano === anoAtual),
    );
    for (const { mes, ano } of competenciasSombra) {
      pagamentosSeed.push(criarPagamento(idSombra, mes, ano, agora));
    }

    // Coice: ingressou há 6 meses, só pagou os 2 primeiros meses -> inadimplente acumulado (4 meses em aberto).
    const competenciasCoice = competenciasDesde(hoje, 6).slice(0, 2);
    for (const { mes, ano } of competenciasCoice) {
      pagamentosSeed.push(criarPagamento(idCoice, mes, ano, agora));
    }

    await db.pagamentos.bulkAdd(pagamentosSeed);
  });
}

function criarPagamento(membroId: number, mes: number, ano: number, agora: number): Pagamento {
  return {
    membroId,
    mes,
    ano,
    valorPago: 50.0,
    dataPagamento: `${ano}-${String(mes).padStart(2, "0")}-05`,
    formaPagamento: "pix",
    criadoEm: agora,
  };
}

/** Retorna a data ISO (YYYY-MM-DD) de N meses atrás em relação a `base`. */
function dataMesesAtras(base: Date, mesesAtras: number): string {
  const d = new Date(base.getFullYear(), base.getMonth() - mesesAtras, 10);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

/** Gera a lista de competências (mes/ano) desde N meses atrás até o mês atual (inclusive). */
function competenciasDesde(base: Date, mesesAtras: number): { mes: number; ano: number }[] {
  const resultado: { mes: number; ano: number }[] = [];
  for (let i = mesesAtras; i >= 0; i--) {
    const d = new Date(base.getFullYear(), base.getMonth() - i, 1);
    resultado.push({ mes: d.getMonth() + 1, ano: d.getFullYear() });
  }
  return resultado;
}

/** Gera a lista de competências entre (mesInicio/anoInicio) e (mesFim/anoFim), inclusive. */
function competenciasEntre(
  mesInicio: number,
  anoInicio: number,
  mesFim: number,
  anoFim: number,
): { mes: number; ano: number }[] {
  const resultado: { mes: number; ano: number }[] = [];
  const inicio = new Date(anoInicio, mesInicio - 1, 1);
  const fim = new Date(anoFim, mesFim - 1, 1);
  const cursor = new Date(inicio);

  while (cursor <= fim) {
    resultado.push({ mes: cursor.getMonth() + 1, ano: cursor.getFullYear() });
    cursor.setMonth(cursor.getMonth() + 1);
  }
  return resultado;
}
