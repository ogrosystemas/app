import { collection, doc, type CollectionReference, type DocumentReference } from "firebase/firestore";
import { db } from "../firebase/config";
import type { AvisoPagamento, ConfigClube, Membro, Pagamento } from "../types";

/**
 * ID fixo do (único) clube atendido por este app. Não há suporte a múltiplos
 * clubes — toda a estrutura de dados vive sob este caminho único no Firestore:
 *
 *   clubes/mutantes-mc                  (documento com a config: nome, valor da mensalidade)
 *   clubes/mutantes-mc/membros/{id}     (subcoleção de membros)
 *   clubes/mutantes-mc/pagamentos/{id}  (subcoleção de pagamentos)
 *   clubes/mutantes-mc/avisos/{id}      (subcoleção de avisos informais de pagamento)
 *
 * A coleção `acessos/{email}` fica de propósito FORA do caminho do clube, na raiz
 * do banco — ela funciona como um "porteiro" independente: as regras de segurança
 * consultam esse documento (cuja própria existência e ID já são a verificação)
 * para decidir se um e-mail de integrante comum pode ler o documento daquele
 * membro específico, sem precisar (nem poder, com segurança) fazer uma query
 * arbitrária dentro da subcoleção de membros.
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

/** Referência à subcoleção de avisos informais de pagamento do clube. */
export function refAvisos(): CollectionReference<AvisoPagamento> {
  return collection(db, "clubes", ID_CLUBE, "avisos") as CollectionReference<AvisoPagamento>;
}

/** Referência a um documento de aviso específico. */
export function refAviso(avisoId: string): DocumentReference<AvisoPagamento> {
  return doc(db, "clubes", ID_CLUBE, "avisos", avisoId) as DocumentReference<AvisoPagamento>;
}

/**
 * Documento "porteiro" de acesso individual: vincula um e-mail a um membroId.
 * O e-mail é usado como o próprio ID do documento (em minúsculas — ver
 * normalizarEmail) para que as regras de segurança consigam checar a
 * existência desse vínculo com um simples `get`, sem precisar de query.
 */
export interface AcessoMembro {
  membroId: string;
}

export function refAcesso(email: string): DocumentReference<AcessoMembro> {
  return doc(db, "acessos", normalizarEmail(email)) as DocumentReference<AcessoMembro>;
}

/** Normaliza um e-mail para uso como ID de documento (minúsculas, sem espaços nas pontas). */
export function normalizarEmail(email: string): string {
  return email.trim().toLowerCase();
}
