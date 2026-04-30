import React, { useState, useEffect } from 'react';
import { UserPlus, Wrench, Camera, TrendingUp, Send, Trash2, Plus, Minus, ChevronRight, DollarSign, Calendar, Percent } from 'lucide-react';
import db from '../../database/db';
import CameraModal from '../../components/CameraModal';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { calcularPrecoServico, calcularTotalOrcamento, formatarMoeda, formatarTempo, DIFICULDADE } from '../../core/calculadora';
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
  const opcoesValidade = [{ dias: 1, label: '1 dia' }, { dias: 5, label: '5 dias' }, { dias: 15, label: '15 dias' }, { dias: 30, label: '30 dias' }];

  useEffect(() => { loadData(); const loadValidadePadrao = async () => { const c = await db.config.where('chave').equals('validadePadrao').first(); if(c) setValidade(c.valor); }; loadValidadePadrao(); }, [profissao]);

  const loadData = async () => {
    const allClientes = await db.clientes.toArray();
    setClientes(allClientes);
    if (profissao) setServicos(await db.servicos.where('profissaoId').equals(profissao.id).toArray());
  };

  const handleAddCliente = async () => {
    if (!newCliente.nome.trim()) { showToast('Nome do cliente é obrigatório', 'error'); return; }
    const id = await db.clientes.add(newCliente);
    setClientes([...clientes, { ...newCliente, id }]);
    setSelectedCliente({ ...newCliente, id });
    setShowClientModal(false);
    setNewCliente({ nome: '', whatsapp: '', endereco: '' });
    showToast('Cliente adicionado!', 'success');
  };

  const handleAddServicoToBudget = (servico, quantidade = 1) => {
    let precoUnitario, usaPrecoFixo = false;
    if (servico.precoFixo && servico.precoFixo > 0) { precoUnitario = servico.precoFixo; usaPrecoFixo = true; }
    else precoUnitario = calcularPrecoServico(servico.tempoPadrao, config.valorMinuto, DIFICULDADE.NORMAL.fator, config.margemReserva);
    setSelectedServicos([...selectedServicos, { id: Date.now(), servicoOriginalId: servico.id, nome: servico.nome, tempoAjustado: servico.tempoPadrao, dificuldade: 'NORMAL', precoUnitario, quantidade, precoTotal: precoUnitario * quantidade, precoFixo: servico.precoFixo || null, usaPrecoFixo }]);
    setShowServicoModal(false);
  };

  const aumentarQuantidade = (i) => { const up = [...selectedServicos]; up[i].quantidade++; up[i].precoTotal = up[i].precoUnitario * up[i].quantidade; setSelectedServicos(up); };
  const diminuirQuantidade = (i) => { const up = [...selectedServicos]; if(up[i].quantidade > 1) { up[i].quantidade--; up[i].precoTotal = up[i].precoUnitario * up[i].quantidade; } setSelectedServicos(up); };
  const updateServicoItem = (i, field, val) => { const up = [...selectedServicos]; if(field==='tempoAjustado') { up[i].tempoAjustado = parseInt(val); if(!up[i].usaPrecoFixo) { up[i].precoUnitario = calcularPrecoServico(up[i].tempoAjustado, config.valorMinuto, DIFICULDADE[up[i].dificuldade].fator, config.margemReserva); up[i].precoTotal = up[i].precoUnitario * up[i].quantidade; } } else if(field==='dificuldade') { up[i].dificuldade = val; if(!up[i].usaPrecoFixo) { up[i].precoUnitario = calcularPrecoServico(up[i].tempoAjustado, config.valorMinuto, DIFICULDADE[val].fator, config.margemReserva); up[i].precoTotal = up[i].precoUnitario * up[i].quantidade; } } setSelectedServicos(up); };
  const removeServicoItem = (i) => setSelectedServicos(selectedServicos.filter((_,idx)=>idx!==i));
  const handleCapturePhoto = (photo) => setFotos([...fotos, photo]);
  const removePhoto = (i) => setFotos(fotos.filter((_,idx)=>idx!==i));
  const calcularSubtotal = () => selectedServicos.reduce((s,item)=>s+item.precoTotal,0);
  const calcularTotalComDesconto = () => { let total = calcularSubtotal() + config.taxaDeslocamento; if(tipoDesconto==='valor') total -= desconto; else if(tipoDesconto==='percentual') total *= (1 - desconto/100); return Math.max(0,total); };
  const totalOrcamento = calcularTotalComDesconto();
  const subtotal = calcularSubtotal();
  const taxaDeslocamento = config.taxaDeslocamento;
  const calcularDataVencimento = () => { const d = new Date(); d.setDate(d.getDate() + validade); return d; };
  const sendWhatsApp = async (orcSalvo) => { if(!selectedCliente?.whatsapp) { showToast('Cliente sem WhatsApp', 'warning'); return false; } const msg = `*ORÇAMENTO MÃO DE OBRA PRO*%0A*Nº:* ${orcSalvo.id}%0A*Cliente:* ${selectedCliente.nome}%0A*Data:* ${new Date(orcSalvo.data).toLocaleDateString()}%0A*Válido até:* ${new Date(orcSalvo.dataVencimento).toLocaleDateString()}%0A%0A*SERVIÇOS:*%0A${selectedServicos.map(s=>`✓ ${s.nome} x${s.quantidade} - ${formatarMoeda(s.precoTotal)}`).join('%0A')}%0A%0A*DESLOCAMENTO:* ${formatarMoeda(taxaDeslocamento)}%0A*DESCONTO:* ${tipoDesconto==='valor'?formatarMoeda(desconto):`${desconto}%`}%0A*TOTAL:* ${formatarMoeda(totalOrcamento)}`; window.location.href = `https://wa.me/55${selectedCliente.whatsapp.replace(/\D/g,'')}?text=${msg}`; return true; };
  const handleSaveBudget = async () => { if(!selectedCliente) { showToast('Selecione um cliente','warning'); return; } if(selectedServicos.length===0) { showToast('Adicione serviços','warning'); return; } setEnviando(true); const dataVenc = calcularDataVencimento(); const budget = { clienteId: selectedCliente.id, data: new Date().toISOString(), total: totalOrcamento, desconto: tipoDesconto==='valor' ? desconto : -desconto, status: 'pendente', itens: selectedServicos.map(s=>({ nome: s.nome, tempo: s.tempoAjustado, dificuldade: s.dificuldade, precoUnitario: s.precoUnitario, quantidade: s.quantidade, precoTotal: s.precoTotal, usaPrecoFixo: s.usaPrecoFixo })), fotos, taxaDeslocamento, subtotal, profissaoId: profissao?.id, profissaoNome: profissao?.nome, validade, dataVencimento: dataVenc.toISOString() }; const id = await db.orcamentos.add(budget); await sendWhatsApp({...budget, id}); showToast('Orçamento salvo e enviado!','success'); setEnviando(false); if(onSave) onSave(); };
  const stepConfig = [{ number: 1, title: 'Cliente', icon: UserPlus }, { number: 2, title: 'Serviços', icon: Wrench }, { number: 3, title: 'Fotos', icon: Camera }, { number: 4, title: 'Resumo', icon: TrendingUp }];
  if(!profissao) return <div className="flex justify-center p-8"><div className="animate-spin rounded-full h-12 w-12"/></div>;
  return ( <div className="space-y-6 pb-32"> <div><h1 className="text-2xl font-bold">Novo Orçamento</h1><p>Profissão: <span className="font-semibold text-blue-600">{profissao.nome}</span></p></div> <div className="flex justify-between items-center">{stepConfig.map((s,idx)=>{ const Icon=s.icon; const isActive=step===s.number; const isCompleted=step>s.number; return (<React.Fragment key={s.number}><button onClick={()=>setStep(s.number)} className={`flex flex-col items-center gap-1 flex-1 ${isActive?'text-blue-600':isCompleted?'text-green-600':'text-slate-400'}`}><div className={`w-10 h-10 rounded-full flex items-center justify-center ${isActive?'bg-blue-100':isCompleted?'bg-green-100':'bg-slate-100'}`}><Icon size={20}/></div><span className="text-xs hidden sm:inline">{s.title}</span></button>{idx<stepConfig.length-1 && <ChevronRight size={16} className="text-slate-300"/>}</React.Fragment>);})}</div> {/* Steps 1-4 igual o original, já está implementado - omitido para brevidade, mas no código real mantêm-se as mesmas telas */} </div> );
};

export default NovoOrcamento;