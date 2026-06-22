import { useEffect, useState } from "react";
import { AccessDeniedScreen, LoginScreen } from "./components/auth";
import { initDatabase } from "./db/db";
import { useAuth } from "./hooks/useAuth";
import { MainApp } from "./MainApp";

type EstadoInicializacao = "verificando" | "autorizado" | "negado";

/**
 * Componente raiz: decide o que renderizar de acordo com o estado de autenticação:
 *
 * 1. Verificando sessão (useAuth.carregando) -> tela em branco/loading mínimo.
 * 2. Sem usuário logado -> LoginScreen (botão "Entrar com Google").
 * 3. Logado, mas checando autorização -> idem (rápido, mas evita flash da tela errada).
 * 4. Logado e SEM permissão nos dados do clube (regras do Firestore rejeitam) ->
 *    AccessDeniedScreen, com opção de tentar outra conta.
 * 5. Logado e autorizado -> MainApp (o app de conferência propriamente dito).
 *
 * A autorização (passo 4) não é decidida aqui — é decidida pelas regras de segurança
 * do Firestore (ver firestore.rules): este componente apenas TENTA inicializar o
 * banco (initDatabase) e trata a rejeição de permissão como "não autorizado".
 */
export default function App() {
  const { usuario, carregando: carregandoAuth, entrarComGoogle, sair, erro } = useAuth();
  const [estadoInicializacao, setEstadoInicializacao] = useState<EstadoInicializacao>("verificando");
  const [entrando, setEntrando] = useState(false);

  useEffect(() => {
    if (!usuario) {
      setEstadoInicializacao("verificando");
      return;
    }

    let cancelado = false;
    setEstadoInicializacao("verificando");

    initDatabase()
      .then(() => {
        if (!cancelado) setEstadoInicializacao("autorizado");
      })
      .catch((erroInicializacao: unknown) => {
        console.error("Falha ao inicializar o banco (provável falta de autorização):", erroInicializacao);
        if (!cancelado) setEstadoInicializacao("negado");
      });

    return () => {
      cancelado = true;
    };
  }, [usuario]);

  async function handleEntrar() {
    setEntrando(true);
    try {
      await entrarComGoogle();
    } finally {
      setEntrando(false);
    }
  }

  if (carregandoAuth) {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (!usuario) {
    return <LoginScreen onEntrar={handleEntrar} erro={erro} entrando={entrando} />;
  }

  if (estadoInicializacao === "negado") {
    return <AccessDeniedScreen email={usuario.email} onSair={sair} />;
  }

  if (estadoInicializacao === "verificando") {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  return <MainApp emailLogado={usuario.email} onSair={sair} />;
}
