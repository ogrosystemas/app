import React, { useState, useEffect } from 'react';
import { Plus, Search, Clock, Trash2, Edit2, X, Check, Package, Briefcase, DollarSign } from 'lucide-react';
import db from '../../database/db';
import { formatarTempo, formatarMoeda } from '../../core/calculadora';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import ConfirmModal from '../../components/ConfirmModal';
import { useToast } from '../../context/ToastContext';

const ServicosPage = () => {
  const { config, profissao } = useFinanceiro();
  const { showToast } = useToast();
  const [servicos, setServicos] = useState([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [editingServico, setEditingServico] = useState(null);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [formData, setFormData] = useState({
    nome: '',
    tempoPadrao: '',
    categoria: '',
    precoFixo: ''
  });

  const categorias = ['Elétrica', 'Hidráulica', 'Climatização', 'Pintura', 'Construção', 'Acabamento', 'Demolição', 'Perfuração', 'Escavação', 'Manutenção', 'Outros'];

  useEffect(() => {
    if (profissao) loadServicos();
  }, [profissao]);

  const loadServicos = async () => {
    const allServicos = await db.servicos.where('profissaoId').equals(profissao.id).toArray();
    setServicos(allServicos.reverse());
  };

  const handleSave = async () => {
    if (!formData.nome.trim()) {
      showToast('Nome do serviço é obrigatório', 'error');
      return;
    }
    if (!formData.tempoPadrao || formData.tempoPadrao <= 0) {
      showToast('Tempo padrão deve ser maior que zero', 'error');
      return;
    }
    try {
      const servicoData = {
        nome: formData.nome,
        tempoPadrao: parseInt(formData.tempoPadrao),
        categoria: formData.categoria || 'Geral',
        profissaoId: profissao.id,
        precoFixo: formData.precoFixo ? parseFloat(formData.precoFixo) : null
      };
      if (editingServico) {
        await db.servicos.update(editingServico.id, servicoData);
        showToast('Serviço atualizado!', 'success');
      } else {
        await db.servicos.add(servicoData);
        showToast('Serviço adicionado!', 'success');
      }
      await loadServicos();
      setShowModal(false);
      setFormData({ nome: '', tempoPadrao: '', categoria: '', precoFixo: '' });
      setEditingServico(null);
    } catch (error) {
      console.error(error);
      showToast('Erro ao salvar serviço', 'error');
    }
  };

  const handleDelete = async (id) => {
    await db.servicos.delete(id);
    await loadServicos();
    setDeleteConfirm(null);
    showToast('Serviço excluído!', 'success');
  };

  const handleEdit = (servico) => {
    setEditingServico(servico);
    setFormData({
      nome: servico.nome,
      tempoPadrao: servico.tempoPadrao.toString(),
      categoria: servico.categoria || '',
      precoFixo: servico.precoFixo ? servico.precoFixo.toString() : ''
    });
    setShowModal(true);
  };

  const filteredServicos = servicos.filter(s => s.nome.toLowerCase().includes(searchTerm.toLowerCase()) || (s.categoria && s.categoria.toLowerCase().includes(searchTerm.toLowerCase())));

  if (!profissao) return <div className="flex justify-center p-8"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>;

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <div><h1 className="text-2xl font-bold">Catálogo de Serviços</h1><p className="text-slate-500">Serviços de <span className="font-semibold text-blue-600">{profissao.nome}</span></p></div>
        <button onClick={() => setShowModal(true)} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"><Plus size={20}/> Novo Serviço</button>
      </div>
      <div className="relative"><Search className="absolute left-3 top-3 text-slate-400"/><input type="text" placeholder="Buscar..." value={searchTerm} onChange={e=>setSearchTerm(e.target.value)} className="w-full pl-10 pr-4 py-2 border rounded"/></div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {filteredServicos.length === 0 ? (
          <div className="col-span-full text-center p-8">Nenhum serviço</div>
        ) : (
          filteredServicos.map(servico => (
            <div key={servico.id} className="bg-white rounded-xl p-4 border shadow-sm">
              <div className="flex justify-between">
                <div><h3 className="font-semibold">{servico.nome}</h3><p className="text-sm text-slate-500">{servico.categoria}</p><p className="text-sm">{formatarTempo(servico.tempoPadrao)}</p>{servico.precoFixo && <p className="text-sm text-green-600">Preço fixo: {formatarMoeda(servico.precoFixo)}</p>}</div>
                <div className="flex gap-1"><button onClick={()=>handleEdit(servico)} className="text-blue-600"><Edit2 size={18}/></button><button onClick={()=>setDeleteConfirm(servico.id)} className="text-red-600"><Trash2 size={18}/></button></div>
              </div>
            </div>
          ))
        )}
      </div>
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex justify-center items-center p-4">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h2 className="text-xl font-bold mb-4">{editingServico ? 'Editar' : 'Novo'} Serviço</h2>
            <input value={formData.nome} onChange={e=>setFormData({...formData,nome:e.target.value})} placeholder="Nome" className="w-full border p-2 rounded mb-2"/>
            <select value={formData.categoria} onChange={e=>setFormData({...formData,categoria:e.target.value})} className="w-full border p-2 rounded mb-2"><option value="">Categoria</option>{categorias.map(c=><option key={c}>{c}</option>)}</select>
            <input type="number" value={formData.tempoPadrao} onChange={e=>setFormData({...formData,tempoPadrao:e.target.value})} placeholder="Tempo (min)" className="w-full border p-2 rounded mb-2"/>
            <input type="number" step="0.01" value={formData.precoFixo} onChange={e=>setFormData({...formData,precoFixo:e.target.value})} placeholder="Preço fixo (opcional)" className="w-full border p-2 rounded mb-4"/>
            <div className="flex gap-2"><button onClick={()=>setShowModal(false)} className="flex-1 border py-2 rounded">Cancelar</button><button onClick={handleSave} className="flex-1 bg-blue-600 text-white py-2 rounded">Salvar</button></div>
          </div>
        </div>
      )}
      <ConfirmModal isOpen={!!deleteConfirm} onClose={()=>setDeleteConfirm(null)} onConfirm={()=>handleDelete(deleteConfirm)} title="Excluir" message="Tem certeza?" confirmText="Excluir"/>
    </div>
  );
};
export default ServicosPage;