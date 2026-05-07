// ============================================
// CUTELARIA CUSTO - NAVBAR.JS
// Menu inferior mobile + side menu
// ============================================

const Navbar = {
    sideMenu: null,
    menuOverlay: null,
    bottomNav: null,

    init() {
        this.sideMenu = document.getElementById('side-menu');
        this.menuOverlay = document.getElementById('menu-overlay');
        this.bottomNav = document.getElementById('bottom-nav');

        // Menu button
        document.getElementById('menu-btn').addEventListener('click', () => this.openSideMenu());
        this.menuOverlay.addEventListener('click', () => this.closeSideMenu());

        // Bottom nav
        this.bottomNav.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.dataset.page;
                if (page) Router.navigate(page);
            });
        });
    },

    openSideMenu() {
        this.sideMenu.classList.add('open');
        this.menuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    closeSideMenu() {
        this.sideMenu.classList.remove('open');
        this.menuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    },

    setActivePage(page) {
        // Side menu
        this.sideMenu.querySelectorAll('.menu-list a').forEach(a => {
            a.classList.toggle('active', a.dataset.page === page);
        });

        // Bottom nav
        this.bottomNav.querySelectorAll('.nav-item').forEach(item => {
            item.classList.toggle('active', item.dataset.page === page);
        });
    },

    updateTitle(title) {
        document.getElementById('page-title').textContent = title;
    }
};
