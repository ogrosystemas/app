import { useMemo } from "react";
import type { Competencia } from "../types";
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
 */
export function useDashboardResumo(competenciaReferencia: Competencia): UseDashboardResumoResult {
  const { config, carregando: carregandoConfig } = useConfig();
  const { membrosComStatus, carregando: carregandoInadimplencia } =
    useInadimplencia(competenciaReferencia);

  const resumo = useMemo<DashboardResumo>(() => {
    const totalMembrosAtivos = membrosComStatus.length;

    let totalPagaramNoMes = 0;
    let valorArrecadadoNoMes = 0;

    for (const { resumo: r } of membrosComStatus) {
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
  }, [membrosComStatus, config.valorMensalidade]);

  return {
    resumo,
    carregando: carregandoConfig || carregandoInadimplencia,
  };
}
