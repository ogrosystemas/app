import { Building2, Download, FileText, LogOut, Upload } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useBackup } from "../../hooks/useBackup";
import type { ConfigClube, ConfigPix } from "../../types";
import { formatarMoeda, parseMoeda } from "../../utils/currency.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface SettingsModalProps {
  clubeId: string;
  aberto: boolean;
  config: ConfigClube;
  onFechar: () => void;
  onSalvar: (nomeClube: string, valorMensalidade: number, pix?: ConfigPix) => Promise<void>;
  onAbrirRelatorio: () => void;
  emailLogado: string | null;
  onSair: () => Promise<void>;
  /** Presente somente para Super Admin — mostra o botão "Trocar sede". */
  onTrocarSede?: () => void;
}

/**
 * Modal de configurações gerais da sede: nome exibido no header, valor da mensalidade,
 * dados da chave Pix DESTA sede (própria, não compartilhada com outras), relatórios em
 * PDF, backup/restauração de dados, e a conta autenticada (com opção de sair). Alterar
 * o valor da mensalidade NÃO afeta pagamentos já registrados (cada Pagamento guarda seu
 * próprio valorPago, congelado no momento da baixa) — afeta apenas o cálculo de
 * pendências futuras e o resumo do dashboard.
 */
export function SettingsModal({
  clubeId,
  aberto,
  config,
  onFechar,
  onSalvar,
  onAbrirRelatorio,
  emailLogado,
  onSair,
  onTrocarSede,
}: SettingsModalProps) {
  const [nomeClube, setNomeClube] = useState(config.nomeClube);
  const [valorTexto, setValorTexto] = useState(String(config.valorMensalidade).replace(".", ","));
  const [pixChave, setPixChave] = useState(config.pix?.chave ?? "");
  const [pixNomeRecebedor, setPixNomeRecebedor] = useState(config.pix?.nomeRecebedor ?? "");
  const [pixCidade, setPixCidade] = useState(config.pix?.cidade ?? "");
  const [salvando, setSalvando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  const { exportarBackup, importarArquivo } = useBackup(clubeId);
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
    setPixChave(config.pix?.chave ?? "");
    setPixNomeRecebedor(config.pix?.nomeRecebedor ?? "");
    setPixCidade(config.pix?.cidade ?? "");
    setErro(null);
    setMensagemBackup(null);
  }, [aberto, config.nomeClube, config.valorMensalidade, config.pix]);

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

    // O Pix é opcional como um todo (a sede pode não ter configurado ainda), mas se
    // qualquer um dos 3 campos foi preenchido, exige todos os 3 — não tem sentido
    // salvar uma chave sem nome/cidade (o QR Code geraria payload inválido).
    const algumCampoPixPreenchido = pixChave.trim() || pixNomeRecebedor.trim() || pixCidade.trim();
    if (algumCampoPixPreenchido && (!pixChave.trim() || !pixNomeRecebedor.trim() || !pixCidade.trim())) {
      setErro("Para configurar o Pix, preencha chave, nome do recebedor e cidade — os três campos.");
      return;
    }

    setSalvando(true);
    setErro(null);
    try {
      const pix: ConfigPix | undefined = algumCampoPixPreenchido
        ? { chave: pixChave.trim(), nomeRecebedor: pixNomeRecebedor.trim(), cidade: pixCidade.trim() }
        : undefined;
      await onSalvar(nomeClube.trim(), valor, pix);
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
            Pix desta sede
          </span>
          <p className="mb-2 text-xs text-graphite-400">
            Usado para gerar os QR Codes de cobrança — exclusivo desta sede, nunca
            compartilhado com outras.
          </p>

          <div className="flex flex-col gap-3">
            <Campo label="Chave Pix">
              <input
                type="text"
                value={pixChave}
                onChange={(e) => setPixChave(e.target.value)}
                placeholder="+55DDDNNNNNNNNN, CPF/CNPJ, e-mail ou chave aleatória"
                className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
              />
            </Campo>
            <Campo label="Nome do recebedor (como na conta bancária)">
              <input
                type="text"
                value={pixNomeRecebedor}
                onChange={(e) => setPixNomeRecebedor(e.target.value)}
                placeholder="Ex: João da Silva"
                className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
              />
            </Campo>
            <Campo label="Cidade do recebedor">
              <input
                type="text"
                value={pixCidade}
                onChange={(e) => setPixCidade(e.target.value)}
                placeholder="Ex: Joinville"
                className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
              />
            </Campo>
          </div>
        </div>

        <div className="asphalt-divider pt-2">
          <span className="mb-2 mt-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Conta
          </span>
          {emailLogado && (
            <p className="mb-2 truncate text-sm text-graphite-200">{emailLogado}</p>
          )}
          {onTrocarSede && (
            <Button
              variant="secondary"
              fullWidth
              icon={<Building2 size={14} />}
              onClick={onTrocarSede}
              className="mb-2"
            >
              Trocar sede
            </Button>
          )}
          <Button variant="secondary" fullWidth icon={<LogOut size={14} />} onClick={onSair}>
            Sair
          </Button>
        </div>

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
