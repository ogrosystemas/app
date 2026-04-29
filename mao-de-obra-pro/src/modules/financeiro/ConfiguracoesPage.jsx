import React, { useState, useEffect } from 'react';
import { Save, DollarSign } from 'lucide-react';
import db from '../../database/db';

const ConfiguracoesPage = () => {
  const [meta, setMeta] = useState(5000);
  const [horas, setHoras] = useState(160);
  const [margem, setMargem] = useState(0.2);
  const [taxa, setTaxa] = useState(50);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    loadConfig();
  }, []);

  const loadConfig = async () => {
    const configs = await db.config.toArray();
    configs.forEach(c => {
      if (c.chave === 'metaSalarial') setMeta(c.valor);
      if (c.chave === 'horasTrabalhadas') setHoras(c.valor);
      if (c.chave === 'margemReserva') setMargem(c.valor);
      if (c.chave === 'taxaDeslocamento') setTaxa(c.valor);
    });
  };

  const saveConfig = async () => {
    setSaving(true);
    await db.config.where('chave').equals('metaSalarial').modify({ valor: meta });
    await db.config.where('chave').equals('horasTrabalhadas').modify({ valor: horas });
    await db.config.where('chave').equals('margemReserva').modify({ valor: margem });
    await db.config.where('chave').equals('taxaDeslocamento').modify({ valor: taxa });
    setSaving(false);
    alert('Configurações salvas!');
  };

  const valorHora = meta / horas;
  const valorMinuto = valorHora / 60;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Financeiro</h1>

      <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <p className="text-sm opacity-90">Seu valor por minuto</p>
        <p className="text-3xl font-bold">
          {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valorMinuto)}
        </p>
        <p className="text-sm opacity-90 mt-1">Equivalente a {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valorHora)}/hora</p>
      </div>

      <div className="bg-white rounded-xl p-4 shadow-sm border space-y-4">
        <div>
          <label className="block text-sm font-medium mb-1">Meta Salarial Mensal (R$)</label>
          <input type="number" value={meta} onChange={(e) => setMeta(parseFloat(e.target.value))} className="w-full px-4 py-2 border rounded-lg" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Horas Trabalhadas/Mês</label>
          <input type="number" value={horas} onChange={(e) => setHoras(parseFloat(e.target.value))} className="w-full px-4 py-2 border rounded-lg" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Margem de Reserva (%)</label>
          <input type="number" step="1" value={margem * 100} onChange={(e) => setMargem(parseFloat(e.target.value) / 100)} className="w-full px-4 py-2 border rounded-lg" />
        </div>
        <div>
          <label className="block text-sm font-medium mb-1">Taxa de Deslocamento (R$)</label>
          <input type="number" value={taxa} onChange={(e) => setTaxa(parseFloat(e.target.value))} className="w-full px-4 py-2 border rounded-lg" />
        </div>
      </div>

      <button onClick={saveConfig} disabled={saving} className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2">
        <Save size={20} /> {saving ? 'Salvando...' : 'Salvar Configurações'}
      </button>
    </div>
  );
};

export default ConfiguracoesPage;