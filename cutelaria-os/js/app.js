import { registerRoute, loadRoute } from './core/router.js';

import { renderNavbar } from './components/navbar.js';

import { dashboardPage } from './pages/dashboard.js';
import { materiaisPage } from './pages/materiais.js';
import { producaoPage } from './pages/producao.js';
import { historicoPage } from './pages/historico.js';
import { configuracoesPage } from './pages/configuracoes.js';

import './database/db.js';

registerRoute('dashboard', dashboardPage);
registerRoute('materiais', materiaisPage);
registerRoute('producao', producaoPage);
registerRoute('historico', historicoPage);
registerRoute('configuracoes', configuracoesPage);

window.addEventListener('hashchange', loadRoute);

window.addEventListener('DOMContentLoaded', async () => {

  renderNavbar();

  if (!window.location.hash) {
    window.location.hash = 'dashboard';
  }

  await loadRoute();

  // Service Worker
  if ('serviceWorker' in navigator) {
    try {
      await navigator.serviceWorker.register('./sw.js');
      console.log('SW registrado');
    } catch (err) {
      console.error(err);
    }
  }

});