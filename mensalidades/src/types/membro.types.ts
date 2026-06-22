/**
 * Status do membro no clube.
 * Membros INATIVOS não entram no cálculo de inadimplência/dashboard.
 */
export type StatusMembro = "ativo" | "inativo";

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

  /** Data de ingresso no clube, em formato ISO (YYYY-MM-DD). */
  dataIngresso: string;

  /** Status atual do membro. */
  status: StatusMembro;

  /** Timestamp de criação do registro (epoch ms). */
  criadoEm: number;

  /** Timestamp da última atualização do registro (epoch ms). */
  atualizadoEm: number;
}

/**
 * Payload para criação de um novo membro (sem campos gerados pelo sistema).
 */
export type NovoMembroInput = Pick<Membro, "nome" | "apelido" | "dataIngresso" | "status">;

/**
 * Payload para edição parcial de um membro existente.
 */
export type EditarMembroInput = Partial<NovoMembroInput>;
