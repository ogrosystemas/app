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
 * service worker de mensagens no escopo correto, já que o app vive numa
 * subpasta (app.ogrosystemas.com.br/mensalidades/), não na raiz do domínio.
 */
const BASE_PATH = "/mensalidades/";

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
    const registroSW = await navigator.serviceWorker.register(`${BASE_PATH}firebase-messaging-sw.js`, {
      scope: BASE_PATH,
    });

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
    const token = await getToken(messaging, { vapidKey: VAPID_KEY }).catch(() => null);
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

  const messaging = getMessaging(firebaseApp);
  return onMessage(messaging, (payload) => {
    const titulo = payload.notification?.title ?? "Mutantes MC";
    const corpo = payload.notification?.body ?? "";
    aoReceber(titulo, corpo);
  });
}
