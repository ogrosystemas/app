import { onSnapshot, updateDoc } from "firebase/firestore";
import { useEffect, useState } from "react";
import { refClube } from "../db/refs";
import type { ConfigClube, EditarConfigInput } from "../types";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";

export interface UseConfigResult {
  /** Configuração atual do clube. Nunca é null após o primeiro load (há fallback). */
  config: ConfigClube;

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Atualiza um ou mais campos da configuração (ex: reajuste de mensalidade). */
  atualizarConfig: (input: EditarConfigInput) => Promise<void>;
}

const configFallback: ConfigClube = {
  nomeClube: NOME_CLUBE_PADRAO,
  valorMensalidade: VALOR_MENSALIDADE_PADRAO,
  atualizadoEm: 0,
};

/**
 * Hook de acesso à configuração geral do clube (documento único: clubes/mutantes-mc).
 * Reativo via onSnapshot do Firestore: qualquer alteração feita por este hook OU por
 * outro dispositivo conectado ao mesmo clube propaga automaticamente para todos os
 * componentes que usam este hook — inclusive offline, lendo do cache local até a
 * sincronização real acontecer.
 */
export function useConfig(): UseConfigResult {
  const [config, setConfig] = useState<ConfigClube | undefined>(undefined);

  useEffect(() => {
    const cancelarInscricao = onSnapshot(refClube(), (snapshot) => {
      setConfig(snapshot.exists() ? snapshot.data() : undefined);
    });
    return cancelarInscricao;
  }, []);

  async function atualizarConfig(input: EditarConfigInput): Promise<void> {
    await updateDoc(refClube(), {
      ...input,
      atualizadoEm: Date.now(),
    });
  }

  return {
    config: config ?? configFallback,
    carregando: config === undefined,
    atualizarConfig,
  };
}
