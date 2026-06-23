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
 *
 * IMPORTANTE sobre o formato da chave de telefone: dentro do payload do QR Code (BR Code),
 * o Manual Operacional do DICT do Banco Central EXIGE o formato internacional completo
 * "+55DDDNNNNNNNNN" (com o "+" e o código do país) — mesmo que, ao CADASTRAR a chave no
 * app do banco, você só tenha digitado o DDD + número, sem o "+55". Usar o número "puro"
 * aqui (sem o +55) é um erro real já cometido nesta implementação: o app do banco lê o QR
 * Code mas rejeita a chave antes mesmo de mostrar os dados da cobrança, com uma mensagem
 * genérica de "não foi possível completar a solicitação" — confirmado na prática com o
 * banco C6 ao testar um QR Code gerado sem o +55.
 */
export const PIX_CHAVE = "+5547996018551";
export const PIX_NOME_RECEBEDOR = "Tiburcio Pancotto de Barcelos";
export const PIX_CIDADE = "Itajaí";
