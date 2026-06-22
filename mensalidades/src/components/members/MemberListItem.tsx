import { CheckCircle2, Handshake, History } from "lucide-react";
import type { Membro } from "../../types";
import type { ResumoInadimplenciaMembro } from "../../utils/status.utils";
import { textoBadgeStatus } from "../../utils/status.utils";
import { LIMITE_MESES_CRITICO } from "../../constants/theme.constants";
import { Badge } from "../ui/Badge";
import { Button } from "../ui/Button";

interface MemberListItemProps {
  membro: Membro;
  resumo: ResumoInadimplenciaMembro;
  onDarBaixaRapida: () => void;
  onAbrirNegociacao: () => void;
  onAbrirHistorico: () => void;
}

/**
 * Item da lista de conferência. Comportamento por status:
 * - Em dia: badge verde, sem botão de ação (já está ok).
 * - Pendente em 1 mês (a competência selecionada): botão "Dar Baixa" direto.
 * - Pendente em 2+ meses (acumulado): badge informa quantidade, botão "Negociar" abre modal.
 */
export function MemberListItem({
  membro,
  resumo,
  onDarBaixaRapida,
  onAbrirNegociacao,
  onAbrirHistorico,
}: MemberListItemProps) {
  const emDia = resumo.totalMesesPendentes === 0;
  const acumulado = resumo.totalMesesPendentes > 1;
  const critico = resumo.totalMesesPendentes >= LIMITE_MESES_CRITICO;

  return (
    <li className="flex items-center justify-between gap-3 border-b border-graphite-800 px-4 py-3.5 last:border-b-0">
      <button
        type="button"
        onClick={onAbrirHistorico}
        className="flex min-w-0 flex-1 items-center gap-3 text-left"
      >
        <div className="flex min-w-0 flex-col">
          <span className="truncate font-display text-sm font-semibold uppercase tracking-wide text-chrome-50">
            {membro.apelido}
          </span>
          <span className="truncate text-xs text-graphite-400">{membro.nome}</span>
        </div>
      </button>

      <div className="flex shrink-0 items-center gap-2">
        <Badge variant={emDia ? "ok" : critico ? "critico" : "alerta"}>
          {textoBadgeStatus(resumo)}
        </Badge>

        {emDia && (
          <button
            type="button"
            onClick={onAbrirHistorico}
            aria-label="Ver histórico"
            className="rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
          >
            <History size={18} />
          </button>
        )}

        {!emDia && !acumulado && (
          <Button size="sm" variant="success" icon={<CheckCircle2 size={14} />} onClick={onDarBaixaRapida}>
            Dar Baixa
          </Button>
        )}

        {!emDia && acumulado && (
          <Button size="sm" variant="danger" icon={<Handshake size={14} />} onClick={onAbrirNegociacao}>
            Negociar
          </Button>
        )}
      </div>
    </li>
  );
}
