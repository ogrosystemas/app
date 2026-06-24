import { useEffect, useState } from "react";
import {
  AccessDeniedScreen,
  LoginScreen,
  NewSedeModal,
  SedeSelectionScreen,
} from "./components/auth";
import { MemberSelfView } from "./components/self";
import { criarSede, initDatabaseDaSede, listarSedes, verificarAcessoAdmin } from "./db/db";
import { useAcessoMembro } from "./hooks/useAcessoMembro";
import { useAuth } from "./hooks/useAuth";
import { useConfig } from "./hooks/useConfig";
import { MainApp } from "./MainApp";
import type { NovaSedeInput, Sede } from "./types";

type EstadoAcesso =
  | { tipo: "verificando" }
  | { tipo: "super-admin"; clubeIdEscolhido: string | null }
  | { tipo: "admin-sede"; clubeId: string }
  | { tipo: "tentando-integrante" }
  | { tipo: "negado" };

/**
 * Componente raiz: decide o que renderizar de acordo com o estado de autenticação
 * E autorização. Existem TRÊS níveis de acesso, numa estrutura multi-sede (cada
 * sede do Mutantes Moto Clube é isolada das demais — ver db/refs.ts):
 *
 * - SUPER ADMIN: administra TODAS as sedes (administradores/{email} com
 *   clubeId="*"). Depois do login, escolhe qual sede administrar nesta sessão
 *   (SedeSelectionScreen), com opção de criar uma sede nova.
 * - ADMIN DE SEDE (tesoureiro): administra SOMENTE a própria sede
 *   (administradores/{email} com clubeId = ID da sede). Entra direto no MainApp
 *   daquela sede, sem nenhuma tela de escolha.
 * - INTEGRANTE COMUM: e-mail vinculado a um membro específico de UMA sede
 *   (acessos/{email}). Vê somente leitura do próprio status/histórico daquela
 *   sede (MemberSelfView).
 *
 * Fluxo de decisão, em ordem:
 * 1. Verificando sessão -> tela em branco/loading mínimo.
 * 2. Sem usuário logado -> LoginScreen.
 * 3. Verifica acesso de administrador (verificarAcessoAdmin, que lê
 *    administradores/{email} diretamente — não mais um teste indireto via
 *    tentativa de leitura). Três resultados possíveis: super-admin, admin de
 *    uma sede específica, ou sem acesso administrativo.
 * 4. Se super-admin -> mostra SedeSelectionScreen até uma sede ser escolhida.
 * 5. Se admin de sede -> garante a config inicial daquela sede e renderiza MainApp.
 * 6. Se sem acesso administrativo -> tenta acesso de INTEGRANTE (useAcessoMembro).
 * 7. Se nenhum dos três -> AccessDeniedScreen.
 */
export default function App() {
  const { usuario, carregando: carregandoAuth, entrarComGoogle, sair, erro } = useAuth();
  const [estado, setEstado] = useState<EstadoAcesso>({ tipo: "verificando" });
  const [entrando, setEntrando] = useState(false);
  const [sedes, setSedes] = useState<Sede[]>([]);
  const [carregandoSedes, setCarregandoSedes] = useState(false);
  const [modalNovaSedeAberto, setModalNovaSedeAberto] = useState(false);

  const acessoMembro = useAcessoMembro(
    estado.tipo === "tentando-integrante" ? (usuario?.email ?? null) : null,
  );

  // A config (valor da mensalidade) só é necessária para o MemberSelfView calcular
  // o status corretamente — useConfig já lida com permissão própria via Firestore.
  // Só faz sentido buscar quando já sabemos a qual sede o integrante pertence.
  const { config } = useConfig(
    acessoMembro.status === "vinculado" ? acessoMembro.clubeId : "_aguardando_",
  );

  useEffect(() => {
    if (!usuario) {
      setEstado({ tipo: "verificando" });
      return;
    }

    let cancelado = false;
    setEstado({ tipo: "verificando" });

    verificarAcessoAdmin(usuario.email ?? "")
      .then(async (resultado) => {
        if (cancelado) return;
        if (resultado.tipo === "super-admin") {
          setEstado({ tipo: "super-admin", clubeIdEscolhido: null });
        } else if (resultado.tipo === "admin-sede") {
          await initDatabaseDaSede(resultado.clubeId, resultado.clubeId);
          if (!cancelado) setEstado({ tipo: "admin-sede", clubeId: resultado.clubeId });
        } else {
          setEstado({ tipo: "tentando-integrante" });
        }
      })
      .catch((erroVerificacao: unknown) => {
        console.error("Falha ao verificar acesso administrativo (tentando acesso de integrante a seguir):", erroVerificacao);
        if (!cancelado) setEstado({ tipo: "tentando-integrante" });
      });

    return () => {
      cancelado = true;
    };
  }, [usuario]);

  useEffect(() => {
    if (estado.tipo !== "super-admin" || estado.clubeIdEscolhido !== null) return;

    let cancelado = false;
    setCarregandoSedes(true);
    listarSedes()
      .then((lista) => {
        if (!cancelado) setSedes(lista);
      })
      .finally(() => {
        if (!cancelado) setCarregandoSedes(false);
      });

    return () => {
      cancelado = true;
    };
  }, [estado]);

  async function handleEntrar() {
    setEntrando(true);
    try {
      await entrarComGoogle();
    } finally {
      setEntrando(false);
    }
  }

  async function handleCriarSede(input: NovaSedeInput) {
    await criarSede(input);
    const listaAtualizada = await listarSedes();
    setSedes(listaAtualizada);
  }

  if (carregandoAuth) {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (!usuario) {
    return <LoginScreen onEntrar={handleEntrar} erro={erro} entrando={entrando} />;
  }

  if (estado.tipo === "verificando") {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (estado.tipo === "super-admin") {
    if (estado.clubeIdEscolhido === null) {
      return (
        <>
          <SedeSelectionScreen
            sedes={sedes}
            carregando={carregandoSedes}
            onEscolherSede={(clubeId) => setEstado({ tipo: "super-admin", clubeIdEscolhido: clubeId })}
            onCriarNovaSede={() => setModalNovaSedeAberto(true)}
            onSair={sair}
          />
          <NewSedeModal
            aberto={modalNovaSedeAberto}
            onFechar={() => setModalNovaSedeAberto(false)}
            onCriar={handleCriarSede}
          />
        </>
      );
    }
    return (
      <MainApp
        clubeId={estado.clubeIdEscolhido}
        emailLogado={usuario.email}
        onSair={async () => {
          // Super admin "sai" da sede escolhida (volta para a seleção), não da conta —
          // sair de fato da conta Google é feito a partir da própria SedeSelectionScreen.
          setEstado({ tipo: "super-admin", clubeIdEscolhido: null });
        }}
      />
    );
  }

  if (estado.tipo === "admin-sede") {
    return <MainApp clubeId={estado.clubeId} emailLogado={usuario.email} onSair={sair} />;
  }

  // estado.tipo === "tentando-integrante" a partir daqui.
  if (acessoMembro.status === "verificando") {
    return <div className="min-h-screen bg-graphite-950" />;
  }

  if (acessoMembro.status === "vinculado") {
    return (
      <MemberSelfView
        clubeId={acessoMembro.clubeId}
        membro={acessoMembro.membro}
        valorMensalidade={config.valorMensalidade}
        pix={config.pix}
        onSair={sair}
      />
    );
  }

  return <AccessDeniedScreen email={usuario.email} onSair={sair} />;
}
