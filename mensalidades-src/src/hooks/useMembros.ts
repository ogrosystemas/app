import { useLiveQuery } from "dexie-react-hooks";
import { db } from "../db/db";
import type { EditarMembroInput, Membro, NovoMembroInput } from "../types";
import { competenciaAtualComoStringAnoMes, hojeISO } from "../utils/date.utils";

export interface UseMembrosResult {
  /** Lista de todos os membros cadastrados, ordenada por apelido. Undefined enquanto carrega. */
  membros: Membro[];

  /** true enquanto a leitura inicial do banco ainda não retornou. */
  carregando: boolean;

  /** Cadastra um novo membro. Data de ingresso é fixada como hoje. Retorna o id gerado. */
  criarMembro: (input: NovoMembroInput) => Promise<number>;

  /** Atualiza campos de um membro existente (nome, apelido). */
  editarMembro: (id: number, input: EditarMembroInput) => Promise<void>;

  /**
   * Marca o membro como afastado a partir do mês corrente: a partir desta competência
   * (inclusive) ele para de gerar novas pendências, mas qualquer dívida anterior é mantida.
   */
  afastarMembro: (id: number) => Promise<void>;

  /**
   * Reverte o afastamento, voltando o membro a "ativo". A cobrança volta a contar
   * normalmente a partir do mês corrente — os meses durante o afastamento não retroagem.
   */
  reativarMembro: (id: number) => Promise<void>;

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
      dataIngresso: hojeISO(),
      status: "ativo",
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

  async function afastarMembro(id: number): Promise<void> {
    await db.membros.update(id, {
      status: "afastado",
      competenciaAfastamento: competenciaAtualComoStringAnoMes(),
      atualizadoEm: Date.now(),
    });
  }

  async function reativarMembro(id: number): Promise<void> {
    await db.membros.update(id, {
      status: "ativo",
      competenciaAfastamento: undefined,
      atualizadoEm: Date.now(),
    });
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
    afastarMembro,
    reativarMembro,
    excluirMembro,
  };
}
