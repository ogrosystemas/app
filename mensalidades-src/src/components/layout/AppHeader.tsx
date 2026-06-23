import { Settings, Skull } from "lucide-react";
import { InstallAppButton } from "../pwa";

interface AppHeaderProps {
  nomeClube: string;
  onAbrirConfiguracoes: () => void;
}

/**
 * Cabeçalho fixo do app. Estilo "placa de moto clube": uppercase, condensado,
 * divisor de asfalto pontilhado abaixo para reforçar a identidade visual.
 */
export function AppHeader({ nomeClube, onAbrirConfiguracoes }: AppHeaderProps) {
  return (
    <header className="sticky top-0 z-30 flex items-center justify-between gap-2 border-b border-graphite-700 bg-graphite-950/95 px-4 py-3 backdrop-blur-sm">
      <div className="flex min-w-0 items-center gap-2">
        <Skull className="shrink-0 text-ember-500" size={24} strokeWidth={2} />
        <div className="flex min-w-0 flex-col leading-tight">
          <span className="truncate font-display text-sm font-bold uppercase tracking-normal text-chrome-50 sm:text-lg sm:tracking-wide">
            {nomeClube}
          </span>
          <span className="truncate text-[9px] font-medium uppercase tracking-wide text-graphite-400 sm:text-[10px] sm:tracking-widest2">
            Conferência de Mensalidades
          </span>
        </div>
      </div>

      <div className="flex shrink-0 items-center gap-2">
        <InstallAppButton />

        <button
          type="button"
          onClick={onAbrirConfiguracoes}
          aria-label="Configurações"
          className="rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
        >
          <Settings size={20} />
        </button>
      </div>
    </header>
  );
}
