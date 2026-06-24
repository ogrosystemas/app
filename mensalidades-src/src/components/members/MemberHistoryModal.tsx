import { CheckCircle2, Pencil, XCircle } from "lucide-react";
import { useMemo } from "react";
import { usePagamentosDoMembro } from "../../hooks/usePagamentos";
import type { Competencia, Membro, Pagamento } from "../../types";
import { formatarMoeda } from "../../utils/currency.utils";
import { competenciaAtual, formatarCompetencia, formatarDataBR } from "../../utils/date.utils";
import { chaveCompetencia, gerarCompetenciasEsperadasHistorico } from "../../utils/status.utils";
import { Modal } from "../ui/Modal";

interface MemberHistoryModalProps {
  clubeId: string;
  aberto: boolean;
  membro?: Membro;
  onFechar: () => void;
  onEditarPagamento: (pagamento: Pagamento, competencia: Competencia) => void;
}

/**
 * Modal de histórico: lista todas as competências de cobrança esperadas (respeitando o
 * ciclo anual de cada ano, desde o ano de ingresso até o ano atual), marcando visualmente
 * quais foram pagas e quais estão pendentes — inclui anos anteriores (histórico multi-ano).
 * Linhas pagas são clicáveis e abrem o modal de edição/estorno daquele pagamento.
 */
export function MemberHistoryModal({ clubeId, aberto, membro, onFechar, onEditarPagamento }: MemberHistoryModalProps) {
  const pagamentos = usePagamentosDoMembro(clubeId, membro?.id);

  const linhas = useMemo(() => {
    if (!membro) return [];

    const todas = gerarCompetenciasEsperadasHistorico(membro, competenciaAtual());
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
              className="border-b border-graphite-800 last:border-b-0"
            >
              {pagamento ? (
                <button
                  type="button"
                  onClick={() => onEditarPagamento(pagamento, competencia)}
                  className="flex w-full items-center justify-between gap-3 py-3 text-left hover:bg-graphite-800"
                >
                  <div className="flex items-center gap-2.5">
                    <CheckCircle2 className="text-ok-500" size={18} />
                    <span className="font-display text-sm font-medium uppercase tracking-wide text-chrome-50">
                      {formatarCompetencia(competencia)}
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    <div className="text-right">
                      <p className="text-sm text-ok-400">{formatarMoeda(pagamento.valorPago)}</p>
                      <p className="text-[11px] text-graphite-400">
                        {formatarDataBR(pagamento.dataPagamento)}
                      </p>
                    </div>
                    <Pencil className="text-graphite-500" size={14} />
                  </div>
                </button>
              ) : (
                <div className="flex items-center justify-between gap-3 py-3">
                  <div className="flex items-center gap-2.5">
                    <XCircle className="text-alert-500" size={18} />
                    <span className="font-display text-sm font-medium uppercase tracking-wide text-chrome-50">
                      {formatarCompetencia(competencia)}
                    </span>
                  </div>
                  <span className="text-xs font-semibold uppercase tracking-wide text-alert-500">
                    Pendente
                  </span>
                </div>
              )}
            </li>
          ))}
        </ul>
      )}
    </Modal>
  );
}
