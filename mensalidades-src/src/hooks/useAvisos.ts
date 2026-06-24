import { addDoc, deleteDoc, onSnapshot, query, where } from "firebase/firestore";
import { useEffect, useState } from "react";
import { refAviso, refAvisos } from "../db/refs";
import type { AvisoPagamento, Competencia } from "../types";
import { chaveCompetencia } from "../utils/status.utils";

export interface UseAvisosResult {
  /** Registra um aviso informal de que o membro pretende pagar essa competência. */
  enviarAviso: (membroId: string, competencia: Competencia) => Promise<void>;

  /** Remove um aviso (ex: depois que a baixa real foi registrada, ou se foi engano). */
  removerAviso: (avisoId: string) => Promise<void>;
}

/**
 * Hook de mutação de avisos informais de pagamento, dentro de UMA sede (clubeId).
 * A leitura reativa (para o admin ver todos os avisos pendentes) é feita por
 * useAvisosDoClube; este hook cuida só de criar/remover, usado tanto pela área do
 * administrador quanto pela área restrita do integrante (que só tem permissão de
 * criar avisos do próprio membroId, conforme as regras de segurança do Firestore).
 */
export function useAvisos(clubeId: string): UseAvisosResult {
  async function enviarAviso(membroId: string, competencia: Competencia): Promise<void> {
    await addDoc(refAvisos(clubeId), {
      membroId,
      mes: competencia.mes,
      ano: competencia.ano,
      criadoEm: Date.now(),
    });
  }

  async function removerAviso(avisoId: string): Promise<void> {
    await deleteDoc(refAviso(clubeId, avisoId));
  }

  return { enviarAviso, removerAviso };
}

/** Hook reativo: todos os avisos de pagamento de uma sede (visão do administrador dela). */
export function useAvisosDoClube(clubeId: string): AvisoPagamento[] {
  const [avisos, setAvisos] = useState<AvisoPagamento[]>([]);

  useEffect(() => {
    const cancelarInscricao = onSnapshot(refAvisos(clubeId), (snapshot) => {
      setAvisos(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, [clubeId]);

  return avisos;
}

/** Hook reativo: avisos de pagamento de um membro específico, dentro de uma sede (visão do próprio integrante). */
export function useAvisosDoMembro(clubeId: string, membroId: string | undefined): AvisoPagamento[] {
  const [avisos, setAvisos] = useState<AvisoPagamento[]>([]);

  useEffect(() => {
    if (membroId === undefined) {
      setAvisos([]);
      return;
    }
    const consulta = query(refAvisos(clubeId), where("membroId", "==", membroId));
    const cancelarInscricao = onSnapshot(consulta, (snapshot) => {
      setAvisos(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, [clubeId, membroId]);

  return avisos;
}

/** Verifica se já existe um aviso para uma competência específica dentro de uma lista de avisos. */
export function jaAvisouCompetencia(avisos: AvisoPagamento[], competencia: Competencia): boolean {
  const chave = chaveCompetencia(competencia);
  return avisos.some((a) => chaveCompetencia(a) === chave);
}
