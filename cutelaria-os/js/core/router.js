import {
  dashboardPage
} from '../pages/dashboard.js';

import {
  producaoPage
} from '../pages/producao.js';

import {
  pedidosPage
} from '../pages/pedidos.js';

// PÁGINAS EXISTENTES DO PROJETO

import {
  calculadoraPage
} from '../pages/calculadora.js';

import {
  materiaisPage
} from '../pages/materiais.js';

import {
  financeiroPage
} from '../pages/financeiro.js';

import {
  configuracoesPage
} from '../pages/configuracoes.js';

import {
  renderNavbar
} from '../modules/navbar.js';

// ========================================
// ROUTES
// ========================================

const routes = {

  dashboard:
    dashboardPage,

  calculadora:
    calculadoraPage,

  materiais:
    materiaisPage,

  producao:
    producaoPage,

  pedidos:
    pedidosPage,

  financeiro:
    financeiroPage,

  config:
    configuracoesPage

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

    const html =
      await page();

    app.innerHTML = `

      <main class="
        min-h-screen
        pb-32
      ">

        ${html}

        ${renderNavbar(route)}

      </main>

    `;

    // ========================================
    // ICONS
    // ========================================

    if (
      window.lucide
    ) {

      lucide.createIcons();

    }

    // ========================================
    // SCROLL TOP
    // ========================================

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