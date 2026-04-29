import React, { useState, useEffect } from 'react';
import {
  DollarSign,
  Clock,
  TrendingUp,
  Car,
  Save,
  AlertCircle,
  Briefcase,
  Settings,
  Calendar,
  Wallet,
  BarChart3
} from 'lucide-react';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { formatarMoeda, calcularValorMinuto } from '../../core/calculadora';
import ProfissaoSelector from '../../components/ProfissaoSelector';
import db from '../../database/db';

const ConfiguracoesPage = () => {
  const { config, profissao, updateAllConfig, selecionarProfissao, loading, refresh } = useFinanceiro();
  const [activeMenu, setActiveMenu] = useState('config');
  const [formData, setFormData] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    margemReserva: 0.2,
    taxaDeslocamento: 50
  });
  const [validadePadrao, setValidadePadrao] = useState(30);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);

  // Estado do caixa
  const [lancamentos, setLancamentos] = useState([]);
  const [showLancamentoModal, setShowLancamentoModal] = useState(false);
  const [novoLancamento, setNovoLancamento] = useState({
    tipo: 'entrada',
    categoria: '',
    descricao: '',
    valor: ''
  });
  const [totalEntradas, setTotalEntradas] = useState(0);
  const [totalSaidas, setTotalSaidas] = useState(0);
  const [saldo, setSaldo] = useState(0);

  useEffect(() => {
    if (config) {
      setFormData({
        metaSalarial: config.metaSalarial || 5000,
        horasTrabalhadas: config.horasTrabalhadas || 160,
        margemReserva: config.margemReserva || 0.2,
        taxaDeslocamento: config.taxaDeslocamento || 50
      });
    }
    loadValidadePadrao();
    loadLancamentos();
  }, [config]);

  useEffect(() => {
    const entradas = lancamentos.filter(l => l.tipo === 'entrada').reduce((sum, l) => sum + l.valor, 0);
    const saidas = lancamentos.filter(l => l.tipo === 'saida').reduce((sum, l) => sum + l.valor, 0);
    setTotalEntradas(entradas);
    setTotalSaidas(saidas);
    setSaldo(entradas - saidas);
  }, [lancamentos]);

  const loadValidadePadrao = async () => {
    const configValidade = await db.config.where('chave').equals('validadePadrao').first();
    if (configValidade) setValidadePadrao(configValidade.valor);
  };

  const saveValidadePadrao = async () => {
    await db.config.where('chave').equals('validadePadrao').modify({ valor: validadePadrao });
    alert('Validade padrão salva!');
  };

  const loadLancamentos = async () => {
    const all = await db.caixa.orderBy('data').reverse().toArray();
    setLancamentos(all);
  };

  const addLancamento = async () => {
    if (!novoLancamento.descricao || !novoLancamento.valor) {
      alert('Preencha descrição e valor');
      return;
    }
    await db.caixa.add({
      data: new Date().toISOString(),
      tipo: novoLancamento.tipo,
      categoria: novoLancamento.categoria,
      descricao: novoLancamento.descricao,
      valor: parseFloat(novoLancamento.valor),
      orcamentoId: null
    });
    setNovoLancamento({ tipo: 'entrada', categoria: '', descricao: '', valor: '' });
    setShowLancamentoModal(false);
    await loadLancamentos();
  };

  const deleteLancamento = async (id) => {
    if (confirm('Excluir este lançamento?')) {
      await db.caixa.delete(id);
      await loadLancamentos();
    }
  };

  const handleSave = async () => {
    setSaving(true);
    const success = await updateAllConfig(formData);
    if (success) {
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    }
    setSaving(false);
  };

  const menus = [
    { id: 'config', label: 'Configurações', icon: Settings },
    { id: 'profissao', label: 'Perfil Profissional', icon: Briefcase },
    { id: 'caixa', label: 'Controle de Caixa', icon: Wallet }
  ];

  return (
    <div className="space-y-6 animate-fade-in pb-20">
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Financeiro</h1>
        <p className="text-slate-500 mt-1">Gerencie suas finanças</p>
      </div>

      {/* Menu de navegação com scroll horizontal */}
      <div className="overflow-x-auto -mx-4 px-4 scrollbar-hide">
        <div className="flex gap-2 border-b border-slate-200 min-w-max">
          {menus.map(menu => {
            const Icon = menu.icon;
            return (
              <button
                key={menu.id}
                onClick={() => setActiveMenu(menu.id)}
                className={`px-4 py-2 font-semibold transition-all whitespace-nowrap ${
                  activeMenu === menu.id
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

      {/* Configurações Gerais */}
      {activeMenu === 'config' && (
        <>
          <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-sm opacity-90">Seu valor atual por minuto</p>
                <p className="text-3xl font-bold mt-1">{formatarMoeda(config.valorMinuto)}</p>
                <p className="text-sm opacity-90 mt-2">Com base na profissão e meta salarial</p>
                {profissao && (
                  <p className="text-xs opacity-80 mt-1">Risco: {Math.round((profissao.riscoBase - 1) * 100)}%</p>
                )}
              </div>
              <DollarSign size={32} className="opacity-80" />
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-6 border-b border-slate-200">
              <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                <TrendingUp size={20} className="text-blue-600" />
                Metas e Objetivos
              </h2>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Meta Salarial Mensal</label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500">R$</span>
                  <input
                    type="number"
                    value={formData.metaSalarial}
                    onChange={(e) => setFormData({...formData, metaSalarial: parseFloat(e.target.value) || 0})}
                    className="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Horas Trabalhadas por Mês</label>
                <input
                  type="number"
                  value={formData.horasTrabalhadas}
                  onChange={(e) => setFormData({...formData, horasTrabalhadas: parseFloat(e.target.value) || 0})}
                  className="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-6 border-b border-slate-200">
              <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                <AlertCircle size={20} className="text-blue-600" />
                Margens e Custos
              </h2>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Margem de Reserva</label>
                <div className="flex items-center gap-3">
                  <input
                    type="range"
                    min="0"
                    max="0.5"
                    step="0.01"
                    value={formData.margemReserva}
                    onChange={(e) => setFormData({...formData, margemReserva: parseFloat(e.target.value)})}
                    className="flex-1"
                  />
                  <span className="text-lg font-semibold text-blue-600 min-w-[60px]">
                    {Math.round(formData.margemReserva * 100)}%
                  </span>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Taxa de Deslocamento</label>
                <div className="relative">
                  <Car className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
                  <span className="absolute left-12 top-1/2 transform -translate-y-1/2 text-slate-500">R$</span>
                  <input
                    type="number"
                    value={formData.taxaDeslocamento}
                    onChange={(e) => setFormData({...formData, taxaDeslocamento: parseFloat(e.target.value) || 0})}
                    className="w-full pl-20 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-6 border-b border-slate-200">
              <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
                <Calendar size={20} className="text-blue-600" />
                Validade Padrão
              </h2>
            </div>
            <div className="p-6">
              <div className="flex gap-3">
                <select
                  value={validadePadrao}
                  onChange={(e) => setValidadePadrao(parseInt(e.target.value))}
                  className="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value={1}>1 dia</option>
                  <option value={5}>5 dias</option>
                  <option value={15}>15 dias</option>
                  <option value={30}>30 dias</option>
                </select>
                <button
                  onClick={saveValidadePadrao}
                  className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold"
                >
                  Salvar
                </button>
              </div>
              <p className="text-xs text-slate-500 mt-2">Validade padrão para novos orçamentos</p>
            </div>
          </div>

          <button
            onClick={handleSave}
            disabled={saving || loading}
            className="w-full bg-blue-600 text-white py-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {saving ? 'Salvando...' : <><Save size={20} /> Salvar Configurações</>}
          </button>
        </>
      )}

      {/* Perfil Profissional */}
      {activeMenu === 'profissao' && (
        <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <div className="p-6 border-b border-slate-200">
            <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
              <Briefcase size={20} className="text-blue-600" />
              Perfil Profissional
            </h2>
          </div>
          <div className="p-6">
            <ProfissaoSelector
              onSelect={async (prof) => {
                await selecionarProfissao(prof);
                await refresh();
                // Forçar atualização dos valores no formData também
                const novaConfig = await db.config.toArray();
                const configObj = {};
                novaConfig.forEach(c => { configObj[c.chave] = c.valor; });
                setFormData({
                  metaSalarial: configObj.metaSalarial || 5000,
                  horasTrabalhadas: configObj.horasTrabalhadas || 160,
                  margemReserva: configObj.margemReserva || 0.2,
                  taxaDeslocamento: configObj.taxaDeslocamento || 50
                });
              }}
              selectedSlug={config.profissaoSelecionada}
            />
            {profissao && (
              <div className="mt-4 p-3 bg-blue-50 rounded-lg">
                <p className="text-sm text-blue-800">
                  <strong>Multiplicador de risco:</strong> {profissao.riscoBase}x
                  <br />
                  <strong>Custo ferramental mensal:</strong> {formatarMoeda(profissao.custoFerramental)}
                </p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Controle de Caixa */}
      {activeMenu === 'caixa' && (
        <>
          <div className="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-sm opacity-90">Saldo Atual</p>
                <p className="text-3xl font-bold mt-1">{formatarMoeda(saldo)}</p>
              </div>
              <Wallet size={32} className="opacity-80" />
            </div>
            <div className="flex justify-between mt-4">
              <div>
                <p className="text-xs opacity-80">Entradas</p>
                <p className="text-lg font-semibold">{formatarMoeda(totalEntradas)}</p>
              </div>
              <div>
                <p className="text-xs opacity-80">Saídas</p>
                <p className="text-lg font-semibold">{formatarMoeda(totalSaidas)}</p>
              </div>
            </div>
          </div>

          <button
            onClick={() => setShowLancamentoModal(true)}
            className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
          >
            + Novo Lançamento
          </button>

          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-4 bg-slate-50 border-b border-slate-200">
              <h3 className="font-semibold text-slate-900">Últimos Lançamentos</h3>
            </div>
            <div className="divide-y divide-slate-200 max-h-96 overflow-y-auto">
              {lancamentos.length === 0 ? (
                <div className="p-8 text-center text-slate-500">
                  <BarChart3 size={48} className="mx-auto mb-3 opacity-50" />
                  <p>Nenhum lançamento registrado</p>
                </div>
              ) : (
                lancamentos.map(l => (
                  <div key={l.id} className="p-4 flex justify-between items-center">
                    <div>
                      <p className="font-medium text-slate-900">{l.descricao}</p>
                      <p className="text-xs text-slate-500">{new Date(l.data).toLocaleDateString('pt-BR')}</p>
                    </div>
                    <div className="text-right">
                      <p className={`font-bold ${l.tipo === 'entrada' ? 'text-green-600' : 'text-red-600'}`}>
                        {l.tipo === 'entrada' ? '+' : '-'} {formatarMoeda(l.valor)}
                      </p>
                      <button
                        onClick={() => deleteLancamento(l.id)}
                        className="text-xs text-red-500 mt-1"
                      >
                        Excluir
                      </button>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </>
      )}

      {success && (
        <div className="fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg animate-fade-in z-50">
          Configurações salvas com sucesso!
        </div>
      )}

      {/* Modal Novo Lançamento */}
      {showLancamentoModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 animate-fade-in">
          <div className="bg-white rounded-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-slate-900 mb-4">Novo Lançamento</h3>
            <div className="space-y-3">
              <div className="flex gap-3">
                <button
                  onClick={() => setNovoLancamento({...novoLancamento, tipo: 'entrada'})}
                  className={`flex-1 py-2 rounded-lg border ${novoLancamento.tipo === 'entrada' ? 'bg-green-500 text-white border-green-500' : 'border-slate-300'}`}
                >
                  Entrada
                </button>
                <button
                  onClick={() => setNovoLancamento({...novoLancamento, tipo: 'saida'})}
                  className={`flex-1 py-2 rounded-lg border ${novoLancamento.tipo === 'saida' ? 'bg-red-500 text-white border-red-500' : 'border-slate-300'}`}
                >
                  Saída
                </button>
              </div>
              <select
                value={novoLancamento.categoria}
                onChange={(e) => setNovoLancamento({...novoLancamento, categoria: e.target.value})}
                className="w-full px-4 py-2 border border-slate-300 rounded-lg"
              >
                <option value="">Selecione uma categoria</option>
                <option value="material">Material</option>
                <option value="ferramenta">Ferramenta</option>
                <option value="transporte">Transporte</option>
                <option value="alimentacao">Alimentação</option>
                <option value="outros">Outros</option>
              </select>
              <input
                type="text"
                placeholder="Descrição"
                value={novoLancamento.descricao}
                onChange={(e) => setNovoLancamento({...novoLancamento, descricao: e.target.value})}
                className="w-full px-4 py-2 border rounded-lg"
              />
              <input
                type="number"
                step="0.01"
                placeholder="Valor"
                value={novoLancamento.valor}
                onChange={(e) => setNovoLancamento({...novoLancamento, valor: e.target.value})}
                className="w-full px-4 py-2 border rounded-lg"
              />
            </div>
            <div className="flex gap-3 mt-6">
              <button onClick={() => setShowLancamentoModal(false)} className="flex-1 border py-2 rounded-lg">Cancelar</button>
              <button onClick={addLancamento} className="flex-1 bg-blue-600 text-white py-2 rounded-lg">Salvar</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ConfiguracoesPage;