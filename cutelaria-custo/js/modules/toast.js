// ============================================
// CUTELARIA CUSTO - TOAST.JS
// Sistema de notificações
// ============================================

const Toast = {
    container: null,
    queue: [],
    isShowing: false,

    init() {
        this.container = document.getElementById('toast');
    },

    show(message, type = 'success', duration = 3000) {
        this.queue.push({ message, type, duration });
        this.processQueue();
    },

    success(message, duration) {
        this.show(message, 'success', duration);
    },

    error(message, duration) {
        this.show(message, 'error', duration || 4000);
    },

    warning(message, duration) {
        this.show(message, 'warning', duration);
    },

    info(message, duration) {
        this.show(message, 'info', duration);
    },

    async processQueue() {
        if (this.isShowing || this.queue.length === 0) return;

        this.isShowing = true;
        const { message, type, duration } = this.queue.shift();

        this.container.textContent = message;
        this.container.className = `toast ${type} show`;

        await this.wait(duration);

        this.container.classList.remove('show');
        await this.wait(300); // Tempo da transição

        this.isShowing = false;
        this.processQueue();
    },

    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
};
