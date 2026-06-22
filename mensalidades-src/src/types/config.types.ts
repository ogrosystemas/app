/**
 * Configuração geral do clube, armazenada como um único registro na tabela `config`.
 * Mantida separada para permitir reajuste do valor da mensalidade sem migração de schema.
 */
export interface ConfigClube {
  /** Chave fixa = 1 (singleton). Garante um único registro de configuração. */
  id?: number;

  /** Nome do clube, exibido no header do app. */
  nomeClube: string;

  /** Valor fixo da mensalidade, em reais, aplicado a todos os membros. */
  valorMensalidade: number;

  /** Timestamp da última atualização (epoch ms). */
  atualizadoEm: number;
}

export type EditarConfigInput = Partial<Pick<ConfigClube, "nomeClube" | "valorMensalidade">>;
