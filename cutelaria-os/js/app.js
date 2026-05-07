import {
  dashboardPage
} from './pages/dashboard.js';

import {
  materiaisPage
} from './pages/materiais.js';

import {
  producaoPage
} from './pages/producao.js';

import {
  configuracoesPage
} from './pages/configuracoes.js';

import {
  orcamentoPage
} from './pages/orcamento.js';

import {
  financeiroPage
} from './pages/financeiro.js';

const app =
  document.getElementById('app');

async function renderRoute() {

  const hash =
    window.location.hash || '#dashboard';

  // ORÇAMENTO

  if (
    hash.startsWith(
      '#orcamento/'
    )
  ) {

    const id =
      hash.split('/')[1];

    app.innerHTML =
      await orcamentoPage(id);

    return;

  }

  const routes = {

    '#dashboard':
      dashboardPage,

    '#materiais':
      materiaisPage,

    '#producao':
      producaoPage,

    '#financeiro':
      financeiroPage,

    '#configuracoes':
      configuracoesPage

  };

  const page =
    routes[hash];

  if (!page) return;

  app.classList.add(
    'page-transition'
  );

  const content =
    await page();

  setTimeout(() => {

    app.innerHTML = content;

    app.classList.remove(
      'page-transition'
    );

  }, 120);

  renderNavbar(hash);

}

function renderNavbar(active) {

  const nav =
    document.getElementById(
      'bottomNav'
    );

  nav.innerHTML = `

    <a
      href="#dashboard"
      class="${active === '#dashboard' ? 'active' : ''}"
    >
      Dashboard
    </a>

    <a
      href="#materiais"
      class="${active === '#materiais' ? 'active' : ''}"
    >
      Materiais
    </a>

    <a
      href="#producao"
      class="${active === '#producao' ? 'active' : ''}"
    >
      Produção
    </a>

    <a
      href="#financeiro"
      class="${active === '#financeiro' ? 'active' : ''}"
    >
      Financeiro
    </a>

    <a
      href="#configuracoes"
      class="${active === '#configuracoes' ? 'active' : ''}"
    >
      Config
    </a>

  `;

}

window.addEventListener(
  'hashchange',
  renderRoute
);

window.addEventListener(
  'load',
  renderRoute
);