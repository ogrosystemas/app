import React, { useState, useEffect } from 'react';
import Layout from './components/Layout.jsx';
import DashboardPage from './modules/dashboard/DashboardPage.jsx';
import ClientesPage from './modules/clientes/ClientesPage.jsx';
import ServicosPage from './modules/catalogo/ServicosPage.jsx';
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage.jsx';
import NovoOrcamento from './modules/orcamentos/NovoOrcamento.jsx';
import VisualizarOrcamento from './modules/orcamentos/VisualizarOrcamento.jsx';
import { initDatabase } from './database/db.js';

function App() {
  const [activeTab, setActiveTab] = useState('dashboard');
  const [dbReady, setDbReady] = useState(false);

  useEffect(() => {
    initDatabase().then(() => {
      setDbReady(true);
    }).catch(error => {
      console.error('Failed to initialize database:', error);
      setDbReady(true); // Continue even if DB fails
    });
  }, []);

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
      case 'visualizar':
        return <VisualizarOrcamento onBack={() => setActiveTab('dashboard')} />;
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

  return (
    <Layout activeTab={activeTab} onTabChange={setActiveTab}>
      {renderContent()}
    </Layout>
  );
}

export default App;