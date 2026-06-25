/**
 * Detecção de plataforma — usada para mostrar mensagens e instruções
 * específicas onde o comportamento de Push/PWA difere de verdade entre
 * sistemas, especialmente iOS (Safari), que tem restrições reais da Apple
 * sem solução por código: push Web só funciona a partir do iOS 16.4, e
 * somente quando o PWA está instalado na Tela de Início — nunca rodando
 * dentro de uma aba comum do Safari, por mais atualizado que o iOS esteja.
 */

/** true se o dispositivo é iPhone/iPad/iPod (qualquer navegador — a restrição de push é do WebKit do sistema, não do app que o exibe). */
export function isIOS(): boolean {
  if (typeof navigator === "undefined") return false;
  const ehIOSClassico = /iPad|iPhone|iPod/.test(navigator.userAgent);
  // iPadOS 13+ finge ser "Macintosh" no userAgent quando o modo desktop está
  // ativo — diferenciado de um Mac real pela presença de touch, que Macs não têm.
  const ehIPadComUserAgentDeMac = navigator.userAgent.includes("Macintosh") && navigator.maxTouchPoints > 1;
  return ehIOSClassico || ehIPadComUserAgentDeMac;
}

/** true se o dispositivo é touch-primary (celular/tablet) — usado para diferenciar textos de instrução entre o menu do navegador mobile e o menu do navegador desktop, que têm itens diferentes. */
export function isMobile(): boolean {
  if (typeof navigator === "undefined") return false;
  return /Android|iPad|iPhone|iPod|Mobile/.test(navigator.userAgent);
}

/** true se o app já está rodando instalado (modo standalone) — funciona tanto para Android/Chrome quanto para iOS/Safari, que expõe essa informação de formas diferentes. */
export function estaInstaladoComoPWA(): boolean {
  if (typeof window === "undefined") return false;
  const padraoWeb = window.matchMedia("(display-mode: standalone)").matches;
  // Propriedade não padronizada, exclusiva do Safari/iOS — true quando aberto
  // via ícone da Tela de Início, false quando aberto dentro de uma aba normal.
  const padraoIOS = (navigator as Navigator & { standalone?: boolean }).standalone === true;
  return padraoWeb || padraoIOS;
}

/**
 * true se, NESTE exato navegador/contexto, push tem alguma chance de
 * funcionar de verdade. Em iOS, isso exige tanto suporte de versão (Notification
 * existir no escopo global, o que só passa a ser verdade a partir do iOS 16.4)
 * quanto estar instalado como PWA — as duas condições são obrigatórias.
 * Em qualquer outra plataforma, só depende do suporte normal do navegador.
 */
export function pushPodeFuncionarNestaPlataforma(): boolean {
  const suportaNotification = typeof Notification !== "undefined";
  if (!isIOS()) return suportaNotification;
  return suportaNotification && estaInstaladoComoPWA();
}
