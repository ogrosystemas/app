import { Plus } from "lucide-react";
import { useState } from "react";
import { DashboardSummary } from "./components/dashboard";
import { AppHeader, MonthSelector } from "./components/layout";
import {
  MemberActionsModal,
  MemberFormModal,
  MemberHistoryModal,
  MemberList,
  NegotiationModal,
} from "./components/members";
import { SettingsModal } from "./components/settings";
import { Button, ConfirmDialog } from "./components/ui";
import { useConfig } from "./hooks/useConfig";
import { useDashboardResumo } from "./hooks/useDashboardResumo";
import { useInadimplencia } from "./hooks/useInadimplencia";
import { useMembros } from "./hooks/useMembros";
import { usePagamentos } from "./hooks/usePagamentos";
import type { Competencia, FormaPagamento, Membro } from "./types";
import { competenciaAtual } from "./utils/date.utils";

type ModalAtivo =
  | { tipo: "nenhum" }
  | { tipo: "cadastro" }
  | { tipo: "edicao"; membro: Membro }
  | { tipo: "historico"; membro: Membro }
  | { tipo: "negociacao"; membro: Membro }
  | { tipo: "acoes"; membro: Membro }
  | { tipo: "confirmar-exclusao"; membro: Membro }
  | { tipo: "configuracoes" };

/** Componente raiz: orquestra estado de competência selecionada e qual modal está aberto. */
export default function App() {
  const [competencia, setCompetencia] = useState<Competencia>(competenciaAtual());
  const [modal, setModal] = useState<ModalAtivo>({ tipo: "nenhum" });

  const { config, atualizarConfig } = useConfig();
  const { membros, criarMembro, editarMembro, afastarMembro, reativarMembro, excluirMembro } =
    useMembros();
  const { membrosComStatus, carregando: carregandoLista } = useInadimplencia(competencia);
  const { resumo, carregando: carregandoResumo } = useDashboardResumo(competencia);
  const { darBaixa, darBaixaEmLote } = usePagamentos();

  function buscarMembro(membroId: number): Membro | undefined {
    return membros.find((m) => m.id === membroId);
  }

  function buscarResumo(membroId: number) {
    return membrosComStatus.find((item) => item.membro.id === membroId)?.resumo;
  }

  function fechar() {
    setModal({ tipo: "nenhum" });
  }

  async function handleDarBaixaRapida(membroId: number) {
    await darBaixa({
      membroId,
      competencia,
      valorPago: config.valorMensalidade,
      formaPagamento: "pix",
    });
  }

  async function handleConfirmarNegociacao(
    membroId: number,
    competencias: Competencia[],
    valorTotalPago: number,
    formaPagamento: FormaPagamento,
    observacao?: string,
  ) {
    await darBaixaEmLote(membroId, competencias, valorTotalPago, formaPagamento, observacao);
  }

  const membroEmFoco =
    modal.tipo === "historico" ||
    modal.tipo === "negociacao" ||
    modal.tipo === "acoes" ||
    modal.tipo === "confirmar-exclusao"
      ? modal.membro
      : undefined;
  const resumoEmFoco = membroEmFoco?.id !== undefined ? buscarResumo(membroEmFoco.id) : undefined;

  return (
    <div className="flex min-h-screen flex-col bg-graphite-950">
      <AppHeader
        nomeClube={config.nomeClube}
        onAbrirConfiguracoes={() => setModal({ tipo: "configuracoes" })}
      />
      <MonthSelector competencia={competencia} onAlterar={setCompetencia} />
      <DashboardSummary resumo={resumo} carregando={carregandoResumo} />

      <div className="flex items-center justify-between px-4 pb-1 pt-2">
        <h2 className="font-display text-sm font-semibold uppercase tracking-widest2 text-graphite-400">
          Membros
        </h2>
        <Button size="sm" variant="primary" icon={<Plus size={14} />} onClick={() => setModal({ tipo: "cadastro" })}>
          Novo
        </Button>
      </div>

      <MemberList
        membrosComStatus={membrosComStatus}
        carregando={carregandoLista}
        onDarBaixaRapida={handleDarBaixaRapida}
        onAbrirNegociacao={(membroId) => {
          const membro = buscarMembro(membroId);
          if (membro) setModal({ tipo: "negociacao", membro });
        }}
        onAbrirHistorico={(membroId) => {
          const membro = buscarMembro(membroId);
          if (membro) setModal({ tipo: "historico", membro });
        }}
        onAbrirAcoes={(membroId) => {
          const membro = buscarMembro(membroId);
          if (membro) setModal({ tipo: "acoes", membro });
        }}
      />

      <MemberFormModal
        aberto={modal.tipo === "cadastro" || modal.tipo === "edicao"}
        membroParaEditar={modal.tipo === "edicao" ? modal.membro : undefined}
        onFechar={fechar}
        onSalvar={async (input) => {
          if (modal.tipo === "edicao" && modal.membro.id !== undefined) {
            await editarMembro(modal.membro.id, input);
          } else {
            await criarMembro(input);
          }
        }}
      />

      <MemberHistoryModal
        aberto={modal.tipo === "historico"}
        membro={membroEmFoco}
        onFechar={fechar}
      />

      <NegotiationModal
        aberto={modal.tipo === "negociacao"}
        membro={membroEmFoco}
        resumo={resumoEmFoco}
        valorMensalidade={config.valorMensalidade}
        onFechar={fechar}
        onConfirmar={async (competencias, valorTotalPago, formaPagamento, observacao) => {
          if (membroEmFoco?.id === undefined) return;
          await handleConfirmarNegociacao(
            membroEmFoco.id,
            competencias,
            valorTotalPago,
            formaPagamento,
            observacao,
          );
        }}
      />

      <MemberActionsModal
        aberto={modal.tipo === "acoes"}
        membro={membroEmFoco}
        onFechar={fechar}
        onEditar={() => {
          if (membroEmFoco) setModal({ tipo: "edicao", membro: membroEmFoco });
        }}
        onAfastar={async () => {
          if (membroEmFoco?.id !== undefined) await afastarMembro(membroEmFoco.id);
          fechar();
        }}
        onReativar={async () => {
          if (membroEmFoco?.id !== undefined) await reativarMembro(membroEmFoco.id);
          fechar();
        }}
        onExcluir={() => {
          if (membroEmFoco) setModal({ tipo: "confirmar-exclusao", membro: membroEmFoco });
        }}
      />

      <ConfirmDialog
        aberto={modal.tipo === "confirmar-exclusao"}
        titulo="Excluir membro"
        mensagem={
          membroEmFoco
            ? `Tem certeza que deseja excluir "${membroEmFoco.apelido}"? Isso remove o cadastro e todo o histórico de pagamentos permanentemente. Esta ação não pode ser desfeita.`
            : ""
        }
        textoConfirmar="Excluir"
        onCancelar={fechar}
        onConfirmar={async () => {
          if (membroEmFoco?.id !== undefined) await excluirMembro(membroEmFoco.id);
          fechar();
        }}
      />

      <SettingsModal
        aberto={modal.tipo === "configuracoes"}
        config={config}
        onFechar={fechar}
        onSalvar={async (nomeClube, valorMensalidade) => {
          await atualizarConfig({ nomeClube, valorMensalidade });
        }}
      />
    </div>
  );
}
