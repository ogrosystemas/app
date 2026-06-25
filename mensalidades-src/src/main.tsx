import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";
import { registrarServiceWorkerDeMensagensUmaVez } from "./firebase/messaging";
import { UpdateBanner } from "./components/pwa";

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
    {/*
      UpdateBanner monta AQUI, fora de App, e não mais dentro de MainApp —
      bug real já corrigido, não repetir: App.tsx tem múltiplos retornos
      condicionais (tela de login, seleção de sede, MainApp do tesoureiro,
      MemberSelfView do integrante, acesso negado), e UpdateBanner só era
      renderizado dentro de MainApp. Como é o `useRegisterSW` deste
      componente que de fato registra o Service Worker do Workbox/PWA
      (`injectRegister: null` em vite.config.ts desativa o registro
      automático do plugin de propósito, delegando tudo a este hook), isso
      significava que o SW do Workbox NUNCA registrava para quem ainda não
      tinha logado — e sem esse SW controlando o `start_url` do manifest, o
      Chrome nunca considera o site instalável, então o evento
      `beforeinstallprompt` nunca disparava enquanto a pessoa estava na tela
      de login (que é a primeira coisa que qualquer um vê ao abrir o app).
      Montando aqui, o registro acontece sempre, desde o primeiro
      carregamento da página, independente de autenticação.
    */}
    <UpdateBanner />
    <App />
  </StrictMode>,
);
