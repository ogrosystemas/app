/**
 * Aviso informal enviado pelo próprio integrante, sinalizando que pretende
 * pagar uma competência específica em breve. É puramente informativo — não
 * altera o status real de pendência (ver status.utils.ts), só aparece para o
 * administrador como um lembrete de que aquele membro já está se organizando.
 */
export interface AvisoPagamento {
  /** ID do documento no Firestore (gerado automaticamente). Ausente antes de salvar. */
  id?: string;

  /** FK para Membro.id. */
  membroId: string;

  /** Mês da competência que o membro avisou que vai pagar (1-12). */
  mes: number;

  /** Ano da competência que o membro avisou que vai pagar. */
  ano: number;

  /** Timestamp de quando o aviso foi enviado (epoch ms). */
  criadoEm: number;
}

export type NovoAvisoPagamentoInput = Pick<AvisoPagamento, "membroId" | "mes" | "ano">;
