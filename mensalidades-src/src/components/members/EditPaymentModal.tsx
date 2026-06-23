import { useEffect, useState } from "react";
import type { Competencia, FormaPagamento, Pagamento } from "../../types";
import { formatarCompetencia } from "../../utils/date.utils";
import { parseMoeda } from "../../utils/currency.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface EditPaymentModalProps {
  aberto: boolean;
  pagamento?: Pagamento;
  competencia?: Competencia;
  apelidoMembro?: string;
  onFechar: () => void;
  onSalvar: (valorPago: number, dataPagamento: string, formaPagamento: FormaPagamento) => Promise<void>;
  onEstornar: () => Promise<void>;
}

const FORMAS_PAGAMENTO: { valor: FormaPagamento; label: string }[] = [
  { valor: "pix", label: "Pix" },
  { valor: "dinheiro", label: "Dinheiro" },
  { valor: "transferencia", label: "Transferência" },
  { valor: "outro", label: "Outro" },
];

/**
 * Modal de edição/estorno de um pagamento já registrado.
 *
 * Editar: corrige valor, data ou forma de pagamento de uma baixa já feita (ex: erro de
 * digitação na hora de registrar). Não afeta o cálculo de pendência — a competência
 * continua paga, só os detalhes do registro mudam.
 *
 * Estornar: exclui o registro de pagamento por completo, fazendo a competência voltar
 * a aparecer como pendente na conferência. Ação irreversível, pede confirmação explícita
 * via um segundo passo dentro deste mesmo modal (evita abrir mais um modal por cima).
 */
export function EditPaymentModal({
  aberto,
  pagamento,
  competencia,
  apelidoMembro,
  onFechar,
  onSalvar,
  onEstornar,
}: EditPaymentModalProps) {
  const [valorTexto, setValorTexto] = useState("");
  const [dataPagamento, setDataPagamento] = useState("");
  const [formaPagamento, setFormaPagamento] = useState<FormaPagamento>("pix");
  const [salvando, setSalvando] = useState(false);
  const [estornando, setEstornando] = useState(false);
  const [confirmandoEstorno, setConfirmandoEstorno] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    if (!aberto || !pagamento) return;
    setValorTexto(String(pagamento.valorPago).replace(".", ","));
    setDataPagamento(pagamento.dataPagamento);
    setFormaPagamento(pagamento.formaPagamento);
    setErro(null);
    setConfirmandoEstorno(false);
  }, [aberto, pagamento]);

  async function handleSalvar() {
    const valor = parseMoeda(valorTexto);
    if (valor === null || valor <= 0) {
      setErro("Informe um valor válido (ex: 130,00).");
      return;
    }
    if (!dataPagamento) {
      setErro("Informe a data do pagamento.");
      return;
    }

    setSalvando(true);
    setErro(null);
    try {
      await onSalvar(valor, dataPagamento, formaPagamento);
      onFechar();
    } catch {
      setErro("Não foi possível salvar. Tente novamente.");
    } finally {
      setSalvando(false);
    }
  }

  async function handleEstornar() {
    setEstornando(true);
    try {
      await onEstornar();
      onFechar();
    } finally {
      setEstornando(false);
    }
  }

  if (!pagamento || !competencia) return null;

  return (
    <Modal
      aberto={aberto}
      onFechar={onFechar}
      titulo={`${apelidoMembro ?? "Pagamento"} — ${formatarCompetencia(competencia)}`}
      rodape={
        confirmandoEstorno ? (
          <div className="flex flex-col gap-3">
            <p className="text-sm text-alert-400">
              Tem certeza? Isso remove este pagamento e a competência volta a ficar pendente.
            </p>
            <div className="flex gap-2">
              <Button variant="ghost" fullWidth onClick={() => setConfirmandoEstorno(false)} disabled={estornando}>
                Cancelar
              </Button>
              <Button variant="danger" fullWidth onClick={handleEstornar} disabled={estornando}>
                {estornando ? "Estornando..." : "Confirmar estorno"}
              </Button>
            </div>
          </div>
        ) : (
          <div className="flex flex-col gap-2">
            <Button variant="primary" fullWidth onClick={handleSalvar} disabled={salvando}>
              {salvando ? "Salvando..." : "Salvar alterações"}
            </Button>
            <Button
              variant="danger"
              fullWidth
              onClick={() => setConfirmandoEstorno(true)}
              disabled={salvando}
            >
              Estornar pagamento
            </Button>
          </div>
        )
      }
    >
      <div className="flex flex-col gap-4">
        {erro && (
          <p className="border border-alert-600 bg-alert-950 px-3 py-2 text-sm text-alert-400">{erro}</p>
        )}

        <Campo label="Valor pago">
          <div className="relative">
            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-graphite-400">
              R$
            </span>
            <input
              type="text"
              inputMode="decimal"
              value={valorTexto}
              onChange={(e) => setValorTexto(e.target.value)}
              className="w-full border border-graphite-700 bg-graphite-900 py-2 pl-9 pr-3 text-sm text-chrome-50 focus:border-ember-500"
            />
          </div>
        </Campo>

        <Campo label="Data do pagamento">
          <input
            type="date"
            value={dataPagamento}
            onChange={(e) => setDataPagamento(e.target.value)}
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 [color-scheme:dark] focus:border-ember-500"
          />
        </Campo>

        <Campo label="Forma de pagamento">
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
        </Campo>
      </div>
    </Modal>
  );
}

function Campo({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="flex flex-col gap-1.5">
      <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">{label}</span>
      {children}
    </label>
  );
}
