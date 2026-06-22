/**
 * Status do membro no clube.
 *
 * "afastado" substitui o antigo conceito de "inativo": diferente de uma simples
 * desativação, o afastamento PARA de gerar novas cobranças a partir do momento em
 * que é marcado, mas preserva qualquer pendência anterior a essa data (a dívida
 * não é perdoada). Membros afastados continuam visíveis na lista de conferência,
 * apenas sem ações de cobrança disponíveis.
 */
export type StatusMembro = "ativo" | "afastado";

/**
 * Representa um integrante do Moto Clube.
 */
export interface Membro {
  /** Chave primária auto-incrementada pelo Dexie. Ausente antes de salvar. */
  id?: number;

  /** Nome completo do membro. */
  nome: string;

  /** Apelido / Alcunha usado no clube (ex: "Foice", "Sombra"). */
  apelido: string;

  /**
   * Data de ingresso no clube, em formato ISO (YYYY-MM-DD).
   * Mantida apenas como informação histórica do cadastro: desde a regra de ciclo
   * anual de cobrança, este campo só afeta o cálculo de mensalidades no próprio
   * ano em que o ingresso ocorreu (ver `src/utils/status.utils.ts`).
   */
  dataIngresso: string;

  /** Status atual do membro. */
  status: StatusMembro;

  /**
   * Competência (formato "YYYY-MM") a partir da qual o afastamento entrou em vigor.
   * Presente somente quando `status === "afastado"`. Competências a partir desta
   * (inclusive) não geram pendência de cobrança; competências anteriores a ela
   * continuam sendo cobradas/contabilizadas normalmente, mesmo após o afastamento.
   */
  competenciaAfastamento?: string;

  /** Timestamp de criação do registro (epoch ms). */
  criadoEm: number;

  /** Timestamp da última atualização do registro (epoch ms). */
  atualizadoEm: number;
}

/**
 * Payload para criação de um novo membro (sem campos gerados pelo sistema).
 * A data de ingresso é preenchida automaticamente com a data atual no momento
 * do cadastro — não é mais solicitada no formulário (ver MemberFormModal).
 */
export type NovoMembroInput = Pick<Membro, "nome" | "apelido">;

/**
 * Payload para edição parcial de um membro existente.
 */
export type EditarMembroInput = Partial<
  Pick<Membro, "nome" | "apelido" | "status" | "competenciaAfastamento">
>;
