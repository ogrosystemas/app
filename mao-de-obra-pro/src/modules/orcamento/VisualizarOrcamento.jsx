import React, { useState, useEffect } from 'react';
import { ArrowLeft, Send, CheckCircle, XCircle, Clock } from 'lucide-react';
import db from '../../database/db.js';
import { formatarMoeda, formatarTempo } from '../../hooks/useFinanceiro.jsx';

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
      console.error('Error:', error);
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
      alert('Cliente não tem WhatsApp');
      return;
    }
    const message = `Orçamento: ${formatarMoeda(orcamento.total)}`;
    window.open(`https://wa.me/55${cliente.whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(message)}`, '_blank');
  };

  if (loading) {
    return <div className="text-center py-12">Carregando...</div>;
  }

  if (!orcamento || !cliente) {
    return (
      <div className="text-center py-12">
        <p>Nenhum orçamento encontrado</p>
        <button onClick={onBack} className="mt-4 text-blue-600">Voltar</button>
      </div>
    );
  }

  return (
    <div className="space-y-4 pb-20">
      <div className="flex items-center gap-3">
        <button onClick={onBack} className="p-2 hover:bg-slate-100 rounded"><ArrowLeft size={24} /></button>
        <h1 className="text-2xl font-bold">Orçamento #{orcamento.id}</h1>
      </div>

      <div className={`rounded-xl p-4 text-center ${orcamento.status === 'aprovado' ? 'bg-green-50' : orcamento.status === 'pendente' ? 'bg-yellow-50' : 'bg-red-50'}`}>
        {orcamento.status === 'aprovado' && <CheckCircle className="inline text-green-600 mr-2" />}
        {orcamento.status === 'pendente' && <Clock className="inline text-yellow-600 mr-2" />}
        {orcamento.status === 'recusado' && <XCircle className="inline text-red-600 mr-2" />}
        <span className="font-semibold">{orcamento.status === 'aprovado' ? 'Aprovado' : orcamento.status === 'pendente' ? 'Pendente' : 'Recusado'}</span>
      </div>

      <div className="bg-white rounded-xl p-4 shadow-sm border">
        <h3 className="font-semibold mb-2">Cliente</h3>
        <p>{cliente.nome}</p>
        {cliente.whatsapp && <p className="text-sm text-slate-500">{cliente.whatsapp}</p>}
      </div>

      <div className="bg-white rounded-xl p-4 shadow-sm border">
        <h3 className="font-semibold mb-3">Serviços</h3>
        {orcamento.itens.map((item, idx) => (
          <div key={idx} className="flex justify-between py-2 border-b">
            <div><p className="font-medium">{item.nome}</p><p className="text-xs text-slate-500">{formatarTempo(item.tempo)} • {item.dificuldade}</p></div>
            <p className="font-semibold text-blue-600">{formatarMoeda(item.preco)}</p>
          </div>
        ))}
        <div className="mt-3 pt-3 border-t">
          <div className="flex justify-between"><span>Subtotal</span><span>{formatarMoeda(orcamento.subtotal)}</span></div>
          <div className="flex justify-between"><span>Deslocamento</span><span>{formatarMoeda(orcamento.taxaDeslocamento)}</span></div>
          <div className="flex justify-between font-bold text-lg mt-2"><span>Total</span><span className="text-blue-600">{formatarMoeda(orcamento.total)}</span></div>
        </div>
      </div>

      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t">
        <div className="flex gap-3">
          {orcamento.status === 'pendente' && (
            <>
              <button onClick={() => updateStatus('aprovado')} className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold">Aprovar</button>
              <button onClick={() => updateStatus('recusado')} className="flex-1 bg-red-600 text-white py-3 rounded-lg font-semibold">Recusar</button>
            </>
          )}
          <button onClick={sendWhatsApp} className="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
            <Send size={20} /> WhatsApp
          </button>
        </div>
      </div>
    </div>
  );
};

export default VisualizarOrcamento;