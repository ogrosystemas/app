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
import { ToastProvider, useToast } from './context/ToastContext';

function AppContent() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);
  const [showSetup, setShowSetup] = useState(true);
  const [selectedBudgetId, setSelectedBudgetId] = useState(null);
  const { showToast } = useToast();

  useEffect(() => {
    const initialize = async () => {
      try {
        await initDatabase();
        // Lê a flag setupConcluido da tabela config
        const setupFlag = await db.config.get('setupConcluido');
        // Se não existir ou for 0, mostra o setup; se for 1, vai direto para o dashboard
        const precisaSetup = !setupFlag || setupFlag.valor !== 1;
        setShowSetup(precisaSetup);
        setDbReady(true);
      } catch (err) {
        console.error('Erro ao inicializar:', err);
        // Fallback: se der erro, não mostrar setup (evita loop)
        setShowSetup(false);
        setDbReady(true);
      }
    };
    initialize();
  }, []);

  const handleSetupComplete = async () => {
    // Salva a flag como 1 (concluído)
    await db.config.put({ chave: 'setupConcluido', valor: 1 });
    setShowSetup(false);
    setActiveTab('dashboard');
    showToast('Configuração concluída!', 'success');
  };

  const renderContent = () => {
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
        <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600"></div>
        <p className="mt-4">Carregando...</p>
      </div>
    );
  }

  if (showSetup) {
    return <SetupPage onComplete={handleSetupComplete} />;
  }

  return <Layout activeTab={activeTab} onTabChange={setActiveTab}>{renderContent()}</Layout>;
}

function App() {
  return (
    <ToastProvider>
      <AppContent />
    </ToastProvider>
  );
}

export default App;