// ============================================
// NAVBAR — renderiza uma vez, atualiza active
// ============================================

const NAV_ITEMS = [
  { route: 'dashboard',  icon: 'layout-dashboard', label: 'Início' },
  { route: 'producao',   icon: 'hammer',            label: 'Produção' },
  { route: 'pedidos',    icon: 'shopping-bag',      label: 'Pedidos' },
  { route: 'calculadora',icon: 'calculator',        label: 'Calcular' },
  { route: 'materiais',  icon: 'package',           label: 'Materiais' },
  { route: 'financeiro', icon: 'wallet',            label: 'Financeiro' },
  { route: 'config',     icon: 'settings',          label: 'Config' }
];

export function renderNavbar(current = '') {
  return `
    <nav id="bottomNav">
      ${NAV_ITEMS.map(item => `
        <a href="#${item.route}" class="${current === item.route ? 'active' : ''}">
          <i data-lucide="${item.icon}"></i>
          <span>${item.label}</span>
        </a>
      `).join('')}
    </nav>
  `;
}

export function updateNavbarActive(route) {
  const nav = document.getElementById('bottomNav');
  if (!nav) return;
  nav.querySelectorAll('a').forEach(a => {
    const href = a.getAttribute('href').replace('#', '');
    a.classList.toggle('active', href === route);
  });
}
