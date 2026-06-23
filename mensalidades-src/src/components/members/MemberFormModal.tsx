import { useEffect, useState } from "react";
import { PATENTES_EM_ORDEM } from "../../constants/patentes.constants";
import type { Membro, NovoMembroInput } from "../../types";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface DadosFormulario extends NovoMembroInput {
  emailAcesso: string;
}

interface MemberFormModalProps {
  aberto: boolean;
  /** Quando presente, o modal opera em modo edição; quando undefined, modo cadastro. */
  membroParaEditar?: Membro;
  onFechar: () => void;
  onSalvar: (input: NovoMembroInput & { emailAcesso?: string }) => Promise<void>;
}

const PATENTE_PADRAO = PATENTES_EM_ORDEM[PATENTES_EM_ORDEM.length - 1] ?? "";

const ESTADO_INICIAL: DadosFormulario = {
  nome: "",
  apelido: "",
  patente: PATENTE_PADRAO,
  emailAcesso: "",
};

/**
 * Modal de cadastro/edição de membro — formulário com nome, apelido, patente, e
 * e-mail de acesso individual (opcional). Data de ingresso é fixada automaticamente
 * como hoje no cadastro (ver useMembros.criarMembro) e não é editável aqui;
 * status (ativo/afastado) tem fluxo próprio na lista de membros.
 *
 * O e-mail de acesso vincula esse membro a uma área de consulta restrita,
 * somente leitura: a pessoa que logar com esse e-mail vê o próprio status e
 * histórico (sem valores, sem ações de administração) — ver MemberSelfView e
 * firestore.rules. Deixe em branco para não conceder acesso individual.
 */
export function MemberFormModal({ aberto, membroParaEditar, onFechar, onSalvar }: MemberFormModalProps) {
  const [form, setForm] = useState<DadosFormulario>(ESTADO_INICIAL);
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    if (!aberto) return;
    setErro(null);
    if (membroParaEditar) {
      setForm({
        nome: membroParaEditar.nome,
        apelido: membroParaEditar.apelido,
        patente: membroParaEditar.patente,
        emailAcesso: membroParaEditar.emailAcesso ?? "",
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
    const emailLimpo = form.emailAcesso.trim();
    if (emailLimpo && !emailLimpo.includes("@")) {
      setErro("Informe um e-mail de acesso válido, ou deixe o campo em branco.");
      return;
    }

    setSalvando(true);
    setErro(null);
    try {
      await onSalvar({
        nome: form.nome.trim(),
        apelido: form.apelido.trim(),
        patente: form.patente,
        emailAcesso: emailLimpo || undefined,
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
            autoFocus
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

        <Campo label="Patente">
          <select
            value={form.patente}
            onChange={(e) => setForm((f) => ({ ...f, patente: e.target.value }))}
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 focus:border-ember-500"
          >
            {PATENTES_EM_ORDEM.map((patente) => (
              <option key={patente} value={patente}>
                {patente}
              </option>
            ))}
          </select>
        </Campo>

        <Campo label="E-mail de acesso (opcional)">
          <input
            type="email"
            value={form.emailAcesso}
            onChange={(e) => setForm((f) => ({ ...f, emailAcesso: e.target.value }))}
            placeholder="conta-google-do-integrante@gmail.com"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
          <span className="text-xs text-graphite-400">
            Se preenchido, essa conta Google poderá entrar no app e ver apenas o próprio
            status e histórico (sem valores, sem acesso administrativo).
          </span>
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
