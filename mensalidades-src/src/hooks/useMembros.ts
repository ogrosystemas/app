import {
  addDoc,
  deleteDoc,
  deleteField,
  getDoc,
  getDocs,
  onSnapshot,
  orderBy,
  query,
  setDoc,
  updateDoc,
  where,
  writeBatch,
} from "firebase/firestore";
import { useEffect, useState } from "react";
import { db } from "../firebase/config";
import { normalizarEmail, refAcesso, refMembro, refMembros, refPagamentos } from "../db/refs";
import type { EditarMembroInput, Membro, NovoMembroInput } from "../types";
import { competenciaAtualComoStringAnoMes, hojeISO } from "../utils/date.utils";

export interface UseMembrosResult {
  /** Lista de todos os membros cadastrados nesta sede, ordenada por apelido. Vazio enquanto carrega. */
  membros: Membro[];

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Cadastra um novo membro nesta sede. Data de ingresso é fixada como hoje. Retorna o id gerado. */
  criarMembro: (input: NovoMembroInput) => Promise<string>;

  /** Atualiza campos de um membro existente (nome, apelido). */
  editarMembro: (id: string, input: EditarMembroInput) => Promise<void>;

  /**
   * Marca o membro como afastado a partir do mês corrente: a partir desta competência
   * (inclusive) ele para de gerar novas pendências, mas qualquer dívida anterior é mantida.
   */
  afastarMembro: (id: string) => Promise<void>;

  /**
   * Reverte o afastamento, voltando o membro a "ativo". A cobrança volta a contar
   * normalmente a partir do mês corrente — os meses durante o afastamento não retroagem.
   */
  reativarMembro: (id: string) => Promise<void>;

  /** Remove definitivamente um membro e todo o seu histórico de pagamentos. */
  excluirMembro: (id: string) => Promise<void>;
}

/**
 * Hook de acesso e mutação da entidade Membro, sempre dentro de UMA sede (clubeId).
 * Toda a lógica de persistência fica aqui — os componentes de UI nunca falam com o
 * Firestore diretamente. Reativo via onSnapshot: mudanças em qualquer dispositivo
 * conectado à mesma sede refletem aqui automaticamente, inclusive offline (lendo
 * do cache local até a sincronização real acontecer).
 */
export function useMembros(clubeId: string): UseMembrosResult {
  const [membros, setMembros] = useState<Membro[] | undefined>(undefined);

  useEffect(() => {
    setMembros(undefined);
    const consulta = query(refMembros(clubeId), orderBy("apelido"));
    const cancelarInscricao = onSnapshot(consulta, (snapshot) => {
      setMembros(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, [clubeId]);

  async function criarMembro(input: NovoMembroInput): Promise<string> {
    const agora = Date.now();
    const docRef = await addDoc(refMembros(clubeId), {
      ...input,
      dataIngresso: hojeISO(),
      status: "ativo",
      criadoEm: agora,
      atualizadoEm: agora,
    });
    return docRef.id;
  }

  async function editarMembro(id: string, input: EditarMembroInput): Promise<void> {
    // Se o e-mail de acesso está sendo alterado, sincroniza a coleção "acessos"
    // (documento espelho usado pelas regras de segurança do Firestore — ver
    // firestore.rules) ANTES de gravar o membro: remove o vínculo antigo (se
    // havia um e-mail diferente) e cria o novo vínculo (se um e-mail foi informado).
    if ("emailAcesso" in input) {
      const snapshotAtual = await getDoc(refMembro(clubeId, id));
      const emailAntigo = snapshotAtual.exists() ? snapshotAtual.data().emailAcesso : undefined;
      const emailNovo = input.emailAcesso;

      if (emailAntigo && normalizarEmail(emailAntigo) !== (emailNovo ? normalizarEmail(emailNovo) : undefined)) {
        await deleteDoc(refAcesso(emailAntigo));
      }

      if (emailNovo) {
        await setDoc(refAcesso(emailNovo), { clubeId, membroId: id });
      }
    }

    await updateDoc(refMembro(clubeId, id), {
      ...input,
      atualizadoEm: Date.now(),
    });
  }

  async function afastarMembro(id: string): Promise<void> {
    await updateDoc(refMembro(clubeId, id), {
      status: "afastado",
      competenciaAfastamento: competenciaAtualComoStringAnoMes(),
      atualizadoEm: Date.now(),
    });
  }

  async function reativarMembro(id: string): Promise<void> {
    await updateDoc(refMembro(clubeId, id), {
      status: "ativo",
      // deleteField() remove o campo do documento por completo no Firestore — diferente
      // de setar undefined, que não tem efeito (o SDK ignora campos undefined em updateDoc).
      competenciaAfastamento: deleteField(),
      atualizadoEm: Date.now(),
    });
  }

  async function excluirMembro(id: string): Promise<void> {
    // Antes de excluir, busca o membro para saber se há um e-mail de acesso
    // vinculado — se houver, o documento espelho em "acessos" também precisa
    // ser removido, senão ficaria "órfão" apontando para um membroId inexistente.
    const snapshotMembro = await getDoc(refMembro(clubeId, id));
    const emailVinculado = snapshotMembro.exists() ? snapshotMembro.data().emailAcesso : undefined;

    // writeBatch garante que a exclusão do membro e de todos os pagamentos dele
    // acontece atomicamente (tudo ou nada) — equivalente à transação que existia
    // na versão com Dexie.
    const pagamentosDoMembro = await getDocs(
      query(refPagamentos(clubeId), where("membroId", "==", id)),
    );

    const lote = writeBatch(db);
    for (const docPagamento of pagamentosDoMembro.docs) {
      lote.delete(docPagamento.ref);
    }
    if (emailVinculado) {
      lote.delete(refAcesso(emailVinculado));
    }
    lote.delete(refMembro(clubeId, id));
    await lote.commit();
  }

  return {
    membros: membros ?? [],
    carregando: membros === undefined,
    criarMembro,
    editarMembro,
    afastarMembro,
    reativarMembro,
    excluirMembro,
  };
}
