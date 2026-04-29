import { useState } from 'react';
import { useLiveQuery } from 'dexie-react-hooks';
import { UserPlus, MessageCircle, MapPin } from 'lucide-react';
import db from '../../database/db';

export const ClientesPage = () => {
  const [novoCliente, setNovoCliente] = useState({ nome: '', whatsapp: '', endereco: '' });
  const clientes = useLiveQuery(() => db.clientes.toArray()) || [];

  const salvarCliente = async (e) => {
    e.preventDefault();
    await db.clientes.add(novoCliente);
    setNovoCliente({ nome: '', whatsapp: '', endereco: '' });
  };

  return (
    <div className="space-y-6">
      <form onSubmit={salvarCliente} className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 space-y-3">
        <h2 className="font-bold text-lg mb-2">Cadastrar Cliente</h2>
        <input
          type="text"
          placeholder="Nome do Cliente"
          className="w-full"
          value={novoCliente.nome}
          onChange={e => setNovoCliente({...novoCliente, nome: e.target.value})}
        />
        <input
          type="tel"
          placeholder="WhatsApp (com DDD)"
          className="w-full"
          value={novoCliente.whatsapp}
          onChange={e => setNovoCliente({...novoCliente, whatsapp: e.target.value})}
        />
        <textarea
          placeholder="Endereço (opcional)"
          className="w-full h-20 border rounded-lg p-2"
          value={novoCliente.endereco}
          onChange={e => setNovoCliente({...novoCliente, endereco: e.target.value})}
        ></textarea>
        <button className="w-full bg-blue-600 text-white py-3 rounded-lg font-bold flex items-center justify-center gap-2">
          <UserPlus size={20} /> Salvar Cliente
        </button>
      </form>

      <div className="grid gap-3">
        {clientes.map(c => (
          <div key={c.id} className="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
            <p className="font-bold text-lg">{c.nome}</p>
            <div className="flex items-center gap-2 text-slate-500 mt-1">
              <MessageCircle size={14} className="text-green-500" />
              <span className="text-sm">{c.whatsapp}</span>
            </div>
            {c.endereco && (
              <div className="flex items-start gap-2 text-slate-500 mt-1">
                <MapPin size={14} className="mt-1" />
                <span className="text-sm italic">{c.endereco}</span>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};