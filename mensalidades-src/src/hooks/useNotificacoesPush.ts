import { useState } from "react";
import {
  ativarNotificacoesPush,
  desativarNotificacoesPush,
  notificacoesJaConcedidas,
  type ResultadoAtivarNotificacoes,
} from "../firebase/messaging";
import type { PapelTokenNotificacao } from "../types";

export interface UseNotificacoesPushResult {
  /** true se este navegador já tem a permissão de notificação concedida. */
  ativas: boolean;

  /** true durante a chamada de ativar/desativar (pedido de permissão, escrita no Firestore). */
  processando: boolean;

  /** Mensagem de erro amigável da última tentativa de ativação, se houver. */
  erro: string | null;

  ativar: () => Promise<void>;
  desativar: () => Promise<void>;
}

const MENSAGENS_ERRO: Record<Exclude<ResultadoAtivarNotificacoes, { ok: true }>["motivo"], string> = {
  "sem-suporte": "Este navegador/dispositivo não tem suporte a notificações push.",
  "permissao-negada":
    "Permissão de notificação negada. Habilite manualmente nas configurações do navegador para ativar.",
  "ios-nao-instalado":
    "No iPhone/iPad, notificações só funcionam com o app instalado na Tela de Início. Toque em Compartilhar e depois em \"Adicionar à Tela de Início\", abra o app por esse ícone e tente ativar de novo.",
  erro: "Não foi possível ativar as notificações agora. Tente novamente.",
};

/**
 * Hook compartilhado entre a área do integrante (MemberSelfView) e a área do
 * tesoureiro (SettingsModal) para ativar/desativar notificações push neste
 * dispositivo. Mantém só o estado de UI (processando, erro) — a lógica real
 * de permissão/token/Firestore vive em firebase/messaging.ts.
 */
export function useNotificacoesPush(
  email: string,
  clubeId: string,
  papel: PapelTokenNotificacao,
  membroId: string | undefined,
): UseNotificacoesPushResult {
  const [ativas, setAtivas] = useState<boolean>(notificacoesJaConcedidas());
  const [processando, setProcessando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  async function ativar() {
    setProcessando(true);
    setErro(null);
    try {
      const resultado = await ativarNotificacoesPush(email, clubeId, papel, membroId);
      if (resultado.ok) {
        setAtivas(true);
      } else {
        setErro(MENSAGENS_ERRO[resultado.motivo]);
      }
    } finally {
      setProcessando(false);
    }
  }

  async function desativar() {
    setProcessando(true);
    setErro(null);
    try {
      await desativarNotificacoesPush();
      setAtivas(false);
    } finally {
      setProcessando(false);
    }
  }

  return { ativas, processando, erro, ativar, desativar };
}
