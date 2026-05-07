export function renderNavbar() {
  const navbar = document.getElementById('navbar');

  navbar.innerHTML = `
    <div class="bottom-navbar">

      <button class="nav-item" onclick="location.hash='dashboard'">
        Dashboard
      </button>

      <button class="nav-item" onclick="location.hash='materiais'">
        Materiais
      </button>

      <button class="nav-item" onclick="location.hash='producao'">
        Produção
      </button>

      <button class="nav-item" onclick="location.hash='historico'">
        Histórico
      </button>

      <button class="nav-item" onclick="location.hash='configuracoes'">
        Config
      </button>

    </div>
  `;
}