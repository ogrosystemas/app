import {
  dashboardPage
} from '../pages/dashboard.js';

import {
  producaoPage
} from '../pages/producao.js';

import {
  pedidosPage
} from '../pages/pedidos.js';

import {
  renderNavbar
} from '../components/navbar.js';

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
  // 404
  // ========================================

  if (!page) {

    app.innerHTML = `

      <main class="
        min-h-screen
        pb-32
      ">

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

        ${renderNavbar(route)}

      </main>

    `;

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

    return;

  }

  // ========================================
  // RENDER
  // ========================================

  try {

    const content =
      await page();

    app.innerHTML = `

      <main class="
        min-h-screen
        pb-32
      ">

        ${content}

        ${renderNavbar(route)}

      </main>

    `;

    // ICONS

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

    // SCROLL TOP

    window.scrollTo({

      top: 0,

      behavior: 'smooth'

    });

  } catch (error) {

    console.error(
      error
    );

    app.innerHTML = `

      <main class="
        min-h-screen
        pb-32
      ">

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
            mb-6
          ">

            Verifique o console.

          </p>

        </section>

        ${renderNavbar(route)}

      </main>

    `;

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

  }

}

// ========================================
// INIT ROUTER
// ========================================

export async function initRouter() {

  async function handleRoute() {

    const hash =
      window.location.hash
        .replace('#', '');

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

  await handleRoute();

}