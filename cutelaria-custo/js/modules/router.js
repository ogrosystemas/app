// ============================================
// CUTELARIA CUSTO - ROUTER.JS
// Navegação SPA (hash router)
// ============================================

const Router = {
    currentPage: 'dashboard',
    params: {},

    init() {
        // Hash change listener
        window.addEventListener('hashchange', () => this.handleRoute());

        // Initial route
        const savedPage = AppStorage.getLastPage();
        const hash = window.location.hash.replace('#', '') || savedPage;
        this.navigate(hash);
    },

    handleRoute() {
        const hash = window.location.hash.replace('#', '');
        const [page, queryString] = hash.split('?');

        if (!page || !PAGES[page]) {
            this.navigate('dashboard');
            return;
        }

        // Parse query params
        this.params = {};
        if (queryString) {
            queryString.split('&').forEach(param => {
                const [key, value] = param.split('=');
                this.params[key] = decodeURIComponent(value);
            });
        }

        this.currentPage = page;
        this.renderPage(page);
    },

    navigate(page, params = {}) {
        if (!PAGES[page]) {
            console.warn(`Página "${page}" não existe`);
            return;
        }

        // Build hash
        let hash = `#${page}`;
        const queryParams = Object.entries(params)
            .map(([k, v]) => `${k}=${encodeURIComponent(v)}`)
            .join('&');

        if (queryParams) {
            hash += `?${queryParams}`;
        }

        window.location.hash = hash;
        AppStorage.setLastPage(page);
    },

    renderPage(page) {
        const mainContent = document.getElementById('main-content');
        const pageConfig = PAGES[page];

        // Update UI
        Navbar.setActivePage(page);
        Navbar.updateTitle(pageConfig.title);

        // Render page content
        let html = '';
        switch (page) {
            case 'dashboard':
                html = DashboardPage.render();
                break;
            case 'materiais':
                html = MateriaisPage.render();
                break;
            case 'insumos':
                html = InsumosPage.render();
                break;
            case 'equipamentos':
                html = EquipamentosPage.render();
                break;
            case 'faca':
                html = FacaPage.render();
                break;
            case 'historico':
                html = HistoricoPage.render();
                break;
            case 'configuracoes':
                html = ConfiguracoesPage.render();
                break;
            default:
                html = DashboardPage.render();
        }

        mainContent.innerHTML = `<div class="page-enter">${html}</div>`;

        // Initialize page scripts
        switch (page) {
            case 'dashboard':
                DashboardPage.init();
                break;
            case 'materiais':
                MateriaisPage.init();
                break;
            case 'insumos':
                InsumosPage.init();
                break;
            case 'equipamentos':
                EquipamentosPage.init();
                break;
            case 'faca':
                FacaPage.init();
                break;
            case 'historico':
                HistoricoPage.init();
                break;
            case 'configuracoes':
                ConfiguracoesPage.init();
                break;
        }

        window.scrollTo(0, 0);
    },

    getParam(key) {
        return this.params[key];
    },

    goBack() {
        window.history.back();
    }
};
