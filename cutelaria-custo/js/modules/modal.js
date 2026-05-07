// ============================================
// CUTELARIA CUSTO - MODAL.JS
// Sistema de modais
// ============================================

const Modal = {
    overlay: null,
    container: null,
    activeModal: null,
    onCloseCallback: null,

    init() {
        this.overlay = document.getElementById('modal-overlay');
        this.container = document.getElementById('modal-container');

        // Event listeners
        this.overlay.addEventListener('click', () => this.close());

        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.close();
            }
        });
    },

    open(content, options = {}) {
        const { title, large = false, onClose } = options;

        this.onCloseCallback = onClose || null;

        const sizeClass = large ? 'modal-large' : '';

        this.container.innerHTML = `
            <div class="modal ${sizeClass}" id="active-modal">
                <div class="modal-header">
                    <h3>${title || ''}</h3>
                    <button class="modal-close" onclick="Modal.close()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;

        this.activeModal = document.getElementById('active-modal');
        this.overlay.classList.add('active');

        // Pequeno delay para animação
        requestAnimationFrame(() => {
            this.activeModal.classList.add('active');
        });

        // Focar primeiro input
        const firstInput = this.activeModal.querySelector('input, select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 300);
        }
    },

    close() {
        if (!this.activeModal) return;

        this.activeModal.classList.remove('active');
        this.overlay.classList.remove('active');

        setTimeout(() => {
            this.container.innerHTML = '';
            this.activeModal = null;

            if (this.onCloseCallback) {
                this.onCloseCallback();
                this.onCloseCallback = null;
            }
        }, 300);
    },

    // Modal de confirmação
    confirm(message, onConfirm, onCancel) {
        const content = `
            <p style="font-size: 15px; margin-bottom: 20px;">${message}</p>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="Modal._confirmYes()">Sim, confirmar</button>
                <button class="btn btn-secondary" onclick="Modal._confirmNo()">Cancelar</button>
            </div>
        `;

        this._confirmResolve = onConfirm;
        this._confirmReject = onCancel;
        this.open(content, { title: '⚠️ Confirmação' });
    },

    _confirmYes() {
        this.close();
        if (this._confirmResolve) this._confirmResolve();
    },

    _confirmNo() {
        this.close();
        if (this._confirmReject) this._confirmReject();
    }
};
