/**
 * Lista de patentes/cargos do clube, EM ORDEM HIERÁRQUICA (a primeira é a mais alta).
 * Usada tanto no seletor do formulário de cadastro quanto para ordenação/agrupamento
 * futuro da lista de membros por patente.
 */
export const PATENTES_EM_ORDEM: readonly string[] = [
  "Diretor Regional",
  "Veterano",
  "Diretor",
  "Subdiretor",
  "Escudo Fechado",
  "Meio Escudo Maior",
  "Meio Escudo Menor",
  "PP",
];

/** Índice de uma patente na hierarquia (0 = mais alta). -1 se a patente não estiver na lista. */
export function indiceDaPatente(patente: string): number {
  return PATENTES_EM_ORDEM.indexOf(patente);
}
