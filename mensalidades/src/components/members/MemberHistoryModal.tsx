import { CheckCircle2, XCircle } from "lucide-react";
import { useMemo } from "react";
import { usePagamentosDoMembro } from "../../hooks/usePagamentos";
import type { Competencia, Membro } from "../../types";
import { formatarMoeda } from "../../utils/currency.utils";
import {
  competenciaAtual,
  competenciaDeDataISO,
  formatarCompetencia,
  formatarDataBR,
  gerarIntervaloCompetencias,
} from "../../utils/date.utils";
import { chaveCompetencia } from "../../utils/status.utils";
import { Modal } from "../ui/Modal";

interface MemberHistoryModalProps {
  aberto: boolean;
  membro?: Membro;
  onFechar: () => void;
}

/**
 * Modal de histórico: lista todas as competências desde o ingresso até o mês atual,
 * marcando visualmente quais foram pagas e quais estão pendentes.
 */
export function MemberHistoryModal({ aberto, membro, onFechar }: MemberHistoryModalProps) {
  const pagamentos = usePagamentosDoMembro(membro?.id);

  const linhas = useMemo(() => {
    if (!membro) return [];

    const ingresso = competenciaDeDataISO(membro.dataIngresso);
    const todas = gerarIntervaloCompetencias(ingresso, competenciaAtual());
    const pagamentosPorChave = new Map(pagamentos.map((p) => [chaveCompetencia(p), p]));

    return todas
      .slice()
      .reverse() // mais recente primeiro
      .map((competencia: Competencia) => ({
        competencia,
        pagamento: pagamentosPorChave.get(chaveCompetencia(competencia)),
      }));
  }, [membro, pagamentos]);

  return (
    <Modal aberto={aberto} onFechar={onFechar} titulo={membro ? `Histórico — ${membro.apelido}` : "Histórico"}>
      {linhas.length === 0 ? (
        <p className="py-8 text-center text-sm text-graphite-400">
          Nenhuma competência registrada ainda.
        </p>
      ) : (
        <ul className="flex flex-col">
          {linhas.map(({ competencia, pagamento }) => (
            <li
              key={chaveCompetencia(competencia)}
              className="flex items-center justify-between gap-3 border-b border-graphite-800 py-3 last:border-b-0"
            >
              <div className="flex items-center gap-2.5">
                {pagamento ? (
                  <CheckCircle2 className="text-ok-500" size={18} />
                ) : (
                  <XCircle className="text-alert-500" size={18} />
                )}
                <span className="font-display text-sm font-medium uppercase tracking-wide text-chrome-50">
                  {formatarCompetencia(competencia)}
                </span>
              </div>

              {pagamento ? (
                <div className="text-right">
                  <p className="text-sm text-ok-400">{formatarMoeda(pagamento.valorPago)}</p>
                  <p className="text-[11px] text-graphite-400">
                    {formatarDataBR(pagamento.dataPagamento)}
                  </p>
                </div>
              ) : (
                <span className="text-xs font-semibold uppercase tracking-wide text-alert-500">
                  Pendente
                </span>
              )}
            </li>
          ))}
        </ul>
      )}
    </Modal>
  );
}
