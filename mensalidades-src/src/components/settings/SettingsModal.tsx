import { Download, FileText, Upload } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useBackup } from "../../hooks/useBackup";
import type { ConfigClube } from "../../types";
import { formatarMoeda, parseMoeda } from "../../utils/currency.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface SettingsModalProps {
  aberto: boolean;
  config: ConfigClube;
  onFechar: () => void;
  onSalvar: (nomeClube: string, valorMensalidade: number) => Promise<void>;
  onAbrirRelatorio: () => void;
}

/**
 * Modal de configurações gerais do clube: nome exibido no header, valor da mensalidade,
 * relatórios em PDF, e backup/restauração de dados. Alterar o valor da mensalidade NÃO
 * afeta pagamentos já registrados (cada Pagamento guarda seu próprio valorPago, congelado
 * no momento da baixa) — afeta apenas o cálculo de pendências futuras e o resumo do dashboard.
 */
export function SettingsModal({ aberto, config, onFechar, onSalvar, onAbrirRelatorio }: SettingsModalProps) {
  const [nomeClube, setNomeClube] = useState(config.nomeClube);
  const [valorTexto, setValorTexto] = useState(String(config.valorMensalidade).replace(".", ","));
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  const { exportarBackup, importarArquivo } = useBackup();
  const inputArquivoRef = useRef<HTMLInputElement>(null);
  const [exportando, setExportando] = useState(false);
  const [importando, setImportando] = useState(false);
  const [mensagemBackup, setMensagemBackup] = useState<{ tipo: "ok" | "erro"; texto: string } | null>(
    null,
  );

  useEffect(() => {
    if (!aberto) return;
    setNomeClube(config.nomeClube);
    setValorTexto(String(config.valorMensalidade).replace(".", ","));
    setErro(null);
    setMensagemBackup(null);
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

  async function handleExportar() {
    setExportando(true);
    setMensagemBackup(null);
    try {
      await exportarBackup();
      setMensagemBackup({ tipo: "ok", texto: "Backup baixado com sucesso." });
    } catch {
      setMensagemBackup({ tipo: "erro", texto: "Não foi possível gerar o backup." });
    } finally {
      setExportando(false);
    }
  }

  function handleEscolherArquivo() {
    inputArquivoRef.current?.click();
  }

  async function handleArquivoSelecionado(evento: React.ChangeEvent<HTMLInputElement>) {
    const arquivo = evento.target.files?.[0];
    evento.target.value = ""; // permite selecionar o mesmo arquivo de novo depois, se precisar
    if (!arquivo) return;

    setImportando(true);
    setMensagemBackup(null);
    try {
      const resultado = await importarArquivo(arquivo);
      setMensagemBackup({
        tipo: "ok",
        texto: `Importado: ${resultado.membrosAdicionados} membro(s) e ${resultado.pagamentosAdicionados} pagamento(s) novo(s). (${resultado.membrosJaExistentes} membro(s) e ${resultado.pagamentosJaExistentes} pagamento(s) já existiam e foram ignorados.)`,
      });
    } catch (erroImportacao) {
      const texto =
        erroImportacao instanceof Error ? erroImportacao.message : "Não foi possível importar o arquivo.";
      setMensagemBackup({ tipo: "erro", texto });
    } finally {
      setImportando(false);
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

        <div className="asphalt-divider pt-2">
          <span className="mb-2 mt-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Relatórios
          </span>
          <Button variant="secondary" fullWidth icon={<FileText size={14} />} onClick={onAbrirRelatorio}>
            Gerar relatório em PDF
          </Button>
        </div>

        <div className="asphalt-divider pt-2">
          <span className="mb-2 mt-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Backup e restauração
          </span>

          {mensagemBackup && (
            <p
              className={`mb-3 border px-3 py-2 text-sm ${
                mensagemBackup.tipo === "ok"
                  ? "border-ok-600 bg-ok-950 text-ok-400"
                  : "border-alert-600 bg-alert-950 text-alert-400"
              }`}
            >
              {mensagemBackup.texto}
            </p>
          )}

          <div className="flex flex-col gap-2">
            <Button
              variant="secondary"
              fullWidth
              icon={<Download size={14} />}
              onClick={handleExportar}
              disabled={exportando || importando}
            >
              {exportando ? "Gerando..." : "Exportar backup (.json)"}
            </Button>

            <Button
              variant="secondary"
              fullWidth
              icon={<Upload size={14} />}
              onClick={handleEscolherArquivo}
              disabled={exportando || importando}
            >
              {importando ? "Importando..." : "Importar backup"}
            </Button>
            <input
              ref={inputArquivoRef}
              type="file"
              accept="application/json,.json"
              onChange={handleArquivoSelecionado}
              className="hidden"
            />
          </div>

          <p className="mt-2 text-xs text-graphite-400">
            Importar um backup soma os dados ao que já existe no app — membros e pagamentos
            que já estiverem aqui não são duplicados.
          </p>
        </div>
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
