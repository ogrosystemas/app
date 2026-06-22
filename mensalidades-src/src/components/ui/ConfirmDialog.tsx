import { AlertTriangle } from "lucide-react";
import { useState } from "react";
import { Button, type ButtonVariant } from "./Button";
import { Modal } from "./Modal";

interface ConfirmDialogProps {
  aberto: boolean;
  titulo: string;
  mensagem: string;
  textoConfirmar?: string;
  textoCancelar?: string;
  variantConfirmar?: ButtonVariant;
  onConfirmar: () => Promise<void> | void;
  onCancelar: () => void;
}

/**
 * Diálogo de confirmação genérico, usado antes de ações destrutivas ou irreversíveis
 * (ex: excluir membro). Sempre exige um clique explícito de confirmação — nunca dispara
 * a ação sozinho.
 */
export function ConfirmDialog({
  aberto,
  titulo,
  mensagem,
  textoConfirmar = "Confirmar",
  textoCancelar = "Cancelar",
  variantConfirmar = "danger",
  onConfirmar,
  onCancelar,
}: ConfirmDialogProps) {
  const [confirmando, setConfirmando] = useState(false);

  async function handleConfirmar() {
    setConfirmando(true);
    try {
      await onConfirmar();
    } finally {
      setConfirmando(false);
    }
  }

  return (
    <Modal
      aberto={aberto}
      onFechar={onCancelar}
      titulo={titulo}
      rodape={
        <div className="flex gap-2">
          <Button variant="ghost" fullWidth onClick={onCancelar} disabled={confirmando}>
            {textoCancelar}
          </Button>
          <Button variant={variantConfirmar} fullWidth onClick={handleConfirmar} disabled={confirmando}>
            {confirmando ? "Aguarde..." : textoConfirmar}
          </Button>
        </div>
      }
    >
      <div className="flex items-start gap-3">
        <AlertTriangle className="mt-0.5 shrink-0 text-alert-500" size={20} />
        <p className="text-sm text-graphite-200">{mensagem}</p>
      </div>
    </Modal>
  );
}
