import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import DashboardPage from './modules/dashboard/DashboardPage'
import ClientesPage from './modules/clientes/ClientesPage'
import ServicosPage from './modules/catalogo/ServicosPage'
import ConfiguracoesPage from './modules/financeiro/ConfiguracoesPage'
import NovoOrcamento from './modules/orcamentos/NovoOrcamento'
import VisualizarOrcamento from './modules/orcamentos/VisualizarOrcamento'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Layout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="clientes" element={<ClientesPage />} />
          <Route path="catalogo" element={<ServicosPage />} />
          <Route path="financeiro" element={<ConfiguracoesPage />} />
          <Route path="orcamentos/novo" element={<NovoOrcamento />} />
          <Route path="orcamentos/:id" element={<VisualizarOrcamento />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}

export default App