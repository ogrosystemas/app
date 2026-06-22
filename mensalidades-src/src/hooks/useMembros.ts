import {
  addDoc,
  deleteField,
  getDocs,
  onSnapshot,
  orderBy,
  query,
  updateDoc,
  where,
  writeBatch,
} from "firebase/firestore";
import { useEffect, useState } from "react";
import { db } from "../firebase/config";
import { refMembro, refMembros, refPagamentos } from "../db/refs";
import type { EditarMembroInput, Membro, NovoMembroInput } from "../types";
import { competenciaAtualComoStringAnoMes, hojeISO } from "../utils/date.utils";

export interface UseMembrosResult {
  /** Lista de todos os membros cadastrados, ordenada por apelido. Vazio enquanto carrega. */
  membros: Membro[];

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Cadastra um novo membro. Data de ingresso é fixada como hoje. Retorna o id gerado. */
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
 * Hook de acesso e mutação da entidade Membro.
 * Toda a lógica de persistência fica aqui — os componentes de UI nunca falam com o
 * Firestore diretamente. Reativo via onSnapshot: muda em qualquer dispositivo
 * conectado ao mesmo clube refletem aqui automaticamente, inclusive offline (lendo
 * do cache local até a sincronização real acontecer).
 */
export function useMembros(): UseMembrosResult {
  const [membros, setMembros] = useState<Membro[] | undefined>(undefined);

  useEffect(() => {
    const consulta = query(refMembros(), orderBy("apelido"));
    const cancelarInscricao = onSnapshot(consulta, (snapshot) => {
      setMembros(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, []);

  async function criarMembro(input: NovoMembroInput): Promise<string> {
    const agora = Date.now();
    const docRef = await addDoc(refMembros(), {
      ...input,
      dataIngresso: hojeISO(),
      status: "ativo",
      criadoEm: agora,
      atualizadoEm: agora,
    });
    return docRef.id;
  }

  async function editarMembro(id: string, input: EditarMembroInput): Promise<void> {
    await updateDoc(refMembro(id), {
      ...input,
      atualizadoEm: Date.now(),
    });
  }

  async function afastarMembro(id: string): Promise<void> {
    await updateDoc(refMembro(id), {
      status: "afastado",
      competenciaAfastamento: competenciaAtualComoStringAnoMes(),
      atualizadoEm: Date.now(),
    });
  }

  async function reativarMembro(id: string): Promise<void> {
    await updateDoc(refMembro(id), {
      status: "ativo",
      // deleteField() remove o campo do documento por completo no Firestore — diferente
      // de setar undefined, que não tem efeito (o SDK ignora campos undefined em updateDoc).
      competenciaAfastamento: deleteField(),
      atualizadoEm: Date.now(),
    });
  }

  async function excluirMembro(id: string): Promise<void> {
    // writeBatch garante que a exclusão do membro e de todos os pagamentos dele
    // acontece atomicamente (tudo ou nada) — equivalente à transação que existia
    // na versão com Dexie.
    const pagamentosDoMembro = await getDocs(
      query(refPagamentos(), where("membroId", "==", id)),
    );

    const lote = writeBatch(db);
    for (const docPagamento of pagamentosDoMembro.docs) {
      lote.delete(docPagamento.ref);
    }
    lote.delete(refMembro(id));
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
