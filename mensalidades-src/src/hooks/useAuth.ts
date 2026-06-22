import {
  onAuthStateChanged,
  signInWithPopup,
  signOut as signOutFirebase,
  type User,
} from "firebase/auth";
import { useEffect, useState } from "react";
import { auth, googleProvider } from "../firebase/config";

export interface UseAuthResult {
  /** Usuário autenticado, ou null se não houver ninguém logado. */
  usuario: User | null;

  /** true enquanto o Firebase ainda está verificando se há uma sessão ativa. */
  carregando: boolean;

  /** Dispara o popup de login do Google. */
  entrarComGoogle: () => Promise<void>;

  /** Desconecta o usuário atual. */
  sair: () => Promise<void>;

  /** Mensagem de erro da última tentativa de login, se houver. */
  erro: string | null;
}

/**
 * Hook de autenticação. Mantém o estado do usuário logado sincronizado com o
 * Firebase Auth — uma vez autenticado, a sessão persiste entre fechamentos do
 * app (inclusive offline), graças ao comportamento padrão do SDK do Firebase.
 *
 * A autorização de QUEM pode efetivamente ler/escrever os dados do clube não
 * é feita aqui — é feita pelas regras de segurança do Firestore (ver
 * firestore.rules), que checam o e-mail autenticado contra uma lista de
 * e-mails autorizados. Este hook só lida com "está logado ou não".
 */
export function useAuth(): UseAuthResult {
  const [usuario, setUsuario] = useState<User | null>(null);
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    const cancelarInscricao = onAuthStateChanged(auth, (usuarioAtual) => {
      setUsuario(usuarioAtual);
      setCarregando(false);
    });
    return cancelarInscricao;
  }, []);

  async function entrarComGoogle(): Promise<void> {
    setErro(null);
    try {
      await signInWithPopup(auth, googleProvider);
    } catch {
      setErro("Não foi possível entrar com o Google. Tente novamente.");
    }
  }

  async function sair(): Promise<void> {
    await signOutFirebase(auth);
  }

  return { usuario, carregando, entrarComGoogle, sair, erro };
}
