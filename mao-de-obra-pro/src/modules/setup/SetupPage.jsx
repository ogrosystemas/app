import React, { useState, useEffect } from 'react';
import { Briefcase, TrendingUp, Car, Save, ArrowRight, Zap, UserPlus } from 'lucide-react';
import ProfissaoSelector from '../../components/ProfissaoSelector';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { formatarMoeda } from '../../core/calculadora';
import db from '../../database/db';

const SetupPage = ({ onComplete }) => {
  const { config, profissao, selecionarProfissao, updateAllConfig, loading } = useFinanceiro();
  const [step, setStep] = useState(1);
  const [selectedProfissao, setSelectedProfissao] = useState(null);
  const [formData, setFormData] = useState({
    metaSalarial: 5000,
    horasTrabalhadas: 160,
    taxaDeslocamento: 50
  });

  useEffect(() => {
    if (config) {
      setFormData({
        metaSalarial: config.metaSalarial || 5000,
        horasTrabalhadas: config.horasTrabalhadas || 160,
        taxaDeslocamento: config.taxaDeslocamento || 50
      });
    }
  }, [config]);

  const handleSelectProfissao = async (prof) => {
    setSelectedProfissao(prof);
    await selecionarProfissao(prof);
    setStep(2);
  };

  const handleSaveConfig = async () => {
    await updateAllConfig({
      metaSalarial: formData.metaSalarial,
      horasTrabalhadas: formData.horasTrabalhadas,
      taxaDeslocamento: formData.taxaDeslocamento,
      margemReserva: 0.2,
      primeiroAcesso: false
    });
    onComplete();
  };

  const valorHoraCalculado = formData.metaSalarial / formData.horasTrabalhadas;
  const valorMinutoCalculado = valorHoraCalculado / 60;
  const riscoMultiplier = selectedProfissao ? selectedProfissao.riscoBase : 1.0;
  const valorFinalMinuto = valorMinutoCalculado * riscoMultiplier;

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-slate-100 flex items-center justify-center p-4">
      <div className="max-w-4xl w-full">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
            <Zap size={32} className="text-white" />
          </div>
          <h1 className="text-3xl font-bold text-slate-900">Bem-vindo ao Mão de Obra PRO</h1>
          <p className="text-slate-600 mt-2">Vamos configurar seu perfil profissional</p>
        </div>

        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
          <div className="flex border-b border-slate-200">
            <div className={`flex-1 p-4 text-center ${step === 1 ? 'bg-blue-50 border-b-2 border-blue-600' : ''}`}>
              <span className={`text-sm font-semibold ${step === 1 ? 'text-blue-600' : 'text-slate-500'}`}>
                Passo 1: Profissão
              </span>
            </div>
            <div className={`flex-1 p-4 text-center ${step === 2 ? 'bg-blue-50 border-b-2 border-blue-600' : ''}`}>
              <span className={`text-sm font-semibold ${step === 2 ? 'text-blue-600' : 'text-slate-500'}`}>
                Passo 2: Financeiro
              </span>
            </div>
          </div>

          <div className="p-6">
            {step === 1 && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-bold text-slate-900 mb-2">Qual sua profissão?</h2>
                  <p className="text-slate-600">Isso nos ajuda a calcular os riscos e custos adequados</p>
                </div>

                <ProfissaoSelector
                  onSelect={handleSelectProfissao}
                  selectedSlug={config.profissaoSelecionada}
                />

                <div className="bg-slate-50 rounded-xl p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <TrendingUp size={18} className="text-blue-600" />
                    <span className="text-sm font-semibold text-slate-700">Como isso afeta seus preços?</span>
                  </div>
                  <p className="text-sm text-slate-600">
                    Cada profissão tem um multiplicador de risco base que influencia no valor da sua hora de trabalho.
                    Profissões com maior risco (como Eletricista) têm um valor/hora mais alto.
                  </p>
                </div>
              </div>
            )}

            {step === 2 && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-bold text-slate-900 mb-2">Configure suas metas</h2>
                  <p className="text-slate-600">Defina quanto você quer ganhar e seus custos</p>
                </div>

                <div className="space-y-4">
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
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">
                      Horas Trabalhadas por Mês
                    </label>
                    <input
                      type="number"
                      value={formData.horasTrabalhadas}
                      onChange={(e) => setFormData({...formData, horasTrabalhadas: parseFloat(e.target.value) || 0})}
                      className="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
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
                      />
                    </div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                    <p className="text-xs opacity-90">Valor/Hora Base</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorHoraCalculado)}</p>
                  </div>
                  <div className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                    <p className="text-xs opacity-90">Com Risco Profissional</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorFinalMinuto * 60)}/h</p>
                  </div>
                </div>

                {selectedProfissao && selectedProfissao.custoFerramental > 0 && (
                  <div className="bg-amber-50 rounded-xl p-4 border border-amber-200">
                    <p className="text-sm text-amber-800">
                      <strong>Custo de Ferramental:</strong> {formatarMoeda(selectedProfissao.custoFerramental)}/mês
                      <br />
                      <span className="text-xs">Este valor já está incluso no seu custo operacional</span>
                    </p>
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="p-6 border-t border-slate-200 bg-slate-50">
            {step === 1 && (
              <button
                onClick={() => setStep(2)}
                disabled={!selectedProfissao}
                className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2 disabled:opacity-50"
              >
                Próximo
                <ArrowRight size={20} />
              </button>
            )}
            {step === 2 && (
              <button
                onClick={handleSaveConfig}
                disabled={loading}
                className="w-full bg-green-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2"
              >
                <Save size={20} />
                Iniciar Mão de Obra PRO
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SetupPage;