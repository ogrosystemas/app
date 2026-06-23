/**
 * Geração de QR Code Pix dinâmico (payload no padrão BR Code / EMV QRCPS), seguindo
 * o Manual de Padrões para Iniciação do Pix do Banco Central. Gerado inteiramente no
 * navegador, sem nenhuma API externa — o BR Code é apenas um texto estruturado no
 * formato TLV (Tag-Length-Value) que qualquer app de banco sabe interpretar.
 *
 * Referência: https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf
 */

export interface DadosPix {
  /** Chave Pix do recebedor (CPF, CNPJ, e-mail, telefone com +55, ou chave aleatória). */
  chave: string;
  /** Nome do recebedor, como aparece na conta bancária. Será truncado em 25 caracteres (limite EMV). */
  nomeRecebedor: string;
  /** Cidade do recebedor. Será truncada em 15 caracteres (limite EMV). */
  cidade: string;
  /** Valor da cobrança em reais (ex: 130.00). Opcional — se omitido, o pagador digita o valor. */
  valor?: number;
  /**
   * Identificador da transação (TxID) — texto curto usado pelo app do banco para
   * identificar esta cobrança específica no extrato. Apenas alfanumérico (A-Z, 0-9);
   * caracteres fora desse conjunto são removidos automaticamente. Máx. 25 caracteres.
   * Se omitido, usa "***" (convenção do padrão para "sem identificador").
   */
  txId?: string;
}

/** Monta um campo no formato TLV: ID (2 dígitos) + Tamanho (2 dígitos) + Valor. */
function campoTLV(id: string, valor: string): string {
  const tamanho = String(valor.length).padStart(2, "0");
  return `${id}${tamanho}${valor}`;
}

/**
 * Remove acentos e caracteres fora do padrão ASCII simples exigido pelo EMV
 * (nome do recebedor e cidade não podem ter acentuação).
 */
function paraAsciiSimples(texto: string): string {
  return texto
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // remove os acentos (diacríticos)
    .replace(/[^a-zA-Z0-9 ]/g, ""); // remove qualquer símbolo restante fora de letras/números/espaço
}

/** Mantém apenas caracteres alfanuméricos, exigência do campo TxID (ID 05 dentro do campo 62). */
function paraAlfanumerico(texto: string): string {
  return texto.replace(/[^a-zA-Z0-9]/g, "");
}

/**
 * Aplica uma "rodada" do CRC16-CCITT-FALSE para um único byte, processando seus 8 bits
 * do mais significativo ao menos significativo.
 */
function avancarCRC16(byte: number, crcAtual: number): number {
  const POLINOMIO = 0x1021;
  let crc = crcAtual;

  for (let i = 7; i >= 0; i--) {
    const bitMaisAltoEstaLigado = (crc & 0x8000) !== 0;
    crc = (crc << 1) & 0xffff;
    crc |= byte & (1 << i) ? 1 : 0;
    if (bitMaisAltoEstaLigado) {
      crc ^= POLINOMIO;
      crc &= 0xffff;
    }
  }

  return crc;
}

/**
 * Calcula o CRC16-CCITT-FALSE (polinômio 0x1021, valor inicial 0xFFFF, sem reflexão) —
 * exatamente o algoritmo exigido pelo campo final (ID 63) do payload Pix.
 *
 * Importante: depois de processar todos os bytes da entrada, é necessário processar
 * mais 2 bytes de valor zero ("augment" de 16 bits) — essa etapa é facilmente esquecida
 * (várias implementações públicas a esquecem) e produz um CRC sutilmente errado que
 * PARECE plausível mas é rejeitado pelo app do banco. Validado aqui contra o valor de
 * referência oficial do CRC16-CCITT: a entrada "123456789" deve produzir 0xE5CC — ver
 * scripts de teste deste projeto, que confirmam exatamente esse valor.
 */
function calcularCRC16(texto: string): string {
  let crc = 0xffff;

  for (let i = 0; i < texto.length; i++) {
    crc = avancarCRC16(texto.charCodeAt(i), crc);
  }

  // Augment: dois bytes adicionais de valor zero, exigido pela definição formal do CRC.
  crc = avancarCRC16(0, crc);
  crc = avancarCRC16(0, crc);

  return crc.toString(16).toUpperCase().padStart(4, "0");
}

/**
 * Gera o payload completo do Pix (texto "Copia e Cola", que também é o conteúdo
 * codificado dentro do QR Code) a partir dos dados informados.
 */
export function gerarPayloadPix(dados: DadosPix): string {
  const nomeRecebedor = paraAsciiSimples(dados.nomeRecebedor).slice(0, 25).toUpperCase();
  const cidade = paraAsciiSimples(dados.cidade).slice(0, 15).toUpperCase();
  const txId = dados.txId ? paraAlfanumerico(dados.txId).slice(0, 25) || "***" : "***";

  // Campo 26 — Merchant Account Information (dados específicos do arranjo Pix).
  const merchantAccountInfo =
    campoTLV("00", "BR.GOV.BCB.PIX") + campoTLV("01", dados.chave);

  // Campo 62 — Additional Data Field (contém o TxID, subcampo 05).
  const additionalDataField = campoTLV("05", txId);

  const camposSemCRC = [
    campoTLV("00", "01"), // Payload Format Indicator — fixo "01"
    campoTLV("26", merchantAccountInfo),
    campoTLV("52", "0000"), // Merchant Category Code — "0000" = não informado
    campoTLV("53", "986"), // Transaction Currency — 986 = BRL (ISO 4217)
    dados.valor !== undefined ? campoTLV("54", dados.valor.toFixed(2)) : "",
    campoTLV("58", "BR"), // Country Code
    campoTLV("59", nomeRecebedor),
    campoTLV("60", cidade),
    campoTLV("62", additionalDataField),
  ].join("");

  // O campo do CRC (ID 63, tamanho 04) é calculado sobre TUDO que vem antes dele,
  // incluindo o próprio "6304" do cabeçalho do campo do CRC (mas não o valor do CRC
  // em si, que ainda não existe nesse momento) — por isso se inclui "6304" aqui antes
  // de calcular, conforme o Manual do BR Code.
  const payloadParaCRC = `${camposSemCRC}6304`;
  const crc = calcularCRC16(payloadParaCRC);

  return `${payloadParaCRC}${crc}`;
}
