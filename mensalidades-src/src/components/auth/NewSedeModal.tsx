import { useState } from "react";
import type { NovaSedeInput, TipoSede } from "../../types";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface NewSedeModalProps {
  aberto: boolean;
  onFechar: () => void;
  onCriar: (input: NovaSedeInput) => Promise<void>;
}

/** Converte um nome livre em um ID de sede válido: minúsculas, sem acento, só letras/números/hífen. */
function sugerirIdAPartirDoNome(nome: string): string {
  return nome
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

/**
 * Modal de criação de uma nova sede — exclusivo do Super Admin (ver
 * SedeSelectionScreen). Cria o documento de metadados da sede, a config inicial
 * (nome + valor da mensalidade) e já vincula o e-mail do tesoureiro responsável
 * como administrador DESSA sede específica — tudo em uma única ação, sem precisar
 * editar nada manualmente no Firestore.
 */
export function NewSedeModal({ aberto, onFechar, onCriar }: NewSedeModalProps) {
  const [nome, setNome] = useState("");
  const [id, setId] = useState("");
  const [idEditadoManualmente, setIdEditadoManualmente] = useState(false);
  const [tipo, setTipo] = useState<TipoSede>("subsede");
  const [valorMensalidade, setValorMensalidade] = useState("130,00");
  const [emailTesoureiro, setEmailTesoureiro] = useState("");
  const [criando, setCriando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  function handleNomeChange(novoNome: string) {
    setNome(novoNome);
    if (!idEditadoManualmente) {
      setId(sugerirIdAPartirDoNome(novoNome));
    }
  }

  async function handleCriar() {
    setErro(null);

    if (!nome.trim() || !id.trim() || !emailTesoureiro.trim()) {
      setErro("Nome, ID e e-mail do tesoureiro são obrigatórios.");
      return;
    }
    if (!/^[a-z0-9-]+$/.test(id)) {
      setErro("O ID da sede só pode ter letras minúsculas, números e hífen (sem espaços/acentos).");
      return;
    }
    const valorNumerico = Number(valorMensalidade.replace(",", "."));
    if (Number.isNaN(valorNumerico) || valorNumerico <= 0) {
      setErro("Informe um valor de mensalidade válido.");
      return;
    }

    setCriando(true);
    try {
      await onCriar({
        id: id.trim(),
        nome: nome.trim(),
        tipo,
        valorMensalidade: valorNumerico,
        emailTesoureiro: emailTesoureiro.trim(),
      });
      setNome("");
      setId("");
      setIdEditadoManualmente(false);
      setTipo("subsede");
      setValorMensalidade("130,00");
      setEmailTesoureiro("");
      onFechar();
    } catch {
      setErro("Não foi possível criar a sede. Verifique se o ID já está em uso.");
    } finally {
      setCriando(false);
    }
  }

  return (
    <Modal
      aberto={aberto}
      onFechar={onFechar}
      titulo="Nova Sede"
      rodape={
        <div className="flex gap-2">
          <Button variant="ghost" fullWidth onClick={onFechar} disabled={criando}>
            Cancelar
          </Button>
          <Button variant="primary" fullWidth onClick={handleCriar} disabled={criando}>
            {criando ? "Criando..." : "Criar Sede"}
          </Button>
        </div>
      }
    >
      <div className="flex flex-col gap-4">
        {erro && (
          <p className="border border-alert-600 bg-alert-950 px-3 py-2 text-sm text-alert-400">{erro}</p>
        )}

        <Campo label="Tipo de sede">
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setTipo("matriz")}
              className={`flex-1 border px-3 py-2 text-sm font-semibold uppercase tracking-wide ${
                tipo === "matriz"
                  ? "border-ember-500 bg-ember-950 text-ember-500"
                  : "border-graphite-700 bg-graphite-900 text-graphite-400"
              }`}
            >
              Matriz
            </button>
            <button
              type="button"
              onClick={() => setTipo("subsede")}
              className={`flex-1 border px-3 py-2 text-sm font-semibold uppercase tracking-wide ${
                tipo === "subsede"
                  ? "border-ember-500 bg-ember-950 text-ember-500"
                  : "border-graphite-700 bg-graphite-900 text-graphite-400"
              }`}
            >
              Subsede
            </button>
          </div>
        </Campo>

        <Campo label="Nome da sede">
          <input
            type="text"
            value={nome}
            onChange={(e) => handleNomeChange(e.target.value)}
            placeholder="Ex: Joinville"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
            autoFocus
          />
        </Campo>

        <Campo label="ID da sede (gerado automaticamente, editável)">
          <input
            type="text"
            value={id}
            onChange={(e) => {
              setId(e.target.value);
              setIdEditadoManualmente(true);
            }}
            placeholder="joinville"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
          <span className="text-xs text-graphite-400">
            Usado internamente para identificar a sede — não pode ser alterado depois de criada.
          </span>
        </Campo>

        <Campo label="Valor inicial da mensalidade (R$)">
          <input
            type="text"
            inputMode="decimal"
            value={valorMensalidade}
            onChange={(e) => setValorMensalidade(e.target.value)}
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 focus:border-ember-500"
          />
        </Campo>

        <Campo label="E-mail do tesoureiro responsável">
          <input
            type="email"
            value={emailTesoureiro}
            onChange={(e) => setEmailTesoureiro(e.target.value)}
            placeholder="tesoureiro-da-sede@gmail.com"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
          <span className="text-xs text-graphite-400">
            Essa conta Google vai administrar somente esta sede (ver/editar membros, pagamentos, Pix).
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
