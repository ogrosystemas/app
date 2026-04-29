import { useState } from 'react';
import { useFinanceiro } from '../../hooks/useFinanceiro';
import { Save, Info } from 'lucide-react';

export const ConfiguracoesPage = () => {
  const { dados, salvarPerfil } = useFinanceiro();
  const [form, setForm] = useState(dados);

  const handleSubmit = (e) => {
    e.preventDefault();
    salvarPerfil(form);
    alert("Perfil financeiro atualizado!");
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="bg-blue-50 p-4 rounded-xl flex gap-3 items-start mb-4">
        <Info className="text-blue-600 shrink-0" size={20} />
        <p className="text-xs text-blue-800">
          Estes valores são a base para o cálculo da sua mão de obra. Seja honesto com seus custos para não ter prejuízo.
        </p>
      </div>

      <section className="bg-white p-4 rounded-xl shadow-sm space-y-4">
        <h3 className="font-bold text-slate-700 border-b pb-2">Meta e Custos</h3>
        
        <label className="block">
          <span className="text-sm text-slate-500">Quanto quer ganhar livre (Salário)?</span>
          <input 
            type="number" 
            className="w-full mt-1" 
            value={form.salarioDesejado}
            onChange={e => setForm({...form, salarioDesejado: e.target.value})}
          />
        </label>

        <label className="block">
          <span className="text-sm text-slate-500">Custos Fixos Mensais (MEI, Aluguel, etc)</span>
          <input 
            type="number" 
            className="w-full mt-1" 
            value={form.custosFixos}
            onChange={e => setForm({...form, custosFixos: e.target.value})}
          />
        </label>

        <label className="block">
          <span className="text-sm text-slate-500">Custo Mensal com Ajudantes</span>
          <input 
            type="number" 
            className="w-full mt-1" 
            value={form.custoAjudante}
            onChange={e => setForm({...form, custoAjudante: e.target.value})}
          />
        </label>
      </section>

      <section className="bg-white p-4 rounded-xl shadow-sm space-y-4">
        <h3 className="font-bold text-slate-700 border-b pb-2">Disponibilidade</h3>
        <div className="grid grid-cols-2 gap-4">
          <label className="block">
            <span className="text-sm text-slate-500">Dias/Mês</span>
            <input 
              type="number" 
              className="w-full mt-1" 
              value={form.diasTrabalhados}
              onChange={e => setForm({...form, diasTrabalhados: e.target.value})}
            />
          </label>
          <label className="block">
            <span className="text-sm text-slate-500">Horas/Dia</span>
            <input 
              type="number" 
              className="w-full mt-1" 
              value={form.horasPorDia}
              onChange={e => setForm({...form, horasPorDia: e.target.value})}
            />
          </label>
        </div>
      </section>

      <button type="submit" className="w-full bg-blue-600 text-white py-4 rounded-xl font-black shadow-lg flex items-center justify-center gap-2">
        <Save size={20} /> ATUALIZAR CONFIGURAÇÕES
      </button>
    </form>
  );
};