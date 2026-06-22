import type { Competencia } from "../types";

const NOMES_MESES = [
  "Janeiro",
  "Fevereiro",
  "Março",
  "Abril",
  "Maio",
  "Junho",
  "Julho",
  "Agosto",
  "Setembro",
  "Outubro",
  "Novembro",
  "Dezembro",
] as const;

const NOMES_MESES_ABREV = [
  "Jan",
  "Fev",
  "Mar",
  "Abr",
  "Mai",
  "Jun",
  "Jul",
  "Ago",
  "Set",
  "Out",
  "Nov",
  "Dez",
] as const;

/** Retorna a competência (mes/ano) correspondente a "hoje". */
export function competenciaAtual(): Competencia {
  const hoje = new Date();
  return { mes: hoje.getMonth() + 1, ano: hoje.getFullYear() };
}

/** Nome completo do mês (1-12) em português. Lança erro se mes for inválido. */
export function nomeMes(mes: number): string {
  const nome = NOMES_MESES[mes - 1];
  if (!nome) throw new Error(`Mês inválido: ${mes}`);
  return nome;
}

/** Nome abreviado do mês (1-12) em português (ex: "Jan"). */
export function nomeMesAbreviado(mes: number): string {
  const nome = NOMES_MESES_ABREV[mes - 1];
  if (!nome) throw new Error(`Mês inválido: ${mes}`);
  return nome;
}

/** Formata uma competência como "Junho/2026". */
export function formatarCompetencia(c: Competencia): string {
  return `${nomeMes(c.mes)}/${c.ano}`;
}

/** Formata uma competência abreviada como "Jun/26". */
export function formatarCompetenciaAbreviada(c: Competencia): string {
  return `${nomeMesAbreviado(c.mes)}/${String(c.ano).slice(-2)}`;
}

/** Compara duas competências: retorna negativo se a < b, positivo se a > b, 0 se iguais. */
export function compararCompetencias(a: Competencia, b: Competencia): number {
  if (a.ano !== b.ano) return a.ano - b.ano;
  return a.mes - b.mes;
}

/** Retorna a competência seguinte (ex: 12/2025 -> 1/2026). */
export function proximaCompetencia(c: Competencia): Competencia {
  if (c.mes === 12) return { mes: 1, ano: c.ano + 1 };
  return { mes: c.mes + 1, ano: c.ano };
}

/** Retorna a competência anterior (ex: 1/2026 -> 12/2025). */
export function competenciaAnterior(c: Competencia): Competencia {
  if (c.mes === 1) return { mes: 12, ano: c.ano - 1 };
  return { mes: c.mes - 1, ano: c.ano };
}

/**
 * Converte uma data ISO (YYYY-MM-DD) na competência correspondente.
 * Usado para derivar a competência de ingresso de um membro.
 */
export function competenciaDeDataISO(dataISO: string): Competencia {
  const [anoStr, mesStr] = dataISO.split("-");
  const ano = Number(anoStr);
  const mes = Number(mesStr);
  if (!ano || !mes) throw new Error(`Data ISO inválida: ${dataISO}`);
  return { mes, ano };
}

/**
 * Gera a lista de todas as competências entre `inicio` e `fim`, inclusive, em ordem crescente.
 * Se `inicio` for posterior a `fim`, retorna lista vazia.
 */
export function gerarIntervaloCompetencias(inicio: Competencia, fim: Competencia): Competencia[] {
  if (compararCompetencias(inicio, fim) > 0) return [];

  const resultado: Competencia[] = [];
  let cursor: Competencia = { ...inicio };

  // Limite de segurança (100 anos) para nunca entrar em loop infinito por dado corrompido.
  let guarda = 0;
  while (compararCompetencias(cursor, fim) <= 0 && guarda < 1200) {
    resultado.push(cursor);
    cursor = proximaCompetencia(cursor);
    guarda++;
  }
  return resultado;
}

/** Formata uma data ISO (YYYY-MM-DD) como "dd/mm/aaaa" para exibição. */
export function formatarDataBR(dataISO: string): string {
  const [ano, mes, dia] = dataISO.split("-");
  return `${dia}/${mes}/${ano}`;
}

/** Retorna a data de hoje em formato ISO (YYYY-MM-DD), respeitando o fuso local. */
export function hojeISO(): string {
  const hoje = new Date();
  const ano = hoje.getFullYear();
  const mes = String(hoje.getMonth() + 1).padStart(2, "0");
  const dia = String(hoje.getDate()).padStart(2, "0");
  return `${ano}-${mes}-${dia}`;
}

/**
 * Converte uma competência no formato "YYYY-MM" (usado em Membro.competenciaAfastamento)
 * para o tipo Competencia. Retorna null se a string for inválida ou vazia.
 */
export function competenciaDeStringAnoMes(valor: string | undefined): Competencia | null {
  if (!valor) return null;
  const [anoStr, mesStr] = valor.split("-");
  const ano = Number(anoStr);
  const mes = Number(mesStr);
  if (!ano || !mes || mes < 1 || mes > 12) return null;
  return { mes, ano };
}

/** Formata a competência atual como string "YYYY-MM", para gravar em Membro.competenciaAfastamento. */
export function competenciaAtualComoStringAnoMes(): string {
  const { mes, ano } = competenciaAtual();
  return `${ano}-${String(mes).padStart(2, "0")}`;
}
