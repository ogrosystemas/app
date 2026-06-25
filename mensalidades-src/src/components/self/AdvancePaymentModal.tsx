import { QrCode } from "lucide-react";
import { useEffect, useState } from "react";
import type { Competencia, ConfigPix, Membro, Pagamento } from "../../types";
import { formatarMoeda } from "../../utils/currency.utils";
import { formatarCompetenciaAbreviada, competenciaAtual } from "../../utils/date.utils";
import {
  chaveCompetencia,
  gerarMesesDoAnoParaNegociacao,
} from "../../utils/status.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";
import { PixPaymentModal } from "../members/PixPaymentModal";

interface AdvancePaymentModalProps {
  aberto: boolean;
  onFechar: () => void;
  membro: Membro;
  pagamentos: Pagamento[];
  valorMensalidade: number;
  pix: ConfigPix | undefined;
}

/**
 * Modal de adiantamento de mensalidades, exclusivo da área de autoconsulta do
 * integrante (MemberSelfView) — mostra apenas os meses FUTUROS do ano corrente
 * que ainda não foram pagos (nunca meses pendentes/atrasados: isso é dívida real,
 * que só o tesoureiro negocia, ver NegotiationModal) e permite ao próprio membro
 * gerar um Pix com o valor somado de quantos meses ele quiser adiantar.
 *
 * Importante: este modal NUNCA registra baixa nenhuma — apenas gera o código Pix
 * para o membro pagar. A baixa de fato continua sendo responsabilidade do
 * tesoureiro, depois de confirmar o recebimento na própria conta (mesmo modelo de
 * confiança do resto do app: o integrante nunca consegue se autodeclarar "pago").
 */
export function AdvancePaymentModal({
  aberto,
  onFechar,
  membro,
  pagamentos,
  valorMensalidade,
  pix,
}: AdvancePaymentModalProps) {
  const [selecionadas, setSelecionadas] = useState<Set<string>>(new Set());
  const [pixAberto, setPixAberto] = useState(false);

  const competenciaHoje = competenciaAtual();
  const mesesFuturos = gerarMesesDoAnoParaNegociacao(membro, pagamentos, competenciaHoje).filter(
    (m) => m.status === "futura",
  );

  useEffect(() => {
    if (!aberto) return;
    setSelecionadas(new Set());
  }, [aberto]);

  function alternarSelecao(competencia: Competencia) {
    const chave = chaveCompetencia(competencia);
    setSelecionadas((atual) => {
      const novo = new Set(atual);
      if (novo.has(chave)) novo.delete(chave);
      else novo.add(chave);
      return novo;
    });
  }

  const competenciasEscolhidas = mesesFuturos
    .filter((m) => selecionadas.has(chaveCompetencia(m.competencia)))
    .map((m) => m.competencia);
  const valorTotal = competenciasEscolhidas.length * valorMensalidade;
  const primeiraCompetenciaEscolhida = competenciasEscolhidas[0] ?? competenciaHoje;

  return (
    <>
      <Modal
        aberto={aberto}
        onFechar={onFechar}
        titulo="Adiantar Mensalidades"
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
              variant="primary"
              fullWidth
              icon={<QrCode size={14} />}
              onClick={() => setPixAberto(true)}
              disabled={competenciasEscolhidas.length === 0}
            >
              Gerar Pix
            </Button>
          </div>
        }
      >
        <div className="flex flex-col gap-4">
          <p className="text-sm text-graphite-400">
            Selecione os meses futuros que você quer pagar adiantado. Depois de pagar, avise o
            administrador para confirmar o recebimento — gerar o Pix aqui não dá baixa
            automaticamente.
          </p>

          {mesesFuturos.length === 0 ? (
            <p className="py-6 text-center text-sm text-graphite-400">
              Não há meses futuros disponíveis para adiantar este ano.
            </p>
          ) : (
            <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
              {mesesFuturos.map((mes) => {
                const chave = chaveCompetencia(mes.competencia);
                const ativo = selecionadas.has(chave);
                return (
                  <button
                    key={chave}
                    type="button"
                    onClick={() => alternarSelecao(mes.competencia)}
                    className={`border px-2 py-2 text-sm font-display font-semibold uppercase tracking-wide transition-colors ${
                      ativo
                        ? "border-ember-500 bg-ember-600 text-chrome-50"
                        : "border-graphite-700 bg-graphite-900 text-graphite-300"
                    }`}
                  >
                    {formatarCompetenciaAbreviada(mes.competencia)}
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </Modal>

      <PixPaymentModal
        aberto={pixAberto}
        onFechar={() => setPixAberto(false)}
        pix={pix}
        apelidoMembro={membro.apelido}
        competencia={primeiraCompetenciaEscolhida}
        valor={valorTotal}
      />
    </>
  );
}
