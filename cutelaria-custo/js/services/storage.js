// ============================================
// CUTELARIA CUSTO - STORAGE.JS
// localStorage wrapper com namespace
// ============================================

const AppStorage = {
    prefix: 'cutelaria_',

    _key(key) {
        return this.prefix + key;
    },

    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(this._key(key));
            return item ? JSON.parse(item) : defaultValue;
        } catch {
            return defaultValue;
        }
    },

    set(key, value) {
        try {
            localStorage.setItem(this._key(key), JSON.stringify(value));
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    },

    remove(key) {
        localStorage.removeItem(this._key(key));
    },

    clear() {
        Object.keys(localStorage)
            .filter(k => k.startsWith(this.prefix))
            .forEach(k => localStorage.removeItem(k));
    },

    // Dados específicos do app
    getLastPage() {
        return this.get('last_page', 'dashboard');
    },

    setLastPage(page) {
        return this.set('last_page', page);
    },

    getTheme() {
        return this.get('theme', 'dark');
    },

    setTheme(theme) {
        return this.set('theme', theme);
    },

    getTutorialSeen() {
        return this.get('tutorial_seen', false);
    },

    setTutorialSeen() {
        return this.set('tutorial_seen', true);
    }
};
