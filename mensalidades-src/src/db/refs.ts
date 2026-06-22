import { collection, doc, type CollectionReference, type DocumentReference } from "firebase/firestore";
import { db } from "../firebase/config";
import type { ConfigClube, Membro, Pagamento } from "../types";

/**
 * ID fixo do (único) clube atendido por este app. Não há suporte a múltiplos
 * clubes — toda a estrutura de dados vive sob este caminho único no Firestore:
 *
 *   clubes/mutantes-mc                  (documento com a config: nome, valor da mensalidade)
 *   clubes/mutantes-mc/membros/{id}     (subcoleção de membros)
 *   clubes/mutantes-mc/pagamentos/{id}  (subcoleção de pagamentos)
 */
export const ID_CLUBE = "mutantes-mc";

/** Referência ao documento raiz do clube (onde vive a ConfigClube). */
export function refClube(): DocumentReference<ConfigClube> {
  return doc(db, "clubes", ID_CLUBE) as DocumentReference<ConfigClube>;
}

/** Referência à subcoleção de membros do clube. */
export function refMembros(): CollectionReference<Membro> {
  return collection(db, "clubes", ID_CLUBE, "membros") as CollectionReference<Membro>;
}

/** Referência a um documento de membro específico. */
export function refMembro(membroId: string): DocumentReference<Membro> {
  return doc(db, "clubes", ID_CLUBE, "membros", membroId) as DocumentReference<Membro>;
}

/** Referência à subcoleção de pagamentos do clube. */
export function refPagamentos(): CollectionReference<Pagamento> {
  return collection(db, "clubes", ID_CLUBE, "pagamentos") as CollectionReference<Pagamento>;
}

/** Referência a um documento de pagamento específico. */
export function refPagamento(pagamentoId: string): DocumentReference<Pagamento> {
  return doc(db, "clubes", ID_CLUBE, "pagamentos", pagamentoId) as DocumentReference<Pagamento>;
}
