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
  const [setupConcluido, setSetupConcluido] = useState(false);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const { showToast, ToastComponent } = useToast();

  useEffect(() => {
    let timeoutId;
    const initialize = async () => {
      try {
        await initDatabase();
        // Verifica se o setup já foi concluído pela chave no config
        const configSetup = await db.config.where('chave').equals('setupConcluido').first();
        setSetupConcluido(!!configSetup && configSetup.valor === 1);
        setDbReady(true);
      } catch (err) {
        console.error('Erro ao inicializar banco:', err);
        // Se der erro, mesmo assim sai do loading para não travar
        setDbReady(true);
      }
    };
    initialize();
    // Timeout de segurança: após 3 segundos, sai do loading
    timeoutId = setTimeout(() => {
      if (!dbReady) {
        console.warn('Timeout na inicialização, forçando saída do loading');
        setDbReady(true);
      }
    }, 3000);
    return () => clearTimeout(timeoutId);
  }, []);

  const handleSetupComplete = async () => {
    // Marca o setup como concluído no banco
    const existing = await db.config.where('chave').equals('setupConcluido').first();
    if (existing) {
      await db.config.where('chave').equals('setupConcluido').modify({ valor: 1 });
    } else {
      await db.config.add({ chave: 'setupConcluido', valor: 1 });
    }
    setSetupConcluido(true);
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

  if (!setupConcluido) {
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