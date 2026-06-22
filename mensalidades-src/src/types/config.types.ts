/**
 * Configuração geral do clube, armazenada no próprio documento raiz do clube
 * no Firestore (caminho fixo: clubes/mutantes-mc). Mantida como um objeto
 * separado (não espalhada em campos soltos) para facilitar atualização parcial.
 */
export interface ConfigClube {
  /** Nome do clube, exibido no header do app. */
  nomeClube: string;

  /** Valor fixo da mensalidade, em reais, aplicado a todos os membros. */
  valorMensalidade: number;

  /** Timestamp da última atualização (epoch ms). */
  atualizadoEm: number;
}

export type EditarConfigInput = Partial<Pick<ConfigClube, "nomeClube" | "valorMensalidade">>;
