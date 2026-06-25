import { Download, Share } from "lucide-react";
import { useEffect, useState } from "react";
import { estaInstaladoComoPWA, isIOS } from "../../utils/platform.utils";

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
  // No iOS, `beforeinstallprompt` nunca dispara (não existe no WebKit) — por
  // isso mostramos uma instrução estática própria ali, em vez de depender do
  // mesmo fluxo de evento usado em Chrome/Edge/Android.
  const [mostrarInstrucaoIOS, setMostrarInstrucaoIOS] = useState(false);

  useEffect(() => {
    // Detecta se o app já está rodando em modo instalado (standalone), nesse caso
    // nunca mostramos o botão, mesmo que o navegador dispare o evento de novo.
    const emModoStandalone = estaInstaladoComoPWA();
    setJaInstalado(emModoStandalone);
    setMostrarInstrucaoIOS(isIOS() && !emModoStandalone);

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

  if (jaInstalado) return null;

  if (mostrarInstrucaoIOS) {
    return <InstrucaoInstalarIOS />;
  }

  if (!eventoInstalacao) return null;

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

/**
 * Substitui o InstallAppButton no iOS — onde não existe `beforeinstallprompt`
 * nem qualquer outra API que permita disparar a instalação por código, ou
 * mesmo detectar que ela aconteceu (diferente de Android, sem evento
 * `appinstalled` no Safari). A única forma de instalar no iOS é a própria
 * pessoa tocar em Compartilhar → Adicionar à Tela de Início, manualmente —
 * este componente só explica esse passo, sem tentar automatizar o que a
 * Apple não permite automatizar.
 *
 * Um clique abre/fecha um popover com a instrução; não fecha sozinho, porque
 * a pessoa pode precisar de tempo para ler e seguir os passos no próprio
 * Safari antes de voltar.
 */
function InstrucaoInstalarIOS() {
  const [aberto, setAberto] = useState(false);

  return (
    <div className="relative shrink-0">
      <button
        type="button"
        onClick={() => setAberto((atual) => !atual)}
        aria-label="Como instalar o aplicativo"
        title="Como instalar o aplicativo"
        className="flex shrink-0 items-center gap-1.5 border border-ember-600 bg-ember-950 px-2 py-1.5 text-ember-500 hover:bg-ember-900 sm:px-2.5"
      >
        <Download size={16} />
        <span className="hidden font-display text-xs font-semibold uppercase tracking-wide sm:inline">
          Instalar
        </span>
      </button>
      {aberto && (
        <div className="absolute right-0 top-full z-50 mt-2 w-64 border border-graphite-700 bg-graphite-900 p-3 text-left shadow-patch">
          <p className="mb-2 text-xs leading-relaxed text-graphite-200">
            Para instalar no iPhone/iPad: toque no ícone{" "}
            <Share size={12} className="inline align-text-bottom text-ember-500" /> (Compartilhar) na
            barra do Safari, depois em <strong>"Adicionar à Tela de Início"</strong>.
          </p>
          <p className="text-xs leading-relaxed text-graphite-400">
            Notificações push só funcionam depois de instalado assim — abrir pelo Safari não é suficiente.
          </p>
        </div>
      )}
    </div>
  );
}
