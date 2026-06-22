import { useLiveQuery } from "dexie-react-hooks";
import { useMemo } from "react";
import { db } from "../db/db";
import type { Competencia, Membro, Pagamento } from "../types";
import { calcularInadimplenciaMembro, type ResumoInadimplenciaMembro } from "../utils/status.utils";
import { useConfig } from "./useConfig";

export interface MembroComStatus {
  membro: Membro;
  resumo: ResumoInadimplenciaMembro;
}

export interface UseInadimplenciaResult {
  /** Lista de membros ativos cruzados com seu status de inadimplência para a competência informada. */
  membrosComStatus: MembroComStatus[];

  /** true enquanto os dados ainda não terminaram de carregar. */
  carregando: boolean;
}

/**
 * Hook central de conferência: para uma competência de referência (mês/ano selecionado no topo),
 * retorna cada membro (ativo ou afastado) já cruzado com seu resumo de inadimplência. Membros
 * afastados continuam na lista — o cálculo de pendência já considera a data de afastamento
 * internamente (ver calcularInadimplenciaMembro), então não há necessidade de filtrar por status
 * aqui: a UI decide como exibir cada caso (ex: esconder botões de cobrança para afastados).
 *
 * Mantém-se reativo: qualquer baixa registrada via usePagamentos atualiza esta lista automaticamente.
 */
export function useInadimplencia(competenciaReferencia: Competencia): UseInadimplenciaResult {
  const { config, carregando: carregandoConfig } = useConfig();

  const membros = useLiveQuery(() => db.membros.toArray(), []);

  const pagamentos = useLiveQuery(() => db.pagamentos.toArray(), []);

  const membrosComStatus = useMemo<MembroComStatus[]>(() => {
    if (!membros || !pagamentos) return [];

    const pagamentosPorMembro = agruparPagamentosPorMembro(pagamentos);

    return membros
      .filter((m): m is Membro & { id: number } => m.id !== undefined)
      .map((membro) => {
        const pagamentosDoMembro = pagamentosPorMembro.get(membro.id) ?? [];
        const resumo = calcularInadimplenciaMembro(
          membro,
          pagamentosDoMembro,
          competenciaReferencia,
          config.valorMensalidade,
        );
        return { membro, resumo };
      })
      .sort((a, b) => {
        // Pendentes primeiro (maior número de meses devidos no topo), depois em dia por apelido.
        if (a.resumo.totalMesesPendentes !== b.resumo.totalMesesPendentes) {
          return b.resumo.totalMesesPendentes - a.resumo.totalMesesPendentes;
        }
        return a.membro.apelido.localeCompare(b.membro.apelido, "pt-BR");
      });
  }, [membros, pagamentos, competenciaReferencia, config.valorMensalidade]);

  return {
    membrosComStatus,
    carregando: membros === undefined || pagamentos === undefined || carregandoConfig,
  };
}

function agruparPagamentosPorMembro(pagamentos: Pagamento[]): Map<number, Pagamento[]> {
  const mapa = new Map<number, Pagamento[]>();
  for (const p of pagamentos) {
    const lista = mapa.get(p.membroId);
    if (lista) {
      lista.push(p);
    } else {
      mapa.set(p.membroId, [p]);
    }
  }
  return mapa;
}
