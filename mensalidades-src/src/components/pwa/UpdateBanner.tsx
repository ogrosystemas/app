import { CheckCircle2, RefreshCw } from "lucide-react";
import { useEffect, useState } from "react";
import { useRegisterSW } from "virtual:pwa-register/react";

/** Duração da contagem regressiva antes de aplicar a atualização automaticamente, em segundos. */
const SEGUNDOS_PARA_AUTO_ATUALIZAR = 10;

/** Chave usada para sinalizar, via sessionStorage, que a confirmação deve aparecer após o reload. */
const CHAVE_CONFIRMACAO_PENDENTE = "mutantes-mc:confirmar-atualizacao";

/**
 * Banner de atualização: aparece quando o vite-plugin-pwa detecta um novo Service Worker
 * pronto (versão nova publicada). Dá duas saídas ao usuário:
 * 1) Clicar em "Atualizar" — aplica e recarrega na hora.
 * 2) Não fazer nada — uma barra de progresso conta os segundos e, ao zerar, a atualização
 *    é aplicada automaticamente (evita o app ficar parado numa versão antiga por dias só
 *    porque ninguém reparou no banner).
 *
 * IMPORTANTE: updateServiceWorker(true) recarrega a página imediatamente (é um reload de
 * verdade, não um evento React) — qualquer estado em memória se perde nesse momento. Por
 * isso a confirmação "App atualizado" é sinalizada em sessionStorage ANTES do reload e lida
 * de volta DEPOIS dele, na nova sessão da página, e não como um estado local deste componente.
 */
export function UpdateBanner() {
  const [confirmacaoVisivel, setConfirmacaoVisivel] = useState(false);

  const {
    needRefresh: [needRefresh, setNeedRefresh],
    updateServiceWorker,
  } = useRegisterSW({
    onRegisteredSW(_url, registration) {
      // Verifica periodicamente se há uma versão nova publicada (a cada hora),
      // já que o app pode ficar aberto/instalado por muito tempo sem recarregar.
      if (!registration) return;
      setInterval(() => {
        registration.update().catch(() => {
          // Falha silenciosa: sem internet no momento da checagem não é um erro do app.
        });
      }, 60 * 60 * 1000);
    },
  });

  const [segundosRestantes, setSegundosRestantes] = useState(SEGUNDOS_PARA_AUTO_ATUALIZAR);

  // Ao montar, verifica se viemos de um reload provocado por uma atualização — se sim,
  // mostra a confirmação por alguns segundos e limpa o sinalizador.
  useEffect(() => {
    let sinalizado = false;
    try {
      sinalizado = sessionStorage.getItem(CHAVE_CONFIRMACAO_PENDENTE) === "1";
    } catch {
      // sessionStorage pode falhar em alguns contextos restritos; ignora silenciosamente.
    }
    if (!sinalizado) return;

    try {
      sessionStorage.removeItem(CHAVE_CONFIRMACAO_PENDENTE);
    } catch {
      // idem
    }
    setConfirmacaoVisivel(true);
    const timeout = setTimeout(() => setConfirmacaoVisivel(false), 3000);
    return () => clearTimeout(timeout);
  }, []);

  useEffect(() => {
    if (!needRefresh) return;

    setSegundosRestantes(SEGUNDOS_PARA_AUTO_ATUALIZAR);
    const intervalo = setInterval(() => {
      setSegundosRestantes((atual) => {
        if (atual <= 1) {
          clearInterval(intervalo);
          aplicarAtualizacao();
          return 0;
        }
        return atual - 1;
      });
    }, 1000);

    return () => clearInterval(intervalo);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [needRefresh]);

  async function aplicarAtualizacao() {
    setNeedRefresh(false);
    try {
      sessionStorage.setItem(CHAVE_CONFIRMACAO_PENDENTE, "1");
    } catch {
      // Se sessionStorage falhar, a atualização ainda acontece — só não mostra a confirmação.
    }

    await updateServiceWorker(true); // dispara o reload real da página, na maioria dos casos

    // Fallback de segurança: em alguns cenários o sinal interno do workbox para recarregar
    // não dispara (ex: o novo SW já assumiu o controle antes do clique, sem deixar nada
    // "waiting" para receber o skip-waiting). Força o reload manualmente após um pequeno
    // atraso caso updateServiceWorker não tenha conseguido fazê-lo por conta própria.
    setTimeout(() => {
      window.location.reload();
    }, 1500);
  }

  if (!needRefresh && !confirmacaoVisivel) return null;

  if (confirmacaoVisivel) {
    return (
      <div className="fixed inset-x-0 bottom-0 z-40 flex justify-center px-4 pb-4">
        <div className="flex items-center gap-2 border border-ok-600 bg-ok-950 px-4 py-2.5 text-sm text-ok-400 shadow-patch">
          <CheckCircle2 size={16} />
          App atualizado
        </div>
      </div>
    );
  }

  const progresso = (segundosRestantes / SEGUNDOS_PARA_AUTO_ATUALIZAR) * 100;

  return (
    <div className="fixed inset-x-0 bottom-0 z-40 px-4 pb-4">
      <div className="mx-auto max-w-md overflow-hidden border border-ember-600 bg-graphite-900 shadow-patch">
        <div className="flex items-center gap-3 px-4 py-3">
          <RefreshCw className="shrink-0 text-ember-500" size={18} />
          <div className="flex-1">
            <p className="font-display text-sm font-semibold uppercase tracking-wide text-chrome-50">
              Nova versão disponível
            </p>
            <p className="text-xs text-graphite-400">
              Atualizando automaticamente em {segundosRestantes}s
            </p>
          </div>
          <button
            type="button"
            onClick={aplicarAtualizacao}
            className="shrink-0 bg-ember-500 px-3 py-2 font-display text-xs font-semibold uppercase tracking-wide text-graphite-950 hover:bg-ember-400"
          >
            Atualizar
          </button>
        </div>
        <div className="h-1 w-full bg-graphite-800">
          <div
            className="h-full bg-ember-500 transition-[width] duration-1000 ease-linear"
            style={{ width: `${progresso}%` }}
          />
        </div>
      </div>
    </div>
  );
}
