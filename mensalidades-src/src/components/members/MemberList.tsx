import { Users } from "lucide-react";
import { useMemo, useState } from "react";
import type { MembroComStatus } from "../../hooks/useInadimplencia";
import type { AvisoPagamento, Competencia } from "../../types";
import { chaveCompetencia } from "../../utils/status.utils";
import { EmptyState } from "../ui/EmptyState";
import { MemberListItem } from "./MemberListItem";

interface MemberListProps {
  membrosComStatus: MembroComStatus[];
  avisos: AvisoPagamento[];
  carregando: boolean;
  onDarBaixaRapida: (membroId: string, competencia: Competencia) => void;
  onAbrirNegociacao: (membroId: string) => void;
  onAbrirAdiantamento: (membroId: string) => void;
  onAbrirHistorico: (membroId: string) => void;
  onAbrirAcoes: (membroId: string) => void;
  onAbrirPix: (membroId: string, competencia: Competencia) => void;
}

/** Lista principal de conferência — busca local por nome/apelido. Mostra membros ativos e afastados. */
export function MemberList({
  membrosComStatus,
  avisos,
  carregando,
  onDarBaixaRapida,
  onAbrirNegociacao,
  onAbrirAdiantamento,
  onAbrirHistorico,
  onAbrirAcoes,
  onAbrirPix,
}: MemberListProps) {
  const [busca, setBusca] = useState("");

  const filtrados = useMemo(() => {
    const termo = busca.trim().toLowerCase();
    if (!termo) return membrosComStatus;
    return membrosComStatus.filter(
      ({ membro }) =>
        membro.apelido.toLowerCase().includes(termo) || membro.nome.toLowerCase().includes(termo),
    );
  }, [membrosComStatus, busca]);

  /**
   * Para cada membro, verifica se existe AO MENOS UM aviso informal de pagamento
   * (ver useAvisos) referente a uma competência que ainda está na lista de
   * pendências dele — um aviso de um mês que já foi pago (ex: baixa registrada
   * depois do aviso) não deveria continuar acendendo o sininho indefinidamente.
   */
  const temAvisoPendentePorMembro = useMemo(() => {
    const mapa = new Map<string, boolean>();
    for (const { membro, resumo } of membrosComStatus) {
      if (membro.id === undefined) continue;
      const chavesPendentes = new Set(resumo.competenciasPendentes.map((c) => chaveCompetencia(c)));
      const possuiAviso = avisos.some(
        (a) => a.membroId === membro.id && chavesPendentes.has(chaveCompetencia(a)),
      );
      mapa.set(membro.id, possuiAviso);
    }
    return mapa;
  }, [membrosComStatus, avisos]);

  if (carregando) {
    return (
      <div className="flex flex-col gap-2 px-4 py-3">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="h-16 animate-pulse border border-graphite-800 bg-graphite-900" />
        ))}
      </div>
    );
  }

  return (
    <div className="flex flex-1 flex-col">
      <div className="px-4 py-2">
        <input
          type="text"
          value={busca}
          onChange={(e) => setBusca(e.target.value)}
          placeholder="Buscar por nome ou apelido..."
          className="w-full border border-graphite-700 bg-graphite-900 px-3 py-2 text-sm text-chrome-50 placeholder:text-graphite-400 focus:border-ember-500"
        />
      </div>

      {filtrados.length === 0 ? (
        <EmptyState
          icon={<Users size={36} />}
          titulo={membrosComStatus.length === 0 ? "Nenhum membro cadastrado" : "Nenhum resultado"}
          descricao={
            membrosComStatus.length === 0
              ? "Cadastre o primeiro integrante do clube para começar a conferência."
              : "Tente buscar por outro nome ou apelido."
          }
        />
      ) : (
        <ul className="flex flex-col">
          {filtrados.map(({ membro, resumo }) => (
            <MemberListItem
              key={membro.id}
              membro={membro}
              resumo={resumo}
              temAvisoPendente={membro.id !== undefined && (temAvisoPendentePorMembro.get(membro.id) ?? false)}
              onDarBaixaRapida={() => {
                // Dá baixa na competência PENDENTE REAL do membro (resumo.competenciasPendentes[0]),
                // não na competência selecionada no seletor do topo — isso garante que o botão
                // "Dar Baixa" sempre regularize a mensalidade certa, mesmo se o membro estiver
                // afastado e a única pendência dele for de um mês anterior ao mês em exibição.
                const competenciaPendente = resumo.competenciasPendentes[0];
                if (competenciaPendente) onDarBaixaRapida(membro.id as string, competenciaPendente);
              }}
              onAbrirNegociacao={() => onAbrirNegociacao(membro.id as string)}
              onAbrirAdiantamento={() => onAbrirAdiantamento(membro.id as string)}
              onAbrirHistorico={() => onAbrirHistorico(membro.id as string)}
              onAbrirAcoes={() => onAbrirAcoes(membro.id as string)}
              onAbrirPix={() => {
                const competenciaPendente = resumo.competenciasPendentes[0];
                if (competenciaPendente) onAbrirPix(membro.id as string, competenciaPendente);
              }}
            />
          ))}
        </ul>
      )}
    </div>
  );
}
