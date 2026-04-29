import { useState } from 'react';
import { Layout } from './components/Layout';
import { DashboardPage } from './modules/dashboard/DashboardPage';
import { ClientesPage } from './modules/clientes/ClientesPage';
import { ServicosPage } from './modules/catalogo/ServicosPage';
import { ConfiguracoesPage } from './modules/financeiro/ConfiguracoesPage';
import { NovoOrcamento } from './modules/orcamentos/NovoOrcamento';
import { VisualizarOrcamento } from './modules/orcamentos/VisualizarOrcamento';

export default function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [orcamentoAtivo, setOrcamentoAtivo] = useState(null);

  const abrirOrcamento = (id) => {
    setOrcamentoAtivo(id);
    setActiveTab('visualizar');
  };

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard': return <DashboardPage aoSelecionar={abrirOrcamento} />;
      case 'clientes': return <ClientesPage />;
      case 'servicos': return <ServicosPage />;
      case 'financeiro': return <ConfiguracoesPage />;
      case 'novo': return <NovoOrcamento aoSalvar={() => setActiveTab('dashboard')} />;
      case 'visualizar': return <VisualizarOrcamento orcamentoId={orcamentoAtivo} aoVoltar={() => setActiveTab('dashboard')} />;
      default: return <DashboardPage />;
    }
  };

  return (
    <Layout activeTab={activeTab} setActiveTab={setActiveTab}>
      {activeTab === 'dashboard' && (
        <button onClick={() => setActiveTab('novo')} className="w-full bg-blue-600 text-white p-4 rounded-2xl font-bold mb-6 shadow-lg">
          + CRIAR NOVO ORÇAMENTO
        </button>
      )}
      {renderContent()}
    </Layout>
  );
}