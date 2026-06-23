import { getDoc, setDoc } from "firebase/firestore";
import { refClube } from "./refs";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";

/**
 * Garante que o documento de configuração do clube (clubes/mutantes-mc) existe
 * no Firestore. Se for a primeira vez que o app conecta a este projeto Firebase,
 * cria a configuração inicial (nome do clube + valor padrão da mensalidade) —
 * mas NUNCA popula membros ou pagamentos fictícios: o banco real do clube
 * começa vazio, pronto para receber os dados reais.
 *
 * Deve ser chamada uma única vez na inicialização do app, depois que o usuário
 * já está autenticado (ver main.tsx) — antes disso as regras de segurança do
 * Firestore rejeitariam a leitura/escrita.
 */
export async function initDatabase(): Promise<void> {
  const ref = refClube();
  const snapshot = await getDoc(ref);

  if (!snapshot.exists()) {
    await setDoc(ref, {
      nomeClube: NOME_CLUBE_PADRAO,
      valorMensalidade: VALOR_MENSALIDADE_PADRAO,
      atualizadoEm: Date.now(),
    });
  }
}
