import { onSnapshot } from "firebase/firestore";
import { useEffect, useMemo, useState } from "react";
import { indiceDaPatente } from "../constants/patentes.constants";
import { refMembros, refPagamentos } from "../db/refs";
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
 * Mantém-se reativo via onSnapshot do Firestore: qualquer baixa registrada via usePagamentos
 * (deste dispositivo OU de outro conectado ao mesmo clube) atualiza esta lista automaticamente.
 */
export function useInadimplencia(competenciaReferencia: Competencia): UseInadimplenciaResult {
  const { config, carregando: carregandoConfig } = useConfig();

  const [membros, setMembros] = useState<Membro[] | undefined>(undefined);
  const [pagamentos, setPagamentos] = useState<Pagamento[] | undefined>(undefined);

  useEffect(() => {
    const cancelarMembros = onSnapshot(refMembros(), (snapshot) => {
      setMembros(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarMembros;
  }, []);

  useEffect(() => {
    const cancelarPagamentos = onSnapshot(refPagamentos(), (snapshot) => {
      setPagamentos(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarPagamentos;
  }, []);

  const membrosComStatus = useMemo<MembroComStatus[]>(() => {
    if (!membros || !pagamentos) return [];

    const pagamentosPorMembro = agruparPagamentosPorMembro(pagamentos);

    return membros
      .filter((m): m is Membro & { id: string } => m.id !== undefined)
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
        // 1º critério: pendentes primeiro (maior número de meses devidos no topo) —
        // nunca esconder inadimplência atrás de outro critério de ordenação.
        if (a.resumo.totalMesesPendentes !== b.resumo.totalMesesPendentes) {
          return b.resumo.totalMesesPendentes - a.resumo.totalMesesPendentes;
        }
        // 2º critério (desempate): patente mais alta na hierarquia primeiro.
        const indicePatenteA = indiceDaPatente(a.membro.patente);
        const indicePatenteB = indiceDaPatente(b.membro.patente);
        if (indicePatenteA !== indicePatenteB) {
          // Patentes não encontradas na lista (-1) vão para o final, depois de
          // qualquer patente reconhecida.
          if (indicePatenteA === -1) return 1;
          if (indicePatenteB === -1) return -1;
          return indicePatenteA - indicePatenteB;
        }
        // 3º critério (desempate final): apelido em ordem alfabética.
        return a.membro.apelido.localeCompare(b.membro.apelido, "pt-BR");
      });
  }, [membros, pagamentos, competenciaReferencia, config.valorMensalidade]);

  return {
    membrosComStatus,
    carregando: membros === undefined || pagamentos === undefined || carregandoConfig,
  };
}

function agruparPagamentosPorMembro(pagamentos: Pagamento[]): Map<string, Pagamento[]> {
  const mapa = new Map<string, Pagamento[]>();
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
