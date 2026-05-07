const routes = {};

export function registerRoute(name, render) {
  routes[name] = render;
}

export async function loadRoute() {

  const route =
    window.location.hash.replace('#', '') || 'dashboard';

  const view = document.getElementById('view');

  if (!view) return;

  if (routes[route]) {

    const content = await routes[route]();

    view.innerHTML = content;

  } else {

    view.innerHTML = `
      <div class="card">
        <h2>404</h2>
        <p>Página não encontrada.</p>
      </div>
    `;
  }
}