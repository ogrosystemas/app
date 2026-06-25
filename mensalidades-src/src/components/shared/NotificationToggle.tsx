import { Bell, BellOff } from "lucide-react";
import { useNotificacoesPush } from "../../hooks/useNotificacoesPush";
import type { PapelTokenNotificacao } from "../../types";
import { Button } from "../ui/Button";

interface NotificationToggleProps {
  email: string;
  clubeId: string;
  papel: PapelTokenNotificacao;
  /** ID do membro vinculado a este e-mail nesta sede, se houver (ver TokenNotificacao). */
  membroId?: string;
}

/**
 * Botão único de opt-in/opt-out de notificações push para ESTE dispositivo —
 * usado tanto na área do integrante (MemberSelfView) quanto na área do
 * tesoureiro (SettingsModal). Mostra "Ativar" ou "Desativar" de acordo com a
 * permissão já concedida (ou não) neste navegador especificamente — a
 * permissão é por dispositivo, então a mesma pessoa pode estar ativa no
 * celular e inativa no notebook ao mesmo tempo.
 */
export function NotificationToggle({ email, clubeId, papel, membroId }: NotificationToggleProps) {
  const { ativas, processando, erro, ativar, desativar } = useNotificacoesPush(
    email,
    clubeId,
    papel,
    membroId,
  );

  return (
    <div className="flex flex-col gap-2">
      <Button
        variant="secondary"
        fullWidth
        icon={ativas ? <BellOff size={14} /> : <Bell size={14} />}
        onClick={ativas ? desativar : ativar}
        disabled={processando}
      >
        {processando
          ? "Aguarde..."
          : ativas
            ? "Desativar lembretes de mensalidade"
            : "Ativar lembretes de mensalidade"}
      </Button>
      {erro && <p className="text-xs text-alert-400">{erro}</p>}
      {!erro && (
        <p className="text-xs text-graphite-400">
          Avisa quando houver mensalidade pendente, todo início de mês.
        </p>
      )}
    </div>
  );
}
