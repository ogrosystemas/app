import { useMemo } from "react";
import type { Competencia } from "../types";
import { membroEstaSujeitoACobranca } from "../utils/status.utils";
import { useConfig } from "./useConfig";
import { useInadimplencia } from "./useInadimplencia";

export interface DashboardResumo {
  totalMembrosAtivos: number;
  totalPagaramNoMes: number;
  totalPendentesNoMes: number;
  valorArrecadadoNoMes: number;
  valorEsperadoNoMes: number;
}

export interface UseDashboardResumoResult {
  resumo: DashboardResumo;
  carregando: boolean;
}

/**
 * Hook do resumo exibido no topo do app, referente à competência selecionada.
 * "Pagou no mês" considera especificamente a competência de referência (não o acumulado);
 * o acumulado de meses anteriores é tratado por useInadimplencia/badge na lista.
 *
 * Membros afastados são excluídos das métricas a partir do mês em que se afastaram
 * (não entram em "Membros Ativos" nem em "Arrecadado Esperado"), mas continuam sendo
 * contabilizados normalmente em meses anteriores ao afastamento.
 */
export function useDashboardResumo(competenciaReferencia: Competencia): UseDashboardResumoResult {
  const { config, carregando: carregandoConfig } = useConfig();
  const { membrosComStatus, carregando: carregandoInadimplencia } =
    useInadimplencia(competenciaReferencia);

  const resumo = useMemo<DashboardResumo>(() => {
    const membrosSujeitosACobranca = membrosComStatus.filter(({ membro }) =>
      membroEstaSujeitoACobranca(membro, competenciaReferencia),
    );

    const totalMembrosAtivos = membrosSujeitosACobranca.length;

    let totalPagaramNoMes = 0;
    let valorArrecadadoNoMes = 0;

    for (const { resumo: r } of membrosSujeitosACobranca) {
      if (r.competenciaReferenciaPaga) {
        totalPagaramNoMes += 1;
        valorArrecadadoNoMes += config.valorMensalidade;
      }
    }

    return {
      totalMembrosAtivos,
      totalPagaramNoMes,
      totalPendentesNoMes: totalMembrosAtivos - totalPagaramNoMes,
      valorArrecadadoNoMes,
      valorEsperadoNoMes: totalMembrosAtivos * config.valorMensalidade,
    };
  }, [membrosComStatus, competenciaReferencia, config.valorMensalidade]);

  return {
    resumo,
    carregando: carregandoConfig || carregandoInadimplencia,
  };
}
