import { Download, MoreVertical, Share } from "lucide-react";
import { useEffect, useState } from "react";
import { estaInstaladoComoPWA, isIOS, isMobile } from "../../utils/platform.utils";

/**
 * Evento disparado pelo navegador quando ele decide que o site é instalável.
 * Não faz parte do DOM padrão do TypeScript — tipado manualmente aqui.
 */
interface BeforeInstallPromptEvent extends Event {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
}

/**
 * Tempo de espera, em ms, antes de mostrar a instrução manual de fallback em
 * Android/Desktop (Chrome/Edge) quando `beforeinstallprompt` não dispara.
 *
 * IMPORTANTE — não é mais um bug de código a partir daqui, é comportamento
 * documentado do próprio Chrome: a partir de uma certa versão, o navegador
 * passou a usar um modelo de ML (sinais incluindo histórico de visitas ao
 * site nos últimos 14 dias) para decidir SE e QUANDO mostra o prompt
 * automático — não depende só de passar os critérios técnicos de
 * instalabilidade (manifest válido, Service Worker ativo, HTTPS), que este
 * app já cumpre. Ver: developer.chrome.com/blog/how_chrome_helps_users_install_the_apps_they_value
 *
 * Por isso, depois deste tempo sem o evento disparar, mostramos a instrução
 * manual pelo menu do navegador (sempre disponível, independente do ML) em
 * vez de deixar o botão simplesmente não aparecer — uma pessoa numa primeira
 * visita não tem 14 dias de histórico para esperar.
 */
const ESPERA_FALLBACK_MANUAL_MS = 4000;

/**
 * Botão "Instalar App" que vive dentro da própria interface (ex: no header), em vez de
 * depender só do Chrome decidir mostrar (ou não) o ícone de instalação no menu/barra de
 * endereço. O navegador dispara o evento `beforeinstallprompt` por iniciativa própria —
 * este componente apenas CAPTURA esse evento quando ele ocorre e guarda uma referência a
 * ele, para poder disparar `prompt()` sob clique do usuário a qualquer momento depois,
 * em vez de depender do timing/heurística do navegador para mostrar sua própria UI.
 *
 * Comportamento:
 * - iOS: sempre mostra a instrução manual (Compartilhar → Adicionar à Tela de Início),
 *   já que `beforeinstallprompt` nunca existe no WebKit.
 * - Android/Desktop (Chrome/Edge): se o evento disparar, mostra o botão "Instalar" de
 *   verdade (clique já abre o prompt nativo). Se não disparar dentro de
 *   ESPERA_FALLBACK_MANUAL_MS, mostra a instrução manual pelo menu do navegador — o
 *   Chrome moderno pode não disparar o prompt automático mesmo em sites 100%
 *   instaláveis, dependendo do seu modelo de ML interno (ver comentário acima).
 * - Depois que o app já está instalado (evento `appinstalled` ou modo standalone
 *   detectado): o botão desaparece, pois não faz sentido oferecer instalar de novo.
 */
export function InstallAppButton() {
  const [eventoInstalacao, setEventoInstalacao] = useState<BeforeInstallPromptEvent | null>(null);
  const [jaInstalado, setJaInstalado] = useState(false);
  const [mostrarInstrucaoIOS, setMostrarInstrucaoIOS] = useState(false);
  // true depois de ESPERA_FALLBACK_MANUAL_MS sem o beforeinstallprompt disparar —
  // ver comentário de ESPERA_FALLBACK_MANUAL_MS para o motivo de isso ser
  // necessário e não um bug a corrigir de outra forma.
  const [mostrarInstrucaoManualFallback, setMostrarInstrucaoManualFallback] = useState(false);

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

    const timeoutFallback = setTimeout(() => {
      setMostrarInstrucaoManualFallback(true);
    }, ESPERA_FALLBACK_MANUAL_MS);

    return () => {
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleAppInstalled);
      clearTimeout(timeoutFallback);
    };
  }, []);

  if (jaInstalado) return null;

  if (mostrarInstrucaoIOS) {
    return <InstrucaoInstalar variante="ios" />;
  }

  if (eventoInstalacao) {
    return <BotaoInstalarNativo evento={eventoInstalacao} onInstalado={() => setJaInstalado(true)} />;
  }

  if (mostrarInstrucaoManualFallback) {
    return <InstrucaoInstalar variante={isMobile() ? "android" : "desktop"} />;
  }

  return null;
}

/** Botão real, que dispara o `prompt()` nativo do navegador — usado quando `beforeinstallprompt` disparou de verdade. */
function BotaoInstalarNativo({
  evento,
  onInstalado,
}: {
  evento: BeforeInstallPromptEvent;
  onInstalado: () => void;
}) {
  async function handleInstalar() {
    await evento.prompt();
    const escolha = await evento.userChoice;
    if (escolha.outcome === "accepted") {
      onInstalado();
    }
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
 * Instrução manual de instalação — três variantes de conteúdo, mesma casca
 * visual (botão + popover):
 * - "ios": Compartilhar → Adicionar à Tela de Início (única forma no Safari).
 * - "android": menu de três pontos → "Adicionar à tela inicial" → escolher
 *   INSTALAR o app na tela seguinte (não "criar atalho", que é uma opção
 *   diferente no mesmo fluxo e só abre o navegador, sem o app de verdade).
 * - "desktop": menu de três pontos → "Instalar [nome do app]..." direto,
 *   sem etapa intermediária — texto exato confirmado no Chrome desktop.
 *
 * Ambos os fallbacks (android/desktop) existem para o mesmo motivo: o
 * prompt automático (beforeinstallprompt) pode não disparar por decisão do
 * modelo de ML do navegador, mesmo em sites 100% instaláveis — ver
 * ESPERA_FALLBACK_MANUAL_MS para a referência completa.
 *
 * Um clique abre/fecha o popover; não fecha sozinho, porque a pessoa pode
 * precisar de tempo para ler e seguir os passos antes de voltar.
 */
function InstrucaoInstalar({ variante }: { variante: "ios" | "android" | "desktop" }) {
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
          {variante === "ios" && (
            <>
              <p className="mb-2 text-xs leading-relaxed text-graphite-200">
                Para instalar no iPhone/iPad: toque no ícone{" "}
                <Share size={12} className="inline align-text-bottom text-ember-500" /> (Compartilhar) na
                barra do Safari, depois em <strong>"Adicionar à Tela de Início"</strong>.
              </p>
              <p className="text-xs leading-relaxed text-graphite-400">
                Notificações push só funcionam depois de instalado assim — abrir pelo Safari não é
                suficiente.
              </p>
            </>
          )}
          {variante === "android" && (
            <p className="text-xs leading-relaxed text-graphite-200">
              Toque no menu <MoreVertical size={12} className="inline align-text-bottom text-ember-500" />{" "}
              (três pontinhos) do navegador, depois em <strong>"Adicionar à tela inicial"</strong>. Na
              tela seguinte, escolha <strong>instalar o app</strong> — não "criar atalho", que só abre o
              navegador sem o app de verdade.
            </p>
          )}
          {variante === "desktop" && (
            <p className="text-xs leading-relaxed text-graphite-200">
              Clique no menu <MoreVertical size={12} className="inline align-text-bottom text-ember-500" />{" "}
              (três pontinhos) do navegador, depois em{" "}
              <strong>"Instalar Mutantes Moto Clube..."</strong>.
            </p>
          )}
        </div>
      )}
    </div>
  );
}
