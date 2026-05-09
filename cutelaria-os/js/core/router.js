import {
  dashboardPage
} from '../pages/dashboard.js';

import {
  producaoPage
} from '../pages/producao.js';

import {
  pedidosPage
} from '../pages/pedidos.js';

// ========================================
// ROUTES
// ========================================

const routes = {

  dashboard: dashboardPage,

  producao: producaoPage,

  pedidos: pedidosPage

};

// ========================================
// NAVIGATE
// ========================================

export async function navigate(
  route = 'dashboard'
) {

  const app =
    document.getElementById(
      'app'
    );

  if (!app) {

    return;

  }

  const page =
    routes[route];

  // ========================================
  // NOT FOUND
  // ========================================

  if (!page) {

    app.innerHTML = `

      <section class="
        py-24
        text-center
      ">

        <h1 class="
          text-5xl
          font-black
          mb-4
        ">

          404

        </h1>

        <p class="
          text-slate-400
        ">

          Página não encontrada.

        </p>

      </section>

    `;

    return;

  }

  // ========================================
  // RENDER
  // ========================================

  try {

    app.innerHTML =
      await page();

    // ICONS

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

    // SCROLL

    window.scrollTo({

      top: 0,

      behavior: 'smooth'

    });

  } catch (error) {

    console.error(
      error
    );

    app.innerHTML = `

      <section class="
        py-24
        text-center
      ">

        <h1 class="
          text-4xl
          font-black
          mb-4
        ">

          Erro ao carregar página

        </h1>

        <p class="
          text-slate-400
        ">

          Verifique o console.

        </p>

      </section>

    `;

  }

}

// ========================================
// INIT ROUTER
// ========================================

export function initRouter() {

  async function handleRoute() {

    const hash =
      window.location.hash
        .replace('#', '')
        .replace('/', '');

    const route =
      hash || 'dashboard';

    await navigate(
      route
    );

  }

  window.addEventListener(
    'hashchange',
    handleRoute
  );

  handleRoute();

}