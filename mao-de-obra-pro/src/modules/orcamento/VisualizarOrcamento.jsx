import React, { useState, useEffect } from 'react';
import {
  ArrowLeft,
  Send,
  Camera as CameraIcon,
  CheckCircle,
  XCircle,
  Clock
} from 'lucide-react';
import db from '../../database/db';
import { formatarMoeda, formatarTempo } from '../../core/calculadora';

const VisualizarOrcamento = ({ onBack }) => {
  const [orcamento, setOrcamento] = useState(null);
  const [cliente, setCliente] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadLastOrcamento();
  }, []);

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
    }
  };

  const sendWhatsApp = () => {
    if (!cliente?.whatsapp) {
      alert('Cliente não possui WhatsApp cadastrado');
      return;
    }

    const message = `
*ORÇAMENTO MÃO DE OBRA PRO*
*Cliente:* ${cliente.nome}
*Data:* ${new Date(orcamento.data).toLocaleDateString('pt-BR')}
*Profissão:* ${orcamento.profissaoNome || 'Não informado'}

*SERVIÇOS:*
${orcamento.itens.map(item => `✓ ${item.nome} (${formatarTempo(item.tempo)}) - ${formatarMoeda(item.preco)}`).join('\n')}

*DESPESAS:*
Deslocamento: ${formatarMoeda(orcamento.taxaDeslocamento)}

*TOTAL: ${formatarMoeda(orcamento.total)}*

*Status: ${orcamento.status === 'pendente' ? 'Aguardando aprovação' : orcamento.status === 'aprovado' ? 'Aprovado' : 'Recusado'}*

Para aprovar ou solicitar alterações, entre em contato.
    `.trim();

    const whatsappNumber = cliente.whatsapp.replace(/\D/g, '');
    const url = `https://wa.me/55${whatsappNumber}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
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
        <p className="text-slate-500">Nenhum orçamento encontrado</p>
        <button
          onClick={onBack}
          className="mt-4 text-blue-600 font-semibold"
        >
          Voltar ao início
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-4 animate-fade-in pb-24">
      <div className="flex items-center gap-3">
        <button
          onClick={onBack}
          className="p-2 hover:bg-slate-100 rounded-lg transition-colors"
        >
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

      <div className={`
        rounded-xl p-4 text-center
        ${orcamento.status === 'aprovado' ? 'bg-green-50 border border-green-200' : ''}
        ${orcamento.status === 'pendente' ? 'bg-yellow-50 border border-yellow-200' : ''}
        ${orcamento.status === 'recusado' ? 'bg-red-50 border border-red-200' : ''}
      `}>
        <div className="flex items-center justify-center gap-2">
          {orcamento.status === 'aprovado' && <CheckCircle className="text-green-600" size={20} />}
          {orcamento.status === 'pendente' && <Clock className="text-yellow-600" size={20} />}
          {orcamento.status === 'recusado' && <XCircle className="text-red-600" size={20} />}
          <span className="font-semibold">
            {orcamento.status === 'aprovado' ? 'Orçamento Aprovado' :
             orcamento.status === 'pendente' ? 'Aguardando Aprovação' : 'Orçamento Recusado'}
          </span>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <h3 className="font-semibold text-slate-900 mb-2">Cliente</h3>
        <p className="text-slate-800">{cliente.nome}</p>
        {cliente.whatsapp && (
          <p className="text-sm text-slate-500 mt-1">WhatsApp: {cliente.whatsapp}</p>
        )}
        {cliente.endereco && (
          <p className="text-sm text-slate-500 mt-1">{cliente.endereco}</p>
        )}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-4 bg-slate-50 border-b border-slate-200">
          <h3 className="font-semibold text-slate-900">Serviços Realizados</h3>
        </div>
        <div className="p-4 space-y-3">
          {orcamento.itens.map((item, idx) => (
            <div key={idx} className="flex justify-between items-start">
              <div>
                <p className="font-medium text-slate-900">{item.nome}</p>
                <p className="text-xs text-slate-500">{formatarTempo(item.tempo)} • Dificuldade: {item.dificuldade}</p>
              </div>
              <p className="font-semibold text-blue-600">{formatarMoeda(item.preco)}</p>
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
            <span className="text-slate-600">Subtotal</span>
            <span>{formatarMoeda(orcamento.subtotal)}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-slate-600">Taxa de Deslocamento</span>
            <span>{formatarMoeda(orcamento.taxaDeslocamento)}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-slate-600">Profissão</span>
            <span className="font-medium">{orcamento.profissaoNome || 'Não informado'}</span>
          </div>
          <div className="border-t border-slate-200 pt-2 mt-2">
            <div className="flex justify-between text-lg font-bold">
              <span>Total</span>
              <span className="text-blue-600">{formatarMoeda(orcamento.total)}</span>
            </div>
          </div>
        </div>
      </div>

      {orcamento.fotos && orcamento.fotos.length > 0 && (
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <div className="p-4 bg-slate-50 border-b border-slate-200">
            <h3 className="font-semibold text-slate-900 flex items-center gap-2">
              <CameraIcon size={18} />
              Fotos do Serviço ({orcamento.fotos.length})
            </h3>
          </div>
          <div className="p-4">
            <div className="grid grid-cols-2 gap-2">
              {orcamento.fotos.map((foto, idx) => (
                <img
                  key={idx}
                  src={foto}
                  alt={`Serviço ${idx + 1}`}
                  className="w-full h-40 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                  onClick={() => window.open(foto, '_blank')}
                />
              ))}
            </div>
          </div>
        </div>
      )}

      <div className="fixed bottom-0 left-0 right-0 lg:left-64 p-4 bg-white border-t border-slate-200">
        <div className="max-w-7xl mx-auto flex gap-3">
          {orcamento.status === 'pendente' && (
            <>
              <button
                onClick={() => updateStatus('aprovado')}
                className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
              >
                <CheckCircle size={20} />
                Aprovar
              </button>
              <button
                onClick={() => updateStatus('recusado')}
                className="flex-1 bg-red-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
              >
                <XCircle size={20} />
                Recusar
              </button>
            </>
          )}
          <button
            onClick={sendWhatsApp}
            className="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
          >
            <Send size={20} />
            Enviar WhatsApp
          </button>
        </div>
      </div>
    </div>
  );
};

export default VisualizarOrcamento;