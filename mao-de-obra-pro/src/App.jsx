import React, { useState } from 'react';

// Import all pages directly - NO relative paths issues
import DashboardPage from './modules/dashboard/DashboardPage';
import ClientesPage from './modules/clientes/ClientesPage';
import ServicosPage from './modules/catalogo/ServicosPage';
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage';
import NovoOrcamento from './modules/orcamentos/NovoOrcamento';
import Layout from './components/Layout';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');

  const renderPage = () => {
    switch(activeTab) {
      case 'dashboard':
        return <DashboardPage onNewBudget={() => setActiveTab('novo')} />;
      case 'clientes':
        return <ClientesPage />;
      case 'catalogo':
        return <ServicosPage />;
      case 'financeiro':
        return <ConfiguracoesPage />;
      case 'novo':
        return <NovoOrcamento onSave={() => setActiveTab('dashboard')} />;
      default:
        return <DashboardPage onNewBudget={() => setActiveTab('novo')} />;
    }
  };

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab}>
      {renderPage()}
    </Layout>
  );
}

export default App;