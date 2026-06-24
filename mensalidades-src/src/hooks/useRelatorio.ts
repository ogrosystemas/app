import { getDoc, getDocs } from "firebase/firestore";
import { refClube, refMembros, refPagamentos } from "../db/refs";
import type { FiltroRelatorio } from "../utils/relatorio.utils";
import { gerarDadosRelatorio } from "../utils/relatorio.utils";
import { gerarPdfRelatorio } from "../utils/pdf-relatorio.utils";

export interface UseRelatorioResult {
  /** Gera o PDF do relatório para o filtro informado e dispara o download no navegador. */
  gerarEBaixarRelatorio: (filtro: FiltroRelatorio) => Promise<void>;
}

/**
 * Hook de geração de relatórios em PDF. Busca os dados atuais do Firestore (membros,
 * pagamentos, config) e delega o cálculo para relatorio.utils e a montagem do PDF
 * para pdf-relatorio.utils — mantém os componentes de UI livres dessa lógica.
 */
export function useRelatorio(clubeId: string): UseRelatorioResult {
  async function gerarEBaixarRelatorio(filtro: FiltroRelatorio): Promise<void> {
    const [configSnapshot, membrosSnapshot, pagamentosSnapshot] = await Promise.all([
      getDoc(refClube(clubeId)),
      getDocs(refMembros(clubeId)),
      getDocs(refPagamentos(clubeId)),
    ]);

    if (!configSnapshot.exists()) {
      throw new Error("Configuração do clube não encontrada.");
    }
    const config = configSnapshot.data();
    const membros = membrosSnapshot.docs.map((d) => ({ ...d.data(), id: d.id }));
    const pagamentos = pagamentosSnapshot.docs.map((d) => ({ ...d.data(), id: d.id }));

    const dados = gerarDadosRelatorio(
      config.nomeClube,
      filtro,
      membros,
      pagamentos,
      config.valorMensalidade,
    );

    const pdfBytes = await gerarPdfRelatorio(dados);
    baixarPdfComoArquivo(pdfBytes, dados.tituloPeriodo);
  }

  return { gerarEBaixarRelatorio };
}

function baixarPdfComoArquivo(pdfBytes: Uint8Array, tituloPeriodo: string): void {
  const nomeArquivo = `relatorio-${normalizarNomeArquivo(tituloPeriodo)}.pdf`;
  // Cópia para um ArrayBuffer "puro": o tipo de retorno de pdfDoc.save() inclui
  // SharedArrayBuffer como possibilidade no genérico do Uint8Array, que o construtor
  // de Blob não aceita sob TypeScript estrito — copiar os bytes resolve a incompatibilidade.
  const bytesPuros = new Uint8Array(pdfBytes);
  const blob = new Blob([bytesPuros], { type: "application/pdf" });
  const url = URL.createObjectURL(blob);

  const link = document.createElement("a");
  link.href = url;
  link.download = nomeArquivo;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/** Converte um título de período (ex: "Junho/2026") em um nome de arquivo seguro. */
function normalizarNomeArquivo(titulo: string): string {
  return titulo
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // remove acentos
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}
