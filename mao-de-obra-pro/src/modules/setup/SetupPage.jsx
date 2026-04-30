import React, { useState, useEffect } from 'react';
import { Briefcase, TrendingUp, Car, Save, ArrowRight, Zap } from 'lucide-react';
import ProfissaoSelector from '../../components/ProfissaoSelector';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { formatarMoeda } from '../../core/calculadora';
import db from '../../database/db';

const SetupPage = ({ onComplete }) => {
  const { config, profissao, selecionarProfissao, updateAllConfig, loading } = useFinanceiro();
  const [step, setStep] = useState(1);
  const [selectedProfissao, setSelectedProfissao] = useState(null);
  const [saving, setSaving] = useState(false);
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
    if (saving) return;
    setSaving(true);
    try {
      await db.open();
      const success = await updateAllConfig({
        metaSalarial: formData.metaSalarial,
        horasTrabalhadas: formData.horasTrabalhadas,
        taxaDeslocamento: formData.taxaDeslocamento,
        margemReserva: 0.2
      });
      if (!success) throw new Error('Falha ao salvar configurações');

      // Marca setup como concluído
      await updateAllConfig({ setupConcluido: 1 });

      // Verifica persistência
      const check = await db.config.get('setupConcluido');
      if (!check || check.valor !== 1) throw new Error('Flag não persistiu');

      alert('Configuração concluída com sucesso!');
      onComplete();
    } catch (error) {
      console.error(error);
      alert('Erro: ' + error.message);
      setSaving(false);
    }
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
              <span className="text-sm font-semibold">Passo 1: Profissão</span>
            </div>
            <div className={`flex-1 p-4 text-center ${step === 2 ? 'bg-blue-50 border-b-2 border-blue-600' : ''}`}>
              <span className="text-sm font-semibold">Passo 2: Financeiro</span>
            </div>
          </div>

          <div className="p-6">
            {step === 1 && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-bold mb-2">Qual sua profissão?</h2>
                  <p className="text-slate-600">Isso nos ajuda a calcular os riscos e custos adequados</p>
                </div>
                <ProfissaoSelector onSelect={handleSelectProfissao} selectedSlug={config.profissaoSelecionada} />
                <div className="bg-slate-50 p-4 rounded-xl text-sm text-slate-600">
                  Cada profissão tem um multiplicador de risco base que influencia no valor da sua hora de trabalho. Profissões com maior risco têm valor/hora mais alto.
                </div>
              </div>
            )}
            {step === 2 && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-bold mb-2">Configure suas metas</h2>
                  <p className="text-slate-600">Defina quanto você quer ganhar e seus custos</p>
                </div>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium mb-1">Meta Salarial Mensal (R$)</label>
                    <input type="number" value={formData.metaSalarial} onChange={e => setFormData({...formData, metaSalarial: +e.target.value})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Horas Trabalhadas por Mês</label>
                    <input type="number" value={formData.horasTrabalhadas} onChange={e => setFormData({...formData, horasTrabalhadas: +e.target.value})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                  <div>
                    <label className="block text-sm font-medium mb-1">Taxa de Deslocamento (R$)</label>
                    <input type="number" value={formData.taxaDeslocamento} onChange={e => setFormData({...formData, taxaDeslocamento: +e.target.value})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-blue-600 text-white p-4 rounded-xl">
                    <p className="text-xs">Valor/Hora Base</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorHoraCalculado)}</p>
                  </div>
                  <div className="bg-purple-600 text-white p-4 rounded-xl">
                    <p className="text-xs">Com Risco Profissional</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorFinalMinuto * 60)}/h</p>
                  </div>
                </div>
                {selectedProfissao && selectedProfissao.custoFerramental > 0 && (
                  <div className="bg-amber-50 p-4 rounded-xl text-sm">
                    <strong>Custo de Ferramental:</strong> {formatarMoeda(selectedProfissao.custoFerramental)}/mês
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="p-6 border-t bg-slate-50">
            {step === 1 && (
              <button onClick={() => setStep(2)} disabled={!selectedProfissao} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50">
                Próximo <ArrowRight className="inline ml-2" size={20} />
              </button>
            )}
            {step === 2 && (
              <button onClick={handleSaveConfig} disabled={saving} className="w-full bg-green-600 text-white py-3 rounded-lg font-semibold flex justify-center items-center gap-2">
                <Save size={20} /> {saving ? 'Salvando...' : 'Iniciar Mão de Obra PRO'}
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default SetupPage;