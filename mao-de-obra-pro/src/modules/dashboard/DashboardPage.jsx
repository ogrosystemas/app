import React, { useState, useEffect } from 'react';
import { Plus, FileText, TrendingUp } from 'lucide-react';
import db from '../../database/db';

const DashboardPage = ({ onNewBudget }) => {
  const [recentBudgets, setRecentBudgets] = useState([]);

  useEffect(() => {
    const loadBudgets = async () => {
      const budgets = await db.orcamentos.orderBy('data').reverse().limit(5).toArray();
      setRecentBudgets(budgets);
    };
    loadBudgets();
  }, []);

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <button onClick={onNewBudget} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
          <Plus size={20} /> Novo Orçamento
        </button>
      </div>

      <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <h2 className="text-lg font-semibold">Bem-vindo ao Mão de Obra PRO!</h2>
        <p className="text-sm opacity-90 mt-1">Sistema profissional de orçamentos</p>
      </div>

      <div className="bg-white rounded-xl p-4 shadow-sm border">
        <h3 className="font-semibold mb-3">Últimos Orçamentos</h3>
        {recentBudgets.length === 0 ? (
          <p className="text-slate-500 text-center py-4">Nenhum orçamento ainda</p>
        ) : (
          recentBudgets.map(b => (
            <div key={b.id} className="border-b py-2 flex justify-between">
              <span>Orçamento #{b.id}</span>
              <span className="font-semibold text-blue-600">
                {new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(b.total)}
              </span>
            </div>
          ))
        )}
      </div>

      <div className="bg-amber-50 rounded-xl p-4 border border-amber-200">
        <div className="flex gap-2">
          <TrendingUp className="text-amber-600" />
          <p className="text-sm text-amber-700">Dica: Sempre fotografe o antes e depois do serviço!</p>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;