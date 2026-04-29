import React, { useState, useEffect } from 'react';
import Layout from './components/Layout';
import DashboardPage from './modules/dashboard/DashboardPage';
import ClientesPage from './modules/clientes/ClientesPage';
import ServicosPage from './modules/catalogo/ServicosPage';
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage';
import NovoOrcamento from './modules/orcamentos/NovoOrcamento';
import VisualizarOrcamento from './modules/orcamentos/VisualizarOrcamento';
import { initDatabase } from './database/db';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [showBudgetView, setShowBudgetView] = useState(false);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);

  useEffect(() => {
    // Initialize database
    initDatabase().then(() => {
      setDbReady(true);
    }).catch(error => {
      console.error('Failed to initialize database:', error);
    });
  }, []);

  const handleNewBudget = () => {
    setActiveTab('novo');
    setShowBudgetView(false);
  };

  const handleViewBudget = (id) => {
    setSelectedBudgetId(id);
    setShowBudgetView(true);
    setActiveTab('visualizar');
  };

  const handleBackToDashboard = () => {
    setShowBudgetView(false);
    setSelectedBudgetId(null);
    setActiveTab('dashboard');
  };

  if (!dbReady) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-slate-600">Inicializando banco de dados...</p>
        </div>
      </div>
    );
  }

  // Renderização condicional baseada na tab ativa
  const renderContent = () => {
    if (showBudgetView && activeTab === 'visualizar') {
      return <VisualizarOrcamento id={selectedBudgetId} onBack={handleBackToDashboard} />;
    }

    switch (activeTab) {
      case 'dashboard':
        return <DashboardPage onNewBudget={handleNewBudget} onViewBudget={handleViewBudget} />;
      case 'clientes':
        return <ClientesPage />;
      case 'catalogo':
        return <ServicosPage />;
      case 'financeiro':
        return <ConfiguracoesPage />;
      case 'novo':
        return <NovoOrcamento onSave={() => setActiveTab('dashboard')} />;
      default:
        return <DashboardPage onNewBudget={handleNewBudget} onViewBudget={handleViewBudget} />;
    }
  };

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab}>
      {renderContent()}
    </Layout>
  );
}

export default App;