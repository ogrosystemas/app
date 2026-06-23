import {
  deleteDoc,
  getDocs,
  onSnapshot,
  query,
  runTransaction,
  updateDoc,
  where,
} from "firebase/firestore";
import { useEffect, useState } from "react";
import { db } from "../firebase/config";
import { refAvisos, refPagamento, refPagamentos } from "../db/refs";
import type { Competencia, FormaPagamento, Pagamento } from "../types";
import { hojeISO } from "../utils/date.utils";

export interface DarBaixaInput {
  membroId: string;
  competencia: Competencia;
  valorPago: number;
  formaPagamento: FormaPagamento;
  observacao?: string;
  /** Data do pagamento em ISO. Default: hoje. */
  dataPagamento?: string;
}

export interface EditarPagamentoInput {
  valorPago?: number;
  dataPagamento?: string;
  formaPagamento?: FormaPagamento;
  observacao?: string;
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
    membroId: string,
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ) => Promise<void>;

  /** Remove a baixa de uma competência específica (estorno completo — volta a ficar pendente). */
  removerBaixa: (membroId: string, competencia: Competencia) => Promise<void>;

  /** Edita campos de um pagamento já registrado (corrigir valor, data ou forma de pagamento). */
  editarPagamento: (pagamentoId: string, input: EditarPagamentoInput) => Promise<void>;
}

/**
 * Gera um ID DETERMINÍSTICO de documento para um pagamento, a partir de
 * membroId + competência. Isso é o que garante idempotência (nunca duas baixas
 * para a mesma competência do mesmo membro) de forma nativa do Firestore: em vez
 * de fazer uma query "já existe um pagamento com esse membroId+mes+ano?" antes de
 * escrever (o que não seria seguro dentro de uma transação concorrente — duas
 * pessoas dando baixa ao mesmo tempo poderiam ambas passar pela checagem antes de
 * qualquer uma escrever), o próprio ID do documento já é a chave de unicidade:
 * tentar criar o "mesmo" pagamento duas vezes sempre aponta para o mesmo
 * documento, então a segunda tentativa apenas sobrescreve (ou é ignorada, se
 * formos cuidadosos) em vez de duplicar.
 */
function idPagamento(membroId: string, competencia: Competencia): string {
  return `${membroId}_${competencia.ano}_${competencia.mes}`;
}

/**
 * Remove (se existir) o aviso informal de pagamento referente a uma competência
 * específica de um membro — chamado depois que uma baixa real é registrada, para
 * o sininho de "avisou que vai pagar" não continuar acendendo num mês que já foi
 * efetivamente pago. Não faz parte da mesma transação do pagamento de propósito:
 * é uma limpeza de conveniência, não uma garantia de consistência crítica — se
 * falhar silenciosamente, o aviso só fica visível por mais tempo, sem prejuízo
 * ao cálculo real de pendência (que nunca depende de avisos).
 */
async function limparAvisoDaCompetencia(membroId: string, competencia: Competencia): Promise<void> {
  const avisosDoMembro = await getDocs(
    query(
      refAvisos(),
      where("membroId", "==", membroId),
      where("ano", "==", competencia.ano),
      where("mes", "==", competencia.mes),
    ),
  );
  await Promise.all(avisosDoMembro.docs.map((d) => deleteDoc(d.ref)));
}

/**
 * Hook de acesso e mutação da entidade Pagamento.
 * Centraliza toda a regra de "dar baixa", incluindo o fluxo de negociação em lote.
 */
export function usePagamentos(): UsePagamentosResult {
  async function darBaixa(input: DarBaixaInput): Promise<void> {
    const id = idPagamento(input.membroId, input.competencia);
    const ref = refPagamento(id);

    await runTransaction(db, async (transacao) => {
      const existente = await transacao.get(ref);
      if (existente.exists()) {
        // Idempotência: já existe baixa para esta competência deste membro — não duplica.
        return;
      }

      const registro: Pagamento = {
        membroId: input.membroId,
        mes: input.competencia.mes,
        ano: input.competencia.ano,
        valorPago: input.valorPago,
        dataPagamento: input.dataPagamento ?? hojeISO(),
        formaPagamento: input.formaPagamento,
        criadoEm: Date.now(),
      };
      if (input.observacao) registro.observacao = input.observacao;

      transacao.set(ref, registro);
    });

    await limparAvisoDaCompetencia(input.membroId, input.competencia);
  }

  async function darBaixaEmLote(
    membroId: string,
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ): Promise<void> {
    if (competencias.length === 0) return;

    const valorPorCompetencia = arredondarCentavos(valorTotalPago / competencias.length);
    const dataPagamento = hojeISO();
    const agora = Date.now();

    await runTransaction(db, async (transacao) => {
      // Lê o estado atual de TODAS as competências envolvidas ANTES de escrever
      // qualquer uma — exigência do Firestore (leituras sempre antes de escritas
      // dentro de uma transação).
      const refs = competencias.map((c) => refPagamento(idPagamento(membroId, c)));
      const snapshots = await Promise.all(refs.map((ref) => transacao.get(ref)));

      for (const [i, competencia] of competencias.entries()) {
        const snapshot = snapshots[i];
        const ref = refs[i];
        if (!snapshot || !ref || snapshot.exists()) continue; // idempotência também no lote

        const registro: Pagamento = {
          membroId,
          mes: competencia.mes,
          ano: competencia.ano,
          valorPago: valorPorCompetencia,
          dataPagamento,
          formaPagamento,
          criadoEm: agora,
        };
        if (observacao) registro.observacao = observacao;

        transacao.set(ref, registro);
      }
    });

    await Promise.all(competencias.map((c) => limparAvisoDaCompetencia(membroId, c)));
  }

  async function removerBaixa(membroId: string, competencia: Competencia): Promise<void> {
    await deleteDoc(refPagamento(idPagamento(membroId, competencia)));
  }

  async function editarPagamento(pagamentoId: string, input: EditarPagamentoInput): Promise<void> {
    await updateDoc(refPagamento(pagamentoId), { ...input });
  }

  return { darBaixa, darBaixaEmLote, removerBaixa, editarPagamento };
}

/**
 * Hook auxiliar reativo: retorna todos os pagamentos de um membro específico,
 * usado no modal de histórico.
 */
export function usePagamentosDoMembro(membroId: string | undefined): Pagamento[] {
  const [pagamentos, setPagamentos] = useState<Pagamento[]>([]);

  useEffect(() => {
    if (membroId === undefined) {
      setPagamentos([]);
      return;
    }

    const consulta = query(refPagamentos(), where("membroId", "==", membroId));
    const cancelarInscricao = onSnapshot(consulta, (snapshot) => {
      setPagamentos(snapshot.docs.map((d) => ({ ...d.data(), id: d.id })));
    });
    return cancelarInscricao;
  }, [membroId]);

  return pagamentos;
}

/** Arredonda um valor para 2 casas decimais (centavos), evitando erros de ponto flutuante. */
function arredondarCentavos(valor: number): number {
  return Math.round(valor * 100) / 100;
}
