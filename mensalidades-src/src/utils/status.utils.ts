import type { Competencia, Membro, Pagamento } from "../types";
import {
  compararCompetencias,
  competenciaDeDataISO,
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

  const todasCompetenciasEsperadas = gerarIntervaloCompetencias(
    competenciaInicioCiclo,
    competenciaReferencia,
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
 * Gera todas as competências de cobrança esperadas de um membro, percorrendo CADA ANO
 * desde o ano de ingresso até o ano da competência final (inclusive), respeitando o
 * ciclo anual de cada ano (Janeiro-Dezembro, exceto o ano de ingresso que pode começar
 * mais tarde). Usado no histórico multi-ano: mostra exatamente os meses que o membro
 * deveria ter pago em cada ano, sem "inventar" pendência de anos antes do ingresso nem
 * de meses do ano de ingresso anteriores à entrada do membro.
 */
export function gerarCompetenciasEsperadasHistorico(
  membro: Membro,
  competenciaFinal: Competencia,
): Competencia[] {
  const competenciaIngresso = competenciaDeDataISO(membro.dataIngresso);
  if (competenciaIngresso.ano > competenciaFinal.ano) return [];

  const resultado: Competencia[] = [];
  for (let ano = competenciaIngresso.ano; ano <= competenciaFinal.ano; ano++) {
    const inicio = inicioCicloDoAno(competenciaIngresso, ano);
    if (inicio === null) continue;

    const fimDoAno: Competencia = ano === competenciaFinal.ano ? competenciaFinal : { mes: 12, ano };
    if (compararCompetencias(inicio, fimDoAno) > 0) continue;

    resultado.push(...gerarIntervaloCompetencias(inicio, fimDoAno));
  }
  return resultado;
}

/** Gera uma chave única "ano-mes" para comparação de competências em Sets/Maps. */
export function chaveCompetencia(c: Competencia): string {
  return `${c.ano}-${c.mes}`;
}

/**
 * Texto curto para o badge de status na lista de membros.
 * Regra de UX combinada: mostra a quantidade de meses pendentes quando > 1,
 * para já informar a gravidade do atraso sem precisar abrir o histórico.
 */
export function textoBadgeStatus(resumo: ResumoInadimplenciaMembro): string {
  if (resumo.totalMesesPendentes === 0) return "Em Dia";
  if (resumo.totalMesesPendentes === 1) return "Pendente";
  return `Pendente (${resumo.totalMesesPendentes} meses)`;
}
