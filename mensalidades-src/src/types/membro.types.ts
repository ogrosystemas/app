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
  /** ID do documento no Firestore (gerado automaticamente). Ausente antes de salvar. */
  id?: string;

  /** Nome completo do membro. */
  nome: string;

  /** Apelido / Alcunha usado no clube (ex: "Foice", "Sombra"). */
  apelido: string;

  /**
   * Patente/cargo do membro dentro do clube (ex: "Presidente", "PP").
   * Lista fixa definida em PATENTES_EM_ORDEM (constants/patentes.constants.ts),
   * em ordem hierárquica — usada tanto no seletor do formulário quanto para
   * ordenação/agrupamento futuro da lista por patente.
   */
  patente: string;

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

  /**
   * E-mail da conta Google que este membro usa para acessar a própria área de
   * consulta (somente leitura: status, histórico, sem valores nem ações de
   * administração). Opcional — membros sem e-mail vinculado simplesmente não
   * têm acesso individual ao app. Vinculado também em uma entrada espelhada na
   * coleção `acessos/{email}` (ver db/refs.ts), usada pelas regras de segurança
   * do Firestore para restringir o que esse e-mail pode ler.
   */
  emailAcesso?: string;

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
export type NovoMembroInput = Pick<Membro, "nome" | "apelido" | "patente">;

/**
 * Payload para edição parcial de um membro existente.
 */
export type EditarMembroInput = Partial<
  Pick<Membro, "nome" | "apelido" | "patente" | "status" | "competenciaAfastamento" | "emailAcesso">
>;
