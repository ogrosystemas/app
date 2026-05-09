export function renderNavbar(current = '') {

  return `

    <nav id="bottomNav">

      <a
        href="#dashboard"
        class="${
          current === 'dashboard'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="layout-dashboard"></i>

        <span>Dashboard</span>

      </a>

      <a
        href="#calculadora"
        class="${
          current === 'calculadora'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="calculator"></i>

        <span>Calcular</span>

      </a>

      <a
        href="#materiais"
        class="${
          current === 'materiais'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="package"></i>

        <span>Materiais</span>

      </a>

      <a
        href="#producao"
        class="${
          current === 'producao'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="hammer"></i>

        <span>Produção</span>

      </a>

      <a
        href="#pedidos"
        class="${
          current === 'pedidos'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="shopping-bag"></i>

        <span>Pedidos</span>

      </a>

      <a
        href="#financeiro"
        class="${
          current === 'financeiro'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="wallet"></i>

        <span>Financeiro</span>

      </a>

      <a
        href="#config"
        class="${
          current === 'config'
            ? 'active'
            : ''
        }"
      >

        <i data-lucide="settings"></i>

        <span>Config</span>

      </a>

    </nav>

  `;

}