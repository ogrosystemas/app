import React, { useState, useEffect } from 'react';
import Layout from './components/Layout';
import SetupPage from './modules/setup/SetupPage';
import DashboardPage from './modules/dashboard/DashboardPage';
import ClientesPage from './modules/clientes/ClientesPage';
import ServicosPage from './modules/catalogo/ServicosPage';
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage';
import NovoOrcamento from './modules/orcamento/NovoOrcamento';
import VisualizarOrcamento from './modules/orcamento/VisualizarOrcamento';
import { initDatabase, db } from './database/db';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [showSetup, setShowSetup] = useState(true);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    const initialize = async () => {
      try {
        await initDatabase();
        const setupFlag = await db.config.where('chave').equals('setupConcluido').first();
        setShowSetup(!setupFlag || setupFlag.valor !== 1);
        setDbReady(true);
      } catch (err) {
        console.error('Erro fatal:', err);
        setError('Falha ao inicializar o banco de dados. Clique em Recarregar.');
        setDbReady(true);
      }
    };
    initialize();
  }, []);

  const handleSetupComplete = () => {
    setShowSetup(false);
    setActiveTab('dashboard');
  };

  const renderContent = () => {
    // mesmo código de antes
    switch (activeTab) {
      case 'dashboard':
        return <DashboardPage onNewBudget={() => setActiveTab('novo')} onViewBudget={(id) => { setSelectedBudgetId(id); setActiveTab('visualizar'); }} />;
      case 'clientes':
        return <ClientesPage />;
      case 'catalogo':
        return <ServicosPage />;
      case 'financeiro':
        return <ConfiguracoesPage />;
      case 'novo':
        return <NovoOrcamento onSave={() => setActiveTab('dashboard')} />;
      case 'visualizar':
        return <VisualizarOrcamento onBack={() => setActiveTab('dashboard')} id={selectedBudgetId} />;
      default:
        return <DashboardPage onNewBudget={() => setActiveTab('novo')} onViewBudget={(id) => { setSelectedBudgetId(id); setActiveTab('visualizar'); }} />;
    }
  };

  if (!dbReady) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-slate-600">Carregando...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center bg-red-50 p-6 rounded-xl">
          <p className="text-red-800">{error}</p>
          <button onClick={() => window.location.reload()} className="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg">Recarregar</button>
        </div>
      </div>
    );
  }

  if (showSetup) {
    return <SetupPage onComplete={handleSetupComplete} />;
  }

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab}>
      {renderContent()}
    </Layout>
  );
}

export default App;