import { dashboardPage }    from '../pages/dashboard.js';
import { calculadoraPage }  from '../pages/calculadora.js';
import { materiaisPage }    from '../pages/materiais.js';
import { producaoPage }     from '../pages/producao.js';
import { pedidosPage }      from '../pages/pedidos.js';
import { financeiroPage }   from '../pages/financeiro.js';
import { configuracoesPage }from '../pages/configuracoes.js';
import { renderNavbar, updateNavbarActive } from '../modules/navbar.js';

const routes = {
  dashboard:   dashboardPage,
  calculadora: calculadoraPage,
  materiais:   materiaisPage,
  producao:    producaoPage,
  pedidos:     pedidosPage,
  financeiro:  financeiroPage,
  config:      configuracoesPage
};

const pageContent = () => document.getElementById('pageContent');

export async function navigate(route = 'dashboard') {
  const app = document.getElementById('app');
  if (!app) return;

  // Primeira vez: monta estrutura com navbar persistente
  if (!document.getElementById('pageContent')) {
    app.innerHTML = `
      <main>
        <div id="pageContent" class="min-h-screen pb-36"></div>
        ${renderNavbar(route)}
      </main>
    `;
  }

  updateNavbarActive(route);

  const page = routes[route];
  const content = pageContent();

  if (!page) {
    content.innerHTML = `
      <section style="text-align:center;padding:80px 20px">
        <div style="font-size:64px;margin-bottom:16px">404</div>
        <p style="color:var(--muted)">Página não encontrada.</p>
      </section>
    `;
    return;
  }

  try {
    content.innerHTML = await page();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (err) {
    console.error(err);
    content.innerHTML = `
      <section style="text-align:center;padding:80px 20px">
        <div style="font-size:40px;font-weight:900;margin-bottom:12px">Erro</div>
        <p style="color:var(--muted);margin-bottom:16px">Falha ao carregar a página.</p>
        <pre style="text-align:left;font-size:12px;overflow:auto;background:rgba(0,0,0,.3);padding:16px;border-radius:12px">${err}</pre>
      </section>
    `;
  }
}

export function initRouter() {
  function handleRoute() {
    const hash  = window.location.hash.replace('#', '').replace('/', '');
    const route = hash || 'dashboard';
    navigate(route);
  }
  window.addEventListener('hashchange', handleRoute);
  handleRoute();
}
