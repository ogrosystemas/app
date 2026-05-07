// ============================================
// CUTELARIA CUSTO - APP.JS
// Inicialização da aplicação
// ============================================

const App = {
    async init() {
        // Splash screen
        this.showSplash();

        try {
            // Inicializar banco de dados
            await Database.init();

            // Carregar configurações
            const config = await Database.getConfiguracoes();
            Formatters.setConfig(config);

            // Inicializar módulos
            Toast.init();
            Modal.init();
            Navbar.init();

            // Inicializar router
            Router.init();

            // Registrar Service Worker
            this.registerSW();

            // Esconder splash
            setTimeout(() => this.hideSplash(), 1000);

        } catch (err) {
            console.error('Erro ao inicializar app:', err);
            Toast.error('Erro ao carregar o app. Tente recarregar.');
        }
    },

    showSplash() {
        const splash = document.getElementById('splash-screen');
        const app = document.getElementById('app');
        splash.style.display = 'flex';
        app.style.display = 'none';
    },

    hideSplash() {
        const splash = document.getElementById('splash-screen');
        const app = document.getElementById('app');

        splash.classList.add('hidden');
        app.style.display = 'flex';

        setTimeout(() => {
            splash.style.display = 'none';
        }, 500);
    },

    registerSW() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('SW registrado:', reg.scope))
                .catch(err => console.log('SW erro:', err));
        }
    }
};

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => App.init());
