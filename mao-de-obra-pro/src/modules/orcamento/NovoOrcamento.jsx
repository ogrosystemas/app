import { useState, useEffect } from 'react';
import { useLiveQuery } from 'dexie-react-hooks';
import { Plus, Camera, Save, Trash2, ChevronLeft } from 'lucide-react';
import db from '../../database/db';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { calcularPrecoServico } from '../../core/calculadora';
import { CameraModal } from '../../components/CameraModal';

export const NovoOrcamento = ({ aoSalvar }) => {
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
    const novoItem = {
      id_base: s.id,
      nome: s.nome,
      tempoAjustado: s.tempoPadrao,
      dificuldade: 1.0,
      valorFinal: calcularPrecoServico(s.tempoPadrao, metricas.valorMinuto, 1.0, dados.margemReserva)
    };
    setItens([...itens, novoItem]);
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

  const removerItem = (index) => {
    setItens(itens.filter((_, i) => i !== index));
  };

  const totalGeral = itens.reduce((acc, i) => acc + i.valorFinal, 0) + Number(taxaDeslocamento);

  const salvarOrcamento = async () => {
    if (!clienteId || itens.length === 0) {
      alert("Selecione um cliente e adicione pelo menos um serviço.");
      return;
    }

    try {
      await db.orcamentos.add({
        clienteId: Number(clienteId),
        data: new Date(),
        itens,
        fotos, // Salva as fotos em Base64 comprimido
        taxaDeslocamento: Number(taxaDeslocamento),
        total: totalGeral,
        status: 'rascunho'
      });
      aoSalvar();
    } catch (error) {
      console.error("Erro ao salvar:", error);
      alert("Erro ao salvar orçamento localmente.");
    }
  };

  return (
    <div className="space-y-4 pb-24 animate-in fade-in duration-300">
      <div className="flex items-center gap-2 mb-2">
        <button onClick={aoSalvar} className="p-2 -ml-2 text-slate-400"><ChevronLeft /></button>
        <h2 className="text-xl font-bold text-slate-800">Novo Orçamento</h2>
      </div>

      {/* Seletor Cliente */}
      <div className="space-y-1">
        <label className="text-xs font-bold text-slate-400 uppercase">Cliente</label>
        <select
          className="w-full bg-white border-slate-200"
          value={clienteId}
          onChange={e => setClienteId(e.target.value)}
        >
          <option value="">Selecione...</option>
          {clientes.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
        </select>
      </div>

      {/* Seletor Serviços */}
      <div className="space-y-1">
        <label className="text-xs font-bold text-slate-400 uppercase">Adicionar Mão de Obra</label>
        <select
          className="w-full bg-blue-50 border-blue-200 text-blue-700 font-medium"
          onChange={e => { if(e.target.value) adicionarServico(e.target.value); e.target.value = ""; }}
        >
          <option value="">+ Toque para buscar serviço...</option>
          {servicosDisponiveis.map(s => <option key={s.id} value={s.id}>{s.nome}</option>)}
        </select>
      </div>

      {/* Lista Dinâmica */}
      <div className="space-y-3">
        {itens.map((item, index) => (
          <div key={index} className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 relative">
            <button
              onClick={() => removerItem(index)}
              className="absolute top-2 right-2 text-slate-300 hover:text-red-500"
            >
              <Trash2 size={16} />
            </button>
            <p className="font-bold text-slate-700 pr-6">{item.nome}</p>
            <div className="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label className="text-[10px] font-bold text-slate-400 uppercase">Tempo (min)</label>
                <input
                  type="number"
                  value={item.tempoAjustado}
                  onChange={e => atualizarItem(index, 'tempoAjustado', e.target.value)}
                  className="w-full mt-1 bg-slate-50 border-none"
                />
              </div>
              <div>
                <label className="text-[10px] font-bold text-slate-400 uppercase">Dificuldade</label>
                <select
                  value={item.dificuldade}
                  onChange={e => atualizarItem(index, 'dificuldade', e.target.value)}
                  className="w-full mt-1 bg-slate-50 border-none"
                >
                  <option value="1.0">Normal</option>
                  <option value="1.3">Média (+30%)</option>
                  <option value="1.6">Alta (+60%)</option>
                </select>
              </div>
            </div>
            <p className="text-right mt-3 font-black text-blue-600">R$ {item.valorFinal.toFixed(2)}</p>
          </div>
        ))}
      </div>

      {/* Fotos */}
      <div className="space-y-2">
        <label className="text-xs font-bold text-slate-400 uppercase">Evidências (Fotos)</label>
        <div className="flex gap-2 overflow-x-auto pb-2">
          <button
            onClick={() => setShowCamera(true)}
            className="min-w-[80px] h-20 bg-blue-100 border-2 border-dashed border-blue-300 rounded-xl flex flex-col items-center justify-center text-blue-600"
          >
            <Camera size={24} />
            <span className="text-[10px] font-bold mt-1 uppercase">Capturar</span>
          </button>
          {fotos.map((f, i) => (
            <div key={i} className="relative shrink-0">
              <img src={f} className="h-20 w-20 object-cover rounded-xl shadow-inner" alt="Evidência" />
              <button
                onClick={() => setFotos(fotos.filter((_, idx) => idx !== i))}
                className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-1"
              >
                <Trash2 size={10} />
              </button>
            </div>
          ))}
        </div>
      </div>

      {/* Taxa Final */}
      <div className="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200">
        <span className="text-sm font-bold text-slate-500 uppercase">Taxa de Deslocamento</span>
        <div className="flex items-center gap-2">
          <span className="text-slate-400 font-bold">R$</span>
          <input
            type="number"
            className="w-20 font-black text-right border-none p-0 focus:ring-0"
            value={taxaDeslocamento}
            onChange={e => setTaxaDeslocamento(e.target.value)}
          />
        </div>
      </div>

      {/* Rodapé Fixo de Ação */}
      <div className="fixed bottom-20 left-0 right-0 p-4 bg-gradient-to-t from-slate-50 via-slate-50 to-transparent">
        <button
          onClick={salvarOrcamento}
          className="w-full bg-blue-600 text-white p-4 rounded-2xl font-black shadow-xl flex items-center justify-center gap-3 active:scale-95 transition-transform"
        >
          <Save size={24} /> SALVAR ORÇAMENTO (R$ {totalGeral.toFixed(2)})
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