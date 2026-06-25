import { deleteToken, getMessaging, getToken, isSupported, onMessage } from "firebase/messaging";
import { deleteDoc, setDoc } from "firebase/firestore";
import { firebaseApp } from "./config";
import { refTokenNotificacao } from "../db/refs";
import type { PapelTokenNotificacao } from "../types";

/**
 * VAPID key pública do projeto (Firebase Console > Configurações do projeto >
 * Cloud Messaging > Web Push certificates) — identifica o remetente para o
 * navegador autorizar a inscrição de push. Não é segredo (é enviada ao
 * navegador de qualquer forma), mas fica em variável de ambiente pelo mesmo
 * motivo das demais chaves do Firebase: trocar de projeto sem editar código.
 */
const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY;

/**
 * BASE_PATH do app, repetido aqui (em vez de importar de vite.config.ts, que
 * não pode ser importado em código de runtime) — necessário para registrar o
 * service worker de mensagens no caminho correto, já que o app vive numa
 * subpasta (app.ogrosystemas.com.br/mensalidades/), não na raiz do domínio.
 */
const BASE_PATH = "/mensalidades/";

/**
 * Escopo PRÓPRIO do service worker de mensagens — um subcaminho fictício
 * dedicado (não precisa existir como pasta real; o navegador só usa essa
 * string como prefixo de escopo), DIFERENTE do escopo do service worker do
 * Workbox/PWA (que é BASE_PATH inteiro, controlando toda a navegação do app —
 * ver navigateFallbackAllowlist em vite.config.ts).
 *
 * Registrar os dois SWs no MESMO escopo (BASE_PATH) faz o navegador tratá-los
 * como concorrentes pelo mesmo controle de página: cada chamada a
 * navigator.serviceWorker.register() neste escopo compartilhado disparava uma
 * re-checagem de atualização também no SW do Workbox, fazendo o banner
 * "Atualizar" da UI (ver UpdateBanner.tsx) reaparecer especificamente quando
 * a pessoa clicava em ativar/desativar notificações — bug real já corrigido,
 * não repetir: o SW de mensagens precisa de um escopo isolado, mesmo que
 * mais restrito do que o necessário para a Push API funcionar (que só exige
 * que o escopo cubra a própria origem das mensagens recebidas).
 */
const ESCOPO_SW_MENSAGENS = `${BASE_PATH}fcm/`;

/**
 * Obtém o registro do service worker de mensagens já existente NESTE escopo
 * isolado, ou registra um novo se ainda não houver — usado pelos três pontos
 * que precisam dele (ativar, desativar, ouvir em primeiro plano), para nunca
 * deixar nenhum deles cair no registro automático/escopo padrão do SDK do
 * Firebase (ver ESCOPO_SW_MENSAGENS para o bug real que isso evita).
 */
async function obterRegistroSWMensagens(): Promise<ServiceWorkerRegistration> {
  const existente = await navigator.serviceWorker.getRegistration(ESCOPO_SW_MENSAGENS);
  if (existente) return existente;
  return navigator.serviceWorker.register(`${BASE_PATH}firebase-messaging-sw.js`, {
    scope: ESCOPO_SW_MENSAGENS,
  });
}

export type ResultadoAtivarNotificacoes =
  | { ok: true }
  | { ok: false; motivo: "sem-suporte" | "permissao-negada" | "erro"; detalhe?: unknown };

/**
 * Ativa notificações push para a pessoa logada: pede permissão ao navegador,
 * registra o service worker dedicado de mensagens, obtém o token do FCM e
 * salva (ou atualiza) o documento correspondente em `tokensNotificacao/{token}`.
 *
 * Idempotente: chamar de novo com a pessoa já tendo concedido permissão apenas
 * atualiza `atualizadoEm` no Firestore (o FCM tende a devolver o mesmo token
 * enquanto a instalação do PWA não for removida) — útil para "renovar" o
 * registro sempre que a pessoa abre o app, sem duplicar nada.
 *
 * Nunca lança: todo caminho de falha (sem suporte do navegador, permissão
 * negada, erro de rede) retorna um resultado tipado para a UI decidir o que
 * mostrar, em vez de precisar de try/catch no chamador.
 */
export async function ativarNotificacoesPush(
  email: string,
  clubeId: string,
  papel: PapelTokenNotificacao,
  membroId: string | undefined,
): Promise<ResultadoAtivarNotificacoes> {
  try {
    const suportado = await isSupported();
    if (!suportado) {
      return { ok: false, motivo: "sem-suporte" };
    }

    if (!VAPID_KEY) {
      console.error("VITE_FIREBASE_VAPID_KEY não configurada — notificações push não podem ser ativadas.");
      return { ok: false, motivo: "erro", detalhe: "VAPID key ausente" };
    }

    const permissao = await Notification.requestPermission();
    if (permissao !== "granted") {
      return { ok: false, motivo: "permissao-negada" };
    }

    // Registra (ou reaproveita, se já registrado) o service worker dedicado ao
    // FCM — separado do service worker do PWA (gerado pelo vite-plugin-pwa),
    // porque o FCM exige um arquivo com nome e conteúdo específicos próprios.
    // Escopo isolado (ESCOPO_SW_MENSAGENS) para não competir com o SW do
    // Workbox — ver comentário da constante para o bug real que isso evita.
    const registroSW = await obterRegistroSWMensagens();

    const messaging = getMessaging(firebaseApp);
    const token = await getToken(messaging, {
      vapidKey: VAPID_KEY,
      serviceWorkerRegistration: registroSW,
    });

    if (!token) {
      return { ok: false, motivo: "erro", detalhe: "getToken retornou vazio" };
    }

    const agora = Date.now();
    await setDoc(
      refTokenNotificacao(token),
      {
        token,
        email,
        clubeId,
        papel,
        ...(membroId !== undefined ? { membroId } : {}),
        criadoEm: agora,
        atualizadoEm: agora,
      },
      { merge: true },
    );

    return { ok: true };
  } catch (erro) {
    console.error("Falha ao ativar notificações push:", erro);
    return { ok: false, motivo: "erro", detalhe: erro };
  }
}

/**
 * Desativa notificações push neste dispositivo: revoga o token no FCM e
 * remove o documento correspondente do Firestore. Chamado pelo botão
 * "Desativar notificações" — se a pessoa não tiver um token ativo, não faz
 * nada (idempotente).
 */
export async function desativarNotificacoesPush(): Promise<void> {
  try {
    const suportado = await isSupported();
    if (!suportado) return;

    const messaging = getMessaging(firebaseApp);
    const registroSW = await obterRegistroSWMensagens();
    const token = await getToken(messaging, { vapidKey: VAPID_KEY, serviceWorkerRegistration: registroSW }).catch(
      () => null,
    );
    if (!token) return;

    await deleteToken(messaging);
    await deleteDoc(refTokenNotificacao(token));
  } catch (erro) {
    console.error("Falha ao desativar notificações push:", erro);
  }
}

/**
 * Verifica, sem solicitar nada à pessoa, se as notificações já estão
 * concedidas neste navegador — usado pela UI para decidir se mostra o botão
 * "Ativar" ou "Desativar". Retorna false em navegadores sem suporte a
 * Notification (ex: alguns navegadores embutidos de WebView) em vez de lançar.
 */
export function notificacoesJaConcedidas(): boolean {
  return typeof Notification !== "undefined" && Notification.permission === "granted";
}

/**
 * Escuta mensagens push recebidas enquanto o app está ABERTO em primeiro
 * plano (a notificação do sistema, quando o app está em segundo plano ou
 * fechado, é tratada pelo service worker — ver public/firebase-messaging-sw.js
 * — e não passa por aqui). Sem isso, uma pessoa com o app aberto na tela não
 * veria nada quando o push chegasse, já que o navegador não mostra notificação
 * do sistema para uma aba em foco.
 *
 * Retorna a função de cancelamento da inscrição, para uso em useEffect.
 */
export async function ouvirNotificacoesEmPrimeiroPlano(
  aoReceber: (titulo: string, corpo: string) => void,
): Promise<() => void> {
  const suportado = await isSupported();
  if (!suportado) return () => {};

  // Garante que o SW de mensagens já está registrado no escopo isolado ANTES
  // de chamar onMessage — onMessage não registra SW por si só, mas mantemos a
  // mesma garantia explícita dos outros pontos de uso por consistência e para
  // não depender de uma chamada anterior já ter registrado o SW.
  await obterRegistroSWMensagens();

  const messaging = getMessaging(firebaseApp);
  return onMessage(messaging, (payload) => {
    const titulo = payload.notification?.title ?? "Mutantes MC";
    const corpo = payload.notification?.body ?? "";
    aoReceber(titulo, corpo);
  });
}
