import type { Competencia, Membro, Pagamento } from "../types";
import {
  compararCompetencias,
  competenciaAtual,
  competenciaDeDataISO,
  formatarCompetencia,
} from "./date.utils";
import { calcularInadimplenciaMembro, membroEstaSujeitoACobranca } from "./status.utils";

export type TipoFiltroRelatorio = "mes" | "periodo" | "ano";

export interface FiltroRelatorio {
  tipo: TipoFiltroRelatorio;
  /** Competência inicial do período a relatar (inclusive). */
  inicio: Competencia;
  /** Competência final do período a relatar (inclusive). */
  fim: Competencia;
}

export interface LinhaRelatorioMembro {
  apelido: string;
  nome: string;
  emDia: boolean;
  mesesDevidos: number;
  afastado: boolean;
}

export interface DadosRelatorio {
  nomeClube: string;
  filtro: FiltroRelatorio;
  tituloPeriodo: string;
  linhas: LinhaRelatorioMembro[];
  totalMembros: number;
  totalEmDia: number;
  totalPendentes: number;
  /** Soma de tudo que foi efetivamente pago (por dataPagamento) dentro do período filtrado. */
  valorArrecadadoNoPeriodo: number;
  geradoEm: string;
}

/** Constrói o filtro de competências a partir de um mês/ano único (relatório mensal). */
export function filtroPorMes(mes: number, ano: number): FiltroRelatorio {
  return { tipo: "mes", inicio: { mes, ano }, fim: { mes, ano } };
}

/** Constrói o filtro de competências a partir de um ano completo (Janeiro-Dezembro). */
export function filtroPorAno(ano: number): FiltroRelatorio {
  return { tipo: "ano", inicio: { mes: 1, ano }, fim: { mes: 12, ano } };
}

/** Constrói o filtro de competências a partir de um intervalo customizado qualquer. */
export function filtroPorPeriodo(inicio: Competencia, fim: Competencia): FiltroRelatorio {
  return { tipo: "periodo", inicio, fim };
}

/** Texto de título legível para o período filtrado, usado no cabeçalho do PDF. */
export function tituloDoFiltro(filtro: FiltroRelatorio): string {
  if (filtro.tipo === "mes") return formatarCompetencia(filtro.inicio);
  if (filtro.tipo === "ano") return `Ano ${filtro.inicio.ano}`;
  return `${formatarCompetencia(filtro.inicio)} a ${formatarCompetencia(filtro.fim)}`;
}

/**
 * Monta os dados completos do relatório para um período filtrado.
 *
 * Status (Em Dia / Pendente + quantidade de meses devidos) é calculado em relação à
 * competência FINAL do filtro, LIMITADA ao mês atual real — ou seja, "está em dia
 * considerando tudo até este período, ou até hoje, o que vier primeiro". Isso evita que
 * um relatório anual gerado no meio do ano (ex: filtro até Dezembro, mas hoje é Junho)
 * acuse meses futuros como "pendentes": eles simplesmente ainda não chegaram, não fazem
 * sentido contar como dívida ainda.
 *
 * "Arrecadado no período" soma apenas pagamentos cuja dataPagamento (data real em que o
 * dinheiro entrou no caixa) cai dentro do intervalo filtrado — mesma regra de "caixa" já
 * usada no dashboard (ver useDashboardResumo.ts), não a competência paga. Esse cálculo usa
 * o filtro original (não limitado), já que não há problema em filtrar um intervalo cujo
 * fim é futuro — simplesmente não haverá pagamentos registrados ali ainda.
 */
export function gerarDadosRelatorio(
  nomeClube: string,
  filtro: FiltroRelatorio,
  membros: Membro[],
  pagamentos: Pagamento[],
  valorMensalidade: number,
): DadosRelatorio {
  const hoje = competenciaAtual();
  const competenciaStatusLimitada =
    compararCompetencias(filtro.fim, hoje) > 0 ? hoje : filtro.fim;

  const pagamentosPorMembro = new Map<string, Pagamento[]>();
  for (const p of pagamentos) {
    const lista = pagamentosPorMembro.get(p.membroId);
    if (lista) lista.push(p);
    else pagamentosPorMembro.set(p.membroId, [p]);
  }

  const linhas: LinhaRelatorioMembro[] = [];
  let totalEmDia = 0;
  let totalPendentes = 0;

  for (const membro of membros) {
    if (membro.id === undefined) continue;
    if (!membroEstaSujeitoACobranca(membro, competenciaStatusLimitada)) continue; // afastado antes do período

    const pagamentosDoMembro = pagamentosPorMembro.get(membro.id) ?? [];
    const resumo = calcularInadimplenciaMembro(
      membro,
      pagamentosDoMembro,
      competenciaStatusLimitada,
      valorMensalidade,
    );
    const emDia = resumo.totalMesesPendentes === 0;

    linhas.push({
      apelido: membro.apelido,
      nome: membro.nome,
      emDia,
      mesesDevidos: resumo.totalMesesPendentes,
      afastado: membro.status === "afastado",
    });

    if (emDia) totalEmDia++;
    else totalPendentes++;
  }

  linhas.sort((a, b) => a.apelido.localeCompare(b.apelido, "pt-BR"));

  let valorArrecadadoNoPeriodo = 0;
  for (const p of pagamentos) {
    const competenciaDoPagamento = competenciaDeDataISO(p.dataPagamento);
    if (
      compararCompetencias(competenciaDoPagamento, filtro.inicio) >= 0 &&
      compararCompetencias(competenciaDoPagamento, filtro.fim) <= 0
    ) {
      valorArrecadadoNoPeriodo += p.valorPago;
    }
  }

  return {
    nomeClube,
    filtro,
    tituloPeriodo: tituloDoFiltro(filtro),
    linhas,
    totalMembros: linhas.length,
    totalEmDia,
    totalPendentes,
    valorArrecadadoNoPeriodo,
    geradoEm: new Date().toISOString(),
  };
}
