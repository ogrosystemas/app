import type { ReactNode } from "react";

interface EmptyStateProps {
  icon: ReactNode;
  titulo: string;
  descricao: string;
  acao?: ReactNode;
}

/** Estado vazio padrão — usado quando não há membros cadastrados ou busca sem resultado. */
export function EmptyState({ icon, titulo, descricao, acao }: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
      <div className="text-graphite-600">{icon}</div>
      <h3 className="font-display text-base font-semibold uppercase tracking-wide text-chrome-50">
        {titulo}
      </h3>
      <p className="max-w-xs text-sm text-graphite-400">{descricao}</p>
      {acao}
    </div>
  );
}
