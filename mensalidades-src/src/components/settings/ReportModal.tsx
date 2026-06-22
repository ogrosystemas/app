import { FileText } from "lucide-react";
import { useState } from "react";
import { useRelatorio } from "../../hooks/useRelatorio";
import type { Competencia } from "../../types";
import {
  competenciaAnterior,
  competenciaAtual,
  formatarCompetencia,
} from "../../utils/date.utils";
import {
  filtroPorAno,
  filtroPorMes,
  filtroPorPeriodo,
  type TipoFiltroRelatorio,
} from "../../utils/relatorio.utils";
import { Button } from "../ui/Button";
import { Modal } from "../ui/Modal";

interface ReportModalProps {
  aberto: boolean;
  onFechar: () => void;
}

const TIPOS_FILTRO: { valor: TipoFiltroRelatorio; label: string }[] = [
  { valor: "mes", label: "Mês" },
  { valor: "periodo", label: "Período" },
  { valor: "ano", label: "Ano" },
];

/**
 * Modal de geração de relatório em PDF. Oferece 3 formas de filtrar o período:
 * - Mês único (ex: Junho/2026)
 * - Período customizado (ex: Março/2026 a Maio/2026)
 * - Ano completo (ex: 2026)
 *
 * A geração do PDF acontece inteiramente no navegador (sem servidor) — ver
 * pdf-relatorio.utils.ts.
 */
export function ReportModal({ aberto, onFechar }: ReportModalProps) {
  const { gerarEBaixarRelatorio } = useRelatorio();

  const competenciaHoje = competenciaAtual();
  const [tipoFiltro, setTipoFiltro] = useState<TipoFiltroRelatorio>("mes");
  const [mesUnico, setMesUnico] = useState<Competencia>(competenciaHoje);
  const [periodoInicio, setPeriodoInicio] = useState<Competencia>(
    competenciaAnterior(competenciaAnterior(competenciaHoje)),
  );
  const [periodoFim, setPeriodoFim] = useState<Competencia>(competenciaHoje);
  const [ano, setAno] = useState<number>(competenciaHoje.ano);
  const [gerando, setGerando] = useState(false);
  const [erro, setErro] = useState<string | null>(null);

  async function handleGerar() {
    setErro(null);

    const filtro =
      tipoFiltro === "mes"
        ? filtroPorMes(mesUnico.mes, mesUnico.ano)
        : tipoFiltro === "ano"
          ? filtroPorAno(ano)
          : filtroPorPeriodo(periodoInicio, periodoFim);

    if (tipoFiltro === "periodo" && compararParaValidacao(periodoInicio, periodoFim) > 0) {
      setErro("A competência inicial deve ser anterior (ou igual) à final.");
      return;
    }

    setGerando(true);
    try {
      await gerarEBaixarRelatorio(filtro);
      onFechar();
    } catch {
      setErro("Não foi possível gerar o relatório. Tente novamente.");
    } finally {
      setGerando(false);
    }
  }

  return (
    <Modal
      aberto={aberto}
      onFechar={onFechar}
      titulo="Gerar relatório"
      rodape={
        <Button
          variant="primary"
          fullWidth
          icon={<FileText size={14} />}
          onClick={handleGerar}
          disabled={gerando}
        >
          {gerando ? "Gerando PDF..." : "Gerar e baixar PDF"}
        </Button>
      }
    >
      <div className="flex flex-col gap-4">
        {erro && (
          <p className="border border-alert-600 bg-alert-950 px-3 py-2 text-sm text-alert-400">{erro}</p>
        )}

        <div>
          <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Tipo de filtro
          </span>
          <div className="grid grid-cols-3 gap-2">
            {TIPOS_FILTRO.map((opcao) => (
              <button
                key={opcao.valor}
                type="button"
                onClick={() => setTipoFiltro(opcao.valor)}
                className={`border px-3 py-2 text-sm font-display font-semibold uppercase tracking-wide transition-colors ${
                  tipoFiltro === opcao.valor
                    ? "border-ember-500 bg-ember-950 text-ember-500"
                    : "border-graphite-700 bg-graphite-900 text-graphite-300"
                }`}
              >
                {opcao.label}
              </button>
            ))}
          </div>
        </div>

        {tipoFiltro === "mes" && (
          <SeletorCompetencia label="Mês a relatar" valor={mesUnico} onAlterar={setMesUnico} />
        )}

        {tipoFiltro === "periodo" && (
          <div className="flex flex-col gap-3">
            <SeletorCompetencia label="De" valor={periodoInicio} onAlterar={setPeriodoInicio} />
            <SeletorCompetencia label="Até" valor={periodoFim} onAlterar={setPeriodoFim} />
          </div>
        )}

        {tipoFiltro === "ano" && (
          <label className="flex flex-col gap-1.5">
            <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">Ano</span>
            <input
              type="number"
              value={ano}
              onChange={(e) => setAno(Number(e.target.value))}
              className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 focus:border-ember-500"
            />
          </label>
        )}
      </div>
    </Modal>
  );
}

interface SeletorCompetenciaProps {
  label: string;
  valor: Competencia;
  onAlterar: (c: Competencia) => void;
}

/** Seletor simples de mês/ano via select + input numérico, usado dentro deste modal. */
function SeletorCompetencia({ label, valor, onAlterar }: SeletorCompetenciaProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">
        {label} — {formatarCompetencia(valor)}
      </span>
      <div className="flex gap-2">
        <select
          value={valor.mes}
          onChange={(e) => onAlterar({ ...valor, mes: Number(e.target.value) })}
          className="flex-1 border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 focus:border-ember-500"
        >
          {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
            <option key={m} value={m}>
              {formatarCompetencia({ mes: m, ano: valor.ano }).split("/")[0]}
            </option>
          ))}
        </select>
        <input
          type="number"
          value={valor.ano}
          onChange={(e) => onAlterar({ ...valor, ano: Number(e.target.value) })}
          className="w-24 border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 focus:border-ember-500"
        />
      </div>
    </div>
  );
}

function compararParaValidacao(a: Competencia, b: Competencia): number {
  if (a.ano !== b.ano) return a.ano - b.ano;
  return a.mes - b.mes;
}
