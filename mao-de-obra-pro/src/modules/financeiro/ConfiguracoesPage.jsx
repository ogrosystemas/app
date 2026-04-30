import React, { useState, useEffect } from 'react';
import { DollarSign, Clock, TrendingUp, Car, Save, AlertCircle, Briefcase, Settings, Calendar, Wallet, BarChart3 } from 'lucide-react';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { formatarMoeda } from '../../core/calculadora';
import ProfissaoSelector from '../../components/ProfissaoSelector';
import db from '../../database/db';
import ConfirmModal from '../../components/ConfirmModal';
import { useToast } from '../../context/ToastContext';

const ConfiguracoesPage = () => {
  const { config, profissao, updateAllConfig, selecionarProfissao, loading, refresh } = useFinanceiro();
  const { showToast } = useToast();
  const [activeMenu, setActiveMenu] = useState('config');
  const [formData, setFormData] = useState({ metaSalarial: 5000, horasTrabalhadas: 160, margemReserva: 0.2, taxaDeslocamento: 50 });
  const [validadePadrao, setValidadePadrao] = useState(30);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [lancamentos, setLancamentos] = useState([]);
  const [showLancamentoModal, setShowLancamentoModal] = useState(false);
  const [novoLancamento, setNovoLancamento] = useState({ tipo: 'entrada', categoria: '', descricao: '', valor: '' });

  useEffect(() => {
    if (config) setFormData({ metaSalarial: config.metaSalarial || 5000, horasTrabalhadas: config.horasTrabalhadas || 160, margemReserva: config.margemReserva || 0.2, taxaDeslocamento: config.taxaDeslocamento || 50 });
    loadValidadePadrao();
    loadLancamentos();
  }, [config]);

  const loadValidadePadrao = async () => { const c = await db.config.where('chave').equals('validadePadrao').first(); if(c) setValidadePadrao(c.valor); };
  const saveValidadePadrao = async () => { await db.config.where('chave').equals('validadePadrao').modify({ valor: validadePadrao }); showToast('Validade padrão salva!', 'success'); };
  const loadLancamentos = async () => { const all = await db.caixa.orderBy('data').reverse().toArray(); setLancamentos(all); };
  const addLancamento = async () => { if (!novoLancamento.descricao || !novoLancamento.valor) { showToast('Preencha descrição e valor', 'error'); return; } await db.caixa.add({ data: new Date().toISOString(), tipo: novoLancamento.tipo, categoria: novoLancamento.categoria, descricao: novoLancamento.descricao, valor: parseFloat(novoLancamento.valor), orcamentoId: null }); setNovoLancamento({ tipo: 'entrada', categoria: '', descricao: '', valor: '' }); setShowLancamentoModal(false); await loadLancamentos(); showToast('Lançamento adicionado!', 'success'); };
  const deleteLancamento = async (id) => { await db.caixa.delete(id); await loadLancamentos(); setDeleteConfirm(null); showToast('Lançamento excluído!', 'success'); };
  const handleSave = async () => { setSaving(true); const ok = await updateAllConfig(formData); if (ok) { setSuccess(true); setTimeout(()=>setSuccess(false),3000); showToast('Configurações salvas!', 'success'); } setSaving(false); };

  const totalEntradas = lancamentos.filter(l=>l.tipo==='entrada').reduce((s,l)=>s+l.valor,0);
  const totalSaidas = lancamentos.filter(l=>l.tipo==='saida').reduce((s,l)=>s+l.valor,0);
  const saldo = totalEntradas - totalSaidas;

  const menus = [
    { id: 'config', label: 'Configurações', icon: Settings },
    { id: 'profissao', label: 'Perfil Profissional', icon: Briefcase },
    { id: 'caixa', label: 'Controle de Caixa', icon: Wallet }
  ];

  return (
    <div className="space-y-6 pb-20">
      <div><h1 className="text-2xl font-bold">Financeiro</h1><p className="text-slate-500">Gerencie suas finanças</p></div>

      {/* MENU CORRIGIDO – sem borda cinza, apenas sublinhado azul no ativo */}
      <div className="overflow-x-auto -mx-4 px-4">
        <div className="flex gap-2 min-w-max">
          {menus.map(menu => {
            const Icon = menu.icon;
            const isActive = activeMenu === menu.id;
            return (
              <button
                key={menu.id}
                onClick={() => setActiveMenu(menu.id)}
                className={`px-4 py-2 font-semibold whitespace-nowrap transition-all ${
                  isActive
                    ? 'text-blue-600 border-b-2 border-blue-600'
                    : 'text-slate-500 hover:text-slate-700'
                }`}
              >
                <div className="flex items-center gap-2">
                  <Icon size={18} />
                  <span>{menu.label}</span>
                </div>
              </button>
            );
          })}
        </div>
      </div>

      {/* Conteúdo (config, profissao, caixa) – inalterado */}
      {activeMenu === 'config' && (
        <>
          <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div><p className="text-sm">Seu valor por minuto</p><p className="text-3xl font-bold">{formatarMoeda(config.valorMinuto)}</p><p className="text-sm">{config.valorMinuto > 0 ? `Risco: ${Math.round((profissao?.riscoBase-1)*100)}%` : ''}</p></div>
          </div>
          <div className="bg-white rounded-xl border p-6"><h2 className="text-lg font-semibold flex gap-2"><TrendingUp size={20}/> Metas</h2><div><label>Meta Salarial</label><input type="number" value={formData.metaSalarial} onChange={e=>setFormData({...formData,metaSalarial:parseFloat(e.target.value)||0})} className="w-full border rounded p-2"/></div><div><label>Horas/Mês</label><input type="number" value={formData.horasTrabalhadas} onChange={e=>setFormData({...formData,horasTrabalhadas:parseFloat(e.target.value)||0})} className="w-full border rounded p-2"/></div></div>
          <div className="bg-white rounded-xl border p-6"><h2 className="text-lg font-semibold flex gap-2"><AlertCircle size={20}/> Margens</h2><div><label>Margem</label><input type="range" min="0" max="0.5" step="0.01" value={formData.margemReserva} onChange={e=>setFormData({...formData,margemReserva:parseFloat(e.target.value)})}/><span>{Math.round(formData.margemReserva*100)}%</span></div><div><label>Deslocamento</label><input type="number" value={formData.taxaDeslocamento} onChange={e=>setFormData({...formData,taxaDeslocamento:parseFloat(e.target.value)||0})} className="w-full border rounded p-2"/></div></div>
          <div className="bg-white rounded-xl border p-6"><h2 className="text-lg font-semibold flex gap-2"><Calendar size={20}/> Validade Padrão</h2><div className="flex gap-2 flex-wrap"><select value={validadePadrao} onChange={e=>setValidadePadrao(parseInt(e.target.value))} className="border rounded p-2"><option value={1}>1 dia</option><option value={5}>5 dias</option><option value={15}>15 dias</option><option value={30}>30 dias</option></select><button onClick={saveValidadePadrao} className="bg-blue-600 text-white px-4 py-2 rounded">Salvar</button></div></div>
          <button onClick={handleSave} disabled={saving||loading} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold">{saving?'Salvando...':'Salvar Configurações'}</button>
        </>
      )}

      {activeMenu === 'profissao' && (
        <div className="bg-white rounded-xl border p-6"><h2 className="text-lg font-semibold flex gap-2"><Briefcase size={20}/> Perfil Profissional</h2><ProfissaoSelector onSelect={async (prof)=>{await selecionarProfissao(prof); await refresh(); const novaConfig = await db.config.toArray(); const obj={}; novaConfig.forEach(c=>obj[c.chave]=c.valor); setFormData({metaSalarial:obj.metaSalarial||5000, horasTrabalhadas:obj.horasTrabalhadas||160, margemReserva:obj.margemReserva||0.2, taxaDeslocamento:obj.taxaDeslocamento||50}); showToast(`Perfil alterado para ${prof.nome}`, 'success');}} selectedSlug={config.profissaoSelecionada}/>{profissao && <div className="mt-4 p-3 bg-blue-50 rounded"><p className="text-sm"><strong>Multiplicador de risco:</strong> {profissao.riscoBase}x<br/><strong>Custo ferramental:</strong> {formatarMoeda(profissao.custoFerramental)}/mês</p></div>}</div>
      )}

      {activeMenu === 'caixa' && (
        <>
          <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white"><div><p>Saldo Atual</p><p className="text-3xl font-bold">{formatarMoeda(saldo)}</p></div><div className="flex justify-between mt-4"><div><p>Entradas</p><p className="text-lg font-bold">{formatarMoeda(totalEntradas)}</p></div><div><p>Saídas</p><p className="text-lg font-bold">{formatarMoeda(totalSaidas)}</p></div></div></div>
          <button onClick={()=>setShowLancamentoModal(true)} className="w-full bg-blue-600 text-white py-3 rounded-lg">+ Novo Lançamento</button>
          <div className="bg-white rounded-xl border"><div className="p-4 bg-slate-50 border-b">Últimos Lançamentos</div><div className="divide-y">{lancamentos.length===0?<div className="p-8 text-center">Nenhum lançamento</div>:lancamentos.map(l=><div key={l.id} className="p-4 flex justify-between"><div><p className="font-medium">{l.descricao}</p><p className="text-xs">{new Date(l.data).toLocaleDateString()}</p></div><div className="text-right"><p className={`font-bold ${l.tipo==='entrada'?'text-green-600':'text-red-600'}`}>{l.tipo==='entrada'?'+':'-'} {formatarMoeda(l.valor)}</p><button onClick={()=>setDeleteConfirm(l.id)} className="text-xs text-red-500">Excluir</button></div></div>)}</div></div>
        </>
      )}

      {success && <div className="fixed bottom-20 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">Configurações salvas!</div>}
      {showLancamentoModal && <div className="fixed inset-0 bg-black/50 flex justify-center items-center p-4"><div className="bg-white rounded-xl max-w-md w-full p-6"><h3 className="text-xl font-bold mb-4">Novo Lançamento</h3><div className="flex gap-3 mb-3"><button onClick={()=>setNovoLancamento({...novoLancamento,tipo:'entrada'})} className={`flex-1 py-2 border rounded ${novoLancamento.tipo==='entrada'?'bg-green-500 text-white':'border-slate-300'}`}>Entrada</button><button onClick={()=>setNovoLancamento({...novoLancamento,tipo:'saida'})} className={`flex-1 py-2 border rounded ${novoLancamento.tipo==='saida'?'bg-red-500 text-white':'border-slate-300'}`}>Saída</button></div><select value={novoLancamento.categoria} onChange={e=>setNovoLancamento({...novoLancamento,categoria:e.target.value})} className="w-full border rounded p-2 mb-3"><option value="">Categoria</option><option>Material</option><option>Ferramenta</option><option>Transporte</option><option>Alimentação</option><option>Outros</option></select><input type="text" placeholder="Descrição" value={novoLancamento.descricao} onChange={e=>setNovoLancamento({...novoLancamento,descricao:e.target.value})} className="w-full border rounded p-2 mb-3"/><input type="number" step="0.01" placeholder="Valor" value={novoLancamento.valor} onChange={e=>setNovoLancamento({...novoLancamento,valor:e.target.value})} className="w-full border rounded p-2 mb-4"/><div className="flex gap-3"><button onClick={()=>setShowLancamentoModal(false)} className="flex-1 border py-2 rounded">Cancelar</button><button onClick={addLancamento} className="flex-1 bg-blue-600 text-white py-2 rounded">Salvar</button></div></div></div>}
      <ConfirmModal isOpen={!!deleteConfirm} onClose={()=>setDeleteConfirm(null)} onConfirm={()=>deleteLancamento(deleteConfirm)} title="Excluir" message="Tem certeza?" confirmText="Excluir"/>
    </div>
  );
};

export default ConfiguracoesPage;