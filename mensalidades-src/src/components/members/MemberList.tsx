import { Users } from "lucide-react";
import { useMemo, useState } from "react";
import type { MembroComStatus } from "../../hooks/useInadimplencia";
import type { Competencia } from "../../types";
import { EmptyState } from "../ui/EmptyState";
import { MemberListItem } from "./MemberListItem";

interface MemberListProps {
  membrosComStatus: MembroComStatus[];
  carregando: boolean;
  onDarBaixaRapida: (membroId: number, competencia: Competencia) => void;
  onAbrirNegociacao: (membroId: number) => void;
  onAbrirHistorico: (membroId: number) => void;
  onAbrirAcoes: (membroId: number) => void;
}

/** Lista principal de conferência — busca local por nome/apelido. Mostra membros ativos e afastados. */
export function MemberList({
  membrosComStatus,
  carregando,
  onDarBaixaRapida,
  onAbrirNegociacao,
  onAbrirHistorico,
  onAbrirAcoes,
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
              onDarBaixaRapida={() => {
                // Dá baixa na competência PENDENTE REAL do membro (resumo.competenciasPendentes[0]),
                // não na competência selecionada no seletor do topo — isso garante que o botão
                // "Dar Baixa" sempre regularize a mensalidade certa, mesmo se o membro estiver
                // afastado e a única pendência dele for de um mês anterior ao mês em exibição.
                const competenciaPendente = resumo.competenciasPendentes[0];
                if (competenciaPendente) onDarBaixaRapida(membro.id as number, competenciaPendente);
              }}
              onAbrirNegociacao={() => onAbrirNegociacao(membro.id as number)}
              onAbrirHistorico={() => onAbrirHistorico(membro.id as number)}
              onAbrirAcoes={() => onAbrirAcoes(membro.id as number)}
            />
          ))}
        </ul>
      )}
    </div>
  );
}
