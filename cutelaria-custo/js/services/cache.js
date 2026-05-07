// ============================================
// CUTELARIA CUSTO - CACHE.JS
// Cache API para offline
// ============================================

const CacheService = {
    cacheName: APP_CONFIG.cacheName,

    async openCache() {
        return await caches.open(this.cacheName);
    },

    async add(url) {
        const cache = await this.openCache();
        await cache.add(url);
    },

    async addAll(urls) {
        const cache = await this.openCache();
        await cache.addAll(urls);
    },

    async match(request) {
        const cache = await this.openCache();
        return await cache.match(request);
    },

    async put(request, response) {
        const cache = await this.openCache();
        await cache.put(request, response);
    },

    async delete(request) {
        const cache = await this.openCache();
        await cache.delete(request);
    },

    async clear() {
        const cache = await this.openCache();
        const keys = await cache.keys();
        for (const request of keys) {
            await cache.delete(request);
        }
    },

    // Verificar se está online
    isOnline() {
        return navigator.onLine;
    },

    // Registrar listener de status de conexão
    onStatusChange(callback) {
        window.addEventListener('online', () => callback(true));
        window.addEventListener('offline', () => callback(false));
    }
};
