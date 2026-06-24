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
 * (nome do recebedor e cidade não podem ter acentuação). Mantém espaços normais.
 *
 * HISTÓRICO IMPORTANTE — não repetir este erro: uma versão anterior desta função
 * substituía espaços por underscore, baseada em um único payload "Copia e Cola"
 * copiado do SITE do Banco do Brasil, que continha "TIBURCIO_PANCOTTO_DE_BARC"
 * (com underscore). Essa generalização a partir de uma única amostra estava
 * ERRADA: comparando depois com um Pix real gerado pelo APP OFICIAL DA CAIXA
 * (o PSP onde a chave de fato está registrada), o nome veio com ESPAÇO NORMAL —
 * "TIBURCIO PANCOTTO DE BARC" — exatamente como o exemplo oficial do Manual de
 * Padrões do Banco Central também usa ("Fulano de Tal"). O underscore era uma
 * particularidade de exibição daquele site específico do BB, não uma exigência
 * universal do formato — usá-lo causou rejeição ("Parâmetros inválidos"/"Ocorreu
 * um erro") em múltiplos bancos pagadores (BB, C6, Bradesco) por semanas, porque
 * a causa real do problema era outra (não identificada até a comparação direta
 * com o payload gerado pelo PSP real da chave). Lição: ao investigar problemas
 * de compatibilidade Pix, comparar sempre contra um payload gerado pelo PSP
 * ONDE A CHAVE ESTÁ REGISTRADA — não contra qualquer site/app de terceiros que
 * gere algo "parecido".
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
 * Tabela de lookup pré-computada para o CRC16 usado pelo Pix (polinômio 0x1021,
 * sem reflexão de bits — formalmente "CRC-16/IBM-3740", às vezes chamado por engano
 * de "CRC-16/CCITT-FALSE" ou só "CRC-CCITT" na documentação de mercado).
 */
function gerarTabelaCRC16(): number[] {
  const POLINOMIO = 0x1021;
  const tabela = new Array<number>(256);
  for (let i = 0; i < 256; i++) {
    let crc = i << 8;
    for (let bit = 0; bit < 8; bit++) {
      crc = crc & 0x8000 ? (crc << 1) ^ POLINOMIO : crc << 1;
      crc &= 0xffff;
    }
    tabela[i] = crc;
  }
  return tabela;
}

const TABELA_CRC16 = gerarTabelaCRC16();

/**
 * Calcula o CRC16 exigido pelo campo final (ID 63) do payload Pix — valor inicial
 * 0xFFFF, sem XOR final, sem reflexão de bits.
 *
 * HISTÓRICO IMPORTANTE — não repetir este erro: uma versão anterior desta função
 * processava 2 bytes extras de valor zero ("augment") ao final do cálculo, e
 * trazia como prova de correção o fato de produzir 0xE5CC para a entrada de teste
 * "123456789". Essa referência estava ERRADA: 0xE5CC não é o valor de teste do
 * algoritmo que o Pix usa (CRC-16/IBM-3740) — é de uma variante diferente. O
 * catálogo oficial de algoritmos CRC (reveng.sourceforge.io/crc-catalogue) confirma
 * que o valor de teste correto para "123456789" é 0x29B1, exatamente o valor que
 * muita gente (inclusive este código, numa versão anterior) descartava como
 * "errado" por desconhecer essa confusão de nomenclatura entre variantes do CCITT.
 * A implementação com "augment" gerava QR Codes estruturalmente bem formados (CRC
 * internamente consistente com a própria lógica errada, então passavam até por
 * validadores de terceiros que só verificam autoconsistência) mas o valor não
 * batia com o que os apps de banco esperavam, causando rejeição silenciosa
 * ("Parâmetros inválidos"/"Ocorreu um erro") sem nenhuma pista de qual campo
 * estava errado. Confirmado definitivamente comparando contra um payload real
 * gerado pelo app oficial da Caixa (PSP onde a chave estava registrada): o CRC
 * correto era 0xF599, e só a implementação SEM o augment (a desta versão) produz
 * esse valor para aquele payload exato.
 */
function calcularCRC16(texto: string): string {
  let crc = 0xffff;
  for (let i = 0; i < texto.length; i++) {
    const byte = texto.charCodeAt(i);
    crc = ((crc << 8) ^ TABELA_CRC16[(crc >> 8) ^ byte]!) & 0xffff;
  }
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
  // O GUI "br.gov.bcb.pix" é usado em MINÚSCULAS no exemplo oficial do Manual de Padrões
  // para Iniciação do Pix (Banco Central) — usar maiúsculas ("BR.GOV.BCB.PIX") é aceito
  // por muitos bancos (provavelmente comparação case-insensitive), mas pelo menos um banco
  // (Banco do Brasil) rejeitou com "Parâmetros inválidos" um payload com o GUI em
  // maiúsculas, confirmado em teste real — manter sempre minúsculas, exatamente como no
  // manual, para máxima compatibilidade.
  const merchantAccountInfo =
    campoTLV("00", "br.gov.bcb.pix") + campoTLV("01", dados.chave);

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
