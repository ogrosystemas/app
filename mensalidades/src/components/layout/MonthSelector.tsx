import { ChevronLeft, ChevronRight } from "lucide-react";
import type { Competencia } from "../../types";
import { competenciaAnterior, formatarCompetencia, proximaCompetencia } from "../../utils/date.utils";

interface MonthSelectorProps {
  competencia: Competencia;
  onAlterar: (nova: Competencia) => void;
}

/** Seletor de mês/ano de referência para a conferência — navega competência a competência. */
export function MonthSelector({ competencia, onAlterar }: MonthSelectorProps) {
  return (
    <div className="flex items-center justify-between gap-2 bg-graphite-800 px-3 py-2">
      <button
        type="button"
        onClick={() => onAlterar(competenciaAnterior(competencia))}
        aria-label="Mês anterior"
        className="rounded-sm p-2 text-graphite-200 hover:bg-graphite-700 active:bg-graphite-600"
      >
        <ChevronLeft size={20} />
      </button>

      <span className="font-display text-base font-semibold uppercase tracking-widest2 text-chrome-50">
        {formatarCompetencia(competencia)}
      </span>

      <button
        type="button"
        onClick={() => onAlterar(proximaCompetencia(competencia))}
        aria-label="Próximo mês"
        className="rounded-sm p-2 text-graphite-200 hover:bg-graphite-700 active:bg-graphite-600"
      >
        <ChevronRight size={20} />
      </button>
    </div>
  );
}
