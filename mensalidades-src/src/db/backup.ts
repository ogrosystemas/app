import { doc, getDoc, getDocs, writeBatch } from "firebase/firestore";
import { db } from "../firebase/config";
import { refClube, refMembros, refPagamento, refPagamentos } from "./refs";
import type { ConfigClube, Membro, Pagamento } from "../types";

/**
 * Formato do arquivo de backup exportado. Versionado desde já (campo `versao`) para
 * permitir migração de formato no futuro sem quebrar backups antigos que o usuário
 * ainda tenha guardados. Sempre referente a UMA sede (clubeId) — backups nunca
 * misturam dados de sedes diferentes.
 */
export interface BackupMutantesMC {
  versao: 1;
  geradoEm: string; // ISO 8601
  nomeClube: string;
  config: ConfigClube;
  membros: Membro[]; // inclui o id original (usado só para casar com pagamentos no próprio arquivo)
  pagamentos: Pagamento[];
}

/** Gera o objeto de backup completo de UMA sede a partir do estado atual do banco (Firestore). */
export async function gerarBackup(clubeId: string): Promise<BackupMutantesMC> {
  const [configSnapshot, membrosSnapshot, pagamentosSnapshot] = await Promise.all([
    getDoc(refClube(clubeId)),
    getDocs(refMembros(clubeId)),
    getDocs(refPagamentos(clubeId)),
  ]);

  if (!configSnapshot.exists()) {
    throw new Error("Configuração da sede não encontrada — não é possível gerar backup.");
  }
  const config = configSnapshot.data();

  return {
    versao: 1,
    geradoEm: new Date().toISOString(),
    nomeClube: config.nomeClube,
    config,
    membros: membrosSnapshot.docs.map((d) => ({ ...d.data(), id: d.id })),
    pagamentos: pagamentosSnapshot.docs.map((d) => ({ ...d.data(), id: d.id })),
  };
}

/** Dispara o download do backup como arquivo .json no navegador. */
export function baixarBackupComoArquivo(backup: BackupMutantesMC): void {
  const conteudo = JSON.stringify(backup, null, 2);
  const blob = new Blob([conteudo], { type: "application/json" });
  const url = URL.createObjectURL(blob);

  const dataFormatada = backup.geradoEm.slice(0, 10); // YYYY-MM-DD
  const nomeArquivo = `backup-${normalizarNomeArquivo(backup.nomeClube)}-${dataFormatada}.json`;

  const link = document.createElement("a");
  link.href = url;
  link.download = nomeArquivo;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function normalizarNomeArquivo(texto: string): string {
  return texto
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
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
 * Importa um backup MESCLANDO com os dados já existentes de UMA sede (clubeId) no
 * Firestore — nunca substitui ou apaga nada que já estava lá, e nunca mistura dados
 * com outra sede. Regras de deduplicação:
 *
 * - Membro: considerado "já existente" se já houver um membro com o mesmo par
 *   (nome, apelido), dentro da MESMA sede — comparação exata, sem normalização de
 *   acentos/maiúsculas, para evitar mesclar membros que o usuário realmente
 *   cadastrou como diferentes. Membros novos do backup recebem um NOVO id de
 *   documento gerado pelo Firestore (nunca reaproveita o id do arquivo, que pode
 *   colidir com ids já usados na nuvem, já que vieram de outro dispositivo/sessão).
 * - Pagamento: a checagem de duplicidade usa o mesmo esquema de ID determinístico
 *   (`membroId_ano_mes`) usado por usePagamentos — um pagamento é "já existente" se
 *   já houver um documento com esse ID composto dentro da mesma sede no Firestore.
 */
export async function importarBackup(clubeId: string, backup: BackupMutantesMC): Promise<ResultadoImportacao> {
  const resultado: ResultadoImportacao = {
    membrosAdicionados: 0,
    membrosJaExistentes: 0,
    pagamentosAdicionados: 0,
    pagamentosJaExistentes: 0,
  };

  const membrosAtuaisSnapshot = await getDocs(refMembros(clubeId));
  const membrosAtuais = membrosAtuaisSnapshot.docs.map((d) => ({ ...d.data(), id: d.id }));

  // Mapa: id do membro NO ARQUIVO DE BACKUP -> id do membro NO FIRESTORE (novo ou já existente).
  const mapaIdBackupParaIdReal = new Map<string, string>();

  const loteMembros = writeBatch(db);
  let pendentesNoLoteMembros = 0;

  for (const membroBackup of backup.membros) {
    const jaExiste = membrosAtuais.find(
      (m) => m.nome === membroBackup.nome && m.apelido === membroBackup.apelido,
    );

    if (jaExiste) {
      resultado.membrosJaExistentes++;
      if (membroBackup.id !== undefined && jaExiste.id !== undefined) {
        mapaIdBackupParaIdReal.set(membroBackup.id, jaExiste.id);
      }
      continue;
    }

    const { id: idAntigo, ...dadosMembro } = membroBackup;
    const novoRef = doc(refMembros(clubeId)); // gera um novo ID de documento, sem reaproveitar o antigo
    loteMembros.set(novoRef, dadosMembro);
    pendentesNoLoteMembros++;
    resultado.membrosAdicionados++;
    if (idAntigo !== undefined) {
      mapaIdBackupParaIdReal.set(idAntigo, novoRef.id);
    }
  }

  if (pendentesNoLoteMembros > 0) {
    await loteMembros.commit();
  }

  const lotePagamentos = writeBatch(db);
  let pendentesNoLotePagamentos = 0;

  for (const pagamentoBackup of backup.pagamentos) {
    const idRealDoMembro = mapaIdBackupParaIdReal.get(pagamentoBackup.membroId);
    if (idRealDoMembro === undefined) {
      // Pagamento referencia um membro que não pôde ser resolvido (dado inconsistente
      // no arquivo) — pula esse registro em vez de falhar a importação inteira.
      continue;
    }

    const idDeterministico = `${idRealDoMembro}_${pagamentoBackup.ano}_${pagamentoBackup.mes}`;
    const refPagamentoExistente = refPagamento(clubeId, idDeterministico);
    const snapshot = await getDoc(refPagamentoExistente);

    if (snapshot.exists()) {
      resultado.pagamentosJaExistentes++;
      continue;
    }

    const { id: _idAntigo, ...dadosPagamento } = pagamentoBackup;
    lotePagamentos.set(refPagamentoExistente, { ...dadosPagamento, membroId: idRealDoMembro });
    pendentesNoLotePagamentos++;
    resultado.pagamentosAdicionados++;
  }

  if (pendentesNoLotePagamentos > 0) {
    await lotePagamentos.commit();
  }

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
