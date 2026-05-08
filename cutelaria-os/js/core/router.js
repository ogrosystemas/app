import { renderNavbar } from '../modules/navbar.js';

import { db } from '../database/db.js';

const routes = {

  onboarding: '../pages/onboarding.js',

  dashboard: '../pages/dashboard.js',

  materiais: '../pages/materiais.js',

  producao: '../pages/producao.js',

  financeiro: '../pages/financeiro.js',

  clientes: '../pages/clientes.js',

  config: '../pages/configuracoes.js'

  calculadora: '../pages/calculadora.js',

};

async function renderRoute() {

  const app =
    document.getElementById('app');

  // SETTINGS

  const settings =
    await db.settings.toArray();

  const onboardingCompleto =
    settings.length > 0;

  let hash =
    window.location.hash
      .replace('#', '');

  // PRIMEIRO ACESSO

  if (!onboardingCompleto) {

    hash = 'onboarding';

  }

  // HASH PADRÃO

  if (!hash) {

    hash = onboardingCompleto
      ? 'dashboard'
      : 'onboarding';

  }

  // ROTA INVÁLIDA

  if (!routes[hash]) {

    hash = onboardingCompleto
      ? 'dashboard'
      : 'onboarding';

  }

  try {

    const module =
      await import(routes[hash]);

    const renderFunction =
      Object.values(module)[0];

    const html =
      await renderFunction();

    const showNavbar =
      hash !== 'onboarding';

    app.innerHTML = `

      <main class="page-transition">

        ${html}

      </main>

      ${
        showNavbar
          ? renderNavbar(hash)
          : ''
      }

    `;

    requestAnimationFrame(() => {

      const page =
        document.querySelector(
          '.page-transition'
        );

      if (page) {

        page.style.opacity = '1';

        page.style.transform =
          'translateY(0px)';

      }

      // LUCIDE

      if (window.lucide) {

        lucide.createIcons();

      }

    });

  } catch (error) {

    console.error(error);

    app.innerHTML = `

      <div class="card">

        <h1 style="
          font-size:28px;
          margin-bottom:20px;
        ">
          Erro no Router
        </h1>

        <pre style="
          color:#f97316;
          white-space:pre-wrap;
        ">${error}</pre>

      </div>

    `;

  }

}

export async function initRouter() {

  window.addEventListener(
    'hashchange',
    renderRoute
  );

  await renderRoute();

}