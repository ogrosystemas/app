import { Bell } from "lucide-react";
import { useEffect, useState } from "react";
import { ouvirNotificacoesEmPrimeiroPlano } from "../../firebase/messaging";

const DURACAO_VISIVEL_MS = 6000;

/**
 * Mostra um toast quando uma notificação push chega com o app ABERTO em
 * primeiro plano — necessário porque, nesse caso, o navegador NUNCA mostra a
 * notificação do sistema por conta própria (isso só acontece via o service
 * worker, quando o app está em segundo plano ou fechado — ver
 * public/firebase-messaging-sw.js, onBackgroundMessage). Sem este componente,
 * uma pessoa com o app aberto na tela simplesmente não veria o lembrete
 * chegar — bug real já corrigido: a função `ouvirNotificacoesEmPrimeiroPlano`
 * existia em firebase/messaging.ts desde a primeira versão desta feature, mas
 * nunca tinha sido conectada a nenhum componente da UI.
 *
 * Autocontido (sem props) — basta montar uma vez em qualquer ponto comum às
 * telas de tesoureiro e integrante (ver MainApp.tsx e MemberSelfView.tsx).
 */
export function ForegroundNotificationToast() {
  const [mensagem, setMensagem] = useState<{ titulo: string; corpo: string } | null>(null);

  useEffect(() => {
    let cancelarInscricao: (() => void) | undefined;
    let cancelado = false;

    ouvirNotificacoesEmPrimeiroPlano((titulo, corpo) => {
      setMensagem({ titulo, corpo });
    }).then((cancelar) => {
      if (cancelado) {
        cancelar();
      } else {
        cancelarInscricao = cancelar;
      }
    });

    return () => {
      cancelado = true;
      cancelarInscricao?.();
    };
  }, []);

  useEffect(() => {
    if (!mensagem) return;
    const timeout = setTimeout(() => setMensagem(null), DURACAO_VISIVEL_MS);
    return () => clearTimeout(timeout);
  }, [mensagem]);

  if (!mensagem) return null;

  return (
    <div className="fixed inset-x-0 top-0 z-50 flex justify-center px-4 pt-4">
      <button
        type="button"
        onClick={() => setMensagem(null)}
        className="flex max-w-md items-start gap-3 border border-ember-600 bg-graphite-900 px-4 py-3 text-left shadow-patch"
      >
        <Bell className="mt-0.5 shrink-0 text-ember-500" size={18} />
        <div className="flex-1">
          <p className="font-display text-sm font-semibold uppercase tracking-wide text-chrome-50">
            {mensagem.titulo}
          </p>
          {mensagem.corpo && <p className="text-xs text-graphite-400">{mensagem.corpo}</p>}
        </div>
      </button>
    </div>
  );
}
