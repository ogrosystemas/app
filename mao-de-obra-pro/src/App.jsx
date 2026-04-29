import React, { useState, useEffect } from 'react';
import Layout from './components/Layout';
import SetupPage from './modules/setup/SetupPage';
import DashboardPage from './modules/dashboard/DashboardPage';
import ClientesPage from './modules/clientes/ClientesPage';
import ServicosPage from './modules/catalogo/ServicosPage';
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage';
import NovoOrcamento from './modules/orcamento/NovoOrcamento';
import { initDatabase, db } from './database/db';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [primeiroAcesso, setPrimeiroAcesso] = useState(true);

  useEffect(() => {
    initDatabase().then(async () => {
      const config = await db.config.where('chave').equals('primeiroAcesso').first();
      setPrimeiroAcesso(config ? config.valor : true);
      setDbReady(true);
    }).catch(error => {
      console.error('Failed to initialize database:', error);
      setDbReady(true);
    });
  }, []);

  const handleSetupComplete = () => {
    setPrimeiroAcesso(false);
    setActiveTab('dashboard');
  };

  const renderContent = () => {
    switch (activeTab) {
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

  if (!dbReady) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-slate-600">Inicializando...</p>
        </div>
      </div>
    );
  }

  if (primeiroAcesso) {
    return <SetupPage onComplete={handleSetupComplete} />;
  }

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab}>
      {renderContent()}
    </Layout>
  );
}

export default App;