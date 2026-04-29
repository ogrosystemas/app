import { useState } from 'react';
import { useLiveQuery } from 'dexie-react-hooks';
import { Plus, Camera, Save, Trash2 } from 'lucide-react';
import db from '../../database/db';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { calcularPrecoServico } from '../../core/calculadora';
import { CameraModal } from '../../components/CameraModal';

export const NovoOrcamento = () => {
  const { metricas, dados } = useFinanceiro();
  const [clienteId, setClienteId] = useState('');
  const [itens, setItens] = useState([]);
  const [fotos, setFotos] = useState([]);
  const [showCamera, setShowCamera] = useState(false);
  const [taxaDeslocamento, setTaxaDeslocamento] = useState(0);

  const clientes = useLiveQuery(() => db.clientes.toArray()) || [];
  const servicosDisponiveis = useLiveQuery(() => db.servicos.toArray()) || [];

  const adicionarServico = (id) => {
    const s = servicosDisponiveis.find(x => x.id === Number(id));
    if (!s) return;
    setItens([...itens, { 
      ...s, 
      tempoAjustado: s.tempoPadrao, 
      dificuldade: 1.0,
      valorFinal: calcularPrecoServico(s.tempoPadrao, metricas.valorMinuto, 1.0, dados.margemReserva)
    }]);
  };

  const atualizarItem = (index, campo, valor) => {
    const novosItens = [...itens];
    novosItens[index][campo] = Number(valor);
    novosItens[index].valorFinal = calcularPrecoServico(
      novosItens[index].tempoAjustado, 
      metricas.valorMinuto, 
      novosItens[index].dificuldade, 
      dados.margemReserva
    );
    setItens(novosItens);
  };

  const totalGeral = itens.reduce((acc, i) => acc + i.valorFinal, 0) + Number(taxaDeslocamento);

  const salvarOrcamento = async () => {
    if (!clienteId || itens.length === 0) return alert("Selecione um cliente e ao menos um serviço.");
    await db.orcamentos.add({
      clienteId: Number(clienteId),
      data: new Date(),
      itens,
      fotos,
      taxaDeslocamento,
      total: totalGeral,
      status: 'rascunho'
    });
    alert("Orçamento salvo com sucesso!");
  };

  return (
    <div className="space-y-4 pb-20">
      <h2 className="text-xl font-bold">Novo Orçamento</h2>
      
      {/* Seleção de Cliente */}
      <select className="w-full bg-white shadow-sm" value={clienteId} onChange={e => setClienteId(e.target.value)}>
        <option value="">Selecione o Cliente</option>
        {clientes.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
      </select>

      {/* Adicionar Serviço */}
      <select className="w-full bg-blue-50 border-blue-200 font-medium" onChange={e => adicionarServico(e.target.value)} value="">
        <option value="">+ Adicionar Serviço do Catálogo</option>
        {servicosDisponiveis.map(s => <option key={s.id} value={s.id}>{s.nome}</option>)}
      </select>

      {/* Lista de Itens Adicionados */}
      <div className="space-y-3">
        {itens.map((item, index) => (
          <div key={index} className="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <div className="flex justify-between font-bold">
              <span>{item.nome}</span>
              <span className="text-blue-600">R$ {item.valorFinal.toFixed(2)}</span>
            </div>
            <div className="grid grid-cols-2 gap-3 mt-3">
              <label className="text-xs text-slate-500">
                Minutos:
                <input type="number" value={item.tempoAjustado} onChange={e => atualizarItem(index, 'tempoAjustado', e.target.value)} className="w-full mt-1 border-slate-200" />
              </label>
              <label className="text-xs text-slate-500">
                Dificuldade:
                <select value={item.dificuldade} onChange={e => atualizarItem(index, 'dificuldade', e.target.value)} className="w-full mt-1 border-slate-200">
                  <option value="1.0">Normal (1.0x)</option>
                  <option value="1.3">Média (1.3x)</option>
                  <option value="1.6">Alta (1.6x)</option>
                </select>
              </label>
            </div>
          </div>
        ))}
      </div>

      {/* Seção de Fotos */}
      <div className="flex gap-2 overflow-x-auto py-2">
        <button onClick={() => setShowCamera(true)} className="min-w-[80px] h-20 bg-slate-200 rounded-lg flex flex-col items-center justify-center text-slate-600">
          <Camera size={24} />
          <span className="text-[10px] mt-1">Foto</span>
        </button>
        {fotos.map((f, i) => (
          <img key={i} src={f} className="h-20 w-20 object-cover rounded-lg" alt="Evidência" />
        ))}
      </div>

      {/* Taxa de Visita */}
      <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex justify-between items-center">
        <span className="font-medium text-slate-600">Taxa de Deslocamento</span>
        <input type="number" className="w-24 text-right font-bold" value={taxaDeslocamento} onChange={e => setTaxaDeslocamento(e.target.value)} />
      </div>

      {/* Resumo Final */}
      <div className="bg-blue-600 text-white p-4 rounded-xl shadow-lg flex justify-between items-center">
        <div>
          <p className="text-xs opacity-80 uppercase font-bold tracking-wider">Total Estimado</p>
          <p className="text-2xl font-black">R$ {totalGeral.toFixed(2)}</p>
        </div>
        <button onClick={salvarOrcamento} className="bg-white text-blue-600 px-6 py-2 rounded-lg font-bold flex items-center gap-2">
          <Save size={20} /> Salvar
        </button>
      </div>

      {showCamera && (
        <CameraModal 
          onCapture={(p) => { setFotos([...fotos, p]); setShowCamera(false); }} 
          onClose={() => setShowCamera(false)} 
        />
      )}
    </div>
  );
};