import { Download } from "lucide-react";
import { useEffect, useState } from "react";

/**
 * Evento disparado pelo navegador quando ele decide que o site é instalável.
 * Não faz parte do DOM padrão do TypeScript — tipado manualmente aqui.
 */
interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
}

/**
 * Botão "Instalar App" que vive dentro da própria interface (ex: no header), em vez de
 * depender só do Chrome decidir mostrar (ou não) o ícone de instalação no menu/barra de
 * endereço. O navegador dispara o evento `beforeinstallprompt` por iniciativa própria —
 * este componente apenas CAPTURA esse evento quando ele ocorre e guarda uma referência a
 * ele, para poder disparar `prompt()` sob clique do usuário a qualquer momento depois,
 * em vez de depender do timing/heurística do navegador para mostrar sua própria UI.
 *
 * Comportamento:
 * - Antes do evento disparar (ou se o navegador não suportar/já tiver decidido não
 *   oferecer instalação): o botão simplesmente não aparece. Não há like fallback aqui —
 *   "Adicionar à tela inicial" continua sempre disponível no menu do navegador,
 *   independentemente deste componente.
 * - Depois que o app já está instalado (evento `appinstalled` ou modo standalone
 *   detectado): o botão desaparece, pois não faz sentido oferecer instalar de novo.
 */
export function InstallAppButton() {
  const [eventoInstalacao, setEventoInstalacao] = useState<BeforeInstallPromptEvent | null>(null);
  const [jaInstalado, setJaInstalado] = useState(false);

  useEffect(() => {
    // Detecta se o app já está rodando em modo instalado (standalone), nesse caso
    // nunca mostramos o botão, mesmo que o navegador dispare o evento de novo.
    const emModoStandalone =
      window.matchMedia("(display-mode: standalone)").matches ||
      // iOS Safari expõe esta propriedade não padronizada quando instalado via "Adicionar à Tela de Início".
      (navigator as Navigator & { standalone?: boolean }).standalone === true;
    setJaInstalado(emModoStandalone);

    function handleBeforeInstallPrompt(evento: Event) {
      // Impede o mini-infobar automático do Chrome — preferimos mostrar nosso próprio botão.
      evento.preventDefault();
      setEventoInstalacao(evento as BeforeInstallPromptEvent);
    }

    function handleAppInstalled() {
      setEventoInstalacao(null);
      setJaInstalado(true);
    }

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleAppInstalled);

    return () => {
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleAppInstalled);
    };
  }, []);

  if (jaInstalado || !eventoInstalacao) return null;

  async function handleInstalar() {
    if (!eventoInstalacao) return;
    await eventoInstalacao.prompt();
    const escolha = await eventoInstalacao.userChoice;
    if (escolha.outcome === "accepted") {
      setJaInstalado(true);
    }
    // O evento só pode ser usado uma vez — descarta independentemente do resultado.
    setEventoInstalacao(null);
  }

  return (
    <button
      type="button"
      onClick={handleInstalar}
      aria-label="Instalar aplicativo"
      title="Instalar aplicativo"
      className="flex shrink-0 items-center gap-1.5 border border-ember-600 bg-ember-950 px-2 py-1.5 text-ember-500 hover:bg-ember-900 sm:px-2.5"
    >
      <Download size={16} />
      <span className="hidden font-display text-xs font-semibold uppercase tracking-wide sm:inline">
        Instalar
      </span>
    </button>
  );
}
