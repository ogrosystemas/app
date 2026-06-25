import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";
import { registrarServiceWorkerDeMensagensUmaVez } from "./firebase/messaging";

/**
 * Diferente da versão anterior (com Dexie local), a inicialização do banco
 * (initDatabase, em db/db.ts) agora depende de autenticação — as regras de
 * segurança do Firestore rejeitam qualquer leitura/escrita de quem não está
 * logado. Por isso essa chamada NÃO acontece mais aqui: ela é feita dentro de
 * App.tsx, somente após o login com Google ser confirmado (ver useAuth).
 *
 * A ferramenta de migração temporária (MigrationTool, usada uma única vez para
 * converter o clube fixo antigo para o modelo multi-sede) já foi removida deste
 * projeto depois de confirmada a migração com sucesso.
 */
const rootElement = document.getElementById("root");
if (!rootElement) throw new Error("Elemento #root não encontrado no index.html");

// Registra o service worker de mensagens (FCM) UMA ÚNICA VEZ, aqui na
// inicialização do app — nunca dentro de um clique de botão (ver
// firebase/messaging.ts, registrarServiceWorkerDeMensagensUmaVez, para o bug
// real que essa separação evita: registrar/desregistrar um Service Worker via
// JS, mesmo em escopo isolado, faz o navegador reavaliar TODOS os Service
// Workers da origem, incluindo o do Workbox — o que disparava o banner de
// "Nova versão disponível" especificamente ao clicar em ativar/desativar
// notificações). Roda em paralelo ao render, sem bloquear nada — se falhar
// (navegador sem suporte, rede), os botões de notificação simplesmente vão
// reportar erro quando clicados, sem afetar o resto do app.
registrarServiceWorkerDeMensagensUmaVez();

createRoot(rootElement).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
