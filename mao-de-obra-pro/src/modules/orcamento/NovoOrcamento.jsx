import React, { useState, useEffect } from 'react';
import {
  UserPlus,
  Wrench,
  Camera,
  TrendingUp,
  Send,
  Trash2,
  Plus,
  ChevronRight
} from 'lucide-react';
import db from '../../database/db';
import CameraModal from '../../components/CameraModal';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import {
  calcularPrecoServico,
  calcularTotalOrcamento,
  formatarMoeda,
  formatarTempo,
  DIFICULDADE
} from '../../core/calculadora';

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
  const [newServico, setNewServico] = useState({ nome: '', tempoPadrao: '', categoria: '' });

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

  const stepConfig = [
    { number: 1, title: 'Cliente', icon: UserPlus },
    { number: 2, title: 'Serviços', icon: Wrench },
    { number: 3, title: 'Fotos', icon: Camera },
    { number: 4, title: 'Resumo', icon: TrendingUp }
  ];

  return (
    <div className="space-y-6 animate-fade-in pb-24">
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Novo Orçamento</h1>
        <p className="text-slate-500 mt-1">Crie um orçamento profissional</p>
      </div>

      <div className="flex justify-between items-center">
        {stepConfig.map((s, idx) => {
          const Icon = s.icon;
          const isActive = step === s.number;
          const isCompleted = step > s.number;
          return (
            <React.Fragment key={s.number}>
              <button
                onClick={() => setStep(s.number)}
                className={`flex flex-col items-center gap-1 flex-1 ${isActive ? 'text-blue-600' : isCompleted ? 'text-green-600' : 'text-slate-400'}`}
              >
                <div className={`
                  w-10 h-10 rounded-full flex items-center justify-center
                  ${isActive ? 'bg-blue-100' : isCompleted ? 'bg-green-100' : 'bg-slate-100'}
                `}>
                  <Icon size={20} />
                </div>
                <span className="text-xs hidden sm:inline">{s.title}</span>
              </button>
              {idx < stepConfig.length - 1 && (
                <ChevronRight size={16} className="text-slate-300" />
              )}
            </React.Fragment>
          );
        })}
      </div>

      {step === 1 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold text-slate-900">Selecionar Cliente</h3>
              <button
                onClick={() => setShowClientModal(true)}
                className="text-blue-600 text-sm font-semibold flex items-center gap-1"
              >
                <UserPlus size={16} />
                Novo Cliente
              </button>
            </div>

            <div className="space-y-2 max-h-96 overflow-y-auto">
              {clientes.map(cliente => (
                <button
                  key={cliente.id}
                  onClick={() => setSelectedCliente(cliente)}
                  className={`w-full text-left p-3 rounded-lg border transition-all ${
                    selectedCliente?.id === cliente.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-slate-200 hover:border-blue-200'
                  }`}
                >
                  <p className="font-medium text-slate-900">{cliente.nome}</p>
                  {cliente.whatsapp && (
                    <p className="text-sm text-slate-500">{cliente.whatsapp}</p>
                  )}
                </button>
              ))}
            </div>
          </div>

          <button
            onClick={() => setStep(2)}
            disabled={!selectedCliente}
            className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50"
          >
            Próximo
          </button>
        </div>
      )}

      {step === 2 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold text-slate-900">Serviços</h3>
              <button
                onClick={() => setShowServicoModal(true)}
                className="text-blue-600 text-sm font-semibold flex items-center gap-1"
              >
                <Plus size={16} />
                Adicionar
              </button>
            </div>

            {selectedServicos.length === 0 ? (
              <div className="text-center py-8 text-slate-500">
                <Wrench size={48} className="mx-auto mb-2 opacity-50" />
                <p>Nenhum serviço adicionado</p>
              </div>
            ) : (
              <div className="space-y-3 max-h-96 overflow-y-auto">
                {selectedServicos.map((servico, idx) => (
                  <div key={servico.id} className="border border-slate-200 rounded-lg p-3">
                    <div className="flex justify-between items-start mb-2">
                      <h4 className="font-medium text-slate-900">{servico.nome}</h4>
                      <button
                        onClick={() => removeServicoItem(idx)}
                        className="text-red-500 hover:bg-red-50 p-1 rounded"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <label className="text-xs text-slate-500">Tempo (min)</label>
                        <input
                          type="number"
                          value={servico.tempoAjustado}
                          onChange={(e) => updateServicoItem(idx, 'tempoAjustado', e.target.value)}
                          className="w-full px-2 py-1 border border-slate-300 rounded mt-1"
                          min="1"
                        />
                      </div>
                      <div>
                        <label className="text-xs text-slate-500">Dificuldade</label>
                        <select
                          value={servico.dificuldade}
                          onChange={(e) => updateServicoItem(idx, 'dificuldade', e.target.value)}
                          className="w-full px-2 py-1 border border-slate-300 rounded mt-1"
                        >
                          <option value="NORMAL">Normal (1.0x)</option>
                          <option value="MEDIO">Médio (1.3x)</option>
                          <option value="ALTO">Alto (1.6x)</option>
                        </select>
                      </div>
                    </div>

                    <div className="mt-2 pt-2 border-t border-slate-100">
                      <p className="text-right font-semibold text-blue-600">
                        {formatarMoeda(servico.preco)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="flex gap-3">
            <button
              onClick={() => setStep(1)}
              className="flex-1 border border-slate-300 text-slate-700 py-3 rounded-lg font-semibold"
            >
              Voltar
            </button>
            <button
              onClick={() => setStep(3)}
              disabled={selectedServicos.length === 0}
              className="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50"
            >
              Próximo
            </button>
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold text-slate-900">Fotos do Serviço</h3>
              <button
                onClick={() => setShowCamera(true)}
                className="text-blue-600 text-sm font-semibold flex items-center gap-1"
              >
                <Camera size={16} />
                Tirar Foto
              </button>
            </div>

            {fotos.length === 0 ? (
              <div className="text-center py-8 text-slate-500">
                <Camera size={48} className="mx-auto mb-2 opacity-50" />
                <p>Nenhuma foto capturada</p>
                <p className="text-xs mt-1">Fotografe o antes e depois do serviço</p>
              </div>
            ) : (
              <div className="grid grid-cols-2 gap-2">
                {fotos.map((foto, idx) => (
                  <div key={idx} className="relative">
                    <img src={foto} alt={`Foto ${idx + 1}`} className="w-full h-32 object-cover rounded-lg" />
                    <button
                      onClick={() => removePhoto(idx)}
                      className="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="flex gap-3">
            <button
              onClick={() => setStep(2)}
              className="flex-1 border border-slate-300 text-slate-700 py-3 rounded-lg font-semibold"
            >
              Voltar
            </button>
            <button
              onClick={() => setStep(4)}
              className="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold"
            >
              Próximo
            </button>
          </div>
        </div>
      )}

      {step === 4 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-4 bg-slate-50 border-b border-slate-200">
              <h3 className="font-semibold text-slate-900">Resumo do Orçamento</h3>
            </div>

            <div className="p-4 space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-slate-600">Cliente:</span>
                <span className="font-medium text-slate-900">{selectedCliente?.nome}</span>
              </div>

              <div className="border-t border-slate-200 pt-3">
                <p className="text-sm font-medium text-slate-700 mb-2">Serviços:</p>
                {selectedServicos.map((servico, idx) => (
                  <div key={idx} className="flex justify-between text-sm py-1">
                    <span>{servico.nome} ({formatarTempo(servico.tempoAjustado)}) - {servico.dificuldade}</span>
                    <span className="font-medium">{formatarMoeda(servico.preco)}</span>
                  </div>
                ))}
              </div>

              <div className="border-t border-slate-200 pt-3 space-y-1">
                <div className="flex justify-between text-sm">
                  <span>Subtotal</span>
                  <span>{formatarMoeda(totalOrcamento.subtotal)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span>Deslocamento</span>
                  <span>{formatarMoeda(totalOrcamento.taxaDeslocamento)}</span>
                </div>
                <div className="flex justify-between text-lg font-bold pt-2 border-t border-slate-200">
                  <span>Total</span>
                  <span className="text-blue-600">{formatarMoeda(totalOrcamento.total)}</span>
                </div>
              </div>

              {fotos.length > 0 && (
                <div className="border-t border-slate-200 pt-3">
                  <p className="text-sm text-slate-600">{fotos.length} foto(s) anexada(s)</p>
                </div>
              )}
            </div>
          </div>

          <div className="flex gap-3">
            <button
              onClick={() => setStep(3)}
              className="flex-1 border border-slate-300 text-slate-700 py-3 rounded-lg font-semibold"
            >
              Voltar
            </button>
            <button
              onClick={handleSaveBudget}
              className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
            >
              <Send size={20} />
              Salvar Orçamento
            </button>
          </div>
        </div>
      )}

      {showClientModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Novo Cliente</h3>
            <div className="space-y-3">
              <input
                type="text"
                placeholder="Nome *"
                value={newCliente.nome}
                onChange={(e) => setNewCliente({...newCliente, nome: e.target.value})}
                className="w-full px-4 py-2 border rounded-lg"
              />
              <input
                type="tel"
                placeholder="WhatsApp"
                value={newCliente.whatsapp}
                onChange={(e) => setNewCliente({...newCliente, whatsapp: e.target.value})}
                className="w-full px-4 py-2 border rounded-lg"
              />
              <textarea
                placeholder="Endereço"
                value={newCliente.endereco}
                onChange={(e) => setNewCliente({...newCliente, endereco: e.target.value})}
                className="w-full px-4 py-2 border rounded-lg"
                rows="2"
              />
            </div>
            <div className="flex gap-3 mt-6">
              <button onClick={() => setShowClientModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button>
              <button onClick={handleAddCliente} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button>
            </div>
          </div>
        </div>
      )}

      {showServicoModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6 max-h-96 overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">Adicionar Serviço</h3>
            <div className="space-y-2">
              {servicos.map(servico => (
                <button
                  key={servico.id}
                  onClick={() => handleAddServicoToBudget(servico)}
                  className="w-full text-left p-3 border rounded-lg hover:border-blue-500 transition-colors"
                >
                  <p className="font-medium">{servico.nome}</p>
                  <p className="text-sm text-slate-500">{formatarTempo(servico.tempoPadrao)}</p>
                </button>
              ))}
            </div>
            <button
              onClick={() => setShowServicoModal(false)}
              className="w-full mt-4 border py-2 rounded-lg"
            >
              Fechar
            </button>
          </div>
        </div>
      )}

      <CameraModal
        isOpen={showCamera}
        onClose={() => setShowCamera(false)}
        onCapture={handleCapturePhoto}
      />
    </div>
  );
};

export default NovoOrcamento;