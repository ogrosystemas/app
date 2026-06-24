import { onSnapshot } from "firebase/firestore";
import { useEffect, useMemo, useState } from "react";
import { refPagamentos } from "../db/refs";
import type { Competencia, Pagamento } from "../types";
import { competenciaDeDataISO, compararCompetencias } from "../utils/date.utils";
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
 *
 * "Arrecadado" é uma métrica de CAIXA, não de competência: soma o valor de TODOS os
 * pagamentos cuja `dataPagamento` cai dentro do mês/ano selecionado no topo, não importa
 * de qual competência (mês cobrado) cada pagamento se refere. Isso é intencional — uma
 * negociação que quita um mês antigo + o mês atual deve aparecer inteira no caixa do mês
 * em que o dinheiro realmente entrou, e não ter parte do valor "voltando" para um mês
 * passado. Já "Em Dia"/"Pendentes" continuam olhando para a competência (quem deve o quê),
 * que é tratado por useInadimplencia.
 *
 * Membros afastados são excluídos de "Membros Ativos"/"Em Dia"/"Pendentes" a partir do mês
 * em que se afastaram, mas continuam contabilizados normalmente em meses anteriores a isso.
 */
export function useDashboardResumo(
  clubeId: string,
  competenciaReferencia: Competencia,
): UseDashboardResumoResult {
  const { config, carregando: carregandoConfig } = useConfig(clubeId);
  const { membrosComStatus, carregando: carregandoInadimplencia } =
    useInadimplencia(clubeId, competenciaReferencia);

  const [pagamentos, setPagamentos] = useState<Pagamento[] | undefined>(undefined);

  useEffect(() => {
    setPagamentos(undefined);
    const cancelarInscricao = onSnapshot(refPagamentos(clubeId), (snapshot) => {
      setPagamentos(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, [clubeId]);

  const resumo = useMemo<DashboardResumo>(() => {
    const membrosSujeitosACobranca = membrosComStatus.filter(({ membro }) =>
      membroEstaSujeitoACobranca(membro, competenciaReferencia),
    );

    const totalMembrosAtivos = membrosSujeitosACobranca.length;

    let totalPagaramNoMes = 0;
    for (const { resumo: r } of membrosSujeitosACobranca) {
      if (r.competenciaReferenciaPaga) totalPagaramNoMes += 1;
    }

    // Arrecadado: soma de todo pagamento cuja dataPagamento cai no mês/ano selecionado,
    // independentemente da competência (mes/ano da mensalidade) a que ele se refere.
    let valorArrecadadoNoMes = 0;
    for (const p of pagamentos ?? []) {
      const competenciaDoPagamento = competenciaDeDataISO(p.dataPagamento);
      if (compararCompetencias(competenciaDoPagamento, competenciaReferencia) === 0) {
        valorArrecadadoNoMes += p.valorPago;
      }
    }

    return {
      totalMembrosAtivos,
      totalPagaramNoMes,
      totalPendentesNoMes: totalMembrosAtivos - totalPagaramNoMes,
      valorArrecadadoNoMes,
      valorEsperadoNoMes: totalMembrosAtivos * config.valorMensalidade,
    };
  }, [membrosComStatus, pagamentos, competenciaReferencia, config.valorMensalidade]);

  return {
    resumo,
    carregando: carregandoConfig || carregandoInadimplencia || pagamentos === undefined,
  };
}
