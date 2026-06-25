// Service worker DEDICADO ao Firebase Cloud Messaging — separado do service
// worker do PWA (gerado por vite-plugin-pwa/Workbox, registrado em
// UpdateBanner.tsx). O FCM exige um arquivo com este nome exato
// ("firebase-messaging-sw.js") rodando em paralelo, responsável SOMENTE por
// receber pushes e mostrar a notificação do sistema quando o app está em
// segundo plano ou fechado — nunca lida com cache de assets ou navegação
// offline, que continuam sendo responsabilidade exclusiva do SW do Workbox.
//
// Não passa pelo bundler do Vite: este arquivo fica em public/ e é copiado
// como está para a raiz do build. Por isso usa importScripts (sintaxe clássica
// de Service Worker), não import/export do ES Modules.

importScripts("https://www.gstatic.com/firebasejs/12.15.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/12.15.0/firebase-messaging-compat.js");

// Mesmas chaves PÚBLICAS de src/firebase/config.ts — repetidas aqui (não
// importadas) porque um Service Worker não tem acesso às variáveis de
// ambiente do Vite (import.meta.env não existe neste contexto). Se o projeto
// Firebase mudar, estes valores precisam ser atualizados manualmente aqui
// também, junto com .env.
firebase.initializeApp({
  apiKey: "AIzaSyCYRA6ss56MsiIX4TDXNcNnlDewZdjb7wA",
  authDomain: "mensalidade-mutantes-itj.firebaseapp.com",
  projectId: "mensalidade-mutantes-itj",
  storageBucket: "mensalidade-mutantes-itj.firebasestorage.app",
  messagingSenderId: "465455247511",
  appId: "1:465455247511:web:37c0a53823e63def4eb9d2",
});

const messaging = firebase.messaging();

// Notificação em SEGUNDO PLANO (app fechado ou minimizado): o FCM já mostra a
// notificação do sistema automaticamente quando o payload tem um campo
// "notification" (é o que o script de disparo no VPS envia) — este handler
// existe principalmente para customizar o ícone/comportamento de clique, não
// para disparar a notificação manualmente (evita notificação duplicada).
messaging.onBackgroundMessage((payload) => {
  const titulo = payload.notification?.title ?? "Mutantes Moto Clube";
  const corpo = payload.notification?.body ?? "";

  self.registration.showNotification(titulo, {
    body: corpo,
    icon: "/mensalidades/icons/icon-192.png",
    badge: "/mensalidades/icons/icon-192.png",
    data: payload.data ?? {},
  });
});

// Clique na notificação: abre o app (ou foca a aba já aberta) na tela de
// autoconsulta/lista, em vez de simplesmente fechar a notificação sem ação.
self.addEventListener("notificationclick", (evento) => {
  evento.notification.close();
  const urlDestino = "/mensalidades/";

  evento.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((listaClientes) => {
      for (const cliente of listaClientes) {
        if (cliente.url.includes("/mensalidades/") && "focus" in cliente) {
          return cliente.focus();
        }
      }
      return self.clients.openWindow(urlDestino);
    }),
  );
});
