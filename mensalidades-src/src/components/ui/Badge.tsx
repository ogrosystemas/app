import type { ReactNode } from "react";

export type BadgeVariant = "ok" | "alerta" | "critico" | "neutro";

interface BadgeProps {
  variant: BadgeVariant;
  children: ReactNode;
  icon?: ReactNode;
}

const ESTILOS_VARIANTE: Record<BadgeVariant, string> = {
  ok: "bg-ok-950 text-ok-400 border-ok-600",
  alerta: "bg-alert-950 text-alert-400 border-alert-600",
  critico: "bg-alert-600 text-chrome-50 border-alert-400",
  neutro: "bg-graphite-800 text-graphite-200 border-graphite-600",
};

/**
 * Badge angular ("patch") usado para indicar status de pagamento.
 * O clip-path angular é a assinatura visual do app — reservado a este componente.
 */
export function Badge({ variant, children, icon }: BadgeProps) {
  return (
    <span
      className={`patch inline-flex items-center gap-1.5 border px-3 py-1 text-xs font-display font-semibold uppercase tracking-wider ${ESTILOS_VARIANTE[variant]}`}
    >
      {icon}
      {children}
    </span>
  );
}
