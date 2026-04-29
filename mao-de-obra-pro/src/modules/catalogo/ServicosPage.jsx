import React, { useState, useEffect } from 'react';
import { Plus, Search, Clock, Trash2, Edit2, X, Check, Package, Briefcase } from 'lucide-react';
import db from '../../database/db';
import { formatarTempo } from '../../core/calculadora';
import { useFinanceiro } from '../../hooks/useFinanceiro';

const ServicosPage = () => {
  const { config, profissao } = useFinanceiro();
  const [servicos, setServicos] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingServico, setEditingServico] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    tempoPadrao: '',
    categoria: ''
  });

  const categorias = ['Elétrica', 'Hidráulica', 'Climatização', 'Pintura', 'Construção', 'Acabamento', 'Demolição', 'Perfuração', 'Escavação', 'Manutenção', 'Outros'];

  useEffect(() => {
    if (profissao) {
      loadServicos();
    }
  }, [profissao]);

  const loadServicos = async () => {
    // Filtrar serviços apenas da profissão selecionada
    const allServicos = await db.servicos
      .where('profissaoId')
      .equals(profissao.id)
      .toArray();
    setServicos(allServicos.reverse());
  };

  const handleSave = async () => {
    if (!formData.nome.trim()) {
      alert('Nome do serviço é obrigatório');
      return;
    }
    if (!formData.tempoPadrao || formData.tempoPadrao <= 0) {
      alert('Tempo padrão é obrigatório e deve ser maior que zero');
      return;
    }

    try {
      const servicoData = {
        nome: formData.nome,
        tempoPadrao: parseInt(formData.tempoPadrao),
        categoria: formData.categoria || 'Geral',
        profissaoId: profissao.id // Vincula serviço à profissão atual
      };

      if (editingServico) {
        await db.servicos.update(editingServico.id, servicoData);
      } else {
        await db.servicos.add(servicoData);
      }
      await loadServicos();
      handleCloseModal();
    } catch (error) {
      console.error('Error saving service:', error);
      alert('Erro ao salvar serviço');
    }
  };

  const handleDelete = async (id) => {
    if (confirm('Tem certeza que deseja excluir este serviço?')) {
      await db.servicos.delete(id);
      await loadServicos();
    }
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setEditingServico(null);
    setFormData({ nome: '', tempoPadrao: '', categoria: '' });
  };

  const handleEdit = (servico) => {
    setEditingServico(servico);
    setFormData({
      nome: servico.nome,
      tempoPadrao: servico.tempoPadrao.toString(),
      categoria: servico.categoria || ''
    });
    setShowModal(true);
  };

  const filteredServicos = servicos.filter(s =>
    s.nome.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (s.categoria && s.categoria.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const getCategoriaColor = (categoria) => {
    const colors = {
      'Elétrica': 'bg-yellow-100 text-yellow-700',
      'Hidráulica': 'bg-blue-100 text-blue-700',
      'Climatização': 'bg-cyan-100 text-cyan-700',
      'Pintura': 'bg-purple-100 text-purple-700',
      'Construção': 'bg-orange-100 text-orange-700',
      'Demolição': 'bg-red-100 text-red-700',
      'Perfuração': 'bg-gray-100 text-gray-700',
      'Escavação': 'bg-amber-100 text-amber-700',
      'Acabamento': 'bg-indigo-100 text-indigo-700',
      'Manutenção': 'bg-green-100 text-green-700',
      'Outros': 'bg-slate-100 text-slate-700'
    };
    return colors[categoria] || colors['Outros'];
  };

  if (!profissao) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-3 text-slate-500">Carregando serviços da profissão...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4 animate-fade-in">
      {/* Header com profissão atual */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Catálogo de Serviços</h1>
          <div className="flex items-center gap-2 mt-1">
            <Briefcase size={16} className="text-blue-600" />
            <p className="text-slate-500">
              Serviços de <span className="font-semibold text-blue-600">{profissao.nome}</span>
            </p>
          </div>
        </div>
        <button
          onClick={() => setShowModal(true)}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2 hover:bg-blue-700 transition-colors w-full sm:w-auto justify-center"
        >
          <Plus size={20} />
          Novo Serviço
        </button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
        <input
          type="text"
          placeholder="Buscar serviço por nome ou categoria..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      {/* Services Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {filteredServicos.length === 0 ? (
          <div className="col-span-full bg-white rounded-xl p-8 text-center text-slate-500 border border-slate-200">
            <Package size={48} className="mx-auto mb-3 opacity-50" />
            <p>Nenhum serviço cadastrado para {profissao.nome}</p>
            <button
              onClick={() => setShowModal(true)}
              className="mt-3 text-blue-600 font-semibold hover:text-blue-700"
            >
              Adicionar primeiro serviço
            </button>
          </div>
        ) : (
          filteredServicos.map((servico) => (
            <div key={servico.id} className="bg-white rounded-xl p-4 shadow-sm border border-slate-200 hover:shadow-md transition-all">
              <div className="flex justify-between items-start">
                <div className="flex-1">
                  <div className="flex items-center gap-2 flex-wrap">
                    <h3 className="font-semibold text-slate-900">{servico.nome}</h3>
                    {servico.categoria && (
                      <span className={`text-xs px-2 py-1 rounded-full ${getCategoriaColor(servico.categoria)}`}>
                        {servico.categoria}
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2 mt-2 text-sm text-slate-600">
                    <Clock size={16} />
                    <span>Tempo padrão: {formatarTempo(servico.tempoPadrao)}</span>
                  </div>
                </div>
                <div className="flex gap-1">
                  <button
                    onClick={() => handleEdit(servico)}
                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  >
                    <Edit2 size={18} />
                  </button>
                  <button
                    onClick={() => handleDelete(servico.id)}
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

      {/* Modal - igual ao original, mas sem opção de profissaoId pois já é fixo */}
      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 animate-fade-in">
          <div className="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-slate-200 flex justify-between items-center">
              <h2 className="text-xl font-bold text-slate-900">
                {editingServico ? 'Editar Serviço' : 'Novo Serviço'}
              </h2>
              <button onClick={handleCloseModal} className="p-1 hover:bg-slate-100 rounded-lg">
                <X size={24} />
              </button>
            </div>

            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Nome do Serviço *
                </label>
                <input
                  type="text"
                  value={formData.nome}
                  onChange={(e) => setFormData({...formData, nome: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ex: Instalação de chuveiro"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Categoria
                </label>
                <select
                  value={formData.categoria}
                  onChange={(e) => setFormData({...formData, categoria: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Selecione uma categoria</option>
                  {categorias.map(cat => (
                    <option key={cat} value={cat}>{cat}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">
                  Tempo Padrão (minutos) *
                </label>
                <input
                  type="number"
                  value={formData.tempoPadrao}
                  onChange={(e) => setFormData({...formData, tempoPadrao: e.target.value})}
                  className="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Ex: 30"
                  min="1"
                />
                <p className="text-xs text-slate-500 mt-1">
                  Tempo médio que você leva para executar este serviço
                </p>
                <p className="text-xs text-blue-600 mt-1">
                  Este serviço será vinculado à profissão: {profissao.nome}
                </p>
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
                className="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
              >
                <Check size={20} />
                Salvar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ServicosPage;