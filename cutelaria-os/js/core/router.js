import { renderNavbar } from '../modules/navbar.js';

const routes = {

  dashboard: '../pages/dashboard.js',
  materiais: '../pages/materiais.js',
  producao: '../pages/producao.js',
  financeiro: '../pages/financeiro.js',
  clientes: '../pages/clientes.js',
  config: '../pages/configuracoes.js'

};

async function renderRoute() {

  const app =
    document.getElementById('app');

  let hash =
    window.location.hash
      .replace('#', '');

  if (!hash) {

    hash = 'dashboard';

  }

  if (!routes[hash]) {

    hash = 'dashboard';

  }

  try {

    const module =
      await import(routes[hash]);

    const renderFunction =
      Object.values(module)[0];

    const html =
      await renderFunction();

    app.innerHTML = `

      <main class="page-transition">

        ${html}

      </main>

      ${renderNavbar(hash)}

    `;

    requestAnimationFrame(() => {

      const page =
        document.querySelector('.page-transition');

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