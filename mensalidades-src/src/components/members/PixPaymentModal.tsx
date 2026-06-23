import { QRCodeSVG } from "qrcode.react";
import { Check, Copy } from "lucide-react";
import { useMemo, useState } from "react";
import { PIX_CHAVE, PIX_CIDADE, PIX_NOME_RECEBEDOR } from "../../constants/theme.constants";
import { formatarCompetencia } from "../../utils/date.utils";
import { formatarMoeda } from "../../utils/currency.utils";
import { gerarPayloadPix } from "../../utils/pix.utils";
import type { Competencia } from "../../types";
import { Modal } from "../ui/Modal";

interface PixPaymentModalProps {
  aberto: boolean;
  onFechar: () => void;
  /** Apelido do membro a quem se refere a cobrança — usado para montar o TxID do Pix. */
  apelidoMembro: string;
  /** Competência de referência exibida no título — para cobrança de várias, use a primeira. */
  competencia: Competencia;
  /** Valor total a cobrar. Para o integrante comum, é sempre 1 mensalidade; para o admin, pode ser qualquer valor negociado. */
  valor: number;
}

/**
 * Modal de cobrança via Pix dinâmico: gera um QR Code (e o respectivo código "Copia e
 * Cola") com a chave Pix do clube, valor e identificação já preenchidos — o pagador só
 * precisa escanear ou colar no app do banco, sem digitar nada manualmente.
 *
 * Importante: este Pix é gerado 100% no navegador, sem nenhuma API ou serviço externo —
 * é só um payload de texto estruturado (ver utils/pix.utils.ts) que qualquer app de banco
 * já sabe interpretar. Isso significa que o app NÃO recebe nenhuma confirmação automática
 * de que o pagamento foi feito — a baixa no sistema continua sendo manual (ver
 * EditPaymentModal / usePagamentos.darBaixa), feita pelo administrador depois de confirmar
 * o recebimento na própria conta bancária.
 */
export function PixPaymentModal({
  aberto,
  onFechar,
  apelidoMembro,
  competencia,
  valor,
}: PixPaymentModalProps) {
  const [copiado, setCopiado] = useState(false);

  const payloadPix = useMemo(() => {
    if (!aberto) return "";
    const txId = `MENS${competencia.ano}${String(competencia.mes).padStart(2, "0")}${apelidoMembro}`;
    return gerarPayloadPix({
      chave: PIX_CHAVE,
      nomeRecebedor: PIX_NOME_RECEBEDOR,
      cidade: PIX_CIDADE,
      valor,
      txId,
    });
  }, [aberto, apelidoMembro, competencia, valor]);

  async function handleCopiar() {
    try {
      await navigator.clipboard.writeText(payloadPix);
      setCopiado(true);
      setTimeout(() => setCopiado(false), 2500);
    } catch {
      // Falha silenciosa: em navegadores sem permissão de clipboard, o usuário ainda
      // pode selecionar o texto manualmente (ver <textarea> abaixo).
    }
  }

  return (
    <Modal aberto={aberto} onFechar={onFechar} titulo={`Pagar via Pix — ${formatarCompetencia(competencia)}`}>
      <div className="flex flex-col items-center gap-4">
        <p className="text-center text-sm text-graphite-400">
          Escaneie o QR Code no app do seu banco, ou copie o código abaixo.
        </p>

        <div className="border border-graphite-700 bg-white p-3">
          <QRCodeSVG value={payloadPix} size={220} level="M" />
        </div>

        <div className="flex flex-col items-center gap-1">
          <span className="font-display text-2xl font-bold text-ember-500">
            {formatarMoeda(valor)}
          </span>
          <span className="text-xs text-graphite-400">Para: {PIX_NOME_RECEBEDOR}</span>
        </div>

        <button
          type="button"
          onClick={handleCopiar}
          className="flex w-full items-center justify-center gap-2 border border-ember-600 bg-ember-950 px-4 py-3 font-display text-sm font-semibold uppercase tracking-wide text-ember-500 hover:bg-ember-900"
        >
          {copiado ? <Check size={16} /> : <Copy size={16} />}
          {copiado ? "Copiado!" : "Copiar código Pix"}
        </button>

        <p className="text-center text-xs text-graphite-400">
          Depois de pagar, avise o administrador para confirmar o recebimento — este código
          não dá baixa automaticamente no sistema.
        </p>
      </div>
    </Modal>
  );
}
