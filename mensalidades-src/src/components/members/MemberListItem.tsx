import { Bell, CheckCircle2, Handshake, MoreVertical, UserX } from "lucide-react";
import type { Membro } from "../../types";
import type { ResumoInadimplenciaMembro } from "../../utils/status.utils";
import { textoBadgeStatus } from "../../utils/status.utils";
import { LIMITE_MESES_CRITICO } from "../../constants/theme.constants";
import { Badge } from "../ui/Badge";
import { Button } from "../ui/Button";

interface MemberListItemProps {
  membro: Membro;
  resumo: ResumoInadimplenciaMembro;
  /** true se o próprio membro já avisou (via área de consulta) que vai pagar alguma competência pendente. */
  temAvisoPendente: boolean;
  onDarBaixaRapida: () => void;
  onAbrirNegociacao: () => void;
  onAbrirHistorico: () => void;
  onAbrirAcoes: () => void;
}

/**
 * Item da lista de conferência. Comportamento por status:
 * - Afastado sem dívida: badge neutro "Afastado", sem botões de cobrança.
 * - Afastado COM dívida residual: badge "Afastado · Deve N mês(es)" em tom de alerta — a
 *   informação de pendência nunca fica escondida só porque o membro está afastado.
 *   Mesmo afastado, ainda mostra o botão de regularizar a dívida (Dar Baixa/Negociar),
 *   já que essa dívida é anterior ao afastamento e continua cobrável.
 * - Em dia: badge verde, sem botão de cobrança.
 * - Pendente em 1 mês (a competência selecionada): botão "Dar Baixa" direto.
 * - Pendente em 2+ meses (acumulado): badge informa quantidade, botão "Negociar" abre modal.
 * - Ícone de sino: aparece quando o próprio membro (via área de consulta restrita)
 *   avisou informalmente que vai pagar alguma competência ainda pendente — é só um
 *   lembrete visual, não altera nenhum cálculo de status.
 *
 * Layout em duas linhas: nome/apelido sempre na linha de cima (nunca trunca por causa de
 * badges largos), status e ações na linha de baixo, ocupando a largura toda do card.
 *
 * O botão de menu (3 pontos) sempre abre o MemberActionsModal (editar/afastar/excluir),
 * independente do status.
 */
export function MemberListItem({
  membro,
  resumo,
  temAvisoPendente,
  onDarBaixaRapida,
  onAbrirNegociacao,
  onAbrirHistorico,
  onAbrirAcoes,
}: MemberListItemProps) {
  const afastado = membro.status === "afastado";
  const emDia = resumo.totalMesesPendentes === 0;
  const acumulado = resumo.totalMesesPendentes > 1;
  const critico = resumo.totalMesesPendentes >= LIMITE_MESES_CRITICO;

  const variantBadge = emDia ? (afastado ? "neutro" : "ok") : critico ? "critico" : "alerta";

  return (
    <li className="flex flex-col gap-2 border-b border-graphite-800 px-4 py-3.5 last:border-b-0">
      <div className="flex items-center justify-between gap-2">
        <button type="button" onClick={onAbrirHistorico} className="min-w-0 flex-1 text-left">
          <div className="flex min-w-0 flex-col">
            <span className="truncate font-display text-sm font-semibold uppercase tracking-wide text-chrome-50">
              {membro.apelido}
              {membro.patente && (
                <span className="ml-1.5 font-body text-[11px] font-normal normal-case tracking-normal text-ember-500">
                  · {membro.patente}
                </span>
              )}
            </span>
            <span className="truncate text-xs text-graphite-400">{membro.nome}</span>
          </div>
        </button>

        <button
          type="button"
          onClick={onAbrirAcoes}
          aria-label={`Mais ações para ${membro.apelido}`}
          className="shrink-0 rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
        >
          <MoreVertical size={18} />
        </button>
      </div>

      <div className="flex items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <Badge variant={variantBadge} icon={afastado ? <UserX size={12} /> : undefined}>
            {textoBadgeStatus(resumo, afastado)}
          </Badge>
          {temAvisoPendente && (
            <span title="Avisou que vai pagar" className="text-ember-500">
              <Bell size={15} />
            </span>
          )}
        </div>

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
