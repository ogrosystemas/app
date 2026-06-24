import { Bell, CheckCircle2, LogOut, QrCode, Skull, XCircle } from "lucide-react";
import { useMemo, useState } from "react";
import { usePagamentosDoMembro } from "../../hooks/usePagamentos";
import { useAvisos, useAvisosDoMembro, jaAvisouCompetencia } from "../../hooks/useAvisos";
import { PixPaymentModal } from "../members/PixPaymentModal";
import type { Competencia, ConfigPix, Membro } from "../../types";
import { competenciaAtual, formatarCompetencia } from "../../utils/date.utils";
import {
  calcularInadimplenciaMembro,
  chaveCompetencia,
  gerarCompetenciasEsperadasHistorico,
  textoBadgeStatus,
} from "../../utils/status.utils";
import { Badge } from "../ui/Badge";
import { Button } from "../ui/Button";

interface MemberSelfViewProps {
  clubeId: string;
  membro: Membro;
  valorMensalidade: number;
  pix: ConfigPix | undefined;
  onSair: () => Promise<void>;
}

/**
 * Área restrita do integrante comum: somente leitura do próprio status e histórico,
 * sem valores em reais e sem nenhuma ação de administração — com DUAS exceções pontuais:
 * o aviso informal "vou pagar [competência]" (não altera o status real, é só um lembrete
 * visível para o administrador) e o botão de gerar Pix para pagar a própria mensalidade
 * pendente (único lugar desta tela em que um valor em R$ aparece, dentro do modal de
 * pagamento — necessário para o pagamento em si, mas nunca exibido na lista/histórico).
 *
 * O status é sempre calculado em relação ao mês atual REAL (hoje) — diferente da
 * área administrativa, aqui não existe seletor de mês para navegar para o passado.
 * `clubeId` identifica a sede a que este membro pertence — todo dado lido/escrito
 * aqui (pagamentos, avisos) fica isolado dentro dela.
 */
export function MemberSelfView({ clubeId, membro, valorMensalidade, pix, onSair }: MemberSelfViewProps) {
  const competenciaHoje = competenciaAtual();
  const pagamentos = usePagamentosDoMembro(clubeId, membro.id);
  const avisos = useAvisosDoMembro(clubeId, membro.id);
  const { enviarAviso } = useAvisos(clubeId);
  const [enviando, setEnviando] = useState<string | null>(null);
  const [competenciaParaPagar, setCompetenciaParaPagar] = useState<Competencia | null>(null);

  const resumo = useMemo(
    () => calcularInadimplenciaMembro(membro, pagamentos, competenciaHoje, valorMensalidade),
    [membro, pagamentos, competenciaHoje, valorMensalidade],
  );

  const linhasHistorico = useMemo(() => {
    const todas = gerarCompetenciasEsperadasHistorico(membro, competenciaHoje);
    const pagamentosPorChave = new Set(pagamentos.map((p) => chaveCompetencia(p)));

    return todas
      .slice()
      .reverse()
      .map((competencia: Competencia) => ({
        competencia,
        pago: pagamentosPorChave.has(chaveCompetencia(competencia)),
      }));
  }, [membro, pagamentos, competenciaHoje]);

  const emDia = resumo.totalMesesPendentes === 0;
  const afastado = membro.status === "afastado";

  async function handleAvisar(competencia: Competencia) {
    if (membro.id === undefined) return;
    setEnviando(chaveCompetencia(competencia));
    try {
      await enviarAviso(membro.id, competencia);
    } finally {
      setEnviando(null);
    }
  }

  return (
    <div className="flex min-h-screen flex-col bg-graphite-950">
      <header className="sticky top-0 z-30 flex items-center justify-between border-b border-graphite-700 bg-graphite-950/95 px-4 py-3 backdrop-blur-sm">
        <div className="flex items-center gap-2.5">
          <Skull className="text-ember-500" size={24} strokeWidth={2} />
          <div className="flex flex-col leading-tight">
            <span className="font-display text-base font-bold uppercase tracking-wide text-chrome-50">
              {membro.apelido}
            </span>
            <span className="text-[10px] font-medium uppercase tracking-wide text-graphite-400">
              Minha situação
            </span>
          </div>
        </div>
        <button
          type="button"
          onClick={onSair}
          aria-label="Sair"
          className="rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
        >
          <LogOut size={20} />
        </button>
      </header>

      <div className="flex flex-col gap-4 p-4">
        <div className="flex flex-col items-center gap-3 border border-graphite-700 bg-graphite-800 px-4 py-6 text-center">
          <span className="text-xs font-semibold uppercase tracking-wide text-graphite-400">
            Situação em {formatarCompetencia(competenciaHoje)}
          </span>
          <Badge variant={afastado ? "neutro" : emDia ? "ok" : "alerta"}>
            {textoBadgeStatus(resumo, afastado)}
          </Badge>
          {membro.patente && (
            <span className="text-xs text-graphite-400">{membro.patente}</span>
          )}
        </div>

        <div>
          <h2 className="mb-2 font-display text-sm font-semibold uppercase tracking-widest2 text-graphite-400">
            Histórico
          </h2>
          {linhasHistorico.length === 0 ? (
            <p className="py-6 text-center text-sm text-graphite-400">
              Nenhuma competência registrada ainda.
            </p>
          ) : (
            <ul className="flex flex-col border border-graphite-800">
              {linhasHistorico.map(({ competencia, pago }) => {
                const chave = chaveCompetencia(competencia);
                const jaAvisou = jaAvisouCompetencia(avisos, competencia);

                return (
                  <li
                    key={chave}
                    className="flex items-center justify-between gap-3 border-b border-graphite-800 bg-graphite-900 px-4 py-3 last:border-b-0"
                  >
                    <div className="flex items-center gap-2.5">
                      {pago ? (
                        <CheckCircle2 className="text-ok-500" size={18} />
                      ) : (
                        <XCircle className="text-alert-500" size={18} />
                      )}
                      <span className="font-display text-sm font-medium uppercase tracking-wide text-chrome-50">
                        {formatarCompetencia(competencia)}
                      </span>
                    </div>

                    {pago ? (
                      <span className="text-xs font-semibold uppercase tracking-wide text-ok-400">
                        Pago
                      </span>
                    ) : (
                      <div className="flex items-center gap-2">
                        <button
                          type="button"
                          onClick={() => setCompetenciaParaPagar(competencia)}
                          aria-label={`Pagar ${formatarCompetencia(competencia)} via Pix`}
                          title="Pagar via Pix"
                          className="rounded-sm p-1.5 text-ember-500 hover:bg-ember-950"
                        >
                          <QrCode size={16} />
                        </button>
                        {jaAvisou ? (
                          <span className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-ember-500">
                            <Bell size={13} />
                            Avisado
                          </span>
                        ) : (
                          <Button
                            size="sm"
                            variant="secondary"
                            icon={<Bell size={13} />}
                            onClick={() => handleAvisar(competencia)}
                            disabled={enviando === chave}
                          >
                            {enviando === chave ? "Enviando..." : "Vou pagar"}
                          </Button>
                        )}
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      </div>

      <PixPaymentModal
        aberto={competenciaParaPagar !== null}
        onFechar={() => setCompetenciaParaPagar(null)}
        pix={pix}
        apelidoMembro={membro.apelido}
        competencia={competenciaParaPagar ?? competenciaHoje}
        valor={valorMensalidade}
      />
    </div>
  );
}
