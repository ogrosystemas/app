import { useLiveQuery } from 'dexie-react-hooks';
import { TrendingUp, AlertCircle } from 'lucide-react';
import db from '../../database/db';
import { useFinanceiro } from '../../hooks/useFinanceiro';

export const DashboardPage = () => {
  const { metricas, dados } = useFinanceiro();
  const orcamentos = useLiveQuery(() => db.orcamentos.toArray()) || [];

  const pagos = orcamentos.filter(o => o.status === 'pago');
  const pendentes = orcamentos.filter(o => o.status === 'rascunho' || o.status === 'enviado');

  const faturamentoTotal = pagos.reduce((acc, cur) => acc + cur.total, 0);
  const faturamentoPendente = pendentes.reduce((acc, cur) => acc + cur.total, 0);

  // Lógica de Decomposição do Caixa
  const totalMetaMensal = Number(dados.salarioDesejado) + metricas.totalDespesas;
  const proporcaoCusto = metricas.totalDespesas / (totalMetaMensal || 1);

  const valorCustos = faturamentoTotal * proporcaoCusto;
  const valorSalario = faturamentoTotal * (1 - proporcaoCusto);
  const progressoMeta = (faturamentoTotal / (totalMetaMensal || 1)) * 100;

  return (
    <div className="space-y-6">
      <div className="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p className="text-slate-500 text-sm font-medium">Faturamento Pago</p>
        <h3 className="text-2xl font-black text-slate-800">
          R$ {faturamentoTotal.toFixed(2)}
          <span className="text-slate-400 text-lg font-normal"> / {totalMetaMensal.toFixed(0)}</span>
        </h3>
        <div className="w-full bg-slate-100 h-3 rounded-full mt-4 overflow-hidden">
          <div className="bg-blue-600 h-full transition-all" style={{ width: `${Math.min(progressoMeta, 100)}%` }} />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="bg-white p-4 rounded-xl border-l-4 border-orange-400 shadow-sm">
          <p className="text-[10px] uppercase font-bold text-slate-400">Gaveta: Custos</p>
          <p className="font-bold text-slate-700">R$ {valorCustos.toFixed(2)}</p>
        </div>
        <div className="bg-white p-4 rounded-xl border-l-4 border-green-500 shadow-sm">
          <p className="text-[10px] uppercase font-bold text-slate-400">Gaveta: Salário</p>
          <p className="font-bold text-slate-700">R$ {valorSalario.toFixed(2)}</p>
        </div>
      </div>

      <section>
        <h3 className="text-sm font-bold text-slate-500 mb-3 flex items-center gap-2">
          <AlertCircle size={16} /> PENDENTE DE RECEBIMENTO
        </h3>
        <div className="bg-orange-50 border border-orange-100 p-4 rounded-xl">
          <p className="text-orange-700 font-bold text-xl">R$ {faturamentoPendente.toFixed(2)}</p>
        </div>
      </section>
    </div>
  );
};