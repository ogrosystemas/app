import React, { useState, useEffect } from 'react';
import { Plus, Search, Phone, MapPin, Trash2, Edit2, X, Check, Users } from 'lucide-react';
import db from '../../database/db';
import ConfirmModal from '../../components/ConfirmModal';

const ClientesPage = ({ showToast }) => {
  const [clientes, setClientes] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingCliente, setEditingCliente] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    nome: '',
    whatsapp: '',
    endereco: ''
  });

  useEffect(() => {
    loadClientes();
  }, []);

  const loadClientes = async () => {
    try {
      if (!db.isOpen()) await db.open();
      const allClientes = await db.clientes.toArray();
      setClientes(allClientes.reverse());
    } catch (error) {
      console.error('Erro ao carregar clientes:', error);
      if (showToast) showToast('Erro ao carregar clientes', 'error');
    }
  };

  const handleSave = async () => {
    if (saving) return;
    if (!formData.nome.trim()) {
      if (showToast) showToast('Nome é obrigatório', 'error');
      return;
    }

    setSaving(true);
    try {
      if (!db.isOpen()) await db.open();

      if (editingCliente) {
        await db.clientes.update(editingCliente.id, {
          nome: formData.nome.trim(),
          whatsapp: formData.whatsapp || '',
          endereco: formData.endereco || ''
        });
        if (showToast) showToast('Cliente atualizado com sucesso!', 'success');
      } else {
        await db.clientes.add({
          nome: formData.nome.trim(),
          whatsapp: formData.whatsapp || '',
          endereco: formData.endereco || ''
        });
        if (showToast) showToast('Cliente adicionado com sucesso!', 'success');
      }
      await loadClientes();
      handleCloseModal();
    } catch (error) {
      console.error('Erro ao salvar cliente:', error);
      if (showToast) showToast('Erro ao salvar cliente. Tente novamente.', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id) => {
    try {
      if (!db.isOpen()) await db.open();
      await db.clientes.delete(id);
      await loadClientes();
      setDeleteConfirm(null);
      if (showToast) showToast('Cliente excluído com sucesso!', 'success');
    } catch (error) {
      console.error('Erro ao excluir cliente:', error);
      if (showToast) showToast('Erro ao excluir cliente', 'error');
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingCliente(null);
    setFormData({ nome: '', whatsapp: '', endereco: '' });
    setSaving(false);
  };

  const handleEdit = (cliente) => {
    setEditingCliente(cliente);
    setFormData({
      nome: cliente.nome,
      whatsapp: cliente.whatsapp || '',
      endereco: cliente.endereco || ''
    });
    setShowModal(true);
  };

  const filteredClientes = clientes.filter(c =>
    c.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (c.whatsapp && c.whatsapp.includes(searchTerm))
  );

  const formatWhatsApp = (whatsapp) => {
    if (!whatsapp) return '';
    let cleaned = whatsapp.replace(/\D/g, '');
    if (cleaned.length === 11) {
      return `(${cleaned.slice(0,2)}) ${cleaned.slice(2,7)}-${cleaned.slice(7)}`;
    }
    return whatsapp;
  };

  return (
    <div className="space-y-4 animate-fade-in">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Clientes</h1>
          <p className="text-slate-500 mt-1">Gerencie sua carteira de clientes</p>
        </div>
        <button
          onClick={() => setShowModal(true)}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2 hover:bg-blue-700 transition-colors w-full sm:w-auto justify-center"
        >
          <Plus size={20} />
          Novo Cliente
        </button>
      </div>

      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
        <input
          type="text"
          placeholder="Buscar cliente por nome ou WhatsApp..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div className="space-y-3">
        {filteredClientes.length === 0 ? (
          <div className="bg-white rounded-xl p-8 text-center text-slate-500 border border-slate-200">
            <Users size={48} className="mx-auto mb-3 opacity-50" />
            <p>Nenhum cliente cadastrado</p>
            <button
              onClick={() => setShowModal(true)}
              className="mt-3 text-blue-600 font-semibold hover:text-blue-700"
            >
              Adicionar primeiro cliente
            </button>
          </div>
        ) : (
          filteredClientes.map((cliente) => (
            <div key={cliente.id} className="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
              <div className="flex justify-between items-start">
                <div className="flex-1">
                  <h3 className="font-semibold text-slate-900 text-lg">{cliente.nome}</h3>
                  {cliente.whatsapp && (
                    <div className="flex items-center gap-2 mt-2 text-sm text-slate-600">
                      <Phone size={16} />
                      <span>{formatWhatsApp(cliente.whatsapp)}</span>
                    </div>
                  )}
                  {cliente.endereco && (
                    <div className="flex items-center gap-2 mt-1 text-sm text-slate-600">
                      <MapPin size={16} />
                      <span>{cliente.endereco}</span>
                    </div>
                  )}
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => handleEdit(cliente)}
                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  >
                    <Edit2 size={18} />
                  </button>
                  <button
                    onClick={() => setDeleteConfirm(cliente.id)}
                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <Trash2 size={18} />
                  </button>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Modal de cadastro/edição */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 animate-fade-in">
          <div className="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-slate-200 flex justify-between items-center">
              <h2 className="text-xl font-bold text-slate-900">
                {editingCliente ? 'Editar Cliente' : 'Novo Cliente'}
              </h2>
              <button onClick={handleCloseModal} className="p-1 hover:bg-slate-100 rounded-lg">
                <X size={24} />
              </button>
            </div>

            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Nome *
                </label>
                <input
                  type="text"
                  value={formData.nome}
                  onChange={(e) => setFormData({...formData, nome: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Nome completo"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  WhatsApp
                </label>
                <input
                  type="tel"
                  value={formData.whatsapp}
                  onChange={(e) => setFormData({...formData, whatsapp: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="(00) 00000-0000"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Endereço
                </label>
                <textarea
                  value={formData.endereco}
                  onChange={(e) => setFormData({...formData, endereco: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  rows="3"
                  placeholder="Endereço completo"
                />
              </div>
            </div>

            <div className="p-6 border-t border-slate-200 flex gap-3">
              <button
                onClick={handleCloseModal}
                className="flex-1 px-4 py-2 border border-slate-300 rounded-lg font-semibold hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                onClick={handleSave}
                disabled={saving}
                className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
              >
                <Check size={20} />
                {saving ? 'Salvando...' : 'Salvar'}
              </button>
            </div>
          </div>
        </div>
      )}

      <ConfirmModal
        isOpen={!!deleteConfirm}
        onClose={() => setDeleteConfirm(null)}
        onConfirm={() => handleDelete(deleteConfirm)}
        title="Excluir Cliente"
        message="Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita."
        confirmText="Excluir"
        cancelText="Cancelar"
      />
    </div>
  );
};

export default ClientesPage;