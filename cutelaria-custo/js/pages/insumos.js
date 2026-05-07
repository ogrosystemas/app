// ============================================
// CUTELARIA CUSTO - INSUMOS.JS
// Cadastro de insumos (lixas, cola, óleo, etc)
// ============================================

const InsumosPage = {
    data: {
        insumos: [],
        editando: null
    },

    async render() {
        this.data.insumos = await Database.getInsumos();

        return `
            <div class="page-actions">
                <button class="btn btn-primary" onclick="InsumosPage.abrirModal()">
                    ➕ Novo Insumo
                </button>
            </div>
            <div class="lista-cards" id="lista-insumos">
                ${this.renderLista()}
            </div>
        `;
    },

    renderLista() {
        if (this.data.insumos.length === 0) {
            return UI.emptyState('Nenhum insumo cadastrado. Adicione lixas, colas, óleos...', '🧪');
        }

        return this.data.insumos.map(i => UI.itemCard(i, {
            badges: [{ class: Formatters.badgeClass(i.tipo), text: Formatters.tipoInsumo(i.tipo) }],
            extraInfo: [
                { text: `${i.quantidade} ${i.unidade}` },
                { text: Formatters.date(i.dataCompra) },
                i.fornecedor ? { text: i.fornecedor } : null
            ].filter(Boolean),
            onEdit: 'InsumosPage.editar',
            onDelete: 'InsumosPage.excluir'
        })).join('');
    },

    init() {
        // Nada especial
    },

    abrirModal(insumo = null) {
        this.data.editando = insumo ? insumo.id : null;
        const isEdit = !!insumo;

        const content = `
            <form id="form-insumo" onsubmit="InsumosPage.salvar(event)">
                ${UI.formGroup('Nome', `<input type="text" id="ins-nome" placeholder="Ex: Lixa 80" value="${isEdit ? insumo.nome : ''}" required>`)}

                <div class="form-row">
                    ${UI.formGroup('Tipo', UI.selectTipoInsumo('ins-tipo', isEdit ? insumo.tipo : 'lixa'))}
                    ${UI.formGroup('Unidade', UI.selectUnidade('ins-unidade', isEdit ? insumo.unidade : 'un'))}
                </div>

                <div class="form-row">
                    ${UI.formGroup('Quantidade', `<input type="number" id="ins-quantidade" step="0.01" min="0" placeholder="0" value="${isEdit ? insumo.quantidade : ''}" required>`)}
                    ${UI.formGroup('Preço Total (R$)', `<input type="number" id="ins-preco" step="0.01" min="0" placeholder="0,00" value="${isEdit ? insumo.preco : ''}" required>`)}
                </div>

                <div class="form-row">
                    ${UI.formGroup('Data Compra', `<input type="date" id="ins-data" value="${isEdit ? insumo.dataCompra : new Date().toISOString().split('T')[0]}" required>`)}
                    ${UI.formGroup('Fornecedor', `<input type="text" id="ins-fornecedor" placeholder="Nome do fornecedor" value="${isEdit ? (insumo.fornecedor || '') : ''}">`)}
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">💾 ${isEdit ? 'Atualizar' : 'Salvar'}</button>
                    <button type="button" class="btn btn-secondary" onclick="Modal.close()">Cancelar</button>
                </div>
            </form>
        `;

        Modal.open(content, { 
            title: isEdit ? '✏️ Editar Insumo' : '🧪 Novo Insumo',
            onClose: () => { this.data.editando = null; }
        });
    },

    async salvar(e) {
        e.preventDefault();

        const insumo = {
            nome: document.getElementById('ins-nome').value,
            tipo: document.getElementById('ins-tipo').value,
            quantidade: parseFloat(document.getElementById('ins-quantidade').value),
            unidade: document.getElementById('ins-unidade').value,
            preco: parseFloat(document.getElementById('ins-preco').value),
            dataCompra: document.getElementById('ins-data').value,
            fornecedor: document.getElementById('ins-fornecedor').value || null
        };

        try {
            if (this.data.editando) {
                await Database.updateInsumo(this.data.editando, insumo);
                Toast.success('Insumo atualizado!');
            } else {
                await Database.addInsumo(insumo);
                Toast.success('Insumo cadastrado!');
            }

            Modal.close();
            this.data.editando = null;
            await this.refresh();
        } catch (err) {
            Toast.error('Erro ao salvar: ' + err.message);
        }
    },

    async editar(id) {
        const insumo = await Database.getInsumoById(id);
        if (insumo) this.abrirModal(insumo);
    },

    async excluir(id) {
        Modal.confirm('Tem certeza que deseja excluir este insumo?', async () => {
            await Database.deleteInsumo(id);
            Toast.success('Insumo excluído');
            await this.refresh();
        });
    },

    async refresh() {
        this.data.insumos = await Database.getInsumos();
        document.getElementById('lista-insumos').innerHTML = this.renderLista();
    }
};
