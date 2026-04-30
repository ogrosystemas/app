import React, { useState, useEffect } from 'react';
import { Plus, Search, Phone, MapPin, Trash2, Edit2, X, Check, Users } from 'lucide-react';
import db from '../../database/db';
import ConfirmModal from '../../components/ConfirmModal';
import { useToast } from '../../context/ToastContext';

const ClientesPage = () => {
  const { showToast } = useToast();
  const [clientes, setClientes] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingCliente, setEditingCliente] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({ nome: '', whatsapp: '', endereco: '' });

  useEffect(() => { loadClientes(); }, []);

  const loadClientes = async () => {
    try {
      const all = await db.clientes.toArray();
      setClientes(all.reverse());
    } catch (err) { showToast('Erro ao carregar clientes', 'error'); }
  };

  const handleSave = async () => {
    if (!formData.nome.trim()) { showToast('Nome é obrigatório', 'error'); return; }
    if (saving) return;
    setSaving(true);
    try {
      if (editingCliente) {
        await db.clientes.update(editingCliente.id, formData);
        showToast('Cliente atualizado', 'success');
      } else {
        await db.clientes.add(formData);
        showToast('Cliente adicionado', 'success');
      }
      await loadClientes();
      setShowModal(false);
      setFormData({ nome: '', whatsapp: '', endereco: '' });
      setEditingCliente(null);
    } catch (err) { showToast('Erro ao salvar', 'error'); }
    finally { setSaving(false); }
  };

  const handleDelete = async (id) => {
    await db.clientes.delete(id);
    await loadClientes();
    setDeleteConfirm(null);
    showToast('Cliente excluído', 'success');
  };

  const handleEdit = (c) => { setEditingCliente(c); setFormData(c); setShowModal(true); };
  const filtered = clientes.filter(c => c.nome.toLowerCase().includes(searchTerm.toLowerCase()));

  return (
    <div className="space-y-4">
      <div className="flex justify-between"><h1 className="text-2xl font-bold">Clientes</h1><button onClick={()=>setShowModal(true)} className="bg-blue-600 text-white px-4 py-2 rounded">+ Novo</button></div>
      <div className="relative"><Search className="absolute left-3 top-3 text-slate-400"/><input type="text" placeholder="Buscar..." value={searchTerm} onChange={e=>setSearchTerm(e.target.value)} className="w-full pl-10 pr-4 py-2 border rounded"/></div>
      <div className="space-y-2">{filtered.length===0?<div className="text-center p-8 text-slate-500">Nenhum cliente</div>:filtered.map(c=>(
        <div key={c.id} className="bg-white p-4 rounded-xl border flex justify-between"><div><p className="font-semibold">{c.nome}</p>{c.whatsapp&&<p className="text-sm">{c.whatsapp}</p>}</div><div className="flex gap-2"><button onClick={()=>handleEdit(c)} className="text-blue-600"><Edit2 size={18}/></button><button onClick={()=>setDeleteConfirm(c.id)} className="text-red-600"><Trash2 size={18}/></button></div></div>
      ))}</div>
      {showModal && <div className="fixed inset-0 bg-black/50 flex justify-center items-center p-4"><div className="bg-white rounded-xl max-w-md w-full p-6"><h2 className="text-xl font-bold mb-4">{editingCliente?'Editar':'Novo'} Cliente</h2><input value={formData.nome} onChange={e=>setFormData({...formData,nome:e.target.value})} placeholder="Nome" className="w-full border rounded p-2 mb-2"/><input value={formData.whatsapp} onChange={e=>setFormData({...formData,whatsapp:e.target.value})} placeholder="WhatsApp" className="w-full border rounded p-2 mb-2"/><textarea value={formData.endereco} onChange={e=>setFormData({...formData,endereco:e.target.value})} placeholder="Endereço" className="w-full border rounded p-2 mb-4"/><div className="flex gap-2"><button onClick={()=>setShowModal(false)} className="flex-1 border rounded py-2">Cancelar</button><button onClick={handleSave} disabled={saving} className="flex-1 bg-blue-600 text-white rounded py-2">Salvar</button></div></div></div>}
      <ConfirmModal isOpen={!!deleteConfirm} onClose={()=>setDeleteConfirm(null)} onConfirm={()=>handleDelete(deleteConfirm)} title="Excluir" message="Tem certeza?" confirmText="Excluir"/>
    </div>
  );
};
export default ClientesPage;