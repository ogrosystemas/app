import React, { useState, useEffect } from 'react';
import { Plus, Trash2, Wrench } from 'lucide-react';
import db from '../../database/db';

const ServicosPage = () => {
  const [servicos, setServicos] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [nome, setNome] = useState('');
  const [tempo, setTempo] = useState('');

  useEffect(() => {
    loadServicos();
  }, []);

  const loadServicos = async () => {
    const all = await db.servicos.toArray();
    setServicos(all.reverse());
  };

  const addServico = async () => {
    if (!nome.trim() || !tempo) return;
    await db.servicos.add({ nome, tempoPadrao: parseInt(tempo), categoria: 'Geral' });
    setNome('');
    setTempo('');
    setShowModal(false);
    await loadServicos();
  };

  const deleteServico = async (id) => {
    if (confirm('Excluir serviço?')) {
      await db.servicos.delete(id);
      await loadServicos();
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Serviços</h1>
        <button onClick={() => setShowModal(true)} className="bg-blue-600 text-white p-2 rounded-lg">
          <Plus size={20} />
        </button>
      </div>

      <div className="space-y-2">
        {servicos.map(s => (
          <div key={s.id} className="bg-white rounded-xl p-4 shadow-sm border flex justify-between items-center">
            <div>
              <p className="font-semibold">{s.nome}</p>
              <p className="text-sm text-slate-500">{s.tempoPadrao} minutos</p>
            </div>
            <button onClick={() => deleteServico(s.id)} className="text-red-500">
              <Trash2 size={18} />
            </button>
          </div>
        ))}
        {servicos.length === 0 && (
          <div className="text-center py-8 text-slate-500">
            <Wrench size={48} className="mx-auto mb-2 opacity-50" />
            <p>Nenhum serviço cadastrado</p>
          </div>
        )}
      </div>

      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Novo Serviço</h3>
            <input type="text" placeholder="Nome do serviço" value={nome} onChange={(e) => setNome(e.target.value)} className="w-full px-4 py-2 border rounded-lg mb-3" />
            <input type="number" placeholder="Tempo (minutos)" value={tempo} onChange={(e) => setTempo(e.target.value)} className="w-full px-4 py-2 border rounded-lg mb-4" />
            <div className="flex gap-3">
              <button onClick={() => setShowModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button>
              <button onClick={addServico} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ServicosPage;