import React, { useState, useEffect } from 'react';
import { Plus, FileText, TrendingUp, Clock, Users, DollarSign } from 'lucide-react';
import db from '../../database/db';
import { formatarMoeda } from '../../core/calculadora';
import { useFinanceiro } from '../../hooks/useFinanceiro';

const DashboardPage = ({ onNewBudget }) => {
  const [recentBudgets, setRecentBudgets] = useState([]);
  const [stats, setStats] = useState({
    totalBudgets: 0,
    totalValue: 0,
    activeClients: 0
  });
  const { config } = useFinanceiro();

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      const budgets = await db.orcamentos
        .orderBy('data')
        .reverse()
        .limit(5)
        .toArray();

      const clients = await db.clientes.toArray();
      const allBudgets = await db.orcamentos.toArray();

      const totalValue = allBudgets.reduce((sum, b) => sum + (b.total || 0), 0);

      setRecentBudgets(budgets);
      setStats({
        totalBudgets: allBudgets.length,
        totalValue: totalValue,
        activeClients: clients.length
      });
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
  };

  const getClientName = async (clienteId) => {
    const client = await db.clientes.get(clienteId);
    return client ? client.nome : 'Cliente não encontrado';
  };

  const [clientNames, setClientNames] = useState({});

  useEffect(() => {
    const loadNames = async () => {
      const names = {};
      for (const budget of recentBudgets) {
        const client = await db.clientes.get(budget.clienteId);
        if (client) names[budget.id] = client.nome;
      }
      setClientNames(names);
    };
    if (recentBudgets.length > 0) loadNames();
  }, [recentBudgets]);

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl lg:text-3xl font-bold text-slate-900">Dashboard</h1>
          <p className="text-slate-500 mt-1">Visão geral do seu negócio</p>
        </div>
        <button
          onClick={onNewBudget}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2 hover:bg-blue-700 transition-colors"
        >
          <Plus size={20} />
          Novo Orçamento
        </button>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-sm opacity-90">Valor/Minuto</p>
              <p className="text-2xl font-bold mt-1">{formatarMoeda(config.valorMinuto)}</p>
            </div>
            <Clock size={24} className="opacity-80" />
          </div>
        </div>

        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-sm text-slate-500">Orçamentos</p>
              <p className="text-2xl font-bold text-slate-900 mt-1">{stats.totalBudgets}</p>
            </div>
            <FileText size={24} className="text-blue-500" />
          </div>
        </div>

        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-sm text-slate-500">Total em Orçamentos</p>
              <p className="text-2xl font-bold text-slate-900 mt-1">{formatarMoeda(stats.totalValue)}</p>
            </div>
            <DollarSign size={24} className="text-green-500" />
          </div>
        </div>

        <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
          <div className="flex justify-between items-start">
            <div>
              <p className="text-sm text-slate-500">Clientes Ativos</p>
              <p className="text-2xl font-bold text-slate-900 mt-1">{stats.activeClients}</p>
            </div>
            <Users size={24} className="text-purple-500" />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-slate-200">
        <div className="p-6 border-b border-slate-200">
          <h2 className="text-xl font-semibold text-slate-900">Últimos Orçamentos</h2>
        </div>
        <div className="divide-y divide-slate-200">
          {recentBudgets.length === 0 ? (
            <div className="p-8 text-center text-slate-500">
              <FileText size={48} className="mx-auto mb-3 opacity-50" />
              <p>Nenhum orçamento criado ainda</p>
              <button
                onClick={onNewBudget}
                className="mt-3 text-blue-600 font-semibold hover:text-blue-700"
              >
                Criar primeiro orçamento
              </button>
            </div>
          ) : (
            recentBudgets.map((budget) => (
              <div key={budget.id} className="p-4 hover:bg-slate-50 transition-colors">
                <div className="flex justify-between items-start">
                  <div>
                    <p className="font-semibold text-slate-900">
                      {clientNames[budget.id] || `Orçamento #${budget.id}`}
                    </p>
                    <p className="text-sm text-slate-500 mt-1">
                      {new Date(budget.data).toLocaleDateString('pt-BR')}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-bold text-blue-600">{formatarMoeda(budget.total)}</p>
                    <span className={`
                      text-xs px-2 py-1 rounded-full mt-1 inline-block
                      ${budget.status === 'aprovado' ? 'bg-green-100 text-green-700' : ''}
                      ${budget.status === 'pendente' ? 'bg-yellow-100 text-yellow-700' : ''}
                      ${budget.status === 'recusado' ? 'bg-red-100 text-red-700' : ''}
                    `}>
                      {budget.status === 'aprovado' ? 'Aprovado' :
                       budget.status === 'pendente' ? 'Pendente' : 'Recusado'}
                    </span>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      <div className="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl p-4 border border-amber-200">
        <div className="flex items-start gap-3">
          <TrendingUp className="text-amber-600 flex-shrink-0 mt-0.5" size={20} />
          <div>
            <p className="font-semibold text-amber-800">Dica Profissional</p>
            <p className="text-sm text-amber-700 mt-1">
              Sempre fotografe o antes e depois do serviço. Isso gera credibilidade
              e protege você contra possíveis questionamentos.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardPage;