import { Users, CheckCircle2, AlertTriangle, Wallet } from "lucide-react";
import type { DashboardResumo } from "../../hooks/useDashboardResumo";
import { formatarMoeda } from "../../utils/currency.utils";
import { SummaryCard } from "./SummaryCard";

interface DashboardSummaryProps {
  resumo: DashboardResumo;
  carregando: boolean;
}

/** Grid 2x2 com o resumo do mês selecionado: total de membros, pagaram, pendentes, valor arrecadado. */
export function DashboardSummary({ resumo, carregando }: DashboardSummaryProps) {
  if (carregando) {
    return (
      <div className="grid grid-cols-2 gap-2.5 px-4 py-3">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="h-[72px] animate-pulse border border-graphite-700 bg-graphite-800" />
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-2.5 px-4 py-3">
      <SummaryCard
        label="Membros Ativos"
        valor={String(resumo.totalMembrosAtivos)}
        icon={<Users size={16} />}
        tone="neutro"
      />
      <SummaryCard
        label="Em Dia"
        valor={String(resumo.totalPagaramNoMes)}
        icon={<CheckCircle2 size={16} />}
        tone="ok"
      />
      <SummaryCard
        label="Pendentes"
        valor={String(resumo.totalPendentesNoMes)}
        icon={<AlertTriangle size={16} />}
        tone="alerta"
      />
      <SummaryCard
        label="Arrecadado"
        valor={formatarMoeda(resumo.valorArrecadadoNoMes)}
        icon={<Wallet size={16} />}
        tone="ember"
      />
    </div>
  );
}
