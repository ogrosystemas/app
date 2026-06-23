/** Limite de meses pendentes a partir do qual consideramos o membro "crítico" (vermelho mais forte / aviso extra). */
export const LIMITE_MESES_CRITICO = 3;

/** Nome padrão do clube usado como fallback antes do carregamento da config do banco. */
export const NOME_CLUBE_PADRAO = "Mutantes Moto Clube";

/** Valor padrão da mensalidade usado como fallback antes do carregamento da config do banco. */
export const VALOR_MENSALIDADE_PADRAO = 130.0;

/**
 * Dados da chave Pix usada para gerar os QR Codes de cobrança (ver utils/pix.utils.ts).
 * Fixos aqui — diferente do nome/valor da mensalidade, não fazem parte da configuração
 * editável pelo app, já que envolvem dados bancários reais.
 */
export const PIX_CHAVE = "47996018551";
export const PIX_NOME_RECEBEDOR = "Tiburcio Pancotto de Barcelos";
export const PIX_CIDADE = "Itajaí";
