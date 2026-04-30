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
import { useToast } from './components/Toast.jsx';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [showSetup, setShowSetup] = useState(false);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const { showToast, ToastComponent } = useToast();

  useEffect(() => {
    const initialize = async () => {
      try {
        await initDatabase();
        // Verificar se já existe algum cliente para decidir se mostra setup
        const clientesCount = await db.clientes.count();
        const configSetup = await db.config.where('chave').equals('setupConcluido').first();
        const setupJaFeito = configSetup ? configSetup.valor === 1 : false;
        // Mostra setup se não tiver clientes E não tiver setup concluído
        const precisaSetup = clientesCount === 0 && !setupJaFeito;
        setShowSetup(precisaSetup);
      } catch (err) {
        console.error('Erro:', err);
        setShowSetup(false);
      } finally {
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

  if (showSetup) {
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