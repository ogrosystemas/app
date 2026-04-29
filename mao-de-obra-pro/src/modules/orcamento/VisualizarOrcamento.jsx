import { useLiveQuery } from 'dexie-react-hooks';
import { Share2, FileText, CheckCircle } from 'lucide-react';
import db from '../../database/db';
import { gerarOrcamentoPdf } from '../../utils/geradorPdf';
import { useFinanceiro } from '../../hooks/useFinanceiro';

export const VisualizarOrcamento = ({ orcamentoId }) => {
  const { dados } = useFinanceiro();
  const orcamento = useLiveQuery(() => db.orcamentos.get(orcamentoId));
  const cliente = useLiveQuery(() => orcamento ? db.clientes.get(orcamento.clienteId) : null);

  if (!orcamento || !cliente) return <p>Carregando...</p>;

  const handleEnviarWhats = () => {
    const doc = gerarOrcamentoPdf(orcamento, cliente, dados);
    const pdfBlob = doc.output('blob');

    // Texto para o WhatsApp
    const mensagem = `Olá ${cliente.nome}, segue o orçamento para os serviços solicitados. Valor total: R$ ${orcamento.total.toFixed(2)}.`;
    const url = `https://wa.me/55${cliente.whatsapp.replace(/\D/g, '')}?text=${encodeURIComponent(mensagem)}`;

    // No PWA, abrimos o link e o usuário anexa o PDF baixado
    doc.save(`Orcamento_${cliente.nome}.pdf`);
    window.open(url, '_blank');
  };

  const marcarComoPago = async () => {
    await db.orcamentos.update(orcamentoId, { status: 'pago' });
    alert("Pagamento registrado no caixa do mês!");
  };

  return (
    <div className="bg-white p-6 rounded-2xl shadow-lg border border-slate-200 space-y-6">
      <div className="text-center">
        <div className="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600">
          <FileText size={32} />
        </div>
        <h2 className="text-2xl font-black">R$ {orcamento.total.toFixed(2)}</h2>
        <p className="text-slate-500 italic">{cliente.nome}</p>
      </div>

      <div className="space-y-3">
        <button
          onClick={handleEnviarWhats}
          className="w-full bg-green-500 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2"
        >
          <Share2 size={20} /> ENVIAR PDF PELO WHATS
        </button>

        {orcamento.status !== 'pago' && (
          <button
            onClick={marcarComoPago}
            className="w-full bg-blue-600 text-white py-4 rounded-xl font-bold flex items-center justify-center gap-2"
          >
            <CheckCircle size={20} /> REGISTRAR PAGAMENTO
          </button>
        )}
      </div>
    </div>
  );
};