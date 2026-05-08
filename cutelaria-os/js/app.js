import { initRouter } from './modules/router.js';

async function startApp() {

  try {

    await initRouter();

  } catch (error) {

    console.error(
      'Erro ao iniciar app:',
      error
    );

    const app = document.getElementById('app');

    if (app) {

      app.innerHTML = `

        <div style="
          padding:40px;
          color:white;
          font-family:Inter,sans-serif;
        ">

          <h1 style="
            font-size:32px;
            margin-bottom:16px;
          ">
            Cutelaria OS
          </h1>

          <p style="color:#94a3b8">
            Erro ao carregar sistema.
          </p>

          <pre style="
            margin-top:20px;
            color:#f97316;
            white-space:pre-wrap;
          ">${error}</pre>

        </div>

      `;

    }

  }

}

window.addEventListener(
  'DOMContentLoaded',
  startApp
);