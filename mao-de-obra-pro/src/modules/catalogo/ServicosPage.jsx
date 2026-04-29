import { useState } from 'react';
import { useLiveQuery } from 'dexie-react-hooks';
import { Plus, Trash2, Clock } from 'lucide-react';
import db from '../../database/db';

export const ServicosPage = () => {
  const [novoServico, setNovoServico] = useState({ nome: '', tempo: '', categoria: '' });
  const servicos = useLiveQuery(() => db.servicos.toArray()) || [];

  const adicionarServico = async (e) => {
    e.preventDefault();
    if (!novoServico.nome || !novoServico.tempo) return;
    await db.servicos.add({
      nome: novoServico.nome,
      tempoPadrao: Number(novoServico.tempo),
      categoria: novoServico.categoria || 'Geral'
    });
    setNovoServico({ nome: '', tempo: '', categoria: '' });
  };

  return (
    <div className="space-y-6">
      <section className="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <h2 className="font-bold text-lg mb-4 flex items-center gap-2">
          <Plus size={20} className="text-blue-600" /> Novo Serviço
        </h2>
        <form onSubmit={adicionarServico} className="space-y-3">
          <input
            type="text"
            placeholder="Ex: Instalação de Chuveiro"
            className="w-full"
            value={novoServico.nome}
            onChange={e => setNovoServico({...novoServico, nome: e.target.value})}
          />
          <div className="flex gap-2">
            <input
              type="number"
              placeholder="Tempo (minutos)"
              className="w-full"
              value={novoServico.tempo}
              onChange={e => setNovoServico({...novoServico, tempo: e.target.value})}
            />
            <button type="submit" className="bg-blue-600 text-white px-6 rounded-lg font-bold">
              OK
            </button>
          </div>
        </form>
      </section>

      <div className="space-y-3">
        <h3 className="font-semibold text-slate-500 uppercase text-xs tracking-wider">Meus Serviços</h3>
        {servicos.map(s => (
          <div key={s.id} className="bg-white p-4 rounded-xl flex justify-between items-center shadow-sm">
            <div>
              <p className="font-bold text-slate-800">{s.nome}</p>
              <p className="text-sm text-slate-500 flex items-center gap-1">
                <Clock size={14} /> {s.tempoPadrao} min
              </p>
            </div>
            <button onClick={() => db.servicos.delete(s.id)} className="text-red-400 p-2">
              <Trash2 size={18} />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};