import type { Competencia, Membro, Pagamento } from "../types";
import {
  compararCompetencias,
  competenciaAnterior,
  competenciaDeDataISO,
  competenciaDeStringAnoMes,
  gerarIntervaloCompetencias,
} from "./date.utils";

/**
 * Resumo agregado de inadimplência de UM membro até uma competência de referência.
 */
export interface ResumoInadimplenciaMembro {
  membroId: number;

  /** Competências em que o membro deveria ter pago e não há registro de Pagamento. */
  competenciasPendentes: Competencia[];

  /** Quantidade de meses pendentes (= competenciasPendentes.length). */
  totalMesesPendentes: number;

  /** Soma do valor devido (totalMesesPendentes * valorMensalidade). */
  valorTotalDevido: number;

  /** Se a competência de referência (geralmente a selecionada no topo) está paga. */
  competenciaReferenciaPaga: boolean;
}

/**
 * Determina a competência de início do ciclo de cobrança de um membro PARA UM ANO específico.
 *
 * Regra de negócio: o ciclo de cobrança é sempre Janeiro-Dezembro do ano em questão,
 * para todo membro ativo — a data de ingresso não desloca a cobrança para anos
 * anteriores ao de ingresso. A única exceção é o próprio ano de ingresso: se o membro
 * entrou em um mês posterior a Janeiro daquele ano, a cobrança daquele ano específico
 * começa no mês real de ingresso (não retroage a meses em que ele ainda não era membro).
 *
 * Retorna null se o ano informado for anterior ao ano de ingresso (o membro ainda não
 * existia no clube naquele ano — não há ciclo de cobrança a calcular).
 */
function inicioCicloDoAno(competenciaIngresso: Competencia, ano: number): Competencia | null {
  if (competenciaIngresso.ano > ano) return null;

  const inicioJaneiro: Competencia = { mes: 1, ano };
  if (competenciaIngresso.ano === ano && compararCompetencias(competenciaIngresso, inicioJaneiro) > 0) {
    return competenciaIngresso;
  }
  return inicioJaneiro;
}

/**
 * Calcula, para um único membro, todas as competências pendentes dentro do CICLO ANUAL
 * do ano da competência de referência, cruzando com os pagamentos já registrados.
 *
 * Se o membro estiver afastado (`status === "afastado"`) e a competência de afastamento
 * já tiver chegado ou passado em relação à referência, o cálculo "para no tempo": a última
 * competência considerada cobrável é a imediatamente anterior à do afastamento. Competências
 * anteriores ao afastamento que ainda estejam pendentes continuam aparecendo normalmente
 * (a dívida não é perdoada) — apenas nenhuma competência nova é gerada a partir dali.
 *
 * Como o ano de referência define o ciclo, esta função também serve para consultar
 * anos anteriores (histórico multi-ano): basta passar uma competenciaReferencia de
 * um ano passado para obter as pendências daquele ciclo especificamente.
 */
export function calcularInadimplenciaMembro(
  membro: Membro,
  pagamentosDoMembro: Pagamento[],
  competenciaReferencia: Competencia,
  valorMensalidade: number,
): ResumoInadimplenciaMembro {
  const competenciaIngresso = competenciaDeDataISO(membro.dataIngresso);
  const competenciaInicioCiclo = inicioCicloDoAno(competenciaIngresso, competenciaReferencia.ano);

  // Sem início de ciclo válido (ano de referência anterior ao ingresso), ou início de
  // ciclo posterior à própria referência (ex: cadastro com data futura): nada pendente ainda.
  if (
    competenciaInicioCiclo === null ||
    compararCompetencias(competenciaInicioCiclo, competenciaReferencia) > 0
  ) {
    return {
      membroId: membro.id ?? -1,
      competenciasPendentes: [],
      totalMesesPendentes: 0,
      valorTotalDevido: 0,
      competenciaReferenciaPaga: false,
    };
  }

  const competenciaFimEfetivo = limitarPorAfastamento(membro, competenciaReferencia);

  // O afastamento pode "zerar" o intervalo (ex: afastado desde antes do início do ciclo
  // deste ano) — neste caso não há competências esperadas neste ciclo.
  if (competenciaFimEfetivo === null || compararCompetencias(competenciaInicioCiclo, competenciaFimEfetivo) > 0) {
    return {
      membroId: membro.id ?? -1,
      competenciasPendentes: [],
      totalMesesPendentes: 0,
      valorTotalDevido: 0,
      competenciaReferenciaPaga: false,
    };
  }

  const todasCompetenciasEsperadas = gerarIntervaloCompetencias(
    competenciaInicioCiclo,
    competenciaFimEfetivo,
  );

  const pagas = new Set(pagamentosDoMembro.map((p) => chaveCompetencia(p)));

  const competenciasPendentes = todasCompetenciasEsperadas.filter(
    (c) => !pagas.has(chaveCompetencia(c)),
  );

  const competenciaReferenciaPaga = pagas.has(chaveCompetencia(competenciaReferencia));

  return {
    membroId: membro.id ?? -1,
    competenciasPendentes,
    totalMesesPendentes: competenciasPendentes.length,
    valorTotalDevido: competenciasPendentes.length * valorMensalidade,
    competenciaReferenciaPaga,
  };
}

/**
 * Aplica o efeito do afastamento sobre o fim do intervalo de cobrança.
 *
 * Se o membro não está afastado, ou está afastado mas a competência de afastamento
 * ainda não chegou em relação à referência (ex: referência é um mês ANTES do afastamento —
 * caso de consulta de histórico passado), o fim do intervalo é a própria referência.
 *
 * Se já está afastado a partir de uma competência igual ou anterior à referência, o fim
 * do intervalo retrocede para a competência imediatamente anterior à do afastamento.
 */
function limitarPorAfastamento(membro: Membro, competenciaReferencia: Competencia): Competencia | null {
  if (membro.status !== "afastado") return competenciaReferencia;

  const competenciaAfastamento = competenciaDeStringAnoMes(membro.competenciaAfastamento);
  if (competenciaAfastamento === null) return competenciaReferencia;

  if (compararCompetencias(competenciaAfastamento, competenciaReferencia) > 0) {
    // Afastamento é no futuro em relação à referência consultada (ex: consultando um
    // mês anterior ao afastamento) — cobrança normal até a própria referência.
    return competenciaReferencia;
  }

  return competenciaAnterior(competenciaAfastamento);
}

/**
 * Gera todas as competências de cobrança esperadas de um membro, percorrendo CADA ANO
 * desde o ano de ingresso até o ano da competência final (inclusive), respeitando o
 * ciclo anual de cada ano (Janeiro-Dezembro, exceto o ano de ingresso que pode começar
 * mais tarde) E o afastamento, se houver (não gera competências a partir da data em que
 * o membro foi afastado). Usado no histórico multi-ano: mostra exatamente os meses que
 * o membro deveria ter pago em cada ano, sem "inventar" pendência de períodos em que
 * ele não estava sujeito à cobrança.
 */
export function gerarCompetenciasEsperadasHistorico(
  membro: Membro,
  competenciaFinal: Competencia,
): Competencia[] {
  const competenciaIngresso = competenciaDeDataISO(membro.dataIngresso);
  const competenciaFinalEfetiva = limitarPorAfastamento(membro, competenciaFinal);

  if (competenciaFinalEfetiva === null || competenciaIngresso.ano > competenciaFinalEfetiva.ano) {
    return [];
  }

  const resultado: Competencia[] = [];
  for (let ano = competenciaIngresso.ano; ano <= competenciaFinalEfetiva.ano; ano++) {
    const inicio = inicioCicloDoAno(competenciaIngresso, ano);
    if (inicio === null) continue;

    const fimDoAno: Competencia =
      ano === competenciaFinalEfetiva.ano ? competenciaFinalEfetiva : { mes: 12, ano };
    if (compararCompetencias(inicio, fimDoAno) > 0) continue;

    resultado.push(...gerarIntervaloCompetencias(inicio, fimDoAno));
  }
  return resultado;
}

/**
 * Verifica se um membro estava sujeito à cobrança em uma competência específica —
 * ou seja, se ela cai dentro do período cobrável dele (considerando ingresso e afastamento).
 * Usado para decidir se um membro deve entrar nas métricas do dashboard de um mês de
 * referência: um membro afastado HOJE ainda contava normalmente em meses anteriores
 * ao seu afastamento, mas não deve ser contado a partir do mês em que se afastou.
 */
export function membroEstaSujeitoACobranca(membro: Membro, competenciaReferencia: Competencia): boolean {
  const competenciaIngresso = competenciaDeDataISO(membro.dataIngresso);
  if (compararCompetencias(competenciaIngresso, competenciaReferencia) > 0) return false;

  if (membro.status !== "afastado") return true;

  const competenciaAfastamento = competenciaDeStringAnoMes(membro.competenciaAfastamento);
  if (competenciaAfastamento === null) return true;

  // Sujeito à cobrança apenas se a referência for ANTERIOR ao mês em que se afastou.
  return compararCompetencias(competenciaReferencia, competenciaAfastamento) < 0;
}

/** Gera uma chave única "ano-mes" para comparação de competências em Sets/Maps. */
export function chaveCompetencia(c: Competencia): string {
  return `${c.ano}-${c.mes}`;
}

/**
 * Texto curto para o badge de status na lista de membros.
 *
 * Regra de UX combinada: mostra a quantidade de meses pendentes quando > 1, para já
 * informar a gravidade do atraso sem precisar abrir o histórico. Para membros afastados,
 * combina o status "Afastado" com a dívida residual (se houver) — o afastamento não some
 * com a informação de que ainda existe pendência anterior a ele.
 */
export function textoBadgeStatus(resumo: ResumoInadimplenciaMembro, afastado = false): string {
  if (afastado) {
    if (resumo.totalMesesPendentes === 0) return "Afastado";
    if (resumo.totalMesesPendentes === 1) return "Afastado · Deve 1 mês";
    return `Afastado · Deve ${resumo.totalMesesPendentes} meses`;
  }
  if (resumo.totalMesesPendentes === 0) return "Em Dia";
  if (resumo.totalMesesPendentes === 1) return "Pendente";
  return `Pendente (${resumo.totalMesesPendentes} meses)`;
}
