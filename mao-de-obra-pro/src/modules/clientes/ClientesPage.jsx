import React, { useState, useEffect } from 'react';
import { Plus, Trash2, User } from 'lucide-react';
import db from '../../database/db';

const ClientesPage = () => {
  const [clientes, setClientes] = useState([]);
  const [showModal, setShowModal] = useState(false);
  const [nome, setNome] = useState('');
  const [whatsapp, setWhatsapp] = useState('');

  useEffect(() => {
    loadClientes();
  }, []);

  const loadClientes = async () => {
    const all = await db.clientes.toArray();
    setClientes(all.reverse());
  };

  const addCliente = async () => {
    if (!nome.trim()) return;
    await db.clientes.add({ nome, whatsapp, endereco: '' });
    setNome('');
    setWhatsapp('');
    setShowModal(false);
    await loadClientes();
  };

  const deleteCliente = async (id) => {
    if (confirm('Excluir cliente?')) {
      await db.clientes.delete(id);
      await loadClientes();
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Clientes</h1>
        <button onClick={() => setShowModal(true)} className="bg-blue-600 text-white p-2 rounded-lg">
          <Plus size={20} />
        </button>
      </div>

      <div className="space-y-2">
        {clientes.map(c => (
          <div key={c.id} className="bg-white rounded-xl p-4 shadow-sm border flex justify-between items-center">
            <div>
              <p className="font-semibold">{c.nome}</p>
              {c.whatsapp && <p className="text-sm text-slate-500">{c.whatsapp}</p>}
            </div>
            <button onClick={() => deleteCliente(c.id)} className="text-red-500">
              <Trash2 size={18} />
            </button>
          </div>
        ))}
        {clientes.length === 0 && (
          <div className="text-center py-8 text-slate-500">
            <User size={48} className="mx-auto mb-2 opacity-50" />
            <p>Nenhum cliente cadastrado</p>
          </div>
        )}
      </div>

      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">Novo Cliente</h3>
            <input type="text" placeholder="Nome" value={nome} onChange={(e) => setNome(e.target.value)} className="w-full px-4 py-2 border rounded-lg mb-3" />
            <input type="tel" placeholder="WhatsApp" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)} className="w-full px-4 py-2 border rounded-lg mb-4" />
            <div className="flex gap-3">
              <button onClick={() => setShowModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button>
              <button onClick={addCliente} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ClientesPage;