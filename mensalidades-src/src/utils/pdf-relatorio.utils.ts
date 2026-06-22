import type { PDFDocument as PDFDocumentType, PDFFont, PDFPage, RGB } from "pdf-lib";
import fontUrl from "../assets/fonts/Roboto-Regular.ttf?url";
import type { DadosRelatorio } from "./relatorio.utils";
import { formatarDataBR } from "./date.utils";

interface PaletaCores {
  preto: RGB;
  cinza: RGB;
  verde: RGB;
  vermelho: RGB;
  laranja: RGB;
  linha: RGB;
}

const LARGURA_PAGINA = 595.28; // A4 em pontos
const ALTURA_PAGINA = 841.89;
const MARGEM = 50;

/**
 * Gera o PDF do relatório de conferência a partir dos dados já calculados
 * (ver gerarDadosRelatorio em relatorio.utils.ts). Retorna os bytes do PDF —
 * cabe ao chamador decidir o que fazer com eles (download, preview, etc).
 *
 * Usa uma fonte TrueType embutida (Roboto, licença SIL OFL — ver assets/fonts/OFL-Roboto.txt)
 * em vez das StandardFonts do pdf-lib, porque estas últimas não suportam caracteres
 * acentuados do português (ã, ç, é, etc.) — ver discussão registrada no código-fonte.
 *
 * pdf-lib e @pdf-lib/fontkit são importados DINAMICAMENTE (import() em vez de import no
 * topo do arquivo) de propósito: são bibliotecas relativamente grandes que só fazem sentido
 * carregar quando o usuário de fato pede um relatório — manter como import estático faria
 * o bundle principal do app (carregado em toda visita) incluir esse código à toa.
 */
export async function gerarPdfRelatorio(dados: DadosRelatorio): Promise<Uint8Array> {
  const [{ PDFDocument, rgb }, { default: fontkit }] = await Promise.all([
    import("pdf-lib"),
    import("@pdf-lib/fontkit"),
  ]);

  const cores: PaletaCores = {
    preto: rgb(0.05, 0.05, 0.05),
    cinza: rgb(0.45, 0.45, 0.45),
    verde: rgb(0.08, 0.5, 0.25),
    vermelho: rgb(0.75, 0.15, 0.1),
    laranja: rgb(0.85, 0.35, 0.05),
    linha: rgb(0.85, 0.85, 0.85),
  };

  const pdfDoc = await PDFDocument.create();
  pdfDoc.registerFontkit(fontkit);

  const fontBytes = await fetch(fontUrl).then((r) => r.arrayBuffer());
  const fonte = await pdfDoc.embedFont(fontBytes, { subset: true });

  pdfDoc.setTitle(`Relatório ${dados.tituloPeriodo} — ${dados.nomeClube}`);
  pdfDoc.setProducer(dados.nomeClube);
  pdfDoc.setCreationDate(new Date(dados.geradoEm));

  let page = pdfDoc.addPage([LARGURA_PAGINA, ALTURA_PAGINA]);
  let cursorY = ALTURA_PAGINA - MARGEM;

  cursorY = desenharCabecalho(page, fonte, dados, cursorY, cores);
  cursorY = desenharResumo(page, fonte, dados, cursorY, cores);
  cursorY -= 10;

  const resultadoTabela = desenharTabelaMembros(pdfDoc, page, fonte, dados, cursorY, cores);
  page = resultadoTabela.page;

  desenharRodape(page, fonte, dados, cores);

  return pdfDoc.save();
}

function desenharCabecalho(
  page: PDFPage,
  fonte: PDFFont,
  dados: DadosRelatorio,
  yInicial: number,
  cores: PaletaCores,
): number {
  let y = yInicial;

  page.drawText(dados.nomeClube, {
    x: MARGEM,
    y,
    size: 18,
    font: fonte,
    color: cores.preto,
  });
  y -= 24;

  page.drawText(`Relatório de Conferência - ${dados.tituloPeriodo}`, {
    x: MARGEM,
    y,
    size: 12,
    font: fonte,
    color: cores.cinza,
  });
  y -= 20;

  page.drawLine({
    start: { x: MARGEM, y },
    end: { x: LARGURA_PAGINA - MARGEM, y },
    thickness: 1,
    color: cores.linha,
  });
  y -= 24;

  return y;
}

function desenharResumo(
  page: PDFPage,
  fonte: PDFFont,
  dados: DadosRelatorio,
  yInicial: number,
  cores: PaletaCores,
): number {
  let y = yInicial;
  const linhasResumo = [
    `Total de membros: ${dados.totalMembros}`,
    `Em dia: ${dados.totalEmDia}`,
    `Pendentes: ${dados.totalPendentes}`,
    `Valor arrecadado no periodo: ${formatarMoedaPdf(dados.valorArrecadadoNoPeriodo)}`,
  ];

  for (const linha of linhasResumo) {
    page.drawText(linha, { x: MARGEM, y, size: 11, font: fonte, color: cores.preto });
    y -= 18;
  }

  return y;
}

/**
 * Desenha a tabela de membros, criando páginas adicionais automaticamente
 * se a lista não couber em uma única página A4.
 */
function desenharTabelaMembros(
  pdfDoc: PDFDocumentType,
  paginaInicial: PDFPage,
  fonte: PDFFont,
  dados: DadosRelatorio,
  yInicial: number,
  cores: PaletaCores,
): { page: PDFPage; cursorY: number } {
  let page = paginaInicial;
  let y = yInicial;

  const ALTURA_LINHA = 22;
  const Y_MINIMO = MARGEM + 40; // deixa espaço para o rodapé

  const colunaApelido = MARGEM;
  const colunaNome = MARGEM + 110;
  const colunaStatus = LARGURA_PAGINA - MARGEM - 150;

  function desenharCabecalhoTabela() {
    page.drawText("Apelido", { x: colunaApelido, y, size: 10, font: fonte, color: cores.cinza });
    page.drawText("Nome", { x: colunaNome, y, size: 10, font: fonte, color: cores.cinza });
    page.drawText("Status", { x: colunaStatus, y, size: 10, font: fonte, color: cores.cinza });
    y -= 8;
    page.drawLine({
      start: { x: MARGEM, y },
      end: { x: LARGURA_PAGINA - MARGEM, y },
      thickness: 0.5,
      color: cores.linha,
    });
    y -= 16;
  }

  desenharCabecalhoTabela();

  for (const linha of dados.linhas) {
    if (y < Y_MINIMO) {
      page = pdfDoc.addPage([LARGURA_PAGINA, ALTURA_PAGINA]);
      y = ALTURA_PAGINA - MARGEM;
      desenharCabecalhoTabela();
    }

    const corStatus = linha.afastado ? cores.laranja : linha.emDia ? cores.verde : cores.vermelho;
    const textoStatus = linha.afastado
      ? linha.mesesDevidos > 0
        ? `Afastado - Deve ${linha.mesesDevidos} ${linha.mesesDevidos === 1 ? "mes" : "meses"}`
        : "Afastado"
      : linha.emDia
        ? "Em dia"
        : `Pendente - ${linha.mesesDevidos} ${linha.mesesDevidos === 1 ? "mes" : "meses"}`;

    page.drawText(truncarTexto(linha.apelido, 18), {
      x: colunaApelido,
      y,
      size: 10,
      font: fonte,
      color: cores.preto,
    });
    page.drawText(truncarTexto(linha.nome, 32), {
      x: colunaNome,
      y,
      size: 10,
      font: fonte,
      color: cores.preto,
    });
    page.drawText(textoStatus, { x: colunaStatus, y, size: 10, font: fonte, color: corStatus });

    y -= ALTURA_LINHA;
  }

  return { page, cursorY: y };
}

function desenharRodape(page: PDFPage, fonte: PDFFont, dados: DadosRelatorio, cores: PaletaCores): void {
  const dataGeracao = formatarDataBR(dados.geradoEm.slice(0, 10));
  page.drawText(`Gerado em ${dataGeracao}`, {
    x: MARGEM,
    y: MARGEM - 10,
    size: 8,
    font: fonte,
    color: cores.cinza,
  });
}

/** Trunca um texto em um número máximo de caracteres, adicionando "..." se necessário. */
function truncarTexto(texto: string, maxCaracteres: number): string {
  if (texto.length <= maxCaracteres) return texto;
  return `${texto.slice(0, maxCaracteres - 1)}…`;
}

/**
 * Formata moeda para uso no PDF, evitando o espaço não-quebrável (\u00A0) que
 * Intl.NumberFormat insere por padrão entre "R$" e o valor — esse caractere não
 * tem glifo garantido em todas as fontes embutidas, e aparece como um quadrado
 * vazio em alguns visualizadores de PDF se não for substituído por espaço normal.
 */
function formatarMoedaPdf(valor: number): string {
  const formatado = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(
    valor,
  );
  return formatado.replace(/\u00A0/g, " ");
}
