import { useLiveQuery } from "dexie-react-hooks";
import { db } from "../db/db";
import type { EditarMembroInput, Membro, NovoMembroInput } from "../types";

export interface UseMembrosResult {
  /** Lista de todos os membros cadastrados, ordenada por apelido. Undefined enquanto carrega. */
  membros: Membro[];

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Cadastra um novo membro. Retorna o id gerado. */
  criarMembro: (input: NovoMembroInput) => Promise<number>;

  /** Atualiza campos de um membro existente. */
  editarMembro: (id: number, input: EditarMembroInput) => Promise<void>;

  /** Alterna o status do membro entre ativo/inativo. */
  alternarStatusMembro: (id: number) => Promise<void>;

  /** Remove definitivamente um membro e todo o seu histórico de pagamentos. */
  excluirMembro: (id: number) => Promise<void>;
}

/**
 * Hook de acesso e mutação da entidade Membro.
 * Toda a lógica de persistência fica aqui — os componentes de UI nunca falam com `db` diretamente.
 */
export function useMembros(): UseMembrosResult {
  const membros = useLiveQuery(
    () => db.membros.orderBy("apelido").toArray(),
    [],
  );

  async function criarMembro(input: NovoMembroInput): Promise<number> {
    const agora = Date.now();
    const id = await db.membros.add({
      ...input,
      criadoEm: agora,
      atualizadoEm: agora,
    });
    // O Dexie sempre retorna a PK gerada após um insert bem-sucedido;
    // o `| undefined` no tipo decorre apenas de `Membro.id` ser opcional na entidade.
    return id as number;
  }

  async function editarMembro(id: number, input: EditarMembroInput): Promise<void> {
    await db.membros.update(id, {
      ...input,
      atualizadoEm: Date.now(),
    });
  }

  async function alternarStatusMembro(id: number): Promise<void> {
    const membro = await db.membros.get(id);
    if (!membro) return;
    const novoStatus = membro.status === "ativo" ? "inativo" : "ativo";
    await db.membros.update(id, { status: novoStatus, atualizadoEm: Date.now() });
  }

  async function excluirMembro(id: number): Promise<void> {
    await db.transaction("rw", db.membros, db.pagamentos, async () => {
      await db.pagamentos.where("membroId").equals(id).delete();
      await db.membros.delete(id);
    });
  }

  return {
    membros: membros ?? [],
    carregando: membros === undefined,
    criarMembro,
    editarMembro,
    alternarStatusMembro,
    excluirMembro,
  };
}
