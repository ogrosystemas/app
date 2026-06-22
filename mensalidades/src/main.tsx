import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import { initDatabase } from "./db/db";
import "./index.css";

/**
 * Garante que o banco (config singleton + dados fictícios, se vazio) esteja
 * pronto ANTES do primeiro render — evita flicker de estado vazio no dashboard.
 */
async function bootstrap() {
  await initDatabase();

  const rootElement = document.getElementById("root");
  if (!rootElement) throw new Error("Elemento #root não encontrado no index.html");

  createRoot(rootElement).render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}

bootstrap().catch((erro: unknown) => {
  console.error("Falha ao inicializar o banco de dados local:", erro);
});
