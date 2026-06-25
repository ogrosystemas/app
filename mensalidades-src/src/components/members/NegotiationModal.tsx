import { QrCode } from "lucide-react";
import { useEffect, useState } from "react";
import { usePagamentosDoMembro } from "../../hooks/usePagamentos";
import type { Competencia, ConfigPix, FormaPagamento, Membro } from "../../types";
import { formatarMoeda } from "../../utils/currency.utils";
import { formatarCompetenciaAbreviada } from "../../utils/date.utils";
import {
  chaveCompetencia,
  gerarMesesDoAnoParaNegociacao,
  type MesParaNegociacao,
} from "../../utils/status.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";
import { PixPaymentModal } from "./PixPaymentModal";

interface NegotiationModalProps {
  aberto: boolean;
  clubeId: string;
  membro?: Membro;
  competenciaReferencia: Competencia;
  valorMensalidade: number;
  /** Dados da chave Pix DESTA sede — passado para o botão "Gerar Pix" do total negociado. */
  pix: ConfigPix | undefined;
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

/** Rótulo e estilo visual de cada status de mês na grade de negociação. */
const ESTILO_POR_STATUS: Record<
  MesParaNegociacao["status"],
  { rotulo: string | null; classeBase: string; classeAtivo: string }
> = {
  pendente: {
    rotulo: null,
    classeBase: "border-graphite-700 bg-graphite-900 text-graphite-300",
    classeAtivo: "border-alert-500 bg-alert-600 text-chrome-50",
  },
  futura: {
    rotulo: "adiantado",
    classeBase: "border-graphite-700 bg-graphite-900 text-graphite-300",
    classeAtivo: "border-ember-500 bg-ember-600 text-chrome-50",
  },
  paga: {
    rotulo: "pago",
    classeBase: "border-ok-700 bg-ok-950 text-ok-600 opacity-60 cursor-not-allowed",
    classeAtivo: "border-ok-700 bg-ok-950 text-ok-600 opacity-60 cursor-not-allowed",
  },
  "fora-do-periodo": {
    rotulo: null,
    classeBase: "border-graphite-800 bg-graphite-950 text-graphite-600 opacity-40 cursor-not-allowed",
    classeAtivo: "border-graphite-800 bg-graphite-950 text-graphite-600 opacity-40 cursor-not-allowed",
  },
};

/**
 * Modal de negociação: usado quando o membro tem 2+ meses pendentes, mas também
 * serve para quitar UM mês com forma de pagamento específica ou para REGISTRAR
 * PAGAMENTO ADIANTADO de meses futuros do mesmo ano.
 *
 * Mostra os 12 meses do ano da competência de referência numa grade só, cada um
 * com status visual (pendente = vermelho, futuro/adiantado = laranja, já pago =
 * verde e bloqueado, fora do período do membro = cinza e bloqueado) — o
 * tesoureiro pode combinar livremente meses pendentes (dívida real) e meses
 * futuros (pagamento adiantado) na mesma negociação, já que ambos resultam na
 * mesma ação: registrar a baixa com o valor total somado.
 *
 * O botão "Gerar Pix" abre o QR Code de cobrança com o valor TOTAL selecionado —
 * útil para mandar uma única cobrança consolidada ao membro pagar de uma vez,
 * em vez de ele ter que pagar mês a mês.
 */
export function NegotiationModal({
  aberto,
  clubeId,
  membro,
  competenciaReferencia,
  valorMensalidade,
  pix,
  onFechar,
  onConfirmar,
}: NegotiationModalProps) {
  const [selecionadas, setSelecionadas] = useState<Set<string>>(new Set());
  const [formaPagamento, setFormaPagamento] = useState<FormaPagamento>("pix");
  const [observacao, setObservacao] = useState("");
  const [confirmando, setConfirmando] = useState(false);
  const [pixAberto, setPixAberto] = useState(false);

  const pagamentosDoMembro = usePagamentosDoMembro(clubeId, membro?.id);

  const mesesDoAno: MesParaNegociacao[] =
    membro !== undefined
      ? gerarMesesDoAnoParaNegociacao(membro, pagamentosDoMembro, competenciaReferencia)
      : [];

  useEffect(() => {
    if (!aberto) return;
    // Por padrão, pré-seleciona TODOS os meses pendentes (dívida real) — meses futuros
    // (pagamento adiantado) começam desmarcados, já que adiantar é uma escolha extra
    // do membro, não o caso de uso padrão.
    const preSelecao = new Set<string>(
      mesesDoAno.filter((m) => m.status === "pendente").map((m) => chaveCompetencia(m.competencia)),
    );
    setSelecionadas(preSelecao);
    setFormaPagamento("pix");
    setObservacao("");
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [aberto, membro?.id]);

  function alternarSelecao(mes: MesParaNegociacao) {
    if (mes.status === "paga" || mes.status === "fora-do-periodo") return;
    const chave = chaveCompetencia(mes.competencia);
    setSelecionadas((atual) => {
      const novo = new Set(atual);
      if (novo.has(chave)) novo.delete(chave);
      else novo.add(chave);
      return novo;
    });
  }

  const competenciasEscolhidas = mesesDoAno
    .filter((m) => selecionadas.has(chaveCompetencia(m.competencia)))
    .map((m) => m.competencia);
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

  const primeiraCompetenciaEscolhida = competenciasEscolhidas[0] ?? competenciaReferencia;
  const temAlgumMesPendente = mesesDoAno.some((m) => m.status === "pendente");
  const tituloModal = membro
    ? `${temAlgumMesPendente ? "Negociar" : "Adiantar"} — ${membro.apelido}`
    : "Negociar";

  return (
    <>
      <Modal
        aberto={aberto}
        onFechar={onFechar}
        titulo={tituloModal}
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

            <Button
              variant="secondary"
              fullWidth
              icon={<QrCode size={14} />}
              onClick={() => setPixAberto(true)}
              disabled={competenciasEscolhidas.length === 0}
            >
              Gerar Pix deste total
            </Button>

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
            Selecione os meses envolvidos nesta negociação — meses pendentes (vermelho) e/ou
            meses futuros do mesmo ano como pagamento adiantado (laranja). Os valores serão
            somados e registrados de uma vez.
          </p>

          <div>
            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
              Meses de {competenciaReferencia.ano}
            </span>
            <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
              {mesesDoAno.map((mes) => {
                const chave = chaveCompetencia(mes.competencia);
                const ativo = selecionadas.has(chave);
                const bloqueado = mes.status === "paga" || mes.status === "fora-do-periodo";
                const estilo = ESTILO_POR_STATUS[mes.status];
                return (
                  <button
                    key={chave}
                    type="button"
                    onClick={() => alternarSelecao(mes)}
                    disabled={bloqueado}
                    className={`flex flex-col items-center gap-0.5 border px-2 py-2 text-sm font-display font-semibold uppercase tracking-wide transition-colors ${
                      ativo ? estilo.classeAtivo : estilo.classeBase
                    }`}
                  >
                    {formatarCompetenciaAbreviada(mes.competencia)}
                    {estilo.rotulo && (
                      <span className="text-[9px] font-normal normal-case tracking-normal opacity-80">
                        {estilo.rotulo}
                      </span>
                    )}
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

      <PixPaymentModal
        aberto={pixAberto}
        onFechar={() => setPixAberto(false)}
        pix={pix}
        apelidoMembro={membro?.apelido ?? ""}
        competencia={primeiraCompetenciaEscolhida}
        valor={valorTotal}
      />
    </>
  );
}
