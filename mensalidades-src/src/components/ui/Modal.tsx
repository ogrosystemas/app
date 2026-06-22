import { useEffect, type ReactNode } from "react";
import { X } from "lucide-react";

interface ModalProps {
  aberto: boolean;
  onFechar: () => void;
  titulo: string;
  children: ReactNode;
  /** Conteúdo fixo no rodapé (ex: botões de ação), fora da área de scroll. */
  rodape?: ReactNode;
}

/**
 * Modal base do app — bottom-sheet em mobile (slide-up), centralizado em telas largas.
 * Fecha ao clicar no overlay ou pressionar Esc.
 */
export function Modal({ aberto, onFechar, titulo, children, rodape }: ModalProps) {
  useEffect(() => {
    if (!aberto) return;

    function handleKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") onFechar();
    }
    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [aberto, onFechar]);

  if (!aberto) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center">
      {/* Overlay */}
      <button
        type="button"
        aria-label="Fechar"
        className="absolute inset-0 bg-black/70"
        onClick={onFechar}
      />

      {/* Conteúdo */}
      <div className="relative z-10 flex max-h-[90vh] w-full flex-col border-t border-graphite-700 bg-graphite-900 shadow-patch sm:max-h-[85vh] sm:w-[480px] sm:rounded-sm sm:border">
        <div className="flex items-center justify-between border-b border-graphite-700 px-5 py-4">
          <h2 className="font-display text-lg font-semibold uppercase tracking-wide text-chrome-50">
            {titulo}
          </h2>
          <button
            type="button"
            onClick={onFechar}
            aria-label="Fechar modal"
            className="rounded-sm p-1 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
          >
            <X size={20} />
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-5 py-4">{children}</div>

        {rodape && <div className="border-t border-graphite-700 px-5 py-4">{rodape}</div>}
      </div>
    </div>
  );
}
