import React, { useState, useEffect } from 'react';
import {
  UserPlus,
  Wrench,
  Camera,
  TrendingUp,
  Send,
  Trash2,
  Plus,
  Minus,
  ChevronRight,
  DollarSign,
  Calendar
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

const NovoOrcamento = ({ onSave, showToast }) => {
  const { config, profissao } = useFinanceiro();
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
  const [validade, setValidade] = useState(30);
  const [enviando, setEnviando] = useState(false);

  const opcoesValidade = [
    { dias: 1, label: '1 dia' },
    { dias: 5, label: '5 dias' },
    { dias: 15, label: '15 dias' },
    { dias: 30, label: '30 dias' }
  ];

  useEffect(() => {
    loadData();
    const loadValidadePadrao = async () => {
      const configValidade = await db.config.where('chave').equals('validadePadrao').first();
      if (configValidade) setValidade(configValidade.valor);
    };
    loadValidadePadrao();
  }, [profissao]);

  const loadData = async () => {
    const allClientes = await db.clientes.toArray();
    setClientes(allClientes);

    if (profissao) {
      const servicosDaProfissao = await db.servicos
        .where('profissaoId')
        .equals(profissao.id)
        .toArray();
      setServicos(servicosDaProfissao);
    }
  };

  const handleAddCliente = async () => {
    if (!newCliente.nome.trim()) {
      showToast('Nome do cliente é obrigatório', 'error');
      return;
    }
    const id = await db.clientes.add(newCliente);
    const added = { ...newCliente, id };
    setClientes([...clientes, added]);
    setSelectedCliente(added);
    setShowClientModal(false);
    setNewCliente({ nome: '', whatsapp: '', endereco: '' });
    showToast('Cliente adicionado com sucesso!', 'success');
  };

  const handleAddServicoToBudget = (servico, quantidade = 1) => {
    let precoUnitario;
    let usaPrecoFixo = false;

    if (servico.precoFixo && servico.precoFixo > 0) {
      precoUnitario = servico.precoFixo;
      usaPrecoFixo = true;
    } else {
      precoUnitario = calcularPrecoServico(
        servico.tempoPadrao,
        config.valorMinuto,
        DIFICULDADE.NORMAL.fator,
        config.margemReserva
      );
    }

    setSelectedServicos([...selectedServicos, {
      id: Date.now(),
      servicoOriginalId: servico.id,
      nome: servico.nome,
      tempoAjustado: servico.tempoPadrao,
      dificuldade: 'NORMAL',
      precoUnitario: precoUnitario,
      quantidade: quantidade,
      precoTotal: precoUnitario * quantidade,
      precoFixo: servico.precoFixo || null,
      usaPrecoFixo: usaPrecoFixo
    }]);
    setShowServicoModal(false);
  };

  const aumentarQuantidade = (index) => {
    const updated = [...selectedServicos];
    updated[index].quantidade += 1;
    updated[index].precoTotal = updated[index].precoUnitario * updated[index].quantidade;
    setSelectedServicos(updated);
  };

  const diminuirQuantidade = (index) => {
    const updated = [...selectedServicos];
    if (updated[index].quantidade > 1) {
      updated[index].quantidade -= 1;
      updated[index].precoTotal = updated[index].precoUnitario * updated[index].quantidade;
      setSelectedServicos(updated);
    }
  };

  const updateServicoItem = (index, field, value) => {
    const updated = [...selectedServicos];
    if (field === 'tempoAjustado') {
      updated[index].tempoAjustado = parseInt(value);
      if (!updated[index].usaPrecoFixo) {
        updated[index].precoUnitario = calcularPrecoServico(
          updated[index].tempoAjustado,
          config.valorMinuto,
          DIFICULDADE[updated[index].dificuldade].fator,
          config.margemReserva
        );
        updated[index].precoTotal = updated[index].precoUnitario * updated[index].quantidade;
      }
    } else if (field === 'dificuldade') {
      updated[index].dificuldade = value;
      if (!updated[index].usaPrecoFixo) {
        updated[index].precoUnitario = calcularPrecoServico(
          updated[index].tempoAjustado,
          config.valorMinuto,
          DIFICULDADE[value].fator,
          config.margemReserva
        );
        updated[index].precoTotal = updated[index].precoUnitario * updated[index].quantidade;
      }
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

  const totalOrcamento = calcularTotalOrcamento(
    selectedServicos.map(s => ({ preco: s.precoTotal })),
    config.taxaDeslocamento
  );

  const calcularDataVencimento = () => {
    const data = new Date();
    data.setDate(data.getDate() + validade);
    return data;
  };

  const sendWhatsApp = async (orcamentoSalvo) => {
    if (!selectedCliente?.whatsapp) {
      showToast('Cliente não possui WhatsApp cadastrado', 'warning');
      return false;
    }

    const dataVencimento = new Date(orcamentoSalvo.dataVencimento).toLocaleDateString('pt-BR');

    const message = `*ORÇAMENTO MÃO DE OBRA PRO*
*Nº:* ${orcamentoSalvo.id}
*Cliente:* ${selectedCliente.nome}
*Data:* ${new Date(orcamentoSalvo.data).toLocaleDateString('pt-BR')}
*Válido até:* ${dataVencimento}

*SERVIÇOS:*
${selectedServicos.map(item => `✓ ${item.nome} x${item.quantidade} - ${formatarMoeda(item.precoTotal)}`).join('\n')}

*TOTAL: ${formatarMoeda(orcamentoSalvo.total)}*

---
Orçamento válido até ${dataVencimento}.
Entre em contato para mais informações.`;

    const whatsappNumber = selectedCliente.whatsapp.replace(/\D/g, '');
    const url = `https://wa.me/55${whatsappNumber}?text=${encodeURIComponent(message)}`;

    window.open(url, '_blank');
    return true;
  };

  const handleSaveBudget = async () => {
    if (!selectedCliente) {
      showToast('Selecione um cliente', 'warning');
      return;
    }
    if (selectedServicos.length === 0) {
      showToast('Adicione pelo menos um serviço', 'warning');
      return;
    }

    setEnviando(true);

    const dataVencimento = calcularDataVencimento();

    const budget = {
      clienteId: selectedCliente.id,
      data: new Date().toISOString(),
      total: totalOrcamento.total,
      status: 'pendente',
      itens: selectedServicos.map(s => ({
        nome: s.nome,
        tempo: s.tempoAjustado,
        dificuldade: s.dificuldade,
        precoUnitario: s.precoUnitario,
        quantidade: s.quantidade,
        precoTotal: s.precoTotal,
        usaPrecoFixo: s.usaPrecoFixo || false
      })),
      fotos: fotos,
      taxaDeslocamento: config.taxaDeslocamento,
      subtotal: totalOrcamento.subtotal,
      profissaoId: profissao?.id,
      profissaoNome: profissao?.nome,
      validade: validade,
      dataVencimento: dataVencimento.toISOString()
    };

    const id = await db.orcamentos.add(budget);
    const budgetSalvo = { ...budget, id };

    await sendWhatsApp(budgetSalvo);

    showToast('Orçamento salvo e enviado com sucesso!', 'success');
    setEnviando(false);
    if (onSave) onSave();
  };

  const stepConfig = [
    { number: 1, title: 'Cliente', icon: UserPlus },
    { number: 2, title: 'Serviços', icon: Wrench },
    { number: 3, title: 'Fotos', icon: Camera },
    { number: 4, title: 'Resumo', icon: TrendingUp }
  ];

  if (!profissao) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-3 text-slate-500">Carregando configurações da profissão...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in pb-32">
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Novo Orçamento</h1>
        <p className="text-slate-500 mt-1">
          Profissão: <span className="font-semibold text-blue-600">{profissao.nome}</span>
        </p>
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
              {clientes.length === 0 ? (
                <div className="text-center py-8 text-slate-500">
                  <p>Nenhum cliente cadastrado</p>
                  <button
                    onClick={() => setShowClientModal(true)}
                    className="mt-2 text-blue-600 text-sm"
                  >
                    Criar primeiro cliente
                  </button>
                </div>
              ) : (
                clientes.map(cliente => (
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
                ))
              )}
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
              <h3 className="font-semibold text-slate-900">
                Serviços de {profissao.nome}
              </h3>
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
                <p className="text-xs mt-1">Clique em "Adicionar" para incluir serviços</p>
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

                    <div className="flex items-center justify-between mb-3">
                      <span className="text-sm text-slate-500">Quantidade:</span>
                      <div className="flex items-center gap-3">
                        <button
                          onClick={() => diminuirQuantidade(idx)}
                          className="p-1 bg-slate-100 rounded-full hover:bg-slate-200 transition-colors"
                          disabled={servico.quantidade <= 1}
                        >
                          <Minus size={16} className={servico.quantidade <= 1 ? 'text-slate-300' : 'text-slate-600'} />
                        </button>
                        <span className="font-semibold text-slate-900 w-8 text-center">{servico.quantidade}</span>
                        <button
                          onClick={() => aumentarQuantidade(idx)}
                          className="p-1 bg-slate-100 rounded-full hover:bg-slate-200 transition-colors"
                        >
                          <Plus size={16} className="text-slate-600" />
                        </button>
                      </div>
                    </div>

                    {servico.usaPrecoFixo ? (
                      <div className="flex items-center gap-2 text-sm text-green-600 mb-2">
                        <DollarSign size={16} />
                        <span>Preço unitário: {formatarMoeda(servico.precoUnitario)}</span>
                      </div>
                    ) : (
                      <div className="grid grid-cols-2 gap-2 text-sm mb-2">
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
                    )}

                    <div className="mt-2 pt-2 border-t border-slate-100">
                      <div className="flex justify-between text-sm text-slate-500 mb-1">
                        <span>Preço unitário:</span>
                        <span>{formatarMoeda(servico.precoUnitario)}</span>
                      </div>
                      <p className="text-right font-semibold text-blue-600">
                        Total: {formatarMoeda(servico.precoTotal)}
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

              <div className="flex justify-between text-sm">
                <span className="text-slate-600">Profissão:</span>
                <span className="font-medium text-slate-900">{profissao?.nome}</span>
              </div>

              <div className="border-t border-slate-200 pt-3">
                <p className="text-sm font-medium text-slate-700 mb-2">Serviços:</p>
                {selectedServicos.map((servico, idx) => (
                  <div key={idx} className="flex justify-between text-sm py-1">
                    <div>
                      <span>{servico.nome} x{servico.quantidade}</span>
                      {servico.usaPrecoFixo ? (
                        <span className="text-xs text-green-600 ml-2">(Preço fixo)</span>
                      ) : (
                        <span className="text-xs text-slate-500 ml-2">({formatarTempo(servico.tempoAjustado)})</span>
                      )}
                    </div>
                    <span className="font-medium">{formatarMoeda(servico.precoTotal)}</span>
                  </div>
                ))}
              </div>

              <div className="border-t border-slate-200 pt-3">
                <label className="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
                  <Calendar size={16} />
                  Validade do Orçamento
                </label>
                <div className="flex gap-2 flex-wrap">
                  {opcoesValidade.map(op => (
                    <button
                      key={op.dias}
                      onClick={() => setValidade(op.dias)}
                      className={`flex-1 py-2 px-3 rounded-lg border transition-all ${
                        validade === op.dias
                          ? 'bg-blue-600 text-white border-blue-600'
                          : 'border-slate-300 text-slate-700 hover:border-blue-300'
                      }`}
                    >
                      {op.label}
                    </button>
                  ))}
                </div>
                <p className="text-xs text-slate-500 mt-2">
                  Vence em: {calcularDataVencimento().toLocaleDateString('pt-BR')}
                </p>
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
              disabled={enviando}
              className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
            >
              <Send size={20} />
              {enviando ? 'Enviando...' : 'Salvar e Enviar WhatsApp'}
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
            <h3 className="text-xl font-bold mb-4">
              Serviços de {profissao?.nome}
            </h3>
            <div className="space-y-2">
              {servicos.length === 0 ? (
                <div className="text-center py-8 text-slate-500">
                  <p>Nenhum serviço cadastrado para {profissao?.nome}</p>
                  <p className="text-xs mt-2">Acesse o Catálogo para adicionar serviços</p>
                </div>
              ) : (
                servicos.map(servico => (
                  <button
                    key={servico.id}
                    onClick={() => handleAddServicoToBudget(servico, 1)}
                    className="w-full text-left p-3 border rounded-lg hover:border-blue-500 transition-colors"
                  >
                    <p className="font-medium">{servico.nome}</p>
                    <div className="flex justify-between items-center mt-1">
                      <p className="text-sm text-slate-500">{formatarTempo(servico.tempoPadrao)}</p>
                      {servico.precoFixo && (
                        <p className="text-sm text-green-600 font-semibold">{formatarMoeda(servico.precoFixo)}</p>
                      )}
                    </div>
                  </button>
                ))
              )}
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