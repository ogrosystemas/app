import type { MutantesDB } from "./db";
import type { Membro, Pagamento } from "../types";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";

/**
 * Popula o banco com dados fictícios de teste, apenas se ele estiver vazio.
 *
 * Cenário desenhado para refletir a regra de negócio real do clube:
 * a cobrança segue o CICLO ANUAL (Janeiro a Dezembro do ano corrente) para todo
 * membro ativo, independentemente de há quanto tempo ele está no clube — a data
 * de ingresso só desloca o início da cobrança no próprio ano em que ele entrou.
 *
 * 1) "Foice"  -> veterano (12+ anos de clube), em dia: pagou Jan até o mês atual do ano corrente.
 * 2) "Sombra" -> ingressou neste mesmo ano, no meio do ano (não deve os meses antes de ingressar).
 * 3) "Coice"  -> veterano inadimplente: deve vários meses do ciclo atual.
 * 4) "Brasa"  -> histórico multi-ano: pagou em dia todos os anos anteriores, mas ficou
 *               devendo Nov/Dez do ANO PASSADO (dívida pontual já "fechada" no passado)
 *               e também está com pendência no ano corrente — cobre o caso de
 *               inadimplência que atravessa a virada do ano sem acumular história fictícia.
 * 5) "Cigano" -> afastado há alguns meses: ficou devendo o mês imediatamente anterior ao
 *               afastamento (dívida que persiste mesmo depois de afastado) e não gera
 *               nenhuma cobrança nova desde então — cobre o caso de afastamento.
 */
export async function seedDatabase(db: MutantesDB): Promise<void> {
  await db.transaction("rw", db.config, db.membros, db.pagamentos, async () => {
    const configCount = await db.config.count();
    if (configCount === 0) {
      await db.config.add({
        id: 1,
        nomeClube: NOME_CLUBE_PADRAO,
        valorMensalidade: VALOR_MENSALIDADE_PADRAO,
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
    const anoAnterior = anoAtual - 1;

    // Mês de ingresso do membro "novo" deste ano: meio do ano corrente, ou o próprio
    // mês atual caso estejamos em Jan/Fev (evita gerar uma data de ingresso no futuro).
    const mesIngressoNovato = Math.max(1, Math.min(mesAtual, Math.ceil(mesAtual / 2)));

    // Competência de afastamento do "Cigano": 2 meses atrás em relação a hoje (sempre um
    // mês plenamente no passado, nunca o mês atual nem o futuro — evita casos de borda).
    const dataAfastamento = new Date(hoje.getFullYear(), hoje.getMonth() - 2, 1);
    const mesAfastamentoCigano = dataAfastamento.getMonth() + 1;
    const anoAfastamentoCigano = dataAfastamento.getFullYear();
    const competenciaAfastamentoCigano = `${anoAfastamentoCigano}-${String(mesAfastamentoCigano).padStart(2, "0")}`;

    const membrosSeed: Membro[] = [
      {
        nome: "Carlos Eduardo Ferreira",
        apelido: "Foice",
        dataIngresso: "2014-03-10", // veterano de longa data — não afeta a cobrança do ciclo atual
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "Marcos Vinícius Lima",
        apelido: "Sombra",
        dataIngresso: `${anoAtual}-${String(mesIngressoNovato).padStart(2, "0")}-15`,
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "Roberto Carlos Souza",
        apelido: "Coice",
        dataIngresso: "2018-07-22", // veterano, mas inadimplente no ciclo atual
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "Anderson Pereira da Silva",
        apelido: "Brasa",
        dataIngresso: "2020-01-05", // veterano — usado para o caso de histórico multi-ano
        status: "ativo",
        criadoEm: agora,
        atualizadoEm: agora,
      },
      {
        nome: "José Carlos Mendes",
        apelido: "Cigano",
        dataIngresso: "2019-05-12", // veterano, afastado há alguns meses
        status: "afastado",
        competenciaAfastamento: competenciaAfastamentoCigano,
        criadoEm: agora,
        atualizadoEm: agora,
      },
    ];

    const idsInseridos = (await db.membros.bulkAdd(membrosSeed, { allKeys: true })) as number[];
    const idFoice = idsInseridos[0];
    const idSombra = idsInseridos[1];
    const idCoice = idsInseridos[2];
    const idBrasa = idsInseridos[3];
    const idCigano = idsInseridos[4];

    if (
      idFoice === undefined ||
      idSombra === undefined ||
      idCoice === undefined ||
      idBrasa === undefined ||
      idCigano === undefined
    ) {
      throw new Error("Falha ao inserir membros fictícios: IDs gerados inválidos.");
    }

    const pagamentosSeed: Pagamento[] = [];

    // Foice: pagou TODOS os meses do ciclo atual, de Janeiro até o mês atual -> 100% em dia,
    // mesmo tendo 12 anos de clube (a cobrança não retroage aos anos anteriores).
    for (const { mes, ano } of competenciasEntre(1, anoAtual, mesAtual, anoAtual)) {
      pagamentosSeed.push(criarPagamento(idFoice, mes, ano, agora));
    }

    // Sombra: ingressou no meio DESTE ano -> pagou só a partir do mês real de ingresso até o
    // mês anterior ao atual; o mês atual fica pendente (caso simples de 1 mês em aberto).
    if (mesIngressoNovato < mesAtual) {
      for (const { mes, ano } of competenciasEntre(mesIngressoNovato, anoAtual, mesAtual - 1, anoAtual)) {
        pagamentosSeed.push(criarPagamento(idSombra, mes, ano, agora));
      }
    }
    // Se mesIngressoNovato === mesAtual, não há pagamento nenhum ainda -> também fica pendente (1 mês).

    // Coice: veterano, mas só pagou Janeiro e Fevereiro do ciclo atual -> inadimplente acumulado
    // nos meses seguintes até o mês atual.
    for (const { mes, ano } of competenciasEntre(1, anoAtual, Math.min(2, mesAtual), anoAtual)) {
      pagamentosSeed.push(criarPagamento(idCoice, mes, ano, agora));
    }

    // Brasa: histórico multi-ano. Pagou em dia todos os anos anteriores ao ano passado
    // (2020 até anoAnterior-1, completos). No ano ANTERIOR, pagou só até Outubro (ficou
    // devendo Nov/Dez daquele ano — uma dívida pontual "fechada no passado"). Neste ano
    // (ciclo atual), pagou Jan-Fev e está pendente do restante até o mês atual.
    for (const { mes, ano } of competenciasEntre(1, 2020, 12, anoAnterior - 1)) {
      pagamentosSeed.push(criarPagamento(idBrasa, mes, ano, agora));
    }
    for (const { mes, ano } of competenciasEntre(1, anoAnterior, 10, anoAnterior)) {
      pagamentosSeed.push(criarPagamento(idBrasa, mes, ano, agora));
    }
    for (const { mes, ano } of competenciasEntre(1, anoAtual, Math.min(2, mesAtual), anoAtual)) {
      pagamentosSeed.push(criarPagamento(idBrasa, mes, ano, agora));
    }

    // Cigano: pagou Janeiro e Fevereiro do ano em que se afastou, mas ficou devendo o mês
    // IMEDIATAMENTE ANTERIOR ao afastamento (uma dívida que persiste mesmo após se afastar —
    // o afastamento não perdoa pendências passadas, só impede novas). Nenhuma competência a
    // partir do mês de afastamento (inclusive) é gerada.
    const mesAnteriorAoAfastamentoCigano = new Date(anoAfastamentoCigano, mesAfastamentoCigano - 2, 1);
    const competenciasPagasAteAfastamento = competenciasEntre(
      1,
      anoAfastamentoCigano,
      mesAnteriorAoAfastamentoCigano.getMonth() + 1,
      mesAnteriorAoAfastamentoCigano.getFullYear(),
    );
    // Remove a última competência (o mês imediatamente anterior ao afastamento) da lista de
    // pagas, propositalmente, para simular a dívida pendente que se mantém após o afastamento.
    const competenciasPagasCigano = competenciasPagasAteAfastamento.slice(0, -1);
    for (const { mes, ano } of competenciasPagasCigano) {
      pagamentosSeed.push(criarPagamento(idCigano, mes, ano, agora));
    }

    await db.pagamentos.bulkAdd(pagamentosSeed);
  });
}

function criarPagamento(membroId: number, mes: number, ano: number, agora: number): Pagamento {
  return {
    membroId,
    mes,
    ano,
    valorPago: VALOR_MENSALIDADE_PADRAO,
    dataPagamento: `${ano}-${String(mes).padStart(2, "0")}-05`,
    formaPagamento: "pix",
    criadoEm: agora,
  };
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
