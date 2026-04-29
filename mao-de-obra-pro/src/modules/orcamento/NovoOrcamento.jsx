import React, { useState, useEffect } from 'react';
import { UserPlus, Wrench, Camera, TrendingUp, Send, Trash2, Plus } from 'lucide-react';
import db from '../../database/db.js';
import CameraModal from '../../components/CameraModal.jsx';
import { useFinanceiro, calcularPrecoServico, calcularTotalOrcamento, formatarMoeda, DIFICULDADE } from '../../hooks/useFinanceiro.jsx';

const NovoOrcamento = ({ onSave }) => {
  const { config } = useFinanceiro();
  const [step, setStep] = useState(1);
  const [clientes, setClientes] = useState([]);
  const [servicos, setServicos] = useState([]);
  const [selectedCliente, setSelectedCliente] = useState(null);
  const [selectedServicos, setSelectedServicos] = useState([]);
  const [showCamera, setShowCamera] = useState(false);
  const [fotos, setFotos] = useState([]);
  const [showClientModal, setShowClientModal] = useState(false);
  const [newCliente, setNewCliente] = useState({ nome: '', whatsapp: '', endereco: '' });
  const [showServicoModal, setShowServicoModal] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    const allClientes = await db.clientes.toArray();
    const allServicos = await db.servicos.toArray();
    setClientes(allClientes);
    setServicos(allServicos);
  };

  const handleAddCliente = async () => {
    if (!newCliente.nome.trim()) {
      alert('Nome é obrigatório');
      return;
    }
    const id = await db.clientes.add(newCliente);
    const added = { ...newCliente, id };
    setClientes([...clientes, added]);
    setSelectedCliente(added);
    setShowClientModal(false);
    setNewCliente({ nome: '', whatsapp: '', endereco: '' });
  };

  const handleAddServicoToBudget = (servico) => {
    setSelectedServicos([...selectedServicos, {
      id: Date.now(),
      nome: servico.nome,
      tempoAjustado: servico.tempoPadrao,
      dificuldade: 'NORMAL',
      preco: calcularPrecoServico(
        servico.tempoPadrao,
        config.valorMinuto,
        DIFICULDADE.NORMAL.fator,
        config.margemReserva
      )
    }]);
    setShowServicoModal(false);
  };

  const updateServicoItem = (index, field, value) => {
    const updated = [...selectedServicos];
    if (field === 'tempoAjustado') {
      updated[index].tempoAjustado = parseInt(value);
      updated[index].preco = calcularPrecoServico(
        updated[index].tempoAjustado,
        config.valorMinuto,
        DIFICULDADE[updated[index].dificuldade].fator,
        config.margemReserva
      );
    } else if (field === 'dificuldade') {
      updated[index].dificuldade = value;
      updated[index].preco = calcularPrecoServico(
        updated[index].tempoAjustado,
        config.valorMinuto,
        DIFICULDADE[value].fator,
        config.margemReserva
      );
    }
    setSelectedServicos(updated);
  };

  const removeServicoItem = (index) => {
    setSelectedServicos(selectedServicos.filter((_, i) => i !== index));
  };

  const handleCapturePhoto = (photo) => {
    setFotos([...fotos, photo]);
  };

  const removePhoto = (index) => {
    setFotos(fotos.filter((_, i) => i !== index));
  };

  const totalOrcamento = calcularTotalOrcamento(selectedServicos, config.taxaDeslocamento);

  const handleSaveBudget = async () => {
    if (!selectedCliente) {
      alert('Selecione um cliente');
      return;
    }
    if (selectedServicos.length === 0) {
      alert('Adicione pelo menos um serviço');
      return;
    }

    const budget = {
      clienteId: selectedCliente.id,
      data: new Date().toISOString(),
      total: totalOrcamento.total,
      status: 'pendente',
      itens: selectedServicos.map(s => ({
        nome: s.nome,
        tempo: s.tempoAjustado,
        dificuldade: s.dificuldade,
        preco: s.preco
      })),
      fotos: fotos,
      taxaDeslocamento: config.taxaDeslocamento,
      subtotal: totalOrcamento.subtotal
    };

    await db.orcamentos.add(budget);
    alert('Orçamento salvo com sucesso!');
    if (onSave) onSave();
  };

  return (
    <div className="space-y-4 animate-fade-in pb-20">
      <h1 className="text-2xl font-bold text-slate-900">Novo Orçamento</h1>

      {/* Step 1: Client */}
      {step === 1 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Cliente</h3>
              <button onClick={() => setShowClientModal(true)} className="text-blue-600 text-sm flex items-center gap-1">
                <UserPlus size={16} /> Novo
              </button>
            </div>
            {clientes.map(cliente => (
              <button
                key={cliente.id}
                onClick={() => setSelectedCliente(cliente)}
                className={`w-full text-left p-3 mb-2 rounded-lg border ${selectedCliente?.id === cliente.id ? 'border-blue-500 bg-blue-50' : 'border-slate-200'}`}
              >
                <p className="font-medium">{cliente.nome}</p>
              </button>
            ))}
          </div>
          <button onClick={() => setStep(2)} disabled={!selectedCliente} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50">
            Próximo
          </button>
        </div>
      )}

      {/* Step 2: Services */}
      {step === 2 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Serviços</h3>
              <button onClick={() => setShowServicoModal(true)} className="text-blue-600 text-sm flex items-center gap-1">
                <Plus size={16} /> Adicionar
              </button>
            </div>
            {selectedServicos.map((servico, idx) => (
              <div key={servico.id} className="border-b pb-3 mb-3">
                <div className="flex justify-between">
                  <p className="font-medium">{servico.nome}</p>
                  <button onClick={() => removeServicoItem(idx)} className="text-red-500"><Trash2 size={16} /></button>
                </div>
                <div className="grid grid-cols-2 gap-2 mt-2">
                  <input type="number" value={servico.tempoAjustado} onChange={(e) => updateServicoItem(idx, 'tempoAjustado', e.target.value)} className="px-2 py-1 border rounded text-sm" />
                  <select value={servico.dificuldade} onChange={(e) => updateServicoItem(idx, 'dificuldade', e.target.value)} className="px-2 py-1 border rounded text-sm">
                    <option value="NORMAL">Normal</option>
                    <option value="MEDIO">Médio</option>
                    <option value="ALTO">Alto</option>
                  </select>
                </div>
                <p className="text-right text-blue-600 font-semibold mt-1">{formatarMoeda(servico.preco)}</p>
              </div>
            ))}
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(1)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={() => setStep(3)} disabled={selectedServicos.length === 0} className="flex-1 bg-blue-600 text-white py-3 rounded-lg disabled:opacity-50">Próximo</button>
          </div>
        </div>
      )}

      {/* Step 3: Photos */}
      {step === 3 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Fotos</h3>
              <button onClick={() => setShowCamera(true)} className="text-blue-600 text-sm flex items-center gap-1">
                <Camera size={16} /> Tirar Foto
              </button>
            </div>
            <div className="grid grid-cols-2 gap-2">
              {fotos.map((foto, idx) => (
                <div key={idx} className="relative">
                  <img src={foto} className="w-full h-32 object-cover rounded" />
                  <button onClick={() => removePhoto(idx)} className="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full"><Trash2 size={12} /></button>
                </div>
              ))}
            </div>
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(2)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={() => setStep(4)} className="flex-1 bg-blue-600 text-white py-3 rounded-lg">Próximo</button>
          </div>
        </div>
      )}

      {/* Step 4: Summary */}
      {step === 4 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl p-4 shadow-sm border">
            <h3 className="font-semibold mb-3">Resumo</h3>
            <p><strong>Cliente:</strong> {selectedCliente?.nome}</p>
            <div className="border-t my-3 pt-3">
              {selectedServicos.map(s => (
                <div key={s.id} className="flex justify-between text-sm py-1">
                  <span>{s.nome}</span>
                  <span>{formatarMoeda(s.preco)}</span>
                </div>
              ))}
            </div>
            <div className="border-t pt-3">
              <div className="flex justify-between"><span>Subtotal</span><span>{formatarMoeda(totalOrcamento.subtotal)}</span></div>
              <div className="flex justify-between"><span>Deslocamento</span><span>{formatarMoeda(totalOrcamento.taxaDeslocamento)}</span></div>
              <div className="flex justify-between font-bold text-lg mt-2 pt-2 border-t"><span>Total</span><span className="text-blue-600">{formatarMoeda(totalOrcamento.total)}</span></div>
            </div>
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(3)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={handleSaveBudget} className="flex-1 bg-green-600 text-white py-3 rounded-lg flex items-center justify-center gap-2">
              <Send size={20} /> Salvar
            </button>
          </div>
        </div>
      )}

      {/* Modals */}
      {showClientModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Novo Cliente</h3>
            <input type="text" placeholder="Nome" value={newCliente.nome} onChange={(e) => setNewCliente({...newCliente, nome: e.target.value})} className="w-full px-4 py-2 border rounded-lg mb-3" />
            <input type="tel" placeholder="WhatsApp" value={newCliente.whatsapp} onChange={(e) => setNewCliente({...newCliente, whatsapp: e.target.value})} className="w-full px-4 py-2 border rounded-lg mb-3" />
            <textarea placeholder="Endereço" value={newCliente.endereco} onChange={(e) => setNewCliente({...newCliente, endereco: e.target.value})} className="w-full px-4 py-2 border rounded-lg mb-4" rows="2" />
            <div className="flex gap-3">
              <button onClick={() => setShowClientModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button>
              <button onClick={handleAddCliente} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button>
            </div>
          </div>
        </div>
      )}

      {showServicoModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6 max-h-96 overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">Serviços</h3>
            {servicos.map(servico => (
              <button key={servico.id} onClick={() => handleAddServicoToBudget(servico)} className="w-full text-left p-3 border rounded-lg mb-2">
                <p className="font-medium">{servico.nome}</p>
              </button>
            ))}
            <button onClick={() => setShowServicoModal(false)} className="w-full mt-3 border py-2 rounded-lg">Fechar</button>
          </div>
        </div>
      )}

      <CameraModal isOpen={showCamera} onClose={() => setShowCamera(false)} onCapture={handleCapturePhoto} />
    </div>
  );
};

export default NovoOrcamento;