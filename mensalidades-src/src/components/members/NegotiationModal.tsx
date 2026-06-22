import { useEffect, useState } from "react";
import type { Competencia, FormaPagamento, Membro } from "../../types";
import { formatarMoeda } from "../../utils/currency.utils";
import { chaveCompetencia, type ResumoInadimplenciaMembro } from "../../utils/status.utils";
import { formatarCompetenciaAbreviada } from "../../utils/date.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface NegotiationModalProps {
  aberto: boolean;
  membro?: Membro;
  resumo?: ResumoInadimplenciaMembro;
  valorMensalidade: number;
  onFechar: () => void;
  onConfirmar: (
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ) => Promise<void>;
}

const FORMAS_PAGAMENTO: { valor: FormaPagamento; label: string }[] = [
  { valor: "pix", label: "Pix" },
  { valor: "dinheiro", label: "Dinheiro" },
  { valor: "transferencia", label: "Transferência" },
  { valor: "outro", label: "Outro" },
];

/**
 * Modal de negociação: usado quando o membro tem 2+ meses pendentes.
 * Permite selecionar quais competências estão sendo quitadas agora
 * (ex: mês mais antigo + mês atual) e registra a baixa em lote,
 * somando o valor total e dividindo igualmente entre as competências escolhidas.
 */
export function NegotiationModal({
  aberto,
  membro,
  resumo,
  valorMensalidade,
  onFechar,
  onConfirmar,
}: NegotiationModalProps) {
  const [selecionadas, setSelecionadas] = useState<Set<string>>(new Set());
  const [formaPagamento, setFormaPagamento] = useState<FormaPagamento>("pix");
  const [observacao, setObservacao] = useState("");
  const [confirmando, setConfirmando] = useState(false);

  const pendencias = resumo?.competenciasPendentes ?? [];

  useEffect(() => {
    if (!aberto) return;
    // Por padrão, pré-seleciona o mês mais antigo + o mês mais recente (caso de uso descrito:
    // "pagamento do mês mais antigo junto com o mês atual"), respeitando a lista real disponível.
    const chaves = pendencias.map(chaveCompetencia);
    const preSelecao = new Set<string>();
    if (chaves.length > 0) preSelecao.add(chaves[0] as string);
    if (chaves.length > 1) preSelecao.add(chaves[chaves.length - 1] as string);
    setSelecionadas(preSelecao);
    setFormaPagamento("pix");
    setObservacao("");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [aberto, membro?.id]);

  function alternarSelecao(c: Competencia) {
    const chave = chaveCompetencia(c);
    setSelecionadas((atual) => {
      const novo = new Set(atual);
      if (novo.has(chave)) novo.delete(chave);
      else novo.add(chave);
      return novo;
    });
  }

  const competenciasEscolhidas = pendencias.filter((c) => selecionadas.has(chaveCompetencia(c)));
  const valorTotal = competenciasEscolhidas.length * valorMensalidade;

  async function handleConfirmar() {
    if (competenciasEscolhidas.length === 0) return;
    setConfirmando(true);
    try {
      await onConfirmar(
        competenciasEscolhidas,
        valorTotal,
        formaPagamento,
        observacao.trim() || undefined,
      );
      onFechar();
    } finally {
      setConfirmando(false);
    }
  }

  return (
    <Modal
      aberto={aberto}
      onFechar={onFechar}
      titulo={membro ? `Negociar — ${membro.apelido}` : "Negociar"}
      rodape={
        <div className="flex flex-col gap-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">
              Total selecionado
            </span>
            <span className="font-display text-xl font-bold text-ember-500">
              {formatarMoeda(valorTotal)}
            </span>
          </div>
          <div className="flex gap-2">
            <Button variant="ghost" fullWidth onClick={onFechar} disabled={confirmando}>
              Cancelar
            </Button>
            <Button
              variant="danger"
              fullWidth
              onClick={handleConfirmar}
              disabled={confirmando || competenciasEscolhidas.length === 0}
            >
              {confirmando ? "Registrando..." : `Confirmar (${competenciasEscolhidas.length})`}
            </Button>
          </div>
        </div>
      }
    >
      <div className="flex flex-col gap-4">
        <p className="text-sm text-graphite-400">
          Selecione os meses que estão sendo quitados nesta negociação. Os valores serão somados e
          registrados de uma vez.
        </p>

        <div>
          <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Meses pendentes ({pendencias.length})
          </span>
          <div className="flex flex-wrap gap-2">
            {pendencias.map((c) => {
              const chave = chaveCompetencia(c);
              const ativo = selecionadas.has(chave);
              return (
                <button
                  key={chave}
                  type="button"
                  onClick={() => alternarSelecao(c)}
                  className={`border px-3 py-2 text-sm font-display font-semibold uppercase tracking-wide transition-colors ${
                    ativo
                      ? "border-alert-500 bg-alert-600 text-chrome-50"
                      : "border-graphite-700 bg-graphite-900 text-graphite-300"
                  }`}
                >
                  {formatarCompetenciaAbreviada(c)}
                </button>
              );
            })}
          </div>
        </div>

        <div>
          <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Forma de pagamento
          </span>
          <div className="grid grid-cols-2 gap-2">
            {FORMAS_PAGAMENTO.map((opcao) => (
              <button
                key={opcao.valor}
                type="button"
                onClick={() => setFormaPagamento(opcao.valor)}
                className={`border px-3 py-2 text-sm font-medium transition-colors ${
                  formaPagamento === opcao.valor
                    ? "border-ember-500 bg-ember-950 text-ember-500"
                    : "border-graphite-700 bg-graphite-900 text-graphite-300"
                }`}
              >
                {opcao.label}
              </button>
            ))}
          </div>
        </div>

        <label className="flex flex-col gap-1.5">
          <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Observação (opcional)
          </span>
          <textarea
            value={observacao}
            onChange={(e) => setObservacao(e.target.value)}
            placeholder="Ex: negociado parcelamento com o tesoureiro"
            rows={2}
            className="w-full resize-none border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
        </label>
      </div>
    </Modal>
  );
}
