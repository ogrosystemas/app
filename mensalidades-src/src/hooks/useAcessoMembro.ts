import { getDoc } from "firebase/firestore";
import { useEffect, useRef, useState } from "react";
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
 *
 * IMPORTANTE — bug real já corrigido aqui, não repetir: quando `email` é `null`
 * (ex: o chamador ainda não decidiu se deve tentar essa verificação), este hook
 * antes retornava IMEDIATAMENTE `{ status: "nao-vinculado" }`, de forma síncrona,
 * sem nenhuma tentativa real de leitura. Isso é um resultado FINAL e DEFINITIVO
 * sendo produzido sem nenhuma verificação de fato ter ocorrido — diferente de
 * "verificando", que sinaliza corretamente "ainda não sei". Combinado com uma
 * particularidade conhecida do Firebase Auth (onAuthStateChanged pode disparar
 * mais de uma vez em sequência rápida ao reabrir o app, ver
 * https://github.com/firebase/firebase-js-sdk/issues/7049), isso criava uma
 * janela real onde o componente pai (App.tsx) podia renderizar com o e-mail já
 * disponível, mas este hook ainda refletindo o "nao-vinculado" instantâneo do
 * render anterior (quando email ainda era null) — fazendo a tela
 * "Acesso não autorizado" aparecer por engano, de forma intermitente, antes da
 * verificação real ter qualquer chance de rodar. A correção: quando `email` é
 * `null`, o hook agora retorna "verificando" (nunca um resultado definitivo sem
 * checagem real) — o estado "nao-vinculado" só é produzido depois de uma
 * tentativa de leitura genuína (sucesso retornando vazio, ou erro de permissão).
 */
export function useAcessoMembro(email: string | null): ResultadoAcesso {
  const [resultado, setResultado] = useState<ResultadoAcesso>({ status: "verificando" });
  // Guarda o e-mail da verificação mais recente DISPARADA (não necessariamente a
  // que já terminou) — usado para descartar resultados de chamadas obsoletas que
  // terminam depois de uma chamada mais nova já ter sido disparada, mesmo que a
  // mais nova ainda não tenha concluído. Mais robusto que apenas a flag local
  // `cancelado` de cada execução do efeito, porque sobrevive entre execuções.
  const emailMaisRecenteRef = useRef<string | null>(null);

  useEffect(() => {
    emailMaisRecenteRef.current = email;

    if (!email) {
      // Ainda não sabemos se vamos tentar essa verificação (chamador não decidiu,
      // ou estamos no meio de uma transição de estado) — "verificando" é o único
      // resultado honesto aqui, nunca "nao-vinculado" sem nenhuma tentativa real.
      setResultado({ status: "verificando" });
      return;
    }

    setResultado({ status: "verificando" });
    const emailDestaExecucao = email;

    async function verificar() {
      function aindaEhAVerificacaoAtual(): boolean {
        return emailMaisRecenteRef.current === emailDestaExecucao;
      }

      try {
        const acessoSnapshot = await getDoc(refAcesso(emailDestaExecucao));
        if (!acessoSnapshot.exists()) {
          if (aindaEhAVerificacaoAtual()) setResultado({ status: "nao-vinculado" });
          return;
        }

        const { clubeId, membroId } = acessoSnapshot.data();
        const membroSnapshot = await getDoc(refMembro(clubeId, membroId));
        if (!membroSnapshot.exists()) {
          if (aindaEhAVerificacaoAtual()) setResultado({ status: "nao-vinculado" });
          return;
        }

        if (aindaEhAVerificacaoAtual()) {
          setResultado({
            status: "vinculado",
            clubeId,
            membro: { ...membroSnapshot.data(), id: membroSnapshot.id },
          });
        }
      } catch {
        if (aindaEhAVerificacaoAtual()) setResultado({ status: "nao-vinculado" });
      }
    }

    verificar();
  }, [email]);

  return resultado;
}
