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
  const [showSetup, setShowSetup] = useState(true); // começa como true para segurança
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const { showToast, ToastComponent } = useToast();

  useEffect(() => {
    const initialize = async () => {
      try {
        await initDatabase();
        // Verifica se o setup já foi concluído
        const configSetup = await db.config.where('chave').equals('setupConcluido').first();
        const setupFeito = configSetup && configSetup.valor === 1;

        // Se já tem clientes ou orçamentos, considera que já está configurado
        const clientesCount = await db.clientes.count();
        const orcamentosCount = await db.orcamentos.count();
        const possuiDados = clientesCount > 0 || orcamentosCount > 0;

        // Mostra setup apenas se NÃO tiver dados E NÃO tiver a flag de setup concluído
        const precisaSetup = !possuiDados && !setupFeito;

        setShowSetup(precisaSetup);
      } catch (err) {
        console.error('Erro na inicialização:', err);
        // Em caso de erro, assume que não precisa de setup (evita loop)
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