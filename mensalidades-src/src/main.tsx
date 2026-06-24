import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import { MigrationTool } from "./MigrationTool";
import "./index.css";

/**
 * Diferente da versão anterior (com Dexie local), a inicialização do banco
 * (initDatabase, em db/db.ts) agora depende de autenticação — as regras de
 * segurança do Firestore rejeitam qualquer leitura/escrita de quem não está
 * logado. Por isso essa chamada NÃO acontece mais aqui: ela é feita dentro de
 * App.tsx, somente após o login com Google ser confirmado (ver useAuth).
 *
 * A ferramenta de migração (MigrationTool) é TEMPORÁRIA, de uso único — acessível
 * só visitando a URL com "?migrar=1" no final (ex:
 * https://app.../mensalidades/?migrar=1), e exige login normal antes de
 * funcionar (as regras de segurança do Firestore continuam protegendo os dados
 * mesmo nessa rota). Depois de confirmada a migração com sucesso, REMOVER este
 * bloco condicional, o arquivo MigrationTool.tsx, e este comentário.
 */
const rootElement = document.getElementById("root");
if (!rootElement) throw new Error("Elemento #root não encontrado no index.html");

const ehRotaDeMigracao = new URLSearchParams(window.location.search).has("migrar");

createRoot(rootElement).render(
  <StrictMode>{ehRotaDeMigracao ? <MigrationTool /> : <App />}</StrictMode>,
);
