import React, { useState, useEffect } from 'react';
import {
  UserPlus, Wrench, Camera, TrendingUp, Send, Trash2,
  Plus, Minus, ChevronRight, DollarSign, Calendar, Percent
} from 'lucide-react';
import db from '../../database/db';
import CameraModal from '../../components/CameraModal';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { calcularPrecoServico, formatarMoeda, formatarTempo, DIFICULDADE } from '../../core/calculadora';
import { useToast } from '../../context/ToastContext';

const NovoOrcamento = ({ onSave }) => {
  const { config, profissao } = useFinanceiro();
  const { showToast } = useToast();
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
  const [desconto, setDesconto] = useState(0);
  const [tipoDesconto, setTipoDesconto] = useState('valor');
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
      const servicosDaProfissao = await db.servicos.where('profissaoId').equals(profissao.id).toArray();
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
    showToast('Cliente adicionado!', 'success');
  };

  const handleAddServicoToBudget = (servico, quantidade = 1) => {
    let precoUnitario, usaPrecoFixo = false;
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
      precoUnitario,
      quantidade,
      precoTotal: precoUnitario * quantidade,
      precoFixo: servico.precoFixo || null,
      usaPrecoFixo
    }]);
    setShowServicoModal(false);
  };

  const aumentarQuantidade = (index) => {
    const updated = [...selectedServicos];
    updated[index].quantidade++;
    updated[index].precoTotal = updated[index].precoUnitario * updated[index].quantidade;
    setSelectedServicos(updated);
  };

  const diminuirQuantidade = (index) => {
    const updated = [...selectedServicos];
    if (updated[index].quantidade > 1) {
      updated[index].quantidade--;
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

  const handleCapturePhoto = (photo) => setFotos([...fotos, photo]);
  const removePhoto = (index) => setFotos(fotos.filter((_, i) => i !== index));

  const calcularSubtotal = () => selectedServicos.reduce((sum, s) => sum + s.precoTotal, 0);
  const calcularTotalComDesconto = () => {
    let total = calcularSubtotal() + config.taxaDeslocamento;
    if (tipoDesconto === 'valor') total -= desconto;
    else if (tipoDesconto === 'percentual') total *= (1 - desconto / 100);
    return Math.max(0, total);
  };

  const subtotal = calcularSubtotal();
  const totalOrcamento = calcularTotalComDesconto();
  const taxaDeslocamento = config.taxaDeslocamento;
  const calcularDataVencimento = () => {
    const d = new Date();
    d.setDate(d.getDate() + validade);
    return d;
  };

  const sendWhatsApp = async (orcamentoSalvo) => {
    if (!selectedCliente?.whatsapp) {
      showToast('Cliente não possui WhatsApp', 'warning');
      return false;
    }
    const msg = `*ORÇAMENTO MÃO DE OBRA PRO*%0A*Nº:* ${orcamentoSalvo.id}%0A*Cliente:* ${selectedCliente.nome}%0A*Data:* ${new Date(orcamentoSalvo.data).toLocaleDateString()}%0A*Válido até:* ${new Date(orcamentoSalvo.dataVencimento).toLocaleDateString()}%0A%0A*SERVIÇOS:*%0A${selectedServicos.map(s => `✓ ${s.nome} x${s.quantidade} - ${formatarMoeda(s.precoTotal)}`).join('%0A')}%0A%0A*DESLOCAMENTO:* ${formatarMoeda(taxaDeslocamento)}%0A*DESCONTO:* ${tipoDesconto === 'valor' ? formatarMoeda(desconto) : `${desconto}%`}%0A*TOTAL:* ${formatarMoeda(totalOrcamento)}`;
    window.location.href = `https://wa.me/55${selectedCliente.whatsapp.replace(/\D/g, '')}?text=${msg}`;
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
      total: totalOrcamento,
      desconto: tipoDesconto === 'valor' ? desconto : -desconto,
      status: 'pendente',
      itens: selectedServicos.map(s => ({
        nome: s.nome,
        tempo: s.tempoAjustado,
        dificuldade: s.dificuldade,
        precoUnitario: s.precoUnitario,
        quantidade: s.quantidade,
        precoTotal: s.precoTotal,
        usaPrecoFixo: s.usaPrecoFixo
      })),
      fotos,
      taxaDeslocamento,
      subtotal,
      profissaoId: profissao?.id,
      profissaoNome: profissao?.nome,
      validade,
      dataVencimento: dataVencimento.toISOString()
    };
    const id = await db.orcamentos.add(budget);
    await sendWhatsApp({ ...budget, id });
    showToast('Orçamento salvo e enviado!', 'success');
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
          <p className="mt-3">Carregando...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in pb-32">
      <div>
        <h1 className="text-2xl font-bold">Novo Orçamento</h1>
        <p className="text-slate-500 mt-1">Profissão: <span className="font-semibold text-blue-600">{profissao.nome}</span></p>
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
                <div className={`w-10 h-10 rounded-full flex items-center justify-center ${isActive ? 'bg-blue-100' : isCompleted ? 'bg-green-100' : 'bg-slate-100'}`}>
                  <Icon size={20} />
                </div>
                <span className="text-xs hidden sm:inline">{s.title}</span>
              </button>
              {idx < stepConfig.length - 1 && <ChevronRight size={16} className="text-slate-300" />}
            </React.Fragment>
          );
        })}
      </div>

      {/* Step 1: Cliente */}
      {step === 1 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Selecionar Cliente</h3>
              <button onClick={() => setShowClientModal(true)} className="text-blue-600 text-sm flex items-center gap-1"><UserPlus size={16} /> Novo Cliente</button>
            </div>
            <div className="space-y-2 max-h-96 overflow-y-auto">
              {clientes.length === 0 ? (
                <div className="text-center py-8 text-slate-500">Nenhum cliente</div>
              ) : (
                clientes.map(cliente => (
                  <button
                    key={cliente.id}
                    onClick={() => setSelectedCliente(cliente)}
                    className={`w-full text-left p-3 rounded-lg border transition-all ${selectedCliente?.id === cliente.id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-blue-200'}`}
                  >
                    <p className="font-medium">{cliente.nome}</p>
                    {cliente.whatsapp && <p className="text-sm text-slate-500">{cliente.whatsapp}</p>}
                  </button>
                ))
              )}
            </div>
          </div>
          <button onClick={() => setStep(2)} disabled={!selectedCliente} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50">Próximo</button>
        </div>
      )}

      {/* Step 2: Serviços */}
      {step === 2 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Serviços de {profissao.nome}</h3>
              <button onClick={() => setShowServicoModal(true)} className="text-blue-600 text-sm flex items-center gap-1"><Plus size={16} /> Adicionar</button>
            </div>
            {selectedServicos.length === 0 ? (
              <div className="text-center py-8 text-slate-500"><Wrench size={48} className="mx-auto mb-2 opacity-50" /><p>Nenhum serviço adicionado</p></div>
            ) : (
              <div className="space-y-3 max-h-96 overflow-y-auto">
                {selectedServicos.map((servico, idx) => (
                  <div key={servico.id} className="border rounded-lg p-3">
                    <div className="flex justify-between items-start mb-2">
                      <h4 className="font-medium">{servico.nome}</h4>
                      <button onClick={() => removeServicoItem(idx)} className="text-red-500"><Trash2 size={16} /></button>
                    </div>
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-sm text-slate-500">Quantidade:</span>
                      <div className="flex items-center gap-3">
                        <button onClick={() => diminuirQuantidade(idx)} disabled={servico.quantidade <= 1} className="p-1 bg-slate-100 rounded-full"><Minus size={16} /></button>
                        <span className="font-semibold w-8 text-center">{servico.quantidade}</span>
                        <button onClick={() => aumentarQuantidade(idx)} className="p-1 bg-slate-100 rounded-full"><Plus size={16} /></button>
                      </div>
                    </div>
                    {servico.usaPrecoFixo ? (
                      <div className="text-sm text-green-600 mb-2">Preço unitário: {formatarMoeda(servico.precoUnitario)}</div>
                    ) : (
                      <div className="grid grid-cols-2 gap-2 text-sm mb-2">
                        <div><label className="text-xs">Tempo (min)</label><input type="number" value={servico.tempoAjustado} onChange={(e) => updateServicoItem(idx, 'tempoAjustado', e.target.value)} className="w-full px-2 py-1 border rounded" min="1" /></div>
                        <div><label className="text-xs">Dificuldade</label><select value={servico.dificuldade} onChange={(e) => updateServicoItem(idx, 'dificuldade', e.target.value)} className="w-full px-2 py-1 border rounded"><option value="NORMAL">Normal (1.0x)</option><option value="MEDIO">Médio (1.3x)</option><option value="ALTO">Alto (1.6x)</option></select></div>
                      </div>
                    )}
                    <div className="mt-2 pt-2 border-t"><p className="text-right font-semibold text-blue-600">Total: {formatarMoeda(servico.precoTotal)}</p></div>
                  </div>
                ))}
              </div>
            )}
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(1)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={() => setStep(3)} disabled={selectedServicos.length === 0} className="flex-1 bg-blue-600 text-white py-3 rounded-lg disabled:opacity-50">Próximo</button>
          </div>
        </div>
      )}

      {/* Step 3: Fotos */}
      {step === 3 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="font-semibold">Fotos do Serviço</h3>
              <button onClick={() => setShowCamera(true)} className="text-blue-600 text-sm flex items-center gap-1"><Camera size={16} /> Tirar Foto</button>
            </div>
            {fotos.length === 0 ? (
              <div className="text-center py-8 text-slate-500"><Camera size={48} className="mx-auto mb-2 opacity-50" /><p>Nenhuma foto</p></div>
            ) : (
              <div className="grid grid-cols-2 gap-2">
                {fotos.map((foto, idx) => (
                  <div key={idx} className="relative">
                    <img src={foto} className="w-full h-32 object-cover rounded-lg" />
                    <button onClick={() => removePhoto(idx)} className="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full"><Trash2 size={14} /></button>
                  </div>
                ))}
              </div>
            )}
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(2)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={() => setStep(4)} className="flex-1 bg-blue-600 text-white py-3 rounded-lg">Próximo</button>
          </div>
        </div>
      )}

      {/* Step 4: Resumo com desconto e validade */}
      {step === 4 && (
        <div className="space-y-4">
          <div className="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div className="p-4 bg-slate-50 border-b">
              <h3 className="font-semibold">Resumo do Orçamento</h3>
            </div>
            <div className="p-4 space-y-3">
              <div className="flex justify-between text-sm"><span>Cliente:</span><span className="font-medium">{selectedCliente?.nome}</span></div>
              <div className="flex justify-between text-sm"><span>Profissão:</span><span className="font-medium">{profissao?.nome}</span></div>
              <div className="border-t pt-3">
                <p className="text-sm font-medium mb-2">Serviços:</p>
                {selectedServicos.map((servico, idx) => (
                  <div key={idx} className="flex justify-between text-sm py-1">
                    <div><span>{servico.nome} x{servico.quantidade}</span>{servico.usaPrecoFixo ? <span className="text-xs text-green-600 ml-2">(Preço fixo)</span> : <span className="text-xs text-slate-500 ml-2">({formatarTempo(servico.tempoAjustado)})</span>}</div>
                    <span className="font-medium">{formatarMoeda(servico.precoTotal)}</span>
                  </div>
                ))}
              </div>
              <div className="border-t pt-3">
                <label className="block text-sm font-medium mb-2 flex items-center gap-2"><Calendar size={16} /> Validade</label>
                <div className="flex gap-2 flex-wrap">{opcoesValidade.map(op => (<button key={op.dias} onClick={() => setValidade(op.dias)} className={`flex-1 py-2 px-3 rounded-lg border ${validade === op.dias ? 'bg-blue-600 text-white' : 'border-slate-300'}`}>{op.label}</button>))}</div>
                <p className="text-xs text-slate-500 mt-2">Vence em: {calcularDataVencimento().toLocaleDateString()}</p>
              </div>
              <div className="border-t pt-3">
                <label className="block text-sm font-medium mb-2 flex items-center gap-2"><Percent size={16} /> Desconto</label>
                <div className="flex gap-2 mb-2">
                  <button onClick={() => setTipoDesconto('valor')} className={`px-3 py-1 rounded-lg text-sm font-semibold ${tipoDesconto === 'valor' ? 'bg-blue-600 text-white' : 'bg-slate-100'}`}>R$</button>
                  <button onClick={() => setTipoDesconto('percentual')} className={`px-3 py-1 rounded-lg text-sm font-semibold ${tipoDesconto === 'percentual' ? 'bg-blue-600 text-white' : 'bg-slate-100'}`}>%</button>
                </div>
                <input type="number" step={tipoDesconto === 'percentual' ? 1 : 0.01} value={desconto} onChange={e => setDesconto(parseFloat(e.target.value) || 0)} className="w-full px-4 py-2 border rounded-lg" placeholder={tipoDesconto === 'valor' ? 'Valor do desconto' : 'Percentual'} />
              </div>
              <div className="border-t pt-3 space-y-1">
                <div className="flex justify-between text-sm"><span>Subtotal</span><span>{formatarMoeda(subtotal)}</span></div>
                <div className="flex justify-between text-sm"><span>Deslocamento</span><span>{formatarMoeda(taxaDeslocamento)}</span></div>
                {desconto > 0 && <div className="flex justify-between text-sm text-red-600"><span>Desconto</span><span>- {tipoDesconto === 'valor' ? formatarMoeda(desconto) : `${desconto}%`}</span></div>}
                <div className="flex justify-between text-lg font-bold pt-2 border-t"><span>Total</span><span className="text-blue-600">{formatarMoeda(totalOrcamento)}</span></div>
              </div>
              {fotos.length > 0 && <div className="border-t pt-3"><p className="text-sm">{fotos.length} foto(s)</p></div>}
            </div>
          </div>
          <div className="flex gap-3">
            <button onClick={() => setStep(3)} className="flex-1 border py-3 rounded-lg">Voltar</button>
            <button onClick={handleSaveBudget} disabled={enviando} className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold flex justify-center gap-2"><Send size={20} /> {enviando ? 'Enviando...' : 'Salvar e Enviar WhatsApp'}</button>
          </div>
        </div>
      )}

      {/* Modais */}
      {showClientModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Novo Cliente</h3>
            <input type="text" placeholder="Nome *" value={newCliente.nome} onChange={e => setNewCliente({...newCliente, nome: e.target.value})} className="w-full border rounded-lg p-2 mb-3" />
            <input type="tel" placeholder="WhatsApp" value={newCliente.whatsapp} onChange={e => setNewCliente({...newCliente, whatsapp: e.target.value})} className="w-full border rounded-lg p-2 mb-3" />
            <textarea placeholder="Endereço" value={newCliente.endereco} onChange={e => setNewCliente({...newCliente, endereco: e.target.value})} rows="2" className="w-full border rounded-lg p-2 mb-4" />
            <div className="flex gap-3"><button onClick={() => setShowClientModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button><button onClick={handleAddCliente} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button></div>
          </div>
        </div>
      )}

      {showServicoModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6 max-h-96 overflow-y-auto">
            <h3 className="text-xl font-bold mb-4">Serviços de {profissao?.nome}</h3>
            <div className="space-y-2">
              {servicos.length === 0 ? (
                <div className="text-center py-8">Nenhum serviço cadastrado</div>
              ) : (
                servicos.map(s => (
                  <button key={s.id} onClick={() => handleAddServicoToBudget(s, 1)} className="w-full text-left p-3 border rounded-lg hover:border-blue-500">
                    <p className="font-medium">{s.nome}</p>
                    <div className="flex justify-between mt-1"><span className="text-sm text-slate-500">{formatarTempo(s.tempoPadrao)}</span>{s.precoFixo && <span className="text-sm text-green-600">{formatarMoeda(s.precoFixo)}</span>}</div>
                  </button>
                ))
              )}
            </div>
            <button onClick={() => setShowServicoModal(false)} className="w-full mt-4 border py-2 rounded-lg">Fechar</button>
          </div>
        </div>
      )}

      <CameraModal isOpen={showCamera} onClose={() => setShowCamera(false)} onCapture={handleCapturePhoto} />
    </div>
  );
};

export default NovoOrcamento;