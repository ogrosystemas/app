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
      // 1. Garante que o banco esteja aberto
      await db.open();

      // 2. Salva as configurações financeiras
      const success = await updateAllConfig({
        metaSalarial: formData.metaSalarial,
        horasTrabalhadas: formData.horasTrabalhadas,
        taxaDeslocamento: formData.taxaDeslocamento,
        margemReserva: 0.2
      });

      if (!success) {
        throw new Error('Falha ao salvar configurações financeiras');
      }

      // 3. Marca o setup como concluído
      const existing = await db.config.where('chave').equals('setupConcluido').first();
      if (existing) {
        await db.config.update(existing.id, { valor: 1 });
      } else {
        await db.config.add({ chave: 'setupConcluido', valor: 1 });
      }

      // 4. Verifica se realmente foi salvo
      const check = await db.config.where('chave').equals('setupConcluido').first();
      if (!check || check.valor !== 1) {
        throw new Error('Flag de conclusão não foi salva');
      }

      // 5. Sucesso
      alert('Configuração concluída com sucesso!');
      onComplete();

    } catch (error) {
      console.error('Erro ao salvar:', error);
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
              <span className={`text-sm font-semibold ${step === 1 ? 'text-blue-600' : 'text-slate-500'}`}>Passo 1: Profissão</span>
            </div>
            <div className={`flex-1 p-4 text-center ${step === 2 ? 'bg-blue-50 border-b-2 border-blue-600' : ''}`}>
              <span className={`text-sm font-semibold ${step === 2 ? 'text-blue-600' : 'text-slate-500'}`}>Passo 2: Financeiro</span>
            </div>
          </div>

          <div className="p-6">
            {step === 1 && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-xl font-bold text-slate-900 mb-2">Qual sua profissão?</h2>
                  <p className="text-slate-600">Isso nos ajuda a calcular os riscos e custos adequados</p>
                </div>
                <ProfissaoSelector onSelect={handleSelectProfissao} selectedSlug={config.profissaoSelecionada} />
                <div className="bg-slate-50 rounded-xl p-4">
                  <p className="text-sm text-slate-600">Cada profissão tem um multiplicador de risco base que influencia no valor da sua hora de trabalho. Profissões com maior risco têm valor/hora mais alto.</p>
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
                    <label className="block text-sm font-medium text-slate-700 mb-1">Meta Salarial Mensal</label>
                    <input type="number" value={formData.metaSalarial} onChange={(e) => setFormData({...formData, metaSalarial: parseFloat(e.target.value) || 0})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Horas Trabalhadas por Mês</label>
                    <input type="number" value={formData.horasTrabalhadas} onChange={(e) => setFormData({...formData, horasTrabalhadas: parseFloat(e.target.value) || 0})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1">Taxa de Deslocamento</label>
                    <input type="number" value={formData.taxaDeslocamento} onChange={(e) => setFormData({...formData, taxaDeslocamento: parseFloat(e.target.value) || 0})} className="w-full px-4 py-3 border rounded-lg" />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-blue-600 rounded-xl p-4 text-white">
                    <p className="text-xs">Valor/Hora Base</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorHoraCalculado)}</p>
                  </div>
                  <div className="bg-purple-600 rounded-xl p-4 text-white">
                    <p className="text-xs">Com Risco Profissional</p>
                    <p className="text-xl font-bold">{formatarMoeda(valorFinalMinuto * 60)}/h</p>
                  </div>
                </div>
                {selectedProfissao && selectedProfissao.custoFerramental > 0 && (
                  <div className="bg-amber-50 rounded-xl p-4">
                    <p className="text-sm">Custo de Ferramental: {formatarMoeda(selectedProfissao.custoFerramental)}/mês</p>
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="p-6 border-t bg-slate-50">
            {step === 1 && (
              <button onClick={() => setStep(2)} disabled={!selectedProfissao} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold disabled:opacity-50">
                Próximo <ArrowRight size={20} className="inline ml-2" />
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