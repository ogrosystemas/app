/**
 * Representa a competência de uma mensalidade: um mês específico de um ano específico.
 * Usamos mes como 1-12 (não 0-11) para evitar confusão fora do contexto de Date do JS.
 */
export interface Competencia {
  /** Mês da competência, de 1 (Janeiro) a 12 (Dezembro). */
  mes: number;

  /** Ano da competência (ex: 2026). */
  ano: number;
}

/**
 * Forma de pagamento aceita pelo clube.
 */
export type FormaPagamento = "dinheiro" | "pix" | "transferencia" | "outro";

/**
 * Representa o registro de pagamento de UMA mensalidade de UM membro
 * para UMA competência (mês/ano) específica.
 *
 * A "baixa" de uma mensalidade é, na prática, a criação de um registro
 * de Pagamento para aquele membro + competência. A ausência de um registro
 * para uma competência passada é o que caracteriza a inadimplência.
 */
export interface Pagamento {
  /** ID do documento no Firestore (gerado automaticamente). Ausente antes de salvar. */
  id?: string;

  /** FK para Membro.id. */
  membroId: string;

  /** Mês da competência paga (1-12). */
  mes: number;

  /** Ano da competência paga. */
  ano: number;

  /** Valor efetivamente pago, em reais (ex: 50.0). Congelado no momento do pagamento. */
  valorPago: number;

  /** Data em que o pagamento foi de fato registrado/baixado, em ISO (YYYY-MM-DD). */
  dataPagamento: string;

  /** Forma de pagamento utilizada. */
  formaPagamento: FormaPagamento;

  /** Observação livre opcional (ex: "pago atrasado, combinado com o tesoureiro"). */
  observacao?: string;

  /** Timestamp de criação do registro (epoch ms). */
  criadoEm: number;
}

/**
 * Payload para registrar ("dar baixa em") um pagamento.
 */
export type NovoPagamentoInput = Pick<
  Pagamento,
  "membroId" | "mes" | "ano" | "valorPago" | "dataPagamento" | "formaPagamento"
> &
  Partial<Pick<Pagamento, "observacao">>;

/**
 * Status de uma competência específica para um membro,
 * já cruzando a existência (ou não) do registro de Pagamento.
 */
export type StatusMensalidade = "em_dia" | "pendente";

/**
 * Resultado de uma competência específica avaliada para um membro:
 * combina a competência, se foi paga, e o registro de pagamento (se existir).
 */
export interface CompetenciaStatus extends Competencia {
  status: StatusMensalidade;
  pagamento?: Pagamento;
}
