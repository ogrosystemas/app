import { getDoc } from "firebase/firestore";
import { useEffect, useState } from "react";
import { refAcesso, refMembro } from "../db/refs";
import type { Membro } from "../types";

export type ResultadoAcesso =
  | { status: "verificando" }
  | { status: "nao-vinculado" }
  | { status: "vinculado"; clubeId: string; membro: Membro };

/**
 * Verifica se o e-mail informado está vinculado a um membro específico, dentro de
 * UMA sede (acesso individual de integrante comum, somente leitura — ver
 * MemberSelfView). Usado pelo App.tsx como segunda tentativa de autorização,
 * depois que a tentativa de acesso administrativo já falhou.
 *
 * Funciona em duas etapas: primeiro lê o documento "porteiro" em acessos/{email}
 * (que contém clubeId + membroId vinculados), depois busca o membro correspondente
 * NAQUELA sede específica. Qualquer falha de permissão em qualquer uma das etapas
 * é tratada como "não vinculado" — este hook nunca lança erro para o chamador.
 */
export function useAcessoMembro(email: string | null): ResultadoAcesso {
  const [resultado, setResultado] = useState<ResultadoAcesso>({ status: "verificando" });

  useEffect(() => {
    if (!email) {
      setResultado({ status: "nao-vinculado" });
      return;
    }

    let cancelado = false;
    setResultado({ status: "verificando" });
    const emailConfirmado: string = email;

    async function verificar() {
      try {
        const acessoSnapshot = await getDoc(refAcesso(emailConfirmado));
        if (!acessoSnapshot.exists()) {
          if (!cancelado) setResultado({ status: "nao-vinculado" });
          return;
        }

        const { clubeId, membroId } = acessoSnapshot.data();
        const membroSnapshot = await getDoc(refMembro(clubeId, membroId));
        if (!membroSnapshot.exists()) {
          if (!cancelado) setResultado({ status: "nao-vinculado" });
          return;
        }

        if (!cancelado) {
          setResultado({
            status: "vinculado",
            clubeId,
            membro: { ...membroSnapshot.data(), id: membroSnapshot.id },
          });
        }
      } catch {
        if (!cancelado) setResultado({ status: "nao-vinculado" });
      }
    }

    verificar();
    return () => {
      cancelado = true;
    };
  }, [email]);

  return resultado;
}
