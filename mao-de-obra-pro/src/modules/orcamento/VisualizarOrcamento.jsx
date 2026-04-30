import React, { useState, useEffect } from 'react';
import {
  ArrowLeft,
  Send,
  Camera as CameraIcon,
  CheckCircle,
  XCircle,
  Clock,
  Calendar,
  Trash2,
  CheckSquare
} from 'lucide-react';
import db from '../../database/db';
import { formatarMoeda } from '../../core/calculadora';
import ConfirmModal from '../../components/ConfirmModal';

const VisualizarOrcamento = ({ onBack, id, showToast }) => {
  const [orcamento, setOrcamento] = useState(null);
  const [cliente, setCliente] = useState(null);
  const [loading, setLoading] = useState(true);
  const [enviando, setEnviando] = useState(false);
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showConcluirModal, setShowConcluirModal] = useState(false);

  useEffect(() => {
    if (id) {
      loadOrcamentoPorId(id);
    } else {
      loadLastOrcamento();
    }
  }, [id]);

  const loadOrcamentoPorId = async (orcamentoId) => {
    try {
      const budget = await db.orcamentos.get(parseInt(orcamentoId));
      if (budget) {
        setOrcamento(budget);
        const client = await db.clientes.get(budget.clienteId);
        setCliente(client);
      }
    } catch (error) {
      console.error('Error loading budget:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadLastOrcamento = async () => {
    try {
      const budgets = await db.orcamentos.orderBy('data').reverse().limit(1).toArray();
      if (budgets.length > 0) {
        setOrcamento(budgets[0]);
        const client = await db.clientes.get(budgets[0].clienteId);
        setCliente(client);
      }
    } catch (error) {
      console.error('Error loading budget:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateStatus = async (newStatus) => {
    if (orcamento) {
      await db.orcamentos.update(orcamento.id, { status: newStatus });
      setOrcamento({ ...orcamento, status: newStatus });
      setShowStatusModal(false);
      const statusMsg = newStatus === 'aprovado' ? 'Aprovado' : newStatus === 'pendente' ? 'Pendente' : 'Recusado';
      showToast(`Status alterado para ${statusMsg}!`, 'success');
    }
  };

  const concluirOrcamento = async () => {
    if (!orcamento) return;

    if (orcamento.status === 'concluido') {
      showToast('Orçamento já está concluído!', 'warning');
      setShowConcluirModal(false);
      return;
    }

    if (orcamento.status !== 'aprovado') {
      showToast('Apenas orçamentos aprovados podem ser concluídos.', 'warning');
      setShowConcluirModal(false);
      return;
    }

    // Lançar no caixa
    const lancamento = {
      data: new Date().toISOString(),
      tipo: 'entrada',
      categoria: 'Serviço',
      descricao: `Orçamento #${orcamento.id} - ${cliente?.nome}`,
      valor: orcamento.total,
      orcamentoId: orcamento.id
    };
    await db.caixa.add(lancamento);

    // Atualizar status do orçamento
    await db.orcamentos.update(orcamento.id, { status: 'concluido' });
    setOrcamento({ ...orcamento, status: 'concluido' });

    showToast(`Orçamento concluído! Valor de ${formatarMoeda(orcamento.total)} lançado no caixa.`, 'success');
    setShowConcluirModal(false);
  };

  const deleteOrcamento = async () => {
    if (orcamento) {
      const lancamentoExistente = await db.caixa.where('orcamentoId').equals(orcamento.id).first();
      if (lancamentoExistente && confirm('Este orçamento possui um lançamento no caixa. Deseja excluí-lo também?')) {
        await db.caixa.where('orcamentoId').equals(orcamento.id).delete();
      }
      await db.orcamentos.delete(orcamento.id);
      setShowDeleteModal(false);
      showToast('Orçamento excluído com sucesso!', 'success');
      onBack();
    }
  };

  const sendWhatsApp = async () => {
    if (!cliente?.whatsapp) {
      showToast('Cliente não possui WhatsApp cadastrado', 'warning');
      return;
    }

    setEnviando(true);

    const dataVencimento = orcamento.dataVencimento
      ? new Date(orcamento.dataVencimento).toLocaleDateString('pt-BR')
      : 'Não informada';

    const descontoInfo = orcamento.desconto ?
      (orcamento.desconto > 0 ? `Desconto: ${formatarMoeda(orcamento.desconto)}` : `Desconto: ${Math.abs(orcamento.desconto)}%`) : '';

    const message = `*ORÇAMENTO MÃO DE OBRA PRO*
*Nº:* ${orcamento.id}
*Cliente:* ${cliente.nome}
*Data:* ${new Date(orcamento.data).toLocaleDateString('pt-BR')}
*Válido até:* ${dataVencimento}

*SERVIÇOS:*
${orcamento.itens.map(item => `✓ ${item.nome} x${item.quantidade || 1} - ${formatarMoeda(item.precoTotal || item.preco)}`).join('\n')}

*DESLOCAMENTO:* ${formatarMoeda(orcamento.taxaDeslocamento)}
${descontoInfo ? `*${descontoInfo}*` : ''}
*TOTAL: ${formatarMoeda(orcamento.total)}*

---
Orçamento válido até ${dataVencimento}.
Entre em contato para mais informações.`;

    const whatsappNumber = cliente.whatsapp.replace(/\D/g, '');
    const url = `https://wa.me/55${whatsappNumber}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
    setEnviando(false);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-3 text-slate-500">Carregando orçamento...</p>
        </div>
      </div>
    );
  }

  if (!orcamento || !cliente) {
    return (
      <div className="text-center py-12">
        <p className="text-slate-500">Orçamento não encontrado</p>
        <button onClick={onBack} className="mt-4 text-blue-600 font-semibold">Voltar ao início</button>
      </div>
    );
  }

  const dataVencimento = orcamento.dataVencimento
    ? new Date(orcamento.dataVencimento).toLocaleDateString('pt-BR')
    : 'Não informada';

  const isVencido = orcamento.dataVencimento && new Date(orcamento.dataVencimento) < new Date() && orcamento.status !== 'concluido';

  const podeConcluir = orcamento.status === 'aprovado';

  return (
    <div className="space-y-4 animate-fade-in pb-32">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button onClick={onBack} className="p-2 hover:bg-slate-100 rounded-lg">
            <ArrowLeft size={24} />
          </button>
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Orçamento #{orcamento.id}</h1>
            <p className="text-sm text-slate-500">
              {new Date(orcamento.data).toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              })}
            </p>
          </div>
        </div>

        <div className="flex gap-2">
          <button
            onClick={sendWhatsApp}
            disabled={enviando}
            className="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2"
          >
            <Send size={18} />
            {enviando ? 'Enviando...' : 'WhatsApp'}
          </button>
          {podeConcluir && (
            <button
              onClick={() => setShowConcluirModal(true)}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2"
            >
              <CheckSquare size={18} />
              Concluir
            </button>
          )}
          <button
            onClick={() => setShowDeleteModal(true)}
            className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
          >
            <Trash2 size={20} />
          </button>
        </div>
      </div>

      {/* status badge */}
      <div
        className={`rounded-xl p-4 text-center cursor-pointer ${
          orcamento.status === 'concluido' ? 'bg-green-100 border border-green-300' :
          orcamento.status === 'aprovado' ? 'bg-green-50 border border-green-200' :
          orcamento.status === 'pendente' ? 'bg-yellow-50 border border-yellow-200' :
          'bg-red-50 border border-red-200'
        }`}
        onClick={() => setShowStatusModal(true)}
      >
        <div className="flex items-center justify-center gap-2">
          {orcamento.status === 'concluido' && <CheckCircle className="text-green-700" size={20} />}
          {orcamento.status === 'aprovado' && <CheckCircle className="text-green-600" size={20} />}
          {orcamento.status === 'pendente' && <Clock className="text-yellow-600" size={20} />}
          {orcamento.status === 'recusado' && <XCircle className="text-red-600" size={20} />}
          <span className="font-semibold">
            {orcamento.status === 'concluido' ? 'Orçamento Concluído' :
             orcamento.status === 'aprovado' ? 'Orçamento Aprovado' :
             orcamento.status === 'pendente' ? 'Aguardando Aprovação' : 'Orçamento Recusado'}
          </span>
        </div>
      </div>

      {/* restante do conteúdo (cliente, serviços, resumo, fotos) igual ao já existente */}
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <h3 className="font-semibold text-slate-900 mb-2">Cliente</h3>
        <p className="text-slate-800">{cliente.nome}</p>
        {cliente.whatsapp && <p className="text-sm text-slate-500 mt-1">WhatsApp: {cliente.whatsapp}</p>}
        {cliente.endereco && <p className="text-sm text-slate-500 mt-1">{cliente.endereco}</p>}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-4 bg-slate-50 border-b border-slate-200">
          <h3 className="font-semibold text-slate-900">Serviços</h3>
        </div>
        <div className="p-4 space-y-2">
          {orcamento.itens.map((item, idx) => (
            <div key={idx} className="flex justify-between items-center py-1">
              <p className="text-slate-700">{item.nome} x{item.quantidade || 1}</p>
              <p className="font-semibold text-blue-600">{formatarMoeda(item.precoTotal || item.preco)}</p>
            </div>
          ))}
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-4 bg-slate-50 border-b border-slate-200">
          <h3 className="font-semibold text-slate-900">Resumo Financeiro</h3>
        </div>
        <div className="p-4 space-y-2">
          <div className="flex justify-between text-sm">
            <span>Subtotal</span>
            <span>{formatarMoeda(orcamento.subtotal)}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span>Deslocamento</span>
            <span>{formatarMoeda(orcamento.taxaDeslocamento)}</span>
          </div>
          {orcamento.desconto && orcamento.desconto !== 0 && (
            <div className="flex justify-between text-sm text-red-600">
              <span>Desconto</span>
              <span>- {orcamento.desconto > 0 ? formatarMoeda(orcamento.desconto) : `${Math.abs(orcamento.desconto)}%`}</span>
            </div>
          )}
          <div className="border-t border-slate-200 pt-2 mt-2">
            <div className="flex justify-between text-lg font-bold">
              <span>Total</span>
              <span className="text-blue-600">{formatarMoeda(orcamento.total)}</span>
            </div>
          </div>
        </div>
      </div>

      <div className={`bg-white rounded-xl shadow-sm border overflow-hidden ${isVencido ? 'border-red-300 bg-red-50' : 'border-slate-200'}`}>
        <div className="p-4 bg-slate-50 border-b border-slate-200">
          <h3 className="font-semibold text-slate-900 flex items-center gap-2">
            <Calendar size={18} /> Validade
          </h3>
        </div>
        <div className="p-4">
          <div className="flex justify-between">
            <span className="text-slate-600">Válido até:</span>
            <span className={`font-semibold ${isVencido ? 'text-red-600' : 'text-green-600'}`}>
              {dataVencimento} {isVencido && '(Vencido)'}
            </span>
          </div>
          {orcamento.validade && (
            <div className="flex justify-between mt-2">
              <span className="text-slate-600">Validade original:</span>
              <span className="text-slate-700">{orcamento.validade} dias</span>
            </div>
          )}
        </div>
      </div>

      {orcamento.fotos && orcamento.fotos.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
          <div className="p-4 bg-slate-50 border-b border-slate-200">
            <h3 className="font-semibold flex items-center gap-2">
              <CameraIcon size={18} /> Fotos ({orcamento.fotos.length})
            </h3>
          </div>
          <div className="p-4 grid grid-cols-2 gap-2">
            {orcamento.fotos.map((foto, idx) => (
              <img key={idx} src={foto} className="w-full h-32 object-cover rounded-lg cursor-pointer" onClick={() => window.open(foto, '_blank')} />
            ))}
          </div>
        </div>
      )}

      <div className="fixed bottom-0 left-0 right-0 lg:left-64 p-4 bg-white border-t shadow-lg">
        <button onClick={sendWhatsApp} disabled={enviando} className="w-full bg-green-600 text-white py-3 rounded-lg font-semibold flex justify-center gap-2">
          <Send size={20} /> {enviando ? 'Enviando...' : 'Enviar via WhatsApp'}
        </button>
      </div>

      {/* Modais */}
      {showStatusModal && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Alterar Status</h3>
            <div className="space-y-3">
              <button onClick={() => updateStatus('pendente')} className="w-full p-3 border-2 rounded-lg flex items-center justify-center gap-2 border-yellow-500 bg-yellow-50">
                <Clock size={20} className="text-yellow-600" /> Pendente
              </button>
              <button onClick={() => updateStatus('aprovado')} className="w-full p-3 border-2 rounded-lg flex items-center justify-center gap-2 border-green-500 bg-green-50">
                <CheckCircle size={20} className="text-green-600" /> Aprovado
              </button>
              <button onClick={() => updateStatus('recusado')} className="w-full p-3 border-2 rounded-lg flex items-center justify-center gap-2 border-red-500 bg-red-50">
                <XCircle size={20} className="text-red-600" /> Recusado
              </button>
            </div>
            <button onClick={() => setShowStatusModal(false)} className="w-full mt-4 border py-2 rounded-lg">Cancelar</button>
          </div>
        </div>
      )}

      <ConfirmModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={deleteOrcamento}
        title="Excluir Orçamento"
        message={`Tem certeza que deseja excluir o orçamento #${orcamento.id}?`}
        confirmText="Excluir"
        cancelText="Cancelar"
      />

      <ConfirmModal
        isOpen={showConcluirModal}
        onClose={() => setShowConcluirModal(false)}
        onConfirm={concluirOrcamento}
        title="Concluir Orçamento"
        message={`Deseja marcar o orçamento #${orcamento.id} como concluído? Isso lançará R$ ${orcamento.total.toFixed(2)} no caixa.`}
        confirmText="Concluir"
        cancelText="Cancelar"
      />
    </div>
  );
};

export default VisualizarOrcamento;