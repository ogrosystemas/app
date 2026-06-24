import { getDoc, getDocs, setDoc } from "firebase/firestore";
import { refAdministrador, refClube, refSede, refSedes, SUPER_ADMIN_CLUBE_ID } from "./refs";
import { NOME_CLUBE_PADRAO, VALOR_MENSALIDADE_PADRAO } from "../constants/theme.constants";
import type { NovaSedeInput } from "../types";

export type ResultadoAcessoAdmin =
  | { tipo: "super-admin" }
  | { tipo: "admin-sede"; clubeId: string }
  | { tipo: "sem-acesso" };

/**
 * Verifica se o e-mail informado tem acesso de ADMINISTRADOR — e, se sim, de qual
 * tipo: super admin (todas as sedes) ou tesoureiro de uma sede específica.
 *
 * A fonte da verdade é o documento `administradores/{email}` (ver refs.ts e
 * firestore.rules) — não uma tentativa de leitura "às cegas" da coleção de
 * membros, que era a abordagem anterior (antes de existir multi-sede) e
 * dependia de capturar exceções de permissão para inferir o tipo de acesso.
 * Ler o vínculo diretamente é mais simples e explícito agora que sabemos
 * exatamente onde essa informação mora.
 */
export async function verificarAcessoAdmin(email: string): Promise<ResultadoAcessoAdmin> {
  try {
    const snapshot = await getDoc(refAdministrador(email));
    if (!snapshot.exists()) {
      return { tipo: "sem-acesso" };
    }
    const { clubeId } = snapshot.data();
    if (clubeId === SUPER_ADMIN_CLUBE_ID) {
      return { tipo: "super-admin" };
    }
    return { tipo: "admin-sede", clubeId };
  } catch {
    return { tipo: "sem-acesso" };
  }
}

/**
 * Garante que o documento de configuração de uma sede (clubes/{clubeId}) existe
 * no Firestore. Se for a primeira vez que essa sede é acessada, cria a configuração
 * inicial (nome padrão + valor padrão da mensalidade) — mas NUNCA popula membros ou
 * pagamentos fictícios: cada sede começa vazia, pronta para receber os dados reais.
 *
 * Só deve ser chamada DEPOIS de confirmado acesso de administrador àquela sede
 * específica (ou de super admin) — ver verificarAcessoAdmin.
 */
export async function initDatabaseDaSede(clubeId: string, nomeSede: string): Promise<void> {
  const ref = refClube(clubeId);
  const snapshot = await getDoc(ref);

  if (!snapshot.exists()) {
    await setDoc(ref, {
      nomeClube: `${NOME_CLUBE_PADRAO} — ${nomeSede}`,
      valorMensalidade: VALOR_MENSALIDADE_PADRAO,
      atualizadoEm: Date.now(),
    });
  }
}

/**
 * Lista todas as sedes existentes — usado pela tela de Super Admin (escolher qual
 * sede administrar) e pelo fluxo de criação de novas sedes (verificar se um ID já
 * está em uso). Só super admins têm permissão de fazer essa leitura em lista (ver
 * firestore.rules) — tesoureiros de sede comum nunca precisam saber quais outras
 * sedes existem.
 */
export async function listarSedes() {
  const snapshot = await getDocs(refSedes());
  return snapshot.docs.map((d) => ({ ...d.data(), id: d.id }));
}

/**
 * Cria uma nova sede por completo: metadados em `sedes/{id}`, config inicial em
 * `clubes/{id}` (nome + valor de mensalidade), e já vincula o e-mail do tesoureiro
 * informado como administrador DESSA sede específica (`administradores/{email}` ->
 * `{ clubeId: id }`) — tudo numa única operação, exclusiva do Super Admin (ver
 * firestore.rules). Lança erro se o ID já estiver em uso, para nunca sobrescrever
 * silenciosamente uma sede existente.
 */
export async function criarSede(input: NovaSedeInput): Promise<void> {
  const sedeExistente = await getDoc(refSede(input.id));
  if (sedeExistente.exists()) {
    throw new Error(`Já existe uma sede com o ID "${input.id}".`);
  }

  const agora = Date.now();

  await setDoc(refSede(input.id), {
    nome: input.nome,
    criadoEm: agora,
  });

  await setDoc(refClube(input.id), {
    nomeClube: `${NOME_CLUBE_PADRAO} — ${input.nome}`,
    valorMensalidade: input.valorMensalidade,
    atualizadoEm: agora,
  });

  await setDoc(refAdministrador(input.emailTesoureiro), {
    clubeId: input.id,
  });
}
