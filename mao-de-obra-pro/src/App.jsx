import { useState } from 'react';
import { Layout } from './components/Layout.jsx';
import { DashboardPage } from './modules/dashboard/DashboardPage.jsx';
import { ClientesPage } from './modules/clientes/ClientesPage.jsx';
import { ServicosPage } from './modules/catalogo/ServicosPage.jsx';
import { ConfiguracoesPage } from './modules/financeiro/ConfiguracoesPage.jsx';

// Importação direta sem o prefixo src (já que App.jsx já está na src)
// Trocando o ponto por /src/ para o Rollup não ter como errar o caminho físico
import { NovoOrcamento } from '/src/modules/orcamentos/NovoOrcamento.jsx';
import { VisualizarOrcamento } from '/src/modules/orcamentos/VisualizarOrcamento.jsx';

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