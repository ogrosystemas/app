import { Pencil, Trash2, UserCheck, UserX } from "lucide-react";
import type { Membro } from "../../types";
import { Modal } from "../ui/Modal";

interface MemberActionsModalProps {
  aberto: boolean;
  membro?: Membro;
  onFechar: () => void;
  onEditar: () => void;
  onAfastar: () => void;
  onReativar: () => void;
  onExcluir: () => void;
}

/**
 * Menu de ações de um membro: editar dados, afastar/reativar, ou excluir definitivamente.
 * Cada ação aqui apenas dispara o callback correspondente — a confirmação de exclusão
 * e o formulário de edição são tratados em modais próprios, abertos pelo App.
 */
export function MemberActionsModal({
  aberto,
  membro,
  onFechar,
  onEditar,
  onAfastar,
  onReativar,
  onExcluir,
}: MemberActionsModalProps) {
  if (!membro) return null;

  const afastado = membro.status === "afastado";

  return (
    <Modal aberto={aberto} onFechar={onFechar} titulo={membro.apelido}>
      <div className="flex flex-col gap-2">
        <ItemAcao icon={<Pencil size={18} />} label="Editar dados" onClick={onEditar} />

        {afastado ? (
          <ItemAcao
            icon={<UserCheck size={18} />}
            label="Reativar membro"
            descricao="Volta a gerar cobrança a partir do mês atual."
            onClick={onReativar}
          />
        ) : (
          <ItemAcao
            icon={<UserX size={18} />}
            label="Afastar membro"
            descricao="Para de gerar cobrança nova a partir de agora. Dívidas anteriores são mantidas."
            onClick={onAfastar}
          />
        )}

        <div className="my-1 asphalt-divider" />

        <ItemAcao
          icon={<Trash2 size={18} />}
          label="Excluir membro"
          descricao="Remove o cadastro e todo o histórico de pagamentos. Não pode ser desfeito."
          tone="danger"
          onClick={onExcluir}
        />
      </div>
    </Modal>
  );
}

interface ItemAcaoProps {
  icon: React.ReactNode;
  label: string;
  descricao?: string;
  tone?: "neutro" | "danger";
  onClick: () => void;
}

function ItemAcao({ icon, label, descricao, tone = "neutro", onClick }: ItemAcaoProps) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`flex items-start gap-3 border border-graphite-700 bg-graphite-900 px-3.5 py-3 text-left transition-colors hover:bg-graphite-800 ${
        tone === "danger" ? "hover:border-alert-600" : "hover:border-graphite-600"
      }`}
    >
      <span className={tone === "danger" ? "text-alert-500" : "text-graphite-300"}>{icon}</span>
      <span className="flex flex-col gap-0.5">
        <span
          className={`font-display text-sm font-semibold uppercase tracking-wide ${
            tone === "danger" ? "text-alert-500" : "text-chrome-50"
          }`}
        >
          {label}
        </span>
        {descricao && <span className="text-xs text-graphite-400">{descricao}</span>}
      </span>
    </button>
  );
}
