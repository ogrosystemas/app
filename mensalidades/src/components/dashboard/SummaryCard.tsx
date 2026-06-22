import type { ReactNode } from "react";

export type SummaryCardTone = "neutro" | "ok" | "alerta" | "ember";

interface SummaryCardProps {
  label: string;
  valor: string;
  icon: ReactNode;
  tone?: SummaryCardTone;
}

const ESTILOS_TONE: Record<SummaryCardTone, string> = {
  neutro: "text-chrome-50",
  ok: "text-ok-400",
  alerta: "text-alert-400",
  ember: "text-ember-500",
};

/** Card individual de métrica usado no resumo do dashboard. */
export function SummaryCard({ label, valor, icon, tone = "neutro" }: SummaryCardProps) {
  return (
    <div className="flex flex-col gap-1.5 border border-graphite-700 bg-graphite-800 px-3.5 py-3">
      <div className="flex items-center justify-between">
        <span className="text-[10px] font-semibold uppercase tracking-widest2 text-graphite-400">
          {label}
        </span>
        <span className={ESTILOS_TONE[tone]}>{icon}</span>
      </div>
      <span className={`font-display text-2xl font-bold leading-none ${ESTILOS_TONE[tone]}`}>
        {valor}
      </span>
    </div>
  );
}
