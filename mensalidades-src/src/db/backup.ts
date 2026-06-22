import type { MutantesDB } from "./db";
import type { ConfigClube, Membro, Pagamento } from "../types";

/**
 * Formato do arquivo de backup exportado. Versionado desde já (campo `versao`) para
 * permitir migração de formato no futuro sem quebrar backups antigos que o usuário
 * ainda tenha guardados.
 */
export interface BackupMutantesMC {
  versao: 1;
  geradoEm: string; // ISO 8601
  nomeClube: string;
  config: Omit<ConfigClube, "id">;
  membros: Membro[]; // inclui o id original (usado só para casar com pagamentos no próprio arquivo)
  pagamentos: Pagamento[];
}

/** Gera o objeto de backup completo a partir do estado atual do banco. */
export async function gerarBackup(db: MutantesDB): Promise<BackupMutantesMC> {
  const [config, membros, pagamentos] = await Promise.all([
    db.config.get(1),
    db.membros.toArray(),
    db.pagamentos.toArray(),
  ]);

  if (!config) {
    throw new Error("Configuração do clube não encontrada — não é possível gerar backup.");
  }

  return {
    versao: 1,
    geradoEm: new Date().toISOString(),
    nomeClube: config.nomeClube,
    config: {
      nomeClube: config.nomeClube,
      valorMensalidade: config.valorMensalidade,
      atualizadoEm: config.atualizadoEm,
    },
    membros,
    pagamentos,
  };
}

/** Dispara o download do backup como arquivo .json no navegador. */
export function baixarBackupComoArquivo(backup: BackupMutantesMC): void {
  const conteudo = JSON.stringify(backup, null, 2);
  const blob = new Blob([conteudo], { type: "application/json" });
  const url = URL.createObjectURL(blob);

  const dataFormatada = backup.geradoEm.slice(0, 10); // YYYY-MM-DD
  const nomeArquivo = `backup-mutantes-mc-${dataFormatada}.json`;

  const link = document.createElement("a");
  link.href = url;
  link.download = nomeArquivo;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/**
 * Valida se um objeto arbitrário (ex: vindo de JSON.parse de um arquivo escolhido pelo
 * usuário) tem o formato mínimo esperado de um backup válido. Não confia ciegamente no
 * conteúdo de um arquivo externo.
 */
export function validarBackup(valor: unknown): valor is BackupMutantesMC {
  if (typeof valor !== "object" || valor === null) return false;
  const v = valor as Record<string, unknown>;
  return (
    v.versao === 1 &&
    typeof v.geradoEm === "string" &&
    typeof v.nomeClube === "string" &&
    typeof v.config === "object" &&
    v.config !== null &&
    Array.isArray(v.membros) &&
    Array.isArray(v.pagamentos)
  );
}

export interface ResultadoImportacao {
  membrosAdicionados: number;
  membrosJaExistentes: number;
  pagamentosAdicionados: number;
  pagamentosJaExistentes: number;
}

/**
 * Importa um backup MESCLANDO com os dados já existentes no banco — nunca substitui ou
 * apaga nada que já estava lá. Regras de deduplicação:
 *
 * - Membro: considerado "já existente" se já houver um membro com o mesmo par
 *   (nome, apelido) — comparação exata, sem normalização de acentos/maiúsculas, para
 *   evitar mesclar membros que o usuário realmente cadastrou como diferentes.
 *   Membros novos do backup recebem um NOVO id gerado pelo banco local (nunca reutiliza
 *   o id do arquivo, que pode colidir com ids já usados localmente).
 * - Pagamento: considerado "já existente" se já houver um pagamento do mesmo membro
 *   (após o remapeamento de id acima) para a mesma competência (ano+mês) — mesma regra
 *   de unicidade que `usePagamentos` já aplica ao dar baixa.
 */
export async function importarBackup(
  db: MutantesDB,
  backup: BackupMutantesMC,
): Promise<ResultadoImportacao> {
  const resultado: ResultadoImportacao = {
    membrosAdicionados: 0,
    membrosJaExistentes: 0,
    pagamentosAdicionados: 0,
    pagamentosJaExistentes: 0,
  };

  await db.transaction("rw", db.membros, db.pagamentos, async () => {
    const membrosAtuais = await db.membros.toArray();

    // Mapa: id do membro NO ARQUIVO DE BACKUP -> id do membro NO BANCO LOCAL (novo ou já existente).
    const mapaIdBackupParaIdLocal = new Map<number, number>();

    for (const membroBackup of backup.membros) {
      const jaExiste = membrosAtuais.find(
        (m) => m.nome === membroBackup.nome && m.apelido === membroBackup.apelido,
      );

      if (jaExiste) {
        resultado.membrosJaExistentes++;
        if (membroBackup.id !== undefined && jaExiste.id !== undefined) {
          mapaIdBackupParaIdLocal.set(membroBackup.id, jaExiste.id);
        }
        continue;
      }

      const { id: _idAntigo, ...dadosMembro } = membroBackup;
      const novoId = (await db.membros.add(dadosMembro)) as number;
      resultado.membrosAdicionados++;
      if (membroBackup.id !== undefined) {
        mapaIdBackupParaIdLocal.set(membroBackup.id, novoId);
      }
    }

    for (const pagamentoBackup of backup.pagamentos) {
      const idLocalDoMembro = mapaIdBackupParaIdLocal.get(pagamentoBackup.membroId);
      if (idLocalDoMembro === undefined) {
        // Pagamento referencia um membro que não pôde ser resolvido (dado inconsistente
        // no arquivo) — pula esse registro em vez de falhar a importação inteira.
        continue;
      }

      const jaExiste = await db.pagamentos
        .where("[membroId+ano+mes]")
        .equals([idLocalDoMembro, pagamentoBackup.ano, pagamentoBackup.mes])
        .first();

      if (jaExiste) {
        resultado.pagamentosJaExistentes++;
        continue;
      }

      const { id: _idAntigo, ...dadosPagamento } = pagamentoBackup;
      await db.pagamentos.add({ ...dadosPagamento, membroId: idLocalDoMembro });
      resultado.pagamentosAdicionados++;
    }
  });

  return resultado;
}

/** Lê um arquivo escolhido pelo usuário (input type=file) e retorna seu conteúdo como texto. */
export function lerArquivoComoTexto(arquivo: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const leitor = new FileReader();
    leitor.onload = () => resolve(String(leitor.result));
    leitor.onerror = () => reject(new Error("Não foi possível ler o arquivo."));
    leitor.readAsText(arquivo);
  });
}
