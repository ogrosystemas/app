/**
 * Dados da chave Pix usada para gerar QR Codes de cobrança desta sede específica
 * (ver utils/pix.utils.ts). Cada sede tem sua própria chave — o dinheiro cai
 * direto na conta do tesoureiro responsável por aquela sede, nunca centralizado.
 */
export interface ConfigPix {
  chave: string;
  nomeRecebedor: string;
  cidade: string;
}

/**
 * Configuração geral de uma sede (clube), armazenada no próprio documento raiz
 * dessa sede no Firestore (caminho: clubes/{clubeId}). Mantida como um objeto
 * separado (não espalhada em campos soltos) para facilitar atualização parcial.
 *
 * Cada sede é completamente isolada das demais: nome, valor de mensalidade e
 * dados do Pix são todos específicos desta sede — nunca compartilhados ou
 * herdados de outra.
 */
export interface ConfigClube {
  /** Nome da sede, exibido no header do app (ex: "Mutantes Moto Clube — Itajaí"). */
  nomeClube: string;

  /** Valor fixo da mensalidade desta sede, em reais, aplicado a todos os membros dela. */
  valorMensalidade: number;

  /** Dados da chave Pix desta sede. Ausente até o tesoureiro configurar em Configurações. */
  pix?: ConfigPix;

  /** Timestamp da última atualização (epoch ms). */
  atualizadoEm: number;
}

export type EditarConfigInput = Partial<Pick<ConfigClube, "nomeClube" | "valorMensalidade" | "pix">>;
