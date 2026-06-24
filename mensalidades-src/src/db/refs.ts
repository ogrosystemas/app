import { collection, doc, type CollectionReference, type DocumentReference } from "firebase/firestore";
import { db } from "../firebase/config";
import type { Administrador, AvisoPagamento, ConfigClube, Membro, Pagamento, Sede } from "../types";

/**
 * Estrutura multi-sede do Firestore: cada sede (clube) do Mutantes Moto Clube vive
 * isolada sob seu próprio clubeId — não há nenhum dado compartilhado entre sedes.
 *
 *   sedes/{clubeId}                       (metadados: nome, data de criação — usado
 *                                          pela tela de Super Admin para listar/criar sedes)
 *   clubes/{clubeId}                      (config da sede: nome exibido, valor da
 *                                          mensalidade, dados do Pix)
 *   clubes/{clubeId}/membros/{id}         (subcoleção de membros DESSA sede)
 *   clubes/{clubeId}/pagamentos/{id}      (subcoleção de pagamentos DESSA sede)
 *   clubes/{clubeId}/avisos/{id}          (subcoleção de avisos informais DESSA sede)
 *
 * Duas coleções na raiz, fora do caminho de qualquer sede — funcionam como "porteiros"
 * independentes, consultados pelas regras de segurança do Firestore:
 *
 *   administradores/{email}  -> { clubeId }   (clubeId real, ou "*" para super admin)
 *   acessos/{email}          -> { clubeId, membroId }   (integrante comum vinculado a
 *                                                          UM membro de UMA sede específica)
 */

/** ID reservado de super admin — administra TODAS as sedes (ver Administrador.clubeId). */
export const SUPER_ADMIN_CLUBE_ID = "*";

// ---------------------------------------------------------------------------
// Sedes (metadados centrais — não confundir com a config de cada sede)
// ---------------------------------------------------------------------------

/** Referência à coleção de metadados de todas as sedes (usada pela tela de Super Admin). */
export function refSedes(): CollectionReference<Sede> {
  return collection(db, "sedes") as CollectionReference<Sede>;
}

/** Referência ao documento de metadados de uma sede específica. */
export function refSede(clubeId: string): DocumentReference<Sede> {
  return doc(db, "sedes", clubeId) as DocumentReference<Sede>;
}

// ---------------------------------------------------------------------------
// Dados de uma sede específica (sempre parametrizados por clubeId)
// ---------------------------------------------------------------------------

/** Referência ao documento de config de uma sede (onde vive a ConfigClube). */
export function refClube(clubeId: string): DocumentReference<ConfigClube> {
  return doc(db, "clubes", clubeId) as DocumentReference<ConfigClube>;
}

/** Referência à subcoleção de membros de uma sede. */
export function refMembros(clubeId: string): CollectionReference<Membro> {
  return collection(db, "clubes", clubeId, "membros") as CollectionReference<Membro>;
}

/** Referência a um documento de membro específico, dentro de uma sede. */
export function refMembro(clubeId: string, membroId: string): DocumentReference<Membro> {
  return doc(db, "clubes", clubeId, "membros", membroId) as DocumentReference<Membro>;
}

/** Referência à subcoleção de pagamentos de uma sede. */
export function refPagamentos(clubeId: string): CollectionReference<Pagamento> {
  return collection(db, "clubes", clubeId, "pagamentos") as CollectionReference<Pagamento>;
}

/** Referência a um documento de pagamento específico, dentro de uma sede. */
export function refPagamento(clubeId: string, pagamentoId: string): DocumentReference<Pagamento> {
  return doc(db, "clubes", clubeId, "pagamentos", pagamentoId) as DocumentReference<Pagamento>;
}

/** Referência à subcoleção de avisos informais de pagamento de uma sede. */
export function refAvisos(clubeId: string): CollectionReference<AvisoPagamento> {
  return collection(db, "clubes", clubeId, "avisos") as CollectionReference<AvisoPagamento>;
}

/** Referência a um documento de aviso específico, dentro de uma sede. */
export function refAviso(clubeId: string, avisoId: string): DocumentReference<AvisoPagamento> {
  return doc(db, "clubes", clubeId, "avisos", avisoId) as DocumentReference<AvisoPagamento>;
}

// ---------------------------------------------------------------------------
// Coleções "porteiro" (administradores e acessos de integrantes) — raiz do banco
// ---------------------------------------------------------------------------

/**
 * Referência ao documento que vincula um e-mail a uma sede como ADMINISTRADOR
 * (tesoureiro) dessa sede — ou a todas as sedes, se clubeId for SUPER_ADMIN_CLUBE_ID.
 */
export function refAdministrador(email: string): DocumentReference<Administrador> {
  return doc(db, "administradores", normalizarEmail(email)) as DocumentReference<Administrador>;
}

/**
 * Documento "porteiro" de acesso individual de um INTEGRANTE COMUM: vincula um
 * e-mail a um membroId dentro de uma sede específica. O e-mail é usado como o
 * próprio ID do documento (em minúsculas — ver normalizarEmail) para que as
 * regras de segurança consigam checar a existência desse vínculo com um simples
 * `get`, sem precisar de query.
 */
export interface AcessoMembro {
  clubeId: string;
  membroId: string;
}

export function refAcesso(email: string): DocumentReference<AcessoMembro> {
  return doc(db, "acessos", normalizarEmail(email)) as DocumentReference<AcessoMembro>;
}

/** Normaliza um e-mail para uso como ID de documento (minúsculas, sem espaços nas pontas). */
export function normalizarEmail(email: string): string {
  return email.trim().toLowerCase();
}
