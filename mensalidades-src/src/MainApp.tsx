import { Plus } from "lucide-react";
import { useState } from "react";
import { DashboardSummary } from "./components/dashboard";
import { AppHeader, MonthSelector } from "./components/layout";
import {
  EditPaymentModal,
  MemberActionsModal,
  MemberFormModal,
  MemberHistoryModal,
  MemberList,
  NegotiationModal,
  PixPaymentModal,
} from "./components/members";
import { ForegroundNotificationToast, UpdateBanner } from "./components/pwa";
import { ReportModal, SettingsModal } from "./components/settings";
import { Button, ConfirmDialog } from "./components/ui";
import { useAvisosDoClube } from "./hooks/useAvisos";
import { useConfig } from "./hooks/useConfig";
import { useSede } from "./hooks/useSede";
import { useDashboardResumo } from "./hooks/useDashboardResumo";
import { useInadimplencia } from "./hooks/useInadimplencia";
import { useMembros } from "./hooks/useMembros";
import { usePagamentos } from "./hooks/usePagamentos";
import type { Competencia, FormaPagamento, Membro, Pagamento } from "./types";
import { competenciaAtual } from "./utils/date.utils";

type ModalAtivo =
  | { tipo: "nenhum" }
  | { tipo: "cadastro" }
  | { tipo: "edicao"; membro: Membro }
  | { tipo: "historico"; membro: Membro }
  | { tipo: "negociacao"; membro: Membro }
  | { tipo: "acoes"; membro: Membro }
  | { tipo: "confirmar-exclusao"; membro: Membro }
  | { tipo: "pix"; membro: Membro; competencia: Competencia }
  | { tipo: "configuracoes" };

interface MainAppProps {
  clubeId: string;
  emailLogado: string | null;
  onSair: () => Promise<void>;
  /** Presente somente para Super Admin — volta à tela de escolha de sede sem deslogar. */
  onTrocarSede?: () => void;
}

/**
 * Conteúdo principal do app — tudo relacionado à conferência de mensalidades
 * de UMA sede específica. Renderizado pelo App.tsx somente depois que o usuário
 * está autenticado E autorizado a administrar a sede identificada por `clubeId`.
 */
export function MainApp({ clubeId, emailLogado, onSair, onTrocarSede }: MainAppProps) {
  const [competencia, setCompetencia] = useState<Competencia>(competenciaAtual());
  const [modal, setModal] = useState<ModalAtivo>({ tipo: "nenhum" });
  const [pagamentoEmEdicao, setPagamentoEmEdicao] = useState<{
    pagamento: Pagamento;
    competencia: Competencia;
  } | null>(null);
  const [relatorioAberto, setRelatorioAberto] = useState(false);

  const { config, atualizarConfig } = useConfig(clubeId);
  const { sede } = useSede(clubeId);
  const { membros, criarMembro, editarMembro, afastarMembro, reativarMembro, excluirMembro } =
    useMembros(clubeId);
  const { membrosComStatus, carregando: carregandoLista } = useInadimplencia(clubeId, competencia);
  const { resumo, carregando: carregandoResumo } = useDashboardResumo(clubeId, competencia);
  const { darBaixa, darBaixaEmLote, editarPagamento, removerBaixa } = usePagamentos(clubeId);
  const avisos = useAvisosDoClube(clubeId);

  function buscarMembro(membroId: string): Membro | undefined {
    return membros.find((m) => m.id === membroId);
  }

  function fechar() {
    setModal({ tipo: "nenhum" });
  }

  async function handleDarBaixaRapida(membroId: string, competenciaPendente: Competencia) {
    await darBaixa({
      membroId,
      competencia: competenciaPendente,
      valorPago: config.valorMensalidade,
      formaPagamento: "pix",
    });
  }

  async function handleConfirmarNegociacao(
    membroId: string,
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

  return (
    <div className="flex min-h-screen flex-col bg-graphite-950">
      <AppHeader
        nomeClube={config.nomeClube}
        tipoSede={sede?.tipo}
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
        avisos={avisos}
        carregando={carregandoLista}
        onDarBaixaRapida={handleDarBaixaRapida}
        onAbrirNegociacao={(membroId) => {
          const membro = buscarMembro(membroId);
          if (membro) setModal({ tipo: "negociacao", membro });
        }}
        onAbrirAdiantamento={(membroId) => {
          // Mesmo modal da negociação — para um membro em dia, nenhum mês "pendente"
          // existe, então o NegotiationModal naturalmente não pré-seleciona nada,
          // deixando só os meses futuros disponíveis para escolha (pagamento adiantado).
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
        onAbrirPix={(membroId, competenciaPix) => {
          const membro = buscarMembro(membroId);
          if (membro) setModal({ tipo: "pix", membro, competencia: competenciaPix });
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
            const { emailAcesso, ...dadosCadastro } = input;
            const novoId = await criarMembro(dadosCadastro);
            // editarMembro cuida de sincronizar a coleção "acessos" (ver useMembros.ts) —
            // chamado separadamente aqui porque criarMembro não lida com esse campo, já
            // que um membro recém-criado ainda não tem um documento anterior para comparar.
            if (emailAcesso) {
              await editarMembro(novoId, { emailAcesso });
            }
          }
        }}
      />

      <MemberHistoryModal
        clubeId={clubeId}
        aberto={modal.tipo === "historico"}
        membro={membroEmFoco}
        onFechar={fechar}
        onEditarPagamento={(pagamento, competenciaPagamento) =>
          setPagamentoEmEdicao({ pagamento, competencia: competenciaPagamento })
        }
      />

      <NegotiationModal
        aberto={modal.tipo === "negociacao"}
        clubeId={clubeId}
        membro={membroEmFoco}
        competenciaReferencia={competencia}
        valorMensalidade={config.valorMensalidade}
        pix={config.pix}
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
        clubeId={clubeId}
        aberto={modal.tipo === "configuracoes"}
        config={config}
        onFechar={fechar}
        onSalvar={async (nomeClube, valorMensalidade, pix) => {
          await atualizarConfig({ nomeClube, valorMensalidade, pix });
        }}
        onAbrirRelatorio={() => setRelatorioAberto(true)}
        emailLogado={emailLogado}
        // ID do membro vinculado a este e-mail nesta sede, se o próprio tesoureiro
        // também for um membro cadastrado (emailAcesso comparado em minúsculas,
        // mesma normalização usada nas regras do Firestore) — usado só para o
        // toggle de notificações push saber se deve calcular pendência pessoal
        // além do resumo da sede.
        membroIdDoEmailLogado={
          membros.find((m) => m.emailAcesso?.trim().toLowerCase() === emailLogado?.trim().toLowerCase())?.id
        }
        onSair={onSair}
        onTrocarSede={onTrocarSede}
      />

      <ReportModal clubeId={clubeId} aberto={relatorioAberto} onFechar={() => setRelatorioAberto(false)} />

      <EditPaymentModal
        aberto={pagamentoEmEdicao !== null}
        pagamento={pagamentoEmEdicao?.pagamento}
        competencia={pagamentoEmEdicao?.competencia}
        apelidoMembro={membroEmFoco?.apelido}
        onFechar={() => setPagamentoEmEdicao(null)}
        onSalvar={async (valorPago, dataPagamento, formaPagamento) => {
          if (pagamentoEmEdicao?.pagamento.id === undefined) return;
          await editarPagamento(pagamentoEmEdicao.pagamento.id, {
            valorPago,
            dataPagamento,
            formaPagamento,
          });
        }}
        onEstornar={async () => {
          if (!pagamentoEmEdicao) return;
          await removerBaixa(pagamentoEmEdicao.pagamento.membroId, pagamentoEmEdicao.competencia);
        }}
      />

      <PixPaymentModal
        aberto={modal.tipo === "pix"}
        onFechar={fechar}
        pix={config.pix}
        apelidoMembro={modal.tipo === "pix" ? modal.membro.apelido : ""}
        competencia={modal.tipo === "pix" ? modal.competencia : competenciaAtual()}
        valor={config.valorMensalidade}
      />

      <UpdateBanner />
      <ForegroundNotificationToast />
    </div>
  );
}
