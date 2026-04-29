import { useLiveQuery } from 'dexie-react-hooks';
import { Wallet, TrendingUp, AlertCircle, CheckCircle2 } from 'lucide-react';
import db from '../../database/db';
import { useFinanceiro } from '../../hooks/useFinanceiro';

export const DashboardPage = () => {
  const { metricas, dados } = useFinanceiro();

  // Busca orçamentos do mês atual
  const orçamentos = useLiveQuery(() => db.orcamentos.toArray()) || [];

  const pagos = orçamentos.filter(o => o.status === 'pago');
  const pendentes = orçamentos.filter(o => o.status === 'rascunho' || o.status === 'enviado');

  const faturamentoTotal = pagos.reduce((acc, cur) => acc + cur.total, 0);
  const faturamentoPendente = pendentes.reduce((acc, cur) => acc + cur.total, 0);

  // Lógica das "Gavetas" (Decomposição baseada nos custos configurados)
  const percentualCusto = metricas.totalDespesas / (Number(dados.salarioDesejado) + metricas.totalDespesas || 1);
  const valorCustos = faturamentoTotal * percentualCusto;
  const valorSalario = faturamentoTotal * (1 - percentualCusto);
  const valorReserva = faturamentoTotal * (dados.margemReserva / 100);

  const progressoMeta = (faturamentoTotal / (Number(dados.salarioDesejado) + metricas.totalDespesas || 1)) * 100;

  return (
    <div className="space-y-6">
      {/* Card de Meta Salarial */}
      <div className="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div className="flex justify-between items-start mb-4">
          <div>
            <p className="text-slate-500 text-sm font-medium">Meta do Mês</p>
            <h3 className="text-2xl font-black text-slate-800">
              R$ {faturamentoTotal.toFixed(2)}
              <span className="text-slate-400 text-lg font-normal"> / {(Number(dados.salarioDesejado) + metricas.totalDespesas).toFixed(0)}</span>
            </h3>
          </div>
          <div className="bg-blue-100 p-2 rounded-lg text-blue-600">
            <TrendingUp size={24} />
          </div>
        </div>

        <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
          <div
            className="bg-blue-600 h-full transition-all duration-500"
            style={{ width: `${Math.min(progressoMeta, 100)}%` }}
          />
        </div>
        <p className="text-xs text-slate-400 mt-2 font-bold">{progressoMeta.toFixed(1)}% da meta atingida</p>
      </div>

      {/* As Gavetas de Dinheiro */}
      <div className="grid grid-cols-2 gap-4">
        <div className="bg-white p-4 rounded-xl border-l-4 border-orange-400 shadow-sm">
          <p className="text-[10px] uppercase font-bold text-slate-400">Custos/Ajudante</p>
          <p className="text-lg font-bold text-slate-700">R$ {valorCustos.toFixed(2)}</p>
        </div>
        <div className="bg-white p-4 rounded-xl border-l-4 border-green-500 shadow-sm">
          <p className="text-[10px] uppercase font-bold text-slate-400">Meu Salário</p>
          <p className="text-lg font-bold text-slate-700">R$ {valorSalario.toFixed(2)}</p>
        </div>
      </div>

      {/* Orçamentos Pendentes */}
      <section>
        <h3 className="text-sm font-bold text-slate-500 mb-3 flex items-center gap-2">
          <AlertCircle size={16} /> AGUARDANDO RECEBIMENTO
        </h3>
        <div className="bg-orange-50 border border-orange-100 p-4 rounded-xl">
          <p className="text-orange-700 font-bold text-xl">R$ {faturamentoPendente.toFixed(2)}</p>
          <p className="text-orange-600 text-xs">Valor em orçamentos não finalizados</p>
        </div>
      </section>

      {/* Histórico Rápido */}
      <section className="space-y-3">
        <h3 className="text-sm font-bold text-slate-500">ÚLTIMOS SERVIÇOS</h3>
        {orçamentos.slice(-3).reverse().map(o => (
          <div key={o.id} className="bg-white p-4 rounded-xl flex justify-between items-center shadow-sm border border-slate-100">
            <div>
              <p className="font-bold text-slate-700">Serviço #{o.id}</p>
              <p className="text-xs text-slate-400">{new Date(o.data).toLocaleDateString()}</p>
            </div>
            <div className="text-right">
              <p className="font-bold text-blue-600">R$ {o.total.toFixed(2)}</p>
              <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${o.status === 'pago' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500'}`}>
                {o.status.toUpperCase()}
              </span>
            </div>
          </div>
        ))}
      </section>
    </div>
  );
};