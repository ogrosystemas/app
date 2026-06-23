import { useEffect, useState } from "react";
import { AccessDeniedScreen, LoginScreen } from "./components/auth";
import { MemberSelfView } from "./components/self";
import { initDatabase } from "./db/db";
import { useAcessoMembro } from "./hooks/useAcessoMembro";
import { useAuth } from "./hooks/useAuth";
import { useConfig } from "./hooks/useConfig";
import { MainApp } from "./MainApp";

type TentativaAdmin = "verificando" | "autorizado" | "negado";

/**
 * Componente raiz: decide o que renderizar de acordo com o estado de autenticação
 * E autorização. Existem dois níveis de acesso totalmente diferentes:
 *
 * - ADMINISTRADOR: e-mail está na lista de administradores em firestore.rules.
 *   Vê e edita tudo (MainApp).
 * - INTEGRANTE COMUM: e-mail está vinculado a um membro específico (campo
 *   emailAcesso no cadastro, espelhado em acessos/{email} no Firestore). Vê
 *   somente leitura do próprio status/histórico, sem valores, sem ações de
 *   administração (MemberSelfView).
 *
 * Fluxo de decisão, em ordem:
 * 1. Verificando sessão (useAuth.carregando) -> tela em branco/loading mínimo.
 * 2. Sem usuário logado -> LoginScreen.
 * 3. Tenta acesso de ADMINISTRADOR primeiro (initDatabase contra as regras do
 *    Firestore). Se passar -> MainApp.
 * 4. Se falhar (sem permissão de admin), tenta acesso de INTEGRANTE (useAcessoMembro,
 *    que consulta acessos/{email}). Se vinculado a um membro -> MemberSelfView.
 * 5. Se nenhum dos dois -> AccessDeniedScreen.
 */
export default function App() {
  const { usuario, carregando: carregandoAuth, entrarComGoogle, sair, erro } = useAuth();
  const [tentativaAdmin, setTentativaAdmin] = useState<TentativaAdmin>("verificando");
  const [entrando, setEntrando] = useState(false);

  const acessoMembro = useAcessoMembro(
    tentativaAdmin === "negado" ? (usuario?.email ?? null) : null,
  );

  // A config (valor da mensalidade) só é necessária para o MemberSelfView calcular
  // o status corretamente — useConfig já lida com permissão própria via Firestore.
  const { config } = useConfig();

  useEffect(() => {
    if (!usuario) {
      setTentativaAdmin("verificando");
      return;
    }

    let cancelado = false;
    setTentativaAdmin("verificando");

    initDatabase()
      .then(() => {
        if (!cancelado) setTentativaAdmin("autorizado");
      })
      .catch((erroInicializacao: unknown) => {
        console.error("Sem acesso administrativo (tentando acesso de integrante a seguir):", erroInicializacao);
        if (!cancelado) setTentativaAdmin("negado");
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

  if (tentativaAdmin === "verificando") {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (tentativaAdmin === "autorizado") {
    return <MainApp emailLogado={usuario.email} onSair={sair} />;
  }

  // tentativaAdmin === "negado" a partir daqui: tenta a segunda via (integrante comum).
  if (acessoMembro.status === "verificando") {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (acessoMembro.status === "vinculado") {
    return (
      <MemberSelfView membro={acessoMembro.membro} valorMensalidade={config.valorMensalidade} onSair={sair} />
    );
  }

  return <AccessDeniedScreen email={usuario.email} onSair={sair} />;
}
