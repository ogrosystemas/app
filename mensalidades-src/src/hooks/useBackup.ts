import { db } from "../db/db";
import {
  baixarBackupComoArquivo,
  gerarBackup,
  importarBackup,
  lerArquivoComoTexto,
  validarBackup,
  type ResultadoImportacao,
} from "../db/backup";

export interface UseBackupResult {
  /** Gera o backup atual e dispara o download como arquivo .json. */
  exportarBackup: () => Promise<void>;

  /**
   * Lê um arquivo .json escolhido pelo usuário, valida o formato e importa, MESCLANDO
   * com os dados já existentes (nunca substitui nem apaga nada). Lança erro com mensagem
   * amigável se o arquivo não for um backup válido deste app.
   */
  importarArquivo: (arquivo: File) => Promise<ResultadoImportacao>;
}

/**
 * Hook de acesso à funcionalidade de backup/restauração — mantém toda a lógica de
 * leitura de arquivo, validação e persistência fora dos componentes de UI.
 */
export function useBackup(): UseBackupResult {
  async function exportarBackup(): Promise<void> {
    const backup = await gerarBackup(db);
    baixarBackupComoArquivo(backup);
  }

  async function importarArquivo(arquivo: File): Promise<ResultadoImportacao> {
    const texto = await lerArquivoComoTexto(arquivo);

    let conteudo: unknown;
    try {
      conteudo = JSON.parse(texto);
    } catch {
      throw new Error("Este arquivo não é um JSON válido.");
    }

    if (!validarBackup(conteudo)) {
      throw new Error("Este arquivo não parece ser um backup do Mutantes Moto Clube.");
    }

    return importarBackup(db, conteudo);
  }

  return { exportarBackup, importarArquivo };
}
