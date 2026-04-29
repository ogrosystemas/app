import React, { useState, useEffect } from 'react';
import { DollarSign, Clock, TrendingUp, Car, Save, AlertCircle } from 'lucide-react';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { formatarMoeda, calcularValorMinuto } from '../../core/calculadora';

const ConfiguracoesPage = () => {
  const { config, updateAllConfig, loading, refresh } = useFinanceiro();
  const [formData, setFormData] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    margemReserva: 0.2,
    taxaDeslocamento: 50
  });
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState(false);

  useEffect(() => {
    if (config) {
      setFormData({
        metaSalarial: config.metaSalarial || 5000,
        horasTrabalhadas: config.horasTrabalhadas || 160,
        margemReserva: config.margemReserva || 0.2,
        taxaDeslocamento: config.taxaDeslocamento || 50
      });
    }
  }, [config]);

  const handleSave = async () => {
    setSaving(true);
    const success = await updateAllConfig(formData);
    if (success) {
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
    }
    setSaving(false);
  };

  const valorMinutoAtual = calcularValorMinuto(
    formData.metaSalarial,
    formData.horasTrabalhadas
  );

  const calcularValorHora = () => {
    return formData.metaSalarial / formData.horasTrabalhadas;
  };

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div>
        <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Configurações Financeiras</h1>
        <p className="text-slate-500 mt-1">Defina seus parâmetros de precificação</p>
      </div>

      {/* Current Value Card */}
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <div className="flex justify-between items-start">
          <div>
            <p className="text-sm opacity-90">Seu valor atual por minuto</p>
            <p className="text-3xl font-bold mt-1">{formatarMoeda(valorMinutoAtual)}</p>
            <p className="text-sm opacity-90 mt-2">
              Equivalente a {formatarMoeda(calcularValorHora())}/hora
            </p>
          </div>
          <DollarSign size={32} className="opacity-80" />
        </div>
      </div>

      {/* Forms */}
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-6 border-b border-slate-200">
          <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
            <TrendingUp size={20} className="text-blue-600" />
            Metas e Objetivos
          </h2>
        </div>
        <div className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Meta Salarial Mensal
            </label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500">R$</span>
              <input
                type="number"
                value={formData.metaSalarial}
                onChange={(e) => setFormData({...formData, metaSalarial: parseFloat(e.target.value) || 0})}
                className="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0"
              />
            </div>
            <p className="text-xs text-slate-500 mt-1">Quanto você quer ganhar por mês?</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Horas Trabalhadas por Mês
            </label>
            <div className="relative">
              <Clock className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
              <input
                type="number"
                value={formData.horasTrabalhadas}
                onChange={(e) => setFormData({...formData, horasTrabalhadas: parseFloat(e.target.value) || 0})}
                className="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0"
              />
            </div>
            <p className="text-xs text-slate-500 mt-1">Quantas horas você trabalha por mês?</p>
          </div>
        </div>
      </div>

      {/* Margins and Costs */}
      <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div className="p-6 border-b border-slate-200">
          <h2 className="text-lg font-semibold text-slate-900 flex items-center gap-2">
            <AlertCircle size={20} className="text-blue-600" />
            Margens e Custos
          </h2>
        </div>
        <div className="p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Margem de Reserva
            </label>
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
            <p className="text-xs text-slate-500 mt-1">
              Margem para lucro e imprevistos (recomendado: 15-30%)
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Taxa de Deslocamento
            </label>
            <div className="relative">
              <Car className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" size={20} />
              <span className="absolute left-12 top-1/2 transform -translate-y-1/2 text-slate-500">R$</span>
              <input
                type="number"
                value={formData.taxaDeslocamento}
                onChange={(e) => setFormData({...formData, taxaDeslocamento: parseFloat(e.target.value) || 0})}
                className="w-full pl-20 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0"
              />
            </div>
            <p className="text-xs text-slate-500 mt-1">
              Valor cobrado por deslocamento até o local do serviço
            </p>
          </div>
        </div>
      </div>

      {/* Preview Table */}
      <div className="bg-slate-50 rounded-xl p-4 border border-slate-200">
        <h3 className="font-semibold text-slate-900 mb-3">Prévia de Preços</h3>
        <div className="space-y-2 text-sm">
          <div className="flex justify-between py-2 border-b border-slate-200">
            <span className="text-slate-600">Serviço de 30min (Normal)</span>
            <span className="font-semibold text-slate-900">
              {formatarMoeda((30 * valorMinutoAtual * 1.0) / (1 - formData.margemReserva))}
            </span>
          </div>
          <div className="flex justify-between py-2 border-b border-slate-200">
            <span className="text-slate-600">Serviço de 1h (Médio)</span>
            <span className="font-semibold text-slate-900">
              {formatarMoeda((60 * valorMinutoAtual * 1.3) / (1 - formData.margemReserva))}
            </span>
          </div>
          <div className="flex justify-between py-2">
            <span className="text-slate-600">Serviço de 2h (Alto)</span>
            <span className="font-semibold text-slate-900">
              {formatarMoeda((120 * valorMinutoAtual * 1.6) / (1 - formData.margemReserva))}
            </span>
          </div>
        </div>
      </div>

      {/* Save Button */}
      <button
        onClick={handleSave}
        disabled={saving || loading}
        className="w-full bg-blue-600 text-white py-4 rounded-xl font-semibold flex items-center justify-center gap-2 hover:bg-blue-700 transition-colors disabled:opacity-50"
      >
        {saving ? (
          'Salvando...'
        ) : (
          <>
            <Save size={20} />
            Salvar Configurações
          </>
        )}
      </button>

      {/* Success Message */}
      {success && (
        <div className="fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg animate-fade-in z-50">
          Configurações salvas com sucesso!
        </div>
      )}
    </div>
  );
};

export default ConfiguracoesPage;