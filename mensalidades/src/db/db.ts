import Dexie, { type EntityTable } from "dexie";
import type { Membro, Pagamento, ConfigClube } from "../types";
import { seedDatabase } from "./seed";

/**
 * Instância principal do banco de dados local (IndexedDB via Dexie).
 *
 * Schema:
 * - membros: cadastro dos integrantes do clube.
 * - pagamentos: cada baixa de mensalidade, atrelada a membroId + mes + ano.
 * - config: registro único (id fixo = 1) com configurações gerais do clube.
 *
 * Índices compostos `[membroId+ano+mes]` e `[ano+mes]` em `pagamentos` são
 * essenciais para a performance das duas consultas mais frequentes do app:
 *  1) "este membro já pagou a competência X/Y?"
 *  2) "quem pagou a competência X/Y?" (usado no dashboard do mês atual)
 */
export class MutantesDB extends Dexie {
  membros!: EntityTable<Membro, "id">;
  pagamentos!: EntityTable<Pagamento, "id">;
  config!: EntityTable<ConfigClube, "id">;

  constructor() {
    super("mutantes-mc-db");

    this.version(1).stores({
      membros: "++id, nome, apelido, status, dataIngresso",
      pagamentos: "++id, membroId, [membroId+ano+mes], [ano+mes], ano, mes",
      config: "++id",
    });
  }
}

export const db = new MutantesDB();

/**
 * Garante que o banco tenha ao menos a configuração padrão e,
 * em ambiente de desenvolvimento sem dados, popula membros/pagamentos fictícios.
 * Deve ser chamado uma única vez na inicialização do app (ver main.tsx).
 */
export async function initDatabase(): Promise<void> {
  await seedDatabase(db);
}
