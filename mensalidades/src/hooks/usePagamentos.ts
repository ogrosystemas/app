import { useLiveQuery } from "dexie-react-hooks";
import { db } from "../db/db";
import type { Competencia, FormaPagamento, Pagamento } from "../types";
import { hojeISO } from "../utils/date.utils";

export interface DarBaixaInput {
  membroId: number;
  competencia: Competencia;
  valorPago: number;
  formaPagamento: FormaPagamento;
  observacao?: string;
  /** Data do pagamento em ISO. Default: hoje. */
  dataPagamento?: string;
}

export interface UsePagamentosResult {
  /** Registra a baixa de UMA competência para um membro. */
  darBaixa: (input: DarBaixaInput) => Promise<void>;

  /**
   * Registra a baixa de MÚLTIPLAS competências de uma vez para o mesmo membro
   * (fluxo de negociação: ex. quitar o mês mais antigo + o mês atual juntos).
   * O valor total informado é dividido igualmente entre as competências selecionadas
   * apenas para fins de registro individual; o valor agregado é o que importa para o caixa.
   */
  darBaixaEmLote: (
    membroId: number,
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ) => Promise<void>;

  /** Remove a baixa de uma competência específica (estorno/correção de lançamento). */
  removerBaixa: (membroId: number, competencia: Competencia) => Promise<void>;
}

/**
 * Hook de acesso e mutação da entidade Pagamento.
 * Centraliza toda a regra de "dar baixa", incluindo o fluxo de negociação em lote.
 */
export function usePagamentos(): UsePagamentosResult {
  async function darBaixa(input: DarBaixaInput): Promise<void> {
    const existente = await db.pagamentos
      .where("[membroId+ano+mes]")
      .equals([input.membroId, input.competencia.ano, input.competencia.mes])
      .first();

    if (existente) {
      // Evita duplicar baixa da mesma competência — idempotência da operação.
      return;
    }

    await db.pagamentos.add({
      membroId: input.membroId,
      mes: input.competencia.mes,
      ano: input.competencia.ano,
      valorPago: input.valorPago,
      dataPagamento: input.dataPagamento ?? hojeISO(),
      formaPagamento: input.formaPagamento,
      observacao: input.observacao,
      criadoEm: Date.now(),
    });
  }

  async function darBaixaEmLote(
    membroId: number,
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ): Promise<void> {
    if (competencias.length === 0) return;

    const valorPorCompetencia = arredondarCentavos(valorTotalPago / competencias.length);
    const dataPagamento = hojeISO();
    const agora = Date.now();

    await db.transaction("rw", db.pagamentos, async () => {
      for (const competencia of competencias) {
        const existente = await db.pagamentos
          .where("[membroId+ano+mes]")
          .equals([membroId, competencia.ano, competencia.mes])
          .first();

        if (existente) continue; // idempotência também no lote

        const registro: Pagamento = {
          membroId,
          mes: competencia.mes,
          ano: competencia.ano,
          valorPago: valorPorCompetencia,
          dataPagamento,
          formaPagamento,
          criadoEm: agora,
        };
        if (observacao) {
          registro.observacao = observacao;
        }

        await db.pagamentos.add(registro);
      }
    });
  }

  async function removerBaixa(membroId: number, competencia: Competencia): Promise<void> {
    await db.pagamentos
      .where("[membroId+ano+mes]")
      .equals([membroId, competencia.ano, competencia.mes])
      .delete();
  }

  return { darBaixa, darBaixaEmLote, removerBaixa };
}

/**
 * Hook auxiliar reativo: retorna todos os pagamentos de um membro específico,
 * usado no modal de histórico.
 */
export function usePagamentosDoMembro(membroId: number | undefined): Pagamento[] {
  const pagamentos = useLiveQuery(() => {
    if (membroId === undefined) return Promise.resolve<Pagamento[]>([]);
    return db.pagamentos.where("membroId").equals(membroId).toArray();
  }, [membroId]);

  return pagamentos ?? [];
}

/** Arredonda um valor para 2 casas decimais (centavos), evitando erros de ponto flutuante. */
function arredondarCentavos(valor: number): number {
  return Math.round(valor * 100) / 100;
}
