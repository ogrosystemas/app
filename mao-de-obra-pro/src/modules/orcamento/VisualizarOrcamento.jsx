import React, { useState, useEffect } from 'react';
import { ArrowLeft, Send, Camera as CameraIcon, CheckCircle, XCircle, Clock, Calendar, Trash2 } from 'lucide-react';
import db from '../../database/db';
import { formatarMoeda } from '../../core/calculadora';
import ConfirmModal from '../../components/ConfirmModal';
import { useToast } from '../../context/ToastContext';

const VisualizarOrcamento = ({ onBack, id }) => {
  const { showToast } = useToast();
  const [orcamento, setOrcamento] = useState(null);
  const [cliente, setCliente] = useState(null);
  const [loading, setLoading] = useState(true);
  const [enviando, setEnviando] = useState(false);
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);

  useEffect(() => { if (id) loadOrcamento(id); else loadLast(); }, [id]);

  const loadOrcamento = async (orcId) => {
    try {
      const budget = await db.orcamentos.get(parseInt(orcId));
      if (budget) { setOrcamento(budget); setCliente(await db.clientes.get(budget.clienteId)); }
    } catch (err) { showToast('Erro ao carregar', 'error'); } finally { setLoading(false); }
  };
  const loadLast = async () => {
    try {
      const budgets = await db.orcamentos.orderBy('data').reverse().limit(1).toArray();
      if (budgets.length) { setOrcamento(budgets[0]); setCliente(await db.clientes.get(budgets[0].clienteId)); }
    } catch (err) { } finally { setLoading(false); }
  };

  const updateStatus = async (newStatus) => {
    if (!orcamento) return;
    // Se for "concluido", também lança no caixa
    if (newStatus === 'concluido') {
      if (orcamento.status !== 'aprovado') {
        showToast('Apenas orçamentos aprovados podem ser concluídos', 'warning');
        setShowStatusModal(false);
        return;
      }
      await db.caixa.add({
        data: new Date().toISOString(),
        tipo: 'entrada',
        categoria: 'Serviço',
        descricao: `Orçamento #${orcamento.id} - ${cliente?.nome}`,
        valor: orcamento.total,
        orcamentoId: orcamento.id
      });
      await db.orcamentos.update(orcamento.id, { status: 'concluido' });
      setOrcamento({ ...orcamento, status: 'concluido' });
      showToast(`Orçamento concluído! Valor de ${formatarMoeda(orcamento.total)} lançado no caixa.`, 'success');
    } else {
      await db.orcamentos.update(orcamento.id, { status: newStatus });
      setOrcamento({ ...orcamento, status: newStatus });
      const msg = newStatus === 'aprovado' ? 'Aprovado' : newStatus === 'pendente' ? 'Pendente' : 'Recusado';
      showToast(`Status alterado para ${msg}`, 'success');
    }
    setShowStatusModal(false);
  };

  const deleteOrcamento = async () => {
    const lanc = await db.caixa.where('orcamentoId').equals(orcamento.id).first();
    if (lanc && confirm('Este orçamento tem um lançamento no caixa. Deseja excluí-lo também?')) await db.caixa.delete(lanc.id);
    await db.orcamentos.delete(orcamento.id);
    showToast('Orçamento excluído', 'success');
    onBack();
  };

  const sendWhatsApp = () => {
    if (!cliente?.whatsapp) { showToast('Cliente sem WhatsApp', 'warning'); return; }
    setEnviando(true);
    const dataVenc = orcamento.dataVencimento ? new Date(orcamento.dataVencimento).toLocaleDateString() : 'Não informada';
    const msg = `*ORÇAMENTO MÃO DE OBRA PRO*%0A*Nº:* ${orcamento.id}%0A*Cliente:* ${cliente.nome}%0A*Data:* ${new Date(orcamento.data).toLocaleDateString()}%0A*Válido até:* ${dataVenc}%0A%0A*SERVIÇOS:*%0A${orcamento.itens.map(i=>`✓ ${i.nome} x${i.quantidade||1} - ${formatarMoeda(i.precoTotal||i.preco)}`).join('%0A')}%0A%0A*DESLOCAMENTO:* ${formatarMoeda(orcamento.taxaDeslocamento)}%0A${orcamento.desconto ? `*DESCONTO:* ${orcamento.desconto>0 ? formatarMoeda(orcamento.desconto) : Math.abs(orcamento.desconto)+'%'}` : ''}%0A*TOTAL:* ${formatarMoeda(orcamento.total)}`;
    window.location.href = `https://wa.me/55${cliente.whatsapp.replace(/\D/g,'')}?text=${msg}`;
    setEnviando(false);
  };

  if (loading) return <div className="flex justify-center p-8"><div className="animate-spin rounded-full h-12 w-12"/></div>;
  if (!orcamento || !cliente) return <div className="text-center p-8">Orçamento não encontrado<button onClick={onBack} className="ml-4 text-blue-600">Voltar</button></div>;

  const isVencido = orcamento.dataVencimento && new Date(orcamento.dataVencimento) < new Date() && orcamento.status !== 'concluido';
  const dataVenc = orcamento.dataVencimento ? new Date(orcamento.dataVencimento).toLocaleDateString() : 'Não informada';

  return (
    <div className="space-y-4 pb-32">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <button onClick={onBack} className="p-2 hover:bg-slate-100 rounded"><ArrowLeft size={24} /></button>
          <div><h1 className="text-2xl font-bold">Orçamento #{orcamento.id}</h1><p className="text-sm text-slate-500">{new Date(orcamento.data).toLocaleDateString()}</p></div>
        </div>
        <div className="flex gap-2">
          <button onClick={sendWhatsApp} disabled={enviando} className="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"><Send size={18} />{enviando ? 'Enviando...' : 'WhatsApp'}</button>
          <button onClick={() => setShowDeleteModal(true)} className="p-2 text-red-600 hover:bg-red-50 rounded"><Trash2 size={20} /></button>
        </div>
      </div>

      <div className={`rounded-xl p-4 text-center cursor-pointer ${orcamento.status === 'concluido' ? 'bg-green-100' : orcamento.status === 'aprovado' ? 'bg-green-50' : orcamento.status === 'pendente' ? 'bg-yellow-50' : 'bg-red-50'}`} onClick={() => setShowStatusModal(true)}>
        <div className="flex justify-center gap-2">
          {orcamento.status === 'concluido' && <CheckCircle className="text-green-700" />}
          {orcamento.status === 'aprovado' && <CheckCircle className="text-green-600" />}
          {orcamento.status === 'pendente' && <Clock className="text-yellow-600" />}
          {orcamento.status === 'recusado' && <XCircle className="text-red-600" />}
          <span className="font-semibold">
            {orcamento.status === 'concluido' ? 'Concluído' : orcamento.status === 'aprovado' ? 'Aprovado' : orcamento.status === 'pendente' ? 'Pendente' : 'Recusado'}
          </span>
        </div>
      </div>

      <div className="bg-white rounded-xl p-4 border"><h3 className="font-semibold">Cliente</h3><p>{cliente.nome}</p>{cliente.whatsapp && <p className="text-sm">WhatsApp: {cliente.whatsapp}</p>}{cliente.endereco && <p className="text-sm">{cliente.endereco}</p>}</div>
      <div className="bg-white rounded-xl border"><div className="p-4 bg-slate-50 border-b"><h3 className="font-semibold">Serviços</h3></div><div className="p-4 space-y-2">{orcamento.itens.map((item, i) => <div key={i} className="flex justify-between"><span>{item.nome} x{item.quantidade || 1}</span><span className="font-semibold">{formatarMoeda(item.precoTotal || item.preco)}</span></div>)}</div></div>
      <div className="bg-white rounded-xl border"><div className="p-4 bg-slate-50 border-b"><h3 className="font-semibold">Resumo Financeiro</h3></div><div className="p-4 space-y-2"><div className="flex justify-between"><span>Subtotal</span><span>{formatarMoeda(orcamento.subtotal)}</span></div><div className="flex justify-between"><span>Deslocamento</span><span>{formatarMoeda(orcamento.taxaDeslocamento)}</span></div>{orcamento.desconto !== 0 && <div className="flex justify-between text-red-600"><span>Desconto</span><span>- {orcamento.desconto > 0 ? formatarMoeda(orcamento.desconto) : Math.abs(orcamento.desconto) + '%'}</span></div>}<div className="border-t pt-2 mt-2 flex justify-between text-lg font-bold"><span>Total</span><span className="text-blue-600">{formatarMoeda(orcamento.total)}</span></div></div></div>
      <div className="bg-white rounded-xl border"><div className="p-4 bg-slate-50 border-b"><h3 className="font-semibold flex gap-2"><Calendar size={18} /> Validade</h3></div><div className="p-4"><div className="flex justify-between"><span>Válido até:</span><span className={isVencido ? 'text-red-600' : 'text-green-600'}>{dataVenc}{isVencido && ' (Vencido)'}</span></div></div></div>
      {orcamento.fotos?.length > 0 && <div className="bg-white rounded-xl border"><div className="p-4 bg-slate-50 border-b"><h3 className="font-semibold">Fotos ({orcamento.fotos.length})</h3></div><div className="p-4 grid grid-cols-2 gap-2">{orcamento.fotos.map((f, i) => <img key={i} src={f} className="w-full h-32 object-cover rounded cursor-pointer" onClick={() => window.open(f)} />)}</div></div>}
      <div className="fixed bottom-0 left-0 right-0 p-4 bg-white border-t"><button onClick={sendWhatsApp} className="w-full bg-green-600 text-white py-3 rounded-lg font-semibold flex justify-center gap-2"><Send size={20} /> Enviar via WhatsApp</button></div>

      {showStatusModal && (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Alterar Status</h3>
            <div className="space-y-3">
              <button onClick={() => updateStatus('pendente')} className="w-full p-3 border-2 border-yellow-500 bg-yellow-50 rounded flex items-center justify-center gap-2"><Clock size={20} /> Pendente</button>
              <button onClick={() => updateStatus('aprovado')} className="w-full p-3 border-2 border-green-500 bg-green-50 rounded flex items-center justify-center gap-2"><CheckCircle size={20} /> Aprovado</button>
              <button onClick={() => updateStatus('recusado')} className="w-full p-3 border-2 border-red-500 bg-red-50 rounded flex items-center justify-center gap-2"><XCircle size={20} /> Recusado</button>
              <button onClick={() => updateStatus('concluido')} className="w-full p-3 border-2 border-blue-500 bg-blue-50 rounded flex items-center justify-center gap-2"><CheckCircle size={20} /> Concluído</button>
            </div>
            <button onClick={() => setShowStatusModal(false)} className="w-full mt-4 border py-2 rounded">Cancelar</button>
          </div>
        </div>
      )}
      <ConfirmModal isOpen={showDeleteModal} onClose={() => setShowDeleteModal(false)} onConfirm={deleteOrcamento} title="Excluir" message="Tem certeza?" confirmText="Excluir" />
    </div>
  );
};
export default VisualizarOrcamento;