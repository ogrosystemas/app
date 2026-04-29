import { useState } from 'react';
// Adicionadas as extensões .jsx em todos os componentes internos
import { Layout } from './components/Layout.jsx';
import { DashboardPage } from './modules/dashboard/DashboardPage.jsx';
import { ClientesPage } from '.src/modules/clientes/ClientesPage.jsx';
import { ServicosPage } from '.src/modules/catalogo/ServicosPage.jsx';
import { ConfiguracoesPage } from '.src/modules/financeiro/ConfiguracoesPage.jsx';
import { NovoOrcamento } from '.src/modules/orcamentos/NovoOrcamento.jsx';
import { VisualizarOrcamento } from '.src/modules/orcamentos/VisualizarOrcamento.jsx';

export default function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [orcamentoAtivo, setOrcamentoAtivo] = useState(null);

  const abrirOrcamento = (id) => {
    setOrcamentoAtivo(id);
    setActiveTab('visualizar');
  };

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <DashboardPage aoSelecionar={abrirOrcamento} />;
      case 'clientes':
        return <ClientesPage />;
      case 'servicos':
        return <ServicosPage />;
      case 'financeiro':
        return <ConfiguracoesPage />;
      case 'novo':
        // Corrigido para garantir que volte ao dashboard após salvar
        return <NovoOrcamento aoSalvar={() => setActiveTab('dashboard')} />;
      case 'visualizar':
        return <VisualizarOrcamento orcamentoId={orcamentoAtivo} aoVoltar={() => setActiveTab('dashboard')} />;
      default:
        return <DashboardPage />;
    }
  };

  return (
    <Layout activeTab={activeTab} setActiveTab={setActiveTab}>
      {activeTab === 'dashboard' && (
        <button
          onClick={() => setActiveTab('novo')}
          className="w-full bg-blue-600 text-white p-4 rounded-2xl font-bold mb-6 shadow-lg active:scale-95 transition-transform"
        >
          + CRIAR NOVO ORÇAMENTO
        </button>
      )}
      {renderContent()}
    </Layout>
  );
}