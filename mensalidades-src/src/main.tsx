import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

/**
 * Diferente da versão anterior (com Dexie local), a inicialização do banco
 * (initDatabase, em db/db.ts) agora depende de autenticação — as regras de
 * segurança do Firestore rejeitam qualquer leitura/escrita de quem não está
 * logado. Por isso essa chamada NÃO acontece mais aqui: ela é feita dentro de
 * App.tsx, somente após o login com Google ser confirmado (ver useAuth).
 */
const rootElement = document.getElementById("root");
if (!rootElement) throw new Error("Elemento #root não encontrado no index.html");

createRoot(rootElement).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
