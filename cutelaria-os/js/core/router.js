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

  '/': dashboardPage,

  '/dashboard': dashboardPage,

  '/producao': producaoPage,

  '/pedidos': pedidosPage

};

// ========================================
// NAVIGATE
// ========================================

export async function navigate(
  path = '/'
) {

  const app =
    document.getElementById(
      'app'
    );

  if (!app) {

    return;

  }

  const page =
    routes[path];

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

  try {

    app.innerHTML =
      await page();

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

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
// HASH ROUTER
// ========================================

export function initRouter() {

  async function handleRoute() {

    const hash =
      window.location.hash
        .replace('#', '');

    const path =
      hash || '/';

    await navigate(path);

  }

  window.addEventListener(
    'hashchange',
    handleRoute
  );

  handleRoute();

}