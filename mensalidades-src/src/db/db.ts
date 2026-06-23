import { getDoc, getDocs, setDoc } from "firebase/firestore";
import { refClube, refMembros } from "./refs";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";

/**
 * Verifica se o usuário autenticado tem acesso de ADMINISTRADOR — distinto de
 * apenas conseguir ler o documento de configuração do clube, que TAMBÉM é
 * permitido para um integrante comum vinculado a um membro (ver firestore.rules,
 * regra de leitura de `clubes/{clubeId}`: `emailAutorizado() ||
 * temAcessoDeIntegranteQualquer()`). Usar getDoc(refClube()) como teste de admin
 * foi um bug real já cometido aqui: funcionava enquanto a checagem de
 * integrante estava quebrada (bug de escopo nas regras, já corrigido), mas
 * passou a aprovar integrantes comuns como se fossem administradores assim que
 * aquele bug foi corrigido.
 *
 * O teste correto é tentar uma operação que SÓ administradores conseguem fazer:
 * uma leitura de LISTA (query) da coleção de membros. A regra de segurança para
 * `list` exige que TODO documento do resultado satisfaça a condição de acesso —
 * como um integrante comum só está vinculado a UM membroId específico, ele nunca
 * passa esse teste para a coleção inteira, mesmo que consiga ler o próprio
 * documento individualmente. Administradores, com `emailAutorizado()` sempre
 * verdadeiro, sempre passam.
 */
export async function verificarAcessoAdmin(): Promise<boolean> {
  try {
    await getDocs(refMembros());
    return true;
  } catch {
    return false;
  }
}

/**
 * Garante que o documento de configuração do clube (clubes/mutantes-mc) existe
 * no Firestore. Se for a primeira vez que o app conecta a este projeto Firebase,
 * cria a configuração inicial (nome do clube + valor padrão da mensalidade) —
 * mas NUNCA popula membros ou pagamentos fictícios: o banco real do clube
 * começa vazio, pronto para receber os dados reais.
 *
 * Só deve ser chamada DEPOIS de verificarAcessoAdmin() confirmar acesso de
 * administrador — chamar antes disso é inócuo para um admin real, mas não deve
 * ser usado como teste de autorização (ver verificarAcessoAdmin).
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
