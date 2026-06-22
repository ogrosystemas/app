import { useEffect, useState } from "react";
import type { Membro, NovoMembroInput, StatusMembro } from "../../types";
import { hojeISO } from "../../utils/date.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface MemberFormModalProps {
  aberto: boolean;
  /** Quando presente, o modal opera em modo edição; quando undefined, modo cadastro. */
  membroParaEditar?: Membro;
  onFechar: () => void;
  onSalvar: (input: NovoMembroInput) => Promise<void>;
}

const ESTADO_INICIAL: NovoMembroInput = {
  nome: "",
  apelido: "",
  dataIngresso: hojeISO(),
  status: "ativo",
};

/** Modal de cadastro/edição de membro — formulário simples conforme requisito de negócio. */
export function MemberFormModal({ aberto, membroParaEditar, onFechar, onSalvar }: MemberFormModalProps) {
  const [form, setForm] = useState<NovoMembroInput>(ESTADO_INICIAL);
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    if (!aberto) return;
    setErro(null);
    if (membroParaEditar) {
      setForm({
        nome: membroParaEditar.nome,
        apelido: membroParaEditar.apelido,
        dataIngresso: membroParaEditar.dataIngresso,
        status: membroParaEditar.status,
      });
    } else {
      setForm(ESTADO_INICIAL);
    }
  }, [aberto, membroParaEditar]);

  async function handleSalvar() {
    if (!form.nome.trim() || !form.apelido.trim()) {
      setErro("Nome e apelido são obrigatórios.");
      return;
    }
    setSalvando(true);
    setErro(null);
    try {
      await onSalvar({
        nome: form.nome.trim(),
        apelido: form.apelido.trim(),
        dataIngresso: form.dataIngresso,
        status: form.status,
      });
      onFechar();
    } catch {
      setErro("Não foi possível salvar. Tente novamente.");
    } finally {
      setSalvando(false);
    }
  }

  return (
    <Modal
      aberto={aberto}
      onFechar={onFechar}
      titulo={membroParaEditar ? "Editar Membro" : "Novo Membro"}
      rodape={
        <div className="flex gap-2">
          <Button variant="ghost" fullWidth onClick={onFechar} disabled={salvando}>
            Cancelar
          </Button>
          <Button variant="primary" fullWidth onClick={handleSalvar} disabled={salvando}>
            {salvando ? "Salvando..." : "Salvar"}
          </Button>
        </div>
      }
    >
      <div className="flex flex-col gap-4">
        {erro && (
          <p className="border border-alert-600 bg-alert-950 px-3 py-2 text-sm text-alert-400">{erro}</p>
        )}

        <Campo label="Nome completo">
          <input
            type="text"
            value={form.nome}
            onChange={(e) => setForm((f) => ({ ...f, nome: e.target.value }))}
            placeholder="Ex: Carlos Eduardo Ferreira"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
        </Campo>

        <Campo label="Apelido / Alcunha">
          <input
            type="text"
            value={form.apelido}
            onChange={(e) => setForm((f) => ({ ...f, apelido: e.target.value }))}
            placeholder="Ex: Foice"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
        </Campo>

        <Campo label="Data de ingresso">
          <input
            type="date"
            value={form.dataIngresso}
            onChange={(e) => setForm((f) => ({ ...f, dataIngresso: e.target.value }))}
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 [color-scheme:dark] focus:border-ember-500"
          />
        </Campo>

        <Campo label="Status">
          <div className="flex gap-2">
            {(["ativo", "inativo"] as StatusMembro[]).map((status) => (
              <button
                key={status}
                type="button"
                onClick={() => setForm((f) => ({ ...f, status }))}
                className={`flex-1 border px-3 py-2 text-sm font-display font-semibold uppercase tracking-wide transition-colors ${
                  form.status === status
                    ? "border-ember-500 bg-ember-950 text-ember-500"
                    : "border-graphite-700 bg-graphite-900 text-graphite-400"
                }`}
              >
                {status === "ativo" ? "Ativo" : "Inativo"}
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
