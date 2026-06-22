import { Settings, Skull } from "lucide-react";

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
    <header className="sticky top-0 z-30 flex items-center justify-between border-b border-graphite-700 bg-graphite-950/95 px-4 py-3 backdrop-blur-sm">
      <div className="flex items-center gap-2.5">
        <Skull className="text-ember-500" size={26} strokeWidth={2} />
        <div className="flex flex-col leading-none">
          <span className="font-display text-lg font-bold uppercase tracking-widest2 text-chrome-50">
            {nomeClube}
          </span>
          <span className="text-[10px] font-medium uppercase tracking-widest2 text-graphite-400">
            Conferência de Mensalidades
          </span>
        </div>
      </div>

      <button
        type="button"
        onClick={onAbrirConfiguracoes}
        aria-label="Configurações"
        className="rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
      >
        <Settings size={20} />
      </button>
    </header>
  );
}
