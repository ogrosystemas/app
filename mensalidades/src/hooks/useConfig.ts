import { useLiveQuery } from "dexie-react-hooks";
import { db } from "../db/db";
import type { ConfigClube, EditarConfigInput } from "../types";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";

const CONFIG_ID = 1;

export interface UseConfigResult {
  /** Configuração atual do clube. Nunca é null após o primeiro load (há fallback). */
  config: ConfigClube;

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Atualiza um ou mais campos da configuração (ex: reajuste de mensalidade). */
  atualizarConfig: (input: EditarConfigInput) => Promise<void>;
}

const configFallback: ConfigClube = {
  id: CONFIG_ID,
  nomeClube: NOME_CLUBE_PADRAO,
  valorMensalidade: VALOR_MENSALIDADE_PADRAO,
  atualizadoEm: 0,
};

/**
 * Hook de acesso à configuração geral do clube (singleton).
 * Reativo: qualquer alteração via `atualizarConfig` propaga automaticamente
 * para todos os componentes que usam este hook (via useLiveQuery).
 */
export function useConfig(): UseConfigResult {
  const config = useLiveQuery(() => db.config.get(CONFIG_ID), []);

  async function atualizarConfig(input: EditarConfigInput): Promise<void> {
    await db.config.update(CONFIG_ID, {
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
