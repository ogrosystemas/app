import { dashboardPage } from '../pages/dashboard.js';

  try {

    const content = await page();

    app.innerHTML = `

      <main class="page-transition">
        ${content}
      </main>

      ${renderNavbar(hash)}

    `;

    requestAnimationFrame(() => {

      const pageElement = document.querySelector('.page-transition');

      if (pageElement) {

        pageElement.style.opacity = '1';
        pageElement.style.transform = 'translateY(0px)';

      }

      // RECRIAR ÍCONES

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
          margin-bottom:16px;
        ">
          Erro de Renderização
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