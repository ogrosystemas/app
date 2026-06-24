import { QRCodeSVG } from "qrcode.react";
import { AlertTriangle, Check, Copy } from "lucide-react";
import { useMemo, useState } from "react";
import { formatarCompetencia } from "../../utils/date.utils";
import { formatarMoeda } from "../../utils/currency.utils";
import { gerarPayloadPix } from "../../utils/pix.utils";
import type { Competencia, ConfigPix } from "../../types";
import { Modal } from "../ui/Modal";

interface PixPaymentModalProps {
  aberto: boolean;
  onFechar: () => void;
  /** Dados da chave Pix DESTA sede — ausente se o tesoureiro ainda não configurou em Configurações. */
  pix: ConfigPix | undefined;
  /** Apelido do membro a quem se refere a cobrança — usado para montar o TxID do Pix. */
  apelidoMembro: string;
  /** Competência de referência exibida no título — para cobrança de várias, use a primeira. */
  competencia: Competencia;
  /** Valor total a cobrar. Para o integrante comum, é sempre 1 mensalidade; para o admin, pode ser qualquer valor negociado. */
  valor: number;
}

/**
 * Modal de cobrança via Pix dinâmico: gera um QR Code (e o respectivo código "Copia e
 * Cola") com a chave Pix DESTA sede, valor e identificação já preenchidos — o pagador só
 * precisa escanear ou colar no app do banco, sem digitar nada manualmente.
 *
 * Importante: este Pix é gerado 100% no navegador, sem nenhuma API ou serviço externo —
 * é só um payload de texto estruturado (ver utils/pix.utils.ts) que qualquer app de banco
 * já sabe interpretar. Isso significa que o app NÃO recebe nenhuma confirmação automática
 * de que o pagamento foi feito — a baixa no sistema continua sendo manual (ver
 * EditPaymentModal / usePagamentos.darBaixa), feita pelo administrador depois de confirmar
 * o recebimento na própria conta bancária.
 *
 * Cada sede tem sua PRÓPRIA chave Pix (cadastrada em Configurações) — nunca compartilhada
 * entre sedes, já que o dinheiro de cada sede cai direto na conta do tesoureiro
 * responsável por ela.
 */
export function PixPaymentModal({
  aberto,
  onFechar,
  pix,
  apelidoMembro,
  competencia,
  valor,
}: PixPaymentModalProps) {
  const [copiado, setCopiado] = useState(false);

  const payloadPix = useMemo(() => {
    if (!aberto || !pix) return "";
    const txId = `MENS${competencia.ano}${String(competencia.mes).padStart(2, "0")}${apelidoMembro}`;
    return gerarPayloadPix({
      chave: pix.chave,
      nomeRecebedor: pix.nomeRecebedor,
      cidade: pix.cidade,
      valor,
      txId,
    });
  }, [aberto, pix, apelidoMembro, competencia, valor]);

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

  if (!pix) {
    return (
      <Modal aberto={aberto} onFechar={onFechar} titulo="Pagar via Pix">
        <div className="flex flex-col items-center gap-3 py-4 text-center">
          <AlertTriangle className="text-alert-500" size={32} />
          <p className="text-sm text-graphite-200">
            Esta sede ainda não configurou a chave Pix.
          </p>
          <p className="text-xs text-graphite-400">
            O administrador precisa preencher os dados do Pix em Configurações antes de
            gerar cobranças.
          </p>
        </div>
      </Modal>
    );
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
          <span className="text-xs text-graphite-400">Para: {pix.nomeRecebedor}</span>
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
