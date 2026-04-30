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
import { useToast } from '/src/components/Toast.jsx';
import './index.css';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [primeiroAcesso, setPrimeiroAcesso] = useState(true);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const { showToast, ToastComponent } = useToast();

  useEffect(() => {
    const initialize = async () => {
      try {
        await initDatabase();
        const config = await db.config.where('chave').equals('primeiroAcesso').first();
        const isFirstAccess = config ? config.valor === 1 || config.valor === true : true;
        setPrimeiroAcesso(isFirstAccess);
        setDbReady(true);
      } catch (err) {
        console.error('Failed to initialize database:', err);
        setDbReady(true);
      }
    };
    initialize();
  }, []);

  const handleSetupComplete = async () => {
    const existing = await db.config.where('chave').equals('primeiroAcesso').first();
    if (existing) {
      await db.config.where('chave').equals('primeiroAcesso').modify({ valor: 0 });
    } else {
      await db.config.add({ chave: 'primeiroAcesso', valor: 0 });
    }
    setPrimeiroAcesso(false);
    setActiveTab('dashboard');
  };

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <DashboardPage
          onNewBudget={() => setActiveTab('novo')}
          onViewBudget={(id) => {
            setSelectedBudgetId(id);
            setActiveTab('visualizar');
          }}
        />;
      case 'clientes':
        return <ClientesPage showToast={showToast} />;
      case 'catalogo':
        return <ServicosPage showToast={showToast} />;
      case 'financeiro':
        return <ConfiguracoesPage showToast={showToast} />;
      case 'novo':
        return <NovoOrcamento onSave={() => setActiveTab('dashboard')} showToast={showToast} />;
      case 'visualizar':
        return <VisualizarOrcamento
          onBack={() => setActiveTab('dashboard')}
          id={selectedBudgetId}
          showToast={showToast}
        />;
      default:
        return <DashboardPage
          onNewBudget={() => setActiveTab('novo')}
          onViewBudget={(id) => {
            setSelectedBudgetId(id);
            setActiveTab('visualizar');
          }}
        />;
    }
  };

  if (!dbReady) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-slate-600">Carregando...</p>
        </div>
      </div>
    );
  }

  if (primeiroAcesso) {
    return <SetupPage onComplete={handleSetupComplete} showToast={showToast} />;
  }

  return (
    <>
      <Layout activeTab={activeTab} onTabChange={setActiveTab}>
        {renderContent()}
      </Layout>
      {ToastComponent}
    </>
  );
}

export default App;
