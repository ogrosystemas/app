import type { ButtonHTMLAttributes, ReactNode } from "react";

export type ButtonVariant = "primary" | "secondary" | "ghost" | "danger" | "success";
export type ButtonSize = "sm" | "md" | "lg";

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  icon?: ReactNode;
  fullWidth?: boolean;
}

const ESTILOS_VARIANTE: Record<ButtonVariant, string> = {
  primary: "bg-ember-500 text-graphite-950 hover:bg-ember-400 active:bg-ember-600",
  secondary:
    "bg-graphite-800 text-chrome-50 border border-graphite-600 hover:bg-graphite-700 active:bg-graphite-800",
  ghost: "bg-transparent text-graphite-200 hover:bg-graphite-800 active:bg-graphite-900",
  danger: "bg-alert-600 text-chrome-50 hover:bg-alert-500 active:bg-alert-600",
  success: "bg-ok-600 text-chrome-50 hover:bg-ok-500 active:bg-ok-600",
};

const ESTILOS_TAMANHO: Record<ButtonSize, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-4 py-2.5 text-sm",
  lg: "px-5 py-3 text-base",
};

/** Botão base do app, com variantes semânticas de cor e tamanhos consistentes. */
export function Button({
  variant = "primary",
  size = "md",
  icon,
  fullWidth = false,
  children,
  className = "",
  disabled,
  ...rest
}: ButtonProps) {
  return (
    <button
      className={`inline-flex items-center justify-center gap-2 font-display font-semibold uppercase tracking-wide transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${ESTILOS_VARIANTE[variant]} ${ESTILOS_TAMANHO[size]} ${fullWidth ? "w-full" : ""} ${className}`}
      disabled={disabled}
      {...rest}
    >
      {icon}
      {children}
    </button>
  );
}
