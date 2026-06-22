import type { Config } from "tailwindcss";

export default {
  content: ["./index.html", "./src/**/*.{ts,tsx}"],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        // Base — grafite/preto, nunca preto puro (#000) para manter profundidade nos cards.
        graphite: {
          950: "#0d0d0d", // fundo base do app
          900: "#161616", // fundo de seções
          800: "#1f1f1f", // cards/superfícies
          700: "#2a2a2a", // bordas e divisores
          600: "#3a3a3a", // bordas em hover/foco
          400: "#7a7a7a", // texto secundário
          200: "#c9c9c4", // texto terciário em fundos escuros
        },
        // Off-white "cromo" para texto principal — evita branco puro, que pesa demais no dark mode.
        chrome: {
          50: "#f5f5f0",
        },
        // Accent primário — laranja fogo, usado em CTAs e elementos de ação/destaque.
        ember: {
          500: "#ff5722",
          600: "#e8491a",
          700: "#c93d14",
          400: "#ff7a4d",
          950: "#2a1308",
        },
        // Vermelho de alerta — reservado a estados de pendência/erro (semântica separada do ember).
        alert: {
          500: "#dc2626",
          600: "#b91c1c",
          400: "#ef4444",
          950: "#2a0a0a",
        },
        // Verde de confirmação — reservado a "em dia"/sucesso.
        ok: {
          500: "#16a34a",
          600: "#15803d",
          400: "#4ade80",
          950: "#0a2012",
        },
      },
      fontFamily: {
        display: ["Oswald", "sans-serif"],
        body: ["Inter", "sans-serif"],
      },
      letterSpacing: {
        widest2: "0.18em",
      },
      backgroundImage: {
        "grain": "radial-gradient(circle at 1px 1px, rgba(255,255,255,0.035) 1px, transparent 0)",
      },
      backgroundSize: {
        grain: "4px 4px",
      },
      boxShadow: {
        "patch": "0 1px 0 rgba(255,255,255,0.04) inset, 0 2px 6px rgba(0,0,0,0.5)",
      },
    },
  },
  plugins: [],
} satisfies Config;
