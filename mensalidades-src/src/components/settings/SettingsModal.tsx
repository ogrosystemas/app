import { useEffect, useState } from "react";
import type { ConfigClube } from "../../types";
import { formatarMoeda, parseMoeda } from "../../utils/currency.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface SettingsModalProps {
  aberto: boolean;
  config: ConfigClube;
  onFechar: () => void;
  onSalvar: (nomeClube: string, valorMensalidade: number) => Promise<void>;
}

/**
 * Modal de configurações gerais do clube: nome exibido no header e valor da mensalidade.
 * Alterar o valor da mensalidade NÃO afeta pagamentos já registrados (cada Pagamento
 * guarda seu próprio valorPago, congelado no momento da baixa) — afeta apenas o cálculo
 * de pendências futuras e o resumo do dashboard a partir de agora.
 */
export function SettingsModal({ aberto, config, onFechar, onSalvar }: SettingsModalProps) {
  const [nomeClube, setNomeClube] = useState(config.nomeClube);
  const [valorTexto, setValorTexto] = useState(String(config.valorMensalidade).replace(".", ","));
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  useEffect(() => {
    if (!aberto) return;
    setNomeClube(config.nomeClube);
    setValorTexto(String(config.valorMensalidade).replace(".", ","));
    setErro(null);
  }, [aberto, config.nomeClube, config.valorMensalidade]);

  async function handleSalvar() {
    if (!nomeClube.trim()) {
      setErro("O nome do clube não pode ficar em branco.");
      return;
    }
    const valor = parseMoeda(valorTexto);
    if (valor === null || valor <= 0) {
      setErro("Informe um valor de mensalidade válido (ex: 50,00).");
      return;
    }

    setSalvando(true);
    setErro(null);
    try {
      await onSalvar(nomeClube.trim(), valor);
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
      titulo="Configurações"
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

        <Campo label="Nome do clube">
          <input
            type="text"
            value={nomeClube}
            onChange={(e) => setNomeClube(e.target.value)}
            placeholder="Ex: Mutantes Moto Clube"
            className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
          />
        </Campo>

        <Campo label="Valor da mensalidade">
          <div className="relative">
            <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-graphite-400">
              R$
            </span>
            <input
              type="text"
              inputMode="decimal"
              value={valorTexto}
              onChange={(e) => setValorTexto(e.target.value)}
              placeholder="50,00"
              className="w-full border border-graphite-700 bg-graphite-900 py-2 pl-9 pr-3 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
            />
          </div>
          <span className="text-xs text-graphite-400">
            Valor atual aplicado: {formatarMoeda(config.valorMensalidade)}. Alterar aqui não modifica
            pagamentos já registrados — apenas cobranças a partir de agora.
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
