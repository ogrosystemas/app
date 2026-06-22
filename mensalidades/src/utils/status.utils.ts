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
 * Calcula, para um único membro, todas as competências pendentes desde o ingresso
 * até a competência de referência (inclusive), cruzando com os pagamentos já registrados.
 *
 * Esta é a função central da regra de negócio "quem está em dia ou inadimplente":
 * uma competência é considerada pendente se está dentro do intervalo
 * [competência de ingresso, competência de referência] e não existe Pagamento para ela.
 */
export function calcularInadimplenciaMembro(
  membro: Membro,
  pagamentosDoMembro: Pagamento[],
  competenciaReferencia: Competencia,
  valorMensalidade: number,
): ResumoInadimplenciaMembro {
  const competenciaIngresso = competenciaDeDataISO(membro.dataIngresso);

  // Se o ingresso é posterior à referência (ex: cadastro futuro), não há nada pendente ainda.
  if (compararCompetencias(competenciaIngresso, competenciaReferencia) > 0) {
    return {
      membroId: membro.id ?? -1,
      competenciasPendentes: [],
      totalMesesPendentes: 0,
      valorTotalDevido: 0,
      competenciaReferenciaPaga: false,
    };
  }

  const todasCompetenciasEsperadas = gerarIntervaloCompetencias(
    competenciaIngresso,
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
